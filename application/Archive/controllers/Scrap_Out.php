<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Composer libs (Dompdf) jika diperlukan
require_once FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Scrap_Out extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // helper/library/model yang dipakai
        $this->load->helper(['url', 'form']);
        $this->load->library(['session', 'form_validation', 'pagination']);
        $this->load->model('Scrap_Out_model');
        $this->load->model('Scrap_Stock_model'); // untuk laporan stok / ajax jika perlu
        $this->load->model('Stock_ledger_model'); // sudah dipanggil di model
        // check_login(); // aktifkan bila ada helper auth
    }

    /**
     * List semua scrap out
     */
    public function index() {
        // permission check bila ada
        // if (!check_access('scrap_out','view')) redirect('denied');

        $data['title'] = 'Daftar Scrap Out';
        $data['documents'] = $this->Scrap_Out_model->get_all();

        // load views (pastikan view scrap_out/list.php ada)
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_out/list', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Form create
     */
    public function create() {
        // if (!check_access('scrap_out','add')) redirect('denied');

        $data['title'] = 'Tambah Scrap Out';
        $data['doc_no'] = $this->Scrap_Out_model->generate_doc_no();
        $data['bc_types'] = $this->Scrap_Out_model->get_commoncode_bc();
        $data['customers'] = $this->Scrap_Out_model->get_customers();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_out/form', $data); // gunakan form.php (create)
        $this->load->view('templates/footer');
    }

    /**
     * Store create draft
     */
    public function store() {
        // if (!check_access('scrap_out','add')) redirect('denied');

        $this->form_validation->set_rules('doc_no', 'No. Dokumen', 'required');
        $this->form_validation->set_rules('doc_date', 'Tgl Dokumen', 'required');
        $this->form_validation->set_rules('bc_type_code', 'Tipe BC', 'required');
        $this->form_validation->set_rules('destination', 'Tujuan (Customer)', 'required');
        
        $scrap_ids = $this->input->post('scrap_id');
        $qtys = $this->input->post('qty');
        $unit_values = $this->input->post('sales_value_per_unit'); // <-- AMBIL NILAI BARU

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            return $this->create();
        }

        // validate details: minimal 1 dan qty > 0
        $details = [];
        if (!empty($scrap_ids) && is_array($scrap_ids)) {
            foreach ($scrap_ids as $i => $sid) {
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                $v = isset($unit_values[$i]) ? (float)$unit_values[$i] : 0; // <-- AMBIL NILAI
                
                if ($q > 0) {
                    $details[] = [
                        'scrap_id' => $sid,
                        'qty' => $q,
                        'sales_value_per_unit' => $v // <-- SIMPAN NILAI JUAL
                    ];
                }
            }
        }

        if (empty($details)) {
            $this->session->set_flashdata('error', 'Minimal 1 item detail dengan Qty > 0.');
            return $this->create();
        }

        $header = [
            'doc_no' => $this->input->post('doc_no'),
            'doc_date' => $this->input->post('doc_date'),
            'bc_type_code' => $this->input->post('bc_type_code'),
            'bc_number' => $this->input->post('bc_number'),
            'bc_date' => $this->input->post('bc_date') ?: null,
            'notes' => $this->input->post('notes'),
            'destination' => $this->input->post('destination'),
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id') ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $inserted = $this->Scrap_Out_model->create_draft($header, $details);
        if ($inserted) {
            $this->session->set_flashdata('success', 'Draft Scrap Out berhasil dibuat.');
            redirect('Scrap_Out/view/' . $inserted);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan draft.');
            redirect('Scrap_Out/create');
        }
    }

    /**
     * View / detail (juga tempat Confirm / Unconfirm actions)
     */
    public function view($id = null) {
        if (empty($id)) show_404();
        // if (!check_access('scrap_out','view')) redirect('denied');

        $data['title'] = 'Detail Scrap Out';
        $data_comp = $this->Scrap_Out_model->get_by_id($id);
        if (!$data_comp) show_404();

        // Data yang dikirim ke view harus konsisten
        $vdata = [
            'title' => $data['title'],
            'header' => $data_comp['header'],
            'details' => $data_comp['details']
        ];

        $this->load->view('templates/header', $vdata);
        $this->load->view('templates/sidebar', $vdata);
        $this->load->view('templates/topbar', $vdata);
        $this->load->view('scrap_out/view_detail', ['title'=>$data['title'], 'header'=>$data_comp['header'], 'details'=>$data_comp['details']]);
        $this->load->view('templates/footer');
    }

    /**
     * Edit form (only Draft)
     */
    public function edit($id = null) {
        if (empty($id)) show_404();

        $doc = $this->Scrap_Out_model->get_by_id($id);
        if (!$doc) show_404();

        if ($doc['header']->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft yang bisa diedit.');
            redirect('Scrap_Out/view/' . $id);
        }

        $data['title'] = 'Edit Scrap Out';
        $data['header'] = $doc['header'];
        $data['details'] = $doc['details'];
        $data['bc_types'] = $this->Scrap_Out_model->get_commoncode_bc();
        $data['customers'] = $this->Scrap_Out_model->get_customers();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_out/form_edit', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Update draft
     */
    public function update($id = null) {
        if (empty($id)) show_404();
        $doc = $this->Scrap_Out_model->get_by_id($id);
        if (!$doc) show_404();
        if ($doc['header']->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diupdate.');
            redirect('Scrap_Out/view/' . $id);
        }

        $this->form_validation->set_rules('doc_date', 'Tgl Dokumen', 'required');
        $this->form_validation->set_rules('bc_type_code', 'Tipe BC', 'required');
        $this->form_validation->set_rules('destination', 'Tujuan (Customer)', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            return $this->edit($id);
        }

        // details
        $scrap_ids = $this->input->post('scrap_id');
        $qtys = $this->input->post('qty');
        $unit_values = $this->input->post('sales_value_per_unit'); // <-- AMBIL NILAI
        $details = [];
        
        if (!empty($scrap_ids) && is_array($scrap_ids)) {
            foreach ($scrap_ids as $i => $sid) {
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                $v = isset($unit_values[$i]) ? (float)$unit_values[$i] : 0; // <-- AMBIL NILAI
                
                if ($q > 0) {
                    $details[] = [
                        'scrap_id' => $sid,
                        'qty' => $q,
                        'sales_value_per_unit' => $v // <-- SIMPAN NILAI JUAL
                    ];
                }
            }
        }
        
        if (empty($details)) {
            $this->session->set_flashdata('error', 'Minimal 1 item detail dengan Qty > 0.');
            return $this->edit($id);
        }

        $header = [
            'doc_date' => $this->input->post('doc_date'),
            'bc_type_code' => $this->input->post('bc_type_code'),
            'bc_number' => $this->input->post('bc_number'),
            'bc_date' => $this->input->post('bc_date') ?: null,
            'notes' => $this->input->post('notes'),
            'destination' => $this->input->post('destination'),
            'updated_by' => $this->session->userdata('user_id') ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $ok = $this->Scrap_Out_model->update_draft($id, $header, $details);
        if ($ok) {
            $this->session->set_flashdata('success', 'Draft berhasil diupdate.');
            redirect('Scrap_Out/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate draft.');
            redirect('Scrap_Out/edit/' . $id);
        }
    }
    /**
     * Delete draft
     */
    public function delete($id = null) {
        if (empty($id)) show_404();
        $res = $this->Scrap_Out_model->delete_draft($id);
        $this->session->set_flashdata($res['status'] ? 'success' : 'error', $res['message']);
        redirect('Scrap_Out');
    }

    /**
     * Confirm - reduce stock (FIFO) and ledger
     */
    public function confirm($id = null) {
        if (empty($id)) show_404();
        $user_id = $this->session->userdata('user_id') ?? null;
        $res = $this->Scrap_Out_model->confirm_scrap_out($id, $user_id);
        $this->session->set_flashdata($res['status'] ? 'success' : 'error', $res['message']);
        redirect('Scrap_Out/view/' . $id);
    }

    /**
     * Unconfirm - rollback stock
     */
    public function unconfirm($id = null) {
        if (empty($id)) show_404();
        $res = $this->Scrap_Out_model->unconfirm_scrap_out($id);
        $this->session->set_flashdata($res['status'] ? 'success' : 'error', $res['message']);
        redirect('Scrap_Out/view/' . $id);
    }

    /**
     * AJAX: get available scrap stock (for modal)
     * Returns JSON {status: 'success', data: [...]}
     */
    public function ajax_get_scrap_stock() {
        // if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->Scrap_Out_model->get_available_scrap();
        if ($rows === false) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data stok.']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => $rows]);
    }

    /**
     * Export PDF single scrap out (detail)
     */
    public function export_pdf($id = null) {
        if (empty($id)) show_404();
        $data = $this->Scrap_Out_model->get_by_id($id);
        if (!$data) show_404();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->load->view('scrap_out/print_pdf', ['header' => $data['header'], 'details' => $data['details']], true);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'scrap_out_' . str_replace('/', '-', $data['header']->doc_no) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
    }
    
    // Helper function (asumsi ada di MY_Controller)
    private function _load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}
