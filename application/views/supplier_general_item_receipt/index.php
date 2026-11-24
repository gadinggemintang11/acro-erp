<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Receiving Item Form (SGR) -- daftar. Redesign 2026-11-09 (user: "ini juga"),
 * gaya .pp2. Dibuka lewat 3 pintu: index/general, index/produksi, atau tanpa
 * segmen (semua kategori yang boleh dilihat). Cari/filter/paginasi & izin per
 * kategori disiapkan Supplier_general_item_receipt::index().
 */
$this->load->view('production_process/_pp_style');

$slug     = $category_slug ? strtolower($category_slug) : '';
$base     = 'supplier_general_item_receipt' . ($active_category ? '/index/' . $slug : '');
$f_search = (string) ($filters['search'] ?? '');
$f_status = (string) ($filters['status'] ?? '');
$sc       = $status_counts ?? [];
$perm     = $perm ?? [];
$can_add  = false; foreach ($perm as $p) { if (!empty($p['add'])) $can_add = true; }
if ($active_category) $can_add = !empty($perm[$active_category]['add']);
$q = function (array $over = []) use ($f_search, $f_status) {
    $p = array_filter(array_merge(['search' => $f_search, 'status' => $f_status], $over), function ($v) { return $v !== '' && $v !== null; });
    return $p ? '?' . http_build_query($p) : '';
};
$cat_tone = ['General' => 'tone-blue', 'Produksi' => 'tone-slate', 'Lainnya' => 'tone-amber'];
$wh_label = ['machine' => lang_line('sgr2_wh_machine', 'Mesin'), 'inventory' => lang_line('sgr2_wh_inventory', 'Inventaris')];
$qty = function ($n) { $s = number_format((float) $n, 4, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
$kpis = [
    ['',          lang_line('sgr2_kpi_all', 'Semua dokumen'),            'fa-layer-group', 'tone-brand', $all_count ?? 0],
    ['Draft',     lang_line('sgr2_kpi_draft', 'Draft — belum disetujui'), 'fa-pen',         'tone-amber', $sc['Draft'] ?? 0],
    ['Completed', lang_line('sgr2_kpi_done', 'Disetujui — stok masuk'),   'fa-check-double','tone-green', $sc['Completed'] ?? 0],
];
?>
<style>
.sgr2 .pp-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.sgr2 .sgr-tabs { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: 1rem; }
.sgr2 .sgr-tab { padding: .42rem .9rem; border-radius: 999px; font-size: .82rem; font-weight: 700; color: var(--pp-muted); background: var(--pp-card); border: 1px solid var(--pp-line); text-decoration: none !important; }
.sgr2 .sgr-tab:hover { color: var(--pp-text); border-color: var(--brand-1); }
.sgr2 .sgr-tab.is-active { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
.sgr2 .sgr-pos { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .3rem; }
.sgr2 .pagination .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--pp-line); color: var(--pp-text); }
.sgr2 .pagination .page-item.active .page-link { background: var(--brand-1); border-color: var(--brand-1); color: #fff; }
@media (max-width: 767.98px) { .sgr2 .pp-kpis { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid pp2 sgr2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-dolly-flatbed mr-2" style="color:var(--brand-1)"></i><?= lang_line('sgr2_title', 'Receiving Item Form'); ?>
                <?php if ($active_category): ?><span class="pp-tag <?= $cat_tone[$active_category] ?? 'tone-slate'; ?> align-middle ml-1" style="font-size:.75rem"><?= htmlspecialchars($active_category); ?></span><?php endif; ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('sgr2_sub', 'Penerimaan barang general (mesin & inventaris) dari PO. Setelah disetujui, barang langsung masuk stok.'); ?></div>
        </div>
        <div class="d-flex flex-wrap" style="gap:.4rem">
            <a href="<?= base_url('goods_receiving'); ?>" class="btn btn-light"><i class="fas fa-th-large mr-1"></i><?= lang_line('sgr2_all_receipts', 'Menu Pemasukan'); ?></a>
            <?php if ($can_add): ?>
                <button class="btn btn-pp" data-toggle="modal" data-target="#addSgrModal"><i class="fas fa-plus mr-1"></i><?= lang_line('sgr2_create', 'Buat Penerimaan'); ?></button>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach (['error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'] as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <?php if (count($cat_tabs ?? []) > 1 || !empty($can_blanket)): ?>
    <div class="sgr-tabs">
        <?php if (!empty($can_blanket)): ?><a href="<?= base_url('supplier_general_item_receipt'); ?>" class="sgr-tab<?= !$active_category ? ' is-active' : ''; ?>"><?= lang_line('sgr2_tab_all', 'Semua kategori'); ?></a><?php endif; ?>
        <?php foreach (($cat_tabs ?? []) as $ts => $tc): ?>
            <a href="<?= base_url('supplier_general_item_receipt/index/' . $ts); ?>" class="sgr-tab<?= $active_category === $tc ? ' is-active' : ''; ?>"><?= htmlspecialchars($tc); ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="pp-kpis">
        <?php foreach ($kpis as $k): ?>
            <a href="<?= base_url($base) . $q(['status' => $k[0], 'page' => null]); ?>" class="pp-kpi <?= $k[3]; ?><?= $f_status === $k[0] ? ' is-active' : ''; ?>">
                <span class="pp-kpi-ico"><i class="fas <?= $k[2]; ?>"></i></span>
                <span><span class="pp-kpi-num d-block"><?= number_format((int) $k[4]); ?></span><span class="pp-kpi-label d-block"><?= htmlspecialchars($k[1]); ?></span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pp-card">
        <div class="pp-card-head">
            <form method="get" action="<?= base_url($base); ?>" class="pp-toolbar w-100">
                <div class="pp-search"><i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="<?= htmlspecialchars(lang_line('sgr2_search_ph', 'Cari no. dokumen / PO / supplier / surat jalan')); ?>" value="<?= htmlspecialchars($f_search); ?>">
                </div>
                <select name="status" class="form-control" style="max-width:200px">
                    <option value=""><?= lang_line('poa_all_status', '-- Semua Status --'); ?></option>
                    <option value="Draft" <?= $f_status === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="Completed" <?= $f_status === 'Completed' ? 'selected' : ''; ?>><?= lang_line('sgr2_approved', 'Disetujui'); ?></option>
                </select>
                <button type="submit" class="btn btn-pp"><i class="fas fa-filter mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                <?php if ($f_search !== '' || $f_status !== ''): ?><a href="<?= base_url($base); ?>" class="btn btn-light"><i class="fas fa-times mr-1"></i><?= lang_line('btn_reset', 'Reset'); ?></a><?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('sgr2_th_number', 'No. Dokumen'); ?></th>
                    <th><?= lang_line('sgr_category', 'Kategori'); ?></th>
                    <th><?= lang_line('sgr2_th_supplier_po', 'Supplier / PO'); ?></th>
                    <th class="num"><?= lang_line('sgr2_th_items', 'Item'); ?></th>
                    <th class="num"><?= lang_line('sgr2_th_value', 'Nilai'); ?></th>
                    <th><?= lang_line('sgr_status', 'Status'); ?></th>
                    <th class="text-right"></th>
                </tr></thead>
                <tbody>
                <?php if (empty($sgrs)): ?>
                    <tr><td colspan="7" class="pp-empty"><i class="fas fa-dolly-flatbed"></i><?= lang_line('sgr_no_data', 'Belum ada data penerimaan'); ?></td></tr>
                <?php else: foreach ($sgrs as $r):
                    $cat = $r->category ?: 'Lainnya';
                    $draft = $r->status === 'Draft';
                    $pos = array_filter(explode('|', (string) $r->po_numbers));
                    $url = base_url('supplier_general_item_receipt/detail/' . $r->id); ?>
                    <tr>
                        <td>
                            <a href="<?= $url; ?>" class="pp-list-title"><?= htmlspecialchars($r->sgr_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= date('d M Y', strtotime($r->sgr_date)); ?> · <?= htmlspecialchars($wh_label[$r->warehouse_type] ?? ucfirst($r->warehouse_type)); ?></div>
                        </td>
                        <td><span class="pp-tag <?= $cat_tone[$cat] ?? 'tone-slate'; ?>"><?= htmlspecialchars($cat); ?></span></td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.84rem"><?= htmlspecialchars($r->nama_supplier ?: '-'); ?></div>
                            <?php if ($pos): ?><div class="sgr-pos"><?php foreach ($pos as $pn): ?><span class="pp-chip"><i class="fas fa-file-invoice"></i><?= htmlspecialchars($pn); ?></span><?php endforeach; ?></div><?php endif; ?>
                            <?php if (!empty($r->delivery_note_number)): ?><div class="pp-item-code"><?= lang_line('sgr2_dn', 'Surat jalan'); ?>: <?= htmlspecialchars($r->delivery_note_number); ?></div><?php endif; ?>
                        </td>
                        <td class="num"><b><?= (int) $r->item_count; ?></b> <small class="text-muted"><?= lang_line('prm2_lines', 'baris'); ?></small><div class="pp-item-code">qty <?= $qty($r->total_qty); ?></div></td>
                        <td class="num"><b><?= number_format((float) $r->total_value, 2, '.', ','); ?></b></td>
                        <td>
                            <?php if ($draft): ?><span class="pp-status pp-status--draft">Draft</span>
                            <?php else: ?><span class="pp-status pp-status--completed"><?= lang_line('sgr2_approved', 'Disetujui'); ?></span>
                                <?php if (!empty($r->approved_at)): ?><div class="pp-item-code mt-1"><?= date('d M Y', strtotime($r->approved_at)); ?></div><?php endif; ?><?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= $url; ?>" class="btn btn-sm <?= $draft ? 'btn-pp' : 'btn-light'; ?>" style="white-space:nowrap">
                                    <?php if ($draft): ?><i class="fas fa-pen mr-1"></i><?= lang_line('sgr2_continue', 'Lanjutkan'); ?><?php else: ?><i class="fas fa-eye mr-1"></i><?= lang_line('po_report_detail', 'Detail'); ?><?php endif; ?>
                                </a>
                                <?php if ($draft && !empty($perm[$cat]['delete'])): ?>
                                    <a href="<?= base_url('supplier_general_item_receipt/delete_header/' . $r->id); ?>" class="btn btn-sm btn-light text-danger btn-delete" title="<?= htmlspecialchars(lang_line('delete', 'Hapus')); ?>"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('sgr2_confirm_delete', 'Hapus %s?'), $r->sgr_number)); ?>"
                                       data-text="<?= htmlspecialchars(lang_line('sgr_confirm_delete_text', 'Seluruh item di dalam dokumen ini ikut terhapus dan tidak dapat dikembalikan!')); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pp-foot">
            <span><b><?= number_format((int) ($total ?? 0)); ?></b> <?= lang_line('sgr2_docs', 'dokumen'); ?><?= ($total ?? 0) != ($all_count ?? 0) ? ' ' . sprintf(lang_line('pst2_of_total', 'dari %s'), number_format((int) ($all_count ?? 0))) : ''; ?></span>
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

<?php if ($can_add): ?>
<!-- Buat dokumen penerimaan (header dulu, item dipilih dari PO di halaman detail) -->
<div class="modal fade pp-modal" id="addSgrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('supplier_general_item_receipt/add_header'); ?>" class="w-100">
            <div class="modal-content pp2" style="padding-bottom:0">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus mr-2"></i><?= lang_line('sgr2_create', 'Buat Penerimaan'); ?><?= $active_category ? ' · ' . htmlspecialchars($active_category) : ''; ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <p class="pp-hint mt-0 mb-3"><i class="fas fa-info-circle mr-1"></i><?= lang_line('sgr2_create_hint', 'Isi data penerimaan, lalu pilih item PO yang datang di halaman berikutnya.'); ?></p>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('sgr2_date', 'Tanggal Penerimaan'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="sgr_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('sgr_delivery_note', 'Nomor Surat Jalan Supplier'); ?></label>
                            <input type="text" name="delivery_note_number" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('sgr_warehouse_type', 'Tipe Gudang'); ?> <span class="text-danger">*</span></label>
                            <select name="warehouse_type" class="form-control" required>
                                <option value=""><?= lang_line('sgr2_pick_type', '-- Pilih Tipe --'); ?></option>
                                <?php foreach ($warehouse_types as $code => $desc): ?><option value="<?= $code; ?>"><?= htmlspecialchars($desc); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="pp-label"><?= lang_line('sgr_category', 'Kategori'); ?> <span class="text-danger">*</span></label>
                            <?php if ($active_category): ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($active_category); ?>" readonly>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($active_category); ?>">
                            <?php else: ?>
                                <select name="category" class="form-control" required>
                                    <option value=""><?= lang_line('sgr2_pick_cat', '-- Pilih Kategori --'); ?></option>
                                    <?php foreach (['General' => 'General', 'Produksi' => 'Produksi', 'Lainnya' => lang_line('sgr2_cat_other', 'Lainnya (bukan dari Permintaan Barang)')] as $cv => $cl): if (empty($perm[$cv]['add'])) continue; ?>
                                        <option value="<?= $cv; ?>"><?= htmlspecialchars($cl); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pp-label mt-1"><?= lang_line('sgr2_bc_section', 'Data Kepabeanan (opsional)'); ?></div>
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <select name="bc_type_code" class="form-control" title="<?= htmlspecialchars(lang_line('sgr_bc_type', 'Tipe BC')); ?>">
                                <option value=""><?= lang_line('sgr2_no_bc', '-- Tanpa BC --'); ?></option>
                                <?php foreach ($bc_types as $bc): ?><option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group"><input type="date" name="bc_date" class="form-control" title="<?= htmlspecialchars(lang_line('sgr_bc_date', 'Tanggal BC')); ?>"></div>
                        <div class="col-md-4 form-group"><input type="text" name="bc_number" class="form-control" placeholder="<?= htmlspecialchars(lang_line('sgr_bc_number', 'Nomor BC')); ?>"></div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="pp-label"><?= lang_line('sgr_remarks', 'Keterangan'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-pp"><?= lang_line('sgr2_save_next', 'Simpan & Pilih Item'); ?> <i class="fas fa-arrow-right ml-1"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
