<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Warehouse In / Receiving Material Form (GRN) -- daftar. Redesign 2026-11-09
 * (user: "ini juga nantiii"), gaya .pp2. Cari/filter/paginasi/ringkasan dari
 * Warehouse_in::index(). Edit header kini dari halaman detail (dulu tiap baris
 * merender modal edit sendiri + 1 query per baris).
 */
$this->load->view('production_process/_pp_style');

$f_search = (string) ($filters['search'] ?? '');
$f_status = (string) ($filters['status'] ?? '');
$f_base   = (string) ($filters['base_on'] ?? '');
$c        = $counts ?? [];
$q = function (array $over = []) use ($f_search, $f_status, $f_base) {
    $p = array_filter(array_merge(['search' => $f_search, 'status' => $f_status, 'base_on' => $f_base], $over), function ($v) { return $v !== '' && $v !== null; });
    return $p ? '?' . http_build_query($p) : '';
};
$filtered = $f_search !== '' || $f_status !== '' || $f_base !== '';
$can_add  = check_access('warehouse_in', 'add');
$can_del  = check_access('warehouse_in', 'delete');
$base_meta = [
    'PO'           => ['Purchase Order', 'tone-brand'],
    'MIR'          => ['MIR', 'tone-blue'],
    'Goods Return' => [lang_line('wi2_goods_return', 'Retur Supplier'), 'tone-amber'],
];
$wh_label = ['material' => 'Material', 'semifinished' => lang_line('wi2_semifinished', 'Semi-finished')];
$qa_meta = [
    'Not Started'    => [lang_line('wi2_qa_not_started', 'QA belum mulai'), 'pp-chip'],
    'In Progress'    => [lang_line('wi2_qa_progress', 'QA berjalan'),       'pp-chip pp-chip--warn'],
    'Passed'         => [lang_line('wi2_qa_passed', 'QA Pass'),             'pp-chip pp-chip--ok'],
    'Partial Passed' => [lang_line('wi2_qa_partial', 'QA Pass sebagian'),   'pp-chip pp-chip--warn'],
    'Rejected'       => [lang_line('wi2_qa_rejected', 'QA Reject'),         'pp-chip'],
];
$qty = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$kpis = [
    ['',        lang_line('sgr2_kpi_all', 'Semua dokumen'),               'fa-layer-group',  'tone-brand', $all_count ?? 0],
    ['draft',   lang_line('wi2_kpi_draft', 'Draft — belum dikonfirmasi'),  'fa-pen',          'tone-amber', $c['draft'] ?? 0],
    ['qa_wait', lang_line('wi2_kpi_qa_wait', 'Menunggu Quality Assurance'),'fa-microscope',   'tone-blue',  $c['qa_wait'] ?? 0],
    ['qa_done', lang_line('wi2_kpi_qa_done', 'QA selesai'),                'fa-check-double', 'tone-green', $c['qa_done'] ?? 0],
];
?>
<style>
.wi2 .wi-banner { display: flex; align-items: center; gap: .85rem; padding: .8rem 1rem; border-radius: 14px; margin-bottom: 1.1rem; background: rgba(245,158,11,.09); border: 1px solid rgba(245,158,11,.3); color: #92400e; font-size: .86rem; }
.wi2 .wi-banner i.big { font-size: 1.2rem; color: #f59e0b; }
body.dark-mode .wi2 .wi-banner { color: #fcd34d; }
.wi2 .wi-src { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .3rem; }
.wi2 .pagination .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--pp-line); color: var(--pp-text); }
.wi2 .pagination .page-item.active .page-link { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
</style>

<div class="container-fluid pp2 wi2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-truck-loading mr-2" style="color:var(--brand-1)"></i><?= lang_line('wi2_title', 'Receiving Material Form (GRN)'); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('wi2_sub', 'Penerimaan material produksi dari PO / MIR / retur supplier. Setelah dikonfirmasi, barang diperiksa di Quality Assurance — hanya qty PASS yang masuk stok.'); ?></div>
        </div>
        <div class="d-flex flex-wrap" style="gap:.4rem">
            <a href="<?= base_url('goods_receiving'); ?>" class="btn btn-light"><i class="fas fa-th-large mr-1"></i><?= lang_line('sgr2_all_receipts', 'Menu Pemasukan'); ?></a>
            <?php if ($can_add): ?><button class="btn btn-pp" data-toggle="modal" data-target="#addHeaderModal"><i class="fas fa-plus mr-1"></i><?= lang_line('wi2_create', 'Buat GRN'); ?></button><?php endif; ?>
        </div>
    </div>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if (!empty($po_waiting)): ?>
        <div class="wi-banner"><i class="fas fa-truck big"></i>
            <div class="flex-grow-1"><?= sprintf(lang_line('wi2_po_waiting', '<b>%d PO material</b> sudah disetujui dan barangnya belum diterima penuh.'), (int) $po_waiting); ?></div>
            <?php if ($can_add): ?><button class="btn btn-sm btn-pp" data-toggle="modal" data-target="#addHeaderModal"><i class="fas fa-plus mr-1"></i><?= lang_line('wi2_create', 'Buat GRN'); ?></button><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k): ?>
            <a href="<?= base_url('warehouse_in') . $q(['status' => $k[0], 'page' => null]); ?>" class="pp-kpi <?= $k[3]; ?><?= $f_status === $k[0] ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $k[2]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $k[4]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($k[1]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="get" action="<?= base_url('warehouse_in'); ?>" class="pp-toolbar w-100">
                <?php if ($f_status !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($f_status); ?>"><?php endif; ?>
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('wi2_search_ph', 'Cari no. GRN / PO / MIR / supplier / surat jalan')); ?>" value="<?= htmlspecialchars($f_search); ?>">
                </div>
                <select name="base_on" class="form-control" style="max-width:210px">
                    <option value=""><?= lang_line('wi2_all_base', '-- Semua Dasar --'); ?></option>
                    <?php foreach ($base_meta as $bk => $bv): ?><option value="<?= htmlspecialchars($bk); ?>" <?= $f_base === $bk ? 'selected' : ''; ?>><?= htmlspecialchars($bv[0]); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                <?php if ($filtered): ?><a href="<?= base_url('warehouse_in'); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('wi2_th_number', 'No. GRN'); ?></th>
                    <th><?= lang_line('wi2_th_base', 'Dasar'); ?></th>
                    <th><?= lang_line('sgr2_th_supplier_po', 'Supplier / Sumber'); ?></th>
                    <th class="num"><?= lang_line('sgr2_th_items', 'Item'); ?></th>
                    <th class="num"><?= lang_line('wi2_th_value', 'Nilai (IDR)'); ?></th>
                    <th><?= lang_line('warehouse_in_status', 'Status'); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($grns)): ?>
                    <tr><td colspan="7" class="pp-empty"><i class="fas fa-truck-loading"></i><?= lang_line('warehouse_in_no_data', 'Belum ada data Penerimaan Barang'); ?></td></tr>
                <?php else: foreach ($grns as $g):
                    $bm = $base_meta[$g->base_on] ?? [$g->base_on, 'tone-slate'];
                    $draft = $g->status === 'Draft';
                    $srcs = array_filter(explode('|', (string) $g->src_numbers));
                    $qm = $qa_meta[$g->qa_status] ?? null;
                    $url = base_url('warehouse_in/detail/' . $g->id); ?>
                    <tr>
                        <td>
                            <a href="<?= $url; ?>" class="pp-list-title"><?= htmlspecialchars($g->grn_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($g->grn_date)); ?><?= !empty($g->warehouse_type) ? ' · ' . htmlspecialchars($wh_label[$g->warehouse_type] ?? ucfirst($g->warehouse_type)) : ''; ?></div>
                        </td>
                        <td><span class="pp-tag <?= $bm[1]; ?>"><?= htmlspecialchars($bm[0]); ?></span></td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.84rem"><?= htmlspecialchars($g->nama_supplier ?: '-'); ?></div>
                            <?php if ($srcs): ?><div class="wi-src"><?php foreach ($srcs as $sn): ?><span class="pp-chip"><i class="fas fa-file-invoice"></i><?= htmlspecialchars($sn); ?></span><?php endforeach; ?></div><?php endif; ?>
                            <?php if (!empty($g->delivery_note_number)): ?><div class="pp-item-code"><?= lang_line('sgr2_dn', 'Surat jalan'); ?>: <?= htmlspecialchars($g->delivery_note_number); ?></div><?php endif; ?>
                        </td>
                        <td class="num"><b><?= (int) $g->item_count; ?></b> <small class="text-muted"><?= lang_line('prm2_lines', 'baris'); ?></small><div class="pp-item-code">qty <?= $qty($g->total_qty); ?></div></td>
                        <td class="num"><b><?= number_format((float) $g->total_value, 2, '.', ','); ?></b></td>
                        <td>
                            <?php if ($draft): ?><span class="pp-status pp-status--draft">Draft</span>
                            <?php else: ?><span class="pp-status pp-status--completed"><?= lang_line('wi2_confirmed', 'Dikonfirmasi'); ?></span>
                                <?php if ($qm): ?><div class="mt-1"><span class="<?= $qm[1]; ?>"><i class="fas fa-microscope"></i><?= htmlspecialchars($qm[0]); ?></span></div><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= $url; ?>" class="btn btn-sm <?= $draft ? 'btn-pp' : 'btn-light'; ?>" style="white-space:nowrap"><?php if ($draft): ?><i class="fas fa-pen mr-1"></i><?= lang_line('sgr2_continue', 'Lanjutkan'); ?><?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('po_report_detail', 'Detail'); ?><?php endif; ?></a>
                                <?php if ($draft && $can_del): ?>
                                    <a href="<?= base_url('warehouse_in/delete_header/' . $g->id); ?>" class="btn btn-sm btn-light text-danger btn-delete" title="<?= htmlspecialchars(lang_line('delete', 'Hapus')); ?>"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('sgr2_confirm_delete', 'Hapus %s?'), $g->grn_number)); ?>"
                                       data-text="<?= htmlspecialchars(lang_line('wi2_delete_hint', 'GRN hanya bisa dihapus kalau belum ada item.')); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><b><?= number_format((int) ($total ?? 0)); ?></b> GRN<?= ($total ?? 0) != ($all_count ?? 0) ? ' ' . sprintf(lang_line('pst2_of_total', 'dari %s'), number_format((int) ($all_count ?? 0))) : ''; ?></span>
            <?php if (($total_pages ?? 1) > 1): $cp = (int) $page; ?>
            <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">
                <?php if ($cp > 1): ?><li class="page-item"><a class="page-link" href="<?= base_url('warehouse_in') . $q(['page' => $cp - 1]); ?>">&laquo;</a></li><?php endif; ?>
                <?php for ($i = max(1, $cp - 2); $i <= min($total_pages, $cp + 2); $i++): ?>
                    <li class="page-item <?= $i === $cp ? 'active' : ''; ?>"><a class="page-link" href="<?= base_url('warehouse_in') . $q(['page' => $i]); ?>"><?= $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($cp < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= base_url('warehouse_in') . $q(['page' => $cp + 1]); ?>">&raquo;</a></li><?php endif; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($can_add): ?>
<div class="modal fade pp-modal" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('warehouse_in/add_header'); ?>" class="w-100">
            <div class="modal-content pp2" style="padding-bottom:0">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus mr-2"></i><?= lang_line('wi2_create', 'Buat GRN'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <p class="pp-hint mt-0 mb-3"><i class="fas fa-info-circle mr-1"></i><?= lang_line('wi2_create_hint', 'Isi data penerimaan, lalu pilih item dari PO / MIR / retur di halaman berikutnya.'); ?></p>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('sgr2_date', 'Tanggal Penerimaan'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="grn_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('warehouse_in_delivery_note', 'Nomor Surat Jalan Supplier'); ?></label>
                            <input type="text" name="delivery_note_number" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('warehouse_in_base_on', 'Dasar Penerimaan'); ?> <span class="text-danger">*</span></label>
                            <select name="base_on" class="form-control" required id="addHeaderBaseOn">
                                <option value="PO"><?= lang_line('warehouse_in_po', 'Purchase Order (PO)'); ?></option>
                                <option value="MIR"><?= lang_line('warehouse_in_mir', 'Material Import Request (MIR)'); ?></option>
                                <option value="Goods Return"><?= lang_line('warehouse_in_goods_return', 'Goods Return'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" id="warehouseTypeWrapper">
                            <label class="pp-label"><?= lang_line('warehouse_in_warehouse_type', 'Penerimaan Gudang Untuk'); ?> <span class="text-danger">*</span></label>
                            <select name="warehouse_type" class="form-control" required>
                                <option value=""><?= lang_line('warehouse_in_select_warehouse_type', '-- Pilih Tipe --'); ?></option>
                                <?php foreach ($warehouse_types as $code => $desc): ?><option value="<?= $code; ?>"><?= htmlspecialchars($desc); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="pp-label mt-1"><?= lang_line('sgr2_bc_section', 'Data Kepabeanan (opsional)'); ?></div>
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <select name="bc_type_code" class="form-control" title="<?= htmlspecialchars(lang_line('warehouse_in_bc_type', 'Tipe BC')); ?>">
                                <option value=""><?= lang_line('warehouse_in_select_bc_type', '-- Tanpa BC --'); ?></option>
                                <?php foreach ($bc_types as $bc): ?><option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group"><input type="date" name="bc_date" class="form-control" title="<?= htmlspecialchars(lang_line('warehouse_in_bc_date', 'Tanggal BC')); ?>"></div>
                        <div class="col-md-4 form-group"><input type="text" name="bc_number" class="form-control" placeholder="<?= htmlspecialchars(lang_line('warehouse_in_bc_number', 'Nomor BC')); ?>"></div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="pp-label"><?= lang_line('warehouse_in_remarks', 'Keterangan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('warehouse_in_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-pp"><?= lang_line('sgr2_save_next', 'Simpan & Pilih Item'); ?> <i class="fas fa-arrow-right ml-1"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
// Goods Return tidak butuh tipe gudang (sama dengan validasi add_header()).
document.addEventListener('DOMContentLoaded', function () {
    var base = document.getElementById('addHeaderBaseOn'), wrap = document.getElementById('warehouseTypeWrapper');
    if (!base || !wrap) return;
    var sync = function () {
        var gr = base.value === 'Goods Return';
        wrap.style.display = gr ? 'none' : '';
        wrap.querySelector('select').required = !gr;
    };
    base.addEventListener('change', sync); sync();
});
</script>
<?php endif; ?>
