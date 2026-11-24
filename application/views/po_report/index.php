<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Laporan PO -- daftar. Redesign 2026-11-09 (user: "ini juga"), gaya .pp2.
 * Ringkasan = rekap hasil filter (Po_report::index), penerimaan = GRN/SGR
 * Completed + Asset Register (Po_report_model::get_all). Dulu: filter status
 * "Open" (status itu tidak ada), PO Asset/Jasa tampil sebagai "Produksi",
 * kartu ringkasan menghitung ganda, tanpa paginasi.
 */
$this->load->view('purchase_order/_po_style');

$f = array_merge(['search' => '', 'supplier_id' => '', 'status' => '', 'receipt_status' => '', 'type' => '', 'date_from' => '', 'date_to' => ''], array_map(function ($v) { return (string) $v; }, $filters ?? []));
$sm = $summary ?? [];
$q = function (array $over = []) use ($f) {
    $p = array_filter(array_merge($f, $over), function ($v) { return $v !== '' && $v !== null; });
    return $p ? '?' . http_build_query($p) : '';
};
$filtered = (bool) array_filter($f, function ($v) { return $v !== ''; });
$type_meta = [
    'produksi'     => [lang_line('pod2_type_produksi', 'Material Produksi'), 'tone-brand'],
    'non_produksi' => [lang_line('pod2_type_general', 'General'),            'tone-slate'],
    'jasa'         => [lang_line('pod2_type_jasa', 'Jasa'),                  'tone-blue'],
    'asset'        => [lang_line('pod2_type_asset', 'Asset'),                'tone-amber'],
];
$statuses = ['Draft', 'Submitted', 'Confirmed', 'Closed', 'Rejected', 'Cancelled'];
$pill = ['Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved Head Dept' => 'pp-status--progress', 'Endorsed' => 'pp-status--progress',
         'Confirmed' => 'pp-status--completed', 'Closed' => 'pp-status--other', 'Cancelled' => 'pod-status--bad', 'Rejected' => 'pod-status--bad'];
$recv_meta = [
    'Not Received' => [lang_line('pst2_recv_none', 'Belum diterima'), 'pp-chip'],
    'Partial'      => [lang_line('pst2_recv_partial', 'Sebagian'),    'pp-chip pp-chip--warn'],
    'Complete'     => [lang_line('pst2_recv_complete', 'Lengkap'),    'pp-chip pp-chip--ok'],
];
$qty = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$kpis = [
    ['',             lang_line('por2_kpi_total', 'Total PO'),             'fa-file-invoice',  'tone-brand', number_format((int) ($sm['count'] ?? 0)), 'IDR ' . number_format((float) ($sm['value'] ?? 0), 0, '.', ',')],
    ['Not Received', lang_line('pst2_recv_none', 'Belum diterima'),       'fa-hourglass-half','tone-amber', number_format((int) ($sm['Not Received'] ?? 0)), lang_line('por2_kpi_of_approved', 'dari PO disetujui')],
    ['Partial',      lang_line('por2_kpi_partial', 'Diterima sebagian'),  'fa-boxes',         'tone-blue',  number_format((int) ($sm['Partial'] ?? 0)), lang_line('por2_kpi_of_approved', 'dari PO disetujui')],
    ['Complete',     lang_line('por2_kpi_complete', 'Diterima lengkap'),  'fa-check-double',  'tone-green', number_format((int) ($sm['Complete'] ?? 0)), lang_line('por2_kpi_of_approved', 'dari PO disetujui')],
    [null,           lang_line('por2_kpi_lead', 'Rata-rata waktu terima'),'fa-stopwatch',     'tone-slate', $sm['lead_avg'] === null ? '—' : sprintf(lang_line('pst2_age_days', '%d hari'), $sm['lead_avg']), lang_line('por2_kpi_lead_hint', 'tanggal PO → penerimaan pertama')],
];
?>
<style>
.por2 .pp-kpis { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.por2 .pp-kpi-sub { font-size: .7rem; color: var(--pp-muted); margin-top: .15rem; }
.por2 .pp-kpi.is-static { cursor: default; } .por2 .pp-kpi.is-static:hover { transform: none; border-color: var(--pp-line); }
.por2 .por-filter { display: grid; grid-template-columns: minmax(0, 2fr) repeat(3, minmax(0, 1fr)); gap: .6rem; }
.por2 .por-filter-2 { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; margin-top: .6rem; }
.por2 .por-date { display: flex; align-items: center; gap: .4rem; }
.por2 .por-date .form-control { width: 160px; }
.por2 .pst-recv { min-width: 170px; } .por2 .pst-recv .pod-prog { width: 100%; margin-left: 0; margin-top: .4rem; }
.por2 .pst-age { font-weight: 700; font-size: .84rem; white-space: nowrap; }
.por2 .pagination .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--pp-line); color: var(--pp-text); }
.por2 .pagination .page-item.active .page-link { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
@media (max-width: 1199.98px) { .por2 .pp-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); } .por2 .por-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575.98px) { .por2 .pp-kpis, .por2 .por-filter { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid pp2 pod2 por2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-clipboard-list mr-2" style="color:var(--brand-1)"></i><?= htmlspecialchars($title ?? 'Laporan PO'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('por2_sub', 'Rekap Purchase Order: status, penerimaan barang, nilai, dan waktu tunggu.'); ?></div>
        </div>
    </div>

    <div class="pp-card">
        <div class="pp-card-body">
            <form method="get" action="<?= base_url('po_report'); ?>">
                <div class="por-filter">
                    <div class="pp-search"><i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pst2_search_ph', 'Cari no. PO / PR / supplier')); ?>" value="<?= htmlspecialchars($f['search']); ?>">
                    </div>
                    <select name="type" class="form-control">
                        <option value=""><?= lang_line('por2_all_types', '-- Semua Tipe --'); ?></option>
                        <?php foreach ($type_meta as $tk => $tv): ?><option value="<?= $tk; ?>" <?= $f['type'] === $tk ? 'selected' : ''; ?>><?= htmlspecialchars($tv[0]); ?></option><?php endforeach; ?>
                    </select>
                    <select name="status" class="form-control">
                        <option value=""><?= lang_line('poa_all_status', '-- Semua Status --'); ?></option>
                        <?php foreach ($statuses as $s): ?><option value="<?= $s; ?>" <?= $f['status'] === $s ? 'selected' : ''; ?>><?= $s; ?></option><?php endforeach; ?>
                    </select>
                    <select name="receipt_status" class="form-control">
                        <option value=""><?= lang_line('pst2_opt_all_receipt', '-- Semua Penerimaan --'); ?></option>
                        <?php foreach ($recv_meta as $rk => $rv): ?><option value="<?= $rk; ?>" <?= $f['receipt_status'] === $rk ? 'selected' : ''; ?>><?= htmlspecialchars($rv[0]); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="por-filter-2">
                    <select name="supplier_id" class="form-control" style="max-width:260px">
                        <option value=""><?= lang_line('pdb_opt_all_supplier', '-- Semua Supplier --'); ?></option>
                        <?php foreach (($suppliers ?? []) as $s): ?><option value="<?= (int) $s->id; ?>" <?= $f['supplier_id'] === (string) $s->id ? 'selected' : ''; ?>><?= htmlspecialchars($s->nama_supplier); ?></option><?php endforeach; ?>
                    </select>
                    <div class="por-date">
                        <span class="pp-label mb-0"><?= lang_line('por2_po_date', 'Tanggal PO'); ?></span>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($f['date_from']); ?>" title="<?= htmlspecialchars(lang_line('por2_date_from', 'Dari')); ?>">
                        <span class="text-muted">–</span>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($f['date_to']); ?>" title="<?= htmlspecialchars(lang_line('por2_date_to', 'Sampai')); ?>">
                    </div>
                    <div class="ml-auto d-flex" style="gap:.4rem">
                        <?php if ($filtered): ?><a href="<?= base_url('po_report'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
                        <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k):
            $static = $k[0] === null;
            $active = !$static && $k[0] !== '' && $f['receipt_status'] === $k[0];
            $href = $static ? null : base_url('po_report') . $q(['receipt_status' => $k[0], 'page' => null]); ?>
            <<?= $static ? 'div' : 'a href="' . $href . '"'; ?> class="pp-kpi <?= $k[3]; ?><?= $active ? ' is-active' : ''; ?><?= $static ? ' is-static' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $k[2]; ?>"></i></span>
                <span style="min-width:0"><span class="pp-kpi-num d-block"><?= $k[4]; ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($k[1]); ?></span><span class="pp-kpi-sub d-block"><?= htmlspecialchars($k[5]); ?></span></span>
            </<?= $static ? 'div' : 'a'; ?>>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('pst2_th_po', 'No. PO'); ?></th>
                    <th><?= lang_line('pdb_th_type', 'Tipe'); ?></th>
                    <th><?= lang_line('pdb_th_supplier', 'Supplier'); ?></th>
                    <th><?= lang_line('pdb_th_status', 'Status'); ?></th>
                    <th><?= lang_line('pst2_th_receipt', 'Penerimaan'); ?></th>
                    <th><?= lang_line('pst2_th_age', 'Waktu'); ?></th>
                    <th class="num"><?= lang_line('pst2_th_value', 'Nilai (IDR)'); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($pos)): ?>
                    <tr><td colspan="8" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('po_report_no_data', 'Tidak ada data PO ditemukan.'); ?></td></tr>
                <?php else: foreach ($pos as $p):
                    $tm   = $type_meta[$p->type] ?? [$p->type, 'tone-slate'];
                    $url  = base_url('po_report/detail/' . $p->id);
                    $appr = in_array($p->status, ['Confirmed', 'Closed'], true);
                    $pct  = (float) $p->total_po_qty > 0 ? min(100, round((float) $p->qty_received_capped / (float) $p->total_po_qty * 100)) : 0;
                    $rm   = $recv_meta[$p->receipt_status] ?? [$p->receipt_status, 'pp-chip'];
                    $days = (int) $p->days_to_warehouse_in; ?>
                    <tr>
                        <td><a href="<?= $url; ?>" class="pp-list-title"><?= htmlspecialchars($p->po_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= $p->po_date ? date('d M Y', strtotime($p->po_date)) : '-'; ?></div></td>
                        <td><span class="pp-tag <?= $tm[1]; ?>"><?= htmlspecialchars($tm[0]); ?></span></td>
                        <td><div class="font-weight-bold" style="font-size:.84rem"><?= htmlspecialchars($p->nama_supplier ?: '-'); ?></div>
                            <?php if (!empty($p->pr_number)): ?><div class="pp-item-code"><i class="fas fa-file-import mr-1"></i><?= htmlspecialchars($p->pr_number); ?><?= !empty($p->requester_name) ? ' · ' . htmlspecialchars($p->requester_name) : ''; ?></div><?php endif; ?></td>
                        <td><span class="pp-status <?= $pill[$p->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($p->status); ?></span></td>
                        <td class="pst-recv">
                            <?php if ($appr || (float) $p->total_qty_received > 0 || (float) $p->qty_pending > 0): ?>
                                <span class="<?= $rm[1]; ?>"><?= htmlspecialchars($rm[0]); ?></span>
                                <span class="pp-item-code ml-1"><?= sprintf(lang_line('pod2_lines_complete', '%d/%d baris lengkap'), (int) $p->n_lines_full, (int) $p->n_lines); ?></span>
                                <div class="pod-prog<?= $pct > 0 && $pct < 100 ? ' is-partial' : ''; ?>"><span style="width:<?= $pct; ?>%"></span></div>
                                <div class="pp-item-code"><?= sprintf(lang_line('por2_docs', '%d dokumen penerimaan'), (int) $p->num_grns); ?><?= (float) $p->qty_pending > 0 ? ' · +' . $qty($p->qty_pending) . ' draft' : ''; ?></div>
                            <?php else: ?><span class="pp-item-code"><?= lang_line('pst2_recv_after_approve', 'Setelah PO disetujui'); ?></span><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($p->first_grn_date)): ?><div class="pst-age"><?= sprintf(lang_line('pst2_age_first', 'H+%d'), $days); ?></div><div class="pp-item-code"><?= lang_line('pst2_age_first_hint', 'penerimaan pertama'); ?></div>
                            <?php elseif (in_array($p->status, ['Cancelled', 'Rejected', 'Closed'], true)): ?><span class="pp-item-code">—</span>
                            <?php else: ?><div class="pst-age"><?= sprintf(lang_line('pst2_age_days', '%d hari'), $days); ?></div><div class="pp-item-code"><?= lang_line('pst2_age_hint', 'sejak tanggal PO'); ?></div><?php endif; ?>
                        </td>
                        <td class="num"><b><?= number_format((float) $p->total_value, 2, '.', ','); ?></b></td>
                        <td class="text-right"><a href="<?= $url; ?>" class="btn btn-sm btn-light" style="white-space:nowrap"><i class="fas fa-eye mr-1"></i><?= lang_line('po_report_detail', 'Detail'); ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><b><?= number_format((int) ($total ?? 0)); ?></b> PO · <?= lang_line('por2_total_value', 'nilai'); ?> <b>IDR <?= number_format((float) ($sm['value'] ?? 0), 2, '.', ','); ?></b> <small>(<?= lang_line('por2_value_hint', 'sebelum PPN'); ?>)</small></span>
            <?php if (($total_pages ?? 1) > 1): $cp = (int) $page; ?>
            <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">
                <?php if ($cp > 1): ?><li class="page-item"><a class="page-link" href="<?= base_url('po_report') . $q(['page' => $cp - 1]); ?>">&laquo;</a></li><?php endif; ?>
                <?php for ($i = max(1, $cp - 2); $i <= min($total_pages, $cp + 2); $i++): ?>
                    <li class="page-item <?= $i === $cp ? 'active' : ''; ?>"><a class="page-link" href="<?= base_url('po_report') . $q(['page' => $i]); ?>"><?= $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($cp < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= base_url('po_report') . $q(['page' => $cp + 1]); ?>">&raquo;</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>
