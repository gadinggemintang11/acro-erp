<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * PR Monitoring -- redesign 2026-11-09 (user: "nanti halaman itu juga ya"),
 * gaya .pp2 sama dengan Status/Laporan PO.
 *
 * Kartu ringkasan & tab status dihitung Pr_monitoring::index dari semua PR
 * dalam cakupan pencarian/tipe; daftar dipaginasi. Kolom baru "Progres PO" =
 * qty PR yang sudah masuk PO aktif (bukan Rejected/Cancelled), lengkap dengan
 * tautan ke PO-nya. Fitur "Atur Kolom" Developer Mode (user 2026-09-03)
 * dipertahankan dengan kunci kolom yang sama -- tanggal, departemen & total
 * qty kini tampil sebagai baris kecil di kolom induknya dan tetap bisa
 * disembunyikan lewat kuncinya masing-masing.
 */
$this->load->view('purchase_order/_po_style');

$devmode_page_key = 'pr_monitoring';
$col_defs = [
    'number'     => lang_line('prm_th_number', 'No. PR'),
    'type'       => lang_line('prm_th_type', 'Tipe'),
    'requester'  => lang_line('prm_th_requester', 'Requester'),
    'department' => lang_line('prm_th_department', 'Departemen'),
    'date'       => lang_line('prm_th_date', 'Tanggal'),
    'items'      => lang_line('prm_th_items', 'Jml Item'),
    'total_qty'  => lang_line('prm_th_total_qty', 'Total Qty'),
    'status'     => lang_line('prm_th_status', 'Status'),
    'irf'        => lang_line('prm_th_irf', 'Asal Permintaan Barang'),
    'po_status'  => lang_line('prm2_th_po', 'Progres PO'),
];
$col = [];
foreach ($col_defs as $ck => $clabel) $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
$hide = function ($k) use ($col) { return $col[$k] ? '' : ' style="display:none"'; };
$main_cols = ['number', 'type', 'requester', 'items', 'status', 'irf', 'po_status'];
$devmode_active = devmode_is('column');
$can_view_irf   = check_access('consumable_request', 'view');

$f = ['search' => (string) ($filters['search'] ?? ''), 'type' => (string) ($filters['type'] ?? ''), 'status' => (string) ($filters['status'] ?? ''),
      'po' => (string) ($filters['po'] ?? ''), 'department' => (string) ($filters['department'] ?? '')];
$q = function (array $over = []) use ($f) {
    $p = array_filter(array_merge($f, $over), function ($v) { return $v !== '' && $v !== null; });
    return $p ? '?' . http_build_query($p) : '';
};
$filtered = (bool) array_filter($f, function ($v) { return $v !== ''; });
$sc = $status_counts ?? [];
$pc = $po_counts ?? [];
$type_meta = [
    'produksi'     => [lang_line('pod2_type_produksi', 'Material Produksi'), 'tone-brand'],
    'non_produksi' => [lang_line('pod2_type_general', 'General'),            'tone-slate'],
    'jasa'         => [lang_line('pod2_type_jasa', 'Jasa'),                  'tone-blue'],
    'asset'        => [lang_line('pod2_type_asset', 'Asset'),                'tone-amber'],
];
$po_routes = ['produksi' => 'purchase_order', 'non_produksi' => 'purchase_order_general', 'jasa' => 'purchase_order_jasa', 'asset' => 'purchase_order_asset'];
$access = [];
$can = function ($route) use (&$access) { if (!isset($access[$route])) $access[$route] = check_access($route, 'view'); return $access[$route]; };
$pill = ['Draft' => 'pp-status--draft', 'Submitted' => 'pp-status--progress', 'Approved' => 'pp-status--completed', 'Rejected' => 'pod-status--bad'];
$cover_meta = [
    'none'    => [lang_line('prm2_po_none', 'Belum PO'),   'pp-chip'],
    'partial' => [lang_line('prm2_po_partial', 'Sebagian'), 'pp-chip pp-chip--warn'],
    'full'    => [lang_line('prm2_po_full', 'Lengkap'),     'pp-chip pp-chip--ok'],
];
$kpis = [
    ['Draft',     '',     lang_line('prm2_kpi_draft', 'Draft'),                      'fa-pen',            'tone-amber', $sc['Draft'] ?? 0],
    ['Submitted', '',     lang_line('prm2_kpi_waiting', 'Menunggu approval'),         'fa-hourglass-half', 'tone-blue',  $sc['Submitted'] ?? 0],
    ['Approved',  'open', lang_line('prm2_kpi_open', 'Disetujui, belum lengkap di-PO'), 'fa-shopping-cart', 'tone-brand', ($pc['none'] ?? 0) + ($pc['partial'] ?? 0)],
    ['Approved',  'full', lang_line('prm2_kpi_full', 'Sudah di-PO lengkap'),          'fa-check-double',   'tone-green', $pc['full'] ?? 0],
];
$tabs = ['' => [lang_line('pst2_tab_all', 'Semua'), $all_count ?? 0]];
foreach (($statuses ?? []) as $s) $tabs[$s] = [$s, $sc[$s] ?? 0];
$qty = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$colspan = count($main_cols) + 1;
?>
<style>
.prm2 .pst-tabs { display: flex; flex-wrap: wrap; gap: .35rem; padding: .85rem 1.25rem 0; }
.prm2 .pst-tab { display: inline-flex; align-items: center; gap: .45rem; padding: .38rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--pp-muted); background: var(--pp-soft); border: 1px solid var(--pp-line); text-decoration: none !important; }
.prm2 .pst-tab b { font-size: .72rem; padding: .05rem .45rem; border-radius: 999px; background: var(--pp-card); color: var(--pp-text); border: 1px solid var(--pp-line); }
.prm2 .pst-tab:hover { color: var(--pp-text); border-color: var(--brand-1); }
.prm2 .pst-tab.is-active { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
.prm2 .pst-tab.is-active b { background: rgba(255,255,255,.18); color: #fff; border-color: transparent; }
.prm2 .prm-purpose { font-size: .74rem; color: var(--pp-muted); max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prm2 .prm-po { min-width: 190px; } .prm2 .prm-po .pod-prog { width: 100%; margin-left: 0; margin-top: .4rem; }
.prm2 .prm-chips { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .35rem; }
.prm2 a.pp-chip:hover { border-color: var(--brand-1); color: var(--brand-1); text-decoration: none; }
.prm2 .pagination .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--pp-line); color: var(--pp-text); }
.prm2 .pagination .page-item.active .page-link { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
</style>

<div class="container-fluid pp2 pod2 prm2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-layer-group mr-2" style="color:var(--brand-1)"></i><?= lang_line('prm_title', 'PR List (Monitoring)'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('prm2_sub', 'Pantau semua Purchase Request (Material, General, Jasa, Asset): status approval dan sejauh mana sudah dibuatkan PO.'); ?></div>
        </div>
    </div>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k): $active = $f['status'] === $k[0] && $f['po'] === $k[1]; ?>
            <a href="<?= base_url('pr_monitoring') . $q(['status' => $k[0], 'po' => $k[1], 'page' => null]); ?>" class="pp-kpi <?= $k[4]; ?><?= $active ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $k[3]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $k[5]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($k[2]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pst-tabs">
            <?php foreach ($tabs as $tk => $tv): ?>
                <a href="<?= base_url('pr_monitoring') . $q(['status' => $tk, 'po' => null, 'page' => null]); ?>" class="pst-tab<?= ($f['status'] === $tk && $f['po'] === '') ? ' is-active' : ''; ?>"><?= htmlspecialchars($tv[0]); ?> <b><?= number_format((int) $tv[1]); ?></b></a>
            <?php endforeach; ?>
        </div>
        <div class="pp-card-head" style="border-bottom:1px solid var(--pp-line)">
            <form method="GET" action="<?= base_url('pr_monitoring'); ?>" class="pp-toolbar w-100">
                <?php if ($f['status'] !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($f['status']); ?>"><?php endif; ?>
                <?php if ($f['department'] !== ''): ?><input type="hidden" name="department" value="<?= htmlspecialchars($f['department']); ?>"><?php endif; ?>
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('prm_search_placeholder', 'Cari No. PR / Keperluan...')); ?>" value="<?= htmlspecialchars($f['search']); ?>">
                </div>
                <select name="type" class="form-control" style="max-width:200px">
                    <option value=""><?= lang_line('por2_all_types', '-- Semua Tipe --'); ?></option>
                    <?php foreach ($type_meta as $tk => $tv): ?><option value="<?= $tk; ?>" <?= $f['type'] === $tk ? 'selected' : ''; ?>><?= htmlspecialchars($tv[0]); ?></option><?php endforeach; ?>
                </select>
                <select name="po" class="form-control" style="max-width:240px">
                    <option value=""><?= lang_line('prm2_all_po', '-- Semua Progres PO --'); ?></option>
                    <option value="open" <?= $f['po'] === 'open' ? 'selected' : ''; ?>><?= lang_line('prm2_po_open', 'Belum lengkap di-PO'); ?></option>
                    <?php foreach ($cover_meta as $ck => $cv): ?><option value="<?= $ck; ?>" <?= $f['po'] === $ck ? 'selected' : ''; ?>><?= htmlspecialchars($cv[0]); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                <?php if ($filtered): ?><a href="<?= base_url('pr_monitoring'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
                <?php if ($devmode_active): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-light" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>"><i class="fas fa-columns mr-1"></i><?= lang_line('devmode_col_manage_btn', 'Atur Kolom'); ?></button>
                    <div class="dropdown-menu dropdown-menu-right p-2" style="min-width:220px;" id="devmodeColMenu">
                        <div class="small font-weight-bold text-muted mb-2"><?= lang_line('devmode_col_manage_heading', 'Tampilkan kolom'); ?></div>
                        <?php foreach ($col_defs as $ck => $clabel): ?>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input devmode-col-toggle" id="devcol_<?= $ck ?>" data-page="<?= $devmode_page_key ?>" data-column="<?= $ck ?>" <?= $col[$ck] ? 'checked' : '' ?>>
                            <label class="custom-control-label small" for="devcol_<?= $ck ?>"><?= htmlspecialchars($clabel) ?></label>
                        </div>
                        <?php endforeach; ?>
                        <div class="justify-content-end mt-2 pt-2 border-top" id="devmodeColActions" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="devmodeColCancel"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                            <button type="button" class="btn btn-sm btn-primary" id="devmodeColSave"><?= lang_line('devmode_col_save', 'Simpan Perubahan'); ?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th class="devmode-col devmode-col-number"<?= $hide('number'); ?>><?= htmlspecialchars($col_defs['number']); ?></th>
                    <th class="devmode-col devmode-col-type"<?= $hide('type'); ?>><?= htmlspecialchars($col_defs['type']); ?></th>
                    <th class="devmode-col devmode-col-requester"<?= $hide('requester'); ?>><?= htmlspecialchars($col_defs['requester']); ?></th>
                    <th class="devmode-col devmode-col-items num"<?= $hide('items'); ?>><?= lang_line('prm2_th_items', 'Item'); ?></th>
                    <th class="devmode-col devmode-col-status"<?= $hide('status'); ?>><?= htmlspecialchars($col_defs['status']); ?></th>
                    <th class="devmode-col devmode-col-irf"<?= $hide('irf'); ?>><?= htmlspecialchars($col_defs['irf']); ?></th>
                    <th class="devmode-col devmode-col-po_status"<?= $hide('po_status'); ?>><?= htmlspecialchars($col_defs['po_status']); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="<?= $colspan; ?>" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('prm_no_data', 'Tidak ada data.'); ?></td></tr>
                <?php else: foreach ($records as $r):
                    $tm = $type_meta[$r->type] ?? [$r->type, 'tone-slate'];
                    $detail_route = $type_routes[$r->type] ?? null;
                    $irf_list = [];
                    foreach (array_filter(explode('|', (string) ($r->irf_refs ?? ''))) as $ref) {
                        $parts = explode(':', $ref, 2);
                        if (count($parts) === 2 && $parts[1] !== '') $irf_list[] = ['id' => (int) $parts[0], 'number' => $parts[1]];
                    }
                    $po_list = [];
                    foreach (array_filter(explode('|', (string) ($r->po_refs ?? ''))) as $ref) {
                        $parts = explode(':', $ref, 4);
                        if (count($parts) === 4) $po_list[] = ['id' => (int) $parts[0], 'number' => $parts[1], 'status' => $parts[2], 'type' => $parts[3]];
                    }
                    $cover = $r->po_cover ?? 'none';
                    $cm = $cover_meta[$cover] ?? $cover_meta['none'];
                    $need = (float) $r->total_qty; $got = (float) ($r->ordered_qty ?? 0);
                    $pct = $need > 0 ? min(100, round($got / $need * 100)) : 0; ?>
                    <tr>
                        <td class="devmode-col devmode-col-number"<?= $hide('number'); ?>>
                            <?php if ($detail_route): ?><a href="<?= base_url($detail_route . '/' . $r->id); ?>" class="pp-list-title"><?= htmlspecialchars($r->pr_number); ?></a><?php else: ?><span class="pp-list-title"><?= htmlspecialchars($r->pr_number); ?></span><?php endif; ?>
                            <div class="pp-item-code devmode-col devmode-col-date"<?= $hide('date'); ?>><i class="far fa-calendar mr-1"></i><?= $r->pr_date ? date('d M Y', strtotime($r->pr_date)) : '-'; ?></div>
                            <?php if (!empty($r->purpose)): ?><div class="prm-purpose" title="<?= htmlspecialchars($r->purpose); ?>"><?= htmlspecialchars($r->purpose); ?></div><?php endif; ?>
                        </td>
                        <td class="devmode-col devmode-col-type"<?= $hide('type'); ?>><span class="pp-tag <?= $tm[1]; ?>"><?= htmlspecialchars($tm[0]); ?></span></td>
                        <td class="devmode-col devmode-col-requester"<?= $hide('requester'); ?>>
                            <div class="font-weight-bold" style="font-size:.84rem"><?= htmlspecialchars($r->requester_name ?: '-'); ?></div>
                            <div class="pp-item-code devmode-col devmode-col-department"<?= $hide('department'); ?>><?= htmlspecialchars($r->department ?: '-'); ?></div>
                        </td>
                        <td class="devmode-col devmode-col-items num"<?= $hide('items'); ?>>
                            <b><?= (int) $r->item_count; ?></b> <small class="text-muted"><?= lang_line('prm2_lines', 'baris'); ?></small>
                            <div class="pp-item-code devmode-col devmode-col-total_qty"<?= $hide('total_qty'); ?>>qty <?= $qty($r->total_qty); ?></div>
                        </td>
                        <td class="devmode-col devmode-col-status"<?= $hide('status'); ?>><span class="pp-status <?= $pill[$r->status] ?? 'pp-status--other'; ?>"><?= htmlspecialchars($r->status); ?></span></td>
                        <td class="devmode-col devmode-col-irf"<?= $hide('irf'); ?>>
                            <?php if (!$irf_list): ?><span class="pp-item-code"><?= lang_line('prm_irf_none', 'Bukan dari Permintaan Barang'); ?></span>
                            <?php else: ?><div class="prm-chips" style="margin-top:0">
                                <?php foreach ($irf_list as $irf): ?>
                                    <?php if ($can_view_irf): ?><a href="<?= base_url('consumable_request/detail/' . $irf['id']); ?>" class="pp-chip" title="<?= htmlspecialchars(lang_line('prm_irf_open', 'Buka Permintaan Barang asal')); ?>"><?= htmlspecialchars($irf['number']); ?></a>
                                    <?php else: ?><span class="pp-chip"><?= htmlspecialchars($irf['number']); ?></span><?php endif; ?>
                                <?php endforeach; ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="devmode-col devmode-col-po_status prm-po"<?= $hide('po_status'); ?>>
                            <?php if ($r->status !== 'Approved' && !$po_list): ?>
                                <span class="pp-item-code"><?= lang_line('prm2_po_after_approve', 'Setelah PR disetujui'); ?></span>
                            <?php else: ?>
                                <span class="<?= $cm[1]; ?>"><?= htmlspecialchars($cm[0]); ?></span>
                                <span class="pp-item-code ml-1"><?= $qty($got); ?> / <?= $qty($need); ?></span>
                                <div class="pod-prog<?= $pct > 0 && $pct < 100 ? ' is-partial' : ''; ?>"><span style="width:<?= $pct; ?>%"></span></div>
                                <?php if ($po_list): ?><div class="prm-chips">
                                    <?php foreach ($po_list as $po): $rt = $po_routes[$po['type']] ?? null; $dead = in_array($po['status'], ['Rejected', 'Cancelled'], true); ?>
                                        <?php if ($rt && $can($rt)): ?><a href="<?= base_url($rt . '/detail/' . $po['id']); ?>" class="pp-chip<?= $dead ? '' : ' pp-chip--ok'; ?>" title="<?= htmlspecialchars($po['status']); ?>"<?= $dead ? ' style="text-decoration:line-through"' : ''; ?>><i class="fas fa-file-invoice"></i><?= htmlspecialchars($po['number']); ?></a>
                                        <?php else: ?><span class="pp-chip" title="<?= htmlspecialchars($po['status']); ?>"><i class="fas fa-file-invoice"></i><?= htmlspecialchars($po['number']); ?></span><?php endif; ?>
                                    <?php endforeach; ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($detail_route): ?><a href="<?= base_url($detail_route . '/' . $r->id); ?>" class="btn btn-sm btn-light" style="white-space:nowrap"><i class="fas fa-eye mr-1"></i><?= lang_line('po_report_detail', 'Detail'); ?></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><b><?= number_format((int) ($total ?? 0)); ?></b> PR<?= ($total ?? 0) != ($all_count ?? 0) ? ' ' . sprintf(lang_line('pst2_of_total', 'dari %s'), number_format((int) ($all_count ?? 0))) : ''; ?></span>
            <?php if (($total_pages ?? 1) > 1): $cp = (int) $page; ?>
            <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">
                <?php if ($cp > 1): ?><li class="page-item"><a class="page-link" href="<?= base_url('pr_monitoring') . $q(['page' => $cp - 1]); ?>">&laquo;</a></li><?php endif; ?>
                <?php for ($i = max(1, $cp - 2); $i <= min($total_pages, $cp + 2); $i++): ?>
                    <li class="page-item <?= $i === $cp ? 'active' : ''; ?>"><a class="page-link" href="<?= base_url('pr_monitoring') . $q(['page' => $i]); ?>"><?= $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($cp < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= base_url('pr_monitoring') . $q(['page' => $cp + 1]); ?>">&raquo;</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($devmode_active): ?>
<script>
// Developer Mode -- atur kolom (pola sama dengan consumable_request/index.php).
// Footer (jQuery) dimuat sesudah view ini -> ikat di DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery, pending = {};
    $('#devmodeColMenu').on('click', function (e) { e.stopPropagation(); });
    function refreshActionBar() { $('#devmodeColActions').css('display', Object.keys(pending).length ? 'flex' : 'none'); }
    $('.devmode-col-toggle').on('change', function () {
        var key = $(this).data('column'), visible = $(this).is(':checked');
        $('.devmode-col-' + key).toggle(visible);
        pending[key] = visible ? 0 : 1;
        refreshActionBar();
    });
    $('#devmodeColCancel').on('click', function () { location.reload(); });
    $('#devmodeColSave').on('click', function () {
        var keys = Object.keys(pending); if (!keys.length) return;
        var btn = $(this).prop('disabled', true), done = 0, failed = false;
        keys.forEach(function (key) {
            $.post(<?= json_encode(base_url('developer_mode/save_column')); ?>, { page_key: <?= json_encode($devmode_page_key); ?>, column_key: key, is_hidden: pending[key] }, null, 'json')
                .fail(function () { failed = true; })
                .always(function () {
                    if (++done < keys.length) return;
                    btn.prop('disabled', false);
                    if (failed) { alert(<?= json_encode(lang_line('devmode_save_err', 'Gagal menyimpan.')); ?>); return; }
                    pending = {}; refreshActionBar(); $('#devmodeColBtn').dropdown('toggle');
                });
        });
    });
});
</script>
<?php endif; ?>
