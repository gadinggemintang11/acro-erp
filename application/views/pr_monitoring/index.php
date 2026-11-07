<?php
    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pola PERSIS sama dgn IRF/PRF/ERF List, lihat [[developer_mode_feature]].
    $devmode_page_key = 'pr_monitoring';
    $col_defs = [
        'number'      => lang_line('prm_th_number', 'No. PR'),
        'type'        => lang_line('prm_th_type', 'Tipe'),
        'requester'   => lang_line('prm_th_requester', 'Requester'),
        'department'  => lang_line('prm_th_department', 'Departemen'),
        'date'        => lang_line('prm_th_date', 'Tanggal'),
        'items'       => lang_line('prm_th_items', 'Jml Item'),
        'total_qty'   => lang_line('prm_th_total_qty', 'Total Qty'),
        'status'      => lang_line('prm_th_status', 'Status'),
        'irf'         => lang_line('prm_th_irf', 'Asal Permintaan Barang'),
        'po_status'   => lang_line('prm_th_po_status', 'Status PO'),
    ];
    $col = [];
    foreach ($col_defs as $ck => $clabel) {
        $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
    }
    $col_colspan = count($col_defs) + 1; // +1 utk kolom Aksi (selalu tampil)
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
    // Nomor IRF tetap ditampilkan buat semua orang; yang ditahan cuma
    // TAUTAN-nya, supaya user tanpa akses IRF tidak mendarat di halaman 403.
    $can_view_irf = check_access('consumable_request', 'view');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-layer-group text-primary mr-2"></i>
                <?= lang_line('prm_title', 'PR List (Monitoring)'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('prm_subtitle', 'Daftar Purchase Request gabungan semua tipe (Produksi/General/Jasa/Asset)'); ?></small>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= htmlspecialchars(lang_line('prm_search_placeholder', 'Cari No. PR / Keperluan...')); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="type" class="form-control form-control-sm">
                        <option value=""><?= lang_line('prm_all_types', 'Semua Tipe'); ?></option>
                        <?php foreach ($type_labels as $tk => $tl): ?>
                            <option value="<?= $tk ?>" <?= ($filters['type'] ?? '') === $tk ? 'selected' : '' ?>><?= htmlspecialchars($tl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('label_status'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('common_filter'); ?></button>
                <a href="<?= base_url('pr_monitoring') ?>" class="btn btn-sm btn-secondary mb-1"><?= lang_line('btn_reset'); ?></a>
                <?php if ($devmode_active): ?>
                <?php
                    // Tombol "Atur Kolom" (ERP Revision Notes, user 2026-09-03) SENGAJA
                    // di LUAR tabel (bukan di dalam <th>) — dropdown-menu jadi keturunan
                    // `.table-responsive` (overflow-x:auto) ke-crop kalau ditaruh di sana,
                    // kelihatan aneh terutama pas tabel pendek (data kosong). Bug
                    // ditemukan user via screenshot.
                ?>
                <div class="dropdown d-inline-block mb-1">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>">
                        <i class="fas fa-columns mr-1"></i> <?= lang_line('devmode_col_manage_btn', 'Atur Kolom'); ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-2" style="min-width:220px;" id="devmodeColMenu">
                        <div class="small font-weight-bold text-muted mb-2"><?= lang_line('devmode_col_manage_heading', 'Tampilkan kolom'); ?></div>
                        <?php foreach ($col_defs as $ck => $clabel): ?>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input devmode-col-toggle" id="devcol_<?= $ck ?>"
                                   data-page="<?= $devmode_page_key ?>" data-column="<?= $ck ?>" <?= $col[$ck] ? 'checked' : '' ?>>
                            <label class="custom-control-label small" for="devcol_<?= $ck ?>"><?= htmlspecialchars($clabel) ?></label>
                        </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-end mt-2 pt-2 border-top" id="devmodeColActions" style="display:none !important;">
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="devmodeColCancel"><?= lang_line('btn_cancel', 'Batalkan'); ?></button>
                            <button type="button" class="btn btn-sm btn-primary" id="devmodeColSave"><?= lang_line('devmode_col_save', 'Simpan Perubahan'); ?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="devmode-col devmode-col-number" <?= $col['number'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['number']) ?></th>
                            <th class="devmode-col devmode-col-type" <?= $col['type'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['type']) ?></th>
                            <th class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['requester']) ?></th>
                            <th class="devmode-col devmode-col-department" <?= $col['department'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['department']) ?></th>
                            <th class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['date']) ?></th>
                            <th class="devmode-col devmode-col-items text-center" <?= $col['items'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['items']) ?></th>
                            <th class="devmode-col devmode-col-total_qty text-right" <?= $col['total_qty'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['total_qty']) ?></th>
                            <th class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['status']) ?></th>
                            <th class="devmode-col devmode-col-irf" <?= $col['irf'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['irf']) ?></th>
                            <th class="devmode-col devmode-col-po_status" <?= $col['po_status'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($col_defs['po_status']) ?></th>
                            <th width="90"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="<?= $col_colspan; ?>" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox mr-1"></i> <?= lang_line('prm_no_data', 'Tidak ada data.'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                            <?php
                                $badge = ['Draft'=>'warning','Submitted'=>'info','Approved'=>'success','Rejected'=>'danger'][$r->status] ?? 'secondary';
                                $detail_route = $type_routes[$r->type] ?? null;
                                $po_status_label = !empty($r->po_status) ? $r->po_status : lang_line('prm_po_status_none', 'Belum ada PO');

                                // irf_refs = "id:nomor|id:nomor" dari model.
                                // PR lama (dibuat sebelum aturan "PR selalu
                                // dari IRF") memang tidak punya asal IRF —
                                // kolomnya kosong, itu benar, bukan bug.
                                $irf_list = [];
                                foreach (array_filter(explode('|', (string) ($r->irf_refs ?? ''))) as $ref) {
                                    $parts = explode(':', $ref, 2);
                                    if (count($parts) === 2 && $parts[1] !== '') {
                                        $irf_list[] = ['id' => (int) $parts[0], 'number' => $parts[1]];
                                    }
                                }
                            ?>
                            <tr>
                                <td class="devmode-col devmode-col-number" <?= $col['number'] ? '' : 'style="display:none"' ?>><strong class="text-primary"><?= htmlspecialchars($r->pr_number) ?></strong></td>
                                <td class="devmode-col devmode-col-type" <?= $col['type'] ? '' : 'style="display:none"' ?>><span class="badge badge-light border"><?= htmlspecialchars($type_labels[$r->type] ?? $r->type) ?></span></td>
                                <td class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($r->requester_name ?? '-') ?></td>
                                <td class="devmode-col devmode-col-department" <?= $col['department'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($r->department ?? '-') ?></td>
                                <td class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= $r->pr_date ? date('d M Y', strtotime($r->pr_date)) : '-' ?></td>
                                <td class="devmode-col devmode-col-items text-center" <?= $col['items'] ? '' : 'style="display:none"' ?>><?= (int) $r->item_count ?></td>
                                <td class="devmode-col devmode-col-total_qty text-right" <?= $col['total_qty'] ? '' : 'style="display:none"' ?>><?= number_format((float) $r->total_qty, 2) ?></td>
                                <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($r->status) ?></span></td>
                                <td class="devmode-col devmode-col-irf small <?= empty($irf_list) ? 'text-muted' : '' ?>" <?= $col['irf'] ? '' : 'style="display:none"' ?>>
                                    <?php if (empty($irf_list)): ?>
                                        <?= lang_line('prm_irf_none', 'Bukan dari Permintaan Barang'); ?>
                                    <?php else: foreach ($irf_list as $irf): ?>
                                        <?php if ($can_view_irf): ?>
                                            <a href="<?= base_url('consumable_request/detail/' . $irf['id']); ?>" class="badge badge-light border mr-1" title="<?= lang_line('prm_irf_open', 'Buka Permintaan Barang asal'); ?>"><?= htmlspecialchars($irf['number']); ?></a>
                                        <?php else: ?>
                                            <span class="badge badge-light border mr-1"><?= htmlspecialchars($irf['number']); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; endif; ?>
                                </td>
                                <td class="devmode-col devmode-col-po_status small <?= empty($r->po_status) ? 'text-muted' : '' ?>" <?= $col['po_status'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($po_status_label) ?></td>
                                <td>
                                    <?php if ($detail_route): ?>
                                        <a href="<?= base_url($detail_route . '/' . $r->id) ?>" class="btn btn-sm btn-outline-primary" title="<?= lang_line('btn_detail'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($devmode_active): ?>
<script>
// Developer Mode — atur kolom PR Monitoring (ERP Revision Notes, user
// 2026-09-03). Pola PERSIS sama dgn Permintaan Barang/Payment Request/Reimburse Request List — lihat komentar di
// consumable_request/index.php utk penjelasan lengkap staged Save/Cancel.
$(document).ready(function() {
    var pending = {};

    $('#devmodeColMenu').on('click', function(e) { e.stopPropagation(); });

    function refreshActionBar() {
        var dirty = Object.keys(pending).length > 0;
        $('#devmodeColActions').css('display', dirty ? 'flex' : 'none');
    }

    $('.devmode-col-toggle').on('change', function() {
        var el = $(this);
        var key = el.data('column');
        var visible = el.is(':checked');
        $('.devmode-col-' + key).toggle(visible);
        pending[key] = visible ? 0 : 1;
        refreshActionBar();
    });

    $('#devmodeColCancel').on('click', function() {
        location.reload();
    });

    $('#devmodeColSave').on('click', function() {
        var keys = Object.keys(pending);
        if (keys.length === 0) return;

        var btn = $(this).prop('disabled', true);
        var page = '<?= $devmode_page_key ?>';
        var done = 0;
        var failed = false;

        keys.forEach(function(key) {
            $.post('<?= base_url("developer_mode/save_column") ?>', {
                page_key: page, column_key: key, is_hidden: pending[key]
            }, null, 'json').fail(function() {
                failed = true;
            }).always(function() {
                done++;
                if (done === keys.length) {
                    btn.prop('disabled', false);
                    if (failed) {
                        alert('<?= lang_line('devmode_save_err', 'Gagal menyimpan.'); ?>');
                    } else {
                        pending = {};
                        refreshActionBar();
                        $('#devmodeColBtn').dropdown('toggle');
                    }
                }
            });
        });
    });
});
</script>
<?php endif; ?>
