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
        $prefix = 'GRN-FG/' . date('Ym') . '/';
        $this->db->trans_start();
        // ambil row terakhir dengan lock
        $sql = "SELECT receipt_number FROM {$this->table_header} WHERE receipt_number LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE";
        $query = $this->db->query($sql, [$prefix . '%']);
        $last = $query->row();
        $new_num = $last ? (int) substr($last->receipt_number, -4) + 1 : 1;
        $this->db->trans_complete();
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }


    // ==========================================================
    // GET SEMUA HEADER
    // ==========================================================
    public function get_all($filters = []) {
        $this->db->select("
            h.id, h.receipt_number, h.receipt_date, h.status,
            ph.production_number, wo.wo_number,
            u_receiver.fullname AS receiver_name, 
            u_confirmer.fullname AS confirmer_name
        ");
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_prod_header . ' ph', 'h.production_header_id = ph.id', 'left');
        $this->db->join($this->table_wo_header . ' wo', 'h.wo_header_id = wo.id', 'left');
        $this->db->join($this->table_users . ' u_receiver', 'h.received_by = u_receiver.id', 'left');
        $this->db->join($this->table_users . ' u_confirmer', 'h.confirmed_by = u_confirmer.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.receipt_number', $term);
            $this->db->or_like('ph.production_number', $term);
            $this->db->or_like('wo.wo_number', $term);
            $this->db->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    // ==========================================================
    // GET HEADER BERDASARKAN ID
    // ==========================================================
    public function get_header_by_id($id) {
        $this->db->select("
            h.*,
            ph.production_number, wo.wo_number, sc.sc_number,
            u_receiver.fullname AS receiver_name, 
            u_confirmer.fullname AS confirmer_name
        ");
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_prod_header . ' ph', 'h.production_header_id = ph.id', 'left');
        $this->db->join($this->table_wo_header . ' wo', 'h.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join($this->table_users . ' u_receiver', 'h.received_by = u_receiver.id', 'left');
        $this->db->join($this->table_users . ' u_confirmer', 'h.confirmed_by = u_confirmer.id', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    // ==========================================================
    // GET DETAILS (DENGAN NAMA QC & SIZE BREAKDOWN)
    // ==========================================================
    public function get_details_by_header_id($header_id) {
        $this->db->select("
            d.*,
            fg.kode_barang AS fg_code, fg.nama_barang AS fg_name, fg.satuan AS fg_uom,
            po.stage_name AS source_stage,
            po.size_breakdown AS prod_size_breakdown,
            u_qc.fullname AS qc_name
        ");
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_fg . ' fg', 'd.fg_id = fg.id', 'left');
        $this->db->join($this->table_prod_output . ' po', 'd.prod_output_id = po.id', 'left');
        $this->db->join($this->table_users . ' u_qc', 'po.qc_checked_by = u_qc.id', 'left');
        $this->db->where('d.fg_receipt_header_id', $header_id);
        return $this->db->get()->result();
    }

    // ==========================================================
    // SEARCH WO UNTUK RECEIPT
    // ==========================================================
    public function search_wo_for_receipt($term = '') {
        $subQueryReceived = "(
            SELECT prod_output_id, SUM(qty_received) AS total_received
            FROM {$this->table_details}
            WHERE prod_output_id IS NOT NULL
            GROUP BY prod_output_id
        )";

        $this->db->select("
            DISTINCT wo.id AS wo_id,
            wo.wo_number,
            sc.sc_number,
            fg_wo.nama_barang AS fg_name,
            ph.id AS production_header_id,
            ph.production_number
        ", FALSE);
        $this->db->from($this->table_wo_header . ' wo');
        $this->db->join($this->table_prod_header . ' ph', 'ph.wo_header_id = wo.id', 'inner');
        $this->db->join($this->table_prod_output . ' po', 'po.production_header_id = ph.id', 'inner');
        $this->db->join($this->table_fg . ' fg_po', 'po.item_id = fg_po.id AND po.item_type = "finished_goods"', 'inner');
        $this->db->join($this->table_fg . ' fg_wo', 'wo.fg_id = fg_wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join($subQueryReceived . ' receipt_calc', 'po.id = receipt_calc.prod_output_id', 'left');

        $this->db->where('po.item_type', 'finished_goods');
        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.qc_checked_at IS NOT NULL');
        $this->db->where('(po.qty_produced - IFNULL(receipt_calc.total_received, 0)) >', 0.00009);

        if (!empty($term)) {
            $term_esc = $this->db->escape_like_str($term);
            $this->db->group_start();
            $this->db->like('wo.wo_number', $term_esc);
            $this->db->or_like('sc.sc_number', $term_esc);
            $this->db->or_like('fg_wo.nama_barang', $term_esc);
            $this->db->or_like('ph.production_number', $term_esc);
            $this->db->group_end();
        }

        $this->db->order_by('wo.id', 'DESC');
        $this->db->limit(15);
        $query = $this->db->get();

        if (!$query) {
            $db_error = $this->db->error();
            log_message('error', 'Database Error [' . $db_error['code'] . '] ' . $db_error['message']);
            return [];
        }

        return $query->result();
    }

    // ==========================================================
    // GET FG OUTPUTS READY FOR RECEIPT (DENGAN SIZE)
    // ==========================================================
    public function get_receivable_fg_outputs_by_prod_id($production_header_id) {
        $subQueryReceived = "(
            SELECT prod_output_id, SUM(qty_received) AS total_received
            FROM {$this->table_details}
            WHERE prod_output_id IS NOT NULL
            GROUP BY prod_output_id
        )";

        $this->db->select("
            po.id AS prod_output_id, 
            po.qty_produced,
            po.size_breakdown,
            fg.id AS fg_id, fg.kode_barang AS fg_code, fg.nama_barang AS fg_name, fg.satuan AS uom,
            IFNULL(receipt_calc.total_received, 0) AS already_received_qty,
            (po.qty_produced - IFNULL(receipt_calc.total_received, 0)) AS remaining_qty
        ");
        $this->db->from($this->table_prod_output . ' po');
        $this->db->join($this->table_fg . ' fg', 'po.item_id = fg.id AND po.item_type = "finished_goods"', 'inner');
        $this->db->join($subQueryReceived . ' receipt_calc', 'po.id = receipt_calc.prod_output_id', 'left');

        $this->db->where('po.production_header_id', $production_header_id);
        $this->db->where('po.item_type', 'finished_goods');
        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.qc_checked_at IS NOT NULL');
        $this->db->having('remaining_qty >', 0.00009);

        return $this->db->get()->result();
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
    // KONFIRMASI RECEIPT -> TAMBAH STOK
    // ==========================================================
    public function confirm_receipt($id, $userId) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') return false;
        $details = $this->get_details_by_header_id($id);
        if (empty($details)) return false;

        $this->db->trans_start();

        // atomic update: ubah status hanya jika masih Draft (mengurangi race condition)
        $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, [
            'status' => 'Confirmed',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $userId
        ]);

        if ($this->db->affected_rows() == 0) {
            // ada kemungkinan sudah dikonfirmasi oleh proses lain
            $this->db->trans_rollback();
            return false;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($details as $detail) {
            // pastikan last_balance numeric
            $last_balance = (float) ($this->Stock_ledger_model->get_last_balance($detail->fg_id, 'finished_goods') ?? 0);

            // optional: ambil warehouse_id dari header (asumsi header punya warehouse_id)
            $warehouse_id = property_exists($header, 'warehouse_id') ? $header->warehouse_id : null;

            $ledger_data = [
                'item_id' => $detail->fg_id,
                'item_type' => 'finished_goods',
                'transaction_date' => $now,
                'transaction_type' => 'fg_in',
                'transaction_id' => $detail->id,
                'source_doc_type' => 'FG_RECEIPT',
                'source_doc_id' => $id,
                'qty_in' => (float)$detail->qty_received,
                'qty_out' => 0,
                'balance' => $last_balance + (float)$detail->qty_received,
                'warehouse_id' => $warehouse_id
            ];
            $this->Stock_ledger_model->insert($ledger_data);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
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
