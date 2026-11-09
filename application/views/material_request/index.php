<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Daftar Material Request -- redesign 2026-11-08 (selaras form Buat MR &
 * detail). Kartu kelompok status bisa diklik untuk filter; periode & cari
 * tetap seperti sebelumnya (default bulan berjalan).
 */
$this->load->view('production_process/_pp_style');
$cur = $filters['group'] ?? '';
$kpis = [
    ''         => [lang_line('mr2_kpi_all', 'Semua'),             'fa-layer-group',   'tone-brand', $counts['all'] ?? 0],
    'draft'    => [lang_line('mr2_kpi_draft', 'Draft'),           'fa-pencil-alt',    'tone-slate', $counts['draft'] ?? 0],
    'approval' => [lang_line('mr2_kpi_approval', 'Menunggu approval'), 'fa-hourglass-half', 'tone-amber', $counts['approval'] ?? 0],
    'process'  => [lang_line('mr2_kpi_process', 'Disetujui / di gudang'), 'fa-dolly',  'tone-blue',  $counts['process'] ?? 0],
];
$pill = ['Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress', 'Endorsed' => 'pp-status--progress',
         'Approved' => 'pp-status--completed', 'Partial Issued' => 'pp-status--progress', 'Issued' => 'pp-status--completed', 'Partial Received' => 'pp-status--progress',
         'Received' => 'pp-status--completed', 'Closed' => 'pp-status--other', 'Rejected' => 'pp-status--other'];
$keep = array_filter(['start_date' => $filters['start_date'] ?? '', 'end_date' => $filters['end_date'] ?? '', 'search' => $filters['search'] ?? '']);
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-clipboard-list mr-2" style="color:var(--brand-1)"></i><?= lang_line('sidebar_mr_list', 'Daftar Material Request'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('mr2_list_sub', 'Permintaan material produksi per Work Order — dari Draft, approval, sampai dikeluarkan gudang.'); ?></div>
        </div>
        <?php if (check_access('material_request', 'add')): ?>
            <a href="<?= base_url('material_request/add'); ?>" class="btn btn-pp shadow-sm px-3"><i class="fas fa-plus-circle mr-1"></i><?= lang_line('material_request_create'); ?></a>
        <?php endif; ?>
    </div>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp): $q = $keep + array_filter(['group' => $k]); ?>
            <a href="<?= base_url('material_request') . ($q ? '?' . http_build_query($q) : ''); ?>" class="pp-kpi <?= $kp[2]; ?><?= $cur === $k ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp[1]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $kp[3]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp[0]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="get" action="<?= base_url('material_request'); ?>" class="pp-toolbar w-100">
                <?php if ($cur !== ''): ?><input type="hidden" name="group" value="<?= htmlspecialchars($cur); ?>"><?php endif; ?>
                <div class="d-flex align-items-center" style="gap:.4rem">
                    <input type="date" name="start_date" class="form-control" style="max-width:160px" value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>" title="<?= htmlspecialchars(lang_line('from_date', 'Dari')); ?>">
                    <span style="color:var(--pp-muted)">–</span>
                    <input type="date" name="end_date" class="form-control" style="max-width:160px" value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>" title="<?= htmlspecialchars(lang_line('to_date', 'Sampai')); ?>">
                </div>
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('material_request_search_placeholder', 'Cari no. MR / pemohon')); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('common_filter', 'Filter'); ?></button>
                <a href="<?= base_url('material_request'); ?>" class="btn btn-light"><i class="fas fa-redo mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('material_request_number', 'No. MR'); ?></th>
                    <th><?= lang_line('mi2_th_dept_req', 'Departemen / Pemohon'); ?></th>
                    <th><?= lang_line('mr2_th_items_wo', 'Item / WO'); ?></th>
                    <th><?= lang_line('mi2_th_progress', 'Progres pengeluaran'); ?></th>
                    <th><?= lang_line('label_status', 'Status'); ?></th>
                    <th class="text-right"><?= lang_line('label_action', 'Aksi'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($mrs)): foreach ($mrs as $mr):
                    $lines = (int) $mr->line_count; $full = (int) $mr->full_count; $pct = $lines ? round($full / $lines * 100) : 0;
                    $after_approve = in_array($mr->status, ['Approved', 'Partial Issued', 'Issued', 'Partial Received', 'Received', 'Closed'], true);
                    $is_draft = $mr->status === 'Draft'; ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('material_request/detail/' . $mr->id); ?>" class="pp-list-title"><?= htmlspecialchars($mr->mr_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($mr->request_date)); ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($dept_names[$mr->department_code ?? ''] ?? ($mr->department_code ?: '-')); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($mr->requester_name ?? '-'); ?></div>
                        </td>
                        <td><span class="pp-chip"><i class="fas fa-box"></i><?= $lines; ?></span> <span class="pp-chip"><i class="fas fa-clipboard-check"></i><?= (int) $mr->wo_count; ?> WO</span>
                            <?php if (!empty($mr->remarks)): ?><div class="pp-item-code text-truncate mt-1" style="max-width:220px" title="<?= htmlspecialchars($mr->remarks); ?>"><?= htmlspecialchars($mr->remarks); ?></div><?php endif; ?></td>
                        <td style="min-width:150px">
                            <?php if ($after_approve && $lines): ?>
                                <div style="font-size:.76rem"><?= $full; ?>/<?= $lines; ?> item</div>
                                <div style="height:6px;border-radius:99px;background:var(--pp-line);overflow:hidden;margin-top:.25rem"><span style="display:block;height:100%;width:<?= $pct; ?>%;background:<?= $pct >= 100 ? '#10b981' : '#3b82f6'; ?>"></span></div>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="pp-status <?= $pill[$mr->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($mr->status); ?></span></td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= base_url('material_request/detail/' . $mr->id); ?>" class="btn btn-pp"><i class="fas fa-eye mr-1"></i><?= lang_line('btn_detail', 'Detail'); ?></a>
                                <?php if ($is_draft && check_access('material_request', 'edit') && can_dept_access('material_request', 'edit', $mr->department_code ?? null)): ?>
                                    <a href="<?= base_url('material_request/edit/' . $mr->id); ?>" class="btn btn-light" title="<?= htmlspecialchars(lang_line('mr2_btn_edit', 'Edit')); ?>"><i class="fas fa-pen"></i></a>
                                <?php endif; ?>
                                <?php if ($is_draft && check_access('material_request', 'delete') && can_dept_access('material_request', 'delete', $mr->department_code ?? null)): ?>
                                    <a href="<?= base_url('material_request/delete_mr/' . $mr->id); ?>" class="btn btn-outline-danger btn-delete" data-message="<?= htmlspecialchars(sprintf(lang_line('mr2_confirm_delete', 'Hapus Material Request %s?'), $mr->mr_number)); ?>" title="<?= htmlspecialchars(lang_line('btn_delete', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-clipboard"></i><?= lang_line('material_request_no_data', 'Belum ada Material Request.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($mrs)): ?>
        <div class="pp-foot"><span><?= count($mrs); ?> Material Request</span>
            <span><?= ($counts['rejected'] ?? 0) ? sprintf(lang_line('mr2_rejected_count', '%d ditolak'), $counts['rejected']) . ' · ' : ''; ?><?= ($counts['done'] ?? 0) ? sprintf(lang_line('mr2_done_count', '%d selesai diterima produksi'), $counts['done']) : ''; ?></span></div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        Swal.fire({ icon: 'warning', text: $(this).data('message') || '', showCancelButton: true, confirmButtonColor: '#e11d48',
            confirmButtonText: <?= json_encode(lang_line('btn_delete', 'Hapus')); ?>, cancelButtonText: <?= json_encode(lang_line('btn_cancel', 'Batal')); ?> })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: <?= json_encode(lang_line('pp_sw_please_wait', 'Mohon tunggu...')); ?>, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });
});
</script>
