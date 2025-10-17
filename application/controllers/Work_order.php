<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Work_order extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Wo_model', 'Sc_model']);
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'   => 'Work Order (Production Plan)',
            'wos'     => $this->Wo_model->get_all($filters),
            'open_contracts' => $this->Sc_model->get_open_contracts(),
            'filters' => $filters
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('work_order/index', $data);
        $this->load->view('templates/footer');
    }

    public function get_sc_details_json() {
        $sc_id = $this->input->get('sc_id');
        if (!$sc_id) { echo json_encode(['success' => false]); return; }
        $sc_details = $this->Wo_model->get_sc_details_for_wo($sc_id);
        if ($sc_details) {
            echo json_encode(['success' => true, 'details' => $sc_details]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function create_header() {
        if (!check_access('work_order', 'add')) { show_error('Tidak ada izin.', 403); }
        $sc_id = $this->input->post('sales_contract_id');
        $sc_details = $this->Wo_model->get_sc_details_for_wo($sc_id);
        if (!$sc_details) {
             $this->session->set_flashdata('error', 'Sales Contract tidak valid atau tidak ditemukan!');
             redirect('work_order');
        }
        $fg_id = $sc_details['fg_id'];

        $data = [
            'wo_number'         => $this->Wo_model->generate_wo_number(),
            'sales_contract_id' => $sc_id,
            'fg_id'             => $fg_id,
            'planned_qty'       => (int) $this->input->post('planned_qty'),
            'planned_start_date'=> $this->input->post('planned_start_date') ?: NULL,
            'planned_end_date'  => $this->input->post('planned_end_date') ?: NULL,
            'remarks'           => $this->input->post('remarks'),
            'status'            => 'Planned',
            'created_by'        => $this->session->userdata('user_id') ?? null
        ];

        $new_wo_id = $this->Wo_model->insert_header($data);
        if ($new_wo_id) {
            $this->session->set_flashdata('success', 'Work Order berhasil dibuat. Silakan tambahkan rencana material.');
            redirect('work_order/detail/' . $new_wo_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat Work Order!');
            redirect('work_order');
        }
    }

    public function detail($id) {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header) show_404();
        $data = [
            'title'   => 'Detail Work Order',
            'header'  => $header,
            'planned_materials' => $this->Wo_model->get_planned_materials($id),
            'bom_components' => $this->Wo_model->get_bom_components_by_fg($header->fg_id)
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('work_order/detail', $data);
        // Footer dipanggil di view
    }

    // === Confirm / Unconfirm WO ===
    public function confirm_wo($id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'Planned') { redirect('work_order/detail/' . $id); }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Wo_model->update_status($id, 'In Progress', $user_id)) {
            $this->session->set_flashdata('success', 'Work Order dikonfirmasi, status In Progress.');
        } else { $this->session->set_flashdata('error', 'Gagal konfirmasi Work Order.'); }
        redirect('work_order/detail/' . $id);
    }

    public function unconfirm_wo($id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'In Progress') { redirect('work_order/detail/' . $id); }

        // TODO: Validasi jika material sudah di-issue
        // $is_issued = $this->Material_issue_model->check_by_wo($id);
        // if($is_issued) { $this->session->set_flashdata('error', 'Tidak bisa unconfirm, material sudah dikeluarkan.'); redirect('work_order/detail/' . $id); }

        if ($this->Wo_model->update_status($id, 'Planned')) {
            $this->session->set_flashdata('success', 'Konfirmasi Work Order dibatalkan, status Planned.');
        } else { $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi.'); }
        redirect('work_order/detail/' . $id);
    }

    // === AJAX & Item Actions ===
    public function search_components() {
        $term = $this->input->get('q');
        $items = $this->Wo_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function add_material($wo_header_id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($wo_header_id);
        if (!$header || !in_array($header->status, ['Planned'])) {
             $this->session->set_flashdata('error', 'Material tidak bisa ditambahkan pada status WO ini.');
             redirect('work_order/detail/' . $wo_header_id);
        }
        $item_data = explode('|', $this->input->post('component_id'));
        if (count($item_data) < 2) { redirect('work_order/detail/' . $wo_header_id); }
        $data = [
            'wo_header_id' => $wo_header_id, 'component_item_id' => $item_data[0],
            'component_item_type' => $item_data[1], 'planned_qty' => (float) $this->input->post('planned_qty') ?: 0,
        ];
        if ($this->Wo_model->insert_material($data)) { $this->session->set_flashdata('success', 'Material ditambahkan ke rencana.'); }
        else { $this->session->set_flashdata('error', 'Gagal menambah material (mungkin sudah ada?).'); }
        redirect('work_order/detail/' . $wo_header_id);
    }

    public function delete_material($wo_header_id, $detail_id) {
        if (!check_access('work_order', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($wo_header_id);
        if (!$header || !in_array($header->status, ['Planned'])) {
            $this->session->set_flashdata('error', 'Material tidak bisa dihapus pada status WO ini.');
             redirect('work_order/detail/' . $wo_header_id);
        }
        if ($this->Wo_model->delete_material($detail_id)) { $this->session->set_flashdata('success', 'Material dihapus dari rencana.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus material.'); }
        redirect('work_order/detail/' . $wo_header_id);
    }

    public function delete_wo($id) {
        if (!check_access('work_order', 'delete')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header || $header->status != 'Planned') { 
            $this->session->set_flashdata('error', 'WO tidak bisa dihapus (status bukan Planned).');
            redirect('work_order');
         }
        if ($this->Wo_model->delete_header($id)) { $this->session->set_flashdata('success', 'Work Order berhasil dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus Work Order.'); }
        redirect('work_order');
    }

    // === Exports ===
    public function export_pdf($id) {
       if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Wo_model->get_planned_materials($id);
        $data = ['header' => $header, 'details' => $details];
        $options = new Options(); $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('work_order/pdf_template', $data, true);
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
        $filename = "WO_".str_replace('/', '-', $header->wo_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

    public function export_excel($id) {
        if (!check_access('work_order', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Wo_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Wo_model->get_planned_materials($id);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Work Order");
        $sheet->setCellValue('A1', 'WORK ORDER'); $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->setCellValue('A3', 'Nomor WO:'); $sheet->setCellValue('B3', $header->wo_number);
        $sheet->setCellValue('D3', 'Tanggal Buat:'); $sheet->setCellValue('E3', date('d-m-Y', strtotime($header->created_at)));
        $sheet->setCellValue('A4', 'Sales Contract:'); $sheet->setCellValue('B4', $header->sc_number);
        $sheet->setCellValue('D4', 'Customer:'); $sheet->setCellValue('E4', $header->nama_customer);
        $sheet->setCellValue('A5', 'Style:'); $sheet->setCellValue('B5', $header->fg_name . ' ('.$header->fg_code.')');
        $sheet->setCellValue('D5', 'Qty Rencana:'); $sheet->setCellValue('E5', $header->planned_qty);
        $startRow = 8;
        $headers = ['No', 'Tipe', 'Kode Komponen', 'Nama Komponen', 'Qty Plan', 'Satuan'];
        $sheet->fromArray($headers, NULL, 'A'.$startRow);
        $sheet->getStyle('A'.$startRow.':F'.$startRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$startRow.':F'.$startRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A'.$startRow.':F'.$startRow)->getBorders()->getBottom()->setBorderStyle('thick');
        $rowNum = $startRow + 1; $no = 1;
        foreach ($details as $d) {
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, ucfirst($d->item_type));
            $sheet->setCellValue('C'.$rowNum, $d->item_code);
            $sheet->setCellValue('D'.$rowNum, $d->item_name);
            $sheet->setCellValue('E'.$rowNum, $d->planned_qty);
            $sheet->setCellValue('F'.$rowNum, $d->unit_measure ?? '-');
            $sheet->getStyle('E'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.0000');
            $rowNum++;
        }
        $sheet->getStyle('A'.$startRow.':F'.($rowNum-1))->getBorders()->getAllBorders()->setBorderStyle('thin');
        foreach (range('A', 'F') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
        $writer = new Xlsx($spreadsheet);
        $filename = 'WO_'.str_replace('/', '-', $header->wo_number).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output'); exit;
    }

} // End Class