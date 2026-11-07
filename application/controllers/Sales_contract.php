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
        // Load translation helper for status translations
        $this->load->helper('translation');
        // Load form validation library jika belum di autoload
        $this->load->library('form_validation'); 
    }

    /**
     * CREATE NEW IPO — list IPO status Draft saja, plus form add/import.
     */
    public function index() {
        if (!check_access('sales_contract', 'view')) {
            show_error(lang_line('sc_msg_no_permission_view', 'Anda tidak memiliki izin untuk melihat modul ini.'), 403);
        }

        $data = [
            'title'     => lang_line('sc_title_create', 'Create New Sales Order'),
            'contracts' => $this->Sc_model->get_drafts(),
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

    /**
     * IPO APPROVAL — list IPO yang butuh action user.
     * - User can_endorsement → lihat status 'Pending Endorsement'
     * - User can_approval    → lihat status 'Endorsed'
     * Visibility eksklusif: user endorsement tidak lihat 'Endorsed', dan sebaliknya.
     */
    public function approval() {
        if (!check_access('sales_contract_approval', 'view')) {
            show_error(lang_line('sc_msg_no_permission_view', 'Anda tidak memiliki izin untuk melihat modul ini.'), 403);
        }

        // Approval Sales Order 1 tahap (2026-11-06): cukup izin 'approval'.
        $can_endorse = false;
        $can_approve = check_access('sales_contract_approval', 'approval');
        $contracts   = $can_approve ? $this->Sc_model->get_pending_approval() : [];
        $stage       = $can_approve ? 'approval' : 'none';
        $data = [
            'title'       => lang_line('sc_title_approval', 'Sales Order Approval'),
            'contracts'   => $contracts,
            'stage'       => $stage,
            'can_endorse' => $can_endorse,
            'can_approve' => $can_approve,
            'can_reject'  => $can_approve,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'sales_contracts',
                'number' => 'sc_number',
                'date'   => 'contract_date',
            ]),
            'my_history_route'    => 'sales_contract/detail',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/approval', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Detail IPO (read-only) — bisa dibuka dari Approval/List/Create.
     * View ini tidak punya tombol aksi; hanya menampilkan data lengkap
     * + audit trail approval workflow.
     */
    public function detail($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        // Akses: minimal salah satu dari 3 menu IPO.
        $can_open = check_access('sales_contract', 'view')
                 || check_access('sales_contract_approval', 'view')
                 || check_access('sales_contract_list', 'view');
        if (!$can_open) {
            show_error(lang_line('sc_msg_no_permission_view', 'Anda tidak memiliki izin untuk melihat modul ini.'), 403);
        }

        // Pakai _get_with_filter agar dapat semua kolom audit + nama user.
        $this->db->select('
            sc.*,
            c.nama_customer, c.alamat as customer_address,
            fg.nama_barang as style_name, fg.kode_barang as style_code,
            us.fullname as submitted_by_name,
            ue.fullname as endorsed_by_name,
            ua.fullname as approved_by_name,
            ur.fullname as rejected_by_name
        ');
        $this->db->from('sales_contracts sc');
        $this->db->join('customers c',     'sc.customer_id = c.id', 'left');
        $this->db->join('finished_goods fg','sc.fg_id = fg.id',     'left');
        $this->db->join('users us', 'us.id = sc.submitted_by', 'left');
        $this->db->join('users ue', 'ue.id = sc.endorsed_by',  'left');
        $this->db->join('users ua', 'ua.id = sc.approved_by',  'left');
        $this->db->join('users ur', 'ur.id = sc.rejected_by',  'left');
        $this->db->where('sc.id', $id);
        $contract = $this->db->get()->row();

        if (!$contract) show_404();

        $data = [
            'title'    => sprintf(lang_line('title_sc_detail'), $contract->sc_number),
            'contract' => $contract,
            // Asal halaman: untuk tombol back yang benar.
            'back_url' => $this->input->get('from') ?: 'sales_contract/list_all',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * IPO LIST — semua IPO (read-only), apapun statusnya.
     */
    public function list_all() {
        if (!check_access('sales_contract_list', 'view')) {
            show_error(lang_line('sc_msg_no_permission_view', 'Anda tidak memiliki izin untuk melihat modul ini.'), 403);
        }

        $data = [
            'title'     => lang_line('sc_title_list', 'Sales Order List'),
            'contracts' => $this->Sc_model->get_all(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/list', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('sales_contract', 'add')) {
            show_error(lang_line('sc_msg_no_permission_add', 'Anda tidak memiliki izin untuk menambah data.'), 403);
        }

        // --- Validasi Form ---
        $this->form_validation->set_rules('customer_id', lang_line('sc_field_customer', 'Pelanggan'), 'required|numeric');
        $this->form_validation->set_rules('fg_id', lang_line('sc_field_style', 'Style'), 'required|numeric');
        $this->form_validation->set_rules('contract_date', lang_line('sc_field_contract_date', 'Tanggal Kontrak'), 'required');
        $this->form_validation->set_rules('lchd_date', lang_line('sc_field_lchd', 'LCHD'), 'required');
        $this->form_validation->set_rules('po_qty', lang_line('sc_field_po_qty', 'PO Qty'), 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('currency_code', lang_line('sc_field_currency', 'Mata Uang'), 'required');
        $this->form_validation->set_rules('unit_price', lang_line('sc_field_unit_price', 'Harga Satuan'), 'required');
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
            $this->session->set_flashdata('error', lang_line('sc_msg_sc_number_exists', 'Nomor Sales Contract sudah digunakan.'));
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
            'status'        => 'Draft',
        ];

        if ($this->Sc_model->insert($data)) {
            // --- LOGGING (CREATE) ---
            $deskripsi = "Membuat Sales Contract baru: $sc_number";
            $this->_log('CREATE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', lang_line('sc_msg_create_success', 'Sales Contract berhasil ditambahkan!'));
        } else {
            $this->session->set_flashdata('error', lang_line('sc_msg_create_failed', 'Gagal menambahkan Sales Contract!'));
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
            show_error(lang_line('sc_msg_no_permission_add', 'Anda tidak memiliki izin untuk menambah data.'), 403);
        }

        if (empty($_FILES['excel_file']['name'])) {
            $this->session->set_flashdata('error', lang_line('sc_msg_select_excel', 'Harap pilih file Excel (.xlsx/.xls).'));
            redirect('sales_contract');
            return;
        }

        $allowed = ['xlsx','xls'];
        $ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $allowed)) {
            $this->session->set_flashdata('error', lang_line('sc_msg_invalid_format', 'Format file tidak didukung. Gunakan .xlsx atau .xls'));
            redirect('sales_contract');
            return;
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            $this->session->set_flashdata('error', lang_line('sc_msg_read_failed', 'Gagal membaca file: ') . $e->getMessage());
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

            $row_label = lang_line('sc_msg_row', 'Baris');
            if (empty($customer_name) || empty($fg_name)) {
                $errors[] = "$row_label $idx: " . lang_line('sc_msg_customer_or_fg_empty', 'Nama customer/supplier atau nama barang kosong');
                $failed++;
                continue;
            }

            $customer_id = $this->Sc_model->get_customer_id_by_name($customer_name);
            $fg_id = $this->Sc_model->get_fg_id_by_name($fg_name);

            if (!$customer_id) {
                $errors[] = "$row_label $idx: " . lang_line('sc_msg_customer_not_found', 'Customer/Supplier tidak ditemukan') . " '$customer_name'";
                $failed++;
                continue;
            }
            if (!$fg_id) {
                $errors[] = "$row_label $idx: " . lang_line('sc_msg_fg_not_found', 'Nama barang tidak ditemukan') . " '$fg_name'";
                $failed++;
                continue;
            }

            // Generate nomor jika kosong
            $final_sc_number = $sc_number ?: $this->Sc_model->generate_sc_number();

            // Cek duplikat jika manual
            if (!empty($sc_number) && $this->Sc_model->is_sc_number_exists($sc_number)) {
                $errors[] = "$row_label $idx: SC $sc_number " . lang_line('sc_msg_sc_already_exists', 'sudah ada');
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
                'status'        => 'Draft',
            ];

            $this->Sc_model->insert($data);
            $success++;
        }

        $msg = "$success " . lang_line('sc_msg_imported_rows', 'baris berhasil di-import.');
        if ($failed > 0) {
            $msg .= " $failed " . lang_line('sc_msg_failed_rows', 'baris gagal.');
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
            show_error(lang_line('sc_msg_no_permission_template', 'Anda tidak memiliki izin untuk mengunduh template.'), 403);
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
            show_error(lang_line('sc_msg_no_permission_edit', 'Anda tidak memiliki izin untuk mengedit data ini.'), 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $contract = $this->Sc_model->get_by_id($id);
        if (!$contract) show_404(); // Tampilkan 404 jika SC tidak ditemukan

        if ($contract->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Sales Order dengan status Draft yang bisa diedit.');
            redirect('sales_contract');
            return;
        }

        $data = [
            'title'     => lang_line('sc_form_edit_title', 'Edit Sales Contract'),
            'contract'  => $contract,
            // Data untuk dropdown di form edit
            'customers' => $this->Customer_model->get_all(['status' => 'active']),
            'styles'    => $this->Fg_model->get_all(['status' => 'active']), 
            'currencies'=> $this->Commoncode_model->get_by_group('CURRENCY'),
            // Definisikan status yang diizinkan (sesuaikan jika perlu)
            'status_list' => ['Draft', 'Open', 'Closed', 'Cancelled']
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
            show_error(lang_line('sc_msg_no_permission_edit', 'Anda tidak memiliki izin untuk mengedit data.'), 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        // Ambil data lama untuk log
        $old_data = $this->Sc_model->get_by_id($id);
        if (!$old_data) show_404();

        if ($old_data->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Sales Order dengan status Draft yang bisa diedit.');
            redirect('sales_contract');
            return;
        }

        // --- Validasi Form ---
        $this->form_validation->set_rules('customer_id', lang_line('sc_field_customer', 'Pelanggan'), 'required|numeric');
        $this->form_validation->set_rules('fg_id', lang_line('sc_field_style', 'Style'), 'required|numeric');
        $this->form_validation->set_rules('contract_date', lang_line('sc_field_contract_date', 'Tanggal Kontrak'), 'required');
        $this->form_validation->set_rules('lchd_date', lang_line('sc_field_lchd', 'LCHD'), 'required');
        $this->form_validation->set_rules('po_qty', lang_line('sc_field_po_qty', 'PO Qty'), 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('currency_code', lang_line('sc_field_currency', 'Mata Uang'), 'required');
        $this->form_validation->set_rules('unit_price', lang_line('sc_field_unit_price', 'Harga Satuan'), 'required');
        $this->form_validation->set_rules('status', lang_line('sc_field_status', 'Status'), 'required');
        
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
            'status'        => 'Draft', // Edit hanya untuk Draft, status tidak bisa diubah dari form
        ];

        if ($this->Sc_model->update($id, $data)) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Memperbarui Sales Contract: {$old_data->sc_number} (ID: $id)";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            
            $this->session->set_flashdata('success', lang_line('sc_msg_update_success', 'Sales Contract berhasil diperbarui!'));
        } else {
            $this->session->set_flashdata('warning', lang_line('sc_msg_update_no_change', 'Tidak ada perubahan data Sales Contract atau gagal memperbarui.'));
        }
        redirect('sales_contract'); // Redirect ke index setelah update
    }

    public function delete($id) {
        if (!check_access('sales_contract', 'delete')) {
            show_error(lang_line('sc_msg_no_permission_delete', 'Anda tidak memiliki izin untuk menghapus data.'), 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $sc_data = $this->Sc_model->get_by_id($id);
        if (!$sc_data) show_404();

        if ($sc_data->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Sales Contract dengan status Draft yang bisa dihapus.');
            redirect('sales_contract');
            return;
        }

        if ($this->Sc_model->delete($id)) {
            $deskripsi = "Membatalkan Sales Contract: {$sc_data->sc_number} (ID: $id)";
            $this->_log('UPDATE', $deskripsi);
            $this->session->set_flashdata('success', lang_line('sc_msg_cancel_success', 'Sales Contract berhasil dibatalkan!'));
        } else {
            $this->session->set_flashdata('error', lang_line('sc_msg_cancel_failed', 'Gagal membatalkan Sales Contract!'));
        }
        redirect('sales_contract');
    }

    // ==========================================================
    // APPROVAL WORKFLOW
    // Draft → Submitted → Open (= Approved) — 1 tahap sejak 2026-11-06
    // ==========================================================

    /**
     * Submit IPO Draft → Pending Endorsement (oleh creator).
     * Yang boleh: user dengan permission 'add' di sales_contract (creator).
     */
    public function submit_for_approval($id) {
        if (!check_access('sales_contract', 'submit')) {
            show_error('Anda tidak memiliki izin untuk submit Sales Order.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $sc = $this->Sc_model->get_by_id($id);
        if (!$sc || $sc->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Sales Order dengan status Draft yang bisa di-submit.');
            redirect('sales_contract');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Sc_model->submit_for_approval($id, $user_id)) {
            $this->_log('SUBMIT', "Submit Sales Order untuk approval: {$sc->sc_number} → Submitted");
            $this->session->set_flashdata('success', "Sales Order {$sc->sc_number} berhasil di-submit, menunggu approval.");
        } else {
            $this->session->set_flashdata('error', 'Gagal submit Sales Order.');
        }
        redirect('sales_contract');
    }

    /**
     * Endorse: Pending Endorsement → Endorsed.
     */
    public function endorse($id) {
        // Tahap Endorse DIHAPUS 2026-11-06: approval Sales Order sekarang 1 tahap.
        $this->session->set_flashdata('error', 'Approval Sales Order sekarang 1 tahap -- gunakan tombol Approve.');
        redirect('sales_contract/approval');
    }

    /**
     * Approve final: Endorsed → Open (= disetujui & aktif untuk modul lain).
     */
    public function approve($id) {
        if (!check_access('sales_contract_approval', 'approval')) {
            show_error('Anda tidak memiliki izin untuk approve Sales Order.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $sc = $this->Sc_model->get_by_id($id);
        if (!$sc || !in_array($sc->status, ['Submitted', 'Pending Endorsement', 'Endorsed'], true)) {
            $this->session->set_flashdata('error', 'Sales Order ini tidak sedang menunggu approval.');
            redirect('sales_contract/approval');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Sc_model->approve($id, $user_id)) {
            $this->_log('APPROVE', "Approve Sales Order final: {$sc->sc_number} → Open");
            $this->session->set_flashdata('success', "Sales Order {$sc->sc_number} berhasil di-approve. Status: Open.");
        } else {
            $this->session->set_flashdata('error', 'Gagal approve Sales Order.');
        }
        redirect('sales_contract/approval');
    }

    /**
     * Reject: status apapun di tahap approval → Rejected.
     * Yang boleh: user dengan permission endorsement ATAU approval.
     */
    public function reject($id) {
        $can_reject = check_access('sales_contract_approval', 'approval');
        if (!$can_reject) {
            show_error('Anda tidak memiliki izin untuk reject Sales Order.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $sc = $this->Sc_model->get_by_id($id);
        if (!$sc || !in_array($sc->status, ['Submitted', 'Pending Endorsement', 'Endorsed'], true)) {
            $this->session->set_flashdata('error', 'Sales Order ini tidak dalam tahap approval.');
            redirect('sales_contract/approval');
            return;
        }

        $reason = trim($this->input->post('reject_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            redirect('sales_contract/approval');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Sc_model->reject($id, $user_id, $reason)) {
            $this->_log('REJECT', "Reject Sales Order: {$sc->sc_number} → Rejected. Alasan: {$reason}");
            $this->session->set_flashdata('success', "Sales Order {$sc->sc_number} berhasil di-reject.");
        } else {
            $this->session->set_flashdata('error', 'Gagal reject Sales Order.');
        }
        redirect('sales_contract/approval');
    }
}
