<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Bom extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Bom_model', 'Fg_model']); // FG model untuk ambil daftar style
        $this->load->helper('url');
    }

    // Halaman utama daftar BOM
    public function index() {
        if (!check_access('bom', 'view')) { // Sesuaikan menu_key
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title' => 'Bill of Materials (BOM)',
            'boms'  => $this->Bom_model->get_all($filters),
            // Ambil FG yang BELUM punya BOM untuk modal Add
            'available_fgs' => $this->Fg_model->get_fgs_without_bom(),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('bom/index', $data);
        $this->load->view('templates/footer');
    }

    // Proses buat header BOM baru
    public function create_header() {
        if (!check_access('bom', 'add')) { show_error('Tidak ada izin.', 403); }

        $fg_id = $this->input->post('fg_id');
        if (empty($fg_id)) {
            $this->session->set_flashdata('error', 'Silakan pilih Style (Finished Good) terlebih dahulu!');
            redirect('bom');
        }

        // Cek lagi jika FG sudah punya BOM (double check)
        if ($this->Bom_model->check_bom_exists_for_fg($fg_id)) {
             $this->session->set_flashdata('error', 'Style ini sudah memiliki BOM!');
             redirect('bom');
        }

        $data = [
            'fg_id'      => $fg_id,
            'status'     => 'Draft', // Status awal
            'created_by' => $this->session->userdata('user_id') ?? null
        ];

        $new_bom_id = $this->Bom_model->insert_header($data);

        if ($new_bom_id) {
            $this->session->set_flashdata('success', 'Header BOM berhasil dibuat. Silakan tambahkan komponen.');
            redirect('bom/detail/' . $new_bom_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header BOM!');
            redirect('bom');
        }
    }

    // Halaman detail BOM untuk kelola komponen
    public function detail($id) {
        if (!check_access('bom', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'   => 'Detail Bill of Materials',
            'header'  => $header,
            'details' => $this->Bom_model->get_details_by_header_id($id)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('bom/detail', $data);
        // Footer dipanggil di view
    }

    // AJAX: Cari komponen (material & semifinished)
    public function search_components() {
        $term = $this->input->get('q');
        $items = $this->Bom_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // Proses tambah item komponen ke BOM
    public function add_item($bom_header_id) {
        if (!check_access('bom', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Bom_model->get_header_by_id($bom_header_id);
        if (!$header || $header->status != 'Draft') { redirect('bom/detail/' . $bom_header_id); }

        $item_data = explode('|', $this->input->post('component_id'));
        if (count($item_data) < 2) { redirect('bom/detail/' . $bom_header_id); }
        $item_id = $item_data[0];
        $item_type = $item_data[1];

        $data = [
            'bom_header_id'         => $bom_header_id,
            'component_item_id'   => $item_id,
            'component_item_type' => $item_type,
            'quantity_required'   => (float) $this->input->post('quantity_required') ?: 1,
        ];

        if ($this->Bom_model->insert_item($data)) { $this->session->set_flashdata('success', 'Komponen ditambahkan.'); }
        else { $this->session->set_flashdata('error', 'Gagal menambah komponen (mungkin sudah ada?).'); }
        redirect('bom/detail/' . $bom_header_id);
    }

    // Proses hapus item komponen dari BOM
    public function delete_item($bom_header_id, $detail_id) {
        if (!check_access('bom', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Bom_model->get_header_by_id($bom_header_id);
        if (!$header || $header->status != 'Draft') { redirect('bom/detail/' . $bom_header_id); }

        if ($this->Bom_model->delete_item($detail_id)) { $this->session->set_flashdata('success', 'Komponen dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus komponen.'); }
        redirect('bom/detail/' . $bom_header_id);
    }

    // Proses hapus BOM header (dan detailnya)
    public function delete_bom($id) {
        if (!check_access('bom', 'delete')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Bom_model->get_header_by_id($id);
        // Hanya boleh hapus jika status Draft
        if (!$header || $header->status != 'Draft') { redirect('bom'); }

        if ($this->Bom_model->delete_header($id)) { $this->session->set_flashdata('success', 'BOM berhasil dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus BOM.'); }
        redirect('bom');
    }
    
    // (Opsional: Fungsi untuk update header, misal deskripsi atau status)
    public function update_header_info($id){
        // ... Logika update header ...
    }

    // Fungsi Export PDF
    public function export_pdf($id) {
        if (!check_access('bom', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Bom_model->get_details_by_header_id($id);

        $data = ['header' => $header, 'details' => $details];
        $options = new Options(); $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('bom/pdf_template', $data, true);
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
        $filename = "BOM_".str_replace('/', '-', $header->fg_code).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }
    public function confirm_bom($id) {
        if (!check_access('bom', 'edit')) { // Sesuaikan permission jika perlu
            show_error('Anda tidak memiliki izin.', 403);
        }
        $header = $this->Bom_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('warning', 'BOM ini tidak bisa dikonfirmasi (status bukan Draft).');
             redirect('bom/detail/' . $id);
        }
        // Validasi: Pastikan BOM punya minimal 1 komponen
        $details = $this->Bom_model->get_details_by_header_id($id);
        if(empty($details)){
             $this->session->set_flashdata('error', 'BOM tidak bisa dikonfirmasi karena belum ada komponen.');
             redirect('bom/detail/' . $id);
        }

        if ($this->Bom_model->update_status($id, 'Active')) {
            $this->session->set_flashdata('success', 'BOM berhasil diaktifkan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengaktifkan BOM!');
        }
        redirect('bom/detail/' . $id);
    }
}