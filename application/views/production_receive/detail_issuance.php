<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Penerimaan Produksi per pengeluaran gudang -- redesign 2026-11-08.
 * Alur: Dikeluarkan gudang -> Konfirmasi Terima (produksi) -> QC.
 * QC per item cukup Qty OK, Qty NG, Keterangan NG (wajib kalau NG > 0;
 * rincian 4 kategori + Kode NG warisan NSI sudah dihapus). Qty OK masuk WIP,
 * Qty NG diretur ke supplier lewat Warehouse Out -> Production Return.
 * Footer dimuat controller.
 */
$this->load->view('production_process/_pp_style');

$is_received = $issuance->status === 'Received';
$is_qc_done  = ($issuance->qc_status ?? '') === 'QC Done';
$can_edit    = check_access('production_receive', 'edit');
$can_confirm = check_access('production_receive', 'confirm');
$fmt_dt = function ($v) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date('d M Y H:i', strtotime($v)) : null; };
$qty    = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };

$tot_issued = 0; $tot_ok = 0; $tot_ng = 0;
foreach ($items as $it) {
    $tot_issued += (float) $it->issued_qty;
    $qr = $qc_results_map[$it->item_id . '_' . $it->item_type] ?? null;
    if ($qr) { $tot_ok += (float) $qr->accept_qty; $tot_ng += (float) $qr->reject_qty; }
}
if (!$is_received)      { $pill = ['pp-status--draft', lang_line('pr2_waiting_receive', 'Menunggu diterima')]; }
elseif (!$is_qc_done)   { $pill = ['pp-status--progress', lang_line('pr_badge_qc_need', 'Perlu QC')]; }
else                    { $pill = ['pp-status--completed', lang_line('pr_badge_qc_done', 'QC Done')]; }
$steps = [
    [lang_line('mr2_step_issued', 'Dikeluarkan Gudang'),  'fa-dolly',      true,          $people->issuer_name ?? null,   $fmt_dt($issuance->issued_at)],
    [lang_line('mr2_step_received', 'Diterima Produksi'), 'fa-inbox',      $is_received,  $people->receiver_name ?? null, $fmt_dt($issuance->received_at)],
    [lang_line('pr2_step_qc', 'QC Penerimaan'),           'fa-microscope', $is_qc_done,   $people->qc_by_name ?? null,    $fmt_dt($issuance->qc_done_at ?? null)],
];
$current = !$is_received ? 1 : (!$is_qc_done ? 2 : 3);
$show_form = $is_received && $can_edit;
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<style>
.pr2 .mr-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1.25rem; }
.pr2 .mr-step { display: flex; gap: .65rem; padding: .8rem .9rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); min-width: 0; }
.pr2 .mr-step-dot { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; font-size: .82rem; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); }
.pr2 .mr-step.is-done .mr-step-dot { background: #10b981; color: #fff; border-color: transparent; }
.pr2 .mr-step.is-current { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12), var(--pp-shadow); }
.pr2 .mr-step.is-current .mr-step-dot { background: var(--brand-1); color: #fff; border-color: transparent; }
.pr2 .mr-step-name { font-weight: 700; font-size: .84rem; }
.pr2 .mr-step-meta { font-size: .72rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 767.98px) { .pr2 .mr-steps { grid-template-columns: 1fr; } }
.pr2 .qc-in { width: 110px; text-align: right; display: inline-block; }
.pr2 .qc-notes { min-width: 200px; }
.pr2 .pr-note { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1rem; font-size: .86rem; background: rgba(59,130,246,.08); color: #1e40af; border: 1px solid rgba(59,130,246,.22); }
body.dark-mode .pr2 .pr-note { color: #93c5fd; }
.pr2 .pr-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .85rem 1.1rem; border-top: 1px solid var(--pp-line); background: var(--pp-soft); border-radius: 0 0 var(--pp-radius) var(--pp-radius); }
</style>

<div class="container-fluid pp2 pr2">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-inbox"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker"><?= lang_line('pr_production_receive_label', 'Penerimaan Produksi'); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($issuance->issuance_number); ?> <span class="pp-status <?= $pill[0]; ?>"><?= htmlspecialchars($pill[1]); ?></span></h1>
                    <div class="pp-hero-subline"><i class="fas fa-clipboard-list mr-1"></i><?= htmlspecialchars($header->mr_number); ?><span class="mx-1">·</span><i class="fas fa-sitemap mr-1"></i><?= htmlspecialchars($dept_name ?? '-'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if (!$is_received && $can_confirm): ?>
                    <a href="<?= base_url('production_receive/confirm_receive/' . $issuance->id); ?>" class="btn pp-btn-light pr-confirm" data-icon="question"
                       data-text="<?= htmlspecialchars(lang_line('pr2_confirm_receive', 'Konfirmasi material dari pengeluaran ini sudah diterima produksi? Setelah itu lakukan QC.')); ?>"><i class="fas fa-check mr-1"></i><?= lang_line('pr_btn_confirm_receive_simple', 'Konfirmasi Terima'); ?></a>
                <?php endif; ?>
                <?php if ($is_received && !$is_qc_done && $can_edit): ?>
                    <a href="<?= base_url('production_receive/unconfirm_receive/' . $issuance->id); ?>" class="btn pp-btn-glass pr-confirm" data-icon="warning" data-danger="1"
                       data-text="<?= htmlspecialchars(lang_line('pr_confirm_cancel_receive', 'Batalkan penerimaan ini?')); ?>"><i class="fas fa-undo mr-1"></i><?= lang_line('pr_btn_cancel_receive', 'Batalkan Terima'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_request', 'view')): ?>
                    <a href="<?= base_url('material_request/detail/' . $header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-clipboard-list mr-1"></i><?= lang_line('mi2_btn_view_mr', 'Lihat MR'); ?></a>
                <?php endif; ?>
                <a href="<?= base_url('production_receive'); ?>" class="btn pp-btn-glass"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('btn_back', 'Kembali'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('material_issuance_requester', 'Dibuat Oleh'); ?></div><div class="pp-meta-value"><?= htmlspecialchars($header->requester_name ?: '-'); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('pr2_items_qty', 'Item / Qty dikeluarkan'); ?></div><div class="pp-meta-value"><?= count($items); ?> <small>item</small> · <?= $qty($tot_issued); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label">Qty OK (WIP)</div><div class="pp-meta-value"><?= $is_qc_done ? $qty($tot_ok) : '—'; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label">Qty NG</div><div class="pp-meta-value"><?= $is_qc_done ? $qty($tot_ng) : '—'; ?></div></div>
        </div>
    </section>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <nav class="mr-steps">
        <?php foreach ($steps as $i => $s): $cls = $s[2] ? 'is-done' : ($i === $current ? 'is-current' : ''); ?>
        <div class="mr-step <?= $cls; ?>">
            <span class="mr-step-dot"><i class="fas <?= $s[2] ? 'fa-check' : $s[1]; ?>"></i></span>
            <span style="min-width:0"><span class="mr-step-name d-block"><?= htmlspecialchars($s[0]); ?></span>
                <span class="mr-step-meta d-block"><?= $s[2] ? htmlspecialchars(($s[3] ?: '-') . ($s[4] ? ' · ' . $s[4] : '')) : ($cls === 'is-current' ? lang_line('mr2_waiting', 'Menunggu') : '—'); ?></span></span>
        </div>
        <?php endforeach; ?>
    </nav>

    <?php if ($is_qc_done && $tot_ng > 0): ?>
        <div class="pr-note"><i class="fas fa-info-circle mt-1"></i><div><?= lang_line('pr_reject_alert_html'); ?></div></div>
    <?php endif; ?>

    <?php if (!$show_form || $is_qc_done): ?>
    <!-- ================= Ringkasan item / hasil QC ================= -->
    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-boxes"></i></span><span><?= lang_line('pr_items_received_title', 'Item Dikeluarkan dari Gudang'); ?> (<?= count($items); ?>)
                <span class="d-block pp-sub"><?= $is_qc_done ? lang_line('pr2_qc_result_sub', 'Hasil QC per item') : (!$is_received ? lang_line('pr2_receive_first', 'Konfirmasi terima dulu, lalu QC.') : ''); ?></span></span></h2>
            <?php if ($is_qc_done && $can_edit): ?>
                <button type="button" class="btn btn-light btn-sm" id="btnReQc"><i class="fas fa-redo mr-1"></i><?= lang_line('pr_btn_re_qc', 'Re-QC'); ?></button>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('pr_th_item_name', 'Nama Item'); ?></th>
                    <th class="num"><?= lang_line('pr_th_issued_qty', 'Issued Qty'); ?></th>
                    <?php if ($is_qc_done): ?>
                    <th class="num"><?= lang_line('pr_th_accept_qty', 'Qty OK'); ?></th>
                    <th class="num"><?= lang_line('pr_th_reject_qty', 'Qty NG'); ?></th>
                    <th><?= lang_line('pr_th_qc_notes', 'Keterangan NG'); ?></th>
                    <?php endif; ?>
                </tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pr_no_items', 'Tidak ada item'); ?></td></tr>
                <?php else: foreach ($items as $it): $qr = $qc_results_map[$it->item_id . '_' . $it->item_type] ?? null; ?>
                    <tr>
                        <td><div class="pp-item-name"><?= htmlspecialchars($it->item_name ?: '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($it->item_code); ?></div></td>
                        <td class="num"><b><?= $qty($it->issued_qty); ?></b> <span class="pp-item-code"><?= htmlspecialchars($it->item_unit); ?></span></td>
                        <?php if ($is_qc_done): ?>
                        <td class="num text-success font-weight-bold"><?= $qr ? $qty($qr->accept_qty) : '—'; ?></td>
                        <td class="num<?= ($qr && $qr->reject_qty > 0) ? ' text-danger font-weight-bold' : ''; ?>"><?= $qr ? $qty($qr->reject_qty) : '—'; ?></td>
                        <td><span class="pp-item-code" style="font-size:.8rem"><?= htmlspecialchars(($qr->qc_notes ?? '') ?: '—'); ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($show_form): ?>
    <!-- ================= Form QC ================= -->
    <form method="post" action="<?= base_url('production_receive/process_qc/' . $issuance->id); ?>" id="qcFormCard" <?= $is_qc_done ? 'style="display:none"' : ''; ?>>
    <div class="pp-card tone-blue">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-microscope"></i></span><span><?= $is_qc_done ? lang_line('pr_qc_form_title_re', 'Re-Process QC') : lang_line('pr_qc_form_title_new', 'Input Hasil QC'); ?>
                <span class="d-block pp-sub"><?= lang_line('pr_qc_form_subtitle', 'Qty OK + Qty NG tidak boleh melebihi Issued Qty; keterangan wajib diisi bila ada NG'); ?></span></span></h2>
            <button type="button" class="btn btn-light btn-sm" id="qcAllOk"><i class="fas fa-check-double mr-1"></i><?= lang_line('pr2_btn_all_ok', 'Semua OK'); ?></button>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('pr_th_item_name', 'Nama Item'); ?></th>
                    <th class="num"><?= lang_line('pr_th_issued_qty', 'Issued Qty'); ?></th>
                    <th class="num"><?= lang_line('pr_th_accept_qty', 'Qty OK'); ?></th>
                    <th class="num"><?= lang_line('pr_th_reject_qty', 'Qty NG'); ?></th>
                    <th><?= lang_line('pr_th_qc_notes', 'Keterangan NG'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $it):
                    $qk = $it->item_id . '_' . $it->item_type; $qr = $qc_results_map[$qk] ?? null;
                    $p_ok = $qr ? (float) $qr->accept_qty : (float) $it->issued_qty; $p_ng = $qr ? (float) $qr->reject_qty : 0; $fk = htmlspecialchars($qk); ?>
                    <tr class="qc-row" data-issued="<?= (float) $it->issued_qty; ?>">
                        <td><div class="pp-item-name"><?= htmlspecialchars($it->item_name ?: '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($it->item_code); ?></div>
                            <input type="hidden" name="items[<?= $fk; ?>][issued_qty]" value="<?= (float) $it->issued_qty; ?>"></td>
                        <td class="num"><b><?= $qty($it->issued_qty); ?></b> <span class="pp-item-code"><?= htmlspecialchars($it->item_unit); ?></span></td>
                        <td class="num"><input type="text" inputmode="decimal" class="form-control form-control-sm qc-in qc-accept" name="items[<?= $fk; ?>][accept_qty]" value="<?= +round($p_ok, 4); ?>" autocomplete="off"></td>
                        <td class="num"><input type="text" inputmode="decimal" class="form-control form-control-sm qc-in qc-reject" name="items[<?= $fk; ?>][reject_qty]" value="<?= +round($p_ng, 4); ?>" autocomplete="off"></td>
                        <td><input type="text" class="form-control form-control-sm qc-notes" maxlength="500" name="items[<?= $fk; ?>][qc_notes]"
                                   placeholder="<?= htmlspecialchars(lang_line('pr_qc_reject_notes_placeholder', 'Keterangan NG (wajib bila ada NG)…')); ?>" value="<?= htmlspecialchars($qr->qc_notes ?? ''); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pr-bar">
            <div class="small" id="qcSummary" style="color:var(--pp-muted)"></div>
            <div class="d-flex" style="gap:.5rem">
                <?php if ($is_qc_done): ?><button type="button" class="btn btn-light" id="btnHideQc"><?= lang_line('btn_cancel', 'Batal'); ?></button><?php endif; ?>
                <button type="submit" class="btn btn-pp px-4" id="btnSubmitQc"><i class="fas fa-save mr-1"></i><?= lang_line('pr_btn_save_qc', 'Simpan Hasil QC'); ?></button>
            </div>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var T = {
        exceed: <?= json_encode(lang_line('pr_js_validation_exceed', 'Qty OK + Qty NG tidak boleh melebihi Issued Qty')); ?>,
        notes:  <?= json_encode(lang_line('pr2_notes_required', 'Keterangan NG wajib diisi untuk item yang ada NG.')); ?>,
        sum:    <?= json_encode(lang_line('pr2_qc_sum', 'Total OK <b>%1$s</b> · NG <b>%2$s</b>')); ?>,
        save:   <?= json_encode(lang_line('pp_sw_save_qc_text', 'Simpan hasil QC?')); ?>,
        yes: <?= json_encode(lang_line('pp_sw_yes', 'Ya')); ?>, cancel: <?= json_encode(lang_line('pp_sw_cancel', 'Batal')); ?>,
        wait: <?= json_encode(lang_line('pp_sw_please_wait', 'Mohon tunggu...')); ?>
    };
    function num(v) { var s = String(v == null ? '' : v).replace(/\s+/g, ''); if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) s = s.replace(/\./g, ''); var n = parseFloat(s.replace(/,/g, '.')); return isNaN(n) ? 0 : n; }
    function fmt(n) { return (Math.round((parseFloat(n) || 0) * 10000) / 10000).toLocaleString('id-ID', { maximumFractionDigits: 4 }); }

    function check() {
        var ok = 0, ng = 0, bad = 0;
        $('.qc-row').each(function () {
            var $r = $(this), iss = parseFloat($r.data('issued')) || 0, a = num($r.find('.qc-accept').val()), n = num($r.find('.qc-reject').val());
            var over = a < 0 || n < 0 || a + n > iss + 0.0001;
            $r.find('.qc-accept, .qc-reject').toggleClass('is-invalid', over).attr('title', over ? T.exceed : '');
            var $nt = $r.find('.qc-notes'), needNote = n > 0 && !$.trim($nt.val());
            $nt.prop('required', n > 0).toggleClass('is-invalid', needNote && $nt.data('touched'));
            if (over) bad++;
            ok += a; ng += n;
        });
        $('#qcSummary').html(T.sum.replace('%1$s', fmt(ok)).replace('%2$s', fmt(ng)));
        return bad;
    }
    $(document).on('input', '.qc-reject', function () {
        var $r = $(this).closest('.qc-row'), iss = parseFloat($r.data('issued')) || 0;
        $r.find('.qc-accept').val(+Math.max(0, iss - num(this.value)).toFixed(4));
        check();
    });
    $(document).on('input', '.qc-accept', check);
    $(document).on('input blur', '.qc-notes', function () { $(this).data('touched', 1); check(); });
    $('#qcAllOk').on('click', function () { $('.qc-row').each(function () { $(this).find('.qc-accept').val(+(+$(this).data('issued')).toFixed(4)); $(this).find('.qc-reject').val(0); }); check(); });
    $('#btnReQc').on('click', function () { $('#qcFormCard').show()[0].scrollIntoView({ behavior: 'smooth' }); });
    $('#btnHideQc').on('click', function () { $('#qcFormCard').hide(); });
    check();

    $('#qcFormCard').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        $('.qc-notes').data('touched', 1);
        if (check()) { Swal.fire({ icon: 'warning', text: T.exceed }); return; }
        if ($('.qc-notes.is-invalid').length) { Swal.fire({ icon: 'warning', text: T.notes }); $('.qc-notes.is-invalid').first().trigger('focus'); return; }
        $('.qc-accept, .qc-reject').each(function () { this.value = num(this.value); });
        Swal.fire({ icon: 'question', text: T.save, showCancelButton: true, confirmButtonText: T.yes, cancelButtonText: T.cancel })
        .then(function (r) { if (r.isConfirmed) { $('#btnSubmitQc').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + T.wait); form.submit(); } });
    });
    $(document).on('click', '.pr-confirm', function (e) {
        e.preventDefault();
        var url = $(this).attr('href'), danger = !!$(this).data('danger');
        Swal.fire({ icon: $(this).data('icon') || 'question', text: $(this).data('text') || '', showCancelButton: true,
            confirmButtonColor: danger ? '#e11d48' : (getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155'),
            confirmButtonText: T.yes, cancelButtonText: T.cancel })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: T.wait, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });
});
</script>
