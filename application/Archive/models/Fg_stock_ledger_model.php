<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fg_stock_ledger_model extends CI_Model {

    private $table = 'stock_ledger';
    // Tambahkan nama tabel lain untuk join
    private $table_fg = 'finished_goods';
    private $table_receipt_header = 'fg_receipt_header';
    private $table_receipt_details = 'fg_receipt_details';
    private $table_delivery_header = 'fg_delivery_header'; // Asumsi nama tabel pengiriman
    private $table_delivery_details = 'fg_delivery_details'; // Asumsi nama tabel detail pengiriman
    private $table_prod_header = 'production_header';
    private $table_wo_header = 'wo_header';
    private $table_sc = 'sales_contracts';
    private $table_common_code = 'common_code';
    private $table_users = 'users';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Menyimpan transaksi HANYA untuk Finished Goods ke stock ledger.
     */
    public function insert($data) {
        // Pastikan item_type selalu finished_goods
        $data['item_type'] = 'finished_goods';

        // Sanitasi qty
        $qty_in = isset($data['qty_in']) ? (float)$data['qty_in'] : 0.0;
        $qty_out = isset($data['qty_out']) ? (float)$data['qty_out'] : 0.0;
        $data['qty_in'] = $qty_in;
        $data['qty_out'] = $qty_out;

        // Transaction: baca saldo terakhir lalu insert secara atomik untuk mengurangi race condition
        $this->db->trans_start();

        // Ambil last balance (model ini sudah filter item_type = 'finished_goods')
        $last_balance = $this->get_last_balance($data['item_id']);
        $data['balance'] = $last_balance + $qty_in - $qty_out;

        // Jika tidak ada transaction_date, set sekarang (disarankan caller mengisi)
        if (empty($data['transaction_date'])) {
            $data['transaction_date'] = date('Y-m-d H:i:s');
        }

        $ok = $this->db->insert($this->table, $data);
        if (!$ok) {
            $db_error = $this->db->error();
            log_message('error', 'Failed to insert FG stock ledger: ' . print_r($data, true) . ' DB Error: ' . print_r($db_error, true));
            $this->db->trans_complete();
            return false;
        }

        $insert_id = $this->db->insert_id();
        $this->db->trans_complete();

        // Pastikan transaksi sukses
        if (!$this->db->trans_status()) {
            log_message('error', 'Transaction failed while inserting FG stock ledger: ' . print_r($data, true));
            return false;
        }

        return $insert_id;
    }


    /**
     * Mengambil saldo terakhir HANYA untuk Finished Good tertentu.
     */
    public function get_last_balance($fg_id) {
        if (empty($fg_id)) return 0.0;

        $this->db->select('balance');
        $this->db->where('item_id', $fg_id);
        $this->db->where('item_type', 'finished_goods'); // Filter FG
        $this->db->order_by('transaction_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        $result = $query->row();

        return $result ? (float)$result->balance : 0.0;
    }

    /**
     * FUNGSI BARU: Mengambil ringkasan stok (saldo terakhir) untuk SEMUA Finished Goods.
     */
    public function get_current_stock_summary($filters = []) {
        // Subquery untuk menemukan ID entri ledger terakhir untuk setiap item FG
        $subquery = $this->db
            ->select('MAX(id) as max_id')
            ->from($this->table)
            ->where('item_type', 'finished_goods')
            ->group_by('item_id')
            ->get_compiled_select();

        $this->db->select("
            sl.balance as current_stock,
            fg.id as fg_id, fg.kode_barang as fg_code, fg.nama_barang as fg_name, fg.satuan as uom
        ");
        $this->db->from($this->table . ' sl');
        $this->db->join("($subquery) sq", 'sl.id = sq.max_id', 'inner');
        $this->db->join($this->table_fg . ' fg', 'sl.item_id = fg.id', 'left');

        if (!empty($filters['search_summary'])) {
             $term = $this->db->escape_like_str($filters['search_summary']);
             $this->db->group_start();
             $this->db->like('fg.kode_barang', $term);
             $this->db->or_like('fg.nama_barang', $term);
             $this->db->group_end();
        }
        
        $this->db->order_by('fg.nama_barang', 'ASC');
        $query = $this->db->get();

        if (!$query) {
            $db_error = $this->db->error();
            log_message('error', '[FG Stock Summary] DB Error [' . $db_error['code'] . '] ' . $db_error['message'] . ' - Query: ' . $this->db->last_query());
            return [];
        }
        return $query->result();
    }


    /**
     * Mengambil data Stock Ledger HANYA untuk Finished Goods (Kartu Stok Detail)
     * (PERBAIKAN FINAL: Hapus SEMUA komentar dari dalam string SELECT)
     */
    public function get_ledger($filters = []) {
        $this->db->select("
            sl.id, sl.transaction_date, sl.transaction_type, sl.qty_in, sl.qty_out, sl.balance,
            sl.source_doc_type, sl.source_doc_id, sl.transaction_id,
            fg.kode_barang as fg_code, fg.nama_barang as fg_name, fg.satuan as uom,
            frh.receipt_number, frh.receipt_date,
            ph.production_number, wo.wo_number, sc_prod.sc_number as sc_number_prod,
            fdh.delivery_number, fdh.delivery_date, sc_del.sc_number as sc_number_delivery
        ", FALSE);

        $this->db->from($this->table . ' sl');
        $this->db->join($this->table_fg . ' fg', 'sl.item_id = fg.id', 'inner');

        // Join untuk Receipt
        // PERBAIKAN: Hapus source_doc_type FG_UNCONFIRM dan FG_CANCEL
        // karena sekarang unconfirm hanya DELETE entry fg_in, tidak insert reversal
        $this->db->join($this->table_receipt_details . ' frd', "sl.transaction_id = frd.id AND sl.source_doc_type = 'FG_RECEIPT'", 'left');
        $this->db->join($this->table_receipt_header . ' frh', 'frd.fg_receipt_header_id = frh.id', 'left');
        $this->db->join($this->table_prod_header . ' ph', 'frh.production_header_id = ph.id', 'left');
        $this->db->join($this->table_wo_header . ' wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join($this->table_sc . ' sc_prod', 'wo.sales_contract_id = sc_prod.id', 'left');

        // Join untuk Delivery
        // PERBAIKAN: Hapus source_doc_type FG_UNSHIP dan FG_OUT_REVERSAL 
        // karena sekarang unship hanya DELETE entry fg_out, tidak insert reversal
        $this->db->join($this->table_delivery_details . ' fdd', "sl.transaction_id = fdd.id AND sl.source_doc_type = 'FG_DELIVERY'", 'left');
        $this->db->join($this->table_delivery_header . ' fdh', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->join($this->table_sc . ' sc_del', 'fdh.sales_contract_id = sc_del.id', 'left');

        $this->db->where('sl.item_type', 'finished_goods');

        // Filters
        if (!empty($filters['fg_id']) && $filters['fg_id'] != 'all') {
            $this->db->where('sl.item_id', $filters['fg_id']);
        }
        if (!empty($filters['start_date'])) {
            $this->db->where('DATE(sl.transaction_date) >=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('DATE(sl.transaction_date) <=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('frh.receipt_number', $term);
            $this->db->or_like('ph.production_number', $term);
            $this->db->or_like('wo.wo_number', $term);
            $this->db->or_like('sc_prod.sc_number', $term);
            $this->db->or_like('fdh.delivery_number', $term);
            $this->db->or_like('sc_del.sc_number', $term);
            $this->db->group_end();
        }

        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            $db_error = $this->db->error();
            log_message('error', '[FG Ledger] DB Error [' . $db_error['code'] . '] ' . $db_error['message'] . ' - Query: ' . $this->db->last_query());
            return [];
        }

        return $query->result();
    }


    /**
     * Mengambil Saldo Awal HANYA FG sebelum tanggal tertentu
     */
    public function get_opening_balance($fg_id, $start_date) {
        if (empty($start_date) || empty($fg_id)) {
            return 0.0;
        }
        $this->db->select('balance');
        $this->db->where('item_id', $fg_id);
        $this->db->where('item_type', 'finished_goods');
        $this->db->where('DATE(transaction_date) <', $start_date);
        $this->db->order_by('transaction_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        $result = $query->row();
        return $result ? (float)$result->balance : 0.0;
    }
    public function get_trace_header($doc_type, $doc_id)
    {
        switch (strtoupper($doc_type)) {
            case 'FG_RECEIPT':
                return $this->db
                    ->select('frh.id as source_id, frh.receipt_number as source_number, "FG_RECEIPT" as source_type, frh.receipt_date as source_date, frh.remarks')
                    ->from('fg_receipt_header frh')
                    ->where('frh.id', $doc_id)
                    ->get()->row_array();

            case 'FG_DELIVER':
            case 'FG_DELIVERY':
                return $this->db
                    ->select('fdh.id as source_id, fdh.delivery_number as source_number, "FG_DELIVER" as source_type, fdh.delivery_date as source_date, fdh.remarks')
                    ->from('fg_delivery_header fdh')
                    ->where('fdh.id', $doc_id)
                    ->get()->row_array();

            case 'PROD_RETURN':
                return $this->db
                    ->select('ph.id as source_id, ph.return_number as source_number, "PROD_RETURN" as source_type, ph.return_date as source_date, ph.remarks')
                    ->from('prod_return_header ph')
                    ->where('ph.id', $doc_id)
                    ->get()->row_array();

            default:
                return [
                    'source_id'     => $doc_id,
                    'source_number' => $doc_id,
                    'source_type'   => $doc_type,
                    'source_date'   => '-',
                    'remarks'       => ''
                ];
        }
    }
    public function get_trace_rows_by_doc($doc_type, $doc_id, $transaction_id = null)
    {
        $this->db->select("
            sl.id,
            sl.transaction_date,
            sl.transaction_type,
            sl.qty_in,
            sl.qty_out,
            sl.balance,
            sl.item_id,
            sl.item_type,
            sl.source_doc_type,
            sl.source_doc_id,
            fg.kode_barang,
            fg.nama_barang,
            fg.satuan,
            frh.receipt_number,
            frh.receipt_date,
            ph.production_number,
            wo.wo_number,
            sc_prod.sc_number AS sc_number_in,
            fdh.delivery_number,
            fdh.delivery_date,
            sc_del.sc_number AS sc_number_out,
            fdh.bc_number,
            cc.description AS bc_type_desc,
            fdd.qty_shipped
        ", FALSE);

        $this->db->from('stock_ledger sl');
        $this->db->join('finished_goods fg', 'sl.item_id = fg.id', 'left');

        // FG_RECEIPT
        $this->db->join('fg_receipt_details frd', "sl.transaction_id = frd.id AND sl.source_doc_type IN ('FG_RECEIPT', 'FG_UNCONFIRM')", 'left');
        $this->db->join('fg_receipt_header frh', 'frd.fg_receipt_header_id = frh.id', 'left');
        $this->db->join('production_header ph', 'frh.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc_prod', 'wo.sales_contract_id = sc_prod.id', 'left');

        // FG_DELIVER
        $this->db->join('fg_delivery_details fdd', "sl.transaction_id = fdd.id AND sl.source_doc_type IN ('FG_DELIVER', 'FG_DELIVERY')", 'left');
        $this->db->join('fg_delivery_header fdh', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->join('sales_contracts sc_del', 'fdh.sales_contract_id = sc_del.id', 'left');
        $this->db->join('common_code cc', "fdh.bc_type_id = cc.id AND cc.group_code = 'BC_TYPE'", 'left');

        // WHERE logic — fix disini
        $this->db->where('sl.item_type', 'finished_goods');
        $this->db->group_start();
            $this->db->group_start();
                $this->db->where('sl.source_doc_type', strtoupper($doc_type));
                $this->db->where('sl.source_doc_id', $doc_id);
            $this->db->group_end();

            if (!empty($transaction_id)) {
                $this->db->or_where('sl.transaction_id', $transaction_id);
            }
        $this->db->group_end(); // <- ini penutup yang sebelumnya hilang

        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            $error = $this->db->error();
            log_message('error', '[get_trace_rows_by_doc] DB Error: ' . $error['message']);
            return [];
        }

        return $query->result_array();
    }



} // End Class