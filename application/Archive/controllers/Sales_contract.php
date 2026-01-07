<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_contract extends MY_Controller {

    // 1. Tambahkan nama modul
    protected $module_name = 'Sales Contract';

    public function __construct() {
        parent::__construct();
        check_login();
        // Muat semua model yang dibutuhkan
        $this->load->model(['Sc_model', 'Customer_model', 'Fg_model', 'Commoncode_model']);
        // Load form helper untuk view edit
        $this->load->helper('form'); 
        // Load form validation library jika belum di autoload
        $this->load->library('form_validation'); 
    }

    public function index() {
        if (!check_access('sales_contract', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'     => 'Sales Contract',
            'contracts' => $this->Sc_model->get_all(),
            // Data untuk modal Add
            'customers' => $this->Customer_model->get_all(['status' => 'active']),
            'styles'    => $this->Fg_model->get_all(['status' => 'active']), 
            'currencies'=> $this->Commoncode_model->get_by_group('CURRENCY')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('sales_contract', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }
        
        // --- Validasi Form ---
        $this->form_validation->set_rules('customer_id', 'Pelanggan', 'required|numeric');
        $this->form_validation->set_rules('fg_id', 'Style', 'required|numeric');
        $this->form_validation->set_rules('contract_date', 'Tanggal Kontrak', 'required');
        $this->form_validation->set_rules('lchd_date', 'LCHD', 'required');
        $this->form_validation->set_rules('po_qty', 'PO Qty', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');
        $this->form_validation->set_rules('unit_price', 'Harga Satuan', 'required'); // Validasi numeric bisa ditambahkan
        
        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('sales_contract');
            return;
        }
        // --- Akhir Validasi ---

        $po_qty = (int) $this->input->post('po_qty');
        // Bersihkan koma dan konversi ke float
        $unit_price_str = str_replace([',', '.'], ['', '.'], $this->input->post('unit_price')); 
        $unit_price = (float) $unit_price_str;
        $total_price = $po_qty * $unit_price;
        $sc_number = $this->Sc_model->generate_sc_number();

        $data = [
            'sc_number'     => $sc_number,
            'customer_id'   => $this->input->post('customer_id'),
            'fg_id'         => $this->input->post('fg_id'),
            'contract_date' => $this->input->post('contract_date'),
            'lchd_date'     => $this->input->post('lchd_date'),
            'po_qty'        => $po_qty,
            'currency_code' => $this->input->post('currency_code'),
            'unit_price'    => $unit_price,
            'total_price'   => $total_price,
            'destination'   => $this->input->post('destination'),
            'status'        => 'Open', // Default saat create
        ];

        if ($this->Sc_model->insert($data)) {
            // --- LOGGING (CREATE) ---
            $deskripsi = "Membuat Sales Contract baru: $sc_number";
            $this->_log('CREATE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'Sales Contract berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan Sales Contract!');
        }
        redirect('sales_contract');
    }
    
    // ==========================================================
    // FUNGSI BARU UNTUK MENAMPILKAN FORM EDIT
    // ==========================================================
    public function edit($id) {
        if (!check_access('sales_contract', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data ini.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $contract = $this->Sc_model->get_by_id($id);
        if (!$contract) show_404(); // Tampilkan 404 jika SC tidak ditemukan

        $data = [
            'title'     => 'Edit Sales Contract',
            'contract'  => $contract,
            // Data untuk dropdown di form edit
            'customers' => $this->Customer_model->get_all(['status' => 'active']),
            'styles'    => $this->Fg_model->get_all(['status' => 'active']), 
            'currencies'=> $this->Commoncode_model->get_by_group('CURRENCY'),
            // Definisikan status yang diizinkan (sesuaikan jika perlu)
            'status_list' => ['Open', 'Closed', 'Cancelled'] 
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/edit', $data); // Load view edit
        $this->load->view('templates/footer');
    }
    // ==========================================================
    // AKHIR FUNGSI BARU
    // ==========================================================


    public function update($id) {
        if (!check_access('sales_contract', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404(); 

        // Ambil data lama untuk log
        $old_data = $this->Sc_model->get_by_id($id);
        if (!$old_data) show_404();

        // --- Validasi Form ---
        $this->form_validation->set_rules('customer_id', 'Pelanggan', 'required|numeric');
        $this->form_validation->set_rules('fg_id', 'Style', 'required|numeric');
        $this->form_validation->set_rules('contract_date', 'Tanggal Kontrak', 'required');
        $this->form_validation->set_rules('lchd_date', 'LCHD', 'required');
        $this->form_validation->set_rules('po_qty', 'PO Qty', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');
        $this->form_validation->set_rules('unit_price', 'Harga Satuan', 'required');
        $this->form_validation->set_rules('status', 'Status', 'required'); // Validasi status
        
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kembalikan ke halaman edit dengan error
            $this->session->set_flashdata('error', validation_errors());
            // Redirect kembali ke halaman edit, bukan index
            redirect('sales_contract/edit/' . $id); 
            return;
        }
        // --- Akhir Validasi ---

        $po_qty = (int) $this->input->post('po_qty');
        $unit_price_str = str_replace([',', '.'], ['', '.'], $this->input->post('unit_price')); 
        $unit_price = (float) $unit_price_str;
        $total_price = $po_qty * $unit_price;

        $data = [
            'customer_id'   => $this->input->post('customer_id'),
            'fg_id'         => $this->input->post('fg_id'),
            'contract_date' => $this->input->post('contract_date'),
            'lchd_date'     => $this->input->post('lchd_date'),
            'po_qty'        => $po_qty,
            'currency_code' => $this->input->post('currency_code'),
            'unit_price'    => $unit_price,
            'total_price'   => $total_price,
            'destination'   => $this->input->post('destination'),
            'status'        => $this->input->post('status'), // Ambil status dari form
        ];

        if ($this->Sc_model->update($id, $data)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Memperbarui Sales Contract: {$old_data->sc_number} (ID: $id)";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'Sales Contract berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('warning', 'Tidak ada perubahan data Sales Contract atau gagal memperbarui.');
        }
        redirect('sales_contract'); // Redirect ke index setelah update
    }

    public function delete($id) {
        if (!check_access('sales_contract', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404(); 

        $sc_data = $this->Sc_model->get_by_id($id);
        if (!$sc_data) show_404();

        // Asumsi model->delete() = soft delete (update status Cancelled)
        // Jika hard delete, logika log harus disesuaikan
        if ($this->Sc_model->delete($id)) { 
            // --- LOGGING (UPDATE STATUS / CANCEL) ---
            $deskripsi = "Membatalkan Sales Contract: {$sc_data->sc_number} (ID: $id)";
            $this->_log('UPDATE', $deskripsi); // Atau 'DELETE' jika hard delete
            // --- END LOG ---
            
            $this->session->set_flashdata('success', 'Sales Contract berhasil dibatalkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan Sales Contract!');
        }
        redirect('sales_contract');
    }
}
