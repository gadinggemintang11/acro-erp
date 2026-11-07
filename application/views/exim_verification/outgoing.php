<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-truck-loading"></i> <?= lang_line('exv_page_out_title', 'EXIM Verification - Pengeluaran Barang'); ?>
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
            <form method="get" action="<?= site_url('exim_verification/outgoing'); ?>">
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
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_doc_type', 'Tipe Dokumen'); ?></label>
                            <select name="doc_type" class="form-control">
                                <option value=""><?= lang_line('exv_select_all', '-- Semua --'); ?></option>
                                <option value="FG_OUT" <?= ($filters['doc_type'] == 'FG_OUT' ? 'selected' : ''); ?>><?= lang_line('exv_type_fg_delivery', 'FG Delivery'); ?></option>
                                <option value="SCRAP_OUT" <?= ($filters['doc_type'] == 'SCRAP_OUT' ? 'selected' : ''); ?>><?= lang_line('exv_type_scrap_out', 'Scrap Out'); ?></option>
                                <option value="SUBKON_OUT" <?= ($filters['doc_type'] == 'SUBKON_OUT' ? 'selected' : ''); ?>><?= lang_line('exv_type_subkon_out', 'Subkon Out'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_bc_type', 'BC Type'); ?></label>
                            <select name="bc_type" class="form-control">
                                <option value=""><?= lang_line('exv_select_all', '-- Semua --'); ?></option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= ($filters['bc_type'] == $bc->code ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($bc->description); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= lang_line('exv_label_status_verif', 'Status Verifikasi'); ?></label>
                            <select name="verified_status" class="form-control">
                                <option value="unverified" <?= ($filters['verified_status'] == 'unverified' ? 'selected' : ''); ?>><?= lang_line('exv_status_short_unverified', 'Belum'); ?></option>
                                <option value="verified" <?= ($filters['verified_status'] == 'verified' ? 'selected' : ''); ?>><?= lang_line('exv_status_short_verified', 'Sudah'); ?></option>
                                <option value="all" <?= ($filters['verified_status'] == 'all' ? 'selected' : ''); ?>><?= lang_line('exv_status_all', 'Semua'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> <?= lang_line('exv_btn_search', 'Cari'); ?>
                        </button>
                        <a href="<?= site_url('exim_verification/outgoing'); ?>" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> <?= lang_line('exv_btn_reset', 'Reset'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('exv_out_list_title', 'List Dokumen Pengeluaran'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($outgoing_list)): ?>
                <p class="text-center text-muted"><?= lang_line('exv_no_out_data', 'Tidak ada data pengeluaran.'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('exv_th_doc_type', 'Tipe'); ?></th>
                                <th><?= lang_line('exv_th_doc_number', 'No. Dokumen'); ?></th>
                                <th><?= lang_line('exv_th_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('exv_th_partner', 'Partner'); ?></th>
                                <th><?= lang_line('exv_th_bc_type', 'BC Type'); ?></th>
                                <th><?= lang_line('exv_th_bc_number', 'BC Number'); ?></th>
                                <th><?= lang_line('exv_th_bc_date', 'BC Date'); ?></th>
                                <th class="text-right"><?= lang_line('exv_th_item_count', 'Jumlah Item'); ?></th>
                                <th class="text-right"><?= lang_line('exv_th_total_qty', 'Total Qty'); ?></th>
                                <th><?= lang_line('exv_th_status', 'Status'); ?></th>
                                <th><?= lang_line('exv_th_status_verif', 'Status Verifikasi'); ?></th>
                                <th width="180"><?= lang_line('label_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($outgoing_list as $doc): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?= $doc->doc_type == 'FG_OUT' ? 'info' : ($doc->doc_type == 'SCRAP_OUT' ? 'warning' : 'secondary'); ?>">
                                        <?= $doc->doc_type; ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($doc->doc_number); ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($doc->doc_date)); ?></td>
                                <td><?= htmlspecialchars($doc->customer_name ?? '-'); ?></td>
                                <td><?= htmlspecialchars($doc->bc_type_description ?? $doc->bc_type_id ?? '-'); ?></td>
                                <td><?= htmlspecialchars($doc->bc_number ?? '-'); ?></td>
                                <td><?= $doc->bc_date ? date('d/m/Y', strtotime($doc->bc_date)) : '-'; ?></td>
                                <td class="text-right"><?= number_format($doc->num_items, 0); ?></td>
                                <td class="text-right"><?= number_format($doc->total_qty, 2); ?></td>
                                <td>
                                    <span class="badge badge-success"><?= $doc->status; ?></span>
                                </td>
                                <td>
                                    <?php if ($doc->exim_verified): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> <?= lang_line('exv_badge_verified', 'Terverifikasi'); ?>
                                        </span><br>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($doc->exim_verified_at)); ?><br>
                                            <?= lang_line('exv_label_by', 'oleh:'); ?> <?= htmlspecialchars($doc->verified_by_name ?? '-'); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> <?= lang_line('exv_badge_unverified', 'Belum Diverifikasi'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info mb-1" onclick="viewDetails('<?= $doc->doc_type; ?>', <?= $doc->id; ?>, '<?= htmlspecialchars($doc->doc_number, ENT_QUOTES); ?>')">
                                        <i class="fas fa-list"></i> <?= lang_line('exv_btn_detail', 'Detail'); ?>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary mb-1" onclick="editBC('<?= $doc->doc_type; ?>', <?= $doc->id; ?>, '<?= $doc->bc_type_code ?? ($doc->bc_type_id ?? ''); ?>', '<?= $doc->bc_number ?? ''; ?>', '<?= $doc->bc_date ?? ''; ?>')">
                                        <i class="fas fa-edit"></i> <?= lang_line('exv_btn_edit_bc', 'Edit BC'); ?>
                                    </button>
                                    <?php if ($doc->exim_verified): ?>
                                        <form method="post" action="<?= site_url('exim_verification/unverify_outgoing'); ?>" style="display:inline;" onsubmit="return confirm('<?= lang_line('exv_confirm_unverify', 'Yakin ingin membatalkan verifikasi?'); ?>');">
                                            <input type="hidden" name="doc_type" value="<?= $doc->doc_type; ?>">
                                            <input type="hidden" name="doc_id" value="<?= $doc->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning mb-1">
                                                <i class="fas fa-undo"></i> <?= lang_line('exv_btn_cancel_verif', 'Batal'); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('exim_verification/verify_outgoing'); ?>" style="display:inline;" onsubmit="return confirm('<?= lang_line('exv_confirm_verify', 'Yakin ingin memverifikasi?'); ?>');">
                                            <input type="hidden" name="doc_type" value="<?= $doc->doc_type; ?>">
                                            <input type="hidden" name="doc_id" value="<?= $doc->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-success mb-1">
                                                <i class="fas fa-check"></i> <?= lang_line('exv_btn_verify', 'Verifikasi'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detail Items -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><?= lang_line('exv_modal_detail_doc', 'Detail Item:'); ?> <span id="modal-doc-number"></span></h5>
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
                <h5 class="modal-title"><?= lang_line('exv_modal_edit_bc', 'Edit BC Info -'); ?> <span id="modal-doc-type-label"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editBCForm">
                <div class="modal-body">
                    <input type="hidden" id="edit-doc-type" name="doc_type">
                    <input type="hidden" id="edit-doc-id" name="doc_id">
                    <div class="form-group">
                        <label><?= lang_line('exv_label_bc_type', 'BC Type'); ?> <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit-bc-type" name="bc_type" required>
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
const I18N_EXV_OUT = {
    loading:           <?= json_encode(lang_line('exv_modal_loading', 'Loading...')); ?>,
    th_no:             <?= json_encode(lang_line('exv_jsmodal_th_no', 'No')); ?>,
    th_code:           <?= json_encode(lang_line('exv_jsmodal_th_code', 'Kode Barang')); ?>,
    th_name:           <?= json_encode(lang_line('exv_jsmodal_th_name', 'Nama Barang')); ?>,
    th_qty:            <?= json_encode(lang_line('exv_jsmodal_th_qty', 'Qty')); ?>,
    th_unit:           <?= json_encode(lang_line('exv_jsmodal_th_unit', 'Satuan')); ?>,
    th_contract:       <?= json_encode(lang_line('exv_jsmodal_th_contract', 'Contract')); ?>,
    th_unit_price:     <?= json_encode(lang_line('exv_jsmodal_th_unit_price', 'Unit Price')); ?>,
    th_currency:       <?= json_encode(lang_line('exv_jsmodal_th_currency', 'Currency')); ?>,
    th_sales_value:    <?= json_encode(lang_line('exv_jsmodal_th_sales_value', 'Sales Value')); ?>,
    th_wo_number:      <?= json_encode(lang_line('exv_jsmodal_th_wo_number', 'WO Number')); ?>,
    th_unit_idr:       <?= json_encode(lang_line('exv_jsmodal_th_unit_idr', 'Unit Value (IDR)')); ?>,
    no_items:          <?= json_encode(lang_line('exv_jsmodal_no_items', 'Tidak ada detail item')); ?>,
    err_load:          <?= json_encode(lang_line('exv_js_err_load', 'Terjadi kesalahan saat memuat data')); ?>,
    err_save:          <?= json_encode(lang_line('exv_js_err_save', 'Terjadi kesalahan saat menyimpan data')); ?>,
    unknown_type:      <?= json_encode(lang_line('exv_js_unknown_type', 'Tipe dokumen tidak dikenali')); ?>,
    type_unavailable:  <?= json_encode(lang_line('exv_js_type_unavailable', 'Detail untuk tipe ini belum tersedia')); ?>
};

// View details function
function viewDetails(docType, docId, docNumber) {
    $('#modal-doc-number').text(docNumber);
    $('#detailModal').modal('show');
    $('#detail-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> ' + I18N_EXV_OUT.loading + '</div>');

    let url = '';
    if (docType === 'FG_OUT') {
        url = '<?= site_url('exim_verification/get_fg_delivery_details/'); ?>' + docId;
    } else if (docType === 'SCRAP_OUT') {
        url = '<?= site_url('exim_verification/get_scrap_out_details/'); ?>' + docId;
    } else if (docType === 'SUBKON_OUT') {
        url = '<?= site_url('exim_verification/get_subkon_out_details/'); ?>' + docId;
    } else if (docType === 'RTN_SUPPLIER') {
        url = '<?= site_url('exim_verification/get_rtn_details/'); ?>' + docId;
    } else {
        $('#detail-content').html('<div class="alert alert-warning">' + I18N_EXV_OUT.type_unavailable + '</div>');
        return;
    }

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                html += '<thead class="thead-light"><tr><th>' + I18N_EXV_OUT.th_no + '</th><th>' + I18N_EXV_OUT.th_code + '</th><th>' + I18N_EXV_OUT.th_name + '</th><th>' + I18N_EXV_OUT.th_qty + '</th><th>' + I18N_EXV_OUT.th_unit + '</th>';

                if (docType === 'FG_OUT') {
                    html += '<th>' + I18N_EXV_OUT.th_contract + '</th><th>' + I18N_EXV_OUT.th_unit_price + '</th><th>' + I18N_EXV_OUT.th_currency + '</th>';
                } else if (docType === 'SCRAP_OUT') {
                    html += '<th>' + I18N_EXV_OUT.th_sales_value + '</th>';
                } else if (docType === 'SUBKON_OUT') {
                    html += '<th>' + I18N_EXV_OUT.th_wo_number + '</th>';
                } else if (docType === 'RTN_SUPPLIER') {
                    html += '<th>' + I18N_EXV_OUT.th_unit_idr + '</th>';
                }

                html += '</tr></thead><tbody>';

                if (response.data.length === 0) {
                    html += '<tr><td colspan="8" class="text-center">' + I18N_EXV_OUT.no_items + '</td></tr>';
                } else {
                    response.data.forEach(function(item, index) {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + (item.item_code || '-') + '</td>';
                        html += '<td>' + (item.item_name || '-') + '</td>';

                        if (docType === 'FG_OUT') {
                            html += '<td class="text-right">' + parseFloat(item.qty_shipped || 0).toFixed(2) + '</td>';
                        } else if (docType === 'SCRAP_OUT' || docType === 'SUBKON_OUT' || docType === 'RTN_SUPPLIER') {
                            html += '<td class="text-right">' + parseFloat(item.qty || 0).toFixed(2) + '</td>';
                        }

                        html += '<td>' + (item.item_unit || '-') + '</td>';

                        if (docType === 'FG_OUT') {
                            html += '<td>' + (item.contract_number || '-') + '</td>';
                            html += '<td class="text-right">' + parseFloat(item.unit_price || 0).toLocaleString('id-ID') + '</td>';
                            html += '<td>' + (item.currency_code || '-') + '</td>';
                        } else if (docType === 'SCRAP_OUT') {
                            html += '<td class="text-right">' + parseFloat(item.sales_value_per_unit || 0).toLocaleString('id-ID') + '</td>';
                        } else if (docType === 'SUBKON_OUT') {
                            html += '<td>' + (item.wo_number || '-') + '</td>';
                        } else if (docType === 'RTN_SUPPLIER') {
                            html += '<td class="text-right">' + (item.unit_value_idr ? parseFloat(item.unit_value_idr).toLocaleString('id-ID') : '-') + '</td>';
                        }

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
            $('#detail-content').html('<div class="alert alert-danger">' + I18N_EXV_OUT.err_load + '</div>');
        }
    });
}

// Edit BC function
function editBC(docType, docId, bcType, bcNumber, bcDate) {
    $('#edit-doc-type').val(docType);
    $('#edit-doc-id').val(docId);
    $('#edit-bc-type').val(bcType);
    $('#edit-bc-number').val(bcNumber);
    $('#edit-bc-date').val(bcDate);
    $('#modal-doc-type-label').text(docType);
    $('#editBCModal').modal('show');
}

// Handle BC edit form submission
$('#editBCForm').on('submit', function(e) {
    e.preventDefault();

    const docType = $('#edit-doc-type').val();
    let url = '';

    if (docType === 'FG_OUT') {
        url = '<?= site_url('exim_verification/update_fg_delivery_bc'); ?>';
        // Rename field for FG
        const formData = new FormData(this);
        formData.set('delivery_id', formData.get('doc_id'));
        formData.set('bc_type_id', formData.get('bc_type'));
        formData.delete('doc_id');
        formData.delete('doc_type');
        formData.delete('bc_type');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: handleBCUpdateResponse,
            error: handleBCUpdateError
        });
    } else if (docType === 'SCRAP_OUT' || docType === 'SUBKON_OUT') {
        url = docType === 'SCRAP_OUT'
            ? '<?= site_url('exim_verification/update_scrap_out_bc'); ?>'
            : '<?= site_url('exim_verification/update_subkon_out_bc'); ?>';
        // Rename field for Scrap/Subkon
        const formData = new FormData(this);
        formData.set('header_id', formData.get('doc_id'));
        formData.set('bc_type_code', formData.get('bc_type'));
        formData.delete('doc_id');
        formData.delete('doc_type');
        formData.delete('bc_type');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: handleBCUpdateResponse,
            error: handleBCUpdateError
        });
    } else {
        alert(I18N_EXV_OUT.unknown_type);
    }
});

function handleBCUpdateResponse(response) {
    if (response.success) {
        alert(response.message);
        $('#editBCModal').modal('hide');
        location.reload();
    } else {
        alert('Error: ' + response.message);
    }
}

function handleBCUpdateError() {
    alert(I18N_EXV_OUT.err_save);
}
</script>
