<?php
    // Accounting boleh input due date dari list ini. Izin: finance/payment, edit PRF, atau approver final.
    $can_set_due_date = check_access('prf_payment', 'view')
        || check_access('prf', 'edit')
        || check_access('prf_approval', 'approval');

    // Query string filter aktif supaya export Excel/PDF mengikuti tampilan yang sedang difilter.
    $export_params = array_filter([
        'date_from'      => $filters['date_from']      ?? null,
        'date_to'        => $filters['date_to']        ?? null,
        'status'         => $filters['status']         ?? null,
        'payment_status' => $filters['payment_status'] ?? null,
        'department_id'  => $filters['department_id']  ?? null,
    ], function ($v) { return $v !== null && $v !== ''; });
    $export_qs = !empty($export_params) ? ('?' . http_build_query($export_params)) : '';

    // "Developer Mode" atur-kolom (ERP Revision Notes, user 2026-09-03) —
    // pola PERSIS sama dgn IRF List (consumable_request/index.php), lihat
    // catatan lengkap di [[developer_mode_feature]]. Page_key SAMA baik
    // dibuka sbg "List PRF" biasa maupun "Approval Queue" ($is_approval_view)
    // — kolomnya identik di kedua konteks, cuma tombol atas yg beda.
    $devmode_page_key = 'prf_list';
    $col_defs = [
        'request_number' => lang_line('prf_th_no', 'No. Payment Request'),
        'date'            => lang_line('prf_th_date', 'Tanggal'),
        'requester'       => lang_line('prf_th_requester', 'Requester'),
        'description'     => lang_line('prf_th_description', 'Deskripsi'),
        'amount'          => lang_line('prf_th_amount', 'Jumlah'),
        'status'          => lang_line('prf_th_status', 'Status'),
        'payment_status'  => lang_line('prf_th_payment_status', 'Status Pembayaran'),
        'due_date'        => lang_line('prf_th_due_date', 'Jatuh Tempo'),
        'pay_date'        => lang_line('prf_th_pay_date2', 'Tgl. Bayar'),
    ];
    $col = [];
    foreach ($col_defs as $ck => $clabel) {
        $col[$ck] = ui_column_visible($devmode_page_key, $ck, $clabel, true);
    }
    $col_colspan = count($col_defs) + 1; // +1 utk kolom Action (selalu tampil)
    // Tombol & panel "Atur Kolom" HANYA muncul kalau user memilih mode
    // 'Atur Kolom' di banner Developer Mode (user 2026-09-06) — supaya
    // tidak bentrok dengan mode edit teks.
    $devmode_active = devmode_is('column');
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <?php if (!empty($is_approval_view)): ?>
                <h1 class="mb-0"><?= lang_line('title_prf_approval', 'Persetujuan Payment Request'); ?></h1>
                <small class="text-muted"><?= lang_line('prf_approval_subtitle', 'Daftar Payment Request yang menunggu persetujuan Anda'); ?></small>
            <?php else: ?>
                <h1 class="mb-0"><?= lang_line('prf_list_title', 'List Payment Request'); ?></h1>
                <small class="text-muted"><?= lang_line('prf_list_subtitle', 'Semua Payment Request'); ?></small>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo base_url('prf/export_list_excel' . $export_qs); ?>" class="btn btn-success" title="<?= lang_line('prf_export_list_excel', 'Export Excel'); ?>">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="<?php echo base_url('prf/export_list_pdf' . $export_qs); ?>" target="_blank" class="btn btn-danger" title="<?= lang_line('prf_export_list_pdf', 'Print PDF'); ?>">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <?php if (check_access('prf_payment', 'view')): ?>
            <a href="<?php echo base_url('prf/payment'); ?>" class="btn btn-success">
                <i class="fas fa-wallet"></i> <?= lang_line('prf_btn_payment', 'Pembayaran'); ?>
            </a>
            <?php endif; ?>
            <?php if (check_access('prf', 'add')): ?>
            <a href="<?php echo base_url('prf/create'); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?= lang_line('prf_create_new', 'Buat Payment Request Baru'); ?>
            </a>
            <?php endif; ?>
            <?php if ($devmode_active): ?>
            <?php
                // Tombol "Atur Kolom" (ERP Revision Notes, user 2026-09-03) SENGAJA
                // di LUAR tabel (bukan di dalam <th>) — dropdown-menu jadi keturunan
                // `.table-responsive` (overflow-x:auto) ke-crop kalau ditaruh di sana,
                // kelihatan aneh terutama pas tabel pendek (data kosong). Bug
                // ditemukan user via screenshot.
            ?>
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-secondary" type="button" id="devmodeColBtn" data-toggle="dropdown" title="<?= htmlspecialchars(lang_line('devmode_col_manage_title', 'Atur kolom')); ?>">
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
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0"><?= lang_line('prf_filter_title', 'Filter'); ?></h6>
        </div>
        <div class="card-body">
            <?php // Tombol "Lihat Semua PRF" & Filter Cepat dihapus (user 2026-09-17) — Admin & Executive otomatis melihat semua PRF. ?>
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_from', 'Dari Tanggal'); ?>:</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_to', 'Sampai Tanggal'); ?>:</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_status', 'Status'); ?>:</label>
                    <select name="status" class="form-control">
                        <option value=""><?= lang_line('prf_filter_all', '-- Semua --'); ?></option>
                        <?php foreach($statuses as $status_value => $status_label): ?>
                            <?php // Backward-compat: kalau array masih numeric (approval_queue), pakai value sbg label.
                            if (is_int($status_value)) { $status_value = $status_label; } ?>
                            <option value="<?php echo $status_value; ?>" <?php echo (isset($filters['status']) && $filters['status'] === $status_value) ? 'selected' : ''; ?>>
                                <?php echo $status_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_payment_status', 'Status Pembayaran'); ?>:</label>
                    <select name="payment_status" class="form-control">
                        <option value=""><?= lang_line('prf_filter_all', '-- Semua --'); ?></option>
                        <?php foreach($payment_statuses as $ps): ?>
                            <option value="<?php echo $ps; ?>" <?php echo (isset($filters['payment_status']) && $filters['payment_status'] === $ps) ? 'selected' : ''; ?>>
                                <?php echo $ps; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i> <?= lang_line('prf_search', 'Cari'); ?>
                </button>
                <a href="<?php echo base_url('prf/list_all'); ?>" class="btn btn-link ml-1"><?= lang_line('prf_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($col_defs as $ck => $clabel): ?>
                        <th class="devmode-col devmode-col-<?= $ck ?>" <?= $col[$ck] ? '' : 'style="display:none"' ?>><?= htmlspecialchars($clabel) ?></th>
                        <?php endforeach; ?>
                        <th><?= lang_line('prf_th_action', 'Action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($prf_list)): ?>
                        <tr>
                            <td colspan="<?= $col_colspan; ?>" class="text-center py-4">
                                <i class="fas fa-inbox text-muted"></i> <?= lang_line('prf_no_data', 'Tidak ada data'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($prf_list as $prf): ?>
                            <tr>
                                <td class="devmode-col devmode-col-request_number" <?= $col['request_number'] ? '' : 'style="display:none"' ?>><strong><?php echo htmlspecialchars($prf->prf_number); ?></strong></td>
                                <td class="devmode-col devmode-col-date" <?= $col['date'] ? '' : 'style="display:none"' ?>><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></td>
                                <td class="devmode-col devmode-col-requester" <?= $col['requester'] ? '' : 'style="display:none"' ?>><?php echo htmlspecialchars($prf->requester_name ?? '-'); ?></td>
                                <td class="devmode-col devmode-col-description" style="max-width: 240px;<?= $col['description'] ? '' : 'display:none' ?>">
                                    <?php $desc = trim($prf->item_descriptions ?? ''); ?>
                                    <?php if ($desc === ''): ?>
                                        <span class="text-muted">-</span>
                                    <?php else: ?>
                                        <?php $is_long = mb_strlen($desc) > 50; ?>
                                        <span class="prf-desc-text"><?php echo htmlspecialchars($is_long ? mb_substr($desc, 0, 50) . '…' : $desc); ?></span>
                                        <?php if ($is_long): ?>
                                            <button type="button" class="btn btn-link btn-sm p-0 prf-desc-toggle"
                                                    data-short="<?php echo htmlspecialchars(mb_substr($desc, 0, 50) . '…', ENT_QUOTES); ?>"
                                                    data-full="<?php echo htmlspecialchars($desc, ENT_QUOTES); ?>">
                                                <?= lang_line('prf_desc_more', 'Selengkapnya'); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="devmode-col devmode-col-amount text-right" <?= $col['amount'] ? '' : 'style="display:none"' ?>>Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                                <td class="devmode-col devmode-col-status" <?= $col['status'] ? '' : 'style="display:none"' ?>>
                                    <span class="badge badge-<?php echo $prf->status === 'Paid' ? 'success' : ($prf->status === 'Draft' ? 'secondary' : 'info'); ?>">
                                        <?php echo $prf->status; ?>
                                    </span>
                                </td>
                                <td class="devmode-col devmode-col-payment_status" <?= $col['payment_status'] ? '' : 'style="display:none"' ?>>
                                    <span class="badge badge-<?php echo $prf->payment_status === 'Paid' ? 'success' : 'warning'; ?>">
                                        <?php echo $prf->payment_status; ?>
                                    </span>
                                </td>
                                <td class="devmode-col devmode-col-due_date prf-due-date-cell" style="<?= $col['due_date'] ? '' : 'display:none;' ?>" data-prf-id="<?php echo $prf->id; ?>" data-due-date="<?php echo $prf->due_date ? date('Y-m-d', strtotime($prf->due_date)) : ''; ?>">
                                    <span class="prf-due-date-label"><?php echo !empty($prf->due_date) ? date('d-m-Y', strtotime($prf->due_date)) : '-'; ?></span>
                                </td>
                                <td class="devmode-col devmode-col-pay_date" <?= $col['pay_date'] ? '' : 'style="display:none"' ?>><?php echo $prf->payment_date ? date('d-m-Y', strtotime($prf->payment_date)) : '-'; ?></td>
                                <td>
                                    <?php
                                        // "PRF Selanjutnya" (ERP Revision Notes, [[erp_revision_prf_erf]]):
                                        // tandai konteks antrian approval kalau memang dibuka dari
                                        // Approval Queue (bukan dari list browsing biasa), supaya
                                        // detail-nya bisa nawarin lompat ke PRF berikutnya.
                                        $prf_detail_url = base_url('prf/detail/' . $prf->id) . (!empty($is_approval_view) ? '?queue=approval' : '');
                                    ?>
                                    <a href="<?php echo $prf_detail_url; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($can_set_due_date): ?>
                                    <button type="button" class="btn btn-sm btn-warning btn-set-due-date"
                                            data-prf-id="<?php echo $prf->id; ?>"
                                            data-prf-number="<?php echo htmlspecialchars($prf->prf_number, ENT_QUOTES); ?>"
                                            data-due-date="<?php echo $prf->due_date ? date('Y-m-d', strtotime($prf->due_date)) : ''; ?>"
                                            title="<?= lang_line('prf_btn_set_due_date', 'Set Jatuh Tempo'); ?>">
                                        <i class="fas fa-calendar-day"></i>
                                    </button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-toggle="dropdown" title="<?= lang_line('prf_export_pdf', 'Export PDF'); ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=L'); ?>">A4 Landscape</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=P'); ?>">A4 Portrait</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=L'); ?>">A5 Landscape</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=P'); ?>">A5 Portrait</a>
                                        </div>
                                    </div>
                                    <a href="<?php echo base_url('prf/export_excel/' . $prf->id); ?>" class="btn btn-sm btn-success" title="<?= lang_line('prf_export_excel', 'Export Excel'); ?>">
                                        <i class="fas fa-file-excel"></i>
                                    </a>
                                    <?php if ($prf->status === 'Draft' && check_access('prf', 'delete')): ?>
                                        <a href="<?php echo base_url('prf/delete/' . $prf->id); ?>" class="btn btn-sm btn-danger" title="<?= lang_line('prf_btn_delete', 'Hapus'); ?>" onclick="return confirm('<?= sprintf(lang_line('prf_confirm_delete', 'Hapus Payment Request %s? Tindakan ini tidak dapat dibatalkan.'), htmlspecialchars($prf->prf_number, ENT_QUOTES)); ?>');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
        // Kartu "Riwayat Approval Saya" (user 2026-09-06: halaman approval
        // "hanya menampilkan list daftar yang akan di approve, seharusnya di
        // bagian bawah ada riwayat yang user itu approve"). Partial bersama
        // dipakai SEMUA halaman approval — lihat templates/my_approval_history.php.
        // Dijaga isset(): view yang dipakai bersama halaman non-approval
        // (mis. prf/list_all juga jadi PRF List) tidak ikut menampilkannya.
        if (isset($my_approval_history)) {
            $this->load->view('templates/my_approval_history', [
                'history' => $my_approval_history,
                'route'   => $my_history_route ?? '',
            ]);
        }
    ?>
</div>

<?php if ($can_set_due_date): ?>
<!-- Modal Set Due Date -->
<div class="modal fade" id="dueDateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="dueDateForm">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang_line('prf_due_date_modal_title', 'Set Tanggal Jatuh Tempo'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><?= lang_line('prf_th_no', 'No. Payment Request'); ?>: <strong id="dueDatePrfNumber"></strong></p>
                    <input type="hidden" id="dueDatePrfId" name="prf_id" value="">
                    <div class="form-group mb-0">
                        <label for="dueDateInput"><?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?></label>
                        <input type="date" class="form-control" id="dueDateInput" name="due_date">
                        <small class="text-muted"><?= lang_line('prf_due_date_hint', 'Kosongkan lalu simpan untuk menghapus jatuh tempo.'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prf_btn_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-primary" id="dueDateSaveBtn"><?= lang_line('prf_btn_save', 'Simpan'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    var $modal = $('#dueDateModal');

    $(document).on('click', '.btn-set-due-date', function () {
        $('#dueDatePrfId').val($(this).data('prf-id'));
        $('#dueDatePrfNumber').text($(this).data('prf-number'));
        $('#dueDateInput').val($(this).data('due-date') || '');
        $modal.modal('show');
    });

    $('#dueDateForm').on('submit', function (e) {
        e.preventDefault();
        var prfId   = $('#dueDatePrfId').val();
        var dueDate = $('#dueDateInput').val();
        var $btn    = $('#dueDateSaveBtn');
        $btn.prop('disabled', true);

        $.ajax({
            url: '<?php echo base_url('prf/set_due_date'); ?>',
            type: 'POST',
            dataType: 'json',
            data: { prf_id: prfId, due_date: dueDate },
            success: function (res) {
                if (res && res.success) {
                    // Update tampilan baris terkait tanpa reload
                    var $cell = $('.prf-due-date-cell[data-prf-id="' + prfId + '"]');
                    $cell.attr('data-due-date', res.due_date || '');
                    $cell.find('.prf-due-date-label').text(res.due_date_label || '-');
                    $('.btn-set-due-date[data-prf-id="' + prfId + '"]').data('due-date', res.due_date || '');
                    $modal.modal('hide');
                } else {
                    alert((res && res.message) ? res.message : '<?= lang_line('prf_due_date_save_failed', 'Gagal menyimpan due date.'); ?>');
                }
            },
            error: function (xhr) {
                var msg = '<?= lang_line('prf_due_date_save_failed', 'Gagal menyimpan due date.'); ?>';
                try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch (e) {}
                alert(msg);
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });
});
</script>
<?php endif; ?>

<script>
// Toggle deskripsi item Payment Request (tampilkan teks pendek ⇄ lengkap).
$(function () {
    $(document).on('click', '.prf-desc-toggle', function () {
        var $btn  = $(this);
        var $text = $btn.siblings('.prf-desc-text');
        var expanded = $btn.data('expanded');
        $text.text(expanded ? $btn.data('short') : $btn.data('full'));
        $btn.text(expanded
            ? '<?= lang_line('prf_desc_more', 'Selengkapnya'); ?>'
            : '<?= lang_line('prf_desc_less', 'Sembunyikan'); ?>');
        $btn.data('expanded', !expanded);
    });
});
</script>

<?php if ($devmode_active): ?>
<script>
// Developer Mode — atur kolom Payment Request List (ERP Revision Notes, user
// 2026-09-03). Pola PERSIS sama dgn Daftar Permintaan Barang (consumable_request/index.php)
// — lihat komentar di sana utk penjelasan lengkap staged Save/Cancel-nya.
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
