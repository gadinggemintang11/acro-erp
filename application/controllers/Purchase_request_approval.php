<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Purchase_request_approval extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        if (!check_access('purchase_request_approval', 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission_access', 'Anda tidak memiliki izin mengakses halaman ini.'), 403);
        }
        $this->load->model(['Purchase_request_model', 'Sc_model']);
        $this->load->helper('url');
    }

    // ===================== LIST - APPROVAL =====================

    public function index() {
        if (!check_access('purchase_request_approval', 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        }

        $filters = [
            'status' => $this->input->get('status') ?: 'Submitted',
            'search' => $this->input->get('search'),
            'type'   => 'produksi',
        ];

        $data = [
            'title'    => 'Persetujuan PR Material',
            'prs'      => $this->Purchase_request_model->get_all($filters),
            'filters'  => $filters,
            'statuses' => ['Submitted', 'Approved', 'Rejected'],
            'pr_type'  => 'produksi',
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'pr_header',
                'number' => 'pr_number',
                'date'   => 'pr_date',
                'where'  => ['type' => 'produksi'],
            ]),
            'my_history_route'    => 'purchase_request_approval/detail',
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view('purchase_request_approval/index', $data);
        $this->load->view('templates/footer');
    }

    // ===================== DETAIL - APPROVAL =====================

    public function detail($id) {
        if (!check_access('purchase_request_approval', 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'          => 'Detail Persetujuan PR Material',
            'header'         => $header,
            'details'        => $this->Purchase_request_model->get_details_by_id($id),
            // Progres PO per baris: sudah masuk PO berapa, PO mana saja.
            'po_progress'        => $this->Purchase_request_model->get_po_progress_by_pr($id),
            'suppliers'      => $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            'can_approve'    => $header->status === 'Submitted' && check_access('purchase_request_approval', 'approve'),
            'can_unsubmit'   => $header->status === 'Submitted' && check_access('purchase_request_approval', 'edit'),
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view('purchase_request_approval/detail', $data);
        $this->load->view('templates/footer');
    }

    // ===================== APPROVAL ACTIONS =====================

    public function approve_pr($id) {
        if (!check_access('purchase_request_approval', 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_invalid_status_approve', 'PR tidak bisa disetujui dalam status ini')]);
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Purchase_request_model->update_status($id, 'Approved', $user_id)) {
            error_log('[PR Approval] PR ID ' . $id . ' approved by user ' . $user_id);
            return $this->_json(['success' => true, 'message' => lang_line('purchase_request_approval_msg_approve_success', 'PR berhasil disetujui.')]);
        }
        return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_approve_failed', 'Gagal menyetujui PR.')]);
    }

    public function reject_pr($id) {
        if (!check_access('purchase_request_approval', 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_invalid_status_reject', 'PR tidak bisa ditolak dalam status ini')]);
        }

        $notes   = $this->input->post('rejection_notes');
        if (!$notes || strlen(trim($notes)) === 0) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_rejection_reason_required', 'Alasan penolakan harus diisi')]);
        }

        $user_id = $this->session->userdata('user_id');

        if ($this->Purchase_request_model->update_status($id, 'Rejected', $user_id, $notes)) {
            error_log('[PR Approval] PR ID ' . $id . ' rejected by user ' . $user_id);
            return $this->_json(['success' => true, 'message' => lang_line('purchase_request_approval_msg_reject_success', 'PR ditolak.')]);
        }
        return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_reject_failed', 'Gagal menolak PR.')]);
    }

    public function unsubmit_pr($id) {
        if (!check_access('purchase_request_approval', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            $this->session->set_flashdata('error', lang_line('purchase_request_approval_msg_invalid_status_unsubmit', 'PR tidak bisa dibatalkan submitnya.'));
            return redirect('purchase_request_approval/detail/' . $id);
        }

        if ($this->Purchase_request_model->update_status($id, 'Draft')) {
            error_log('[PR Approval] PR ID ' . $id . ' unsubmitted by user ' . $this->session->userdata('user_id'));
            $this->session->set_flashdata('success', lang_line('purchase_request_approval_msg_unsubmit_success', 'PR dikembalikan ke Draft.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_approval_msg_unsubmit_failed', 'Gagal membatalkan submit PR.'));
        }
        redirect('purchase_request_approval/detail/' . $id);
    }

    // ===================== ITEM EDIT/DELETE (by approver) =====================

    public function edit_item($detail_id) {
        if (!check_access('purchase_request_approval', 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $detail_id = (int)$detail_id;
        $qty = (float)$this->input->post('qty');
        if ($qty <= 0) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_qty_invalid', 'Qty harus lebih dari 0')]);
        }

        $this->Purchase_request_model->update_item($detail_id, ['qty' => $qty]);
        return $this->_json(['success' => true, 'message' => 'Qty diperbarui.', 'new_qty' => $qty]);
    }

    public function delete_item($detail_id) {
        if (!check_access('purchase_request_approval', 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $detail_id = (int)$detail_id;
        if ($this->Purchase_request_model->delete_item($detail_id)) {
            return $this->_json(['success' => true, 'message' => 'Item dihapus dari PR.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus item.']);
    }

    // ===================== EXPORT =====================

    public function export_list_excel()
    {
        if (!check_access('purchase_request_approval', 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'DAFTAR PURCHASE REQUEST');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'PR Number');
        $sheet->setCellValue('C3', 'Tanggal');
        $sheet->setCellValue('D3', 'Requester');
        $sheet->setCellValue('E3', 'Supplier');
        $sheet->setCellValue('F3', 'Keperluan / Purpose');
        $sheet->setCellValue('G3', 'Status');

        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A3:G3')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 4;
        $no  = 1;
        foreach ($prs as $pr) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $pr->pr_number);
            $sheet->setCellValue('C'.$row, date('d/m/Y', strtotime($pr->pr_date)));
            $sheet->setCellValue('D'.$row, $pr->requester_name ?? '-');
            $sheet->setCellValue('E'.$row, $pr->nama_supplier ?? '-');
            $sheet->setCellValue('F'.$row, $pr->purpose ?? '-');
            $sheet->setCellValue('G'.$row, $pr->status);
            $row++;
        }

        foreach (range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = 'PR_List_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export_list_pdf()
    {
        if (!check_access('purchase_request_approval', 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $status_colors = [
            'Draft'     => 'orange',
            'Submitted' => 'blue',
            'Approved'  => 'green',
            'Rejected'  => 'red',
        ];

        $rows = '';
        $no   = 1;
        foreach ($prs as $pr) {
            $color = $status_colors[$pr->status] ?? '#333';
            $rows .= '<tr>
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($pr->pr_number) . '</td>
                <td>' . date('d/m/Y', strtotime($pr->pr_date)) . '</td>
                <td>' . htmlspecialchars($pr->requester_name ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->nama_supplier ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->purpose ?? '-') . '</td>
                <td style="text-align:center;color:' . $color . ';font-weight:bold">' . htmlspecialchars($pr->status) . '</td>
            </tr>';
        }

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 6px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #4472C4; color: #fff; padding: 5px 4px; text-align: left; }
            td { padding: 4px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f5f5f5; }
        </style>
        <h2>DAFTAR PURCHASE REQUEST</h2>
        <p style="text-align:center;font-size:9px">Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th width="4%">No</th>
                <th width="14%">PR Number</th>
                <th width="10%">Tanggal</th>
                <th width="14%">Requester</th>
                <th width="16%">Supplier</th>
                <th width="30%">Keperluan</th>
                <th width="12%">Status</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';

        $temp_dir = APPPATH . 'cache/pdf_temp/';
        if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }
        $mpdf = new Mpdf(['tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('PR_List_' . date('Ymd') . '.pdf', 'D');
        exit;
    }

    public function export_detail_excel($id)
    {
        redirect('purchase_request/export_detail_excel/' . $id);
    }

    public function export_detail_pdf($id)
    {
        redirect('purchase_request/export_detail_pdf/' . $id);
    }

    // ===================== HELPER =====================

    private function _json($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
