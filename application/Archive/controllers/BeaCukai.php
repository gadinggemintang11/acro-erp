<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BeaCukai extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('BeaCukai_model');
        $this->load->model('User_model');
        $this->load->helper('url');
    }

    public function detail($identifier = null) {
        if (empty($identifier)) {
            show_error('Parameter dokumen BC tidak valid.', 400);
            return;
        }

        // Deteksi apakah ini ID numeric atau Nomor BC
        if (is_numeric($identifier) && strlen($identifier) < 6) {
            $doc = $this->BeaCukai_model->get_by_id($identifier);
        } else {
            $doc = $this->BeaCukai_model->get_by_number($identifier);
        }

        // Jika tidak ditemukan di penerimaan (BC 2.3, 2.7, 4.0), cek di scrap_out_header (BC 3.0, 4.1, dst)
        if (empty($doc)) {
            $this->db->select('
                h.id,
                h.bc_type_code,
                h.bc_number,
                h.bc_date,
                h.destination AS tujuan,
                h.created_by,
                h.created_at
            ');
            $this->db->from('scrap_out_header h');
            $this->db->where('h.bc_number', $identifier);
            $doc = $this->db->get()->row_array();

            if (!empty($doc)) {
                $doc['type'] = 'OUT';
                $doc['details'] = $this->db->select('
                        d.id,
                        s.scrap_code AS item_code,
                        s.scrap_name AS item_name,
                        d.qty,
                        d.unit
                    ')
                    ->from('scrap_out_detail d')
                    ->join('master_scrap s', 's.id = d.scrap_id', 'left')
                    ->where('d.header_id', $doc['id'])
                    ->get()
                    ->result_array();
            }
        } else {
            $doc['type'] = 'IN';
            $doc['details'] = $this->BeaCukai_model->get_scrap_details($doc['id'], 'grn');
        }

        if (empty($doc)) {
            show_error('Dokumen BC "' . htmlspecialchars($identifier) . '" tidak ditemukan.', 404);
            return;
        }

        // Ambil nama user pembuat
        if (!empty($doc['created_by'])) {
            $user_name = $this->User_model->get_user_name($doc['created_by']);
            $doc['created_by'] = $user_name ?: 'Tidak diketahui';
        }

        // Siapkan data untuk view template utama
        $data = [
            'page_title' => 'Dokumen BC ' . ($doc['bc_number'] ?? '-'),
            'doc'        => $doc,
            'content_view' => 'it_inventory/lap_mutasi_scrap/view_bc_doc', // body content
        ];

        // Load tampilan lengkap menggunakan template Neokaber
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($data['content_view'], $data);
        $this->load->view('templates/footer', $data);
    }
}
