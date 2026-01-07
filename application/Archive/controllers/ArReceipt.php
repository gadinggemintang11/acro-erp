<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Impor library ekspor
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ArReceipt extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('ArReceipt_model', 'model');
        $this->load->model('ArInvoice_model'); 
        $this->load->model('JurnalUmum_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    // Halaman Utama: Daftar Penerimaan
    public function index()
    {
        $data['title'] = 'Penerimaan Piutang (AR Receipt)';
        $data['receipts'] = $this->model->get_all_receipt_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_receipt/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman Buat Penerimaan Baru
    public function create()
    {
        $data['title'] = 'Buat Penerimaan Piutang Baru';
        $data['receipt_no'] = $this->model->get_new_receipt_number();
        $data['customers'] = $this->ArInvoice_model->get_customers();
        $data['currencies'] = $this->JurnalUmum_model->get_currencies();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_receipt/create', $data);
        $this->load->view('templates/footer');
    }
    
    // Halaman View Penerimaan
    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Penerimaan: ' . $data['header']->receipt_no;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        if($data['header']->journal_header_id) {
            $data['journal_header'] = $this->JurnalUmum_model->get_header_by_id($data['header']->journal_header_id);
            $data['journal_details'] = $this->JurnalUmum_model->get_details_by_header_id($data['header']->journal_header_id);
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_receipt/view', $data);
        $this->load->view('templates/footer');
    }

    // Proses Simpan (Draft)
    public function store()
    {
        $this->form_validation->set_rules('customer_id', 'Customer', 'required');
        $this->form_validation->set_rules('receipt_date', 'Tgl. Terima', 'required');
        $this->form_validation->set_rules('payment_account_id', 'Akun Terima', 'required');
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric');
        
        $details = $this->input->post('details');
        if (empty($details)) {
             // Buat aturan dummy agar validasi gagal dan menampilkan error
            $this->form_validation->set_rules('details_dummy', 'Detail Faktur', 'required', ['required' => 'Detail Faktur yang dibayar tidak boleh kosong.']);
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            $this->create(); // Kembali ke form
        } else {
            $post_data = $this->input->post();
            try {
                $header_id = $this->model->insert_receipt($post_data);
                if ($header_id) {
                    $this->session->set_flashdata('success', 'Penerimaan berhasil disimpan sebagai Draft.');
                    redirect('arreceipt/view/' . $header_id);
                } else {
                    throw new Exception('Gagal menyimpan data.');
                }
            } catch (Exception $e) {
                $this->session->set_flashdata('error', $e->getMessage());
                redirect('arreceipt/create');
            }
        }
    }
    
    // Hapus (Hanya Draft)
    public function delete($id)
    {
        $result = $this->model->delete_draft_receipt($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Penerimaan Draft berhasil dihapus.');
            redirect('arreceipt');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('arreceipt/view/' . $id);
        }
    }

    // Konfirmasi & Posting Jurnal
    public function post($id)
    {
        $user_id = $this->session->userdata('user_id');
        try {
            $this->load->model('SettingAkun_model');
            $result = $this->model->post_receipt($id, $user_id, $this->SettingAkun_model);
            if ($result['status'] == TRUE) {
                $this->session->set_flashdata('success', 'Penerimaan berhasil di-posting. Jurnal ' . $result['journal_no'] . ' dibuat.');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal Posting: ' . $e->getMessage());
        }
        redirect('arreceipt/view/' . $id);
    }
    
    /**
     * ======================================
     * AJAX HELPERS (Untuk form create)
     * ======================================
     */

    public function ajax_get_payment_accounts()
    {
        $currency = $this->input->get('currency');
        $accounts = $this->model->get_payment_accounts_by_currency($currency);
        echo json_encode(['success' => true, 'data' => $accounts]);
    }
    
    public function ajax_get_unpaid_invoices()
    {
        $customer_id = $this->input->get('customer_id');
        $currency = $this->input->get('currency');
        $invoices = $this->model->get_unpaid_invoices_by_currency($customer_id, $currency);
        echo json_encode(['success' => true, 'data' => $invoices]);
    }
    
    /**
     * ======================================
     * FUNGSI EKSPOR
     * ======================================
     */

    public function export_excel()
    {
        $receipts = $this->model->get_all_receipt_headers();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Daftar Penerimaan Piutang (AR)');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['No. Penerimaan', 'Tgl. Terima', 'Customer', 'Akun Terima', 'Total', 'Currency', 'Status Jurnal', 'No. Jurnal'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:H3')->getFont()->setBold(true);

        $rowNum = 4;
        foreach ($receipts as $r) {
            $sheet->setCellValue('A' . $rowNum, $r->receipt_no);
            $sheet->setCellValue('B' . $rowNum, $r->receipt_date);
            $sheet->setCellValue('C' . $rowNum, $r->nama_customer);
            $sheet->setCellValue('D' . $rowNum, $r->payment_account_name);
            $sheet->setCellValue('E' . $rowNum, $r->total_amount);
            $sheet->setCellValue('F' . $rowNum, $r->currency);
            $sheet->setCellValue('G' . $rowNum, $r->status);
            $sheet->setCellValue('H' . $rowNum, $r->journal_header_id ? 'JRN-' . $r->journal_header_id : 'N/A'); // Ganti sesuai format No. Jurnal
            
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }
        
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar_AR_Receipt_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    public function export_pdf()
    {
        $data['title'] = 'Daftar Penerimaan Piutang (AR)';
        $data['receipts'] = $this->model->get_all_receipt_headers();
        
        $html = $this->load->view('accounting/ar_receipt/pdf_index_view', $data, true);
        $this->generate_pdf($html, 'Daftar_AR_Receipt.pdf', 'A4', 'landscape');
    }

    public function export_pdf_detail($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'Bukti Penerimaan Piutang';
        $data['details'] = $this->model->get_details_by_header_id($id);

        $html = $this->load->view('accounting/ar_receipt/pdf_detail_view', $data, true);
        $this->generate_pdf($html, 'AR_Receipt_' . $data['header']->receipt_no . '.pdf');
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