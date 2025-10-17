<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Purchase_order extends CI_Controller {

    // ... (Fungsi __construct, index, add_header, update_header, delete_header tetap sama) ...
    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Po_model', 'Supplier_model', 'Commoncode_model', 'Sc_model']);
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('purchase_order', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Purchase Order (PO)',
            'pos'           => $this->Po_model->get_all_po(),
            'suppliers'     => $this->Supplier_model->get_all(['status' => 'active']),
            'shipping_modes'=> $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes' => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'    => $this->Commoncode_model->get_by_group('CURRENCY')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('purchase_order/index', $data);
        $this->load->view('templates/footer');
    }

    public function add_header() {
        if (!check_access('purchase_order', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }
        if ($this->input->method() !== 'post') {
           show_error('Invalid request method.', 405); return;
        }

        $currency_code = $this->input->post('currency_code');
        $exchange_rate = ($currency_code == 'IDR' || !$this->input->post('exchange_rate'))
                            ? 1
                            : (float) str_replace(',', '', $this->input->post('exchange_rate'));

        $data = [
            'po_number'           => $this->Po_model->generate_po_number(),
            'supplier_id'         => $this->input->post('supplier_id'),
            'po_date'             => $this->input->post('po_date'),
            'shipping_mode_code'  => $this->input->post('shipping_mode_code'),
            'payment_mode_code'   => $this->input->post('payment_mode_code'),
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            'remarks'             => $this->input->post('remarks'),
            'status'              => 'Draft',
            'created_by'          => $this->session->userdata('user_id') ?? null
        ];

        $new_po_id = $this->Po_model->insert_header($data);

        if ($new_po_id) {
            $this->session->set_flashdata('success', 'Header PO berhasil dibuat. Silakan tambahkan item detail.');
            redirect('purchase_order/detail/' . $new_po_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header PO!');
            redirect('purchase_order');
        }
    }

    public function update_header($id) {
        if (!check_access('purchase_order', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404();
        if ($this->input->method() !== 'post') {
           show_error('Invalid request method.', 405); return;
        }

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'Header PO tidak ditemukan atau sudah tidak bisa diedit!');
             redirect('purchase_order');
        }

        $currency_code = $this->input->post('currency_code');
        $exchange_rate = ($currency_code == 'IDR' || !$this->input->post('exchange_rate'))
                            ? 1
                            : (float) str_replace(',', '', $this->input->post('exchange_rate'));

        $data = [
            'po_date'             => $this->input->post('po_date'),
            'shipping_mode_code'  => $this->input->post('shipping_mode_code'),
            'payment_mode_code'   => $this->input->post('payment_mode_code'),
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            'remarks'             => $this->input->post('remarks'),
        ];

        if ($this->Po_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header PO berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui header PO!');
        }
        redirect('purchase_order/detail/' . $id);
    }

    public function delete_header($id) {
        if (!check_access('purchase_order', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'PO tidak ditemukan atau sudah tidak bisa dihapus!');
             redirect('purchase_order');
        }

        $details = $this->Po_model->get_po_details_by_id($id);
        if (!empty($details)) {
             $this->session->set_flashdata('warning', 'PO tidak bisa dihapus karena sudah memiliki item. Hapus item terlebih dahulu!');
             redirect('purchase_order');
        }

        if ($this->Po_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'PO berhasil dihapus!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO!');
        }
        redirect('purchase_order');
    }


    // ✅ BARU: Fungsi untuk submit PO (ubah status ke Submitted)
    public function submit_po($id) {
        if (!check_access('purchase_order', 'edit')) { // Atau buat permission 'submit'
            show_error('Anda tidak memiliki izin untuk submit PO ini.', 403);
        }
        if (empty($id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('warning', 'PO ini tidak dalam status Draft!');
             redirect('purchase_order/detail/' . $id);
        }

        // Validasi: Pastikan PO memiliki minimal 1 item detail sebelum submit
        $details = $this->Po_model->get_po_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'PO tidak bisa disubmit karena belum memiliki item detail!');
             redirect('purchase_order/detail/' . $id);
        }

        // Ubah status menjadi Submitted
        if ($this->Po_model->update_status($id, 'Submitted')) {
            $this->session->set_flashdata('success', 'Purchase Order berhasil disubmit.');
        } else {
            $this->session->set_flashdata('error', 'Gagal submit Purchase Order!');
        }
        redirect('purchase_order/detail/' . $id);
    }


    // ... (Fungsi detail, search_items, add_item, delete_item, export_pdf, export_excel tetap sama) ...
    public function detail($id) {
        if (!check_access('purchase_order', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }
        if (empty($id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'         => 'Detail Purchase Order',
            'header'        => $header,
            'details'       => $this->Po_model->get_po_details_by_id($id),
            'shipping_modes'=> $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes' => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'    => $this->Commoncode_model->get_by_group('CURRENCY'),
            'open_contracts'=> $this->Sc_model->get_open_contracts()
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('purchase_order/detail', $data);
    }

    public function search_items() {
        $term = $this->input->get('q');
        $items = $this->Po_model->search_items_for_po($term);

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id' => $item->id . '|' . $item->type,
                'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function add_item($po_id) {
        if (!check_access('purchase_order', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if ($this->input->method() !== 'post') {
           show_error('Invalid request method.', 405); return;
        }

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'Item tidak bisa ditambahkan karena status PO bukan Draft!');
             redirect('purchase_order/detail/' . $po_id);
        }

        $item_data = explode('|', $this->input->post('item_id'));
        if (count($item_data) < 2) {
             $this->session->set_flashdata('error', 'Item yang dipilih tidak valid!');
             redirect('purchase_order/detail/' . $po_id);
        }
        $item_id = $item_data[0];
        $item_type = $item_data[1];

        $sales_contract_id = $this->input->post('sales_contract_id');
        $qty = (float) $this->input->post('qty');
        $base_price = (float) str_replace(',', '', $this->input->post('base_price'));
        $local_price = $base_price * $header->exchange_rate;
        $total_local = $qty * $local_price;

        $data = [
            'po_header_id'      => $po_id,
            'item_id'           => $item_id,
            'item_type'         => $item_type,
            'sales_contract_id' => empty($sales_contract_id) ? NULL : $sales_contract_id,
            'description'       => $this->input->post('description'),
            'qty'               => $qty,
            'base_price'        => $base_price,
            'local_price'       => $local_price,
            'total_local'       => $total_local
        ];

        if ($this->Po_model->insert_item($data)) {
            $this->session->set_flashdata('success', 'Item berhasil ditambahkan ke PO.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan item!');
        }
        redirect('purchase_order/detail/' . $po_id);
    }

    public function delete_item($po_id, $detail_id) {
        if (!check_access('purchase_order', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($po_id) || empty($detail_id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'Item tidak bisa dihapus karena status PO bukan Draft!');
             redirect('purchase_order/detail/' . $po_id);
        }

        if ($this->Po_model->delete_item($detail_id)) {
            $this->session->set_flashdata('success', 'Item berhasil dihapus dari PO.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item!');
        }
        redirect('purchase_order/detail/' . $po_id);
    }

    public function export_pdf($id) {
        if (!check_access('purchase_order', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengekspor data.', 403);
        }
        if (empty($id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);

        $data = ['header' => $header, 'details' => $details];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->load->view('purchase_order/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = "PO_".str_replace('/', '-', $header->po_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

    public function export_excel($id) {
        if (!check_access('purchase_order', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengekspor data.', 403);
        }
        if (empty($id)) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'PURCHASE ORDER');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Nomor PO:');
        $sheet->setCellValue('B3', $header->po_number);
        $sheet->setCellValue('E3', 'Tanggal:');
        $sheet->setCellValue('F3', date('d-m-Y', strtotime($header->po_date)));

        $sheet->setCellValue('A4', 'Supplier:');
        $sheet->setCellValue('B4', $header->nama_supplier);
        $sheet->setCellValue('E4', 'Currency:');
        $sheet->setCellValue('F4', $header->currency_code . ($header->currency_code != 'IDR' ? ' (Kurs: '.number_format($header->exchange_rate, 4).')' : ''));

        $startRow = 7;
        $sheet->setCellValue('A'.$startRow, 'No');
        $sheet->setCellValue('B'.$startRow, 'Kode Barang');
        $sheet->setCellValue('C'.$startRow, 'Nama Barang');
        $sheet->setCellValue('D'.$startRow, 'Satuan');
        $sheet->setCellValue('E'.$startRow, 'Qty');
        $sheet->setCellValue('F'.$startRow, 'Harga (' . $header->currency_code . ')');
        $sheet->setCellValue('G'.$startRow, 'Total (' . $header->currency_code . ')');
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $rowNum = $startRow + 1;
        $no = 1;
        $grand_total_base = 0;
        foreach ($details as $d) {
            $total_base = $d->qty * $d->base_price;
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, $d->item_code);
            $sheet->setCellValue('C'.$rowNum, $d->item_name);
            $sheet->setCellValue('D'.$rowNum, $d->item_unit);
            $sheet->setCellValue('E'.$rowNum, $d->qty);
            $sheet->setCellValue('F'.$rowNum, $d->base_price);
            $sheet->setCellValue('G'.$rowNum, $total_base);

            $sheet->getStyle('E'.$rowNum.':G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $grand_total_base += $total_base;
            $rowNum++;
        }

        $sheet->setCellValue('F'.$rowNum, 'Subtotal:');
        $sheet->setCellValue('G'.$rowNum, $grand_total_base);
        $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true);
        $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        $rowNum++;

        if($header->has_ppn){
             $ppn_amount = $grand_total_base * 0.11;
             $sheet->setCellValue('F'.$rowNum, 'PPN 11%:');
             $sheet->setCellValue('G'.$rowNum, $ppn_amount);
             $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true);
             $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
             $rowNum++;

             $sheet->setCellValue('F'.$rowNum, 'Grand Total:');
             $sheet->setCellValue('G'.$rowNum, $grand_total_base + $ppn_amount);
             $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true)->setSize(12);
             $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        }


        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'PO_'.str_replace('/', '-', $header->po_number).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

}