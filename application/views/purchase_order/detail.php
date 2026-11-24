<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Detail Purchase Order -- SATU view untuk keempat tipe PO (Produksi, General,
 * Jasa, Asset). Redesign 2026-11-09 (user: "lanjut halaman po detail rombak
 * designnya"), gaya .pp2 sama dengan halaman Buat PO / MR / QA.
 *
 * Dulu ada 4 salinan (purchase_order[_general|_jasa|_asset]/detail.php) yang melenceng: General/
 * Jasa/Asset masih menghitung PPN 11% tetap, dan skrip konfirmasi hapus/submit
 * diganti stub kosong sehingga tombol Hapus Item & Submit langsung jalan tanpa
 * konfirmasi. Sekarang konfirmasi memakai handler global di templates/footer.php
 * (.btn-confirm / .btn-submit / .btn-delete).
 *
 * Data dari Purchase_order::detail(): $po_type, $po_route, $approval_route,
 * $receipts (Po_model::get_receipt_overview), + data khusus tipe dari
 * _extra_detail_data(): open_contracts (Produksi/General), service_items (Jasa),
 * departments/receivable_items/registered_assets (Asset).
 */
$this->load->view('purchase_order/_po_style');

$po_type    = $po_type ?? 'produksi';
$route      = $po_route ?? 'purchase_order';
$appr_route = $approval_route ?? 'purchase_order_approval';
$st         = $header->status;
$cur        = $header->currency_code ?: 'IDR';
$fx         = (float) $header->exchange_rate ?: 1;
$details    = $details ?? [];
$can_edit_item  = !empty($can_edit_item);
$allow_manual   = !empty($allow_manual_items);
$admin_mode     = !empty($admin_edit_mode);
$admin_q        = $admin_mode ? '?admin_edit=1' : '';
$recv_lines     = $receipts['lines'] ?? [];
$recv_docs      = $receipts['docs'] ?? [];

$type_meta = [
    'produksi'     => ['label' => lang_line('pod2_type_produksi', 'Material Produksi'), 'icon' => 'fa-boxes',         'pr' => 'purchase_request',
                       'recv' => lang_line('pod2_step_received', 'Diterima Gudang'),     'unconfirm' => lang_line('po_confirm_unconfirm_text', 'Pastikan belum ada penerimaan barang.')],
    'non_produksi' => ['label' => lang_line('pod2_type_general', 'General'),            'icon' => 'fa-toolbox',       'pr' => 'purchase_request_general',
                       'recv' => lang_line('pod2_step_received', 'Diterima Gudang'),     'unconfirm' => lang_line('po_confirm_unconfirm_text', 'Pastikan belum ada penerimaan barang.')],
    'jasa'         => ['label' => lang_line('pod2_type_jasa', 'Jasa'),                  'icon' => 'fa-hands-helping', 'pr' => 'purchase_request_jasa',
                       'recv' => lang_line('pod2_step_service', 'Jasa Diselesaikan'),   'unconfirm' => lang_line('pod2_unconfirm_jasa', 'Pastikan belum ada jasa yang diselesaikan.')],
    'asset'        => ['label' => lang_line('pod2_type_asset', 'Asset'),                'icon' => 'fa-building',      'pr' => 'purchase_request_asset',
                       'recv' => lang_line('pod2_step_asset', 'Aset Diterima'),         'unconfirm' => lang_line('pod2_unconfirm_asset', 'Pastikan belum ada unit yang masuk Asset Register.')],
][$po_type] ?? null;
if (!$type_meta) $type_meta = ['label' => 'PO', 'icon' => 'fa-file-invoice', 'pr' => 'purchase_request', 'recv' => 'Diterima', 'unconfirm' => ''];
// Daftar GMPO menggabungkan General/Jasa/Asset (user 2026-09-05) -> kembali ke sana.
$back_route = $po_type === 'produksi' ? 'purchase_order'
            : (check_access('purchase_order_general', 'view') ? 'purchase_order_general' : $route);

// ---- format angka (format en-US, sama dengan halaman Buat PO & PDF) ----
$fmt_dt = function ($v, $time = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$money  = function ($n) { return number_format((float) $n, 2, '.', ','); };
$price  = function ($n) { return preg_replace('/(\.\d\d\d*?)0+$/', '$1', number_format((float) $n, 4, '.', ',')); };
$qty    = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };

// ---- jejak approval dari log (submit_po tidak mengisi submitted_by) ----
$last_log = function ($action) use ($approval_logs) { $f = null; foreach (($approval_logs ?? []) as $l) { if ($l->action === $action) $f = $l; } return $f; };
$log_submit = $last_log('Submit');
$log_final  = $last_log('Approve Final');
$log_reject = $last_log('Reject');
$log_return = $last_log('Return to Draft');

// ---- nilai & penerimaan ----
$subtotal = 0.0; $subtotal_local = 0.0; $n_zero = 0; $n_full = 0; $any_recv = false; $any_pending = false;
foreach ($details as $d) {
    $subtotal       += (float) $d->qty * (float) $d->base_price;
    $subtotal_local += (float) ($d->total_local ?? 0);
    if ((float) $d->base_price == 0) $n_zero++;
    $r = $recv_lines[(int) $d->id] ?? ['received' => 0, 'pending' => 0];
    if ($r['received'] > 0) $any_recv = true;
    if ($r['pending'] > 0) $any_pending = true;
    if ($r['received'] >= (float) $d->qty - 0.0001) $n_full++;
}
$ppn       = po_ppn_from_header($header, $subtotal);
$ppn_local = po_ppn_from_header($header, $subtotal_local);
$n_items   = count($details);

// ---- posisi alur: Dibuat -> Diajukan -> Disetujui -> Diterima ----
$level = ['Draft' => 0, 'Submitted' => 1, 'Approved Head Dept' => 1, 'Endorsed' => 1, 'Confirmed' => 2, 'Closed' => 3, 'Rejected' => 1][$st] ?? 0;
if ($st === 'Cancelled') $level = !empty($header->confirmed_at) ? 2 : ($log_submit ? 1 : 0);
$recv_full    = $n_items > 0 && $n_full === $n_items;
if ($level === 2 && $recv_full) $level = 3;
$recv_partial = $level === 2 && ($any_recv || $any_pending);
$stopped      = in_array($st, ['Rejected', 'Cancelled'], true);
$steps = [
    ['label' => lang_line('pod2_step_created', 'Dibuat'),    'icon' => 'fa-pen',         'who' => $header->creator_name, 'at' => $fmt_dt($header->created_at ?? null), 'wait' => ''],
    ['label' => lang_line('pod2_step_submitted', 'Diajukan'), 'icon' => 'fa-paper-plane', 'who' => $log_submit->actor_name ?? ($header->creator_name ?? null),
     'at' => $log_submit ? $fmt_dt($log_submit->created_at) : $fmt_dt($header->submitted_at ?? null), 'wait' => lang_line('pod2_wait_submit', 'Menunggu submit')],
    ['label' => lang_line('pod2_step_approved', 'Disetujui'), 'icon' => 'fa-check',       'who' => $header->confirmer_name ?: ($log_final->actor_name ?? null),
     'at' => $fmt_dt($header->confirmed_at ?: ($header->final_approved_at ?? null)), 'wait' => lang_line('pod2_wait_approval', 'Menunggu approval')],
    ['label' => $type_meta['recv'],                          'icon' => $po_type === 'jasa' ? 'fa-clipboard-check' : ($po_type === 'asset' ? 'fa-tags' : 'fa-dolly'),
     'who' => $st === 'Closed' && !$recv_full ? lang_line('pod2_closed', 'PO ditutup') : sprintf(lang_line('pod2_lines_complete', '%d/%d baris lengkap'), $n_full, $n_items),
     'at' => null, 'wait' => $po_type === 'jasa' ? lang_line('pod2_wait_service', 'Menunggu penyelesaian jasa') : lang_line('pod2_wait_receipt', 'Menunggu penerimaan')],
];

$status_cls = [
    'Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress',
    'Endorsed' => 'pp-status--progress', 'Confirmed' => 'pp-status--completed', 'Closed' => 'pp-status--other',
    'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad',
][$st] ?? 'pp-status--other';
$waiting_approval = in_array($st, ['Submitted', 'Approved Head Dept', 'Endorsed'], true);

// ---- kolom tabel item ----
$show_recv_col = $level >= 2 || $any_recv || $any_pending;
$show_sc       = in_array($po_type, ['produksi', 'non_produksi'], true);
$trail_cols    = ($show_recv_col ? 1 : 0) + ($can_edit_item ? 1 : 0);
$recv_word     = $po_type === 'jasa' ? lang_line('pod2_th_done', 'Selesai') : lang_line('pod2_th_received', 'Diterima');

$more_actions = [];
if ($st === 'Confirmed' && check_access($route, 'edit')) {
    $more_actions[] = '<a class="dropdown-item btn-confirm" href="' . base_url($route . '/unconfirm_po/' . $header->id) . '" data-message="' . htmlspecialchars(lang_line('po_confirm_unconfirm', 'Yakin ingin membatalkan konfirmasi PO ini?')) . '" data-text="' . htmlspecialchars($type_meta['unconfirm']) . '" data-icon="warning" data-confirm-text="' . htmlspecialchars(lang_line('pod2_yes_unconfirm', 'Ya, Batalkan Konfirmasi')) . '"><i class="fas fa-undo-alt"></i>' . lang_line('po_unconfirm', 'Unconfirm') . '</a>';
}
if (in_array($st, ['Draft', 'Submitted', 'Confirmed'], true) && check_access($route, 'delete')) {
    $more_actions[] = '<a class="dropdown-item text-danger btn-confirm" href="' . base_url($route . '/cancel_po/' . $header->id) . '" data-message="' . htmlspecialchars(lang_line('po_confirm_cancel', 'Yakin ingin membatalkan (cancel) PO ini?')) . '" data-text="' . htmlspecialchars(lang_line('po_confirm_cancel_text', 'Aksi ini tidak bisa diurungkan.')) . '" data-icon="error" data-confirm-text="' . htmlspecialchars(lang_line('pod2_yes_cancel', 'Ya, Batalkan PO')) . '"><i class="fas fa-ban"></i>' . lang_line('po_cancel_po', 'Cancel PO') . '</a>';
}
if (in_array($st, ['Draft', 'Submitted', 'Confirmed'], true) && is_admin()) {
    $more_actions[] = '<button type="button" class="dropdown-item text-danger" id="btn-batalkan-po" data-return-url="' . base_url($route . '/return_to_draft/' . $header->id) . '" data-delete-url="' . base_url($route . '/force_delete/' . $header->id) . '" data-status="' . htmlspecialchars($st) . '" data-po-number="' . htmlspecialchars($header->po_number) . '"><i class="fas fa-user-shield"></i>' . lang_line('po_force_action', 'Force Return/Delete') . ' <small class="text-muted">(Admin)</small></button>';
}
?>

<div class="container-fluid pp2 pod2">

    <!-- ================= HERO ================= -->
    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas <?= $type_meta['icon']; ?>"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker">Purchase Order · <?= htmlspecialchars($type_meta['label']); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($header->po_number); ?> <span class="pp-status <?= $status_cls; ?>"><?= htmlspecialchars($st); ?></span></h1>
                    <div class="pp-hero-subline">
                        <i class="far fa-calendar mr-1"></i><?= $fmt_dt($header->po_date, false); ?>
                        <span class="mx-1">·</span><i class="fas fa-store mr-1"></i><?= htmlspecialchars($header->nama_supplier ?: '-'); ?>
                    </div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($st === 'Draft' && check_access($route, 'submit')): ?>
                    <a href="<?= base_url($route . '/submit_po/' . $header->id); ?>" class="btn pp-btn-light btn-submit<?= $n_items ? '' : ' disabled'; ?>"
                       data-message="<?= htmlspecialchars(lang_line('po_confirm_submit', 'Yakin ingin submit PO ini? Setelah disubmit, header tidak bisa diedit.')); ?>"
                       data-text="<?= $n_zero ? htmlspecialchars(sprintf(lang_line('pod2_submit_zero_warn', 'Perhatian: %d item harga satuannya masih 0.'), $n_zero)) : ''; ?>"
                       data-icon="<?= $n_zero ? 'warning' : 'question'; ?>" data-confirm-text="<?= htmlspecialchars(lang_line('pod2_yes_submit', 'Ya, Submit')); ?>"><i class="fas fa-paper-plane mr-1"></i><?= lang_line('po_submit', 'Submit PO'); ?></a>
                <?php endif; ?>
                <?php if (!empty($can_edit_header)): ?>
                    <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#editHeaderModalDetail"><i class="fas fa-pen mr-1"></i><?= lang_line('po_edit', 'Edit Header'); ?></button>
                <?php endif; ?>
                <?php if (!empty($can_unlock_admin) && !$admin_mode): ?>
                    <a href="<?= base_url($route . '/detail/' . $header->id); ?>?admin_edit=1" class="btn pp-btn-glass btn-confirm no-loader"
                       data-message="<?= htmlspecialchars(lang_line('pod2_unlock_q', 'Buka kunci edit PO ini?')); ?>"
                       data-text="<?= htmlspecialchars(sprintf(lang_line('pod2_unlock_text', 'PO sudah %s. Membuka kunci memungkinkan Anda mengubahnya sebagai Admin. Approval TIDAK direset.'), $st)); ?>"
                       data-icon="warning" data-confirm-text="<?= htmlspecialchars(lang_line('pod2_yes_unlock', 'Ya, Buka Kunci')); ?>"><i class="fas fa-lock mr-1"></i><?= lang_line('pod2_unlock', 'Buka Kunci (Admin)'); ?></a>
                <?php elseif ($admin_mode): ?>
                    <a href="<?= base_url($route . '/detail/' . $header->id); ?>" class="btn pp-btn-light no-loader"><i class="fas fa-lock-open mr-1"></i><?= lang_line('pod2_lock_again', 'Selesai Edit (Kunci Lagi)'); ?></a>
                <?php endif; ?>
                <?php if ($waiting_approval && check_access($appr_route, 'view')): ?>
                    <a href="<?= base_url($appr_route . '/detail/' . $header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-user-check mr-1"></i><?= lang_line('pod2_open_approval', 'Buka di Approval'); ?></a>
                <?php endif; ?>
                <a href="<?= base_url($route . '/export_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="<?= base_url($route . '/export_excel/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-file-excel mr-1"></i>Excel</a>
                <?php if ($more_actions): ?>
                <div class="dropdown">
                    <button type="button" class="btn pp-btn-glass" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars(lang_line('pod2_more', 'Aksi lain')); ?>"><i class="fas fa-ellipsis-h"></i></button>
                    <div class="dropdown-menu dropdown-menu-right"><?= implode('', $more_actions); ?></div>
                </div>
                <?php endif; ?>
                <a href="<?= base_url($back_route); ?>" class="btn pp-btn-glass btn-load"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('pod2_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_supplier', 'Supplier'); ?></div><div class="pp-meta-value" title="<?= htmlspecialchars($header->nama_supplier ?? ''); ?>"><?= htmlspecialchars($header->nama_supplier ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_currency', 'Mata Uang'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($cur); ?><?php if ($cur !== 'IDR'): ?> <small>@ <?= $price($fx); ?></small><?php endif; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></div><div class="pp-meta-value" title="<?= htmlspecialchars($header->payment_mode_desc ?? ''); ?>"><?= htmlspecialchars($header->payment_mode_desc ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></div><div class="pp-meta-value" title="<?= htmlspecialchars($header->shipping_mode_desc ?? ''); ?>"><?= htmlspecialchars($header->shipping_mode_desc ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pod2_tax', 'Pajak'); ?></div><div class="pp-meta-value"><?= $ppn['applied'] ? htmlspecialchars(po_ppn_label($ppn['rate'], false)) . ($ppn['inclusive'] ? ' <small>' . lang_line('pod2_ppn_incl', 'termasuk') . '</small>' : '') : lang_line('pod2_no_ppn', 'Tanpa PPN'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_grand_total', 'Grand Total'); ?></div><div class="pp-meta-value is-total"><small><?= htmlspecialchars($cur); ?></small> <span id="grandtotal-hero"><?= $money($ppn['total']); ?></span></div></div>
        </div>
    </section>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <!-- ================= CATATAN STATUS ================= -->
    <?php if ($st === 'Rejected'): ?>
        <div class="pod-note pod-note--bad"><i class="fas fa-ban"></i><div>
            <b><?= lang_line('pod2_rejected', 'PO ditolak'); ?></b><?= ($log_reject->actor_name ?? null) ? ' ' . lang_line('pod2_by', 'oleh') . ' ' . htmlspecialchars($log_reject->actor_name) : ''; ?><?= $fmt_dt($header->rejected_at ?? null) ? ' · ' . $fmt_dt($header->rejected_at) : ''; ?>
            <br><?= nl2br(htmlspecialchars((string) ($header->reject_reason ?: '-'))); ?>
        </div></div>
    <?php elseif ($st === 'Cancelled'): ?>
        <div class="pod-note pod-note--bad"><i class="fas fa-ban"></i><div><b><?= lang_line('pod2_cancelled', 'PO ini sudah dibatalkan (Cancelled).'); ?></b><br><small><?= lang_line('pod2_cancelled_hint', 'Dokumen hanya untuk arsip — tidak bisa diproses lagi.'); ?></small></div></div>
    <?php elseif ($st === 'Draft' && !empty($header->return_reason)): ?>
        <div class="pod-note pod-note--warn"><i class="fas fa-undo"></i><div>
            <b><?= lang_line('pod2_returned', 'Dikembalikan ke Draft'); ?></b><?= ($log_return->actor_name ?? null) ? ' ' . lang_line('pod2_by', 'oleh') . ' ' . htmlspecialchars($log_return->actor_name) : ''; ?><?= $fmt_dt($header->returned_to_draft_at ?? null) ? ' · ' . $fmt_dt($header->returned_to_draft_at) : ''; ?>
            <br><?= nl2br(htmlspecialchars($header->return_reason)); ?>
            <br><small><?= lang_line('pod2_returned_hint', 'Perbaiki PO, lalu Submit ulang. Balas di Riwayat Approval kalau perlu klarifikasi.'); ?></small>
        </div></div>
    <?php endif; ?>

    <?php if ($admin_mode && !empty($can_edit_header)): ?>
        <div class="pod-note pod-note--admin"><i class="fas fa-user-shield"></i><div>
            <b><?= lang_line('pod2_admin_mode', 'Mode Edit Admin'); ?></b> — <?= sprintf(lang_line('pod2_admin_mode_text', 'PO berstatus %s normalnya terkunci. Perubahan Anda tidak mereset approval dan tercatat di Log Activity.'), '<b>' . htmlspecialchars($st) . '</b>'); ?>
            <?php if (!empty($has_receipts)): ?><br><span class="text-danger font-weight-bold"><i class="fas fa-lock mr-1"></i><?= lang_line('pod2_admin_rows_locked', 'Baris item terkunci karena PO ini sudah ada penerimaan — hanya header yang bisa diubah.'); ?></span><?php endif; ?>
        </div></div>
    <?php endif; ?>

    <?php if ($n_zero && $can_edit_item): ?>
        <div class="pod-note pod-note--warn"><i class="fas fa-tag"></i><div><?= sprintf(lang_line('pod2_zero_price', '<b>%d item</b> harga satuannya masih 0 — klik tombol edit pada baris item untuk mengisi harga.'), $n_zero); ?></div></div>
    <?php endif; ?>
    <?php if (!$n_items && $st === 'Draft'): ?>
        <div class="pod-note pod-note--info"><i class="fas fa-inbox"></i><div><?= $allow_manual ? lang_line('pod2_empty_manual', 'Belum ada item. Tarik dari Purchase Request atau tambah item manual sebelum Submit.') : lang_line('pod2_empty_pr', 'Belum ada item. Klik "Tarik dari PR" untuk mengambil item dari Purchase Request yang sudah disetujui.'); ?></div></div>
    <?php endif; ?>

    <!-- ================= ALUR ================= -->
    <nav class="pod-steps" aria-label="<?= htmlspecialchars(lang_line('pod2_flow', 'Alur Purchase Order')); ?>">
        <?php foreach ($steps as $i => $s):
            $cls = '';
            if ($st === 'Rejected' && $i === 2) $cls = 'is-rejected';
            elseif ($i <= $level) $cls = 'is-done';
            elseif ($i === 3 && $i === $level + 1 && $recv_partial && !$stopped) $cls = 'is-partial';
            elseif ($i === $level + 1 && !$stopped) $cls = 'is-current';
            $label = ($cls === 'is-rejected') ? lang_line('pod2_step_rejected', 'Ditolak') : $s['label'];
            $icon  = $cls === 'is-done' ? 'fa-check' : ($cls === 'is-rejected' ? 'fa-times' : $s['icon']);
            if ($cls === 'is-rejected') $meta = trim(($log_reject->actor_name ?? '-') . ($fmt_dt($header->rejected_at ?? null) ? ' · ' . $fmt_dt($header->rejected_at) : ''));
            elseif ($cls === 'is-done' || $cls === 'is-partial') $meta = trim(($s['who'] ?: '-') . ($s['at'] ? ' · ' . $s['at'] : ''));
            elseif ($cls === 'is-current') $meta = $s['wait'];
            else $meta = '—';
        ?>
        <div class="pod-step <?= $cls; ?>">
            <span class="pod-step-dot"><i class="fas <?= $icon; ?>"></i></span>
            <span style="min-width:0">
                <span class="pod-step-name d-block"><?= htmlspecialchars($label); ?><?= $cls === 'is-partial' ? ' <small class="text-warning">(' . lang_line('pod2_partial', 'sebagian') . ')</small>' : ''; ?></span>
                <span class="pod-step-meta d-block" title="<?= htmlspecialchars($meta); ?>"><?= htmlspecialchars($meta); ?></span>
            </span>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- ================= ITEM ================= -->
    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-list-ul"></i></span><span><?= lang_line('pod2_items', 'Item Purchase Order'); ?> (<span id="pod-item-count"><?= $n_items; ?></span>)
                <span class="d-block pp-sub"><?= $can_edit_item ? lang_line('pod2_items_sub_edit', 'Klik ikon pensil pada baris untuk mengubah qty, harga, atau keterangan.') : sprintf(lang_line('pod2_items_sub', 'Harga dalam %s'), htmlspecialchars($cur)); ?></span></span></h2>
            <?php if ($can_edit_item): ?>
            <div class="d-flex flex-wrap" style="gap:.4rem">
                <?php if (!empty($open_contracts) && $show_sc): ?>
                    <button type="button" class="btn btn-light btn-sm" id="pod-bom-toggle"><i class="fas fa-magic mr-1 text-success"></i><?= lang_line('pod2_bom_btn', 'Muat dari BOM'); ?></button>
                <?php endif; ?>
                <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#pullPrModal"><i class="fas fa-file-import mr-1"></i><?= lang_line('pod2_pull_pr', 'Tarik dari PR'); ?></button>
                <?php if ($allow_manual): ?>
                    <button type="button" class="btn btn-pp btn-sm" id="add-item-row-btn"><i class="fas fa-plus mr-1"></i><?= lang_line('po_add_item', 'Tambah Item'); ?></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($can_edit_item && !empty($open_contracts) && $show_sc): ?>
        <div class="pod-bom" id="pod-bom-panel">
            <div class="d-flex flex-wrap align-items-center" style="gap:.5rem">
                <select id="bom-sc-select" class="form-control form-control-sm" style="max-width:320px">
                    <option value=""><?= lang_line('po_select_sales_contract', '-- Pilih Sales Order --'); ?></option>
                    <?php foreach ($open_contracts as $sc): ?><option value="<?= (int) $sc->id; ?>"><?= htmlspecialchars($sc->sc_number); ?></option><?php endforeach; ?>
                </select>
                <button type="button" id="btn-load-bom" class="btn btn-success btn-sm"><i class="fas fa-download mr-1"></i><?= lang_line('po_load_bom_button', 'Load BOM'); ?></button>
                <span id="bom-load-result" class="small"></span>
            </div>
            <small class="pp-hint"><?= lang_line('po_load_bom_help', 'Qty otomatis = qty style SC × kebutuhan material di BOM. Harga diisi Purchasing.'); ?></small>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="pp-table pod-items" id="po-items-table">
                <thead><tr>
                    <th class="pod-no">#</th>
                    <th><?= lang_line('pod2_th_item', 'Item'); ?></th>
                    <th class="num"><?= lang_line('po_qty', 'Qty'); ?></th>
                    <th><?= lang_line('po_unit', 'Satuan'); ?></th>
                    <th class="num"><?= lang_line('po_base_price', 'Harga Satuan'); ?> <small>(<?= htmlspecialchars($cur); ?>)</small></th>
                    <th class="num"><?= lang_line('po_total', 'Total'); ?> <small>(<?= htmlspecialchars($cur); ?>)</small></th>
                    <?php if ($show_recv_col): ?><th class="num"><?= $recv_word; ?></th><?php endif; ?>
                    <?php if ($can_edit_item): ?><th class="text-right" style="width:90px"></th><?php endif; ?>
                </tr></thead>
                <tbody id="po-item-details-body">
                <?php if (!$details): ?>
                    <tr id="no-item-row"><td colspan="<?= 6 + $trail_cols; ?>" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pod2_no_items', 'Belum ada item.'); ?></td></tr>
                <?php else: $no = 1; foreach ($details as $d):
                    $line_total = (float) $d->qty * (float) $d->base_price;
                    $zero = (float) $d->base_price == 0;
                    $r = $recv_lines[(int) $d->id] ?? ['received' => 0, 'pending' => 0];
                    $pct = (float) $d->qty > 0 ? min(100, round($r['received'] / (float) $d->qty * 100)) : 0;
                ?>
                    <tr data-detail-id="<?= (int) $d->id; ?>" data-total="<?= $line_total; ?>" class="<?= $zero ? 'price-zero-row' : ''; ?>">
                        <td class="pod-no"><?= $no++; ?></td>
                        <td>
                            <div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div>
                            <div class="pod-meta">
                                <span class="pp-item-code"><?= htmlspecialchars($d->item_code ?: '-'); ?></span>
                                <?php if (!empty($d->pr_number) && !empty($d->pr_header_id)): ?>
                                    <a href="<?= base_url($type_meta['pr'] . '/detail/' . (int) $d->pr_header_id); ?>" class="pp-chip" title="<?= htmlspecialchars(lang_line('pod2_from_pr', 'Ditarik dari PR') . (isset($d->pr_qty) ? ' · qty PR ' . $qty($d->pr_qty) : '')); ?>"><i class="fas fa-file-import"></i><?= htmlspecialchars($d->pr_number); ?></a>
                                <?php endif; ?>
                                <?php if ($show_sc && !empty($d->sc_number)): ?><span class="pp-chip" title="Sales Order"><i class="fas fa-file-contract"></i><?= htmlspecialchars($d->sc_number); ?></span><?php endif; ?>
                            </div>
                            <div class="cell-desc">
                                <div class="pod-desc display-val"<?= trim((string) $d->description) === '' ? ' style="display:none"' : ''; ?>><?= htmlspecialchars((string) $d->description); ?></div>
                                <?php if ($can_edit_item): ?><input type="text" class="form-control edit-input" style="display:none" value="<?= htmlspecialchars((string) $d->description); ?>" placeholder="<?= htmlspecialchars(lang_line('pod2_desc_ph', 'Keterangan / spesifikasi (opsional)')); ?>"><?php endif; ?>
                            </div>
                        </td>
                        <td class="num cell-qty">
                            <span class="display-val font-weight-bold"><?= $qty($d->qty); ?></span>
                            <?php if ($can_edit_item): ?><input type="number" step="0.0001" min="0.0001" class="form-control edit-input text-right no-spin" style="display:none" value="<?= (float) $d->qty; ?>"><?php endif; ?>
                        </td>
                        <td><span class="pod-unit"><?= htmlspecialchars($d->item_unit ?: '-'); ?></span></td>
                        <td class="num cell-price<?= $zero ? ' price-zero' : ''; ?>">
                            <span class="display-val"><?= $price($d->base_price); ?><?= $zero ? ' <i class="fas fa-exclamation-triangle" title="' . htmlspecialchars(lang_line('pod2_price_zero', 'Harga belum diisi')) . '"></i>' : ''; ?></span>
                            <?php if ($can_edit_item): ?><input type="number" step="0.0001" min="0" class="form-control edit-input text-right no-spin" style="display:none" value="<?= (float) $d->base_price; ?>"><?php endif; ?>
                        </td>
                        <td class="num cell-total font-weight-bold"><?= $money($line_total); ?></td>
                        <?php if ($show_recv_col): ?>
                        <td class="num">
                            <span class="<?= $pct >= 100 ? 'text-success font-weight-bold' : ''; ?>"><?= $qty($r['received']); ?></span><small class="text-muted"> / <?= $qty($d->qty); ?></small>
                            <div class="pod-prog<?= $pct > 0 && $pct < 100 ? ' is-partial' : ''; ?>"><span style="width:<?= $pct; ?>%"></span></div>
                            <?php if ($r['pending'] > 0): ?><div class="pp-item-code" title="<?= htmlspecialchars(lang_line('pod2_pending_hint', 'Masih di dokumen penerimaan berstatus Draft')); ?>">+<?= $qty($r['pending']); ?> draft</div><?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($can_edit_item): ?>
                        <td class="text-right">
                            <div class="pp-actions btn-view-mode">
                                <button type="button" class="btn btn-light btn-edit-inline" data-id="<?= (int) $d->id; ?>" title="<?= htmlspecialchars(lang_line('po_edit_button_title', 'Edit')); ?>"><i class="fas fa-pen"></i></button>
                                <a href="<?= base_url($route . '/delete_item/' . $d->po_header_id . '/' . $d->id) . $admin_q; ?>" class="btn btn-light text-danger btn-delete" title="<?= htmlspecialchars(lang_line('po_delete_button_title', 'Hapus')); ?>"
                                   data-message="<?= htmlspecialchars(sprintf(lang_line('pod2_delete_item_q', 'Hapus item %s dari PO?'), $d->item_name ?: '')); ?>" data-text="<?= htmlspecialchars(!empty($d->pr_number) ? lang_line('pod2_delete_item_pr', 'Qty-nya kembali tersedia di PR asal.') : ''); ?>"><i class="fas fa-trash-alt"></i></a>
                            </div>
                            <div class="pp-actions btn-edit-mode" style="display:none">
                                <button type="button" class="btn btn-success btn-save-inline" data-id="<?= (int) $d->id; ?>" title="<?= htmlspecialchars(lang_line('po_save_button_title', 'Simpan')); ?>"><i class="fas fa-check"></i></button>
                                <button type="button" class="btn btn-light btn-cancel-inline" title="<?= htmlspecialchars(lang_line('po_cancel', 'Batal')); ?>"><i class="fas fa-times"></i></button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot id="po-item-details-footer">
                    <tr><td colspan="5" class="text-right"><?= lang_line('po_subtotal', 'Subtotal'); ?><?= ($ppn['applied'] && $ppn['inclusive']) ? ' <small class="text-muted">(DPP)</small>' : ''; ?></td><td class="num" id="subtotal-base"><?= $money($ppn['dpp']); ?></td><?= $trail_cols ? '<td colspan="' . $trail_cols . '"></td>' : ''; ?></tr>
                    <?php if ($ppn['applied']): ?>
                    <tr><td colspan="5" class="text-right"><?= htmlspecialchars(po_ppn_label($ppn['rate'], $ppn['inclusive'])); ?></td><td class="num" id="ppn-base"><?= $money($ppn['ppn']); ?></td><?= $trail_cols ? '<td colspan="' . $trail_cols . '"></td>' : ''; ?></tr>
                    <?php endif; ?>
                    <tr class="pod-grand"><td colspan="5" class="text-right"><?= lang_line('po_grand_total', 'Grand Total'); ?> <small class="text-muted">(<?= htmlspecialchars($cur); ?>)</small></td><td class="num" id="grandtotal-base"><?= $money($ppn['total']); ?></td><?= $trail_cols ? '<td colspan="' . $trail_cols . '"></td>' : ''; ?></tr>
                    <?php if ($cur !== 'IDR'): ?>
                    <tr class="pod-idr"><td colspan="5" class="text-right"><?= lang_line('po_estimated_grand_total_idr', 'Estimasi Grand Total (IDR)'); ?></td><td class="num" id="grandtotal-local"><?= $money($ppn_local['total']); ?></td><?= $trail_cols ? '<td colspan="' . $trail_cols . '"></td>' : ''; ?></tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ================= AKSI KHUSUS TIPE (status Confirmed) ================= -->
    <?php if ($po_type === 'jasa' && $st === 'Confirmed'): ?>
    <div class="pp-card tone-green">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-clipboard-check"></i></span><span><?= lang_line('pod2_service_title', 'Selesaikan Jasa'); ?>
            <span class="d-block pp-sub"><?= lang_line('pod2_service_sub', 'Jasa yang diselesaikan otomatis bisa ditagihkan lewat Payment Request / AP Invoice.'); ?></span></span></h2></div>
        <?php if (empty($service_items)): ?>
            <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-check-circle text-success"></i><?= lang_line('pod2_service_all_done', 'Semua jasa pada PO ini sudah diselesaikan.'); ?></div></div>
        <?php elseif (!check_access($route, 'confirm')): ?>
            <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-lock"></i><?= lang_line('pod2_service_no_access', 'Anda tidak punya izin menyelesaikan jasa pada PO ini.'); ?></div></div>
        <?php else: ?>
            <form method="post" action="<?= base_url($route . '/complete_service/' . $header->id); ?>" id="complete-service-form">
                <div class="table-responsive">
                    <table class="pp-table pod-recv-table">
                        <thead><tr>
                            <th style="width:40px"><input type="checkbox" id="check-all-service" checked></th>
                            <th><?= lang_line('pod2_th_service', 'Jasa'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_ordered', 'Dipesan'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_remaining', 'Sisa'); ?></th>
                            <th class="num" style="width:170px"><?= lang_line('pod2_th_complete_qty', 'Qty Diselesaikan'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($service_items as $si): ?>
                            <tr>
                                <td><input type="checkbox" class="service-check" checked></td>
                                <td><div class="pp-item-name"><?= htmlspecialchars($si->item_name ?? '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($si->item_code ?? '-'); ?></div></td>
                                <td class="num"><?= $qty($si->ordered_qty); ?> <small class="text-muted"><?= htmlspecialchars($si->item_unit ?? ''); ?></small></td>
                                <td class="num font-weight-bold"><?= $qty($si->remaining_qty); ?></td>
                                <td class="num">
                                    <input type="hidden" name="detail_id[]" value="<?= (int) $si->detail_id; ?>">
                                    <input type="hidden" name="item_id[]" value="<?= (int) $si->item_id; ?>">
                                    <input type="hidden" name="unit_price[]" value="<?= (float) $si->unit_price; ?>">
                                    <input type="number" step="0.0001" min="0" max="<?= (float) $si->remaining_qty; ?>" data-max="<?= (float) $si->remaining_qty; ?>"
                                           name="qty[]" class="form-control text-right service-qty-input no-spin ml-auto" style="max-width:140px" value="<?= (float) $si->remaining_qty; ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pp-foot"><span><?= lang_line('pod2_service_foot', 'Hilangkan centang untuk jasa yang belum selesai.'); ?></span>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check mr-1"></i><?= lang_line('pod2_service_submit', 'Selesaikan Jasa Terpilih'); ?></button></div>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($po_type === 'asset' && $st === 'Confirmed'):
        $sisa_total = 0; foreach (($receivable_items ?? []) as $ri) $sisa_total += (float) $ri->remaining_qty; ?>
    <div class="pp-card tone-green">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-dolly"></i></span><span><?= lang_line('pod2_asset_title', 'Terima Aset'); ?>
            <span class="d-block pp-sub"><?= lang_line('pod2_asset_sub', 'Tiap unit dicatat sebagai satu nomor di Asset Register — tanggal perolehan menjadi awal penyusutan.'); ?></span></span></h2></div>
        <?php if ($sisa_total <= 0): ?>
            <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-check-circle text-success"></i><?= lang_line('pod2_asset_all_done', 'Semua unit pada PO ini sudah tercatat di Asset Register.'); ?></div></div>
        <?php elseif (!check_access($route, 'confirm')): ?>
            <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-lock"></i><?= lang_line('pod2_asset_no_access', 'Anda tidak punya izin menerima aset pada PO ini.'); ?></div></div>
        <?php else: ?>
            <form method="post" action="<?= base_url($route . '/receive_asset/' . $header->id); ?>">
                <div class="table-responsive">
                    <table class="pp-table pod-recv-table">
                        <thead><tr>
                            <th><?= lang_line('pod2_th_asset', 'Aset'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_ordered', 'Dipesan'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_received', 'Diterima'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_remaining', 'Sisa'); ?></th>
                            <th class="num"><?= lang_line('pod2_th_receive_qty', 'Qty Terima'); ?></th>
                            <th><?= lang_line('pod2_th_acq_date', 'Tgl Perolehan'); ?></th>
                            <th><?= lang_line('pod2_th_dept', 'Departemen'); ?></th>
                            <th><?= lang_line('pod2_th_location', 'Lokasi'); ?></th>
                            <th><?= lang_line('pod2_th_pic', 'PIC'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($receivable_items as $ri): $sisa = (int) $ri->remaining_qty; ?>
                            <tr class="<?= $sisa <= 0 ? 'is-off' : ''; ?>">
                                <td><div class="pp-item-name"><?= htmlspecialchars($ri->item_name ?? '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($ri->item_code ?? '-'); ?></div></td>
                                <td class="num"><?= (int) $ri->ordered_qty; ?></td>
                                <td class="num"><?= (int) $ri->received_qty; ?></td>
                                <td class="num font-weight-bold"><?= $sisa; ?></td>
                                <td class="num">
                                    <input type="hidden" name="detail_id[]" value="<?= (int) $ri->detail_id; ?>">
                                    <input type="number" step="1" min="0" max="<?= $sisa; ?>" name="qty[]" class="form-control text-right no-spin" style="width:80px" value="<?= $sisa; ?>" <?= $sisa <= 0 ? 'readonly' : ''; ?>>
                                </td>
                                <td><input type="date" name="tanggal_perolehan[]" class="form-control" value="<?= date('Y-m-d'); ?>"></td>
                                <td>
                                    <select name="department[]" class="form-control">
                                        <option value=""><?= lang_line('pod2_dept_from_po', '-- dari PO --'); ?></option>
                                        <?php foreach (($departments ?? []) as $dp): ?>
                                            <option value="<?= htmlspecialchars($dp->code); ?>" <?= (($header->department ?? '') === $dp->code) ? 'selected' : ''; ?>><?= htmlspecialchars($dp->description); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="lokasi[]" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pod2_location_ph', 'Ruang / lantai')); ?>"></td>
                                <td><input type="text" name="pic[]" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pod2_pic_ph', 'Pemegang')); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pp-foot"><span><?= lang_line('pod2_asset_foot', 'Isi qty 0 untuk unit yang belum datang.'); ?></span>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check mr-1"></i><?= lang_line('pod2_asset_submit', 'Catat ke Asset Register'); ?></button></div>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- ================= KIRI: penerimaan ================= -->
        <div class="col-lg-7">
            <?php if ($po_type === 'asset'): ?>
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-tags"></i></span><?= lang_line('pod2_registered', 'Unit di Asset Register'); ?> (<?= count($registered_assets ?? []); ?>)</h2></div>
                <?php if (empty($registered_assets)): ?>
                    <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-info-circle"></i><?= $level >= 2 ? lang_line('pod2_registered_empty', 'Belum ada unit yang diterima.') : lang_line('pod2_recv_after_approve', 'Penerimaan bisa dicatat setelah PO disetujui.'); ?></div></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="pp-table">
                        <thead><tr><th><?= lang_line('pod2_th_asset_no', 'No. Aset'); ?></th><th><?= lang_line('pod2_th_asset', 'Aset'); ?></th><th><?= lang_line('pod2_th_acq_date', 'Tgl Perolehan'); ?></th><th class="num"><?= lang_line('pod2_th_acq_value', 'Nilai Perolehan'); ?></th><th><?= lang_line('pod2_th_location', 'Lokasi'); ?> / PIC</th><th><?= lang_line('pod2_th_journal', 'Jurnal'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($registered_assets as $ra): ?>
                            <tr>
                                <td class="font-weight-bold"><?= htmlspecialchars($ra->no_asset); ?></td>
                                <td><?= htmlspecialchars($ra->nama_asset ?? '-'); ?><div class="pp-item-code"><?= htmlspecialchars($ra->department ?? '-'); ?></div></td>
                                <td><?= $fmt_dt($ra->tanggal_perolehan, false) ?: '-'; ?></td>
                                <td class="num"><?= $money($ra->nilai_perolehan); ?></td>
                                <td><?= htmlspecialchars($ra->lokasi ?: '-'); ?><div class="pp-item-code"><?= htmlspecialchars($ra->pic ?: '-'); ?></div></td>
                                <td><?= !empty($ra->journal_header_id) ? '<span class="pp-chip pp-chip--ok">' . lang_line('pod2_journaled', 'Sudah') . '</span>' : '<span class="pp-chip pp-chip--warn">' . lang_line('pod2_not_journaled', 'Belum') . '</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas <?= $po_type === 'jasa' ? 'fa-clipboard-check' : 'fa-truck-loading'; ?>"></i></span><span><?= $po_type === 'jasa' ? lang_line('pod2_service_history', 'Riwayat Penyelesaian Jasa') : lang_line('pod2_receipts', 'Penerimaan Barang'); ?> (<?= count($recv_docs); ?>)
                    <span class="d-block pp-sub"><?= $po_type === 'jasa' ? lang_line('pod2_service_history_sub', 'Dokumen penyelesaian jasa dari PO ini') : lang_line('pod2_receipts_sub', 'GRN (Warehouse In) & penerimaan barang general dari PO ini'); ?></span></span></h2></div>
                <?php if (!$recv_docs): ?>
                    <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-info-circle"></i>
                        <?php if ($level < 2 && $st !== 'Closed'): ?><?= lang_line('pod2_recv_after_approve', 'Penerimaan bisa dicatat setelah PO disetujui.'); ?>
                        <?php elseif ($po_type === 'jasa'): ?><?= lang_line('pod2_service_none', 'Belum ada jasa yang diselesaikan.'); ?>
                        <?php else: ?><?= lang_line('pod2_receipts_none', 'Belum ada penerimaan. Barang material dicatat di Warehouse In (GRN), barang general di Penerimaan Barang General.'); ?><?php endif; ?>
                    </div></div>
                <?php else: ?>
                <ul class="pod-docs">
                    <?php foreach ($recv_docs as $doc):
                        $is_sgr = $doc->kind === 'sgr';
                        $url = $is_sgr ? base_url('supplier_general_item_receipt/detail/' . $doc->id) : base_url('warehouse_in/detail/' . $doc->id);
                        $kind_lbl = $doc->kind === 'service' ? lang_line('pod2_doc_service', 'Penyelesaian Jasa') : ($is_sgr ? lang_line('pod2_doc_sgr', 'Penerimaan General') : 'GRN · Warehouse In');
                        $done = $doc->status === 'Completed'; ?>
                    <li class="<?= $done ? 'tone-green' : 'tone-amber'; ?>">
                        <span class="pod-doc-ico"><i class="fas <?= $doc->kind === 'service' ? 'fa-clipboard-check' : 'fa-truck'; ?>"></i></span>
                        <span style="min-width:0;flex:1">
                            <a href="<?= $url; ?>" class="pod-doc-title"><?= htmlspecialchars($doc->number); ?></a>
                            <span class="pod-doc-meta d-block"><?= $kind_lbl; ?> · <?= $fmt_dt($doc->date, false) ?: '-'; ?> · <?= sprintf(lang_line('pod2_doc_lines', '%d baris, qty %s'), $doc->lines, $qty($doc->qty)); ?><?= ($doc->kind === 'grn' && !empty($doc->qa_status)) ? ' · QA: ' . htmlspecialchars($doc->qa_status) : ''; ?></span>
                        </span>
                        <span class="pp-status <?= $done ? 'pp-status--completed' : 'pp-status--draft'; ?>"><?= htmlspecialchars($doc->status); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ================= KANAN: info + riwayat ================= -->
        <div class="col-lg-5">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-info"></i></span><?= lang_line('pod2_info', 'Informasi'); ?></h2></div>
                <div class="pp-card-body">
                    <dl class="pod-kv">
                        <dt><?= lang_line('po_supplier', 'Supplier'); ?></dt><dd class="font-weight-bold"><?= htmlspecialchars($header->nama_supplier ?: '-'); ?></dd>
                        <?php if (!empty($header->supplier_contact_person)): ?><dt><?= lang_line('pod2_contact', 'Kontak'); ?></dt><dd><?= htmlspecialchars($header->supplier_contact_person); ?></dd><?php endif; ?>
                        <dt><?= lang_line('po_supplier_phone', 'Telepon'); ?></dt><dd><?= htmlspecialchars($header->telepon ?: '-'); ?></dd>
                        <dt><?= lang_line('po_supplier_address', 'Alamat'); ?></dt><dd><?= nl2br(htmlspecialchars($header->alamat ?: '-')); ?></dd>
                    </dl>
                    <div class="pod-sep"></div>
                    <dl class="pod-kv">
                        <dt><?= lang_line('po_created_by', 'Dibuat oleh'); ?></dt><dd><?= htmlspecialchars($header->creator_name ?: '-'); ?><?= $fmt_dt($header->created_at ?? null) ? '<div class="pp-item-code">' . $fmt_dt($header->created_at) . '</div>' : ''; ?></dd>
                        <dt><?= lang_line('pod2_approved_by', 'Disetujui oleh'); ?></dt><dd><?= htmlspecialchars(($header->confirmer_name ?: ($log_final->actor_name ?? '')) ?: '-'); ?><?= $fmt_dt($header->confirmed_at ?? null) ? '<div class="pp-item-code">' . $fmt_dt($header->confirmed_at) . '</div>' : ''; ?></dd>
                        <?php if (!empty($source_prs)): ?>
                        <dt><?= lang_line('pod2_source_pr', 'Sumber PR'); ?></dt>
                        <dd class="d-flex flex-wrap" style="gap:.3rem">
                            <?php foreach ($source_prs as $spr): ?>
                                <a href="<?= base_url($type_meta['pr'] . '/detail/' . (int) $spr->id); ?>" class="pp-chip" title="<?= htmlspecialchars($spr->purpose ?? ''); ?>"><i class="fas fa-file-alt"></i><?= htmlspecialchars($spr->pr_number); ?><?= isset($spr->line_count) ? ' · ' . (int) $spr->line_count : ''; ?></a>
                            <?php endforeach; ?>
                        </dd>
                        <?php endif; ?>
                    </dl>
                    <?php if (trim((string) $header->remarks) !== ''): ?>
                        <div class="pod-sep"></div>
                        <div class="pp-label"><?= lang_line('po_remarks', 'Catatan'); ?></div>
                        <div class="pod-remarks"><?= htmlspecialchars($header->remarks); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($approval_logs) || !empty($can_reply)):
                $log_meta = [
                    'Submit'          => ['Submitted', 'fa-paper-plane', 'tone-blue'],
                    'Approve HOD'     => ['Approved (Head of Dept)', 'fa-check', 'tone-green'],
                    'Endorse'         => ['Endorsed', 'fa-file-signature', 'tone-blue'],
                    'Approve Final'   => [lang_line('pod2_log_approved', 'Disetujui'), 'fa-check-double', 'tone-green'],
                    'Return to Draft' => [lang_line('pod2_log_returned', 'Dikembalikan ke Draft'), 'fa-undo', 'tone-amber'],
                    'Reject'          => [lang_line('pod2_log_rejected', 'Ditolak'), 'fa-times', 'tone-rose'],
                    'Reply'           => [lang_line('pod2_log_reply', 'Balasan'), 'fa-reply', 'tone-slate'],
                ]; ?>
            <div class="pp-card" id="riwayat-approval">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-history"></i></span><?= lang_line('pod2_history', 'Riwayat Approval'); ?></h2></div>
                <div class="pp-card-body">
                    <?php if (empty($approval_logs)): ?>
                        <div class="pp-item-code mb-2"><?= lang_line('pod2_history_empty', 'Belum ada riwayat.'); ?></div>
                    <?php else: ?>
                    <ul class="pod-tl">
                        <?php foreach ($approval_logs as $log):
                            $m = $log_meta[$log->action] ?? [$log->action, 'fa-circle', 'tone-slate'];
                            $is_reply = $log->action === 'Reply';
                            $who_role = $is_reply ? (((int) ($log->actor_id ?? 0) === (int) $header->created_by) ? 'Requestor' : 'Approver') : ''; ?>
                        <li class="<?= $m[2]; ?><?= $is_reply ? ' pod-tl-reply' : ''; ?>">
                            <span class="pod-tl-dot"><i class="fas <?= $m[1]; ?>"></i></span>
                            <div style="min-width:0;flex:1">
                                <div class="pod-tl-title"><?= htmlspecialchars($m[0]); ?></div>
                                <div class="pod-tl-meta"><?= htmlspecialchars($log->actor_name ?: '-'); ?><?= $who_role ? ' · ' . $who_role : ''; ?><?= $fmt_dt($log->created_at ?? null) ? ' · ' . $fmt_dt($log->created_at) : ''; ?></div>
                                <?php if (!empty($log->notes)): ?><div class="pod-tl-note"><?= nl2br(htmlspecialchars($log->notes)); ?></div><?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if (!empty($can_reply)): ?>
                    <form method="POST" action="<?= base_url($appr_route . '/reply/' . $header->id); ?>" class="pt-3" style="border-top:1px solid var(--pp-line)">
                        <label class="pp-label"><?= lang_line('pod2_reply', 'Balas'); ?></label>
                        <textarea name="message" class="form-control mb-2" rows="2" placeholder="<?= htmlspecialchars(lang_line('pod2_reply_ph', 'Tulis balasan / klarifikasi...')); ?>" required></textarea>
                        <button type="submit" class="btn btn-pp btn-sm"><i class="fas fa-paper-plane mr-1"></i><?= lang_line('pod2_reply_send', 'Kirim Balasan'); ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($can_edit_header)):
    $cur_list = $currencies ?? [];
    $has_cur = false; foreach ($cur_list as $c) { if ($c->code === $cur) $has_cur = true; }
?>
<!-- ================= MODAL EDIT HEADER ================= -->
<div class="modal fade pp-modal pod2-modal" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url($route . '/update_header/' . $header->id); ?>" class="needs-validation w-100" novalidate>
            <div class="modal-content pp2">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-pen mr-2"></i><?= lang_line('po_edit', 'Edit Header'); ?> · <?= htmlspecialchars($header->po_number); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <?php if ($admin_mode): ?>
                        <input type="hidden" name="admin_edit" value="1">
                        <div class="pp-flash pp-flash--warning"><i class="fas fa-user-shield"></i><div><?= sprintf(lang_line('pod2_admin_modal', 'Edit Admin pada PO berstatus %s — approval tidak direset.'), '<b>' . htmlspecialchars($st) . '</b>'); ?></div></div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('po_supplier', 'Supplier'); ?></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier); ?>" readonly>
                            <input type="hidden" name="supplier_id" value="<?= (int) $header->supplier_id; ?>">
                            <small class="pp-hint"><?= lang_line('po_supplier_note', 'Supplier sudah dipilih saat pembuatan PO'); ?></small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="pp-label" for="edit_po_date"><?= lang_line('po_date', 'Tanggal PO'); ?> <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_po_date" name="po_date" value="<?= date('Y-m-d', strtotime($header->po_date)); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label" for="edit_shipping_mode"><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></label>
                            <select name="shipping_mode_code" id="edit_shipping_mode" class="form-control select2-modal" style="width:100%">
                                <option value=""><?= lang_line('po_shipping_mode_select', '-- Pilih Mode --'); ?></option>
                                <?php foreach (($shipping_modes ?? []) as $mode): ?><option value="<?= htmlspecialchars($mode->code); ?>" <?= ($header->shipping_mode_code ?? '') == $mode->code ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="pp-label" for="edit_payment_mode"><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></label>
                            <select name="payment_mode_code" id="edit_payment_mode" class="form-control select2-modal" style="width:100%">
                                <option value=""><?= lang_line('po_payment_mode_select', '-- Pilih Mode --'); ?></option>
                                <?php foreach (($payment_modes ?? []) as $mode): ?><option value="<?= htmlspecialchars($mode->code); ?>" <?= ($header->payment_mode_code ?? '') == $mode->code ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label" for="edit_currency_code"><?= lang_line('po_currency', 'Mata Uang'); ?> <span class="text-danger">*</span></label>
                            <select class="form-control" id="edit_currency_code" name="currency_code" required>
                                <?php if (!$has_cur): ?><option value="<?= htmlspecialchars($cur); ?>" selected><?= htmlspecialchars($cur); ?></option><?php endif; ?>
                                <?php foreach ($cur_list as $c): ?><option value="<?= htmlspecialchars($c->code); ?>" <?= $c->code === $cur ? 'selected' : ''; ?>><?= htmlspecialchars($c->description); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" id="edit_exchange_rate_div" style="<?= $cur === 'IDR' ? 'display:none' : ''; ?>">
                            <label class="pp-label" for="edit_exchange_rate"><?= lang_line('po_exchange_rate', 'Kurs'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-right autonumeric-4dec-edit" id="edit_exchange_rate" name="exchange_rate" value="<?= number_format($fx, 4, '.', ''); ?>" <?= $cur !== 'IDR' ? 'required' : ''; ?>>
                            <small class="pp-hint" id="edit_kurs_info"><?= lang_line('po_exchange_rate_help', 'Contoh: 1 USD = 15000.0000 IDR'); ?></small>
                        </div>
                    </div>
                    <div class="pod-ppn mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input ppn-toggle" id="edit_has_ppn" name="has_ppn" value="1" <?= !empty($header->has_ppn) ? 'checked' : ''; ?>>
                            <label class="custom-control-label font-weight-bold" for="edit_has_ppn"><?= lang_line('po_has_ppn_check', 'Kena PPN'); ?></label>
                        </div>
                        <div class="form-row ppn-detail mt-2" style="<?= !empty($header->has_ppn) ? '' : 'display:none'; ?>">
                            <div class="col-md-8 form-group mb-0">
                                <label class="pp-label"><?= lang_line('po_ppn_mode', 'Perhitungan PPN'); ?></label>
                                <select name="ppn_inclusive" class="form-control">
                                    <option value="0" <?= empty($header->ppn_inclusive) ? 'selected' : ''; ?>><?= lang_line('po_ppn_exclude', 'Exclude — harga belum termasuk PPN (PPN ditambahkan)'); ?></option>
                                    <option value="1" <?= !empty($header->ppn_inclusive) ? 'selected' : ''; ?>><?= lang_line('po_ppn_include', 'Include — harga sudah termasuk PPN (PPN diambil dari total)'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group mb-0">
                                <label class="pp-label"><?= lang_line('po_ppn_rate', 'Persen PPN'); ?></label>
                                <div class="input-group">
                                    <input type="number" name="ppn_rate" class="form-control text-right" step="0.01" min="0" max="100" value="<?= htmlspecialchars((string) (float) ($header->ppn_rate ?: 11)); ?>">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="pp-label" for="edit_remarks"><?= lang_line('po_remarks', 'Catatan / Remarks'); ?></label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3" placeholder="<?= htmlspecialchars(lang_line('po_optional', 'Opsional...')); ?>"><?= htmlspecialchars($header->remarks ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-pp"><i class="fas fa-save mr-1"></i><?= lang_line('po_save', 'Simpan Perubahan'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($can_edit_item && $allow_manual): ?>
<template id="new-item-row-template">
    <tr class="new-item-row">
        <td class="pod-no">*</td>
        <td>
            <select name="item_id" class="form-control select2-inline-item" style="width:100%"><option value=""></option></select>
            <div class="pod-meta"><span class="pp-item-code item-code-display"></span></div>
            <input type="text" name="description" class="form-control mt-1" placeholder="<?= htmlspecialchars(lang_line('pod2_desc_ph', 'Keterangan / spesifikasi (opsional)')); ?>">
            <?php if ($show_sc && !empty($open_contracts)): ?>
            <select name="sales_contract_id" class="form-control mt-1">
                <option value=""><?= lang_line('pod2_sc_optional', 'Sales Order (opsional)'); ?></option>
                <?php foreach ($open_contracts as $sc): ?><option value="<?= (int) $sc->id; ?>"><?= htmlspecialchars($sc->sc_number); ?></option><?php endforeach; ?>
            </select>
            <?php endif; ?>
        </td>
        <td class="num"><input type="number" step="0.0001" min="0.0001" name="qty" class="form-control text-right inline-qty no-spin" placeholder="0" style="width:100px;margin-left:auto"></td>
        <td><span class="pod-unit item-unit-display">-</span></td>
        <td class="num"><input type="number" step="0.0001" min="0" name="base_price" class="form-control text-right inline-price no-spin" value="0" style="width:130px;margin-left:auto"></td>
        <td class="num inline-total font-weight-bold">0.00</td>
        <?php if ($show_recv_col): ?><td></td><?php endif; ?>
        <td class="text-right">
            <div class="pp-actions">
                <button type="button" class="btn btn-success save-inline-item" title="<?= htmlspecialchars(lang_line('po_save_item', 'Simpan Item')); ?>"><i class="fas fa-check"></i></button>
                <button type="button" class="btn btn-light cancel-inline-item" title="<?= htmlspecialchars(lang_line('po_cancel', 'Batal')); ?>"><i class="fas fa-times"></i></button>
            </div>
        </td>
    </tr>
</template>
<?php endif; ?>

<?php $this->load->view('templates/footer'); ?>

<?php
// Modal "Tarik Item dari PR" -- markup + JS-nya dipakai bersama keempat tipe PO.
if ($can_edit_item) {
    $this->load->view('templates/partials/pull_pr_modal', ['header' => $header, 'pull_pr_route' => $route]);
}
?>

<script>
$(function () {
    var ROUTE_URL   = <?= json_encode(base_url($route)); ?>;
    var PO_ID       = <?= (int) $header->id; ?>;
    var ADMIN_FLAG  = <?= $admin_mode ? '1' : '0'; ?>;
    var PO_PPN = {
        has: <?= !empty($header->has_ppn) ? 'true' : 'false'; ?>,
        rate: <?= (float) ($header->ppn_rate ?? 0); ?>,
        inclusive: <?= !empty($header->ppn_inclusive) ? 'true' : 'false'; ?>,
        fx: <?= $fx; ?>
    };
    var T = {
        failed: <?= json_encode(lang_line('po_failed', 'Gagal!')); ?>,
        conn: <?= json_encode(lang_line('po_connection_error', 'Gagal menghubungi server. Periksa koneksi Anda.')); ?>,
        qtyZero: <?= json_encode(lang_line('po_qty_zero_error', 'Qty harus lebih dari 0')); ?>,
        priceNeg: <?= json_encode(lang_line('po_price_negative_error', 'Harga tidak boleh negatif')); ?>,
        saved: <?= json_encode(lang_line('po_item_save_success', 'Item berhasil disimpan.')); ?>,
        selectItem: <?= json_encode(lang_line('po_alert_select_item', 'Harap pilih Item.')); ?>,
        finishPrev: <?= json_encode(lang_line('po_alert_finish_previous', 'Selesaikan atau batalkan penambahan item sebelumnya.')); ?>,
        searchItem: <?= json_encode(lang_line('po_search_item', '-- Cari Nama Item --')); ?>,
        selectSc: <?= json_encode(lang_line('po_load_bom_select_first', 'Pilih Sales Order terlebih dahulu.')); ?>,
        loading: <?= json_encode(lang_line('po_load_bom_loading', 'Memuat...')); ?>,
        loadBom: <?= json_encode(lang_line('po_load_bom_button', 'Load BOM')); ?>,
        kursLoading: <?= json_encode(lang_line('pod2_kurs_loading', 'Mengambil kurs pajak terbaru...')); ?>,
        kursFail: <?= json_encode(lang_line('pod2_kurs_fail', 'Kurs tidak tersedia, isi manual.')); ?>,
        kursOk: <?= json_encode(lang_line('pod2_kurs_ok', 'Otomatis dari Kurs Pajak')); ?>,
        saving: <?= json_encode(lang_line('pod2_saving', 'Menyimpan...')); ?>,
        cancelPoTitle: <?= json_encode(lang_line('pod2_force_title', 'Batalkan PO %s?')); ?>,
        cancelPoText: <?= json_encode(lang_line('pod2_force_text', 'Pilih tindakan yang ingin dilakukan:')); ?>,
        toDraft: <?= json_encode(lang_line('pod2_force_to_draft', 'Kembalikan ke Draft')); ?>,
        delData: <?= json_encode(lang_line('pod2_force_delete', 'Hapus Data')); ?>,
        cancel: <?= json_encode(lang_line('po_cancel', 'Batal')); ?>,
        delTitle: <?= json_encode(lang_line('pod2_force_delete_title', 'Hapus Permanen?')); ?>,
        delHtml: <?= json_encode(lang_line('pod2_force_delete_html', 'PO <b>%s</b> beserta semua itemnya akan dihapus.<br><span class="text-danger">Aksi ini tidak dapat dibatalkan!</span>')); ?>,
        delYes: <?= json_encode(lang_line('pod2_force_delete_yes', 'Ya, Hapus Permanen')); ?>
    };

    function fmt(n, dec) { return (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
    function fmtQty(n) { return (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 4 }); }
    function fmtPrice(n) { return (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }); }
    function fail(msg) {
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: T.failed, html: msg || T.conn });
        else alert($('<div>').html(msg || T.conn).text());
    }

    // ---------- ringkasan nilai (cermin po_ppn_breakdown() di ppn_helper) ----------
    function recalcTotals() {
        var subtotal = 0;
        $('#po-item-details-body tr[data-detail-id]').each(function () { subtotal += parseFloat($(this).attr('data-total')) || 0; });
        var dpp = subtotal, ppn = 0, total = subtotal;
        if (PO_PPN.has && PO_PPN.rate > 0) {
            if (PO_PPN.inclusive) { ppn = subtotal * PO_PPN.rate / (100 + PO_PPN.rate); dpp = subtotal - ppn; }
            else { ppn = subtotal * PO_PPN.rate / 100; total = subtotal + ppn; }
        }
        $('#subtotal-base').text(fmt(dpp, 2));
        $('#ppn-base').text(fmt(ppn, 2));
        $('#grandtotal-base, #grandtotal-hero').text(fmt(total, 2));
        $('#grandtotal-local').text(fmt(total * PO_PPN.fx, 2));
    }

    // ---------- edit di tempat (qty, harga, keterangan) ----------
    var $body = $('#po-item-details-body');
    function setEditing($row, on) {
        $row.toggleClass('editing', on);
        $row.find('.edit-input').toggle(on);
        $row.find('.cell-qty .display-val, .cell-price .display-val').toggle(!on);
        var $desc = $row.find('.cell-desc .display-val');
        $desc.toggle(!on && $.trim($desc.text()) !== '');
        $row.find('.btn-view-mode').toggle(!on);
        $row.find('.btn-edit-mode').toggle(on);
    }
    $body.on('click', '.btn-edit-inline', function () {
        var $row = $(this).closest('tr');
        setEditing($row, true);
        $row.find('.cell-price .edit-input').trigger('focus').trigger('select');
    });
    $body.on('click', '.btn-cancel-inline', function () {
        var $row = $(this).closest('tr');
        $row.find('.edit-input').each(function () { this.value = this.defaultValue; });
        setEditing($row, false);
    });
    $body.on('keydown', 'tr.editing .edit-input', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $(this).closest('tr').find('.btn-save-inline').trigger('click'); }
        if (e.key === 'Escape') { $(this).closest('tr').find('.btn-cancel-inline').trigger('click'); }
    });
    $body.on('click', '.btn-save-inline', function () {
        var $btn = $(this), $row = $btn.closest('tr');
        var qty = parseFloat($row.find('.cell-qty .edit-input').val());
        var price = parseFloat($row.find('.cell-price .edit-input').val());
        var desc = $row.find('.cell-desc .edit-input').val() || '';
        if (!(qty > 0)) { fail(T.qtyZero); return; }
        if (isNaN(price) || price < 0) { fail(T.priceNeg); return; }
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post(ROUTE_URL + '/ajax_update_item/' + $btn.data('id'), { qty: qty, base_price: price, description: desc, admin_edit: ADMIN_FLAG }, null, 'json')
            .done(function (res) {
                if (!res || !res.success) { fail(res && res.message); return; }
                var total = qty * price;
                $row.attr('data-total', total).toggleClass('price-zero-row', price === 0);
                $row.find('.cell-qty .display-val').text(fmtQty(qty));
                $row.find('.cell-price').toggleClass('price-zero', price === 0).find('.display-val').text(fmtPrice(price));
                $row.find('.cell-total').text(fmt(total, 2));
                $row.find('.cell-desc .display-val').text(desc);
                $row.find('.edit-input').each(function () { this.defaultValue = this.value; });
                setEditing($row, false);
                recalcTotals();
                if (typeof toastr !== 'undefined') toastr.success(T.saved);
            })
            .fail(function () { fail(); })
            .always(function () { $btn.prop('disabled', false).html('<i class="fas fa-check"></i>'); });
    });

    // ---------- tambah item manual (PO Produksi) ----------
    var tpl = document.getElementById('new-item-row-template');
    $('#add-item-row-btn').on('click', function () {
        if (!tpl) return;
        if ($body.find('.new-item-row').length) { fail(T.finishPrev); return; }
        $('#no-item-row').hide();
        $body.append(tpl.content.cloneNode(true));
        var $row = $body.find('.new-item-row').last();
        $row.find('.select2-inline-item').select2({
            placeholder: T.searchItem, minimumInputLength: 2, width: '100%', dropdownParent: $(document.body),
            ajax: { url: ROUTE_URL + '/search_items', dataType: 'json', delay: 350,
                    data: function (p) { return { q: p.term }; },
                    processResults: function (d) { return { results: (d && d.results) || [] }; } }
        }).on('select2:select', function (e) {
            var d = e.params.data;
            $row.find('.item-code-display').text(d.code || '');
            $row.find('.item-unit-display').text(d.unit || '-');
            $row.find('.inline-qty').trigger('focus');
        }).select2('open');
    });
    $body.on('input', '.new-item-row .inline-qty, .new-item-row .inline-price', function () {
        var $r = $(this).closest('tr');
        $r.find('.inline-total').text(fmt((parseFloat($r.find('.inline-qty').val()) || 0) * (parseFloat($r.find('.inline-price').val()) || 0), 2));
    });
    $body.on('click', '.cancel-inline-item', function () {
        $(this).closest('tr').remove();
        if (!$body.find('tr[data-detail-id]').length) $('#no-item-row').show();
    });
    $body.on('click', '.save-inline-item', function () {
        var $btn = $(this), $row = $btn.closest('tr');
        var itemId = $row.find('select[name="item_id"]').val();
        var qty = parseFloat($row.find('.inline-qty').val());
        var price = parseFloat($row.find('.inline-price').val());
        if (!itemId) { fail(T.selectItem); return; }
        if (!(qty > 0)) { fail(T.qtyZero); return; }
        if (isNaN(price) || price < 0) { fail(T.priceNeg); return; }
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.post(ROUTE_URL + '/ajax_add_item/' + PO_ID, {
            item_id: itemId, qty: qty, base_price: price,
            sales_contract_id: $row.find('select[name="sales_contract_id"]').val() || '',
            description: $row.find('input[name="description"]').val() || '', admin_edit: ADMIN_FLAG
        }, null, 'json').done(function (res) {
            if (res && res.status === 'success') { window.location.reload(); return; }
            fail(res && res.message);
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
        }).fail(function () { fail(); $btn.prop('disabled', false).html('<i class="fas fa-check"></i>'); });
    });

    // ---------- muat dari BOM Sales Order ----------
    $('#pod-bom-toggle').on('click', function () { $('#pod-bom-panel').toggleClass('is-open'); });
    $('#btn-load-bom').on('click', function () {
        var scId = $('#bom-sc-select').val();
        if (!scId) { fail(T.selectSc); return; }
        var $btn = $(this), $res = $('#bom-load-result');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + T.loading);
        $res.removeClass('text-success text-danger').empty();
        $.post(ROUTE_URL + '/load_bom_from_sc/' + PO_ID, { sc_id: scId, admin_edit: ADMIN_FLAG }, null, 'json')
            .done(function (res) {
                if (res && res.success) { $res.addClass('text-success').text(res.message); setTimeout(function () { window.location.reload(); }, 1000); return; }
                $res.addClass('text-danger').text((res && res.message) || T.conn);
                $btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i>' + T.loadBom);
            })
            .fail(function () { $res.addClass('text-danger').text(T.conn); $btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i>' + T.loadBom); });
    });

    // ---------- Force Return / Delete (Admin) ----------
    $('#btn-batalkan-po').on('click', function () {
        var $b = $(this), isDraft = $b.data('status') === 'Draft', no = String($b.data('po-number'));
        Swal.fire({
            title: T.cancelPoTitle.replace('%s', no), text: T.cancelPoText, icon: 'warning',
            showConfirmButton: !isDraft, showDenyButton: true, showCancelButton: true, reverseButtons: true,
            confirmButtonText: '<i class="fas fa-undo mr-1"></i>' + T.toDraft, denyButtonText: '<i class="fas fa-trash mr-1"></i>' + T.delData, cancelButtonText: T.cancel,
            confirmButtonColor: '#f59e0b', denyButtonColor: '#e11d48', cancelButtonColor: '#94a3b8'
        }).then(function (r) {
            if (r.isConfirmed) { window.location.href = $b.data('return-url'); return; }
            if (!r.isDenied) return;
            Swal.fire({ title: T.delTitle, html: T.delHtml.replace('%s', $('<div>').text(no).html()), icon: 'error', showCancelButton: true,
                        confirmButtonText: T.delYes, cancelButtonText: T.cancel, confirmButtonColor: '#e11d48', cancelButtonColor: '#94a3b8' })
                .then(function (r2) { if (r2.isConfirmed) window.location.href = $b.data('delete-url'); });
        });
    });

    // ---------- Selesaikan Jasa: centang = qty sisa, tanpa centang = 0 (indeks array tetap selaras) ----------
    $('#check-all-service').on('change', function () { $('.service-check').prop('checked', this.checked).trigger('change'); });
    $(document).on('change', '.service-check', function () {
        var $i = $(this).closest('tr').find('.service-qty-input');
        $(this).closest('tr').toggleClass('is-off', !this.checked);
        $i.prop('readonly', !this.checked).val(this.checked ? $i.data('max') : 0);
    });

    // ---------- modal edit header ----------
    var $modal = $('#editHeaderModalDetail');
    if ($modal.length) {
        $modal.find('.select2-modal').each(function () {
            $(this).select2({ dropdownParent: $modal, width: '100%' });
        });
        var $rate = $('#edit_exchange_rate'), anRate = null;
        if ($rate.length && typeof AutoNumeric === 'function') anRate = new AutoNumeric($rate[0], { decimalPlaces: 4, modifyValueOnWheel: false, unformatOnSubmit: true });
        var setRate = function (v) { if (anRate) anRate.set(v); else $rate.val(v); };
        $('#edit_currency_code').on('change', function () {
            var c = $(this).val(), $info = $('#edit_kurs_info');
            if (c === 'IDR') { $('#edit_exchange_rate_div').slideUp(150); $rate.prop('required', false); setRate(1); return; }
            $('#edit_exchange_rate_div').slideDown(150); $rate.prop('required', true);
            $info.html('<i class="fas fa-spinner fa-spin mr-1"></i>' + T.kursLoading);
            $.getJSON(<?= json_encode(base_url('kurs_pajak/ajax_get_rate')); ?>, { currency: c })
                .done(function (res) {
                    if (res && res.ok && res.rate) { setRate(res.rate); $info.html('<i class="fas fa-check-circle text-success mr-1"></i>' + T.kursOk + ' (' + res.effective_date_start + ' – ' + res.effective_date_end + ')'); }
                    else $info.html('<i class="fas fa-exclamation-triangle text-warning mr-1"></i>' + ((res && res.message) || T.kursFail));
                })
                .fail(function () { $info.html('<i class="fas fa-exclamation-triangle text-warning mr-1"></i>' + T.kursFail); });
        });
        $modal.on('change', '.ppn-toggle', function () { $modal.find('.ppn-detail').toggle(this.checked); });
        $modal.find('form.needs-validation').on('submit', function (e) {
            if (!this.checkValidity()) { e.preventDefault(); e.stopPropagation(); $(this).addClass('was-validated'); }
        });
    }
});
</script>
