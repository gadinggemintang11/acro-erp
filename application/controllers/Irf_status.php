<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * IRF Status — monitoring & analisis per nomor IRF (consumable request).
 *
 * Menjawab: "IRF nomor sekian itu diminta berapa, sudah di-release berapa,
 * sisa outstanding berapa, dan sudah sampai mana prosesnya."
 *
 * Modul ini read-only (analisis). Untuk agregasi per ITEM lihat
 * Consumable_outstanding, untuk transaksinya lihat Consumable_request /
 * Consumable_out.
 */
class Irf_status extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Irf_status_model', 'Consumable_request_model']);
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    private function _filters($category_override = null) {
        return [
            'search'      => $this->input->get('search'),
            'department'  => $this->input->get('department'),
            'status'      => $this->input->get('status'),
            'fulfillment' => $this->input->get('fulfillment'),
            'date_from'   => $this->input->get('date_from'),
            'date_to'     => $this->input->get('date_to'),
            'category'    => $category_override,
        ];
    }

    /**
     * @param string|null $category_slug 'general'/'produksi' dari URL segment
     *        (pintu sidebar terkunci) — null berarti akses lama tanpa segment.
     */
    public function index($category_slug = null) {
        $locked_category = $this->normalize_category_slug($category_slug);

        if ($locked_category !== null) {
            if (!$this->can_category('irf_status', $locked_category, 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $visible_categories = [$locked_category];
        } else {
            $visible_categories = $this->allowed_categories('irf_status', 'view');
            if (empty($visible_categories)) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        $category_filter = $locked_category;
        if ($category_filter === null) {
            $requested = $this->input->get('category');
            if (!empty($requested) && in_array($requested, $visible_categories, true)) {
                $category_filter = $requested;
            }
        }

        $filters = $this->_filters($category_filter ?: $visible_categories);

        $title = lang_line('irfs_title', 'Status Permintaan Barang');
        if ($locked_category) { $title .= ' - ' . $locked_category; }

        $data = [
            'title'            => $title,
            'records'          => $this->Irf_status_model->get_list($filters),
            'summary'          => $this->Irf_status_model->get_summary($filters),
            'departments'      => $this->Irf_status_model->get_departments(),
            'statuses'         => $this->Irf_status_model->get_statuses(),
            'filters'          => array_merge($filters, ['category' => $category_filter]),
            'categories'       => $visible_categories,
            'locked_category'  => $locked_category,
            'category_slug'    => $category_slug,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('irf_status/index', $data);
        $this->load->view('templates/footer');
    }

    public function detail($id = null) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Irf_status_model->get_header($id);
        if (!$header) {
            $this->session->set_flashdata('error', lang_line('irfs_not_found', 'Permintaan Barang tidak ditemukan.'));
            redirect('irf_status');
            return;
        }

        if (!$this->can_category('irf_status', $header->category ?? 'General', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $items     = $this->Irf_status_model->get_item_breakdown($id);
        $issuances = $this->Irf_status_model->get_issuance_docs($id);
        $logs      = $this->Consumable_request_model->get_approval_logs($id);

        $data = [
            'title'     => sprintf(lang_line('irfs_detail_title', 'Status Permintaan Barang %s'), $header->request_number),
            'header'    => $header,
            'items'     => $items,
            'issuances' => $issuances,
            'logs'      => $logs,
            'stats'     => $stats = $this->_build_stats($header, $items, $issuances),
            'tracking'  => $this->_build_tracking($header, $items, $issuances, $stats, $this->Irf_status_model->get_tracking($id)),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('irf_status/detail', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // IRF TRACKING (user 2026-09-15): "IRF itu sampai mana — masih IRF
    // saja, sampai di PR, atau sudah di PO"
    // ============================================================

    /** URL detail dokumen terkait, atau null kalau user tidak punya akses modulnya. */
    private function _doc_url($kind, $doc) {
        $pr_map = ['produksi' => 'purchase_request', 'non_produksi' => 'purchase_request_general', 'jasa' => 'purchase_request_jasa', 'asset' => 'purchase_request_asset'];
        $po_map = ['produksi' => 'purchase_order', 'non_produksi' => 'purchase_order_general', 'jasa' => 'purchase_order_jasa', 'asset' => 'purchase_order_asset'];
        switch ($kind) {
            case 'pr':
                $c = $pr_map[$doc->pr_type] ?? null;
                return ($c && check_access($c, 'view')) ? base_url($c . '/detail/' . (int) $doc->pr_id) : null;
            case 'po':
                $c = $po_map[$doc->po_type] ?? null;
                return ($c && check_access($c, 'view')) ? base_url($c . '/detail/' . (int) $doc->po_id) : null;
            case 'sgr':
                $ok = check_access('supplier_general_item_receipt', 'view') || check_access('supplier_general_item_receipt_general', 'view') || check_access('supplier_general_item_receipt_produksi', 'view');
                return $ok ? base_url('supplier_general_item_receipt/detail/' . (int) $doc->doc_id) : null;
            case 'grn':
                return ($doc->warehouse_type !== 'jasa' && check_access('warehouse_in', 'view')) ? base_url('warehouse_in/detail/' . (int) $doc->doc_id) : null;
            case 'co':
                return $this->can_category('consumable_out', $doc->category ?? 'General', 'view') ? base_url('consumable_out/detail/' . (int) $doc->id) : null;
        }
        return null;
    }

    /**
     * Susun tahapan IRF (untuk stepper) dan posisi tiap item.
     *
     * Tahap: IRF dibuat -> Approval IRF -> PR -> PO -> Diterima gudang ->
     * Dikeluarkan ke user. Barang yang dipenuhi dari stok melompati PR/PO/
     * penerimaan (ditandai "dilewati").
     */
    private function _build_tracking($header, $items, $issuances, $stats, $chain) {
        $in_approval = in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true);
        $approved    = in_array($header->status, ['Approved', 'Released'], true);
        $stopped     = in_array($header->status, ['Rejected', 'Cancelled'], true);

        // Tautan detail untuk SETIAP objek di rantai (satu PR/PO bisa muncul di beberapa item).
        foreach ($chain as $row) {
            foreach ($row['prs'] as $pr) {
                $pr->url = $this->_doc_url('pr', $pr);
                foreach ($pr->pos as $po) {
                    $po->url = $this->_doc_url('po', $po);
                    foreach ($po->receipts as $rc) $rc->url = $this->_doc_url($rc->kind, $rc);
                }
            }
        }

        // Kumpulkan dokumen unik lintas item.
        $prs = []; $pos = []; $receipts = [];
        $pr_lines = 0; $pr_lines_with_po = 0; $po_qty = 0.0; $po_received = 0.0;
        foreach ($chain as $row) {
            foreach ($row['prs'] as $pr) {
                $prs[(int) $pr->pr_id] = $pr;
                if ($pr->pr_status === 'Rejected') continue;
                $pr_lines++;
                $active_pos = array_filter($pr->pos, function ($po) { return !in_array($po->po_status, ['Cancelled', 'Rejected'], true); });
                if ($active_pos) $pr_lines_with_po++;
                foreach ($pr->pos as $po) {
                    $pos[(int) $po->po_id] = $po;
                    if (in_array($po->po_status, ['Cancelled', 'Rejected'], true)) continue;
                    $po_qty      += (float) $po->po_qty;
                    $po_received += min((float) $po->received_qty, (float) $po->po_qty);
                    foreach ($po->receipts as $rc) $receipts[$rc->kind . $rc->doc_id] = $rc;
                }
            }
        }
        $doc = function ($kind, $list) {
            $out = [];
            foreach ($list as $d) {
                if (!isset($d->url)) $d->url = $this->_doc_url($kind === 'receipt' ? $d->kind : $kind, $d);
                $out[] = $d;
            }
            return $out;
        };
        foreach ($issuances as $co) $co->category = $header->category ?? 'General';

        $fully_released = $stats['qty_requested'] > 0 && $stats['qty_outstanding'] <= 0;
        $active_prs = array_filter($prs, function ($p) { return $p->pr_status !== 'Rejected'; });
        $active_pos = array_filter($pos, function ($p) { return !in_array($p->po_status, ['Cancelled', 'Rejected'], true); });
        $needs_purchase = !empty($active_prs);

        $steps = [];
        // Jalur approval berdasarkan nilai (2026-09-15); IRF lama = 3 tahap.
        $route_label = property_exists($header, 'approval_route')
            ? ' · 1 tahap (Head of Dept)'
            : '';

        $steps[] = ['label' => 'Permintaan Barang Dibuat', 'icon' => 'file-alt', 'state' => 'done',
                    'info' => date('d M Y', strtotime($header->request_date)) . ($header->created_by_name ? ' · ' . $header->created_by_name : '')];

        if ($stopped)          $steps[] = ['label' => 'Approval Permintaan Barang', 'icon' => 'user-check', 'state' => 'stopped', 'info' => $header->status];
        elseif ($approved)     $steps[] = ['label' => 'Approval Permintaan Barang', 'icon' => 'user-check', 'state' => 'done', 'info' => ($header->final_approved_at ?? null) ? 'Disetujui ' . date('d M Y', strtotime($header->final_approved_at)) . $route_label : 'Disetujui' . $route_label];
        elseif ($in_approval)  $steps[] = ['label' => 'Approval Permintaan Barang', 'icon' => 'user-check', 'state' => 'current', 'info' => 'Menunggu: ' . $header->status . $route_label];
        else                   $steps[] = ['label' => 'Approval Permintaan Barang', 'icon' => 'user-check', 'state' => 'pending', 'info' => 'Belum disubmit'];

        // PR
        if (!$approved)                  $pr_step = ['state' => 'pending', 'info' => '-'];
        elseif (!$needs_purchase)        $pr_step = $fully_released ? ['state' => 'skipped', 'info' => 'Tidak perlu PR — dipenuhi dari stok'] : ['state' => 'pending', 'info' => 'Belum ada PR'];
        else {
            $waiting = array_filter($active_prs, function ($p) { return $p->pr_status !== 'Approved'; });
            $pr_step = $waiting ? ['state' => 'current', 'info' => count($waiting) . ' PR menunggu approval'] : ['state' => 'done', 'info' => count($active_prs) . ' PR disetujui'];
        }
        $steps[] = $pr_step + ['label' => 'Purchase Request', 'icon' => 'clipboard-list', 'docs' => $doc('pr', $prs)];

        // PO
        if (!$needs_purchase)            $po_step = ['state' => $pr_step['state'] === 'skipped' ? 'skipped' : 'pending', 'info' => $pr_step['state'] === 'skipped' ? 'Dilewati' : '-'];
        elseif (!$active_pos)            $po_step = ['state' => $pr_step['state'] === 'done' ? 'current' : 'pending', 'info' => 'PR belum dibuatkan PO'];
        else {
            $waiting = array_filter($active_pos, function ($p) { return $p->po_status !== 'Confirmed'; });
            if ($pr_lines_with_po < $pr_lines)  $po_step = ['state' => 'current', 'info' => $pr_lines_with_po . ' dari ' . $pr_lines . ' baris PR sudah di PO'];
            elseif ($waiting)                   $po_step = ['state' => 'current', 'info' => count($waiting) . ' PO menunggu approval'];
            else                                $po_step = ['state' => 'done', 'info' => count($active_pos) . ' PO disetujui'];
        }
        $steps[] = $po_step + ['label' => 'PO', 'icon' => 'shopping-cart', 'docs' => $doc('po', $pos)];

        // Penerimaan gudang
        if (!$active_pos)                $rc_step = ['state' => $po_step['state'] === 'skipped' ? 'skipped' : 'pending', 'info' => $po_step['state'] === 'skipped' ? 'Dilewati' : '-'];
        elseif ($po_received <= 0)       $rc_step = ['state' => $po_step['state'] === 'done' ? 'current' : 'pending', 'info' => 'Barang belum datang'];
        elseif ($po_received + 0.0001 < $po_qty) $rc_step = ['state' => 'current', 'info' => 'Diterima sebagian (' . (0 + round($po_received, 2)) . ' / ' . (0 + round($po_qty, 2)) . ')'];
        else                             $rc_step = ['state' => 'done', 'info' => 'Diterima lengkap'];
        $steps[] = $rc_step + ['label' => 'Diterima Gudang', 'icon' => 'truck-loading', 'docs' => $doc('receipt', $receipts)];

        // Pengeluaran ke user
        if ($stopped)                     $out_step = ['state' => 'stopped', 'info' => '-'];
        elseif ($fully_released)          $out_step = ['state' => 'done', 'info' => 'Semua sudah dikeluarkan'];
        elseif ($stats['qty_released'] > 0) $out_step = ['state' => 'current', 'info' => (0 + round($stats['qty_released'], 2)) . ' / ' . (0 + round($stats['qty_requested'], 2)) . ' dikeluarkan'];
        else                              $out_step = ['state' => $approved ? 'current' : 'pending', 'info' => $approved ? 'Belum ada yang dikeluarkan' : '-'];
        $steps[] = $out_step + ['label' => 'Dikeluarkan ke User', 'icon' => 'hand-holding', 'docs' => $doc('co', $issuances)];

        // Posisi per item
        $item_stage = [];
        foreach ($items as $it) {
            $prs_it = $chain[(int) $it->cr_detail_id]['prs'] ?? [];
            $has_pr = false; $pr_ok = false; $has_po = false; $po_ok = false; $received = 0.0;
            foreach ($prs_it as $pr) {
                if ($pr->pr_status === 'Rejected') continue;
                $has_pr = true; if ($pr->pr_status === 'Approved') $pr_ok = true;
                foreach ($pr->pos as $po) {
                    if (in_array($po->po_status, ['Cancelled', 'Rejected'], true)) continue;
                    $has_po = true; if ($po->po_status === 'Confirmed') $po_ok = true;
                    $received += (float) $po->received_qty;
                }
            }
            if ($stopped)                          $st = ['Dihentikan (Permintaan Barang ' . $header->status . ')', 'danger'];
            elseif (!$approved)                    $st = [$in_approval ? 'Menunggu approval Permintaan Barang' : 'Permintaan Barang masih Draft', 'secondary'];
            elseif ((float) $it->qty_outstanding <= 0) $st = ['Selesai — sudah dikeluarkan ke user', 'success'];
            elseif ($received > 0)                 $st = ['Sudah diterima gudang, menunggu dikeluarkan', 'info'];
            elseif ($has_po)                       $st = [$po_ok ? 'Di PO — menunggu barang datang' : 'Di PO — menunggu approval PO', 'primary'];
            elseif ($has_pr)                       $st = [$pr_ok ? 'PR disetujui — belum dibuatkan PO' : 'Di PR — menunggu approval PR', 'warning'];
            elseif ((float) $it->qty_pending > 0)  $st = ['Sedang proses pengeluaran gudang', 'info'];
            elseif ((float) $it->qty_released > 0) $st = ['Sebagian dikeluarkan, sisanya belum diproses', 'warning'];
            else                                   $st = ['Masih di Permintaan Barang — menunggu dikeluarkan gudang / dibuatkan PR', 'secondary'];
            $item_stage[(int) $it->cr_detail_id] = ['label' => $st[0], 'color' => $st[1], 'prs' => $prs_it];
        }

        return ['steps' => $steps, 'items' => $item_stage];
    }

    /**
     * Hitung metrik analisis satu IRF dari baris item + dokumen pengeluaran.
     */
    private function _build_stats($header, $items, $issuances) {
        $qty_requested = 0.0;
        $qty_released  = 0.0;
        $qty_pending   = 0.0;
        $qty_out       = 0.0;
        $lines_complete = 0;
        $lines_partial  = 0;
        $lines_untouched = 0;
        $lines_over      = 0;

        foreach ($items as $it) {
            $req = (float)$it->qty_requested;
            $rel = (float)$it->qty_released;
            $pen = (float)$it->qty_pending;
            $out = (float)$it->qty_outstanding;

            $qty_requested += $req;
            $qty_released  += $rel;
            $qty_pending   += $pen;
            $qty_out       += $out;

            if ($rel > $req)           $lines_over++;
            if ($out <= 0)             $lines_complete++;
            elseif ($rel > 0)          $lines_partial++;
            else                       $lines_untouched++;
        }

        $progress = $qty_requested > 0 ? ($qty_released / $qty_requested) * 100 : 0;
        if ($progress > 100) $progress = 100;

        // Dokumen pengeluaran yang benar-benar terhitung (Confirmed) vs masih proses.
        $confirmed = array_filter($issuances, function ($d) { return $d->status === 'Confirmed'; });
        $inprocess = array_filter($issuances, function ($d) { return $d->status === 'Created'; });
        $cancelled = array_filter($issuances, function ($d) { return $d->status === 'Cancelled'; });

        $first_issue = null;
        $last_issue  = null;
        foreach ($confirmed as $d) {
            $when = $d->confirmed_at ?: $d->issuance_date;
            if (!$when) continue;
            if ($first_issue === null || strtotime($when) < strtotime($first_issue)) $first_issue = $when;
            if ($last_issue  === null || strtotime($when) > strtotime($last_issue))  $last_issue  = $when;
        }

        $approved_at = $header->final_approved_at ?: ($header->approved_at ?: $header->hod_approved_at);
        $base_date   = $approved_at ?: $header->request_date;

        $days = function ($from, $to) {
            if (empty($from) || empty($to)) return null;
            return (int) floor((strtotime($to) - strtotime($from)) / 86400);
        };

        $now = date('Y-m-d H:i:s');

        return [
            'qty_requested'    => $qty_requested,
            'qty_released'     => $qty_released,
            'qty_pending'      => $qty_pending,
            'qty_outstanding'  => $qty_out,
            'progress'         => $progress,
            'total_lines'      => count($items),
            'lines_complete'   => $lines_complete,
            'lines_partial'    => $lines_partial,
            'lines_untouched'  => $lines_untouched,
            'lines_over'       => $lines_over,
            'doc_confirmed'    => count($confirmed),
            'doc_inprocess'    => count($inprocess),
            'doc_cancelled'    => count($cancelled),
            'first_issue'      => $first_issue,
            'last_issue'       => $last_issue,
            'approved_at'      => $approved_at,
            // Lead time approval: request -> approve final
            'days_to_approve'  => $days($header->request_date, $approved_at),
            // Approve -> pengeluaran pertama
            'days_to_first'    => $days($base_date, $first_issue),
            // Approve -> pengeluaran terakhir (hanya bermakna kalau sudah lunas)
            'days_to_complete' => ($qty_out <= 0) ? $days($base_date, $last_issue) : null,
            // Umur outstanding berjalan
            'days_outstanding' => ($qty_out > 0) ? $days($base_date, $now) : null,
        ];
    }

    /**
     * Export daftar IRF (mengikuti filter aktif) ke CSV.
     */
    public function export_csv() {
        $requested = $this->input->get('category');
        if (in_array($requested, ['General', 'Produksi'], true)) {
            if (!$this->can_category('irf_status', $requested, 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $category_filter = $requested;
        } else {
            $category_filter = $this->allowed_categories('irf_status', 'view');
            if (empty($category_filter)) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        $records = $this->Irf_status_model->get_list($this->_filters($category_filter));

        $filename = 'irf_status_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

        fputcsv($out, [
            'No Permintaan Barang', 'Tanggal', 'Departemen', 'Requestor', 'Status',
            'Jml Item', 'Qty Diminta', 'Qty Released', 'Qty Proses', 'Qty Outstanding',
            'Progress (%)', 'Jml Dok Keluar', 'Release Pertama', 'Release Terakhir',
            'Umur (hari)', 'Umur Outstanding (hari)',
        ]);

        foreach ($records as $r) {
            $req = (float)$r->total_requested;
            $progress = $req > 0 ? min(((float)$r->total_released / $req) * 100, 100) : 0;
            fputcsv($out, [
                $r->request_number,
                $r->request_date,
                $r->department,
                $r->requestor_name,
                $r->status,
                $r->total_lines,
                number_format((float)$r->total_requested, 2, '.', ''),
                number_format((float)$r->total_released, 2, '.', ''),
                number_format((float)$r->total_pending, 2, '.', ''),
                number_format((float)$r->total_outstanding, 2, '.', ''),
                number_format($progress, 1, '.', ''),
                $r->issuance_count,
                $r->first_issued_at,
                $r->last_issued_at,
                $r->age_days,
                $r->outstanding_age_days,
            ]);
        }

        fclose($out);
    }
}
