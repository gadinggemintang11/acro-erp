<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scrap_Out_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('Stock_ledger_model');
    }

    /** Generate nomor dokumen unik */
    public function generate_doc_no() {
        $prefix = 'SCR-OUT/' . date('Ym') . '/';
        $this->db->select('doc_no')->like('doc_no', $prefix)->order_by('id', 'DESC')->limit(1);
        $last = $this->db->get('scrap_out_header')->row();
        $num = 1;
        if ($last && preg_match('/(\d{4})$/', $last->doc_no, $m)) $num = (int)$m[1] + 1;
        return $prefix . sprintf('%04d', $num);
    }

    /** Ambil semua dokumen */
    public function get_all() {
        $this->db->select('h.*, u.fullname AS created_by_name, c.nama_customer AS customer_name');
        $this->db->from('scrap_out_header h');
        $this->db->join('users u', 'u.id = h.created_by', 'left');
        $this->db->join('customers c', 'c.id = h.destination', 'left');
        $this->db->order_by('h.doc_date', 'DESC');
        return $this->db->get()->result();
    }

    /** Ambil data lengkap header + detail (FIXED: Ambil nilai jual) */
    public function get_by_id($id) {
        $data = [];
        $this->db->select('h.*, u.fullname AS created_by_name, c.nama_customer AS customer_name, cc_bc.description as bc_type_desc');
        $this->db->from('scrap_out_header h');
        $this->db->join('users u', 'u.id = h.created_by', 'left');
        $this->db->join('customers c', 'c.id = h.destination', 'left');
        $this->db->join('common_code cc_bc', 'cc_bc.code = h.bc_type_code AND cc_bc.group_code = "BC_TYPE"', 'left');
        $this->db->where('h.id', $id);
        $data['header'] = $this->db->get()->row();

        if (empty($data['header'])) return null;

        $this->db->select('d.*, m.scrap_code, m.scrap_name, m.unit AS satuan_desc'); // m.unit seharusnya sudah punya satuan
        $this->db->from('scrap_out_detail d');
        $this->db->join('master_scrap m', 'm.id = d.scrap_id');
        // $this->db->join('common_code co', "co.code = m.unit AND co.group_code = 'SATUAN'", 'left'); // Jika satuan sudah ada di master_scrap.unit
        $this->db->where('d.header_id', $id);
        $data['details'] = $this->db->get()->result();

        return $data;
    }

    /** Simpan dokumen baru */
    public function create_draft($header_data, $details) {
        $this->db->trans_start();
        $this->db->insert('scrap_out_header', $header_data);
        $header_id = $this->db->insert_id();
        foreach ($details as &$item) $item['header_id'] = $header_id;
        $this->db->insert_batch('scrap_out_detail', $details);
        $this->db->trans_complete();
        return ($this->db->trans_status()) ? $header_id : false;
    }

    /** Update dokumen draft */
    public function update_draft($id, $header_data, $details) {
        $this->db->trans_start();
        $this->db->where('id', $id)->update('scrap_out_header', $header_data);
        $this->db->delete('scrap_out_detail', ['header_id' => $id]);
        foreach ($details as &$row) $row['header_id'] = $id;
        if (!empty($details)) $this->db->insert_batch('scrap_out_detail', $details);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Konfirmasi (kurangi stok + buat ledger) - LOGIKA FIXED */
    public function confirm_scrap_out($id, $user_id) {
        $this->db->trans_start();
        $header = $this->db->where('id', $id)->get('scrap_out_header')->row();

        if (!$header || $header->status != 'Draft') {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Hanya dokumen Draft yang bisa dikonfirmasi.'];
        }

        $details = $this->db->where('header_id', $id)->get('scrap_out_detail')->result();
        if (empty($details)) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Dokumen tidak memiliki detail item.'];
        }

        $current_time = date('Y-m-d H:i:s');

        // === PROSES PEMOTONGAN STOK & LEDGER ===
        foreach ($details as $item) {
            $scrap_id = $item->scrap_id;
            $qty_needed = (float)$item->qty;
            $sales_value = (float)$item->sales_value_per_unit; // Nilai Jual per unit

            // 1. Cek stok tersedia total
            $current_stock = $this->Stock_ledger_model->get_last_balance($scrap_id, 'scrap');
            if ($current_stock < $qty_needed) {
                $this->db->trans_rollback();
                return ['status' => false, 'message' => 'Stok scrap ' . $item->scrap_name . ' tidak mencukupi.'];
            }

            // 2. Potong Stok dan Buat Ledger (Sederhana: potong stok total dan buat 1 ledger)
            // Note: Untuk pemotongan FIFO multi-batch, logika ini harus memanggil SOH, 
            // tetapi karena ini scrap, kita asumsikan potong dari total dan buat 1 ledger.
            
            $new_balance = $current_stock - $qty_needed;

            // Buat Ledger
            $ledger_data = [
                'item_id'           => $scrap_id,
                'item_type'         => 'scrap',
                'transaction_date'  => $current_time,
                'transaction_type'  => 'OUT_SCRAP_BC',
                'bc_type_code'      => $header->bc_type_code,
                'bc_number'         => $header->bc_number,
                'bc_date'           => $header->bc_date,
                'transaction_id'    => $id, // ✅ FIXED: Gunakan ID HEADER, bukan ID detail
                'source_doc_type'   => 'SCRAP_OUT',
                'source_doc_id'     => $id,
                'qty_in'            => 0,
                'qty_out'           => $qty_needed,
                'unit_value'        => $sales_value, // Menggunakan nilai jual
                'balance'           => $new_balance,
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                $this->db->trans_rollback();
                return ['status' => false, 'message' => 'Gagal mencatat ledger stok.'];
            }
            
            // 3. Update Stok Total (Scrap_Stock) - Logika khusus untuk update saldo total
            $this->db->set('available_qty', 'available_qty - ' . $qty_needed, FALSE)
                     ->where('scrap_id', $scrap_id)
                     ->update('scrap_stock'); 
        }

        // Update Header
        $this->db->where('id', $id)->update('scrap_out_header', [
            'status' => 'Confirmed',
            'confirmed_by' => $user_id,
            'confirmed_at' => $current_time
        ]);
        $this->db->trans_complete();

        return $this->db->trans_status()
            ? ['status' => true, 'message' => 'Dokumen berhasil dikonfirmasi.']
            : ['status' => false, 'message' => 'Gagal konfirmasi dokumen.'];
    }

    /** Batalkan konfirmasi (Rollback Stok dan Ledger) */
    public function unconfirm_scrap_out($id) {
        $this->db->trans_start();
        $header = $this->db->where('id', $id)->get('scrap_out_header')->row();

        if (!$header || $header->status != 'Confirmed') {
            return ['status' => false, 'message' => 'Hanya dokumen Confirmed yang bisa dibatalkan.'];
        }

        $details = $this->db->where('header_id', $id)->get('scrap_out_detail')->result();
        
        foreach ($details as $item) {
            $qty = (float)$item->qty;
            
            // 1. Kembalikan Stok Total (Scrap_Stock)
            $this->db->set('available_qty', 'available_qty + ' . $qty, FALSE)
                     ->where('scrap_id', $item->scrap_id)
                     ->update('scrap_stock');
                     
            // 2. Hapus Ledger (Berdasarkan ID Detail)
            $this->Stock_ledger_model->delete_by_transaction_id($item->id, 'OUT_SCRAP_BC');
        }

        // 3. Update Header
        $this->db->where('id', $id)->update('scrap_out_header', [
            'status' => 'Draft',
            'confirmed_by' => null,
            'confirmed_at' => null
        ]);
        
        $this->db->trans_complete();

        return ['status' => true, 'message' => 'Konfirmasi dibatalkan dan stok dikembalikan.'];
    }
    
    /** Hapus draft */
    public function delete_draft($id) {
        $header = $this->db->where('id', $id)->get('scrap_out_header')->row();
        if (!$header || $header->status != 'Draft') {
            return ['status' => false, 'message' => 'Hanya Draft yang bisa dihapus.'];
        }
        $this->db->trans_start();
        $this->db->delete('scrap_out_detail', ['header_id' => $id]);
        $this->db->delete('scrap_out_header', ['id' => $id]);
        $this->db->trans_complete();
        return ['status' => true, 'message' => 'Draft berhasil dihapus.'];
    }

    /** Data referensi */
    public function get_commoncode_bc() {
        return $this->db->where('group_code', 'BC_TYPE')->get('common_code')->result_array();
    }

    public function get_customers() {
        return $this->db->select('id, nama_customer')->where('status', 'active')->order_by('nama_customer', 'ASC')->get('customers')->result_array();
    }

    public function get_available_scrap() {
        $this->db->select("s.scrap_id, m.scrap_code, m.scrap_name, SUM(s.available_qty) AS available_qty, c.description AS satuan_desc");
        $this->db->from('scrap_stock s');
        $this->db->join('master_scrap m', 'm.id = s.scrap_id');
        $this->db->join('common_code c', "c.code = m.unit AND c.group_code = 'SATUAN'", 'left');
        $this->db->where('s.available_qty >', 0);
        $this->db->group_by('s.scrap_id, m.scrap_code, m.scrap_name, c.description');
        $this->db->order_by('m.scrap_name', 'ASC');
        return $this->db->get()->result_array();
    }
}