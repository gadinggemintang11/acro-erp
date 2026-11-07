<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Impor library ekspor
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class CashBank extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('CashBank_model', 'model');
        $this->load->model('JurnalUmum_model');
        $this->load->model('SettingAkun_model');
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    /**
     * Halaman Utama: Daftar Transaksi Kas/Bank
     */
    public function index()
    {
        $data['title'] = lang_line('title_cb_index', 'Transaksi Kas & Bank');
        $data['transactions'] = $this->model->get_all_headers();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/cash_bank/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman Buat Transaksi Baru
     */
    public function create()
    {
        $data['title'] = lang_line('title_cb_create', 'Buat Transaksi Kas/Bank Baru');
        $data['doc_no'] = $this->model->get_new_doc_number();
        $data['currencies'] = $this->JurnalUmum_model->get_currencies();
        // Ambil semua akun postable untuk dropdown detail
        $data['accounts'] = $this->JurnalUmum_model->get_postable_accounts();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/cash_bank/create', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman View Transaksi
     */
    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();

        $data['title'] = sprintf(lang_line('title_cb_view', 'View Transaksi: %s'), $data['header']->doc_no);
        $data['details'] = $this->model->get_details_by_header_id($id);

        if ($data['header']->journal_header_id) {
            $data['journal_header'] = $this->JurnalUmum_model->get_header_by_id($data['header']->journal_header_id);
            $data['journal_details'] = $this->JurnalUmum_model->get_details_by_header_id($data['header']->journal_header_id);
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/cash_bank/view', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Proses Simpan (Draft)
     */
    public function store()
    {
        $this->form_validation->set_rules('transaction_type', 'Tipe Transaksi', 'required');
        $this->form_validation->set_rules('doc_date', 'Tanggal', 'required');
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('cash_bank_account_id', 'Akun Kas/Bank', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');

        $details = $this->input->post('details');
        if (empty($details)) {
            $this->form_validation->set_rules('details_dummy', 'Detail Akun', 'required', [
                'required' => 'Detail Akun tidak boleh kosong.'
            ]);
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            $this->create(); // Kembali ke form
        } else {
            $post_data = $this->input->post();
            try {
                $header_id = $this->model->insert_transaction($post_data);
                if ($header_id) {
                    $this->session->set_flashdata('success', 'Transaksi berhasil disimpan sebagai Draft.');
                    redirect('cashbank/view/' . $header_id);
                } else {
                    throw new Exception('Gagal menyimpan data.');
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect('cashbank/create');
            }
        }
    }

    /**
     * Hapus (Hanya Draft)
     */
    public function delete($id)
    {
        $result = $this->model->delete_draft_transaction($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Transaksi Draft berhasil dihapus.');
            redirect('cashbank');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('cashbank/view/' . $id);
        }
    }

    /**
     * Konfirmasi & Posting Jurnal
     */
    public function post($id)
    {
        $user_id = $this->session->userdata('user_id');
        try {
            $result = $this->model->post_transaction($id, $user_id);
            if ($result['status'] == TRUE) {
                $this->session->set_flashdata('success', 'Transaksi berhasil di-posting. Jurnal ' . $result['journal_no'] . ' dibuat.');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal Posting: ' . $e->getMessage());
        }
        redirect('cashbank/view/' . $id);
    }

    /**
     * ======================================
     * AJAX HELPERS (Untuk form create)
     * ======================================
     */

    // AJAX: Ambil Akun Kas/Bank berdasarkan Currency
    public function ajax_get_cashbank_accounts()
    {
        $currency = $this->input->get('currency');
        $accounts = $this->model->get_cash_bank_accounts_by_currency($currency);
        echo json_encode(['success' => true, 'data' => $accounts]);
    }

    // AJAX: Ambil Akun Lawan (Non Kas/Bank) berdasarkan Currency
    public function ajax_get_other_accounts()
    {
        $currency = $this->input->get('currency');
        $accounts = $this->model->get_postable_accounts_by_currency($currency);
        echo json_encode(['success' => true, 'data' => $accounts]);
    }

    /**
     * ======================================
     * FUNGSI EKSPOR
     * ======================================
     */

    public function export_excel()
    {
        $transactions = $this->model->get_all_headers();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Daftar Transaksi Kas & Bank');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = [
            'No. Dokumen', 'Tgl. Transaksi', 'Tipe', 'Akun Kas/Bank',
            'Keterangan', 'Total', 'Currency', 'Status Jurnal', 'No. Jurnal'
        ];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:I3')->getFont()->setBold(true);

        $rowNum = 4;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $rowNum, $t->doc_no);
            $sheet->setCellValue('B' . $rowNum, $t->doc_date);
            $sheet->setCellValue('C' . $rowNum, $t->transaction_type);
            $sheet->setCellValue('D' . $rowNum, $t->payment_account_name);
            $sheet->setCellValue('E' . $rowNum, $t->description);
            $sheet->setCellValue('F' . $rowNum, $t->total_amount);
            $sheet->setCellValue('G' . $rowNum, $t->currency);
            $sheet->setCellValue('H' . $rowNum, $t->status);
            $sheet->setCellValue('I' . $rowNum, $t->journal_header_id ? 'JRN-' . $t->journal_header_id : 'N/A');

            $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar_Kas_Bank_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    public function export_pdf()
    {
        $data['title'] = lang_line('title_cb_export_pdf_list', 'Daftar Transaksi Kas & Bank');
        $data['transactions'] = $this->model->get_all_headers();

        $html = $this->load->view('accounting/cash_bank/pdf_index_view', $data, true);
        $this->generate_pdf($html, 'Daftar_Kas_Bank.pdf', 'A4', 'landscape');
    }

    public function export_pdf_detail($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();

        $data['title'] = ($data['header']->transaction_type == 'IN') ?
            lang_line('title_cb_receipt_in', 'Bukti Kas/Bank Masuk') : lang_line('title_cb_receipt_out', 'Bukti Kas/Bank Keluar');
        $data['details'] = $this->model->get_details_by_header_id($id);

        $html = $this->load->view('accounting/cash_bank/pdf_detail_view', $data, true);
        $this->generate_pdf($html, 'CashBank_' . $data['header']->doc_no . '.pdf');
    }

    private function generate_pdf($html, $filename, $paper = 'A4', $orientation = 'portrait')
    {
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $dompdf->stream($filename, array("Attachment" => 0));
    }
}
