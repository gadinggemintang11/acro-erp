<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ERF (Expense Reimbursement Form) — model
 * Mirror Prf_model + 3-stage approval (HOD → Endorsement → Approval).
 * Final approved status = 'Approved'. Setelah final, baru bisa di-`record_payment`.
 */
class Erf_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    

    // ============================================================
    // NUMBER GEN + DEPARTMENT RESOLVERS
    // ============================================================

    public function generate_erf_number($department_code = null) {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        // $department_code tidak dipakai lagi: nomor tidak memuat kode departemen.
        return doc_number_next('reimburse_request', 'erf_header', 'erf_number');
    }

    public function resolve_department_code($value) {
        if (empty($value)) return null;
        $cc = $this->db->select('code')->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('code', $value)
                ->or_where('description', $value)
                ->or_where('id', is_numeric($value) ? (int)$value : 0)
            ->group_end()
            ->get()->row();
        return $cc ? $cc->code : (is_string($value) ? $value : null);
    }

    public function resolve_department_name($value) {
        if (empty($value)) return null;
        $cc = $this->db->select('description')->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('code', $value)
                ->or_where('id', is_numeric($value) ? (int)$value : 0)
            ->group_end()
            ->get()->row();
        return $cc ? $cc->description : $value;
    }

    public function get_user_department_code($user_id) {
        if (!$user_id) return null;
        $u = $this->db->select('departemen')->where('id', $user_id)->get('users')->row();
        return $u ? $this->resolve_department_code($u->departemen) : null;
    }

    public function get_user_department_name($user_id) {
        if (!$user_id) return null;
        $u = $this->db->select('departemen')->where('id', $user_id)->get('users')->row();
        return $u ? $this->resolve_department_name($u->departemen) : null;
    }

    // ============================================================
    // QUERIES
    // ============================================================

    public function get_all_erf_headers($filters = []) {
        $this->db->reset_query();
        $this->db->select('eh.*, u.fullname as requester_name', FALSE);
        $this->db->from('erf_header eh');
        $this->db->join('users u', 'eh.requester_id = u.id', 'left');

        if (!empty($filters['date_from']))      $this->db->where('eh.erf_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))        $this->db->where('eh.erf_date <=', $filters['date_to']);
        if (!empty($filters['status']))         $this->db->where('eh.status', $filters['status']);
        if (!empty($filters['payment_status'])) $this->db->where('eh.payment_status', $filters['payment_status']);
        if (!empty($filters['department_id']))  $this->db->where('eh.department_id', $filters['department_id']);
        if (!empty($filters['requester_id']))   $this->db->where('eh.requester_id', (int)$filters['requester_id']);
        if (!empty($filters['scope_sql']))      $this->db->where('(' . $filters['scope_sql'] . ')', null, false);
        if (!empty($filters['search'])) {
            $kw = $filters['search'];
            $this->db->group_start()
                ->like('eh.erf_number', $kw)
                ->or_like('u.fullname', $kw)
                ->or_like('eh.notes', $kw)
            ->group_end();
        }

        $this->db->order_by('eh.erf_date', 'DESC');
        $this->db->order_by('eh.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Pending approvals filtered by user's permission stage.
     */
    /** Status yang ditangani tiap tahap approval (dipakai pembatasan departemen). */
    const APPROVAL_STAGE_MAP = [
        'head_dept'   => ['Submitted'],
        'endorsement' => ['Approved Head Dept'],
        'approval'    => ['Endorsed'],
    ];

    public function get_pending_approvals($can_hod, $can_endorsement, $can_approval) {
        $stages = [];
        if ($can_hod)         $stages[] = 'Submitted';
        if ($can_endorsement) $stages[] = 'Approved Head Dept';
        if ($can_approval)    $stages[] = 'Endorsed';
        if (empty($stages)) return [];

        // Tiap tahap hanya departemen dalam cakupan user (tahap 3). Dihitung
        // sebelum builder dirakit (helper menjalankan query sendiri).
        $scope_sql = approval_stage_scope_sql('erf_approval', 'eh.status', 'eh.department_code', self::APPROVAL_STAGE_MAP);

        $this->db->select('eh.*, u.fullname as requester_name', FALSE);
        $this->db->from('erf_header eh');
        $this->db->join('users u', 'eh.requester_id = u.id', 'left');
        $this->db->where_in('eh.status', $stages);
        $this->db->where($scope_sql, null, false);
        $this->db->order_by('eh.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_erf_payment_queue() {
        $this->db->select('eh.*, u.fullname as requester_name', FALSE);
        $this->db->from('erf_header eh');
        $this->db->join('users u', 'eh.requester_id = u.id', 'left');
        $this->db->where('eh.status', 'Approved');
        $this->db->where('eh.payment_status !=', 'Paid');
        $this->db->order_by('eh.erf_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_erf_payment_history($filters = []) {
        $this->db->select('eh.*, u.fullname as requester_name, pay.fullname as paid_by_name', FALSE);
        $this->db->from('erf_header eh');
        $this->db->join('users u', 'eh.requester_id = u.id', 'left');
        $this->db->join('users pay', 'eh.updated_by = pay.id', 'left');
        $this->db->where('eh.payment_status', 'Paid');

        if (!empty($filters['date_from'])) $this->db->where('eh.payment_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $this->db->where('eh.payment_date <=', $filters['date_to']);
        if (!empty($filters['search'])) {
            $kw = $filters['search'];
            $this->db->group_start()
                ->like('eh.erf_number', $kw)
                ->or_like('u.fullname', $kw)
            ->group_end();
        }
        $this->db->order_by('eh.payment_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_erf_header_by_id($id) {
        $this->db->select('eh.*, u.fullname as requester_name', FALSE);
        $this->db->from('erf_header eh');
        $this->db->join('users u', 'eh.requester_id = u.id', 'left');
        $this->db->where('eh.id', $id);
        return $this->db->get()->row();
    }

    public function get_erf_details_by_header_id($header_id) {
        return $this->db->where('erf_header_id', $header_id)
            ->order_by('id', 'ASC')->get('erf_details')->result();
    }

    public function get_approval_logs($erf_header_id) {
        $this->db->select('l.*, u.fullname as actor_full', FALSE);
        $this->db->from('erf_approval_logs l');
        $this->db->join('users u', 'l.actor_id = u.id', 'left');
        $this->db->where('l.erf_header_id', $erf_header_id);
        $this->db->order_by('l.created_at', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Single row payment tracking (untuk stream bukti & update bukti).
     */
    public function get_payment_by_id($payment_id) {
        return $this->db->where('id', $payment_id)->get('erf_payment_tracking')->row();
    }

    /**
     * Update bukti bayar di 1 payment row + mirror ke header kalau row tsb terbaru.
     */
    public function update_payment_proof($payment_id, $new_path) {
        $pay = $this->get_payment_by_id($payment_id);
        if (!$pay) return false;

        $this->db->where('id', $payment_id)
            ->update('erf_payment_tracking', ['payment_proof_path' => $new_path]);

        $latest = $this->db->select('id')
            ->where('erf_header_id', $pay->erf_header_id)
            ->order_by('id', 'DESC')->limit(1)
            ->get('erf_payment_tracking')->row();
        if ($latest && (int)$latest->id === (int)$payment_id) {
            $this->db->where('id', $pay->erf_header_id)
                ->update('erf_header', ['payment_proof_path' => $new_path]);
        }
        return true;
    }

    public function get_payment_tracking($erf_header_id) {
        $this->db->select('pt.*, u.fullname as created_by_name', FALSE);
        $this->db->from('erf_payment_tracking pt');
        $this->db->join('users u', 'pt.created_by = u.id', 'left');
        $this->db->where('pt.erf_header_id', $erf_header_id);
        $this->db->order_by('pt.payment_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Pencatat pembayaran terakhir -- isi kolom "Dibayar" di PDF/Excel.
     * null kalau Reimburse Request ini belum pernah di-Record Payment.
     */
    public function get_payment_signer($erf_header_id) {
        return $this->db->select('pt.created_by, pt.payment_date, u.fullname, u.signature_path')
            ->from('erf_payment_tracking pt')
            ->join('users u', 'u.id = pt.created_by', 'left')
            ->where('pt.erf_header_id', (int) $erf_header_id)
            ->order_by('pt.payment_date', 'DESC')->order_by('pt.id', 'DESC')
            ->limit(1)->get()->row();
    }

    /**
     * Lookup audit users (id => fullname) untuk render audit trail di view.
     */
    public function get_audit_users($header) {
        $ids = array_filter([
            $header->submitted_by ?? null,
            $header->hod_approved_by ?? null,
            $header->endorsed_by ?? null,
            $header->final_approved_by ?? null,
            $header->rejected_by ?? null,
            $header->returned_to_draft_by ?? null,
        ]);
        if (empty($ids)) return [];
        $users = $this->db->select('id, fullname')->where_in('id', $ids)->get('users')->result();
        $map = [];
        foreach ($users as $u) $map[$u->id] = $u->fullname;
        return $map;
    }

    // ============================================================
    // CREATE / UPDATE
    // ============================================================

    /**
     * Ambil snapshot data karyawan (penerima reimburse + bank) by karyawan_id.
     * Mengembalikan karyawan_id, karyawan_name, bank_name, bank_account.
     * Bila karyawan_id kosong / tidak ditemukan, semua nilai null.
     */
    private function _karyawan_snapshot($karyawan_id) {
        $snapshot = [
            'karyawan_id'   => null,
            'karyawan_name' => null,
            'bank_name'     => null,
            'bank_account'  => null,
        ];
        if (empty($karyawan_id)) return $snapshot;

        $kar = $this->db->select('id, nama_karyawan, bank_name, bank_account')
            ->where('id', (int) $karyawan_id)
            ->get('master_karyawan')->row();
        if ($kar) {
            $snapshot['karyawan_id']   = (int) $kar->id;
            $snapshot['karyawan_name'] = $kar->nama_karyawan;
            $snapshot['bank_name']     = $kar->bank_name;
            $snapshot['bank_account']  = $kar->bank_account;
        }
        return $snapshot;
    }

    public function create_erf($data, $details) {
        $this->db->trans_start();

        // Departemen dokumen dari form (divalidasi controller, tahap 3), fallback profil.
        $department_code = !empty($data['department_code'])
            ? $data['department_code']
            : $this->get_user_department_code($this->session->userdata('user_id'));
        $erf_number = $this->generate_erf_number($department_code);

        $subtotal = 0;
        foreach ($details as $d) $subtotal += ((float)$d['qty'] * (float)$d['unit_price']);

        $karyawan_snapshot = $this->_karyawan_snapshot($data['karyawan_id'] ?? null);

        $header = [
            'erf_number'     => $erf_number,
            'erf_date'       => $data['erf_date'],
            'department_id'  => !empty($department_code) ? $department_code : ($data['department_id'] ?? null),
            // department_id bertipe INT (kode teks jadi 0) — kolom teks inilah yang dipakai.
            'department_code' => !empty($department_code) ? $department_code : null,
            'requester_id'   => $this->session->userdata('user_id'),
            'karyawan_id'    => $karyawan_snapshot['karyawan_id'],
            'karyawan_name'  => $karyawan_snapshot['karyawan_name'],
            'bank_name'      => $karyawan_snapshot['bank_name'],
            'bank_account'   => $karyawan_snapshot['bank_account'],
            'subtotal'       => $subtotal,
            'status'         => 'Draft',
            'payment_status' => 'Unpaid',
            'notes'          => $data['notes'] ?? null,
            'created_by'     => $this->session->userdata('user_id'),
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        $this->db->insert('erf_header', $header);
        $id = $this->db->insert_id();

        foreach ($details as $d) {
            $this->db->insert('erf_details', [
                'erf_header_id'  => $id,
                'expense_date'   => $d['expense_date'] ?? null,
                'category'       => $d['category'] ?? null,
                'receipt_number' => $d['receipt_number'] ?? null,
                'description'    => $d['description'],
                'qty'            => (float)$d['qty'],
                'unit_price'     => (float)$d['unit_price'],
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE ? FALSE : $id;
    }

    /**
     * Update ERF.
     * @param bool $allow_locked TRUE untuk mode admin yang ingin membuka kunci
     *        dan mengizinkan edit pada record yang berada di tahap approval.
     */
    public function update_erf($id, $data, $details, $allow_locked = FALSE) {
        $this->db->trans_start();

        $subtotal = 0;
        foreach ($details as $d) $subtotal += ((float)$d['qty'] * (float)$d['unit_price']);

        $karyawan_snapshot = $this->_karyawan_snapshot($data['karyawan_id'] ?? null);

        $update = [
            'erf_date'      => $data['erf_date'],
            'department_id' => $data['department_id'] ?? null,
            'karyawan_id'   => $karyawan_snapshot['karyawan_id'],
            'karyawan_name' => $karyawan_snapshot['karyawan_name'],
            'bank_name'     => $karyawan_snapshot['bank_name'],
            'bank_account'  => $karyawan_snapshot['bank_account'],
            'subtotal'      => $subtotal,
            'notes'         => $data['notes'] ?? null,
            'updated_by'    => $this->session->userdata('user_id'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id', $id);
        if (!$allow_locked) $this->db->where_in('status', ['Draft','Paid']);
        $this->db->update('erf_header', $update);

        $this->db->where('erf_header_id', $id)->delete('erf_details');
        foreach ($details as $d) {
            $this->db->insert('erf_details', [
                'erf_header_id'  => $id,
                'expense_date'   => $d['expense_date'] ?? null,
                'category'       => $d['category'] ?? null,
                'receipt_number' => $d['receipt_number'] ?? null,
                'description'    => $d['description'],
                'qty'            => (float)$d['qty'],
                'unit_price'     => (float)$d['unit_price'],
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE ? FALSE : TRUE;
    }

    public function delete_erf($id) {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || !in_array($h->status, ['Draft','Cancelled','Rejected'])) return false;
        return $this->db->where('id', $id)->delete('erf_header');
    }

    /**
     * Delete a single detail row. Allow when header is Draft, or when
     * $admin_override === TRUE (controller must ensure caller is Admin & admin_edit_mode).
     */
    public function delete_erf_detail($detail_id, $admin_override = FALSE) {
        $detail = $this->db->where('id', $detail_id)->get('erf_details')->row();
        if (!$detail) return false;
        $header = $this->get_erf_header_by_id($detail->erf_header_id);
        if (!$header) return false;
        if (!$admin_override && $header->status !== 'Draft') return false;
        if ($admin_override) {
            if (in_array($header->payment_status ?? 'Unpaid', ['Paid','Partial'], true)) return false;
        }
        return $this->db->where('id', $detail_id)->delete('erf_details');
    }

    // ============================================================
    // 3-STAGE APPROVAL
    // ============================================================

    public function submit_erf($id) {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || $h->status !== 'Draft') return ['success' => false, 'message' => 'Reimburse Request hanya bisa di-submit dari status Draft.'];

        $now  = date('Y-m-d H:i:s');
        $uid  = $this->session->userdata('user_id');

        $update = [
            'status'       => 'Submitted',
            'submitted_by' => $uid,
            'submitted_at' => $now,
            'updated_by'   => $uid,
            'updated_at'   => $now,
        ];

        $this->db->trans_start();
        $this->db->where('id', $id)->update('erf_header', $update);
        $this->_log($id, 'Submit', $uid);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal submit Reimburse Request.']
            : ['success' => true];
    }

    public function approve_hod($id, $notes = '') {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || $h->status !== 'Submitted') return ['success' => false, 'message' => 'Reimburse Request hanya bisa di-approve HOD jika status Submitted.'];

        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        // Approval 1 tahap (2026-11-06): Head of Dept = approval final -> siap dibayar.
        $this->db->where('id', $id)->update('erf_header', [
            'status'            => 'Approved',
            'hod_approved_by'   => $uid,
            'hod_approved_at'   => $now,
            'final_approved_by' => $uid,
            'final_approved_at' => $now,
            'updated_by'        => $uid,
            'updated_at'        => $now,
        ]);
        $this->_log($id, 'Approve HOD', $uid, $notes);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal approve HOD.']
            : ['success' => true];
    }

    public function approve_endorsement($id, $notes = '') {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || $h->status !== 'Approved Head Dept') return ['success' => false, 'message' => 'Reimburse Request hanya bisa di-endorse setelah Approved Head Dept.'];

        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('erf_header', [
            'status'      => 'Endorsed',
            'endorsed_by' => $uid,
            'endorsed_at' => $now,
            'updated_by'  => $uid,
            'updated_at'  => $now,
        ]);
        $this->_log($id, 'Endorse', $uid, $notes);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal endorse.']
            : ['success' => true];
    }

    public function approve_final($id, $notes = '') {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || $h->status !== 'Endorsed') return ['success' => false, 'message' => 'Reimburse Request hanya bisa di-approve final setelah Endorsed.'];

        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('erf_header', [
            'status'            => 'Approved',
            'final_approved_by' => $uid,
            'final_approved_at' => $now,
            'updated_by'        => $uid,
            'updated_at'        => $now,
        ]);
        $this->_log($id, 'Approve Final', $uid, $notes);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal approve final.']
            : ['success' => true];
    }

    public function return_to_draft($id, $reason) {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || !in_array($h->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return ['success' => false, 'message' => 'Reimburse Request tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('erf_header', [
            'status'               => 'Draft',
            'returned_to_draft_by' => $uid,
            'returned_to_draft_at' => $now,
            'return_reason'        => $reason,
            // reset audit trail
            'submitted_by'         => NULL,
            'submitted_at'         => NULL,
            'hod_approved_by'      => NULL,
            'hod_approved_at'      => NULL,
            'endorsed_by'          => NULL,
            'endorsed_at'          => NULL,
            'final_approved_by'    => NULL,
            'final_approved_at'    => NULL,
            'rejected_by'          => NULL,
            'rejected_at'          => NULL,
            'reject_reason'        => NULL,
            'updated_by'           => $uid,
            'updated_at'           => $now,
        ]);
        $this->_log($id, 'Return to Draft', $uid, $reason);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal return ke Draft.']
            : ['success' => true];
    }

    public function reject($id, $reason) {
        $h = $this->get_erf_header_by_id($id);
        if (!$h || !in_array($h->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return ['success' => false, 'message' => 'Reimburse Request tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('erf_header', [
            'status'        => 'Rejected',
            'rejected_by'   => $uid,
            'rejected_at'   => $now,
            'reject_reason' => $reason,
            'updated_by'    => $uid,
            'updated_at'    => $now,
        ]);
        $this->_log($id, 'Reject', $uid, $reason);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal reject.']
            : ['success' => true];
    }

    // ============================================================
    // PAYMENT (hanya untuk status='Approved' / fully approved)
    // Hanya mencatat pembayaran, tidak membuat journal entry
    // ============================================================

    public function record_payment($erf_header_id, $payment_data) {
        $h = $this->get_erf_header_by_id($erf_header_id);
        if (!$h || $h->status !== 'Approved') {
            return ['success' => false, 'message' => 'Reimburse Request harus berstatus Approved (Final) sebelum dibayar.'];
        }

        $this->db->trans_start();
        $payment_amount = (float)$payment_data['payment_amount'];
        $uid = $this->session->userdata('user_id');

        // Payment tracking row
        $tracking = [
            'erf_header_id'      => $erf_header_id,
            'payment_date'       => $payment_data['payment_date'],
            'payment_amount'     => $payment_amount,
            'payment_method'     => $payment_data['payment_method']     ?? null,
            'reference_number'   => $payment_data['reference_number']   ?? null,
            'notes'              => $payment_data['notes']              ?? null,
            'payment_proof_path' => $payment_data['payment_proof_path'] ?? null,
            'created_by'         => $uid,
        ];
        // Biaya transfer bank (opsional) — hanya kalau kolomnya sudah ada.
        if ($this->db->field_exists('transfer_fee', 'erf_payment_tracking')) {
            $tracking['transfer_fee'] = $payment_data['transfer_fee'] ?? 0;
        }
        $this->db->insert('erf_payment_tracking', $tracking);

        // Update header (full payment assumption mirror PRF)
        $this->db->where('id', $erf_header_id)->update('erf_header', [
            'status'             => 'Paid',
            'payment_status'     => 'Paid',
            'payment_date'       => $payment_data['payment_date'],
            'payment_method'     => $payment_data['payment_method'] ?? null,
            'payment_proof_path' => $payment_data['payment_proof_path'] ?? null,
            'updated_by'         => $uid,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->_log($erf_header_id, 'Record Payment', $uid,
            'Paid ' . number_format($payment_amount, 2) . ' via ' . ($payment_data['payment_method'] ?? '-'));

        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal record payment.']
            : ['success' => true];
    }

    // ============================================================
    // ATTACHMENTS
    // ============================================================

    public function get_attachments($erf_header_id) {
        return $this->db->where('erf_header_id', $erf_header_id)
            ->order_by('id', 'DESC')->get('erf_attachments')->result();
    }

    public function get_attachment($id) {
        return $this->db->where('id', $id)->get('erf_attachments')->row();
    }

    public function add_attachment($data) {
        $this->db->insert('erf_attachments', $data);
        return $this->db->insert_id();
    }

    public function delete_attachment($id) {
        return $this->db->where('id', $id)->delete('erf_attachments');
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi setelah return/reject). `action` enum sudah dilebarkan
     * tambah 'Reply' via db/migration_back_to_draft_reply.sql.
     */
    public function add_reply($erf_header_id, $actor_id, $message) {
        $this->_log($erf_header_id, 'Reply', $actor_id, $message);
    }

    // ============================================================
    // INTERNAL
    // ============================================================

    /**
     * Append audit log row. Always invoked from within trans_start when applicable.
     */
    private function _log($erf_header_id, $action, $actor_id, $notes = null) {
        $actor = $this->db->select('fullname')->where('id', $actor_id)->get('users')->row();
        $this->db->insert('erf_approval_logs', [
            'erf_header_id' => $erf_header_id,
            'action'        => $action,
            'actor_id'      => $actor_id,
            'actor_name'    => $actor ? $actor->fullname : null,
            'notes'         => $notes,
        ]);
    }
}
