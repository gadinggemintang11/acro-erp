<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as PssDate;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;

/**
 * Summary Ledger — Import jurnal dari Excel software akuntansi China.
 * Hasil parsing -> journal_header / journal_details dengan status Draft.
 */
class Summary_ledger extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('Summary_ledger_model', 'model');
        $this->load->helper(['form', 'url']);
    }

    public function index()
    {
        $data['title']   = lang_line('summary_ledger_title', 'Upload Voucher');
        $data['imports'] = $this->model->get_recent_imports(50);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/summary_ledger/index', $data);
        $this->load->view('templates/footer');
    }

    public function process_import()
    {
        if ($this->input->method() !== 'post') {
            redirect('summary_ledger');
            return;
        }

        $upload_path = FCPATH . 'uploads/temp/';
        if (!is_dir($upload_path) && !mkdir($upload_path, 0777, TRUE)) {
            $this->session->set_flashdata('error', 'Gagal membuat folder upload. Periksa permission folder.');
            redirect('summary_ledger');
            return;
        }
        if (!is_writable($upload_path)) {
            $this->session->set_flashdata('error', 'Folder upload tidak dapat ditulis: ' . $upload_path);
            redirect('summary_ledger');
            return;
        }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'xls|xlsx|csv',
            'max_size'      => 15360, // 15MB
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_excel')) {
            $err = $this->upload->display_errors('', '');
            $this->session->set_flashdata('error', 'Upload gagal: ' . $err);
            redirect('summary_ledger');
            return;
        }

        $file_data = $this->upload->data();
        $file_path = $file_data['full_path'];
        $orig_name = $file_data['orig_name'] ?? ($file_data['client_name'] ?? basename($file_path));
        $sheet_name = trim((string) $this->input->post('sheet_name')) ?: 'Journal Import';

        try {
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            $reader = ($extension === 'csv') ? new Csv()
                    : (($extension === 'xls') ? new Xls() : new Xlsx());

            $spreadsheet = $reader->load($file_path);

            // Pilih sheet sesuai input (default 'Journal Import'), fallback active sheet.
            $worksheet = $spreadsheet->getSheetByName($sheet_name);
            if ($worksheet === null) {
                $worksheet = $spreadsheet->getActiveSheet();
            }

            $sheet_data    = [];
            $highestRow    = $worksheet->getHighestDataRow();
            $highestColumn = $worksheet->getHighestDataColumn();

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                foreach (range('A', $highestColumn) as $col) {
                    $cell = $worksheet->getCell($col . $row);
                    $value = $cell->getCalculatedValue();
                    // Konversi serial date Excel ke DateTime jika cell adalah date
                    if (PssDate::isDateTime($cell) && is_numeric($value)) {
                        try {
                            $value = PssDate::excelToDateTimeObject((float) $value);
                        } catch (\Throwable $e) { /* keep raw */ }
                    }
                    $rowData[$col] = $value;
                }
                $sheet_data[$row] = $rowData;
            }

            // Hapus file upload — kita sudah punya data di memory
            @unlink($file_path);

            $account_map = $this->model->build_account_map();

            $parsed = $this->model->parse_sheet($sheet_data, $account_map);
            $vouchers       = $parsed['vouchers'];
            $skipped        = $parsed['skipped'];
            $parse_warnings = $parsed['parse_warnings'];

            $created_journals = [];
            if (!empty($vouchers)) {
                $insert_result = $this->model->insert_vouchers($vouchers, $this->session->userdata('user_id'));
                if (!$insert_result['success']) {
                    $this->session->set_flashdata('error', 'Gagal menyimpan transaksi ke database (transaksi di-rollback).');
                    redirect('summary_ledger');
                    return;
                }
                $created_journals = $insert_result['created_journals'];
            }

            // Catat log import
            $error_summary = '';
            if (!empty($skipped) || !empty($parse_warnings)) {
                $bits = [];
                foreach ($skipped as $s) {
                    $bits[] = '[' . $s['voucher_no'] . ' rows ' . $s['rows'] . '] ' . implode(' | ', $s['reasons']);
                }
                foreach ($parse_warnings as $w) $bits[] = $w;
                $error_summary = mb_substr(implode("\n", $bits), 0, 5000);
            }

            $import_id = $this->model->log_import([
                'original_filename' => mb_substr($orig_name, 0, 255),
                'total_rows'        => count($sheet_data),
                'total_vouchers'    => count($vouchers) + count($skipped),
                'success_vouchers'  => count($vouchers),
                'skipped_vouchers'  => count($skipped),
                'error_summary'     => $error_summary !== '' ? $error_summary : null,
                'uploaded_by'       => $this->session->userdata('user_id'),
                'uploaded_at'       => date('Y-m-d H:i:s'),
            ]);

            // Tautkan setiap journal yang baru di-create ke import_id
            if (!empty($created_journals) && $import_id) {
                $journal_ids = array_map(fn($j) => (int) $j['id'], $created_journals);
                $this->model->link_journals_to_import($journal_ids, (int) $import_id);
            }

            $data['title']            = lang_line('summary_ledger_result', 'Hasil Import Summary Ledger');
            $data['original_filename']= $orig_name;
            $data['sheet_name']       = $sheet_name;
            $data['created_journals'] = $created_journals;
            $data['skipped']          = $skipped;
            $data['parse_warnings']   = $parse_warnings;
            $data['import_id']        = (int) $import_id;

            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('accounting/summary_ledger/result', $data);
            $this->load->view('templates/footer');

        } catch (\Throwable $e) {
            if (file_exists($file_path)) @unlink($file_path);
            $this->session->set_flashdata('error', 'Gagal membaca file Excel: ' . $e->getMessage());
            redirect('summary_ledger');
        }
    }

    /**
     * Batch-post semua journal Draft yang baru saja di-import.
     * Menerima journal_ids[] via POST dari form di halaman result.
     */
    public function post_imported()
    {
        if ($this->input->method() !== 'post') {
            redirect('summary_ledger');
            return;
        }

        $journal_ids = $this->input->post('journal_ids');
        if (!is_array($journal_ids) || empty($journal_ids)) {
            $this->session->set_flashdata('error', 'Tidak ada journal untuk di-post.');
            redirect('summary_ledger');
            return;
        }

        $posted_count = $this->model->post_imported_journals(
            $journal_ids,
            $this->session->userdata('user_id')
        );

        if ($posted_count > 0) {
            $this->session->set_flashdata(
                'success',
                "Berhasil mem-post {$posted_count} journal dari hasil import (status Draft → Posted)."
            );
        } else {
            $this->session->set_flashdata(
                'error',
                'Tidak ada journal yang ter-post. Mungkin sudah di-post sebelumnya.'
            );
        }

        redirect('jurnalumum');
    }

    /**
     * Halaman detail satu import — list semua journal yang masuk dari batch tsb,
     * dengan tombol PDF Disbursement & Hapus (untuk yang masih Draft).
     */
    public function detail($import_id)
    {
        $import = $this->model->get_import_by_id($import_id);
        if (!$import) show_404();

        $data['title']    = lang_line('summary_ledger_detail', 'Detail Import Summary Ledger');
        $data['import']   = $import;
        $data['journals'] = $this->model->get_journals_by_import_id($import_id);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/summary_ledger/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman list semua voucher disbursement (journal hasil Summary Ledger).
     * Mendukung filter status (Draft/Posted), search, date range.
     */
    public function voucher_list()
    {
        if (!check_access('voucher_disbursement_list', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengakses modul ini.', 403);
        }

        $filters = [
            'status'    => $this->input->get('status') ?: 'all',
            'q'         => trim((string) $this->input->get('q')),
            'date_from' => $this->input->get('date_from') ?: null,
            'date_to'   => $this->input->get('date_to')   ?: null,
        ];

        // Pagination
        $per_page = 25;
        $page     = (int) $this->input->get('page');
        $offset   = $page > 0 ? ($page - 1) * $per_page : 0;
        $total    = $this->model->count_vouchers($filters);

        $this->load->library('pagination');
        $config = [
            'base_url'             => site_url('summary_ledger/voucher_list'),
            'total_rows'           => $total,
            'per_page'             => $per_page,
            'page_query_string'    => TRUE,
            'query_string_segment' => 'page',
            'reuse_query_string'   => TRUE,
            'use_page_numbers'     => TRUE,
            'full_tag_open'  => '<ul class="pagination justify-content-end mb-0">',
            'full_tag_close' => '</ul>',
            'num_tag_open'   => '<li class="page-item">', 'num_tag_close'  => '</li>',
            'cur_tag_open'   => '<li class="page-item active"><a class="page-link" href="#">', 'cur_tag_close' => '</a></li>',
            'first_tag_open' => '<li class="page-item">', 'first_tag_close'=> '</li>',
            'last_tag_open'  => '<li class="page-item">', 'last_tag_close' => '</li>',
            'next_tag_open'  => '<li class="page-item">', 'next_tag_close' => '</li>',
            'prev_tag_open'  => '<li class="page-item">', 'prev_tag_close' => '</li>',
            'attributes'     => ['class' => 'page-link'],
        ];
        $this->pagination->initialize($config);

        $data['title']         = lang_line('voucher_disbursement_list_title', 'Voucher Disbursement List');
        $data['vouchers']      = $this->model->get_voucher_list($filters, $per_page, $offset);
        $data['filters']       = $filters;
        $data['status_counts'] = $this->model->get_voucher_status_counts($filters);
        $data['total_rows']    = $total;
        $data['row_offset']    = $offset;
        $data['pagination']    = $this->pagination->create_links();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/summary_ledger/voucher_list', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Bulk post voucher Draft → Posted.
     * Menerima journal_ids[] via POST dari form di voucher_list.
     */
    public function bulk_post_vouchers()
    {
        if (!check_access('voucher_disbursement_list', 'approval')
            && !check_access('voucher_disbursement_list', 'confirm')) {
            show_error('Anda tidak memiliki izin untuk mem-post voucher.', 403);
        }

        $journal_ids = $this->input->post('journal_ids');
        if (!is_array($journal_ids) || empty($journal_ids)) {
            $this->session->set_flashdata('error', 'Tidak ada voucher yang dipilih.');
            redirect($this->input->server('HTTP_REFERER') ?: 'summary_ledger/voucher_list');
            return;
        }

        $posted = $this->model->post_imported_journals(
            $journal_ids,
            $this->session->userdata('user_id')
        );

        if ($posted > 0) {
            $this->session->set_flashdata('success', "Berhasil mem-post {$posted} voucher (Draft → Posted).");
        } else {
            $this->session->set_flashdata('error', 'Tidak ada voucher yang ter-post (mungkin sudah Posted atau bukan Draft).');
        }
        redirect($this->input->server('HTTP_REFERER') ?: 'summary_ledger/voucher_list');
    }

    /**
     * Bulk delete voucher Draft.
     * Menerima journal_ids[] via POST dari form di voucher_list.
     */
    public function bulk_delete_vouchers()
    {
        if (!check_access('voucher_disbursement_list', 'delete')
            && !check_access('summary_ledger', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus voucher.', 403);
        }

        $journal_ids = $this->input->post('journal_ids');
        if (!is_array($journal_ids) || empty($journal_ids)) {
            $this->session->set_flashdata('error', 'Tidak ada voucher yang dipilih.');
            redirect($this->input->server('HTTP_REFERER') ?: 'summary_ledger/voucher_list');
            return;
        }

        $deleted = $this->model->bulk_delete_draft_vouchers($journal_ids);

        if ($deleted > 0) {
            $this->session->set_flashdata('success', "Berhasil menghapus {$deleted} voucher Draft.");
        } else {
            $this->session->set_flashdata('error', 'Tidak ada voucher yang terhapus (hanya status Draft yang bisa dihapus).');
        }
        redirect($this->input->server('HTTP_REFERER') ?: 'summary_ledger/voucher_list');
    }

    /**
     * Post 1 voucher Draft jadi Posted dari halaman list.
     */
    public function post_voucher($journal_id)
    {
        if (!check_access('voucher_disbursement_list', 'approval')
            && !check_access('voucher_disbursement_list', 'confirm')) {
            show_error('Anda tidak memiliki izin untuk mem-post voucher.', 403);
        }

        $ok = $this->model->post_single_voucher($journal_id, $this->session->userdata('user_id'));
        if ($ok) {
            $this->session->set_flashdata('success', 'Voucher berhasil di-post.');
        } else {
            $this->session->set_flashdata('error', 'Voucher tidak bisa di-post (sudah Posted atau bukan dari Summary Ledger).');
        }
        redirect($this->input->server('HTTP_REFERER') ?: 'summary_ledger/voucher_list');
    }

    /**
     * Hapus 1 journal Draft hasil import.
     * Boleh diakses jika user punya delete permission di summary_ledger atau voucher_disbursement_list.
     * Redirect ke HTTP_REFERER kalau ada (supaya balik ke voucher_list / detail import sesuai asal klik).
     */
    public function delete_journal($journal_id)
    {
        if (!check_access('summary_ledger', 'delete')
            && !check_access('voucher_disbursement_list', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus voucher.', 403);
        }

        $import_id = (int) $this->input->get('import_id');
        $referer   = $this->input->server('HTTP_REFERER');
        $back = $referer ?: ($import_id > 0
            ? 'summary_ledger/detail/' . $import_id
            : 'summary_ledger');

        $ok = $this->model->delete_draft_journal($journal_id);
        if ($ok) {
            $this->session->set_flashdata('success', 'Voucher Draft berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Voucher tidak bisa dihapus (sudah Posted atau bukan dari Summary Ledger).');
        }
        redirect($back);
    }

    /**
     * Generate PDF Voucher Disbursement untuk satu journal hasil import.
     */
    public function voucher_pdf($journal_id)
    {
        $bundle = $this->model->get_journal_for_pdf($journal_id);
        if (!$bundle) show_404();

        $data = [
            'header'  => $bundle['header'],
            'details' => $bundle['details'],
        ];

        $html = $this->load->view('accounting/summary_ledger/pdf_disbursement', $data, true);

        $tmpDir = APPPATH . 'cache/dompdf/tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);

        $options = new DompdfOptions();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->setTempDir($tmpDir);
        $options->setFontCache(APPPATH . 'cache/dompdf');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Inline preview — Attachment:0 bikin browser render PDF, bukan auto-download
        $filename = 'Disbursement_' . str_replace('/', '-', $bundle['header']->journal_no) . '_' . date('YmdHis') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0, 'compress' => 1]);
    }

    /**
     * Generate Excel Voucher Disbursement (plek-ketiplek template Excel).
     * Load template sheet 'Disbursement', fill in values, stream sebagai .xlsx.
     */
    public function voucher_excel($journal_id)
    {
        $bundle = $this->model->get_journal_for_pdf($journal_id);
        if (!$bundle) show_404();

        $header  = $bundle['header'];
        $details = $bundle['details'];

        $template = APPPATH . 'templates/disbursement_template.xlsx';
        if (!file_exists($template)) {
            show_error('Template disbursement_template.xlsx tidak ditemukan di application/templates/.', 500);
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setLoadSheetsOnly(['Disbursement']);
        $spreadsheet = $reader->load($template);
        $sheet = $spreadsheet->getSheetByName('Disbursement');
        if (!$sheet) show_error('Sheet "Disbursement" tidak ada di template.', 500);
        $spreadsheet->setActiveSheetIndexByName('Disbursement');

        // Template berisi gambar contoh voucher kosong (EMF) yang ter-anchor di H48,
        // sehingga di output muncul "voucher kedua". Buang semua drawing kecuali logo (B3).
        $drawings = $sheet->getDrawingCollection();
        for ($i = count($drawings) - 1; $i >= 0; $i--) {
            if (!isset($drawings[$i])) continue;
            if (strtoupper($drawings[$i]->getCoordinates()) !== 'B3') {
                $drawings->offsetUnset($i);
            }
        }

        // ============ HEADER INFO (kanan atas) ============
        // Disamakan dengan contoh: label "Voucher No" (kosong, diisi manual),
        // "Document Date" & "Due Date" (format dd/mm/yy), nilai dari field header.
        $sheet->setCellValue('M3', 'Voucher No');
        $sheet->setCellValue('M4', 'Document Date');
        $doc_date = !empty($header->document_date) ? date('d/m/y', strtotime($header->document_date))
                  : (!empty($header->journal_date) ? date('d/m/y', strtotime($header->journal_date)) : '');
        $due_date = !empty($header->due_date) ? date('d/m/y', strtotime($header->due_date)) : '';
        $sheet->setCellValue('O4', $doc_date);
        $sheet->setCellValue('O5', $due_date);

        // ============ INFO BLOCK ============
        // J10 Vendor Name, J13 Bank Name, J14 Account Bank diisi otomatis dari vendor
        // (snapshot di header). J11 Description dari header.description.
        // Account Bank ditulis "no. rekening / atas nama" — sama dengan versi PDF.
        $v_acc   = trim((string) ($header->vendor_bank_account ?? ''));
        $v_owner = trim((string) ($header->vendor_name ?? ''));
        $v_acc_line = ($v_acc !== '' && $v_owner !== '') ? $v_acc . ' / ' . $v_owner : $v_acc;

        $sheet->setCellValue('J10', $header->vendor_name ?? '');
        $sheet->setCellValue('J11', $header->description);
        $sheet->setCellValue('J13', $header->vendor_bank_name ?? '');
        $sheet->setCellValueExplicit('J14', $v_acc_line, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        // ============ TABLE BODY ============
        // Data rows start at 19, template menyiapkan ~9 baris (sampai row 27).
        // Kalau detail > 9 baris, kita insert baris extra sebelum row 28.
        $data_start_row = 19;
        $template_data_rows = 9; // baris 19..27 di template
        $detail_count = count($details);
        $extra_rows = max(0, $detail_count - $template_data_rows);

        if ($extra_rows > 0) {
            // Insert extra rows BEFORE row 28 (yang berisi 'Total')
            $sheet->insertNewRowBefore(28, $extra_rows);
        }

        // Estimasi kapasitas karakter per baris untuk kolom GL Description (gabungan F:I)
        // dipakai untuk menghitung tinggi baris saat wrap (merged cell tidak auto-fit di Excel).
        $desc_chars_per_line = 0;
        foreach (['F', 'G', 'H', 'I'] as $c) {
            $desc_chars_per_line += $sheet->getColumnDimension($c)->getWidth();
        }
        $desc_chars_per_line = max(20, (int) floor($desc_chars_per_line) - 2); // dikurangi sedikit untuk padding

        $no = 1;
        $row = $data_start_row;
        foreach ($details as $d) {
            $sheet->setCellValue('C' . $row, $no++);
            $sheet->setCellValue('D' . $row, (string) ($d->account_code ?? ''));
            $desc = trim((string) ($d->account_name ?? ''));
            if (!empty($d->description)) {
                $desc .= ($desc !== '' ? ' — ' : '') . $d->description;
            }
            $sheet->setCellValue('F' . $row, $desc);

            // Gabungkan kolom GL Description (F:I) & aktifkan wrap text supaya teks panjang
            // turun ke bawah, bukan menabrak kolom Debit.
            $sheet->mergeCells('F' . $row . ':I' . $row);
            $sheet->getStyle('F' . $row)->getAlignment()
                ->setWrapText(true)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            // Tinggi baris mengikuti jumlah baris hasil wrap (estimasi).
            $lines = max(1, (int) ceil(mb_strlen($desc) / $desc_chars_per_line));
            if ($lines > 1) {
                $sheet->getRowDimension($row)->setRowHeight($lines * 15);
            }

            $sheet->setCellValue('J' . $row, (float) $d->debit_local  > 0 ? (float) $d->debit_local  : null);
            $sheet->setCellValue('M' . $row, (float) $d->kredit_local > 0 ? (float) $d->kredit_local : null);
            $row++;
        }

        // ============ FOOTER (Total / Total Paid) ============
        // Row index untuk Total bergeser kalau ada extra rows
        $total_row      = 28 + $extra_rows;
        $total_paid_row = 31 + $extra_rows;

        $total_debit  = 0;
        $total_credit = 0;
        foreach ($details as $d) {
            $total_debit  += (float) $d->debit_local;
            $total_credit += (float) $d->kredit_local;
        }
        // Nilai pajak & rate (untuk recap VAT/WHT + Total Paid).
        $ppn_amount   = !empty($header->ppn_enabled) ? (float) ($header->ppn_amount ?? 0) : 0;
        $ppn_percent  = (float) ($header->ppn_percent ?? 0);
        $wht23_amount = (float) ($header->wht23_amount ?? 0);
        $wht23_rate   = (float) ($header->wht23_rate ?? 0);
        $wht42_amount = (float) ($header->wht42_amount ?? 0);
        $wht42_rate   = (float) ($header->wht42_rate ?? 0);
        $total_wht    = $wht23_amount + $wht42_amount;
        $rate_fmt  = fn($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $vat_pct   = $ppn_percent > 0 ? $rate_fmt($ppn_percent) : '11';
        $wht23_pct = $rate_fmt($wht23_rate);
        $wht42_pct = $rate_fmt($wht42_rate);
        $vat_row   = 29 + $extra_rows;
        $wht_row   = 30 + $extra_rows; // baris WHT 23
        $right     = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;

        // Kalau WHT 4(2) ada, sisipkan 1 baris setelah WHT 23 (sebelum Total Paid).
        $wht_shift = ($wht42_amount > 0) ? 1 : 0;
        if ($wht_shift > 0) {
            $sheet->insertNewRowBefore($total_paid_row, 1);
            $total_paid_row += 1;
        }

        $sheet->setCellValue('J' . $total_row, $total_debit);
        $sheet->setCellValue('M' . $total_row, $total_credit);
        // Kolom "Checked" baris Total = TRUE bila debit == kredit (seperti contoh).
        $sheet->setCellValue('P' . $total_row, (round($total_debit, 2) == round($total_credit, 2)) ? 'TRUE' : 'FALSE');

        // Total Paid = total kredit dikurangi total WHT (dipotong dari pembayaran ke vendor).
        $sheet->setCellValue('J' . $total_paid_row, $total_credit - $total_wht);

        // Label VAT/WHT pakai rate (mis. "VAT 11%", "WHT 23 (2%)").
        $sheet->setCellValue('C' . $vat_row, 'VAT ' . $vat_pct . '%');
        $sheet->setCellValue('C' . $wht_row, 'WHT 23' . ($wht23_rate > 0 ? ' (' . $wht23_pct . '%)' : ''));

        // Nilai VAT di sisi KANAN (kredit). Baris ini tidak di-merge di template,
        // jadi gabung J:P + rata kanan supaya sejajar dengan WHT/Total Paid.
        if ($ppn_amount > 0) {
            $sheet->mergeCells('J' . $vat_row . ':P' . $vat_row);
            $sheet->getStyle('J' . $vat_row)->getAlignment()->setHorizontal($right);
            $sheet->getStyle('J' . $vat_row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('J' . $vat_row, $ppn_amount);
        }
        // Nilai WHT 23 (cell J:P sudah di-merge di template pada row 30).
        if ($wht23_amount > 0) {
            $sheet->getStyle('J' . $wht_row)->getAlignment()->setHorizontal($right);
            $sheet->getStyle('J' . $wht_row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('J' . $wht_row, $wht23_amount);
        }
        // Nilai WHT 4(2) di baris yang baru disisipkan (wht_row + 1).
        if ($wht42_amount > 0) {
            $wht42_row = $wht_row + 1;
            $sheet->setCellValue('C' . $wht42_row, 'WHT 4(2)' . ($wht42_rate > 0 ? ' (' . $wht42_pct . '%)' : ''));
            $sheet->mergeCells('J' . $wht42_row . ':P' . $wht42_row);
            $sheet->getStyle('J' . $wht42_row)->getAlignment()->setHorizontal($right);
            $sheet->getStyle('J' . $wht42_row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('J' . $wht42_row, $wht42_amount);
        }

        // Poin 1: kosongkan teks "Finance Department" di blok tanda tangan.
        $sig_name_row = 40 + $extra_rows + $wht_shift;
        foreach (['D', 'G', 'J', 'M'] as $sig_col) {
            $sheet->setCellValue($sig_col . $sig_name_row, '');
        }

        // ============ REF NO JOURNAL (di bawah border tanda tangan) ============
        // Blok tanda tangan ada di row 34..40, border voucher berakhir di row 41.
        // Tulis "Ref No Journal" 2 baris di bawah border lalu perluas print area.
        $ref_row = 43 + $extra_rows + $wht_shift;
        $sheet->setCellValue('B' . $ref_row, 'Ref No Journal : ' . $header->journal_no);
        $sheet->getStyle('B' . $ref_row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $ref_row)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getPageSetup()->setPrintArea('A1:R' . $ref_row);

        // ============ OUTPUT ============
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Disbursement_' . str_replace('/', '-', $header->journal_no) . '_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
