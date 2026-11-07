<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i> <?= lang_line('purchase_request_approval_title_detail', 'Review Purchase Request'); ?></h3>
            <p class="text-muted mb-0">
                <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                &nbsp;|&nbsp;
                <?php $badge = ['Draft'=>'secondary','Submitted'=>'warning','Approved'=>'success','Rejected'=>'danger'][$header->status] ?? 'secondary'; ?>
                <span class="badge badge-<?= $badge ?> badge-lg"><?= $header->status ?></span>
            </p>
        </div>
        <div>
            <a href="<?= base_url('purchase_request_approval/export_detail_excel/'.$header->id) ?>" target="_blank" class="btn btn-success btn-sm no-loader mr-1"><i class="fas fa-file-excel fa-sm"></i> Excel</a>
            <a href="<?= base_url('purchase_request_approval/export_detail_pdf/'.$header->id) ?>" target="_blank" class="btn btn-danger btn-sm no-loader mr-1"><i class="fas fa-file-pdf fa-sm"></i> PDF</a>
            <a href="<?= base_url('purchase_request_approval') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('purchase_request_approval_back_button', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <?php if ($header->status === 'Rejected' && $header->rejection_notes): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle mr-1"></i>
            <strong><?= lang_line('purchase_request_approval_rejected_alert_text', 'Ditolak:'); ?></strong> <?= htmlspecialchars($header->rejection_notes) ?>
            <?php if ($header->approver_name): ?>
                &mdash; <?= lang_line('purchase_request_approval_rejected_alert_by', 'oleh'); ?> <strong><?= htmlspecialchars($header->approver_name) ?></strong>
                <?= lang_line('purchase_request_approval_rejected_alert_on', 'pada'); ?> <?= date('d M Y H:i', strtotime($header->approved_at)) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Info Header -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_approval_info_title', 'Informasi Purchase Request'); ?></h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_number', 'PR Number'); ?></small>
                    <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_pr_date', 'Tanggal PR'); ?></small>
                    <strong><?= date('d M Y', strtotime($header->pr_date)) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_requester', 'Requester'); ?></small>
                    <strong><?= htmlspecialchars($header->requester_name ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_supplier_proposed', 'Supplier (Usulan)'); ?></small>
                    <strong><?= htmlspecialchars($header->nama_supplier ?? lang_line('purchase_request_approval_not_yet_determined', 'Belum ditentukan')); ?></strong>
                </div>
            </div>
            <?php if ($header->purpose): ?>
            <hr class="my-3">
            <div>
                <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_purpose', 'Keperluan / Tujuan'); ?></small>
                <strong><?= htmlspecialchars($header->purpose) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($header->remarks): ?>
            <div class="mt-2">
                <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_remarks', 'Keterangan'); ?></small>
                <?= nl2br(htmlspecialchars($header->remarks)) ?>
            </div>
            <?php endif; ?>
            <?php if ($header->status === 'Approved'): ?>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_approved_by', 'Disetujui Oleh'); ?></small>
                    <strong class="text-success"><?= htmlspecialchars($header->approver_name ?? '-') ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approval_label_approval_date', 'Tanggal Approval'); ?></small>
                    <strong><?= $header->approved_at ? date('d M Y H:i', strtotime($header->approved_at)) : '-' ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($can_approve): ?>
    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <strong><?= lang_line('purchase_request_approval_review_mode_title', 'Mode Review:'); ?></strong> <?= lang_line('purchase_request_approval_review_mode_desc', 'Anda dapat mengedit qty atau menghapus item yang tidak disetujui sebelum memberi keputusan.'); ?>
    </div>
    <?php endif; ?>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_approval_detail_items_title', 'Detail Item Request'); ?></h6>
            <span class="badge badge-primary" id="item-count"><?= count($details) ?> <?= lang_line('text_item', 'item'); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="approvalItemTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('label_no', 'No'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_code', 'Kode Item'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_name', 'Nama Item'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_type', 'Tipe'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_ipo_sc', 'Sales Order / SC'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_qty', 'Qty Request'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_item_description', 'Deskripsi'); ?></th>
                            <?php if ($can_approve): ?><th width="90"><?= lang_line('label_action', 'Aksi'); ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="approvalItemBody">
                        <?php if (empty($details)): ?>
                            <tr id="noItemRow"><td colspan="<?= $can_approve ? 9 : 8 ?>" class="text-center text-muted py-3"><?= lang_line('pra_no_items'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($details as $i => $d): ?>
                            <?php $tc = ['material'=>'primary','machine'=>'warning','inventory'=>'info','semifinished'=>'secondary'][$d->item_type] ?? 'secondary'; ?>
                            <tr id="arow-<?= $d->id ?>">
                                <td class="text-center row-no"><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($d->item_code ?? '-') ?></code></td>
                                <td><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                                <td class="text-center"><span class="badge badge-<?= $tc ?>"><?= ucfirst($d->item_type) ?></span></td>
                                <td class="text-center"><small><?= htmlspecialchars($d->sc_number ?? '-') ?></small></td>
                                <td class="text-center">
                                    <strong class="item-qty-display"><?= number_format($d->qty, 2) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($d->item_unit ?? '') ?></small>
                                </td>
                                <td class="text-center"><small><?= htmlspecialchars($d->item_unit ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($d->description ?? '-') ?></small></td>
                                <?php if ($can_approve): ?>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-warning btn-edit-qty"
                                            data-id="<?= $d->id ?>"
                                            data-qty="<?= $d->qty ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            title="<?= lang_line('pra_tooltip_edit_qty'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-item"
                                            data-id="<?= $d->id ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            title="<?= lang_line('pra_tooltip_delete_item'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Action Bar -->
    <?php if ($can_approve || $can_unsubmit): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_approval_decision_title', 'Keputusan'); ?></h6></div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php if ($can_approve): ?>
                <button type="button" class="btn btn-success mr-2" id="btnApprove">
                    <i class="fas fa-check-circle mr-1"></i> <?= lang_line('purchase_request_approval_approve_button', 'Setujui PR'); ?>
                </button>
                <button type="button" class="btn btn-danger mr-2" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times-circle mr-1"></i> <?= lang_line('purchase_request_approval_reject_button', 'Tolak PR'); ?>
                </button>
                <?php endif; ?>
                <?php if ($can_unsubmit): ?>
                <a href="<?= base_url('purchase_request_approval/unsubmit_pr/' . $header->id) ?>"
                   class="btn btn-outline-warning"
                   onclick="return confirm('<?= lang_line('purchase_request_approval_return_to_draft_confirm', 'Kembalikan PR ke status Draft?'); ?>')">
                    <i class="fas fa-undo mr-1"></i> <?= lang_line('purchase_request_approval_return_to_draft_button', 'Kembalikan ke Draft'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php elseif ($header->status === 'Approved'): ?>
    <div class="card border-0 shadow-sm border-left-success">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> <?= lang_line('purchase_request_approval_approved_title', 'PR Telah Disetujui'); ?></span>
                <?php if ($header->approver_name): ?>
                    <small class="text-muted ml-2"><?= lang_line('purchase_request_approval_approved_by', 'oleh'); ?> <strong><?= htmlspecialchars($header->approver_name) ?></strong>
                    <?= $header->approved_at ? lang_line('purchase_request_approval_approved_on', 'pada') . ' ' . date('d M Y H:i', strtotime($header->approved_at)) : '' ?></small>
                <?php endif; ?>
            </div>
            <?php if (check_access('purchase_order', 'add')): ?>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createPoModal">
                <i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('purchase_request_approval_create_po_button', 'Buat PO dari PR'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($header->status === 'Rejected'): ?>
    <div class="alert alert-danger border-0 shadow-sm">
        <i class="fas fa-times-circle mr-1"></i> <?= lang_line('purchase_request_approval_rejected_desc', 'PR ini telah ditolak dan tidak dapat diproses lebih lanjut.'); ?>
    </div>
    <?php endif; ?>

</div>

<!-- Edit Qty Modal -->
<div class="modal fade" id="editQtyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i> <?= lang_line('purchase_request_approval_edit_qty_title', 'Edit Qty Item'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted small" id="editQtyItemName"></p>
                <input type="hidden" id="editQtyDetailId">
                <div class="form-group mb-0">
                    <label><?= lang_line('purchase_request_approval_edit_qty_label', 'Qty yang Disetujui'); ?> <span class="text-danger">*</span></label>
                    <input type="number" id="editQtyValue" class="form-control" step="any" min="0.01" placeholder="0">
                    <small class="text-muted"><?= lang_line('purchase_request_approval_edit_qty_help', 'Ubah qty sesuai kebutuhan yang disetujui'); ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btnSaveQty">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('purchase_request_approval_edit_qty_save', 'Simpan Qty'); ?>
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('purchase_request_approval_edit_qty_cancel', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> <?= lang_line('purchase_request_approval_reject_modal_title', 'Tolak Purchase Request'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label><?= lang_line('purchase_request_approval_reject_reason_label', 'Alasan Penolakan'); ?> <span class="text-danger">*</span></label>
                    <textarea id="rejectionNotes" class="form-control" rows="4"
                              placeholder="<?= lang_line('purchase_request_approval_reject_reason_placeholder', 'Tuliskan alasan penolakan secara jelas...'); ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btnConfirmReject">
                    <i class="fas fa-times mr-1"></i> <?= lang_line('purchase_request_approval_reject_confirm_button', 'Tolak PR'); ?>
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('purchase_request_approval_reject_cancel_button', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Create PO Modal -->
<div class="modal fade" id="createPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 500px;">
        <form id="createPoForm" method="POST" action="<?= base_url('purchase_request/create_po_from_pr/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-arrow-right mr-2"></i> <?= lang_line('purchase_request_approval_create_po_modal_title', 'Buat Purchase Order'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-2"></i> <?= lang_line('purchase_request_approval_create_po_modal_info', 'Pilih supplier yang akan mengerjakan PR ini'); ?></p>

                    <!-- Supplier Selection -->
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_approval_create_po_supplier_label', 'Pilih Supplier'); ?> <span class="text-danger">*</span></label>
                        <select id="supplierSelect" name="supplier_id" class="form-control select2-supplier">
                            <option value=""><?= lang_line('purchase_request_approval_create_po_supplier_placeholder', '-- Pilih Supplier --'); ?></option>
                            <?php foreach ($suppliers ?? [] as $supplier): ?>
                                <option value="<?= $supplier->id ?>"><?= htmlspecialchars($supplier->nama_supplier) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php
                    // Pilihan item + qty parsial. Boleh ambil sebagian; sisanya masih bisa
                    // ditarik ke PO lain lewat tombol "Tarik dari PR" di halaman PO.
                    $this->load->view('templates/partials/create_po_items', [
                        'details'     => $details ?? [],
                        'po_progress' => $po_progress ?? [],
                    ]);
                    ?>

                    <!-- PPN Section -->
                    <hr class="my-3">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="ppnCheckbox" name="include_ppn" value="1">
                            <label class="custom-control-label" for="ppnCheckbox">
                                <strong><?= lang_line('purchase_request_approval_create_po_tax_checkbox', 'Tambahkan Pajak (PPN / PPH)'); ?></strong>
                            </label>
                        </div>
                    </div>

                    <!-- PPN Rate (shown when checked) -->
                    <div id="ppnRateGroup" class="form-group" style="display: none;">
                        <label><?= lang_line('purchase_request_approval_create_po_tax_rate_label', 'Tarif Pajak (%)'); ?></label>
                        <input type="number" id="ppnRate" name="ppn_rate" class="form-control" value="10" min="0" max="100" step="0.01" placeholder="10">
                        <small class="text-muted"><?= lang_line('purchase_request_approval_create_po_tax_rate_help', 'Contoh: 10 (PPN 10%), 11 (PPN 11%), 2 (PPH 2%), atau nilai custom lainnya'); ?></small>
                    </div>

                    <!-- Summary (shown when checked) -->
                    <div id="ppnSummary" style="display: none;">
                        <div class="alert alert-info small mb-0">
                            <div class="row mb-2">
                                <div class="col-12">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong><?= lang_line('purchase_request_approval_create_po_tax_summary', 'Pajak'); ?> <span id="ppnRateDisplay">10</span>%</strong> <?= lang_line('purchase_request_approval_create_po_tax_summary_info', 'akan ditambahkan ke subtotal setelah harga item diinput.'); ?>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row small text-muted">
                                <div class="col-12">
                                    <strong><?= lang_line('purchase_request_approval_create_po_tax_formula', 'Rumus: Total = Subtotal + (Subtotal ×'); ?></strong> <span id="ppnRateDisplay2">10</span>%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('purchase_request_approval_create_po_cancel_button', 'Batal'); ?></button>
                    <button type="submit" id="submitCreatePo" class="btn btn-primary" disabled><i class="fas fa-check mr-1"></i> <?= lang_line('purchase_request_approval_create_po_submit_button', 'Buat PO'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
var BASE_URL  = '<?= base_url() ?>';
var PR_ID     = <?= $header->id ?>;
var CAN_APPROVE = <?= $can_approve ? 'true' : 'false' ?>;

// Language strings
var LANG = {
    qtyErrorZero: '<?= lang_line('purchase_request_approval_qty_error_zero', 'Qty harus lebih dari 0'); ?>',
    qtyUpdateFailed: '<?= lang_line('purchase_request_approval_qty_update_failed', 'Gagal memperbarui qty.'); ?>',
    serverError: '<?= lang_line('purchase_request_approval_server_error', 'Gagal menghubungi server ('); ?>',
    confirmDeleteItem: '<?= lang_line('purchase_request_approval_confirm_delete_item', 'Hapus item dari PR ini? Item yang dihapus tidak akan masuk ke PO jika PR disetujui.'); ?>',
    allItemsDeleted: '<?= lang_line('purchase_request_approval_all_items_deleted', 'Semua item telah dihapus.'); ?>',
    deleteItemFailed: '<?= lang_line('purchase_request_approval_delete_item_failed', 'Gagal menghapus item.'); ?>',
    noItemsApprove: '<?= lang_line('purchase_request_approval_no_items_approve', 'Tidak bisa menyetujui PR tanpa item.'); ?>',
    confirmApprove: '<?= lang_line('purchase_request_approval_confirm_approve', 'Setujui Purchase Request ini? item akan dilanjutkan ke proses Purchase Order.'); ?>',
    approveSuccess: '<?= lang_line('purchase_request_approval_approve_success', 'PR berhasil disetujui.'); ?>',
    approveFailed: '<?= lang_line('purchase_request_approval_approve_failed', 'Gagal menyetujui PR.'); ?>',
    rejectReasonRequired: '<?= lang_line('purchase_request_approval_confirm_reject_label', 'Alasan penolakan harus diisi.'); ?>',
    rejectSuccess: '<?= lang_line('purchase_request_approval_reject_success', 'PR ditolak.'); ?>',
    rejectFailed: '<?= lang_line('purchase_request_approval_reject_failed', 'Gagal menolak PR.'); ?>',
    approving: '<?= lang_line('purchase_request_approval_approving', 'Menyetujui...'); ?>',
    rejecting: '<?= lang_line('purchase_request_approval_rejecting', 'Menolak...'); ?>'
};

$(document).ready(function() {

    // Select2 for create PO modal supplier
    $('.select2-supplier').select2({ dropdownParent: $('#createPoModal'), width: '100%' });

    // Enable/disable submit button based on supplier selection
    $('#supplierSelect').on('change', function() {
        var supplierValue = $(this).val();
        if (supplierValue) {
            $('#submitCreatePo').prop('disabled', false);
        } else {
            $('#submitCreatePo').prop('disabled', true);
        }
    });

    // ── PPN/Tax Handling ──────────────────────────────────────
    // PPN checkbox handler
    $('#ppnCheckbox').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('#ppnRateGroup').toggle(isChecked);
        $('#ppnSummary').toggle(isChecked);
    });

    // PPN rate change handler
    $('#ppnRate').on('change keyup', function() {
        var ppnRate = parseFloat($(this).val()) || 10;
        $('#ppnRateDisplay').text(ppnRate.toFixed(2));
        $('#ppnRateDisplay2').text(ppnRate.toFixed(2));
    });

    // Initialize PPN rate display on modal show
    $('#createPoModal').on('show.bs.modal', function() {
        var ppnRate = parseFloat($('#ppnRate').val()) || 10;
        $('#ppnRateDisplay').text(ppnRate.toFixed(2));
        $('#ppnRateDisplay2').text(ppnRate.toFixed(2));
    });

    // Currency formatter (IDR)
    function formatCurrency(amount) {
        return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // ── Edit Qty ──────────────────────────────────────────
    $(document).on('click', '.btn-edit-qty', function() {
        var id   = $(this).data('id');
        var qty  = $(this).data('qty');
        var name = $(this).data('name');
        $('#editQtyDetailId').val(id);
        $('#editQtyValue').val(qty);
        $('#editQtyItemName').text('Item: ' + name);
        $('#editQtyModal').modal('show');
        setTimeout(function(){ $('#editQtyValue').select(); }, 300);
    });

    $('#btnSaveQty').on('click', function() {
        var id  = $('#editQtyDetailId').val();
        var qty = parseFloat($('#editQtyValue').val());
        if (!qty || qty <= 0) { toastr.error(LANG.qtyErrorZero); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url:      BASE_URL + 'purchase_request_approval/edit_item/' + id,
            type:     'POST',
            dataType: 'json',
            data:     { qty: qty },
            success:  function(res) {
                if (res.success) {
                    // Update displayed qty in row
                    var $row = $('#arow-' + id);
                    $row.find('.item-qty-display').text(parseFloat(res.new_qty).toFixed(2));
                    $row.find('.btn-edit-qty').data('qty', res.new_qty).attr('data-qty', res.new_qty);
                    $('#editQtyModal').modal('hide');
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || LANG.qtyUpdateFailed);
                }
            },
            error: function(xhr) {
                toastr.error(LANG.serverError + xhr.status + ').');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Qty');
            }
        });
    });

    // ── Delete Item ───────────────────────────────────────
    $(document).on('click', '.btn-delete-item', function() {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        var $row = $('#arow-' + id);
        var $btn = $(this);

        if (!confirm('Delete "' + name + '"?\n\n' + LANG.confirmDeleteItem)) return;

        $btn.prop('disabled', true);
        $.ajax({
            url:      BASE_URL + 'purchase_request_approval/delete_item/' + id,
            type:     'POST',
            dataType: 'json',
            success:  function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Renumber rows
                        $('#approvalItemBody tr[id^="arow-"]').each(function(i) {
                            $(this).find('.row-no').text(i + 1);
                        });
                        var remaining = $('#approvalItemBody tr[id^="arow-"]').length;
                        $('#item-count').text(remaining + ' item');
                        if (remaining === 0) {
                            $('#approvalItemBody').html('<tr id="noItemRow"><td colspan="9" class="text-center text-muted py-3">' + LANG.allItemsDeleted + '</td></tr>');
                        }
                    });
                } else {
                    toastr.error(res.message || LANG.deleteItemFailed);
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                toastr.error(LANG.serverError + xhr.status + ').');
                $btn.prop('disabled', false);
            }
        });
    });

    // ── Approve ───────────────────────────────────────────
    $('#btnApprove').on('click', function() {
        var remaining = $('#approvalItemBody tr[id^="arow-"]').length;
        if (remaining === 0) {
            toastr.error(LANG.noItemsApprove);
            return;
        }
        if (!confirm(LANG.confirmApprove + '\n' + remaining + ' items will proceed to Purchase Order process.')) return;

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + LANG.approving);
        var redirectUrl = BASE_URL + 'purchase_request_approval/detail/' + PR_ID;

        $.ajax({
            url:      BASE_URL + 'purchase_request_approval/approve_pr/' + PR_ID,
            type:     'POST',
            dataType: 'json',
            success:  function(res) {
                if (res.success) {
                    toastr.success(res.message || LANG.approveSuccess);
                    window.location.href = redirectUrl;
                } else {
                    toastr.error(res.message || LANG.approveFailed);
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Approve PR');
                }
            },
            error: function(xhr) {
                toastr.error(LANG.serverError + xhr.status + ').');
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i> Approve PR');
            }
        });
    });

    // ── Reject ────────────────────────────────────────────
    $('#btnConfirmReject').on('click', function() {
        var notes = $('#rejectionNotes').val().trim();
        if (!notes) { toastr.error(LANG.rejectReasonRequired); $('#rejectionNotes').focus(); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + LANG.rejecting);
        var redirectUrl = BASE_URL + 'purchase_request_approval/detail/' + PR_ID;

        $.ajax({
            url:      BASE_URL + 'purchase_request_approval/reject_pr/' + PR_ID,
            type:     'POST',
            dataType: 'json',
            data:     { rejection_notes: notes },
            success:  function(res) {
                if (res.success) {
                    toastr.success(res.message || LANG.rejectSuccess);
                    $('#rejectModal').modal('hide');
                    window.location.href = redirectUrl;
                } else {
                    toastr.error(res.message || LANG.rejectFailed);
                    $btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject PR');
                }
            },
            error: function(xhr) {
                toastr.error(LANG.serverError + xhr.status + ').');
                $btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject PR');
            }
        });
    });

    // Enter key in qty modal
    $('#editQtyValue').on('keypress', function(e) {
        if (e.which === 13) $('#btnSaveQty').click();
    });

});
</script>
