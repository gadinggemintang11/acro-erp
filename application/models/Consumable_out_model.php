<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consumable_out_model extends CI_Model {

    private $table_header  = 'consumable_out_header';
    private $table_details = 'consumable_out_details';
    private $table_items   = 'inventories';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Stock_ledger_model');
    }

    // -------------------------------------------------------
    // Ambil semua dokumen dengan filter
    // -------------------------------------------------------
    public function get_all($filters = []) {
        $this->db->select('h.*, u.fullname as created_by_name, u2.fullname as confirmed_by_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u',  'h.created_by   = u.id',  'left');
        $this->db->join('users u2', 'h.confirmed_by = u2.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.doc_number',  $term);
            $this->db->or_like('h.department', $term);
            $this->db->or_like('h.recipient',  $term);
            $this->db->group_end();
        }
        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('h.issuance_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('h.issuance_date <=', $filters['date_to']);
        }

        $this->db->order_by('h.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil satu header
    // -------------------------------------------------------
    public function get_header($id) {
        $this->db->select('h.*, u.fullname as created_by_name, u2.fullname as confirmed_by_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u',  'h.created_by   = u.id',  'left');
        $this->db->join('users u2', 'h.confirmed_by = u2.id', 'left');
        $this->db->where('h.id', $id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    // -------------------------------------------------------
    // Ambil semua detail baris
    // -------------------------------------------------------
    public function get_details($header_id) {
        $query = $this->db->get_where($this->table_details, ['header_id' => $header_id]);
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil daftar item inventaris aktif
    // -------------------------------------------------------
    public function get_inventory_items() {
        $this->db->select('id, kode_barang, nama_barang, satuan, kategori');
        $this->db->from($this->table_items);
        $this->db->where('status', 'Active');
        $this->db->order_by('nama_barang', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    // -------------------------------------------------------
    // Ambil satu item inventaris
    // -------------------------------------------------------
    public function get_inventory_item($item_id) {
        $this->db->select('id, kode_barang, nama_barang, satuan, kategori');
        $this->db->from($this->table_items);
        $this->db->where('id', $item_id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    // -------------------------------------------------------
    // Generate nomor dokumen: CO/YYYYMM/0001
    // -------------------------------------------------------
    public function generate_doc_number() {
        $prefix = 'CO/' . date('Ym') . '/';
        // Prefix length = 10 chars, sequence starts at char 11
        $this->db->select_max('CAST(SUBSTRING(doc_number, 11, 4) AS UNSIGNED)', 'max_num');
        $this->db->like('doc_number', $prefix, 'after');
        $last = $this->db->get($this->table_header)->row();
        $new_num = ($last && $last->max_num) ? (int)$last->max_num + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------
    // Buat dokumen baru — status: Created (no stock impact)
    // -------------------------------------------------------
    public function create($header_data, $items) {
        $this->db->trans_start();

        $this->db->insert($this->table_header, $header_data);
        $header_id = $this->db->insert_id();

        if (!$header_id) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal menyimpan header.'];
        }

        foreach ($items as $item) {
            $item_id = (int)$item['item_id'];
            $qty     = (float)$item['qty'];
            $inv     = $this->get_inventory_item($item_id);

            $detail = [
                'header_id'  => $header_id,
                'item_id'    => $item_id,
                'item_code'  => $inv ? $inv->kode_barang  : ($item['item_code']  ?? ''),
                'item_name'  => $inv ? $inv->nama_barang  : ($item['item_name']  ?? ''),
                'item_unit'  => $inv ? $inv->satuan       : ($item['item_unit']  ?? ''),
                'qty_issued' => $qty,
                'notes'      => $item['notes'] ?? null,
            ];
            $this->db->insert($this->table_details, $detail);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }

        return [
            'success'    => true,
            'id'         => $header_id,
            'doc_number' => $header_data['doc_number'],
        ];
    }

    // -------------------------------------------------------
    // CONFIRM — proses stock (FIFO) dan update status → Confirmed
    // -------------------------------------------------------
    public function process_confirm($header_id, $user_id) {
        $this->db->trans_start();

        $details = $this->get_details($header_id);
        if (empty($details)) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Tidak ada item untuk dikonfirmasi.'];
        }

        foreach ($details as $d) {
            $item_id      = (int)$d->item_id;
            $item_type    = 'inventory';
            $qty_needed   = (float)$d->qty_issued;

            if ($qty_needed <= 0) continue;

            // Ambil batch FIFO dari stock_on_hand
            $available_batches = $this->db
                ->select('id, available_qty, unit_value')
                ->from('stock_on_hand')
                ->where('item_id',   $item_id)
                ->where('item_type', $item_type)
                ->where('available_qty >', 0)
                ->order_by('id', 'ASC') // FIFO: batch lama dulu
                ->get()->result();

            if (!empty($available_batches)) {
                // Ada batch: FIFO
                foreach ($available_batches as $batch) {
                    if ($qty_needed <= 0) break;

                    $batch_available = (float)$batch->available_qty;
                    $batch_cost      = (float)$batch->unit_value;
                    $qty_to_take     = min($batch_available, $qty_needed);
                    $new_batch_qty   = $batch_available - $qty_to_take;

                    // Kurangi available_qty di stock_on_hand
                    $this->db->where('id', $batch->id)
                             ->update('stock_on_hand', ['available_qty' => $new_batch_qty]);

                    // Insert ke stock_ledger
                    $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
                    $new_balance  = $last_balance - $qty_to_take;

                    $ledger_data = [
                        'item_id'          => $item_id,
                        'item_type'        => $item_type,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'transaction_type' => 'OUT_CONSUMABLE',
                        'transaction_id'   => $header_id,
                        'source_doc_type'  => 'CO',
                        'source_doc_id'    => $header_id,
                        'qty_in'           => 0,
                        'qty_out'          => $qty_to_take,
                        'unit_value'       => $batch_cost,
                        'balance'          => $new_balance,
                        'stock_on_hand_id' => $batch->id,
                    ];

                    if (!$this->Stock_ledger_model->insert($ledger_data)) {
                        $this->db->trans_rollback();
                        return ['success' => false, 'message' => 'Gagal insert stock ledger untuk item: ' . $d->item_name];
                    }

                    $qty_needed -= $qty_to_take;
                }

                if ($qty_needed > 0) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Stok tidak cukup untuk item: ' . $d->item_name . '. Kekurangan: ' . $qty_needed];
                }
            } else {
                // Tidak ada batch, langsung insert ledger tanpa traceability
                $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
                $new_balance  = $last_balance - $qty_needed;

                $ledger_data = [
                    'item_id'          => $item_id,
                    'item_type'        => $item_type,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_type' => 'OUT_CONSUMABLE',
                    'transaction_id'   => $header_id,
                    'source_doc_type'  => 'CO',
                    'source_doc_id'    => $header_id,
                    'qty_in'           => 0,
                    'qty_out'          => $qty_needed,
                    'unit_value'       => 0,
                    'balance'          => $new_balance,
                ];

                if (!$this->Stock_ledger_model->insert($ledger_data)) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal insert stock ledger untuk item: ' . $d->item_name];
                }
            }
        }

        // Update header status → Confirmed
        $this->db->where('id', $header_id)->update($this->table_header, [
            'status'       => 'Confirmed',
            'confirmed_by' => $user_id,
            'confirmed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat konfirmasi.'];
        }

        return ['success' => true];
    }

    // -------------------------------------------------------
    // CANCEL — reverse stock dan update status → Cancelled
    // -------------------------------------------------------
    public function process_cancel($header_id, $user_id) {
        $this->db->trans_start();

        // Ambil semua entri ledger yang terkait dokumen ini
        $ledger_entries = $this->db->get_where('stock_ledger', [
            'transaction_type' => 'OUT_CONSUMABLE',
            'transaction_id'   => $header_id,
        ])->result();

        if (!empty($ledger_entries)) {
            foreach ($ledger_entries as $entry) {
                $qty_to_return   = (float)$entry->qty_out;
                $stock_on_hand_id = $entry->stock_on_hand_id ?? null;

                // Kembalikan ke batch stock_on_hand jika ada
                if ($stock_on_hand_id) {
                    $this->db->where('id', $stock_on_hand_id)
                             ->set('available_qty', "available_qty + $qty_to_return", FALSE)
                             ->update('stock_on_hand');
                }

                // Insert reversal ke stock_ledger
                $last_balance = $this->Stock_ledger_model->get_last_balance($entry->item_id, $entry->item_type);
                $new_balance  = $last_balance + $qty_to_return;

                $reversal = [
                    'item_id'          => $entry->item_id,
                    'item_type'        => $entry->item_type,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'transaction_type' => 'IN_RETURN_CONSUMABLE',
                    'transaction_id'   => $header_id,
                    'source_doc_type'  => 'CO_CANCEL',
                    'source_doc_id'    => $header_id,
                    'qty_in'           => $qty_to_return,
                    'qty_out'          => 0,
                    'unit_value'       => $entry->unit_value,
                    'balance'          => $new_balance,
                    'stock_on_hand_id' => $stock_on_hand_id,
                ];

                $this->Stock_ledger_model->insert($reversal);
            }
        }

        // Update header status → Cancelled
        $this->db->where('id', $header_id)->update($this->table_header, [
            'status'    => 'Cancelled',
            'cancelled_by' => $user_id,
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat pembatalan.'];
        }

        return ['success' => true];
    }

    // -------------------------------------------------------
    // Hapus dokumen (hanya Created)
    // -------------------------------------------------------
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table_header);
    }
}
