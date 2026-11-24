<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Status PO (pmpo_status & gmpo_status) -- redesign 2026-11-09 (user: "tampilan
 * ini juga ya"), gaya .pp2 sama dengan detail & approval PO.
 *
 * Data dari Po_report_model::get_status_page(): kartu ringkasan dihitung dari
 * semua PO dalam cakupan pencarian/supplier (dulu dari hasil filter, jadi kartu
 * lain jadi 0), daftar dipaginasi. Penerimaan = GRN/SGR Completed + Asset
 * Register (sama dgn detail PO). No. PO membuka halaman detail PO yang baru kalau
 * user punya aksesnya; kalau tidak, laporan detail lama ($detail_route).
 */
$this->load->view('purchase_order/_po_style');

$base     = $status_route ?? 'pmpo_status';
$mixed    = !empty($mixed_types);
$f_status = (string) ($filters['status'] ?? '');
$f_recv   = (string) ($filters['receipt_status'] ?? '');
$f_search = (string) ($filters['search'] ?? '');
$f_sup    = (string) ($filters['supplier_id'] ?? '');
$sc       = $status_counts ?? [];
$rc       = $receipt_counts ?? [];
$waiting  = ($sc['Submitted'] ?? 0) + ($sc['Approved Head Dept'] ?? 0) + ($sc['Endorsed'] ?? 0);
$delete_type = $po_type ?? 'produksi'; // gmpo_list/delete & pmpo_list/delete hanya menerima tipe induknya

$q = function (array $over = []) use ($f_status, $f_recv, $f_search, $f_sup) {
    $p = array_filter(array_merge(['search' => $f_search, 'supplier_id' => $f_sup, 'status' => $f_status, 'receipt_status' => $f_recv], $over), function ($v) { return $v !== '' && $v !== null; });
    return $p ? '?' . http_build_query($p) : '';
};
$type_meta = [
    'produksi'     => [lang_line('pod2_type_produksi', 'Material Produksi'), 'tone-brand', 'purchase_order'],
    'non_produksi' => [lang_line('pod2_type_general', 'General'),            'tone-slate', 'purchase_order_general'],
    'jasa'         => [lang_line('pod2_type_jasa', 'Jasa'),                  'tone-blue',  'purchase_order_jasa'],
    'asset'        => [lang_line('pod2_type_asset', 'Asset'),                'tone-amber', 'purchase_order_asset'],
];
$access_cache = [];
$detail_url = function ($p) use ($type_meta, &$access_cache, $detail_route) {
    $rt = $type_meta[$p->type][2] ?? null;
    if ($rt && !isset($access_cache[$rt])) $access_cache[$rt] = check_access($rt, 'view');
    return ($rt && $access_cache[$rt]) ? base_url($rt . '/detail/' . $p->id) : base_url($detail_route . '/' . $p->id);
};
$status_label = [
    'Draft' => lang_line('pdb_status_draft', 'Draft'), 'Submitted' => lang_line('pdb_status_submitted', 'Submitted'),
    'Confirmed' => lang_line('pdb_status_confirmed', 'Confirmed'), 'Closed' => lang_line('pdb_status_closed', 'Closed'),
    'Cancelled' => lang_line('pdb_status_cancelled', 'Cancelled'),
];
$pill = ['Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress', 'Endorsed' => 'pp-status--progress',
         'Confirmed' => 'pp-status--completed', 'Closed' => 'pp-status--other', 'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad'];
$recv_meta = [
    'Not Received' => [lang_line('pst2_recv_none', 'Belum diterima'), 'pp-chip'],
    'Partial'      => [lang_line('pst2_recv_partial', 'Sebagian'),    'pp-chip pp-chip--warn'],
    'Complete'     => [lang_line('pst2_recv_complete', 'Lengkap'),    'pp-chip pp-chip--ok'],
];
$kpis = [
    ['Draft',     '',          lang_line('pst2_kpi_draft', 'Draft'),                    'fa-pen',            'tone-amber', $sc['Draft'] ?? 0],
    ['waiting',   '',          lang_line('pst2_kpi_waiting', 'Menunggu approval'),       'fa-hourglass-half', 'tone-blue',  $waiting],
    ['Confirmed', 'open',      lang_line('pst2_kpi_open', 'Disetujui, barang belum lengkap'), 'fa-truck',     'tone-brand', $open_receipts ?? 0],
    ['',          'Complete',  lang_line('pst2_kpi_complete', 'Barang diterima lengkap'), 'fa-check-double', 'tone-green', $rc['Complete'] ?? 0],
];
$tabs = [
    ''          => [lang_line('pst2_tab_all', 'Semua'), $all_count ?? 0],
    'Draft'     => ['Draft', $sc['Draft'] ?? 0],
    'waiting'   => [lang_line('pst2_tab_waiting', 'Menunggu'), $waiting],
    'Confirmed' => ['Confirmed', $sc['Confirmed'] ?? 0],
    'Closed'    => ['Closed', $sc['Closed'] ?? 0],
    'Rejected'  => ['Rejected', $sc['Rejected'] ?? 0],
    'Cancelled' => ['Cancelled', $sc['Cancelled'] ?? 0],
];
$qty = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$cols = 7 + ($mixed ? 1 : 0);
?>
<style>
.pst2 .pst-tabs { display: flex; flex-wrap: wrap; gap: .35rem; padding: .85rem 1.25rem 0; }
.pst2 .pst-tab { display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--pp-muted); background: var(--pp-soft); border: 1px solid var(--pp-line); text-decoration: none !important; }
.pst2 .pst-tab b { font-size: .72rem; padding: .05rem .45rem; border-radius: 999px; background: var(--pp-card); color: var(--pp-text); border: 1px solid var(--pp-line); }
.pst2 .pst-tab:hover { color: var(--pp-text); border-color: var(--brand-1); }
.pst2 .pst-tab.is-active { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
.pst2 .pst-tab.is-active b { background: rgba(255,255,255,.18); color: #fff; border-color: transparent; }
.pst2 .pst-recv { min-width: 170px; }
.pst2 .pst-recv .pod-prog { width: 100%; margin-left: 0; margin-top: .4rem; }
.pst2 .pst-age { font-weight: 700; font-size: .84rem; white-space: nowrap; }
.pst2 .pst-age.is-late { color: #d97706; }
.pst2 .pagination .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--pp-line); color: var(--pp-text); }
.pst2 .pagination .page-item.active .page-link { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
</style>

<div class="container-fluid pp2 pod2 pst2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-tachometer-alt mr-2" style="color:var(--brand-1)"></i><?= htmlspecialchars($title ?? 'Status PO'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= $mixed ? lang_line('pst2_sub_mixed', 'Pantau status approval & penerimaan PO General, Jasa, dan Asset.') : lang_line('pst2_sub', 'Pantau status approval & penerimaan PO Material Produksi.'); ?></div>
        </div>
    </div>

    <?php foreach (['error' => 'fa-exclamation-circle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k): $active = $f_status === $k[0] && $f_recv === $k[1]; ?>
            <a href="<?= base_url($base) . $q(['status' => $k[0], 'receipt_status' => $k[1], 'page' => null]); ?>" class="pp-kpi <?= $k[4]; ?><?= $active ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $k[3]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $k[5]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($k[2]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pst-tabs">
            <?php foreach ($tabs as $tk => $tv): if ($tk !== '' && $tk !== $f_status && !$tv[1] && !in_array($tk, ['Draft', 'waiting', 'Confirmed'], true)) continue; ?>
                <a href="<?= base_url($base) . $q(['status' => $tk, 'page' => null]); ?>" class="pst-tab<?= $f_status === $tk ? ' is-active' : ''; ?>"><?= htmlspecialchars($tv[0]); ?> <b><?= number_format((int) $tv[1]); ?></b></a>
            <?php endforeach; ?>
        </div>
        <div class="pp-card-head" style="border-bottom:1px solid var(--pp-line)">
            <form method="get" action="<?= base_url($base); ?>" class="pp-toolbar w-100">
                <?php if ($f_status !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($f_status); ?>"><?php endif; ?>
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pst2_search_ph', 'Cari no. PO / PR / supplier')); ?>" value="<?= htmlspecialchars($f_search); ?>">
                </div>
                <select name="receipt_status" class="form-control" style="max-width:230px">
                    <option value=""><?= lang_line('pst2_opt_all_receipt', '-- Semua Penerimaan --'); ?></option>
                    <option value="open" <?= $f_recv === 'open' ? 'selected' : ''; ?>><?= lang_line('pst2_recv_open', 'Belum lengkap'); ?></option>
                    <?php foreach ($recv_meta as $rk => $rv): ?><option value="<?= $rk; ?>" <?= $f_recv === $rk ? 'selected' : ''; ?>><?= htmlspecialchars($rv[0]); ?></option><?php endforeach; ?>
                </select>
                <select name="supplier_id" class="form-control" style="max-width:230px">
                    <option value=""><?= lang_line('pdb_opt_all_supplier', '-- Semua Supplier --'); ?></option>
                    <?php foreach (($suppliers ?? []) as $s): ?><option value="<?= (int) $s->id; ?>" <?= $f_sup === (string) $s->id ? 'selected' : ''; ?>><?= htmlspecialchars($s->nama_supplier); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                <?php if ($f_search !== '' || $f_sup !== '' || $f_recv !== '' || $f_status !== ''): ?><a href="<?= base_url($base); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('pst2_th_po', 'No. PO'); ?></th>
                    <?php if ($mixed): ?><th><?= lang_line('pdb_th_type', 'Tipe'); ?></th><?php endif; ?>
                    <th><?= lang_line('pdb_th_supplier', 'Supplier'); ?></th>
                    <th><?= lang_line('pdb_th_status', 'Status'); ?></th>
                    <th><?= lang_line('pst2_th_receipt', 'Penerimaan'); ?></th>
                    <th><?= lang_line('pst2_th_age', 'Waktu'); ?></th>
                    <th class="num"><?= lang_line('pst2_th_value', 'Nilai (IDR)'); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($pos)): ?>
                    <tr><td colspan="<?= $cols; ?>" class="pp-empty"><i class="fas fa-inbox"></i><?= htmlspecialchars(sprintf(lang_line('pdb_no_data', 'Tidak ada data %s'), $po_type_label ?? 'PO')); ?></td></tr>
                <?php else: foreach ($pos as $p):
                    $tm    = $type_meta[$p->type] ?? ['-', 'tone-slate', null];
                    $url   = $detail_url($p);
                    $appr  = in_array($p->status, ['Confirmed', 'Closed'], true);
                    $pct   = (float) $p->total_po_qty > 0 ? min(100, round((float) $p->qty_received_capped / (float) $p->total_po_qty * 100)) : 0;
                    $rm    = $recv_meta[$p->receipt_status] ?? [$p->receipt_status, 'pp-chip'];
                    $days  = (int) $p->days_to_warehouse_in;
                    $late  = $p->status === 'Confirmed' && empty($p->first_grn_date) && $days > 14; ?>
                    <tr>
                        <td>
                            <a href="<?= $url; ?>" class="pp-list-title"><?= htmlspecialchars($p->po_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= $p->po_date ? date('d M Y', strtotime($p->po_date)) : '-'; ?></div>
                        </td>
                        <?php if ($mixed): ?><td><span class="pp-tag <?= $tm[1]; ?>"><?= htmlspecialchars($tm[0]); ?></span></td><?php endif; ?>
                        <td>
                            <div class="font-weight-bold" style="font-size:.84rem"><?= htmlspecialchars($p->nama_supplier ?: '-'); ?></div>
                            <?php if (!empty($p->pr_number)): ?><div class="pp-item-code"><i class="fas fa-file-import mr-1"></i><?= htmlspecialchars($p->pr_number); ?><?= !empty($p->requester_name) ? ' · ' . htmlspecialchars($p->requester_name) : ''; ?></div><?php endif; ?>
                        </td>
                        <td><span class="pp-status <?= $pill[$p->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($status_label[$p->status] ?? $p->status); ?></span></td>
                        <td class="pst-recv">
                            <?php if ($appr || (float) $p->total_qty_received > 0 || (float) $p->qty_pending > 0): ?>
                                <span class="<?= $rm[1]; ?>"><?= htmlspecialchars($rm[0]); ?></span>
                                <span class="pp-item-code ml-1"><?= sprintf(lang_line('pod2_lines_complete', '%d/%d baris lengkap'), (int) $p->n_lines_full, (int) $p->n_lines); ?></span>
                                <div class="pod-prog<?= $pct > 0 && $pct < 100 ? ' is-partial' : ''; ?>"><span style="width:<?= $pct; ?>%"></span></div>
                                <?php if ((float) $p->qty_pending > 0): ?><div class="pp-item-code" title="<?= htmlspecialchars(lang_line('pod2_pending_hint', 'Masih di dokumen penerimaan berstatus Draft')); ?>">+<?= $qty($p->qty_pending); ?> draft</div><?php endif; ?>
                            <?php else: ?>
                                <span class="pp-item-code"><?= lang_line('pst2_recv_after_approve', 'Setelah PO disetujui'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($p->first_grn_date)): ?>
                                <div class="pst-age"><?= sprintf(lang_line('pst2_age_first', 'H+%d'), $days); ?></div><div class="pp-item-code"><?= lang_line('pst2_age_first_hint', 'penerimaan pertama'); ?></div>
                            <?php elseif (in_array($p->status, ['Cancelled', 'Rejected', 'Closed'], true)): ?>
                                <span class="pp-item-code">—</span>
                            <?php else: ?>
                                <div class="pst-age<?= $late ? ' is-late' : ''; ?>"><?= sprintf(lang_line('pst2_age_days', '%d hari'), $days); ?></div><div class="pp-item-code"><?= lang_line('pst2_age_hint', 'sejak tanggal PO'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><b><?= number_format((float) $p->total_value, 2, '.', ','); ?></b></td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= $url; ?>" class="btn btn-light" title="<?= htmlspecialchars(lang_line('pdb_btn_detail', 'Detail')); ?>"><i class="fas fa-eye"></i></a>
                                <?php if (!empty($can_delete) && $p->status === 'Draft' && !empty($delete_route) && $p->type === $delete_type): ?>
                                    <a href="<?= base_url($delete_route . '/' . $p->id); ?>" class="btn btn-light text-danger btn-delete" title="<?= htmlspecialchars(lang_line('pdb_btn_delete_draft', 'Hapus Draft')); ?>"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('pdb_confirm_delete', 'Hapus PO %s?'), $p->po_number)); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><b><?= number_format((int) ($total ?? 0)); ?></b> PO<?= ($total ?? 0) != ($all_count ?? 0) ? ' ' . sprintf(lang_line('pst2_of_total', 'dari %s'), number_format((int) ($all_count ?? 0))) : ''; ?></span>
            <?php if (($total_pages ?? 1) > 1): $cp = (int) $page; ?>
            <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">
                <?php if ($cp > 1): ?><li class="page-item"><a class="page-link" href="<?= base_url($base) . $q(['page' => $cp - 1]); ?>">&laquo;</a></li><?php endif; ?>
                <?php for ($i = max(1, $cp - 2); $i <= min($total_pages, $cp + 2); $i++): ?>
                    <li class="page-item <?= $i === $cp ? 'active' : ''; ?>"><a class="page-link" href="<?= base_url($base) . $q(['page' => $i]); ?>"><?= $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($cp < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= base_url($base) . $q(['page' => $cp + 1]); ?>">&raquo;</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>
