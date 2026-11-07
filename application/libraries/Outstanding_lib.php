<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Outstanding_lib
 * ----------------
 * Membangun daftar "Outstanding Task" untuk user yang sedang login,
 * berdasarkan permission (role_permissions.can_view / can_confirm) per modul.
 *
 * Cara kerja:
 *   - Tiap task didaftarkan di register_tasks() dengan:
 *       module: menu_key di tabel menus (untuk cek permission)
 *       action: 'view' atau 'confirm' (dipakai check_access)
 *       query : closure yang return array baris pending
 *   - get_for_user() mem-filter task sesuai akses user, lalu menjalankan query.
 *
 * Menambah task baru = tambah 1 entri di register_tasks(). Tidak perlu
 * menyentuh controller / view. Role baru cukup diberi permission lewat
 * Role Access — outstanding task auto ikut.
 */
class Outstanding_lib
{
    protected $CI;
    protected $tasks = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('auth');
        $this->register_tasks();
    }

    /**
     * Daftar semua task. Tiap task = 1 jenis dokumen pending di sebuah modul.
     */
    protected function register_tasks()
    {
        $db = $this->CI->db;

        $this->tasks = [

            // ============================================================
            // [TIDAK BERLAKU LAGI sejak 2026-11-06: PR punya approval 1 tahap lagi,
            //  task-nya ada di blok "APPROVAL MATERIAL REQUEST & PURCHASE REQUEST".]
            // PURCHASE REQUEST — TASK APPROVAL DIHAPUS (2026-09-07)
            // ============================================================
            // Dulu ada 3 task: 'pr_approval' (Produksi/MPR),
            // 'pr_general_approval' (Non-Produksi/IPR) & 'pr_jasa_approval'.
            // SEMUANYA DIHAPUS karena PR sekarang TIDAK PUNYA TAHAP APPROVAL
            // lagi — user 2026-09-07: "approval IPR harusnya udah ga ada
            // karena itu kan ga ada approval lagi".
            //
            // Alasannya di kode: setiap PR sekarang lahir SUDAH ter-approve.
            //  - IPR (non_produksi/jasa/asset) dibuat Consumable_outstanding::
            //    create_pr() dari IRF yang SUDAH lolos approval IRF -> langsung
            //    status 'Approved' + kolom HOD/Endorsement/Final terisi.
            //  - MPR (produksi) sama, lewat Material_outstanding::create_pr()
            //    dari MRF.
            // Pembuatan manual sudah diblokir di Purchase_request_general::
            // create()/add_header(). Jadi antrian approval PR SELALU kosong,
            // dan kalaupun terisi itu pasti sisa data lama (mis. PWH00006 di
            // production) atau kasus tepi Admin return-to-draft — bukan
            // pekerjaan rutin yang layak ditagih di dashboard.
            //
            // Menu "PR Approval"-nya SENGAJA TIDAK dihapus: masih dipakai utk
            // melihat riwayat & menangani kasus tepi itu.

            // ============================================================
            // APPROVAL MATERIAL REQUEST & PURCHASE REQUEST (2026-11-06)
            // ------------------------------------------------------------
            // MR: 1 tahap Head of Dept. PR: 1 tahap (General/Jasa/Asset izin
            // 'approval', Material izin 'confirm'). Status tengah alur 3 tahap
            // lama tetap ikut supaya dokumen lama tidak nyangkut.
            // ============================================================
            'so_approval' => [
                'label'     => 'Sales Order Menunggu Approval',
                'label_key' => 'dashboard_task_so_approval',
                'module' => 'sales_contract_approval',
                'action' => 'approval',
                'icon'   => 'fas fa-file-signature',
                'color'  => 'warning',
                'url'    => 'sales_contract/approval',
                'query'  => function() use ($db) {
                    return $db->select('id, sc_number AS doc_number, contract_date AS doc_date, status')
                        ->from('sales_contracts')
                        ->where_in('status', ['Submitted', 'Pending Endorsement', 'Endorsed'])
                        ->order_by('contract_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'mr_approval' => [
                'label'     => 'Material Request Menunggu Approval',
                'label_key' => 'dashboard_task_mr_approval',
                'module' => 'material_request_approval',
                'action' => 'head_dept',
                'icon'   => 'fas fa-clipboard-check',
                'color'  => 'warning',
                'url'    => 'material_request/approval',
                'access' => function() {
                    return !empty($this->approval_stages('material_request_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->approval_stages('material_request_approval');
                    if (empty($stages)) return [];
                    $scope_sql = approval_stage_scope_sql('material_request_approval', 'status', 'department_code', ['head_dept' => ['Submitted'], 'endorsement' => ['Approved Head Dept'], 'approval' => ['Endorsed']]);
                    return $db->select('id, mr_number AS doc_number, request_date AS doc_date, status')
                        ->from('mr_header')
                        ->where_in('status', $stages)
                        ->where($scope_sql, null, false)
                        ->order_by('request_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'fgr_approval' => [
                'label'     => 'FG Receipt Menunggu Approval',
                'label_key' => 'dashboard_task_fgr_approval',
                'module' => 'fg_receipt_approval',
                'action' => 'head_dept',
                'icon'   => 'fas fa-boxes',
                'color'  => 'warning',
                'url'    => 'fg_receipt/approval',
                'access' => function() {
                    return !empty($this->approval_stages('fg_receipt_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->approval_stages('fg_receipt_approval');
                    if (empty($stages)) return [];
                    return $db->select('id, receipt_number AS doc_number, receipt_date AS doc_date, status')
                        ->from('fg_receipt_header')
                        ->where_in('status', $stages)
                        ->order_by('receipt_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'pr_general_approval' => [
                'label'     => 'Purchase Request Menunggu Approval',
                'label_key' => 'dashboard_task_pr_general_approval',
                'module' => 'purchase_request_approval_general',
                'action' => 'approval',
                'icon'   => 'fas fa-file-invoice',
                'color'  => 'warning',
                'url'    => 'purchase_request_approval_general/approval',
                'access' => function() {
                    return !empty($this->pr_approval_types());
                },
                'query'  => function() use ($db) {
                    $types = $this->pr_approval_types();
                    if (empty($types)) return [];
                    return $db->select('id, pr_number AS doc_number, pr_date AS doc_date, status')
                        ->from('pr_header')
                        ->where_in('status', ['Submitted', 'Approved Head Dept', 'Endorsed'])
                        ->where_in('type', $types)
                        ->order_by('pr_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'pr_material_approval' => [
                'label'     => 'PR Material Menunggu Approval',
                'label_key' => 'dashboard_task_pr_material_approval',
                'module' => 'purchase_request_approval',
                'action' => 'confirm',
                'icon'   => 'fas fa-file-invoice',
                'color'  => 'warning',
                'url'    => 'purchase_request_approval',
                'query'  => function() use ($db) {
                    return $db->select('id, pr_number AS doc_number, pr_date AS doc_date, status')
                        ->from('pr_header')
                        ->where('status', 'Submitted')
                        ->where('type', 'produksi')
                        ->order_by('pr_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // PURCHASE ORDER
            // ============================================================
            'po_approval' => [
                'label'     => 'PO Menunggu Approval',
                'label_key' => 'dashboard_task_po_approval',
                'module' => 'purchase_order_approval',
                'action' => 'confirm',
                'icon'   => 'fas fa-file-signature',
                'color'  => 'warning',
                'url'    => 'purchase_order_approval',
                // 3-stage: tampil sesuai stage yang boleh di-action user.
                'access' => function() {
                    return !empty($this->po_approval_stages('purchase_order_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->po_approval_stages('purchase_order_approval');
                    if (empty($stages)) return [];
                    return $db->select('id, po_number AS doc_number, po_date AS doc_date, status')
                        ->from('po_header')
                        ->where_in('status', $stages)
                        ->where('(type = "produksi" OR type IS NULL)', null, false)
                        ->order_by('po_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'po_general_approval' => [
                'label'     => 'PO Non-Produksi Menunggu Approval',
                'label_key' => 'dashboard_task_po_general_approval',
                'module' => 'purchase_order_approval_general',
                'action' => 'confirm',
                'icon'   => 'fas fa-file-signature',
                'color'  => 'warning',
                'url'    => 'purchase_order_approval_general',
                // 3-stage: tampil sesuai stage yang boleh di-action user.
                'access' => function() {
                    return !empty($this->po_approval_stages('purchase_order_approval_general'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->po_approval_stages('purchase_order_approval_general');
                    if (empty($stages)) return [];
                    return $db->select('id, po_number AS doc_number, po_date AS doc_date, status')
                        ->from('po_header')
                        ->where_in('status', $stages)
                        ->where('type', 'non_produksi')
                        ->order_by('po_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // PURCHASE ORDER (Jasa / Service) — same table, `type` = 'jasa'
            'po_jasa_approval' => [
                'label'     => 'PO Jasa Menunggu Approval',
                'label_key' => 'dashboard_task_po_jasa_approval',
                'module'    => 'purchase_order_approval_jasa',
                'action'    => 'confirm',
                'icon'      => 'fas fa-file-signature',
                'color'     => 'warning',
                'url'       => 'purchase_order_approval_jasa',
                // 3-stage: tampil sesuai stage yang boleh di-action user.
                'access' => function() {
                    return !empty($this->po_approval_stages('purchase_order_approval_jasa'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->po_approval_stages('purchase_order_approval_jasa');
                    if (empty($stages)) return [];
                    return $db->select('id, po_number AS doc_number, po_date AS doc_date, status')
                        ->from('po_header')
                        ->where_in('status', $stages)
                        ->where('type', 'jasa')
                        ->order_by('po_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // MATERIAL REQUEST / ISSUANCE
            // ============================================================
            'mr_pending_issuance' => [
                'label'     => 'Material Request Menunggu Dikeluarkan',
                'label_key' => 'dashboard_task_mr_pending_issuance',
                'module' => 'Material_issuance',
                'action' => 'confirm',
                'icon'   => 'fas fa-dolly',
                'color'  => 'info',
                'url'    => 'material_issuance',
                'query'  => function() use ($db) {
                    return $db->select('id, mr_number AS doc_number, request_date AS doc_date')
                        ->from('mr_header')
                        ->where_in('status', ['Approved', 'Partial Issued'])
                        ->order_by('request_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'material_outstanding' => [
                'label'     => 'Outstanding Material Request',
                'label_key' => 'dashboard_task_material_outstanding',
                'module' => 'material_outstanding',
                'action' => 'view',
                'icon'   => 'fas fa-boxes',
                'color'  => 'info',
                'url'    => 'material_outstanding',
                'query'  => function() use ($db) {
                    // MR yang sudah Issued tapi belum Received user
                    return $db->select('id, mr_number AS doc_number, issued_at AS doc_date')
                        ->from('mr_header')
                        ->where('status', 'Issued')
                        ->order_by('issued_at', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // CONSUMABLE (HRGA / General)
            // ============================================================
            'consumable_approval' => [
                'label'     => 'Permintaan Barang Menunggu Approval',
                'label_key' => 'dashboard_task_consumable_approval',
                'module' => 'consumable_request_approval',
                'action' => 'confirm',
                'icon'   => 'fas fa-clipboard-list',
                'color'  => 'warning',
                'url'    => 'consumable_request/approval',
                // 3-stage: tampil sesuai stage yang boleh di-action user.
                'access' => function() {
                    return !empty($this->approval_stages('consumable_request_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->approval_stages('consumable_request_approval');
                    if (empty($stages)) return [];
                    // Tahap 3: hanya departemen dalam cakupan user per tahap. Dihitung
                    // sebelum builder dirakit (helper menjalankan query sendiri).
                    $scope_sql = approval_stage_scope_sql('consumable_request_approval', 'status', 'department', ['head_dept' => ['Submitted'], 'endorsement' => ['Approved Head Dept', 'Head Approved'], 'approval' => ['Endorsed']]);
                    return $db->select('id, request_number AS doc_number, request_date AS doc_date, status')
                        ->from('consumable_request_header')
                        ->where_in('status', $stages)
                        ->where($scope_sql, null, false)
                        ->order_by('request_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'consumable_outstanding' => [
                'label'     => 'Outstanding Permintaan Barang',
                'label_key' => 'dashboard_task_consumable_outstanding',
                'module' => 'consumable_outstanding',
                'action' => 'view',
                'icon'   => 'fas fa-box-open',
                'color'  => 'info',
                // Diarahkan ke IRF List (consumable_request) dgn filter
                // "outstanding_only" ON (user 2026-09-06) -- BUKAN lagi ke
                // controller Consumable_outstanding terpisah. Checkbox filter
                // ini SUDAH ADA di consumable_request/index.php (dipakai jg
                // oleh tombol "Lihat Outstanding" di halaman itu sendiri,
                // walau tombol itu masih ke consumable_outstanding -- di luar
                // scope perubahan ini, tidak diubah). module/action gate izin
                // TETAP 'consumable_outstanding' (siapa yg sebelumnya lihat
                // task ini, tetap lihat -- cuma tujuannya yg berubah).
                'url'    => 'consumable_request?outstanding_only=1',
                'query'  => function() use ($db) {
                    // Consumable yang sudah approved DAN masih punya item outstanding
                    // (qty_requested > qty_issued). IRF yang sudah release penuh berstatus
                    // 'Released' (di-sync Consumable_out_model), tapi cek EXISTS tetap perlu
                    // untuk release parsial yang statusnya masih 'Approved'. Logika ini mirror
                    // Consumable_outstanding_model agar count dashboard konsisten dengan
                    // halaman Outstanding Consumable Request.
                    $sql = "
                        SELECT cr_h.id, cr_h.request_number AS doc_number, cr_h.request_date AS doc_date
                        FROM consumable_request_header cr_h
                        WHERE cr_h.status = 'Approved'
                          AND EXISTS (
                              SELECT 1
                              FROM consumable_request_details cr_d
                              LEFT JOIN (
                                  SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                                  FROM consumable_out_details co_d
                                  INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                                  WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                                  GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
                              ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id
                                      AND issued.item_type = cr_d.item_type
                              WHERE cr_d.header_id = cr_h.id
                                AND (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0)) > 0
                          )
                        ORDER BY cr_h.request_date ASC
                        LIMIT 20
                    ";
                    return $db->query($sql)->result();
                },
            ],

            // ============================================================
            // GRN / WAREHOUSE IN
            // ============================================================
            'grn_draft' => [
                'label'     => 'GRN Draft Menunggu Konfirmasi',
                'label_key' => 'dashboard_task_grn_draft',
                'module' => 'warehouse_in',
                'action' => 'confirm',
                'icon'   => 'fas fa-truck-loading',
                'color'  => 'primary',
                'url'    => 'warehouse_in',
                'query'  => function() use ($db) {
                    return $db->select('id, grn_number AS doc_number, grn_date AS doc_date')
                        ->from('grn_header')
                        ->where('status', 'Draft')
                        ->order_by('grn_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // QUALITY ASSURANCE
            // ============================================================
            'qa_pending' => [
                'label'     => 'QA Test Pending',
                'label_key' => 'dashboard_task_qa_pending',
                'module' => 'quality_assurance',
                'action' => 'confirm',
                'icon'   => 'fas fa-vial',
                'color'  => 'info',
                'url'    => 'quality_assurance',
                'query'  => function() use ($db) {
                    return $db->select('id, qa_number AS doc_number, qa_date AS doc_date')
                        ->from('qa_header')
                        ->where_in('status', ['Pending', 'In Progress'])
                        ->order_by('qa_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // WORK ORDER & PRODUCTION
            // ============================================================
            'wo_planned' => [
                'label'     => 'WO Belum Dimulai',
                'label_key' => 'dashboard_task_wo_planned',
                'module' => 'work_order',
                'action' => 'confirm',
                'icon'   => 'fas fa-cogs',
                'color'  => 'primary',
                'url'    => 'work_order',
                'query'  => function() use ($db) {
                    return $db->select('id, wo_number AS doc_number, planned_start_date AS doc_date')
                        ->from('wo_header')
                        ->where('status', 'Planned')
                        ->order_by('planned_start_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'production_draft' => [
                'label'     => 'Produksi Draft / In Progress',
                'label_key' => 'dashboard_task_production_draft',
                'module' => 'production_process',
                'action' => 'confirm',
                'icon'   => 'fas fa-industry',
                'color'  => 'primary',
                'url'    => 'production_process',
                'query'  => function() use ($db) {
                    return $db->select('id, production_number AS doc_number, production_date AS doc_date')
                        ->from('production_header')
                        ->where_in('status', ['Draft', 'In Progress'])
                        ->order_by('production_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // FG DELIVERY (DO)
            // ============================================================
            'fg_delivery_draft' => [
                'label'     => 'DO Draft Menunggu Konfirmasi',
                'label_key' => 'dashboard_task_fg_delivery_draft',
                'module' => 'fg_delivery',
                'action' => 'confirm',
                'icon'   => 'fas fa-truck',
                'color'  => 'warning',
                'url'    => 'fg_delivery',
                'query'  => function() use ($db) {
                    return $db->select('id, delivery_number AS doc_number, delivery_date AS doc_date')
                        ->from('fg_delivery_header')
                        ->where('status', 'Draft')
                        ->order_by('delivery_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // STOCK OPNAME & ADJUSTMENT
            // ============================================================
            'opname_draft' => [
                'label'     => 'Stock Opname Belum Selesai',
                'label_key' => 'dashboard_task_opname_draft',
                'module' => 'stockopname',
                'action' => 'confirm',
                'icon'   => 'fas fa-clipboard',
                'color'  => 'info',
                'url'    => 'stockopname',
                'query'  => function() use ($db) {
                    return $db->select('id, opname_no AS doc_number, opname_date AS doc_date')
                        ->from('opname_header')
                        ->where_in('status', ['Draft', 'Counting'])
                        ->order_by('opname_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'adjustment_draft' => [
                'label'     => 'Stock Adjustment Draft',
                'label_key' => 'dashboard_task_adjustment_draft',
                'module' => 'adjustment',
                'action' => 'confirm',
                'icon'   => 'fas fa-balance-scale',
                'color'  => 'info',
                'url'    => 'adjustment',
                'query'  => function() use ($db) {
                    return $db->select('id, doc_no AS doc_number, doc_date')
                        ->from('adjustment_header')
                        ->where('status', 'Draft')
                        ->order_by('doc_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // PRF (Payment Request Form)
            // ============================================================
            'prf_pending_approval' => [
                'label'     => 'Payment Request Menunggu Approval',
                'label_key' => 'dashboard_task_prf_pending_approval',
                'module' => 'prf_approval',
                'action' => 'view',
                'icon'   => 'fas fa-money-check-alt',
                'color'  => 'warning',
                'url'    => 'prf/approval_queue',
                // 3-stage: hanya tampilkan PRF di stage yang boleh di-action user,
                // bukan semua PRF pending (dulu pakai action 'view' → over-inclusive).
                'access' => function() {
                    return !empty($this->approval_stages('prf_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->approval_stages('prf_approval');
                    if (empty($stages)) return [];
                    // Tahap 3: hanya departemen dalam cakupan user per tahap. Dihitung
                    // sebelum builder dirakit (helper menjalankan query sendiri).
                    $scope_sql = approval_stage_scope_sql('prf_approval', 'status', 'department_code', ['head_dept' => ['Submitted'], 'endorsement' => ['Approved Head Dept', 'Head Approved'], 'approval' => ['Endorsed']]);
                    return $db->select('id, prf_number AS doc_number, prf_date AS doc_date, status')
                        ->from('prf_header')
                        ->where_in('status', $stages)
                        ->where($scope_sql, null, false)
                        ->order_by('prf_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // ERF (Employee Reimbursement Form) — workflow 3-stage spt PRF
            // ============================================================
            'erf_pending_approval' => [
                'label'     => 'Reimburse Request Menunggu Approval',
                'label_key' => 'dashboard_task_erf_pending_approval',
                'module' => 'erf_approval',
                'action' => 'view',
                'icon'   => 'fas fa-hand-holding-usd',
                'color'  => 'warning',
                'url'    => 'erf/approval',
                // 3-stage: hanya tampilkan ERF di stage yang boleh di-action user.
                'access' => function() {
                    return !empty($this->approval_stages('erf_approval'));
                },
                'query'  => function() use ($db) {
                    $stages = $this->approval_stages('erf_approval');
                    if (empty($stages)) return [];
                    // Tahap 3: hanya departemen dalam cakupan user per tahap. Dihitung
                    // sebelum builder dirakit (helper menjalankan query sendiri).
                    $scope_sql = approval_stage_scope_sql('erf_approval', 'status', 'department_code', ['head_dept' => ['Submitted'], 'endorsement' => ['Approved Head Dept', 'Head Approved'], 'approval' => ['Endorsed']]);
                    return $db->select('id, erf_number AS doc_number, erf_date AS doc_date, status')
                        ->from('erf_header')
                        ->where_in('status', $stages)
                        ->where($scope_sql, null, false)
                        ->order_by('erf_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // FINANCE - AR / AP
            // ============================================================
            'ar_unpaid' => [
                'label'     => 'AR Invoice Belum Lunas',
                'label_key' => 'dashboard_task_ar_unpaid',
                'module' => 'arinvoice',
                'action' => 'view',
                'icon'   => 'fas fa-file-invoice-dollar',
                'color'  => 'danger',
                'url'    => 'arinvoice',
                'query'  => function() use ($db) {
                    return $db->select('id, invoice_no AS doc_number, invoice_date AS doc_date')
                        ->from('ar_invoice_header')
                        ->where('status', 'Posted')
                        ->where("(payment_status IS NULL OR payment_status != 'Paid')", null, false)
                        ->order_by('invoice_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'ap_unpaid' => [
                'label'     => 'AP Invoice Belum Dibayar',
                'label_key' => 'dashboard_task_ap_unpaid',
                'module' => 'apinvoice',
                'action' => 'view',
                'icon'   => 'fas fa-file-invoice',
                'color'  => 'danger',
                'url'    => 'apinvoice',
                'query'  => function() use ($db) {
                    return $db->select('id, invoice_no AS doc_number, invoice_date AS doc_date')
                        ->from('ap_invoice_header')
                        ->where('status', 'Posted')
                        ->where("(payment_status IS NULL OR payment_status != 'Paid')", null, false)
                        ->order_by('invoice_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            // ============================================================
            // SUBKON & SCRAP OUT
            // ============================================================
            'subkon_out_draft' => [
                'label'     => 'Subkon Out Draft',
                'label_key' => 'dashboard_task_subkon_out_draft',
                'module' => 'subkon_out',
                'action' => 'confirm',
                'icon'   => 'fas fa-shipping-fast',
                'color'  => 'info',
                'url'    => 'subkon_out',
                'query'  => function() use ($db) {
                    return $db->select('id, subkon_out_no AS doc_number, shipping_date AS doc_date')
                        ->from('subkon_out_header')
                        ->where('status', 'Draft')
                        ->order_by('shipping_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

            'scrap_out_draft' => [
                'label'     => 'Scrap Out Draft',
                'label_key' => 'dashboard_task_scrap_out_draft',
                'module' => 'scrap_out',
                'action' => 'confirm',
                'icon'   => 'fas fa-trash-alt',
                'color'  => 'info',
                'url'    => 'Scrap_Out',
                'query'  => function() use ($db) {
                    return $db->select('id, doc_no AS doc_number, doc_date')
                        ->from('scrap_out_header')
                        ->where('status', 'Draft')
                        ->order_by('doc_date', 'ASC')
                        ->limit(20)->get()->result();
                },
            ],

        ];
    }

    /**
     * Daftar status (stage) yang BOLEH di-action user pada modul approval 3-stage,
     * dihitung dari permission stage-nya. Mirror logika di model get_pending_approvals():
     *   can_head_dept   → 'Submitted'
     *   can_endorsement → 'Approved Head Dept' (+ legacy 'Head Approved' utk PRF lama)
     *   can_approval    → 'Endorsed'
     * Kalau user tak punya satupun stage → array kosong (task disembunyikan).
     *
     * @param string $module menu_key modul approval (mis. 'purchase_order_approval')
     * @return string[]
     */
    /** Tipe PR (non_produksi/jasa/asset) yang boleh di-approve user ini (izin 'approval'). */
    protected function pr_approval_types()
    {
        $types = [];
        foreach (['non_produksi' => 'purchase_request_approval_general', 'jasa' => 'purchase_request_approval_jasa', 'asset' => 'purchase_request_approval_asset'] as $t => $menu) {
            if (check_access($menu, 'approval')) $types[] = $t;
        }
        return $types;
    }

    protected function approval_stages($module)
    {
        $stages = [];
        if (check_access($module, 'head_dept'))   { $stages[] = 'Submitted'; }
        if (check_access($module, 'endorsement')) { $stages[] = 'Approved Head Dept'; $stages[] = 'Head Approved'; }
        if (check_access($module, 'approval'))     { $stages[] = 'Endorsed'; }
        return array_values(array_unique($stages));
    }

    /**
     * Versi khusus PO. PO cuma punya SATU gate approval (ERP Revision Notes
     * Fase 2 poin 7), jadi pemegang izin 'approval' menangani PO yang baru
     * berstatus 'Submitted' — bukan 'Endorsed'.
     *
     * Sebelum 2026-09-09 hal ini "kebetulan jalan" karena submit PO otomatis
     * dilompatkan ke 'Endorsed'; lompatan itu sudah dihapus (memalsukan 2
     * tanda tangan), jadi pemetaan stage-nya harus eksplisit di sini.
     * 'Endorsed' tetap dibawa supaya PO lama tidak hilang dari outstanding.
     */
    protected function po_approval_stages($module)
    {
        $stages = $this->approval_stages($module);
        if (check_access($module, 'approval')) { $stages[] = 'Submitted'; }
        return array_values(array_unique($stages));
    }

    /**
     * Ambil semua task yang relevan untuk user yang sedang login.
     * Return: array of [
     *   'key', 'label', 'label_key', 'icon', 'color', 'url', 'count', 'items'
     * ]
     * Item pending dengan list kosong di-skip otomatis.
     */
    public function get_for_user()
    {
        $result = [];

        $current_user_id = (int) $this->CI->session->userdata('user_id');

        foreach ($this->tasks as $key => $task) {
            // Sembunyikan task tertentu untuk user spesifik (override per-user),
            // terlepas dari permission role-nya.
            if (!empty($task['hide_for_users']) && in_array($current_user_id, $task['hide_for_users'])) {
                continue;
            }

            // Skip kalau user tidak punya akses modul ini.
            // Task multi-stage (PR/PO/PRF approval) memakai closure 'access' sendiri
            // karena gate-nya bukan satu flag (can_confirm) melainkan kombinasi
            // can_head_dept / can_endorsement / can_approval.
            if (isset($task['access'])) {
                if (!call_user_func($task['access'])) {
                    continue;
                }
            } elseif (!check_access($task['module'], $task['action'])) {
                continue;
            }

            try {
                $items = call_user_func($task['query']);
            } catch (Exception $e) {
                log_message('error', "Outstanding_lib task[$key] failed: " . $e->getMessage());
                continue;
            }

            if (empty($items)) {
                continue;
            }

            $result[] = [
                'key'       => $key,
                'label'     => $task['label'],
                'label_key' => $task['label_key'] ?? null,
                'icon'      => $task['icon'] ?? 'fas fa-tasks',
                'color'     => $task['color'] ?? 'primary',
                'url'       => $task['url'] ?? '#',
                'count'     => count($items),
                'items'     => $items,
            ];
        }

        return $result;
    }

    /**
     * Total dokumen pending across all relevant tasks.
     */
    public function total_count_for_user()
    {
        $total = 0;
        foreach ($this->get_for_user() as $task) {
            $total += $task['count'];
        }
        return $total;
    }
}
