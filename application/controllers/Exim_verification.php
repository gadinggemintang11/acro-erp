<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exim_verification extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Exim_verification_model');
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('exim_verification', 'view')) {
            show_error('Akses Ditolak', 403);
            return;
        }

        // Get statistics
        $data['incoming_stats'] = $this->Exim_verification_model->get_incoming_stats();
        $data['outgoing_stats'] = $this->Exim_verification_model->get_outgoing_stats();
        $data['title'] = 'EXIM Verification';
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('exim_verification/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Verifikasi Pemasukan Barang (GRN)
     */
    public function incoming() {
        if (!check_access('exim_verification', 'view')) {
            show_error('Akses Ditolak', 403);
            return;
        }

        // Get filters
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'supplier_id' => $this->input->get('supplier_id'),
            'bc_type' => $this->input->get('bc_type'),
            'verified_status' => $this->input->get('verified_status') ?? 'unverified' // default: belum diverifikasi
        ];

        $data['grn_list'] = $this->Exim_verification_model->get_incoming_list($filters);
        $data['suppliers'] = $this->Exim_verification_model->get_suppliers();
        $data['bc_types'] = $this->Exim_verification_model->get_bc_types();
        $data['filters'] = $filters;
        $data['title'] = 'EXIM Verification - Pemasukan Barang';
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('exim_verification/incoming', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Verifikasi Pengeluaran Barang (FG Out, Scrap Out)
     */
    public function outgoing() {
        if (!check_access('exim_verification', 'view')) {
            show_error('Akses Ditolak', 403);
            return;
        }

        // Get filters
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'doc_type' => $this->input->get('doc_type'), // FG_OUT, SCRAP_OUT
            'bc_type' => $this->input->get('bc_type'),
            'verified_status' => $this->input->get('verified_status') ?? 'unverified'
        ];

        $data['outgoing_list'] = $this->Exim_verification_model->get_outgoing_list($filters);
        $data['bc_types'] = $this->Exim_verification_model->get_bc_types();
        $data['filters'] = $filters;
        $data['title'] = 'EXIM Verification - Pengeluaran Barang';
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('exim_verification/outgoing', $data);
        $this->load->view('templates/footer');
    }

  
    public function verify_incoming($grn_id) {
        if (!check_access('exim_verification', 'edit')) {
            $this->session->set_flashdata('error', 'Akses Ditolak');
            redirect('exim_verification/incoming');
            return;
        }

        if (empty($grn_id) || !is_numeric($grn_id)) {
            show_404();
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $result = $this->Exim_verification_model->verify_grn($grn_id, $user_id);

        if ($result) {
            $this->session->set_flashdata('success', 'GRN berhasil diverifikasi.');
        } else {
            $this->session->set_flashdata('error', 'Gagal memverifikasi GRN.');
        }

        redirect('exim_verification/incoming');
    }

    /**
     * Proses unverify GRN (Pemasukan)
     */
    public function unverify_incoming($grn_id) {
        if (!check_access('exim_verification', 'edit')) {
            $this->session->set_flashdata('error', 'Akses Ditolak');
            redirect('exim_verification/incoming');
            return;
        }

        if (empty($grn_id) || !is_numeric($grn_id)) {
            show_404();
            return;
        }

        $result = $this->Exim_verification_model->unverify_grn($grn_id);

        if ($result) {
            $this->session->set_flashdata('success', 'Verifikasi GRN berhasil dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan verifikasi GRN.');
        }

        redirect('exim_verification/incoming');
    }

    /**
     * Proses verifikasi Outgoing (FG/Scrap)
     */
    public function verify_outgoing() {
        if (!check_access('exim_verification', 'edit')) {
            $this->session->set_flashdata('error', 'Akses Ditolak');
            redirect('exim_verification/outgoing');
            return;
        }

        $doc_type = $this->input->post('doc_type'); // FG_OUT, SCRAP_OUT, SUBKON_OUT
        $doc_id = $this->input->post('doc_id');

        if (empty($doc_type) || empty($doc_id)) {
            $this->session->set_flashdata('error', 'Data tidak lengkap');
            redirect('exim_verification/outgoing');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        
        if ($doc_type === 'SUBKON_OUT') {
            $result = $this->Exim_verification_model->verify_subkon_out($doc_id, $user_id);
        } else {
            $result = $this->Exim_verification_model->verify_outgoing($doc_type, $doc_id, $user_id);
        }

        if ($result) {
            $this->session->set_flashdata('success', 'Dokumen pengeluaran berhasil diverifikasi.');
        } else {
            $this->session->set_flashdata('error', 'Gagal memverifikasi dokumen.');
        }

        redirect('exim_verification/outgoing');
    }

    /**
     * Proses unverify Outgoing
     */
    public function unverify_outgoing() {
        if (!check_access('exim_verification', 'edit')) {
            $this->session->set_flashdata('error', 'Akses Ditolak');
            redirect('exim_verification/outgoing');
            return;
        }

        $doc_type = $this->input->post('doc_type');
        $doc_id = $this->input->post('doc_id');

        if (empty($doc_type) || empty($doc_id)) {
            $this->session->set_flashdata('error', 'Data tidak lengkap');
            redirect('exim_verification/outgoing');
            return;
        }

        if ($doc_type === 'SUBKON_OUT') {
            $result = $this->Exim_verification_model->unverify_subkon_out($doc_id);
        } else {
            $result = $this->Exim_verification_model->unverify_outgoing($doc_type, $doc_id);
        }

        if ($result) {
            $this->session->set_flashdata('success', 'Verifikasi dokumen berhasil dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan verifikasi.');
        }

        redirect('exim_verification/outgoing');
    }

    /**
     * Bulk verify - verifikasi banyak dokumen sekaligus
     */
    public function bulk_verify_incoming() {
        if (!check_access('exim_verification', 'edit')) {
            $this->session->set_flashdata('error', 'Akses Ditolak');
            redirect('exim_verification/incoming');
            return;
        }

        $grn_ids = $this->input->post('grn_ids'); // Array of IDs
        if (empty($grn_ids) || !is_array($grn_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal 1 GRN untuk diverifikasi');
            redirect('exim_verification/incoming');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $success_count = 0;

        foreach ($grn_ids as $grn_id) {
            if ($this->Exim_verification_model->verify_grn($grn_id, $user_id)) {
                $success_count++;
            }
        }

        $this->session->set_flashdata('success', "$success_count GRN berhasil diverifikasi.");
        redirect('exim_verification/incoming');
    }

    /**
     * Get GRN detail items (AJAX)
     */
    public function get_grn_details($grn_id) {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        try {
            $details = $this->Exim_verification_model->get_grn_details($grn_id);
            
            if ($details !== false) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Detail tidak ditemukan']);
            }
        } catch (Exception $e) {
            log_message('error', 'get_grn_details error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get FG Delivery detail items (AJAX)
     */
    public function get_fg_delivery_details($delivery_id) {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        try {
            $details = $this->Exim_verification_model->get_fg_delivery_details($delivery_id);
            
            if ($details !== false) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Detail tidak ditemukan']);
            }
        } catch (Exception $e) {
            log_message('error', 'get_fg_delivery_details error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get Scrap Out detail items (AJAX)
     */
    public function get_scrap_out_details($header_id) {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        try {
            $details = $this->Exim_verification_model->get_scrap_out_details($header_id);
            
            if ($details !== false) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Detail tidak ditemukan']);
            }
        } catch (Exception $e) {
            log_message('error', 'get_scrap_out_details error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get Subkon Out detail items (AJAX)
     */
    public function get_subkon_out_details($header_id) {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        try {
            $details = $this->Exim_verification_model->get_subkon_out_details($header_id);
            
            if ($details !== false) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Detail tidak ditemukan']);
            }
        } catch (Exception $e) {
            log_message('error', 'get_subkon_out_details error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update BC info for GRN (AJAX)
     */
    public function update_grn_bc() {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $grn_id = $this->input->post('grn_id');
        $bc_type_code = $this->input->post('bc_type_code');
        $bc_number = $this->input->post('bc_number');
        $bc_date = $this->input->post('bc_date');

        if (empty($grn_id) || empty($bc_type_code) || empty($bc_number)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        try {
            $result = $this->Exim_verification_model->update_grn_bc($grn_id, $bc_type_code, $bc_number, $bc_date);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'BC info berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal update BC info']);
            }
        } catch (Exception $e) {
            log_message('error', 'update_grn_bc error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    
    public function update_fg_delivery_bc() {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $delivery_id = $this->input->post('delivery_id');
        $bc_type_id = $this->input->post('bc_type_id');
        $bc_number = $this->input->post('bc_number');
        $bc_date = $this->input->post('bc_date');

        if (empty($delivery_id) || empty($bc_type_id) || empty($bc_number)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        try {
            $result = $this->Exim_verification_model->update_fg_delivery_bc($delivery_id, $bc_type_id, $bc_number, $bc_date);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'BC info berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal update BC info']);
            }
        } catch (Exception $e) {
            log_message('error', 'update_fg_delivery_bc error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update BC info for Scrap Out (AJAX)
     */
    public function update_scrap_out_bc() {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $header_id = $this->input->post('header_id');
        $bc_type_code = $this->input->post('bc_type_code');
        $bc_number = $this->input->post('bc_number');
        $bc_date = $this->input->post('bc_date');

        if (empty($header_id) || empty($bc_type_code) || empty($bc_number)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        try {
            $result = $this->Exim_verification_model->update_scrap_out_bc($header_id, $bc_type_code, $bc_number, $bc_date);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'BC info berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal update BC info']);
            }
        } catch (Exception $e) {
            log_message('error', 'update_scrap_out_bc error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update BC info for Subkon Out (AJAX)
     */
    public function update_subkon_out_bc() {
        header('Content-Type: application/json');

        if (!check_access('exim_verification', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $header_id = $this->input->post('header_id');
        $bc_type_code = $this->input->post('bc_type_code');
        $bc_number = $this->input->post('bc_number');
        $bc_date = $this->input->post('bc_date');

        if (empty($header_id) || empty($bc_type_code) || empty($bc_number)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        try {
            $result = $this->Exim_verification_model->update_subkon_out_bc($header_id, $bc_type_code, $bc_number, $bc_date);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'BC info berhasil diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal update BC info']);
            }
        } catch (Exception $e) {
            log_message('error', 'update_subkon_out_bc error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
