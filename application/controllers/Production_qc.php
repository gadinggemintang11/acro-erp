<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Production QC - Action page untuk approve QC dari production outputs.
 * Berbeda dari QC_production (existing report) yang read-only.
 *
 * Flow:
 *   1. Production output dibuat (stage cutting/sewing/assembling/finishing) di Production_process
 *   2. Output otomatis masuk antrian Production QC (qc_checked_by = NULL)
 *   3. QC officer buka menu ini, review per output, approve QC
 *   4. Setelah approve, output keluar dari antrian → muncul di QC Production Report
 */
class Production_qc extends MY_Controller {

    protected $module_name = 'Production QC';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['QC_model', 'Production_model']);
        $this->load->helper('url');
        $this->load->library('session');
    }

    /**
     * Index — list production outputs PENDING QC.
     */
    public function index() {
        if (!check_access('production_qc', 'view')) {
            show_error('Tidak ada izin.', 403);
        }

        $filters = [
            'search'    => $this->input->get('search'),
            'stage'     => $this->input->get('stage'),
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
        ];

        $pending  = $this->QC_model->get_pending_qc($filters);
        $by_stage = $this->QC_model->count_pending_qc_by_stage();

        // Map stage → count untuk badge
        $stage_counts = ['cutting' => 0, 'sewing' => 0, 'assembling' => 0, 'finishing' => 0];
        foreach ($by_stage as $s) {
            $stage_counts[$s->stage_name] = (int)$s->total;
        }

        $data = [
            'title'        => lang_line('title_pqc_index'),
            'pending'      => $pending,
            'filters'      => $filters,
            'stage_counts' => $stage_counts,
            'stages'       => ['cutting', 'sewing', 'assembling', 'finishing'],
        ];

        $this->_load_views('production_qc/index', $data);
    }

    /**
     * Detail — full detail dari production output + traceability input.
     */
    public function detail($output_id) {
        if (!check_access('production_qc', 'view')) {
            show_error('Tidak ada izin.', 403);
        }
        if (empty($output_id) || !is_numeric($output_id)) show_404();

        $qc_detail = $this->QC_model->get_qc_detail($output_id);
        if (!$qc_detail) show_404();

        // Ambil input materials yang dipakai pada stage ini (untuk traceability)
        $stage_inputs = $this->QC_model->get_qc_stage_inputs(
            $qc_detail->production_header_id,
            $qc_detail->stage_name
        );

        // Parse size breakdown jika ada (untuk finishing FG)
        $size_breakdown = [];
        if (!empty($qc_detail->size_breakdown)) {
            $decoded = json_decode($qc_detail->size_breakdown, true);
            if (is_array($decoded)) $size_breakdown = $decoded;
        }

        $data = [
            'title'          => lang_line('title_pqc_detail'),
            'qc_detail'      => $qc_detail,
            'stage_inputs'   => $stage_inputs,
            'size_breakdown' => $size_breakdown,
            'can_approve'    => check_access('production_qc', 'confirm'),
        ];

        $this->_load_views('production_qc/detail', $data);
    }

    /**
     * Approve QC: set qc_checked_by + qc_checked_at + optional description/photo.
     */
    public function approve_qc($output_id) {
        if (!check_access('production_qc', 'confirm')) {
            show_error('Anda tidak memiliki izin approve QC.', 403);
        }
        if (empty($output_id) || !is_numeric($output_id)) show_404();

        $output = $this->db->get_where('production_outputs', ['id' => $output_id])->row();
        if (!$output) show_404();
        if (!empty($output->qc_checked_by)) {
            $this->session->set_flashdata('warning', 'Output sudah di-QC sebelumnya.');
            redirect('production_qc/detail/' . $output_id);
            return;
        }

        $user_id     = $this->session->userdata('user_id');
        $description = trim($this->input->post('qc_description'));
        $photo_url   = trim($this->input->post('qc_photo_url'));

        $ok = $this->QC_model->approve_qc(
            $output_id,
            $user_id,
            $description !== '' ? $description : null,
            $photo_url   !== '' ? $photo_url   : null
        );

        if ($ok) {
            $this->_log('QC_APPROVE', "Approve QC output ID: {$output_id} (stage: {$output->stage_name})");
            $this->session->set_flashdata('success', 'QC berhasil di-approve.');
        } else {
            $this->session->set_flashdata('error', 'Gagal approve QC.');
        }
        redirect('production_qc/detail/' . $output_id);
    }

    /**
     * Undo approve QC (hanya untuk approver).
     */
    public function unapprove_qc($output_id) {
        if (!check_access('production_qc', 'confirm')) {
            show_error('Anda tidak memiliki izin batal QC.', 403);
        }
        if (empty($output_id) || !is_numeric($output_id)) show_404();

        $ok = $this->QC_model->unapprove_qc($output_id);
        if ($ok) {
            $this->_log('QC_REJECT', "Batal QC output ID: {$output_id}");
            $this->session->set_flashdata('success', 'Approve QC dibatalkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan approve QC.');
        }
        redirect('production_qc/detail/' . $output_id);
    }

    // ==========================================================
    private function _load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}
