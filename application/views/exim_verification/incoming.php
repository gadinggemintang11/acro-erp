<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> <?= lang_line('exv_page_in_title', 'EXIM Verification - Pemasukan Barang'); ?>
        </h1>
        <a href="<?= site_url('exim_verification'); ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= lang_line('btn_back', 'Kembali'); ?>
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('exv_filter_title', 'Filter Pencarian'); ?></h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('exim_verification/incoming'); ?>">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_date_from', 'Tanggal Dari'); ?></label>
                            <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_date_to', 'Tanggal Sampai'); ?></label>
                            <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_supplier', 'Supplier'); ?></label>
                            <select name="supplier_id" class="form-control">
                                <option value=""><?= lang_line('exv_select_all_supplier', '-- Semua Supplier --'); ?></option>
                                <?php foreach($suppliers as $supplier): ?>
                                    <option value="<?= $supplier->id; ?>" <?= ($filters['supplier_id'] == $supplier->id ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($supplier->nama_supplier); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_bc_type', 'BC Type'); ?></label>
                            <select name="bc_type" class="form-control">
                                <option value=""><?= lang_line('exv_select_all_bc', '-- Semua BC Type --'); ?></option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= ($filters['bc_type'] == $bc->code ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($bc->description); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_status_verif', 'Status Verifikasi'); ?></label>
                            <select name="verified_status" class="form-control">
                                <option value="unverified" <?= ($filters['verified_status'] == 'unverified' ? 'selected' : ''); ?>><?= lang_line('exv_label_unverified', 'Belum Diverifikasi'); ?></option>
                                <option value="verified" <?= ($filters['verified_status'] == 'verified' ? 'selected' : ''); ?>><?= lang_line('exv_label_verified', 'Sudah Diverifikasi'); ?></option>
                                <option value="all" <?= ($filters['verified_status'] == 'all' ? 'selected' : ''); ?>><?= lang_line('exv_status_all', 'Semua'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> <?= lang_line('exv_btn_search', 'Cari'); ?>
                            </button>
                            <a href="<?= site_url('exim_verification/incoming'); ?>" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> <?= lang_line('exv_btn_reset', 'Reset'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('exv_in_list_title', 'List GRN - Penerimaan Barang'); ?></h6>
            <?php if (!empty($grn_list) && $filters['verified_status'] == 'unverified'): ?>
                <button type="button" class="btn btn-sm btn-success" onclick="bulkVerify()">
                    <i class="fas fa-check-double"></i> <?= lang_line('exv_btn_bulk_verify', 'Verifikasi Terpilih'); ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($grn_list)): ?>
                <p class="text-center text-muted"><?= lang_line('exv_no_grn_data', 'Tidak ada data GRN.'); ?></p>
            <?php else: ?>
                <form id="bulk-verify-form" method="post" action="<?= site_url('exim_verification/bulk_verify_incoming'); ?>">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable">
                            <thead class="thead-light">
                                <tr>
                                    <?php if ($filters['verified_status'] == 'unverified'): ?>
                                        <th width="30">
                                            <input type="checkbox" id="check-all">
                                        </th>
                                    <?php endif; ?>
                                    <th><?= lang_line('exv_th_grn_number', 'GRN Number'); ?></th>
                                    <th><?= lang_line('exv_th_date', 'Tanggal'); ?></th>
                                    <th><?= lang_line('exv_th_supplier', 'Supplier'); ?></th>
                                    <th><?= lang_line('exv_th_bc_type', 'BC Type'); ?></th>
                                    <th><?= lang_line('exv_th_bc_number', 'BC Number'); ?></th>
                                    <th><?= lang_line('exv_th_bc_date', 'BC Date'); ?></th>
                                    <th class="text-right"><?= lang_line('exv_th_item_count', 'Jumlah Item'); ?></th>
                                    <th class="text-right"><?= lang_line('exv_th_total_qty', 'Total Qty'); ?></th>
                                    <th><?= lang_line('exv_th_status', 'Status'); ?></th>
                                    <th><?= lang_line('exv_th_status_verif', 'Status Verifikasi'); ?></th>
                                    <th width="120"><?= lang_line('label_action', 'Aksi'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($grn_list as $grn): ?>
                                <tr>
                                    <?php if ($filters['verified_status'] == 'unverified'): ?>
                                        <td>
                                            <input type="checkbox" name="grn_ids[]" value="<?= $grn->id; ?>" class="grn-checkbox">
                                        </td>
                                    <?php endif; ?>
                                    <td><strong><?= htmlspecialchars($grn->grn_number); ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($grn->grn_date)); ?></td>
                                    <td><?= htmlspecialchars($grn->nama_supplier ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($grn->bc_type_code ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($grn->bc_number ?? '-'); ?></td>
                                    <td><?= $grn->bc_date ? date('d/m/Y', strtotime($grn->bc_date)) : '-'; ?></td>
                                    <td class="text-right"><?= number_format($grn->num_items, 0); ?></td>
                                    <td class="text-right"><?= number_format($grn->total_qty, 2); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?= $grn->status; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($grn->exim_verified): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> <?= lang_line('exv_badge_verified', 'Terverifikasi'); ?>
                                            </span><br>
                                            <small class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($grn->exim_verified_at)); ?><br>
                                                <?= lang_line('exv_label_by', 'oleh:'); ?> <?= htmlspecialchars($grn->verified_by_name ?? '-'); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> <?= lang_line('exv_badge_unverified', 'Belum Diverifikasi'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info mb-1" onclick="viewDetails(<?= $grn->id; ?>, '<?= htmlspecialchars($grn->grn_number, ENT_QUOTES); ?>')">
                                            <i class="fas fa-list"></i> <?= lang_line('exv_btn_detail', 'Detail'); ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary mb-1" onclick="editBC(<?= $grn->id; ?>, '<?= $grn->bc_type_code ?? ''; ?>', '<?= $grn->bc_number ?? ''; ?>', '<?= $grn->bc_date ?? ''; ?>')">
                                            <i class="fas fa-edit"></i> <?= lang_line('exv_btn_edit_bc', 'Edit BC'); ?>
                                        </button>
                                        <?php if ($grn->exim_verified): ?>
                                            <a href="<?= site_url('exim_verification/unverify_incoming/' . $grn->id); ?>"
                                               class="btn btn-sm btn-warning mb-1"
                                               onclick="return confirm('<?= lang_line('exv_confirm_unverify_grn', 'Yakin ingin membatalkan verifikasi GRN ini?'); ?>');">
                                                <i class="fas fa-undo"></i> <?= lang_line('exv_btn_cancel_verif', 'Batal'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= site_url('exim_verification/verify_incoming/' . $grn->id); ?>"
                                               class="btn btn-sm btn-success mb-1"
                                               onclick="return confirm('<?= lang_line('exv_confirm_verify_grn', 'Yakin ingin memverifikasi GRN ini?'); ?>');">
                                                <i class="fas fa-check"></i> <?= lang_line('exv_btn_verify', 'Verifikasi'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detail Items -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><?= lang_line('exv_modal_detail_grn', 'Detail Item GRN:'); ?> <span id="modal-grn-number"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detail-content">
                    <div class="text-center"><i class="fas fa-spinner fa-spin"></i> <?= lang_line('exv_modal_loading', 'Loading...'); ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('exv_modal_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit BC -->
<div class="modal fade" id="editBCModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><?= lang_line('exv_modal_edit_bc_grn', 'Edit BC Info - GRN'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editBCForm">
                <div class="modal-body">
                    <input type="hidden" id="edit-grn-id" name="grn_id">
                    <div class="form-group">
                        <label><?= lang_line('exv_label_bc_type', 'BC Type'); ?> <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit-bc-type" name="bc_type_code" required>
                            <option value=""><?= lang_line('exv_select_bc_type', '-- Pilih BC Type --'); ?></option>
                            <?php foreach($bc_types as $bc): ?>
                                <option value="<?= $bc->code; ?>"><?= htmlspecialchars($bc->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('exv_label_bc_number', 'BC Number'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-bc-number" name="bc_number" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('exv_label_bc_date', 'BC Date'); ?></label>
                        <input type="date" class="form-control" id="edit-bc-date" name="bc_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= lang_line('btn_save', 'Simpan'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const I18N_EXV = {
    select_min:        <?= json_encode(lang_line('exv_js_select_min_1', 'Pilih minimal 1 GRN untuk diverifikasi')); ?>,
    bulk_confirm:      <?= json_encode(lang_line('exv_js_bulk_confirm', 'Yakin ingin memverifikasi %d GRN yang dipilih?')); ?>,
    loading:           <?= json_encode(lang_line('exv_modal_loading', 'Loading...')); ?>,
    th_no:             <?= json_encode(lang_line('exv_jsmodal_th_no', 'No')); ?>,
    th_code:           <?= json_encode(lang_line('exv_jsmodal_th_code', 'Kode Barang')); ?>,
    th_name:           <?= json_encode(lang_line('exv_jsmodal_th_name', 'Nama Barang')); ?>,
    th_qty:            <?= json_encode(lang_line('exv_jsmodal_th_qty', 'Qty')); ?>,
    th_unit:           <?= json_encode(lang_line('exv_jsmodal_th_unit', 'Satuan')); ?>,
    th_unit_value:     <?= json_encode(lang_line('exv_jsmodal_th_unit_value', 'Unit Value')); ?>,
    no_items:          <?= json_encode(lang_line('exv_jsmodal_no_items', 'Tidak ada detail item')); ?>,
    err_load:          <?= json_encode(lang_line('exv_js_err_load', 'Terjadi kesalahan saat memuat data')); ?>,
    err_save:          <?= json_encode(lang_line('exv_js_err_save', 'Terjadi kesalahan saat menyimpan data')); ?>
};

// Check all checkboxes
document.getElementById('check-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.grn-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Bulk verify function
function bulkVerify() {
    const checkboxes = document.querySelectorAll('.grn-checkbox:checked');
    if (checkboxes.length === 0) {
        alert(I18N_EXV.select_min);
        return;
    }

    if (confirm(I18N_EXV.bulk_confirm.replace('%d', checkboxes.length))) {
        document.getElementById('bulk-verify-form').submit();
    }
}

// View details function
function viewDetails(grnId, grnNumber) {
    $('#modal-grn-number').text(grnNumber);
    $('#detailModal').modal('show');
    $('#detail-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> ' + I18N_EXV.loading + '</div>');

    $.ajax({
        url: '<?= site_url('exim_verification/get_grn_details/'); ?>' + grnId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                html += '<thead class="thead-light"><tr><th>' + I18N_EXV.th_no + '</th><th>' + I18N_EXV.th_code + '</th><th>' + I18N_EXV.th_name + '</th><th>' + I18N_EXV.th_qty + '</th><th>' + I18N_EXV.th_unit + '</th><th>' + I18N_EXV.th_unit_value + '</th></tr></thead><tbody>';

                if (response.data.length === 0) {
                    html += '<tr><td colspan="6" class="text-center">' + I18N_EXV.no_items + '</td></tr>';
                } else {
                    response.data.forEach(function(item, index) {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + (item.item_code || '-') + '</td>';
                        html += '<td>' + (item.item_name || '-') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.receive_qty).toFixed(2) + '</td>';
                        html += '<td>' + (item.item_unit || '-') + '</td>';
                        html += '<td class="text-right">' + parseFloat(item.unit_value || 0).toLocaleString('id-ID') + '</td>';
                        html += '</tr>';
                    });
                }
                html += '</tbody></table></div>';
                $('#detail-content').html(html);
            } else {
                $('#detail-content').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#detail-content').html('<div class="alert alert-danger">' + I18N_EXV.err_load + '</div>');
        }
    });
}

// Edit BC function
function editBC(grnId, bcType, bcNumber, bcDate) {
    $('#edit-grn-id').val(grnId);
    $('#edit-bc-type').val(bcType);
    $('#edit-bc-number').val(bcNumber);
    $('#edit-bc-date').val(bcDate);
    $('#editBCModal').modal('show');
}

// Handle BC edit form submission
$('#editBCForm').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: '<?= site_url('exim_verification/update_grn_bc'); ?>',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                $('#editBCModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert(I18N_EXV.err_save);
        }
    });
});
</script>
