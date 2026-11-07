<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_model extends CI_Model {

    // --- Definisi Nama Tabel (Sesuaikan jika perlu) ---
    private $table_header = 'po_header';
    private $table_details = 'po_details';
    private $table_supplier = 'suppliers';
    private $table_common = 'common_code'; 
    private $table_sc = 'sales_contracts';
    private $table_materials = 'materials';
    private $table_semifinished = 'semifinished_goods';
    private $table_machines = 'machines';     
    private $table_inventories = 'inventories';
    private $table_jasa = 'jasa';
    private $table_asset = 'assets';
    private $table_grn_details = 'grn_details';
    private $table_grn_header = 'grn_header';
    // --- Akhir Definisi ---

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // === HEADER ===

    public function get_all_po($filters = []) {
        // Backward compat: caller may pass a string type instead of filter array.
        if (is_string($filters)) { $filters = ['type' => $filters]; }
        if (!is_array($filters)) { $filters = []; }

        // ppn_rate & ppn_inclusive WAJIB ikut: po_ppn_from_header() di bawah
        // memakainya. Tanpa ini totalnya jatuh jadi sama dgn subtotal.
        $this->db->select('h.id, h.po_number, h.po_date, h.status, h.has_ppn, h.ppn_rate, '
            . (db_has_column('po_header', 'ppn_inclusive') ? 'h.ppn_inclusive, ' : '')
            . 'h.type, s.nama_supplier,
            COALESCE(SUM(d.total_local), 0) as subtotal');
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_supplier . ' s', 'h.supplier_id = s.id', 'left');
        $this->db->join($this->table_details . ' d', 'h.id = d.po_header_id', 'left');
        if (!empty($filters['type'])) {
            // GMPO (General Material) sekarang jadi 1 menu gabungan utk 3
            // po_type (non_produksi/jasa/asset) -- user 2026-09-05 minta
            // sidebar Jasa & Asset digabung ke bawah GMPO. Terima array
            // ATAU string tunggal (backward compat, dipakai Produksi/PMPO).
            if (is_array($filters['type'])) {
                $this->db->where_in('h.type', $filters['type']);
            } else {
                $this->db->where('h.type', $filters['type']);
            }
        }
        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $this->db->group_start()
                ->like('h.po_number', $term)
                ->or_like('s.nama_supplier', $term)
                ->group_end();
        }
        $this->db->group_by('h.id, h.po_number, h.po_date, h.status, h.has_ppn, h.ppn_rate, '
            . (db_has_column('po_header', 'ppn_inclusive') ? 'h.ppn_inclusive, ' : '')
            . 'h.type, s.nama_supplier');
        $this->db->order_by('h.id', 'DESC');
        
        $result = $this->db->get()->result();
        
        // Total = subtotal + PPN, lewat po_ppn_breakdown() supaya persentase &
        // include/exclude-nya ikut. Dulu dipatok 1.11 di sini walau ppn_rate
        // sudah ada dan terisi 10 maupun 11 (klien 2026-10-02).
        foreach ($result as $row) {
            $row->total_amount = po_ppn_from_header($row, $row->subtotal)['total'];
        }
        
        return $result;
    }

    public function get_po_header_by_id($id) {
        $this->db->select('
            h.*, s.nama_supplier, s.contact_person AS supplier_contact_person, s.alamat, s.telepon,
            cc_ship.description as shipping_mode_desc,
            cc_pay.description as payment_mode_desc,
            cc_curr.description as currency_desc,
            u_confirm.fullname as confirmer_name,
            u_confirm.signature_path as confirmer_signature,
            u_create.fullname as creator_name,
            u_create.signature_path as creator_signature
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_supplier . ' s', 'h.supplier_id = s.id', 'left');
        $this->db->join($this->table_common . ' cc_ship', "h.shipping_mode_code = cc_ship.code AND cc_ship.group_code = 'SHIPPING_MODE'", 'left');
        $this->db->join($this->table_common . ' cc_pay', "h.payment_mode_code = cc_pay.code AND cc_pay.group_code = 'PAYMENT_MODE'", 'left');
        $this->db->join($this->table_common . ' cc_curr', "h.currency_code = cc_curr.code AND cc_curr.group_code = 'CURRENCY'", 'left');
        $this->db->join('users u_confirm', 'h.confirmed_by = u_confirm.id', 'left');
        $this->db->join('users u_create', 'h.created_by = u_create.id', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        if (empty($data['status'])) { $data['status'] = 'Draft'; }
        if (empty($data['type'])) { $data['type'] = 'produksi'; }

        // If po_number is not provided or empty, auto-generate it
        if (empty($data['po_number'])) {
            $data['po_number'] = $this->generate_po_number($data['type']);
        } else {
            // Check if manual po_number already exists
            if ($this->check_po_number_exists($data['po_number'])) {
                log_message('error', 'Po_model::insert_header() - PO number already exists: ' . $data['po_number']);
                return false;
            }
        }
        
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    /**
     * Update header PO.
     *
     * Guard status ikut ke WHERE (bukan cuma dicek di controller) supaya request
     * yang balapan tidak bisa menyelinapkan perubahan setelah PO pindah status.
     * Default tetap Draft-only; controller mengirim status lain hanya untuk
     * override Admin (edit PO yang sudah Submitted/Confirmed).
     *
     * status/confirmed_by/confirmed_at tetap di-unset: edit Admin TIDAK mereset
     * approval, PO yang sudah di-approve tetap approved tanpa perlu approve ulang.
     *
     * @param array $allowed_statuses status yang boleh diupdate
     */
    public function update_header($id, $data, $allowed_statuses = ['Draft']) {
        // DEBUG: Log data array and unsets
        log_message('debug', '[Po_model::update_header] Data before unset: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        unset($data['status'], $data['confirmed_by'], $data['confirmed_at'], $data['po_number'], $data['created_by'], $data['supplier_id']);

        log_message('debug', '[Po_model::update_header] Data after unset: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        log_message('debug', '[Po_model::update_header] Updating ID='.$id.' with status check = ' . implode('/', $allowed_statuses));

        $this->db->where('id', $id)->where_in('status', $allowed_statuses);
        $this->db->update($this->table_header, $data);

        $affected = $this->db->affected_rows();
        log_message('debug', '[Po_model::update_header] Affected rows: ' . $affected);

        return $affected > 0;
    }

    public function delete_header($id) {
        $details_count = $this->db->where('po_header_id', $id)->count_all_results($this->table_details);
        if ($details_count > 0) { return false; }
        $this->db->where('id', $id)->where('status', 'Draft');
        $this->db->delete($this->table_header);
        return $this->db->affected_rows() > 0;
    }

    public function generate_po_number($type = 'produksi') {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        $key = ['jasa' => 'po_jasa', 'asset' => 'po_asset', 'non_produksi' => 'po_general'][$type] ?? 'po_material';
        return doc_number_next($key, 'po_header', 'po_number');
    }

    public function check_po_number_exists($po_number) {
        $this->db->where('po_number', $po_number);
        $query = $this->db->get($this->table_header, 1);
        return $query->num_rows() > 0;
    }

    public function update_status($id, $new_status, $userId = NULL, $reset_approval = FALSE) {
        $data = ['status' => $new_status];
        if ($new_status == 'Confirmed' && $userId) {
            $data['confirmed_by'] = $userId;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($new_status, ['Draft', 'Submitted']) || $reset_approval) {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        }

        // History: log 'Submit' di sini (bukan di controller) supaya konsisten
        // ke manapun update_status() dipanggil (base/general/jasa/asset PO
        // semua share method ini). Cuma utk submit BENERAN dari Draft — bukan
        // saat unconfirm_po() balikin status ke 'Submitted' lagi (dibedakan
        // via $reset_approval, TRUE khusus dipakai unconfirm_po()).
        if ($new_status === 'Submitted' && !$reset_approval) {
            $this->_log($id, 'Submit', (int) $userId);
        }

        // PO memang 1-STAGE approval (ERP Revision Notes 2026-09-02, Fase 2
        // poin 7): cuma ada 1 gate, yaitu Final Approval.
        //
        // KOREKSI 2026-09-09 (user: "kenapa ttd otomatis terisi dan statusnya
        // ketika submit langsung endorse, harusnya ya cuma submit dan
        // approved"). Cara lama mencapai "1 stage" itu dengan MEMALSUKAN dua
        // stage yang dilewati: begitu Submit, status ditulis 'Endorsed' dan
        // kolom hod_approved_by/endorsed_by diisi id CREATOR sendiri sebagai
        // penanda "dilewati sistem". Akibatnya:
        //   1. PO yang belum di-approve siapa pun sudah berstatus 'Endorsed',
        //   2. jejak approval mencatat si pembuat seolah-olah me-review
        //      dokumennya sendiri (2 tanda tangan palsu per PO — 53 baris
        //      lama di DB kena ini),
        //   3. di layar & cetakan seolah dokumennya sudah jalan 2 tahap.
        //
        // Sekarang Submit ya cuma jadi 'Submitted'. Gate tunggalnya tetap satu,
        // tapi dipindah ke tempat yang benar: approve_final() & antrian
        // approval sekarang berangkat dari status 'Submitted' (lihat
        // get_pending_approvals() dan approve_final() di bawah). Tidak ada lagi
        // kolom approval yang diisi tanpa ada orang yang benar-benar menekan
        // tombol approve.
        //
        // 'Endorsed' TETAP diterima di mana-mana supaya PO lama yang terlanjur
        // berstatus itu tidak nyangkut.

        $this->db->where('id', $id);
        $update_result = $this->db->update($this->table_header, $data);
        return ($update_result && $this->db->affected_rows() > 0);
    }

    // ============================================================
    // 3-STAGE APPROVAL (mirror IRF/PMR pattern)
    // Flow: Draft → Submitted → Approved Head Dept → Endorsed → Confirmed
    // Final stage = 'Confirmed' supaya downstream (GRN, dll) tetap kompatibel.
    // ============================================================

    /**
     * List PO pending approval filtered by user's permission tier + PO type.
     * @param bool   $can_hod
     * @param bool   $can_endorsement
     * @param bool   $can_approval
     * @param string $type 'produksi'|'non_produksi'|null
     */
    public function get_pending_approvals($can_hod, $can_endorsement, $can_approval, $type = null) {
        $stages = [];
        if ($can_hod)         $stages[] = 'Submitted';
        if ($can_endorsement) $stages[] = 'Approved Head Dept';
        // Gate tunggal PO: pemegang izin 'approval' yang menangani PO baru
        // disubmit. 'Endorsed' ikut supaya PO lama (hasil lompatan otomatis
        // sebelum 2026-09-09) tetap muncul di antrian dan bisa diselesaikan.
        if ($can_approval)  { $stages[] = 'Submitted'; $stages[] = 'Endorsed'; }
        $stages = array_values(array_unique($stages));
        if (empty($stages)) return [];

        $this->db->select('h.id, h.po_number, h.po_date, h.status, h.type, h.has_ppn,
            s.nama_supplier, u.fullname as created_by_name,
            COALESCE(SUM(d.total_local), 0) as subtotal'
            . ', h.ppn_rate'
            . (db_has_column('po_header', 'ppn_inclusive') ? ', h.ppn_inclusive' : ''));
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_supplier . ' s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users u', 'h.created_by = u.id', 'left');
        $this->db->join($this->table_details . ' d', 'h.id = d.po_header_id', 'left');
        $this->db->where_in('h.status', $stages);
        if (!empty($type)) {
            // GMPO gabungan (non_produksi/jasa/asset) -- lihat catatan sama
            // di get_all_po().
            if (is_array($type)) {
                $this->db->where_in('h.type', $type);
            } else {
                $this->db->where('h.type', $type);
            }
        }
        $this->db->group_by('h.id, h.po_number, h.po_date, h.status, h.type, h.has_ppn, h.ppn_rate, '
            . (db_has_column('po_header', 'ppn_inclusive') ? 'h.ppn_inclusive, ' : '')
            . 's.nama_supplier, u.fullname');
        $this->db->order_by('h.id', 'DESC');
        $result = $this->db->get()->result();

        foreach ($result as $row) {
            $row->total_amount = po_ppn_from_header($row, $row->subtotal)['total'];
        }
        return $result;
    }

    public function approve_hod($po_id, $user_id) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || $header->status !== 'Submitted') {
            return ['success' => false, 'message' => 'PO hanya bisa di-approve Head of Dept jika status Submitted.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'          => 'Approved Head Dept',
            'hod_approved_by' => $user_id,
            'hod_approved_at' => $now,
        ]);
        if ($ok) $this->_log($po_id, 'Approve HOD', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal approve HOD.'];
    }

    public function approve_endorsement($po_id, $user_id) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || $header->status !== 'Approved Head Dept') {
            return ['success' => false, 'message' => 'PO hanya bisa di-endorse jika sudah di-approve Head of Dept.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'      => 'Endorsed',
            'endorsed_by' => $user_id,
            'endorsed_at' => $now,
        ]);
        if ($ok) $this->_log($po_id, 'Endorse', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal endorse.'];
    }

    public function approve_final($po_id, $user_id) {
        $header = $this->get_po_header_by_id($po_id);
        // 'Submitted' = alur normal PO 1-stage. 'Endorsed' = PO lama yang
        // terlanjur dilompatkan otomatis sebelum 2026-09-09.
        if (!$header || !in_array($header->status, ['Submitted', 'Endorsed'], true)) {
            return ['success' => false, 'message' => 'PO hanya bisa di-approve kalau statusnya masih Submitted.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'            => 'Confirmed',
            'final_approved_by' => $user_id,
            'final_approved_at' => $now,
            // Sinkron ke kolom legacy `confirmed_by/at` biar kode lama tetap konsisten.
            'confirmed_by'      => $user_id,
            'confirmed_at'      => $now,
        ]);
        if ($ok) $this->_log($po_id, 'Approve Final', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal approve final.'];
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes, [[erp_revision_cross_cutting]])
     * — replikasi persis pola IRF/PRF/ERF: requestor & approver bolak-balik
     * reply SELAMA status masih Draft (window revisi setelah return),
     * tertutup lagi begitu di-submit ulang. Delegasi ke _log() yang sudah
     * ada (action 'Reply', dilebarkan via db/migration_po_back_to_draft_reply.sql).
     */
    public function add_reply($po_id, $actor_id, $message) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'Balasan cuma bisa dikirim selagi PO berstatus Draft (baru dikembalikan).'];
        }
        $this->_log($po_id, 'Reply', $actor_id, $message);
        return ['success' => true];
    }

    public function return_to_draft($po_id, $user_id, $reason) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || !in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return ['success' => false, 'message' => 'PO tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'               => 'Draft',
            'returned_to_draft_by' => $user_id,
            'returned_to_draft_at' => $now,
            'return_reason'        => $reason,
            // Reset audit trail biar mulai dari awal.
            'submitted_by'         => NULL,
            'submitted_at'         => NULL,
            'hod_approved_by'      => NULL,
            'hod_approved_at'      => NULL,
            'endorsed_by'          => NULL,
            'endorsed_at'          => NULL,
            'final_approved_by'    => NULL,
            'final_approved_at'    => NULL,
            'confirmed_by'         => NULL,
            'confirmed_at'         => NULL,
            'rejected_by'          => NULL,
            'rejected_at'          => NULL,
            'reject_reason'        => NULL,
        ]);
        if ($ok) $this->_log($po_id, 'Return to Draft', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal return ke Draft.'];
    }

    public function reject_approval($po_id, $user_id, $reason) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || !in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return ['success' => false, 'message' => 'PO tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'        => 'Rejected',
            'rejected_by'   => $user_id,
            'rejected_at'   => $now,
            'reject_reason' => $reason,
        ]);
        if ($ok) $this->_log($po_id, 'Reject', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal reject PO.'];
    }

    public function submit_for_approval($po_id, $user_id) {
        $header = $this->get_po_header_by_id($po_id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'PO hanya bisa di-submit jika status Draft.'];
        }
        $ok = $this->db->where('id', $po_id)->update($this->table_header, [
            'status'       => 'Submitted',
            'submitted_by' => $user_id,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal submit PO.'];
    }

    // === DETAILS ===

    public function get_po_details_by_id($po_id) {
        $select_clause = "
            d.id, d.po_header_id, d.item_id, d.item_type, d.description, d.qty,
            d.base_price, d.local_price, d.total_local, d.sales_contract_id,
            d.pr_header_id, d.pr_detail_id, pr.pr_number, prd.qty AS pr_qty,
            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'semifinished' THEN sf.kode_barang
                WHEN d.item_type = 'machine' THEN mc.kode_mesin
                WHEN d.item_type = 'inventory' THEN i.kode_barang
                WHEN d.item_type = 'jasa' THEN j.kode_jasa
                WHEN d.item_type = 'asset' THEN a.kode_asset
            END as item_code,
            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'semifinished' THEN sf.nama_barang
                WHEN d.item_type = 'machine' THEN mc.nama_mesin
                WHEN d.item_type = 'inventory' THEN i.nama_barang
                WHEN d.item_type = 'jasa' THEN j.nama_jasa
                WHEN d.item_type = 'asset' THEN a.nama_asset
            END as item_name,
            CASE
                WHEN d.item_type = 'material' THEN COALESCE(m.satuan, 'pcs')
                WHEN d.item_type = 'semifinished' THEN COALESCE(sf.satuan, 'pcs')
                WHEN d.item_type = 'inventory' THEN COALESCE(i.satuan, 'pcs')
                WHEN d.item_type = 'machine' THEN COALESCE(mc.satuan, 'Unit')
                WHEN d.item_type = 'jasa' THEN COALESCE(j.satuan, 'Lot')
                WHEN d.item_type = 'asset' THEN COALESCE(a.satuan, 'UNIT')
                ELSE 'pcs'
            END as item_unit,
            COALESCE(m.nama_material, sf.nama_barang, mc.nama_mesin, i.nama_barang, j.nama_jasa, a.nama_asset, '') as item_name_for_translation,
            sc.sc_number
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_materials . ' m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join($this->table_semifinished . ' sf', 'd.item_id = sf.id AND d.item_type = "semifinished"', 'left');
        $this->db->join($this->table_machines . ' mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join($this->table_inventories . ' i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join($this->table_jasa . ' j', 'd.item_id = j.id AND d.item_type = "jasa"', 'left');
        $this->db->join($this->table_asset . ' a', 'd.item_id = a.id AND d.item_type = "asset"', 'left');
        $this->db->join($this->table_sc . ' sc', 'd.sales_contract_id = sc.id', 'left');
        $this->db->join('pr_header pr', 'd.pr_header_id = pr.id', 'left');
        $this->db->join('pr_details prd', 'd.pr_detail_id = prd.id', 'left');
        $this->db->where('d.po_header_id', $po_id);
        $this->db->order_by('d.id', 'ASC'); 
        return $this->db->get()->result();
    }

    public function add_item($data) {
        if (empty($data['po_header_id']) || empty($data['item_id'])) {
            log_message('error', 'Po_model::add_item() - Missing po_header_id or item_id.');
            return false;
        }
        if (strpos($data['item_id'], '-') !== false) {
             list($type, $id) = explode('-', $data['item_id']);
             $numeric_item_id = (int)$id; 
             if ($numeric_item_id <= 0) {
                 log_message('error', 'Po_model::add_item() - Invalid numeric ID after explode: ' . $data['item_id']);
                 return false; 
             }
             $data['item_id'] = $numeric_item_id; 
        } else {
             log_message('error', 'Po_model::add_item() - Invalid item_id format received: ' . $data['item_id']);
             return false;
        }
        if ($this->db->insert($this->table_details, $data)) {
            return $this->db->insert_id();
        } else {
             log_message('error', 'Po_model::add_item() - DB Insert failed: ' . print_r($this->db->error(), true));
            return false;
        }
    }

    public function delete_item($id) {
        $this->db->where('id', $id);
        $this->db->delete($this->table_details);
        return $this->db->affected_rows() > 0;
    }

    public function check_existing_item($po_id, $item_id, $item_type) {
        return $this->db->where('po_header_id', $po_id)
                        ->where('item_id', $item_id)
                        ->where('item_type', $item_type)
                        ->get($this->table_details)->row();
    }

    public function update_item_qty($detail_id, $qty) {
        return $this->db->where('id', $detail_id)
                        ->update($this->table_details, ['qty' => $qty, 'total_local' => 0, 'total' => 0]);
    }

    public function insert_item_direct($data) {
        if ($this->db->insert($this->table_details, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function search_all_items($term, $only_type = null) {
        $term = $this->db->escape_like_str(trim($term));
        if (empty($term)) { return []; }
        $limit = 15;

        // PO Jasa hanya boleh pilih dari master jasa — short-circuit ke satu tabel saja
        // (tidak mengubah perilaku pemanggil lama yang tidak mengirim $only_type).
        if ($only_type === 'jasa') {
            $this->db->select("j.id, j.kode_jasa as code, j.nama_jasa as name, j.satuan as unit, 'jasa' as item_type");
            $this->db->from($this->table_jasa . ' j');
            $this->db->where('j.status', 'active');
            $this->db->group_start()->like('j.kode_jasa', $term)->or_like('j.nama_jasa', $term)->group_end();
            $this->db->limit($limit);
            $query = $this->db->get();
            return $query ? $query->result() : [];
        }

        // Idem untuk PO Asset: hanya dari master asset.
        if ($only_type === 'asset') {
            $this->db->select("a.id, a.kode_asset as code, a.nama_asset as name, a.satuan as unit, 'asset' as item_type");
            $this->db->from($this->table_asset . ' a');
            $this->db->where('a.status', 'active');
            $this->db->group_start()->like('a.kode_asset', $term)->or_like('a.nama_asset', $term)->group_end();
            $this->db->limit($limit);
            $query = $this->db->get();
            return $query ? $query->result() : [];
        }

        // Query Material
        // REVISI: Select id, code, name, unit, type
        $this->db->select("m.id, m.kode_material as code, m.nama_material as name, m.satuan as unit, 'material' as item_type");
        $this->db->from($this->table_materials . ' m'); 
        $this->db->group_start()->like('m.kode_material', $term)->or_like('m.nama_material', $term)->group_end();
        $query1 = $this->db->get_compiled_select(); 

        // Query Semifinished
        // REVISI: Select id, code, name, unit, type
        $this->db->select("sf.id, sf.kode_barang as code, sf.nama_barang as name, sf.satuan as unit, 'semifinished' as item_type");
        $this->db->from($this->table_semifinished . ' sf'); 
        $this->db->group_start()->like('sf.kode_barang', $term)->or_like('sf.nama_barang', $term)->group_end();
        $query2 = $this->db->get_compiled_select();

        // Query Inventory
        // REVISI: Select id, code, name, unit, type (Pastikan kolom inventory benar)
        $this->db->select("i.id, i.kode_barang as code, i.nama_barang as name, i.satuan as unit, 'inventory' as item_type");
        $this->db->from($this->table_inventories . ' i'); 
        $this->db->group_start()->like('i.kode_barang', $term)->or_like('i.nama_barang', $term)->group_end();
        $query3 = $this->db->get_compiled_select(); 

        // Query Machine
        // REVISI: Select id, code, name, unit, type
        $this->db->select("mc.id, mc.kode_mesin as code, mc.nama_mesin as name, '' as unit, 'machine' as item_type");
        $this->db->from($this->table_machines . ' mc');
        $this->db->group_start()->like('mc.kode_mesin', $term)->or_like('mc.nama_mesin', $term)->group_end();
        $query4 = $this->db->get_compiled_select();

        // Query Jasa
        $this->db->select("j.id, j.kode_jasa as code, j.nama_jasa as name, j.satuan as unit, 'jasa' as item_type");
        $this->db->from($this->table_jasa . ' j');
        $this->db->where('j.status', 'active');
        $this->db->group_start()->like('j.kode_jasa', $term)->or_like('j.nama_jasa', $term)->group_end();
        $query5 = $this->db->get_compiled_select();

        $final_sql = "($query1) UNION ALL ($query2) UNION ALL ($query3) UNION ALL ($query4) UNION ALL ($query5) LIMIT " . $limit;
        $final_query = $this->db->query($final_sql);
            
        if (!$final_query) { /* ... (error logging) ... */ return []; }    
        return $final_query->result(); // Kembalikan hasil query
    }
    public function get_item_details($item_id_string) {
        if (empty($item_id_string) || strpos($item_id_string, '-') === false) return null; 
        list($type, $id) = explode('-', $item_id_string);
        $id = (int)$id;
        if ($id <= 0) return null;
        $result = null;
        switch ($type) {
            case 'material':
                $this->db->select("id, kode_material as code, nama_material as name, satuan as unit, 'material' as item_type");
                $result = $this->db->get_where($this->table_materials, ['id' => $id])->row();
                break;
            case 'semifinished':
                 $this->db->select("id, kode_barang as code, nama_barang as name, satuan as unit, 'semifinished' as item_type");
                $result = $this->db->get_where($this->table_semifinished, ['id' => $id])->row();
                break;
             case 'inventory': 
                 $this->db->select("id, kode_barang as code, nama_barang as name, satuan as unit, 'inventory' as item_type");
                $result = $this->db->get_where($this->table_inventories, ['id' => $id])->row(); 
                break;
             case 'machine':
                 $this->db->select("id, kode_mesin as code, nama_mesin as name, '' as unit, 'machine' as item_type");
                $result = $this->db->get_where($this->table_machines, ['id' => $id])->row();
                break;
            case 'jasa':
                $this->db->select("id, kode_jasa as code, nama_jasa as name, satuan as unit, 'jasa' as item_type");
                $result = $this->db->get_where($this->table_jasa, ['id' => $id])->row();
                break;
            case 'asset':
                $this->db->select("id, kode_asset as code, nama_asset as name, satuan as unit, 'asset' as item_type");
                $result = $this->db->get_where($this->table_asset, ['id' => $id])->row();
                break;
        }
        return $result;
    }

    public function update_item($detail_id, $data) {
        $this->db->where('id', $detail_id);
        return $this->db->update($this->table_details, $data);
    }

    public function get_detail_by_id($detail_id) {
        return $this->db->where('id', $detail_id)->get($this->table_details)->row();
    }

    /**
     * Padanan check_if_po_has_receipts() untuk PO Asset.
     *
     * Aset tidak lewat GRN sama sekali (modul GRN tidak dipakai) -- penerimaan
     * aset menghasilkan baris di `asset_register`. Jadi guard "sudah diterima"
     * untuk PO Asset harus melihat ke tabel itu, bukan ke grn_details, kalau
     * tidak PO yang unitnya sudah masuk register masih bisa di-unconfirm.
     */
    public function check_if_po_has_asset_register($po_id) {
        $this->db->select('id');
        $this->db->from('asset_register');
        $this->db->where('po_header_id', $po_id);
        $this->db->limit(1);
        return $this->db->get()->num_rows() > 0;
    }

    public function check_if_po_has_receipts($po_id) {
        $this->db->select('grn.id');
        $this->db->from($this->table_grn_details . ' grn');
        $this->db->join($this->table_details . ' pod', 'grn.source_doc_detail_id = pod.id AND grn.source_doc_type = "PO"', 'inner');
        $this->db->where('pod.po_header_id', $po_id);
        $this->db->limit(1);
        $query = $this->db->get();
        return ($query && $query->num_rows() > 0);
    }

    // ============================================================
    // "SELESAIKAN JASA" — tombol di PO Jasa yang menulis penyelesaian
    // langsung ke grn_header/grn_details (bentuk sama seperti GRN barang),
    // supaya AP Accounting (ApInvoice_model::get_pending_grn) otomatis
    // mendeteksinya lewat jalur GRN_BASED yang sudah ada, tanpa PO Jasa
    // perlu punya menu "penerimaan" terpisah.
    // ============================================================

    /**
     * List baris PO Jasa beserta sisa qty yang belum "diselesaikan".
     * Pola sama persis Warehouse_in_model::get_available_items() untuk PO barang.
     */
    public function get_service_completion_items($po_header_id) {
        $this->db->reset_query();
        $received_subquery = $this->db->select('source_doc_detail_id, SUM(receive_qty) as total_received')
            ->from($this->table_grn_details)
            ->where('source_doc_header_id', $po_header_id)
            ->where('source_doc_type', 'PO')
            ->group_by('source_doc_detail_id')
            ->get_compiled_select();
        $this->db->reset_query();

        $select_clause = "
            d.id as detail_id, d.item_id, d.qty as ordered_qty, d.local_price as unit_price,
            GREATEST(0, d.qty - IFNULL(received.total_received, 0)) as remaining_qty,
            j.kode_jasa as item_code, j.nama_jasa as item_name, COALESCE(j.satuan, 'Lot') as item_unit
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_jasa . ' j', 'd.item_id = j.id', 'left');
        $this->db->join("({$received_subquery}) received", 'd.id = received.source_doc_detail_id', 'left');
        $this->db->where('d.po_header_id', $po_header_id);
        $this->db->where('d.item_type', 'jasa');
        $this->db->having('remaining_qty >', 0.001);
        $this->db->order_by('d.id', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Nomor GRN khusus penyelesaian jasa: JSA/YYYYMM/NNNN
     * (prefix beda dari GRN/YYYYMM/NNNN punya barang, biar gampang dibedakan).
     */
    public function generate_service_grn_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('service_receipt', 'grn_header', 'grn_number');
    }

    /**
     * Catat penyelesaian jasa: insert grn_header (status langsung Completed,
     * tanpa QA) + grn_details per baris. supplier_id WAJIB diisi karena
     * ApInvoice_model::get_pending_grn() filter berdasarkan itu.
     *
     * @param int   $po_header_id
     * @param array $items [['detail_id'=>, 'item_id'=>, 'qty'=>, 'unit_price'=>], ...]
     * @param int   $user_id
     * @return array ['success'=>bool, 'message'=>string, 'grn_id'=>int|null]
     */
    public function complete_service_items($po_header_id, $items, $user_id) {
        $header = $this->get_po_header_by_id($po_header_id);
        if (!$header || $header->type !== 'jasa') {
            return ['success' => false, 'message' => 'PO Jasa tidak ditemukan.'];
        }
        if ($header->status !== 'Confirmed') {
            return ['success' => false, 'message' => 'PO hanya bisa diselesaikan jika status Confirmed.'];
        }

        // Clamp qty ke sisa yang benar-benar belum diselesaikan (cegah over-completion
        // jika request dikirim dua kali / qty di form dimanipulasi melebihi sisa PO).
        $remaining_map = [];
        foreach ($this->get_service_completion_items($po_header_id) as $row) {
            $remaining_map[$row->detail_id] = (float) $row->remaining_qty;
        }

        $valid_items = [];
        foreach ($items as $item) {
            $detail_id = (int) ($item['detail_id'] ?? 0);
            $max_qty   = $remaining_map[$detail_id] ?? 0;
            $qty       = min((float) $item['qty'], $max_qty);
            if ($qty <= 0) continue;
            $valid_items[] = [
                'detail_id'  => $detail_id,
                'item_id'    => (int) $item['item_id'],
                'qty'        => $qty,
                'unit_price' => (float) $item['unit_price'],
            ];
        }

        if (empty($valid_items)) {
            return ['success' => false, 'message' => 'Tidak ada baris jasa valid untuk diselesaikan (qty kosong atau sudah selesai semua).'];
        }

        $this->db->trans_start();

        $now = date('Y-m-d H:i:s');
        $grn_header_data = [
            'grn_number'     => $this->generate_service_grn_number(),
            'grn_date'       => date('Y-m-d'),
            'base_on'        => 'PO',
            'warehouse_type' => 'jasa',
            'supplier_id'    => $header->supplier_id,
            'remarks'        => 'Penyelesaian jasa untuk PO ' . $header->po_number,
            'status'         => 'Completed',
            'created_by'     => $user_id,
            'confirmed_by'   => $user_id,
            'confirmed_at'   => $now,
        ];
        $this->db->insert($this->table_grn_header, $grn_header_data);
        $grn_id = $this->db->insert_id();

        foreach ($valid_items as $item) {
            $this->db->insert($this->table_grn_details, [
                'grn_header_id'         => $grn_id,
                'source_doc_header_id'  => $po_header_id,
                'source_doc_detail_id'  => $item['detail_id'],
                'source_doc_type'       => 'PO',
                'item_id'               => $item['item_id'],
                'item_type'             => 'jasa',
                'receive_qty'           => $item['qty'],
                'unit_value'            => $item['unit_price'],
                'total_value'           => $item['qty'] * $item['unit_price'],
            ]);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Gagal menyimpan penyelesaian jasa (kesalahan database).'];
        }
        return ['success' => true, 'message' => 'Jasa berhasil diselesaikan dan siap ditagihkan di AP.', 'grn_id' => $grn_id];
    }

    // ============================================================
    // TARIK ITEM DARI PR (multi-PR, parsial)
    //
    // Satu PO boleh menampung baris dari beberapa PR sekaligus, dan tiap
    // baris PR boleh ditarik sebagian. Jejaknya di po_details.pr_header_id
    // + pr_detail_id; sisa qty dihitung on-the-fly oleh Purchase_request_model.
    //
    // po_header.pr_header_id tetap diisi PR sumber PERTAMA supaya konsumen
    // lama (Po_report_model, export PDF, traceability IRF/MR) tidak rusak.
    // Daftar lengkapnya baca lewat get_source_prs().
    // ============================================================

    /**
     * Rakit payload pull dari input form.
     *
     * Form mengirim `pr_detail_ids[]` (checkbox baris yang dicentang) dan
     * `pull_qty[<pr_detail_id>]` (qty per baris). Baris tanpa qty dianggap
     * "ambil semua sisanya" — 0 di sini akan diklamp ke sisa oleh pull_pr_items().
     *
     * @param array $ids     isi pr_detail_ids[]
     * @param array $qty_map isi pull_qty[]
     */
    public static function build_pull_rows($ids, $qty_map) {
        $ids     = is_array($ids) ? $ids : [];
        $qty_map = is_array($qty_map) ? $qty_map : [];

        $rows = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) continue;
            $rows[] = [
                'pr_detail_id' => $id,
                // PHP_FLOAT_MAX sebagai "sisa berapa pun ambil semua"; pull_pr_items()
                // yang menentukan angka finalnya dari sisa sebenarnya.
                'qty' => isset($qty_map[$id]) && $qty_map[$id] !== '' ? (float) $qty_map[$id] : PHP_FLOAT_MAX,
            ];
        }
        return $rows;
    }

    /**
     * Tarik baris PR ke PO yang sudah ada.
     *
     * Qty selalu diklamp ulang ke sisa yang benar-benar tersedia saat ini —
     * angka dari form tidak dipercaya, jadi dua orang yang menarik baris sama
     * bersamaan tidak bisa membuat qty PO melebihi qty PR.
     *
     * @param int   $po_id
     * @param array $rows            [['pr_detail_id'=>int, 'qty'=>float], ...]
     * @param array $allow_statuses  status PO yang boleh ditarik (default Draft)
     * @return array ['success','message','added','merged','skipped'=>[]]
     */
    public function pull_pr_items($po_id, $rows, $allow_statuses = ['Draft']) {
        $this->load->model('Purchase_request_model');

        $header = $this->get_po_header_by_id($po_id);
        if (!$header) {
            return ['success' => false, 'message' => 'PO tidak ditemukan.'];
        }
        if (!in_array($header->status, $allow_statuses, TRUE)) {
            return ['success' => false, 'message' => 'Item hanya bisa ditarik saat PO berstatus ' . implode('/', $allow_statuses) . '. Status sekarang: ' . $header->status . '.'];
        }

        // Gabungkan dulu duplikat di dalam satu request (baris yang sama
        // dikirim dua kali) supaya klamp sisa dihitung atas totalnya.
        $wanted = [];
        foreach ((array) $rows as $r) {
            $pr_detail_id = (int) ($r['pr_detail_id'] ?? 0);
            $qty          = (float) ($r['qty'] ?? 0);
            if ($pr_detail_id <= 0 || $qty <= 0) continue;
            $wanted[$pr_detail_id] = ($wanted[$pr_detail_id] ?? 0) + $qty;
        }
        if (empty($wanted)) {
            return ['success' => false, 'message' => 'Tidak ada baris PR yang dipilih.'];
        }

        $eps       = Purchase_request_model::QTY_EPSILON;
        $remaining = $this->Purchase_request_model->get_remaining_qty_map(array_keys($wanted));

        // Baris PO yang sudah menunjuk pr_detail tertentu di PO ini — kalau
        // ditarik lagi, qty-nya ditambahkan ke baris itu, bukan bikin baris kembar.
        $existing = [];
        foreach ($this->db->select('id, pr_detail_id, qty, local_price, base_price')
                          ->from($this->table_details)
                          ->where('po_header_id', $po_id)
                          ->where('pr_detail_id IS NOT NULL', NULL, FALSE)
                          ->get()->result() as $row) {
            $existing[(int) $row->pr_detail_id] = $row;
        }

        $added = 0; $merged = 0; $skipped = [];

        $this->db->trans_start();

        foreach ($wanted as $pr_detail_id => $qty_req) {
            $src = $remaining[$pr_detail_id] ?? null;

            if (!$src) {
                $skipped[] = 'Baris PR #' . $pr_detail_id . ' tidak ditemukan.';
                continue;
            }
            if ($src->pr_status !== 'Approved') {
                $skipped[] = $src->pr_number . ': PR belum Approved (' . $src->pr_status . ').';
                continue;
            }
            if ($src->pr_type !== $header->type) {
                $skipped[] = $src->pr_number . ': tipe PR (' . $src->pr_type . ') tidak cocok dengan tipe PO (' . $header->type . ').';
                continue;
            }

            $qty = min($qty_req, (float) $src->remaining_qty);
            if ($qty <= $eps) {
                $skipped[] = $src->pr_number . ': sisa qty sudah habis (semua sudah masuk PO lain).';
                continue;
            }

            if (isset($existing[$pr_detail_id])) {
                $row     = $existing[$pr_detail_id];
                $new_qty = (float) $row->qty + $qty;
                $this->db->where('id', $row->id)->update($this->table_details, [
                    'qty'         => $new_qty,
                    'total_local' => round($new_qty * (float) $row->local_price, 2),
                    'total'       => round($new_qty * (float) $row->base_price, 2),
                ]);
                $merged++;
            } else {
                $this->db->insert($this->table_details, [
                    'po_header_id'      => $po_id,
                    'pr_header_id'      => (int) $src->pr_header_id,
                    'pr_detail_id'      => $pr_detail_id,
                    'item_id'           => (int) $src->item_id,
                    'item_type'         => $src->item_type,
                    'category'          => $src->category ?? null,
                    'sales_contract_id' => $src->sales_contract_id,
                    'description'       => $src->description,
                    'qty'               => $qty,
                    'base_price'        => 0,
                    'local_price'       => 0,
                    'total_local'       => 0,
                    'total'             => 0,
                ]);
                $added++;
            }
        }

        // Isi PR sumber utama di header kalau masih kosong, supaya laporan &
        // traceability lama tetap menemukan jalurnya.
        if (empty($header->pr_header_id) && ($added > 0 || $merged > 0)) {
            $first = $this->db->select('pr_header_id')
                ->from($this->table_details)
                ->where('po_header_id', $po_id)
                ->where('pr_header_id IS NOT NULL', NULL, FALSE)
                ->order_by('id', 'ASC')->limit(1)->get()->row();
            if ($first) {
                $this->db->where('id', $po_id)->update($this->table_header, ['pr_header_id' => (int) $first->pr_header_id]);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Gagal menyimpan item dari PR (kesalahan database).'];
        }

        if ($added === 0 && $merged === 0) {
            return [
                'success' => false,
                'message' => 'Tidak ada baris yang bisa ditarik. ' . implode(' ', $skipped),
                'added'   => 0, 'merged' => 0, 'skipped' => $skipped,
            ];
        }

        $msg = [];
        if ($added)  $msg[] = $added . ' item ditambahkan';
        if ($merged) $msg[] = $merged . ' item qty-nya ditambah';
        $message = ucfirst(implode(', ', $msg)) . ' dari PR. Lengkapi harga pada PO.';
        if (!empty($skipped)) {
            $message .= ' Dilewati: ' . implode(' ', $skipped);
        }

        return ['success' => true, 'message' => $message, 'added' => $added, 'merged' => $merged, 'skipped' => $skipped];
    }

    /**
     * Buat PO baru langsung dari sebagian item satu PR (tombol "Buat PO" di
     * halaman PR). Header dibatalkan lagi kalau ternyata tidak ada satu pun
     * baris yang bisa ditarik — supaya tidak meninggalkan PO kosong.
     *
     * @param array $po_data data header (tanpa pr_header_id; diisi di sini)
     * @param array $rows    [['pr_detail_id'=>int, 'qty'=>float], ...]
     * @return array ['success','message','po_id']
     */
    public function create_po_from_pr($pr_header_id, $po_data, $rows) {
        $po_data['pr_header_id'] = (int) $pr_header_id;
        $po_data['status']       = 'Draft';

        $po_id = $this->insert_header($po_data);
        if (!$po_id) {
            return ['success' => false, 'message' => 'Gagal membuat header PO dari PR.', 'po_id' => null];
        }

        $result = $this->pull_pr_items($po_id, $rows);
        if (empty($result['success'])) {
            // Bersihkan header yatim. delete_header() menolak kalau ada detail,
            // jadi PO yang sudah terisi sebagian tidak akan ikut terhapus.
            $this->delete_header($po_id);
            return ['success' => false, 'message' => $result['message'], 'po_id' => null];
        }

        $result['po_id'] = $po_id;
        return $result;
    }

    /**
     * Daftar PR yang menyumbang baris ke satu PO (bisa lebih dari satu).
     * Fallback ke po_header.pr_header_id untuk PO lama yang barisnya belum
     * punya jejak per-baris.
     */
    public function get_source_prs($po_id) {
        $rows = $this->db
            ->select('pr.id, pr.pr_number, pr.pr_date, pr.department, pr.purpose, pr.remarks,
                      pr.requester_id, pr.created_by, pr.created_at, pr.approved_by, pr.approved_at,
                      SUM(d.qty) AS pulled_qty, COUNT(d.id) AS line_count', FALSE)
            ->from($this->table_details . ' d')
            ->join('pr_header pr', 'pr.id = d.pr_header_id', 'inner')
            ->where('d.po_header_id', $po_id)
            ->group_by('pr.id, pr.pr_number, pr.pr_date, pr.department, pr.purpose, pr.remarks,
                        pr.requester_id, pr.created_by, pr.created_at, pr.approved_by, pr.approved_at')
            ->order_by('pr.id', 'ASC')
            ->get()->result();

        if (!empty($rows)) return $rows;

        $header = $this->db->select('pr_header_id')->from($this->table_header)->where('id', $po_id)->get()->row();
        if (!$header || empty($header->pr_header_id)) return [];

        return $this->db->select('id, pr_number, pr_date, department, purpose, remarks,
                                  requester_id, created_by, created_at, approved_by, approved_at')
            ->from('pr_header')->where('id', $header->pr_header_id)->get()->result();
    }

    /**
     * PO terbaru yang memakai PR ini — lewat header (PR sumber utama) maupun
     * lewat baris (PR yang nebeng di PO gabungan). Dipakai traceability IRF/MR.
     */
    public function get_latest_po_for_pr($pr_header_id) {
        $pr_header_id = (int) $pr_header_id;

        $po_ids = [];
        $row = $this->db->select('id')->from($this->table_header)
            ->where('pr_header_id', $pr_header_id)->order_by('id', 'DESC')->limit(1)->get()->row();
        if ($row) $po_ids[] = (int) $row->id;

        $row = $this->db->select('po_header_id')->from($this->table_details)
            ->where('pr_header_id', $pr_header_id)->order_by('po_header_id', 'DESC')->limit(1)->get()->row();
        if ($row) $po_ids[] = (int) $row->po_header_id;

        if (empty($po_ids)) return null;

        return $this->db->select('id, created_by, created_at, confirmed_by, confirmed_at, status, po_number')
            ->from($this->table_header)
            ->where_in('id', array_unique($po_ids))
            ->order_by('id', 'DESC')->limit(1)->get()->row();
    }

    // ============================================================
    // HISTORY / APPROVAL LOG (ERP Revision Notes 2026-09-02, Fase 2 poin 11
    // — replikasi History universal ke PO). Pola sama persis dgn
    // Erf_model/Prf_model::_log() + get_approval_logs().
    // ============================================================

    public function get_approval_logs($po_header_id) {
        return $this->db->where('po_header_id', $po_header_id)
            ->order_by('id', 'ASC')->get('po_approval_logs')->result();
    }

    private function _log($po_header_id, $action, $actor_id, $notes = null) {
        $actor = $this->db->select('fullname')->where('id', $actor_id)->get('users')->row();
        $this->db->insert('po_approval_logs', [
            'po_header_id' => $po_header_id,
            'action'       => $action,
            'actor_id'     => $actor_id,
            'actor_name'   => $actor ? $actor->fullname : null,
            'notes'        => $notes,
        ]);
    }

} // End Class