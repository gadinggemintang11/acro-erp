<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer untuk ekspor
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ApPayment extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('ApPayment_model', 'model');
        $this->load->model('ApInvoice_model'); // Untuk ambil data supplier
        $this->load->model('JurnalUmum_model'); // Untuk posting jurnal
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    // Halaman Utama: Daftar Pembayaran
    public function index()
    {
        $data['title'] = 'Pembayaran Utang (AP Payment)';
        $data['payments'] = $this->model->get_all_payment_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_payment/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman Buat Pembayaran Baru
    public function create()
    {
        $data['title'] = 'Buat Pembayaran Utang Baru';
        $data['payment_no'] = $this->model->get_new_payment_number();
        $data['suppliers'] = $this->ApInvoice_model->get_suppliers(); // Ambil dari model AP Invoice
        $data['payment_accounts'] = $this->model->get_payment_accounts(); // Ambil Akun Kas/Bank
        // ✨ NEW: Ambil list akun untuk pilihan debet dan kredit
        $data['debet_accounts'] = $this->model->get_debet_accounts();
        $data['kredit_accounts'] = $this->model->get_kredit_accounts();
        // ✨ NEW: Ambil list akun Hutang PPh untuk pilihan potong pajak
        $data['pph_payable_accounts'] = $this->model->get_pph_payable_accounts();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_payment/create', $data);
        $this->load->view('templates/footer');
    }
    
    // Halaman View Pembayaran
    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Pembayaran: ' . $data['header']->payment_no;
        $data['details'] = $this->model->get_details_by_header_id($id);
        $data['can_edit'] = $this->_can_edit();
        $data['redraft_block'] = ($data['header']->status === 'Posted') ? $this->model->redraft_block_reason($data['header']) : null;
        $data['account_names'] = $this->_account_names([$data['header']->debet_account_id, $data['header']->kredit_account_id, $data['header']->pph_account_id]);
        
        // Load data jurnal terkait
        if ($data['header']->journal_header_id) {
            $data['journal_header'] = $this->JurnalUmum_model->get_header_by_id($data['header']->journal_header_id);
            $data['journal_details'] = $this->JurnalUmum_model->get_details_by_header_id($data['header']->journal_header_id);
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_payment/view', $data);
        $this->load->view('templates/footer');
    }

    // Proses Simpan Pembayaran (Draft)
    public function store()
    {
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'required');
        $this->form_validation->set_rules('payment_date', 'Tgl. Bayar', 'required');
        // ✨ NEW: Validasi akun debet dan kredit yang dipilih user
        $this->form_validation->set_rules('debet_account_id', 'Akun DEBET (Hutang)', 'required');
        $this->form_validation->set_rules('kredit_account_id', 'Akun KREDIT (Kas/Bank)', 'required');
        // ✨ NEW: Validasi PPh (tarif dan akun) - opsional jika tidak ada potong pajak
        if (!empty($this->input->post('pph_rate')) && $this->input->post('pph_rate') > 0) {
            $this->form_validation->set_rules('pph_account_id', 'Akun Hutang PPh', 'required');
        }
        // $this->form_validation->set_rules('details', 'Detail Faktur', 'required'); // Validasi manual di bawah

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', 'Validasi gagal. Pastikan semua field terisi.');
            $this->create(); // Kembali ke form
        } else {
            // Validasi manual untuk detail
            if (empty($this->input->post('details'))) {
                $this->session->set_flashdata('error', 'Gagal: Anda harus memilih setidaknya satu faktur untuk dibayar.');
                $this->create();
                return;
            }

            $post_data = $this->input->post();
            try {
                $header_id = $this->model->insert_payment($post_data);
                if ($header_id) {
                    $this->session->set_flashdata('success', 'Pembayaran berhasil disimpan sebagai Draft.');
                    redirect('appayment/view/' . $header_id);
                } else {
                    throw new Exception('Gagal menyimpan data.');
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect('appayment/create');
            }
        }
    }
    
    // Hapus (Hanya Draft)
    public function delete($id)
    {
        $result = $this->model->delete_draft_payment($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Pembayaran Draft berhasil dihapus.');
            redirect('appayment');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('appayment/view/' . $id);
        }
    }

    /** Redraft & edit butuh izin Edit di menu Payable Payment. */
    private function _can_edit()
    {
        return check_access('appayment', 'edit') || is_admin();
    }

    /** id akun => "kode - nama" (untuk tampilan). */
    private function _account_names(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) return [];
        $out = [];
        foreach ($this->db->select('id, account_code, account_name')->where_in('id', $ids)->get('chart_of_accounts')->result() as $a) {
            $out[(int) $a->id] = $a->account_code . ' - ' . $a->account_name;
        }
        return $out;
    }

    /**
     * Redraft: pembayaran Posted dikembalikan ke Draft; sisa hutang faktur
     * dikembalikan dan jurnalnya ikut Draft sampai diposting ulang.
     */
    public function redraft($id)
    {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin me-redraft Pembayaran Hutang.', 403); return; }

        $reason = trim((string) $this->input->post('reason'));
        if (mb_strlen($reason) < 5) {
            $this->session->set_flashdata('error', 'Alasan redraft wajib diisi (minimal 5 karakter).');
            redirect('appayment/view/' . $id);
            return;
        }
        try {
            $p = $this->model->redraft_payment($id);
            $jno = $this->JurnalUmum_model->get_header_by_id($p->journal_header_id)->journal_no ?? '-';
            add_log('REDRAFT', 'AP Payment', 'Redraft pembayaran ' . $p->payment_no . ' (jurnal ' . $jno . ' kembali Draft, sisa hutang faktur dikembalikan). Alasan: ' . $reason);
            $this->session->set_flashdata('success', 'Pembayaran ' . $p->payment_no . ' dikembalikan ke Draft. Sisa hutang faktur sudah dikembalikan dan jurnal '
                . $jno . ' ikut menjadi Draft sampai pembayaran diposting ulang.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal redraft: ' . $e->getMessage());
        }
        redirect('appayment/view/' . $id);
    }

    /** Form edit pembayaran Draft (termasuk hasil redraft). */
    public function edit($id)
    {
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin mengedit Pembayaran Hutang.', 403); return; }
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        if ($header->status !== 'Draft' || !empty($header->prf_header_id)) {
            $this->session->set_flashdata('error', 'Hanya pembayaran Draft (bukan dari Payment Request) yang bisa diedit. Untuk pembayaran Posted, klik Redraft dulu.');
            redirect('appayment/view/' . $id);
            return;
        }
        $data = [
            'title'                => 'Edit Pembayaran: ' . $header->payment_no,
            'header'               => $header,
            'invoices'             => $this->model->get_invoices_for_edit($header),
            'debet_accounts'       => $this->model->get_debet_accounts(),
            'kredit_accounts'      => $this->model->get_kredit_accounts(),
            'pph_payable_accounts' => $this->model->get_pph_payable_accounts(),
            'journal_no'           => $header->journal_header_id ? ($this->JurnalUmum_model->get_header_by_id($header->journal_header_id)->journal_no ?? null) : null,
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_payment/edit', $data);
        $this->load->view('templates/footer');
    }

    /** Simpan hasil edit pembayaran Draft. */
    public function update($id)
    {
        if ($this->input->method() !== 'post') { show_error('Metode ilegal.', 405); return; }
        if (!$this->_can_edit()) { show_error('Anda tidak memiliki izin mengedit Pembayaran Hutang.', 403); return; }
        try {
            $this->model->update_payment($id, $this->input->post());
            $p = $this->model->get_header_by_id($id);
            add_log('UPDATE', 'AP Payment', 'Edit pembayaran Draft ' . $p->payment_no . ' (total ' . number_format((float) $p->total_amount, 2) . ').');
            $this->session->set_flashdata('success', 'Perubahan pembayaran disimpan (masih Draft). Klik Posting bila sudah benar.');
            redirect('appayment/view/' . $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal menyimpan: ' . $e->getMessage());
            redirect('appayment/edit/' . $id);
        }
    }

    // Konfirmasi & Posting Jurnal
    public function post($id)
    {
        $user_id = $this->session->userdata('user_id');
        try {
            $result = $this->model->post_payment($id, $user_id);
            if ($result['status'] == TRUE) {
                $this->session->set_flashdata('success', 'Pembayaran berhasil di-posting. Jurnal ' . $result['journal_no'] . ' dibuat.');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal Posting: ' . $e->getMessage());
        }
        redirect('appayment/view/' . $id);
    }
    
    /**
     * ======================================
     * FUNGSI EKSPOR DARI HALAMAN DETAIL
     * ======================================
     */

    // Ekspor Detail Pembayaran ke PDF
    public function export_detail_pdf($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();

        // Form sekarang menyimpan akun Kas/Bank di kredit_account_id (payment_account_id = 0).
        if (empty($header->payment_account_name) && !empty($header->kredit_account_id)) {
            $header->payment_account_name = $this->db->select('account_name')->where('id', (int) $header->kredit_account_id)
                                                     ->get('chart_of_accounts')->row('account_name');
        }

        $data['header'] = $header;
        $data['details'] = $this->model->get_details_by_header_id($id);
        $data['title'] = 'Bukti Pembayaran Utang';

        // Desain sama dengan Bukti Kas/Bank: A4 portrait dibagi 2 (lihat pdf_template.php)
        $html = $this->load->view('accounting/ap_payment/pdf_template', $data, true);

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

        // Output PDF ke browser
        $filename = "AP-Payment-" . str_replace('/', '-', $header->payment_no) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0, 'compress' => 1]);
    }

    // Ekspor Detail Pembayaran ke Excel
    public function export_detail_excel($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->model->get_details_by_header_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payment ' . $header->payment_no);

        // --- Header Dokumen ---
        $sheet->setCellValue('A1', 'BUKTI PEMBAYARAN UTANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A3', 'No. Pembayaran:')->setCellValue('B3', $header->payment_no);
        $sheet->setCellValue('A4', 'Supplier:')->setCellValue('B4', $header->nama_supplier);
        $sheet->setCellValue('A5', 'Tgl. Bayar:')->setCellValue('B5', date('d M Y', strtotime($header->payment_date)));
        $sheet->setCellValue('D3', 'Total Bayar:')->setCellValue('E3', $header->total_amount);
        $sheet->getStyle('E3')->getNumberFormat()->setFormatCode('#,##0.00');

        // --- Header Tabel Detail ---
        $sheet->setCellValue('A7', 'No. Faktur');
        $sheet->setCellValue('B7', 'Tgl. Faktur');
        $sheet->setCellValue('C7', 'Total Faktur');
        $sheet->setCellValue('D7', 'Jumlah Dibayar');
        $sheet->getStyle('A7:D7')->getFont()->setBold(true);

        // --- Isi Tabel Detail ---
        $rowNum = 8;
        foreach ($details as $d) {
            $sheet->setCellValue('A' . $rowNum, $d->invoice_no);
            $sheet->setCellValue('B' . $rowNum, date('d M Y', strtotime($d->invoice_date)));
            $sheet->setCellValue('C' . $rowNum, (float)$d->total_invoice);
            $sheet->setCellValue('D' . $rowNum, (float)$d->amount_paid);
            $rowNum++;
        }

        // --- Formatting ---
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('C8:D' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');

        // --- Output File ---
        $writer = new Xlsx($spreadsheet);
        $filename = "AP-Payment-" . str_replace('/', '-', $header->payment_no) . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
    }

    /**
     * ======================================
     * AJAX HELPERS (Untuk form create)
     * ======================================
     */

    // AJAX: Ambil Faktur yang belum lunas
    public function ajax_get_unpaid_invoices()
    {
        $supplier_id = $this->input->get('supplier_id');
        $invoices = $this->model->get_unpaid_invoices($supplier_id);
        echo json_encode(['success' => true, 'data' => $invoices]);
    }

    /**
     * ✨ NEW: AJAX: Ambil daftar akun persediaan (untuk pilihan debet)
     */
    public function ajax_get_debet_accounts()
    {
        $accounts = $this->model->get_debet_accounts();
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $accounts]));
    }

    /**
     * ✨ NEW: AJAX: Ambil daftar akun utang (untuk pilihan kredit)
     */
    public function ajax_get_kredit_accounts()
    {
        $accounts = $this->model->get_kredit_accounts();
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $accounts]));
    }

    /**
     * ✨ NEW: AJAX: Ambil detail invoice untuk ditampilkan di pembayaran
     */
    public function ajax_get_invoice_details()
    {
        $invoice_id = $this->input->get('invoice_id');
        $details = $this->model->get_invoice_details_for_payment($invoice_id);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $details]));
    }
}
