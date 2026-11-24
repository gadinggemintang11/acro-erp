<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Approval PO -- detail. SATU view untuk keempat tipe (Material/General/Jasa/
 * Asset). Redesign 2026-11-09 (user: "di approval po juga perbarui
 * tampilannya ya"), gaya sama dengan purchase_order/detail.php.
 *
 * Dulu 4 salinan dengan dua bug: PPN dihitung 11% tetap (bukan ppn_rate/
 * include), dan modal Reject & Return to Draft ikut terbungkus kondisi mode
 * edit Admin -- approver biasa menekan tombolnya tapi modalnya tidak pernah
 * dirender.
 *
 * Hak tiap tombol dihitung di Purchase_order_approval::detail() (can_*).
 * Approve = GET approve_final (konfirmasi via handler global .btn-confirm di
 * templates/footer.php); Reject/Return = form POST dengan alasan wajib.
 */
$this->load->view('purchase_order/_po_style');

$po_type    = $po_type ?? 'produksi';
$appr       = $approval_route ?? 'purchase_order_approval';
$main       = $main_route ?? 'purchase_order';
$st         = $header->status;
$cur        = $header->currency_code ?: 'IDR';
$fx         = (float) $header->exchange_rate ?: 1;
$details    = $details ?? [];
$type_label = $po_type_label ?? 'PO';
$show_item_action = !empty($can_edit_po_item_inline);

$type_meta = [
    'produksi'     => [lang_line('pod2_type_produksi', 'Material Produksi'), 'fa-boxes',         'purchase_request'],
    'non_produksi' => [lang_line('pod2_type_general', 'General'),            'fa-toolbox',       'purchase_request_general'],
    'jasa'         => [lang_line('pod2_type_jasa', 'Jasa'),                  'fa-hands-helping', 'purchase_request_jasa'],
    'asset'        => [lang_line('pod2_type_asset', 'Asset'),                'fa-building',      'purchase_request_asset'],
][$po_type] ?? ['PO', 'fa-file-invoice', 'purchase_request'];
// Approval General menampilkan gabungan General/Jasa/Asset -> kembali ke sana.
$back_route = $po_type === 'produksi' ? 'purchase_order_approval'
            : (check_access('purchase_order_approval_general', 'view') ? 'purchase_order_approval_general' : $appr);

$fmt_dt = function ($v, $time = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$money  = function ($n) { return number_format((float) $n, 2, '.', ','); };
$price  = function ($n) { return preg_replace('/(\.\d\d\d*?)0+$/', '$1', number_format((float) $n, 4, '.', ',')); };
$qty    = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$who    = function ($id) use ($audit) { return ($id && !empty($audit[$id])) ? $audit[$id] : null; };

$last_log = function ($action) use ($approval_logs) { $f = null; foreach (($approval_logs ?? []) as $l) { if ($l->action === $action) $f = $l; } return $f; };
$log_submit = $last_log('Submit');
$log_final  = $last_log('Approve Final');

// ---- nilai ----
$subtotal = 0.0; $n_zero = 0; $n_over_pr = 0;
foreach ($details as $d) {
    $subtotal += (float) $d->qty * (float) $d->base_price;
    if ((float) $d->base_price == 0) $n_zero++;
    if (isset($d->pr_qty) && $d->pr_qty !== null && (float) $d->qty > (float) $d->pr_qty + 0.0001) $n_over_pr++;
}
$ppn     = po_ppn_from_header($header, $subtotal);
$n_items = count($details);

// ---- alur: Dibuat -> Diajukan -> Keputusan ----
$pending   = in_array($st, ['Submitted', 'Approved Head Dept', 'Endorsed'], true);
$approved  = in_array($st, ['Confirmed', 'Closed'], true) || ($st === 'Cancelled' && !empty($header->confirmed_at));
$final_by  = $who($header->final_approved_by ?? null) ?: ($header->confirmer_name ?: ($log_final->actor_name ?? null));
$final_at  = $fmt_dt(($header->final_approved_at ?? null) ?: ($header->confirmed_at ?? null));
$steps = [
    ['cls' => 'is-done', 'icon' => 'fa-check', 'label' => lang_line('pod2_step_created', 'Dibuat'), 'meta' => trim(($header->creator_name ?: '-') . ($fmt_dt($header->created_at ?? null) ? ' · ' . $fmt_dt($header->created_at) : ''))],
    ['cls' => $st === 'Draft' ? 'is-current' : 'is-done', 'icon' => $st === 'Draft' ? 'fa-paper-plane' : 'fa-check', 'label' => lang_line('pod2_step_submitted', 'Diajukan'),
     'meta' => $st === 'Draft' ? lang_line('pod2_wait_submit', 'Menunggu submit') : trim(($log_submit->actor_name ?? ($who($header->submitted_by ?? null) ?: ($header->creator_name ?: '-'))) . (($log_submit && $fmt_dt($log_submit->created_at)) ? ' · ' . $fmt_dt($log_submit->created_at) : ''))],
];
if ($st === 'Rejected') {
    $steps[] = ['cls' => 'is-rejected', 'icon' => 'fa-times', 'label' => lang_line('pod2_step_rejected', 'Ditolak'), 'meta' => trim(($who($header->rejected_by ?? null) ?: '-') . ($fmt_dt($header->rejected_at ?? null) ? ' · ' . $fmt_dt($header->rejected_at) : ''))];
} elseif ($approved) {
    $steps[] = ['cls' => 'is-done', 'icon' => 'fa-check', 'label' => lang_line('pod2_step_approved', 'Disetujui'), 'meta' => trim(($final_by ?: '-') . ($final_at ? ' · ' . $final_at : ''))];
} else {
    $steps[] = ['cls' => $pending ? 'is-current' : '', 'icon' => 'fa-gavel', 'label' => lang_line('poa2_step_decision', 'Keputusan Approval'), 'meta' => $pending ? lang_line('pod2_wait_approval', 'Menunggu approval') : '—'];
}

$status_cls = [
    'Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress',
    'Endorsed' => 'pp-status--progress', 'Confirmed' => 'pp-status--completed', 'Closed' => 'pp-status--other',
    'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad',
][$st] ?? 'pp-status--other';
$can_decide = !empty($can_approve_final) || !empty($can_return_reject);
$trail_cols = $show_item_action ? 1 : 0;

$approve_text = lang_line('poa2_approve_text', 'PO menjadi Confirmed dan bisa diproses di gudang / penerimaan.');
if ($n_zero) $approve_text .= ' ' . sprintf(lang_line('pod2_submit_zero_warn', 'Perhatian: %d item harga satuannya masih 0.'), $n_zero);
?>
<style>
.poa2 .poa-decide { position: sticky; top: 1rem; }
.poa2 .poa-total { font-size: 1.6rem; font-weight: 800; line-height: 1.1; }
.poa2 .poa-total small { font-size: .8rem; font-weight: 600; color: var(--pp-muted); }
.poa2 .poa-checks { list-style: none; padding: 0; margin: .9rem 0 1rem; font-size: .82rem; }
.poa2 .poa-checks li { display: flex; gap: .55rem; align-items: flex-start; padding: .3rem 0; }
.poa2 .poa-checks li i { margin-top: .2rem; width: 1rem; text-align: center; }
.poa2 .poa-btns .btn { display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; padding: .6rem .9rem; margin-bottom: .5rem; }
.poa2 .poa-state { display: flex; gap: .8rem; align-items: flex-start; }
.poa2 .poa-state-ico { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; flex: 0 0 auto; color: var(--t); background: var(--t-soft); font-size: 1rem; }
.poa2 .poa-state b { display: block; font-size: .92rem; }
.poa2 .poa-state span { font-size: .8rem; color: var(--pp-muted); }
@media (max-width: 1199.98px) { .poa2 .poa-decide { position: static; } }
</style>

<div class="container-fluid pp2 pod2 poa2">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-clipboard-check"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker"><?= lang_line('poa2_kicker', 'Approval Purchase Order'); ?> · <?= htmlspecialchars($type_meta[0]); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($header->po_number); ?> <span class="pp-status <?= $status_cls; ?>"><?= htmlspecialchars($st); ?></span></h1>
                    <div class="pp-hero-subline">
                        <i class="far fa-calendar mr-1"></i><?= $fmt_dt($header->po_date, false); ?>
                        <span class="mx-1">·</span><i class="fas fa-store mr-1"></i><?= htmlspecialchars($header->nama_supplier ?: '-'); ?>
                    </div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if (!empty($can_edit_po_draft)): ?>
                    <a href="<?= base_url($po_edit_route); ?>" class="btn pp-btn-glass"><i class="fas fa-pen mr-1"></i><?= lang_line('poa2_edit_po', 'Edit PO'); ?></a>
                <?php elseif (!empty($can_unlock_po_admin)): ?>
                    <a href="<?= base_url($po_edit_route); ?>?admin_edit=1" class="btn pp-btn-glass btn-confirm no-loader"
                       data-message="<?= htmlspecialchars(lang_line('pod2_unlock_q', 'Buka kunci edit PO ini?')); ?>"
                       data-text="<?= htmlspecialchars(sprintf(lang_line('pod2_unlock_text', 'PO sudah %s. Membuka kunci memungkinkan Anda mengubahnya sebagai Admin. Approval TIDAK direset.'), $st)); ?>"
                       data-icon="warning" data-confirm-text="<?= htmlspecialchars(lang_line('pod2_yes_unlock', 'Ya, Buka Kunci')); ?>"><i class="fas fa-lock mr-1"></i><?= lang_line('pod2_unlock', 'Buka Kunci (Admin)'); ?></a>
                <?php elseif (check_access($main, 'view')): ?>
                    <a href="<?= base_url($main . '/detail/' . $header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-external-link-alt mr-1"></i><?= lang_line('poa2_open_po', 'Buka PO'); ?></a>
                <?php endif; ?>
                <a href="<?= base_url($appr . '/export_detail_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="<?= base_url($appr . '/export_detail_excel/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-file-excel mr-1"></i>Excel</a>
                <a href="<?= base_url($back_route); ?>" class="btn pp-btn-glass btn-load"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('pod2_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_supplier', 'Supplier'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->nama_supplier ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_created_by', 'Dibuat Oleh'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->creator_name ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_currency', 'Mata Uang'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($cur); ?><?php if ($cur !== 'IDR'): ?> <small>@ <?= $price($fx); ?></small><?php endif; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->payment_mode_desc ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pod2_tax', 'Pajak'); ?></div><div class="pp-meta-value"><?= $ppn['applied'] ? htmlspecialchars(po_ppn_label($ppn['rate'], false)) . ($ppn['inclusive'] ? ' <small>' . lang_line('pod2_ppn_incl', 'termasuk') . '</small>' : '') : lang_line('pod2_no_ppn', 'Tanpa PPN'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_grand_total', 'Grand Total'); ?></div><div class="pp-meta-value is-total"><small><?= htmlspecialchars($cur); ?></small> <span class="js-grand"><?= $money($ppn['total']); ?></span></div></div>
        </div>
    </section>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if ($st === 'Rejected' && !empty($header->reject_reason)): ?>
        <div class="pod-note pod-note--bad"><i class="fas fa-ban"></i><div><b><?= lang_line('pod2_rejected', 'PO ditolak'); ?></b><?= $who($header->rejected_by ?? null) ? ' ' . lang_line('pod2_by', 'oleh') . ' ' . htmlspecialchars($who($header->rejected_by)) : ''; ?><?= $fmt_dt($header->rejected_at ?? null) ? ' · ' . $fmt_dt($header->rejected_at) : ''; ?><br><?= nl2br(htmlspecialchars($header->reject_reason)); ?></div></div>
    <?php elseif ($st === 'Draft' && !empty($header->return_reason)): ?>
        <div class="pod-note pod-note--warn"><i class="fas fa-undo"></i><div><b><?= lang_line('pod2_returned', 'Dikembalikan ke Draft'); ?></b><?= $who($header->returned_to_draft_by ?? null) ? ' ' . lang_line('pod2_by', 'oleh') . ' ' . htmlspecialchars($who($header->returned_to_draft_by)) : ''; ?><?= $fmt_dt($header->returned_to_draft_at ?? null) ? ' · ' . $fmt_dt($header->returned_to_draft_at) : ''; ?><br><?= nl2br(htmlspecialchars($header->return_reason)); ?></div></div>
    <?php endif; ?>
    <?php if (!empty($header->rejection_notes)): ?>
        <div class="pod-note pod-note--warn"><i class="fas fa-exclamation-triangle"></i><div><b><?= lang_line('poa_catatan_penolakan', 'Catatan Penolakan'); ?></b><br><?= nl2br(htmlspecialchars($header->rejection_notes)); ?></div></div>
    <?php endif; ?>
    <?php if ($show_item_action): ?>
        <div class="pod-note pod-note--admin"><i class="fas fa-user-shield"></i><div><b><?= lang_line('pod2_admin_mode', 'Mode Edit Admin'); ?></b> — <?= lang_line('poa2_admin_items', 'Item bisa diubah / dihapus dari halaman ini. Approval tidak direset.'); ?></div></div>
    <?php endif; ?>

    <nav class="pod-steps" style="grid-template-columns: repeat(3, minmax(0, 1fr));" aria-label="<?= htmlspecialchars(lang_line('pod2_flow', 'Alur Purchase Order')); ?>">
        <?php foreach ($steps as $s): ?>
        <div class="pod-step <?= $s['cls']; ?>">
            <span class="pod-step-dot"><i class="fas <?= $s['icon']; ?>"></i></span>
            <span style="min-width:0">
                <span class="pod-step-name d-block"><?= htmlspecialchars($s['label']); ?></span>
                <span class="pod-step-meta d-block" title="<?= htmlspecialchars($s['meta']); ?>"><?= htmlspecialchars($s['meta']); ?></span>
            </span>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="row">
        <!-- ================= KIRI: item ================= -->
        <div class="col-xl-8">
            <div class="pp-card">
                <div class="pp-card-head">
                    <h2><span class="pp-ico"><i class="fas fa-list-ul"></i></span><span><?= lang_line('pod2_items', 'Item Purchase Order'); ?> (<span id="item-count"><?= $n_items; ?></span>)
                        <span class="d-block pp-sub"><?= sprintf(lang_line('pod2_items_sub', 'Harga dalam %s'), htmlspecialchars($cur)); ?></span></span></h2>
                    <div class="d-flex flex-wrap" style="gap:.3rem">
                        <?php if ($n_zero): ?><span class="pp-chip pp-chip--warn"><i class="fas fa-tag"></i><?= sprintf(lang_line('poa2_chip_zero', '%d harga 0'), $n_zero); ?></span><?php endif; ?>
                        <?php if ($n_over_pr): ?><span class="pp-chip pp-chip--warn"><i class="fas fa-exclamation-triangle"></i><?= sprintf(lang_line('poa2_chip_over_pr', '%d melebihi qty PR'), $n_over_pr); ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="pp-table pod-items">
                        <thead><tr>
                            <th class="pod-no">#</th>
                            <th><?= lang_line('pod2_th_item', 'Item'); ?></th>
                            <th class="num"><?= lang_line('po_qty', 'Qty'); ?></th>
                            <th><?= lang_line('po_unit', 'Satuan'); ?></th>
                            <th class="num"><?= lang_line('po_base_price', 'Harga Satuan'); ?></th>
                            <th class="num"><?= lang_line('po_total', 'Total'); ?></th>
                            <?php if ($show_item_action): ?><th style="width:86px"></th><?php endif; ?>
                        </tr></thead>
                        <tbody id="po-approval-items">
                        <?php if (!$details): ?>
                            <tr id="no-item-row"><td colspan="<?= 6 + $trail_cols; ?>" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pod2_no_items', 'Belum ada item.'); ?></td></tr>
                        <?php else: $no = 1; foreach ($details as $d):
                            $line = (float) $d->qty * (float) $d->base_price;
                            $zero = (float) $d->base_price == 0;
                            $over = isset($d->pr_qty) && $d->pr_qty !== null && (float) $d->qty > (float) $d->pr_qty + 0.0001; ?>
                            <tr data-detail-id="<?= (int) $d->id; ?>" data-total="<?= $line; ?>" class="<?= $zero ? 'price-zero-row' : ''; ?>">
                                <td class="pod-no row-no"><?= $no++; ?></td>
                                <td>
                                    <div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div>
                                    <div class="pod-meta">
                                        <span class="pp-item-code"><?= htmlspecialchars($d->item_code ?: '-'); ?></span>
                                        <?php if (!empty($d->pr_number) && !empty($d->pr_header_id)): ?>
                                            <a href="<?= base_url($type_meta[2] . '/detail/' . (int) $d->pr_header_id); ?>" class="pp-chip<?= $over ? ' pp-chip--warn' : ''; ?>" title="<?= htmlspecialchars(lang_line('pod2_from_pr', 'Ditarik dari PR')); ?>"><i class="fas fa-file-import"></i><?= htmlspecialchars($d->pr_number); ?><?= isset($d->pr_qty) && $d->pr_qty !== null ? ' · ' . lang_line('poa2_pr_qty', 'qty PR') . ' ' . $qty($d->pr_qty) : ''; ?></a>
                                        <?php endif; ?>
                                        <?php if (!empty($d->sc_number)): ?><span class="pp-chip" title="Sales Order"><i class="fas fa-file-contract"></i><?= htmlspecialchars($d->sc_number); ?></span><?php endif; ?>
                                    </div>
                                    <div class="pod-desc js-desc"<?= trim((string) $d->description) === '' ? ' style="display:none"' : ''; ?>><?= htmlspecialchars((string) $d->description); ?></div>
                                </td>
                                <td class="num font-weight-bold js-qty"><?= $qty($d->qty); ?></td>
                                <td><span class="pod-unit"><?= htmlspecialchars($d->item_unit ?: '-'); ?></span></td>
                                <td class="num js-price<?= $zero ? ' price-zero' : ''; ?>"><span class="display-val"><?= $price($d->base_price); ?></span></td>
                                <td class="num font-weight-bold js-total"><?= $money($line); ?></td>
                                <?php if ($show_item_action): ?>
                                <td class="text-right"><div class="pp-actions">
                                    <button type="button" class="btn btn-light btn-edit-po-item" data-id="<?= (int) $d->id; ?>" data-qty="<?= (float) $d->qty; ?>" data-price="<?= (float) $d->base_price; ?>" data-desc="<?= htmlspecialchars((string) $d->description); ?>" data-name="<?= htmlspecialchars($d->item_name ?? ''); ?>" title="<?= htmlspecialchars(lang_line('po_edit_button_title', 'Edit')); ?>"><i class="fas fa-pen"></i></button>
                                    <button type="button" class="btn btn-light text-danger btn-delete-po-item" data-id="<?= (int) $d->id; ?>" data-name="<?= htmlspecialchars($d->item_name ?? ''); ?>" title="<?= htmlspecialchars(lang_line('po_delete_button_title', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></button>
                                </div></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="5" class="text-right"><?= lang_line('po_subtotal', 'Subtotal'); ?><?= ($ppn['applied'] && $ppn['inclusive']) ? ' <small class="text-muted">(DPP)</small>' : ''; ?></td><td class="num js-sub"><?= $money($ppn['dpp']); ?></td><?= $trail_cols ? '<td></td>' : ''; ?></tr>
                            <?php if ($ppn['applied']): ?>
                            <tr><td colspan="5" class="text-right"><?= htmlspecialchars(po_ppn_label($ppn['rate'], $ppn['inclusive'])); ?></td><td class="num js-ppn"><?= $money($ppn['ppn']); ?></td><?= $trail_cols ? '<td></td>' : ''; ?></tr>
                            <?php endif; ?>
                            <tr class="pod-grand"><td colspan="5" class="text-right"><?= lang_line('po_grand_total', 'Grand Total'); ?> <small class="text-muted">(<?= htmlspecialchars($cur); ?>)</small></td><td class="num js-grand"><?= $money($ppn['total']); ?></td><?= $trail_cols ? '<td></td>' : ''; ?></tr>
                            <?php if ($cur !== 'IDR'): ?>
                            <tr class="pod-idr"><td colspan="5" class="text-right"><?= lang_line('po_estimated_grand_total_idr', 'Estimasi Grand Total (IDR)'); ?></td><td class="num js-idr"><?= $money($ppn['total'] * $fx); ?></td><?= $trail_cols ? '<td></td>' : ''; ?></tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= KANAN: keputusan + info + riwayat ================= -->
        <div class="col-xl-4">
            <div class="poa-decide">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-gavel"></i></span><?= lang_line('poa2_decision', 'Keputusan Approval'); ?></h2></div>
                <div class="pp-card-body">
                    <?php if ($pending && $can_decide): ?>
                        <div class="pp-label"><?= lang_line('po_grand_total', 'Grand Total'); ?></div>
                        <div class="poa-total"><small><?= htmlspecialchars($cur); ?></small> <span class="js-grand"><?= $money($ppn['total']); ?></span></div>
                        <?php if ($cur !== 'IDR'): ?><div class="pp-item-code">≈ IDR <span class="js-idr"><?= $money($ppn['total'] * $fx); ?></span></div><?php endif; ?>
                        <ul class="poa-checks">
                            <li><i class="fas fa-list-ul text-muted"></i><span><?= sprintf(lang_line('poa2_check_items', '%d item dari %s'), $n_items, htmlspecialchars($header->nama_supplier ?: '-')); ?></span></li>
                            <li><?php if ($n_zero): ?><i class="fas fa-exclamation-triangle text-warning"></i><span><?= sprintf(lang_line('poa2_check_zero', '%d item harga satuannya masih 0'), $n_zero); ?></span><?php else: ?><i class="fas fa-check text-success"></i><span><?= lang_line('poa2_check_price_ok', 'Semua item sudah berharga'); ?></span><?php endif; ?></li>
                            <?php if (!empty($source_prs)): ?>
                            <li><?php if ($n_over_pr): ?><i class="fas fa-exclamation-triangle text-warning"></i><span><?= sprintf(lang_line('poa2_check_over_pr', '%d item qty-nya melebihi PR'), $n_over_pr); ?></span><?php else: ?><i class="fas fa-check text-success"></i><span><?= lang_line('poa2_check_pr_ok', 'Qty sesuai Purchase Request'); ?></span><?php endif; ?></li>
                            <?php endif; ?>
                        </ul>
                        <div class="poa-btns">
                            <?php if (!empty($can_approve_final)): ?>
                                <a href="<?= base_url($appr . '/approve_final/' . $header->id); ?>" class="btn btn-success btn-confirm<?= $n_items ? '' : ' disabled'; ?>"
                                   data-message="<?= htmlspecialchars(sprintf(lang_line('poa2_approve_q', 'Setujui PO %s?'), $header->po_number)); ?>" data-text="<?= htmlspecialchars($approve_text); ?>"
                                   data-icon="<?= $n_zero ? 'warning' : 'question'; ?>" data-confirm-text="<?= htmlspecialchars(lang_line('poa_yes_approve', 'Ya, Setujui')); ?>"><i class="fas fa-check-double"></i><?= lang_line('poa2_btn_approve', 'Setujui PO'); ?></a>
                            <?php endif; ?>
                            <?php if (!empty($can_return_reject)): ?>
                                <button type="button" class="btn btn-light" data-toggle="modal" data-target="#returnDraftModal"><i class="fas fa-undo text-warning"></i><?= lang_line('poa2_btn_return', 'Kembalikan ke Draft'); ?></button>
                                <button type="button" class="btn btn-light text-danger" data-toggle="modal" data-target="#rejectModal"><i class="fas fa-times-circle"></i><?= lang_line('poa2_btn_reject', 'Tolak PO'); ?></button>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($can_approve_final)): ?>
                            <div class="pp-hint"><i class="fas fa-info-circle mr-1"></i><?= lang_line('poa2_no_approve_access', 'Anda tidak punya izin approve — hanya bisa mengembalikan atau menolak.'); ?></div>
                        <?php elseif (!$n_items): ?>
                            <div class="pp-hint text-danger"><i class="fas fa-ban mr-1"></i><?= lang_line('poa_no_items_approve_error', 'PO tidak bisa disetujui karena tidak ada item.'); ?></div>
                        <?php endif; ?>
                    <?php else:
                        if ($pending)                 { $t = 'tone-blue';  $i = 'fa-hourglass-half'; $b = lang_line('pod2_wait_approval', 'Menunggu approval'); $x = lang_line('poa2_state_pending_noaccess', 'Anda tidak punya izin mengambil keputusan atas PO ini.'); }
                        elseif ($approved)            { $t = 'tone-green'; $i = 'fa-check-double';    $b = lang_line('poa2_state_approved', 'PO sudah disetujui'); $x = trim(($final_by ?: '-') . ($final_at ? ' · ' . $final_at : '')) . ($st === 'Confirmed' ? '. ' . lang_line('poa2_state_approved_hint', 'Siap diproses di gudang / penerimaan.') : ''); }
                        elseif ($st === 'Rejected')   { $t = 'tone-rose';  $i = 'fa-ban';             $b = lang_line('pod2_rejected', 'PO ditolak'); $x = trim(($who($header->rejected_by ?? null) ?: '-') . ($fmt_dt($header->rejected_at ?? null) ? ' · ' . $fmt_dt($header->rejected_at) : '')); }
                        elseif ($st === 'Draft')      { $t = 'tone-amber'; $i = 'fa-pen';             $b = lang_line('poa2_state_draft', 'PO masih Draft'); $x = !empty($header->return_reason) ? lang_line('poa2_state_draft_returned', 'Dikembalikan ke pembuat untuk direvisi.') : lang_line('poa2_state_draft_new', 'Belum disubmit oleh pembuat.'); }
                        else                          { $t = 'tone-slate'; $i = 'fa-archive';         $b = htmlspecialchars($st); $x = lang_line('pod2_cancelled_hint', 'Dokumen hanya untuk arsip — tidak bisa diproses lagi.'); }
                    ?>
                        <div class="poa-state <?= $t; ?>"><span class="poa-state-ico"><i class="fas <?= $i; ?>"></i></span><div><b><?= $b; ?></b><span><?= htmlspecialchars($x); ?></span></div></div>
                    <?php endif; ?>
                </div>
            </div>
            </div>

            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-info"></i></span><?= lang_line('pod2_info', 'Informasi'); ?></h2></div>
                <div class="pp-card-body">
                    <dl class="pod-kv">
                        <dt><?= lang_line('po_supplier', 'Supplier'); ?></dt><dd class="font-weight-bold"><?= htmlspecialchars($header->nama_supplier ?: '-'); ?></dd>
                        <?php if (!empty($header->supplier_contact_person)): ?><dt><?= lang_line('pod2_contact', 'Kontak'); ?></dt><dd><?= htmlspecialchars($header->supplier_contact_person); ?></dd><?php endif; ?>
                        <dt><?= lang_line('po_supplier_phone', 'Telepon'); ?></dt><dd><?= htmlspecialchars($header->telepon ?: '-'); ?></dd>
                        <dt><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></dt><dd><?= htmlspecialchars($header->shipping_mode_desc ?: '-'); ?></dd>
                        <?php if (!empty($source_prs)): ?>
                        <dt><?= lang_line('pod2_source_pr', 'Sumber PR'); ?></dt>
                        <dd class="d-flex flex-wrap" style="gap:.3rem">
                            <?php foreach ($source_prs as $spr): ?><a href="<?= base_url($type_meta[2] . '/detail/' . (int) $spr->id); ?>" class="pp-chip" title="<?= htmlspecialchars($spr->purpose ?? ''); ?>"><i class="fas fa-file-alt"></i><?= htmlspecialchars($spr->pr_number); ?></a><?php endforeach; ?>
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
                    <form method="POST" action="<?= base_url($appr . '/reply/' . $header->id); ?>" class="pt-3" style="border-top:1px solid var(--pp-line)">
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

<?php if (!empty($can_return_reject)): ?>
<!-- Kembalikan ke Draft -->
<div class="modal fade pp-modal pod2-modal" id="returnDraftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url($appr . '/return_to_draft/' . $header->id); ?>" class="w-100">
            <div class="modal-content pp2">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= lang_line('poa2_btn_return', 'Kembalikan ke Draft'); ?></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <p class="pp-hint mt-0 mb-3"><?= lang_line('poa2_return_hint', 'PO kembali ke pembuat untuk direvisi, lalu bisa disubmit ulang. Alasan Anda tampil di halaman PO dan bisa dibalas.'); ?></p>
                    <label class="pp-label"><?= lang_line('poa2_reason', 'Alasan'); ?> <span class="text-danger">*</span></label>
                    <textarea name="return_reason" class="form-control" rows="4" required placeholder="<?= htmlspecialchars(lang_line('poa2_return_ph', 'Mis. harga belum sesuai penawaran, mohon cek ulang.')); ?>"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button><button type="submit" class="btn btn-warning"><i class="fas fa-undo mr-1"></i><?= lang_line('poa2_btn_return', 'Kembalikan ke Draft'); ?></button></div>
            </div>
        </form>
    </div>
</div>
<!-- Tolak -->
<div class="modal fade pp-modal pod2-modal" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url($appr . '/reject/' . $header->id); ?>" class="w-100">
            <div class="modal-content pp2">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= lang_line('poa2_btn_reject', 'Tolak PO'); ?> · <?= htmlspecialchars($header->po_number); ?></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <p class="pp-hint mt-0 mb-3"><?= lang_line('poa2_reject_hint', 'PO yang ditolak tidak bisa diproses lagi. Pakai "Kembalikan ke Draft" kalau PO masih bisa diperbaiki.'); ?></p>
                    <label class="pp-label"><?= lang_line('poa2_reason', 'Alasan'); ?> <span class="text-danger">*</span></label>
                    <textarea name="reject_reason" class="form-control" rows="4" required placeholder="<?= htmlspecialchars(lang_line('poa2_reject_ph', 'Tulis alasan penolakan...')); ?>"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button><button type="submit" class="btn btn-danger"><i class="fas fa-times-circle mr-1"></i><?= lang_line('poa2_btn_reject', 'Tolak PO'); ?></button></div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($show_item_action): ?>
<!-- Edit item (mode Admin) -->
<div class="modal fade pp-modal pod2-modal" id="editPoItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pp2">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-pen mr-2"></i><?= lang_line('poa_edit_item_title', 'Edit Item PO'); ?></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" id="poa-edit-id">
                <p class="font-weight-bold mb-3" id="poa-edit-name"></p>
                <div class="form-row">
                    <div class="col-6 form-group"><label class="pp-label"><?= lang_line('po_qty', 'Qty'); ?> <span class="text-danger">*</span></label><input type="number" step="0.0001" min="0.0001" class="form-control text-right" id="poa-edit-qty"></div>
                    <div class="col-6 form-group"><label class="pp-label"><?= lang_line('po_base_price', 'Harga Satuan'); ?> (<?= htmlspecialchars($cur); ?>) <span class="text-danger">*</span></label><input type="number" step="0.0001" min="0" class="form-control text-right" id="poa-edit-price"></div>
                </div>
                <div class="form-group mb-0"><label class="pp-label"><?= lang_line('po_description', 'Keterangan'); ?></label><input type="text" class="form-control" id="poa-edit-desc" placeholder="<?= htmlspecialchars(lang_line('po_optional', 'Opsional...')); ?>"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button><button type="button" class="btn btn-pp" id="btn-save-poa-item"><i class="fas fa-save mr-1"></i><?= lang_line('po_save', 'Simpan'); ?></button></div>
        </div>
    </div>
</div>

<script>
// Footer (jQuery + plugin) dimuat SESUDAH view ini oleh controller -> ikat di DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var URL_EDIT = <?= json_encode(base_url($appr . '/edit_item/')); ?>, URL_DEL = <?= json_encode(base_url($appr . '/delete_item/')); ?>;
    var PPN = { has: <?= $ppn['applied'] ? 'true' : 'false'; ?>, rate: <?= (float) $ppn['rate']; ?>, inclusive: <?= $ppn['inclusive'] ? 'true' : 'false'; ?>, fx: <?= $fx; ?> };
    var T = {
        failed: <?= json_encode(lang_line('po_failed', 'Gagal!')); ?>, conn: <?= json_encode(lang_line('poa_server_error', 'Gagal menghubungi server.')); ?>,
        qty: <?= json_encode(lang_line('poa_qty_error', 'Qty harus lebih dari 0')); ?>, price: <?= json_encode(lang_line('poa_price_error', 'Harga tidak boleh negatif')); ?>,
        delTitle: <?= json_encode(lang_line('poa_delete_item_title', 'Hapus Item?')); ?>, delText: <?= json_encode(lang_line('poa_delete_item_confirm', 'Yakin hapus item ini dari PO?')); ?>,
        yesDel: <?= json_encode(lang_line('poa_yes_delete', 'Ya, Hapus')); ?>, cancel: <?= json_encode(lang_line('po_cancel', 'Batal')); ?>,
        empty: <?= json_encode(lang_line('pod2_no_items', 'Belum ada item.')); ?>
    };
    function fmt(n, d) { return (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d }); }
    function fail(m) { Swal.fire({ icon: 'error', title: T.failed, text: m || T.conn }); }
    function recalc() {
        var sub = 0; $('#po-approval-items tr[data-detail-id]').each(function () { sub += parseFloat($(this).attr('data-total')) || 0; });
        var dpp = sub, ppn = 0, tot = sub;
        if (PPN.has && PPN.rate > 0) { if (PPN.inclusive) { ppn = sub * PPN.rate / (100 + PPN.rate); dpp = sub - ppn; } else { ppn = sub * PPN.rate / 100; tot = sub + ppn; } }
        $('.js-sub').text(fmt(dpp, 2)); $('.js-ppn').text(fmt(ppn, 2)); $('.js-grand').text(fmt(tot, 2)); $('.js-idr').text(fmt(tot * PPN.fx, 2));
        var n = $('#po-approval-items tr[data-detail-id]').length; $('#item-count').text(n);
        $('#po-approval-items tr[data-detail-id]').each(function (i) { $(this).find('.row-no').text(i + 1); });
        if (!n) $('#po-approval-items').html('<tr><td colspan="7" class="pp-empty"><i class="fas fa-inbox"></i>' + T.empty + '</td></tr>');
    }
    $(document).on('click', '.btn-edit-po-item', function () {
        var b = $(this);
        $('#poa-edit-id').val(b.data('id')); $('#poa-edit-name').text(b.data('name'));
        $('#poa-edit-qty').val(b.data('qty')); $('#poa-edit-price').val(b.data('price')); $('#poa-edit-desc').val(b.data('desc'));
        $('#editPoItemModal').modal('show');
    });
    $('#btn-save-poa-item').on('click', function () {
        var id = $('#poa-edit-id').val(), q = parseFloat($('#poa-edit-qty').val()), p = parseFloat($('#poa-edit-price').val()), d = $('#poa-edit-desc').val() || '';
        if (!(q > 0)) { fail(T.qty); return; }
        if (isNaN(p) || p < 0) { fail(T.price); return; }
        var $b = $(this).prop('disabled', true);
        $.post(URL_EDIT + id, { qty: q, base_price: p, description: d }, null, 'json').done(function (res) {
            if (!res || !res.success) { fail(res && res.message); return; }
            var $r = $('#po-approval-items tr[data-detail-id="' + id + '"]');
            $r.attr('data-total', q * p).toggleClass('price-zero-row', p === 0);
            $r.find('.js-qty').text(q.toLocaleString('en-US', { maximumFractionDigits: 4 }));
            $r.find('.js-price').toggleClass('price-zero', p === 0).find('.display-val').text(p.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }));
            $r.find('.js-total').text(fmt(q * p, 2));
            $r.find('.js-desc').text(d).toggle(d !== '');
            $r.find('.btn-edit-po-item').data({ qty: q, price: p, desc: d });
            $('#editPoItemModal').modal('hide'); recalc();
            if (window.toastr) toastr.success(res.message);
        }).fail(function () { fail(); }).always(function () { $b.prop('disabled', false); });
    });
    $(document).on('click', '.btn-delete-po-item', function () {
        var id = $(this).data('id'), name = $(this).data('name'), $r = $(this).closest('tr');
        Swal.fire({ icon: 'warning', title: T.delTitle, text: '"' + name + '" — ' + T.delText, showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#e11d48', confirmButtonText: T.yesDel, cancelButtonText: T.cancel }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post(URL_DEL + id, {}, null, 'json').done(function (res) {
                if (!res || !res.success) { fail(res && res.message); return; }
                $r.remove(); recalc();
                if (window.toastr) toastr.success(res.message);
            }).fail(function () { fail(); });
        });
    });
});
</script>
<?php endif; ?>
