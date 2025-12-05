<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Production_receive extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // Load model MR karena semua logika ada di sana
        $this->load->model('Mr_model'); 
        $this->load->helper('url');
    }

    // Halaman daftar MR (Status Issued / Received)
    public function index() {
        if (!check_access('production_receive', 'view')) { // Sesuaikan menu_key
            show_error('Anda tidak memiliki izin.', 403);
        }
        
        $filters = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status')
        ];

        // Ambil data MR yang relevan untuk produksi
        $data = [
            'title'   => 'Penerimaan Material Produksi',
            'mrs'     => $this->Mr_model->get_mrs_for_production($filters),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_receive/index', $data); // View baru
        $this->load->view('templates/footer');
    }

    // Halaman detail untuk konfirmasi
    public function detail($mr_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($mr_id)) show_404();

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || !in_array($header->status, ['Issued', 'Received'])) {
             $this->session->set_flashdata('error', 'MR ini belum dikeluarkan oleh Gudang.');
             redirect('production_receive');
        }

        $data = [
            'title'   => 'Konfirmasi Penerimaan Produksi',
            'header'  => $header,
            'details' => $this->Mr_model->get_details_by_id($mr_id) // Ambil detail MR
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('production_receive/detail', $data); // View baru
        // Footer dipanggil di view
    }
    
    // Proses Konfirmasi Terima (oleh Produksi)
    public function confirm_receive($mr_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Issued') {
             $this->session->set_flashdata('error', 'MR ini tidak dalam status Issued.');
             redirect('production_receive/detail/' . $mr_id);
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->process_reception($mr_id, $user_id)) {
            $this->session->set_flashdata('success', 'Barang berhasil dikonfirmasi diterima oleh Produksi.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengkonfirmasi penerimaan.');
        }
        redirect('production_receive/detail/' . $mr_id);
    }
    
    // Proses Batal Terima (oleh Produksi)
    public function unconfirm_receive($mr_id) {
        if (!check_access('production_receive', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Received') {
             $this->session->set_flashdata('error', 'MR ini tidak dalam status Received.');
             redirect('production_receive/detail/' . $mr_id);
        }
        
        // TODO: Validasi apakah stok WIP sudah terpakai di WO?
        
        if ($this->Mr_model->reverse_reception($mr_id)) {
            $this->session->set_flashdata('success', 'Penerimaan berhasil dibatalkan. Status kembali ke Issued.');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan penerimaan.');
        }
        redirect('production_receive/detail/' . $mr_id);
    }
    
    // Export PDF Production Receive
    public function export_pdf($id) {
        if (!check_access('production_receive', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || !in_array($header->status, ['Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'Data penerimaan tidak ditemukan atau belum dikeluarkan.');
            redirect('production_receive');
            return;
        }

        $details = $this->Mr_model->get_details_by_id($id);

        $data = [
            'header' => $header,
            'details' => $details
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('production_receive/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'Production_Receive_' . str_replace('/', '-', $header->mr_number) . '.pdf';
        $dompdf->stream($filename, ["Attachment" => 0]);
    }
}