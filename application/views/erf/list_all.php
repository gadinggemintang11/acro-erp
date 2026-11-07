<?php
    $badge_map = [
        'Draft'=>'warning','Submitted'=>'info',
        'Approved Head Dept'=>'primary','Endorsed'=>'primary',
        'Approved'=>'success','Paid'=>'success',
        'Cancelled'=>'secondary','Rejected'=>'danger',
    ];

    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pola PERSIS sama dgn IRF List/PRF List, lihat [[developer_mode_feature]].
    // "#" (nomor baris) & "Aksi" SENGAJA tidak dimasukkan (bukan data).
    $devmode_page_key = 'erf_list';
    $col_defs = [
        'erf_number'    => lang_line('erf_th_erf_number', 'Reimburse Request Number'),
        'date'          => lang_line('erf_th_date', 'Date'),
        'requester'     => lang_line('erf_th_requester', 'Requester'),
        'subtotal'      => lang_line('erf_th_subtotal', 'Subtotal'),
        'status'        => lang_line('erf_th_status', 'Status'),
        'payment'       => lang_line('erf_th_payment', 'Payment'),
        'payment_date'  => lang_line('erf_th_payment_date', 'Payment Date'),
    ];
    $col = [];
    foreach ($col_defs as $ck => $clabel) {
        $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
    }
    $col_colspan = count($col_defs) + 2; // +2 utk kolom "#" & "Aksi" (selalu tampil)
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
            <p class="text-muted small mb-0"><?= lang_line('erf_list_all_subtitle', 'Daftar semua Reimburse Request (semua status).') ?></p>
        </div>
    </div>


    <div class="card border-0 shadow-sm mb-3">
        <?php if (!empty($can_view_all)): ?>
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="badge badge-<?= !empty($show_all) ? 'info' : 'secondary'; ?>">
                <i class="fas fa-<?= !empty($show_all) ? 'globe' : 'user'; ?> mr-1"></i>
                <?= !empty($show_all)
                    ? lang_line('erf_scope_all', 'Menampilkan semua Reimburse Request (mode admin)')
                    : lang_line('erf_scope_mine', 'Hanya Reimburse Request saya'); ?>
            </span>
            <?php if (!empty($show_all)): ?>
                <a href="<?= base_url('erf/list_all'); ?>" class="btn btn-sm btn-outline-secondary" title="<?= lang_line('erf_scope_show_mine_tip', 'Kembali hanya menampilkan Reimburse Request buatan Anda'); ?>">
                    <i class="fas fa-user"></i> <?= lang_line('erf_scope_show_mine', 'Lihat Reimburse Request Saya Saja'); ?>
                </a>
            <?php else: ?>
                <a href="<?= base_url('erf/list_all?show_all=1'); ?>" class="btn btn-sm btn-outline-info" title="<?= lang_line('erf_scope_show_all_tip', 'Lihat Reimburse Request dari semua user (mode admin / approver / finance)'); ?>">
                    <i class="fas fa-globe"></i> <?= lang_line('erf_scope_show_all', 'Lihat Semua Reimburse Request'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <?php if (!empty($show_all)): ?>
                    <input type="hidden" name="show_all" value="1">
                <?php endif; ?>
                <input type="text" name="search" class="form-control form-control-sm mr-2 mb-1" placeholder="<?= lang_line('erf_list_search_ph', 'Cari Reimburse Request/requester...') ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                <input type="date" name="date_from" class="form-control form-control-sm mr-2 mb-1" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                <input type="date" name="date_to" class="form-control form-control-sm mr-2 mb-1" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                <select name="status" class="form-control form-control-sm mr-2 mb-1">
                    <option value=""><?= lang_line('erf_filter_all_status', '-- Semua Status --') ?></option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['status'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="payment_status" class="form-control form-control-sm mr-2 mb-1">
                    <option value=""><?= lang_line('erf_list_payment_all', '-- Semua Payment --') ?></option>
                    <?php foreach ($payment_statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['payment_status'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i></button>
                <a href="<?= base_url('erf/list_all' . (!empty($show_all) ? '?show_all=1' : '')); ?>" class="btn btn-sm btn-secondary mb-1"><i class="fas fa-redo"></i></a>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= sprintf(lang_line('erf_list_total_label', 'Total: %s Reimburse Request'), number_format(count($erf_list))) ?></h6>
            <?php if ($devmode_active): ?>
            <?php
                // Tombol "Atur Kolom" (ERP Revision Notes, user 2026-09-03) SENGAJA
                // di LUAR tabel (bukan di dalam <th>) — dropdown-menu jadi keturunan
                // `.table-responsive` (overflow-x:auto) ke-crop kalau ditaruh di sana,
                // kelihatan aneh terutama pas tabel pendek (data kosong). Bug
                // ditemukan user via screenshot.
            ?>
            <div class="dropdown d-inline-block">
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
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <?php foreach ($col_defs as $ck => $clabel): ?>
                            <th class="devmode-col devmode-col-<?= $ck ?>" <?= $col[$ck] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($clabel) ?></th>
                            <?php endforeach; ?>
                            <th width="80"><?= lang_line('erf_th_action', 'Aksi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($erf_list)): ?>
                            <tr><td colspan="<?= $col_colspan; ?>" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('erf_list_no_data', 'Tidak ada Reimburse Request.') ?></td></tr>
                        <?php else: $no = 1; foreach ($erf_list as $e): ?>
                            <?php $b = $badge_map[$e->status] ?? 'secondary'; ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="devmode-col devmode-col-erf_number" <?= $col['erf_number'] ? '' : 'style="display:none"' ?>><strong class="text-primary"><?= htmlspecialchars($e->erf_number); ?></strong></td>
                                <td class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?= date('d M Y', strtotime($e->erf_date)); ?></td>
                                <td class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($e->requester_name ?? '-'); ?></td>
                                <td class="devmode-col devmode-col-subtotal text-right" <?= $col['subtotal'] ? '' : 'style="display:none"' ?>><?= number_format((float)$e->subtotal, 2); ?></td>
                                <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $b; ?>"><?= htmlspecialchars($e->status); ?></span></td>
                                <td class="devmode-col devmode-col-payment" <?= $col['payment'] ? '' : 'style="display:none"' ?>><span class="badge badge-<?= $e->payment_status == 'Paid' ? 'success' : 'secondary'; ?>"><?= htmlspecialchars($e->payment_status); ?></span></td>
                                <td class="devmode-col devmode-col-payment_date small" <?= $col['payment_date'] ? '' : 'style="display:none"' ?>><?= $e->payment_date ? date('d M Y', strtotime($e->payment_date)) : '-'; ?></td>
                                <td><a href="<?= base_url('erf/detail/' . $e->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($devmode_active): ?>
<script>
// Developer Mode — atur kolom Reimburse Request List (ERP Revision Notes, user
// 2026-09-03). Pola PERSIS sama dgn Daftar Permintaan Barang/Payment Request List — lihat komentar di
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
