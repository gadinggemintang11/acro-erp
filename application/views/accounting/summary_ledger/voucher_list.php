<?php
$status_active = $filters['status'] ?? 'all';
// Counts per status dihitung di model (lintas halaman), bukan dari $vouchers (1 halaman saja).
$counts = isset($status_counts) ? $status_counts : ['all' => 0, 'Draft' => 0, 'Posted' => 0, 'Cancelled' => 0];
$total_rows = isset($total_rows) ? (int) $total_rows : count($vouchers);
$row_offset = isset($row_offset) ? (int) $row_offset : 0;
$can_post   = check_access('voucher_disbursement_list', 'approval') || check_access('voucher_disbursement_list', 'confirm');
$can_delete = check_access('voucher_disbursement_list', 'delete') || check_access('summary_ledger', 'delete');

// Baris yang bisa dipilih untuk bulk = Draft & berasal dari upload Summary Ledger.
// (Jurnal lain hanya bisa di-print, status-nya dikelola di General Journal.)
$selectable_count = 0;
foreach ($vouchers as $v) {
    if ($v->status === 'Draft' && $v->source_module === 'Summary Ledger') $selectable_count++;
}
$has_selectable = $selectable_count > 0;
?>
<div class="container-fluid">
    <h1 class="h3 mb-3 text-gray-800"><i class="fas fa-file-invoice-dollar mr-2"></i><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Filter bar -->
    <div class="card shadow mb-3">
        <div class="card-body py-3">
            <form method="get" action="<?= base_url('summary_ledger/voucher_list'); ?>" class="form-inline">
                <div class="btn-group btn-group-sm mr-3" role="group">
                    <?php
                    // Draft tidak ditampilkan di list voucher (hanya Posted/Cancelled).
                    $tabs = [
                        ['all',       lang_line('sl_vl_tab_all',       'All'),       'secondary'],
                        ['Posted',    lang_line('sl_vl_tab_posted',    'Posted'),    'success'],
                        ['Cancelled', lang_line('sl_vl_tab_cancelled', 'Cancelled'), 'dark'],
                    ];
                    foreach ($tabs as $t):
                        [$val, $label, $color] = $t;
                        $is_active = ($status_active === $val);
                    ?>
                        <a href="<?= base_url('summary_ledger/voucher_list?status=' . $val); ?>"
                           class="btn btn-<?= $is_active ? $color : ('outline-' . $color); ?>">
                            <?= $label; ?> <span class="badge badge-light ml-1"><?= $counts[$val]; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" name="status" value="<?= htmlspecialchars($status_active); ?>">

                <div class="form-group mr-2">
                    <input type="text" name="q" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['q']); ?>"
                           placeholder="<?= lang_line('sl_vl_search_placeholder', 'Cari No Jurnal / Deskripsi') ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" title="<?= lang_line('sl_vl_date_from_title', 'Tanggal dari') ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" title="<?= lang_line('sl_vl_date_to_title', 'Tanggal sampai') ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> <?= lang_line('sl_vl_btn_filter', 'Filter') ?>
                </button>
                <a href="<?= base_url('summary_ledger/voucher_list'); ?>" class="btn btn-light btn-sm ml-1">
                    <i class="fas fa-undo"></i> <?= lang_line('sl_vl_btn_reset', 'Reset') ?>
                </a>
                <a href="<?= base_url('summary_ledger'); ?>" class="btn btn-info btn-sm ml-auto">
                    <i class="fas fa-upload"></i> <?= lang_line('sl_vl_btn_upload_new', 'Upload Voucher Baru') ?>
                </a>
            </form>
        </div>
    </div>

    <form id="bulkForm" method="post" action="">
        <input type="hidden" name="bulk_action" id="bulkActionField" value="">

        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list"></i> <?= sprintf(lang_line('sl_vl_voucher_list_count', 'Daftar Voucher (%d)'), $total_rows) ?>
                    <span id="selectedCount" class="badge badge-secondary ml-2" style="display:none;"><?= sprintf(lang_line('sl_vl_n_selected', '%d dipilih'), 0) ?></span>
                </h6>
                <?php if ($has_selectable && ($can_post || $can_delete)): ?>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php if ($can_post): ?>
                            <button type="button" id="btnBulkPost" class="btn btn-primary" disabled>
                                <i class="fas fa-check"></i> <?= lang_line('sl_vl_btn_post_selected', 'Post Selected') ?>
                            </button>
                        <?php endif; ?>
                        <?php if ($can_delete): ?>
                            <button type="button" id="btnBulkDelete" class="btn btn-warning" disabled>
                                <i class="fas fa-trash"></i> <?= lang_line('sl_vl_btn_delete_selected', 'Delete Selected') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($vouchers)): ?>
                    <p class="text-muted mb-0 text-center py-4"><?= lang_line('sl_vl_no_vouchers', 'Tidak ada voucher dengan filter ini.') ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <?php if ($has_selectable): ?>
                                    <th style="width: 32px;" class="text-center">
                                        <input type="checkbox" id="chkAll" title="<?= lang_line('sl_vl_chkall_title', 'Pilih semua Draft di halaman ini') ?>">
                                    </th>
                                    <?php endif; ?>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 140px;"><?= lang_line('sl_th_journal_no', 'Nomor Jurnal') ?></th>
                                    <th style="width: 100px;"><?= lang_line('sl_th_journal_date', 'Tanggal') ?></th>
                                    <th><?= lang_line('sl_th_description', 'Deskripsi') ?></th>
                                    <th style="width: 70px;"><?= lang_line('sl_vl_th_curr', 'Curr') ?></th>
                                    <th class="text-right" style="width: 130px;"><?= lang_line('sl_th_total_debit_idr', 'Total Debit (IDR)') ?></th>
                                    <th class="text-right" style="width: 130px;"><?= lang_line('sl_th_total_credit_idr', 'Total Credit (IDR)') ?></th>
                                    <th class="text-center" style="width: 80px;"><?= lang_line('sl_th_status', 'Status') ?></th>
                                    <th style="width: 110px;"><?= lang_line('sl_vl_th_created_by', 'Created By') ?></th>
                                    <th class="text-center" style="width: 200px;"><?= lang_line('sl_th_actions', 'Aksi') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vouchers as $i => $v): ?>
                                    <tr>
                                        <?php if ($has_selectable): ?>
                                        <td class="text-center">
                                            <?php if ($v->status === 'Draft' && $v->source_module === 'Summary Ledger'): ?>
                                                <input type="checkbox" name="journal_ids[]" value="<?= (int) $v->id; ?>" class="chkRow">
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                        <td><?= $row_offset + $i + 1; ?></td>
                                    <td class="font-weight-bold text-primary"><?= htmlspecialchars($v->journal_no); ?></td>
                                    <td><?= date('d M Y', strtotime($v->journal_date)); ?></td>
                                    <td>
                                        <?= htmlspecialchars($v->description); ?>
                                        <?php if (!empty($v->import_filename)): ?>
                                            <br><small class="text-muted"><i class="fas fa-file-import"></i> <?= htmlspecialchars($v->import_filename); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($v->currency); ?></td>
                                    <td class="text-right"><?= number_format($v->total_debit_local, 2); ?></td>
                                    <td class="text-right"><?= number_format($v->total_kredit_local, 2); ?></td>
                                    <td class="text-center">
                                        <?php if ($v->status === 'Posted'): ?>
                                            <span class="badge badge-success" title="<?= sprintf(lang_line('sl_vl_posted_by_tooltip', 'Posted oleh %s'), htmlspecialchars($v->posted_by_name ?? '-')); ?>&#10;<?= !empty($v->posted_at) ? date('d M Y H:i', strtotime($v->posted_at)) : ''; ?>"><?= lang_line('sl_status_posted', 'Posted') ?></span>
                                        <?php elseif ($v->status === 'Cancelled'): ?>
                                            <span class="badge badge-dark"><?= lang_line('sl_status_cancelled', 'Cancelled') ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= lang_line('sl_status_draft', 'Draft') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($v->created_by_name ?? '-'); ?></small><br>
                                        <small class="text-muted"><?= date('d M Y', strtotime($v->created_at)); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('summary_ledger/voucher_pdf/' . (int) $v->id); ?>"
                                           target="_blank"
                                           class="btn btn-danger btn-sm" title="<?= lang_line('sl_vl_btn_preview_pdf', 'Preview PDF') ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="<?= base_url('summary_ledger/voucher_excel/' . (int) $v->id); ?>"
                                           class="btn btn-success btn-sm" title="<?= lang_line('sl_vl_btn_download_excel', 'Download Excel') ?>">
                                            <i class="fas fa-file-excel"></i>
                                        </a>
                                        <a href="<?= base_url('jurnalumum/view/' . (int) $v->id); ?>"
                                           class="btn btn-info btn-sm" title="<?= lang_line('sl_btn_view_in_gj', 'Lihat di General Journal') ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($v->status === 'Draft' && $can_post && $v->source_module === 'Summary Ledger'): ?>
                                            <a href="<?= base_url('summary_ledger/post_voucher/' . (int) $v->id); ?>"
                                               class="btn btn-primary btn-sm"
                                               title="<?= lang_line('sl_vl_btn_post_voucher', 'Post voucher') ?>"
                                               onclick="return confirm(<?= htmlspecialchars(json_encode(sprintf(lang_line('sl_vl_confirm_post_voucher', 'Post voucher %s? Status berubah jadi Posted dan tidak bisa diubah lagi.'), $v->journal_no)), ENT_QUOTES) ?>);">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($v->status === 'Draft' && $can_delete && $v->source_module === 'Summary Ledger'): ?>
                                            <a href="<?= base_url('summary_ledger/delete_journal/' . (int) $v->id); ?>"
                                               class="btn btn-warning btn-sm"
                                               title="<?= lang_line('sl_vl_btn_delete_voucher', 'Hapus voucher (hanya Draft)') ?>"
                                               onclick="return confirm(<?= htmlspecialchars(json_encode(sprintf(lang_line('sl_vl_confirm_delete_voucher', 'Hapus voucher %s? Aksi ini tidak bisa di-undo.'), $v->journal_no)), ENT_QUOTES) ?>);">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($vouchers) && !empty($pagination)): ?>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
                    <small class="text-muted">
                        <?= sprintf(
                            lang_line('sl_vl_showing_info', 'Menampilkan %d–%d dari %d voucher'),
                            $row_offset + 1,
                            $row_offset + count($vouchers),
                            $total_rows
                        ) ?>
                    </small>
                    <nav><?= $pagination; ?></nav>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
const I18N_SL_VL = {
    n_selected_tpl:    <?= json_encode(lang_line('sl_vl_n_selected', '%d dipilih')) ?>,
    confirm_bulk_post: <?= json_encode(lang_line('sl_vl_js_bulk_post', 'Post {n} voucher Draft jadi Posted? Aksi ini tidak bisa di-undo.')) ?>,
    confirm_bulk_del:  <?= json_encode(lang_line('sl_vl_js_bulk_delete', 'Hapus {n} voucher Draft? Aksi ini tidak bisa di-undo.')) ?>
};

(function () {
    const form = document.getElementById('bulkForm');
    if (!form) return;
    const chkAll = document.getElementById('chkAll');
    const rows   = () => document.querySelectorAll('.chkRow');
    const checked = () => Array.from(rows()).filter(c => c.checked);
    const btnPost   = document.getElementById('btnBulkPost');
    const btnDelete = document.getElementById('btnBulkDelete');
    const counter   = document.getElementById('selectedCount');

    const POST_URL   = '<?= base_url('summary_ledger/bulk_post_vouchers'); ?>';
    const DELETE_URL = '<?= base_url('summary_ledger/bulk_delete_vouchers'); ?>';

    function refresh() {
        const n = checked().length;
        if (counter) {
            counter.style.display = n > 0 ? 'inline-block' : 'none';
            counter.textContent = I18N_SL_VL.n_selected_tpl.replace('%d', n);
        }
        if (btnPost)   btnPost.disabled   = (n === 0);
        if (btnDelete) btnDelete.disabled = (n === 0);
        if (chkAll) {
            const all = rows().length;
            chkAll.checked = (n > 0 && n === all);
            chkAll.indeterminate = (n > 0 && n < all);
        }
    }

    if (chkAll) {
        chkAll.addEventListener('change', () => {
            rows().forEach(c => c.checked = chkAll.checked);
            refresh();
        });
    }
    rows().forEach(c => c.addEventListener('change', refresh));

    function submitBulk(actionUrl, confirmMsg) {
        const n = checked().length;
        if (n === 0) return;
        if (!confirm(confirmMsg.replace('{n}', n))) return;
        form.action = actionUrl;
        if (window.showLoader) window.showLoader(); // loader muncul saat navigasi, bukan saat dialog konfirmasi
                form.submit();
    }

    if (btnPost) {
        btnPost.addEventListener('click', () => {
            submitBulk(POST_URL, I18N_SL_VL.confirm_bulk_post);
        });
    }
    if (btnDelete) {
        btnDelete.addEventListener('click', () => {
            submitBulk(DELETE_URL, I18N_SL_VL.confirm_bulk_del);
        });
    }

    refresh();
})();
</script>
