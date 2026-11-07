<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Mpdf\Mpdf;

/**
 * Quality Assurance Controller
 * 
 * Menangani Quality Control dan Quality Assurance untuk Warehouse In (GRN)
 * Workflow:
 * 1. GRN di-confirm -> QA Header dibuat otomatis
 * 2. QA Inspector melakukan QC check per item
 * 3. QA selesai -> Hanya barang PASS yang masuk stock
 * 4. Atasan bisa review/approve QA
 */
class Quality_assurance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Quality_assurance_model',
            'Warehouse_in_model',
            'Stock_ledger_model',
            'Stock_on_hand_model'
        ]);
        $this->load->helper('url');
    }

    /**
     * List semua QA
     */
    public function index() {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $page = $this->input->get('page') ?? 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        // Get filters dari request
        $filters = [
            'status' => $this->input->get('status'),
            'qa_status' => $this->input->get('qa_status'),
            'search' => $this->input->get('search')
        ];

        // Get data
        $qa_headers = $this->Quality_assurance_model->get_all_qa_headers($per_page, $offset, $filters);

        // Recalculate pass/reject items dari qa_details untuk accuracy
        foreach ($qa_headers as $qa) {
            $qa_details = $this->Quality_assurance_model->get_qa_details_by_header($qa->id);
            $qa->pass_items = 0;
            $qa->reject_items = 0;
            $qa->pass_qty = 0;
            $qa->reject_qty = 0;

            foreach ($qa_details as $detail) {
                $qa->pass_qty += (float)($detail->qa_pass_qty ?? 0);
                $qa->reject_qty += (float)($detail->qa_reject_qty ?? 0);

                if ($detail->qa_status === 'Pass') {
                    $qa->pass_items++;
                } elseif (in_array($detail->qa_status, ['Reject', 'Partial']) && (float)($detail->qa_reject_qty ?? 0) > 0) {
                    $qa->reject_items++;
                }
            }
        }

        $total = $this->Quality_assurance_model->count_qa_headers($filters);
        $total_pages = ceil($total / $per_page);

        $data = [
            'title' => lang_line('page_quality_assurance_title'),
            'qa_headers' => $qa_headers,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'per_page' => $per_page,
            'filters' => $filters,
            'qa_statuses' => ['Pending', 'In Progress', 'Passed', 'Partial Passed', 'Rejected'],
            'header_statuses' => ['Pending', 'In Progress', 'Draft', 'Completed', 'Rejected']
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('quality_assurance/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Detail QA - lihat item yang perlu di-check
     */
    public function detail($qa_header_id) {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            show_404();
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header) {
            show_404();
        }

        $qa_details = $this->Quality_assurance_model->get_qa_details_by_header($qa_header_id);
        
        // Get item names untuk setiap detail
        foreach ($qa_details as $detail) {
            $item_name = $this->_get_item_name($detail->item_id, $detail->item_type);
            $detail->item_name = $item_name['name'];
            $detail->item_code = $item_name['code'];
            
            // Get attachments jika ada
            $detail->attachments = $this->Quality_assurance_model->get_reject_attachments($detail->id);
        }

        $approval_logs = $this->Quality_assurance_model->get_approval_logs($qa_header_id);
        $latest_approval = $this->Quality_assurance_model->get_latest_approval($qa_header_id);

        $data = [
            'title' => lang_line('quality_assurance_detail_title', 'Quality Assurance Detail'),
            'qa_header' => $qa_header,
            'qa_details' => $qa_details,
            'approval_logs' => $approval_logs,
            'latest_approval' => $latest_approval,
            'can_edit' => $qa_header->status === 'In Progress' && check_access('quality_assurance', 'edit'),
            'can_edit_items' => in_array($qa_header->status, ['In Progress', 'Draft']) && check_access('quality_assurance', 'edit'),
            'can_approve' => $qa_header->status === 'Draft' && check_access('quality_assurance', 'approve'),
            'can_reopen' => in_array($qa_header->status, ['Draft', 'Rejected']) && check_access('quality_assurance', 'edit'),
            'can_unapprove' => $qa_header->status === 'Completed' && check_access('quality_assurance', 'approve'),
            'user_role' => $this->session->userdata('role_id'),
            'user_id' => $this->session->userdata('user_id')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('quality_assurance/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * AJAX: Update QA item dengan hasil QC
     * Input: qa_detail_id, qa_pass_qty, qa_reject_qty, reject_reason, reject_description
     */
    /**
     * Get QA item data untuk populate form (AJAX)
     */
    public function get_qa_item($qa_detail_id) {
        if (!check_access('quality_assurance', 'view')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_detail_id) || !is_numeric($qa_detail_id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA detail ID']);
            return;
        }

        $detail = $this->Quality_assurance_model->get_qa_detail_by_id($qa_detail_id);
        if (!$detail) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'QA detail not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'receive_qty'        => $detail->receive_qty,
                'qa_pass_qty'        => $detail->qa_pass_qty,
                'qa_reject_qty'      => $detail->qa_reject_qty,
                'reject_reason'      => $detail->reject_reason,
                'ng_category'        => $detail->ng_category ?? '',
                'reject_description' => $detail->reject_description,
                'photo_link'         => $detail->photo_link ?? '',
                'pic'                => $detail->pic ?? ''
            ]
        ]);
    }

    public function update_qa_item() {
        if (!check_access('quality_assurance', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $qa_detail_id     = $this->input->post('qa_detail_id');
        $reject_qty       = (float)($this->input->post('qa_reject_qty') ?? 0);
        $reject_reason    = $this->input->post('reject_reason');
        $ng_category      = $this->input->post('ng_category');
        $reject_description = $this->input->post('reject_description');
        $photo_link       = $this->input->post('photo_link');
        $pic              = $this->input->post('pic');

        if (empty($qa_detail_id) || (!is_numeric($qa_detail_id))) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA detail ID']);
            return;
        }

        // Validasi qty
        if ($reject_qty < 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'NG Qty tidak boleh negatif']);
            return;
        }

        // Get detail untuk hitung pass_qty
        $detail = $this->Quality_assurance_model->get_qa_detail_by_id($qa_detail_id);
        if (!$detail) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'QA detail not found']);
            return;
        }

        // Hanya boleh edit saat header status In Progress atau Draft (sebelum approve final)
        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($detail->qa_header_id);
        if (!$qa_header || !in_array($qa_header->status, ['In Progress', 'Draft'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Item tidak bisa diedit di status saat ini']);
            return;
        }

        // Validasi reject_qty tidak melebihi receive_qty
        if ($reject_qty > $detail->receive_qty + 0.01) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'NG Qty (' . $reject_qty . ') melebihi Receive Qty (' . $detail->receive_qty . ')'
            ]);
            return;
        }

        // Pass qty dihitung otomatis
        $pass_qty = max(0, (float)$detail->receive_qty - $reject_qty);

        // Update detail
        $update_data = [
            'qa_pass_qty'        => $pass_qty,
            'qa_reject_qty'      => $reject_qty,
            'checked_by'         => $this->session->userdata('user_id'),
            'pic'                => $pic,
            'reject_reason'      => $reject_reason,
            'ng_category'        => $ng_category,
            'reject_description' => $reject_description,
            'photo_link'         => $photo_link
        ];

        if (!$this->Quality_assurance_model->update_qa_detail($qa_detail_id, $update_data)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to update QA detail']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'QC item berhasil di-update'
        ]);
    }

    /**
     * Upload attachment untuk reject item
     */
    public function upload_attachment($qa_detail_id) {
        if (!check_access('quality_assurance', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_detail_id) || !is_numeric($qa_detail_id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA detail ID']);
            return;
        }

        // Get detail untuk validasi
        $detail = $this->Quality_assurance_model->get_qa_detail_by_id($qa_detail_id);
        if (!$detail) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'QA detail not found']);
            return;
        }

        // TODO: Implement file upload or cloud link storage
        // Options:
        // 1. Direct file upload ke server
        // 2. Accept cloud link (Google Drive, Dropbox, OneDrive)
        // 3. Generate signed URL untuk cloud storage

        // For now, accept manual URL input
        $attachment_url = $this->input->post('attachment_url');
        $attachment_type = $this->input->post('attachment_type') ?? 'document';
        $attachment_description = $this->input->post('attachment_description');

        if (empty($attachment_url)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Attachment URL required']);
            return;
        }

        // Validasi URL
        if (!filter_var($attachment_url, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid URL format']);
            return;
        }

        // Add attachment
        $attachment_id = $this->Quality_assurance_model->add_reject_attachment(
            $qa_detail_id,
            $attachment_url,
            $attachment_type,
            $attachment_description,
            $this->session->userdata('user_id')
        );

        if ($attachment_id) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Attachment berhasil di-upload',
                'attachment_id' => $attachment_id
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to add attachment']);
        }
    }

    /**
     * Delete attachment
     */
    public function delete_attachment($attachment_id) {
        if (!check_access('quality_assurance', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($attachment_id) || !is_numeric($attachment_id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid attachment ID']);
            return;
        }

        if ($this->Quality_assurance_model->delete_attachment($attachment_id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Attachment deleted']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete attachment']);
        }
    }

    /**
     * Bulk QC update: set Pass All atau Reject All untuk multiple qa_details sekaligus.
     * Input POST:
     *  - action: 'pass' | 'reject'
     *  - qa_detail_ids[]: array of ids
     *  - reject_reason, ng_category, reject_description, pic (opsional, dipakai kalau action='reject')
     */
    public function bulk_qc_update($qa_header_id) {
        header('Content-Type: application/json');

        if (!check_access('quality_assurance', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA ID']);
            return;
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || !in_array($qa_header->status, ['In Progress', 'Draft'])) {
            echo json_encode(['status' => 'error', 'message' => 'QA harus berstatus In Progress atau Draft']);
            return;
        }

        $action          = $this->input->post('action');
        $detail_ids      = $this->input->post('qa_detail_ids');
        $reject_reason   = trim((string)$this->input->post('reject_reason'));
        $ng_category     = $this->input->post('ng_category');
        $reject_desc     = $this->input->post('reject_description');
        $pic             = $this->input->post('pic');

        if (!in_array($action, ['pass', 'reject'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
            return;
        }

        if (!is_array($detail_ids) || empty($detail_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada item yang dipilih']);
            return;
        }

        if ($action === 'reject' && $reject_reason === '') {
            echo json_encode(['status' => 'error', 'message' => 'Reject reason wajib diisi']);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $ok_count = 0;
        $fail_count = 0;

        foreach ($detail_ids as $detail_id) {
            if (!is_numeric($detail_id)) { $fail_count++; continue; }

            $detail = $this->Quality_assurance_model->get_qa_detail_by_id($detail_id);
            if (!$detail || $detail->qa_header_id != $qa_header_id) {
                $fail_count++;
                continue;
            }

            if ($action === 'pass') {
                $data = [
                    'qa_pass_qty'        => (float)$detail->receive_qty,
                    'qa_reject_qty'      => 0,
                    'reject_reason'      => null,
                    'reject_description' => null,
                    'ng_category'        => null,
                    'checked_by'         => $user_id,
                ];
            } else {
                $data = [
                    'qa_pass_qty'        => 0,
                    'qa_reject_qty'      => (float)$detail->receive_qty,
                    'reject_reason'      => $reject_reason,
                    'reject_description' => $reject_desc ?: null,
                    'ng_category'        => $ng_category ?: null,
                    'pic'                => $pic ?: $detail->pic,
                    'checked_by'         => $user_id,
                ];
            }

            if ($this->Quality_assurance_model->update_qa_detail($detail_id, $data)) {
                $ok_count++;
            } else {
                $fail_count++;
            }
        }

        echo json_encode([
            'status'  => $ok_count > 0 ? 'success' : 'error',
            'message' => "Bulk update selesai: {$ok_count} berhasil, {$fail_count} gagal.",
            'ok'      => $ok_count,
            'fail'    => $fail_count,
        ]);
    }

    /**
     * Complete QA - finalisasi QC check
     */
    public function complete_qa($qa_header_id) {
        if (!check_access('quality_assurance', 'edit')) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki izin.');
            redirect('quality_assurance');
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            show_404();
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || $qa_header->status !== 'In Progress') {
            $this->session->set_flashdata('error', 'QA tidak bisa diselesaikan dalam status saat ini.');
            redirect('quality_assurance/detail/' . $qa_header_id);
            return;
        }

        // Check semua items sudah di-QC
        $qa_details = $this->Quality_assurance_model->get_qa_details_by_header($qa_header_id);
        $all_checked = true;
        foreach ($qa_details as $detail) {
            if ($detail->qa_status === 'Pending') {
                $all_checked = false;
                break;
            }
        }

        if (!$all_checked) {
            $this->session->set_flashdata('error', 'Semua item harus di-QC terlebih dahulu.');
            redirect('quality_assurance/detail/' . $qa_header_id);
            return;
        }

        // Complete QA → status Draft, menunggu approval atasan.
        // Stock-in baru terjadi saat approve_qa.
        if ($this->Quality_assurance_model->complete_qa($qa_header_id, $this->session->userdata('user_id'))) {
            $this->session->set_flashdata('success',
                'QC selesai. QA menunggu approval atasan sebelum stok masuk ke ledger.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menyelesaikan QA.');
        }

        redirect('quality_assurance/detail/' . $qa_header_id);
    }

    /**
     * Reopen QA:
     * - Dari 'Draft'    → re-QC (reset details, status In Progress)
     * - Dari 'Rejected' → recovery salah klik (status balik ke Draft, details preserved)
     */
    public function reopen_qa($qa_header_id) {
        if (!check_access('quality_assurance', 'edit')) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki izin.');
            redirect('quality_assurance');
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            show_404();
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || !in_array($qa_header->status, ['Draft', 'Rejected'])) {
            $this->session->set_flashdata('error', 'QA tidak bisa dibuka kembali dalam status saat ini.');
            redirect('quality_assurance/detail/' . $qa_header_id);
            return;
        }

        $prev_status = $qa_header->status;

        if ($this->Quality_assurance_model->reopen_qa($qa_header_id)) {
            $msg = $prev_status === 'Rejected'
                ? 'QA dipulihkan dari Rejected ke Draft. Anda dapat approve/reject ulang.'
                : 'QA dibuka kembali untuk re-check. Hasil QC sebelumnya direset.';
            $this->session->set_flashdata('success', $msg);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuka kembali QA.');
        }

        redirect('quality_assurance/detail/' . $qa_header_id);
    }

    /**
     * Approve QA (dari atasan): status Draft → Completed.
     * Stock PASS qty masuk ke ledger di sini.
     */
    public function approve_qa($qa_header_id) {
        header('Content-Type: application/json');

        if (!check_access('quality_assurance', 'approve')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA ID']);
            return;
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || $qa_header->status !== 'Draft') {
            echo json_encode(['status' => 'error', 'message' => 'QA cannot be approved in current status']);
            return;
        }

        $approval_notes = $this->input->post('approval_notes');
        $user_id = $this->session->userdata('user_id');

        // 1) Update status QA Header → Completed + set GRN.qa_status
        if (!$this->Quality_assurance_model->approve_qa_header($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal update status QA']);
            return;
        }

        // 2) Add stock dari PASS qty ke ledger
        $stock_result = $this->Warehouse_in_model->add_stock_from_qa(
            $qa_header->grn_header_id,
            $qa_header_id,
            $user_id
        );

        if (!$stock_result) {
            log_message('error', 'QA Approve: stock update failed for QA ID ' . $qa_header_id);
            echo json_encode([
                'status'  => 'error',
                'message' => 'QA di-approve tapi gagal update stok. Hubungi admin.'
            ]);
            return;
        }

        // 3) Log approval
        $this->Quality_assurance_model->add_approval_log(
            $qa_header_id,
            $user_id,
            'Approved',
            $approval_notes
        );

        echo json_encode([
            'status'  => 'success',
            'message' => 'QA berhasil di-approve. Barang PASS sudah masuk ke stok.'
        ]);
    }

    /**
     * Unapprove QA (dari atasan): status Completed → Draft.
     * Hapus stock ledger + SOH batches yang dibuat saat approve.
     * BLOCK kalau stock sudah terpakai (cegah inventory corruption).
     */
    public function unapprove_qa($qa_header_id) {
        header('Content-Type: application/json');

        if (!check_access('quality_assurance', 'approve')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA ID']);
            return;
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || $qa_header->status !== 'Completed') {
            echo json_encode(['status' => 'error', 'message' => 'QA harus berstatus Completed untuk di-unapprove']);
            return;
        }

        $notes = trim((string)$this->input->post('unapprove_notes'));

        $result = $this->Quality_assurance_model->unapprove_qa($qa_header_id);
        if (!$result['ok']) {
            echo json_encode(['status' => 'error', 'message' => $result['message']]);
            return;
        }

        $this->Quality_assurance_model->add_approval_log(
            $qa_header_id,
            $this->session->userdata('user_id'),
            'Unapproved',
            $notes !== '' ? $notes : null
        );

        echo json_encode([
            'status'  => 'success',
            'message' => 'QA berhasil di-unapprove. Stok dari approve sebelumnya dihapus. QA kembali ke Draft.'
        ]);
    }

    /**
     * Reject QA final (dari atasan): status Draft → Rejected.
     * qa_details tidak di-overwrite — kalau salah klik, bisa di-reopen dari Rejected ke Draft.
     */
    public function reject_qa($qa_header_id) {
        header('Content-Type: application/json');

        if (!check_access('quality_assurance', 'approve')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid QA ID']);
            return;
        }

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header || $qa_header->status !== 'Draft') {
            echo json_encode(['status' => 'error', 'message' => 'QA cannot be rejected in current status']);
            return;
        }

        $rejection_notes = trim((string)$this->input->post('rejection_notes'));
        if ($rejection_notes === '') {
            echo json_encode(['status' => 'error', 'message' => 'Rejection notes wajib diisi']);
            return;
        }

        if (!$this->Quality_assurance_model->final_reject_qa($qa_header_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal reject QA']);
            return;
        }

        $this->Quality_assurance_model->add_approval_log(
            $qa_header_id,
            $this->session->userdata('user_id'),
            'Rejected',
            $rejection_notes
        );

        echo json_encode([
            'status'  => 'success',
            'message' => 'QA di-reject (final). Jika salah klik, gunakan Reopen untuk recovery.'
        ]);
    }

    /**
     * Export list QA ke Excel (dengan filter aktif)
     */
    public function export_list_excel() {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'status'    => $this->input->get('status'),
            'qa_status' => $this->input->get('qa_status'),
            'search'    => $this->input->get('search')
        ];

        $qa_headers = $this->Quality_assurance_model->get_all_qa_headers(9999, 0, $filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar QA');

        // Judul
        $sheet->setCellValue('A1', 'DAFTAR QUALITY ASSURANCE');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Info filter
        $filterParts = [];
        if (!empty($filters['status']))    $filterParts[] = 'Status: ' . $filters['status'];
        if (!empty($filters['qa_status'])) $filterParts[] = 'QA Status: ' . $filters['qa_status'];
        if (!empty($filters['search']))    $filterParts[] = 'Pencarian: ' . $filters['search'];
        $filterInfo = empty($filterParts) ? 'Semua Data' : implode(' | ', $filterParts);
        $filterInfo .= '  |  Dicetak: ' . date('d/m/Y H:i');

        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        // Header kolom
        $headerRow = 4;
        $cols = ['No.', 'QA Number', 'GRN Number', 'Supplier', 'Tgl QA', 'Status', 'Total Items', 'Pass Items', 'Reject Items'];
        foreach ($cols as $i => $label) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $label);
        }

        $headerRange = 'A' . $headerRow . ':I' . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data
        $row = $headerRow + 1;
        $no = 1;
        foreach ($qa_headers as $qa) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $qa->qa_number);
            $sheet->setCellValue('C' . $row, $qa->grn_number);
            $sheet->setCellValue('D' . $row, $qa->nama_supplier ?? '-');
            $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($qa->qa_date)));
            $sheet->setCellValue('F' . $row, $qa->status);
            $sheet->setCellValue('G' . $row, (int)($qa->total_items ?? 0));
            $sheet->setCellValue('H' . $row, (int)($qa->pass_items ?? 0));
            $sheet->setCellValue('I' . $row, (int)($qa->reject_items ?? 0));

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            }
            $row++;
        }

        // Border seluruh tabel
        $sheet->getStyle('A' . $headerRow . ':I' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Lebar kolom
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(13);
        $sheet->getColumnDimension('H')->setWidth(13);
        $sheet->getColumnDimension('I')->setWidth(14);

        $filename = 'QA_List_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export list QA ke PDF (dengan filter aktif)
     */
    public function export_list_pdf() {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'status'    => $this->input->get('status'),
            'qa_status' => $this->input->get('qa_status'),
            'search'    => $this->input->get('search')
        ];

        $qa_headers = $this->Quality_assurance_model->get_all_qa_headers(9999, 0, $filters);

        $filterParts = [];
        if (!empty($filters['status']))    $filterParts[] = 'Status: ' . $filters['status'];
        if (!empty($filters['qa_status'])) $filterParts[] = 'QA Status: ' . $filters['qa_status'];
        if (!empty($filters['search']))    $filterParts[] = 'Pencarian: ' . $filters['search'];
        $filterInfo = empty($filterParts) ? 'Semua Data' : implode(' &nbsp;|&nbsp; ', array_map('htmlspecialchars', $filterParts));

        $rows = '';
        $no = 1;
        foreach ($qa_headers as $qa) {
            $bg = ($no % 2 === 0) ? '#f9f9f9' : '#ffffff';
            $rows .= '<tr style="background:' . $bg . '">
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($qa->qa_number) . '</td>
                <td>' . htmlspecialchars($qa->grn_number) . '</td>
                <td>' . htmlspecialchars($qa->nama_supplier ?? '-') . '</td>
                <td style="text-align:center">' . date('d/m/Y', strtotime($qa->qa_date)) . '</td>
                <td style="text-align:center">' . htmlspecialchars($qa->status) . '</td>
                <td style="text-align:center">' . (int)($qa->total_items ?? 0) . '</td>
                <td style="text-align:center;color:#1a7a1a">' . (int)($qa->pass_items ?? 0) . '</td>
                <td style="text-align:center;color:#c0392b">' . (int)($qa->reject_items ?? 0) . '</td>
            </tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 9pt; color: #222; }
            h2 { text-align: center; margin: 0 0 4px 0; font-size: 13pt; }
            .sub { text-align:center; font-size:8pt; color:#555; margin-bottom:10px; }
            table { width:100%; border-collapse:collapse; margin-top:8px; }
            th { background:#4472C4; color:#fff; padding:6px 4px; font-size:8pt; border:1px solid #2c4e8a; }
            td { padding:5px 4px; border:1px solid #ccc; font-size:8pt; }
        </style></head><body>
        <h2>DAFTAR QUALITY ASSURANCE</h2>
        <p class="sub">' . $filterInfo . ' &nbsp;&nbsp; Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th style="width:4%">No.</th>
                <th style="width:14%">QA Number</th>
                <th style="width:12%">GRN Number</th>
                <th style="width:22%">Supplier</th>
                <th style="width:9%">Tgl QA</th>
                <th style="width:10%">Status</th>
                <th style="width:9%">Total Items</th>
                <th style="width:10%">Pass Items</th>
                <th style="width:10%">Reject Items</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>
        </body></html>';

        try {
            $temp_dir = APPPATH . 'cache/mpdf';
            if (!is_dir($temp_dir)) @mkdir($temp_dir, 0777, true);

            $mpdf = new Mpdf([
                'tempDir'     => $temp_dir,
                'mode'        => 'utf-8',
                'format'      => 'A4-L',
                'orientation' => 'L',
            ]);
            $mpdf->WriteHTML($html);
            $filename = 'QA_List_' . date('Ymd_His') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (Exception $e) {
            log_message('error', 'QA PDF List Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export detail QA ke Excel
     */
    public function export_detail_excel($qa_header_id) {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) show_404();

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header) show_404();

        $qa_details = $this->Quality_assurance_model->get_qa_details_by_header($qa_header_id);
        foreach ($qa_details as $detail) {
            $item = $this->_get_item_name($detail->item_id, $detail->item_type);
            $detail->item_name = $item['name'];
            $detail->item_code = $item['code'];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail QA');

        // Judul
        $sheet->setCellValue('A1', 'DETAIL QUALITY ASSURANCE');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Info header
        $sheet->setCellValue('A3', 'QA Number');   $sheet->setCellValue('B3', $qa_header->qa_number);
        $sheet->setCellValue('D3', 'GRN Number');  $sheet->setCellValue('E3', $qa_header->grn_number);
        $sheet->setCellValue('A4', 'Supplier');    $sheet->setCellValue('B4', $qa_header->nama_supplier ?? '-');
        $sheet->setCellValue('D4', 'Tanggal QA');  $sheet->setCellValue('E4', date('d/m/Y', strtotime($qa_header->qa_date)));
        $sheet->setCellValue('A5', 'Status');      $sheet->setCellValue('B5', $qa_header->status);
        $sheet->setCellValue('D5', 'QC Checker');  $sheet->setCellValue('E5', $qa_header->qa_checked_by_name ?? '-');

        $infoRange = 'A3:A5';
        $sheet->getStyle($infoRange)->getFont()->setBold(true);
        $sheet->getStyle('D3:D5')->getFont()->setBold(true);

        // Header kolom
        $headerRow = 7;
        $cols = ['No.', 'Item Code', 'Item Name', 'Item Type', 'Receive Qty', 'Pass Qty', 'Reject Qty', 'Status', 'Reject Reason'];
        foreach ($cols as $i => $label) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $label);
        }

        $headerRange = 'A' . $headerRow . ':I' . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data
        $row = $headerRow + 1;
        $no = 1;
        $total_receive = 0; $total_pass = 0; $total_reject = 0;
        foreach ($qa_details as $detail) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $detail->item_code ?? '-');
            $sheet->setCellValue('C' . $row, $detail->item_name ?? '-');
            $sheet->setCellValue('D' . $row, ucfirst($detail->item_type ?? '-'));
            $sheet->setCellValue('E' . $row, (float)$detail->receive_qty);
            $sheet->setCellValue('F' . $row, (float)$detail->qa_pass_qty);
            $sheet->setCellValue('G' . $row, (float)$detail->qa_reject_qty);
            $sheet->setCellValue('H' . $row, $detail->qa_status);
            $sheet->setCellValue('I' . $row, $detail->reject_reason ?? '-');

            $sheet->getStyle('E' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.##');

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            }

            $total_receive += (float)$detail->receive_qty;
            $total_pass    += (float)$detail->qa_pass_qty;
            $total_reject  += (float)$detail->qa_reject_qty;
            $row++;
        }

        // Baris total
        $sheet->setCellValue('D' . $row, 'TOTAL');
        $sheet->setCellValue('E' . $row, $total_receive);
        $sheet->setCellValue('F' . $row, $total_pass);
        $sheet->setCellValue('G' . $row, $total_reject);
        $sheet->getStyle('D' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.##');

        // Border
        $sheet->getStyle('A' . $headerRow . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Lebar kolom
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(13);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(13);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(28);

        $safe_qa_number = str_replace(['/', '\\', ':'], '-', $qa_header->qa_number);
        $filename = 'QA_Detail_' . $safe_qa_number . '_' . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export detail QA ke PDF
     */
    public function export_detail_pdf($qa_header_id) {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($qa_header_id) || !is_numeric($qa_header_id)) show_404();

        $qa_header = $this->Quality_assurance_model->get_qa_header_by_id($qa_header_id);
        if (!$qa_header) show_404();

        $qa_details = $this->Quality_assurance_model->get_qa_details_by_header($qa_header_id);
        foreach ($qa_details as $detail) {
            $item = $this->_get_item_name($detail->item_id, $detail->item_type);
            $detail->item_name = $item['name'];
            $detail->item_code = $item['code'];
        }

        $approval_logs = $this->Quality_assurance_model->get_approval_logs($qa_header_id);

        $rows = '';
        $no = 1;
        $total_receive = 0; $total_pass = 0; $total_reject = 0;
        foreach ($qa_details as $detail) {
            $bg = ($no % 2 === 0) ? '#f5f5f5' : '#ffffff';
            $status_color = ['Pass' => '#1a7a1a', 'Reject' => '#c0392b', 'Partial' => '#d68910', 'Pending' => '#666'][$detail->qa_status] ?? '#333';
            $rows .= '<tr style="background:' . $bg . '">
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($detail->item_code ?? '-') . '</td>
                <td>' . htmlspecialchars($detail->item_name ?? '-') . '</td>
                <td style="text-align:center">' . htmlspecialchars(ucfirst($detail->item_type ?? '-')) . '</td>
                <td style="text-align:right">' . number_format((float)$detail->receive_qty, 2) . '</td>
                <td style="text-align:right;color:#1a7a1a">' . number_format((float)$detail->qa_pass_qty, 2) . '</td>
                <td style="text-align:right;color:#c0392b">' . number_format((float)$detail->qa_reject_qty, 2) . '</td>
                <td style="text-align:center;color:' . $status_color . ';font-weight:bold">' . htmlspecialchars($detail->qa_status) . '</td>
                <td>' . htmlspecialchars($detail->reject_reason ?? '-') . '</td>
            </tr>';
            $total_receive += (float)$detail->receive_qty;
            $total_pass    += (float)$detail->qa_pass_qty;
            $total_reject  += (float)$detail->qa_reject_qty;
        }

        $approval_html = '';
        if (!empty($approval_logs)) {
            $approval_html = '<h3 style="margin-top:20px;font-size:10pt">Approval History</h3>
            <table><thead><tr>
                <th>Approver</th><th>Status</th><th>Tanggal</th><th>Catatan</th>
            </tr></thead><tbody>';
            foreach ($approval_logs as $log) {
                $ac = ['Approved' => '#1a7a1a', 'Rejected' => '#c0392b'][$log->approval_status] ?? '#333';
                $approval_html .= '<tr>
                    <td>' . htmlspecialchars($log->approver_name ?? '-') . '</td>
                    <td style="color:' . $ac . ';font-weight:bold">' . htmlspecialchars($log->approval_status) . '</td>
                    <td>' . date('d/m/Y H:i', strtotime($log->approval_date)) . '</td>
                    <td>' . htmlspecialchars($log->approval_notes ?? '-') . '</td>
                </tr>';
            }
            $approval_html .= '</tbody></table>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 9pt; color: #222; }
            h2 { text-align:center; margin:0 0 6px 0; font-size:13pt; }
            .info-table { width:100%; border-collapse:collapse; margin-bottom:12px; font-size:8.5pt; }
            .info-table td { padding:3px 6px; }
            .info-table .label { font-weight:bold; width:12%; }
            table { width:100%; border-collapse:collapse; margin-top:6px; }
            th { background:#4472C4; color:#fff; padding:5px 4px; font-size:8pt; border:1px solid #2c4e8a; }
            td { padding:4px; border:1px solid #ccc; font-size:8pt; }
            .total-row td { font-weight:bold; background:#e8eaf0; }
        </style></head><body>
        <h2>DETAIL QUALITY ASSURANCE</h2>
        <table class="info-table">
            <tr>
                <td class="label">QA Number</td><td>: ' . htmlspecialchars($qa_header->qa_number) . '</td>
                <td class="label">GRN Number</td><td>: ' . htmlspecialchars($qa_header->grn_number) . '</td>
                <td class="label">Tanggal QA</td><td>: ' . date('d/m/Y', strtotime($qa_header->qa_date)) . '</td>
            </tr>
            <tr>
                <td class="label">Supplier</td><td>: ' . htmlspecialchars($qa_header->nama_supplier ?? '-') . '</td>
                <td class="label">Status</td><td>: ' . htmlspecialchars($qa_header->status) . '</td>
                <td class="label">QC Checker</td><td>: ' . htmlspecialchars($qa_header->qa_checked_by_name ?? '-') . '</td>
            </tr>
        </table>
        <table>
            <thead><tr>
                <th style="width:4%">No.</th>
                <th style="width:11%">Item Code</th>
                <th style="width:22%">Item Name</th>
                <th style="width:9%">Type</th>
                <th style="width:10%">Receive Qty</th>
                <th style="width:9%">Pass Qty</th>
                <th style="width:9%">Reject Qty</th>
                <th style="width:8%">Status</th>
                <th style="width:18%">Reject Reason</th>
            </tr></thead>
            <tbody>' . $rows . '
            <tr class="total-row">
                <td colspan="4" style="text-align:right">TOTAL</td>
                <td style="text-align:right">' . number_format($total_receive, 2) . '</td>
                <td style="text-align:right;color:#1a7a1a">' . number_format($total_pass, 2) . '</td>
                <td style="text-align:right;color:#c0392b">' . number_format($total_reject, 2) . '</td>
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
        ' . $approval_html . '
        </body></html>';

        try {
            $temp_dir = APPPATH . 'cache/mpdf';
            if (!is_dir($temp_dir)) @mkdir($temp_dir, 0777, true);

            $mpdf = new Mpdf([
                'tempDir'     => $temp_dir,
                'mode'        => 'utf-8',
                'format'      => 'A4-L',
                'orientation' => 'L',
            ]);
            $mpdf->WriteHTML($html);
            $safe_qa_number = str_replace(['/', '\\', ':'], '-', $qa_header->qa_number);
            $filename = 'QA_Detail_' . $safe_qa_number . '_' . date('Ymd') . '.pdf';
            $mpdf->Output($filename, 'D');
            exit;
        } catch (Exception $e) {
            log_message('error', 'QA PDF Detail Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * QA Report - Supplier Rejection Summary
     */
    public function qa_report() {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        // Get filters
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'supplier_id' => $this->input->get('supplier_id'),
            'qa_status' => $this->input->get('qa_status'),
            'min_reject_percentage' => $this->input->get('min_reject_percentage')
        ];

        // Get report data - ALWAYS show reject items regardless of QA header status
        // Create filters for report that don't filter by qa_status (rejection items must always be visible)
        $report_filters = $filters;
        unset($report_filters['qa_status']); // Remove qa_status filter for reject items
        $supplier_rejection_data = $this->Quality_assurance_model->get_supplier_rejection_report($report_filters);

        // Get suppliers list for filter dropdown
        $suppliers = $this->db->select('id, nama_supplier')
            ->from('suppliers')
            ->order_by('nama_supplier', 'ASC')
            ->get()
            ->result();

        $data = [
            'title' => 'QA Report - Supplier Rejection',
            'supplier_rejection_data' => $supplier_rejection_data,
            'suppliers' => $suppliers,
            'filters' => $filters,
            'qa_statuses' => ['Pending', 'In Progress', 'Completed']
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('quality_assurance/qa_report', $data);
        $this->load->view('templates/footer');
    }

    /**
     * QA Report Detail - Show reject items for a specific supplier
     */
    public function qa_report_detail($supplier_id = null) {
        if (!check_access('quality_assurance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($supplier_id) || !is_numeric($supplier_id) || $supplier_id == 0) {
            $this->session->set_flashdata('error', 'Supplier tidak valid.');
            redirect('quality_assurance/qa_report');
        }

        // Get supplier info
        $supplier = $this->db->select('id, nama_supplier')
            ->from('suppliers')
            ->where('id', $supplier_id)
            ->get()
            ->row();

        if (!$supplier) {
            $this->session->set_flashdata('error', 'Supplier tidak ditemukan.');
            redirect('quality_assurance/qa_report');
        }

        // Get filters
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'search' => $this->input->get('search')
        ];

        // Get detail reject items
        $reject_items = $this->Quality_assurance_model->get_supplier_reject_details($supplier_id, $filters);

        // Enhance dengan item names
        foreach ($reject_items as $item) {
            $item_info = $this->_get_item_name($item->item_id, $item->item_type);
            $item->item_name = $item_info['name'];
            $item->item_code = $item_info['code'];
        }

        $data = [
            'title' => 'QA Report Detail - ' . $supplier->nama_supplier,
            'supplier' => $supplier,
            'reject_items' => $reject_items,
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('quality_assurance/qa_report_detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Helper: Get item name berdasarkan type
     */
    private function _get_item_name($item_id, $item_type) {
        $name = '';
        $code = '';

        if ($item_type === 'material') {
            $item = $this->db->select('kode_material, nama_material')
                ->from('materials')
                ->where('id', $item_id)
                ->get()
                ->row();
            if ($item) {
                $code = $item->kode_material ?? '';
                $name = $item->nama_material ?? '';
            }
        } else if ($item_type === 'semifinished') {
            $item = $this->db->select('kode_barang, nama_barang')
                ->from('semifinished_goods')
                ->where('id', $item_id)
                ->get()
                ->row();
            if ($item) {
                $code = $item->kode_barang ?? '';
                $name = $item->nama_barang ?? '';
            }
        } else if ($item_type === 'machine') {
            $item = $this->db->select('kode_mesin, nama_mesin')
                ->from('machines')
                ->where('id', $item_id)
                ->get()
                ->row();
            if ($item) {
                $code = $item->kode_mesin ?? '';
                $name = $item->nama_mesin ?? '';
            }
        } else if ($item_type === 'inventory') {
            $item = $this->db->select('kode_barang, nama_barang')
                ->from('inventories')
                ->where('id', $item_id)
                ->get()
                ->row();
            if ($item) {
                $code = $item->kode_barang ?? '';
                $name = $item->nama_barang ?? '';
            }
        }

        return ['name' => $name, 'code' => $code];
    }
}
