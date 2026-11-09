<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Proses Pengeluaran Material (gudang) -- redesign 2026-11-08.
 *
 * Alur tetap sama: gudang mengisi "Qty keluarkan sekarang" per baris MR ->
 * Material_issuance::process_issuance (FIFO per batch, 1 dokumen
 * pengeluaran per proses; MR bisa dikeluarkan bertahap). Qty default =
 * min(sisa permintaan, stok gudang). Batal Keluarkan hanya saat status Issued.
 * Bukti pengeluaran (attachment) diupload SETELAH barang keluar.
 * Footer dimuat controller.
 */
$this->load->view('production_process/_pp_style');

$st        = $header->status;
$can_edit  = check_access('material_issuance', 'edit');
$open_st   = in_array($st, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received'], true);
$fmt_dt    = function ($v, $t = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($t ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$qty       = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };

$groups = []; $has_remaining = false; $n_full = 0; $n_short = 0;
foreach ($details as $d) {
    $d->issued_so_far = (float) ($d->issued_qty ?? 0);
    $d->remaining     = max(0, round((float) $d->requested_qty - $d->issued_so_far, 4));
    $d->stock         = (float) $d->total_available_stock;
    $d->default_qty   = max(0, min($d->remaining, $d->stock));
    if ($d->remaining > 0.00001) $has_remaining = true; else $n_full++;
    if ($d->remaining > 0.00001 && $d->stock < $d->remaining) $n_short++;
    $groups[$d->wo_number ?: '-'][] = $d;
}
$can_issue        = $open_st && $has_remaining && $can_edit;
$needs_attachment = in_array($st, ['Partial Issued', 'Partial Received', 'Issued', 'Received'], true) && empty($attachments);
$mi_need_pr       = (float) ($remaining_pr_qty ?? 0) > 0.0001;
$pill = ['Approved' => 'pp-status--draft', 'Partial Issued' => 'pp-status--progress', 'Partial Received' => 'pp-status--progress',
         'Issued' => 'pp-status--completed', 'Received' => 'pp-status--other'][$st] ?? 'pp-status--other';
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<style>
.mi2 tr.mr-group td { background: var(--pp-soft); font-size: .78rem; font-weight: 700; padding-top: .55rem; padding-bottom: .55rem; }
.mi2 .mi-qty { width: 120px; text-align: right; display: inline-block; }
.mi2 .mi-prog { height: 5px; border-radius: 99px; background: var(--pp-line); overflow: hidden; margin-top: .3rem; width: 90px; margin-left: auto; }
.mi2 .mi-prog > span { display: block; height: 100%; background: #10b981; }
.mi2 tr.is-done td { opacity: .6; }
.mi2 .mi-flag { display: inline-flex; align-items: center; gap: .25rem; font-size: .66rem; font-weight: 700; padding: .1rem .45rem; border-radius: 6px; margin-top: .2rem; background: rgba(244,63,94,.1); color: #be123c; }
body.dark-mode .mi2 .mi-flag { color: #fda4af; }
.mi2 .mi-bar { position: sticky; bottom: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .85rem 1.1rem; border-top: 1px solid var(--pp-line); background: var(--pp-card); border-radius: 0 0 var(--pp-radius) var(--pp-radius); }
.mi2 .mi-bar-info { font-size: .85rem; color: var(--pp-muted); } .mi2 .mi-bar-info b { color: var(--pp-text); }
.mi2 .mi-note { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .86rem; background: rgba(245,158,11,.1); color: #92400e; border: 1px solid rgba(245,158,11,.3); }
body.dark-mode .mi2 .mi-note { color: #fcd34d; }
.mi2 .mi-file { display: flex; align-items: center; gap: .75rem; padding: .65rem .8rem; border: 1px solid var(--pp-line); border-radius: 12px; margin-bottom: .5rem; }
.mi2 .mi-file-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: rgba(var(--brand-rgb), .1); color: var(--brand-1); flex: 0 0 auto; }
</style>

<div class="container-fluid pp2 mi2">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-dolly"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker"><?= lang_line('material_issuance_title', 'Pengeluaran Material'); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($header->mr_number); ?> <span class="pp-status <?= $pill; ?>"><?= htmlspecialchars($st); ?></span></h1>
                    <div class="pp-hero-subline"><i class="far fa-calendar mr-1"></i><?= $fmt_dt($header->request_date, false); ?><span class="mx-1">·</span><i class="fas fa-sitemap mr-1"></i><?= htmlspecialchars($dept_name ?? '-'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if (check_access('material_request', 'view')): ?>
                    <a href="<?= base_url('material_request/detail/' . $header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-clipboard-list mr-1"></i><?= lang_line('mi2_btn_view_mr', 'Lihat MR'); ?></a>
                <?php endif; ?>
                <?php if ($st === 'Issued' && $can_edit): ?>
                    <a href="<?= base_url('material_issuance/reverse_issuance/' . $header->id); ?>" class="btn pp-btn-glass mi-confirm" data-danger="1"
                       data-text="<?= htmlspecialchars(lang_line('material_issuance_reverse_confirm', 'Yakin ingin membatalkan pengeluaran ini? Stok akan dikembalikan.')); ?>"><i class="fas fa-undo mr-1"></i><?= lang_line('mi2_btn_reverse', 'Batal Keluarkan'); ?></a>
                <?php endif; ?>
                <?php if (in_array($st, ['Issued', 'Received'], true)): ?>
                    <a href="<?= base_url('material_issuance/export_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <?php endif; ?>
                <a href="<?= base_url('material_issuance'); ?>" class="btn pp-btn-glass"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('material_issuance_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('material_issuance_requester', 'Dibuat Oleh'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->requester_name ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mr2_approver', 'Disetujui (Head of Dept)'); ?></div><div class="pp-meta-value"><?= htmlspecialchars(($header->hod_approver_name ?: $header->final_approver_name) ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('material_issuance_issued_by', 'Disiapkan Oleh'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->issuer_name ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mi2_full_items', 'Item keluar penuh'); ?></div><div class="pp-meta-value"><?= $n_full; ?> <small>/ <?= count($details); ?></small></div></div>
            <?php if (!empty($header->remarks)): ?>
            <div class="pp-meta" style="grid-column: span 2;"><div class="pp-meta-label"><?= lang_line('material_request_remarks', 'Catatan'); ?></div><div class="pp-meta-value" style="font-weight:600" title="<?= htmlspecialchars($header->remarks); ?>"><?= htmlspecialchars($header->remarks); ?></div></div>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if ($needs_attachment): ?>
        <div class="mi-note"><i class="fas fa-paperclip mt-1"></i><div><b><?= lang_line('material_issuance_need_attachment_title2', 'Belum selesai:'); ?></b> <?= lang_line('material_issuance_need_attachment2', 'Barang sudah dikeluarkan — upload attachment bukti pengeluaran di bawah untuk menyelesaikan dokumen ini.'); ?>
            <a href="#miAttachments" class="font-weight-bold ml-1" style="color:inherit;text-decoration:underline"><?= lang_line('mi2_go_upload', 'Upload sekarang'); ?></a></div></div>
    <?php endif; ?>

    <!-- ================= Form pengeluaran ================= -->
    <form id="issuanceForm" method="post" action="<?= base_url('material_issuance/process_issuance/' . $header->id); ?>">
    <div class="pp-card tone-brand">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-sign-out-alt"></i></span><span><?= lang_line('material_issuance_items_issuance', 'Item Pengeluaran'); ?> (<?= count($details); ?>)
                <span class="d-block pp-sub"><?= $can_issue ? lang_line('mi2_items_sub', 'Isi qty yang dikeluarkan sekarang — default = sisa permintaan, dibatasi stok gudang.') : ($has_remaining ? lang_line('mi2_items_sub_ro', 'Hanya lihat.') : lang_line('material_issuance_all_issued', 'Semua Material Sudah Dikeluarkan')); ?></span></span></h2>
            <?php if ($can_issue): ?>
            <div class="d-flex flex-wrap" style="gap:.4rem">
                <?php if ($n_short): ?><span class="pp-chip pp-chip--warn"><i class="fas fa-exclamation-triangle"></i><?= sprintf(lang_line('mr2_short_count', '%d item stoknya kurang'), $n_short); ?></span><?php endif; ?>
                <button type="button" class="btn btn-light btn-sm" id="miFillMax"><i class="fas fa-magic mr-1"></i><?= lang_line('mi2_btn_fill', 'Isi maksimal'); ?></button>
                <button type="button" class="btn btn-light btn-sm" id="miClear"><i class="fas fa-eraser mr-1"></i><?= lang_line('mi2_btn_clear', 'Kosongkan'); ?></button>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table" id="itemIssuanceTable">
                <thead><tr>
                    <th><?= lang_line('material_issuance_item_name', 'Nama Item'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_requested_qty', 'Diminta'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_issued', 'Dikeluarkan'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_outstanding', 'Sisa'); ?></th>
                    <th class="num"><?= lang_line('material_issuance_available_stock', 'Stok Tersedia'); ?></th>
                    <?php if ($can_issue): ?><th class="num"><?= lang_line('material_issuance_qty_issue', 'Qty Keluarkan Sekarang'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php if (empty($details)): ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('material_issuance_no_items', 'Item Permintaan Barang tidak ditemukan'); ?></td></tr>
                <?php else: foreach ($groups as $wo => $rows): ?>
                    <tr class="mr-group"><td colspan="6"><i class="fas fa-clipboard-check mr-1"></i><?= htmlspecialchars($wo); ?> <span class="font-weight-normal" style="color:var(--pp-muted)">· <?= count($rows); ?> item</span></td></tr>
                    <?php foreach ($rows as $d):
                        $done = $d->remaining <= 0.00001;
                        $pct  = (float) $d->requested_qty > 0 ? min(100, round($d->issued_so_far / (float) $d->requested_qty * 100)) : 0;
                        $short = !$done && $d->stock < $d->remaining; ?>
                    <tr class="<?= $done ? 'is-done' : ''; ?>">
                        <td>
                            <div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($d->item_code); ?> · <?= htmlspecialchars($d->item_type); ?></div>
                            <?php if ($done): ?><span class="pp-chip pp-chip--ok mt-1"><i class="fas fa-check"></i><?= lang_line('mi2_done', 'Sudah keluar penuh'); ?></span>
                            <?php elseif ($short): ?><span class="mi-flag"><i class="fas fa-exclamation-circle"></i><?= sprintf(lang_line('mi2_flag_short', 'Stok kurang %s'), $qty($d->remaining - $d->stock)); ?></span><?php endif; ?>
                        </td>
                        <td class="num"><b><?= $qty($d->requested_qty); ?></b> <span class="pp-item-code"><?= htmlspecialchars($d->item_unit); ?></span></td>
                        <td class="num"><?= $qty($d->issued_so_far); ?><div class="mi-prog"><span style="width:<?= $pct; ?>%"></span></div></td>
                        <td class="num font-weight-bold"><?= $qty($d->remaining); ?></td>
                        <td class="num<?= $short ? ' text-danger font-weight-bold' : ''; ?>"><?= $qty($d->stock); ?></td>
                        <?php if ($can_issue): ?>
                        <td class="num">
                            <?php if (!$done): ?>
                                <input type="text" inputmode="decimal" name="items[<?= (int) $d->detail_id; ?>][qty]" class="form-control form-control-sm mi-qty qty-issued"
                                       value="<?= $d->default_qty > 0 ? rtrim(rtrim(number_format($d->default_qty, 4, '.', ''), '0'), '.') : '0'; ?>"
                                       data-max="<?= min($d->remaining, $d->stock); ?>" data-remaining="<?= $d->remaining; ?>" data-stock="<?= $d->stock; ?>" autocomplete="off">
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($can_issue): ?>
        <div class="mi-bar">
            <div class="mi-bar-info" id="miBarInfo">—</div>
            <button type="submit" class="btn btn-pp px-4" id="btnConfirmIssuance"><i class="fas fa-check-circle mr-1"></i><?= lang_line('material_issuance_confirm_issuance', 'Konfirmasi Pengeluaran'); ?></button>
        </div>
        <?php endif; ?>
    </div>
    </form>

    <?php if (!empty($remaining_outstanding) && !empty($can_create_pr) && ($mi_need_pr || !empty($pr_docs))): ?>
    <!-- ================= Sisa yang perlu PR ================= -->
    <div class="pp-card <?= $mi_need_pr ? 'tone-amber' : 'tone-blue'; ?>">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-shopping-cart"></i></span><span>
                <?= $mi_need_pr ? lang_line('mi_remaining_pr_title', 'Stock belum cukup:') : lang_line('mi_remaining_nopr_title', 'Masih ada sisa Material Request:'); ?>
                <span class="d-block pp-sub"><?= $mi_need_pr ? lang_line('mi_remaining_pr_desc', 'Material Request ini masih punya sisa yang belum dikeluarkan. Qty PR = sisa dikurangi stok gudang dan qty yang sudah ada di PR sebelumnya.') : lang_line('mi_remaining_nopr_desc', 'PR untuk sisa ini sudah dibuat, atau sisanya sudah bisa dipenuhi dari stok gudang.'); ?></span></span></h2>
            <?php if ($mi_need_pr): ?>
            <form method="POST" action="<?= base_url('material_outstanding/create_pr'); ?>" class="mi-pr-form">
                <input type="hidden" name="redirect_to" value="<?= base_url('material_issuance/detail/' . $header->id); ?>">
                <?php foreach ($remaining_outstanding as $r): ?><input type="hidden" name="mr_detail_ids[]" value="<?= (int) $r->mr_detail_id; ?>"><?php endforeach; ?>
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-shopping-cart mr-1"></i><?= lang_line('mi_btn_remaining_pr', 'Buat PR Sisanya'); ?></button>
            </form>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('mi_rem_th_item', 'Material'); ?></th>
                    <th class="num"><?= lang_line('mi_rem_th_outstanding', 'Sisa Material Request'); ?></th>
                    <th class="num"><?= lang_line('mi_rem_th_stock', 'Dari Stok'); ?></th>
                    <th class="num"><?= lang_line('mi_rem_th_on_pr', 'Sudah di PR'); ?></th>
                    <th class="num"><?= lang_line('mi_rem_th_pr', 'Perlu PR'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($remaining_outstanding as $r): ?>
                    <tr>
                        <td><div class="pp-item-name"><?= htmlspecialchars($r->item_name ?? '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($r->item_code ?? ''); ?></div></td>
                        <td class="num"><?= $qty($r->qty_outstanding); ?></td>
                        <td class="num" title="<?= htmlspecialchars(sprintf(lang_line('mi_rem_stock_hint', 'Stok gudang saat ini: %s'), $qty($r->stock_available))); ?>"><?= $qty($r->qty_from_stock); ?></td>
                        <td class="num"><?= $qty($r->qty_on_pr); ?><?php $this->load->view('templates/partials/pr_doc_links', ['docs' => $pr_docs[(int) $r->mr_detail_id] ?? []]); ?></td>
                        <td class="num font-weight-bold <?= (float) $r->qty_pr > 0 ? 'text-warning' : ''; ?>"><?= $qty($r->qty_pr); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- ================= Riwayat pengeluaran ================= -->
        <div class="col-xl-7">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-history"></i></span><?= lang_line('mi2_history', 'Riwayat Pengeluaran'); ?> (<?= count($issuances ?? []); ?>)</h2></div>
                <div class="table-responsive">
                    <table class="pp-table">
                        <thead><tr>
                            <th><?= lang_line('mr2_th_issuance_no', 'No. Pengeluaran'); ?></th>
                            <th><?= lang_line('mr2_th_issued_at', 'Dikeluarkan'); ?></th>
                            <th class="num">Qty</th>
                            <th><?= lang_line('mr2_th_received', 'Diterima Produksi'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($issuances)): ?>
                            <tr><td colspan="4" class="pp-empty"><i class="fas fa-dolly"></i><?= lang_line('mi2_history_empty', 'Belum ada pengeluaran.'); ?></td></tr>
                        <?php else: foreach ($issuances as $is): ?>
                            <tr>
                                <td class="pp-item-name"><?= htmlspecialchars($is->issuance_number); ?></td>
                                <td><?= $fmt_dt($is->issued_at) ?: '-'; ?><div class="pp-item-code"><?= htmlspecialchars($is->issuer_name ?: ''); ?></div></td>
                                <td class="num"><?= (int) $is->item_count; ?> item · <?= $qty($is->issued_qty); ?></td>
                                <td><?php if ($is->status === 'Received'): ?><span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i><?= $fmt_dt($is->received_at); ?></span>
                                    <?php else: ?><span class="pp-chip pp-chip--warn"><i class="fas fa-hourglass-half"></i><?= lang_line('mr2_waiting', 'Menunggu'); ?></span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= Bukti pengeluaran ================= -->
        <div class="col-xl-5" id="miAttachments">
            <div class="pp-card">
                <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-paperclip"></i></span><span><?= lang_line('material_issuance_attachments_title', 'Bukti Pengeluaran'); ?> (<?= count($attachments); ?>)
                    <span class="d-block pp-sub"><?= lang_line('mi2_att_sub', 'Foto / scan dokumen serah terima, diupload setelah barang keluar'); ?></span></span></h2></div>
                <div class="pp-card-body">
                    <?php if ($can_edit): ?>
                    <form method="POST" action="<?= base_url('material_issuance/upload_attachment/' . $header->id); ?>" enctype="multipart/form-data" class="d-flex mb-3" style="gap:.5rem">
                        <div class="custom-file flex-grow-1">
                            <input type="file" name="attachment" class="custom-file-input" id="miFile" required>
                            <label class="custom-file-label text-truncate" for="miFile"><?= lang_line('material_issuance_lbl_choose_file', 'Pilih File'); ?></label>
                        </div>
                        <button type="submit" class="btn btn-pp"><i class="fas fa-upload"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php if (empty($attachments)): ?>
                        <div class="text-center small py-3" style="color:var(--pp-muted)"><i class="fas fa-folder-open d-block mb-2" style="font-size:1.6rem;opacity:.35"></i><?= lang_line('material_issuance_no_attachment', 'Belum ada attachment.'); ?></div>
                    <?php else: foreach ($attachments as $a): ?>
                        <div class="mi-file">
                            <span class="mi-file-ico"><i class="fas <?= preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $a->original_name) ? 'fa-image' : (preg_match('/\.pdf$/i', $a->original_name) ? 'fa-file-pdf' : 'fa-file'); ?>"></i></span>
                            <span style="min-width:0;flex:1"><span class="pp-item-name d-block text-truncate" title="<?= htmlspecialchars($a->original_name); ?>"><?= htmlspecialchars($a->original_name); ?></span>
                                <span class="pp-item-code"><?= number_format($a->file_size / 1024, 1); ?> KB · <?= $fmt_dt($a->created_at); ?></span></span>
                            <button type="button" class="btn btn-sm btn-light btn-view-attachment" data-name="<?= htmlspecialchars($a->original_name); ?>" data-url="<?= base_url('material_issuance/attachment/' . $a->id); ?>" title="<?= htmlspecialchars(lang_line('material_issuance_btn_view', 'View')); ?>"><i class="fas fa-eye"></i></button>
                            <?php if ($st !== 'Received' && $can_edit): ?>
                                <a href="<?= base_url('material_issuance/delete_attachment/' . $a->id); ?>" class="btn btn-sm btn-outline-danger mi-confirm" data-danger="1" data-text="<?= htmlspecialchars(lang_line('material_issuance_confirm_delete_att', 'Hapus attachment?')); ?>"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pp-modal" id="attachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-truncate"><i class="fas fa-file mr-2"></i><span id="attachmentName"></span></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body" id="attachmentBody" style="max-height:70vh;overflow-y:auto"></div>
        <div class="modal-footer">
            <a id="attachmentDownload" href="#" class="btn btn-pp" download><i class="fas fa-download mr-1"></i><?= lang_line('material_issuance_att_download', 'Download'); ?></a>
            <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('material_issuance_att_close', 'Close'); ?></button>
        </div>
    </div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var T = {
        yes: <?= json_encode(lang_line('pp_sw_yes', 'Ya')); ?>, cancel: <?= json_encode(lang_line('pp_sw_cancel', 'Batal')); ?>,
        wait: <?= json_encode(lang_line('pp_sw_please_wait', 'Mohon tunggu...')); ?>,
        bar: <?= json_encode(lang_line('mi2_bar', '<b>%1$d</b> item akan dikeluarkan · <b>%2$d</b> item sisa menunggu')); ?>,
        none: <?= json_encode(lang_line('mi2_bar_none', 'Belum ada qty yang diisi.')); ?>,
        over: <?= json_encode(lang_line('mi2_over', 'Qty melebihi sisa atau stok (maks. %s).')); ?>,
        confirm: <?= json_encode(lang_line('material_issuance_confirm_submit', 'Yakin ingin mengkonfirmasi pengeluaran ini? Stok akan dipotong.')); ?>,
        partial: <?= json_encode(lang_line('mi2_confirm_partial', 'Sebagian item tidak dikeluarkan penuh — sisanya tetap menunggu pengeluaran berikutnya.')); ?>,
        need: <?= json_encode(lang_line('mi2_need_qty', 'Isi qty minimal untuk satu item.')); ?>,
        noPreview: <?= json_encode(lang_line('material_issuance_att_no_preview', 'Preview tidak tersedia untuk tipe file ini.')); ?>,
        processing: <?= json_encode(lang_line('material_issuance_processing', 'Memproses...')); ?>
    };
    function num(v) { var s = String(v == null ? '' : v).replace(/\s+/g, ''); if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) s = s.replace(/\./g, ''); var n = parseFloat(s.replace(/,/g, '.')); return isNaN(n) ? 0 : n; }
    function fmt(n) { return (Math.round((parseFloat(n) || 0) * 10000) / 10000).toLocaleString('id-ID', { maximumFractionDigits: 4 }); }

    function refresh() {
        var go = 0, partial = 0, bad = 0;
        $('.qty-issued').each(function () {
            var q = num(this.value), max = parseFloat($(this).data('max')) || 0, rem = parseFloat($(this).data('remaining')) || 0;
            var invalid = q < 0 || q > max + 0.00001;
            $(this).toggleClass('is-invalid', invalid).attr('title', invalid ? T.over.replace('%s', fmt(max)) : '');
            if (invalid) bad++;
            if (q > 0) go++;
            if (q + 0.00001 < rem) partial++;
        });
        $('#miBarInfo').html(go ? T.bar.replace('%1$d', go).replace('%2$d', partial) : T.none);
        $('#btnConfirmIssuance').prop('disabled', !go || bad > 0);
        return { go: go, partial: partial, bad: bad };
    }
    $('#itemIssuanceTable').on('input', '.qty-issued', refresh);
    $('#miFillMax').on('click', function () { $('.qty-issued').each(function () { var m = parseFloat($(this).data('max')) || 0; this.value = m > 0 ? +(+m).toFixed(4) : 0; }); refresh(); });
    $('#miClear').on('click', function () { $('.qty-issued').val(0); refresh(); });
    refresh();

    $('#issuanceForm').on('submit', function (e) {
        e.preventDefault();
        var r = refresh(), form = this;
        if (r.bad) return;
        if (!r.go) { Swal.fire({ icon: 'warning', text: T.need }); return; }
        $('.qty-issued').each(function () { this.value = num(this.value); });   // kirim titik desimal
        Swal.fire({ icon: 'question', text: T.confirm, html: r.partial ? '<div>' + T.confirm + '</div><small class="text-muted">' + T.partial + '</small>' : undefined,
            showCancelButton: true, confirmButtonText: T.yes, cancelButtonText: T.cancel })
        .then(function (res) {
            if (!res.isConfirmed) return;
            $('#btnConfirmIssuance').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + T.processing);
            form.submit();
        });
    });

    $(document).on('click', '.mi-confirm', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        Swal.fire({ icon: 'warning', text: $(this).data('text') || '', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: T.yes, cancelButtonText: T.cancel })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: T.wait, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });
    $('.mi-pr-form').on('submit', function (e) {
        var f = this; e.preventDefault();
        Swal.fire({ icon: 'question', text: <?= json_encode(lang_line('mi_remaining_pr_confirm', 'Buat Purchase Request untuk semua sisa outstanding Material Request ini?')); ?>, showCancelButton: true, confirmButtonText: T.yes, cancelButtonText: T.cancel })
        .then(function (r) { if (r.isConfirmed) f.submit(); });
    });
    $('#miFile').on('change', function () { $(this).next('.custom-file-label').text(this.files.length ? this.files[0].name : ''); });

    $('.btn-view-attachment').on('click', function () {
        var name = $(this).data('name'), url = $(this).data('url'), ext = String(name).split('.').pop().toLowerCase();
        $('#attachmentName').text(name);
        $('#attachmentDownload').attr({ href: url, download: name });
        var $b = $('#attachmentBody').empty();
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].indexOf(ext) !== -1) $b.append($('<img class="rounded" style="max-width:100%;height:auto">').attr('src', url));
        else if (ext === 'pdf') $b.append($('<embed type="application/pdf" style="width:100%;height:65vh">').attr('src', url));
        else $b.html('<div class="text-center text-muted py-5"><i class="fas fa-file fa-3x mb-3"></i><p></p></div>').find('p').text(T.noPreview);
        $('#attachmentModal').modal('show');
    });
});
</script>
