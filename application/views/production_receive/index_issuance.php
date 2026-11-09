<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Penerimaan Material Produksi -- daftar pengeluaran gudang (mr_issuances).
 * Redesign 2026-11-08: kartu status (bisa diklik), qty & NG per pengeluaran,
 * yang perlu ditindaklanjuti (belum diterima / belum QC) tampil paling atas.
 */
$this->load->view('production_process/_pp_style');
$cur  = $filters['status'] ?? '';
$kpis = [
    ''        => [lang_line('pr2_kpi_all', 'Semua'),              'fa-layer-group',   'tone-brand', (int) ($counts->total ?? 0)],
    'Issued'  => [lang_line('pr2_kpi_waiting', 'Menunggu diterima'),'fa-truck-loading','tone-amber', (int) ($counts->waiting ?? 0)],
    'need_qc' => [lang_line('pr2_kpi_need_qc', 'Perlu QC'),         'fa-microscope',    'tone-blue',  (int) ($counts->need_qc ?? 0)],
    'qc_done' => [lang_line('pr2_kpi_done', 'Selesai (QC)'),        'fa-check-double',  'tone-green', (int) ($counts->qc_done ?? 0)],
];
$qty = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">
    <div class="mb-3">
        <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-inbox mr-2" style="color:var(--brand-1)"></i><?= lang_line('production_receive_title', 'Penerimaan Material Produksi'); ?></h1>
        <div class="small" style="color:var(--pp-muted)"><?= lang_line('pr2_list_sub', 'Material yang dikeluarkan gudang: konfirmasi terima, lalu QC — qty OK masuk WIP, qty NG diretur.'); ?></div>
    </div>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp): $q = array_filter(['search' => $filters['search'] ?? '', 'status' => $k]); ?>
            <a href="<?= base_url('production_receive') . ($q ? '?' . http_build_query($q) : ''); ?>" class="pp-kpi <?= $kp[2]; ?><?= $cur === $k ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp[1]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format($kp[3]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp[0]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="GET" action="<?= base_url('production_receive'); ?>" class="pp-toolbar w-100">
                <?php if ($cur !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($cur); ?>"><?php endif; ?>
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pr2_search_ph', 'Cari no. pengeluaran, no. MR, pemohon…')); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-pp"><i class="fas fa-search mr-1"></i><?= lang_line('btn_search', 'Cari'); ?></button>
                <?php if (!empty($filters['search']) || $cur !== ''): ?>
                    <a href="<?= base_url('production_receive'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('mr2_th_issuance_no', 'No. Pengeluaran'); ?></th>
                    <th><?= lang_line('pr2_th_mr', 'Material Request'); ?></th>
                    <th class="num"><?= lang_line('pr2_th_items', 'Item / Qty'); ?></th>
                    <th><?= lang_line('pr2_th_receive', 'Penerimaan'); ?></th>
                    <th>QC</th>
                    <th class="text-right"><?= lang_line('label_action', 'Aksi'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($issuances)): foreach ($issuances as $iss):
                    $received = $iss->status === 'Received'; $qc_done = ($iss->qc_status ?? '') === 'QC Done';
                    $todo = !$received || !$qc_done; ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('production_receive/detail_issuance/' . $iss->issuance_id); ?>" class="pp-list-title"><?= htmlspecialchars($iss->issuance_number); ?></a>
                            <div class="pp-item-code"><i class="fas fa-dolly mr-1"></i><?= date('d M Y H:i', strtotime($iss->issued_at)); ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($iss->mr_number); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars(($iss->requester_name ?? '-') . ' · ' . ($dept_names[$iss->department_code ?? ''] ?? ($iss->department_code ?: '-'))); ?></div>
                        </td>
                        <td class="num"><?= (int) $iss->item_count; ?> item<div class="pp-item-code"><?= $qty($iss->issued_qty); ?></div></td>
                        <td>
                            <?php if ($received): ?><span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i><?= date('d M Y H:i', strtotime($iss->received_at)); ?></span>
                            <?php else: ?><span class="pp-status pp-status--draft"><?= lang_line('pr2_waiting_receive', 'Menunggu diterima'); ?></span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($qc_done): ?>
                                <span class="pp-chip pp-chip--ok"><i class="fas fa-microscope"></i>QC Done</span>
                                <?php if ((float) $iss->ng_qty > 0): ?><div><span class="pp-chip pp-chip--warn mt-1"><i class="fas fa-exclamation-triangle"></i>NG <?= $qty($iss->ng_qty); ?></span></div><?php endif; ?>
                            <?php elseif ($received): ?><span class="pp-status pp-status--progress"><?= lang_line('pr_badge_qc_need', 'Perlu QC'); ?></span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= base_url('production_receive/detail_issuance/' . $iss->issuance_id); ?>" class="btn btn-sm <?= $todo ? 'btn-pp' : 'btn-light'; ?>">
                                <?php if (!$received): ?><i class="fas fa-check mr-1"></i><?= lang_line('pr2_btn_receive', 'Terima'); ?>
                                <?php elseif (!$qc_done): ?><i class="fas fa-microscope mr-1"></i><?= lang_line('pr_btn_input_qc', 'Input QC'); ?>
                                <?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('pr_btn_detail', 'Detail'); ?><?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pr_no_issuance_data', 'Tidak ada data issuance'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($issuances)): ?><div class="pp-foot"><span><?= count($issuances); ?> <?= lang_line('pr2_docs', 'pengeluaran'); ?></span><span><?= lang_line('pr2_order_hint', 'Diurutkan: belum diterima → perlu QC → selesai'); ?></span></div><?php endif; ?>
    </div>
</div>
