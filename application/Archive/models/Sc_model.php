<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sc_model extends CI_Model {

    // --- Definisi Nama Tabel (PENTING) ---
    private $table_sc = 'sales_contracts'; // Nama tabel Sales Contract
    private $table_customer = 'customers';     // Nama tabel Customer
    private $table_fg = 'finished_goods'; // Definisi untuk finished_goods
    private $table_sc_details = 'sales_contract_details'; // Asumsi nama tabel detail SC
    // --- Akhir Definisi ---

    public function __construct() {
        parent::__construct();
        $this->load->database();
    } // <-- Error 100% ada di sekitar sini, pastikan '}' dan ';' benar

    /**
     * Mengambil semua header Sales Contract
     * (PERBAIKAN: Hapus alias 'as customer_name' agar view tidak error)
     */
    public function get_all() { // <-- Ini kemungkinan baris 15 tempat error terdeteksi
        $this->db->select('
            sc.id, sc.sc_number, sc.contract_date, sc.po_qty, sc.total_price, sc.currency_code, sc.status,
            c.nama_customer,
            fg.nama_barang as style_name
        ');
        $this->db->from($this->table_sc . ' sc');
        $this->db->join($this->table_customer . ' c', 'sc.customer_id = c.id', 'left');
        $this->db->join($this->table_fg . ' fg', 'sc.fg_id = fg.id', 'left');
        $this->db->order_by('sc.id', 'DESC');
        
        $query = $this->db->get();
        if (!$query) {
             log_message('error', '[Sc_model] DB Error get_all: ' . print_r($this->db->error(), true) . ' Query: ' . $this->db->last_query());
             return [];
         }
        return $query->result();
    }

    /**
     * Mengambil satu baris Sales Contract berdasarkan ID
     */
    public function get_by_id($id) {
         if (empty($id) || !is_numeric($id)) return null;
        $query = $this->db->where('id', $id)->get($this->table_sc);
         if (!$query) {
             log_message('error', '[Sc_model] DB Error get_by_id: ' . print_r($this->db->error(), true) . ' Query: ' . $this->db->last_query());
             return null;
         }
        return $query->row();
    }

    /**
     * Mengambil SC yang statusnya Open (contoh filter)
     */
    public function get_open_contracts() {
    $this->db->select('
        sc.id, sc.sc_number, sc.contract_date,
        c.nama_customer as customer_name
    ');
    $this->db->from('sales_contracts sc');
    $this->db->join('customers c', 'sc.customer_id = c.id', 'left'); // <--- TAMBAHKAN JOIN ini
    $this->db->where('sc.status', 'Open'); // Atau filter status "Open" lainnya
    // $this->db->where(...); // Filter lain jika ada
    $this->db->order_by('sc.sc_number', 'ASC');
    return $this->db->get()->result();
}

    /**
     * Menyimpan data Sales Contract baru
     */
    public function insert($data) {
        return $this->db->insert($this->table_sc, $data);
    }

    /**
     * Mengupdate data Sales Contract
     */
    public function update($id, $data) {
         if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, $data);
    }

    /**
     * Soft delete (mengubah status menjadi Cancelled)
     */
    public function delete($id) {
         if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, ['status' => 'Cancelled']);
    }

    /**
     * Generate nomor SC baru
     */
    public function generate_sc_number() {
        $prefix = 'SC/' . date('Y') . '/';
        $this->db->like('sc_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_sc, 1)->row();

        if ($last) {
            $last_num = (int) substr($last->sc_number, -5);
            $new_num = str_pad($last_num + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $new_num = '00001';
        }
        return $prefix . $new_num;
    }

    /**
     * Mengambil data header Sales Contract berdasarkan ID,
     * termasuk join ke tabel customer.
     * (Fungsi ini dibutuhkan oleh Fg_delivery controller)
     */
    public function get_header_with_customer($id) {
        if (empty($id) || !is_numeric($id)) {
            return null;
        }

        $this->db->select("
            sc.*,
            cust.nama_customer as customer_name,
            cust.alamat as customer_address
        ");
        $this->db->from($this->table_sc . ' sc');
        $this->db->join($this->table_customer . ' cust', 'sc.customer_id = cust.id', 'left');
        $this->db->where('sc.id', $id);
        $query = $this->db->get();

        if (!$query) {
             log_message('error', '[Sc_model] DB Error get_header_with_customer: ' . print_r($this->db->error(), true) . ' Query: ' . $this->db->last_query());
             return null;
        }
        return $query->row();
    }

    /**
     * Mengambil detail item dari Sales Contract
     * (Asumsi 1 item per SC dari tabel header)
     */
    public function get_details_by_header_id($sc_header_id) {
        if (empty($sc_header_id) || !is_numeric($sc_header_id)) {
            return [];
        }
        // Cek jika tabel detail TIDAK ADA, ambil dari header
        if (!$this->db->table_exists($this->table_sc_details)) {
            
            // Ambil dari header SC
            $this->db->select("
                sc.fg_id, sc.po_qty as qty, 0 as qty_delivered,
                fg.kode_barang, fg.nama_barang, fg.satuan
            ");
            $this->db->from($this->table_sc . ' sc');
            $this->db->join($this->table_fg . ' fg', 'sc.fg_id = fg.id', 'left');
            $this->db->where('sc.id', $sc_header_id);
            $query = $this->db->get();
            if (!$query) { /* Error logging */ return []; }
            return $query->result();

        } else {
            // Jika tabel detail ADA, ambil dari sana
            $this->db->select("
                scd.*,
                fg.kode_barang, fg.nama_barang, fg.satuan
            ");
            $this->db->from($this->table_sc_details . ' scd');
            $this->db->join($this->table_fg . ' fg', 'scd.fg_id = fg.id', 'left');
            $this->db->where('scd.sales_contract_id', $sc_header_id);
            $query = $this->db->get();
             if (!$query) {
                 log_message('error', '[Sc_model] DB Error get_details_by_header_id: ' . print_r($this->db->error(), true));
                 return [];
             }
            return $query->result();
        }
    }
    public function get_by_customer($customer_id, $status = 'Open') {
        if (empty($customer_id)) return [];

        $this->db->select('id, sc_number, fg_id, contract_date, po_qty, destination, status');
        $this->db->from($this->table_sc); // ✅ perbaikan penting
        $this->db->where('customer_id', $customer_id);

        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        $this->db->order_by('contract_date', 'DESC');
        $query = $this->db->get();

        if (!$query) {
            log_message('error', '[Sc_model] get_by_customer DB Error: '.print_r($this->db->error(), true));
            return [];
        }
        return $query->result();
    }

    /**
     * Mengambil item Finished Goods dari Sales Contract untuk Load BOM
     * Returns: array of objects dengan fg_id, qty, kode_barang, nama_barang
     */
    public function get_items_by_contract($sc_id) {
        if (empty($sc_id) || !is_numeric($sc_id)) {
            return [];
        }

        // Cek apakah tabel detail ada
        if (!$this->db->table_exists($this->table_sc_details)) {
            // Ambil dari header SC (asumsi 1 FG per SC)
            $this->db->select("
                sc.fg_id,
                sc.po_qty as qty,
                fg.kode_barang,
                fg.nama_barang,
                'finished' as item_type
            ");
            $this->db->from($this->table_sc . ' sc');
            $this->db->join($this->table_fg . ' fg', 'sc.fg_id = fg.id', 'left');
            $this->db->where('sc.id', $sc_id);
            $this->db->where('sc.fg_id IS NOT NULL');
            
            $query = $this->db->get();
            if (!$query) {
                log_message('error', '[Sc_model] get_items_by_contract DB Error: ' . print_r($this->db->error(), true));
                return [];
            }
            return $query->result();
            
        } else {
            // Ambil dari tabel detail
            $this->db->select("
                scd.fg_id,
                scd.qty,
                fg.kode_barang,
                fg.nama_barang,
                'finished' as item_type
            ");
            $this->db->from($this->table_sc_details . ' scd');
            $this->db->join($this->table_fg . ' fg', 'scd.fg_id = fg.id', 'left');
            $this->db->where('scd.sales_contract_id', $sc_id);
            $this->db->where('scd.fg_id IS NOT NULL');
            
            $query = $this->db->get();
            if (!$query) {
                log_message('error', '[Sc_model] get_items_by_contract DB Error: ' . print_r($this->db->error(), true));
                return [];
            }
            return $query->result();
        }
    }

    
} // End Class