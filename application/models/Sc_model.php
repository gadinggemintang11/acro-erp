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
        return $this->_get_with_filter(null);
    }

    /**
     * Ambil SC dengan filter status (string atau array).
     * NULL = semua status.
     */
    public function get_by_status($status) {
        return $this->_get_with_filter($status);
    }

    /**
     * Ambil SC status Draft saja (untuk menu Create New IPO).
     */
    public function get_drafts() {
        return $this->_get_with_filter('Draft');
    }

    /**
     * Ambil SC yang menunggu endorsement (untuk user can_endorsement).
     */
    public function get_pending_endorsement() {
        return $this->_get_with_filter('Pending Endorsement');
    }

    /**
     * Ambil SC yang sudah di-endorse, menunggu approval final (untuk user can_approval).
     */
    public function get_pending_approval() {
        // Approval 1 tahap (2026-11-06): semua SO yang menunggu, termasuk sisa alur lama.
        return $this->_get_with_filter(['Submitted', 'Pending Endorsement', 'Endorsed']);
    }

    /**
     * Helper internal: query header SC dengan filter status opsional.
     * Mengikutkan kolom audit approval workflow + nama user (jika tabel users ada).
     */
    private function _get_with_filter($status = null) {
        $this->db->select('
            sc.id, sc.sc_number, sc.contract_date, sc.lchd_date, sc.po_qty,
            sc.total_price, sc.currency_code, sc.status, sc.destination,
            sc.submitted_by, sc.submitted_at,
            sc.endorsed_by, sc.endorsed_at,
            sc.approved_by, sc.approved_at,
            sc.rejected_by, sc.rejected_at, sc.reject_reason,
            c.nama_customer,
            fg.nama_barang as style_name,
            us.fullname as submitted_by_name,
            ue.fullname as endorsed_by_name,
            ua.fullname as approved_by_name,
            ur.fullname as rejected_by_name
        ');
        $this->db->from($this->table_sc . ' sc');
        $this->db->join($this->table_customer . ' c', 'sc.customer_id = c.id', 'left');
        $this->db->join($this->table_fg . ' fg', 'sc.fg_id = fg.id', 'left');
        $this->db->join('users us', 'us.id = sc.submitted_by', 'left');
        $this->db->join('users ue', 'ue.id = sc.endorsed_by', 'left');
        $this->db->join('users ua', 'ua.id = sc.approved_by', 'left');
        $this->db->join('users ur', 'ur.id = sc.rejected_by', 'left');

        if (!empty($status)) {
            if (is_array($status)) {
                $this->db->where_in('sc.status', $status);
            } else {
                $this->db->where('sc.status', $status);
            }
        }

        $this->db->order_by('sc.id', 'DESC');

        $query = $this->db->get();
        if (!$query) {
            log_message('error', '[Sc_model] DB Error _get_with_filter: ' . print_r($this->db->error(), true) . ' Query: ' . $this->db->last_query());
            return [];
        }
        return $query->result();
    }

    /**
     * Submit Draft → Pending Endorsement (oleh creator).
     */
    public function submit_for_approval($id, $user_id) {
        if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, [
            'status'       => 'Submitted', // 1 tahap (2026-11-06); dulu 'Pending Endorsement'
            'submitted_by' => $user_id,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Endorse: Pending Endorsement → Endorsed (oleh user can_endorsement).
     */
    public function endorse($id, $user_id) {
        if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, [
            'status'      => 'Endorsed',
            'endorsed_by' => $user_id,
            'endorsed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Approve final: Endorsed → Open (oleh user can_approval).
     * Pakai status 'Open' biar kompatibel dengan PR/WO/BOM/Fg_delivery existing.
     */
    public function approve($id, $user_id) {
        if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, [
            'status'      => 'Open',
            'approved_by' => $user_id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reject: status apapun → Rejected (dengan alasan).
     */
    public function reject($id, $user_id, $reason) {
        if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, [
            'status'        => 'Rejected',
            'rejected_by'   => $user_id,
            'rejected_at'   => date('Y-m-d H:i:s'),
            'reject_reason' => $reason,
        ]);
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
     * Cek apakah nomor SC sudah digunakan
     */
    public function is_sc_number_exists($sc_number) {
        if (empty($sc_number)) return false;
        return $this->db->where('sc_number', $sc_number)->count_all_results($this->table_sc) > 0;
    }

    /**
     * Ambil customer_id berdasarkan kode customer
     */
    public function get_customer_id_by_code($kode_customer) {
        if (empty($kode_customer)) return null;
        $row = $this->db->select('id')->from($this->table_customer)->where('kode_customer', $kode_customer)->get()->row();
        return $row ? $row->id : null;
    }

    /**
     * Ambil customer_id berdasarkan nama supplier/customer
     */
    public function get_customer_id_by_name($nama_customer) {
        if (empty($nama_customer)) return null;
        $row = $this->db->select('id')->from($this->table_customer)->where('nama_customer', $nama_customer)->get()->row();
        return $row ? $row->id : null;
    }

    /**
     * Ambil fg_id berdasarkan kode barang/style
     */
    public function get_fg_id_by_code($kode_barang) {
        if (empty($kode_barang)) return null;
        $row = $this->db->select('id')->from($this->table_fg)->where('kode_barang', $kode_barang)->get()->row();
        return $row ? $row->id : null;
    }

    /**
     * Ambil fg_id berdasarkan nama barang
     */
    public function get_fg_id_by_name($nama_barang) {
        if (empty($nama_barang)) return null;
        $row = $this->db->select('id')->from($this->table_fg)->where('nama_barang', $nama_barang)->get()->row();
        return $row ? $row->id : null;
    }

    /**
     * Mengupdate data Sales Contract
     */
    public function update($id, $data) {
         if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, $data);
    }

    /**
     * Update status Sales Contract
     */
    public function update_status($id, $status) {
        if (empty($id) || !is_numeric($id)) return false;
        return $this->db->where('id', $id)->update($this->table_sc, ['status' => $status]);
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
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('sales_order', 'sales_contracts', 'sc_number');
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


    /**
     * Ambil daftar Finished Goods yang memiliki Sales Contract dengan status Open.
     * Digunakan untuk dropdown "Load dari Model" di Purchase Request.
     */
    public function get_fg_with_open_sc() {
        $this->db->select('fg.id, fg.kode_barang, fg.nama_barang, SUM(sc.po_qty) as total_qty, COUNT(sc.id) as sc_count');
        $this->db->from('finished_goods fg');
        $this->db->join('sales_contracts sc', "sc.fg_id = fg.id AND sc.status = 'Open'", 'inner');
        $this->db->group_by('fg.id, fg.kode_barang, fg.nama_barang');
        $this->db->order_by('fg.kode_barang', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Ambil total qty dari semua Sales Contract Open untuk FG tertentu.
     * Returns object dengan total_qty dan sc_numbers (comma-separated SC numbers).
     */
    public function get_open_sc_total_qty_by_fg($fg_id) {
        if (empty($fg_id) || !is_numeric($fg_id)) return null;
        $this->db->select("SUM(po_qty) as total_qty, GROUP_CONCAT(sc_number ORDER BY sc_number SEPARATOR ', ') as sc_numbers");
        $this->db->from('sales_contracts');
        $this->db->where('fg_id', $fg_id);
        $this->db->where('status', 'Open');
        return $this->db->get()->row();
    }

    /**
     * Ambil daftar SC Open (dengan id, sc_number, po_qty) untuk FG tertentu.
     * Digunakan agar items hasil load-from-model memiliki sales_contract_id.
     */
    public function get_open_scs_by_fg($fg_id) {
        if (empty($fg_id) || !is_numeric($fg_id)) return [];
        $this->db->select('id, sc_number, po_qty as qty');
        $this->db->from('sales_contracts');
        $this->db->where('fg_id', $fg_id);
        $this->db->where('status', 'Open');
        $this->db->order_by('sc_number', 'ASC');
        return $this->db->get()->result();
    }

} // End Class