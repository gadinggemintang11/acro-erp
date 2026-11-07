<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ERF (Expense Reimbursement Form) controller.
 * Workflow: Draft → Submitted → Approved Head Dept → Endorsed → Approved → Paid
 * Mirror Prf controller + 3-stage approval (HOD → Endorsement → Final).
 */

class Erf extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Erf_model', 'model');
        $this->load->model('Masterkaryawan_model');
        $this->load->library('form_validation');
        $this->load->helper(['form','url']);
    }

    // ============================================================
    // PAGES
    // ============================================================

    // ============================================================
    // CAKUPAN DEPARTEMEN (Role Level tahap 3)
    // ============================================================

    /** Departemen ERF: kolom department_code, fallback departemen profil pemohon (ERF lama). */
    private function _doc_dept($erf) {
        if (!empty($erf->department_code)) return $erf->department_code;
        return $this->model->get_user_department_code($erf->requester_id);
    }

    /** ERF milik sendiri ATAU departemen yang boleh dia edit/submit/hapus (mis. Supervisor). */
    private function _own_scope_sql() {
        $conds = ['eh.requester_id = ' . (int) $this->session->userdata('user_id')];
        $colleague = dept_scope_sql('erf', ['edit', 'submit', 'delete'], 'eh.department_code', true);
        if ($colleague !== '1=0') $conds[] = $colleague;
        return implode(' OR ', $conds);
    }

    /** 403 kalau ERF bukan departemen yang boleh ditangani di tahap approval ini. */
    private function _require_approval_scope($id, $action = null) {
        $erf = $this->model->get_erf_header_by_id((int) $id);
        if (!$erf) show_404();
        $action = $action ?: approval_action_for_status($erf->status, Erf_model::APPROVAL_STAGE_MAP);
        if ($action === null || !can_dept_access('erf_approval', $action, $this->_doc_dept($erf))) {
            show_error('Reimburse Request ini bukan departemen yang bisa Anda tangani di tahap approval ini.', 403);
        }
        return $erf;
    }

    /** Draft departemen lain tidak boleh diedit (Finance/Admin tetap bisa koreksi nominal). */
    private function _require_edit_scope($erf) {
        if ($erf->status === 'Draft' && !is_admin() && !check_access('erf_payment', 'view')
            && !can_dept_access('erf', 'edit', $this->_doc_dept($erf))) {
            show_error('Reimburse Request ini milik departemen lain — Anda tidak berhak mengubahnya.', 403);
        }
    }

    public function index() {
        if (!check_access('erf', 'view')) show_error('Tidak ada izin.', 403);

        $filters = [
            'date_from'      => $this->input->get('date_from'),
            'date_to'        => $this->input->get('date_to'),
            'status'         => $this->input->get('status'),
            'payment_status' => $this->input->get('payment_status'),
            // Tahap 3: milik sendiri + departemen yang dia tangani. Role lama
            // ('legacy') & Finance tetap melihat semua ERF seperti dulu.
            'scope_sql'      => (is_admin() || check_access('erf_payment', 'view')
                                 || dept_scope_is_legacy('erf', ['view', 'add', 'edit', 'submit', 'delete'])
                                 || dept_scope_is_legacy('erf_approval', ['view', 'head_dept', 'endorsement', 'approval']))
                                ? null
                                : $this->_own_scope_sql() . ' OR ' . dept_scope_sql('erf_approval', ['head_dept', 'endorsement', 'approval'], 'eh.department_code'),
        ];

        $data = [
            'title'    => lang_line('title_erf_main'),
            'erf_list' => $this->model->get_all_erf_headers($filters),
            'filters'  => $filters,
            'statuses' => $this->_status_list(),
        ];
        $this->_render('erf/index', $data);
    }

    public function list_all() {
        if (!check_access('erf_list', 'view') && !check_access('erf', 'view')) {
            show_error('Tidak ada izin.', 403);
        }

        // Per-user visibility (mirror Prf::list_all). Approvers / finance get a
        // "Lihat Semua" toggle via ?show_all=1 to override the requester scope.
        $user_id      = (int) $this->session->userdata('user_id');
        $can_view_all = check_access('erf_approval', 'view')
            || check_access('erf_approval', 'head_dept')
            || check_access('erf_approval', 'endorsement')
            || check_access('erf_approval', 'approval')
            || check_access('erf_payment', 'view');
        $show_all = $can_view_all && ((int) $this->input->get('show_all') === 1);

        $filters = [
            'date_from'      => $this->input->get('date_from'),
            'date_to'        => $this->input->get('date_to'),
            'status'         => $this->input->get('status'),
            'payment_status' => $this->input->get('payment_status'),
            'search'         => $this->input->get('search'),
            'requester_id'   => null,
            'scope_sql'      => !$show_all
                ? $this->_own_scope_sql()
                : ((is_admin() || check_access('erf_payment', 'view') || dept_scope_is_legacy('erf_approval', ['view', 'head_dept', 'endorsement', 'approval']))
                    ? null
                    : $this->_own_scope_sql() . ' OR ' . dept_scope_sql('erf_approval', ['view', 'head_dept', 'endorsement', 'approval'], 'eh.department_code')),
        ];

        $data = [
            'title'            => lang_line('title_erf_list'),
            'erf_list'         => $this->model->get_all_erf_headers($filters),
            'filters'          => $filters,
            'statuses'         => $this->_status_list(),
            'payment_statuses' => ['Unpaid','Paid','Partial','Cancelled'],
            'can_view_all'     => $can_view_all,
            'show_all'         => $show_all,
        ];
        $this->_render('erf/list_all', $data);
    }

    /**
     * Approval queue page — list ERF pending di stage user.
     */
    public function approval() {
        if (!check_access('erf_approval', 'view')) {
            show_error('Tidak ada izin akses Reimburse Request Approval.', 403);
        }
        $can_hod         = check_access('erf_approval', 'head_dept');
        $can_endorsement = check_access('erf_approval', 'endorsement');
        $can_approval    = check_access('erf_approval', 'approval');

        $data = [
            'title'           => lang_line('title_erf_approval'),
            'pending'         => $this->model->get_pending_approvals($can_hod, $can_endorsement, $can_approval),
            'can_hod'         => $can_hod,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'erf_header',
                'number' => 'erf_number',
                'date'   => 'erf_date',
            ]),
            'my_history_route'    => 'erf/detail',
        ];
        $this->_render('erf/approval', $data);
    }

    public function payment() {
        if (!check_access('erf_payment', 'view')) {
            show_error('Tidak ada izin.', 403);
        }
        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
            'search'    => $this->input->get('q'),
        ];

        $pending = $this->model->get_erf_payment_queue();
        $paid    = $this->model->get_erf_payment_history($filters);

        $sum_pending = 0;
        foreach ($pending as $p) $sum_pending += (float)$p->subtotal;
        $sum_paid = 0;
        foreach ($paid as $p) $sum_paid += (float)$p->subtotal;

        $data = [
            'title'        => lang_line('title_erf_payment'),
            'pending_list' => $pending,
            'paid_list'    => $paid,
            'sum_pending'  => $sum_pending,
            'sum_paid'     => $sum_paid,
            'filters'      => $filters,
            'active_tab'   => $this->input->get('tab') ?: 'pending',
        ];
        $this->_render('erf/payment', $data);
    }

    public function create() {
        if (!check_access('erf', 'add')) show_error('Tidak ada izin.', 403);

        // Departemen dokumen dipilih dari departemen role user (tahap 3, poin 8).
        $dept_options = dept_options_for('erf', 'add');
        $dept_default = default_dept_for($dept_options);
        $data = [
            'title'           => lang_line('title_erf_create'),
            'erf_number'      => $this->model->generate_erf_number($dept_default),
            'department_id'   => $dept_default,
            'department_name' => $dept_options[$dept_default] ?? 'Unknown',
            'dept_options'    => $dept_options,
            'requester_name'  => $this->session->userdata('fullname'),
            'karyawan_list'   => $this->Masterkaryawan_model->get_active(),
        ];
        $this->_render('erf/create', $data);
    }

    public function store() {
        if (!check_access('erf', 'add')) show_error('Tidak ada izin.', 403);

        $details_in = $this->input->post('items');
        $details = [];
        if (is_array($details_in)) {
            foreach ($details_in as $row) {
                if (empty($row['description']) || (float)$row['qty'] <= 0) continue;
                $details[] = [
                    'expense_date'   => !empty($row['expense_date']) ? $row['expense_date'] : null,
                    'category'       => $row['category']       ?? null,
                    'receipt_number' => $row['receipt_number'] ?? null,
                    'description'    => trim($row['description']),
                    'qty'            => (float)$row['qty'],
                    'unit_price'     => (float)str_replace(',', '', $row['unit_price'] ?? 0),
                ];
            }
        }
        if (empty($details)) {
            $this->session->set_flashdata('error', 'Minimal 1 item harus diisi.');
            redirect('erf/create');
            return;
        }

        $karyawan_id = (int) $this->input->post('karyawan_id');
        if ($karyawan_id <= 0) {
            $this->session->set_flashdata('error', 'Karyawan (penerima reimburse) wajib dipilih.');
            redirect('erf/create');
            return;
        }

        $department_code = normalize_department_code($this->input->post('department_code'));
        if ($department_code === null || !isset(dept_options_for('erf', 'add')[$department_code])) {
            $this->session->set_flashdata('error', 'Departemen Reimburse Request tidak valid atau di luar departemen role Anda.');
            redirect('erf/create');
            return;
        }

        $header = [
            'erf_date'        => $this->input->post('erf_date') ?: date('Y-m-d'),
            'karyawan_id'     => $karyawan_id,
            'notes'           => $this->input->post('notes'),
            'department_code' => $department_code,
        ];

        $new_id = $this->model->create_erf($header, $details);
        if ($new_id) {
            $this->session->set_flashdata('success', 'Reimburse Request berhasil dibuat. Lengkapi attachment lalu submit.');
            redirect('erf/detail/' . $new_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat Reimburse Request.');
            redirect('erf/create');
        }
    }

    public function detail($id) {
        if (!check_access('erf', 'view')) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        $header = $this->model->get_erf_header_by_id($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('erf', 'erf_approval', $this->_doc_dept($header), (int) $header->requester_id === (int) $this->session->userdata('user_id'), check_access('erf_payment', 'view'))) {
            show_error('Reimburse Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        // Tahap approval hanya untuk departemen dalam cakupan user (tahap 3).
        $doc_dept        = $this->_doc_dept($header);
        $can_hod         = can_dept_access('erf_approval', 'head_dept', $doc_dept);
        $can_endorsement = can_dept_access('erf_approval', 'endorsement', $doc_dept);
        $can_approval    = can_dept_access('erf_approval', 'approval', $doc_dept);

        $admin_edit = is_erf_admin_edit($header);
        // Tombol Kembali kontekstual: balik ke halaman asal (list ATAU
        // payment/reimbursement queue), bukan selalu ke list. Lihat
        // MY_Controller::resolve_back_url() (bug sama seperti di Prf::detail()).
        $back_url = $this->resolve_back_url('erf', ['erf']);
        $data = [
            'title'             => sprintf(lang_line('title_erf_detail'), $header->erf_number),
            'back_url'          => $back_url,
            'header'            => $header,
            'details'           => $this->model->get_erf_details_by_header_id($id),
            'attachments'       => $this->model->get_attachments($id),
            'payment_tracking'  => $this->model->get_payment_tracking($id),
            'logs'              => $this->model->get_approval_logs($id),
            'audit'             => $this->model->get_audit_users($header),
            'department_name'   => $this->model->resolve_department_name($doc_dept ?: $header->department_id),
            // Dulu edit & submit hanya pemohon sendiri. Sekarang siapa pun yang punya
            // izin untuk departemen ERF ini (mis. Supervisor submit ERF buatan Admin/Clerk).
            'can_edit'          => ($header->status === 'Draft' && can_dept_access('erf', 'edit', $doc_dept)) || $admin_edit,
            'can_delete'        => in_array($header->status, ['Draft','Cancelled','Rejected']) && can_dept_access('erf', 'delete', $doc_dept),
            'can_submit'        => $header->status === 'Draft' && can_dept_access('erf', 'submit', $doc_dept),
            // Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin
            // 11b): requestor & approver mana pun bisa reply selagi Draft.
            // Thread balasan cuma terbuka setelah ERF dikembalikan lewat Back to
            // Draft, pesertanya requestor + approver yang mengembalikan saja
            // (koreksi 2026-09-06 — lihat can_reply_return_thread() di auth_helper).
            'can_reply'         => can_reply_return_thread($header),
            'can_approve_hod'         => $header->status === 'Submitted'          && $can_hod,
            'can_approve_endorsement' => $header->status === 'Approved Head Dept' && $can_endorsement,
            'can_approve_final'       => $header->status === 'Endorsed'           && $can_approval,
            'can_return_reject' => in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])
                                && ($can_hod || $can_endorsement || $can_approval),
            'can_pay'           => $header->status === 'Approved' && $header->payment_status !== 'Paid' && check_access('erf_payment', 'add'),
            'can_upload_payment_proof' => check_access('erf_payment', 'add'),
            'admin_edit_mode'   => $admin_edit,
            'can_unlock_admin'  => can_unlock_erf_admin_edit($header),
            'status_colors'     => [
                'Draft'=>'warning','Submitted'=>'info',
                'Approved Head Dept'=>'primary','Endorsed'=>'primary',
                'Approved'=>'success','Paid'=>'success',
                'Cancelled'=>'secondary','Rejected'=>'danger',
            ],
        ];
        $this->_render('erf/detail', $data);
    }

    public function edit($id) {
        $can_edit_nominal = check_access('erf', 'edit') || check_access('erf_payment', 'view') || is_admin();
        if (!$can_edit_nominal) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();
        $header = $this->model->get_erf_header_by_id($id);
        if (!$header) show_404();
        $this->_require_edit_scope($header);

        $admin_edit = is_erf_admin_edit($header);
        $allowed_statuses = ['Draft','Paid'];
        if (!in_array($header->status, $allowed_statuses, true) && !$admin_edit) {
            $this->session->set_flashdata('error', 'Hanya Reimburse Request Draft yang bisa diedit.');
            redirect('erf/detail/' . $id);
        }
        $data = [
            'title'           => sprintf(lang_line('title_erf_edit'), $header->erf_number),
            'header'          => $header,
            'details'         => $this->model->get_erf_details_by_header_id($id),
            'department_name' => $this->model->resolve_department_name($header->department_id),
            'karyawan_list'   => $this->Masterkaryawan_model->get_active(),
            'admin_edit_mode' => $admin_edit,
        ];
        $this->_render('erf/edit', $data);
    }

    public function update($id) {
        $can_edit_nominal = check_access('erf', 'edit') || check_access('erf_payment', 'view') || is_admin();
        if (!$can_edit_nominal) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        $details_in = $this->input->post('items');
        $details = [];
        if (is_array($details_in)) {
            foreach ($details_in as $row) {
                if (empty($row['description']) || (float)$row['qty'] <= 0) continue;
                $details[] = [
                    'expense_date'   => !empty($row['expense_date']) ? $row['expense_date'] : null,
                    'category'       => $row['category']       ?? null,
                    'receipt_number' => $row['receipt_number'] ?? null,
                    'description'    => trim($row['description']),
                    'qty'            => (float)$row['qty'],
                    'unit_price'     => (float)str_replace(',', '', $row['unit_price'] ?? 0),
                ];
            }
        }
        if (empty($details)) {
            $this->session->set_flashdata('error', 'Minimal 1 item harus diisi.');
            redirect('erf/edit/' . $id);
            return;
        }

        $karyawan_id = (int) $this->input->post('karyawan_id');
        if ($karyawan_id <= 0) {
            $this->session->set_flashdata('error', 'Karyawan (penerima reimburse) wajib dipilih.');
            redirect('erf/edit/' . $id);
            return;
        }

        $header = [
            'erf_date'    => $this->input->post('erf_date'),
            'karyawan_id' => $karyawan_id,
            'notes'       => $this->input->post('notes'),
        ];

        $erf_header = $this->model->get_erf_header_by_id($id);
        if (!$erf_header) show_404();
        $this->_require_edit_scope($erf_header);
        $admin_edit = is_erf_admin_edit($erf_header);

        try {
            $res = $this->model->update_erf($id, $header, $details, $admin_edit);
            if ($res) {
                if ($admin_edit) {
                    add_log('ADMIN_EDIT', 'ERF', 'Edit Reimburse Request ' . $erf_header->erf_number . ' pada status ' . $erf_header->status . ' (override Admin — approval tidak direset).');
                }
                $this->session->set_flashdata('success', 'Reimburse Request berhasil diperbarui.');
                redirect('erf/detail/' . $id . ($admin_edit ? '?admin_edit=1' : ''));
            } else {
                throw new Exception('Gagal memperbarui Reimburse Request.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            $edit_url = 'erf/edit/' . $id . ($admin_edit ? '?admin_edit=1' : '');
            redirect($edit_url);
        }
    }

    public function delete($id) {
        if (!check_access('erf', 'delete')) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();
        $erf = $this->model->get_erf_header_by_id($id);
        if ($erf && !is_admin() && !can_dept_access('erf', 'delete', $this->_doc_dept($erf))) {
            show_error('Reimburse Request ini milik departemen lain — Anda tidak berhak menghapusnya.', 403);
        }

        if ($this->model->delete_erf($id)) {
            $this->session->set_flashdata('success', 'Reimburse Request dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Reimburse Request tidak bisa dihapus (status bukan Draft/Cancelled/Rejected).');
        }
        redirect('erf');
    }

    /**
     * Delete single detail row (Admin edit mode).
     */
    public function delete_item($header_id, $detail_id) {
        $header_id = (int)$header_id; $detail_id = (int)$detail_id;
        if ($header_id <= 0 || $detail_id <= 0) show_404();

        $header = $this->model->get_erf_header_by_id($header_id);
        if (!$header) show_404();

        // Only allow when admin edit mode active for this header
        if (!is_erf_admin_edit($header)) show_error('Tidak ada izin menghapus item.', 403);

        $res = $this->model->delete_erf_detail($detail_id, TRUE);
        if ($res) {
            add_log('ADMIN_EDIT', 'ERF', 'Hapus item #' . $detail_id . ' dari Reimburse Request ' . $header->erf_number . ' (override Admin).');
            $this->session->set_flashdata('success', 'Item berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item.');
        }
        redirect('erf/detail/' . $header_id . '?admin_edit=1');
    }

    // ============================================================
    // APPROVAL ACTIONS (3-stage)
    // ============================================================

    public function submit($id) {
        if (!check_access('erf', 'submit')) show_error('Tidak ada izin submit.', 403);
        $erf = $this->model->get_erf_header_by_id((int) $id);
        if (!$erf) show_404();
        if (!can_dept_access('erf', 'submit', $this->_doc_dept($erf))) {
            show_error('Anda tidak berhak submit Reimburse Request departemen ini.', 403);
        }

        // Attachment bukti pengeluaran WAJIB ada sebelum submit ke approval.
        if (empty($this->model->get_attachments((int)$id))) {
            $this->session->set_flashdata('error', 'Attachment bukti pengeluaran wajib diupload sebelum submit.');
            redirect('erf/detail/' . (int)$id);
            return;
        }

        $res = $this->model->submit_erf((int)$id);
        $this->_flash($res, 'Reimburse Request disubmit ke Head of Dept.');
        if (!empty($res['success'])) {
            // Notifikasi "New ERF" (ERP Revision Notes, [[erp_revision_cross_cutting]]),
            // pola identik IRF/PRF.
            $header = $this->model->get_erf_header_by_id((int) $id);
            if ($header) {
                // Hanya HOD departemen ERF ini (atau pemegang ALL DEPT) — tahap 3.
                $recipients = users_in_dept_scope($this->users_with_permission('erf_approval', 'can_head_dept'), 'erf_approval', 'can_head_dept', $this->_doc_dept($header));
                $this->notify_users(
                    $recipients,
                    'Reimburse Request Baru Menunggu Approval',
                    'Reimburse Request ' . $header->erf_number . ' dari ' . ($header->requester_name ?: '-') . ' menunggu approval Anda (Head of Dept).',
                    'erf/detail/' . $id,
                    'info', 'fa-receipt',
                    'erf', (int) $id
                );
            }
        }
        redirect('erf/detail/' . (int)$id);
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi terbuka setelah return/reject). Ditutup lagi begitu
     * requestor submit ulang (status berubah dari Draft).
     */
    public function reply($id) {
        $id = (int) $id;
        $header = $this->model->get_erf_header_by_id($id);
        if (!$header) show_404();

        $user_id = $this->session->userdata('user_id');

        // Cuma requestor ERF & approver yang mengembalikannya, dan cuma selama
        // ERF ini masih Draft hasil Back to Draft (bukan draft baru).
        if (!can_reply_return_thread($header)) {
            $this->session->set_flashdata('error', 'Balasan cuma bisa dikirim oleh pembuat Reimburse Request & approver yang mengembalikannya, selagi Reimburse Request masih Draft hasil Back to Draft.');
            redirect('erf/detail/' . $id);
            return;
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            $this->session->set_flashdata('error', 'Pesan balasan tidak boleh kosong.');
            redirect('erf/detail/' . $id);
            return;
        }

        $this->model->add_reply($id, $user_id, $message);
        $this->session->set_flashdata('success', 'Balasan terkirim.');
        redirect('erf/detail/' . $id . '#riwayat-approval');
    }

    public function approve_hod($id) {
        if (!check_access('erf_approval', 'head_dept')) show_error('Tidak ada izin approve HOD.', 403);
        $this->_require_approval_scope($id, 'head_dept');
        $res = $this->model->approve_hod((int)$id, $this->input->post('notes') ?? '');
        // Approval 1 tahap (2026-11-06): HOD = approval final -> siap dibayar.
        $this->_flash($res, 'Reimburse Request disetujui (approval 1 tahap). Siap dibayar.');
        if (!empty($res['success'])) { $this->_notify_erf_approved((int)$id); }
        redirect('erf/detail/' . (int)$id);
    }

    public function approve_endorsement($id) {
        if (!check_access('erf_approval', 'endorsement')) show_error('Tidak ada izin endorsement.', 403);
        $this->_require_approval_scope($id, 'endorsement');
        $res = $this->model->approve_endorsement((int)$id, $this->input->post('notes') ?? '');
        $this->_flash($res, 'Reimburse Request di-endorse.');
        if (!empty($res['success'])) { $this->_notify_erf_next_stage((int)$id, 'can_approval', 'Final Approval'); }
        redirect('erf/detail/' . (int)$id);
    }

    public function approve_final($id) {
        if (!check_access('erf_approval', 'approval')) show_error('Tidak ada izin approve final.', 403);
        $this->_require_approval_scope($id, 'approval');
        $res = $this->model->approve_final((int)$id, $this->input->post('notes') ?? '');
        $this->_flash($res, 'Reimburse Request di-approve final. Siap dibayar.');
        if (!empty($res['success'])) { $this->_notify_erf_approved((int)$id); }
        redirect('erf/detail/' . (int)$id);
    }

    /**
     * Helper kecil (pola sama Consumable_request/Prf) — notifikasi
     * "Waiting Approval" ke user yang berhak approve stage berikutnya.
     */
    /** Notifikasi "Approved" -> pemohon. */
    private function _notify_erf_approved($id) {
        $header = $this->model->get_erf_header_by_id((int) $id);
        if (!$header) return;
        $this->notify_users(
            [(int) $header->requester_id],
            'Reimburse Request Disetujui',
            'Reimburse Request ' . $header->erf_number . ' Anda telah disetujui, siap diproses reimbursement.',
            'erf/detail/' . $id,
            'success', 'fa-check-circle',
            'erf', (int) $id
        );
    }

    private function _notify_erf_next_stage($id, $permission_column, $stage_label) {
        $header = $this->model->get_erf_header_by_id($id);
        if (!$header) return;
        $recipients = users_in_dept_scope($this->users_with_permission('erf_approval', $permission_column), 'erf_approval', $permission_column, $this->_doc_dept($header));
        $this->notify_users(
            $recipients,
            'Reimburse Request Menunggu Approval',
            'Reimburse Request ' . $header->erf_number . ' menunggu approval Anda (' . $stage_label . ').',
            'erf/detail/' . $id,
            'info', 'fa-receipt',
            'erf', $id
        );
    }

    public function return_to_draft($id) {
        $can = check_access('erf_approval', 'head_dept') || check_access('erf_approval', 'endorsement') || check_access('erf_approval', 'approval');
        if (!$can) show_error('Tidak ada izin return.', 403);
        $this->_require_approval_scope($id);

        $reason = trim($this->input->post('return_reason') ?? '');
        if (!$reason) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            redirect('erf/detail/' . (int)$id); return;
        }
        $res = $this->model->return_to_draft((int)$id, $reason);
        $this->_flash($res, 'Reimburse Request dikembalikan ke Draft.');
        if (!empty($res['success'])) {
            // Notifikasi "Back to Draft" -> requester.
            $header = $this->model->get_erf_header_by_id((int) $id);
            if ($header) {
                $this->notify_users(
                    [(int) $header->requester_id],
                    'Reimburse Request Dikembalikan ke Draft',
                    'Reimburse Request ' . $header->erf_number . ' dikembalikan utk direvisi: "' . $reason . '"',
                    'erf/detail/' . $id,
                    'warning', 'fa-undo',
                    'erf', (int) $id
                );
            }
        }
        redirect('erf/detail/' . (int)$id);
    }

    public function reject($id) {
        $can = check_access('erf_approval', 'head_dept') || check_access('erf_approval', 'endorsement') || check_access('erf_approval', 'approval');
        if (!$can) show_error('Tidak ada izin reject.', 403);
        $this->_require_approval_scope($id);

        $reason = trim($this->input->post('reject_reason') ?? '');
        if (!$reason) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            redirect('erf/detail/' . (int)$id); return;
        }
        $res = $this->model->reject((int)$id, $reason);
        $this->_flash($res, 'Reimburse Request ditolak.');
        redirect('erf/detail/' . (int)$id);
    }

    // ============================================================
    // PAYMENT
    // ============================================================

    public function record_payment($id) {
        if (!check_access('erf_payment', 'add')) show_error('Tidak ada izin record payment.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        // Upload bukti bayar (opsional, satu file). Folder terpisah dari attachment ERF.
        $proof_path = null;
        if (!empty($_FILES['payment_proof']['name'])) {
            $upload_dir = FCPATH . 'uploads/erf_payment_proofs/';
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
            $config = [
                'upload_path'   => $upload_dir,
                'allowed_types' => 'pdf|jpg|jpeg|png',
                'max_size'      => 10240,
                'encrypt_name'  => true,
            ];
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('payment_proof')) {
                $this->session->set_flashdata('error', 'Bukti bayar gagal diupload: ' . strip_tags($this->upload->display_errors()));
                redirect('erf/detail/' . $id); return;
            }
            $up = $this->upload->data();
            $proof_path = 'uploads/erf_payment_proofs/' . $up['file_name'];
        }

        $payment_data = [
            'payment_date'       => $this->input->post('payment_date') ?: date('Y-m-d'),
            'payment_amount'     => (float)str_replace(',', '', $this->input->post('payment_amount') ?? 0),
            'transfer_fee'       => (float)str_replace(',', '', $this->input->post('transfer_fee') ?? 0),
            'payment_method'     => $this->input->post('payment_method'),
            'reference_number'   => $this->input->post('reference_number'),
            'notes'              => $this->input->post('notes'),
            'payment_proof_path' => $proof_path,
        ];
        if ($payment_data['payment_amount'] <= 0) {
            $this->session->set_flashdata('error', 'Amount wajib diisi.');
            redirect('erf/detail/' . $id); return;
        }

        $res = $this->model->record_payment($id, $payment_data);
        $this->_flash($res, 'Payment Reimburse Request berhasil dicatat.');
        if (!empty($res['success'])) {
            // Notifikasi "Reimbursement Process" -> requester (ERP Revision Notes,
            // [[erp_revision_cross_cutting]]).
            $header = $this->model->get_erf_header_by_id($id);
            if ($header) {
                $this->notify_users(
                    [(int) $header->requester_id],
                    'Reimburse Request Direimburse',
                    'Reimburse Request ' . $header->erf_number . ' telah dibayar (Rp ' . number_format((float) $payment_data['payment_amount'], 0, ',', '.') . ').',
                    'erf/detail/' . $id,
                    'success', 'fa-money-check-alt',
                    'erf', $id
                );
            }
        }
        redirect('erf/detail/' . $id);
    }

    /**
     * Stream bukti bayar (inline preview). Akses: viewer ERF / approver / finance.
     */
    public function payment_proof($payment_id) {
        $can = check_access('erf', 'view')
            || check_access('erf_approval', 'view')
            || check_access('erf_approval', 'head_dept')
            || check_access('erf_approval', 'endorsement')
            || check_access('erf_approval', 'approval')
            || check_access('erf_payment', 'view');
        if (!$can) show_error('Tidak ada izin.', 403);

        $pay = $this->model->get_payment_by_id((int)$payment_id);
        if (!$pay || empty($pay->payment_proof_path)) show_404();

        $abs = FCPATH . $pay->payment_proof_path;
        if (!file_exists($abs)) show_404();

        $mime = mime_content_type($abs);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($pay->payment_proof_path) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }

    /**
     * Upload / replace bukti bayar untuk 1 row erf_payment_tracking existing.
     * Dipakai kalau finance lupa attach bukti saat record_payment.
     */
    public function upload_payment_proof($payment_id) {
        if (!check_access('erf_payment', 'add')) show_error('Tidak ada izin.', 403);
        $pay = $this->model->get_payment_by_id((int)$payment_id);
        if (!$pay) show_404();

        if (empty($_FILES['payment_proof']['name'])) {
            $this->session->set_flashdata('error', 'Tidak ada file yang dipilih.');
            redirect('erf/detail/' . $pay->erf_header_id); return;
        }

        $upload_dir = FCPATH . 'uploads/erf_payment_proofs/';
        if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
        $config = [
            'upload_path'   => $upload_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        ];
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('payment_proof')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . strip_tags($this->upload->display_errors()));
            redirect('erf/detail/' . $pay->erf_header_id); return;
        }

        $up = $this->upload->data();
        $new_path = 'uploads/erf_payment_proofs/' . $up['file_name'];

        // Hapus file lama supaya tidak menumpuk.
        if (!empty($pay->payment_proof_path)) {
            $old_abs = FCPATH . $pay->payment_proof_path;
            if (file_exists($old_abs)) @unlink($old_abs);
        }

        $this->model->update_payment_proof((int)$payment_id, $new_path);
        $this->session->set_flashdata('success', 'Bukti bayar berhasil diupload.');
        redirect('erf/detail/' . $pay->erf_header_id);
    }

    // ============================================================
    // ATTACHMENTS
    // ============================================================

    public function upload_attachment($id) {
        if (!check_access('erf', 'edit')) show_error('Tidak ada izin upload.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        $upload_path = FCPATH . 'uploads/erf_attachments/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('attachment')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('erf/detail/' . $id); return;
        }

        $f = $this->upload->data();
        $this->model->add_attachment([
            'erf_header_id' => $id,
            'file_name'     => $f['file_name'],
            'original_name' => $f['orig_name'],
            'file_path'     => 'uploads/erf_attachments/' . $f['file_name'],
            'file_type'     => $f['file_type'],
            'file_size'     => $f['file_size'] * 1024, // KB to bytes (CI raw is in kb)
            'uploaded_by'   => $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Attachment berhasil di-upload.');
        redirect('erf/detail/' . $id);
    }

    public function delete_attachment($attachment_id) {
        if (!check_access('erf', 'edit')) show_error('Tidak ada izin.', 403);
        $att = $this->model->get_attachment((int)$attachment_id);
        if (!$att) show_404();

        $full = FCPATH . $att->file_path;
        if (is_file($full)) @unlink($full);

        $this->model->delete_attachment((int)$attachment_id);
        $this->session->set_flashdata('success', 'Attachment dihapus.');
        redirect('erf/detail/' . $att->erf_header_id);
    }

    public function attachment($attachment_id) {
        $att = $this->model->get_attachment((int)$attachment_id);
        if (!$att) show_404();

        $full = FCPATH . $att->file_path;
        if (!is_file($full)) show_404();

        header('Content-Type: ' . $att->file_type);
        header('Content-Disposition: inline; filename="' . $att->original_name . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }

    // ============================================================
    // EXPORT — PDF / EXCEL
    // ============================================================

    public function export_pdf($id) {
        if (!check_access('erf', 'view')) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        $header = $this->model->get_erf_header_by_id($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('erf', 'erf_approval', $this->_doc_dept($header), (int) $header->requester_id === (int) $this->session->userdata('user_id'), check_access('erf_payment', 'view'))) {
            show_error('Reimburse Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $details = $this->model->get_erf_details_by_header_id($id);
        $department_name = $this->model->resolve_department_name($header->department_id);

        // Department CODE (mis. "ADM") — diambil dari common_code group_code='Department'.
        // Kalau header->department_id kosong / tidak ketemu, fallback ke departemen requester.
        $department_code = $this->model->resolve_department_code($header->department_id);
        if (empty($department_code)) {
            $user_dept = $this->model->get_user_department_code($header->requester_id);
            if (!empty($user_dept)) $department_code = $user_dept;
        }

        // Tanda tangan (2026-11-06, approval 1 tahap): Pemohon, Disetujui (approver final =
        // Head of Dept), Dibayar (terisi otomatis dari Record Payment terakhir).
        $sig_requester = $this->_sig_slot($header->requester_id, $header->erf_date);
        $sig_approved  = $this->_sig_slot($header->final_approved_by, $header->final_approved_at);
        $payer         = $this->model->get_payment_signer($id);
        $sig_paid      = $payer ? $this->_sig_slot($payer->created_by, $payer->payment_date) : $this->_sig_slot(null, null);

        // Cap "COMPLETED" (assets/img/stamp.png) -- muncul cuma kalau ERF ini
        // sudah dibayar (record_payment() set payment_status+status jadi
        // 'Paid' sekaligus, lihat Erf_model::record_payment()).
        $payment_stamp_html = (($header->payment_status ?? null) === 'Paid')
            ? render_payment_stamp_html()
            : '';

        $html = $this->load->view('erf/pdf_template', [
            'header'              => $header,
            'details'             => $details,
            'department_name'     => $department_name,
            'department_code'     => $department_code,
            'sig_requester'       => $sig_requester,
            'sig_approved'        => $sig_approved,
            'sig_paid'            => $sig_paid,
            'payment_stamp_html'  => $payment_stamp_html,
        ], true);

        try {
            $temp_dir = APPPATH . 'cache/pdf_temp/';
            if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }

            // Header/footer letterhead identitas perusahaan REPEATING di
            // tiap halaman (user 2026-09-06) via Mpdf native
            // SetHTMLHeader()/SetHTMLFooter() -- BUKAN CSS position:fixed spt
            // di template Dompdf (irf/pdf_template.php, pdf_po_letterhead.php)
            // krn dicoba & Mpdf tidak resolve nested position:absolute dgn
            // benar (lihat [[payment_completed_stamp]] utk histori bug
            // serupa). margin_top/margin_bottom Mpdf dlm MM, sedangkan
            // render_pdf_letterhead_header_margin()/footer_margin() dlm PX
            // (konsisten dgn sisi Dompdf) -- WAJIB dikonversi.
            $letterhead_header = render_pdf_letterhead_header_html(50, 24);
            $letterhead_footer = render_pdf_letterhead_footer_html();
            $px2mm = 25.4 / 96;
            $margin_top    = round(render_pdf_letterhead_header_margin(50, 24) * $px2mm, 1);
            $margin_bottom = round(render_pdf_letterhead_footer_margin() * $px2mm, 1);
            $margin_lr     = round(25 * $px2mm, 1);

            // Setelan Developer Mode (2026-09-10) — lihat catatan sama di Prf.php.
            if (function_exists('pdf_layout_margins')) {
                $_m = pdf_layout_margins('erf', $margin_top, $margin_bottom);
                $margin_top    = $_m['margin_top'];
                $margin_bottom = $_m['margin_bottom'];
                $margin_lr     = $_m['margin_left'];
            }

            // margin_left/right JUGA WAJIB lewat sini, bukan @page CSS di
            // template -- dicoba taruh margin-left/right di @page (biarin
            // top/bottom kosong biar "diwarisi" dari constructor), ternyata
            // Mpdf tetap reset margin_top/bottom ke default bawaan begitu
            // ada rule @page apa pun, bikin header/footer gagal tampil sama
            // sekali (dites nyata, bukan asumsi).
            $mpdf = new Mpdf([
                'tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4',
                'margin_header' => 0, 'margin_footer' => 0,
                'margin_top' => $margin_top, 'margin_bottom' => $margin_bottom,
                'margin_left' => $margin_lr, 'margin_right' => $margin_lr,
            ]);
            $mpdf->SetHTMLHeader($letterhead_header);
            $mpdf->SetHTMLFooter($letterhead_footer);
            $mpdf->WriteHTML($html);

            // Append attachments.
            //
            // Halaman lampiran dibuka lewat mpdf_add_attachment_page() supaya
            // letterhead repeating tidak menimpa isi scan (user 2026-09-08),
            // sama dengan Prf.php::export_pdf().
            $attachments = $this->model->get_attachments($id);
            foreach ($attachments as $att) {
                $abs = FCPATH . $att->file_path;
                if (!file_exists($abs)) continue;

                if (strpos($att->file_type, 'image') !== false) {
                    $mpdf->SetMargins(0, 0, 0);
                    $mpdf->SetAutoPageBreak(false, 0);
                    mpdf_add_attachment_page($mpdf, 'P');
                    $info = @getimagesize($abs);
                    $iw = $info ? $info[0] : 0;
                    $ih = $info ? $info[1] : 0;
                    $pw = $mpdf->w; $ph = $mpdf->h;
                    $margin = 5; $availW = $pw - 2*$margin; $availH = $ph - 2*$margin;
                    if ($iw > 0 && $ih > 0) {
                        $ratio = $iw / $ih;
                        if (($availW / $availH) > $ratio) { $imgH = $availH; $imgW = $availH * $ratio; }
                        else                              { $imgW = $availW; $imgH = $availW / $ratio; }
                    } else { $imgW = $availW; $imgH = $availH; }
                    $posX = ($pw - $imgW) / 2; $posY = ($ph - $imgH) / 2;
                    $mpdf->WriteHTML('<div style="position:fixed;top:' . round($posY,2) . 'mm;left:' . round($posX,2) . 'mm;width:' . round($imgW,2) . 'mm;height:' . round($imgH,2) . 'mm;"><img src="' . htmlspecialchars($abs) . '" style="width:100%;height:100%;"></div>');
                } elseif (strpos($att->file_type, 'pdf') !== false) {
                    try {
                        $pageCount = $mpdf->setSourceFile($abs);
                        for ($pn = 1; $pn <= $pageCount; $pn++) {
                            $tplId = $mpdf->importPage($pn);
                            $tplSize = $mpdf->getTemplateSize($tplId);
                            $mpdf->SetMargins(0, 0, 0);
                            $mpdf->SetAutoPageBreak(false, 0);
                            mpdf_add_attachment_page($mpdf, 'P');
                            $pw = $mpdf->w; $ph = $mpdf->h;
                            $scale = min($pw / $tplSize['width'], $ph / $tplSize['height']);
                            $fw = $tplSize['width']  * $scale;
                            $fh = $tplSize['height'] * $scale;
                            $ox = ($pw - $fw) / 2; $oy = ($ph - $fh) / 2;
                            $mpdf->useTemplate($tplId, $ox, $oy, $fw, $fh);
                        }
                    } catch (\Exception $e) {
                        $mpdf->SetMargins(15, 15, 15);
                        $mpdf->SetAutoPageBreak(true, 15);
                        mpdf_add_attachment_page($mpdf, 'P');
                        $mpdf->WriteHTML('<p style="color:#a00;">Tidak dapat menampilkan lampiran PDF: ' . htmlspecialchars($att->original_name) . '</p>');
                    }
                }
            }

            $mpdf->Output('ERF_' . str_replace(['/','\\'], '-', $header->erf_number) . '.pdf', 'I');
            exit;
        } catch (\Exception $e) {
            show_error('Gagal generate PDF: ' . $e->getMessage());
        }
    }

    public function export_excel($id) {
        if (!check_access('erf', 'view')) show_error('Tidak ada izin.', 403);
        $id = (int)$id; if ($id <= 0) show_404();

        $header  = $this->model->get_erf_header_by_id($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('erf', 'erf_approval', $this->_doc_dept($header), (int) $header->requester_id === (int) $this->session->userdata('user_id'), check_access('erf_payment', 'view'))) {
            show_error('Reimburse Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }
        $details = $this->model->get_erf_details_by_header_id($id);
        $department_name = $this->model->resolve_department_name($header->department_id);

        // Sama dengan PDF: pakai CODE dari common_code dengan fallback ke departemen requester.
        $department_code = $this->model->resolve_department_code($header->department_id);
        if (empty($department_code)) {
            $user_dept = $this->model->get_user_department_code($header->requester_id);
            if (!empty($user_dept)) $department_code = $user_dept;
        }
        if (empty($department_code)) $department_code = $department_name; // last resort

        // Nomor ditampilkan utuh (mis. RR/ACRO/26110001): sejak format nomor Acropoly
        // (config/doc_number.php, 2026-11-05) nomor tidak lagi memuat kode departemen.

        $date_yy = date('Y', strtotime($header->erf_date));
        $date_mm = date('m', strtotime($header->erf_date));
        $date_dd = date('d', strtotime($header->erf_date));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reimburse Request');

        // Page setup: landscape A4 with narrow margins, fit to one page
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(1);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.3)->setLeft(0.3)->setRight(0.3);

        // Column widths (A..I = 9 columns)
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(8);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(20);

        // Header: Company info (A1:F4) and ERF number (G1:I2)
        $sheet->setCellValue('A1', company_info('company_name'));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Alamat & telepon dari Developer Mode (dulu alamat NSI ditulis mentah).
        $contact = company_contact_lines(3);
        foreach ([2, 3, 4] as $i => $row) {
            $sheet->setCellValue('A' . $row, $contact[$i] ?? '');
            $sheet->mergeCells('A' . $row . ':F' . $row);
        }
        $sheet->getStyle('A2:A4')->getFont()->setSize(10);

        // ERF number top right
        $sheet->mergeCells('G2:I2');
        $rich = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $rich->createText('Reimburse Request :  ');
        $rich->createTextRun($header->erf_number)->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValue('G2', $rich);
        $sheet->getStyle('G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Title
        $sheet->mergeCells('A6:I6');
        $sheet->setCellValue('A6', 'REIMBURSE REQUEST');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtitle
        $sheet->mergeCells('A7:I7');
        $sheet->setCellValue('A7', '[費用報銷單]');
        $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // DATE row (row 9)
        $sheet->setCellValue('A9', 'DATE [日期]');
        $sheet->mergeCells('A9:B9');
        $sheet->getStyle('A9')->getFont()->setBold(true);
        $sheet->setCellValue('C9', ':');
        $sheet->setCellValueExplicit('D9', $date_yy, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E9', 'YY [年]');
        $sheet->setCellValueExplicit('F9', $date_mm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('G9', 'MM [月]');
        $sheet->setCellValueExplicit('H9', $date_dd, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('I9', 'DD [日]');
        $sheet->getStyle('D9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D9')->getFont()->setBold(true);
        $sheet->getStyle('F9')->getFont()->setBold(true);
        $sheet->getStyle('H9')->getFont()->setBold(true);
        $sheet->getStyle('E9')->getFont()->setBold(true);
        $sheet->getStyle('G9')->getFont()->setBold(true);
        $sheet->getStyle('I9')->getFont()->setBold(true);
        // Borders for date boxes
        foreach (['D9','F9','H9'] as $box) {
            $sheet->getStyle($box)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // DEPARTMENT row (row 10)
        $sheet->setCellValue('A10', 'DEPARTMENT [部門]');
        $sheet->mergeCells('A10:B10');
        $sheet->getStyle('A10')->getFont()->setBold(true);
        $sheet->setCellValue('C10', ':');
        $sheet->mergeCells('D10:I10');
        $sheet->setCellValue('D10', $department_code ?: '');
        $sheet->getStyle('D10:I10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // NAME row (row 11)
        $sheet->setCellValue('A11', 'NAME [姓名]');
        $sheet->mergeCells('A11:B11');
        $sheet->getStyle('A11')->getFont()->setBold(true);
        $sheet->setCellValue('C11', ':');
        $sheet->mergeCells('D11:I11');
        $sheet->setCellValue('D11', $header->requester_name);
        $sheet->getStyle('D11:I11')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // PENERIMA / BANK / ACCOUNT rows (12-14) — snapshot dari master karyawan saat ERF dibuat.
        $info_rows = [
            12 => ['PENERIMA [收款人]', $header->karyawan_name ?? ''],
            13 => ['BANK [銀行]',       $header->bank_name ?? ''],
            14 => ['ACCOUNT [賬號]',    $header->bank_account ?? ''],
        ];
        foreach ($info_rows as $r => $info) {
            $sheet->setCellValue('A' . $r, $info[0]);
            $sheet->mergeCells('A' . $r . ':B' . $r);
            $sheet->getStyle('A' . $r)->getFont()->setBold(true);
            $sheet->setCellValue('C' . $r, ':');
            $sheet->mergeCells('D' . $r . ':I' . $r);
            $sheet->setCellValueExplicit('D' . $r, (string) $info[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyle('D' . $r . ':I' . $r)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }

        // Dashed line separator (row 15 - styled)
        $sheet->getStyle('A15:I15')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DASHED);

        // Items table header (row 16)
        $startRow = 16;
        $header_row = $startRow;
        $sheet->setCellValue('A' . $header_row, "NO\n[號]");
        $sheet->mergeCells('B' . $header_row . ':E' . $header_row);
        $sheet->setCellValue('B' . $header_row, "DESCRIPTION\n[描述]");
        $sheet->mergeCells('F' . $header_row . ':G' . $header_row);
        $sheet->setCellValue('F' . $header_row, "EXPENSE DATE\n[日期]");
        $sheet->setCellValue('H' . $header_row, "UNIT PRICE\n[單價]");
        $sheet->setCellValue('I' . $header_row, "AMOUNT\n[金額]");

        $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle('A' . $header_row . ':I' . $header_row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension($header_row)->setRowHeight(30);

        // Items rows
        $min_rows = 10;
        $start_row = $header_row + 1;
        $no = 1;
        $idx = 0;
        $grand = 0;

        foreach ($details as $detail) {
            $r = $start_row + $idx;
            $sub = (float)$detail->qty * (float)$detail->unit_price;
            $grand += $sub;
            $sheet->setCellValue('A' . $r, $no);
            $sheet->mergeCells('B' . $r . ':E' . $r);
            $sheet->setCellValue('B' . $r, $detail->description);
            $sheet->mergeCells('F' . $r . ':G' . $r);
            $sheet->setCellValue('F' . $r, $detail->expense_date ? date('d M Y', strtotime($detail->expense_date)) : '-');
            $sheet->setCellValue('H' . $r, (float)$detail->unit_price);
            $sheet->setCellValue('I' . $r, $sub);
            $no++;
            $idx++;
        }

        for ($i = $idx; $i < $min_rows; $i++) {
            $r = $start_row + $i;
            $sheet->mergeCells('B' . $r . ':E' . $r);
            $sheet->mergeCells('F' . $r . ':G' . $r);
        }

        $last_item_row = $start_row + $min_rows - 1;

        // Border all item rows
        $sheet->getStyle('A' . $start_row . ':I' . $last_item_row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A' . $start_row . ':A' . $last_item_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $start_row . ':F' . $last_item_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $start_row . ':I' . $last_item_row)->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
        $sheet->getStyle('H' . $start_row . ':I' . $last_item_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Larger font for description column
        $sheet->getStyle('B' . $start_row . ':E' . $last_item_row)->getFont()->setSize(14);
        $sheet->getStyle('B' . $start_row . ':E' . $last_item_row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        for ($r = $start_row; $r <= $last_item_row; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(28);
        }

        // TOTAL row
        $total_row = $last_item_row + 1;
        $sheet->mergeCells('A' . $total_row . ':G' . $total_row);
        $sheet->setCellValue('H' . $total_row, 'TOTAL [總]');
        $sheet->setCellValue('I' . $total_row, (float)$grand);
        $sheet->getStyle('H' . $total_row . ':I' . $total_row)->getFont()->setBold(true);
        $sheet->getStyle('H' . $total_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $total_row)->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
        $sheet->getStyle('I' . $total_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H' . $total_row . ':I' . $total_row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Signature row
        $sig_title_row = $total_row + 2;
        $sig_box_row   = $sig_title_row + 1;
        $sig_name_row  = $sig_box_row + 1;
        $sig_date_row  = $sig_name_row + 1;

        $fmtDate = function ($d) {
            return !empty($d) ? date('d/m/Y', strtotime($d)) : '___/___/___';
        };

        // Get signature data
        $sig_requester_name = $header->requester_name ?: '-';
        $sig_approved_name = $header->final_approved_by ? ($this->db->select('fullname')->where('id', $header->final_approved_by)->get('users')->row()->fullname ?? '-') : '-';
        $payer = $this->model->get_payment_signer($header->id);

        // 3 kolom signature (2026-11-06): Pemohon, Disetujui, Dibayar (otomatis dari Record Payment).
        $sig_cols = [
            ['A', 'B', "Pemohon\n[申請人]",   $sig_requester_name,                       $fmtDate($header->erf_date)],
            ['C', 'F', "Disetujui\n[核准人]", $sig_approved_name,                        $fmtDate($header->final_approved_at)],
            ['G', 'I', "Dibayar\n[付款人]",   $payer ? ($payer->fullname ?: '-') : '-', $fmtDate($payer->payment_date ?? null)],
        ];

        $sheet->getRowDimension($sig_box_row)->setRowHeight(55);

        foreach ($sig_cols as $i => $sc) {
            list($cs, $ce, $title, $name, $sigDate) = $sc;
            $titleRange = $cs . $sig_title_row . ':' . $ce . $sig_title_row;
            $boxRange   = $cs . $sig_box_row . ':' . $ce . $sig_box_row;
            $nameRange  = $cs . $sig_name_row . ':' . $ce . $sig_name_row;
            $dateRange  = $cs . $sig_date_row . ':' . $ce . $sig_date_row;
            if ($cs !== $ce) {
                $sheet->mergeCells($titleRange);
                $sheet->mergeCells($boxRange);
                $sheet->mergeCells($nameRange);
                $sheet->mergeCells($dateRange);
            }
            $sheet->setCellValue($cs . $sig_title_row, $title);
            $sheet->getStyle($cs . $sig_title_row)->getFont()->setBold(true);
            $sheet->getStyle($cs . $sig_title_row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle($cs . $sig_title_row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');

            $sheet->setCellValue($cs . $sig_name_row, $name);
            $sheet->getStyle($cs . $sig_name_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue($cs . $sig_date_row, 'Date: ' . $sigDate);
            $sheet->getStyle($cs . $sig_date_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Full border around entire signature block
        $sheet->getStyle('A' . $sig_title_row . ':I' . $sig_date_row)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $filename = 'ERF_' . str_replace(['/','\\'], '-', $header->erf_number) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Resolve signature slot: name + signature_html (stempel siap pakai dari
     * render_signature_stamp_html(), document_pdf_helper.php autoload) +
     * date. Dulu cuma nyimpen `path` mentah (template yg bikin <img>-nya
     * sendiri) -- diganti `signature_html` krn isinya bisa beda-beda bentuk.
     * $with_frame=false: ttd personal tampil POLOS tanpa frame ttd2.png
     * (user 2026-09-09, khusus ERF & PRF); yang belum upload ttd tetap dapat
     * stempel generik ttd.png.
     */
    private function _sig_slot($user_id, $date_raw) {
        $slot = ['name' => '-', 'signature_html' => '', 'date' => $date_raw];
        if (!$user_id) return $slot;
        $u = $this->db->select('fullname, signature_path')->where('id', $user_id)->get('users')->row();
        if ($u) {
            $slot['name'] = $u->fullname;
            $slot['signature_html'] = render_signature_stamp_html($u->signature_path, 80, false);
        }
        return $slot;
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function _status_list() {
        return ['Draft','Submitted','Approved Head Dept','Endorsed','Approved','Paid','Cancelled','Rejected'];
    }

    private function _flash($res, $success_message) {
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', $success_message);
        } else {
            $this->session->set_flashdata('error', $res['message'] ?? 'Aksi gagal.');
        }
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
