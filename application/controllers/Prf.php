<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class Prf extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Prf_model', 'model');
        $this->load->model('JurnalUmum_model', 'journal_model');
        $this->load->model('Supplier_model');
        $this->load->model('Commoncode_model');
        $this->load->model('Ap_auto_model');
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    // ============================================================
    // HUTANG OTOMATIS (2026-11-06): tarik PO / AP Invoice -> AP + jurnal
    // ============================================================

    /** Ambil baris item mentah dari form (termasuk jejak PO / AP / jenis baris). */
    private function _raw_details_input() {
        $details = $this->input->post('details');
        if (empty($details) || !is_array($details)) return [];
        $out = [];
        foreach ($details as $d) {
            if (!is_array($d)) continue;
            $out[] = [
                'description' => trim((string) ($d['description'] ?? '')),
                'qty' => (float) ($d['qty'] ?? 0),
                'unit' => trim((string) ($d['unit'] ?? '')),
                'unit_price' => (float) ($d['unit_price'] ?? 0),
                'po_header_id' => (int) ($d['po_header_id'] ?? 0),
                'po_detail_id' => (int) ($d['po_detail_id'] ?? 0),
                'ap_invoice_header_id' => (int) ($d['ap_invoice_header_id'] ?? 0),
                'line_type' => ($d['line_type'] ?? 'item') === 'ppn' ? 'ppn' : 'item',
            ];
        }
        return $out;
    }

    /** Data pendukung form item (PO yang masih bisa ditagih, AP Invoice terbuka, info baris lama). */
    private function _items_form_data($prf_id = null, $details = []) {
        $po_meta = [];
        $po_ids = [];
        foreach ($details as $d) if (!empty($d->po_detail_id)) $po_ids[] = (int) $d->po_header_id;
        if ($po_ids) {
            foreach ($this->Ap_auto_model->po_line_status($po_ids, $prf_id) as $r) {
                $po_meta[(int) $r->po_detail_id] = [
                    'max' => $r->remaining_qty, 'po_number' => $r->po_number,
                    'ppn_applied' => $r->ppn_applied, 'ppn_rate' => $r->ppn_rate, 'ppn_inclusive' => $r->ppn_inclusive,
                    'local_price' => $r->local_price, 'po_header_id' => (int) $r->po_header_id,
                ];
            }
        }
        $ap_meta = [];
        $ap_ids = [];
        foreach ($details as $d) if (!empty($d->ap_invoice_header_id)) $ap_ids[] = (int) $d->ap_invoice_header_id;
        if ($ap_ids) {
            foreach ($this->Ap_auto_model->get_open_ap_invoices(null, $prf_id, $ap_ids) as $ap) {
                $ap_meta[(int) $ap->id] = ['max' => $ap->outstanding, 'invoice_no' => $ap->invoice_no];
            }
        }
        return [
            'billable_pos' => $this->Ap_auto_model->get_billable_pos(null, $prf_id),
            'open_aps' => array_values($this->Ap_auto_model->get_open_ap_invoices(null, $prf_id)),
            'line_po_meta' => $po_meta,
            'line_ap_meta' => $ap_meta,
        ];
    }

    /**
     * List semua PRF
     */
    // ============================================================
    // CAKUPAN DEPARTEMEN (Role Level tahap 3)
    // ============================================================

    /** Departemen PRF: kolom department_code, fallback departemen profil pemohon (PRF lama). */
    private function _doc_dept($prf) {
        if (!empty($prf->department_code)) return $prf->department_code;
        return $this->model->get_user_department_code($prf->requester_id);
    }

    /** PRF milik sendiri ATAU departemen yang boleh dia edit/submit/hapus (mis. Supervisor). */
    private function _own_scope_sql() {
        $conds = ['ph.requester_id = ' . (int) $this->session->userdata('user_id')];
        $colleague = dept_scope_sql('prf', ['edit', 'submit', 'delete'], 'ph.department_code', true);
        if ($colleague !== '1=0') $conds[] = $colleague;
        return implode(' OR ', $conds);
    }

    /** 403 kalau PRF bukan departemen yang boleh ditangani di tahap approval ini. */
    private function _require_approval_scope($id, $action = null) {
        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) show_404();
        $action = $action ?: approval_action_for_status($prf->status, Prf_model::APPROVAL_STAGE_MAP);
        if ($action === null || !can_dept_access('prf_approval', $action, $this->_doc_dept($prf))) {
            show_error('Payment Request ini bukan departemen yang bisa Anda tangani di tahap approval ini.', 403);
        }
        return $prf;
    }

    public function index() {
        // Approver juga boleh lihat list (read-only) — supaya bisa search/filter PRF lain.
        $can_view_list = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_list) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to'),
            'status' => 'Draft',
            'payment_status' => null,
            'department_id' => $this->input->get('department_id'),
            // Tahap 3: draft milik sendiri + departemen yang dia tangani. Role lama
            // ('legacy', belum diatur ulang) tetap melihat semua draft seperti dulu.
            'scope_sql' => (is_admin() || dept_scope_is_legacy('prf', ['view', 'add', 'edit', 'submit', 'delete'])) ? null : $this->_own_scope_sql(),
        ];

        $data = [
            'title' => lang_line('title_prf_draft'),
            'prf_list' => $this->model->get_all_prf_headers($filters),
            'filters' => $filters,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * List semua PRF (semua status)
     */
    public function list_all() {
        $can_view_list = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_list) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = $this->_build_list_filters();

        $data = [
            'title' => lang_line('title_prf_list'),
            'prf_list' => $this->model->get_all_prf_headers($filters),
            'filters' => $filters,
            // value=>label. Legacy 'Head Approved' & 'Finance Approved' di-drop dari filter
            // (data lama masih bisa dicari manual via URL). 'Approved' diberi label
            // 'Approve Completed' supaya jelas itu stage final 3-stage approval.
            'statuses' => [
                'Draft'              => 'Draft',
                'Submitted'          => 'Submitted',
                'Approved Head Dept' => 'Approved Head Dept',
                'Endorsed'           => 'Endorsed',
                'Approved'           => 'Approve Completed',
                'Paid'               => 'Paid',
                'Rejected'           => 'Rejected',
                'Cancelled'          => 'Cancelled',
            ],
            'payment_statuses' => ['Unpaid', 'Paid', 'Partial', 'Cancelled']
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/list_all', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Cakupan List PRF. Admin, user Executive & user Accounting selalu melihat
     * SEMUA PRF; user lain: milik sendiri + departemen yang dia tangani
     * (tahap 3).
     * User 2026-09-17: tombol "Lihat Semua PRF" (?show_all=1) dihapus — "buat
     * agar admin, dan executive user emang bisa liat semua prf".
     * User 2026-09-22: Accounting ikut melihat semua — bagian keuangan perlu
     * pandangan menyeluruh, bukan cuma dokumen departemennya sendiri.
     *
     * Tipe user dibaca dari users.user_type, TERPISAH dari role_id (lihat
     * current_user_type() di auth_helper). Jadi menambah orang ke sini cukup
     * lewat Master User, tanpa menyentuh Role Access.
     */
    private function _list_all_scope_sql() {
        if (is_admin() || is_accounting_user()) return null; // tipe Executive dihapus 2026-11-05
        return $this->_own_scope_sql();
    }

    /**
     * Set / update tanggal jatuh tempo (due date) sebuah PRF dari halaman List PRF.
     * Permintaan Accounting. Diakses via AJAX POST { prf_id, due_date }.
     * Izin: user finance (prf_payment) atau yang punya edit PRF / approval.
     */
    public function set_due_date() {
        $can_set = check_access('prf_payment', 'view')
            || check_access('prf', 'edit')
            || check_access('prf_approval', 'approval');
        if (!$can_set) {
            return $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tidak ada izin untuk mengubah due date.']));
        }

        $prf_id   = (int) $this->input->post('prf_id');
        $due_date = $this->input->post('due_date');

        if (!$prf_id || !$this->model->get_prf_header_by_id($prf_id)) {
            return $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Payment Request tidak ditemukan.']));
        }

        // Validasi format tanggal (boleh kosong untuk menghapus due date).
        if (!empty($due_date)) {
            $d = DateTime::createFromFormat('Y-m-d', $due_date);
            if (!$d || $d->format('Y-m-d') !== $due_date) {
                return $this->output
                    ->set_status_header(422)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']));
            }
        } else {
            $due_date = null;
        }

        $this->model->update_due_date($prf_id, $due_date);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'        => true,
                'message'        => 'Due date berhasil disimpan.',
                'due_date'       => $due_date,
                'due_date_label' => $due_date ? date('d-m-Y', strtotime($due_date)) : '-',
            ]));
    }

    /**
     * Bangun filter list_all dari query string (dipakai bersama oleh list_all & export list).
     */
    private function _build_list_filters() {
        return [
            'date_from'      => $this->input->get('date_from'),
            'date_to'        => $this->input->get('date_to'),
            'status'         => $this->input->get('status'),
            'payment_status' => $this->input->get('payment_status'),
            'department_id'  => $this->input->get('department_id'),
            // Cakupan sama persis dengan halaman List PRF (tahap 3), supaya export
            // tidak memuat PRF yang tidak boleh dilihat di layar.
            'requester_id'   => null,
            'scope_sql'      => $this->_list_all_scope_sql(),
        ];
    }

    /**
     * Export seluruh List PRF (sesuai filter aktif) ke Excel — format tabel laporan.
     */
    public function export_list_excel() {
        $can_view_list = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_list) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = $this->_build_list_filters();
        $rows = $this->model->get_all_prf_headers($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payment Request List');

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        // Judul
        $sheet->setCellValue('A1', company_info('company_name'));
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Daftar Payment Request');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

        $period = 'Periode: ' . ($filters['date_from'] ?: 'Semua') . ' s/d ' . ($filters['date_to'] ?: 'Semua');
        $sheet->setCellValue('A3', $period . '   |   Dicetak: ' . date('d-m-Y H:i'));
        $sheet->mergeCells('A3:H3');

        // Header tabel
        $headers = ['No. Payment Request', 'Tanggal', 'Requester', 'Jumlah (Rp)', 'Status', 'Status Bayar', 'Jatuh Tempo', 'Tgl. Bayar'];
        $col = 'A'; $hr = 5;
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $hr, $h);
            $col++;
        }
        $sheet->getStyle('A5:H5')->getFont()->setBold(true);
        $sheet->getStyle('A5:H5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        // Isi
        $r = 6; $total = 0;
        foreach ($rows as $prf) {
            $sheet->setCellValueExplicit('A' . $r, $prf->prf_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $r, $prf->prf_date ? date('d-m-Y', strtotime($prf->prf_date)) : '-');
            $sheet->setCellValue('C' . $r, $prf->requester_name ?? '-');
            $sheet->setCellValue('D' . $r, (float) $prf->subtotal);
            $sheet->setCellValue('E' . $r, $prf->status);
            $sheet->setCellValue('F' . $r, $prf->payment_status);
            $sheet->setCellValue('G' . $r, !empty($prf->due_date) ? date('d-m-Y', strtotime($prf->due_date)) : '-');
            $sheet->setCellValue('H' . $r, $prf->payment_date ? date('d-m-Y', strtotime($prf->payment_date)) : '-');
            $total += (float) $prf->subtotal;
            $r++;
        }
        // Total
        $sheet->setCellValue('C' . $r, 'TOTAL');
        $sheet->getStyle('C' . $r)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $r, $total);
        $sheet->getStyle('D' . $r)->getFont()->setBold(true);

        // Format angka & border
        $sheet->getStyle('D6:D' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A5:H' . $r)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $widths = ['A' => 18, 'B' => 13, 'C' => 22, 'D' => 18, 'E' => 18, 'F' => 14, 'G' => 14, 'H' => 14];
        foreach ($widths as $c => $w) { $sheet->getColumnDimension($c)->setWidth($w); }

        $filename = 'PRF_List_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export seluruh List PRF (sesuai filter aktif) ke PDF — format tabel laporan.
     */
    public function export_list_pdf() {
        $can_view_list = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_list) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = $this->_build_list_filters();
        $rows = $this->model->get_all_prf_headers($filters);

        $total = 0;
        $body = '';
        foreach ($rows as $prf) {
            $total += (float) $prf->subtotal;
            $body .= '<tr>'
                . '<td>' . htmlspecialchars($prf->prf_number) . '</td>'
                . '<td align="center">' . ($prf->prf_date ? date('d-m-Y', strtotime($prf->prf_date)) : '-') . '</td>'
                . '<td>' . htmlspecialchars($prf->requester_name ?? '-') . '</td>'
                . '<td align="right">' . number_format((float) $prf->subtotal, 2, ',', '.') . '</td>'
                . '<td align="center">' . htmlspecialchars($prf->status) . '</td>'
                . '<td align="center">' . htmlspecialchars($prf->payment_status) . '</td>'
                . '<td align="center">' . (!empty($prf->due_date) ? date('d-m-Y', strtotime($prf->due_date)) : '-') . '</td>'
                . '<td align="center">' . ($prf->payment_date ? date('d-m-Y', strtotime($prf->payment_date)) : '-') . '</td>'
                . '</tr>';
        }
        if ($body === '') {
            $body = '<tr><td colspan="8" align="center">Tidak ada data</td></tr>';
        }

        $period = ($filters['date_from'] ?: 'Semua') . ' s/d ' . ($filters['date_to'] ?: 'Semua');
        $html = '
        <style>
            body { font-family: sans-serif; font-size: 10px; }
            h2 { margin: 0; }
            .sub { color: #555; font-size: 10px; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #444; padding: 4px 6px; }
            thead th { background: #D9E1F2; }
            tfoot td { font-weight: bold; }
        </style>
        <h2>' . htmlspecialchars(company_info('company_name')) . '</h2>
        <div class="sub">Daftar Payment Request<br>
            Periode: ' . htmlspecialchars($period) . ' &nbsp;|&nbsp; Dicetak: ' . date('d-m-Y H:i') . '</div>
        <table>
            <thead>
                <tr>
                    <th>No. Payment Request</th><th>Tanggal</th><th>Requester</th><th>Jumlah (Rp)</th>
                    <th>Status</th><th>Status Bayar</th><th>Jatuh Tempo</th><th>Tgl. Bayar</th>
                </tr>
            </thead>
            <tbody>' . $body . '</tbody>
            <tfoot>
                <tr>
                    <td colspan="3" align="right">TOTAL</td>
                    <td align="right">' . number_format($total, 2, ',', '.') . '</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>';

        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'margin_top' => 10, 'margin_bottom' => 10]);
        $mpdf->WriteHTML($html);
        $mpdf->Output('PRF_List_' . date('Ymd_His') . '.pdf', \Mpdf\Output\Destination::INLINE);
        exit;
    }

    /**
     * Approval Queue — list PRF yang menunggu approval (status=Submitted/Head Approved).
     * Menu_key terpisah `prf_approval` untuk permission granular.
     */
    /**
     * Approval Queue — tampilkan PRF sesuai stage approval yang user-nya berwenang:
     *   - can_head_dept   → status 'Submitted'
     *   - can_endorsement → status 'Approved Head Dept' (+ legacy 'Head Approved')
     *   - can_approval    → status 'Endorsed'
     * User yang tidak punya satupun permission approval akan dapat list kosong
     * dengan info banner.
     */
    public function approval_queue() {
        if (!check_access('prf_approval', 'view')) {
            show_error('Tidak ada izin akses Payment Request Approval.', 403);
        }

        $can_head_dept   = check_access('prf_approval', 'head_dept');
        $can_endorsement = check_access('prf_approval', 'endorsement');
        $can_approval    = check_access('prf_approval', 'approval');

        $list = $this->model->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval);

        // Tentukan stage(s) yang ditampilkan untuk informasi UI.
        $shown_stages = [];
        if ($can_head_dept)   $shown_stages[] = 'Submitted';
        if ($can_endorsement) $shown_stages[] = 'Approved Head Dept';
        if ($can_approval)    $shown_stages[] = 'Endorsed';

        $data = [
            'title'            => lang_line('title_prf_approval'),
            'prf_list'         => $list,
            'filters'          => ['date_from'=>null,'date_to'=>null,'status'=>null,'payment_status'=>null,'department_id'=>null],
            'statuses'         => $shown_stages,
            'payment_statuses' => ['Unpaid', 'Paid', 'Partial', 'Cancelled'],
            'is_approval_view' => TRUE,
            'can_head_dept'    => $can_head_dept,
            'can_endorsement'  => $can_endorsement,
            'can_approval_stage' => $can_approval,
            'shown_stages'     => $shown_stages,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'prf_header',
                'number' => 'prf_number',
                'date'   => 'prf_date',
            ]),
            'my_history_route'    => 'prf/detail',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/list_all', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Daftar PRF untuk proses pembayaran
     */
    public function payment() {
        if (!check_access('prf_payment', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
            'search'    => $this->input->get('q'),
        ];
        $tab = $this->input->get('tab') ?: 'pending';

        $pending = $this->model->get_prf_payment_queue();
        foreach ($pending as $item) {
            $item->department_name = $this->model->resolve_department_name($item->department_id);
            if (empty($item->department_name) || $item->department_name === $item->department_id) {
                $item->department_name = $this->model->get_user_department_name($item->requester_id);
            }
            $item->attachment_count = count($this->model->get_attachments($item->id));
        }

        $paid = $this->model->get_prf_payment_history($filters);
        foreach ($paid as $item) {
            $item->department_name = $this->model->resolve_department_name($item->department_id);
            if (empty($item->department_name) || $item->department_name === $item->department_id) {
                $item->department_name = $this->model->get_user_department_name($item->requester_id);
            }
            $item->attachment_count = count($this->model->get_attachments($item->id));
        }

        $sum_pending = 0;
        foreach ($pending as $p) { $sum_pending += (float)$p->subtotal; }
        $sum_paid = 0;
        foreach ($paid as $p) { $sum_paid += (float)$p->subtotal; }

        $data = [
            'title'        => lang_line('title_prf_payment'),
            'pending_list' => $pending,
            'paid_list'    => $paid,
            'sum_pending'  => $sum_pending,
            'sum_paid'     => $sum_paid,
            'filters'      => $filters,
            'active_tab'   => $tab,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/payment', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Buat PRF baru
     */
    public function create() {
        if (!check_access('prf', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        // Departemen dokumen dipilih dari departemen role user (tahap 3, poin 8).
        $dept_options = dept_options_for('prf', 'add');
        $department_code = default_dept_for($dept_options);
        $department_name = $dept_options[$department_code] ?? null;

        $data = [
            'title' => lang_line('title_prf_create'),
            'prf_number' => $this->model->generate_prf_number($department_code),
            'department_id' => $department_code,
            'department_name' => !empty($department_name) ? $department_name : 'Unknown',
            'dept_options' => $dept_options,
            'suppliers' => $this->Supplier_model->get_all_suppliers(),
            'satuan_list' => $this->Commoncode_model->get_by_group('SATUAN'),
            'details' => [],
        ] + $this->_items_form_data();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/create', $data);
        $this->load->view('templates/footer');
    }

    /**
     * API endpoint: Get PO details untuk di-pull ke PRF (AJAX JSON)
     * POST /prf/get_po_items_json { po_id }
     */
    public function get_po_items_json() {
        if (!$this->input->is_ajax_request()) {
            return $this->output->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid request']));
        }

        // Dipakai oleh form Create maupun Edit PRF, jadi izinnya mengikuti keduanya.
        $can_pull = check_access('prf', 'add') || check_access('prf', 'edit')
            || check_access('prf_payment', 'view') || is_admin();
        if (!$can_pull) {
            return $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Access denied']));
        }

        $po_id = (int) $this->input->post('po_id');
        if (empty($po_id)) {
            return $this->output->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'PO ID required']));
        }

        $po_header = $this->model->get_po_header_for_prf($po_id);
        if (!$po_header) {
            return $this->output->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'PO not found or not approved']));
        }

        // Hanya qty yang SUDAH DATANG dan belum ditagih (PO Jasa: qty PO).
        $exclude_prf = (int) $this->input->post('prf_id') ?: null;
        $lines = $this->Ap_auto_model->get_po_billable_lines($po_id, $exclude_prf);
        $po_details = [];
        $ppn = null;
        foreach ($lines as $r) {
            if ($ppn === null) $ppn = ['applied' => $r->ppn_applied, 'rate' => $r->ppn_rate, 'inclusive' => $r->ppn_inclusive,
                                       'label' => $r->ppn_applied ? po_ppn_label($r->ppn_rate, $r->ppn_inclusive) : ''];
            $po_details[] = [
                'po_detail_id' => (int) $r->po_detail_id,
                'description' => $r->description,
                'unit' => $r->unit,
                'po_qty' => $r->po_qty,
                'received_qty' => $r->received_qty,
                'billed_qty' => $r->billed_qty,
                'qty' => $r->remaining_qty,
                'remaining_qty' => $r->remaining_qty,
                'is_jasa' => $r->is_jasa,
                'unit_price' => $r->dpp_unit_price,
                'local_price' => $r->local_price,
                'subtotal' => round($r->remaining_qty * $r->dpp_unit_price, 2),
            ];
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'po' => [
                    'id' => $po_header->id,
                    'po_number' => $po_header->po_number,
                    'type' => $po_header->type,
                    'supplier_id' => $po_header->supplier_id,
                    'supplier_name' => $po_header->nama_supplier,
                    'bank_name' => $po_header->bank_name,
                    'bank_account' => $po_header->bank_account,
                    'bank_account_name' => $po_header->bank_account_name ?? '',
                ],
                'ppn' => $ppn ?: ['applied' => false, 'rate' => 0, 'inclusive' => false, 'label' => ''],
                'details' => $po_details,
            ]));
    }

    /**
     * Store PRF baru (updated untuk support po_id)
     */

    public function store() {
        if (!check_access('prf', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $this->form_validation->set_rules('prf_date', 'Tanggal Payment Request', 'required');
        $this->form_validation->set_rules('department_id', 'Departemen', 'required');

        $department_code = normalize_department_code($this->input->post('department_id'));
        if ($department_code === null || !isset(dept_options_for('prf', 'add')[$department_code])) {
            $this->session->set_flashdata('error', 'Departemen Payment Request tidak valid atau di luar departemen role Anda.');
            redirect('prf/create');
        }
        // Supplier / Vendor opsional — ada kasus PRF tanpa supplier (mis. reimbursement,
        // pembayaran internal). Model._supplier_snapshot() sudah handle supplier_id kosong.

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('prf/create');
        }

        $raw = $this->_raw_details_input();
        if (empty($raw)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item.');
            redirect('prf/create');
        }

        // Rapikan & validasi baris: MANUAL (perilaku lama) atau HUTANG (tarik
        // PO / AP Invoice: qty dibatasi barang yang sudah datang, harga PO,
        // baris PPN dibuat ulang, satu supplier). Lihat Ap_auto_model.
        $norm = $this->Ap_auto_model->normalize_prf_lines($raw);
        if (empty($norm['ok'])) {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($norm['message'])));
            redirect('prf/create');
        }
        $cleaned_details = $norm['details'];
        $post = array_merge($this->input->post(), [
            'department_code' => $department_code,
            'source_type' => $norm['source_type'],
            'ppn_amount' => $norm['ppn_amount'],
        ]);
        if ($norm['source_type'] === 'HUTANG') $post['supplier_id'] = $norm['supplier_id'];

        try {
            $header_id = $this->model->create_prf($post, $cleaned_details);
            if ($header_id) {
                $this->session->set_flashdata('success', 'Payment Request berhasil dibuat.');
                redirect('prf/detail/' . $header_id);
            } else {
                throw new Exception('Gagal menyimpan Payment Request.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            redirect('prf/create');
        }
    }

    /**
     * Detail PRF
     */
    public function detail($id) {
        $can_hod         = check_access('prf_approval', 'head_dept');
        $can_endorsement = check_access('prf_approval', 'endorsement');
        $can_approval    = check_access('prf_approval', 'approval');
        // Approver boleh lihat detail meski tidak punya prf.view, karena halaman detail
        // adalah pintu masuk untuk aksi approve/reject dari Approval Queue.
        $can_view_detail = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || $can_hod || $can_endorsement || $can_approval;
        if (!$can_view_detail) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf_header = $this->model->get_prf_header_by_id($id);
        if (!$prf_header) {
            show_404();
        }
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('prf', 'prf_approval', $this->_doc_dept($prf_header), (int) $prf_header->requester_id === (int) $this->session->userdata('user_id'), check_access('prf_payment', 'view'))) {
            show_error('Payment Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $doc_dept        = $this->_doc_dept($prf_header);
        $department_name = $this->model->resolve_department_name($doc_dept ?: $prf_header->department_id);

        $status_now      = $prf_header->status;
        // Tahap approval hanya untuk departemen dalam cakupan user (tahap 3).
        $stage_hod         = $can_hod         && can_dept_access('prf_approval', 'head_dept', $doc_dept);
        $stage_endorsement = $can_endorsement && can_dept_access('prf_approval', 'endorsement', $doc_dept);
        $stage_approval    = $can_approval    && can_dept_access('prf_approval', 'approval', $doc_dept);

        // Mode edit Admin: buka kunci PRF yang sudah lewat Draft supaya bisa
        // dibetulkan (item, nominal, pull dari PO, lampiran) TANPA mereset
        // approval. Harus diminta eksplisit lewat tombol (flag admin_edit=1).
        $admin_edit = is_prf_admin_edit($prf_header);

        // Tombol Kembali kontekstual: balik ke halaman asal (list_all ATAU
        // payment queue), bukan selalu ke list_all. Lihat MY_Controller::resolve_back_url().
        $back_url = $this->resolve_back_url('prf/list_all', ['prf/']);

        // "PRF Selanjutnya" (ERP Revision Notes, [[erp_revision_prf_erf]]):
        // kalau detail ini dibuka dari antrian approval/payment (?queue=...),
        // lompat ke PRF berikutnya dalam antrian yang sama. Kalau dibuka
        // tanpa konteks queue (langsung lewat URL/bookmark, dari PRF List
        // biasa, dll — user 2026-09-03 minta tombol ini SELALU ada di
        // halaman detail manapun) -> fallback ke get_next_prf_id() (next by
        // id, di-scope sama kaya list_all(): "Lihat Semua" vs punya sendiri).
        // Arah mundurnya ("PRF Sebelumnya") ditambahkan 2026-09-16 atas
        // permintaan user -- aturan & scope-nya persis sama, cuma terbalik.
        $queue = $this->input->get('queue');
        if (in_array($queue, ['approval', 'payment'], true)) {
            $next_prf_id = $this->model->get_next_in_queue($queue, $id, $can_hod, $can_endorsement, $can_approval);
            $prev_prf_id = $this->model->get_prev_in_queue($queue, $id, $can_hod, $can_endorsement, $can_approval);
        } else {
            $queue = null;
            $can_view_all_prf = check_access('prf_approval', 'view') || check_access('prf_payment', 'view')
                || $can_hod || $can_endorsement || $can_approval;
            $user_id_now = $this->session->userdata('user_id');
            $next_prf_id = $this->model->get_next_prf_id($id, $user_id_now, $can_view_all_prf);
            $prev_prf_id = $this->model->get_prev_prf_id($id, $user_id_now, $can_view_all_prf);
        }

        $data = [
            'title' => sprintf(lang_line('title_prf_detail'), $prf_header->prf_number),
            'back_url' => $back_url,
            'queue' => $queue,
            'next_prf_id' => $next_prf_id,
            'prev_prf_id' => $prev_prf_id,
            'prf' => $prf_header,
            'details' => $this->model->get_prf_details_by_header_id($id),
            'approval_logs' => $this->model->get_approval_logs($id),
            'payment_logs' => $this->model->get_payment_tracking($id),
            'payment_signer' => $this->model->get_payment_signer($id),
            'attachments' => $this->model->get_attachments($id),
            'audit_users' => $this->model->get_audit_users($prf_header),
            'department_name' => !empty($department_name) ? $department_name : 'Unknown',
            'coa_accounts' => $this->journal_model->get_postable_accounts(),
            // Payment Request hutang: AP Invoice, jurnal hutang & jurnal bayar terkait.
            'is_hutang' => ($prf_header->source_type ?? 'MANUAL') === 'HUTANG',
            'acct_links' => ($prf_header->source_type ?? 'MANUAL') === 'HUTANG' ? $this->Ap_auto_model->prf_accounting_links($prf_header) : null,
            'cash_accounts' => ($prf_header->source_type ?? 'MANUAL') === 'HUTANG' ? $this->Ap_auto_model->get_cash_accounts() : [],
            // PRF hutang di luar Draft dikunci (sudah terhubung AP Invoice & jurnal).
            'can_edit_nominal' => (($status_now === 'Draft' && can_dept_access('prf', 'edit', $doc_dept))
                || ($status_now === 'Paid' && (check_access('prf', 'edit') || check_access('prf_payment', 'view') || is_admin()))
                || $admin_edit)
                && !(($prf_header->source_type ?? 'MANUAL') === 'HUTANG' && $status_now !== 'Draft'),
            'admin_edit_mode'      => $admin_edit,
            'can_unlock_admin'     => can_unlock_prf_admin_edit($prf_header),
            // Kenapa tombol buka kunci tidak ada padahal user Admin (sudah dibayar /
            // sudah dijurnal). Ditampilkan sebagai catatan kecil, bukan error.
            'admin_edit_blocker'   => (is_admin() && in_array($status_now, prf_admin_editable_statuses(), true))
                                      ? prf_admin_edit_blocker($prf_header) : null,
            'can_edit_payment' => in_array($prf_header->payment_status ?? 'Unpaid', ['Paid', 'Partial'], true)
                && (check_access('prf_payment', 'add') || check_access('prf_payment', 'view') || is_admin()),
            'can_submit'  => $status_now === 'Draft' && can_dept_access('prf', 'submit', $doc_dept),
            // Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin
            // 11b): requestor & approver mana pun bisa reply selagi Draft.
            // Thread balasan cuma terbuka setelah PRF dikembalikan lewat Back to
            // Draft, pesertanya requestor + approver yang mengembalikan saja
            // (koreksi 2026-09-06 — lihat can_reply_return_thread() di auth_helper).
            // $status_now dipakai krn PRF punya status legacy yg dinormalisasi.
            'can_reply'   => can_reply_return_thread($prf_header, $status_now),
            // Tombol approval. 1 tahap sejak 2026-11-06 (HOD); endorsement/final
            // hanya muncul utk Payment Request lama yg masih di tengah alur 3 tahap.
            'can_approve_hod'         => $status_now === 'Submitted' && $stage_hod,
            'can_approve_endorsement' => in_array($status_now, ['Approved Head Dept', 'Head Approved']) && $stage_endorsement,
            'can_approve_final'       => $status_now === 'Endorsed' && $stage_approval,
            'can_return_reject' => ($status_now === 'Submitted' && $stage_hod)
                                   || (in_array($status_now, ['Approved Head Dept', 'Head Approved']) && $stage_endorsement)
                                   || ($status_now === 'Endorsed' && $stage_approval),
            // Payment baru muncul setelah Approved final (atau legacy Finance Approved).
            'can_record_payment' => in_array($status_now, ['Approved', 'Finance Approved'])
                                    && $prf_header->payment_status !== 'Paid'
                                    && check_access('prf_payment', 'add'),
            // Lampiran PRF (invoice/quotation/dll) hanya bisa diupload pada status Draft —
            // setelah submit, dokumen pendukung dianggap final. Untuk bukti BAYAR
            // pakai endpoint upload_payment_proof terpisah (gate: prf_payment.add).
            'can_upload_attachment' => ($status_now === 'Draft' && check_access('prf', 'view')) || $admin_edit,
            'can_upload_payment_proof' => check_access('prf_payment', 'add'),
            'can_delete_attachment' => ($status_now === 'Draft' && (int)$prf_header->requester_id === (int)$this->session->userdata('user_id'))
                                       || $admin_edit,
            // Admin override: tombol Force Return-to-Draft & Force Delete tampil
            // utk koreksi data lama. Disembunyikan kalau status sudah Draft (tidak relevan)
            // atau ada pembayaran (Paid/Partial) — supaya jurnal & saldo tidak bocor.
            'is_admin'              => is_admin(),
            'can_admin_return'      => is_admin()
                                       && $status_now !== 'Draft'
                                       && $status_now !== 'Paid'
                                       && !in_array($prf_header->payment_status ?? 'Unpaid', ['Paid','Partial'], true),
            'can_admin_delete'      => is_admin()
                                       && $status_now !== 'Paid'
                                       && !in_array($prf_header->payment_status ?? 'Unpaid', ['Paid','Partial'], true),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Edit PRF
     */
    public function edit($id) {
        $can_edit_nominal = check_access('prf', 'edit') || check_access('prf_payment', 'view') || is_admin();
        if (!$can_edit_nominal) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf_header = $this->model->get_prf_header_by_id($id);
        if (!$prf_header) show_404();

        // Draft milik departemen lain tidak boleh diedit (tahap 3). Finance/Admin tetap bisa (koreksi nominal).
        if ($prf_header->status === 'Draft' && !is_admin() && !check_access('prf_payment', 'view')
            && !can_dept_access('prf', 'edit', $this->_doc_dept($prf_header))) {
            show_error('Payment Request ini milik departemen lain — Anda tidak berhak mengubahnya.', 403);
        }

        // Di luar Draft/Paid, form ini cuma terbuka lewat mode edit Admin.
        $admin_edit = is_prf_admin_edit($prf_header);
        $allowed_statuses = ['Draft', 'Paid'];
        if (!in_array($prf_header->status, $allowed_statuses, true) && !$admin_edit) {
            show_404();
        }
        if ($prf_header->status === 'Paid' && !in_array($prf_header->payment_status ?? 'Unpaid', ['Paid', 'Partial'], true)) {
            show_404();
        }

        if (($prf_header->source_type ?? 'MANUAL') === 'HUTANG' && $prf_header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Payment Request hutang (dari PO / AP Invoice) yang sudah di-approve/dibayar tidak bisa diedit karena sudah terhubung ke daftar hutang & jurnal. Kembalikan ke Draft dulu (sebelum dibayar) untuk koreksi.');
            redirect('prf/detail/' . $id);
        }

        $department_name = $this->model->resolve_department_name($prf_header->department_id);
        $details = $this->model->get_prf_details_by_header_id($id);

        $data = [
            'title' => sprintf(lang_line('title_prf_edit'), $prf_header->prf_number),
            'admin_edit_mode' => $admin_edit,
            'prf' => $prf_header,
            'details' => $details,
            'department_name' => !empty($department_name) ? $department_name : 'Unknown',
            'suppliers' => $this->Supplier_model->get_all_suppliers(),
            'satuan_list' => $this->Commoncode_model->get_by_group('SATUAN'),
        ] + $this->_items_form_data((int) $id, $details);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('prf/edit', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Update PRF
     */
    public function update($id) {
        $can_edit_nominal = check_access('prf', 'edit') || check_access('prf_payment', 'view') || is_admin();
        if (!$can_edit_nominal) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf_header = $this->model->get_prf_header_by_id($id);
        if (!$prf_header) show_404();

        // Draft milik departemen lain tidak boleh diedit (tahap 3). Finance/Admin tetap bisa (koreksi nominal).
        if ($prf_header->status === 'Draft' && !is_admin() && !check_access('prf_payment', 'view')
            && !can_dept_access('prf', 'edit', $this->_doc_dept($prf_header))) {
            show_error('Payment Request ini milik departemen lain — Anda tidak berhak mengubahnya.', 403);
        }

        $admin_edit = is_prf_admin_edit($prf_header);
        $allowed_statuses = ['Draft', 'Paid'];
        if (!in_array($prf_header->status, $allowed_statuses, true) && !$admin_edit) {
            show_404();
        }
        if ($prf_header->status === 'Paid' && !in_array($prf_header->payment_status ?? 'Unpaid', ['Paid', 'Partial'], true)) {
            show_404();
        }

        // Flag ikut dibawa ulang ke form kalau ada yang gagal validasi, supaya
        // mode edit tidak lepas di tengah jalan.
        $edit_url = 'prf/edit/' . $id . ($admin_edit ? '?admin_edit=1' : '');

        $this->form_validation->set_rules('prf_date', 'Tanggal Payment Request', 'required');
        // Supplier / Vendor opsional (lihat catatan di store()).

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($edit_url);
        }

        if (($prf_header->source_type ?? 'MANUAL') === 'HUTANG' && $prf_header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Payment Request hutang yang sudah di-approve/dibayar tidak bisa diedit (sudah terhubung ke daftar hutang & jurnal).');
            redirect('prf/detail/' . $id);
        }

        $raw = $this->_raw_details_input();
        if (empty($raw)) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item.');
            redirect($edit_url);
        }

        $norm = $this->Ap_auto_model->normalize_prf_lines($raw, (int) $id);
        if (empty($norm['ok'])) {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($norm['message'])));
            redirect($edit_url);
        }
        // Menjadikan Payment Request hutang (tarik PO / AP) hanya boleh selagi
        // Draft -- di luar itu jurnalnya tidak akan pernah terbentuk.
        if ($norm['source_type'] === 'HUTANG' && $prf_header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Tarik dari PO / AP Invoice hanya bisa untuk Payment Request berstatus Draft.');
            redirect($edit_url);
        }
        $cleaned_details = $norm['details'];
        $post = array_merge($this->input->post(), ['source_type' => $norm['source_type'], 'ppn_amount' => $norm['ppn_amount']]);
        if ($norm['source_type'] === 'HUTANG') $post['supplier_id'] = $norm['supplier_id'];

        try {
            $result = $this->model->update_prf($id, $post, $cleaned_details, $admin_edit);
            if ($result) {
                if ($admin_edit) {
                    add_log('ADMIN_EDIT', 'PRF', 'Edit Payment Request ' . $prf_header->prf_number . ' pada status ' . $prf_header->status
                        . ' (' . count($cleaned_details) . ' baris item, override Admin — approval tidak direset).');
                }
                $this->session->set_flashdata('success', 'Payment Request berhasil diupdate.');
                redirect('prf/detail/' . $id . ($admin_edit ? '?admin_edit=1' : ''));
            } else {
                throw new Exception('Gagal mengupdate Payment Request.');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Error: ' . $e->getMessage());
            redirect($edit_url);
        }
    }

    /**
     * Submit PRF
     */
    public function submit($id) {
        if (!check_access('prf', 'submit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf || $prf->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Payment Request tidak dapat disubmit.');
            redirect('prf/detail/' . $id);
            return;
        }
        if (!can_dept_access('prf', 'submit', $this->_doc_dept($prf))) {
            show_error('Anda tidak berhak submit Payment Request departemen ini.', 403);
        }

        // Attachment bukti/dokumen pendukung WAJIB ada sebelum submit ke approval.
        if (empty($this->model->get_attachments($id))) {
            $this->session->set_flashdata('error', 'Attachment bukti/dokumen pendukung wajib diupload sebelum submit.');
            redirect('prf/detail/' . $id);
            return;
        }

        if ($this->model->submit_prf($id)) {
            $this->session->set_flashdata('success', 'Payment Request berhasil disubmit untuk approval.');
            // Notifikasi "New PRF" (ERP Revision Notes, [[erp_revision_cross_cutting]]),
            // pola identik IRF -> replikasi ke PRF/ERF/PO.
            // Hanya HOD departemen PRF ini (atau pemegang ALL DEPT) — tahap 3.
            $recipients = users_in_dept_scope($this->users_with_permission('prf_approval', 'can_head_dept'), 'prf_approval', 'can_head_dept', $this->_doc_dept($prf));
            $this->notify_users(
                $recipients,
                'Payment Request Baru Menunggu Approval',
                'Payment Request ' . $prf->prf_number . ' dari ' . ($prf->requester_name ?: '-') . ' menunggu approval Anda (Head of Dept).',
                'prf/detail/' . $id,
                'info', 'fa-file-invoice-dollar',
                'prf', $id
            );
            redirect('prf/detail/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal submit Payment Request.');
            redirect('prf/detail/' . $id);
        }
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi terbuka setelah return/reject). Ditutup lagi begitu
     * requestor submit ulang (status berubah dari Draft).
     */
    public function reply($id) {
        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) show_404();

        $user_id = $this->session->userdata('user_id');

        // Cuma requestor PRF & approver yang mengembalikannya, dan cuma selama
        // PRF ini masih Draft hasil Back to Draft (bukan draft baru).
        if (!can_reply_return_thread($prf)) {
            $this->session->set_flashdata('error', 'Balasan cuma bisa dikirim oleh pembuat Payment Request & approver yang mengembalikannya, selagi Payment Request masih Draft hasil Back to Draft.');
            redirect('prf/detail/' . $id);
            return;
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            $this->session->set_flashdata('error', 'Pesan balasan tidak boleh kosong.');
            redirect('prf/detail/' . $id);
            return;
        }

        $this->model->add_reply($id, $user_id, $message);
        $this->session->set_flashdata('success', 'Balasan terkirim.');
        redirect('prf/detail/' . $id . '#riwayat-approval');
    }

    /**
     * Approval 1 tahap (2026-11-06): Head of Department -> langsung Approved,
     * masuk antrian Pembayaran.
     */
    public function approve_hod($id) {
        if (!check_access('prf_approval', 'head_dept')) {
            show_error('Anda tidak memiliki izin Head of Department.', 403);
        }
        $this->_require_approval_scope($id, 'head_dept');
        $notes = $this->input->post('notes') ?? '';
        $res = $this->model->approve_hod((int)$id, $notes);
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', 'Payment Request disetujui (approval 1 tahap). Silakan lanjutkan ke proses Pembayaran.' . $this->_ap_created_note($res));
            $this->_notify_prf_approved($id);
        } else {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($res['message'] ?? 'Gagal approve HOD.')));
        }
        redirect('prf/detail/' . $id);
    }

    /**
     * Stage 2: Endorsement.
     */
    public function approve_endorsement($id) {
        if (!check_access('prf_approval', 'endorsement')) {
            show_error('Anda tidak memiliki izin endorsement.', 403);
        }
        $this->_require_approval_scope($id, 'endorsement');
        $notes = $this->input->post('notes') ?? '';
        $res = $this->model->approve_endorsement((int)$id, $notes);
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', 'Payment Request berhasil di-endorse.');
            $this->_notify_prf_next_stage($id, 'can_approval', 'Final Approval');
        } else {
            $this->session->set_flashdata('error', $res['message'] ?? 'Gagal endorse.');
        }
        redirect('prf/detail/' . $id);
    }

    /**
     * Stage 3: Final Approval. Setelah ini PRF muncul di queue Payment.
     */
    public function approve_final($id) {
        if (!check_access('prf_approval', 'approval')) {
            show_error('Anda tidak memiliki izin approval final.', 403);
        }
        $this->_require_approval_scope($id, 'approval');
        $notes = $this->input->post('notes') ?? '';
        $res = $this->model->approve_final((int)$id, $notes);
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', 'Payment Request berhasil di-approve final. Silakan lanjutkan ke proses Pembayaran.' . $this->_ap_created_note($res));
            $this->_notify_prf_approved($id);
        } else {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($res['message'] ?? 'Gagal approve final.')));
        }
        redirect('prf/detail/' . $id);
    }

    private function _ap_created_note($res) {
        if (empty($res['ap'])) return '';
        return ' Hutang otomatis tercatat: AP Invoice ' . $res['ap']['ap_no'] . ' + jurnal ' . $res['ap']['journal_no'] . '.';
    }

    /** Notifikasi "Approved" -> pemohon (ERP Revision Notes, [[erp_revision_cross_cutting]]). */
    private function _notify_prf_approved($id) {
        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) return;
        $this->notify_users(
            [(int) $prf->requester_id],
            'Payment Request Disetujui',
            'Payment Request ' . $prf->prf_number . ' Anda telah disetujui, siap diproses pembayaran.',
            'prf/detail/' . $id,
            'success', 'fa-check-circle',
            'prf', $id
        );
    }

    /**
     * Helper kecil (pola sama Consumable_request::_notify_next_stage()) —
     * notifikasi "Waiting Approval" ke user yang berhak approve stage berikutnya.
     */
    private function _notify_prf_next_stage($id, $permission_column, $stage_label) {
        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) return;
        $recipients = users_in_dept_scope($this->users_with_permission('prf_approval', $permission_column), 'prf_approval', $permission_column, $this->_doc_dept($prf));
        $this->notify_users(
            $recipients,
            'Payment Request Menunggu Approval',
            'Payment Request ' . $prf->prf_number . ' menunggu approval Anda (' . $stage_label . ').',
            'prf/detail/' . $id,
            'info', 'fa-file-invoice-dollar',
            'prf', $id
        );
    }

    /**
     * Reject: status → 'Rejected', tidak dapat di-revisi creator.
     * Bisa dilakukan oleh siapapun yang berwenang di stage approval (HOD/Endorser/Approver).
     */
    public function reject($id) {
        $can = check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can) show_error('Anda tidak memiliki izin reject.', 403);
        $this->_require_approval_scope($id);

        $reason = trim($this->input->post('reject_reason') ?? '');
        if ($reason === '') {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            redirect('prf/detail/' . $id);
            return;
        }
        $res = $this->model->reject((int)$id, $reason);
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', 'Payment Request berhasil di-reject.');
        } else {
            $this->session->set_flashdata('error', $res['message'] ?? 'Gagal reject.');
        }
        redirect('prf/detail/' . $id);
    }

    /**
     * Return to Draft: status balik ke Draft, creator bisa revisi & submit ulang.
     */
    public function return_to_draft($id) {
        // Admin role boleh return-to-draft di status APA SAJA (override penuh) —
        // dipakai utk koreksi PRF yang sudah Approved/Paid/Rejected.
        $admin = is_admin();
        $can = $admin
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can) show_error('Anda tidak memiliki izin return ke Draft.', 403);
        if (!$admin) $this->_require_approval_scope($id);

        $reason = trim($this->input->post('return_reason') ?? '');
        if ($reason === '') {
            $this->session->set_flashdata('error', 'Alasan return ke Draft wajib diisi.');
            redirect('prf/detail/' . $id);
            return;
        }
        $res = $this->model->return_to_draft((int)$id, $reason, $admin);
        if (!empty($res['success'])) {
            $msg = $admin
                ? 'Payment Request dikembalikan ke Draft (Admin override). Semua approval & payment direset.'
                : 'Payment Request berhasil dikembalikan ke Draft. Creator dapat merevisi & submit ulang.';
            $this->session->set_flashdata('success', $msg);
            // Notifikasi "Back to Draft" -> requester.
            $prf = $this->model->get_prf_header_by_id($id);
            if ($prf) {
                $this->notify_users(
                    [(int) $prf->requester_id],
                    'Payment Request Dikembalikan ke Draft',
                    'Payment Request ' . $prf->prf_number . ' dikembalikan utk direvisi: "' . $reason . '"',
                    'prf/detail/' . $id,
                    'warning', 'fa-undo',
                    'prf', $id
                );
            }
        } else {
            $this->session->set_flashdata('error', $res['message'] ?? 'Gagal return ke Draft.');
        }
        redirect('prf/detail/' . $id);
    }

    /**
     * Record payment
     */
    public function record_payment($id) {
        if (!check_access('prf_payment', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        // Hanya Payment Request yang sudah Approved & belum dibayar.
        $prf_now = $this->model->get_prf_header_by_id($id);
        if (!$prf_now || !in_array($prf_now->status, ['Approved', 'Finance Approved'], true) || $prf_now->payment_status === 'Paid') {
            $this->session->set_flashdata('error', 'Payment Request belum di-approve atau sudah dibayar.');
            redirect('prf/detail/' . $id);
            return;
        }

        $this->form_validation->set_rules('payment_date', 'Tanggal Pembayaran', 'required');
        $this->form_validation->set_rules('payment_amount', 'Jumlah Pembayaran', 'required|numeric');
        $this->form_validation->set_rules('transfer_fee', 'Biaya Transfer', 'numeric');
        $this->form_validation->set_rules('pph_amount', 'PPh 23', 'numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('prf/detail/' . $id);
            return;
        }

        // Payment Request hutang: akun Kas/Bank, PPh & nominal dicek dulu SEBELUM
        // bukti bayar diupload (supaya tidak ada file yatim kalau ditolak).
        $is_hutang = ($prf_now->source_type ?? 'MANUAL') === 'HUTANG';
        if ($is_hutang) {
            $check = $this->Ap_auto_model->validate_prf_payment($prf_now, [
                'payment_amount' => (float) $this->input->post('payment_amount'),
                'transfer_fee' => (float) ($this->input->post('transfer_fee') ?: 0),
                'pph_amount' => (float) ($this->input->post('pph_amount') ?: 0),
                'cash_account_id' => (int) $this->input->post('cash_account_id'),
                'payment_date' => $this->input->post('payment_date'),
            ]);
            if (empty($check['ok'])) {
                $this->session->set_flashdata('error', $check['message']);
                redirect('prf/detail/' . $id);
                return;
            }
        }

        // Upload bukti bayar (bisa 1 atau 2, semuanya opsional). Pakai folder
        // terpisah supaya tidak campur dengan attachment dokumen permintaan.
        $proof_path   = null;
        $proof_path_2 = null;

        if (!empty($_FILES['payment_proof']['name'])) {
            $res = $this->_upload_payment_proof_file('payment_proof');
            if ($res['error']) {
                $this->session->set_flashdata('error', 'Bukti bayar 1 gagal diupload: ' . $res['error']);
                redirect('prf/detail/' . $id);
                return;
            }
            $proof_path = $res['path'];
        }

        if (!empty($_FILES['payment_proof_2']['name'])) {
            $res = $this->_upload_payment_proof_file('payment_proof_2');
            if ($res['error']) {
                $this->session->set_flashdata('error', 'Bukti bayar 2 gagal diupload: ' . $res['error']);
                redirect('prf/detail/' . $id);
                return;
            }
            $proof_path_2 = $res['path'];
        }

        $payment_data = [
            'payment_date' => $this->input->post('payment_date'),
            'payment_amount' => $this->input->post('payment_amount'),
            'transfer_fee' => $this->input->post('transfer_fee') ?: 0,
            'payment_method' => $this->input->post('payment_method'),
            'reference_number' => $this->input->post('reference_number'),
            'notes' => $this->input->post('notes'),
            'payment_proof_path' => $proof_path,
            'payment_proof_path_2' => $proof_path_2,
            'pph_amount' => $is_hutang ? (float) ($this->input->post('pph_amount') ?: 0) : 0,
            'cash_account_id' => $is_hutang ? (int) $this->input->post('cash_account_id') : null,
        ];

        if ($this->model->record_payment($id, $payment_data)) {
            $this->session->set_flashdata('success', $is_hutang
                ? 'Pembayaran berhasil dicatat. Jurnal pembayaran hutang dibuat & diposting otomatis.'
                : 'Pembayaran berhasil dicatat.');
            // Notifikasi "Payment Process" -> requester (ERP Revision Notes,
            // [[erp_revision_cross_cutting]]).
            $prf = $this->model->get_prf_header_by_id($id);
            if ($prf) {
                $this->notify_users(
                    [(int) $prf->requester_id],
                    'Payment Request Dibayar',
                    'Payment Request ' . $prf->prf_number . ' telah dibayar (Rp ' . number_format((float) $payment_data['payment_amount'], 0, ',', '.') . ').',
                    'prf/detail/' . $id,
                    'success', 'fa-money-check-alt',
                    'prf', $id
                );
            }
            redirect('prf/detail/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal mencatat pembayaran.' . ($this->model->last_error ? ' ' . $this->model->last_error : ''));
            redirect('prf/detail/' . $id);
        }
    }

    /**
     * Update payment record (date/amount/method/reference/notes) for an already-paid PRF.
     */
    public function update_payment($id) {
        $can_edit_payment = check_access('prf_payment', 'add') || check_access('prf_payment', 'view') || is_admin();
        if (!$can_edit_payment) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf_header = $this->model->get_prf_header_by_id($id);
        if (!$prf_header || !in_array($prf_header->status, ['Paid'], true)) {
            show_404();
        }

        $payment_id = (int) $this->input->post('payment_id');
        $existing_payment = $this->model->get_payment_by_id($payment_id);
        if (!$existing_payment || (int) $existing_payment->prf_header_id !== (int) $id) {
            $this->session->set_flashdata('error', 'Data pembayaran tidak ditemukan.');
            redirect('prf/detail/' . $id);
            return;
        }

        $this->form_validation->set_rules('payment_date', 'Tanggal Pembayaran', 'required');
        $this->form_validation->set_rules('payment_amount', 'Jumlah Pembayaran', 'required|numeric');
        $this->form_validation->set_rules('transfer_fee', 'Biaya Transfer', 'numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('prf/detail/' . $id);
            return;
        }

        $proof_path = $existing_payment->payment_proof_path;
        $proof_path_2 = $existing_payment->payment_proof_path_2;

        if (!empty($_FILES['payment_proof']['name'])) {
            $res = $this->_upload_payment_proof_file('payment_proof');
            if ($res['error']) {
                $this->session->set_flashdata('error', 'Bukti bayar 1 gagal diupload: ' . $res['error']);
                redirect('prf/detail/' . $id);
                return;
            }
            $proof_path = $res['path'];
        }

        if (!empty($_FILES['payment_proof_2']['name'])) {
            $res = $this->_upload_payment_proof_file('payment_proof_2');
            if ($res['error']) {
                $this->session->set_flashdata('error', 'Bukti bayar 2 gagal diupload: ' . $res['error']);
                redirect('prf/detail/' . $id);
                return;
            }
            $proof_path_2 = $res['path'];
        }

        $payment_data = [
            'payment_date' => $this->input->post('payment_date'),
            'payment_amount' => $this->input->post('payment_amount'),
            'transfer_fee' => $this->input->post('transfer_fee') ?: 0,
            'payment_method' => $this->input->post('payment_method'),
            'reference_number' => $this->input->post('reference_number'),
            'notes' => $this->input->post('notes'),
            'payment_proof_path' => $proof_path,
            'payment_proof_path_2' => $proof_path_2,
        ];
        // Payment Request hutang: nominal & biaya sudah dijurnal otomatis -> dikunci.
        // Tanggal boleh dikoreksi (tanggal jurnal ikut berubah).
        if (($prf_header->source_type ?? 'MANUAL') === 'HUTANG') {
            $payment_data['payment_amount'] = $existing_payment->payment_amount;
            $payment_data['transfer_fee'] = $existing_payment->transfer_fee ?? 0;
        }

        if ($this->model->update_payment_record($id, $payment_id, $payment_data)) {
            $this->session->set_flashdata('success', 'Data pembayaran berhasil diupdate.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate data pembayaran.');
        }

        redirect('prf/detail/' . $id);
    }

    /**
     * Export to PDF
     */
    public function export_pdf($id) {
        // Approver juga butuh PDF untuk review sebelum approve.
        $can_view_pdf = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_pdf) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) {
            show_404();
        }
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('prf', 'prf_approval', $this->_doc_dept($prf), (int) $prf->requester_id === (int) $this->session->userdata('user_id'), check_access('prf_payment', 'view'))) {
            show_error('Payment Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $size   = strtoupper((string) $this->input->get('size'));
        $orient = strtoupper((string) $this->input->get('orient'));
        if (!in_array($size, ['A4', 'A5'], true))   $size   = 'A4';
        if (!in_array($orient, ['L', 'P'], true))   $orient = 'L';

        $details = $this->model->get_prf_details_by_header_id($id);

        // Get requester signature. render_signature_stamp_html()
        // (document_pdf_helper.php, autoload) -- stempel "ERP Approved"
        // generik kalau user tidak punya ttd personal, atau ttd personal
        // POLOS kalau ada ($with_frame=false: user 2026-09-09 minta frame
        // ttd2.png dihapus khusus PRF & ERF). Ukuran stempel
        // disesuaikan ke varian kertas (.sig-space/.sig-image PRF py 3
        // ukuran beda: A4=110px, A5=70px, A5+portrait=56px -- lihat CSS di
        // prf/pdf_template.php) supaya tidak overflow cell yg lebih kecil.
        $sig_size = ($size === 'A5' && $orient === 'P') ? 42 : (($size === 'A5') ? 55 : 80);

        $requester = $this->db->select('signature_path, fullname')->where('id', $prf->requester_id)->get('users')->row();
        $requester_name = $requester ? $requester->fullname : '-';
        $requester_signature = $requester ? render_signature_stamp_html($requester->signature_path, $sig_size, false) : '';

        // Tanda tangan (2026-11-06, approval 1 tahap): Pemohon, Disetujui, Dibayar.
        // Helper: ambil signature + nama + tanggal dari user_id; return [html, name, date|null].
        $sig_for = function($user_id, $approved_at) use ($sig_size) {
            if (empty($user_id)) return ['', '-', null];
            $u = $this->db->select('signature_path, fullname')->where('id', $user_id)->get('users')->row();
            $name = $u ? $u->fullname : '-';
            $html = $u ? render_signature_stamp_html($u->signature_path, $sig_size, false) : '';
            return [$html, $name, $approved_at];
        };

        // "Disetujui" = approver final (alur 1 tahap: Head of Dept; Payment Request
        // lama: approver tahap akhir). Kosong selama belum disetujui penuh.
        list($approver_signature, $approver_name, $approver_date) = $sig_for(
            $prf->final_approved_by ?? null,
            $prf->final_approved_at ?? null
        );
        // "Dibayar" terisi otomatis dari Record Payment terakhir (pencatat + tanggal bayar).
        $payer = $this->model->get_payment_signer($prf->id);
        list($payer_signature, $payer_name, $payer_date) = $payer
            ? $sig_for($payer->created_by, $payer->payment_date)
            : ['', '-', null];

        // Department: use department code for export
        $department_code = $this->model->resolve_department_code($prf->department_id);
        if (empty($department_code)) {
            $user_dept = $this->model->get_user_department_code($prf->requester_id);
            if (!empty($user_dept)) {
                $department_code = $user_dept;
            }
        }

        // Cap "COMPLETED" (assets/img/stamp.png) -- muncul cuma kalau PRF ini
        // sudah dibayar (record_payment() set payment_status+status jadi
        // 'Paid' sekaligus, lihat Prf_model::record_payment()). Lebar
        // disesuaikan ke varian kertas spt $sig_size di atas.
        //
        // 100/70/55 (bukan 170/120/95) -- ukuran awal 170 utk A4 terbukti
        // bikin PDF 2 halaman: baris nama/tanggal sig-table kepental ke
        // halaman 2 krn header-table jadi lebih tinggi (dites nyata pakai
        // PRF id 117 di DB, 2 item -- overflow SEBELUM stamp cuma pas2an,
        // stamp menambah ~84px tinggi header lalu jebol). Disempitkan ke 100
        // (dites: 130 masih 2 halaman, 100 sudah pasti 1 halaman) supaya PDF
        // tetap 1 halaman kalau jumlah item tidak banyak, sesuai permintaan
        // user. ERF TIDAK kena masalah ini (dites worst-case 1 item jg tetap
        // 1 halaman di lebar 170) makanya ERF tidak diubah.
        $stamp_width = ($size === 'A5' && $orient === 'P') ? 55 : (($size === 'A5') ? 70 : 100);
        $payment_stamp_html = (($prf->payment_status ?? null) === 'Paid')
            ? render_payment_stamp_html($stamp_width)
            : '';

        $html = $this->load->view('prf/pdf_template', [
            'prf' => $prf,
            'details' => $details,
            'department_code' => $department_code,
            'requester_signature' => $requester_signature,
            'requester_name' => $requester_name,
            'requester_date' => $prf->prf_date,
            'payment_stamp_html' => $payment_stamp_html,
            // Kolom Disetujui & Dibayar (approval 1 tahap, 2026-11-06).
            'approver_signature' => $approver_signature,
            'approver_name'      => $approver_name,
            'approver_date'      => $approver_date,
            'payer_signature'    => $payer_signature,
            'payer_name'         => $payer_name,
            'payer_date'         => $payer_date,
            'paper_size' => $size,
            'orientation' => $orient
        ], true);

        try {
            // Header/footer letterhead identitas perusahaan REPEATING di
            // tiap halaman (user 2026-09-06) via Mpdf native
            // SetHTMLHeader()/SetHTMLFooter() -- lihat catatan panjang di
            // Erf.php::export_pdf() (mekanisme sama) soal kenapa @page CSS
            // margin TIDAK dipakai (Mpdf reset margin_top/bottom-nya sendiri
            // begitu ada rule @page apa pun, bikin header/footer gagal
            // tampil). Ukuran icon/font diskalakan per varian kertas spt
            // $sig_size/$stamp_width di atas.
            $letterhead_icon = ($size === 'A5' && $orient === 'P') ? 30 : (($size === 'A5') ? 38 : 50);
            $letterhead_font = ($size === 'A5' && $orient === 'P') ? 15 : (($size === 'A5') ? 19 : 24);
            $letterhead_header = render_pdf_letterhead_header_html($letterhead_icon, $letterhead_font);
            $letterhead_footer = render_pdf_letterhead_footer_html();
            $px2mm = 25.4 / 96;
            $margin_top    = round(render_pdf_letterhead_header_margin($letterhead_icon, $letterhead_font) * $px2mm, 1);
            $margin_bottom = round(render_pdf_letterhead_footer_margin() * $px2mm, 1);
            $margin_lr     = round(25 * $px2mm, 1);

            // Setelan Developer Mode (2026-09-10). Margin atas/bawah cuma
            // DITAMBAH di atas ruang kop surat yang sudah dihitung di atas —
            // bukan diganti — supaya isi dokumen tidak pernah ketimpa header
            // letterhead yang berulang tiap halaman.
            if (function_exists('pdf_layout_margins')) {
                $_m = pdf_layout_margins('prf', $margin_top, $margin_bottom);
                $margin_top    = $_m['margin_top'];
                $margin_bottom = $_m['margin_bottom'];
                $margin_lr     = $_m['margin_left'];
            }

            $mpdf = new Mpdf([
                'format' => $size . '-' . $orient,
                'margin_header' => 0, 'margin_footer' => 0,
                'margin_top' => $margin_top, 'margin_bottom' => $margin_bottom,
                'margin_left' => $margin_lr, 'margin_right' => $margin_lr,
            ]);
            $mpdf->SetHTMLHeader($letterhead_header);
            $mpdf->SetHTMLFooter($letterhead_footer);
            $mpdf->WriteHTML($html);

            // Append attachments as additional pages.
            //
            // Halaman lampiran dibuka lewat mpdf_add_attachment_page() supaya
            // letterhead repeating di atas TIDAK ikut tercetak menimpa isi
            // scan (user 2026-09-08) -- lihat catatan lengkap di helper itu.
            $attachments = $this->model->get_attachments($id);
            foreach ($attachments as $idx => $att) {
                // PDF yang tidak bisa diparse FPDI punya versi rakitan khusus
                // export (render_path); file aslinya tetap yang dilihat user.
                $rel = (!empty($att->render_path) && file_exists(FCPATH . $att->render_path))
                     ? $att->render_path
                     : $att->file_path;
                $abs = FCPATH . $rel;
                if (!file_exists($abs)) continue;

                if ($att->file_type === 'image') {
                    $mpdf->SetMargins(0, 0, 0);
                    $mpdf->SetAutoPageBreak(false, 0);
                    mpdf_add_attachment_page($mpdf, $orient);
                    $pw = $mpdf->w;
                    $ph = $mpdf->h;

                    $info = @getimagesize($abs);
                    $iw = $info ? $info[0] : 0;
                    $ih = $info ? $info[1] : 0;

                    $margin = 5;
                    $availW = $pw - 2 * $margin;
                    $availH = $ph - 2 * $margin;
                    if ($iw > 0 && $ih > 0) {
                        $ratio = $iw / $ih;
                        if (($availW / $availH) > $ratio) {
                            $imgH = $availH; $imgW = $availH * $ratio;
                        } else {
                            $imgW = $availW; $imgH = $availW / $ratio;
                        }
                    } else {
                        $imgW = $availW; $imgH = $availH;
                    }
                    $posX = ($pw - $imgW) / 2;
                    $posY = ($ph - $imgH) / 2;

                    $mpdf->WriteHTML(
                        '<div style="position:fixed;top:' . round($posY, 2) . 'mm;left:' . round($posX, 2) . 'mm;' .
                        'width:' . round($imgW, 2) . 'mm;height:' . round($imgH, 2) . 'mm;">' .
                        '<img src="' . htmlspecialchars($abs) . '" style="width:100%;height:100%;" /></div>'
                    );

                } elseif ($att->file_type === 'pdf') {
                    try {
                        $pageCount = $mpdf->setSourceFile($abs);
                        for ($pn = 1; $pn <= $pageCount; $pn++) {
                            // Import before AddPage (mPDF requirement)
                            $tplId   = $mpdf->importPage($pn);
                            $tplSize = $mpdf->getTemplateSize($tplId);

                            $mpdf->SetMargins(0, 0, 0);
                            $mpdf->SetAutoPageBreak(false, 0);
                            mpdf_add_attachment_page($mpdf, $orient);
                            $pw = $mpdf->w;
                            $ph = $mpdf->h;

                            // Scale proportionally to fit, centred — never stretch.
                            $scale = min($pw / $tplSize['width'], $ph / $tplSize['height']);
                            $fw = $tplSize['width']  * $scale;
                            $fh = $tplSize['height'] * $scale;
                            $ox = ($pw - $fw) / 2;
                            $oy = ($ph - $fh) / 2;
                            $mpdf->useTemplate($tplId, $ox, $oy, $fw, $fh);
                        }
                    } catch (\Exception $e) {
                        $this->_render_attachment_fallback_page($mpdf, $att, $orient);
                    }
                }
            }

            $mpdf->Output('PRF_' . $prf->prf_number . '.pdf', 'I');
        } catch (\Exception $e) {
            show_error('Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Render a fallback placeholder page when a PDF attachment can't be embedded
     * (FPDI free can't parse PDF 1.5+ compressed object streams).
     * Shows filename, clickable URL, and a QR code linking to the streaming endpoint.
     */
    private function _render_attachment_fallback_page($mpdf, $att, $orient) {
        $att_url = base_url('prf/attachment/' . $att->id);
        $qr_src  = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=5&data=' . urlencode($att_url);

        $mpdf->SetMargins(15, 15, 15);
        $mpdf->SetAutoPageBreak(true, 15);
        mpdf_add_attachment_page($mpdf, $orient);

        $html = '
            <div style="font-family: Arial, sans-serif; text-align:center; padding-top:25mm;">
                <div style="font-size:11pt; color:#888; letter-spacing:2px; text-transform:uppercase;">Lampiran Payment Request</div>
                <h2 style="margin:6mm 0 2mm 0; color:#222; font-size:18pt;">' . htmlspecialchars($att->original_name) . '</h2>
                <p style="color:#666; font-size:10pt; margin:0 auto 10mm auto; max-width:140mm;">
                    File ini tidak dapat di-embed langsung ke dalam PDF export karena menggunakan format
                    kompresi PDF modern yang tidak didukung parser embed. File asli tetap tersimpan dan
                    dapat diakses melalui link / QR di bawah ini.
                </p>
                <table style="margin:0 auto; border:1px solid #ddd; padding:0;" cellpadding="8" cellspacing="0">
                    <tr>
                        <td style="text-align:center; vertical-align:middle;">
                            <img src="' . $qr_src . '" style="width:55mm; height:55mm;" />
                            <div style="font-size:8pt; color:#999; margin-top:2mm;">Scan untuk lihat lampiran</div>
                        </td>
                    </tr>
                </table>
                <p style="margin-top:8mm; font-size:9pt; color:#444;">Akses langsung (klik / copy URL):</p>
                <p style="font-size:10pt;"><a href="' . $att_url . '" style="color:#0066cc; word-break:break-all;">' . $att_url . '</a></p>
            </div>';

        try {
            $mpdf->WriteHTML($html);
        } catch (\Exception $e2) {
            // Last-resort plain fallback if even the placeholder render fails
            // (e.g. server has no outbound internet for the QR image).
            $mpdf->WriteHTML(
                '<p style="font-family:Arial; color:#333;"><strong>Lampiran:</strong> '
                . htmlspecialchars($att->original_name) . '<br>'
                . 'Buka via: <a href="' . $att_url . '">' . $att_url . '</a></p>'
            );
        }
    }

    /**
     * Export to Excel (same layout as PDF form)
     */
    public function export_excel($id) {
        $can_view_excel = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_excel) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) {
            show_404();
        }
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). Finance tetap semua.
        if (!can_view_dept_doc('prf', 'prf_approval', $this->_doc_dept($prf), (int) $prf->requester_id === (int) $this->session->userdata('user_id'), check_access('prf_payment', 'view'))) {
            show_error('Payment Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $details = $this->model->get_prf_details_by_header_id($id);

        $department_code = $this->model->resolve_department_code($prf->department_id);
        if (empty($department_code)) {
            $user_dept = $this->model->get_user_department_code($prf->requester_id);
            if (!empty($user_dept)) {
                $department_code = $user_dept;
            }
        }

        // Nomor ditampilkan utuh (mis. PAY/ACRO/26110001): sejak format nomor Acropoly
        // (config/doc_number.php, 2026-11-05) nomor tidak lagi memuat kode departemen.

        $date_yy = date('y', strtotime($prf->prf_date));
        $date_mm = date('m', strtotime($prf->prf_date));
        $date_dd = date('d', strtotime($prf->prf_date));

        // Get signatures (reuse PDF logic)
        $requester = $this->db->select('signature_path, fullname')->where('id', $prf->requester_id)->get('users')->row();
        $requester_name = $requester ? $requester->fullname : '-';
        $requester_signature = ($requester && !empty($requester->signature_path) && file_exists(FCPATH . $requester->signature_path))
            ? FCPATH . $requester->signature_path : null;
        $requester_date = $prf->prf_date;

        // Tanda tangan (2026-11-06): Pemohon, Disetujui (approver final), Dibayar (Record Payment terakhir).
        $sig_for_excel = function($user_id, $approved_at) {
            if (empty($user_id)) return ['-', null, null];
            $u = $this->db->select('signature_path, fullname')->where('id', $user_id)->get('users')->row();
            $name = $u ? $u->fullname : '-';
            $path = ($u && !empty($u->signature_path) && file_exists(FCPATH . $u->signature_path))
                ? FCPATH . $u->signature_path : null;
            return [$name, $path, $approved_at];
        };
        list($approver_name, $approver_signature, $approver_date) = $sig_for_excel($prf->final_approved_by ?? null, $prf->final_approved_at ?? null);
        $payer = $this->model->get_payment_signer($prf->id);
        list($payer_name, $payer_signature, $payer_date) = $payer
            ? $sig_for_excel($payer->created_by, $payer->payment_date)
            : ['-', null, null];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payment Request');

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

        // Header: Company info (A1:F4) and PRF number (G1:I2)
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

        // PRF number top right
        $sheet->mergeCells('G2:I2');
        $sheet->setCellValueExplicit('G2', 'Payment Request :  ' . $prf->prf_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->getStyle('G2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Title
        $sheet->mergeCells('A6:I6');
        $sheet->setCellValue('A6', 'PAYMENT REQUEST');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A7:I7');
        $sheet->setCellValue('A7', '[付款申請表]');
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
        $sheet->setCellValue('D10', $department_code);
        $sheet->getStyle('D10:I10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // NAME row (row 11)
        $sheet->setCellValue('A11', 'NAME [姓名]');
        $sheet->mergeCells('A11:B11');
        $sheet->getStyle('A11')->getFont()->setBold(true);
        $sheet->setCellValue('C11', ':');
        $sheet->mergeCells('D11:I11');
        $sheet->setCellValue('D11', $prf->requester_name);
        $sheet->getStyle('D11:I11')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // VENDOR / BANK / ACCOUNT rows (12-14) — snapshot dari supplier saat PRF dibuat.
        // ACCOUNT ditulis "no. rekening / atas nama" spy jelas rekening itu punya siapa.
        // Atas nama diambil dari Nama Akun di Master Supplier; sebelum 2026-10-02
        // ini ditebak dari nama vendor. Kalau belum diisi, jatuh kembali ke nama
        // vendor supaya PRF lama tetap tercetak seperti sebelumnya.
        $acc_no    = trim((string) ($prf->bank_account ?? ''));
        $acc_owner = trim((string) ($prf->bank_account_name ?? ''));
        if ($acc_owner === '') $acc_owner = trim((string) ($prf->supplier_name ?? ''));
        $acc_line  = ($acc_no !== '' && $acc_owner !== '') ? $acc_no . ' / ' . $acc_owner : $acc_no;

        $info_rows = [
            12 => ['VENDOR [供應商]', $prf->supplier_name ?? ''],
            13 => ['BANK [銀行]',     $prf->bank_name ?? ''],
            14 => ['ACCOUNT [賬號]',  $acc_line],
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
        $header_row = 16;
        $sheet->setCellValue('A' . $header_row, "NO\n[號]");
        $sheet->mergeCells('B' . $header_row . ':E' . $header_row);
        $sheet->setCellValue('B' . $header_row, "DESCRIPTION\n[描述]");
        $sheet->mergeCells('F' . $header_row . ':G' . $header_row);
        $sheet->setCellValue('F' . $header_row, "QTY\n[數量]");
        $sheet->setCellValue('H' . $header_row, "UNIT PRICE\n[單價]");
        $sheet->setCellValue('I' . $header_row, "SUBTOTAL\n[小計]");

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
        foreach ($details as $detail) {
            $r = $start_row + $idx;
            $sheet->setCellValue('A' . $r, $no);
            $sheet->mergeCells('B' . $r . ':E' . $r);
            $sheet->setCellValue('B' . $r, $detail->description);
            $sheet->mergeCells('F' . $r . ':G' . $r);
            $sheet->setCellValue('F' . $r, (float)$detail->qty);
            $sheet->setCellValue('H' . $r, (float)$detail->unit_price);
            $sheet->setCellValue('I' . $r, (float)$detail->subtotal);
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
        $sheet->getStyle('F' . $start_row . ':F' . $last_item_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H' . $start_row . ':I' . $last_item_row)->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
        $sheet->getStyle('H' . $start_row . ':I' . $last_item_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F' . $start_row . ':F' . $last_item_row)->getNumberFormat()->setFormatCode('#,##0.00');
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
        $sheet->setCellValue('I' . $total_row, (float)$prf->subtotal);
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
        // 3 kolom signature (2026-11-06): Pemohon, Disetujui, Dibayar.
        $sig_cols = [
            ['A', 'B', "Pemohon\n[申請人]",   $requester_name, $requester_signature, $fmtDate($requester_date)],
            ['C', 'F', "Disetujui\n[核准人]", $approver_name,  $approver_signature,  $fmtDate($approver_date)],
            ['G', 'I', "Dibayar\n[付款人]",   $payer_name,     $payer_signature,     $fmtDate($payer_date)],
        ];

        $sheet->getRowDimension($sig_box_row)->setRowHeight(55);

        foreach ($sig_cols as $i => $sc) {
            list($cs, $ce, $title, $name, $sigPath, $sigDate) = $sc;
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

            if ($sigPath && file_exists($sigPath)) {
                try {
                    $drawing = new Drawing();
                    $drawing->setName('sig' . $i);
                    $drawing->setPath($sigPath);
                    $drawing->setHeight(50);
                    $drawing->setCoordinates($cs . $sig_box_row);
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($sheet);
                } catch (\Exception $e) {
                    // ignore image errors
                }
            }
        }
        // Full border around entire signature block
        $sheet->getStyle('A' . $sig_title_row . ':I' . $sig_date_row)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Output
        $filename = 'PRF_' . $prf->prf_number . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Upload attachment (image/PDF) to a PRF.
     *
     * PDF disimpan utuh apa adanya — satu file upload selalu jadi satu baris
     * lampiran. Kalau file-nya tidak sanggup dibaca FPDI (PDF 1.5+ dengan
     * compressed object stream, atau terenkripsi), response menandai
     * needs_render supaya browser merender halamannya dan server merakit versi
     * export lewat upload_attachment_page() + finalize_attachment_render().
     */
    public function upload_attachment($id) {
        if (!check_access('prf', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $prf = $this->model->get_prf_header_by_id($id);
        if (!$prf) show_404();

        // AJAX uploads return JSON instead of redirecting, so the JS uploader
        // can chain the render step without a full detail-page render.
        $is_ajax = $this->input->is_ajax_request() || (int) $this->input->post('ajax') === 1;

        // Upload normalnya cuma di status Draft; mode edit Admin membukanya lagi
        // untuk PRF yang sudah masuk approval.
        $back = 'prf/detail/' . $id . (is_prf_admin_edit($prf) ? '?admin_edit=1' : '');

        $fail = function($msg) use ($back, $is_ajax) {
            if ($is_ajax) {
                $this->output->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['ok' => false, 'error' => $msg]));
                return;
            }
            $this->session->set_flashdata('error', $msg);
            redirect($back);
        };

        // post_max_size terlampaui bikin PHP mengosongkan $_FILES tanpa pesan —
        // tanpa cek ini pesannya jadi "tidak ada file yang dipilih" yang menyesatkan.
        if (empty($_FILES) && (int) $this->input->server('CONTENT_LENGTH') > 0) {
            return $fail('Ukuran file melebihi batas server (post_max_size). Kecilkan file atau naikkan limit di php.ini.');
        }

        if (empty($_FILES['attachment']['name'])) {
            return $fail('Tidak ada file yang dipilih.');
        }

        $upload_dir = FCPATH . 'uploads/prf_attachments/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $config = [
            'upload_path'   => $upload_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            // PDF sekarang diunggah utuh (dulu per halaman), jadi batasnya
            // harus muat satu dokumen scan penuh sekaligus.
            'max_size'      => 51200,
            'encrypt_name'  => true,
        ];
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('attachment')) {
            return $fail(strip_tags($this->upload->display_errors()));
        }

        $up = $this->upload->data();
        $ext = strtolower($up['file_ext']);
        $type = ($ext === '.pdf') ? 'pdf' : 'image';

        // Cek sekali di sini apakah export nanti sanggup menempel file ini.
        $page_count   = null;
        $needs_render = false;
        if ($type === 'pdf') {
            $pages = $this->_probe_pdf_pages($up['full_path']);
            if ($pages === false) {
                $needs_render = true;
            } else {
                $page_count = $pages;
            }
        }

        $attachment_id = $this->model->add_attachment([
            'prf_header_id' => $id,
            'file_name'     => $up['file_name'],
            'original_name' => $up['client_name'],
            'file_path'     => 'uploads/prf_attachments/' . $up['file_name'],
            'file_type'     => $type,
            'file_size'     => $up['file_size'] * 1024,
            'page_count'    => $page_count,
            'uploaded_by'   => $this->session->userdata('user_id'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if ($is_ajax) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok'            => true,
                    'attachment_id' => (int) $attachment_id,
                    'file_name'     => $up['file_name'],
                    'type'          => $type,
                    'page_count'    => $page_count,
                    'needs_render'  => $needs_render,
                ]));
            return;
        }

        $this->session->set_flashdata('success', 'Lampiran berhasil diunggah.');
        redirect($back);
    }

    /**
     * Jumlah halaman PDF kalau FPDI sanggup mem-parse-nya, false kalau tidak.
     *
     * FPDI versi gratis gagal pada PDF 1.5+ yang memakai compressed object
     * stream dan pada PDF terenkripsi. importPage() sengaja dipanggil karena
     * setSourceFile() saja belum tentu menyentuh isi halaman.
     */
    private function _probe_pdf_pages($abs) {
        try {
            $probe = new Mpdf(['mode' => 'utf-8']);
            $pages = $probe->setSourceFile($abs);
            $probe->importPage(1);
            unset($probe);
            return (int) $pages;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Folder penampung sementara halaman hasil render browser, per lampiran.
     */
    private function _attachment_render_tmp_dir($attachment_id) {
        return FCPATH . 'uploads/prf_attachments/render_tmp/' . (int) $attachment_id . '/';
    }

    /**
     * Guard bersama untuk endpoint render: balikin object lampiran, atau null
     * kalau tidak boleh diakses (response JSON sudah di-set saat return null).
     */
    private function _guard_render_endpoint($attachment_id) {
        $deny = function($code, $msg) {
            $this->output->set_status_header($code)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'error' => $msg]));
            return null;
        };

        if (!check_access('prf', 'view')) {
            return $deny(403, 'Anda tidak memiliki izin.');
        }
        $att = $this->model->get_attachment($attachment_id);
        if (!$att || $att->file_type !== 'pdf') {
            return $deny(404, 'Lampiran tidak ditemukan.');
        }
        $prf = $this->model->get_prf_header_by_id($att->prf_header_id);
        if (!$prf) {
            return $deny(404, 'Payment Request tidak ditemukan.');
        }
        return $att;
    }

    /**
     * Terima satu halaman hasil render PDF.js, simpan ke folder sementara.
     * Belum menyentuh tabel — baru dirakit di finalize_attachment_render().
     */
    public function upload_attachment_page($attachment_id) {
        $att = $this->_guard_render_endpoint($attachment_id);
        if (!$att) return;

        $fail = function($msg) {
            $this->output->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'error' => $msg]));
        };

        $page = (int) $this->input->post('page_no');
        if ($page < 1 || $page > 5000) {
            return $fail('Nomor halaman tidak valid.');
        }
        if (empty($_FILES['page']['name'])) {
            return $fail('Tidak ada data halaman yang dikirim.');
        }

        $dir = $this->_attachment_render_tmp_dir($att->id);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $config = [
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg',
            'max_size'      => 10240,
            'file_name'     => sprintf('p%04d.jpg', $page),
            'overwrite'     => true,
        ];
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('page')) {
            return $fail(strip_tags($this->upload->display_errors()));
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'page' => $page]));
    }

    /**
     * Rakit semua halaman di folder sementara jadi SATU PDF 1.4 yang dipakai
     * khusus saat export PRF. File asli tidak disentuh sama sekali.
     */
    public function finalize_attachment_render($attachment_id) {
        $att = $this->_guard_render_endpoint($attachment_id);
        if (!$att) return;

        $dir   = $this->_attachment_render_tmp_dir($att->id);
        $pages = glob($dir . 'p*.jpg');
        if (empty($pages)) {
            $this->output->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'error' => 'Tidak ada halaman yang tersimpan.']));
            return;
        }
        sort($pages, SORT_STRING);

        // Lampiran scan puluhan halaman gampang menembus limit default.
        @ini_set('memory_limit', '768M');
        @set_time_limit(300);

        try {
            $rel_render = 'uploads/prf_attachments/'
                . pathinfo($att->file_name, PATHINFO_FILENAME) . '_render.pdf';
            $this->_build_pdf_from_images($pages, FCPATH . $rel_render);
        } catch (\Throwable $e) {
            $this->output->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'error' => 'Gagal merakit PDF: ' . $e->getMessage()]));
            return;
        }

        // Versi rakitan lama (mis. user upload ulang file yang sama) ikut dibuang.
        if (!empty($att->render_path)
            && $att->render_path !== $rel_render
            && file_exists(FCPATH . $att->render_path)) {
            @unlink(FCPATH . $att->render_path);
        }

        $this->model->update_attachment($att->id, [
            'render_path' => $rel_render,
            'page_count'  => count($pages),
        ]);

        $this->_rrmdir($dir);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'pages' => count($pages)]));
    }

    /**
     * Bangun PDF dari daftar gambar — satu gambar satu halaman, ukuran halaman
     * mengikuti rasio gambarnya supaya tidak ada bidang kosong tambahan.
     * Output mPDF selalu PDF 1.4, jadi dijamin bisa dibaca balik FPDI gratis.
     */
    private function _build_pdf_from_images(array $images, $out_abs) {
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->SetAutoPageBreak(false, 0);

        foreach ($images as $img) {
            $info = @getimagesize($img);
            $w = 210.0;
            $h = ($info && $info[0] > 0) ? round($w * ($info[1] / $info[0]), 2) : 297.0;

            $mpdf->AddPageByArray([
                'orientation'   => 'P',
                'sheet-size'    => [$w, $h],
                'margin-left'   => 0,
                'margin-right'  => 0,
                'margin-top'    => 0,
                'margin-bottom' => 0,
            ]);
            $mpdf->WriteHTML(
                '<div style="position:fixed;top:0;left:0;width:' . $w . 'mm;height:' . $h . 'mm;">'
                . '<img src="' . htmlspecialchars($img) . '" style="width:100%;height:100%;" /></div>'
            );
        }

        $mpdf->Output($out_abs, \Mpdf\Output\Destination::FILE);
    }

    /**
     * Hapus folder beserta isinya (dipakai untuk folder render sementara).
     */
    private function _rrmdir($dir) {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '*') as $f) {
            is_dir($f) ? $this->_rrmdir($f . '/') : @unlink($f);
        }
        @rmdir($dir);
    }

    /**
     * Delete an attachment
     */
    public function delete_attachment($attachment_id) {
        if (!check_access('prf', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $att = $this->model->get_attachment($attachment_id);
        if (!$att) show_404();

        $prf = $this->model->get_prf_header_by_id($att->prf_header_id);
        if (!$prf) show_404();

        // Mode edit Admin melewati dua syarat di bawah (status Draft + hanya
        // pembuat), karena justru dipakai untuk membetulkan PRF orang lain yang
        // sudah terlanjur masuk approval.
        $admin_edit = is_prf_admin_edit($prf);
        $back = 'prf/detail/' . $att->prf_header_id . ($admin_edit ? '?admin_edit=1' : '');

        if (!$admin_edit && $prf->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Lampiran hanya dapat dihapus ketika Payment Request masih berstatus Draft.');
            redirect($back);
            return;
        }

        if (!$admin_edit && (int)$prf->requester_id !== (int)$this->session->userdata('user_id')) {
            $this->session->set_flashdata('error', 'Hanya pembuat Payment Request yang dapat menghapus lampiran.');
            redirect($back);
            return;
        }

        $abs = FCPATH . $att->file_path;
        if (file_exists($abs)) @unlink($abs);
        if (!empty($att->render_path) && file_exists(FCPATH . $att->render_path)) {
            @unlink(FCPATH . $att->render_path);
        }
        $this->_rrmdir($this->_attachment_render_tmp_dir($att->id));
        $this->model->delete_attachment($attachment_id);

        if ($admin_edit) {
            add_log('ADMIN_EDIT', 'PRF', 'Hapus lampiran #' . $attachment_id . ' dari Payment Request ' . $prf->prf_number
                . ' di status ' . $prf->status . ' (override Admin).');
        }
        $this->session->set_flashdata('success', 'Lampiran dihapus.');
        redirect($back);
    }

    /**
     * Stream attachment for inline preview
     */
    public function attachment($attachment_id) {
        $can_view_att = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval');
        if (!$can_view_att) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $att = $this->model->get_attachment($attachment_id);
        if (!$att) show_404();

        $abs = FCPATH . $att->file_path;
        if (!file_exists($abs)) show_404();

        // ?dl=1 memaksa unduh; tanpa itu tetap inline supaya preview modal jalan.
        $disposition = ((int) $this->input->get('dl') === 1) ? 'attachment' : 'inline';
        $safe_name   = str_replace('"', '', $att->original_name);

        $mime = mime_content_type($abs);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safe_name . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }

    /**
     * Helper upload 1 file bukti bayar ke folder prf_payment_proofs.
     * Return ['path' => string|null, 'error' => string|null].
     */
    private function _upload_payment_proof_file($field) {
        $upload_dir = FCPATH . 'uploads/prf_payment_proofs/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }
        $config = [
            'upload_path'   => $upload_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        ];
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($field)) {
            return ['path' => null, 'error' => strip_tags($this->upload->display_errors())];
        }
        $up = $this->upload->data();
        return ['path' => 'uploads/prf_payment_proofs/' . $up['file_name'], 'error' => null];
    }

    /**
     * Stream bukti bayar (inline). Akses sama dengan view detail (approver, requester, finance).
     * $slot: 1 = bukti utama, 2 = bukti kedua.
     */
    public function payment_proof($payment_id, $slot = 1) {
        $can = check_access('prf', 'view')
            || check_access('prf_approval', 'view')
            || check_access('prf_approval', 'head_dept')
            || check_access('prf_approval', 'endorsement')
            || check_access('prf_approval', 'approval')
            || check_access('prf_payment', 'view');
        if (!$can) show_error('Anda tidak memiliki izin.', 403);

        $pay = $this->model->get_payment_by_id((int)$payment_id);
        if (!$pay) show_404();

        $path = ((int)$slot === 2) ? ($pay->payment_proof_path_2 ?? null) : $pay->payment_proof_path;
        if (empty($path)) show_404();

        $abs = FCPATH . $path;
        if (!file_exists($abs)) show_404();

        $mime = mime_content_type($abs);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }

    /**
     * Upload / replace bukti bayar untuk 1 row payment_tracking yang sudah ada.
     * Dipakai kalau finance lupa attach bukti saat record_payment.
     * $slot: 1 = bukti utama, 2 = bukti kedua (opsional).
     */
    public function upload_payment_proof($payment_id, $slot = 1) {
        if (!check_access('prf_payment', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $slot = ((int)$slot === 2) ? 2 : 1;

        $pay = $this->model->get_payment_by_id((int)$payment_id);
        if (!$pay) show_404();

        if (empty($_FILES['payment_proof']['name'])) {
            $this->session->set_flashdata('error', 'Tidak ada file yang dipilih.');
            redirect('prf/detail/' . $pay->prf_header_id);
            return;
        }

        $res = $this->_upload_payment_proof_file('payment_proof');
        if ($res['error']) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $res['error']);
            redirect('prf/detail/' . $pay->prf_header_id);
            return;
        }
        $new_path = $res['path'];

        // Hapus file lama (sesuai slot) supaya tidak menumpuk.
        $old_path = ($slot === 2) ? ($pay->payment_proof_path_2 ?? null) : $pay->payment_proof_path;
        if (!empty($old_path)) {
            $old_abs = FCPATH . $old_path;
            if (file_exists($old_abs)) @unlink($old_abs);
        }

        $this->model->update_payment_proof((int)$payment_id, $new_path, $slot);
        $this->session->set_flashdata('success', 'Bukti bayar ' . $slot . ' berhasil diupload.');
        redirect('prf/detail/' . $pay->prf_header_id);
    }

    /**
     * Delete PRF. Default: hanya status Draft & user dengan permission delete.
     * Admin (role Admin) bisa hapus di status apapun (override) — beserta semua
     * child rows (details, attachments, payment tracking, approval logs).
     */
    public function delete($id) {
        $admin = is_admin();
        if (!$admin && !check_access('prf', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (!$admin) {
            $prf = $this->model->get_prf_header_by_id($id);
            if ($prf && !can_dept_access('prf', 'delete', $this->_doc_dept($prf))) {
                show_error('Payment Request ini milik departemen lain — Anda tidak berhak menghapusnya.', 403);
            }
        }

        if ($this->model->delete_prf($id, $admin)) {
            $msg = $admin
                ? 'Payment Request dihapus (Admin override).'
                : 'Payment Request berhasil dihapus.';
            $this->session->set_flashdata('success', $msg);
            redirect('prf');
        } else {
            $this->session->set_flashdata('error', $this->model->last_error ?: 'Gagal menghapus Payment Request.');
            redirect('prf/detail/' . $id);
        }
    }
}
