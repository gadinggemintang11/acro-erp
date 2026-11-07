<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mr_model extends CI_Model {

    private $table_header = 'mr_header';
    private $table_details = 'mr_details';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Load session helper jika belum autoload
        $this->load->library('session');
        // Jangan load Stock_ledger_model di constructor; load on-demand
    }

    /**
     * Normalisasi user id yang diterima:
     * - jika null -> coba ambil dari session
     * - jika bukan numeric -> kembalikan null (agar tidak menyebabkan FK error)
     * - jika numeric -> cast ke int
     */
    private function _normalize_user_id($user_id = null) {
        if ($user_id === null) {
            $user_id = $this->session->userdata('user_id') ?? null;
        }
        // Jika user_id adalah string kosong atau non-numeric, kembalikan null
        if ($user_id === '' || $user_id === false || $user_id === 'null') {
            return null;
        }
        if (is_numeric($user_id)) {
            return (int)$user_id;
        }
        return null;
    }

    // === HEADER ===
    /** Status yang ditangani tiap tahap approval (pembatasan departemen, Role Level tahap 3). */
    const APPROVAL_STAGE_MAP = [
        'head_dept'   => ['Submitted'],
        'endorsement' => ['Approved Head Dept'],
        'approval'    => ['Endorsed'],
    ];

    public function get_all($filters = []) {
        // Kondisi departemen dihitung SEBELUM builder dirakit — helper-nya ikut
        // menjalankan query (check_access) yang akan me-reset builder.
        $visibility_sql = null;
        if (!empty($filters['visibility'])) {
            $user_id = (int)($filters['visibility']['user_id'] ?? 0);
            $conds = [$user_id > 0 ? 'mr.requester_id = ' . $user_id : '1=0'];
            // Rekan satu departemen yang boleh edit/submit/hapus MRF (mis. Supervisor melihat draft Admin/Clerk).
            $colleague = dept_scope_sql('material_request', ['edit', 'submit', 'delete'], 'mr.department_code', true);
            if ($colleague !== '1=0') $conds[] = '(' . $colleague . ')';
            // Tahap approval yang boleh ditangani, dibatasi departemen.
            $conds[] = approval_stage_scope_sql('material_request_approval', 'mr.status', 'mr.department_code', self::APPROVAL_STAGE_MAP);
            // Status final (Approved ke atas) tampak untuk siapapun yang lolos check_access('view')
            $conds[] = "mr.status IN ('Approved', 'Partial Issued', 'Issued', 'Received', 'Closed')";
            $visibility_sql = '(' . implode(' OR ', $conds) . ')';
        }

        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, mr.department_code, u.fullname as requester_name');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        if (!empty($filters['start_date'])) { $this->db->where('mr.request_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('mr.request_date <=', $filters['end_date']); }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('mr.mr_number', $term);
             $this->db->or_like('u.fullname', $term);
             $this->db->group_end();
        }

        if ($visibility_sql !== null) {
            $this->db->where($visibility_sql, null, false);
        }

        $this->db->order_by('mr.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Khusus untuk menu PMR Approval: list MR yang menunggu aksi user
     * berdasarkan stage approval yang dia punya.
     * - can_head_dept   → status 'Submitted'
     * - can_endorsement → status 'Approved Head Dept'
     * - can_approval    → status 'Endorsed'
     * Visibility eksklusif: user hanya lihat stage yang relevan ke permission-nya.
     */
    public function get_pending_approvals($can_head_dept, $can_endorsement, $can_approval) {
        $stages = [];
        if ($can_head_dept)   $stages[] = 'Submitted';
        if ($can_endorsement) $stages[] = 'Approved Head Dept';
        if ($can_approval)    $stages[] = 'Endorsed';

        if (empty($stages)) return [];

        // Tiap tahap hanya departemen dalam cakupan user (tahap 3); dihitung sebelum builder.
        $scope_sql = approval_stage_scope_sql('material_request_approval', 'mr.status', 'mr.department_code', self::APPROVAL_STAGE_MAP);

        $this->db->select('
            mr.id, mr.mr_number, mr.request_date, mr.status, mr.remarks,
            mr.requested_at, mr.hod_approved_at, mr.endorsed_at,
            req.fullname as requester_name,
            hod.fullname as hod_approver_name,
            end.fullname as endorser_name
        ');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users req', 'mr.requester_id = req.id',     'left');
        $this->db->join('users hod', 'mr.hod_approved_by = hod.id',  'left');
        $this->db->join('users end', 'mr.endorsed_by = end.id',      'left');
        $this->db->where_in('mr.status', $stages);
        $this->db->where($scope_sql, null, false);
        $this->db->order_by('mr.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Hitung status MR header berdasarkan progress issuance & reception.
     * Single source of truth — dipanggil setiap kali ada perubahan
     * (issue / reverse-issue / receive / reverse-receive).
     *
     * State machine:
     * - Tidak ada issuance     → 'Approved'
     * - Ada issuance, semua diterima & qty terpenuhi → 'Received' (= Full Received)
     * - Ada issuance yang sudah diterima (R>=1)      → 'Partial Received'
     *   (mencakup case: belum full issued tapi udah ada yang diterima,
     *    atau sudah full issued tapi belum semua issuance diterima)
     * - Belum ada yang diterima, qty issued = qty req → 'Issued' (Full Issued)
     * - Belum ada yang diterima, qty issued < qty req → 'Partial Issued'
     */
    public function calculate_header_status($mr_id) {
        $details = $this->db->get_where('mr_details', ['mr_header_id' => $mr_id])->result();
        if (empty($details)) return 'Approved';

        $total_requested = 0.0;
        $total_issued    = 0.0;
        foreach ($details as $d) {
            $total_requested += (float)$d->requested_qty;
            $total_issued    += (float)($d->issued_qty ?? 0);
        }

        $issuance_total = $this->db
            ->where('mr_header_id', $mr_id)
            ->where('status !=', 'Cancelled')
            ->count_all_results('mr_issuances');

        if ($issuance_total === 0) return 'Approved';

        $issuance_received = $this->db
            ->where('mr_header_id', $mr_id)
            ->where('status', 'Received')
            ->count_all_results('mr_issuances');

        $fully_issued = ($total_issued >= $total_requested - 0.0001) && ($total_requested > 0);
        $all_received = ($issuance_received >= $issuance_total);

        if ($fully_issued && $all_received) return 'Received';
        if ($issuance_received > 0)         return 'Partial Received';
        if ($fully_issued)                  return 'Issued';
        return 'Partial Issued';
    }

    /**
     * Lightweight: cuma ambil status & mr_number untuk approval flow guard.
     * Hindari overhead 6-join di get_header_by_id.
     */
    public function get_status_by_id($id) {
        $row = $this->db->select('id, mr_number, status')
            ->from($this->table_header)
            ->where('id', $id)
            ->limit(1)->get()->row();
        return $row;
    }

    public function get_header_by_id($id) {
        $this->db->select('
            mr.*,
            req.fullname as requester_name,
            req.signature_path as requester_signature,
            iss.fullname as issuer_name,
            iss.signature_path as issuer_signature,
            rec.fullname as receiver_name,
            rec.signature_path as receiver_signature,
            hod.fullname as hod_approver_name,
            hod.signature_path as hod_approver_signature,
            end.fullname as endorser_name,
            end.signature_path as endorser_signature,
            fin.fullname as final_approver_name,
            fin.signature_path as final_approver_signature
        ');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users req', 'mr.requester_id = req.id', 'left');
        $this->db->join('users iss', 'mr.issuer_id = iss.id', 'left');
        $this->db->join('users rec', 'mr.receiver_id = rec.id', 'left');
        $this->db->join('users hod', 'mr.hod_approved_by = hod.id', 'left');
        $this->db->join('users end', 'mr.endorsed_by = end.id', 'left');
        $this->db->join('users fin', 'mr.final_approved_by = fin.id', 'left');
        $this->db->where('mr.id', $id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Insert header MR.
     * Jika data['requester_id'] disediakan, akan dinormalisasi.
     * Jika tidak ada dan status = 'Submitted', akan gunakan session user (jika ada).
     */
    public function insert_header($data) {
        // Normalisasi requester_id jika ada
        if (isset($data['requester_id'])) {
            $data['requester_id'] = $this->_normalize_user_id($data['requester_id']);
        }

        // Jika status Submitted dan requester_id belum terisi, coba ambil dari session
        if (!empty($data['status']) && strtolower($data['status']) === 'submitted' && empty($data['requester_id'])) {
            $data['requester_id'] = $this->_normalize_user_id(null);
            $data['requested_at'] = $data['requested_at'] ?? date('Y-m-d H:i:s');
        }

        // Departemen MRF (tahap 3): dari form, atau departemen profil pemohon untuk
        // MRF yang dibuat otomatis modul lain (mis. dari Production Process).
        if (empty($data['department_code']) && !empty($data['requester_id']) && $this->db->field_exists('department_code', $this->table_header)) {
            $u = $this->db->query('SELECT departemen FROM users WHERE id = ?', [(int) $data['requester_id']])->row();
            $data['department_code'] = $u ? normalize_department_code($u->departemen) : null;
        }

        $insert = $this->db->insert($this->table_header, $data);
        if (!$insert) {
            log_message('error', '[MR] insert_header fail: ' . json_encode($this->db->error()) . ' | DATA: ' . json_encode($data));
            return false;
        }
        return $this->db->insert_id();
    }

    public function delete_header($id) {
         // Hanya hapus Draft
         return $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
    }

    /**
     * Update status MR dengan keamanan FK user_id.
     * $user_id dapat diberikan; jika null, akan coba ambil dari session.
     */

    public function update_status($id, $new_status, $user_id = null, $extra_data = []) {
        $user_id = $this->_normalize_user_id($user_id);
        $data = ['status' => $new_status];
        $now = date('Y-m-d H:i:s');

        switch ($new_status) {
            case 'Submitted':
                // Jika requester_id belum ada, set sekarang. Jika sudah ada (dari Draft), jangan overwrite
                $current = $this->db->select('requester_id')->get_where($this->table_header, ['id' => $id])->row();
                if (!$current || empty($current->requester_id)) {
                    $data['requester_id'] = $user_id;
                }
                $data['requested_at'] = $now;
                break;
            case 'Draft': // Unconfirm submit
                $data['requester_id']      = NULL;
                $data['requested_at']      = NULL;
                $data['hod_approved_by']   = NULL;
                $data['hod_approved_at']   = NULL;
                $data['endorsed_by']       = NULL;
                $data['endorsed_at']       = NULL;
                $data['final_approved_by'] = NULL;
                $data['final_approved_at'] = NULL;
                break;
            case 'Approved Head Dept':
                $data['hod_approved_by'] = $user_id;
                $data['hod_approved_at'] = $now;
                break;
            case 'Endorsed':
                $data['endorsed_by'] = $user_id;
                $data['endorsed_at'] = $now;
                break;
            case 'Approved':
                $data['final_approved_by'] = $user_id;
                $data['final_approved_at'] = $now;
                // Approval 1 tahap (2026-11-05): approve_hod() ikut mengisi hod_approved_*.
                foreach ((array) $extra_data as $k => $v) {
                    if (in_array($k, ['hod_approved_by', 'hod_approved_at'], true)) $data[$k] = $v;
                }
                break;
            case 'Rejected':
                $data['rejected_by']   = $user_id;
                $data['rejected_at']   = $now;
                $data['reject_reason'] = $extra_data['reject_reason'] ?? null;
                break;
            case 'Returned to Draft':
                // Special: status balik ke 'Draft' tapi simpan trail return.
                $data['status']               = 'Draft';
                $data['returned_to_draft_by'] = $user_id;
                $data['returned_to_draft_at'] = $now;
                $data['return_reason']        = $extra_data['return_reason'] ?? null;
                // Reset approval audit (workflow mulai dari 0).
                $data['hod_approved_by']   = NULL;
                $data['hod_approved_at']   = NULL;
                $data['endorsed_by']       = NULL;
                $data['endorsed_at']       = NULL;
                $data['final_approved_by'] = NULL;
                $data['final_approved_at'] = NULL;
                // Reset reject (jika dulu pernah)
                $data['rejected_by']   = NULL;
                $data['rejected_at']   = NULL;
                $data['reject_reason'] = NULL;
                break;
            case 'Issued':
                $data['issuer_id'] = $user_id;
                $data['issued_at'] = $now;
                if (isset($extra_data['issuance_number'])) {
                     $data['issuance_number'] = $extra_data['issuance_number'];
                }
                break;
            case 'Received':
                $data['receiver_id'] = $user_id;
                $data['received_at'] = $now;
                break;
            default:
                // Untuk status lain, izinkan extra_data menimpa field spesifik
                if (!empty($extra_data) && is_array($extra_data)) {
                    foreach ($extra_data as $k => $v) {
                        $data[$k] = $v;
                    }
                }
                break;
        }

        $res = $this->db->where('id', $id)->update($this->table_header, $data);
        if (!$res) {
            log_message('error', "[MR] update_status fail for id={$id}, status={$new_status}: " . json_encode($this->db->error()) . " | DATA: " . json_encode($data));
        }
        return $res;
    }

    public function generate_mr_number($dept_code = null) {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        // $dept_code tidak dipakai lagi: nomor tidak memuat kode departemen.
        return doc_number_next('material_request', 'mr_header', 'mr_number');
    }

    // === DETAILS ===
    public function get_details_by_id($mr_id) {
         $select_clause = "
            d.id, d.mr_header_id, d.requested_qty, d.issued_qty, d.notes,
            d.component_item_id, d.component_item_type, d.wo_header_id,
            wo.wo_number, sc.sc_number, fg.nama_barang as style_name,
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            CASE WHEN d.component_item_type = 'material' THEN m.satuan WHEN d.component_item_type = 'semifinished' THEN sf.satuan ELSE 'PCS' END as item_unit,
            d.unit_measure
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.mr_header_id', $mr_id);
        $this->db->order_by('d.id', 'ASC');
        $query = $this->db->get();
        $rows = $query ? $query->result() : [];
        foreach ($rows as $r) {
            $r->stock = $this->get_stock_for_item($r->component_item_id, $r->component_item_type);
        }
        return $rows;
    }

    public function get_detail_item_by_id($detail_id) {
        $this->db->where('id', $detail_id);
        $query = $this->db->get($this->table_details);
        return $query ? $query->row() : null;
    }

    /**
     * Get item by ID with complete data (for AJAX response)
     */
    public function get_item_by_id($detail_id) {
        $select_clause = "
            d.id, d.mr_header_id, d.requested_qty, d.issued_qty, d.notes,
            d.component_item_id, d.component_item_type, d.wo_header_id,
            wo.wo_number,
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            d.unit_measure
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.id', $detail_id);
        $query = $this->db->get();
        $row = $query ? $query->row() : null;
        if ($row) {
            $row->stock = $this->get_stock_for_item($row->component_item_id, $row->component_item_type);
        }
        return $row;
    }

    public function insert_item($data) {
        // Validasi dasar
        if (empty($data['mr_header_id']) || empty($data['wo_header_id']) || empty($data['component_item_id']) || empty($data['component_item_type']) || !isset($data['requested_qty'])) {
             log_message('error', 'MR_model->insert_item gagal: Data tidak lengkap. ' . json_encode($data));
             return false;
        }

        // Check header status - hanya boleh insert jika header status = Draft
        $hdr = $this->get_header_by_id($data['mr_header_id']);
        if (!$hdr) {
            log_message('error', 'MR_model->insert_item gagal: header MR tidak ditemukan. mr_header_id=' . $data['mr_header_id']);
            return false;
        }
        if (strtolower($hdr->status) !== 'draft') {
            log_message('error', 'MR_model->insert_item gagal: header status bukan Draft. status=' . $hdr->status);
            return false;
        }

        // Ambil satuan dari master
        $satuan = 'PCS';
        if ($data['component_item_type'] == 'material') {
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row();
            if ($component) $satuan = $component->satuan;
        } else if ($data['component_item_type'] == 'semifinished') {
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row();
            if ($component) $satuan = $component->satuan;
        }
        $data['unit_measure'] = $satuan;

        // Pastikan issued_qty tidak ikut tersimpan saat insert awal
        unset($data['issued_qty']);

        $res = $this->db->insert($this->table_details, $data);
        if (!$res) {
            log_message('error', 'MR_model->insert_item DB ERROR: ' . json_encode($this->db->error()) . ' | DATA: ' . json_encode($data));
            return false;
        }
        return $this->db->insert_id();
    }

    public function update_item($id, $data) {
         // Hanya boleh update qty request atau notes jika status header masih Draft
         unset($data['issued_qty']);
         $detail = $this->get_detail_item_by_id($id);
         if (!$detail) { log_message('error', 'MR_model->update_item gagal: detail tidak ditemukan id=' . $id); return false; }
         $hdr = $this->get_header_by_id($detail->mr_header_id);
         if (!$hdr || strtolower($hdr->status) !== 'draft') { log_message('error', 'MR_model->update_item gagal: header bukan Draft'); return false; }
         return $this->db->where('id', $id)->update($this->table_details, $data);
    }

    public function delete_item($id) {
        $detail = $this->get_detail_item_by_id($id);
        if (!$detail) { log_message('error', 'MR_model->delete_item gagal: detail tidak ditemukan id=' . $id); return false; }
        $hdr = $this->get_header_by_id($detail->mr_header_id);
        if (!$hdr || strtolower($hdr->status) !== 'draft') { log_message('error', 'MR_model->delete_item gagal: header bukan Draft'); return false; }
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    /**
     * Daftar Work Order untuk modal "Load from WO Materials" di form Buat MRF.
     *
     * Modal itu dulu meminta user MENGETIK nomor WO di select2 (minimal 2
     * huruf) sebelum apa pun muncul, jadi tidak ada cara melihat WO mana saja
     * yang bisa dipakai (user 2026-09-30: "moda load from wo juga di perbaiki
     * ya").
     *
     * Aturannya SAMA dengan search_open_wos() yang lama: hanya WO berstatus
     * 'In Progress'. Yang bertambah cuma keterangan per baris — nomor IPO,
     * style, qty rencana, dan berapa baris material yang sudah direncanakan
     * di WO itu. WO tanpa rencana material tidak ada gunanya ditarik ke MRF,
     * jadi angkanya perlu kelihatan sebelum dipilih.
     */
    public function get_wo_list_for_mrf($filters = []) {
        $this->db->select("
                wo.id, wo.wo_number, wo.planned_qty, wo.planned_start_date,
                COALESCE(sc.sc_number, '')   AS sc_number,
                COALESCE(fg.kode_barang, '') AS fg_code,
                COALESCE(fg.nama_barang, '') AS fg_name,
                (SELECT COUNT(*) FROM wo_details_material d WHERE d.wo_header_id = wo.id) AS material_count
            ", FALSE)
            ->from('wo_header wo')
            ->join('sales_contracts sc', 'sc.id = wo.sales_contract_id', 'left')
            ->join('finished_goods fg', 'fg.id = wo.fg_id', 'left')
            ->where('wo.status', 'In Progress');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->where("(wo.wo_number LIKE '%{$term}%'"
                          . " OR sc.sc_number LIKE '%{$term}%'"
                          . " OR fg.nama_barang LIKE '%{$term}%'"
                          . " OR fg.kode_barang LIKE '%{$term}%')", null, FALSE);
        }

        $rows = $this->db->order_by('wo.planned_start_date', 'DESC')
                         ->order_by('wo.wo_number', 'DESC')
                         ->get()->result();
        foreach ($rows as $r) $r->material_count = (int) $r->material_count;
        return $rows;
    }

    public function search_open_wos($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->where('wo.status', 'In Progress');
        $this->db->group_start();
        $this->db->like('wo.wo_number', $term);
        $this->db->or_like('fg.nama_barang', $term);
        $this->db->group_end();
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    public function get_wo_references($wo_id) {
        $this->load->model('Wo_model');
        $header = $this->Wo_model->get_header_by_id($wo_id);
        $bom_components = $this->Wo_model->get_bom_components_by_fg($header->fg_id);
        $wo_plan = $this->Wo_model->get_planned_materials($wo_id);

        return [
            'header' => $header,
            'bom' => $bom_components,
            'plan' => $wo_plan
        ];
    }

    public function search_components($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, satuan as unit, 'material' as type")
                              ->group_start()->like('kode_material', $term)->or_like('nama_material', $term)->group_end()
                              ->where('status', 'active')->limit(10)->get('materials')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, satuan as unit, 'semifinished' as type")
                                 ->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end()
                                 ->where('status', 'active')->limit(10)->get('semifinished_goods')->result();
        $rows = array_merge($materials, $semifinished);
        foreach ($rows as $r) {
            $r->stock = $this->get_stock_for_item($r->id, $r->type);
        }
        return $rows;
    }

    public function get_stock_for_item($item_id, $item_type) {
        $this->load->model('Stock_ledger_model');
        return $this->Stock_ledger_model->get_current_stock($item_id, $item_type);
    }

    public function get_mrs_for_production($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.issuance_number, mr.request_date, mr.status, u.fullname as requester_name, i.fullname as issuer_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');
        $this->db->join('users i', 'mr.issuer_id = i.id', 'left');
        if (!empty($filters['status'])) {
             $this->db->where('mr.status', $filters['status']);
        } else {
             $this->db->where_in('mr.status', ['Issued', 'Received']);
        }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('mr.mr_number', $term);
             $this->db->or_like('mr.issuance_number', $term);
             $this->db->group_end();
        }
        $this->db->order_by('mr.request_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Proses penerimaan MR oleh produksi:
     * - update header -> Received
     * - catat ledger 'wip_in' untuk setiap issued item (jika ada issued_qty)
     */
    public function process_reception($mr_id, $user_id = null) {
        $this->db->trans_start();

        $user_id = $this->_normalize_user_id($user_id);

        // 1. Update status MR Header
        $data = [
            'status' => 'Received',
            'receiver_id' => $user_id,
            'received_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);
        if ($this->db->affected_rows() === 0) {
            log_message('warning', "[MR] process_reception: no header updated for mr_id={$mr_id}");
        }

        // 2. Pindahkan stok ke WIP: catat ke stock_ledger sebagai 'wip_in'
        // STRATEGY: Use OUT_ISSUE entries (created during issuance) as source for WIP_IN
        // This ensures batch allocation matches warehouse actual OUT_ISSUE
        // PENTING: Setiap item detail punya wo_header_id sendiri, bukan dari mr_header!
        $items = $this->db->get_where('mr_details', ['mr_header_id' => $mr_id])->result();
        $this->load->model('Stock_ledger_model');

        foreach ($items as $item) {
            if (isset($item->issued_qty) && (float)$item->issued_qty > 0) {
                // Validasi: WO ID harus ada di setiap item
                if (empty($item->wo_header_id)) {
                    log_message('error', '[MR] process_reception: wo_header_id kosong untuk mr_id=' . $mr_id . ' detail_id=' . $item->id);
                    $this->db->trans_rollback();
                    return false;
                }

                // Ambil unit_value last known dari stock_ledger jika ada
                $unit_value = 0.0;
                $this->db->reset_query();
                $this->db->select('unit_value')->from('stock_ledger')
                         ->where('item_id', $item->component_item_id)
                         ->where('item_type', $item->component_item_type)
                         ->order_by('id', 'DESC')->limit(1);
                $urow = $this->db->get()->row();
                if ($urow && isset($urow->unit_value)) $unit_value = (float)$urow->unit_value;

                // BETTER APPROACH: Get OUT_ISSUE entries untuk MR ini
                // These entries sudah pasti allocate dengan SOH_ID yang tepat
                $this->db->reset_query();
                $out_issue_entries = $this->db
                    ->select('stock_on_hand_id, qty_out, transaction_date')
                    ->from('stock_ledger')
                    ->where('transaction_id', $mr_id)
                    ->where('transaction_type', 'OUT_ISSUE')
                    ->where('item_id', $item->component_item_id)
                    ->where('item_type', $item->component_item_type)
                    ->order_by('id', 'ASC')
                    ->get()->result();
                
                // Create WIP_IN entries based on OUT_ISSUE entries (dengan BC dari batch)
                if (!empty($out_issue_entries)) {
                    foreach ($out_issue_entries as $out_entry) {
                        if (empty($out_entry->stock_on_hand_id)) {
                            // Fallback jika OUT_ISSUE tidak punya SOH_ID (shouldn't happen normally)
                            continue;
                        }

                        // Skip jika wip_in sudah ada untuk OUT_ISSUE ini (dari MR yang sama)
                        // Ini mencegah duplicate jika process_reception dipanggil berkali-kali
                        $this->db->reset_query();
                        $existing_wip = $this->db
                            ->select('id')
                            ->from('stock_ledger')
                            ->where('stock_on_hand_id', $out_entry->stock_on_hand_id)
                            ->where('transaction_type', 'wip_in')
                            ->where('item_id', $item->component_item_id)
                            ->where('item_type', $item->component_item_type)
                            ->where('transaction_id', $mr_id)  // PENTING: Filter by MR ID
                            ->get()->row();

                        if ($existing_wip) {
                            // wip_in sudah ada untuk batch ini dari MR ini, skip
                            continue;
                        }

                        // Get BC info dari SOH (dengan defensive column check)
                        $bc_type = null;
                        $bc_number = null;
                        $bc_date = null;
                        
                        // Cek apakah kolom BC ada di tabel stock_on_hand
                        $soh_columns = $this->db->list_fields('stock_on_hand');
                        if (in_array('bc_type_code', $soh_columns) || 
                            in_array('bc_number', $soh_columns) || 
                            in_array('bc_date', $soh_columns)) {
                            
                            $this->db->reset_query();
                            $soh_data = $this->db
                                ->select('bc_type_code, bc_number, bc_date')
                                ->from('stock_on_hand')
                                ->where('id', $out_entry->stock_on_hand_id)
                                ->get()->row();
                            
                            if ($soh_data) {
                                $bc_type = $soh_data->bc_type_code ?? null;
                                $bc_number = $soh_data->bc_number ?? null;
                                $bc_date = $soh_data->bc_date ?? null;
                            }
                        }
                        
                        // Create WIP_IN ledger entry
                        $ledger_data = [
                            'item_id'         => $item->component_item_id,
                            'item_type'       => $item->component_item_type,
                            'transaction_date'=> date('Y-m-d H:i:s'),
                            'transaction_type'=> 'wip_in',
                            'transaction_id'  => $mr_id,
                            'source_doc_type' => 'MR',
                            'source_doc_id'   => $item->wo_header_id,
                            'qty_in'          => (float)$out_entry->qty_out,
                            'qty_out'         => 0,
                            'unit_value'      => $unit_value,
                            'stock_on_hand_id'=> $out_entry->stock_on_hand_id  // Link to specific batch
                        ];
                        
                        // Hanya tambah BC fields jika ada
                        if ($bc_type || $bc_number || $bc_date) {
                            $ledger_data['bc_type_code'] = $bc_type;
                            $ledger_data['bc_number'] = $bc_number;
                            $ledger_data['bc_date'] = $bc_date;
                        }
                        
                        if (!$this->Stock_ledger_model->insert($ledger_data)) {
                            log_message('error', '[MR] process_reception: gagal insert wip_in ledger for mr_id=' . $mr_id . ' item_id=' . $item->component_item_id . ' batch_id=' . $out_entry->stock_on_hand_id);
                            $this->db->trans_rollback();
                            return false;
                        }
                        
                        // Decrement available_qty from stock_on_hand
                        $this->db->where('id', $out_entry->stock_on_hand_id)
                                 ->set('available_qty', 'available_qty - ' . $out_entry->qty_out, FALSE)
                                 ->update('stock_on_hand');
                    }
                }
                
                // If NO OUT_ISSUE entries found (shouldn't happen), create fallback without batch
                if (empty($out_issue_entries)) {
                    $ledger_data = [
                        'item_id'         => $item->component_item_id,
                        'item_type'       => $item->component_item_type,
                        'transaction_date'=> date('Y-m-d H:i:s'),
                        'transaction_type'=> 'wip_in',
                        'transaction_id'  => $mr_id,
                        'source_doc_type' => 'MR',
                        'source_doc_id'   => $item->wo_header_id,
                        'qty_in'          => (float)$item->issued_qty,
                        'qty_out'         => 0,
                        'unit_value'      => $unit_value,
                        'stock_on_hand_id'=> NULL,  // No batch info available
                        'bc_type_code'    => NULL,
                        'bc_number'       => NULL,
                        'bc_date'         => NULL
                    ];
                    
                    if (!$this->Stock_ledger_model->insert($ledger_data)) {
                        log_message('error', '[MR] process_reception: gagal insert fallback wip_in ledger for mr_id=' . $mr_id . ' item_id=' . $item->component_item_id);
                        $this->db->trans_rollback();
                        return false;
                    }
                }
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Process reception for a specific issuance (per-issuance model)
     * Creates wip_in entries only for the specified issuance
     */
    public function process_reception_by_issuance($issuance_id, $user_id = null) {
        $this->db->trans_start();

        $user_id = $this->_normalize_user_id($user_id);

        // 1. Get issuance info
        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance) {
            $this->db->trans_rollback();
            return false;
        }

        $mr_id = $issuance->mr_header_id;

        // 2. Update issuance status
        $this->db->where('id', $issuance_id)->update('mr_issuances', [
            'status' => 'Received',
            'receiver_id' => $user_id,
            'received_at' => date('Y-m-d H:i:s')
        ]);

        // Catatan: wip_in tidak di-insert di sini lagi.
        // Why: barang baru "resmi" masuk WIP setelah lolos QC. Insert wip_in dipindah ke
        // process_qc_issuance dengan qty_in = accept_qty saja. Ini menghindari
        // hapus-lalu-insert-ulang di QC pertama dan bikin laporan WIP lebih akurat.

        // Hitung ulang status MR header berdasarkan kondisi sekarang.
        // Bisa jadi: Partial Received (sebagian diterima) atau Received (semua diterima).
        $new_status = $this->calculate_header_status($mr_id);
        $update_data = ['status' => $new_status];

        // Hanya isi receiver_id/received_at di MR header saat status final 'Received'.
        if ($new_status === 'Received') {
            $update_data['receiver_id'] = $user_id;
            $update_data['received_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $mr_id)->update('mr_header', $update_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Reverse reception for specific issuance (per-issuance model)
     * Menghapus BOTH OUT_ISSUE dan wip_in entries untuk avoid double entry
     */
    public function reverse_reception_by_issuance($issuance_id) {
        $this->db->trans_start();

        // Get issuance info
        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance) {
            $this->db->trans_rollback();
            return false;
        }

        $this->load->model('Stock_ledger_model');

        // === STEP 1: Delete OUT_ISSUE entries and restore stock_on_hand ===
        $out_issue_entries = $this->db
            ->select('stock_on_hand_id, qty_out')
            ->from('stock_ledger')
            ->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'OUT_ISSUE')
            ->get()->result();

        foreach ($out_issue_entries as $entry) {
            if ($entry->stock_on_hand_id) {
                // Restore available_qty karena OUT_ISSUE sudah menguranginya
                $this->db->where('id', $entry->stock_on_hand_id)
                    ->set('available_qty', 'available_qty + ' . $entry->qty_out, FALSE)
                    ->update('stock_on_hand');
            }
        }

        // Delete OUT_ISSUE entries
        $this->db->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'OUT_ISSUE')
            ->delete('stock_ledger');

        // === STEP 2: Undo QC jika sudah dilakukan (IN_RETURN_WIP) ===
        // IN_RETURN_WIP mengembalikan barang reject ke gudang (SOH naik).
        // Saat unconfirm, undo ini: kurangi SOH kembali, hapus entri.
        $return_wip_entries = $this->db
            ->select('stock_on_hand_id, qty_in')
            ->from('stock_ledger')
            ->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'IN_RETURN_WIP')
            ->get()->result();

        foreach ($return_wip_entries as $entry) {
            if ($entry->stock_on_hand_id && $entry->qty_in > 0) {
                $this->db->where('id', $entry->stock_on_hand_id)
                    ->set('available_qty', 'available_qty - ' . (float)$entry->qty_in, FALSE)
                    ->update('stock_on_hand');
            }
        }

        $this->db->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'IN_RETURN_WIP')
            ->delete('stock_ledger');

        // Hapus hasil QC
        $this->db->where('issuance_id', $issuance_id)->delete('issuance_qc_results');

        // === STEP 3: Delete wip_in entries and restore stock_on_hand ===
        // wip_in mengurangi SOH saat production confirm receive.
        // Undo: kembalikan SOH, hapus entri.
        $wip_entries = $this->db
            ->select('stock_on_hand_id, qty_in')
            ->from('stock_ledger')
            ->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'wip_in')
            ->get()->result();

        foreach ($wip_entries as $entry) {
            if ($entry->stock_on_hand_id) {
                $this->db->where('id', $entry->stock_on_hand_id)
                    ->set('available_qty', 'available_qty + ' . (float)$entry->qty_in, FALSE)
                    ->update('stock_on_hand');
            }
        }

        $this->db->where('issuance_id', $issuance_id)
            ->where('transaction_type', 'wip_in')
            ->delete('stock_ledger');

        // Update issuance status back to Issued + reset QC
        $this->db->where('id', $issuance_id)->update('mr_issuances', [
            'status'      => 'Issued',
            'receiver_id' => NULL,
            'received_at' => NULL,
            'qc_status'   => 'Pending',
            'qc_done_by'  => NULL,
            'qc_done_at'  => NULL,
        ]);

        // Hitung ulang status MR header.
        $mr_id = $issuance->mr_header_id;
        $new_status = $this->calculate_header_status($mr_id);
        $update_data = ['status' => $new_status];

        // Kalau status bukan lagi 'Received', clear receiver_id/received_at di header.
        if ($new_status !== 'Received') {
            $update_data['receiver_id'] = NULL;
            $update_data['received_at'] = NULL;
        }
        $this->db->where('id', $mr_id)->update('mr_header', $update_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Reverse reception (legacy - untuk MR tanpa issuance tracking)
     * Menghapus BOTH OUT_ISSUE dan wip_in entries untuk consistency
     */
    public function reverse_reception($mr_id) {
        $this->db->trans_start();

        $this->load->model('Stock_ledger_model');

        // === STEP 1: Delete OUT_ISSUE entries and restore stock_on_hand ===
        $out_issue_entries = $this->db
            ->select('stock_on_hand_id, qty_out')
            ->from('stock_ledger')
            ->where('transaction_id', $mr_id)
            ->where('transaction_type', 'OUT_ISSUE')
            ->get()->result();

        foreach ($out_issue_entries as $entry) {
            if ($entry->stock_on_hand_id) {
                // Restore available_qty karena OUT_ISSUE sudah menguranginya
                $this->db->where('id', $entry->stock_on_hand_id)
                    ->set('available_qty', 'available_qty + ' . $entry->qty_out, FALSE)
                    ->update('stock_on_hand');
            }
        }

        // Delete OUT_ISSUE entries
        $this->db->where('transaction_id', $mr_id)
            ->where('transaction_type', 'OUT_ISSUE')
            ->delete('stock_ledger');

        // === STEP 2: Delete wip_in entries and restore stock_on_hand ===
        $wip_entries = $this->db
            ->select('stock_on_hand_id, qty_in')
            ->from('stock_ledger')
            ->where('transaction_id', $mr_id)
            ->where('transaction_type', 'wip_in')
            ->get()->result();

        foreach ($wip_entries as $entry) {
            if ($entry->stock_on_hand_id) {
                // Restore available_qty
                $this->db->where('id', $entry->stock_on_hand_id)
                    ->set('available_qty', 'available_qty + ' . $entry->qty_in, FALSE)
                    ->update('stock_on_hand');
            }
        }

        // Delete wip_in entries
        $this->db->where('transaction_id', $mr_id)
            ->where('transaction_type', 'wip_in')
            ->delete('stock_ledger');

        // === STEP 3: Update MR Header status ===
        $data = [
            'status' => 'Issued',
            'receiver_id' => NULL,
            'received_at' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // =====================================================
    // PRODUCTION QC — Quality Check setelah Production Receive
    // =====================================================

    /**
     * Proses QC untuk satu issuance.
     *
     * $qc_items = [
     *   ['item_id'=>1,'item_type'=>'material','accept_qty'=>58,'reject_qty'=>2,'qc_notes'=>'...'],
     *   ...
     * ]
     *
     * Logic:
     * - accept_qty: tetap di WIP (wip_in sudah ada, tidak perlu ubah)
     * - reject_qty: buat IN_RETURN_WIP ke stock_ledger, restore SOH batch
     * - Simpan hasil ke issuance_qc_results
     * - Update mr_issuances.qc_status = 'QC Done'
     */
    public function process_qc_issuance($issuance_id, $qc_items, $user_id = null) {
        $this->db->trans_start();
        $this->load->model('Stock_ledger_model');

        $issuance = $this->db->get_where('mr_issuances', ['id' => $issuance_id])->row();
        if (!$issuance || $issuance->status !== 'Received') {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Issuance harus berstatus Received untuk QC.'];
        }

        // Hapus hasil QC lama jika ada (re-process)
        $this->db->where('issuance_id', $issuance_id)->delete('issuance_qc_results');

        $mr_id = $issuance->mr_header_id;

        // === VALIDASI RE-QC: jaga agar saldo WIP tidak jadi negatif ===
        // wip_in TIDAK dibuat saat penerimaan -- barang baru resmi masuk WIP
        // setelah lolos QC (lihat process_reception_by_issuance). Jadi:
        //
        //   QC PERTAMA : belum ada wip_in dari issuance ini. Tidak ada yang
        //                dibatalkan, jadi tidak mungkin bikin WIP negatif.
        //   RE-QC      : wip_in lama dihapus lalu diganti accept_qty yang baru.
        //                Kalau barangnya sudah terlanjur dipakai produksi,
        //                penggantian itu bisa bikin saldo WIP minus.
        //
        // Versi sebelumnya menolak berdasarkan TOTAL pemakaian item tsb di
        // SELURUH WO terkait -- tanpa peduli pemakaian itu berasal dari
        // issuance mana, dan tanpa peduli issuance ini pernah di-QC atau belum.
        // Akibatnya (dilaporkan user 2026-09-30): material yang reject di
        // produksi diminta ulang lewat MRF, issuance barunya cuma 1 pcs dan
        // belum pernah di-QC, tapi QC-nya ditolak gara-gara 1.200 pcs dari
        // issuance LAIN di WO yang sama sudah dipakai produksi.
        //
        // Sekarang yang diperiksa persis dampaknya:
        //   saldo WIP sekarang - wip_in lama issuance ini + accept_qty baru
        foreach ($qc_items as $qi) {
            $vid   = (int)($qi['item_id'] ?? 0);
            $vtype = $qi['item_type'] ?? 'material';
            if ($vid <= 0) continue;

            // wip_in yang akan DIBATALKAN -- hanya milik issuance ini.
            $prev = $this->db->select_sum('qty_in', 'q')
                ->from('stock_ledger')
                ->where('issuance_id', $issuance_id)
                ->where('transaction_type', 'wip_in')
                ->where('item_id', $vid)
                ->where('item_type', $vtype)
                ->get()->row();
            $prev_wip_in = $prev ? (float) $prev->q : 0.0;

            // Tidak ada wip_in dari issuance ini -> ini QC pertama, aman.
            if ($prev_wip_in <= 0.0001) continue;

            $bal = $this->db->select("
                    IFNULL(SUM(CASE WHEN transaction_type = 'wip_in'  THEN qty_in  ELSE 0 END), 0)
                  - IFNULL(SUM(CASE WHEN transaction_type = 'wip_out' THEN qty_out ELSE 0 END), 0) AS saldo
                ", FALSE)
                ->from('stock_ledger')
                ->where('item_id', $vid)
                ->where('item_type', $vtype)
                ->get()->row();
            $saldo_wip = $bal ? (float) $bal->saldo : 0.0;

            $accept_new = (float)($qi['accept_qty'] ?? ($qi['issued_qty'] ?? 0));
            $saldo_baru = $saldo_wip - $prev_wip_in + $accept_new;

            if ($saldo_baru < -0.0001) {
                $name = '';
                if ($vtype === 'material') {
                    $m = $this->db->select('nama_material')->from('materials')->where('id', $vid)->get()->row();
                    $name = $m ? $m->nama_material : "ID {$vid}";
                } else {
                    $sf = $this->db->select('nama_barang')->from('semifinished_goods')->where('id', $vid)->get()->row();
                    $name = $sf ? $sf->nama_barang : "ID {$vid}";
                }
                $this->db->trans_rollback();
                return ['success' => false, 'message' =>
                    "Tidak bisa re-QC material '{$name}': hasil QC sebelumnya (" . number_format($prev_wip_in, 2) .
                    ") sudah terpakai di proses produksi, sisa WIP sekarang " . number_format($saldo_wip, 2) .
                    ". Kalau diganti jadi " . number_format($accept_new, 2) . ", saldo WIP jadi " .
                    number_format($saldo_baru, 2) . " (minus). Hapus dulu inputan pemakaian material di " .
                    "Production Process, baru re-QC."];
            }
        }

        // === RE-QC: Hapus wip_in & IN_RETURN_WIP existing untuk items yg di-QC ===
        // Why: wip_in awal di-insert oleh process_reception_by_issuance dgn full issued_qty.
        // QC harus menyesuaikan agar wip_in hanya = accept_qty. Untuk re-QC, hapus dulu
        // ledger entries yg dibuat QC sebelumnya (dan yg awal dari reception) lalu insert ulang.
        // IN_RETURN_WIP dari QC sebelumnya juga dihapus — modul ini tidak akan create lagi
        // (reject ke supplier via Warehouse_out Production Return, bukan balik ke warehouse).
        foreach ($qc_items as $qi) {
            $item_id   = (int)($qi['item_id']    ?? 0);
            $item_type = $qi['item_type']         ?? 'material';
            if ($item_id <= 0) continue;

            // Undo wip_in: SOH += qty_in, lalu delete row
            $existing_wip = $this->db
                ->select('id, stock_on_hand_id, qty_in')
                ->from('stock_ledger')
                ->where('issuance_id', $issuance_id)
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('transaction_type', 'wip_in')
                ->get()->result();
            foreach ($existing_wip as $w) {
                if (!empty($w->stock_on_hand_id) && (float)$w->qty_in > 0) {
                    $this->db->where('id', $w->stock_on_hand_id)
                        ->set('available_qty', 'available_qty + ' . (float)$w->qty_in, FALSE)
                        ->update('stock_on_hand');
                }
            }
            $this->db->where('issuance_id', $issuance_id)
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('transaction_type', 'wip_in')
                ->delete('stock_ledger');

            // Undo IN_RETURN_WIP dari QC sebelumnya (jika ada): SOH -= qty_in, lalu delete
            $existing_ret = $this->db
                ->select('id, stock_on_hand_id, qty_in')
                ->from('stock_ledger')
                ->where('issuance_id', $issuance_id)
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('transaction_type', 'IN_RETURN_WIP')
                ->get()->result();
            foreach ($existing_ret as $r) {
                if (!empty($r->stock_on_hand_id) && (float)$r->qty_in > 0) {
                    $this->db->where('id', $r->stock_on_hand_id)
                        ->set('available_qty', 'available_qty - ' . (float)$r->qty_in, FALSE)
                        ->update('stock_on_hand');
                }
            }
            $this->db->where('issuance_id', $issuance_id)
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('transaction_type', 'IN_RETURN_WIP')
                ->delete('stock_ledger');
        }

        // Ambil info WO dari mr_details (untuk source_doc_id di ledger)
        $mr_items_map = [];
        $mr_items = $this->db->get_where('mr_details', ['mr_header_id' => $mr_id])->result();
        foreach ($mr_items as $mi) {
            $key = $mi->component_item_id . '_' . $mi->component_item_type;
            $mr_items_map[$key] = $mi->wo_header_id;
        }

        foreach ($qc_items as $qi) {
            $item_id    = (int)($qi['item_id']    ?? 0);
            $item_type  = $qi['item_type']         ?? 'material';
            $issued_qty = (float)($qi['issued_qty'] ?? 0);
            $accept_qty = (float)($qi['accept_qty'] ?? $issued_qty);
            $reject_qty = (float)($qi['reject_qty'] ?? 0);
            $qc_notes   = $qi['qc_notes']           ?? null;

            if ($item_id <= 0) continue;

            // Validasi
            if ($accept_qty + $reject_qty > $issued_qty + 0.0001) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => "Accept + Reject qty melebihi issued qty untuk item ID $item_id"];
            }

            // Tentukan QC status
            if ($reject_qty <= 0) {
                $qc_status = 'Accepted';
            } elseif ($accept_qty > 0) {
                $qc_status = 'Partial';
            } else {
                $qc_status = 'Rejected';
            }

            // Simpan hasil QC
            $qc_row = [
                'issuance_id' => $issuance_id,
                'item_id'     => $item_id,
                'item_type'   => $item_type,
                'issued_qty'  => $issued_qty,
                'accept_qty'  => $accept_qty,
                'reject_qty'  => $reject_qty,
                'qc_notes'    => $qc_notes,
                'qc_status'   => $qc_status,
                'qc_done_by'  => $user_id,
                'qc_done_at'  => date('Y-m-d H:i:s'),
            ];

            // Rincian NG: 4 kategori penyebab + kode NG (lihat ng_helper.php).
            // Hanya relevan kalau ada yang ditolak; catatan saja, tidak
            // mempengaruhi stok/ledger. Dilewati kalau migrasi belum jalan.
            if ($reject_qty > 0.0001) {
                $qc_row += ng_collect_post($qi, $reject_qty, 'issuance_qc_results');
            }

            $this->db->insert('issuance_qc_results', $qc_row);

            // === Insert wip_in baru hanya untuk accept_qty (FIFO across OUT_ISSUE batches) ===
            // Why: hanya item yg lolos QC yg masuk WIP. Reject tidak insert ledger —
            // barang reject diretur ke supplier via Warehouse_out Production Return
            // (yg sudah di-skip dari ledger). Net warehouse balance = full issued_qty,
            // konsisten: items keluar permanen saat issuance, reject tidak balik.
            if ($accept_qty > 0.0001) {
                $wo_header_id = $mr_items_map[$item_id . '_' . $item_type] ?? null;

                // OUT_ISSUE batches untuk issuance ini (FIFO)
                $out_issue_entries = $this->db
                    ->select('stock_on_hand_id, qty_out')
                    ->from('stock_ledger')
                    ->where('issuance_id', $issuance_id)
                    ->where('item_id', $item_id)
                    ->where('item_type', $item_type)
                    ->where('transaction_type', 'OUT_ISSUE')
                    ->order_by('id', 'ASC')
                    ->get()->result();

                $remaining = $accept_qty;
                foreach ($out_issue_entries as $oe) {
                    if ($remaining <= 0) break;
                    if (empty($oe->stock_on_hand_id)) continue;

                    $portion = min((float)$oe->qty_out, $remaining);

                    // Unit value dari ledger terakhir
                    $unit_value = 0.0;
                    $urow = $this->db->select('unit_value')->from('stock_ledger')
                        ->where('item_id', $item_id)
                        ->where('item_type', $item_type)
                        ->order_by('id', 'DESC')->limit(1)->get()->row();
                    if ($urow) $unit_value = (float)$urow->unit_value;

                    // BC info dari SOH batch
                    $bc_type = null; $bc_number = null; $bc_date = null;
                    $soh_columns = $this->db->list_fields('stock_on_hand');
                    if (in_array('bc_type_code', $soh_columns) || in_array('bc_number', $soh_columns) || in_array('bc_date', $soh_columns)) {
                        $soh_data = $this->db->select('bc_type_code, bc_number, bc_date')
                            ->from('stock_on_hand')->where('id', $oe->stock_on_hand_id)
                            ->get()->row();
                        if ($soh_data) {
                            $bc_type   = $soh_data->bc_type_code ?? null;
                            $bc_number = $soh_data->bc_number    ?? null;
                            $bc_date   = $soh_data->bc_date      ?? null;
                        }
                    }

                    $ledger_data = [
                        'item_id'          => $item_id,
                        'item_type'        => $item_type,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'transaction_type' => 'wip_in',
                        'transaction_id'   => $mr_id,
                        'issuance_id'      => $issuance_id,
                        'source_doc_type'  => 'MR',
                        'source_doc_id'    => $wo_header_id,
                        'qty_in'           => $portion,
                        'qty_out'          => 0,
                        'unit_value'       => $unit_value,
                        'stock_on_hand_id' => $oe->stock_on_hand_id,
                    ];
                    if ($bc_type || $bc_number || $bc_date) {
                        $ledger_data['bc_type_code'] = $bc_type;
                        $ledger_data['bc_number']    = $bc_number;
                        $ledger_data['bc_date']      = $bc_date;
                    }

                    if (!$this->Stock_ledger_model->insert($ledger_data)) {
                        $this->db->trans_rollback();
                        return ['success' => false, 'message' => 'Gagal insert wip_in untuk item ID ' . $item_id];
                    }

                    // Decrement SOH (mirror pola process_reception_by_issuance)
                    $this->db->where('id', $oe->stock_on_hand_id)
                        ->set('available_qty', 'available_qty - ' . $portion, FALSE)
                        ->update('stock_on_hand');

                    $remaining -= $portion;
                }
            }
        }

        // Update mr_issuances QC status
        $this->db->where('id', $issuance_id)->update('mr_issuances', [
            'qc_status'  => 'QC Done',
            'qc_done_by' => $user_id,
            'qc_done_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Transaksi database gagal.'];
        }
        return ['success' => true, 'message' => 'QC berhasil diproses.'];
    }

    /**
     * Get QC results untuk issuance tertentu.
     */
    public function get_qc_results($issuance_id) {
        return $this->db
            ->select('qr.*,
                CASE WHEN qr.item_type="material" THEN m.kode_material WHEN qr.item_type="semifinished" THEN sf.kode_barang END as item_code,
                CASE WHEN qr.item_type="material" THEN m.nama_material WHEN qr.item_type="semifinished" THEN sf.nama_barang END as item_name,
                CASE WHEN qr.item_type="material" THEN m.satuan WHEN qr.item_type="semifinished" THEN sf.satuan ELSE "PCS" END as item_unit,
                u.fullname as qc_done_by_name
            ', FALSE)
            ->from('issuance_qc_results qr')
            ->join('materials m',          'qr.item_id = m.id AND qr.item_type = "material"',          'left')
            ->join('semifinished_goods sf', 'qr.item_id = sf.id AND qr.item_type = "semifinished"',    'left')
            ->join('users u',              'qr.qc_done_by = u.id',                                      'left')
            ->where('qr.issuance_id', $issuance_id)
            ->get()->result();
    }
}
