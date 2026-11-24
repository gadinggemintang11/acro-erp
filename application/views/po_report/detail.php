<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Detail PO versi LAPORAN -- dipakai Po_report, Po_monitoring, Pmpo_list &
 * Gmpo_list. Redesign 2026-11-09, gaya .pp2.
 *
 * Read-only: ringkasan + jejak PR -> PO -> Approval -> Penerimaan. Dokumen PO-nya
 * sendiri dibuka lewat tombol "Buka Dokumen PO" (user 2026-10-01), digate izin
 * modul tujuannya. Tombol Kembali ke modul asal (segmen URL pertama).
 * Data: Po_report_model::get_po_detail() -- penerimaan = GRN/SGR Completed +
 * Asset Register, $receipts = GRN + penerimaan general.
 */
$this->load->view('purchase_order/_po_style');

$h        = $po_header;
$details  = $po_details ?? [];
$receipts = $receipts ?? [];
$po_type  = $h->type ?? '';
$po_modul = [
    'produksi'     => ['purchase_order',         lang_line('pod2_type_produksi', 'Material Produksi'), 'purchase_request'],
    'non_produksi' => ['purchase_order_general', lang_line('pod2_type_general', 'General'),            'purchase_request_general'],
    'jasa'         => ['purchase_order_jasa',    lang_line('pod2_type_jasa', 'Jasa'),                  'purchase_request_jasa'],
    'asset'        => ['purchase_order_asset',   lang_line('pod2_type_asset', 'Asset'),                'purchase_request_asset'],
];
$pm          = $po_modul[$po_type] ?? [null, 'PO', 'purchase_request'];
$can_open_po = $pm[0] && check_access($pm[0], 'view');
$modul_asal  = $this->uri->segment(1) ?: 'po_report';
$cur         = $h->currency_code ?: 'IDR';

$fmt_dt = function ($v, $time = false) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$money  = function ($n) { return number_format((float) $n, 2, '.', ','); };
$price  = function ($n) { return preg_replace('/(\.\d\d\d*?)0+$/', '$1', number_format((float) $n, 4, '.', ',')); };
$qty    = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };

$total_cur = 0.0; $total_idr = 0.0; $n_full = 0; $any_recv = false;
foreach ($details as $d) {
    $total_cur += (float) $d->line_total;
    $total_idr += (float) ($d->total_local ?? 0);
    if ($d->item_receipt_status === 'Complete') $n_full++;
    if ((float) $d->qty_received > 0 || (float) ($d->qty_draft ?? 0) > 0) $any_recv = true;
}
$n_items  = count($details);
$approved = in_array($h->status, ['Confirmed', 'Closed'], true);
$days     = (int) ($h->days_to_warehouse_in ?? 0);
$pill = ['Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress', 'Endorsed' => 'pp-status--progress',
         'Confirmed' => 'pp-status--completed', 'Closed' => 'pp-status--other', 'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad'];
$recv_meta = [
    'Not Received' => [lang_line('pst2_recv_none', 'Belum diterima'), 'pp-chip'],
    'Partial'      => [lang_line('pst2_recv_partial', 'Sebagian'),    'pp-chip pp-chip--warn'],
    'Complete'     => [lang_line('pst2_recv_complete', 'Lengkap'),    'pp-chip pp-chip--ok'],
];

// jejak PR -> PO -> Approval -> Penerimaan
$recv_cls = !$approved ? '' : ($n_items && $n_full === $n_items ? 'is-done' : ($any_recv ? 'is-partial' : 'is-current'));
$steps = [
    ['cls' => !empty($h->pr_number) ? 'is-done' : '', 'icon' => 'fa-file-import', 'label' => 'Purchase Request',
     'meta' => !empty($h->pr_number) ? trim($h->pr_number . ($fmt_dt($h->pr_date ?? null) ? ' · ' . $fmt_dt($h->pr_date) : '')) : lang_line('por2_no_pr_header', 'Tanpa PR utama')],
    ['cls' => 'is-done', 'icon' => 'fa-check', 'label' => lang_line('por2_step_po', 'PO Dibuat'),
     'meta' => trim(($h->creator_name ?? '-') . ($fmt_dt($h->created_at ?? null) ? ' · ' . $fmt_dt($h->created_at) : ''))],
    ['cls' => $approved ? 'is-done' : (in_array($h->status, ['Rejected', 'Cancelled'], true) ? 'is-rejected' : 'is-current'), 'icon' => $approved ? 'fa-check' : 'fa-gavel', 'label' => lang_line('pod2_step_approved', 'Disetujui'),
     'meta' => $approved ? trim(($h->confirmer_name ?? '-') . ($fmt_dt($h->confirmed_at ?? null, true) ? ' · ' . $fmt_dt($h->confirmed_at, true) : '')) : $h->status],
    ['cls' => $recv_cls, 'icon' => $recv_cls === 'is-done' ? 'fa-check' : 'fa-dolly', 'label' => lang_line('por2_step_recv', 'Penerimaan'),
     'meta' => $approved ? sprintf(lang_line('pod2_lines_complete', '%d/%d baris lengkap'), $n_full, $n_items) : '—'],
];
?>
<div class="container-fluid pp2 pod2 por2d">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-receipt"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker"><?= lang_line('por2_kicker', 'Laporan PO'); ?> · <?= htmlspecialchars($pm[1]); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($h->po_number); ?> <span class="pp-status <?= $pill[$h->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($h->status); ?></span></h1>
                    <div class="pp-hero-subline"><i class="far fa-calendar mr-1"></i><?= $fmt_dt($h->po_date); ?><span class="mx-1">·</span><i class="fas fa-store mr-1"></i><?= htmlspecialchars($h->nama_supplier ?: '-'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($can_open_po): ?>
                    <a href="<?= base_url($pm[0] . '/detail/' . $h->id); ?>" class="btn pp-btn-light"><i class="fas fa-external-link-alt mr-1"></i><?= lang_line('por2_open_po', 'Buka Dokumen PO'); ?></a>
                <?php endif; ?>
                <a href="<?= base_url('po_report/export_pdf/' . $h->id); ?>" target="_blank" class="btn pp-btn-glass no-loader"><i class="fas fa-print mr-1"></i><?= lang_line('por2_print', 'Cetak'); ?></a>
                <a href="<?= base_url($modul_asal); ?>" class="btn pp-btn-glass btn-load"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('pod2_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_supplier', 'Supplier'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($h->nama_supplier ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('po_currency', 'Mata Uang'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($cur); ?><?php if ($cur !== 'IDR'): ?> <small>@ <?= $price($h->exchange_rate); ?></small><?php endif; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pst2_th_value', 'Nilai (IDR)'); ?></div><div class="pp-meta-value"><?= $money($total_idr); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pst2_th_receipt', 'Penerimaan'); ?></div><div class="pp-meta-value"><?= $approved ? sprintf(lang_line('pod2_lines_complete', '%d/%d baris lengkap'), $n_full, $n_items) : '—'; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pst2_th_age', 'Waktu'); ?></div><div class="pp-meta-value">
                <?php if (!empty($h->first_grn_date)): ?><?= sprintf(lang_line('pst2_age_first', 'H+%d'), $days); ?> <small><?= $fmt_dt($h->first_grn_date); ?></small>
                <?php else: ?><?= sprintf(lang_line('pst2_age_days', '%d hari'), $days); ?> <small><?= lang_line('pst2_age_hint', 'sejak tanggal PO'); ?></small><?php endif; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pdb_th_requester', 'Requester'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($h->requester_name ?? '-') ?: '-'; ?></div></div>
        </div>
    </section>

    <nav class="pod-steps" aria-label="<?= htmlspecialchars(lang_line('po_report_traceability', 'Traceability')); ?>">
        <?php foreach ($steps as $s): ?>
        <div class="pod-step <?= $s['cls']; ?>">
            <span class="pod-step-dot"><i class="fas <?= $s['cls'] === 'is-rejected' ? 'fa-times' : $s['icon']; ?>"></i></span>
            <span style="min-width:0"><span class="pod-step-name d-block"><?= htmlspecialchars($s['label']); ?></span><span class="pod-step-meta d-block" title="<?= htmlspecialchars($s['meta']); ?>"><?= htmlspecialchars($s['meta']); ?></span></span>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="pp-card">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-list-ul"></i></span><span><?= lang_line('por2_items', 'Item & Status Penerimaan'); ?> (<?= $n_items; ?>)
            <span class="d-block pp-sub"><?= sprintf(lang_line('pod2_items_sub', 'Harga dalam %s'), htmlspecialchars($cur)); ?></span></span></h2></div>
        <div class="table-responsive">
            <table class="pp-table pod-items">
                <thead><tr>
                    <th class="pod-no">#</th>
                    <th><?= lang_line('pod2_th_item', 'Item'); ?></th>
                    <th class="num"><?= lang_line('po_qty', 'Qty'); ?></th>
                    <th><?= lang_line('po_unit', 'Satuan'); ?></th>
                    <th class="num"><?= lang_line('po_base_price', 'Harga Satuan'); ?></th>
                    <th class="num"><?= lang_line('po_total', 'Total'); ?></th>
                    <th class="num"><?= lang_line('pod2_th_received', 'Diterima'); ?></th>
                    <th class="num"><?= lang_line('pod2_th_remaining', 'Sisa'); ?></th>
                    <th><?= lang_line('pdb_th_status', 'Status'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!$details): ?>
                    <tr><td colspan="9" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('po_report_no_items', 'Tidak ada item dalam PO ini'); ?></td></tr>
                <?php else: $no = 1; foreach ($details as $d):
                    $pct = (float) $d->qty > 0 ? min(100, round((float) $d->qty_received / (float) $d->qty * 100)) : 0;
                    $rm  = $recv_meta[$d->item_receipt_status] ?? [$d->item_receipt_status, 'pp-chip']; ?>
                    <tr>
                        <td class="pod-no"><?= $no++; ?></td>
                        <td>
                            <div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div>
                            <div class="pod-meta"><span class="pp-item-code"><?= htmlspecialchars($d->item_code ?: '-'); ?> · <?= htmlspecialchars($d->item_type ?? '-'); ?></span>
                                <?php if (!empty($d->pr_number)): ?><span class="pp-chip"><i class="fas fa-file-import"></i><?= htmlspecialchars($d->pr_number); ?></span><?php endif; ?></div>
                            <?php if (trim((string) $d->description) !== '' && $d->description !== $d->item_name): ?><div class="pod-desc"><?= htmlspecialchars($d->description); ?></div><?php endif; ?>
                        </td>
                        <td class="num font-weight-bold"><?= $qty($d->qty); ?></td>
                        <td><span class="pod-unit"><?= htmlspecialchars($d->satuan ?: '-'); ?></span></td>
                        <td class="num"><?= $price($d->base_price); ?></td>
                        <td class="num font-weight-bold"><?= $money($d->line_total); ?></td>
                        <td class="num">
                            <span class="<?= $pct >= 100 ? 'text-success font-weight-bold' : ''; ?>"><?= $qty($d->qty_received); ?></span>
                            <div class="pod-prog<?= $pct > 0 && $pct < 100 ? ' is-partial' : ''; ?>"><span style="width:<?= $pct; ?>%"></span></div>
                            <?php if ((float) ($d->qty_draft ?? 0) > 0): ?><div class="pp-item-code">+<?= $qty($d->qty_draft); ?> draft</div><?php endif; ?>
                        </td>
                        <td class="num <?= (float) $d->qty_pending > 0 ? 'font-weight-bold' : 'text-muted'; ?>"><?= $qty($d->qty_pending); ?></td>
                        <td><span class="<?= $rm[1]; ?>"><?= htmlspecialchars($rm[0]); ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if ($details): ?>
                <tfoot>
                    <tr class="pod-grand"><td colspan="5" class="text-right"><?= lang_line('por2_items_total', 'Total item'); ?> <small class="text-muted">(<?= htmlspecialchars($cur); ?>, <?= lang_line('por2_value_hint', 'sebelum PPN'); ?>)</small></td><td class="num"><?= $money($total_cur); ?></td><td colspan="3"></td></tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-truck-loading"></i></span><span><?= lang_line('po_report_receipt_history', 'Riwayat Penerimaan'); ?> (<?= count($receipts); ?>)
                    <span class="d-block pp-sub"><?= lang_line('pod2_receipts_sub', 'GRN (Warehouse In) & penerimaan barang general dari PO ini'); ?></span></span></h2></div>
                <?php if (!$receipts): ?>
                    <div class="pp-card-body"><div class="pp-readonly"><i class="fas fa-info-circle"></i><?= $approved ? lang_line('po_report_no_receipts', 'Belum ada penerimaan untuk PO ini') : lang_line('pod2_recv_after_approve', 'Penerimaan bisa dicatat setelah PO disetujui.'); ?></div></div>
                <?php else: ?>
                <ul class="pod-docs">
                    <?php foreach ($receipts as $doc):
                        $is_sgr = $doc->kind === 'sgr';
                        $url = $is_sgr ? base_url('supplier_general_item_receipt/detail/' . $doc->id) : base_url('warehouse_in/detail/' . $doc->id);
                        $kind_lbl = $doc->kind === 'service' ? lang_line('pod2_doc_service', 'Penyelesaian Jasa') : ($is_sgr ? lang_line('pod2_doc_sgr', 'Penerimaan General') : 'GRN · Warehouse In');
                        $done = $doc->status === 'Completed'; ?>
                    <li class="<?= $done ? 'tone-green' : 'tone-amber'; ?>">
                        <span class="pod-doc-ico"><i class="fas <?= $doc->kind === 'service' ? 'fa-clipboard-check' : 'fa-truck'; ?>"></i></span>
                        <span style="min-width:0;flex:1">
                            <a href="<?= $url; ?>" class="pod-doc-title"><?= htmlspecialchars($doc->number); ?></a>
                            <span class="pod-doc-meta d-block"><?= $kind_lbl; ?> · <?= $fmt_dt($doc->date) ?: '-'; ?> · <?= sprintf(lang_line('pod2_doc_lines', '%d baris, qty %s'), (int) $doc->num_items, $qty($doc->qty)); ?><?= ($doc->kind === 'grn' && !empty($doc->qa_status)) ? ' · QA: ' . htmlspecialchars($doc->qa_status) : ''; ?></span>
                        </span>
                        <span class="pp-status <?= $done ? 'pp-status--completed' : 'pp-status--draft'; ?>"><?= htmlspecialchars($doc->status); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-info"></i></span><?= lang_line('pod2_info', 'Informasi'); ?></h2></div>
                <div class="pp-card-body">
                    <dl class="pod-kv">
                        <dt><?= lang_line('po_supplier', 'Supplier'); ?></dt><dd class="font-weight-bold"><?= htmlspecialchars($h->nama_supplier ?: '-'); ?></dd>
                        <dt><?= lang_line('po_supplier_phone', 'Telepon'); ?></dt><dd><?= htmlspecialchars($h->supplier_phone ?? '-') ?: '-'; ?></dd>
                        <dt><?= lang_line('po_supplier_address', 'Alamat'); ?></dt><dd><?= nl2br(htmlspecialchars($h->supplier_address ?? '-') ?: '-'); ?></dd>
                    </dl>
                    <?php if (!empty($h->pr_number)): ?>
                    <div class="pod-sep"></div>
                    <dl class="pod-kv">
                        <dt><?= lang_line('pod2_source_pr', 'Sumber PR'); ?></dt><dd><a href="<?= base_url($pm[2] . '/detail/' . (int) $h->pr_header_id); ?>" class="pp-chip"><i class="fas fa-file-alt"></i><?= htmlspecialchars($h->pr_number); ?></a></dd>
                        <dt><?= lang_line('pdb_th_requester', 'Requester'); ?></dt><dd><?= htmlspecialchars($h->requester_name ?? '-') ?: '-'; ?><?= !empty($h->requester_email) ? '<div class="pp-item-code">' . htmlspecialchars($h->requester_email) . '</div>' : ''; ?></dd>
                        <?php if (!empty($h->pr_description)): ?><dt><?= lang_line('por2_purpose', 'Keperluan'); ?></dt><dd><?= htmlspecialchars($h->pr_description); ?></dd><?php endif; ?>
                    </dl>
                    <?php endif; ?>
                    <?php if (trim((string) ($h->remarks ?? '')) !== ''): ?>
                        <div class="pod-sep"></div>
                        <div class="pp-label"><?= lang_line('po_remarks', 'Catatan'); ?></div>
                        <div class="pod-remarks"><?= htmlspecialchars($h->remarks); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
