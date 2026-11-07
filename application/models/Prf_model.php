<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Prf_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('JurnalUmum_model');
        $this->load->model('Ap_auto_model');
    }

    /** Pesan error terakhir dari record_payment() (dibaca controller). */
    public $last_error = null;

    /**
     * GenerANate PRF Number (format: DEPT-000001)
     */
    public function generate_prf_number($department_code = null) {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        // $department_code tidak dipakai lagi: nomor tidak memuat kode departemen.
        return doc_number_next('payment_request', 'prf_header', 'prf_number');
    }

    public function resolve_department_code($value) {
        if (empty($value)) return null;
        $cc = $this->db->select('code')->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('code', $value)
                ->or_where('description', $value)
            ->group_end()
            ->limit(1)->get()->row();
        return $cc ? $cc->code : $value;
    }

    public function resolve_department_name($value) {
        if (empty($value)) return '';
        $cc = $this->db->select('description')->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('code', $value)
                ->or_where('description', $value)
            ->group_end()
            ->limit(1)->get()->row();
        return $cc ? $cc->description : $value;
    }

    public function get_user_department_code($user_id) {
        if (empty($user_id)) return null;
        $user = $this->db->select('departemen')->from('users')->where('id', $user_id)->get()->row();
        if (!$user || empty($user->departemen)) return null;
        return $this->resolve_department_code($user->departemen);
    }

    public function get_user_department_name($user_id) {
        if (empty($user_id)) return '';
        $user = $this->db->select('departemen')->from('users')->where('id', $user_id)->get()->row();
        if (!$user || empty($user->departemen)) return '';
        return $this->resolve_department_name($user->departemen);
    }

    /**
     * Get all PRF headers with filters
     */
    public function get_all_prf_headers($filters = []) {
        $this->db->reset_query();
        $this->db->select('
            ph.*,
            u.fullname as requester_name,
            u2.fullname as approved_by_name,
            (SELECT GROUP_CONCAT(d.description SEPARATOR ", ")
               FROM prf_details d WHERE d.prf_header_id = ph.id) as item_descriptions
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        $this->db->join('users u2', 'ph.updated_by = u2.id', 'left');

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $this->db->where('ph.prf_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('ph.prf_date <=', $filters['date_to']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $this->db->where('ph.status', $filters['status']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $this->db->where('ph.payment_status', $filters['payment_status']);
        }

        // Filter by department
        if (!empty($filters['department_id'])) {
            $this->db->where('ph.department_id', $filters['department_id']);
        }

        // Scope to a single requester (per-user visibility)
        if (!empty($filters['requester_id'])) {
            $this->db->where('ph.requester_id', (int)$filters['requester_id']);
        }

        // Kondisi cakupan departemen yang sudah jadi (dari controller, tahap 3).
        if (!empty($filters['scope_sql'])) {
            $this->db->where('(' . $filters['scope_sql'] . ')', null, false);
        }

        $this->db->order_by('ph.prf_date', 'DESC');
        $this->db->order_by('ph.id', 'DESC');

        return $this->db->get()->result();
    }

    /**
     * Update tanggal jatuh tempo (due date) sebuah PRF.
     * $due_date: string 'Y-m-d' atau null untuk mengosongkan.
     */
    public function update_due_date($id, $due_date) {
        $this->db->where('id', (int) $id);
        return $this->db->update('prf_header', [
            'due_date'   => $due_date ?: null,
            'updated_by' => $this->session->userdata('user_id'),
        ]);
    }

    public function get_prf_payment_queue() {
        $this->db->reset_query();
        $this->db->select('
            ph.*,
            u.fullname as requester_name
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        // 'Approved' = status final pada 3-stage workflow.
        // 'Finance Approved' tetap diterima untuk PRF lama (legacy 2-stage) supaya tetap bisa dibayar.
        $this->db->where_in('ph.status', ['Approved', 'Finance Approved']);
        $this->db->where('ph.payment_status !=', 'Paid');
        $this->db->order_by('ph.prf_date', 'DESC');
        $this->db->order_by('ph.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * "PRF Selanjutnya" (ERP Revision Notes, [[erp_revision_prf_erf]]) — cari
     * id PRF berikutnya dalam antrian yang sama (approval/payment), supaya
     * approver/finance bisa proses banyak PRF berturut-turut tanpa balik ke
     * list tiap kali. Urutan queue-nya ikut PERSIS urutan
     * get_pending_approvals()/get_prf_payment_queue() (queue itu sendiri) —
     * bukan cari ulang berdasarkan tanggal/id independen, supaya "next"
     * benar2 konsisten sama apa yg user lihat pas buka list-nya.
     */
    public function get_next_in_queue($queue, $current_id, $can_head_dept = false, $can_endorsement = false, $can_approval = false) {
        if ($queue === 'approval') {
            $list = $this->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval);
        } elseif ($queue === 'payment') {
            $list = $this->get_prf_payment_queue();
        } else {
            return null;
        }

        $found = false;
        foreach ($list as $item) {
            if ($found) return (int) $item->id;
            if ((int) $item->id === (int) $current_id) $found = true;
        }
        return null;
    }

    /**
     * Kebalikan get_next_in_queue() — PRF SEBELUMNYA di antrian yang sama
     * (user 2026-09-16: "tambahkan forward prf ya, fungsinya sama tapi
     * kebalikannya"). Urutan antriannya identik, cuma arah jalannya mundur.
     */
    public function get_prev_in_queue($queue, $current_id, $can_head_dept = false, $can_endorsement = false, $can_approval = false) {
        if ($queue === 'approval') {
            $list = $this->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval);
        } elseif ($queue === 'payment') {
            $list = $this->get_prf_payment_queue();
        } else {
            return null;
        }

        $prev = null;
        foreach ($list as $item) {
            if ((int) $item->id === (int) $current_id) return $prev;
            $prev = (int) $item->id;
        }
        return null;
    }

    /**
     * Kebalikan get_next_prf_id(): PRF dengan id lebih BESAR berikutnya
     * (lebih baru). Scope-nya sama persis — kalau user cuma boleh lihat PRF
     * miliknya sendiri, lompatannya juga dibatasi ke requester_id dia.
     */
    public function get_prev_prf_id($current_id, $user_id, $can_view_all) {
        $this->db->select('id');
        $this->db->from('prf_header');
        $this->db->where('id >', (int) $current_id);
        if (!$can_view_all) {
            $this->db->where('requester_id', (int) $user_id);
        }
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        $row = $this->db->get()->row();
        return $row ? (int) $row->id : null;
    }

    /**
     * Fallback umum "PRF Selanjutnya" — dipakai kalau halaman detail dibuka
     * TANPA konteks queue (mis. langsung lewat URL/bookmark, atau dari PRF
     * List biasa) — user 2026-09-03 minta tombol Next tetap ada di halaman
     * detail apa pun, bukan cuma pas dari Approval/Payment Queue.
     * Urutan ikut PRF List biasa (id DESC = terbaru dulu), jadi "next" =
     * PRF dengan id lebih kecil berikutnya (lebih lama). Scope sama seperti
     * list_all(): kalau user cuma boleh lihat punya sendiri, next juga
     * dibatasi ke requester_id dia.
     */
    public function get_next_prf_id($current_id, $user_id, $can_view_all) {
        $this->db->select('id');
        $this->db->from('prf_header');
        $this->db->where('id <', (int) $current_id);
        if (!$can_view_all) {
            $this->db->where('requester_id', (int) $user_id);
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get()->row();
        return $row ? (int) $row->id : null;
    }

    /**
     * PRF yang boleh ditarik ke Jurnal Umum: sudah Approved & belum Paid
     * (sama seperti antrian pembayaran) DAN belum pernah ditarik ke jurnal
     * manapun (journal_header_id masih NULL) — cegah PRF dijurnal dua kali.
     */
    public function get_journalable_prfs() {
        $this->db->reset_query();
        $this->db->select('
            ph.*,
            (SELECT GROUP_CONCAT(d.description SEPARATOR ", ")
               FROM prf_details d WHERE d.prf_header_id = ph.id) as item_descriptions
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->where_in('ph.status', ['Approved', 'Finance Approved']);
        $this->db->where('ph.payment_status !=', 'Paid');
        $this->db->where('ph.journal_header_id IS NULL', null, false);
        // Payment Request hutang (dari PO / AP Invoice) dijurnal otomatis saat
        // approve & bayar -- jangan bisa ditarik lagi ke Jurnal Umum (dobel).
        if (db_has_column('prf_header', 'source_type')) $this->db->where('ph.source_type !=', 'HUTANG');
        $this->db->order_by('ph.prf_date', 'DESC');
        $this->db->order_by('ph.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Tandai PRF sudah ditarik jadi jurnal $journal_id.
     * WHERE journal_header_id IS NULL sebagai guard anti double-link (race condition).
     */
    public function link_journal($prf_id, $journal_id) {
        $this->db->where('id', (int) $prf_id);
        $this->db->where('journal_header_id IS NULL', null, false);
        $this->db->update('prf_header', ['journal_header_id' => (int) $journal_id]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Lepaskan link PRF ↔ jurnal (dipanggil saat draft jurnal hasil tarik-PRF dihapus).
     */
    public function unlink_journal($journal_id) {
        $this->db->where('journal_header_id', (int) $journal_id);
        return $this->db->update('prf_header', ['journal_header_id' => null]);
    }

    /**
     * Daftar PRF yang menunggu approval di stage yang sesuai dengan permission user.
     * - can_head_dept   → status 'Submitted'
     * - can_endorsement → status 'Approved Head Dept'
     * - can_approval    → status 'Endorsed'
     * Bila user tidak punya satupun, return kosong.
     *
     * NOTE: status legacy 'Head Approved' juga ditangkap di can_endorsement supaya
     * PRF lama (2-stage) yang sudah Head Approved tetap bisa diteruskan ke endorser
     * tanpa perlu data migration. Admin bisa minta endorser melanjutkan flow normal.
     */
    /** Status yang ditangani tiap tahap approval (dipakai pembatasan departemen). */
    const APPROVAL_STAGE_MAP = [
        'head_dept'   => ['Submitted'],
        'endorsement' => ['Approved Head Dept', 'Head Approved'],
        'approval'    => ['Endorsed'],
    ];

    public function get_pending_approvals($can_head_dept, $can_endorsement, $can_approval) {
        $stages = [];
        if ($can_head_dept)   $stages[] = 'Submitted';
        if ($can_endorsement) { $stages[] = 'Approved Head Dept'; $stages[] = 'Head Approved'; }
        if ($can_approval)    $stages[] = 'Endorsed';
        if (empty($stages)) return [];

        // Dihitung sebelum builder dirakit (helper menjalankan query sendiri).
        $scope_sql = approval_stage_scope_sql('prf_approval', 'ph.status', 'ph.department_code', self::APPROVAL_STAGE_MAP);

        $this->db->reset_query();
        $this->db->select('ph.*, u.fullname as requester_name,
            (SELECT GROUP_CONCAT(d.description SEPARATOR ", ")
               FROM prf_details d WHERE d.prf_header_id = ph.id) as item_descriptions', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        $this->db->where_in('ph.status', $stages);
        // Tiap tahap hanya departemen dalam cakupan user (tahap 3).
        $this->db->where($scope_sql, null, false);
        $this->db->order_by('ph.prf_date', 'ASC');
        $this->db->order_by('ph.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Mapping audit users untuk render trail di view: id => fullname.
     */
    public function get_audit_users($header) {
        $ids = array_filter([
            $header->submitted_by         ?? null,
            $header->hod_approved_by      ?? null,
            $header->endorsed_by          ?? null,
            $header->final_approved_by    ?? null,
            $header->rejected_by          ?? null,
            $header->returned_to_draft_by ?? null,
        ]);
        if (empty($ids)) return [];
        $rows = $this->db->select('id, fullname')->where_in('id', $ids)->get('users')->result();
        $map = [];
        foreach ($rows as $r) $map[(int)$r->id] = $r->fullname;
        return $map;
    }

    /**
     * Paid PRF list (history) — optional date filter
     */
    public function get_prf_payment_history($filters = []) {
        $this->db->reset_query();
        $this->db->select('
            ph.*,
            u.fullname as requester_name,
            pay.fullname as paid_by_name
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        $this->db->join('users pay', 'ph.updated_by = pay.id', 'left');
        $this->db->where('ph.payment_status', 'Paid');

        if (!empty($filters['date_from'])) {
            $this->db->where('ph.payment_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('ph.payment_date <=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $kw = $filters['search'];
            $this->db->group_start()
                ->like('ph.prf_number', $kw)
                ->or_like('u.fullname', $kw)
            ->group_end();
        }

        $this->db->order_by('ph.payment_date', 'DESC');
        $this->db->order_by('ph.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get PRF header by ID
     */
    public function get_prf_header_by_id($id) {
        $this->db->select('
            ph.*,
            u.fullname as requester_name
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        $this->db->where('ph.id', $id);
        return $this->db->get()->row();
    }

    /**
     * Get PRF details by header ID
     */
    public function get_prf_details_by_header_id($header_id) {
        $has_ap = db_has_column('prf_details', 'ap_invoice_header_id');
        $this->db->select('prf_details.*, ph.po_number AS source_po_number' . ($has_ap ? ', api.invoice_no AS source_ap_number' : ''), FALSE);
        $this->db->from('prf_details');
        $this->db->join('po_header ph', 'ph.id = prf_details.po_header_id', 'left');
        if ($has_ap) $this->db->join('ap_invoice_header api', 'api.id = prf_details.ap_invoice_header_id', 'left');
        $this->db->where('prf_details.prf_header_id', $header_id);
        $this->db->order_by('prf_details.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get approval logs for PRF
     */
    public function get_approval_logs($prf_header_id) {
        $this->db->select('*');
        $this->db->from('prf_approval_logs');
        $this->db->where('prf_header_id', $prf_header_id);
        $this->db->order_by('id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Get payment tracking for PRF
     */
    public function get_payment_tracking($prf_header_id) {
        $this->db->select('*');
        $this->db->from('prf_payment_tracking');
        $this->db->where('prf_header_id', $prf_header_id);
        $this->db->order_by('payment_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Pencatat pembayaran terakhir -- isi kolom "Dibayar" di PDF/Excel/detail.
     * null kalau Payment Request ini belum pernah di-Record Payment.
     */
    public function get_payment_signer($prf_header_id) {
        return $this->db->select('pt.created_by, pt.payment_date, u.fullname, u.signature_path')
            ->from('prf_payment_tracking pt')
            ->join('users u', 'u.id = pt.created_by', 'left')
            ->where('pt.prf_header_id', (int) $prf_header_id)
            ->order_by('pt.payment_date', 'DESC')->order_by('pt.id', 'DESC')
            ->limit(1)->get()->row();
    }

    /**
     * Single payment tracking row (untuk stream bukti bayar).
     */
    public function get_payment_by_id($payment_id) {
        return $this->db->where('id', $payment_id)->get('prf_payment_tracking')->row();
    }

    /**
     * Update path bukti bayar untuk 1 payment_tracking row + mirror ke prf_header
     * kalau row tsb adalah pembayaran terbaru.
     */
    public function update_payment_proof($payment_id, $new_path, $slot = 1) {
        $pay = $this->get_payment_by_id($payment_id);
        if (!$pay) return false;

        $column = ((int)$slot === 2) ? 'payment_proof_path_2' : 'payment_proof_path';

        $this->db->where('id', $payment_id)
            ->update('prf_payment_tracking', [$column => $new_path]);

        // Mirror ke header hanya untuk bukti utama (slot 1) & payment terbaru.
        if ((int)$slot === 1) {
            $latest = $this->db->select('id')
                ->where('prf_header_id', $pay->prf_header_id)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get('prf_payment_tracking')->row();
            if ($latest && (int)$latest->id === (int)$payment_id) {
                $this->db->where('id', $pay->prf_header_id)
                    ->update('prf_header', ['payment_proof_path' => $new_path]);
            }
        }
        return true;
    }

    /**
     * Ambil snapshot data supplier (vendor + bank) berdasarkan supplier_id.
     * Mengembalikan array dengan supplier_id, supplier_name, bank_name,
     * bank_account & bank_account_name (nama pemilik rekening).
     * Bila supplier_id kosong / tidak ditemukan, semua nilai null.
     */
    private function _supplier_snapshot($supplier_id) {
        $snapshot = [
            'supplier_id'   => null,
            'supplier_name' => null,
            'bank_name'     => null,
            'bank_account'  => null,
            // Nama pemilik rekening. Dulu cetak PRF menebaknya dari NAMA
            // VENDOR (user 2026-10-02) -- sering keliru karena rekening bisa
            // atas nama pemilik usaha, bukan nama badan usahanya.
            'bank_account_name' => null,
        ];
        if (empty($supplier_id)) return $snapshot;

        // Kolom bank_account_name baru ada setelah
        // migration_supplier_account_name.sql -- dijaga supaya DB yang belum
        // dimigrasi tidak membuat query gagal.
        $punya_nama_akun = db_has_column('suppliers', 'bank_account_name');
        $kolom = 'id, nama_supplier, bank_name, bank_account'
               . ($punya_nama_akun ? ', bank_account_name' : '');

        $sup = $this->db->select($kolom)
            ->where('id', (int) $supplier_id)
            ->get('suppliers')->row();
        if ($sup) {
            $snapshot['supplier_id']   = (int) $sup->id;
            $snapshot['supplier_name'] = $sup->nama_supplier;
            $snapshot['bank_name']     = $sup->bank_name;
            $snapshot['bank_account']  = $sup->bank_account;
            $snapshot['bank_account_name'] = $sup->bank_account_name ?? null;
        }
        return $snapshot;
    }

    /**
     * Create new PRF
     */
    public function create_prf($data, $details) {
        $this->db->trans_start();

        // Departemen dokumen: dipilih di form (controller sudah memvalidasi
        // terhadap departemen role user — Role Level tahap 3), fallback profil.
        $department_code = !empty($data['department_code'])
            ? $data['department_code']
            : $this->get_user_department_code($this->session->userdata('user_id'));
        $prf_number = $this->generate_prf_number($department_code);

        // Calculate subtotal
        $subtotal = 0;
        foreach ($details as $detail) {
            $subtotal += ($detail['qty'] * $detail['unit_price']);
        }

        // Snapshot supplier (vendor) + bank info at creation time, so PDF stays accurate
        // even if the supplier's bank details are later edited in the master.
        $supplier_snapshot = $this->_supplier_snapshot($data['supplier_id'] ?? null);

        // PRF bisa menarik item dari beberapa PO sekaligus (per-baris di prf_details).
        // po_id/po_type di header cuma cache dari PO pertama yang dipull, untuk kompatibilitas lama.
        $first_po_id = null;
        $first_po_type = null;
        foreach ($details as $detail) {
            if (!empty($detail['po_header_id'])) {
                $first_po_id = (int)$detail['po_header_id'];
                $first_po_type = $data['po_type'] ?? null;
                break;
            }
        }

        // Insert header
        $header_data = [
            'prf_number' => $prf_number,
            'prf_date' => $data['prf_date'],
            'department_id' => !empty($department_code) ? $department_code : ($data['department_id'] ?? null),
            // department_id bertipe INT (kode teks jadi 0) — kolom teks inilah yang dipakai.
            'department_code' => !empty($department_code) ? $department_code : null,
            'requester_id' => $this->session->userdata('user_id'),
            'supplier_id' => $supplier_snapshot['supplier_id'],
            'supplier_name' => $supplier_snapshot['supplier_name'],
            'bank_name' => $supplier_snapshot['bank_name'],
            'bank_account' => $supplier_snapshot['bank_account'],
            // Nama pemilik rekening ikut DISALIN ke PRF, sejalan dgn bank_name
            // & bank_account: dokumen yang sudah terbit tidak boleh berubah
            // kalau master suppliernya diedit belakangan. Baris ini dibuang
            // lagi di bawah kalau kolomnya belum ada (migrasi belum jalan).
            'bank_account_name' => $supplier_snapshot['bank_account_name'],
            'subtotal' => $subtotal,
            // HUTANG = dari PO / AP Invoice (jurnal otomatis); MANUAL = perilaku lama.
            'source_type' => ($data['source_type'] ?? 'MANUAL') === 'HUTANG' ? 'HUTANG' : 'MANUAL',
            'ppn_amount' => (float) ($data['ppn_amount'] ?? 0),
            'status' => 'Draft',
            'payment_status' => 'Unpaid',
            'notes' => $data['notes'] ?? null,
            'po_id' => $first_po_id,
            'po_type' => $first_po_type,
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Kolom bank_account_name baru ada setelah migrasi; dibuang kalau belum ada
        // supaya perintah di bawah tidak gagal di DB yang belum dimigrasi.
        if (!db_has_column('prf_header', 'bank_account_name')) unset($header_data['bank_account_name']);
        if (!db_has_column('prf_header', 'source_type')) unset($header_data['source_type'], $header_data['ppn_amount']);
        $this->db->insert('prf_header', $header_data);
        $prf_header_id = $this->db->insert_id();

        // Insert details
        foreach ($details as $detail) {
            $detail['prf_header_id'] = $prf_header_id;
            $this->db->insert('prf_details', $detail);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() === FALSE ? FALSE : $prf_header_id;
    }

    /**
     * Update PRF untuk status Draft atau sudah dibayar.
     */
    /**
     * @param bool $allow_locked TRUE cuma dipakai mode edit Admin (buka kunci PRF
     *        yang masih di tahap approval). Kolom status & approval tidak ikut
     *        ditulis di sini, jadi approval yang sudah ada tetap utuh.
     */
    public function update_prf($id, $data, $details, $allow_locked = FALSE) {
        $this->db->trans_start();

        // Calculate subtotal
        $subtotal = 0;
        foreach ($details as $detail) {
            $subtotal += ($detail['qty'] * $detail['unit_price']);
        }

        // Re-snapshot supplier + bank info on edit (Draft only).
        $supplier_snapshot = $this->_supplier_snapshot($data['supplier_id'] ?? null);

        // Update header
        $header_data = [
            'prf_date' => $data['prf_date'],
            'department_id' => $data['department_id'] ?? null,
            'supplier_id' => $supplier_snapshot['supplier_id'],
            'supplier_name' => $supplier_snapshot['supplier_name'],
            'bank_name' => $supplier_snapshot['bank_name'],
            'bank_account' => $supplier_snapshot['bank_account'],
            // Nama pemilik rekening ikut DISALIN ke PRF, sejalan dgn bank_name
            // & bank_account: dokumen yang sudah terbit tidak boleh berubah
            // kalau master suppliernya diedit belakangan. Baris ini dibuang
            // lagi di bawah kalau kolomnya belum ada (migrasi belum jalan).
            'bank_account_name' => $supplier_snapshot['bank_account_name'],
            'subtotal' => $subtotal,
            'source_type' => ($data['source_type'] ?? 'MANUAL') === 'HUTANG' ? 'HUTANG' : 'MANUAL',
            'ppn_amount' => (float) ($data['ppn_amount'] ?? 0),
            'notes' => $data['notes'] ?? null,
            'updated_by' => $this->session->userdata('user_id'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        if (!$allow_locked) $this->db->where_in('status', ['Draft', 'Paid']);
        // Kolom bank_account_name baru ada setelah migrasi; dibuang kalau belum ada
        // supaya perintah di bawah tidak gagal di DB yang belum dimigrasi.
        if (!db_has_column('prf_header', 'bank_account_name')) unset($header_data['bank_account_name']);
        if (!db_has_column('prf_header', 'source_type')) unset($header_data['source_type'], $header_data['ppn_amount']);
        $this->db->update('prf_header', $header_data);

        // Delete old details
        $this->db->where('prf_header_id', $id);
        $this->db->delete('prf_details');

        // Insert new details
        foreach ($details as $detail) {
            $detail['prf_header_id'] = $id;
            $this->db->insert('prf_details', $detail);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() === FALSE ? FALSE : TRUE;
    }

    /**
     * Submit PRF to Head of Department.
     */
    public function submit_prf($id) {
        $h = $this->get_prf_header_by_id($id);
        if (!$h || $h->status !== 'Draft') return FALSE;

        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');

        $update = [
            'status'       => 'Submitted',
            'submitted_by' => $uid,
            'submitted_at' => $now,
            'updated_by'   => $uid,
            'updated_at'   => $now,
        ];

        $this->db->trans_start();
        $this->db->where('id', $id)->where('status', 'Draft')->update('prf_header', $update);
        $this->_log_action($id, 'Submitted', 'Submit', $uid, null);
        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    // ============================================================
    // APPROVAL 1 TAHAP (2026-11-06): Head of Dept = approval final.
    // approve_endorsement()/approve_final() di bawah hanya untuk
    // Payment Request lama (alur 3 tahap) yang masih di tengah jalan.
    // ============================================================

    /**
     * Approve oleh Head of Department -> langsung 'Approved' (siap dibayar).
     * Hanya valid bila status saat ini = 'Submitted'.
     */
    public function approve_hod($id, $notes = '') {
        $h = $this->get_prf_header_by_id($id);
        if (!$h || $h->status !== 'Submitted') {
            return ['success' => false, 'message' => 'Payment Request hanya bisa di-approve HOD jika status Submitted.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        return $this->_approve_to_final($h, 'Submitted', [
            'status'            => 'Approved',
            'hod_approved_by'   => $uid,
            'hod_approved_at'   => $now,
            // Approval 1 tahap: HOD sekaligus approver final (kolom final_* dipakai
            // antrian pembayaran, laporan & kolom "Disetujui" di PDF).
            'final_approved_by' => $uid,
            'final_approved_at' => $now,
            'updated_by'        => $uid,
            'updated_at'        => $now,
        ], 'Head of Dept', $notes, 'Gagal approve HOD.');
    }

    /**
     * Simpan approval final. Payment Request HUTANG sekalian dibuatkan AP
     * Invoice + jurnal hutang (Ap_auto_model) dalam SATU transaksi: kalau
     * akun belum dipetakan / jurnal gagal, approval ikut batal.
     */
    private function _approve_to_final($h, $from_status, array $update, $level, $notes, $fail_msg) {
        $prep = $this->Ap_auto_model->prepare_ap_for_prf($h);
        if (empty($prep['ok'])) return ['success' => false, 'message' => $prep['message']];

        $uid = $this->session->userdata('user_id');
        $ap = null;
        $this->db->trans_begin();
        try {
            $this->db->where('id', (int) $h->id)->where('status', $from_status)->update('prf_header', $update);
            if ($this->db->affected_rows() < 1) throw new Exception('Status Payment Request sudah berubah, muat ulang halaman.');
            $this->_log_action($h->id, 'Approved', $level, $uid, $notes);
            if (!empty($prep['plan'])) {
                $ap = $this->Ap_auto_model->write_ap_for_prf($h, $prep['plan'], $uid);
            }
            if ($this->db->trans_status() === FALSE) throw new Exception($fail_msg);
            $this->db->trans_commit();
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
        return ['success' => true, 'ap' => $ap];
    }

    /**
     * Stage 2: Endorsement.
     * Valid bila status = 'Approved Head Dept' (atau legacy 'Head Approved' utk PRF lama).
     */
    public function approve_endorsement($id, $notes = '') {
        $h = $this->get_prf_header_by_id($id);
        if (!$h || !in_array($h->status, ['Approved Head Dept', 'Head Approved'])) {
            return ['success' => false, 'message' => 'Payment Request hanya bisa di-endorse setelah Approved Head Dept.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('prf_header', [
            'status'      => 'Endorsed',
            'endorsed_by' => $uid,
            'endorsed_at' => $now,
            'updated_by'  => $uid,
            'updated_at'  => $now,
        ]);
        $this->_log_action($id, 'Approved', 'Endorsement', $uid, $notes);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal endorse.']
            : ['success' => true];
    }

    /**
     * Stage 3: Approval final.
     * Valid bila status = 'Endorsed'. Setelah ini PRF baru muncul di queue Payment.
     */
    public function approve_final($id, $notes = '') {
        $h = $this->get_prf_header_by_id($id);
        if (!$h || $h->status !== 'Endorsed') {
            return ['success' => false, 'message' => 'Payment Request hanya bisa di-approve final setelah Endorsed.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        return $this->_approve_to_final($h, 'Endorsed', [
            'status'            => 'Approved',
            'final_approved_by' => $uid,
            'final_approved_at' => $now,
            'updated_by'        => $uid,
            'updated_at'        => $now,
        ], 'Final Approval', $notes, 'Gagal approve final.');
    }

    /**
     * Return-to-Draft: kembalikan ke creator untuk revisi.
     * Reset semua kolom audit approval supaya flow mulai dari Submit lagi.
     */
    public function return_to_draft($id, $reason, $admin_override = FALSE) {
        $h = $this->get_prf_header_by_id($id);
        // Status normal yang boleh return-to-draft: pertengahan approval.
        // Admin override (role Admin) memperluas ke 'Approved' (final approval) +
        // status terminal (Rejected, Cancelled) — sengaja TIDAK mencakup 'Paid'
        // / partial-payment untuk mencegah reset PRF yang uangnya sudah cair.
        $allowed = ['Submitted','Approved Head Dept','Endorsed','Head Approved'];
        if (!$h) {
            return ['success' => false, 'message' => 'Payment Request tidak ditemukan.'];
        }
        if ($h->status === 'Draft') {
            return ['success' => false, 'message' => 'Payment Request sudah dalam status Draft.'];
        }
        if ($admin_override) {
            if ($h->status === 'Paid' || in_array($h->payment_status ?? 'Unpaid', ['Paid','Partial'], true)) {
                return ['success' => false, 'message' => 'Payment Request sudah ada pembayaran — tidak bisa di-return ke Draft.'];
            }
        } elseif (!in_array($h->status, $allowed)) {
            return ['success' => false, 'message' => 'Payment Request tidak dalam tahap approval.'];
        }

        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();

        // Payment Request hutang yang sudah Approved: AP Invoice hasil approval
        // dibatalkan & jurnal hutangnya dibalik (bukan dihapus).
        if (!empty($h->ap_invoice_header_id)) {
            $rev = $this->Ap_auto_model->reverse_ap_for_prf($h, $uid, $reason);
            if (empty($rev['ok'])) {
                $this->db->trans_complete();
                return ['success' => false, 'message' => $rev['message']];
            }
        }

        $reset = [
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
        ];

        // Catatan: admin override hanya boleh utk status approval (guard di atas
        // sudah menolak Paid/Partial), jadi tidak ada payment_tracking & jurnal yg
        // perlu di-rollback di sini. Reset kolom payment header untuk jaga-jaga
        // kalau ada PRF lama yg payment_status-nya bocor walau belum betul2 paid.
        if ($admin_override) {
            $reset['payment_status']     = 'Unpaid';
            $reset['payment_date']       = NULL;
            $reset['payment_method']     = NULL;
            $reset['payment_proof_path'] = NULL;
        }

        $this->db->where('id', $id)->update('prf_header', $reset);
        $log_level = $admin_override ? 'Admin Override Return' : 'Return to Draft';
        $this->_log_action($id, 'Returned', $log_level, $uid, $reason);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal return ke Draft.']
            : ['success' => true];
    }

    /**
     * Reject final: status → 'Rejected', tidak bisa di-revisi.
     */
    public function reject($id, $reason) {
        $h = $this->get_prf_header_by_id($id);
        if (!$h || !in_array($h->status, ['Submitted','Approved Head Dept','Endorsed','Head Approved'])) {
            return ['success' => false, 'message' => 'Payment Request tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $uid = $this->session->userdata('user_id');
        $this->db->trans_start();
        $this->db->where('id', $id)->update('prf_header', [
            'status'        => 'Rejected',
            'rejected_by'   => $uid,
            'rejected_at'   => $now,
            'reject_reason' => $reason,
            'updated_by'    => $uid,
            'updated_at'    => $now,
        ]);
        $this->_log_action($id, 'Rejected', 'Reject', $uid, $reason);
        $this->db->trans_complete();
        return $this->db->trans_status() === FALSE
            ? ['success' => false, 'message' => 'Gagal reject.']
            : ['success' => true];
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi setelah return/reject). Kolom approval_level/status di
     * prf_approval_logs VARCHAR bebas — tidak perlu migration.
     */
    public function add_reply($prf_header_id, $actor_id, $message) {
        $this->_log_action($prf_header_id, 'Reply', 'Reply', $actor_id, $message);
    }

    /**
     * Helper untuk insert ke prf_approval_logs (skema: approval_level + status).
     */
    private function _log_action($prf_header_id, $log_status, $approval_level, $actor_id, $notes = null) {
        $actor = $this->db->select('fullname')->where('id', $actor_id)->get('users')->row();
        $this->db->insert('prf_approval_logs', [
            'prf_header_id'  => $prf_header_id,
            'approval_level' => $approval_level,
            'approver_id'    => $actor_id,
            'approver_name'  => $actor ? $actor->fullname : null,
            'status'         => $log_status,
            'notes'          => $notes,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Record payment for PRF
     */
    public function record_payment($prf_header_id, $payment_data) {
        $this->last_error = null;
        $this->db->trans_begin();
        try {

        // Get current header
        $header = $this->get_prf_header_by_id($prf_header_id);

        // Payment Request MANUAL: hanya dicatat; jurnal debit/kredit dibuat
        // manual oleh Finance lewat menu Jurnal Umum (perilaku lama).
        // Payment Request HUTANG: jurnal bayar + Pembayaran Hutang dibuat
        // otomatis di bawah (Ap_auto_model::write_prf_payment).

        // Insert payment tracking
        $tracking = [
            'prf_header_id' => $prf_header_id,
            'payment_date' => $payment_data['payment_date'],
            'payment_amount' => $payment_data['payment_amount'],
            'payment_method' => $payment_data['payment_method'] ?? null,
            'reference_number' => $payment_data['reference_number'] ?? null,
            'notes' => $payment_data['notes'] ?? null,
            'payment_proof_path' => $payment_data['payment_proof_path'] ?? null,
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        // Bukti bayar kedua (opsional) — hanya kalau kolomnya sudah ada.
        if ($this->db->field_exists('payment_proof_path_2', 'prf_payment_tracking')) {
            $tracking['payment_proof_path_2'] = $payment_data['payment_proof_path_2'] ?? null;
        }
        // Biaya transfer bank (opsional) — hanya kalau kolomnya sudah ada.
        if ($this->db->field_exists('transfer_fee', 'prf_payment_tracking')) {
            $tracking['transfer_fee'] = $payment_data['transfer_fee'] ?? 0;
        }
        $this->db->insert('prf_payment_tracking', $tracking);
        $tracking_id = (int) $this->db->insert_id();

        if (($header->source_type ?? 'MANUAL') === 'HUTANG') {
            $this->Ap_auto_model->write_prf_payment($header, $tracking_id, $payment_data, $this->session->userdata('user_id'));
        }

        // Update header payment status
        $new_status = 'Paid';
        $new_payment_status = 'Paid';

        $this->db->where('id', $prf_header_id);
        $this->db->update('prf_header', [
            'status' => $new_status,
            'payment_status' => $new_payment_status,
            'payment_date' => $payment_data['payment_date'],
            'payment_method' => $payment_data['payment_method'] ?? null,
            'payment_proof_path' => $payment_data['payment_proof_path'] ?? null,
            'updated_by' => $this->session->userdata('user_id'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

            if ($this->db->trans_status() === FALSE) throw new Exception('Transaksi database gagal.');
            $this->db->trans_commit();
            return TRUE;
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->last_error = $e->getMessage();
            return FALSE;
        }
    }

    /**
     * Update data pembayaran yang sudah tercatat untuk PRF.
     */
    public function update_payment_record($prf_header_id, $payment_id, $payment_data) {
        $this->db->trans_start();

        $row = $this->db->where('id', (int) $payment_id)
            ->where('prf_header_id', (int) $prf_header_id)
            ->get('prf_payment_tracking')
            ->row();

        if (!$row) {
            $this->db->trans_complete();
            return false;
        }

        $update = [
            'payment_date' => $payment_data['payment_date'],
            'payment_amount' => $payment_data['payment_amount'],
            'payment_method' => $payment_data['payment_method'] ?? null,
            'reference_number' => $payment_data['reference_number'] ?? null,
            'notes' => $payment_data['notes'] ?? null,
            'payment_proof_path' => $payment_data['payment_proof_path'] ?? $row->payment_proof_path,
        ];

        if ($this->db->field_exists('payment_proof_path_2', 'prf_payment_tracking')) {
            $update['payment_proof_path_2'] = $payment_data['payment_proof_path_2'] ?? $row->payment_proof_path_2 ?? null;
        }

        if ($this->db->field_exists('transfer_fee', 'prf_payment_tracking')) {
            $update['transfer_fee'] = $payment_data['transfer_fee'] ?? 0;
        }

        $this->db->where('id', (int) $payment_id)->update('prf_payment_tracking', $update);
        if ($row->payment_date !== $payment_data['payment_date']) {
            $this->Ap_auto_model->sync_payment_date($row, $payment_data['payment_date']);
        }

        $latest = $this->db->select('id')
            ->where('prf_header_id', (int) $prf_header_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('prf_payment_tracking')
            ->row();

        if ($latest && (int) $latest->id === (int) $payment_id) {
            $this->db->where('id', (int) $prf_header_id)->update('prf_header', [
                'payment_date' => $payment_data['payment_date'],
                'payment_method' => $payment_data['payment_method'] ?? null,
                'payment_proof_path' => $payment_data['payment_proof_path'] ?? $row->payment_proof_path,
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Get unpaid PRFs for notification (monthly payment reminder)
     */
    public function get_unpaid_prfs() {
        $this->db->select('
            ph.*,
            u.fullname as requester_name
        ', FALSE);
        $this->db->from('prf_header ph');
        $this->db->join('users u', 'ph.requester_id = u.id', 'left');
        $this->db->where('ph.payment_status', 'Unpaid');
        // 'Approved' = new 3-stage final. 'Finance Approved' = legacy 2-stage final, dipertahankan utk data lama.
        $this->db->where_in('ph.status', ['Approved', 'Finance Approved']);
        $this->db->order_by('ph.prf_date', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Delete PRF.
     * Default: hanya status Draft. Admin override (role Admin) boleh hapus
     * di status approval (Submitted s/d Approved) + terminal (Rejected/Cancelled).
     * TIDAK boleh hapus PRF yang sudah Paid / Partial — karena pembayaran sudah
     * masuk ke jurnal & menghapus tracking-nya akan membuat jurnal jadi orphan.
     */
    public function delete_prf($id, $admin_override = FALSE) {
        if ($admin_override) {
            $h = $this->get_prf_header_by_id($id);
            if (!$h) return false;
            if ($h->status === 'Paid' || in_array($h->payment_status ?? 'Unpaid', ['Paid','Partial'], true)) {
                return false;
            }
            // Sudah ada AP Invoice + jurnal hutang -> kembalikan ke Draft dulu
            // (jurnalnya dibalik), baru boleh dihapus.
            if (!empty($h->ap_invoice_header_id)) {
                $this->last_error = 'Payment Request ini sudah membentuk hutang (AP Invoice) & jurnal. Kembalikan ke Draft dulu (jurnal dibalik otomatis), baru hapus.';
                return false;
            }
        }

        if (!$admin_override) {
            $this->db->where('status', 'Draft');
        } else {
            // Bersihkan child tables yang tidak terikat FK CASCADE supaya tidak
            // jadi orphan. prf_payment_tracking sengaja TIDAK disentuh karena
            // payment dijaga oleh guard di atas (Paid/Partial diblok).
            // Catatan: tiap ->delete() otomatis reset query builder (termasuk
            // where() yang sudah di-set), jadi where('id', $id) untuk delete
            // prf_header di bawah HARUS di-set ulang setelah semua ini,
            // bukan di awal fungsi.
            $this->db->where('prf_header_id', $id)->delete('prf_approval_logs');
            $this->db->where('prf_header_id', $id)->delete('prf_attachments');
            $this->db->where('prf_header_id', $id)->delete('prf_details');
        }

        $this->db->where('id', $id);
        return $this->db->delete('prf_header');
    }

    /**
     * Attachments
     */
    public function get_attachments($prf_header_id) {
        return $this->db->where('prf_header_id', $prf_header_id)
            ->order_by('id', 'ASC')
            ->get('prf_attachments')->result();
    }

    public function get_attachment($id) {
        return $this->db->where('id', $id)->get('prf_attachments')->row();
    }

    public function add_attachment($data) {
        $this->db->insert('prf_attachments', $data);
        return $this->db->insert_id();
    }

    public function update_attachment($id, $data) {
        return $this->db->where('id', $id)->update('prf_attachments', $data);
    }

    public function delete_attachment($id) {
        return $this->db->where('id', $id)->delete('prf_attachments');
    }

    /**
     * Get list approved POs (status = Confirmed) that can be pulled into PRF
     * Available untuk semua user (tidak filter by creator)
     */
    public function get_approved_pos_for_pull($user_id = null, $type = null) {
        $this->db->select('
            h.id, h.po_number, h.po_date, h.type, h.has_ppn,
            s.nama_supplier, s.id as supplier_id,
            COALESCE(SUM(d.total_local), 0) as subtotal
        ', FALSE);
        $this->db->from('po_header h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('po_details d', 'h.id = d.po_header_id', 'left');
        $this->db->where('h.status', 'Confirmed');
        if (!empty($type)) {
            $this->db->where('h.type', $type);
        }
        $this->db->group_by('h.id, h.po_number, h.po_date, h.type, h.has_ppn, s.nama_supplier, s.id');
        $this->db->order_by('h.po_date', 'DESC');
        $this->db->order_by('h.id', 'DESC');

        $result = $this->db->get()->result();
        foreach ($result as $row) {
            $row->total_amount = $row->subtotal;
            if ($row->has_ppn == 1) {
                $row->total_amount = $row->subtotal * 1.11;
            }
        }
        return $result;
    }

    /**
     * Get PO details to be pulled into PRF
     */
    public function get_po_details_for_prf($po_id)
    {
        $this->db->select("
            d.id AS po_detail_id,
            d.item_id,
            d.item_type,

            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'inventory' THEN i.kode_barang
                WHEN d.item_type = 'machine' THEN mc.kode_mesin
                WHEN d.item_type = 'jasa' THEN j.kode_jasa
                ELSE NULL
            END AS item_code,

            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'inventory' THEN i.nama_barang
                WHEN d.item_type = 'machine' THEN mc.nama_mesin
                WHEN d.item_type = 'jasa' THEN j.nama_jasa
                ELSE d.description
            END AS description,

            -- Satuan ikut dari master item asal (utk auto-isi kolom Unit Payment Request
            -- saat pull dari PO, ERP Revision Notes poin 3 [[erp_revision_prf_erf]]).
            CASE
                WHEN d.item_type = 'material' THEN m.satuan
                WHEN d.item_type = 'inventory' THEN i.satuan
                WHEN d.item_type = 'machine' THEN mc.satuan
                WHEN d.item_type = 'jasa' THEN j.satuan
                ELSE NULL
            END AS unit,

            d.qty,
            d.local_price AS unit_price,
            d.total_local AS subtotal
        ", FALSE);

        $this->db->from('po_details d');

        $this->db->join('materials m', "m.id = d.item_id AND d.item_type = 'material'", 'left');
        $this->db->join('inventories i', "i.id = d.item_id AND d.item_type = 'inventory'", 'left');
        $this->db->join('machines mc', "mc.id = d.item_id AND d.item_type = 'machine'", 'left');
        $this->db->join('jasa j', "j.id = d.item_id AND d.item_type = 'jasa'", 'left');

        $this->db->where('d.po_header_id', $po_id);
        $this->db->order_by('d.id', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Get PO header info (supplier, etc) for PRF creation
     */
    public function get_po_header_for_prf($po_id) {
        // Nama Akun ikut dibawa supaya form PRF bisa menampilkannya saat PO dipilih.
        $this->db->select("
            h.id, h.po_number, h.type, h.supplier_id,
            s.nama_supplier, s.bank_name, s.bank_account"
            . (db_has_column('suppliers', 'bank_account_name') ? ', s.bank_account_name' : '')
        , FALSE);
        $this->db->from('po_header h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->where('h.id', $po_id);
        $this->db->where_in('h.status', ['Confirmed', 'Closed']);
        return $this->db->get()->row();
    }
}
