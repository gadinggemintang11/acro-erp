<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * API: APPROVALS (mobile approval untuk atasan)
 * ------------------------------------------------------------
 * Semua endpoint butuh header "Authorization: Bearer <token>".
 *
 * GET  api/approvals/pending                  → daftar dokumen menunggu approval user
 * GET  api/approvals/{type}/{id}              → detail dokumen + item + riwayat
 * POST api/approvals/{type}/{id}/approve      {notes?}  → approve sesuai tahap saat ini
 * POST api/approvals/{type}/{id}/reject       {reason}  → reject
 *
 * {type}: po_produksi | po_general | irf | prf | erf
 *
 * Controller ini TIDAK punya logika approval sendiri — semua memakai
 * ulang method model existing (Po_model, Consumable_request_model,
 * Prf_model, Erf_model) dan permission check_access() yang sama
 * dengan web, sehingga aturan web dan mobile dijamin identik.
 * ============================================================
 */
class Approvals extends CI_Controller {

    private $modules = [
        'po_produksi' => ['label' => 'PO Produksi',        'menu' => 'purchase_order_approval',         'po_type' => 'produksi'],
        'po_general'  => ['label' => 'PO Non-Produksi',    'menu' => 'purchase_order_approval_general', 'po_type' => 'non_produksi'],
        'irf'         => ['label' => 'Permintaan Barang (Consumable)',   'menu' => 'consumable_request_approval'],
        'prf'         => ['label' => 'Payment Request (Payment Req.)', 'menu' => 'prf_approval'],
        'erf'         => ['label' => 'Reimburse Request (Reimburse)',    'menu' => 'erf_approval'],
    ];

    private $user = null;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session', 'api_auth']);
        $this->load->helper('auth');
        $this->load->model(['Po_model', 'Consumable_request_model', 'Prf_model', 'Erf_model']);
    }

    // ---------- helpers ----------

    private function _json($payload, $code = 200) {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function _body() {
        $raw  = $this->input->raw_input_stream;
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $this->input->post();
    }

    private function _require_auth() {
        $this->user = $this->api_auth->user();
        if (!$this->user) {
            $this->_json(['success' => false, 'message' => 'Sesi tidak valid. Silakan login ulang.'], 401);
            return false;
        }
        return true;
    }

    /** Tahap approval berikutnya berdasarkan status dokumen saat ini. */
    private function _stage_for_status($status) {
        switch ($status) {
            case 'Submitted':          return 'head_dept';
            case 'Approved Head Dept':
            case 'Head Approved':      return 'endorsement'; // 'Head Approved' = legacy PRF
            case 'Endorsed':           return 'approval';
            default:                   return null;
        }
    }

    private function _stage_label($stage) {
        switch ($stage) {
            case 'head_dept':   return 'Approval Head Dept';
            case 'endorsement': return 'Endorsement';
            case 'approval':    return 'Final Approval';
            default:            return null;
        }
    }

    /** Header dokumen per tipe (memakai getter model existing). */
    private function _get_header($type, $id) {
        switch ($type) {
            case 'po_produksi':
            case 'po_general': return $this->Po_model->get_po_header_by_id($id);
            case 'irf':        return $this->Consumable_request_model->get_header($id);
            case 'prf':        return $this->Prf_model->get_prf_header_by_id($id);
            case 'erf':        return $this->Erf_model->get_erf_header_by_id($id);
        }
        return null;
    }

    /**
     * Cakupan departemen (Role Level tahap 3), sama dengan web: HOD hanya
     * departemennya, pemegang ALL DEPT semua. PO tidak dibatasi departemen.
     */
    private function _dept_ok($type, $cfg, $stage, $h) {
        if ($stage === null || in_array($type, ['po_produksi', 'po_general'], true)) return true;
        if ($type === 'irf') {
            $dept = $h->department ?? null;
        } else {
            $model = $type === 'prf' ? $this->Prf_model : $this->Erf_model;
            $dept  = !empty($h->department_code) ? $h->department_code : $model->get_user_department_code($h->requester_id);
        }
        return can_dept_access($cfg['menu'], $stage, $dept);
    }

    /** Riwayat approval dari kolom audit header (untuk PO yang tak punya tabel log). */
    private function _audit_logs($h) {
        $steps = [
            ['Submit',             'submitted_by',      'submitted_at'],
            ['Approve Head Dept',  'hod_approved_by',   'hod_approved_at'],
            ['Endorse',            'endorsed_by',       'endorsed_at'],
            ['Approve Final',      'final_approved_by', 'final_approved_at'],
            ['Reject',             'rejected_by',       'rejected_at'],
            ['Return to Draft',    'returned_to_draft_by', 'returned_to_draft_at'],
        ];
        $ids = [];
        foreach ($steps as $s) {
            if (!empty($h->{$s[1]})) $ids[] = (int) $h->{$s[1]};
        }
        $names = [];
        if (!empty($ids)) {
            $rows = $this->db->select('id, fullname')->where_in('id', $ids)->get('users')->result();
            foreach ($rows as $r) $names[(int) $r->id] = $r->fullname;
        }
        $logs = [];
        foreach ($steps as $s) {
            if (empty($h->{$s[1]}) || empty($h->{$s[2]})) continue;
            $uid   = (int) $h->{$s[1]};
            $notes = null;
            if ($s[0] === 'Reject' && !empty($h->reject_reason)) $notes = $h->reject_reason;
            if ($s[0] === 'Return to Draft' && !empty($h->return_reason)) $notes = $h->return_reason;
            $logs[] = [
                'action' => $s[0],
                'actor'  => isset($names[$uid]) ? $names[$uid] : ('User #' . $uid),
                'notes'  => $notes,
                'time'   => $h->{$s[2]},
            ];
        }
        return $logs;
    }

    private function _fmt_qty($v) {
        return (float) $v;
    }

    // ---------- endpoints ----------

    /**
     * GET api/approvals/pending
     * Gabungan semua dokumen yang menunggu aksi user di tiap modul,
     * difilter dengan permission tahap yang sama persis dengan web.
     */
    public function pending() {
        if (!$this->_require_auth()) return;
        $g = $this->_gather_pending();
        return $this->_json([
            'success' => true,
            'data'    => [
                'total'  => count($g['items']),
                'counts' => $g['counts'],
                'items'  => $g['items'],
            ],
        ]);
    }

    /**
     * Kumpulkan semua dokumen pending untuk user yang sedang login.
     * Dipakai bersama oleh pending() dan dashboard() supaya hitungan
     * di kedua endpoint selalu konsisten.
     *
     * @return array{items: array, counts: array, stages: array}
     */
    private function _gather_pending() {
        $items  = [];
        $counts = [];
        $stages = ['head_dept' => 0, 'endorsement' => 0, 'approval' => 0];

        foreach ($this->modules as $type => $cfg) {
            $hd = check_access($cfg['menu'], 'head_dept');
            $en = check_access($cfg['menu'], 'endorsement');
            $ap = check_access($cfg['menu'], 'approval');
            if (!$hd && !$en && !$ap) continue;

            $new = [];
            switch ($type) {
                case 'po_produksi':
                case 'po_general':
                    $rows = $this->Po_model->get_pending_approvals($hd, $en, $ap, $cfg['po_type']);
                    foreach ($rows as $r) {
                        $new[] = $this->_pending_item($type, $cfg, $r->id, $r->po_number, $r->po_date,
                            $r->status, $r->created_by_name, (float) $r->total_amount,
                            'Supplier: ' . $r->nama_supplier);
                    }
                    break;

                case 'irf':
                    $rows = $this->Consumable_request_model->get_pending_approvals($hd, $en, $ap);
                    foreach ($rows as $r) {
                        $requester = !empty($r->requestor_name) ? $r->requestor_name : $r->created_by_name;
                        $new[] = $this->_pending_item($type, $cfg, $r->id, $r->request_number, $r->request_date,
                            $r->status, $requester, null, $r->purpose);
                    }
                    break;

                case 'prf':
                    $rows = $this->Prf_model->get_pending_approvals($hd, $en, $ap);
                    foreach ($rows as $r) {
                        $summary = !empty($r->supplier_name) ? $r->supplier_name : $r->item_descriptions;
                        $new[] = $this->_pending_item($type, $cfg, $r->id, $r->prf_number, $r->prf_date,
                            $r->status, $r->requester_name, (float) $r->subtotal, $summary);
                    }
                    break;

                case 'erf':
                    $rows = $this->Erf_model->get_pending_approvals($hd, $en, $ap);
                    foreach ($rows as $r) {
                        $new[] = $this->_pending_item($type, $cfg, $r->id, $r->erf_number, $r->erf_date,
                            $r->status, $r->requester_name, (float) $r->subtotal, $r->notes);
                    }
                    break;
            }

            $counts[$type] = count($new);
            foreach ($new as $it) {
                if (isset($stages[$it['stage']])) $stages[$it['stage']]++;
            }
            $items = array_merge($items, $new);
        }

        return ['items' => $items, 'counts' => $counts, 'stages' => $stages];
    }

    /**
     * GET api/dashboard
     * Ringkasan untuk layar utama aplikasi: identitas user, total dokumen
     * yang menunggu aksi user, rincian per modul, dan per tahap approval.
     */
    public function dashboard() {
        if (!$this->_require_auth()) return;

        $g = $this->_gather_pending();

        // Rincian per modul hanya untuk modul yang user punya hak approval-nya.
        $modules = [];
        foreach ($this->modules as $type => $cfg) {
            if (!array_key_exists($type, $g['counts'])) continue;
            $modules[] = [
                'type'  => $type,
                'label' => $cfg['label'],
                'count' => $g['counts'][$type],
            ];
        }

        return $this->_json([
            'success' => true,
            'data'    => [
                'user' => [
                    'id'         => (int) $this->user->id,
                    'fullname'   => $this->user->fullname,
                    'role_name'  => isset($this->user->role_name) ? $this->user->role_name : null,
                    'departemen' => $this->user->departemen,
                ],
                'total_pending' => count($g['items']),
                'stages'        => $g['stages'],
                'modules'       => $modules,
            ],
        ]);
    }

    private function _pending_item($type, $cfg, $id, $doc_number, $doc_date, $status, $requester, $amount, $summary) {
        $stage = $this->_stage_for_status($status);
        return [
            'type'        => $type,
            'type_label'  => $cfg['label'],
            'id'          => (int) $id,
            'doc_number'  => $doc_number,
            'doc_date'    => $doc_date,
            'status'      => $status,
            'stage'       => $stage,
            'stage_label' => $this->_stage_label($stage),
            'requester'   => $requester,
            'amount'      => $amount,
            'summary'     => $summary !== null && $summary !== '' ? $summary : null,
        ];
    }

    /**
     * GET api/approvals/{type}/{id}
     */
    public function detail($type, $id) {
        if (!$this->_require_auth()) return;

        if (!isset($this->modules[$type])) {
            return $this->_json(['success' => false, 'message' => 'Tipe dokumen tidak dikenal.'], 404);
        }
        $cfg = $this->modules[$type];

        $hd = check_access($cfg['menu'], 'head_dept');
        $en = check_access($cfg['menu'], 'endorsement');
        $ap = check_access($cfg['menu'], 'approval');
        if (!check_access($cfg['menu'], 'view') && !$hd && !$en && !$ap) {
            return $this->_json(['success' => false, 'message' => 'Anda tidak punya akses ke modul ini.'], 403);
        }

        $h = $this->_get_header($type, (int) $id);
        if (!$h) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        if (in_array($type, ['po_produksi', 'po_general']) && $h->type !== $cfg['po_type']) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        // Sama dengan web (tahap 3): dokumen departemen lain tidak bisa dibuka.
        if (in_array($type, ['irf', 'prf', 'erf'], true)) {
            $uid = (int) $this->user->id;
            if ($type === 'irf') {
                $allowed = can_view_dept_doc('consumable_request', $cfg['menu'], $h->department ?? null, (int) $h->created_by === $uid,
                    in_array($h->status, ['Approved', 'Released', 'Rejected', 'Cancelled'], true));
            } else {
                $model = $type === 'prf' ? $this->Prf_model : $this->Erf_model;
                $dept  = !empty($h->department_code) ? $h->department_code : $model->get_user_department_code($h->requester_id);
                $allowed = can_view_dept_doc($type, $cfg['menu'], $dept, (int) $h->requester_id === $uid, check_access($type . '_payment', 'view'));
            }
            if (!$allowed) {
                return $this->_json(['success' => false, 'message' => 'Dokumen ini milik departemen lain.'], 403);
            }
        }

        $stage  = $this->_stage_for_status($h->status);
        $fields = [];
        $doc_no = '';
        $items  = [];
        $logs   = [];
        $total  = null;

        switch ($type) {
            case 'po_produksi':
            case 'po_general':
                $doc_no  = $h->po_number;
                $details = $this->Po_model->get_po_details_by_id((int) $id);
                $sum     = 0;
                foreach ($details as $d) {
                    $line_total = (float) $d->total_local;
                    $sum += $line_total;
                    $name = !empty($d->item_name) ? $d->item_name : $d->description;
                    if (!empty($d->item_code)) $name = $d->item_code . ' — ' . $name;
                    $items[] = [
                        'name'       => $name,
                        'qty'        => $this->_fmt_qty($d->qty),
                        'unit'       => isset($d->item_unit) ? $d->item_unit : null,
                        'unit_price' => (float) $d->local_price,
                        'total'      => $line_total,
                    ];
                }
                $total  = $h->has_ppn == 1 ? round($sum * (1 + ((float) $h->ppn_rate) / 100), 2) : $sum;
                $fields = [
                    ['label' => 'Tanggal PO',  'value' => $h->po_date],
                    ['label' => 'Supplier',    'value' => $h->nama_supplier],
                    ['label' => 'Tipe',        'value' => $h->type === 'produksi' ? 'Produksi' : 'Non-Produksi'],
                    ['label' => 'Mata Uang',   'value' => $h->currency_code],
                    ['label' => 'PPN',         'value' => $h->has_ppn == 1 ? 'Ya (' . (float) $h->ppn_rate . '%)' : 'Tidak'],
                    ['label' => 'Dibuat oleh', 'value' => $h->creator_name],
                    ['label' => 'Remarks',     'value' => $h->remarks],
                ];
                $logs = $this->_audit_logs($h);
                break;

            case 'irf':
                $doc_no  = $h->request_number;
                $details = $this->Consumable_request_model->get_details((int) $id);
                foreach ($details as $d) {
                    $name = $d->item_name;
                    if (!empty($d->item_code)) $name = $d->item_code . ' — ' . $name;
                    $items[] = [
                        'name'       => $name,
                        'qty'        => $this->_fmt_qty($d->qty_requested),
                        'unit'       => $d->item_unit,
                        'unit_price' => null,
                        'total'      => null,
                    ];
                }
                $fields = [
                    ['label' => 'Tanggal',    'value' => $h->request_date],
                    ['label' => 'Departemen', 'value' => $h->department],
                    ['label' => 'Pemohon',    'value' => !empty($h->requestor_name) ? $h->requestor_name : $h->created_by_name],
                    ['label' => 'Tujuan',     'value' => $h->purpose],
                    ['label' => 'Catatan',    'value' => $h->notes],
                ];
                foreach ($this->Consumable_request_model->get_approval_logs((int) $id) as $l) {
                    $logs[] = ['action' => $l->action, 'actor' => $l->actor_name, 'notes' => $l->notes, 'time' => $l->created_at];
                }
                break;

            case 'prf':
                $doc_no  = $h->prf_number;
                $details = $this->Prf_model->get_prf_details_by_header_id((int) $id);
                foreach ($details as $d) {
                    $items[] = [
                        'name'       => $d->description,
                        'qty'        => $this->_fmt_qty($d->qty),
                        'unit'       => null,
                        'unit_price' => (float) $d->unit_price,
                        'total'      => (float) $d->subtotal,
                    ];
                }
                $total  = (float) $h->subtotal;
                $fields = [
                    ['label' => 'Tanggal Payment Request',  'value' => $h->prf_date],
                    ['label' => 'Jatuh Tempo',  'value' => $h->due_date],
                    ['label' => 'Pemohon',      'value' => $h->requester_name],
                    ['label' => 'Supplier',     'value' => $h->supplier_name],
                    ['label' => 'Bank',         'value' => $h->bank_name],
                    ['label' => 'No. Rekening', 'value' => $h->bank_account],
                    ['label' => 'Catatan',      'value' => $h->notes],
                ];
                foreach ($this->Prf_model->get_approval_logs((int) $id) as $l) {
                    $logs[] = [
                        'action' => trim($l->status . ' — ' . $l->approval_level),
                        'actor'  => $l->approver_name,
                        'notes'  => $l->notes,
                        'time'   => $l->created_at,
                    ];
                }
                break;

            case 'erf':
                $doc_no  = $h->erf_number;
                $details = $this->Erf_model->get_erf_details_by_header_id((int) $id);
                foreach ($details as $d) {
                    $name = $d->description;
                    if (!empty($d->category)) $name = '[' . $d->category . '] ' . $name;
                    $items[] = [
                        'name'       => $name,
                        'qty'        => $this->_fmt_qty($d->qty),
                        'unit'       => null,
                        'unit_price' => (float) $d->unit_price,
                        'total'      => (float) $d->subtotal,
                    ];
                }
                $total  = (float) $h->subtotal;
                $fields = [
                    ['label' => 'Tanggal Reimburse Request', 'value' => $h->erf_date],
                    ['label' => 'Pemohon',     'value' => $h->requester_name],
                    ['label' => 'Karyawan',    'value' => $h->karyawan_name],
                    ['label' => 'Supplier',    'value' => $h->supplier_name],
                    ['label' => 'Catatan',     'value' => $h->notes],
                ];
                foreach ($this->Erf_model->get_approval_logs((int) $id) as $l) {
                    $actor  = !empty($l->actor_full) ? $l->actor_full : $l->actor_name;
                    $logs[] = ['action' => $l->action, 'actor' => $actor, 'notes' => $l->notes, 'time' => $l->created_at];
                }
                break;
        }

        // Buang field yang nilainya kosong supaya tampilan mobile ringkas.
        $fields = array_values(array_filter($fields, function ($f) {
            return $f['value'] !== null && $f['value'] !== '';
        }));

        return $this->_json([
            'success' => true,
            'data'    => [
                'type'        => $type,
                'type_label'  => $cfg['label'],
                'id'          => (int) $id,
                'doc_number'  => $doc_no,
                'status'      => $h->status,
                'stage'       => $stage,
                'stage_label' => $this->_stage_label($stage),
                'can_approve' => $stage !== null && check_access($cfg['menu'], $stage) && $this->_dept_ok($type, $cfg, $stage, $h),
                'can_reject'  => $stage !== null && ($hd || $en || $ap) && $this->_dept_ok($type, $cfg, $stage, $h),
                'fields'      => $fields,
                'items'       => $items,
                'total'       => $total,
                'logs'        => $logs,
            ],
        ]);
    }

    /**
     * POST api/approvals/{type}/{id}/approve  {notes?}
     * Tahap ditentukan dari status dokumen saat ini (sama dengan web):
     * Submitted → head_dept, Approved Head Dept → endorsement, Endorsed → approval.
     */
    public function approve_doc($type, $id) {
        if (strtolower($this->input->method()) !== 'post') {
            return $this->_json(['success' => false, 'message' => 'Gunakan method POST.'], 405);
        }
        if (!$this->_require_auth()) return;

        if (!isset($this->modules[$type])) {
            return $this->_json(['success' => false, 'message' => 'Tipe dokumen tidak dikenal.'], 404);
        }
        $cfg = $this->modules[$type];
        $id  = (int) $id;

        $h = $this->_get_header($type, $id);
        if (!$h) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        if (in_array($type, ['po_produksi', 'po_general']) && $h->type !== $cfg['po_type']) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }

        $stage = $this->_stage_for_status($h->status);
        if ($stage === null) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak dalam tahap approval (status: ' . $h->status . ').'], 400);
        }
        if (!check_access($cfg['menu'], $stage)) {
            return $this->_json(['success' => false, 'message' => 'Anda tidak punya hak ' . $this->_stage_label($stage) . ' untuk modul ini.'], 403);
        }
        if (!$this->_dept_ok($type, $cfg, $stage, $h)) {
            return $this->_json(['success' => false, 'message' => 'Dokumen ini bukan departemen yang bisa Anda tangani.'], 403);
        }

        $b     = $this->_body();
        $notes = isset($b['notes']) ? trim($b['notes']) : '';
        $uid   = (int) $this->user->id;

        switch ($type) {
            case 'po_produksi':
            case 'po_general':
                if ($stage === 'head_dept')        $res = $this->Po_model->approve_hod($id, $uid);
                elseif ($stage === 'endorsement')  $res = $this->Po_model->approve_endorsement($id, $uid);
                else                               $res = $this->Po_model->approve_final($id, $uid);
                break;
            case 'irf':
                if ($stage === 'head_dept')        $res = $this->Consumable_request_model->approve_hod($id, $uid);
                elseif ($stage === 'endorsement')  $res = $this->Consumable_request_model->approve_endorsement($id, $uid);
                else                               $res = $this->Consumable_request_model->approve_final($id, $uid);
                break;
            case 'prf':
                if ($stage === 'head_dept')        $res = $this->Prf_model->approve_hod($id, $notes);
                elseif ($stage === 'endorsement')  $res = $this->Prf_model->approve_endorsement($id, $notes);
                else                               $res = $this->Prf_model->approve_final($id, $notes);
                break;
            case 'erf':
                if ($stage === 'head_dept')        $res = $this->Erf_model->approve_hod($id, $notes);
                elseif ($stage === 'endorsement')  $res = $this->Erf_model->approve_endorsement($id, $notes);
                else                               $res = $this->Erf_model->approve_final($id, $notes);
                break;
        }

        if (empty($res['success'])) {
            $msg = isset($res['message']) ? $res['message'] : 'Gagal memproses approval.';
            return $this->_json(['success' => false, 'message' => $msg], 400);
        }

        $fresh = $this->_get_header($type, $id);
        return $this->_json([
            'success' => true,
            'message' => 'Dokumen berhasil di-approve (' . $this->_stage_label($stage) . ').',
            'data'    => ['new_status' => $fresh ? $fresh->status : null],
        ]);
    }

    /**
     * POST api/approvals/{type}/{id}/reject  {reason}
     * Mengikuti aturan web: pemilik hak approval tahap mana pun boleh reject
     * selama dokumen masih dalam tahap approval.
     */
    public function reject_doc($type, $id) {
        if (strtolower($this->input->method()) !== 'post') {
            return $this->_json(['success' => false, 'message' => 'Gunakan method POST.'], 405);
        }
        if (!$this->_require_auth()) return;

        if (!isset($this->modules[$type])) {
            return $this->_json(['success' => false, 'message' => 'Tipe dokumen tidak dikenal.'], 404);
        }
        $cfg = $this->modules[$type];
        $id  = (int) $id;

        $hd = check_access($cfg['menu'], 'head_dept');
        $en = check_access($cfg['menu'], 'endorsement');
        $ap = check_access($cfg['menu'], 'approval');
        if (!$hd && !$en && !$ap) {
            return $this->_json(['success' => false, 'message' => 'Anda tidak punya hak approval untuk modul ini.'], 403);
        }

        $h = $this->_get_header($type, $id);
        if (!$h) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        if (in_array($type, ['po_produksi', 'po_general']) && $h->type !== $cfg['po_type']) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak ditemukan.'], 404);
        }
        if (!$this->_dept_ok($type, $cfg, $this->_stage_for_status($h->status), $h)) {
            return $this->_json(['success' => false, 'message' => 'Dokumen ini bukan departemen yang bisa Anda tangani.'], 403);
        }
        if ($this->_stage_for_status($h->status) === null) {
            return $this->_json(['success' => false, 'message' => 'Dokumen tidak dalam tahap approval (status: ' . $h->status . ').'], 400);
        }

        $b      = $this->_body();
        $reason = isset($b['reason']) ? trim($b['reason']) : '';
        if ($reason === '') {
            return $this->_json(['success' => false, 'message' => 'Alasan reject wajib diisi.'], 400);
        }

        $uid = (int) $this->user->id;
        switch ($type) {
            case 'po_produksi':
            case 'po_general': $res = $this->Po_model->reject_approval($id, $uid, $reason); break;
            case 'irf':        $res = $this->Consumable_request_model->reject($id, $uid, $reason); break;
            case 'prf':        $res = $this->Prf_model->reject($id, $reason); break;
            case 'erf':        $res = $this->Erf_model->reject($id, $reason); break;
        }

        if (empty($res['success'])) {
            $msg = isset($res['message']) ? $res['message'] : 'Gagal me-reject dokumen.';
            return $this->_json(['success' => false, 'message' => $msg], 400);
        }

        return $this->_json([
            'success' => true,
            'message' => 'Dokumen berhasil di-reject.',
            'data'    => ['new_status' => 'Rejected'],
        ]);
    }
}
