<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Production extends MY_Controller {

    protected $module_name = 'Production';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Production_model');
        $this->load->helper('url');
        $this->load->library('session');
    }

    /**
     * Konfirmasi dokumen produksi dari Draft -> In Progress
     */
    public function confirm($id) {
        if (!check_access('production', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Production_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status Draft yang bisa dikonfirmasi.');
            redirect($this->agent->referrer() ?? 'production');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Production_model->confirm_production($id, $user_id)) {
            // --- LOGGING (APPROVE) ---
            $deskripsi = "Konfirmasi dokumen Produksi: {$header->production_number} (ID: $id). Status: Draft -> In Progress";
            $this->_log('APPROVE', $deskripsi);
            $this->session->set_flashdata('success', 'Dokumen produksi berhasil dikonfirmasi.');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal mengkonfirmasi dokumen produksi.');
        }
        redirect($this->agent->referrer() ?? 'production/detail/' . $id);
    }

    /**
     * Batalkan konfirmasi dokumen produksi dari In Progress -> Draft
     */
    public function unconfirm($id) {
        if (!check_access('production', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Production_model->get_header_by_id($id);
        if (!$header || $header->status != 'In Progress') {
            $this->session->set_flashdata('error', 'Hanya status In Progress yang bisa dibatalkan konfirmasinya.');
            redirect($this->agent->referrer() ?? 'production');
            return;
        }

        if ($this->Production_model->unconfirm_production($id)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Membatalkan konfirmasi Produksi: {$header->production_number} (ID: $id). Status: In Progress -> Draft";
            $this->_log('UPDATE', $deskripsi);
            $this->session->set_flashdata('success', 'Konfirmasi dokumen produksi berhasil dibatalkan.');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi.');
        }
        redirect($this->agent->referrer() ?? 'production/detail/' . $id);
    }

    /**
     * Konfirmasi hasil output oleh QC
     */
    public function confirm_qc($output_id) {
        if (!check_access('production', 'qc_check')) { show_error('Anda tidak memiliki izin.', 403); }
        $user_id = $this->session->userdata('user_id');

        if ($this->Production_model->confirm_qc_output($output_id, $user_id)) {
            // --- LOGGING (QC) ---
            $deskripsi = "QC Check untuk output produksi ID: {$output_id}.";
            $this->_log('QC_APPROVE', $deskripsi);
            $this->session->set_flashdata('success', 'Output berhasil diverifikasi QC.');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal verifikasi QC.');
        }
        redirect($this->agent->referrer());
    }

    /**
     * Batalkan konfirmasi hasil output oleh QC
     */
    public function unconfirm_qc($output_id) {
        if (!check_access('production', 'qc_check')) { show_error('Anda tidak memiliki izin.', 403); }

        if ($this->Production_model->unconfirm_qc_output($output_id)) {
            // --- LOGGING (QC) ---
            $deskripsi = "Membatalkan QC Check untuk output produksi ID: {$output_id}.";
            $this->_log('QC_REJECT', $deskripsi);
            $this->session->set_flashdata('success', 'Verifikasi QC berhasil dibatalkan.');
            // --- END LOG ---
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan verifikasi QC.');
        }
        redirect($this->agent->referrer());
    }
}
