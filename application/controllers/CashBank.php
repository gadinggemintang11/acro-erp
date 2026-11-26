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
        $data['can_edit'] = $this->_can_edit();
        $data['redraft_block'] = ($data['header']->status === 'Posted') ? $this->model->redraft_block_reason($data['header']) : null;

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

    /** Redraft & edit butuh izin Edit di menu Cash & Bank. */
    private function _can_edit()
    {
        return check_access('cashbank', 'edit') || is_admin();
    }

    /**
     * Redraft: transaksi Posted dikembalikan ke Draft supaya bisa diedit;
     * jurnalnya ikut Draft (keluar dari laporan) sampai diposting ulang.
     */
    public function redraft($id)
    {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin me-redraft transaksi Kas & Bank.', 403); return; }

        $reason = trim((string) $this->input->post('reason'));
        if (mb_strlen($reason) < 5) {
            $this->session->set_flashdata('error', 'Alasan redraft wajib diisi (minimal 5 karakter).');
            redirect('cashbank/view/' . $id);
            return;
        }
        try {
            $h = $this->model->redraft_transaction($id);
            $jno = $this->JurnalUmum_model->get_header_by_id($h->journal_header_id)->journal_no ?? '-';
            add_log('REDRAFT', 'Cash Bank', 'Redraft transaksi ' . $h->doc_no . ' (jurnal ' . $jno . ' kembali Draft). Alasan: ' . $reason);
            $this->session->set_flashdata('success', 'Transaksi ' . $h->doc_no . ' dikembalikan ke Draft. Jurnal ' . $jno
                . ' ikut menjadi Draft (tidak masuk laporan) sampai transaksi diposting ulang. Silakan edit lalu Posting lagi.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal redraft: ' . $e->getMessage());
        }
        redirect('cashbank/view/' . $id);
    }

    /** Form edit transaksi Draft (termasuk hasil redraft) -- memakai form create dalam mode edit. */
    public function edit($id)
    {
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin mengedit transaksi Kas & Bank.', 403); return; }
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        if ($header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya transaksi Draft yang bisa diedit. Untuk transaksi Posted, klik Redraft dulu.');
            redirect('cashbank/view/' . $id);
            return;
        }
        $data['title']      = sprintf(lang_line('title_cb_edit', 'Edit Transaksi: %s'), $header->doc_no);
        $data['header']     = $header;
        $data['details']    = $this->model->get_details_by_header_id($id);
        $data['doc_no']     = $header->doc_no;
        $data['currencies'] = $this->JurnalUmum_model->get_currencies();
        $data['accounts']   = $this->JurnalUmum_model->get_postable_accounts();
        $data['journal_no'] = $header->journal_header_id ? ($this->JurnalUmum_model->get_header_by_id($header->journal_header_id)->journal_no ?? null) : null;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/cash_bank/create', $data);
        $this->load->view('templates/footer');
    }

    /** Simpan hasil edit transaksi Draft. */
    public function update($id)
    {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin mengedit transaksi Kas & Bank.', 403); return; }

        $this->form_validation->set_rules('transaction_type', 'Tipe Transaksi', 'required|in_list[IN,OUT]');
        $this->form_validation->set_rules('doc_date', 'Tanggal', 'required');
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('cash_bank_account_id', 'Akun Kas/Bank', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');
        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('cashbank/edit/' . $id);
            return;
        }
        try {
            $this->model->update_transaction($id, $this->input->post());
            $h = $this->model->get_header_by_id($id);
            add_log('UPDATE', 'Cash Bank', 'Edit transaksi Draft ' . $h->doc_no . ' (total ' . $h->currency . ' ' . number_format((float) $h->total_amount, 2) . ').');
            $this->session->set_flashdata('success', 'Perubahan transaksi disimpan (masih Draft). Klik Posting bila sudah benar.');
            redirect('cashbank/view/' . $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal menyimpan: ' . $e->getMessage());
            redirect('cashbank/edit/' . $id);
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

    /**
     * PDF 1 transaksi = format Voucher Summary Ledger (permintaan user 2026-11-26):
     * kertas A4 portrait dibagi 2, voucher landscape di setengah atas + garis potong.
     * Template: summary_ledger/pdf_disbursement.php + _voucher_block.php.
     */
    public function export_pdf_detail($id)
    {
        $cb = $this->model->get_header_by_id($id);
        if (!$cb) show_404();
        $bundle = $this->_voucher_bundle($cb, $this->model->get_details_by_header_id($id));

        $html = $this->load->view('accounting/summary_ledger/pdf_disbursement', ['vouchers' => [$bundle]], true);

        $tmpDir = APPPATH . 'cache/dompdf/tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->setTempDir($tmpDir);
        $options->setFontCache(APPPATH . 'cache/dompdf');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Voucher_' . str_replace('/', '-', $cb->doc_no) . '.pdf', ['Attachment' => 0, 'compress' => 1]);
    }

    /**
     * Susun data voucher (format Summary_ledger_model::get_journal_for_pdf) dari
     * transaksi Kas/Bank. Baris jurnal = sama dengan yang dibuat post_transaction
     * (OUT: detail Debit, Kas/Bank Kredit; IN: Kas/Bank Debit, detail Kredit),
     * nilai dalam IDR (local). Draft pun bisa dicetak (belum ada jurnal).
     */
    private function _voucher_bundle($cb, $details)
    {
        $is_in = ($cb->transaction_type == 'IN');

        $journal_no = '';
        if (!empty($cb->journal_header_id)) {
            $journal_no = (string) $this->db->select('journal_no')->where('id', (int) $cb->journal_header_id)
                                            ->get('journal_header')->row('journal_no');
        }

        $header = (object) [
            'journal_no'          => $journal_no,
            'journal_date'        => $cb->doc_date,
            'document_date'       => $cb->doc_date,
            'due_date'            => null,
            'description'         => $cb->description,
            'voucher_no'          => $cb->doc_no,
            'voucher_total_label' => $is_in ? 'Total Received' : 'Total Paid',
            'voucher_sig_last'    => $is_in ? 'Received By' : 'Paid By',
        ];

        $line = function ($code, $name, $desc, $debit, $kredit) {
            return (object) ['account_code' => $code, 'account_name' => $name, 'description' => $desc,
                             'debit_local' => $debit, 'kredit_local' => $kredit];
        };
        $cash_line = $line($cb->account_code, $cb->payment_account_name, $cb->description,
                           $is_in ? (float) $cb->total_amount_local : 0, $is_in ? 0 : (float) $cb->total_amount_local);

        $lines = $is_in ? [$cash_line] : [];
        foreach ($details as $d) {
            $amt = (float) $d->amount_local;
            $lines[] = $line($d->account_code, $d->account_name, $d->description, $is_in ? 0 : $amt, $is_in ? $amt : 0);
        }
        if (!$is_in) $lines[] = $cash_line;

        return ['header' => $header, 'details' => $lines];
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
