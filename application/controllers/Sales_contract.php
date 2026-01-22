<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        // sc_number optional; jika diisi dicek unik di bawah
        
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
        $input_sc_number = trim($this->input->post('sc_number'));
        $sc_number = $input_sc_number ?: $this->Sc_model->generate_sc_number();

        // Cek unik jika user mengisi manual
        if (!empty($input_sc_number) && $this->Sc_model->is_sc_number_exists($input_sc_number)) {
            $this->session->set_flashdata('error', 'Nomor Sales Contract sudah digunakan.');
            redirect('sales_contract');
            return;
        }

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

    /**
     * Upload Excel untuk bulk insert Sales Contract
     * Kolom yang dibaca (baris header di baris 1):
     * A: sc_number (boleh kosong untuk auto), B: customer_code, C: fg_code, D: contract_date (Y-m-d),
     * E: lchd_date (Y-m-d), F: po_qty, G: currency_code, H: unit_price, I: destination
     */
    public function upload_excel() {
        if (!check_access('sales_contract', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('error', 'Harap pilih file Excel (.xlsx/.xls).');
            redirect('sales_contract');
            return;
        }

        $allowed = ['xlsx','xls'];
        $ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $allowed)) {
            $this->session->set_flashdata('error', 'Format file tidak didukung. Gunakan .xlsx atau .xls');
            redirect('sales_contract');
            return;
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('sales_contract');
            return;
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        // Mulai dari baris 2 (baris 1 diasumsikan header)
        foreach ($rows as $idx => $row) {
            if ($idx == 1) continue;

            $sc_number     = trim($row['A']);
            $customer_name = trim($row['B']);
            $fg_name       = trim($row['C']);
            $contract_date = trim($row['D']);
            $lchd_date     = trim($row['E']);
            $po_qty        = (int) $row['F'];
            $currency_code = trim($row['G'] ?: 'USD');
            $unit_price    = (float) $row['H'];
            $destination   = trim($row['I']);

            if (empty($customer_name) || empty($fg_name)) {
                $errors[] = "Baris $idx: Nama customer/supplier atau nama barang kosong";
                $failed++;
                continue;
            }

            $customer_id = $this->Sc_model->get_customer_id_by_name($customer_name);
            $fg_id = $this->Sc_model->get_fg_id_by_name($fg_name);

            if (!$customer_id) {
                $errors[] = "Baris $idx: Customer/Supplier '$customer_name' tidak ditemukan";
                $failed++;
                continue;
            }
            if (!$fg_id) {
                $errors[] = "Baris $idx: Nama barang '$fg_name' tidak ditemukan";
                $failed++;
                continue;
            }

            // Generate nomor jika kosong
            $final_sc_number = $sc_number ?: $this->Sc_model->generate_sc_number();

            // Cek duplikat jika manual
            if (!empty($sc_number) && $this->Sc_model->is_sc_number_exists($sc_number)) {
                $errors[] = "Baris $idx: Nomor SC $sc_number sudah ada";
                $failed++;
                continue;
            }

            $total_price = $po_qty * $unit_price;

            $data = [
                'sc_number'     => $final_sc_number,
                'customer_id'   => $customer_id,
                'fg_id'         => $fg_id,
                'contract_date' => $contract_date ?: date('Y-m-d'),
                'lchd_date'     => $lchd_date ?: date('Y-m-d'),
                'po_qty'        => $po_qty,
                'currency_code' => $currency_code,
                'unit_price'    => $unit_price,
                'total_price'   => $total_price,
                'destination'   => $destination,
                'status'        => 'Open',
            ];

            $this->Sc_model->insert($data);
            $success++;
        }

        $msg = "$success baris berhasil di-import.";
        if ($failed > 0) {
            $msg .= " $failed baris gagal.";
        }

        if ($failed > 0) {
            $detail = implode('<br>', $errors);
            if ($success > 0) {
                $this->session->set_flashdata('warning', $msg . '<br>' . $detail);
            } else {
                $this->session->set_flashdata('error', $msg . '<br>' . $detail);
            }
        } else {
            $this->session->set_flashdata('success', $msg);
        }

        redirect('sales_contract');
    }

    /**
     * Download template Excel untuk import Sales Contract
     */
    public function download_template() {
        if (!check_access('sales_contract', 'add')) {
            show_error('Anda tidak memiliki izin untuk mengunduh template.', 403);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'sc_number (optional)');
        $sheet->setCellValue('B1', 'customer_name');
        $sheet->setCellValue('C1', 'fg_name');
        $sheet->setCellValue('D1', 'contract_date (Y-m-d)');
        $sheet->setCellValue('E1', 'lchd_date (Y-m-d)');
        $sheet->setCellValue('F1', 'po_qty');
        $sheet->setCellValue('G1', 'currency_code');
        $sheet->setCellValue('H1', 'unit_price');
        $sheet->setCellValue('I1', 'destination');

        // Sample row
        $sheet->setCellValue('A2', '');
        $sheet->setCellValue('B2', 'ACME TEXTILE');
        $sheet->setCellValue('C2', 'KAIN TWILL 40S');
        $sheet->setCellValue('D2', date('Y-m-d'));
        $sheet->setCellValue('E2', date('Y-m-d'));
        $sheet->setCellValue('F2', 1000);
        $sheet->setCellValue('G2', 'USD');
        $sheet->setCellValue('H2', 12.5);
        $sheet->setCellValue('I2', 'Jakarta');

        // Autosize columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_sales_contract.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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

        
        if ($this->Sc_model->delete($id)) { 
            // --- LOGGING (UPDATE STATUS / CANCEL) ---
            $deskripsi = "Membatalkan Sales Contract: {$sc_data->sc_number} (ID: $id)";
            $this->_log('UPDATE', $deskripsi); 
            
            $this->session->set_flashdata('success', 'Sales Contract berhasil dibatalkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan Sales Contract!');
        }
        redirect('sales_contract');
    }
}
