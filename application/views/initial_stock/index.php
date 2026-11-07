<div class="container-fluid">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <div>
            <a href="<?= base_url('initial_stock/download_template') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">
                <i class="fas fa-download fa-sm"></i> <?= lang_line('is_btn_template', 'Download Template'); ?>
            </a>
            <button type="button" class="btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-file-excel fa-sm"></i> <?= lang_line('is_btn_import', 'Import Excel'); ?>
            </button>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="<?= base_url('initial_stock/import_excel') ?>" method="post" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--brand-1);">
                        <h5 class="modal-title"><?= lang_line('is_import_title', 'Import Stock Awal dari Excel'); ?></h5>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle"></i>
                            <?= lang_line('is_import_hint', 'Unduh template, isi kolom Qty & Tanggal, lalu unggah di sini. Data masuk sebagai Draft dan perlu di-Confirm.'); ?>
                        </p>
                        <div class="form-group">
                            <label><strong><?= lang_line('is_import_file', 'File Excel (.xlsx / .xls)'); ?></strong></label>
                            <input type="file" name="excel_file" class="form-control-file" accept=".xlsx,.xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= base_url('initial_stock/download_template') ?>" class="btn btn-outline-secondary btn-sm mr-auto">
                            <i class="fas fa-download"></i> <?= lang_line('is_btn_template', 'Download Template'); ?>
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('is_btn_cancel', 'Batal'); ?></button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> <?= lang_line('is_btn_upload', 'Upload'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('warning'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Input -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 text-white" style="background-color: var(--brand-1);">
                    <h6 class="m-0 font-weight-bold"><?= lang_line('is_input_section', '📝 Input Stock Awal (Draft)'); ?></h6>
                </div>
                <div class="card-body">
                    <form id="inputForm">
                        <div class="form-group">
                            <label><strong><?= lang_line('is_label_pick_item', 'Pilih Barang'); ?></strong> <span class="text-danger">*</span></label>
                            <select id="itemSelect" class="form-control select2" style="width: 100%" required>
                                <option value=""><?= lang_line('is_placeholder_pick', '-- Cari Barang (Kode/Nama) --'); ?></option>
                                <?php foreach ($inventories as $item): ?>
                                    <?php
                                        // Status ditampilkan PER GUDANG (user 2026-09-10) — satu
                                        // barang bisa sudah punya stock awal General tapi Produksi-nya
                                        // masih kosong, dan itu harus kelihatan sebelum user memilih.
                                        // Tanpa ini barang tsb tampil "CONFIRMED" begitu saja lalu
                                        // orang mengira bucket Produksi sudah terisi juga.
                                        $mark = function ($draft_id, $stock) {
                                            if (!empty($draft_id))  return lang_line('is_status_draft_label', 'DRAFT');
                                            if ((float) $stock > 0) return lang_line('is_status_confirmed_label', 'CONFIRMED');
                                            return '-';
                                        };
                                        $status_text = ' [G: ' . $mark($item->draft_general_id ?? null,  $item->stock_general ?? 0)
                                                     . ' | P: '  . $mark($item->draft_produksi_id ?? null, $item->stock_produksi ?? 0) . ']';
                                    ?>
                                    <option value="<?= $item->id ?>" data-unit="<?= $item->satuan_desc ?>" data-code="<?= htmlspecialchars($item->kode_barang) ?>"
                                            data-stock-general="<?= (float) ($item->stock_general ?? 0) ?>"
                                            data-stock-produksi="<?= (float) ($item->stock_produksi ?? 0) ?>">
                                        [<?= htmlspecialchars($item->kode_barang) ?>] <?= htmlspecialchars($item->nama_barang) ?><?= $status_text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label><strong><?= lang_line('is_label_period_date', 'Tanggal Periode'); ?></strong> <span class="text-danger">*</span></label>
                                <input type="date" id="dateInput" class="form-control" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label><strong><?= lang_line('is_label_qty_initial', 'Qty Awal'); ?></strong> <span class="text-danger">*</span></label>
                                <input type="number" id="qtyInput" class="form-control" step="0.01" min="0" placeholder="0" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label><strong><?= lang_line('is_label_unit', 'Satuan'); ?></strong></label>
                                <input type="text" id="unitDisplay" class="form-control" readonly style="background-color: #f5f5f5;">
                            </div>
                            <div class="form-group col-md-3">
                                <label><strong><?= lang_line('cr_category', 'Masuk Gudang'); ?></strong> <span class="text-danger">*</span></label>
                                <select id="categoryInput" class="form-control" required>
                                    <option value="General">General</option>
                                    <option value="Produksi">Produksi</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?= lang_line('is_label_notes', 'Catatan'); ?></label>
                            <textarea id="notesInput" class="form-control" rows="2" placeholder="<?= lang_line('is_notes_placeholder', 'Catatan...'); ?>"></textarea>
                        </div>

                        <div class="text-right">
                            <button type="reset" class="btn btn-secondary"><?= lang_line('is_btn_reset', 'Reset'); ?></button>
                            <button type="button" class="btn btn-primary" id="saveDraftBtn"><?= lang_line('is_btn_save_draft', 'Simpan ke Draft'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-light">
                    <h6 class="m-0 font-weight-bold"><?= lang_line('is_status_section', '📊 Status'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted"><?= lang_line('is_status_draft', 'Draft (belum confirm)'); ?></small>
                        <div class="h4 text-warning"><?= count($drafts) ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted"><?= lang_line('is_status_confirmed', 'Confirmed (posting ledger)'); ?></small>
                        <div class="h4 text-success"><?= count($confirmed) ?></div>
                    </div>
                    <hr>
                    <p class="text-sm">
                        <i class="fas fa-info-circle"></i> <?= lang_line('is_workflow_info', 'Simpan → Draft → Confirm → Stock Ledger'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Draft Items (Pending Confirmation) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-warning"><?= lang_line('is_draft_section', '⏳ Draft (Menunggu Konfirmasi)'); ?></h6>
            <?php if (!empty($drafts)): ?>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="bulkConfirmBtn" disabled>
                    <i class="fas fa-check-double"></i> <?= lang_line('is_btn_bulk_confirm', 'Confirm Terpilih'); ?>
                    (<span class="bulk-count">0</span>)
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled>
                    <i class="fas fa-trash-alt"></i> <?= lang_line('is_btn_bulk_delete', 'Hapus Terpilih'); ?>
                    (<span class="bulk-count">0</span>)
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($drafts)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th width="36" class="text-center">
                                    <input type="checkbox" id="checkAllDrafts" title="<?= lang_line('is_check_all', 'Pilih semua'); ?>">
                                </th>
                                <th><?= lang_line('is_th_code', 'Kode'); ?></th>
                                <th><?= lang_line('is_th_item_name', 'Nama Barang'); ?></th>
                                <th><?= lang_line('cr_category', 'Kategori'); ?></th>
                                <th width="10%"><?= lang_line('is_th_period_date', 'Tgl Periode'); ?></th>
                                <th width="10%"><?= lang_line('is_th_qty', 'Qty'); ?></th>
                                <th><?= lang_line('is_th_unit', 'Satuan'); ?></th>
                                <th><?= lang_line('is_th_notes', 'Catatan'); ?></th>
                                <th width="15%"><?= lang_line('is_th_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drafts as $draft): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="draft-check" value="<?= $draft->id ?>">
                                </td>
                                <td><small><?= htmlspecialchars($draft->kode_barang) ?></small></td>
                                <td><small><?= htmlspecialchars($draft->nama_barang) ?></small></td>
                                <td><span class="badge badge-<?= ($draft->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($draft->category ?? 'General') ?></span></td>
                                <td><small><?= date('d/m/Y', strtotime($draft->transaction_date)) ?></small></td>
                                <td class="text-right"><strong><?= number_format($draft->quantity, 2) ?></strong></td>
                                <td><small><?= htmlspecialchars($draft->satuan_desc) ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars($draft->notes) ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success confirm-btn" data-draft-id="<?= $draft->id ?>" title="<?= lang_line('is_btn_confirm_title', 'Confirm ke Stock Ledger'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('is_btn_confirm', 'Confirm'); ?>
                                    </button>
                                    <a href="<?= base_url('initial_stock/delete/' . $draft->id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang_line('is_confirm_delete', 'Hapus draft ini?'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3"><?= lang_line('is_no_draft', 'Tidak ada draft'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Confirmed Items -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-success"><?= lang_line('is_confirmed_section', '✅ Confirmed (Sudah di Stock Ledger)'); ?></h6>
            <div class="d-flex align-items-center">
                <span class="badge badge-success mr-2" style="font-size: 0.85rem;">
                    <?= lang_line('is_confirmed_total', 'Total'); ?>: <?= count($confirmed) ?>
                </span>
                <?php if (!empty($confirmed)): ?>
                <a href="<?= base_url('initial_stock/export_excel') ?>" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-file-excel"></i> <?= lang_line('is_btn_export', 'Export Excel'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($confirmed)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="confirmedTable" width="100%">
                        <thead class="bg-light">
                            <tr>
                                <th><?= lang_line('is_th_code', 'Kode'); ?></th>
                                <th><?= lang_line('is_th_item_name', 'Nama Barang'); ?></th>
                                <th><?= lang_line('cr_category', 'Kategori'); ?></th>
                                <th width="10%"><?= lang_line('is_th_period_date', 'Tgl Periode'); ?></th>
                                <th width="10%"><?= lang_line('is_th_qty', 'Qty'); ?></th>
                                <th><?= lang_line('is_th_unit', 'Satuan'); ?></th>
                                <th><?= lang_line('is_th_confirm_date', 'Tgl Confirm'); ?></th>
                                <th width="12%"><?= lang_line('is_th_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($confirmed as $item): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($item->kode_barang) ?></small></td>
                                <td><small><?= htmlspecialchars($item->nama_barang) ?></small></td>
                                <td><span class="badge badge-<?= ($item->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($item->category ?? 'General') ?></span></td>
                                <td><small><?= date('d/m/Y', strtotime($item->transaction_date)) ?></small></td>
                                <td class="text-right"><strong><?= number_format($item->quantity, 2) ?></strong></td>
                                <td><small><?= htmlspecialchars($item->satuan_desc) ?></small></td>
                                <td><small><?= date('d/m/Y', strtotime($item->confirmed_at)) ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning unconfirm-btn" data-draft-id="<?= $item->id ?>" title="<?= lang_line('is_btn_edit_title', 'Unconfirm untuk edit kembali'); ?>">
                                        <i class="fas fa-undo"></i> <?= lang_line('is_btn_edit', 'Edit'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-3"><?= lang_line('is_no_confirmed', 'Belum ada yang dikonfirmasi'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.card-header { font-size: 0.95rem; }
table.table-sm td { padding: 0.5rem; vertical-align: middle; }
.badge { font-size: 0.75rem; }

/* Perpanjang input fields */
.form-control,
.select2-container--default .select2-selection--single {
    height: 35px !important;
    font-size: 1rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    padding: 8px 0 !important;
    line-height: 30px !important;
}

#inputForm .form-group {
    margin-bottom: 1.5rem;
}

#inputForm label {
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
}

/* Buat input lebih lebar */
#itemSelect, #dateInput, #qtyInput, #unitDisplay, #notesInput {
    width: 100% !important;
    padding: 12px 15px !important;
    border: 1px solid #ddd !important;
    border-radius: 5px !important;
}

#itemSelect:focus, #dateInput:focus, #qtyInput:focus, #notesInput:focus {
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
}

/* Perlebar Select2 dropdown panel */
.select2-container--default .select2-dropdown {
    width: 80% !important;
    min-width: 300px !important;
}

.select2-container--default .select2-results__option {
    padding: 12px 15px !important;
    font-size: 1rem !important;
}

.select2-container--default .select2-results > .select2-results__options {
    max-height: 500px !important;
}
</style>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const I18N_IS = {
    select2_ph:       <?= json_encode(lang_line('is_js_select2_ph', 'Cari barang berdasarkan kode atau nama...')); ?>,
    no_results:       <?= json_encode(lang_line('is_js_no_results', 'Barang tidak ditemukan')); ?>,
    validation:       <?= json_encode(lang_line('is_js_validation', 'Pilih barang, tanggal periode, dan masukkan quantity')); ?>,
    confirm_ledger:   <?= json_encode(lang_line('is_js_confirm_ledger', 'Confirm ke Stock Ledger?')); ?>,
    unconfirm:        <?= json_encode(lang_line('is_js_unconfirm', 'Kembalikan ke Draft? Stock ledger akan dihapus.')); ?>,
    bulk_none:        <?= json_encode(lang_line('is_js_bulk_none', 'Pilih minimal satu draft terlebih dahulu.')); ?>,
    bulk_confirm:     <?= json_encode(lang_line('is_js_bulk_confirm', 'Confirm semua draft terpilih ke Stock Ledger?')); ?>,
    bulk_delete:      <?= json_encode(lang_line('is_js_bulk_delete', 'Hapus semua draft terpilih? Tindakan ini tidak dapat dibatalkan.')); ?>
};

$(document).ready(function() {
    // Initialize Select2
    $('#itemSelect').select2({
        placeholder: I18N_IS.select2_ph,
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return I18N_IS.no_results;
            }
        }
    });

    // Update satuan saat item dipilih
    $('#itemSelect').on('change', function() {
        var unit = $(this).find('option:selected').data('unit');
        $('#unitDisplay').val(unit || '');
    });

    // Set default date to today
    var today = new Date().toISOString().split('T')[0];
    $('#dateInput').val(today);

    // Simpan draft
    $('#saveDraftBtn').on('click', function() {
        var item_id = $('#itemSelect').val();
        var quantity = $('#qtyInput').val();
        var transaction_date = $('#dateInput').val();
        var notes = $('#notesInput').val();
        var category = $('#categoryInput').val();

        if (!item_id || !quantity || !transaction_date) {
            alert(I18N_IS.validation);
            return;
        }

        $.ajax({
            url: '<?= base_url('initial_stock/save') ?>',
            type: 'POST',
            data: { item_id: item_id, quantity: quantity, transaction_date: transaction_date, notes: notes, category: category },
            dataType: 'json',
            success: function(resp) {
                if (resp.status) {
                    alert(resp.message);
                    location.reload();
                } else {
                    alert('Error: ' + resp.message);
                }
            }
        });
    });

    // Confirm draft
    $(document).on('click', '.confirm-btn', function() {
        var draft_id = $(this).data('draft-id');
        if (!confirm(I18N_IS.confirm_ledger)) return;

        $.ajax({
            url: '<?= base_url('initial_stock/confirm') ?>',
            type: 'POST',
            data: { draft_id: draft_id },
            dataType: 'json',
            success: function(resp) {
                if (resp.status) {
                    alert(resp.message);
                    location.reload();
                } else {
                    alert('Error: ' + resp.message);
                }
            }
        });
    });

    // ===== Pagination tabel Confirmed (DataTables) =====
    if (window.jQuery && $.fn.DataTable && $('#confirmedTable').length) {
        $('#confirmedTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            lengthChange: true,
            info: true,
            responsive: true,
            pageLength: 20,
            lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, '<?= lang_line('is_dt_all', 'Semua'); ?>']],
            order: [], // pertahankan urutan dari server (terbaru dulu)
            columnDefs: [
                { orderable: false, targets: -1 } // kolom Aksi tidak bisa di-sort
            ],
            language: {
                search: "<?= lang_line('is_dt_search', 'Cari:'); ?>",
                lengthMenu: "<?= lang_line('is_dt_length', 'Tampilkan _MENU_ data per halaman'); ?>",
                zeroRecords: "<?= lang_line('is_dt_zero', 'Tidak ada data ditemukan'); ?>",
                info: "<?= lang_line('is_dt_info', 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data'); ?>",
                infoEmpty: "<?= lang_line('is_dt_info_empty', 'Tidak ada data tersedia'); ?>",
                infoFiltered: "<?= lang_line('is_dt_info_filtered', '(difilter dari total _MAX_ data)'); ?>",
                paginate: {
                    first: "<?= lang_line('is_dt_first', 'Awal'); ?>",
                    last: "<?= lang_line('is_dt_last', 'Akhir'); ?>",
                    next: "<?= lang_line('is_dt_next', 'Berikutnya'); ?>",
                    previous: "<?= lang_line('is_dt_prev', 'Sebelumnya'); ?>"
                }
            }
        });
    }

    // ===== Bulk selection (Draft) =====
    function refreshBulkState() {
        var n = $('.draft-check:checked').length;
        $('.bulk-count').text(n);
        $('#bulkConfirmBtn, #bulkDeleteBtn').prop('disabled', n === 0);
        var total = $('.draft-check').length;
        $('#checkAllDrafts').prop('checked', total > 0 && n === total);
    }

    $('#checkAllDrafts').on('change', function() {
        $('.draft-check').prop('checked', $(this).prop('checked'));
        refreshBulkState();
    });

    $(document).on('change', '.draft-check', refreshBulkState);

    function getSelectedDrafts() {
        return $('.draft-check:checked').map(function() { return $(this).val(); }).get();
    }

    function runBulk(url, ids, btn) {
        btn.prop('disabled', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: { draft_ids: ids },
            dataType: 'json',
            success: function(resp) {
                alert(resp.message || (resp.status ? 'Berhasil' : 'Gagal'));
                location.reload();
            },
            error: function() {
                alert('Terjadi kesalahan saat memproses permintaan.');
                btn.prop('disabled', false);
            }
        });
    }

    $('#bulkConfirmBtn').on('click', function() {
        var ids = getSelectedDrafts();
        if (!ids.length) { alert(I18N_IS.bulk_none); return; }
        if (!confirm(I18N_IS.bulk_confirm)) return;
        runBulk('<?= base_url('initial_stock/confirm_bulk') ?>', ids, $(this));
    });

    $('#bulkDeleteBtn').on('click', function() {
        var ids = getSelectedDrafts();
        if (!ids.length) { alert(I18N_IS.bulk_none); return; }
        if (!confirm(I18N_IS.bulk_delete)) return;
        runBulk('<?= base_url('initial_stock/delete_bulk') ?>', ids, $(this));
    });

    // Unconfirm
    $(document).on('click', '.unconfirm-btn', function() {
        var draft_id = $(this).data('draft-id');
        if (!confirm(I18N_IS.unconfirm)) return;

        $.ajax({
            url: '<?= base_url('initial_stock/unconfirm') ?>',
            type: 'POST',
            data: { draft_id: draft_id },
            dataType: 'json',
            success: function(resp) {
                if (resp.status) {
                    alert(resp.message);
                    location.reload();
                } else {
                    alert('Error: ' + resp.message);
                }
            }
        });
    });
});
</script>
