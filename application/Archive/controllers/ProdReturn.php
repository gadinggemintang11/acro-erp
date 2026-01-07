<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Gunakan library dari Composer
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProdReturn extends MY_Controller {

    protected $module_name = 'Material Return';

    public function __construct() {
        parent::__construct();
        // Load semua yang dibutuhkan
        $this->load->model('ProdReturn_model');
        // Stock_ledger_model akan di-load di model ProdReturn saat dibutuhkan
        $this->load->library('form_validation');
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('auth_helper'); // Asumsi helper ini ada

        // Cek login di constructor
        check_login();
    }

    /**
     * Halaman Utama: Daftar Retur
     */
    public function index() {
        if (!check_access('prodreturn', 'view')) { show_error('Anda tidak memiliki izin akses.', 403); return; }

        $data['title'] = 'Material Return from Production';
        $filters = ['search' => $this->input->get('search', TRUE)];
        $data['filters'] = $filters;
        $data['returns'] = $this->ProdReturn_model->get_all_returns($filters);

        $this->_load_views('prod_return/list', $data);
    }

    /**
     * Halaman Form: Buat Retur Baru
     */
    public function create() {
        if (!check_access('prodreturn', 'add')) { show_error('Anda tidak memiliki izin membuat data baru.', 403); return; }

        $data['title'] = 'Create Material Return';
        $data['work_orders'] = $this->ProdReturn_model->get_active_work_orders();
        $data['return_no'] = $this->ProdReturn_model->generate_return_no();

        if (empty($data['return_no'])) {
             log_message('error', 'Gagal generate nomor retur baru di controller create.');
             $this->session->set_flashdata('error', 'Gagal membuat nomor retur. Silakan coba lagi.');
             redirect('ProdReturn/index');
             return;
        }

        $this->_load_views('prod_return/form', $data);
    }

    /**
     * Aksi: Simpan Retur Baru (Draft)
     */
    public function store() {
        if (!check_access('prodreturn', 'add')) { show_error('Anda tidak memiliki izin menyimpan data.', 403); return; }

        $this->form_validation->set_rules('prod_return_no', 'Return Number', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('return_date', 'Return Date', 'required');
        $this->form_validation->set_rules('work_order_id', 'Work Order', 'required|numeric|greater_than[0]');
        // Validasi detail item (minimal 1) dilakukan manual di bawah

        if ($this->form_validation->run() == FALSE) {
            log_message('debug', 'Validasi form create retur gagal (header): ' . validation_errors());
            $this->session->set_flashdata('error', 'Gagal menyimpan. Harap periksa input header: ' . validation_errors('', ' '));
            $data['title'] = 'Create Material Return';
            $data['work_orders'] = $this->ProdReturn_model->get_active_work_orders();
            $data['return_no'] = $this->input->post('prod_return_no') ?: $this->ProdReturn_model->generate_return_no();
            $this->_load_views('prod_return/form', $data);
            return;
        }

        $header_data = [
            'prod_return_no' => $this->input->post('prod_return_no', TRUE),
            'return_date'    => $this->input->post('return_date', TRUE),
            'work_order_id'  => (int)$this->input->post('work_order_id', TRUE),
            'notes'          => strip_tags($this->input->post('notes', TRUE)),
        ];

        $detail_data = [];
        $material_ids = $this->input->post('material_id');
        $qtys = $this->input->post('qty_return');
        $reasons = $this->input->post('reason');
        $valid_details_found = false;

        if (!empty($material_ids) && is_array($material_ids)) {
            for ($i = 0; $i < count($material_ids); $i++) {
                $mat_id = $material_ids[$i] ?? null;
                $qty = isset($qtys[$i]) ? filter_var($qtys[$i], FILTER_VALIDATE_FLOAT) : false;
                $reason = isset($reasons[$i]) ? strip_tags($reasons[$i]) : null;

                if (!empty($mat_id) && is_numeric($mat_id) && $qty !== false && $qty > 0) {
                    $detail_data[] = [
                        'material_id' => (int)$mat_id,
                        'qty_return'  => (float)$qty,
                        'reason'      => $reason
                    ];
                    $valid_details_found = true;
                }
            }
        }

        if (!$valid_details_found) {
            log_message('warning', 'Percobaan menyimpan retur tanpa detail item yang valid.');
            $this->session->set_flashdata('error', 'Gagal menyimpan. Minimal harus ada 1 item material dengan Qty Retur lebih dari 0.');
            $data['title'] = 'Create Material Return';
            $data['work_orders'] = $this->ProdReturn_model->get_active_work_orders();
            $data['return_no'] = $header_data['prod_return_no'];
            $this->_load_views('prod_return/form', $data);
            return;
        }

        $result_id = $this->ProdReturn_model->create_return($header_data, $detail_data);

        if ($result_id) {
            // --- LOGGING (CREATE) ---
            $deskripsi = "Membuat draft Material Return baru: {$header_data['prod_return_no']}";
            $this->_log('CREATE', $deskripsi);
            // --- END LOG ---

            $this->session->set_flashdata('success', 'Material Return ' . $header_data['prod_return_no'] . ' berhasil disimpan sebagai DRAFT.');
            redirect('ProdReturn/view/' . $result_id);
        } else {
            log_message('error', 'Controller ProdReturn->store gagal memanggil model create_return.');
            $this->session->set_flashdata('error', 'Gagal menyimpan retur karena kesalahan database. Silakan coba lagi.');
            $data['title'] = 'Create Material Return';
            $data['work_orders'] = $this->ProdReturn_model->get_active_work_orders();
            $data['return_no'] = $header_data['prod_return_no'];
            $this->_load_views('prod_return/form', $data);
        }
    }

    /**
     * Halaman Detail Retur
     */
    public function view($id) {
        if (!check_access('prodreturn', 'view')) { show_error('Anda tidak memiliki izin akses.', 403); return; }
        $id = (int)$id; if ($id <= 0) { redirect('ProdReturn/index'); return; }

        $data['return_data'] = $this->ProdReturn_model->get_return_by_id($id);
        if (empty($data['return_data'])) {
            $this->session->set_flashdata('error', 'Data retur tidak ditemukan.');
            redirect('ProdReturn/index'); return;
        }
        $data['title'] = 'View Material Return';
        $this->_load_views('prod_return/view_detail', $data);
    }

    /**
     * Halaman Form Edit Retur (Hanya Draft)
     */
    public function edit($id) {
        if (!check_access('prodreturn', 'edit')) { show_error('Anda tidak memiliki izin akses.', 403); return; }
        $id = (int)$id; if ($id <= 0) redirect('ProdReturn/index');

        $data['return_data'] = $this->ProdReturn_model->get_return_by_id($id);
        if (empty($data['return_data'])) {
            $this->session->set_flashdata('error','Data retur tidak ditemukan.');
            redirect('ProdReturn/index'); return;
        }
        if (($data['return_data']['header']['status'] ?? '') !== 'Draft') {
            $this->session->set_flashdata('error','Hanya DRAFT yang bisa diedit.');
            redirect('ProdReturn/view/' . $id); return;
        }

        $data['title'] = 'Edit Material Return';
        $data['wip_balance'] = $this->ProdReturn_model->get_wip_balance_by_wo($data['return_data']['header']['work_order_id']);
        $this->_load_views('prod_return/form_edit', $data);
    }

    /**
     * Aksi: Update Retur (Hanya Draft)
     */
    public function update($id) {
        if (!check_access('prodreturn', 'edit')) { show_error('Anda tidak memiliki izin akses.', 403); return; }
        $id = (int)$id; if ($id <= 0) redirect('ProdReturn/index');

        // Ambil data lama untuk log
        $old_data = $this->ProdReturn_model->get_return_by_id($id);
        if (!$old_data) show_404();

        $this->form_validation->set_rules('return_date', 'Return Date', 'required');
        if (empty($this->input->post('material_id'))) {
            $this->session->set_flashdata('error','Minimal 1 item required.');
            $this->edit($id); return;
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error','Validasi header gagal.');
            $this->edit($id); return;
        }

        $header_data = [
            'return_date' => $this->input->post('return_date', TRUE),
            'notes'       => strip_tags($this->input->post('notes', TRUE))
        ];

        // Kumpulkan data detail (logika sama seperti store)
        $material_ids = $this->input->post('material_id');
        $qtys = $this->input->post('qty_return');
        $reasons = $this->input->post('reason');
        $detail_data = [];
        $valid = false;
        if (!empty($material_ids) && is_array($material_ids)) {
            for ($i=0; $i < count($material_ids); $i++) {
                $mid = $material_ids[$i] ?? null;
                $qty = isset($qtys[$i]) ? filter_var($qtys[$i], FILTER_VALIDATE_FLOAT) : false;
                $reason = $reasons[$i] ?? null;
                if (!empty($mid) && is_numeric($mid) && $qty !== false && $qty > 0) {
                    $detail_data[] = [
                        'material_id' => (int)$mid,
                        'qty_return'  => (float)$qty,
                        'reason'      => strip_tags($reason)
                    ];
                    $valid = true;
                }
            }
        }
        if (!$valid) {
            $this->session->set_flashdata('error','Minimal 1 item valid.');
            $this->edit($id); return;
        }

        $ok = $this->ProdReturn_model->update_return($id, $header_data, $detail_data);
        if ($ok) {
            // --- LOGGING (UPDATE) ---
            $deskripsi = "Memperbarui draft Material Return: {$old_data['header']['prod_return_no']} (ID: $id)";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---

            $this->session->set_flashdata('success','Draft retur diperbarui.');
            redirect('ProdReturn/view/' . $id);
        } else {
            log_message('error', "Gagal update retur ID {$id} di controller.");
            $this->session->set_flashdata('error','Gagal update retur (database error atau status bukan Draft).');
            $this->edit($id);
        }
    }

    /**
     * Delete draft
     */
    public function delete($id) {
        if (!check_access('prodreturn', 'delete')) { show_error('Anda tidak memiliki izin akses.', 403); return; }
        $id = (int)$id; if ($id <= 0) redirect('ProdReturn/index');

        // Ambil data untuk log sebelum dihapus
        $return_data = $this->ProdReturn_model->get_return_by_id($id);

        $result = $this->ProdReturn_model->delete_draft_return($id);
        if ($result['success']) {
            // --- LOGGING (DELETE) ---
            $deskripsi = "Menghapus draft Material Return: " . ($return_data['header']['prod_return_no'] ?? "ID: $id");
            $this->_log('DELETE', $deskripsi);
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('ProdReturn/index');
    }

    /**
     * AJAX: ambil WIP balance
     */
    public function get_wip_details_ajax($wo_id = 0) {
        header('Content-Type: application/json');
        $wo_id = (int)$wo_id;
        if ($wo_id <= 0) {
            $this->output->set_status_header(400); // Bad Request
            echo json_encode(['success'=>false,'message'=>'Invalid WO ID']);
            exit;
        }

        $res = $this->ProdReturn_model->get_wip_balance_by_wo($wo_id);
        if ($res === false) { // Cek error model
            $this->output->set_status_header(500); // Server Error
            echo json_encode(['success'=>false,'message'=>'Gagal ambil data (Database Error).']);
            exit;
        }

        // Cek data kosong
        if (empty($res)) {
            echo json_encode(['success'=>true, 'data'=>[], 'message'=>'Tidak ada sisa material ditemukan di WIP untuk WO ini.']);
            exit;
        }

        // Sukses
        echo json_encode(['success'=>true,'data'=>$res]);
        exit;
    }


    /**
     * --- PERBAIKAN ---
     * KONFIRMASI RETUR (Sekarang memanggil model)
     */
    public function confirm($id) {
        if (!check_access('prodreturn', 'edit')) { // Ganti key permission jika perlu
            show_error('Anda tidak memiliki izin akses.', 403); return;
        }
        $id = (int)$id; if ($id <= 0) { redirect('ProdReturn/index'); return; }

        // Panggil FUNGSI MODEL yang sudah diperbaiki
        $result = $this->ProdReturn_model->confirm_return($id);

        if ($result['success']) {
            // --- LOGGING (APPROVE) ---
            $return_data = $this->ProdReturn_model->get_return_by_id($id); // Ambil data lagi untuk nomornya
            $deskripsi = "Konfirmasi (approve) Material Return: " . ($return_data['header']['prod_return_no'] ?? "ID: $id");
            $this->_log('APPROVE', $deskripsi);
            // --- END LOG ---
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('ProdReturn/view/' . $id);
    }

    /**
     * --- PERBAIKAN ---
     * BATALKAN KONFIRMASI (Sekarang memanggil model)
     */
    public function unconfirm($id) {
        if (!check_access('prodreturn', 'edit')) { // Ganti key permission jika perlu
            show_error('Anda tidak memiliki izin akses.', 403); return;
        }
        $id = (int)$id; if ($id <= 0) { redirect('ProdReturn/index'); return; }

        // Panggil FUNGSI MODEL yang sudah diperbaiki
        $result = $this->ProdReturn_model->unconfirm_return($id);

        if ($result['success']) {
            // --- LOGGING (UPDATE) ---
            $return_data = $this->ProdReturn_model->get_return_by_id($id); // Ambil data lagi untuk nomornya
            $deskripsi = "Membatalkan konfirmasi Material Return: " . ($return_data['header']['prod_return_no'] ?? "ID: $id") . ". Status kembali ke Draft.";
            $this->_log('UPDATE', $deskripsi);
            // --- END LOG ---
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('ProdReturn/view/' . $id);
    }


    /**
     * Export PDF (memanggil model untuk data)
     */
    public function export_pdf($id) {
        if (!check_access('prodreturn', 'export')) { show_error('Anda tidak memiliki izin akses.', 403); return; }
        $id = (int)$id; if ($id <= 0) redirect('ProdReturn/index');

        $data['return_data'] = $this->ProdReturn_model->get_data_for_pdf($id);
        if (empty($data['return_data'])) { /* ... */ }

        $data['title'] = 'Bukti Retur Material - ' . $data['return_data']['header']['prod_return_no'];
        try {
            $html = $this->load->view('prod_return/print_pdf', $data, TRUE);
            $mpdf = new Mpdf(['mode'=>'utf-8','format'=>'A4', 'margin_left'=>10, 'margin_right'=>10, 'margin_top'=>10, 'margin_bottom'=>10]);
            $mpdf->WriteHTML($html);
            $fname = 'RFP-' . $data['return_data']['header']['prod_return_no'] . '.pdf';
            $mpdf->Output($fname, 'I'); exit;
        } catch (Exception $e) {
            log_message('error','PDF Export Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF. Cek log server.');
        }
    }

    /**
     * Export Excel (list)
     */
    public function export_excel() {
        if (!check_access('prodreturn', 'export')) { show_error('Anda tidak memiliki izin akses.', 403); return; }

        $filters = ['search' => $this->input->get('search', TRUE)];
        $returns = $this->ProdReturn_model->get_all_returns($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Returns');

        $headers = ['No. Retur','Tgl Retur','No. Produksi (WO)','Diretur Oleh','Diterima Gudang Oleh','Status'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($returns as $r) {
            $sheet->setCellValue('A'.$row, $r->return_number ?? '');
            if (!empty($r->return_date)) {
                $sheet->setCellValue('B'.$row, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($r->return_date)));
                $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            $sheet->setCellValue('C'.$row, $r->production_number ?? '');
            $sheet->setCellValue('D'.$row, $r->returner_name ?? '');
            $sheet->setCellValue('E'.$row, $r->confirmer_name ?? ''); // PERBAIKAN: Gunakan confirmer_name
            $sheet->setCellValue('F'.$row, $r->status ?? '');
            $row++;
        }

        foreach(range('A','F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $writer = new Xlsx($spreadsheet);
        $fname = 'material_return_export_'.date('YmdHis').'.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fname.'"');
        header('Cache-Control: max-age=0');
        try { $writer->save('php://output'); }
        catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) { log_message('error', 'Excel Export Error: ' . $e->getMessage()); }
        exit;
    }

    /**
     * Helper load views (template)
     */
    private function _load_views($main_view, $data = []) {
        if (!isset($data['title'])) $data['title'] = 'Material Return';
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}