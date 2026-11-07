<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hutang otomatis: Payment Request <-> AP Invoice <-> Jurnal (2026-11-06).
 *
 * Permintaan user: Payment Request yang membayar PO (hutang) harus otomatis
 * membuat daftar hutang (AP Invoice) + jurnalnya, dan pembayarannya juga
 * otomatis dijurnal. Kalau AP Invoice sudah dibuat duluan (mis. oleh
 * Accounting dari PO / GRN), Payment Request cukup MENARIK AP Invoice itu dan
 * jurnal hutangnya tidak dibuat lagi.
 *
 * Aturan (keputusan user):
 *   - Jurnal hutang dibuat saat Payment Request DI-APPROVE (bukan saat Draft).
 *       Dr Persediaan / Beban / Aset (akun hasil pemetaan barang)
 *       Dr PPN Masukan
 *           Kr Utang Usaha
 *   - Jurnal bayar dibuat saat Record Payment:
 *       Dr Utang Usaha
 *       Dr Biaya Admin Bank (biaya transfer, bila ada)
 *           Kr Kas/Bank (dipilih Finance)
 *           Kr Utang PPh 23 (bila dipotong)
 *   - Tagihan PO parsial mengikuti barang yang SUDAH DATANG (GRN material/jasa,
 *     SGR mesin/inventory, Asset Register). PO Jasa boleh ditagih tanpa
 *     penerimaan (batasnya qty PO).
 *   - Akun debit dipetakan Accounting di Setting Akun, bertingkat:
 *       per barang -> (aset: akun di Master Aset) -> per kategori -> default per jenis.
 *     Akun belum dipetakan -> approval ditolak.
 *   - Payment Request manual (tanpa PO/AP) TIDAK disentuh: tetap dijurnal
 *     manual lewat Jurnal Umum.
 *
 * Satu-satunya tempat logika ini; Prf_model, ApInvoice_model & ApPayment_model
 * memanggil fungsi di sini supaya batas tagih & akun selalu sama di semua modul.
 */
class Ap_auto_model extends CI_Model {

    const QTY_EPS = 0.0001;
    const AMT_EPS = 0.01;

    /** Status Payment Request yang tidak lagi "memegang" qty / nominal tagihan. */
    const PRF_DEAD_STATUSES = ['Rejected', 'Cancelled'];

    /** Status PO yang boleh ditagih. */
    const PO_BILLABLE_STATUSES = ['Confirmed', 'Closed'];

    private $map_cache = null;
    private $item_cache = [];
    private $account_cache = [];

    public function __construct() {
        parent::__construct();
        $this->load->model('SettingAkun_model');
        $this->load->model('JurnalUmum_model');
    }

    // =====================================================================
    // PEMETAAN AKUN BARANG
    // =====================================================================

    /** Jenis barang yang bisa ada di PO + sumber master & grup kategorinya. */
    public function item_types() {
        return [
            'material'     => ['label' => 'Material',              'table' => 'materials',          'code' => 'kode_material', 'name' => 'nama_material', 'cat_group' => 'MATERIAL_CATEGORY'],
            'semifinished' => ['label' => 'Barang Setengah Jadi',  'table' => 'semifinished_goods', 'code' => 'kode_barang',   'name' => 'nama_barang',   'cat_group' => 'SEMIFINISHED_CATEGORY'],
            'inventory'    => ['label' => 'Inventory / Barang Umum', 'table' => 'inventories',      'code' => 'kode_barang',   'name' => 'nama_barang',   'cat_group' => 'INVENTORY_CATEGORY'],
            'machine'      => ['label' => 'Mesin',                 'table' => 'machines',           'code' => 'kode_mesin',    'name' => 'nama_mesin',    'cat_group' => 'MACHINE_CATEGORY'],
            'jasa'         => ['label' => 'Jasa',                  'table' => 'jasa',               'code' => 'kode_jasa',     'name' => 'nama_jasa',     'cat_group' => 'JASA_CATEGORY'],
            'asset'        => ['label' => 'Aset Tetap',            'table' => 'assets',             'code' => 'kode_asset',    'name' => 'nama_asset',    'cat_group' => 'ASSET_CATEGORY'],
        ];
    }

    private function _map() {
        if ($this->map_cache === null) {
            $this->map_cache = [];
            foreach ($this->db->get('item_account_map')->result() as $r) {
                $this->map_cache[$r->item_type . '|' . $r->scope . '|' . $r->scope_key] = (int) $r->account_id;
            }
        }
        return $this->map_cache;
    }

    /** Data master barang (kode, nama, kategori, satuan; aset + coa_asset_id). */
    public function item_master($type, $item_id) {
        $key = $type . '|' . (int) $item_id;
        if (!array_key_exists($key, $this->item_cache)) {
            $meta = $this->item_types()[$type] ?? null;
            $row = null;
            if ($meta && (int) $item_id > 0) {
                $cols = "id, {$meta['code']} AS code, {$meta['name']} AS name, kategori, satuan";
                if ($type === 'asset') $cols .= ', coa_asset_id';
                $row = $this->db->select($cols, false)->where('id', (int) $item_id)->get($meta['table'])->row();
            }
            $this->item_cache[$key] = $row;
        }
        return $this->item_cache[$key];
    }

    /** Akun valid untuk diposting: ada, aktif, bukan akun header. */
    public function account_row($account_id) {
        $account_id = (int) $account_id;
        if ($account_id <= 0) return null;
        if (!array_key_exists($account_id, $this->account_cache)) {
            $this->account_cache[$account_id] = $this->db
                ->select('id, account_code, account_name, account_type, is_header, status')
                ->where('id', $account_id)->get('chart_of_accounts')->row();
        }
        $a = $this->account_cache[$account_id];
        return ($a && $a->status === 'active' && (int) $a->is_header === 0) ? $a : null;
    }

    /**
     * Akun DEBIT untuk satu barang PO.
     * Urutan: pengecualian per barang -> akun di Master Aset (khusus aset)
     *         -> per kategori -> default per jenis barang.
     *
     * @return array ['account_id' => int|null, 'source' => item|asset_master|category|default|null,
     *                'category' => ..., 'code' => ..., 'name' => ...]
     */
    public function resolve_item_account($type, $item_id) {
        $map  = $this->_map();
        $item = $this->item_master($type, $item_id);
        $cat  = ($item && $item->kategori !== null) ? trim((string) $item->kategori) : '';
        $res  = [
            'account_id' => null, 'source' => null, 'category' => $cat,
            'code' => $item->code ?? null, 'name' => $item->name ?? null,
        ];

        $candidates = [];
        $candidates[] = ['item', $map[$type . '|item|' . (int) $item_id] ?? null];
        if ($type === 'asset' && !empty($item->coa_asset_id)) {
            $candidates[] = ['asset_master', (int) $item->coa_asset_id];
        }
        if ($cat !== '') {
            $candidates[] = ['category', $map[$type . '|category|' . $cat] ?? null];
        }
        $candidates[] = ['default', $map[$type . '|default|'] ?? null];

        foreach ($candidates as $c) {
            if (!empty($c[1]) && $this->account_row($c[1])) {
                $res['account_id'] = (int) $c[1];
                $res['source'] = $c[0];
                break;
            }
        }
        return $res;
    }

    /** Daftar kategori per jenis (common_code + nilai yang benar-benar dipakai di master). */
    public function category_options($type) {
        $meta = $this->item_types()[$type] ?? null;
        if (!$meta) return [];
        $out = [];
        $cc = $this->db->select('code, description')->where('group_code', $meta['cat_group'])
            ->order_by('code', 'ASC')->get('common_code')->result();
        foreach ($cc as $r) $out[(string) $r->code] = $r->description ?: $r->code;
        if ($this->db->table_exists($meta['table'])) {
            $used = $this->db->query("SELECT DISTINCT kategori FROM `{$meta['table']}` WHERE kategori IS NOT NULL AND kategori <> ''")->result();
            foreach ($used as $r) {
                $k = trim((string) $r->kategori);
                if ($k !== '' && !isset($out[$k])) $out[$k] = $k;
            }
        }
        return $out;
    }

    /** Semua baris pemetaan, dikelompokkan untuk halaman Setting Akun. */
    public function get_map_grouped() {
        $out = ['default' => [], 'category' => [], 'item' => []];
        $rows = $this->db->select('m.*, c.account_code, c.account_name')
            ->from('item_account_map m')
            ->join('chart_of_accounts c', 'c.id = m.account_id', 'left')
            ->order_by('m.item_type, m.scope_key')->get()->result();
        foreach ($rows as $r) {
            if ($r->scope === 'item') {
                $it = $this->item_master($r->item_type, (int) $r->scope_key);
                $r->item_code = $it->code ?? '?';
                $r->item_name = $it->name ?? '(barang sudah dihapus)';
                $out['item'][] = $r;
            } else {
                $out[$r->scope][$r->item_type . '|' . $r->scope_key] = $r;
            }
        }
        return $out;
    }

    /**
     * Simpan default per jenis + per kategori sekaligus (form Setting Akun).
     * $defaults   = [type => account_id|'' ]
     * $categories = [type => [category_code => account_id|'']]
     */
    public function save_map($defaults, $categories, $user_id) {
        $types = $this->item_types();
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        foreach ((array) $defaults as $type => $acc) {
            if (!isset($types[$type])) continue;
            $this->_upsert_map($type, 'default', '', $acc, $user_id, $now);
        }
        foreach ((array) $categories as $type => $rows) {
            if (!isset($types[$type]) || !is_array($rows)) continue;
            foreach ($rows as $cat => $acc) {
                $this->_upsert_map($type, 'category', (string) $cat, $acc, $user_id, $now);
            }
        }
        $this->db->trans_complete();
        $this->map_cache = null;
        return $this->db->trans_status() !== FALSE;
    }

    public function save_item_override($type, $item_id, $account_id, $user_id) {
        if (!isset($this->item_types()[$type])) return 'Jenis barang tidak dikenal.';
        if (!$this->item_master($type, $item_id)) return 'Barang tidak ditemukan di master.';
        if (!$this->account_row($account_id)) return 'Akun tidak valid (harus akun detail yang aktif).';
        $this->_upsert_map($type, 'item', (string) (int) $item_id, $account_id, $user_id, date('Y-m-d H:i:s'));
        $this->map_cache = null;
        return true;
    }

    public function delete_map_row($id) {
        $this->db->where('id', (int) $id)->where('scope', 'item')->delete('item_account_map');
        $this->map_cache = null;
        return $this->db->affected_rows() > 0;
    }

    private function _upsert_map($type, $scope, $key, $acc, $user_id, $now) {
        $acc = (int) $acc;
        $where = ['item_type' => $type, 'scope' => $scope, 'scope_key' => $key];
        if ($acc <= 0) {
            $this->db->where($where)->delete('item_account_map');
            return;
        }
        if (!$this->account_row($acc)) return; // abaikan akun header/nonaktif
        $exists = $this->db->where($where)->get('item_account_map')->row();
        if ($exists) {
            if ((int) $exists->account_id !== $acc) {
                $this->db->where('id', $exists->id)->update('item_account_map', ['account_id' => $acc, 'updated_by' => $user_id, 'updated_at' => $now]);
            }
        } else {
            $this->db->insert('item_account_map', $where + ['account_id' => $acc, 'updated_by' => $user_id, 'updated_at' => $now]);
        }
    }

    /** Cari barang master untuk ditambahkan sebagai pengecualian (AJAX select). */
    public function search_items($type, $q, $limit = 30) {
        $meta = $this->item_types()[$type] ?? null;
        if (!$meta) return [];
        $this->db->select("id, {$meta['code']} AS code, {$meta['name']} AS name, kategori", false)->from($meta['table']);
        if ($q !== '') {
            $this->db->group_start()->like($meta['code'], $q)->or_like($meta['name'], $q)->group_end();
        }
        return $this->db->order_by($meta['code'], 'ASC')->limit((int) $limit)->get()->result();
    }

    // =====================================================================
    // SISA YANG BISA DITAGIH PER BARIS PO
    // =====================================================================

    /**
     * Status tagihan semua baris dari PO-PO berikut.
     *
     *   received = GRN (Completed) + SGR (Completed) + unit di Asset Register
     *   cap      = jasa ? qty PO : min(received, qty PO)
     *   billed   = baris AP Invoice aktif (bukan hasil PRF) + baris Payment
     *              Request aktif (Draft s/d Paid; bukan Rejected/Cancelled)
     *   remaining= cap - billed
     *
     * AP yang dibuat dari approval PRF tidak dihitung lagi karena baris PRF-nya
     * sudah dihitung (hindari hitung dobel).
     *
     * @return array po_detail_id => object
     */
    public function po_line_status(array $po_ids, $exclude_prf_id = null, $exclude_ap_id = null) {
        $po_ids = array_values(array_unique(array_filter(array_map('intval', $po_ids))));
        if (empty($po_ids)) return [];
        $in     = implode(',', $po_ids);
        $ex_prf = (int) $exclude_prf_id;
        $ex_ap  = (int) $exclude_ap_id;
        $dead   = "'" . implode("','", self::PRF_DEAD_STATUSES) . "'";
        $incl   = db_has_column('po_header', 'ppn_inclusive') ? 'h.ppn_inclusive' : '0';

        $sql = "SELECT pd.id AS po_detail_id, pd.po_header_id, pd.item_id, pd.item_type,
                       pd.description AS po_description, pd.qty AS po_qty, pd.local_price,
                       h.po_number, h.type AS po_type, h.status AS po_status, h.supplier_id,
                       h.has_ppn, h.ppn_rate, {$incl} AS ppn_inclusive,
                       s.nama_supplier,
                       COALESCE(g.qty, 0) + COALESCE(sg.qty, 0) + COALESCE(ar.qty, 0) AS received_qty,
                       COALESCE(ap.qty, 0) AS billed_ap_qty,
                       COALESCE(pr.qty, 0) AS billed_prf_qty
                FROM po_details pd
                JOIN po_header h ON h.id = pd.po_header_id
                LEFT JOIN suppliers s ON s.id = h.supplier_id
                LEFT JOIN (SELECT gd.source_doc_detail_id AS did, SUM(gd.receive_qty) AS qty
                           FROM grn_details gd JOIN grn_header gh ON gh.id = gd.grn_header_id
                           WHERE gd.source_doc_type = 'PO' AND gh.status = 'Completed' AND gd.source_doc_header_id IN ($in)
                           GROUP BY gd.source_doc_detail_id) g ON g.did = pd.id
                LEFT JOIN (SELECT sd.source_doc_detail_id AS did, SUM(sd.receive_qty) AS qty
                           FROM sgr_details sd JOIN sgr_header sh ON sh.id = sd.sgr_header_id
                           WHERE sd.source_doc_type = 'PO' AND sh.status = 'Completed' AND sd.source_doc_header_id IN ($in)
                           GROUP BY sd.source_doc_detail_id) sg ON sg.did = pd.id
                LEFT JOIN (SELECT po_detail_id AS did, COUNT(*) AS qty FROM asset_register
                           WHERE po_header_id IN ($in) GROUP BY po_detail_id) ar ON ar.did = pd.id
                LEFT JOIN (SELECT COALESCE(aid.po_detail_id, IF(g2.source_doc_type = 'PO', g2.source_doc_detail_id, NULL)) AS did,
                                  SUM(aid.qty) AS qty
                           FROM ap_invoice_details aid
                           JOIN ap_invoice_header aih ON aih.id = aid.ap_invoice_header_id
                           LEFT JOIN grn_details g2 ON g2.id = aid.grn_detail_id
                           WHERE aih.status <> 'Cancelled' AND aih.prf_header_id IS NULL AND aih.id <> $ex_ap
                           GROUP BY did) ap ON ap.did = pd.id
                LEFT JOIN (SELECT x.po_detail_id AS did, SUM(x.qty) AS qty
                           FROM prf_details x JOIN prf_header ph ON ph.id = x.prf_header_id
                           WHERE x.line_type = 'item' AND x.po_detail_id IS NOT NULL
                             AND ph.status NOT IN ($dead) AND ph.id <> $ex_prf
                           GROUP BY x.po_detail_id) pr ON pr.did = pd.id
                WHERE pd.po_header_id IN ($in)
                ORDER BY pd.po_header_id, pd.id";

        $out = [];
        foreach ($this->db->query($sql)->result() as $r) {
            $r->po_qty         = (float) $r->po_qty;
            $r->received_qty   = (float) $r->received_qty;
            $r->billed_qty     = (float) $r->billed_ap_qty + (float) $r->billed_prf_qty;
            $r->is_jasa        = ($r->item_type === 'jasa' || $r->po_type === 'jasa');
            $r->cap_qty        = $r->is_jasa ? $r->po_qty : min($r->received_qty, $r->po_qty);
            $r->remaining_qty  = max(0, round($r->cap_qty - $r->billed_qty, 4));
            $r->ppn_rate       = (float) $r->ppn_rate;
            $r->ppn_applied    = !empty($r->has_ppn) && $r->ppn_rate > 0;
            $r->ppn_inclusive  = $r->ppn_applied && !empty($r->ppn_inclusive);
            $r->local_price    = (float) $r->local_price;
            // Harga per unit TANPA PPN (DPP). PO "harga termasuk PPN" dipecah dulu
            // supaya PPN Masukan bisa dijurnal terpisah.
            $r->dpp_unit_price = $r->ppn_inclusive
                ? round($r->local_price * 100 / (100 + $r->ppn_rate), 2)
                : round($r->local_price, 2);

            $item = $this->item_master($r->item_type, $r->item_id);
            $r->item_code   = $item->code ?? '';
            $r->item_name   = $item->name ?? '';
            $r->unit        = $item->satuan ?? null;
            $r->description = $r->item_name !== ''
                ? trim(($r->item_code !== '' ? $r->item_code . ' - ' : '') . $r->item_name)
                : (string) $r->po_description;
            $out[(int) $r->po_detail_id] = $r;
        }
        return $out;
    }

    /** Hitung PPN satu kelompok baris PO (dipakai PRF & AP berbasis PO). */
    public function ppn_for_lines($po_row, array $qty_by_line) {
        if (!$po_row->ppn_applied) return 0.0;
        $dpp = 0.0; $gross = 0.0;
        foreach ($qty_by_line as $line) {
            $dpp   += round($line['qty'] * $line['row']->dpp_unit_price, 2);
            $gross += round($line['qty'] * $line['row']->local_price, 2);
        }
        if ($po_row->ppn_inclusive) {
            // Total tetap sama dengan nilai PO (harga sudah termasuk PPN).
            return round($gross - $dpp, 2);
        }
        return round($dpp * $po_row->ppn_rate / 100, 2);
    }

    /**
     * PO yang masih punya sisa tagihan.
     * @return array of object (id, po_number, po_date, type, supplier_id, nama_supplier,
     *                          lines_open, amount_open)
     */
    public function get_billable_pos($supplier_id = null, $exclude_prf_id = null, $exclude_ap_id = null) {
        $this->db->select('id')->from('po_header')->where_in('status', self::PO_BILLABLE_STATUSES);
        if (!empty($supplier_id)) $this->db->where('supplier_id', (int) $supplier_id);
        $ids = array_map(function ($r) { return (int) $r->id; }, $this->db->get()->result());
        if (empty($ids)) return [];

        $status = $this->po_line_status($ids, $exclude_prf_id, $exclude_ap_id);
        $by_po = [];
        foreach ($status as $r) {
            if ($r->remaining_qty <= self::QTY_EPS) continue;
            $pid = (int) $r->po_header_id;
            if (!isset($by_po[$pid])) {
                $by_po[$pid] = ['row' => $r, 'lines' => []];
            }
            $by_po[$pid]['lines'][] = ['qty' => $r->remaining_qty, 'row' => $r];
        }

        $out = [];
        $dates = [];
        if ($by_po) {
            foreach ($this->db->select('id, po_date')->where_in('id', array_keys($by_po))->get('po_header')->result() as $d) {
                $dates[(int) $d->id] = $d->po_date;
            }
        }
        foreach ($by_po as $pid => $g) {
            $dpp = 0.0;
            foreach ($g['lines'] as $l) $dpp += round($l['qty'] * $l['row']->dpp_unit_price, 2);
            $ppn = $this->ppn_for_lines($g['row'], $g['lines']);
            $out[] = (object) [
                'id'            => $pid,
                'po_number'     => $g['row']->po_number,
                'po_date'       => $dates[$pid] ?? null,
                'type'          => $g['row']->po_type,
                'supplier_id'   => (int) $g['row']->supplier_id,
                'nama_supplier' => $g['row']->nama_supplier,
                'lines_open'    => count($g['lines']),
                'amount_open'   => round($dpp + $ppn, 2),
            ];
        }
        usort($out, function ($a, $b) { return strcmp((string) $b->po_date, (string) $a->po_date) ?: ($b->id - $a->id); });
        return $out;
    }

    /** Baris PO yang masih bisa ditagih (untuk modal Tarik PO / AP berbasis PO). */
    public function get_po_billable_lines($po_id, $exclude_prf_id = null, $exclude_ap_id = null, $with_account = false) {
        $rows = [];
        foreach ($this->po_line_status([(int) $po_id], $exclude_prf_id, $exclude_ap_id) as $r) {
            if ($r->remaining_qty <= self::QTY_EPS) continue;
            if ($with_account) {
                $acc = $this->resolve_item_account($r->item_type, $r->item_id);
                $r->account_id = $acc['account_id'];
                $r->account_source = $acc['source'];
            }
            $rows[] = $r;
        }
        return $rows;
    }

    // =====================================================================
    // AP INVOICE YANG BISA DITARIK KE PAYMENT REQUEST
    // =====================================================================

    /**
     * Nominal AP yang sedang "dipegang" Payment Request aktif yang belum dibayar
     * (+ draft Pembayaran Hutang), supaya satu tagihan tidak dibayar dua kali.
     * @return array ap_id => amount
     */
    public function ap_reserved_map(array $ap_ids = null, $exclude_prf_id = null, $exclude_payment_id = null) {
        $dead = "'" . implode("','", array_merge(self::PRF_DEAD_STATUSES, ['Paid'])) . "'";
        $ex_prf = (int) $exclude_prf_id;
        $ex_pay = (int) $exclude_payment_id;
        $filter_prf = $filter_pay = '';
        if ($ap_ids !== null) {
            $ids = array_values(array_filter(array_map('intval', $ap_ids)));
            if (empty($ids)) return [];
            $filter_prf = ' AND x.ap_invoice_header_id IN (' . implode(',', $ids) . ')';
            $filter_pay = ' AND d.ap_invoice_header_id IN (' . implode(',', $ids) . ')';
        }
        $out = [];
        $q1 = $this->db->query("SELECT x.ap_invoice_header_id AS aid, SUM(x.qty * x.unit_price) AS amt
            FROM prf_details x JOIN prf_header ph ON ph.id = x.prf_header_id
            WHERE x.ap_invoice_header_id IS NOT NULL AND ph.status NOT IN ($dead)
              AND ph.payment_status <> 'Paid' AND ph.id <> $ex_prf $filter_prf
            GROUP BY x.ap_invoice_header_id")->result();
        foreach ($q1 as $r) $out[(int) $r->aid] = ($out[(int) $r->aid] ?? 0) + (float) $r->amt;
        $q2 = $this->db->query("SELECT d.ap_invoice_header_id AS aid, SUM(d.amount_paid) AS amt
            FROM ap_payment_details d JOIN ap_payment_header p ON p.id = d.payment_header_id
            WHERE p.status = 'Draft' AND p.id <> $ex_pay $filter_pay
            GROUP BY d.ap_invoice_header_id")->result();
        foreach ($q2 as $r) $out[(int) $r->aid] = ($out[(int) $r->aid] ?? 0) + (float) $r->amt;
        return $out;
    }

    /**
     * AP Invoice terposting yang masih punya sisa untuk dibayar lewat Payment Request.
     * AP hasil approval PRF tidak ikut (pembayarannya lewat PRF asalnya).
     */
    public function get_open_ap_invoices($supplier_id = null, $exclude_prf_id = null, array $only_ids = null) {
        $this->db->select('aih.id, aih.invoice_no, aih.invoice_date, aih.due_date, aih.supplier_id, aih.invoice_type,
                           aih.subtotal, aih.ppn_amount, aih.pph_amount, aih.total_invoice, aih.total_paid,
                           s.nama_supplier, s.bank_name, s.bank_account')
            ->from('ap_invoice_header aih')
            ->join('suppliers s', 's.id = aih.supplier_id', 'left')
            ->where('aih.status', 'Posted')
            ->where('aih.payment_status !=', 'Paid')
            ->where('aih.prf_header_id IS NULL', null, false);
        if (!empty($supplier_id)) $this->db->where('aih.supplier_id', (int) $supplier_id);
        if ($only_ids !== null) {
            $only_ids = array_values(array_filter(array_map('intval', $only_ids)));
            if (empty($only_ids)) return [];
            $this->db->where_in('aih.id', $only_ids);
        }
        $rows = $this->db->order_by('aih.due_date', 'ASC')->order_by('aih.id', 'ASC')->get()->result();
        if (empty($rows)) return [];

        $reserved = $this->ap_reserved_map(array_map(function ($r) { return $r->id; }, $rows), $exclude_prf_id);
        $out = [];
        foreach ($rows as $r) {
            $r->reserved    = round($reserved[(int) $r->id] ?? 0, 2);
            $r->outstanding = round((float) $r->total_invoice - (float) $r->total_paid - $r->reserved, 2);
            if ($r->outstanding > self::AMT_EPS || $only_ids !== null) $out[(int) $r->id] = $r;
        }
        return $out;
    }

    // =====================================================================
    // NORMALISASI BARIS PAYMENT REQUEST
    // =====================================================================

    /**
     * Rapikan & validasi baris Payment Request dari form.
     *
     *  - Tanpa baris PO/AP -> MANUAL (perilaku lama, tidak diubah).
     *  - Ada baris PO/AP   -> HUTANG:
     *      * tidak boleh dicampur item manual,
     *      * satu supplier saja,
     *      * qty baris PO <= sisa yang bisa ditagih, harga dikunci ke harga PO (DPP),
     *      * nominal baris AP <= sisa AP,
     *      * baris PPN dibuat ulang di server per PO (nilai dari klien diabaikan).
     *
     * @return array ['ok'=>bool, 'message'=>string, 'source_type'=>MANUAL|HUTANG,
     *                'details'=>[], 'supplier_id'=>int|null, 'ppn_amount'=>float]
     */
    public function normalize_prf_lines(array $raw, $exclude_prf_id = null) {
        $manual = []; $po_req = []; $ap_req = [];

        foreach ($raw as $d) {
            if (!is_array($d)) continue;
            if (($d['line_type'] ?? 'item') === 'ppn') continue;   // dibuat ulang di bawah
            $po_detail_id = (int) ($d['po_detail_id'] ?? 0);
            $ap_id        = (int) ($d['ap_invoice_header_id'] ?? 0);
            $desc         = trim((string) ($d['description'] ?? ''));
            $unit         = trim((string) ($d['unit'] ?? ''));
            $qty          = (float) ($d['qty'] ?? 0);
            $price        = (float) ($d['unit_price'] ?? 0);

            if ($po_detail_id > 0) {
                if ($qty <= 0) continue;
                if (!isset($po_req[$po_detail_id])) $po_req[$po_detail_id] = ['qty' => 0.0, 'description' => $desc, 'unit' => $unit];
                $po_req[$po_detail_id]['qty'] += $qty;
            } elseif ($ap_id > 0) {
                $amt = round(($qty > 0 ? $qty : 1) * $price, 2);
                if ($amt <= 0) continue;
                if (!isset($ap_req[$ap_id])) $ap_req[$ap_id] = ['amount' => 0.0, 'description' => $desc];
                $ap_req[$ap_id]['amount'] += $amt;
            } else {
                if ($desc === '' || $qty == 0 || $price == 0) continue;
                $manual[] = [
                    'description' => $desc, 'qty' => $qty, 'unit' => $unit !== '' ? $unit : null,
                    'unit_price' => $price, 'po_header_id' => null, 'po_detail_id' => null,
                    'line_type' => 'item', 'ap_invoice_header_id' => null,
                ];
            }
        }

        if (empty($po_req) && empty($ap_req)) {
            if (empty($manual)) return $this->_fail('Minimal harus ada 1 item dengan data lengkap.');
            return ['ok' => true, 'message' => '', 'source_type' => 'MANUAL', 'details' => $manual,
                    'supplier_id' => null, 'ppn_amount' => 0.0];
        }
        if (!empty($manual)) {
            return $this->_fail('Payment Request hutang (tarik dari PO / AP Invoice) tidak boleh dicampur item manual. '
                . 'Hapus item manualnya, lalu buat Payment Request terpisah untuk item tersebut.');
        }

        $details = [];
        $suppliers = [];
        $ppn_total = 0.0;

        // --- Baris PO ---
        if (!empty($po_req)) {
            $po_ids = array_map(function ($r) { return (int) $r->po_header_id; },
                $this->db->select('po_header_id')->where_in('id', array_keys($po_req))->get('po_details')->result());
            $status = $this->po_line_status($po_ids, $exclude_prf_id);

            $groups = [];
            foreach ($po_req as $pdid => $req) {
                $r = $status[$pdid] ?? null;
                if (!$r) return $this->_fail('Baris PO yang ditarik tidak ditemukan (mungkin PO-nya sudah diubah). Tarik ulang dari PO.');
                if (!in_array($r->po_status, self::PO_BILLABLE_STATUSES, true)) {
                    return $this->_fail("PO {$r->po_number} berstatus {$r->po_status} — tidak bisa ditagih.");
                }
                if ($req['qty'] > $r->remaining_qty + self::QTY_EPS) {
                    return $this->_fail(sprintf(
                        'Qty "%s" (PO %s) = %s melebihi sisa yang bisa ditagih (%s). %s',
                        $r->description, $r->po_number, $this->_n($req['qty']), $this->_n($r->remaining_qty),
                        $r->is_jasa
                            ? 'Qty PO ' . $this->_n($r->po_qty) . ', sudah ditagih ' . $this->_n($r->billed_qty) . '.'
                            : 'Qty PO ' . $this->_n($r->po_qty) . ', sudah datang ' . $this->_n($r->received_qty) . ', sudah ditagih ' . $this->_n($r->billed_qty) . '.'
                    ));
                }
                $suppliers[(int) $r->supplier_id] = $r->nama_supplier;
                $groups[(int) $r->po_header_id][] = ['qty' => round($req['qty'], 2), 'row' => $r, 'req' => $req];
            }

            foreach ($groups as $po_id => $lines) {
                foreach ($lines as $l) {
                    $r = $l['row'];
                    $details[] = [
                        'description' => $l['req']['description'] !== '' ? $l['req']['description'] : $r->description,
                        'qty' => $l['qty'],
                        'unit' => $l['req']['unit'] !== '' ? $l['req']['unit'] : $r->unit,
                        'unit_price' => $r->dpp_unit_price,
                        'po_header_id' => $po_id,
                        'po_detail_id' => (int) $r->po_detail_id,
                        'line_type' => 'item',
                        'ap_invoice_header_id' => null,
                    ];
                }
                $first = $lines[0]['row'];
                $ppn = $this->ppn_for_lines($first, $lines);
                if ($ppn > 0) {
                    $details[] = [
                        'description' => po_ppn_label($first->ppn_rate, $first->ppn_inclusive) . ' - ' . $first->po_number,
                        'qty' => 1, 'unit' => null, 'unit_price' => $ppn,
                        'po_header_id' => $po_id, 'po_detail_id' => null,
                        'line_type' => 'ppn', 'ap_invoice_header_id' => null,
                    ];
                    $ppn_total += $ppn;
                }
            }
        }

        // --- Baris AP Invoice ---
        if (!empty($ap_req)) {
            $open = $this->get_open_ap_invoices(null, $exclude_prf_id, array_keys($ap_req));
            foreach ($ap_req as $ap_id => $req) {
                $ap = $open[$ap_id] ?? null;
                if (!$ap) return $this->_fail('AP Invoice yang ditarik tidak ditemukan / sudah lunas / belum diposting.');
                if ($req['amount'] > $ap->outstanding + self::AMT_EPS) {
                    return $this->_fail(sprintf('Nominal AP Invoice %s (Rp %s) melebihi sisa yang belum dibayar (Rp %s).',
                        $ap->invoice_no, number_format($req['amount'], 2, ',', '.'), number_format(max(0, $ap->outstanding), 2, ',', '.')));
                }
                $suppliers[(int) $ap->supplier_id] = $ap->nama_supplier;
                $details[] = [
                    'description' => $req['description'] !== '' ? $req['description'] : 'Pembayaran AP Invoice ' . $ap->invoice_no,
                    'qty' => 1, 'unit' => null, 'unit_price' => round($req['amount'], 2),
                    'po_header_id' => null, 'po_detail_id' => null,
                    'line_type' => 'item', 'ap_invoice_header_id' => (int) $ap_id,
                ];
            }
        }

        if (count($suppliers) > 1) {
            return $this->_fail('Satu Payment Request hutang hanya untuk SATU supplier. Ditemukan: ' . implode(', ', array_filter($suppliers)) . '.');
        }

        return ['ok' => true, 'message' => '', 'source_type' => 'HUTANG', 'details' => $details,
                'supplier_id' => (int) key($suppliers), 'ppn_amount' => round($ppn_total, 2)];
    }

    // =====================================================================
    // SAAT APPROVE: BUAT AP INVOICE + JURNAL HUTANG
    // =====================================================================

    /**
     * Siapkan AP Invoice untuk baris PO sebuah Payment Request hutang, TANPA
     * menulis apa pun. Dipanggil sebelum approval disimpan supaya kalau akun
     * belum lengkap, approval ditolak dengan pesan jelas.
     *
     * @return array ['ok'=>bool, 'message'=>..., 'plan'=>array|null]  plan null = tidak ada baris PO
     */
    public function prepare_ap_for_prf($prf) {
        if (($prf->source_type ?? 'MANUAL') !== 'HUTANG') return ['ok' => true, 'plan' => null];
        if (!empty($prf->ap_invoice_header_id)) return ['ok' => true, 'plan' => null]; // sudah ada

        $lines = $this->db->where('prf_header_id', (int) $prf->id)->where('po_detail_id IS NOT NULL', null, false)
            ->where('line_type', 'item')->get('prf_details')->result();
        if (empty($lines)) return ['ok' => true, 'plan' => null];

        $ppn_lines = $this->db->where('prf_header_id', (int) $prf->id)->where('line_type', 'ppn')->get('prf_details')->result();

        $po_ids = array_unique(array_map(function ($l) { return (int) $l->po_header_id; }, $lines));
        $status = $this->po_line_status($po_ids, $prf->id);

        $missing = [];
        $plan_lines = [];
        $qty_by_detail = [];
        foreach ($lines as $l) $qty_by_detail[(int) $l->po_detail_id] = ($qty_by_detail[(int) $l->po_detail_id] ?? 0) + (float) $l->qty;
        foreach ($qty_by_detail as $pdid => $qty) {
            $r = $status[$pdid] ?? null;
            if (!$r) return $this->_fail('Baris PO pada Payment Request ini sudah tidak ada di PO-nya.');
            if ($qty > $r->remaining_qty + self::QTY_EPS) {
                return $this->_fail(sprintf('Qty "%s" (PO %s) melebihi sisa tagihan (%s). Kembalikan ke Draft dan sesuaikan qty.',
                    $r->description, $r->po_number, $this->_n($r->remaining_qty)));
            }
        }
        foreach ($lines as $l) {
            $r = $status[(int) $l->po_detail_id];
            $acc = $this->resolve_item_account($r->item_type, $r->item_id);
            if (empty($acc['account_id'])) {
                $label = $this->item_types()[$r->item_type]['label'] ?? $r->item_type;
                $missing[] = sprintf('[%s] %s%s', $label, $r->description,
                    $acc['category'] !== '' ? ' (kategori ' . $acc['category'] . ')' : '');
                continue;
            }
            $plan_lines[] = [
                'po_detail_id' => (int) $l->po_detail_id, 'po_header_id' => (int) $l->po_header_id,
                'item_id' => (int) $r->item_id, 'item_type' => $r->item_type,
                'description' => $l->description, 'qty' => (float) $l->qty, 'unit' => $l->unit,
                'unit_price' => (float) $l->unit_price,
                'total' => round((float) $l->qty * (float) $l->unit_price, 2),
                'account_id' => (int) $acc['account_id'],
            ];
        }
        if ($missing) {
            return $this->_fail("Akun jurnal belum dipetakan Accounting untuk barang berikut:\n- " . implode("\n- ", array_unique($missing))
                . "\nMinta Accounting mengisinya di Setting Akun → Pemetaan Akun Barang, lalu approve ulang.");
        }

        $ppn = 0.0;
        foreach ($ppn_lines as $p) $ppn += round((float) $p->qty * (float) $p->unit_price, 2);
        $subtotal = 0.0;
        foreach ($plan_lines as $pl) $subtotal += $pl['total'];

        $acc_utang = $this->SettingAkun_model->get_account_id('akun_utang_usaha');
        $acc_ppn   = $this->SettingAkun_model->get_account_id('akun_ppn_masukan');
        $err = [];
        if (!$this->account_row($acc_utang)) $err[] = 'Akun Utang Usaha (AP)';
        if ($ppn > 0 && !$this->account_row($acc_ppn)) $err[] = 'Akun PPN Masukan';
        if ($err) {
            return $this->_fail('Setting Akun belum lengkap: ' . implode(', ', $err) . '. Minta Accounting mengisinya di menu Setting Akun, lalu approve ulang.');
        }

        $rate = null;
        foreach ($status as $r) { if ($r->ppn_applied) { $rate = $r->ppn_rate; break; } }

        return ['ok' => true, 'message' => '', 'plan' => [
            'lines' => $plan_lines, 'subtotal' => round($subtotal, 2), 'ppn' => round($ppn, 2),
            'total' => round($subtotal + $ppn, 2), 'ppn_rate' => $rate,
            'acc_utang' => (int) $acc_utang, 'acc_ppn' => (int) $acc_ppn,
            'po_ids' => array_values($po_ids),
            'po_numbers' => array_values(array_unique(array_map(function ($r) { return $r->po_number; }, array_intersect_key($status, $qty_by_detail)))),
        ]];
    }

    /**
     * Tulis AP Invoice + jurnal hutang dari plan prepare_ap_for_prf(). Harus
     * dipanggil DI DALAM transaksi approval (supaya gagal = approval batal).
     * @return array ['ap_id'=>int, 'journal_no'=>string]
     */
    public function write_ap_for_prf($prf, array $plan, $user_id) {
        $now   = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $ap_no = doc_number_next('ap_invoice', 'ap_invoice_header', 'invoice_no');

        $this->db->insert('ap_invoice_header', [
            'invoice_no'    => $ap_no,
            'invoice_date'  => $today,
            'due_date'      => !empty($prf->due_date) ? $prf->due_date : $today,
            'supplier_id'   => (int) $prf->supplier_id,
            'invoice_type'  => 'PO_BASED',
            'grn_ids'       => null,
            'subtotal'      => $plan['subtotal'],
            'ppn_amount'    => $plan['ppn'],
            'pph_amount'    => 0,
            'total_invoice' => $plan['total'],
            'total_paid'    => 0,
            'payment_status'=> 'Unpaid',
            'status'        => 'Draft',
            'prf_header_id' => (int) $prf->id,
            'po_header_id'  => count($plan['po_ids']) === 1 ? (int) $plan['po_ids'][0] : null,
            'created_by'    => $user_id,
            'created_at'    => $now,
        ]);
        $ap_id = (int) $this->db->insert_id();

        foreach ($plan['lines'] as $l) {
            $this->db->insert('ap_invoice_details', [
                'ap_invoice_header_id' => $ap_id,
                'po_detail_id'      => $l['po_detail_id'],
                'item_id'           => $l['item_id'],
                'item_type'         => $l['item_type'],
                'debet_account_id'  => $l['account_id'],
                'kredit_account_id' => $plan['acc_utang'],
                'account_id'        => $l['account_id'],
                'description'       => $l['description'],
                'qty'               => $l['qty'],
                'unit_price'        => $l['unit_price'],
                'total_price'       => $l['total'],
            ]);
        }

        $po_txt = implode(', ', $plan['po_numbers']);
        $jd = [];
        foreach ($plan['lines'] as $l) {
            $jd[] = ['account_id' => $l['account_id'], 'debit' => $l['total'], 'kredit' => 0,
                     'description' => mb_substr($l['description'] . ' (' . $this->_n($l['qty']) . ' ' . ($l['unit'] ?: '') . ')', 0, 255)];
        }
        if ($plan['ppn'] > 0) {
            $jd[] = ['account_id' => $plan['acc_ppn'], 'debit' => $plan['ppn'], 'kredit' => 0,
                     'description' => 'PPN Masukan ' . $ap_no, 'is_ppn_line' => 1];
        }
        $jd[] = ['account_id' => $plan['acc_utang'], 'debit' => 0, 'kredit' => $plan['total'],
                 'description' => mb_substr('Utang ke ' . ($prf->supplier_name ?: 'supplier') . ' - ' . $po_txt, 0, 255)];

        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        $jh = [
            'journal_no'   => $journal_no,
            'journal_date' => $today,
            'document_date'=> $today,
            'due_date'     => !empty($prf->due_date) ? $prf->due_date : null,
            'description'  => mb_substr('Hutang ' . ($prf->supplier_name ?: '') . ' atas PO ' . $po_txt . ' (Payment Request ' . $prf->prf_number . ', AP ' . $ap_no . ')', 0, 65000),
            'source_module'=> 'AP_INVOICE',
            'source_doc_id'=> $ap_id,
            'currency'     => 'IDR',
            'exchange_rate'=> 1,
            'total_debit'  => $plan['total'], 'total_kredit' => $plan['total'],
            'total_debit_local' => $plan['total'], 'total_kredit_local' => $plan['total'],
            'supplier_id'  => (int) $prf->supplier_id,
            'vendor_name'  => $prf->supplier_name,
            'created_by'   => $user_id,
            'status'       => 'Draft',
        ];
        if ($plan['ppn'] > 0) {
            $jh['ppn_enabled'] = 1; $jh['ppn_type'] = 'IN';
            $jh['ppn_percent'] = (float) $plan['ppn_rate']; $jh['dpp_amount'] = $plan['subtotal']; $jh['ppn_amount'] = $plan['ppn'];
        }
        $journal_id = $this->_insert_posted_journal($jh, $jd, $user_id);

        $this->db->where('id', $ap_id)->update('ap_invoice_header', [
            'status' => 'Posted', 'journal_header_id' => $journal_id,
            'confirmed_by' => $user_id, 'confirmed_at' => $now,
        ]);
        $this->db->where('id', (int) $prf->id)->update('prf_header', ['ap_invoice_header_id' => $ap_id]);

        return ['ap_id' => $ap_id, 'ap_no' => $ap_no, 'journal_no' => $journal_no];
    }

    /**
     * Batalkan AP hasil approval (dipakai saat Admin mengembalikan PRF yang
     * sudah Approved tapi BELUM dibayar ke Draft). Jurnal hutang tidak
     * dihapus: dibuat jurnal BALIK supaya buku besar tetap bisa diaudit.
     * Dipanggil di dalam transaksi pemanggil.
     */
    public function reverse_ap_for_prf($prf, $user_id, $reason) {
        if (empty($prf->ap_invoice_header_id)) return ['ok' => true];
        $ap = $this->db->where('id', (int) $prf->ap_invoice_header_id)->get('ap_invoice_header')->row();
        if (!$ap) {
            $this->db->where('id', (int) $prf->id)->update('prf_header', ['ap_invoice_header_id' => null]);
            return ['ok' => true];
        }
        if ((float) $ap->total_paid > self::AMT_EPS) {
            return $this->_fail('AP Invoice ' . $ap->invoice_no . ' sudah ada pembayaran — tidak bisa dibatalkan.');
        }
        $rev_id = null;
        if (!empty($ap->journal_header_id)) {
            $rev_id = $this->_reverse_journal((int) $ap->journal_header_id, $user_id,
                'Pembatalan hutang AP ' . $ap->invoice_no . ' (Payment Request ' . $prf->prf_number . ' dikembalikan ke Draft: ' . $reason . ')',
                'AP_INVOICE_REVERSAL', (int) $ap->id);
        }
        $this->db->where('id', (int) $ap->id)->update('ap_invoice_header', [
            'status' => 'Cancelled', 'reversal_journal_id' => $rev_id,
            'cancelled_by' => $user_id, 'cancelled_at' => date('Y-m-d H:i:s'),
            'cancel_reason' => mb_substr((string) $reason, 0, 255),
        ]);
        $this->db->where('id', (int) $prf->id)->update('prf_header', ['ap_invoice_header_id' => null]);
        return ['ok' => true, 'ap_no' => $ap->invoice_no];
    }

    // =====================================================================
    // SAAT RECORD PAYMENT: JURNAL BAYAR + PEMBAYARAN HUTANG
    // =====================================================================

    /** Akun Kas/Bank yang bisa dipilih Finance (akun Aset detail aktif). */
    public function get_cash_accounts() {
        $rows = $this->db->select('id, account_code, account_name')
            ->where('is_header', 0)->where('status', 'active')->where('account_type', 'Aset')
            ->order_by('account_code', 'ASC')->get('chart_of_accounts')->result();
        // Akun bernama Kas/Bank diletakkan paling atas.
        usort($rows, function ($a, $b) {
            $ka = preg_match('/\b(kas|bank|cash)\b/i', $a->account_name) ? 0 : 1;
            $kb = preg_match('/\b(kas|bank|cash)\b/i', $b->account_name) ? 0 : 1;
            return $ka <=> $kb ?: strcmp($a->account_code, $b->account_code);
        });
        return $rows;
    }

    /** Alokasi pembayaran PRF hutang ke AP Invoice-nya. @return array ap_id => amount */
    public function prf_payment_allocation($prf) {
        $alloc = [];
        if (!empty($prf->ap_invoice_header_id)) {
            $ap = $this->db->select('id, total_invoice, total_paid')->where('id', (int) $prf->ap_invoice_header_id)->get('ap_invoice_header')->row();
            if ($ap) $alloc[(int) $ap->id] = round((float) $ap->total_invoice - (float) $ap->total_paid, 2);
        }
        $rows = $this->db->select('ap_invoice_header_id, SUM(qty * unit_price) AS amt', false)
            ->where('prf_header_id', (int) $prf->id)->where('ap_invoice_header_id IS NOT NULL', null, false)
            ->group_by('ap_invoice_header_id')->get('prf_details')->result();
        foreach ($rows as $r) $alloc[(int) $r->ap_invoice_header_id] = ($alloc[(int) $r->ap_invoice_header_id] ?? 0) + round((float) $r->amt, 2);
        return $alloc;
    }

    /**
     * Validasi sebelum Record Payment PRF hutang (tanpa menulis).
     * $pay = [payment_amount, transfer_fee, pph_amount, cash_account_id, payment_date]
     */
    public function validate_prf_payment($prf, array $pay) {
        if (($prf->source_type ?? 'MANUAL') !== 'HUTANG') return ['ok' => true];

        $has_po_lines = $this->db->where('prf_header_id', (int) $prf->id)->where('po_detail_id IS NOT NULL', null, false)
            ->where('line_type', 'item')->count_all_results('prf_details') > 0;
        if ($has_po_lines && empty($prf->ap_invoice_header_id)) {
            return $this->_fail('Hutang (AP Invoice) Payment Request ini belum terbentuk. Pastikan sudah di-approve.');
        }
        $alloc = $this->prf_payment_allocation($prf);
        if (empty($alloc)) return $this->_fail('Payment Request hutang ini tidak punya AP Invoice untuk dibayar.');
        foreach ($alloc as $ap_id => $amt) {
            $ap = $this->db->select('invoice_no, status, total_invoice, total_paid')->where('id', $ap_id)->get('ap_invoice_header')->row();
            if (!$ap || $ap->status !== 'Posted') return $this->_fail('AP Invoice ' . ($ap->invoice_no ?? '#' . $ap_id) . ' belum/tidak berstatus Posted.');
            if ($amt > (float) $ap->total_invoice - (float) $ap->total_paid + self::AMT_EPS) {
                return $this->_fail('AP Invoice ' . $ap->invoice_no . ' sudah dibayar sebagian lewat modul lain — sisa tidak cukup.');
            }
        }
        $total_alloc = round(array_sum($alloc), 2);
        $paid = round((float) $pay['payment_amount'] + (float) ($pay['pph_amount'] ?? 0), 2);
        if (abs($paid - $total_alloc) > self::AMT_EPS) {
            return $this->_fail(sprintf('Payment Request hutang harus dibayar penuh: Jumlah Pembayaran + PPh 23 = Rp %s, padahal total tagihan Rp %s. '
                . 'Untuk bayar sebagian, buat Payment Request dengan qty/nominal yang lebih kecil.',
                number_format($paid, 2, ',', '.'), number_format($total_alloc, 2, ',', '.')));
        }
        if ((float) ($pay['pph_amount'] ?? 0) < 0 || (float) ($pay['transfer_fee'] ?? 0) < 0) return $this->_fail('Nilai PPh / biaya transfer tidak boleh negatif.');

        $err = [];
        if (!$this->account_row($pay['cash_account_id'] ?? 0)) return $this->_fail('Pilih akun Kas/Bank yang dipakai membayar.');
        if (!$this->account_row($this->SettingAkun_model->get_account_id('akun_utang_usaha'))) $err[] = 'Akun Utang Usaha (AP)';
        if ((float) ($pay['transfer_fee'] ?? 0) > 0 && !$this->account_row($this->SettingAkun_model->get_account_id('akun_biaya_admin_bank'))) $err[] = 'Akun Biaya Admin / Transfer Bank';
        if ((float) ($pay['pph_amount'] ?? 0) > 0 && !$this->account_row($this->SettingAkun_model->get_account_id('akun_utang_pph_23'))) $err[] = 'Akun Utang PPh 23';
        if ($err) return $this->_fail('Setting Akun belum lengkap: ' . implode(', ', $err) . '. Minta Accounting mengisinya di menu Setting Akun.');

        return ['ok' => true, 'alloc' => $alloc, 'total' => $total_alloc];
    }

    /**
     * Tulis Pembayaran Hutang + jurnal bayar untuk satu record payment PRF.
     * Dipanggil di dalam transaksi Prf_model::record_payment().
     */
    public function write_prf_payment($prf, $tracking_id, array $pay, $user_id) {
        $v = $this->validate_prf_payment($prf, $pay);
        if (empty($v['ok'])) throw new Exception($v['message']);
        $alloc = $v['alloc'];
        $total = $v['total'];
        $fee   = round((float) ($pay['transfer_fee'] ?? 0), 2);
        $pph   = round((float) ($pay['pph_amount'] ?? 0), 2);
        $cash  = (int) $pay['cash_account_id'];
        $date  = $pay['payment_date'];
        $now   = date('Y-m-d H:i:s');

        $acc_utang = (int) $this->SettingAkun_model->get_account_id('akun_utang_usaha');
        $acc_fee   = (int) $this->SettingAkun_model->get_account_id('akun_biaya_admin_bank');
        $acc_pph   = (int) $this->SettingAkun_model->get_account_id('akun_utang_pph_23');

        $pay_no = doc_number_next('ap_payment', 'ap_payment_header', 'payment_no');
        $this->db->insert('ap_payment_header', [
            'payment_no' => $pay_no, 'payment_date' => $date, 'supplier_id' => (int) $prf->supplier_id,
            'payment_account_id' => $cash, 'debet_account_id' => $acc_utang, 'kredit_account_id' => $cash,
            'total_amount' => $total, 'pph_rate' => 0, 'pph_amount' => $pph, 'pph_account_id' => $pph > 0 ? $acc_pph : null,
            'transfer_fee' => $fee, 'prf_header_id' => (int) $prf->id,
            'notes' => 'Dibayar lewat Payment Request ' . $prf->prf_number,
            'status' => 'Draft', 'created_by' => $user_id, 'created_at' => $now,
        ]);
        $pay_id = (int) $this->db->insert_id();

        foreach ($alloc as $ap_id => $amt) {
            $this->db->insert('ap_payment_details', ['payment_header_id' => $pay_id, 'ap_invoice_header_id' => $ap_id, 'amount_paid' => $amt]);
            $this->db->set('total_paid', 'total_paid + ' . (float) $amt, false)->where('id', $ap_id)->update('ap_invoice_header');
            $this->db->query("UPDATE ap_invoice_header SET payment_status = IF(total_paid >= total_invoice - 0.01, 'Paid', 'Partial') WHERE id = ?", [$ap_id]);
        }

        $ap_nos = array_map(function ($r) { return $r->invoice_no; },
            $this->db->select('invoice_no')->where_in('id', array_keys($alloc))->get('ap_invoice_header')->result());

        $jd = [];
        $jd[] = ['account_id' => $acc_utang, 'debit' => $total, 'kredit' => 0,
                 'description' => mb_substr('Pelunasan utang ' . ($prf->supplier_name ?: '') . ' - ' . implode(', ', $ap_nos), 0, 255)];
        if ($fee > 0) $jd[] = ['account_id' => $acc_fee, 'debit' => $fee, 'kredit' => 0, 'description' => 'Biaya transfer bank ' . $prf->prf_number];
        $jd[] = ['account_id' => $cash, 'debit' => 0, 'kredit' => round((float) $pay['payment_amount'] + $fee, 2),
                 'description' => mb_substr('Pembayaran Payment Request ' . $prf->prf_number . (!empty($pay['reference_number']) ? ' (Ref ' . $pay['reference_number'] . ')' : ''), 0, 255)];
        if ($pph > 0) $jd[] = ['account_id' => $acc_pph, 'debit' => 0, 'kredit' => $pph, 'description' => 'PPh 23 dipotong ' . $prf->prf_number, 'is_wht_line' => 1];

        $sum = round($total + $fee, 2);
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        $journal_id = $this->_insert_posted_journal([
            'journal_no' => $journal_no, 'journal_date' => $date, 'document_date' => $date,
            'description' => mb_substr('Pembayaran hutang ' . ($prf->supplier_name ?: '') . ' via Payment Request ' . $prf->prf_number . ' (' . $pay_no . ')', 0, 65000),
            'source_module' => 'AP_PAYMENT', 'source_doc_id' => $pay_id,
            'currency' => 'IDR', 'exchange_rate' => 1,
            'total_debit' => $sum, 'total_kredit' => $sum, 'total_debit_local' => $sum, 'total_kredit_local' => $sum,
            'supplier_id' => (int) $prf->supplier_id, 'vendor_name' => $prf->supplier_name,
            'vendor_bank_name' => $prf->bank_name, 'vendor_bank_account' => $prf->bank_account,
            'created_by' => $user_id, 'status' => 'Draft',
        ], $jd, $user_id);

        $this->db->where('id', $pay_id)->update('ap_payment_header', [
            'status' => 'Posted', 'journal_header_id' => $journal_id, 'posted_by' => $user_id, 'posted_at' => $now,
        ]);
        $this->db->where('id', (int) $tracking_id)->update('prf_payment_tracking', [
            'cash_account_id' => $cash, 'pph_amount' => $pph,
            'journal_header_id' => $journal_id, 'ap_payment_header_id' => $pay_id,
        ]);
        return ['payment_id' => $pay_id, 'payment_no' => $pay_no, 'journal_no' => $journal_no];
    }

    /** Ubah tanggal pembayaran -> tanggal jurnal & Pembayaran Hutang ikut. */
    public function sync_payment_date($tracking, $new_date) {
        if (!empty($tracking->journal_header_id)) {
            $this->db->where('id', (int) $tracking->journal_header_id)->update('journal_header', ['journal_date' => $new_date, 'document_date' => $new_date]);
        }
        if (!empty($tracking->ap_payment_header_id)) {
            $this->db->where('id', (int) $tracking->ap_payment_header_id)->update('ap_payment_header', ['payment_date' => $new_date]);
        }
    }

    // =====================================================================
    // INFO UNTUK HALAMAN DETAIL
    // =====================================================================

    /** Ringkasan dokumen akuntansi terkait sebuah PRF hutang. */
    public function prf_accounting_links($prf) {
        $out = ['ap' => [], 'payments' => []];
        $ap_ids = array_keys($this->prf_payment_allocation($prf));
        $cancelled = $this->db->select('id')->where('prf_header_id', (int) $prf->id)->where('status', 'Cancelled')->get('ap_invoice_header')->result();
        foreach ($cancelled as $c) $ap_ids[] = (int) $c->id;
        $ap_ids = array_values(array_unique($ap_ids));
        if ($ap_ids) {
            $out['ap'] = $this->db->select('a.id, a.invoice_no, a.invoice_type, a.status, a.payment_status, a.total_invoice, a.total_paid, a.prf_header_id,
                                            j.journal_no, j.id AS journal_id, r.journal_no AS reversal_no, r.id AS reversal_id')
                ->from('ap_invoice_header a')
                ->join('journal_header j', 'j.id = a.journal_header_id', 'left')
                ->join('journal_header r', 'r.id = a.reversal_journal_id', 'left')
                ->where_in('a.id', $ap_ids)->order_by('a.id')->get()->result();
        }
        $out['payments'] = $this->db->select('t.id, t.payment_date, t.payment_amount, t.pph_amount, t.transfer_fee,
                                              c.account_code, c.account_name, j.journal_no, j.id AS journal_id, p.payment_no, p.id AS ap_payment_id')
            ->from('prf_payment_tracking t')
            ->join('chart_of_accounts c', 'c.id = t.cash_account_id', 'left')
            ->join('journal_header j', 'j.id = t.journal_header_id', 'left')
            ->join('ap_payment_header p', 'p.id = t.ap_payment_header_id', 'left')
            ->where('t.prf_header_id', (int) $prf->id)->order_by('t.id')->get()->result();
        return $out;
    }

    // =====================================================================
    // INTERNAL
    // =====================================================================

    private function _insert_posted_journal(array $header, array $details, $user_id) {
        $d = 0.0; $k = 0.0;
        foreach ($details as $row) { $d += (float) $row['debit']; $k += (float) $row['kredit']; }
        if (abs(round($d, 2) - round($k, 2)) > 0.001) {
            throw new Exception(sprintf('Jurnal tidak seimbang (Debit %s, Kredit %s).', number_format($d, 2), number_format($k, 2)));
        }
        // Kolom opsional yang belum ada di DB lama dibuang supaya insert tidak gagal.
        foreach (['document_date', 'due_date', 'supplier_id', 'vendor_name', 'vendor_bank_name', 'vendor_bank_account',
                  'ppn_enabled', 'ppn_type', 'ppn_percent', 'dpp_amount', 'ppn_amount'] as $col) {
            if (array_key_exists($col, $header) && !db_has_column('journal_header', $col)) unset($header[$col]);
        }
        $id = $this->JurnalUmum_model->insert_journal($header, $details);
        if (!$id) throw new Exception('Gagal menyimpan jurnal.');
        $this->JurnalUmum_model->post_journal($id, $user_id);
        return (int) $id;
    }

    private function _reverse_journal($journal_id, $user_id, $description, $source_module, $source_doc_id) {
        $orig = $this->db->where('id', $journal_id)->get('journal_header')->row();
        if (!$orig) return null;
        $rows = $this->db->where('journal_header_id', $journal_id)->get('journal_details')->result();
        $jd = [];
        foreach ($rows as $r) {
            $jd[] = ['account_id' => $r->account_id, 'debit' => (float) $r->kredit, 'kredit' => (float) $r->debit,
                     'description' => mb_substr('Balik: ' . $r->description, 0, 255),
                     'is_ppn_line' => (int) ($r->is_ppn_line ?? 0), 'is_wht_line' => (int) ($r->is_wht_line ?? 0)];
        }
        $today = date('Y-m-d');
        return $this->_insert_posted_journal([
            'journal_no' => $this->JurnalUmum_model->get_new_journal_number(),
            'journal_date' => $today, 'document_date' => $today,
            'description' => mb_substr($description . ' — membalik ' . $orig->journal_no, 0, 65000),
            'source_module' => $source_module, 'source_doc_id' => $source_doc_id,
            'currency' => 'IDR', 'exchange_rate' => 1,
            'total_debit' => (float) $orig->total_kredit, 'total_kredit' => (float) $orig->total_debit,
            'total_debit_local' => (float) $orig->total_kredit_local, 'total_kredit_local' => (float) $orig->total_debit_local,
            'supplier_id' => $orig->supplier_id ?? null, 'vendor_name' => $orig->vendor_name ?? null,
            'created_by' => $user_id, 'status' => 'Draft',
        ], $jd, $user_id);
    }

    private function _fail($msg) {
        return ['ok' => false, 'message' => $msg];
    }

    private function _n($v) {
        return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    }
}
