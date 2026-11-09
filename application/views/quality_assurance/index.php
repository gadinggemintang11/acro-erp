<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Quality Assurance - daftar (redesign 2026-11-08, selaras halaman lain).
 * Kartu status dihitung dari SELURUH data (dulu hanya halaman aktif), link
 * paginasi mempertahankan filter, footer hanya dimuat controller.
 */
$this->load->view('production_process/_pp_style');
$cur  = $filters['status'] ?? '';
$sc   = $status_counts ?? [];
$kpis = [
    ''          => [lang_line('mi2_kpi_all', 'Semua'),                    'fa-layer-group',  'tone-brand', $sc['all'] ?? 0],
    'need_qc'   => [lang_line('qa2_kpi_need_qc', 'Perlu diperiksa'),      'fa-microscope',   'tone-amber', ($sc['Pending'] ?? 0) + ($sc['In Progress'] ?? 0)],
    'Draft'     => [lang_line('qa2_kpi_approval', 'Menunggu approval'),   'fa-user-clock',   'tone-blue',  $sc['Draft'] ?? 0],
    'Completed' => [lang_line('qa2_kpi_done', 'Selesai (masuk stok)'),    'fa-check-double', 'tone-green', $sc['Completed'] ?? 0],
];
$pill = ['Pending' => 'pp-status--other', 'In Progress' => 'pp-status--progress', 'Draft' => 'pp-status--draft', 'Completed' => 'pp-status--completed', 'Rejected' => 'pp-status--other'];
$qty  = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };
$base_q = array_filter(['status' => $cur, 'qa_status' => $filters['qa_status'] ?? '', 'search' => $filters['search'] ?? '']);
$page_url = function ($p) use ($base_q) { return base_url('quality_assurance') . '?' . http_build_query($base_q + ['page' => $p]); };
$export_params = http_build_query(array_filter(['status' => $cur === 'need_qc' ? '' : $cur, 'qa_status' => $filters['qa_status'] ?? '', 'search' => $filters['search'] ?? '']));
?>
<div class="container-fluid pp2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-microscope mr-2" style="color:var(--brand-1)"></i><?= lang_line('quality_assurance_title', 'Quality Assurance'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('qa2_list_sub', 'Pemeriksaan material masuk dari GRN — hanya qty PASS yang masuk stok gudang setelah di-approve.'); ?></div>
        </div>
        <div class="d-flex flex-wrap" style="gap:.4rem">
            <a href="<?= base_url('quality_assurance/export_list_excel?' . $export_params); ?>" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
            <a href="<?= base_url('quality_assurance/export_list_pdf?' . $export_params); ?>" class="btn btn-light" target="_blank"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
            <a href="<?= base_url('warehouse_in'); ?>" class="btn btn-light"><i class="fas fa-truck mr-1"></i><?= lang_line('qa2_btn_grn', 'Penerimaan (GRN)'); ?></a>
        </div>
    </div>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k => $kp): $q = array_filter(['search' => $filters['search'] ?? '', 'status' => $k]); ?>
            <a href="<?= base_url('quality_assurance') . ($q ? '?' . http_build_query($q) : ''); ?>" class="pp-kpi <?= $kp[2]; ?><?= $cur === $k ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $kp[1]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $kp[3]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($kp[0]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="GET" action="<?= base_url('quality_assurance'); ?>" class="pp-toolbar w-100">
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('quality_assurance_search_placeholder', 'Cari no. QA / GRN / supplier')); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <select name="status" class="form-control" style="max-width:180px">
                    <option value=""><?= lang_line('quality_assurance_all_status', '-- Semua Status --'); ?></option>
                    <?php foreach ($header_statuses as $hs): ?><option value="<?= $hs; ?>" <?= $cur === $hs ? 'selected' : ''; ?>><?= $hs; ?></option><?php endforeach; ?>
                </select>
                <select name="qa_status" class="form-control" style="max-width:190px" title="<?= htmlspecialchars(lang_line('quality_assurance_qa_status', 'QA Status (GRN)')); ?>">
                    <option value=""><?= lang_line('quality_assurance_all_qa_status', '-- Semua Hasil QA --'); ?></option>
                    <?php foreach ($qa_statuses as $qs): ?><option value="<?= $qs; ?>" <?= ($filters['qa_status'] ?? '') === $qs ? 'selected' : ''; ?>><?= $qs; ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                <?php if ($base_q): ?><a href="<?= base_url('quality_assurance'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('quality_assurance_table_qa_number', 'QA #'); ?></th>
                    <th><?= lang_line('qa2_th_grn_supplier', 'GRN / Supplier'); ?></th>
                    <th><?= lang_line('quality_assurance_table_items', 'Items'); ?></th>
                    <th><?= lang_line('qa2_th_result', 'Hasil (Pass / NG)'); ?></th>
                    <th><?= lang_line('quality_assurance_table_status', 'Status'); ?></th>
                    <th class="text-right"><?= lang_line('quality_assurance_table_action', 'Action'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (empty($qa_headers)): ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-microscope"></i><?= lang_line('quality_assurance_no_data', 'No Quality Assurance data'); ?></td></tr>
                <?php else: foreach ($qa_headers as $qa):
                    $tot = (int) ($qa->total_items ?? 0); $checked = (int) ($qa->pass_items ?? 0) + (int) ($qa->reject_items ?? 0);
                    $todo = in_array($qa->status, ['Pending', 'In Progress', 'Draft'], true); ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('quality_assurance/detail/' . $qa->id); ?>" class="pp-list-title"><?= htmlspecialchars($qa->qa_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($qa->qa_date)); ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($qa->grn_number); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($qa->nama_supplier ?? '-'); ?></div>
                        </td>
                        <td style="min-width:130px">
                            <div style="font-size:.76rem"><?= min($checked, $tot); ?>/<?= $tot; ?> <?= lang_line('qa2_checked_short', 'diperiksa'); ?></div>
                            <div style="height:6px;border-radius:99px;background:var(--pp-line);overflow:hidden;margin-top:.25rem"><span style="display:block;height:100%;width:<?= $tot ? min(100, round($checked / $tot * 100)) : 0; ?>%;background:#3b82f6"></span></div>
                        </td>
                        <td>
                            <span class="pp-chip pp-chip--ok"><i class="fas fa-check"></i><?= (int) ($qa->pass_items ?? 0); ?> · <?= $qty($qa->pass_qty ?? 0); ?></span>
                            <?php if ((float) ($qa->reject_qty ?? 0) > 0): ?><span class="pp-chip pp-chip--warn"><i class="fas fa-times"></i><?= (int) ($qa->reject_items ?? 0); ?> · <?= $qty($qa->reject_qty); ?></span><?php endif; ?>
                        </td>
                        <td><span class="pp-status <?= $pill[$qa->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($qa->status); ?></span></td>
                        <td class="text-right">
                            <a href="<?= base_url('quality_assurance/detail/' . $qa->id); ?>" class="btn btn-sm <?= $todo ? 'btn-pp' : 'btn-light'; ?>">
                                <?php if ($qa->status === 'Draft'): ?><i class="fas fa-user-check mr-1"></i><?= lang_line('qa2_btn_review', 'Review'); ?>
                                <?php elseif ($todo): ?><i class="fas fa-microscope mr-1"></i><?= lang_line('qa2_btn_check', 'Periksa'); ?>
                                <?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('quality_assurance_detail_view', 'Detail'); ?><?php endif; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><?= number_format((int) $total); ?> QA</span>
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($current_page > 1): ?><li class="page-item"><a class="page-link" href="<?= $page_url($current_page - 1); ?>">&laquo;</a></li><?php endif; ?>
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : ''; ?>"><a class="page-link" href="<?= $page_url($i); ?>"><?= $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($current_page < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= $page_url($current_page + 1); ?>">&raquo;</a></li><?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
