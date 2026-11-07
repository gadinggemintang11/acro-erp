<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consumable_request_model extends CI_Model {

    private $table_header  = 'consumable_request_header';
    private $table_details = 'consumable_request_details';
    private $table_items   = 'inventories';

    /**
     * Daftar IRF. $limit null = semua baris (perilaku lama); diisi angka utk
     * pagination -- lihat count_all() yang memakai filter yang persis sama.
     */
    public function get_all($filters = [], $limit = null, $offset = 0) {
        $this->_apply_list_filters($filters);
        $this->db->order_by('h.id', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get()->result();
    }

    /**
     * Jumlah baris utk filter yang sama -- dipakai menghitung total halaman.
     * Query-nya dirakit lewat _apply_list_filters() yang sama supaya angka
     * total tidak pernah beda dengan isi tabelnya.
     */
    public function count_all($filters = []) {
        $this->_apply_list_filters($filters);
        return $this->db->count_all_results();
    }

    /**
     * Rakit select + join + semua filter daftar IRF ke query builder aktif.
     */
    private function _apply_list_filters($filters = []) {
        // Kondisi cakupan departemen dihitung SEBELUM query builder dirakit:
        // helper-nya ikut menjalankan query (check_access) yang akan me-reset
        // builder yang sedang setengah jadi.
        $visibility_sql = null;
        if (!empty($filters['visibility'])) {
            $v = $filters['visibility'];
            $user_id = (int)($v['user_id'] ?? 0);
            $conds = [$user_id > 0 ? 'h.created_by = ' . $user_id : '1=0'];
            $colleague = dept_scope_sql('consumable_request', ['edit', 'submit', 'delete'], 'h.department', true);
            if ($colleague !== '1=0') $conds[] = '(' . $colleague . ')';
            $conds[] = approval_stage_scope_sql('consumable_request_approval', 'h.status', 'h.department', self::APPROVAL_STAGE_MAP);
            $conds[] = "h.status IN ('Approved', 'Released', 'Rejected', 'Cancelled')";
            $visibility_sql = '(' . implode(' OR ', $conds) . ')';
        }

        $this->_select_with_audit_joins();

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start()
                ->like('h.request_number', $term)
                ->or_like('h.department', $term)
                ->or_like('h.requestor_name', $term)
                ->group_end();
        }
        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('h.category', $filters['category']);
        }

        // Outstanding Task Filter (ERP Revision Notes, [[erp_revision_irf]] #1):
        // "tampilkan transaksi yang masih Outstanding saja" langsung di IRF
        // List, tanpa pindah ke menu Outstanding terpisah. "Outstanding" cuma
        // bermakna utk header yang sudah 'Approved' (lihat docblock
        // Consumable_outstanding_model) — jadi filter status dropdown yang
        // mungkin dikirim bareng SENGAJA di-override jadi 'Approved' di sini
        // kalau checkbox ini dicentang (kombinasi lain scr konsep tidak valid:
        // Draft/Submitted/dst tidak pernah "outstanding").
        // Query EXISTS ini SENGAJA disalin manual dari
        // Consumable_outstanding_model (bukan reuse method krn beda model &
        // butuh bentuk EXISTS, bukan hasil baris) — kalau formula outstanding
        // berubah lagi, sinkronkan juga di sini & Consumable_request_model::
        // check_outstanding_by_item().
        if (!empty($filters['outstanding_only'])) {
            $this->db->where('h.status', 'Approved');
            $this->db->where("
                EXISTS (
                    SELECT 1 FROM consumable_request_details cr_d2
                    LEFT JOIN (
                        SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                        FROM consumable_out_details co_d
                        INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                        WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                        GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
                    ) issued2 ON issued2.request_id = cr_d2.header_id AND issued2.item_id = cr_d2.item_id AND issued2.item_type = cr_d2.item_type
                    LEFT JOIN (
                        SELECT link.cr_detail_id, SUM(LEAST(link.qty, link.qty * (COALESCE(comp.completed_qty, 0) / pr_d.qty))) AS qty_completed
                        FROM pr_detail_cr_links link
                        INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id AND pr_d.qty > 0
                        LEFT JOIN (
                            SELECT po_d.pr_detail_id, SUM(grn_d.receive_qty) AS completed_qty
                            FROM po_details po_d
                            INNER JOIN grn_details grn_d ON grn_d.source_doc_type = 'PO' AND grn_d.source_doc_detail_id = po_d.id
                            INNER JOIN grn_header grn_h ON grn_h.id = grn_d.grn_header_id
                            WHERE grn_h.warehouse_type = 'jasa' AND grn_h.status = 'Completed'
                            GROUP BY po_d.pr_detail_id
                        ) comp ON comp.pr_detail_id = pr_d.id
                        GROUP BY link.cr_detail_id
                    ) jasa_done2 ON jasa_done2.cr_detail_id = cr_d2.id
                    WHERE cr_d2.header_id = h.id
                    AND (cr_d2.qty_requested - COALESCE(issued2.qty_issued_sum, 0) - COALESCE(jasa_done2.qty_completed, 0)) > 0.0001
                )
            ", null, false);
        }

        // Visibility: IRF sendiri, IRF rekan satu departemen yang boleh dia
        // edit/submit/hapus (mis. Supervisor melihat draft Admin/Clerk), IRF
        // di tahap approval yang boleh dia tangani — semuanya dibatasi
        // departemen (Role Level tahap 3) — plus IRF yang sudah selesai.
        if ($visibility_sql !== null) {
            $this->db->where($visibility_sql, null, false);
        }
    }

    public function get_header($id) {
        $this->_select_with_audit_joins();
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    /**
     * Helper: select clause + join ke users untuk semua kolom audit approval.
     */
    private function _select_with_audit_joins() {
        $this->db->select('
            h.*,
            u.fullname  as created_by_name,
            u2.fullname as approved_by_name,
            us.fullname as submitted_by_name,
            uh.fullname as hod_approver_name,
            ue.fullname as endorser_name,
            uf.fullname as final_approver_name,
            ur.fullname as rejected_by_name,
            urd.fullname as returned_by_name
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u',   'h.created_by = u.id',           'left');
        $this->db->join('users u2',  'h.approved_by = u2.id',         'left');
        $this->db->join('users us',  'h.submitted_by = us.id',        'left');
        $this->db->join('users uh',  'h.hod_approved_by = uh.id',     'left');
        $this->db->join('users ue',  'h.endorsed_by = ue.id',         'left');
        $this->db->join('users uf',  'h.final_approved_by = uf.id',   'left');
        $this->db->join('users ur',  'h.rejected_by = ur.id',         'left');
        $this->db->join('users urd', 'h.returned_to_draft_by = urd.id','left');
    }

    /**
     * List IRF yang menunggu aksi approval user (Head of Dept only).
     */
    /** Status yang ditangani tiap tahap approval (dipakai pembatasan departemen). */
    const APPROVAL_STAGE_MAP = [
        'head_dept'   => ['Submitted'],
        'endorsement' => ['Approved Head Dept'],
        'approval'    => ['Endorsed'],
    ];

    public function get_pending_approvals($can_head_dept, $can_endorsement, $can_approval) {
        $stages = [];
        if ($can_head_dept)   $stages[] = 'Submitted';
        // Stage Endorsement — dulu TIDAK dipetakan sama sekali, jadi pemegang
        // izin endorsement selalu melihat antrian kosong (3-stage-nya memang
        // terpotong di approve_hod()). Dikembalikan 2026-09-10.
        if ($can_endorsement) $stages[] = 'Approved Head Dept';
        // 'Endorsed' = hasil stage Endorsement, ATAU shortcut Johanes yang
        // melompat langsung ke sini saat submit (lihat submit()).
        if ($can_approval)    $stages[] = 'Endorsed';
        $stages = array_values(array_unique($stages));

        if (empty($stages)) return [];

        // Tiap tahap hanya untuk departemen dalam cakupan user (tahap 3):
        // HOD departemennya sendiri, Accounting/Direktur sesuai izin ALL DEPT.
        // Dihitung sebelum builder dirakit (helper menjalankan query sendiri).
        $scope_sql = approval_stage_scope_sql('consumable_request_approval', 'h.status', 'h.department', self::APPROVAL_STAGE_MAP);

        $this->_select_with_audit_joins();
        $this->db->where_in('h.status', $stages);
        $this->db->where($scope_sql, null, false);
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Catat satu aksi approval ke consumable_request_approval_logs.
     * Defensif: kalau tabel log belum dibuat (environment belum migrasi),
     * langsung return supaya alur approval utama tidak ikut gagal.
     *
     * @param int    $header_id
     * @param string $action  submit|approve_hod|endorse|approve_final|reject|return
     * @param int    $actor_id
     * @param string|null $notes  alasan (reject/return) bila ada
     */
    private function _log_action($header_id, $action, $actor_id, $notes = null) {
        if (!$this->db->table_exists('consumable_request_approval_logs')) {
            return;
        }
        $actor = $this->db->select('fullname')->where('id', (int)$actor_id)->get('users')->row();
        $this->db->insert('consumable_request_approval_logs', [
            'header_id'  => (int)$header_id,
            'action'     => $action,
            'actor_id'   => (int)$actor_id,
            'actor_name' => $actor ? $actor->fullname : null,
            'notes'      => ($notes !== null && $notes !== '') ? $notes : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi setelah return/reject). Dicatat sbg action baru 'reply'
     * di tabel log yang sama — kolomnya varchar bebas, tidak perlu migration.
     */
    public function add_reply($header_id, $actor_id, $message) {
        $this->_log_action($header_id, 'reply', $actor_id, $message);
    }

    /**
     * Riwayat aksi approval IRF (kronologis, paling lama dulu).
     */
    public function get_approval_logs($header_id) {
        if (!$this->db->table_exists('consumable_request_approval_logs')) {
            return [];
        }
        return $this->db->select('*')
            ->from('consumable_request_approval_logs')
            ->where('header_id', (int)$header_id)
            ->order_by('id', 'ASC')
            ->get()->result();
    }

    public function get_details($header_id) {
        return $this->db->get_where($this->table_details, ['header_id' => $header_id])->result();
    }

    /**
     * Item type yang boleh direquest lewat IRF.
     * 'inventory' = tabel inventories, 'machine' = tabel machines (Stock Machine),
     * 'asset' = tabel assets (Master Asset), 'jasa' = tabel jasa (Master Jasa).
     * asset/jasa TIDAK PERNAH punya stock_ledger (bukan barang fisik yg
     * digudangkan) — jadi stock-nya selalu 0, otomatis 100% jadi outstanding
     * begitu IRF di-approve (lewat mekanisme outstanding yg sudah ada, TIDAK
     * butuh jalur baru) — pas dgn prinsip "semua asset/jasa lewat PR", lihat
     * [[revision_irf_auto_pr_at_issuance]].
     */
    const ITEM_TYPES = ['inventory', 'machine', 'asset', 'jasa'];

    /**
     * Form mengirim item sebagai string gabungan "type-id" (mis. "machine-15")
     * karena id inventories & machines bisa bentrok. Fungsi ini memecahnya.
     * Nilai lama yang cuma angka tetap dianggap 'inventory' (backward compatible).
     *
     * @return array{item_id:int, item_type:string}
     */
    public static function parse_item_ref($ref) {
        $ref = trim((string) $ref);
        if (strpos($ref, '-') !== false) {
            list($type, $id) = explode('-', $ref, 2);
            $type = trim($type);
            $id   = (int) $id;
            if (in_array($type, self::ITEM_TYPES, true) && $id > 0) {
                return ['item_id' => $id, 'item_type' => $type];
            }
        }
        return ['item_id' => (int) $ref, 'item_type' => 'inventory'];
    }

    /**
     * Resolve satu baris item hasil POST form jadi pasangan item_id + item_type.
     * Menerima dua bentuk: item_id gabungan ("machine-15") atau item_id numerik
     * plus field item_type terpisah.
     *
     * @return array{item_id:int, item_type:string}
     */
    public function resolve_item_ref($item) {
        $raw = trim((string) ($item['item_id'] ?? ''));
        $ref = self::parse_item_ref($raw);
        // Field item_type terpisah hanya dipakai kalau item_id bukan bentuk gabungan,
        // supaya "inventory-3" tidak bisa dibelokkan jadi machine lewat POST.
        if (strpos($raw, '-') === false
            && !empty($item['item_type'])
            && in_array($item['item_type'], self::ITEM_TYPES, true)) {
            $ref['item_type'] = $item['item_type'];
        }
        return $ref;
    }

    public function get_inventory_items() {
        $this->db->select('id, kode_barang, nama_barang, satuan');
        $this->db->from($this->table_items);
        $this->db->where('status', 'Active');
        $this->db->order_by('nama_barang', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Ambil master item sesuai tipe-nya. Output dinormalisasi ke
     * kode_barang / nama_barang / satuan supaya pemanggil tidak perlu tahu
     * tabel asalnya.
     */
    public function get_item_master($item_id, $item_type = 'inventory') {
        $item_id = (int) $item_id;
        if ($item_type === 'machine') {
            $sql = "SELECT id, kode_mesin AS kode_barang, nama_mesin AS nama_barang,
                           COALESCE(satuan, 'UNIT') AS satuan, kategori,
                           'machine' AS item_type
                    FROM machines WHERE id = ?";
        } elseif ($item_type === 'asset') {
            $sql = "SELECT id, kode_asset AS kode_barang, nama_asset AS nama_barang,
                           COALESCE(satuan, 'UNIT') AS satuan, kategori,
                           'asset' AS item_type
                    FROM assets WHERE id = ?";
        } elseif ($item_type === 'jasa') {
            $sql = "SELECT id, kode_jasa AS kode_barang, nama_jasa AS nama_barang,
                           COALESCE(satuan, 'Lot') AS satuan, kategori,
                           'jasa' AS item_type
                    FROM jasa WHERE id = ?";
        } else {
            $sql = "SELECT id, kode_barang, nama_barang, satuan, kategori,
                           'inventory' AS item_type
                    FROM inventories WHERE id = ?";
        }
        $query = $this->db->query($sql, [$item_id]);
        return $query ? $query->row() : null;
    }

    /** @deprecated pakai get_item_master() — dipertahankan untuk kode lama. */
    public function get_inventory_item($item_id) {
        return $this->get_item_master($item_id, 'inventory');
    }

    public function get_inventory_item_with_stock($item_id, $item_type = 'inventory') {
        $this->load->model('Stock_ledger_model');
        $item_id = (int) $item_id;

        if ($item_type === 'machine') {
            $sql = "SELECT m.id, m.kode_mesin AS kode_barang, m.nama_mesin AS nama_barang,
                           COALESCE(m.satuan, 'UNIT') AS satuan, m.kategori,
                           'machine' AS item_type,
                           COALESCE((SELECT balance FROM stock_ledger
                                     WHERE item_id = m.id AND item_type = 'machine'
                                     ORDER BY id DESC LIMIT 1), 0) as stock
                    FROM machines m
                    WHERE m.id = ?";
        } elseif ($item_type === 'asset') {
            // Asset bukan barang gudang — tidak pernah punya stock_ledger,
            // stock selalu 0 (langsung 100% outstanding, lihat catatan ITEM_TYPES).
            $sql = "SELECT a.id, a.kode_asset AS kode_barang, a.nama_asset AS nama_barang,
                           COALESCE(a.satuan, 'UNIT') AS satuan, a.kategori,
                           'asset' AS item_type, 0 as stock
                    FROM assets a
                    WHERE a.id = ?";
        } elseif ($item_type === 'jasa') {
            $sql = "SELECT j.id, j.kode_jasa AS kode_barang, j.nama_jasa AS nama_barang,
                           COALESCE(j.satuan, 'Lot') AS satuan, j.kategori,
                           'jasa' AS item_type, 0 as stock
                    FROM jasa j
                    WHERE j.id = ?";
        } else {
            $sql = "SELECT i.id, i.kode_barang, i.nama_barang, i.satuan, i.kategori,
                           'inventory' AS item_type,
                           COALESCE((SELECT balance FROM stock_ledger
                                     WHERE item_id = i.id AND item_type = 'inventory'
                                     ORDER BY id DESC LIMIT 1), 0) as stock
                    FROM inventories i
                    WHERE i.id = ?";
        }
        $query = $this->db->query($sql, [$item_id]);
        return $query ? $query->row() : null;
    }

    /**
     * Daftar item yang bisa dipilih di IRF: inventories + machines + assets +
     * jasa, masing-masing dengan saldo stok terakhir dari stock_ledger sesuai
     * item_type (assets/jasa selalu stock=0 — bukan barang gudang, lihat
     * catatan ITEM_TYPES). Kolom `item_key` ("type-id") dipakai sebagai value
     * <option> di form.
     *
     * Optgroup Asset & Jasa ditampilkan APAPUN kategori IRF yang dipilih
     * (General/Produksi/Jasa) — sesuai [[revision_irf_auto_pr_at_issuance]]
     * ("4 master jadi optgroup TAMBAHAN", tidak disebutkan perlu difilter per
     * kategori). Asumsi ini bisa diubah kalau user mau lebih ketat nanti
     * (mis. Jasa optgroup cuma muncul kalau kategori='Jasa').
     *
     * @param string $category 'General'/'Produksi'/'Jasa' — General/Produksi
     *        gudang fisik terpisah, jadi stock inventory/machine yang
     *        ditampilkan HARUS sesuai kategori IRF yang sedang dibuat.
     *        Kategori 'Jasa' tidak berpengaruh ke bucket stock apa pun (jasa
     *        tidak punya stock fisik) — dipakai apa adanya sebagai fallback.
     */
    public function get_inventory_items_with_stock($category = 'General') {
        $this->load->model('Stock_ledger_model');
        $cat = $this->db->escape($category);

        // Jenis pemakaian barang (user 2026-10-06): IRF kategori General cuma
        // memunculkan barang General, Produksi cuma barang Produksi. Dulu
        // SEMUA barang muncul di kategori apa pun -- kategori hanya menentukan
        // ember stock mana yang dibaca di subquery saldo di bawah.
        //
        // Dua penjaga:
        //  - kolomnya baru ada setelah migration_inventory_usage_category.sql,
        //    jadi DB yang belum dimigrasi tetap jalan (tampil semua, spt dulu);
        //  - saringan HANYA utk General/Produksi. Kategori 'Jasa' tidak punya
        //    padanan di kolom ini, dan kalau ikut disaring daftarnya jadi
        //    kosong total -- IRF Jasa tetap melihat semua barang.
        $usage_filter = '';
        if (in_array($category, ['General', 'Produksi'], true)
            && function_exists('db_has_column') && db_has_column('inventories', 'usage_category')) {
            $usage_filter = ' AND i.usage_category = ' . $cat;
        }

        $sql = "
            SELECT * FROM (
                SELECT i.id,
                       CONCAT('inventory-', i.id) AS item_key,
                       'inventory' AS item_type,
                       i.kode_barang, i.nama_barang, i.satuan, i.kategori,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = i.id AND item_type = 'inventory' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM inventories i
                WHERE i.status = 'Active'$usage_filter

                UNION ALL

                SELECT m.id,
                       CONCAT('machine-', m.id) AS item_key,
                       'machine' AS item_type,
                       m.kode_mesin AS kode_barang, m.nama_mesin AS nama_barang,
                       COALESCE(m.satuan, 'UNIT') AS satuan, m.kategori,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = m.id AND item_type = 'machine' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM machines m
                WHERE m.status IS NULL OR m.status <> 'Nonaktif'

                UNION ALL

                SELECT a.id,
                       CONCAT('asset-', a.id) AS item_key,
                       'asset' AS item_type,
                       a.kode_asset AS kode_barang, a.nama_asset AS nama_barang,
                       COALESCE(a.satuan, 'UNIT') AS satuan, a.kategori,
                       0 as stock
                FROM assets a
                WHERE a.status = 'active'

                UNION ALL

                SELECT j.id,
                       CONCAT('jasa-', j.id) AS item_key,
                       'jasa' AS item_type,
                       j.kode_jasa AS kode_barang, j.nama_jasa AS nama_barang,
                       COALESCE(j.satuan, 'Lot') AS satuan, j.kategori,
                       0 as stock
                FROM jasa j
                WHERE j.status = 'active'
            ) x
            ORDER BY x.item_type ASC, x.nama_barang ASC";
        $query = $this->db->query($sql);
        $rows = $query ? $query->result() : [];

        // Harga PO terakhir per barang (untuk perkiraan nilai & jalur approval di form).
        $prices = $this->get_last_po_prices();
        foreach ($rows as $r) {
            $p = $prices[$r->item_type . '-' . $r->id] ?? null;
            $r->last_price     = $p ? (float) $p->unit_price : null;
            $r->last_po_number = $p ? $p->po_number : null;
        }
        return $rows;
    }

    // ==========================================================
    // NILAI IRF & JALUR APPROVAL (user 2026-09-15)
    // ==========================================================

    /** Total nilai mulai angka ini -> approval 3 tahap (user: tepat 1.000.000 = 3 tahap). */

    /**
     * Harga satuan dari PO TERAKHIR tiap barang: PO status Confirmed (sudah
     * disetujui) dengan harga > 0, urut tanggal PO terbaru. Harga = local_price
     * (IDR, sebelum PPN).
     *
     * @param array|null $refs [['item_id'=>..,'item_type'=>..], ...] atau null = semua barang
     * @return array ["type-id" => {unit_price, po_detail_id, po_number, po_date}]
     */
    /**
     * Ekspresi SQL harga satuan 1 baris PO, dengan cadangan berurutan
     * (lihat get_last_po_prices()). Dipakai dua fungsi, jadi satu tempat saja.
     */
    private function _po_unit_price_sql() {
        return "COALESCE(
            NULLIF(pd.local_price, 0),
            NULLIF(pd.base_price, 0) * COALESCE(NULLIF(ph.exchange_rate, 0), 1),
            NULLIF(pd.total_local, 0) / NULLIF(pd.qty, 0),
            NULLIF(pd.total, 0) * COALESCE(NULLIF(ph.exchange_rate, 0), 1) / NULLIF(pd.qty, 0)
        )";
    }

    /**
     * Harga dari PO yang BENAR-BENAR lahir dari IRF ini (user 2026-09-16:
     * "misal IRF itu sudah di-PO, pembacaannya IRF itu PO-nya dari mana").
     *
     * Rantainya sama dengan IRF Tracking: consumable_request_details ->
     * pr_detail_cr_links -> pr_details -> po_details -> po_header. Lebih tepat
     * daripada "PO terakhir barang ini" karena ini memang PO untuk permintaan
     * tersebut. Dipakai untuk MENAMPILKAN dokumen yang sudah ada; penentuan
     * jalur approval saat dibuat tetap memakai PO terakhir per barang (waktu
     * IRF dibuat, PO-nya memang belum ada).
     *
     * @return array [cr_detail_id => row(unit_price, po_number, po_date, po_detail_id)]
     */
    public function get_po_prices_for_irf($header_id) {
        $price = $this->_po_unit_price_sql();
        $rows = $this->db->query("
            SELECT d.id AS cr_detail_id, $price AS unit_price, pd.id AS po_detail_id,
                   ph.po_number, ph.po_date
            FROM " . $this->table_details . " d
            JOIN pr_detail_cr_links l ON l.cr_detail_id = d.id
            JOIN pr_details prd ON prd.id = l.pr_detail_id
            JOIN po_details pd ON pd.pr_detail_id = prd.id
            JOIN po_header ph ON ph.id = pd.po_header_id
            WHERE d.header_id = ? AND ph.status IN ('Confirmed', 'Closed') AND $price > 0
            ORDER BY ph.po_date DESC, ph.id DESC, pd.id DESC
        ", [(int) $header_id])->result();

        $map = [];
        foreach ($rows as $r) {
            if (!isset($map[(int) $r->cr_detail_id])) $map[(int) $r->cr_detail_id] = $r;  // PO terbaru
        }
        return $map;
    }

    public function get_last_po_prices($refs = null) {
        $where = '';
        if (is_array($refs)) {
            if (empty($refs)) return [];
            $pairs = [];
            foreach ($refs as $r) {
                $pairs[] = '(pd.item_id = ' . (int) $r['item_id'] . ' AND pd.item_type = ' . $this->db->escape((string) $r['item_type']) . ')';
            }
            $where = ' AND (' . implode(' OR ', array_unique($pairs)) . ')';
        }
        // Harga satuan diambil berurutan dari sumber yang tersedia di baris PO
        // itu (user 2026-09-16: "ada item statusnya belum pernah di PO padahal
        // sudah ada PO nya dan confirmed"):
        //   1. local_price            -> harga satuan dalam Rupiah (jalur normal)
        //   2. base_price x kurs      -> PO mata uang asing yang local_price-nya
        //                               tidak terisi
        //   3. total_local / qty      -> hanya totalnya yang terisi
        //   4. total x kurs / qty     -> total mata uang asing saja
        // Baris yang benar-benar tidak punya angka harga (semuanya 0) tetap
        // dianggap belum pernah di-PO -- nilainya memang tidak diketahui.
        $price_expr = $this->_po_unit_price_sql();
        $rows = $this->db->query("
            SELECT pd.item_id, pd.item_type, $price_expr AS unit_price, pd.id AS po_detail_id,
                   ph.po_number, ph.po_date
            FROM po_details pd
            JOIN po_header ph ON ph.id = pd.po_header_id
            WHERE ph.status IN ('Confirmed', 'Closed') AND $price_expr > 0 $where
            ORDER BY ph.po_date DESC, ph.id DESC, pd.id DESC
        ")->result();
        $map = [];
        foreach ($rows as $r) {
            $key = $r->item_type . '-' . (int) $r->item_id;
            if (!isset($map[$key])) $map[$key] = $r;   // baris pertama = PO terbaru
        }
        return $map;
    }

    /**
     * Isi ulang harga tiap baris dari PO terakhir & hitung total nilai
     * (informasi). Jalur approval selalu '1_stage' (Head of Dept final) sejak
     * 2026-11-05.
     *
     * Dipanggil saat simpan (perkiraan) dan saat submit (dikunci).
     * @return array ['route','total','reason','missing' => [nama barang...]]
     */
    public function refresh_value_and_route($id, $update_route = true) {
        if (!$this->db->field_exists('approval_route', $this->table_header)) {
            return ['route' => '3_stage', 'total' => null, 'reason' => null, 'missing' => []];
        }
        $details = $this->db->query('SELECT id, item_id, item_type, item_name, qty_requested FROM ' . $this->table_details . ' WHERE header_id = ?', [(int) $id])->result();
        $prices  = $this->get_last_po_prices(array_map(function ($d) { return ['item_id' => $d->item_id, 'item_type' => $d->item_type]; }, $details));

        $total = 0.0; $missing = [];
        foreach ($details as $d) {
            $p = $prices[$d->item_type . '-' . (int) $d->item_id] ?? null;
            $this->db->where('id', $d->id)->update($this->table_details, [
                'unit_price'         => $p ? $p->unit_price : null,
                'price_po_detail_id' => $p ? (int) $p->po_detail_id : null,
            ]);
            if ($p) $total += (float) $d->qty_requested * (float) $p->unit_price;
            else    $missing[] = $d->item_name;
        }
        $total = round($total, 2);

        // Sejak 2026-11-05 (rebrand Acropoly) approval Permintaan Barang SELALU
        // 1 tahap: Head of Dept / atasan langsung final. Aturan lama (nilai
        // < Rp 1 jt & semua barang pernah di-PO -> 1 tahap, selain itu 3 tahap;
        // pemohon user ID 37 -> langsung Final) dihapus. Nilai tetap dihitung
        // dari harga PO terakhir sebagai informasi.
        $route  = '1_stage';
        $reason = 'Approval 1 tahap: Head of Dept / atasan';
        $upd = ['total_value' => $total];
        if ($update_route) { $upd['approval_route'] = $route; $upd['approval_route_reason'] = mb_substr($reason, 0, 255); }
        $this->db->where('id', $id)->update($this->table_header, $upd);

        return ['route' => $route, 'total' => $total, 'reason' => $reason, 'missing' => $missing];
    }

    public function generate_request_number($dept_code = null) {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        // $dept_code tidak dipakai lagi: nomor tidak memuat kode departemen.
        return doc_number_next('permintaan_barang', 'consumable_request_header', 'request_number');
    }

    public function create($header_data, $items) {
        $this->db->trans_start();

        $this->db->insert($this->table_header, $header_data);
        $header_id = $this->db->insert_id();

        if (!$header_id) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal menyimpan header.'];
        }

        foreach ($items as $item) {
            $ref       = $this->resolve_item_ref($item);
            $item_id   = $ref['item_id'];
            $item_type = $ref['item_type'];
            $qty = (float)$item['qty'];
            $inv = $this->get_item_master($item_id, $item_type);

            $detail = [
                'header_id' => $header_id,
                'item_id' => $item_id,
                'item_type' => $item_type,
                'item_code' => $inv ? $inv->kode_barang : ($item['item_code'] ?? ''),
                'item_name' => $inv ? $inv->nama_barang : ($item['item_name'] ?? ''),
                'item_unit' => $inv ? $inv->satuan : ($item['item_unit'] ?? ''),
                'qty_requested' => $qty,
                'notes' => $item['notes'] ?? null,
            ];
            $this->db->insert($this->table_details, $detail);
        }

        // Perkiraan nilai & jalur approval (dihitung ulang & dikunci saat submit).
        $this->refresh_value_and_route($header_id);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }

        return [
            'success' => true,
            'id' => $header_id,
            'request_number' => $header_data['request_number'],
        ];
    }

    /**
     * Update IRF (header + items).
     *
     * Strategi item: baris yang barangnya SAMA di-UPDATE di tempat (id-nya
     * dipertahankan), baris baru di-INSERT, baris yang dibuang di-DELETE.
     *
     * Dulu seluruh detail dihapus lalu disisipkan ulang — id barisnya berubah
     * dan `pr_detail_cr_links` (jejak IRF -> PR -> PO) jadi menunjuk baris yang
     * sudah tidak ada. Akibatnya IRF Tracking melaporkan "Belum ada PR" walau
     * PO-nya sudah jalan, dan hitungan "sudah di PR" pada pembuatan PR ikut
     * meleset (user 2026-09-17, kasus IRF ADM00003 vs PO POGNR-000069).
     * Satu IRF tidak boleh memuat barang yang sama dua kali (dicegat
     * Consumable_request::_duplicate_item_error()), jadi pasangan
     * item_type+item_id aman dipakai sebagai kunci pencocokan.
     *
     * @param bool     $bypass_status TRUE = Admin override, boleh ubah walau bukan Draft.
     * @param int|null $actor_id      User yang melakukan edit (untuk audit log override).
     */
    public function update($id, $header_data, $items, $bypass_status = false, $actor_id = null) {
        $header = $this->get_header($id);
        if (!$header) {
            return ['success' => false, 'message' => 'Request tidak ditemukan.'];
        }
        if (!$bypass_status && $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'Hanya request Draft yang bisa diubah.'];
        }
        $is_admin_override = $header->status !== 'Draft';

        $this->db->trans_start();

        $this->db->where('id', $id)->update($this->table_header, $header_data);

        // Baris lama dipetakan per barang supaya id-nya bisa dipakai ulang.
        $existing = [];
        foreach ($this->db->get_where($this->table_details, ['header_id' => $id])->result() as $row) {
            $existing[($row->item_type ?: 'inventory') . '-' . (int) $row->item_id] = (int) $row->id;
        }

        $kept = [];
        foreach ($items as $item) {
            $ref       = $this->resolve_item_ref($item);
            $item_id   = $ref['item_id'];
            $item_type = $ref['item_type'];
            $qty = (float)$item['qty'];
            $inv = $this->get_item_master($item_id, $item_type);

            $detail = [
                'header_id'     => $id,
                'item_id'       => $item_id,
                'item_type'     => $item_type,
                'item_code'     => $inv ? $inv->kode_barang : ($item['item_code'] ?? ''),
                'item_name'     => $inv ? $inv->nama_barang : ($item['item_name'] ?? ''),
                'item_unit'     => $inv ? $inv->satuan : ($item['item_unit'] ?? ''),
                'qty_requested' => $qty,
                'notes'         => $item['notes'] ?? null,
            ];

            $key = $item_type . '-' . (int) $item_id;
            if (isset($existing[$key])) {
                $this->db->where('id', $existing[$key])->update($this->table_details, $detail);
                $kept[] = $existing[$key];
            } else {
                $this->db->insert($this->table_details, $detail);
                $kept[] = (int) $this->db->insert_id();
            }
        }

        // Baris yang dibuang user dihapus belakangan (id yang dipertahankan
        // sudah dikumpulkan di atas).
        if ($kept) {
            $this->db->where('header_id', $id)->where_not_in('id', $kept)->delete($this->table_details);
        } else {
            $this->db->where('header_id', $id)->delete($this->table_details);
        }

        // Harga baris diisi ulang. Jalur approval
        // hanya dihitung ulang selama Draft — sesudah submit jalurnya terkunci.
        $this->refresh_value_and_route($id, !$is_admin_override);

        // Catat jejak audit kalau ini override Admin pada IRF non-Draft.
        if ($is_admin_override && $actor_id) {
            $this->_log_action($id, 'admin_edit', $actor_id, 'Daftar item diedit oleh Admin (status: ' . $header->status . ').');
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat update.'];
        }
        return ['success' => true, 'id' => $id];
    }

    public function submit($id, $user_id) {
        $header = $this->get_header($id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'Request tidak bisa disubmit dalam status ini'];
        }

        // Harga dari PO terakhir & jalur approval dihitung ulang lalu DIKUNCI di
        // sini (user 2026-09-15) — selalu memakai PO terbaru saat submit.
        $route = $this->refresh_value_and_route($id);

        $now    = date('Y-m-d H:i:s');
        $update = [
            'status'       => 'Submitted',
            'submitted_by' => $user_id,
            'submitted_at' => $now,
        ];
        $this->db->where('id', $id)->update($this->table_header, $update);

        $this->_log_action($id, 'submit', $user_id, 'Approval 1 tahap: Head of Dept / atasan');

        return ['success' => true];
    }

    /**
     * 3-stage approval (sama dengan PMR).
     * Stage berhubungan dengan kolom audit:
     * - approve_hod         → status 'Approved Head Dept' (hod_approved_by/at)
     * - approve_endorsement → status 'Endorsed' (endorsed_by/at)
     * - approve_final       → status 'Approved' (final_approved_by/at) + sync ke approved_by/at legacy
     */
    public function approve_hod($id, $user_id) {
        $header = $this->get_header($id);
        if (!$header || $header->status !== 'Submitted') {
            return ['success' => false, 'message' => 'Permintaan Barang hanya dapat di-approve Head of Dept jika status Submitted.'];
        }
        // Sejak 2026-11-05: approval Head of Dept / atasan LANGSUNG FINAL (1 tahap).
        // Kolom legacy approved_by/approved_at = penanda "sudah final" (dibaca modul lain).
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'          => 'Approved',
            'hod_approved_by' => $user_id,
            'hod_approved_at' => $now,
            // Kolom legacy penanda "sudah final" (dibaca modul lain).
            'approved_by'     => $user_id,
            'approved_at'     => $now,
        ]);
        if ($ok) $this->_log_action($id, 'approve_hod', $user_id, 'Final — approval 1 tahap (Head of Dept / atasan)');
        return $ok ? ['success' => true, 'final' => true] : ['success' => false, 'message' => 'Gagal approve.'];
    }

    public function approve_endorsement($id, $user_id) {
        $header = $this->get_header($id);
        if (!$header || $header->status !== 'Approved Head Dept') {
            return ['success' => false, 'message' => 'Permintaan Barang hanya dapat di-endorse jika sudah di-approve Head of Dept.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'      => 'Endorsed',
            'endorsed_by' => $user_id,
            'endorsed_at' => $now,
        ]);
        if ($ok) $this->_log_action($id, 'endorse', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal endorse.'];
    }

    public function approve_final($id, $user_id) {
        $header = $this->get_header($id);
        if (!$header || $header->status !== 'Endorsed') {
            return ['success' => false, 'message' => 'Permintaan Barang hanya dapat di-approve final jika sudah di-endorse.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'            => 'Approved',
            'final_approved_by' => $user_id,
            'final_approved_at' => $now,
            // Sinkron ke kolom legacy supaya kode lama yg baca approved_by/at tetap konsisten.
            'approved_by'       => $user_id,
            'approved_at'       => $now,
        ]);
        if ($ok) $this->_log_action($id, 'approve_final', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal approve final.'];
    }

    /**
     * Return to Draft: status balik ke 'Draft', reset audit approval,
     * creator bisa revisi & submit ulang.
     */
    public function return_to_draft($id, $user_id, $reason) {
        $header = $this->get_header($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            return ['success' => false, 'message' => 'Permintaan Barang tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'               => 'Draft',
            'returned_to_draft_by' => $user_id,
            'returned_to_draft_at' => $now,
            'return_reason'        => $reason,
            // Reset trail approval supaya mulai dari awal.
            'submitted_by'         => NULL,
            'submitted_at'         => NULL,
            'hod_approved_by'      => NULL,
            'hod_approved_at'      => NULL,
            'endorsed_by'          => NULL,
            'endorsed_at'          => NULL,
            'final_approved_by'    => NULL,
            'final_approved_at'    => NULL,
            'approved_by'          => NULL,
            'approved_at'          => NULL,
            'rejected_by'          => NULL,
            'rejected_at'          => NULL,
            'reject_reason'        => NULL,
        ]);
        if ($ok) $this->_log_action($id, 'return', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal return ke Draft.'];
    }

    /**
     * Reject final: status → 'Rejected', tidak bisa di-revisi.
     */
    public function reject($id, $user_id, $reason) {
        $header = $this->get_header($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            return ['success' => false, 'message' => 'Permintaan Barang tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'        => 'Rejected',
            'rejected_by'   => $user_id,
            'rejected_at'   => $now,
            'reject_reason' => $reason,
        ]);
        if ($ok) $this->_log_action($id, 'reject', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal reject Permintaan Barang.'];
    }


    public function delete($id) {
        $this->db->where('id', $id)->delete($this->table_header);
        return true;
    }

    /**
     * Cek apakah ada IRF sebelumnya untuk item yang sama dengan outstanding > 0
     * Validasi: requestor tidak boleh bikin IRF baru untuk item yang outstanding
     */
    public function check_outstanding_by_item($user_id, $item_id, $item_type = 'inventory', $category = null) {
        $category_filter = '';
        $params = [$user_id, $item_id, $item_type];
        if (!empty($category)) {
            $category_filter = ' AND cr_h.category = ?';
            $params[] = $category;
        }
        // Item Jasa (ERP Revision Notes 2026-09-03, [[revision_irf_auto_pr_at_issuance]])
        // tidak pernah punya baris di consumable_out_details — tidak ada
        // gudang yang "mengeluarkan" jasa. Penyelesaiannya ditandai di level
        // PO (Purchase_order_jasa::complete_service() -> grn_header
        // warehouse_type='jasa'), ditelusuri balik ke cr_detail lewat
        // pr_detail_cr_links. Tanpa join jasa_done ini, IRF Jasa yang
        // service-nya sudah selesai akan tetap dianggap outstanding selamanya
        // dan requestor tidak akan pernah bisa bikin IRF baru utk item yang
        // sama. Lihat db/migration_pr_detail_cr_links.sql.
        $jasa_done_sql = "
            SELECT
                link.cr_detail_id,
                SUM(LEAST(link.qty, link.qty * (COALESCE(comp.completed_qty, 0) / pr_d.qty))) AS qty_completed
            FROM pr_detail_cr_links link
            INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id AND pr_d.qty > 0
            LEFT JOIN (
                SELECT po_d.pr_detail_id, SUM(grn_d.receive_qty) AS completed_qty
                FROM po_details po_d
                INNER JOIN grn_details grn_d ON grn_d.source_doc_type = 'PO' AND grn_d.source_doc_detail_id = po_d.id
                INNER JOIN grn_header grn_h ON grn_h.id = grn_d.grn_header_id
                WHERE grn_h.warehouse_type = 'jasa' AND grn_h.status = 'Completed'
                GROUP BY po_d.pr_detail_id
            ) comp ON comp.pr_detail_id = pr_d.id
            GROUP BY link.cr_detail_id
        ";
        $sql = "
            SELECT cr_h.id, cr_h.request_number, cr_h.status,
                SUM(cr_d.qty_requested) AS total_requested,
                (COALESCE(SUM(issued.qty_issued_sum), 0) + COALESCE(SUM(jasa_done.qty_completed), 0)) AS total_issued,
                COALESCE(SUM(pending.qty_pending_sum), 0) AS total_pending,
                (SUM(cr_d.qty_requested) - COALESCE(SUM(issued.qty_issued_sum), 0) - COALESCE(SUM(jasa_done.qty_completed), 0) - COALESCE(SUM(pending.qty_pending_sum), 0)) AS total_outstanding
            FROM consumable_request_header cr_h
            INNER JOIN consumable_request_details cr_d ON cr_d.header_id = cr_h.id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_h.id AND issued.item_id = cr_d.item_id
                    AND issued.item_type = cr_d.item_type
            LEFT JOIN ($jasa_done_sql) jasa_done ON jasa_done.cr_detail_id = cr_d.id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_pending_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Created' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) pending ON pending.request_id = cr_h.id AND pending.item_id = cr_d.item_id
                     AND pending.item_type = cr_d.item_type
            WHERE cr_h.created_by = ?
                AND cr_d.item_id = ?
                AND cr_d.item_type = ?
                AND cr_h.status = 'Approved'
                $category_filter
            GROUP BY cr_h.id
            HAVING total_outstanding > 0
        ";
        $query = $this->db->query($sql, $params);
        return $query ? $query->row() : NULL;
    }

    // ============================================================
    // ATTACHMENTS — "Bukti Penerimaan" oleh requestor (ERP Revision Notes
    // 2026-09-02, lanjutan Fase 2 poin 8). Beda dgn "Bukti Pengeluaran" di
    // Consumable_out/Material_issuance (itu punya gudang) — ini punya
    // requestor, dilekatkan di IRF, muncul begitu status = 'Released'.
    // Pola sama persis dgn erf_attachments.
    // ============================================================

    public function get_attachments($header_id) {
        return $this->db->where('consumable_request_header_id', $header_id)
            ->order_by('id', 'DESC')->get('consumable_request_attachments')->result();
    }

    public function get_attachment($id) {
        return $this->db->where('id', $id)->get('consumable_request_attachments')->row();
    }

    public function add_attachment($data) {
        $this->db->insert('consumable_request_attachments', $data);
        return $this->db->insert_id();
    }

    public function delete_attachment($id) {
        return $this->db->where('id', $id)->delete('consumable_request_attachments');
    }
}
