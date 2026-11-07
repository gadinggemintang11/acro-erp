<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stock_transfer_model
 *
 * Mindahin saldo stock antar bucket kategori (General <-> Produksi) untuk
 * item yang sama — item_type inventory/machine, sesuai split di
 * migration_stock_category_split.sql. Dicatat sebagai sepasang baris
 * stock_ledger: OUT dari kategori asal, IN ke kategori tujuan.
 */
class Stock_transfer_model extends CI_Model {

    private $table_header  = 'stock_transfer_header';
    private $table_details = 'stock_transfer_details';

    const CATEGORIES = ['General', 'Produksi'];

    public function __construct() {
        parent::__construct();
        $this->load->model('Stock_ledger_model');
    }

    public function generate_transfer_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('stock_transfer', 'stock_transfer_header', 'transfer_number');
    }

    public function get_all($filters = []) {
        $this->db->select('h.*, u.fullname as created_by_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u', 'h.created_by = u.id', 'left');
        if (!empty($filters['search'])) {
            $this->db->like('h.transfer_number', $filters['search']);
        }
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header($id) {
        $this->db->select('h.*, u.fullname as created_by_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u', 'h.created_by = u.id', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function get_details($header_id) {
        return $this->db->get_where($this->table_details, ['header_id' => $header_id])->result();
    }

    /**
     * Barang (inventories + machines) beserta saldo di kategori asal —
     * dipakai buat picker item di form transfer (mirip Consumable modules).
     */
    public function get_items_with_stock($from_category) {
        $cat = $this->db->escape($from_category);
        $sql = "
            SELECT * FROM (
                SELECT i.id,
                       CONCAT('inventory-', i.id) AS item_key,
                       'inventory' AS item_type,
                       i.kode_barang, i.nama_barang, i.satuan,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = i.id AND item_type = 'inventory' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM inventories i
                WHERE i.status = 'Active'

                UNION ALL

                SELECT m.id,
                       CONCAT('machine-', m.id) AS item_key,
                       'machine' AS item_type,
                       m.kode_mesin AS kode_barang, m.nama_mesin AS nama_barang,
                       COALESCE(m.satuan, 'UNIT') AS satuan,
                       COALESCE((SELECT balance FROM stock_ledger
                                 WHERE item_id = m.id AND item_type = 'machine' AND category = $cat
                                 ORDER BY id DESC LIMIT 1), 0) as stock
                FROM machines m
                WHERE m.status IS NULL OR m.status <> 'Nonaktif'
            ) x
            HAVING stock > 0
            ORDER BY x.item_type ASC, x.nama_barang ASC";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    private function resolve_item_ref($raw_key) {
        $raw = trim((string)$raw_key);
        if (strpos($raw, '-') !== false) {
            list($type, $id) = explode('-', $raw, 2);
            if (in_array($type, ['inventory', 'machine'], true)) {
                return ['item_id' => (int)$id, 'item_type' => $type];
            }
        }
        return ['item_id' => (int)$raw, 'item_type' => 'inventory'];
    }

    private function get_item_master($item_id, $item_type) {
        if ($item_type === 'machine') {
            return $this->db->select('kode_mesin as kode_barang, nama_mesin as nama_barang, satuan')
                ->where('id', $item_id)->get('machines')->row();
        }
        return $this->db->select('kode_barang, nama_barang, satuan')
            ->where('id', $item_id)->get('inventories')->row();
    }

    /**
     * Eksekusi transfer: validasi saldo cukup di kategori asal, lalu tulis
     * sepasang baris stock_ledger (OUT asal, IN tujuan) per item dalam 1
     * transaksi. Langsung efektif — tidak ada tahap Draft/Confirm terpisah,
     * karena ini murni koreksi saldo administratif antar bucket sendiri
     * (bukan penerimaan/pengeluaran fisik ke pihak luar).
     */
    public function create_transfer($header_data, $items) {
        if ($header_data['from_category'] === $header_data['to_category']) {
            return ['success' => false, 'message' => 'Kategori asal & tujuan tidak boleh sama.'];
        }

        // Validasi saldo cukup dulu, sebelum nulis apapun.
        foreach ($items as $item) {
            $ref = $this->resolve_item_ref($item['item_id']);
            $qty = (float)$item['qty'];
            if ($qty <= 0) continue;
            $current = $this->Stock_ledger_model->get_last_balance($ref['item_id'], $ref['item_type'], $header_data['from_category']);
            if ($current < $qty) {
                $inv = $this->get_item_master($ref['item_id'], $ref['item_type']);
                $name = $inv->nama_barang ?? ('item #' . $ref['item_id']);
                return ['success' => false, 'message' => "Stok {$header_data['from_category']} tidak cukup untuk $name. Tersedia: $current, diminta: $qty."];
            }
        }

        $this->db->trans_start();

        $header_data['transfer_number'] = $this->generate_transfer_number();
        $this->db->insert($this->table_header, $header_data);
        $header_id = $this->db->insert_id();
        if (!$header_id) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal membuat header transfer.'];
        }

        $now = date('Y-m-d H:i:s');
        foreach ($items as $item) {
            $ref = $this->resolve_item_ref($item['item_id']);
            $qty = (float)$item['qty'];
            if ($qty <= 0) continue;

            $inv = $this->get_item_master($ref['item_id'], $ref['item_type']);

            $this->db->insert($this->table_details, [
                'header_id' => $header_id,
                'item_id'   => $ref['item_id'],
                'item_type' => $ref['item_type'],
                'item_code' => $inv->kode_barang ?? '',
                'item_name' => $inv->nama_barang ?? '',
                'item_unit' => $inv->satuan ?? '',
                'qty'       => $qty,
            ]);

            // OUT dari kategori asal
            $bal_from = $this->Stock_ledger_model->get_last_balance($ref['item_id'], $ref['item_type'], $header_data['from_category']);
            $this->Stock_ledger_model->insert([
                'item_id'          => $ref['item_id'],
                'item_type'        => $ref['item_type'],
                'category'         => $header_data['from_category'],
                'transaction_date' => $now,
                'transaction_type' => 'STOCK_TRANSFER_OUT',
                'transaction_id'   => $header_id,
                'source_doc_type'  => 'STOCK_TRANSFER',
                'source_doc_id'    => $header_id,
                'qty_in'           => 0,
                'qty_out'          => $qty,
                'unit_value'       => 0,
                'balance'          => $bal_from - $qty,
            ]);

            // IN ke kategori tujuan
            $bal_to = $this->Stock_ledger_model->get_last_balance($ref['item_id'], $ref['item_type'], $header_data['to_category']);
            $this->Stock_ledger_model->insert([
                'item_id'          => $ref['item_id'],
                'item_type'        => $ref['item_type'],
                'category'         => $header_data['to_category'],
                'transaction_date' => $now,
                'transaction_type' => 'STOCK_TRANSFER_IN',
                'transaction_id'   => $header_id,
                'source_doc_type'  => 'STOCK_TRANSFER',
                'source_doc_id'    => $header_id,
                'qty_in'           => $qty,
                'qty_out'          => 0,
                'unit_value'       => 0,
                'balance'          => $bal_to + $qty,
            ]);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat transfer.'];
        }
        return ['success' => true, 'id' => $header_id, 'transfer_number' => $header_data['transfer_number']];
    }
}
