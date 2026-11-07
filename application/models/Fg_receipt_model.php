<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fg_receipt_model extends CI_Model {

    // --- Definisi Nama Tabel ---
    private $table_header = 'fg_receipt_header';
    private $table_details = 'fg_receipt_details';
    private $table_prod_output = 'production_outputs';
    private $table_fg = 'finished_goods';
    private $table_prod_header = 'production_header';
    private $table_wo_header = 'wo_header';
    private $table_sc = 'sales_contracts';
    private $table_users = 'users';
    // --- Akhir Definisi Nama Tabel ---

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Stock_ledger_model');
    }

    // ==========================================================
    // GET SALES CONTRACT ID BY WO ID
    // ==========================================================
    public function get_sales_contract_id_by_wo_id($wo_id) {
        $this->db->select('sales_contract_id');
        $this->db->from($this->table_wo_header);
        $this->db->where('id', $wo_id);
        $query = $this->db->get();
        $row = $query->row();
        return $row ? $row->sales_contract_id : null;
    }

    // ==========================================================
    // GENERATE NOMOR OTOMATIS
    // ==========================================================
    public function generate_receipt_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('fg_receipt', 'fg_receipt_header', 'receipt_number');
    }


    // ==========================================================
    // GET SEMUA HEADER
    // Concat WO/Production/IPO dari detail (multi-WO). Fallback ke
    // kolom header (legacy data) bila detail-nya NULL.
    // ==========================================================
    public function get_all($filters = []) {
        $sub_agg = "(
            SELECT
                d.fg_receipt_header_id,
                GROUP_CONCAT(DISTINCT wo.wo_number ORDER BY wo.wo_number SEPARATOR ', ') AS wo_numbers,
                GROUP_CONCAT(DISTINCT ph.production_number ORDER BY ph.production_number SEPARATOR ', ') AS production_numbers,
                GROUP_CONCAT(DISTINCT sc.sc_number ORDER BY sc.sc_number SEPARATOR ', ') AS ipo_numbers
            FROM {$this->table_details} d
            LEFT JOIN {$this->table_wo_header} wo  ON d.wo_header_id = wo.id
            LEFT JOIN {$this->table_prod_header} ph ON d.production_header_id = ph.id
            LEFT JOIN {$this->table_sc} sc          ON d.sales_contract_id = sc.id
            GROUP BY d.fg_receipt_header_id
        )";

        $this->db->select("
            h.id, h.receipt_number, h.receipt_date, h.status,
            fg.kode_barang AS fg_code, fg.nama_barang AS fg_name,
            COALESCE(agg.production_numbers, ph_legacy.production_number) AS production_number,
            COALESCE(agg.wo_numbers, wo_legacy.wo_number) AS wo_number,
            COALESCE(agg.ipo_numbers, sc_legacy.sc_number) AS ipo_number,
            u_receiver.fullname  AS receiver_name,
            u_confirmer.fullname AS confirmer_name
        ", FALSE);
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_fg . ' fg',                'fg.id = h.fg_id',                          'left');
        $this->db->join($sub_agg . ' agg',                      'agg.fg_receipt_header_id = h.id',          'left');
        $this->db->join($this->table_prod_header . ' ph_legacy','ph_legacy.id = h.production_header_id',    'left');
        $this->db->join($this->table_wo_header . ' wo_legacy',  'wo_legacy.id = h.wo_header_id',            'left');
        $this->db->join($this->table_sc . ' sc_legacy',         'sc_legacy.id = h.sales_contract_id',       'left');
        $this->db->join($this->table_users . ' u_receiver',     'h.received_by = u_receiver.id',            'left');
        $this->db->join($this->table_users . ' u_confirmer',    'h.confirmed_by = u_confirmer.id',          'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.receipt_number', $term);
            $this->db->or_like('fg.nama_barang', $term);
            $this->db->or_like('fg.kode_barang', $term);
            $this->db->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    // ==========================================================
    // GET HEADER BERDASARKAN ID
    // Tetap expose wo_number/production_number/sc_number (= IPO) untuk
    // legacy data; receipt baru pakai fg_id (kolom fg_code/fg_name).
    // ==========================================================
    public function get_header_by_id($id) {
        $this->db->select("
            h.*,
            fg.kode_barang AS fg_code, fg.nama_barang AS fg_name, fg.satuan AS fg_uom,
            ph.production_number, wo.wo_number, sc.sc_number,
            u_receiver.fullname AS receiver_name,
            u_receiver.signature_path AS receiver_signature,
            u_confirmer.fullname AS confirmer_name,
            u_confirmer.signature_path AS confirmer_signature
        ");
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_fg . ' fg',              'fg.id = h.fg_id',                          'left');
        $this->db->join($this->table_prod_header . ' ph',     'h.production_header_id = ph.id',           'left');
        $this->db->join($this->table_wo_header . ' wo',       'h.wo_header_id = wo.id',                   'left');
        $this->db->join($this->table_sc . ' sc',              'h.sales_contract_id = sc.id',              'left');
        $this->db->join($this->table_users . ' u_receiver',   'h.received_by = u_receiver.id',            'left');
        $this->db->join($this->table_users . ' u_confirmer',  'h.confirmed_by = u_confirmer.id',          'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    // ==========================================================
    // GET DETAILS (WITH WO/IPO/PRODUCTION PER ROW, FALLBACK LEGACY)
    // Untuk receipt baru, wo/ph/sc diambil dari kolom detail.
    // Untuk legacy data (kolom detail NULL), fallback via prod_output → ph → wo → sc.
    // ==========================================================
    public function get_details_by_header_id($header_id) {
        $this->db->select("
            d.*,
            fg.kode_barang AS fg_code, fg.nama_barang AS fg_name, fg.satuan AS fg_uom,
            po.stage_name AS source_stage,
            po.size_breakdown AS prod_size_breakdown,
            u_qc.fullname AS qc_name,
            COALESCE(wo_d.wo_number, wo_legacy.wo_number) AS wo_number,
            COALESCE(ph_d.production_number, ph_legacy.production_number) AS production_number,
            COALESCE(sc_d.sc_number, sc_legacy.sc_number) AS ipo_number,
            COALESCE(d.wo_header_id, wo_legacy.id)        AS row_wo_id,
            COALESCE(d.production_header_id, ph_legacy.id) AS row_production_header_id,
            COALESCE(d.sales_contract_id, sc_legacy.id)    AS row_sales_contract_id
        ", FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_fg . ' fg',                 'd.fg_id = fg.id',                          'left');
        $this->db->join($this->table_prod_output . ' po',        'd.prod_output_id = po.id',                 'left');
        $this->db->join($this->table_users . ' u_qc',            'po.qc_checked_by = u_qc.id',               'left');
        // Direct (new schema)
        $this->db->join($this->table_wo_header . ' wo_d',        'wo_d.id = d.wo_header_id',                 'left');
        $this->db->join($this->table_prod_header . ' ph_d',      'ph_d.id = d.production_header_id',         'left');
        $this->db->join($this->table_sc . ' sc_d',               'sc_d.id = d.sales_contract_id',            'left');
        // Legacy fallback (via prod_output → production_header → wo → sc)
        $this->db->join($this->table_prod_header . ' ph_legacy', 'ph_legacy.id = po.production_header_id',   'left');
        $this->db->join($this->table_wo_header . ' wo_legacy',   'wo_legacy.id = ph_legacy.wo_header_id',    'left');
        $this->db->join($this->table_sc . ' sc_legacy',          'sc_legacy.id = wo_legacy.sales_contract_id','left');
        $this->db->where('d.fg_receipt_header_id', $header_id);
        $this->db->order_by('d.id', 'ASC');
        return $this->db->get()->result();
    }

    // ==========================================================
    // SEARCH FG (MODEL) untuk dropdown create receipt.
    // Hanya FG yg punya output produksi siap diterima (stage=finishing,
    // QC done, qty sisa > 0).
    // ==========================================================
    public function search_fg_for_receipt($term = '') {
        $subQueryReceived = "(
            SELECT prod_output_id, SUM(qty_received) AS total_received
            FROM {$this->table_details}
            WHERE prod_output_id IS NOT NULL
            GROUP BY prod_output_id
        )";

        $this->db->select("
            DISTINCT fg.id AS fg_id,
            fg.kode_barang AS fg_code,
            fg.nama_barang AS fg_name,
            fg.satuan      AS uom
        ", FALSE);
        $this->db->from($this->table_fg . ' fg');
        $this->db->join($this->table_prod_output . ' po', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'inner');
        $this->db->join($subQueryReceived . ' receipt_calc', 'po.id = receipt_calc.prod_output_id', 'left');

        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.qc_checked_at IS NOT NULL');
        $this->db->where('(po.qty_produced - IFNULL(receipt_calc.total_received, 0)) >', 0.00009);

        if (!empty($term)) {
            $term_esc = $this->db->escape_like_str($term);
            $this->db->group_start();
            $this->db->like('fg.nama_barang', $term_esc);
            $this->db->or_like('fg.kode_barang', $term_esc);
            $this->db->group_end();
        }

        $this->db->order_by('fg.nama_barang', 'ASC');
        $this->db->limit(20);
        $query = $this->db->get();

        if (!$query) {
            $db_error = $this->db->error();
            log_message('error', 'Database Error [' . $db_error['code'] . '] ' . $db_error['message']);
            return [];
        }

        return $query->result();
    }

    // ==========================================================
    // GET RECEIVABLE WO LIST untuk FG yang dipilih.
    // Output: per WO + IPO + Production + array outputs (per prod_output).
    // ==========================================================
    public function get_receivable_wos_by_fg_id($fg_id) {
        if (empty($fg_id) || !is_numeric($fg_id)) return [];

        $subQueryReceived = "(
            SELECT prod_output_id, SUM(qty_received) AS total_received
            FROM {$this->table_details}
            WHERE prod_output_id IS NOT NULL
            GROUP BY prod_output_id
        )";

        $this->db->select("
            po.id           AS prod_output_id,
            po.qty_produced,
            po.size_breakdown,
            ph.id           AS production_header_id,
            ph.production_number,
            wo.id           AS wo_header_id,
            wo.wo_number,
            sc.id           AS sales_contract_id,
            sc.sc_number    AS ipo_number,
            fg.id           AS fg_id,
            fg.kode_barang  AS fg_code,
            fg.nama_barang  AS fg_name,
            fg.satuan       AS uom,
            IFNULL(receipt_calc.total_received, 0) AS already_received_qty,
            (po.qty_produced - IFNULL(receipt_calc.total_received, 0)) AS remaining_qty
        ", FALSE);
        $this->db->from($this->table_prod_output . ' po');
        $this->db->join($this->table_fg . ' fg',            'fg.id = po.item_id AND po.item_type = "finished_goods"', 'inner');
        $this->db->join($this->table_prod_header . ' ph',   'ph.id = po.production_header_id', 'inner');
        $this->db->join($this->table_wo_header . ' wo',     'wo.id = ph.wo_header_id', 'inner');
        $this->db->join($this->table_sc . ' sc',            'sc.id = wo.sales_contract_id', 'left');
        $this->db->join($subQueryReceived . ' receipt_calc','po.id = receipt_calc.prod_output_id', 'left');

        $this->db->where('po.item_id', $fg_id);
        $this->db->where('po.item_type', 'finished_goods');
        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.qc_checked_at IS NOT NULL');
        $this->db->having('remaining_qty >', 0.00009);

        $this->db->order_by('wo.id', 'DESC');
        $this->db->order_by('po.id', 'ASC');

        $rows = $this->db->get()->result();

        // Group by WO
        $by_wo = [];
        foreach ($rows as $r) {
            $key = $r->wo_header_id;
            if (!isset($by_wo[$key])) {
                $by_wo[$key] = [
                    'wo_header_id'         => $r->wo_header_id,
                    'wo_number'            => $r->wo_number,
                    'sales_contract_id'    => $r->sales_contract_id,
                    'ipo_number'           => $r->ipo_number,
                    'production_header_id' => $r->production_header_id,
                    'production_number'    => $r->production_number,
                    'fg_id'                => $r->fg_id,
                    'fg_code'              => $r->fg_code,
                    'fg_name'              => $r->fg_name,
                    'uom'                  => $r->uom,
                    'total_remaining'      => 0,
                    'outputs'              => [],
                ];
            }
            $by_wo[$key]['outputs'][] = [
                'prod_output_id'     => (int)$r->prod_output_id,
                'qty_produced'       => (float)$r->qty_produced,
                'already_received'   => (float)$r->already_received_qty,
                'remaining_qty'      => (float)$r->remaining_qty,
                'size_breakdown'     => $r->size_breakdown,
            ];
            $by_wo[$key]['total_remaining'] += (float)$r->remaining_qty;
        }

        return array_values($by_wo);
    }

    // ==========================================================
    // INSERT RECEIPT (DENGAN SIZE)
    // ==========================================================
    public function insert_receipt($headerData, $detailsData) {
        $this->db->trans_start();
        $this->db->insert($this->table_header, $headerData);
        $headerId = $this->db->insert_id();

        if (!empty($detailsData)) {
            foreach ($detailsData as &$detail) {
                $detail['fg_receipt_header_id'] = $headerId;
                if (!empty($detail['prod_output_id'])) {
                    $output = $this->db->select('size_breakdown')
                        ->get_where($this->table_prod_output, ['id' => $detail['prod_output_id']])->row();
                    if ($output && !empty($output->size_breakdown)) {
                        $detail['size_breakdown'] = $output->size_breakdown;
                    }
                }
            }
            unset($detail);
            $this->db->insert_batch($this->table_details, $detailsData);
        }

        $this->db->trans_complete();
        return ($this->db->trans_status()) ? $headerId : false;
    }

    // ==========================================================
    // UPDATE RECEIPT (DENGAN SIZE)
    // ==========================================================
    public function update_receipt($id, $headerData, $detailsData) {
        $header = $this->db->get_where($this->table_header, ['id' => $id, 'status' => 'Draft'])->row();
        if (!$header) return false;

        $this->db->trans_start();
        $allowed_header_updates = ['receipt_date', 'remarks', 'received_by'];
        $update_header = array_intersect_key($headerData, array_flip($allowed_header_updates));
        if (!empty($update_header)) {
            $this->db->where('id', $id)->update($this->table_header, $update_header);
        }

        $this->db->where('fg_receipt_header_id', $id)->delete($this->table_details);

        if (!empty($detailsData)) {
            foreach ($detailsData as &$detail) {
                $detail['fg_receipt_header_id'] = $id;
                if (!empty($detail['prod_output_id'])) {
                    $output = $this->db->select('size_breakdown')->get_where($this->table_prod_output, ['id' => $detail['prod_output_id']])->row();
                    if ($output && !empty($output->size_breakdown)) {
                        $detail['size_breakdown'] = $output->size_breakdown;
                    }
                }
            }
            unset($detail);
            $this->db->insert_batch($this->table_details, $detailsData);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ==========================================================
    // DELETE RECEIPT (DRAFT ONLY)
    // ==========================================================
    public function delete_receipt($id) {
        $header = $this->db->get_where($this->table_header, ['id' => $id, 'status' => 'Draft'])->row();
        if (!$header) return false;

        $this->db->trans_start();
        // Hapus details dulu
        $this->db->where('fg_receipt_header_id', $id)->delete($this->table_details);
        // Hapus header
        $this->db->where('id', $id)->delete($this->table_header);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }


    // ==========================================================
    // LIGHTWEIGHT: get status untuk approval flow guard
    // ==========================================================
    public function get_status_by_id($id) {
        return $this->db->select('id, receipt_number, status')
            ->from($this->table_header)
            ->where('id', $id)
            ->limit(1)->get()->row();
    }

    // ==========================================================
    // APPROVAL WORKFLOW (3-stage)
    // Draft → Submitted → Approved Head Dept → Endorsed → Approved
    // Stock ledger HANYA fire di approve_final.
    // ==========================================================

    /**
     * Daftar FG Receipt yang menunggu approval user (eksklusif per stage permission).
     */
    public function get_pending_approvals($can_head_dept, $can_endorsement, $can_approval) {
        $stages = [];
        if ($can_head_dept)   $stages[] = 'Submitted';
        if ($can_endorsement) $stages[] = 'Approved Head Dept';
        if ($can_approval)    $stages[] = 'Endorsed';
        if (empty($stages)) return [];

        $sub_agg = "(
            SELECT
                d.fg_receipt_header_id,
                GROUP_CONCAT(DISTINCT wo.wo_number ORDER BY wo.wo_number SEPARATOR ', ') AS wo_numbers,
                GROUP_CONCAT(DISTINCT ph.production_number ORDER BY ph.production_number SEPARATOR ', ') AS production_numbers
            FROM {$this->table_details} d
            LEFT JOIN {$this->table_wo_header} wo  ON d.wo_header_id = wo.id
            LEFT JOIN {$this->table_prod_header} ph ON d.production_header_id = ph.id
            GROUP BY d.fg_receipt_header_id
        )";

        $this->db->select("
            h.id, h.receipt_number, h.receipt_date, h.status, h.remarks,
            h.submitted_at, h.hod_approved_at, h.endorsed_at,
            fg.kode_barang AS fg_code, fg.nama_barang AS fg_name,
            COALESCE(agg.production_numbers, ph_legacy.production_number) AS production_number,
            COALESCE(agg.wo_numbers, wo_legacy.wo_number) AS wo_number,
            u_sub.fullname AS submitted_by_name,
            u_hod.fullname AS hod_approver_name,
            u_end.fullname AS endorser_name
        ", FALSE);
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_fg . ' fg',                 'fg.id = h.fg_id',                          'left');
        $this->db->join($sub_agg . ' agg',                       'agg.fg_receipt_header_id = h.id',          'left');
        $this->db->join($this->table_prod_header . ' ph_legacy', 'ph_legacy.id = h.production_header_id',    'left');
        $this->db->join($this->table_wo_header . ' wo_legacy',   'wo_legacy.id = h.wo_header_id',            'left');
        $this->db->join($this->table_users . ' u_sub',           'h.submitted_by = u_sub.id',                'left');
        $this->db->join($this->table_users . ' u_hod',           'h.hod_approved_by = u_hod.id',             'left');
        $this->db->join($this->table_users . ' u_end',           'h.endorsed_by = u_end.id',                 'left');
        $this->db->where_in('h.status', $stages);
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Submit Draft → Pending Approval (creator).
     */
    public function submit_for_approval($id, $user_id) {
        $header = $this->get_status_by_id($id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'FG Receipt hanya bisa di-submit dari status Draft.'];
        }
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) {
            return ['success' => false, 'message' => 'FG Receipt tidak punya item; tidak bisa di-submit.'];
        }
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'       => 'Submitted',
            'submitted_by' => $user_id,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal submit.'];
    }

    /**
     * Approval 1 tahap (2026-11-06): Head of Dept = approval final.
     * Status -> Approved dan stok barang jadi langsung masuk stock_ledger (fg_in).
     */
    public function approve_hod($id, $user_id) {
        return $this->_approve_and_post_stock($id, $user_id, 'Submitted', true);
    }

    public function approve_endorsement($id, $user_id) {
        $header = $this->get_status_by_id($id);
        if (!$header || $header->status !== 'Approved Head Dept') {
            return ['success' => false, 'message' => 'FG Receipt hanya bisa di-endorse dari status Approved Head Dept.'];
        }
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'      => 'Endorsed',
            'endorsed_by' => $user_id,
            'endorsed_at' => date('Y-m-d H:i:s'),
        ]);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal endorse.'];
    }

    /**
     * Final approve -- hanya untuk FG Receipt lama (alur 3 tahap) yang sudah Endorsed.
     */
    public function approve_final($id, $user_id) {
        return $this->_approve_and_post_stock($id, $user_id, 'Endorsed', false);
    }

    /**
     * Status -> Approved + FIRE stock_ledger (fg_in). Satu-satunya tempat stok FG
     * Receipt masuk ledger. $as_hod = approval 1 tahap oleh Head of Dept (isi juga
     * kolom hod_approved_*).
     */
    private function _approve_and_post_stock($id, $user_id, $from_status, $as_hod) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status !== $from_status) {
            return ['success' => false, 'message' => $as_hod
                ? 'FG Receipt hanya bisa di-approve dari status Submitted.'
                : 'FG Receipt hanya bisa di-approve final dari status Endorsed.'];
        }
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) {
            return ['success' => false, 'message' => 'FG Receipt tidak punya item.'];
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'            => 'Approved',
            'final_approved_by' => $user_id,
            'final_approved_at' => $now,
            // Sinkron ke kolom legacy supaya kode lama yg baca confirmed_by/at tetap konsisten
            'confirmed_by'      => $user_id,
            'confirmed_at'      => $now,
        ];
        if ($as_hod) {
            $update['hod_approved_by'] = $user_id;
            $update['hod_approved_at'] = $now;
        }

        $this->db->trans_start();

        // Update status atomik
        $this->db->where('id', $id)->where('status', $from_status)->update($this->table_header, $update);
        if ($this->db->affected_rows() == 0) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Status berubah saat proses approve (race condition).'];
        }

        // FIRE stock_ledger fg_in HANYA di sini
        $warehouse_id = property_exists($header, 'warehouse_id') ? $header->warehouse_id : null;
        foreach ($details as $detail) {
            $last_balance = (float)($this->Stock_ledger_model->get_last_balance($detail->fg_id, 'finished_goods') ?? 0);
            $qty_in = (float)$detail->qty_received;
            $this->Stock_ledger_model->insert([
                'item_id'          => $detail->fg_id,
                'item_type'        => 'finished_goods',
                'transaction_date' => $now,
                'transaction_type' => 'fg_in',
                'transaction_id'   => $detail->id,
                'source_doc_type'  => 'FG_RECEIPT',
                'source_doc_id'    => $id,
                'qty_in'           => $qty_in,
                'qty_out'          => 0,
                'balance'          => $last_balance + $qty_in,
                'warehouse_id'     => $warehouse_id,
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status()
            ? ['success' => true]
            : ['success' => false, 'message' => 'Transaction gagal saat approve.'];
    }

    /**
     * Return to Draft: status balik ke 'Draft', reset audit approval.
     */
    public function return_to_draft($id, $user_id, $reason) {
        $header = $this->get_status_by_id($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            return ['success' => false, 'message' => 'FG Receipt tidak dalam tahap approval.'];
        }
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'               => 'Draft',
            'returned_to_draft_by' => $user_id,
            'returned_to_draft_at' => date('Y-m-d H:i:s'),
            'return_reason'        => $reason,
            'submitted_by'         => NULL, 'submitted_at'      => NULL,
            'hod_approved_by'      => NULL, 'hod_approved_at'   => NULL,
            'endorsed_by'          => NULL, 'endorsed_at'       => NULL,
            'final_approved_by'    => NULL, 'final_approved_at' => NULL,
            'rejected_by'          => NULL, 'rejected_at'       => NULL,
            'reject_reason'        => NULL,
            'confirmed_by'         => NULL, 'confirmed_at'      => NULL,
        ]);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal return ke Draft.'];
    }

    /**
     * Reject final: status → Rejected.
     */
    public function reject($id, $user_id, $reason) {
        $header = $this->get_status_by_id($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            return ['success' => false, 'message' => 'FG Receipt tidak dalam tahap approval.'];
        }
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'        => 'Rejected',
            'rejected_by'   => $user_id,
            'rejected_at'   => date('Y-m-d H:i:s'),
            'reject_reason' => $reason,
        ]);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal reject FG Receipt.'];
    }

    // ==========================================================
    // LEGACY: confirm_receipt — DEPRECATED, dipanggil dari controller lama.
    // Sekarang redirect ke approve_final flow (cuma untuk backward compat).
    // ==========================================================
    public function confirm_receipt($id, $userId) {
        // Status 'Confirmed' lama dianggap = 'Approved'. Jangan dipanggil dari UI baru.
        log_message('warning', '[Fg_receipt_model] confirm_receipt() is deprecated. Use approve_final() via approval menu.');
        return false;
    }


    // ==========================================================
    // UNCONFIRM RECEIPT -> DELETE fg_in entries and recalculate balance
    // PERBAIKAN: Jangan insert reversal entry, cukup hapus fg_in original
    // ==========================================================
    public function unconfirm_receipt($id) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status != 'Confirmed') return false;
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        $this->db->trans_start();

        // ubah status
        $this->db->where('id', $id)->update($this->table_header, [
            'status' => 'Draft',
            'confirmed_at' => NULL,
            'confirmed_by' => NULL
        ]);

        // DELETE fg_in entries (don't insert reversal, just remove the original)
        foreach ($details as $detail) {
            $this->db->where('transaction_type', 'fg_in')
                    ->where('transaction_id', $detail->id)
                    ->where('source_doc_type', 'FG_RECEIPT')
                    ->where('source_doc_id', $id)
                    ->delete('stock_ledger');
        }
        
        // RECALCULATE all balances for affected items
        $affected_fgs = [];
        foreach ($details as $d) {
            $affected_fgs[] = $d->fg_id;
        }
        
        if (!empty($affected_fgs)) {
            // For each affected FG, recalculate all balances from scratch
            foreach ($affected_fgs as $fg_id) {
                $this->_recalculate_fg_balance($fg_id);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    /**
     * Helper: Recalculate running balance for a finished good
     */
    private function _recalculate_fg_balance($fg_id) {
        // Get all entries for this FG, ordered by ID (creation order)
        $entries = $this->db->select('id, qty_in, qty_out')
                            ->where('item_id', $fg_id)
                            ->where('item_type', 'finished_goods')
                            ->order_by('id', 'ASC')
                            ->get('stock_ledger')
                            ->result();
        
        $running_balance = 0;
        foreach ($entries as $e) {
            $running_balance += ((float)$e->qty_in - (float)$e->qty_out);
            $this->db->where('id', $e->id)->update('stock_ledger', ['balance' => $running_balance]);
        }
    }

    // ==========================================================
    // CANCEL RECEIPT -> REVERSE STOK + STATUS CANCELLED
    // ==========================================================
    public function cancel_receipt($id) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status != 'Confirmed') return false;
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        $this->db->trans_start();
        $this->db->where('id', $id)->update($this->table_header, ['status' => 'Cancelled']);

        $now = date('Y-m-d H:i:s');
        foreach ($details as $detail) {
            $last_balance = $this->Stock_ledger_model->get_last_balance($detail->fg_id, 'finished_goods');
            $ledger_data = [
                'item_id' => $detail->fg_id,
                'item_type' => 'finished_goods',
                'transaction_date' => $now,
                'transaction_type' => 'fg_in_reversal',
                'transaction_id' => $detail->id,
                'source_doc_type' => 'FG_CANCEL',
                'source_doc_id' => $id,
                'qty_in' => 0,
                'qty_out' => $detail->qty_received,
                'balance' => $last_balance - $detail->qty_received
            ];
            $this->Stock_ledger_model->insert($ledger_data);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

} // End Class
