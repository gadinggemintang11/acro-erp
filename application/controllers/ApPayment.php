<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer untuk ekspor
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ApPayment extends CI_Controller {

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
        $this->form_validation->set_rules('payment_account_id', 'Akun Pembayaran', 'required');
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

        $data['header'] = $header;
        $data['details'] = $this->model->get_details_by_header_id($id);
        $data['title'] = 'Bukti Pembayaran: ' . $header->payment_no;

        // Render view ke dalam HTML
        $html = $this->load->view('accounting/ap_payment/pdf_template', $data, true);

        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output PDF ke browser
        $filename = "AP-Payment-" . str_replace('/', '-', $header->payment_no) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
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
}
