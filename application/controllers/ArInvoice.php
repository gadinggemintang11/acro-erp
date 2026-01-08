<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Impor library ekspor
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ArInvoice extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('ArInvoice_model', 'model');
        $this->load->model('JurnalUmum_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    /**
     * Halaman Utama: Daftar Faktur Customer
     */
    public function index()
    {
        $data['title'] = 'Daftar Faktur Customer (AR)';
        $data['invoices'] = $this->model->get_all_invoice_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_invoice/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman Buat Faktur Baru
     */
    public function create()
    {
        $data['title'] = lang_line('ar_invoice_create', 'Buat Faktur Customer Baru');
        $data['customers'] = $this->model->get_customers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_invoice/create', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman View Faktur (Beserta Opsi Ekspor)
     */
    public function view($id)
    {
        $data['header'] = $this->model->get_invoice_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Faktur Customer: ' . $data['header']->invoice_no;
        $data['details'] = $this->model->get_invoice_details_by_header_id($id);
        
        if($data['header']->journal_header_id) {
            $data['journal_header'] = $this->JurnalUmum_model->get_header_by_id($data['header']->journal_header_id);
            $data['journal_details'] = $this->JurnalUmum_model->get_details_by_header_id($data['header']->journal_header_id);
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ar_invoice/view', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * Ekspor Halaman View Faktur ke PDF
     */
    public function export_pdf_detail($id)
    {
        $data['header'] = $this->model->get_invoice_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'INVOICE: ' . $data['header']->invoice_no;
        $data['details'] = $this->model->get_invoice_details_by_header_id($id);

        $html = $this->load->view('accounting/ar_invoice/pdf_detail_view', $data, true);
        $this->generate_pdf($html, 'Invoice_' . $data['header']->invoice_no . '.pdf');
    }

    /**
     * Proses Simpan Faktur (Draft)
     */
    public function store()
    {
        $this->form_validation->set_rules('customer_id', 'Customer', 'required');
        $this->form_validation->set_rules('invoice_date', 'Tgl. Faktur', 'required');
        $this->form_validation->set_rules('source_type', 'Sumber Tagihan', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('arinvoice/create');
        }

        $details = $this->input->post('details');
        if (empty($details)) {
            $this->session->set_flashdata('error', 'Detail tagihan tidak boleh kosong.');
            redirect('arinvoice/create');
        }

        try {
            $header_id = $this->model->insert_invoice($this->input->post());
            if ($header_id) {
                $this->session->set_flashdata('success', 'Faktur Customer berhasil disimpan sebagai Draft.');
                redirect('arinvoice/view/' . $header_id);
            } else {
                throw new Exception('Gagal menyimpan faktur ke database.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            redirect('arinvoice/create');
        }
    }
    
    /**
     * Hapus Faktur (Hanya Draft)
     */
    public function delete($id)
    {
        $result = $this->model->delete_draft_invoice($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Faktur Draft berhasil dihapus.');
            redirect('arinvoice');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('arinvoice/view/' . $id);
        }
    }
    
    /**
     * Proses Konfirmasi & Posting Jurnal Otomatis
     */
    public function post($id)
    {
        $user_id = $this->session->userdata('user_id');
        try {
            $this->load->model('SettingAkun_model');
            $accounts = [
                'piutang_usaha'   => $this->SettingAkun_model->get_account_id('akun_piutang_usaha'),
                'penjualan_fg'    => $this->SettingAkun_model->get_account_id('akun_penjualan_fg'),
                'penjualan_scrap' => $this->SettingAkun_model->get_account_id('akun_penjualan_scrap'),
                'ppn_keluaran'    => $this->SettingAkun_model->get_account_id('akun_ppn_keluaran'),
                'hpp_fg'          => $this->SettingAkun_model->get_account_id('akun_hpp_fg'),
                'persediaan_fg'   => $this->SettingAkun_model->get_account_id('akun_persediaan_fg')
            ];
            
            if(!$accounts['piutang_usaha'] || !$accounts['penjualan_fg'] || !$accounts['penjualan_scrap']) {
                throw new Exception("Akun Piutang/Penjualan (FG/Scrap) belum di-setting di modul Setting Akun.");
            }
            
            $result = $this->model->post_invoice_journal($id, $user_id, $accounts);
            if ($result['status'] == TRUE) {
                $this->session->set_flashdata('success', 'Faktur berhasil di-posting. Jurnal ' . $result['journal_no'] . ' telah dibuat.');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal Posting: ' . $e->getMessage());
        }
        redirect('arinvoice/view/' . $id);
    }

    /**
     * ======================================
     * AJAX HELPERS (Untuk form create)
     * ======================================
     */
    public function ajax_get_pending_docs()
    {
        $customer_id = $this->input->get('customer_id');
        $source_type = $this->input->get('source_type');
        
        if ($source_type == 'FG_DELIVERY') {
            $docs = $this->model->get_pending_fg_delivery($customer_id);
        } else if ($source_type == 'SCRAP_OUT') {
            $docs = $this->model->get_pending_scrap_out($customer_id);
        } else {
            $docs = [];
        }
        echo json_encode(['success' => true, 'data' => $docs]);
    }
    
    public function ajax_get_doc_details()
    {
        $source_ids = $this->input->post('source_ids');
        $source_type = $this->input->post('source_type');
        if (empty($source_ids)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada dokumen dipilih']);
            return;
        }
        
        if ($source_type == 'FG_DELIVERY') {
            $details = $this->model->get_details_from_fg_delivery($source_ids);
        } else if ($source_type == 'SCRAP_OUT') {
            $details = $this->model->get_details_from_scrap_out($source_ids);
        } else {
            $details = [];
        }
        echo json_encode(['success' => true, 'data' => $details]);
    }

    /**
     * ======================================
     * FUNGSI EKSPOR LIST (INDEX)
     * ======================================
     */

    public function export_excel()
    {
        $invoices = $this->model->get_all_invoice_headers();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Daftar Faktur Customer (AR)');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $headers = ['No. Faktur', 'Tgl. Faktur', 'Customer', 'Tipe', 'Total Tagihan', 'Total Terbayar', 'Sisa Utang', 'Status Bayar', 'Status Jurnal'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:I3')->getFont()->setBold(true);

        $rowNum = 4;
        foreach ($invoices as $inv) {
            $tipe = ($inv->source_type == 'SCRAP_OUT') ? 'Scrap Out' : (($inv->customer_type == 'Lokal') ? 'FG (Lokal)' : 'FG (Ekspor)');
            
            $sheet->setCellValue('A' . $rowNum, $inv->invoice_no);
            $sheet->setCellValue('B' . $rowNum, $inv->invoice_date);
            $sheet->setCellValue('C' . $rowNum, $inv->nama_customer);
            $sheet->setCellValue('D' . $rowNum, $tipe);
            $sheet->setCellValue('E' . $rowNum, $inv->total_invoice);
            $sheet->setCellValue('F' . $rowNum, $inv->total_paid);
            $sheet->setCellValue('G' . $rowNum, $inv->total_invoice - $inv->total_paid);
            $sheet->setCellValue('H' . $rowNum, $inv->payment_status);
            $sheet->setCellValue('I' . $rowNum, $inv->status);
            $sheet->getStyle('E' . $rowNum . ':G' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }
        
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar_AR_Invoice_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    public function export_pdf()
    {
        $data['title'] = 'Daftar Faktur Customer (AR)';
        $data['invoices'] = $this->model->get_all_invoice_headers();
        
        $html = $this->load->view('accounting/ar_invoice/pdf_index_view', $data, true);
        $this->generate_pdf($html, 'Daftar_AR_Invoice.pdf', 'A4', 'landscape');
    }
    
    // Helper Generate PDF
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
