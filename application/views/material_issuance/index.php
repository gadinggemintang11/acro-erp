<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Pengeluaran Material (gudang) -- daftar MR yang sudah di-approve.
 * Redesign 2026-11-08: kartu status (bisa diklik untuk filter), progres
 * pengeluaran per MR, yang masih perlu dikerjakan tampil paling atas.
 */
$this->load->view('production_process/_pp_style');
$cur  = $filters['status'] ?? '';
$wait = (int) ($status_counts['Approved'] ?? 0);
$part = (int) ($status_counts['Partial Issued'] ?? 0) + (int) ($status_counts['Partial Received'] ?? 0);
$kpis = [
    ''               => [lang_line('mi2_kpi_all', 'Semua'),                   'fa-layer-group',  'tone-brand', array_sum($status_counts)],
    'Approved'       => [lang_line('mi2_kpi_wait', 'Menunggu dikeluarkan'),   'fa-hourglass-half','tone-amber', $wait],
    'partial'        => [lang_line('mi2_kpi_partial', 'Sebagian dikeluarkan'),'fa-adjust',       'tone-blue',  $part],
    'Issued'         => [lang_line('mi2_kpi_issued', 'Sudah dikeluarkan'),    'fa-dolly',        'tone-green', (int) ($status_counts['Issued'] ?? 0)],
];
$pill = ['Approved' => 'pp-status--draft', 'Partial Issued' => 'pp-status--progress', 'Partial Received' => 'pp-status--progress',
         'Issued' => 'pp-status--completed', 'Received' => 'pp-status--other'];
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-dolly mr-2" style="color:var(--brand-1)"></i><?= lang_line('material_issuance_title', 'Pengeluaran Material'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('mi2_list_sub', 'Material Request yang sudah disetujui, siap dikeluarkan gudang ke produksi (FIFO per batch).'); ?></div>
        </div>
    </div>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp): $q = array_filter(['search' => $filters['search'] ?? '', 'status' => $k]); ?>
            <a href="<?= base_url('material_issuance') . ($q ? '?' . http_build_query($q) : ''); ?>" class="pp-kpi <?= $kp[2]; ?><?= $cur === $k ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp[1]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $kp[3]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp[0]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="get" action="<?= base_url('material_issuance'); ?>" class="pp-toolbar w-100">
                <div class="pp-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('mi2_search_ph', 'Cari no. MR, no. pengeluaran, pemohon…')); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <select name="status" class="form-control" style="max-width:210px">
                    <option value=""><?= lang_line('material_issuance_all_status', '-- Semua Status --'); ?></option>
                    <?php foreach (['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received'] as $s): ?>
                        <option value="<?= $s; ?>" <?= $cur === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-search mr-1"></i><?= lang_line('search', 'Cari'); ?></button>
                <?php if (!empty($filters['search']) || $cur !== ''): ?>
                    <a href="<?= base_url('material_issuance'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('reset', 'Reset'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('material_issuance_mr_number', 'No. Material Request'); ?></th>
                    <th><?= lang_line('mi2_th_dept_req', 'Departemen / Pemohon'); ?></th>
                    <th><?= lang_line('mi2_th_progress', 'Progres pengeluaran'); ?></th>
                    <th><?= lang_line('material_issuance_status', 'Status'); ?></th>
                    <th class="text-right"><?= lang_line('material_issuance_actions', 'Aksi'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($mrs)): foreach ($mrs as $mr):
                    $lines = (int) $mr->line_count; $full = (int) $mr->full_count;
                    $pct = $lines ? round($full / $lines * 100) : 0;
                    $todo = in_array($mr->status, ['Approved', 'Partial Issued', 'Partial Received'], true); ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('material_issuance/detail/' . $mr->id); ?>" class="pp-list-title"><?= htmlspecialchars($mr->mr_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($mr->request_date)); ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($dept_names[$mr->department_code ?? ''] ?? ($mr->department_code ?: '-')); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($mr->requester_name ?? '-'); ?></div>
                        </td>
                        <td style="min-width:180px">
                            <div class="d-flex justify-content-between" style="font-size:.76rem"><span><?= $full; ?>/<?= $lines; ?> item</span><span style="color:var(--pp-muted)"><?= (int) $mr->issuance_count; ?>× <?= lang_line('mi2_issued_short', 'keluar'); ?></span></div>
                            <div style="height:6px;border-radius:99px;background:var(--pp-line);overflow:hidden;margin-top:.25rem"><span style="display:block;height:100%;width:<?= $pct; ?>%;background:<?= $pct >= 100 ? '#10b981' : '#3b82f6'; ?>"></span></div>
                        </td>
                        <td><span class="pp-status <?= $pill[$mr->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($mr->status); ?></span></td>
                        <td class="text-right">
                            <a href="<?= base_url('material_issuance/detail/' . $mr->id); ?>" class="btn btn-sm <?= $todo ? 'btn-pp' : 'btn-light'; ?>">
                                <?php if ($todo): ?><i class="fas fa-sign-out-alt mr-1"></i><?= lang_line('mi2_btn_process', 'Keluarkan'); ?><?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('material_issuance_detail', 'Lihat Detail'); ?><?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="pp-empty"><i class="fas fa-dolly"></i><?= lang_line('material_issuance_no_data', 'Tidak ada Permintaan Barang ditemukan'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($mrs)): ?><div class="pp-foot"><span><?= count($mrs); ?> Material Request</span><span><?= lang_line('mi2_order_hint', 'Diurutkan: yang perlu dikeluarkan dulu'); ?></span></div><?php endif; ?>
    </div>
</div>
