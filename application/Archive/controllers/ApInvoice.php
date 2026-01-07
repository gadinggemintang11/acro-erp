<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer untuk ekspor
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class ApInvoice extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('ApInvoice_model', 'model');
        $this->load->model('JurnalUmum_model'); 
        $this->load->model('Coa_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    /**
     * Halaman Utama: Daftar Faktur Supplier
     */
    public function index()
    {
        $data['title'] = 'Daftar Faktur Supplier (AP)';
        $data['invoices'] = $this->model->get_all_invoice_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_invoice/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman Buat Faktur Baru
     */
    public function create()
    {
        $data['title'] = 'Buat Faktur Supplier Baru';
        $data['suppliers'] = $this->model->get_suppliers();
        $data['accounts'] = $this->model->get_postable_accounts_for_expense();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_invoice/create', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman View Faktur
     */
    public function view($id)
    {
        $data['header'] = $this->model->get_invoice_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Faktur Supplier: ' . $data['header']->invoice_no;
        $data['details'] = $this->model->get_invoice_details_by_header_id($id);
        
        if($data['header']->journal_header_id) {
            $data['journal_header'] = $this->JurnalUmum_model->get_header_by_id($data['header']->journal_header_id);
            $data['journal_details'] = $this->JurnalUmum_model->get_details_by_header_id($data['header']->journal_header_id);
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/ap_invoice/view', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Proses Simpan Faktur (Draft)
     */
    public function store()
    {
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'required');
        $this->form_validation->set_rules('invoice_no', 'No. Faktur Supplier', 'required');
        $this->form_validation->set_rules('invoice_date', 'Tgl. Faktur', 'required');
        $this->form_validation->set_rules('due_date', 'Tgl. Jatuh Tempo', 'required');
        $this->form_validation->set_rules('invoice_type', 'Tipe Faktur', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('apinvoice/create');
        }

        $invoice_type = $this->input->post('invoice_type');
        $details = $this->input->post('details');

        if (empty($details)) {
            $this->session->set_flashdata('error', 'Detail faktur tidak boleh kosong.');
            redirect('apinvoice/create');
        }

        try {
            $header_id = $this->model->insert_invoice($this->input->post());
            if ($header_id) {
                $this->session->set_flashdata('success', 'Faktur Supplier berhasil disimpan sebagai Draft.');
                redirect('apinvoice/view/' . $header_id);
            } else {
                throw new Exception('Gagal menyimpan faktur ke database.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            redirect('apinvoice/create');
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
            redirect('apinvoice');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('apinvoice/view/' . $id);
        }
    }
    
    /**
     * Proses Konfirmasi & Posting Jurnal Otomatis
     */
    public function post($id)
    {
        $user_id = $this->session->userdata('user_id');
        try {
            $result = $this->model->post_invoice_journal($id, $user_id);
            if ($result['status'] == TRUE) {
                $this->session->set_flashdata('success', 'Faktur berhasil di-posting. Jurnal ' . $result['journal_no'] . ' telah dibuat.');
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal Posting: ' . $e->getMessage());
        }
        redirect('apinvoice/view/' . $id);
    }

    /**
     * ======================================
     * FUNGSI EKSPOR DARI HALAMAN DETAIL
     * ======================================
     */

    // Ekspor Detail Faktur ke PDF
    public function export_detail_pdf($id)
    {
        $header = $this->model->get_invoice_header_by_id($id);
        if (!$header) show_404();

        $data['header'] = $header;
        $data['details'] = $this->model->get_invoice_details_by_header_id($id);
        $data['title'] = 'Faktur Supplier: ' . $header->invoice_no;

        // Render view ke dalam HTML
        $html = $this->load->view('accounting/ap_invoice/pdf_template', $data, true);

        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output PDF ke browser
        $filename = "AP-Invoice-" . str_replace('/', '-', $header->invoice_no) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

    // Ekspor Detail Faktur ke Excel
    public function export_detail_excel($id)
    {
        $header = $this->model->get_invoice_header_by_id($id);
        if (!$header) show_404();
        $details = $this->model->get_invoice_details_by_header_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Faktur ' . $header->invoice_no);

        // --- Header Dokumen ---
        $sheet->setCellValue('A1', 'FAKTUR SUPPLIER');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A3', 'No. Faktur:')->setCellValue('B3', $header->invoice_no);
        $sheet->setCellValue('A4', 'Supplier:')->setCellValue('B4', $header->nama_supplier);
        $sheet->setCellValue('A5', 'Tgl. Faktur:')->setCellValue('B5', date('d M Y', strtotime($header->invoice_date)));
        $sheet->setCellValue('D3', 'Total Tagihan:')->setCellValue('E3', $header->total_invoice);
        $sheet->getStyle('E3')->getNumberFormat()->setFormatCode('#,##0.00');

        // --- Header Tabel Detail ---
        $sheet->setCellValue('A7', 'Deskripsi (Item/Jasa)');
        $sheet->setCellValue('B7', 'Qty');
        $sheet->setCellValue('C7', 'Harga Satuan');
        $sheet->setCellValue('D7', 'Total');
        $sheet->getStyle('A7:D7')->getFont()->setBold(true);

        // --- Isi Tabel Detail ---
        $rowNum = 8;
        foreach ($details as $d) {
            $description = $d->account_id ? "({$d->account_code}) {$d->account_name}" : $d->description;
            $sheet->setCellValue('A' . $rowNum, $description);
            $sheet->setCellValue('B' . $rowNum, (float)$d->qty);
            $sheet->setCellValue('C' . $rowNum, (float)$d->unit_price);
            $sheet->setCellValue('D' . $rowNum, (float)$d->total_price);
            $rowNum++;
        }

        // --- Footer Total ---
        $rowNum++;
        $sheet->setCellValue('C' . $rowNum, 'Subtotal:')->setCellValue('D' . $rowNum, (float)$header->subtotal);
        $sheet->setCellValue('C' . ($rowNum + 1), 'PPN:')->setCellValue('D' . ($rowNum + 1), (float)$header->ppn_amount);
        $sheet->setCellValue('C' . ($rowNum + 2), 'Potongan PPh:')->setCellValue('D' . ($rowNum + 2), (float)$header->pph_amount * -1);
        $sheet->setCellValue('C' . ($rowNum + 3), 'Grand Total:')->setCellValue('D' . ($rowNum + 3), (float)$header->total_invoice);
        $sheet->getStyle('C' . $rowNum . ':D' . ($rowNum + 3))->getFont()->setBold(true);

        // --- Formatting ---
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('B8:D' . ($rowNum + 3))->getNumberFormat()->setFormatCode('#,##0.00');

        // --- Output File ---
        $writer = new Xlsx($spreadsheet);
        $filename = "AP-Invoice-" . str_replace('/', '-', $header->invoice_no) . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
    }

    /**
     * ======================================
     * AJAX HELPERS (Untuk form create)
     * ======================================
     */

    public function ajax_get_pending_grn()
    {
        $supplier_id = $this->input->get('supplier_id');
        $grn_list = $this->model->get_pending_grn($supplier_id);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $grn_list]));
    }
    
    public function ajax_get_grn_details()
    {
        // Try to read posted grn_ids from standard POST or raw JSON body
        $grn_ids = $this->input->post('grn_ids');

        if (empty($grn_ids)) {
            // try raw input (in case client sent JSON)
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['grn_ids'])) {
                $grn_ids = $decoded['grn_ids'];
            }
        }

        if (empty($grn_ids)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tidak ada GRN dipilih']));
        }

        // Normalize to array of ints
        if (!is_array($grn_ids)) {
            // might be comma separated string
            $grn_ids = is_string($grn_ids) ? explode(',', $grn_ids) : [$grn_ids];
        }
        $grn_ids = array_map('intval', $grn_ids);

        $details = $this->model->get_details_from_grn($grn_ids);

        // Debug logging for quick diagnostics (temporary)
        log_message('debug', 'ajax_get_grn_details - grn_ids: ' . json_encode($grn_ids) . ' | rows: ' . count($details));

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'data' => $details]));
    }
}