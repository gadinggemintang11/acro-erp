<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_issuance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // Load model (Mr_model mungkin tidak perlu jika header diambil dari Material_issuance_model)
        // Tapi kita biarkan dulu jika Mr_model punya fungsi get_header_by_id
        $this->load->model(['Mr_model', 'Material_issuance_model', 'Stock_ledger_model']);
        $this->load->helper('url');
        $this->load->library('session'); // Pastikan session di-load
    }

    // Halaman daftar MR (Submitted, Issued, Received)
    public function index() {
        if (!check_access('material_issuance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return; // Tambahkan return
        }
        
        $filters = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status')
        ];

        $data = [
            'title'   => 'Material Issuance (Pengeluaran Barang)',
            'mrs'     => $this->Material_issuance_model->get_all_mrs($filters),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman detail untuk input Qty Gudang
    public function detail($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        // Ambil header dari Mr_model (seperti kode Anda)
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || !in_array($header->status, ['Submitted', 'Issued', 'Received'])) {
            $this->session->set_flashdata('error', 'Material Request ini tidak valid atau status tidak sesuai.');
            redirect('material_issuance');
            return;
        }

        $data = [
            'title'   => 'Proses Pengeluaran Material',
            'header'  => $header,
            'details' => $this->Material_issuance_model->get_mr_details_for_issuance($mr_id) // Ini sudah ada total_available_stock
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/detail', $data);
        // Pastikan footer di-load di view detail atau di sini
        // $this->load->view('templates/footer'); 
    }
    
    // --- FUNGSI ajax_get_stock() DIHAPUS KARENA TIDAK DIPERLUKAN LAGI ---
    
    // Proses Konfirmasi Pengeluaran (Submit Form)
    public function process_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        $header = $this->Mr_model->get_header_by_id($mr_id);
        // Hanya Submitted yang bisa diproses
        if (!$header || $header->status != 'Submitted') {
            $this->session->set_flashdata('error', 'MR ini tidak bisa diproses (Status: ' . ($header->status ?? 'N/A') . ').');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }

        $items = $this->input->post('items');
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Tidak ada item yang diisi.');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if (!$user_id) {
             $this->session->set_flashdata('error', 'Sesi Anda berakhir. Silakan login ulang.');
             redirect('material_issuance/detail/' . $mr_id);
             return;
        }

        // Panggil model baru yang sudah FIFO
        $result = $this->Material_issuance_model->process_issuance($mr_id, $items, $user_id);
        
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Barang berhasil dikeluarkan dan stok (FIFO) dipotong.');
        } else {
            // Tampilkan pesan error dari model
            $this->session->set_flashdata('error', 'Gagal: ' . ($result['message'] ?? 'Terjadi kesalahan tidak diketahui.'));
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
    
    // Proses Batal Pengeluaran (Un-Issue)
    public function reverse_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        if (empty($mr_id) || !is_numeric($mr_id)) { show_404(); } // Validasi ID

        $header = $this->Mr_model->get_header_by_id($mr_id);
        // Hanya Issued yang bisa dibatalkan
        if (!$header || $header->status != 'Issued') {
            $this->session->set_flashdata('error', 'MR tidak bisa dibatalkan (Status: ' . ($header->status ?? 'N/A') . ').');
            redirect('material_issuance/detail/' . $mr_id);
            return;
        }
        
        // Cek jika sudah 'Received' oleh produksi (logika Anda sudah benar)
        if ($header->status == 'Received') {
           $this->session->set_flashdata('error', 'Tidak bisa dibatalkan, barang sudah diterima Produksi!');
           redirect('material_issuance/detail/' . $mr_id);
           return;
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Material_issuance_model->reverse_issuance($mr_id, $user_id)) {
            $this->session->set_flashdata('success', 'Pengeluaran berhasil dibatalkan, stok (FIFO) dikembalikan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan pengeluaran (Database Error).');
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
}