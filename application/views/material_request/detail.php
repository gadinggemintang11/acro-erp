<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Detail Material Request -- redesign 2026-11-08 (user: "harusnya ini juga
 * diubah karna submitnya disini, lalu untuk edit juga").
 *
 * Halaman baca + aksi dokumen. Mengubah item TIDAK lagi di sini: tombol Edit
 * membuka form yang sama dengan Buat MR (Material_request::edit/update).
 * Aksi: Edit / Submit / Hapus (Draft), Batal Submit (Submitted, pemohon),
 * Approve / Kembalikan / Tolak (Submitted, Head of Dept -- approval 1 tahap),
 * Export PDF. Hak tiap tombol dihitung di controller (can_*).
 */
$this->load->view('production_process/_pp_style');

$st = $header->status;
$fmt_dt = function ($v, $time = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($time ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$qty = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };

// ---- posisi alur ----
$level_map = ['Draft' => 0, 'Submitted' => 1, 'Approved Head Dept' => 1, 'Endorsed' => 1, 'Approved' => 2,
              'Partial Issued' => 2, 'Issued' => 3, 'Partial Received' => 3, 'Received' => 4, 'Closed' => 4, 'Rejected' => 1];
$level   = $level_map[$st] ?? 0;
$partial = in_array($st, ['Partial Issued', 'Partial Received'], true);
$steps = [
    ['label' => lang_line('mr2_step_created', 'Dibuat'),            'icon' => 'fa-pen',         'who' => $header->requester_name, 'at' => $fmt_dt($header->created_at ?? null)],
    ['label' => lang_line('mr2_step_submitted', 'Diajukan'),        'icon' => 'fa-paper-plane', 'who' => $level >= 1 ? $header->requester_name : null, 'at' => $level >= 1 ? $fmt_dt($header->requested_at) : null],
    ['label' => lang_line('mr2_step_approved', 'Disetujui'),        'icon' => 'fa-check',       'who' => $header->hod_approver_name ?: $header->final_approver_name, 'at' => $fmt_dt($header->hod_approved_at ?: $header->final_approved_at)],
    ['label' => lang_line('mr2_step_issued', 'Dikeluarkan Gudang'), 'icon' => 'fa-dolly',       'who' => $header->issuer_name,   'at' => $fmt_dt($header->issued_at)],
    ['label' => lang_line('mr2_step_received', 'Diterima Produksi'),'icon' => 'fa-industry',    'who' => $header->receiver_name, 'at' => $fmt_dt($header->received_at)],
];

// ---- item per WO + ringkasan ----
$groups = []; $n_full = 0; $n_short = 0;
foreach ($details as $d) {
    $groups[$d->wo_header_id]['wo'] = $d;
    $groups[$d->wo_header_id]['rows'][] = $d;
    if ((float) $d->issued_qty >= (float) $d->requested_qty - 0.00001) $n_full++;
    if ($level < 2 && (float) $d->stock < (float) $d->requested_qty) $n_short++;
}
$show_stock = $level < 3;   // sebelum dikeluarkan gudang, stok masih relevan
$status_cls = [
    'Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress',
    'Endorsed' => 'pp-status--progress', 'Approved' => 'pp-status--completed', 'Partial Issued' => 'pp-status--draft',
    'Issued' => 'pp-status--progress', 'Partial Received' => 'pp-status--draft', 'Received' => 'pp-status--completed',
    'Closed' => 'pp-status--other', 'Rejected' => 'pp-status--other',
][$st] ?? 'pp-status--other';
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<style>
.mr2d .mr-steps { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1.25rem; }
.mr2d .mr-step { position: relative; display: flex; gap: .65rem; padding: .8rem .9rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); min-width: 0; }
.mr2d .mr-step-dot { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; font-size: .82rem; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); }
.mr2d .mr-step.is-done .mr-step-dot { background: #10b981; color: #fff; border-color: transparent; }
.mr2d .mr-step.is-current { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12), var(--pp-shadow); }
.mr2d .mr-step.is-current .mr-step-dot { background: var(--brand-1); color: #fff; border-color: transparent; }
.mr2d .mr-step.is-partial .mr-step-dot { background: #f59e0b; color: #fff; border-color: transparent; }
.mr2d .mr-step.is-rejected { border-color: #f43f5e; }
.mr2d .mr-step.is-rejected .mr-step-dot { background: #f43f5e; color: #fff; border-color: transparent; }
.mr2d .mr-step-name { font-weight: 700; font-size: .84rem; }
.mr2d .mr-step-meta { font-size: .72rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 991.98px) { .mr2d .mr-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.mr2d tr.mr-group td { background: var(--pp-soft); font-size: .78rem; font-weight: 700; padding-top: .55rem; padding-bottom: .55rem; }
.mr2d .mr-prog { height: 5px; border-radius: 99px; background: var(--pp-line); overflow: hidden; margin-top: .3rem; width: 90px; margin-left: auto; }
.mr2d .mr-prog > span { display: block; height: 100%; background: #10b981; }
.mr2d .mr-note { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .86rem; }
.mr2d .mr-note--warn { background: rgba(245,158,11,.1); color: #92400e; border: 1px solid rgba(245,158,11,.3); }
.mr2d .mr-note--bad { background: rgba(244,63,94,.08); color: #9f1239; border: 1px solid rgba(244,63,94,.25); }
body.dark-mode .mr2d .mr-note--warn { color: #fcd34d; } body.dark-mode .mr2d .mr-note--bad { color: #fda4af; }
</style>

<div class="container-fluid pp2 mr2d">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-dolly-flatbed"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker">Material Request</div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($header->mr_number); ?> <span class="pp-status <?= $status_cls; ?>"><?= htmlspecialchars($st); ?></span></h1>
                    <div class="pp-hero-subline"><i class="far fa-calendar mr-1"></i><?= $fmt_dt($header->request_date, false); ?> <span class="mx-1">·</span><i class="fas fa-sitemap mr-1"></i><?= htmlspecialchars($dept_name); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($can_edit): ?>
                    <a href="<?= base_url('material_request/edit/' . $header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-pen mr-1"></i><?= lang_line('mr2_btn_edit', 'Edit'); ?></a>
                <?php endif; ?>
                <?php if ($can_submit): ?>
                    <a href="<?= base_url('material_request/submit_mr/' . $header->id); ?>" class="btn pp-btn-light mr-confirm<?= empty($details) ? ' disabled' : ''; ?>"
                       data-text="<?= htmlspecialchars(lang_line('material_request_confirm_submit')); ?>" data-icon="question"><i class="fas fa-paper-plane mr-1"></i>Submit</a>
                <?php endif; ?>
                <?php if ($can_approve): ?>
                    <a href="<?= base_url('material_request/approve_hod/' . $header->id); ?>" class="btn pp-btn-light mr-confirm"
                       data-text="<?= htmlspecialchars(sprintf(lang_line('mr2_confirm_approve', 'Approve Material Request %s? MR langsung masuk ke Material Issuance.'), $header->mr_number)); ?>" data-icon="question"><i class="fas fa-check mr-1"></i>Approve</a>
                    <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#mrReturnModal"><i class="fas fa-undo mr-1"></i><?= lang_line('mr2_btn_return', 'Kembalikan'); ?></button>
                    <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#mrRejectModal"><i class="fas fa-times mr-1"></i><?= lang_line('mr2_btn_reject', 'Tolak'); ?></button>
                <?php endif; ?>
                <?php if ($can_unsubmit): ?>
                    <a href="<?= base_url('material_request/unsubmit_mr/' . $header->id); ?>" class="btn pp-btn-glass mr-confirm" data-danger="1" data-icon="warning"
                       data-text="<?= htmlspecialchars(lang_line('mr2_confirm_unsubmit', 'Batal submit akan MENGHAPUS Material Request ini dari sistem (nomornya bisa dipakai lagi). Lanjutkan?')); ?>"><i class="fas fa-undo mr-1"></i><?= lang_line('material_request_cancel_submit'); ?></a>
                <?php endif; ?>
                <?php if ($can_delete): ?>
                    <a href="<?= base_url('material_request/delete_mr/' . $header->id); ?>" class="btn pp-btn-glass mr-confirm" data-danger="1" data-icon="warning"
                       data-text="<?= htmlspecialchars(sprintf(lang_line('mr2_confirm_delete', 'Hapus Material Request %s?'), $header->mr_number)); ?>" title="<?= htmlspecialchars(lang_line('btn_delete', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></a>
                <?php endif; ?>
                <a href="<?= base_url('material_request/export_pdf/' . $header->id); ?>" target="_blank" class="btn pp-btn-glass"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="<?= base_url('material_request'); ?>" class="btn pp-btn-glass"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('material_request_back'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mr2_applicant', 'Pemohon'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->requester_name ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mr2_approver', 'Disetujui (Head of Dept)'); ?></div><div class="pp-meta-value"><?= htmlspecialchars(($header->hod_approver_name ?: $header->final_approver_name) ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mr2_sum_items', 'Item'); ?> / Work Order</div><div class="pp-meta-value"><?= count($details); ?> <small>item</small> · <?= count($groups); ?> <small>WO</small></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('mr2_issued_progress', 'Sudah dikeluarkan penuh'); ?></div><div class="pp-meta-value"><?= $n_full; ?> <small>/ <?= count($details); ?> item</small></div></div>
            <?php if (!empty($header->remarks)): ?>
            <div class="pp-meta" style="grid-column: span 2;"><div class="pp-meta-label"><?= lang_line('material_request_remarks'); ?></div><div class="pp-meta-value" style="font-weight:600" title="<?= htmlspecialchars($header->remarks); ?>"><?= htmlspecialchars($header->remarks); ?></div></div>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if ($st === 'Rejected'): ?>
        <div class="mr-note mr-note--bad"><i class="fas fa-ban mt-1"></i><div><b><?= lang_line('mr2_rejected', 'Ditolak'); ?></b><?= $fmt_dt($header->rejected_at) ? ' · ' . $fmt_dt($header->rejected_at) : ''; ?><br><?= nl2br(htmlspecialchars((string) $header->reject_reason ?: '-')); ?><br><small><?= lang_line('mr2_rejected_hint', 'MR yang ditolak tidak bisa direvisi — buat Material Request baru.'); ?></small></div></div>
    <?php elseif ($st === 'Draft' && !empty($header->return_reason)): ?>
        <div class="mr-note mr-note--warn"><i class="fas fa-undo mt-1"></i><div><b><?= lang_line('mr2_returned', 'Dikembalikan ke Draft'); ?></b><?= $fmt_dt($header->returned_to_draft_at) ? ' · ' . $fmt_dt($header->returned_to_draft_at) : ''; ?><br><?= nl2br(htmlspecialchars($header->return_reason)); ?><br><small><?= lang_line('mr2_returned_hint', 'Perbaiki lewat tombol Edit, lalu Submit ulang.'); ?></small></div></div>
    <?php endif; ?>
    <?php if ($st === 'Draft' && empty($details)): ?>
        <div class="mr-note mr-note--warn"><i class="fas fa-inbox mt-1"></i><div><?= lang_line('mr2_draft_empty', 'Belum ada item — klik Edit untuk menambahkan item sebelum Submit.'); ?></div></div>
    <?php endif; ?>

    <nav class="mr-steps" aria-label="Alur Material Request">
        <?php foreach ($steps as $i => $s):
            $cls = '';
            if ($st === 'Rejected' && $i === 2) $cls = 'is-rejected';
            elseif ($i <= $level) $cls = 'is-done';
            elseif ($i === $level + 1 && $partial) $cls = 'is-partial';
            elseif ($i === $level + 1 && $st !== 'Rejected') $cls = 'is-current';
            $label = ($st === 'Rejected' && $i === 2) ? lang_line('mr2_rejected', 'Ditolak') : $s['label'];
        ?>
        <div class="mr-step <?= $cls; ?>">
            <span class="mr-step-dot"><i class="fas <?= $cls === 'is-done' ? 'fa-check' : ($cls === 'is-rejected' ? 'fa-times' : $s['icon']); ?>"></i></span>
            <span style="min-width:0">
                <span class="mr-step-name d-block"><?= htmlspecialchars($label); ?><?= $cls === 'is-partial' ? ' <small class="text-warning">(' . lang_line('mr2_partial', 'sebagian') . ')</small>' : ''; ?></span>
                <span class="mr-step-meta d-block" title="<?= htmlspecialchars(trim(($s['who'] ?? '') . ' ' . ($s['at'] ?? ''))); ?>">
                    <?php if ($cls === 'is-done' || $cls === 'is-partial' || $cls === 'is-rejected'): ?>
                        <?= htmlspecialchars($s['who'] ?: '-'); ?><?= $s['at'] ? ' · ' . $s['at'] : ''; ?>
                    <?php elseif ($cls === 'is-current'): ?>
                        <?= lang_line('mr2_waiting', 'Menunggu'); ?>
                    <?php else: ?>—<?php endif; ?>
                </span>
            </span>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-boxes"></i></span><span><?= lang_line('material_request_items'); ?> (<?= count($details); ?>)
                <span class="d-block pp-sub"><?= $show_stock ? lang_line('mr2_items_sub_stock', 'Stok gudang saat ini ditampilkan untuk memperkirakan ketersediaan') : lang_line('mr2_items_sub_issue', 'Qty diminta vs sudah dikeluarkan gudang'); ?></span></span></h2>
            <?php if ($n_short): ?><span class="pp-chip pp-chip--warn"><i class="fas fa-exclamation-triangle"></i><?= sprintf(lang_line('mr2_short_count', '%d item stoknya kurang'), $n_short); ?></span><?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('material_request_table_name'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_requested_qty', 'Diminta'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_issued', 'Dikeluarkan'); ?></th>
                    <th class="num"><?= lang_line('mr2_th_outstanding', 'Sisa'); ?></th>
                    <?php if ($show_stock): ?><th class="num"><?= lang_line('material_request_table_stock'); ?></th><?php endif; ?>
                    <th><?= lang_line('material_request_table_notes'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (empty($details)): ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('material_request_no_items'); ?></td></tr>
                <?php else: foreach ($groups as $g): $w = $g['wo']; ?>
                    <tr class="mr-group"><td colspan="6"><i class="fas fa-clipboard-check mr-1"></i><?= htmlspecialchars($w->wo_number ?: '-'); ?>
                        <span class="font-weight-normal" style="color:var(--pp-muted)">· <?= htmlspecialchars(trim(($w->sc_number ? $w->sc_number . ' · ' : '') . ($w->style_name ?? ''))); ?> · <?= count($g['rows']); ?> item</span></td></tr>
                    <?php foreach ($g['rows'] as $d):
                        $req = (float) $d->requested_qty; $iss = (float) $d->issued_qty; $out = max(0, $req - $iss);
                        $pct = $req > 0 ? min(100, round($iss / $req * 100)) : 0;
                        $unit = $d->unit_measure ?: $d->item_unit;
                        $short = $show_stock && (float) $d->stock < $req; ?>
                    <tr>
                        <td><div class="pp-item-name"><?= htmlspecialchars($d->item_name ?: '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($d->item_code); ?> · <?= htmlspecialchars($d->component_item_type); ?></div></td>
                        <td class="num"><b><?= $qty($req); ?></b> <span class="pp-item-code"><?= htmlspecialchars($unit); ?></span></td>
                        <td class="num"><?= $qty($iss); ?><div class="mr-prog"><span style="width:<?= $pct; ?>%"></span></div></td>
                        <td class="num<?= $out > 0 && $level >= 2 ? ' font-weight-bold text-warning' : ''; ?>"><?= $qty($out); ?></td>
                        <?php if ($show_stock): ?><td class="num<?= $short ? ' text-danger font-weight-bold' : ''; ?>"><?= $qty($d->stock); ?></td><?php endif; ?>
                        <td><span class="pp-item-code" style="font-size:.8rem"><?= htmlspecialchars($d->notes ?: '—'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($issuances)): ?>
    <div class="pp-card">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-dolly"></i></span><?= lang_line('mr2_issuances', 'Pengeluaran Gudang'); ?> (<?= count($issuances); ?>)</h2></div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('mr2_th_issuance_no', 'No. Pengeluaran'); ?></th>
                    <th><?= lang_line('mr2_th_issued_at', 'Dikeluarkan'); ?></th>
                    <th class="num"><?= lang_line('mr2_sum_items', 'Item'); ?> / Qty</th>
                    <th><?= lang_line('mr2_th_received', 'Diterima Produksi'); ?></th>
                    <th>QC</th>
                    <?php if (check_access('production_receive', 'view')): ?><th></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($issuances as $is): ?>
                    <tr>
                        <td class="pp-item-name"><?= htmlspecialchars($is->issuance_number); ?></td>
                        <td><?= $fmt_dt($is->issued_at) ?: '-'; ?><div class="pp-item-code"><?= htmlspecialchars($is->issuer_name ?: ''); ?></div></td>
                        <td class="num"><?= (int) $is->item_count; ?> item · <?= $qty($is->issued_qty); ?></td>
                        <td><?php if ($is->status === 'Received'): ?><span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i><?= $fmt_dt($is->received_at); ?></span><div class="pp-item-code"><?= htmlspecialchars($is->receiver_name ?: ''); ?></div>
                            <?php else: ?><span class="pp-chip pp-chip--warn"><i class="fas fa-hourglass-half"></i><?= lang_line('mr2_waiting', 'Menunggu'); ?></span><?php endif; ?></td>
                        <td><?= ($is->qc_status === 'QC Done') ? '<span class="pp-chip pp-chip--ok"><i class="fas fa-microscope"></i>QC Done</span>' : '<span class="pp-chip">' . htmlspecialchars($is->qc_status ?: '-') . '</span>'; ?></td>
                        <?php if (check_access('production_receive', 'view')): ?>
                        <td class="text-right"><a href="<?= base_url('production_receive/detail_issuance/' . $is->id); ?>" class="btn btn-sm btn-light"><i class="fas fa-external-link-alt"></i></a></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($can_approve): ?>
<div class="modal fade pp-modal" id="mrReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="post" action="<?= base_url('material_request/return_to_draft/' . $header->id); ?>">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= lang_line('mr2_btn_return', 'Kembalikan'); ?> · <?= htmlspecialchars($header->mr_number); ?></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p class="small text-muted"><?= lang_line('mr2_return_hint', 'MR kembali ke Draft agar pemohon bisa memperbaiki lalu submit ulang.'); ?></p>
                <label class="pp-label"><?= lang_line('mr2_reason', 'Alasan'); ?> <span class="text-danger">*</span></label>
                <textarea name="return_reason" class="form-control" rows="3" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button><button type="submit" class="btn btn-warning"><i class="fas fa-undo mr-1"></i><?= lang_line('mr2_btn_return', 'Kembalikan'); ?></button></div>
        </form>
    </div></div>
</div>
<div class="modal fade pp-modal" id="mrRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="post" action="<?= base_url('material_request/reject/' . $header->id); ?>">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= lang_line('mr2_btn_reject', 'Tolak'); ?> · <?= htmlspecialchars($header->mr_number); ?></h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p class="small text-danger"><?= lang_line('mr_approval_reject_warning', '<strong>Reject = final.</strong> MR tidak dapat di-revisi; creator harus membuat MR baru.'); ?></p>
                <label class="pp-label"><?= lang_line('mr2_reason', 'Alasan'); ?> <span class="text-danger">*</span></label>
                <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button><button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i><?= lang_line('mr2_btn_reject', 'Tolak'); ?></button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    $(document).on('click', '.mr-confirm', function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) return;
        var url = $(this).attr('href'), danger = !!$(this).data('danger');
        Swal.fire({ icon: $(this).data('icon') || 'question', text: $(this).data('text') || '', showCancelButton: true,
            confirmButtonColor: danger ? '#e11d48' : (getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155'),
            confirmButtonText: <?= json_encode(lang_line('pp_sw_yes', 'Ya')); ?>, cancelButtonText: <?= json_encode(lang_line('pp_sw_cancel', 'Batal')); ?> })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: <?= json_encode(lang_line('pp_sw_please_wait', 'Mohon tunggu...')); ?>, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });
    $('#mrReturnModal form, #mrRejectModal form').on('submit', function (e) {
        var $t = $(this).find('textarea');
        if (!$.trim($t.val())) { e.preventDefault(); $t.addClass('is-invalid').trigger('focus'); return; }
        $(this).find('button[type=submit]').prop('disabled', true);
    });
});
</script>
