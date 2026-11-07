<style>
    #itemTable td .item-remarks,
    #itemTable td .item-desc {
        display: block;
        max-width: 260px;
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }
</style>
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-file-alt text-primary mr-2"></i> <?= lang_line('purchase_request_detail_title'); ?></h3>
            <p class="text-muted mb-0">
                <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                &nbsp;|&nbsp;
                <?php
                $badge = [
                    'Draft'              => 'secondary',
                    'Submitted'          => 'warning',
                    'Approved Head Dept' => 'info',
                    'Endorsed'           => 'primary',
                    'Approved'           => 'success',
                    'Rejected'           => 'danger',
                ][$header->status] ?? 'secondary';
                ?>
                <span class="badge badge-<?= $badge ?> badge-lg"><?= $header->status ?></span>
            </p>
        </div>
        <div class="text-right">
            <?php if ($can_edit): ?>
                <button class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#editHeaderModal">
                    <i class="fas fa-edit"></i> <?= lang_line('purchase_request_edit_header'); ?>
                </button>
            <?php endif; ?>
            <?php if ($can_submit): ?>
                <a href="<?= base_url('purchase_request_general/submit_pr/' . $header->id) ?>"
                   class="btn btn-sm btn-success"
                   onclick="return confirm('<?= lang_line('purchase_request_submit_confirm'); ?>')">
                    <i class="fas fa-paper-plane"></i> <?= lang_line('purchase_request_submit_action'); ?>
                </a>
            <?php endif; ?>
            <?php if ($can_unsubmit): ?>
                <a href="<?= base_url('purchase_request_general/unsubmit_pr/' . $header->id) ?>"
                   class="btn btn-sm btn-warning"
                   onclick="return confirm('<?= lang_line('purchase_request_unsubmit_confirm'); ?>')">
                    <i class="fas fa-undo"></i> <?= lang_line('purchase_request_unsubmit_action'); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                <a href="<?= base_url('purchase_request_general/detail/' . $header->id) ?>?admin_edit=1"
                   class="btn btn-sm btn-outline-warning btn-confirm no-loader"
                   data-message="<?= htmlspecialchars(lang_line('prg_admin_unlock_confirm_title', 'Buka kunci edit PR ini?')); ?>"
                   data-text="<?= htmlspecialchars(sprintf(lang_line('prg_admin_unlock_confirm_text', 'PR sudah %s. Membuka kunci memungkinkan Anda menambah/memperbaiki item sebagai Admin. Approval TIDAK direset.'), $header->status)); ?>"
                   data-icon="warning"
                   data-confirm-text="<?= htmlspecialchars(lang_line('prg_admin_unlock_confirm_button', 'Ya, Buka Kunci')); ?>">
                    <i class="fas fa-lock"></i> <?= lang_line('prg_admin_unlock_button', 'Buka Kunci Edit (Admin)'); ?>
                </a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                <a href="<?= base_url('purchase_request_general/detail/' . $header->id) ?>" class="btn btn-sm btn-warning no-loader">
                    <i class="fas fa-lock-open"></i> <?= lang_line('prg_admin_lock_button', 'Selesai Edit (Kunci Lagi)'); ?>
                </a>
            <?php endif; ?>
            <?php if (in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])): ?>
                <?php
                    $stage_label = [
                        'Submitted'          => 'Menunggu Approval',
                        'Approved Head Dept' => 'Menunggu Approval',
                        'Endorsed'           => 'Menunggu Approval',
                    ][$header->status];
                ?>
                <span class="badge badge-info px-3 py-2 mr-1">
                    <i class="fas fa-clock mr-1"></i> <?= $stage_label ?>
                </span>
            <?php endif; ?>
            <?php if ($header->status === 'Approved'): ?>
                <span class="badge badge-success px-3 py-2 mr-1">
                    <i class="fas fa-check-circle mr-1"></i> <?= lang_line('purchase_request_approved_status'); ?>
                </span>
                <?php if (check_access('purchase_order_general', 'add')): ?>
                    <button type="button" class="btn btn-sm btn-primary ml-1" data-toggle="modal" data-target="#createPoModal">
                        <i class="fas fa-arrow-right"></i> <?= lang_line('purchase_request_create_po', 'Buat PO'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= base_url('purchase_request_general/export_detail_excel/'.$header->id) ?>" target="_blank" class="btn btn-success btn-sm no-loader ml-1"><i class="fas fa-file-excel fa-sm"></i> <?= lang_line('text_excel'); ?></a>
            <a href="<?= base_url('purchase_request_general/export_detail_pdf/'.$header->id) ?>" target="_blank" class="btn btn-danger btn-sm no-loader ml-1"><i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('text_pdf'); ?></a>
            <a href="<?= base_url('purchase_request_general') ?>" class="btn btn-sm btn-outline-secondary ml-1">
                <i class="fas fa-arrow-left"></i> <?= lang_line('btn_back'); ?>
            </a>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Mode Edit Admin -->
    <?php if (!empty($admin_edit_mode)): ?>
    <div class="alert alert-warning border-left-warning" role="alert">
        <h6 class="font-weight-bold mb-1"><i class="fas fa-user-shield mr-1"></i> <?= lang_line('prg_admin_banner_title', 'Mode Edit Admin'); ?></h6>
        <p class="mb-1 small">
            <?= sprintf(
                lang_line('prg_admin_banner_text', 'PR ini berstatus <strong>%1$s</strong> dan normalnya terkunci. Sebagai Admin Anda tetap bisa menambah atau memperbaiki item, dan <strong>approval tidak direset</strong> &mdash; PR tetap %2$s tanpa perlu submit &amp; approve ulang. Semua perubahan tercatat di Log Activity.'),
                htmlspecialchars($header->status),
                htmlspecialchars($header->status)
            ); ?>
        </p>
        <p class="mb-0 small text-danger font-weight-bold">
            <i class="fas fa-lock mr-1"></i> <?= lang_line('prg_admin_row_locked_note', 'Baris yang sudah ditarik ke PO tetap terkunci: qty-nya jadi acuan PO, jadi tidak bisa diubah atau dihapus dari sini.'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Rejection / Return notes -->
    <?php
        $reject_reason_display = $header->reject_reason ?? $header->rejection_notes ?? null;
        $rejecter_display      = $header->rejecter_name  ?? $header->approver_name  ?? null;
        $rejected_at_display   = $header->rejected_at    ?? $header->approved_at    ?? null;
    ?>
    <?php if ($header->status === 'Rejected' && $reject_reason_display): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle mr-1"></i>
            <strong><?= lang_line('purchase_request_rejection_reason'); ?>:</strong> <?= htmlspecialchars($reject_reason_display) ?>
            <?php if ($rejecter_display): ?>
                &mdash; <?= lang_line('text_by'); ?> <strong><?= htmlspecialchars($rejecter_display) ?></strong>
                <?php if ($rejected_at_display): ?>
                    <?= lang_line('text_on'); ?> <?= date('d M Y H:i', strtotime($rejected_at_display)) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($header->return_reason) && $header->status === 'Draft' && !empty($header->returned_to_draft_by)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-undo mr-1"></i>
            <strong>Dikembalikan ke Draft:</strong> <?= htmlspecialchars($header->return_reason) ?>
            <?php if (!empty($header->returner_name)): ?>
                &mdash; oleh <strong><?= htmlspecialchars($header->returner_name) ?></strong>
                <?= !empty($header->returned_to_draft_at) ? 'pada ' . date('d M Y H:i', strtotime($header->returned_to_draft_at)) : '' ?>
            <?php endif; ?>
            <br><small class="text-muted"><?= lang_line('prg_revise_msg'); ?></small>
        </div>
    <?php endif; ?>

    <!-- Header Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_info_title'); ?></h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_number'); ?></small>
                    <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_date'); ?></small>
                    <strong><?= date('d M Y', strtotime($header->pr_date)) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_requester'); ?></small>
                    <strong><?= htmlspecialchars($header->requester_name ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_department_label', 'Department'); ?></small>
                    <strong><?= htmlspecialchars($header->department ?? '-') ?></strong>
                </div>
            </div>
            <?php if ($header->purpose): ?>
            <hr class="my-3">
            <div>
                <small class="text-muted d-block"><?= lang_line('purchase_request_purpose'); ?></small>
                <strong><?= htmlspecialchars($header->purpose) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($header->remarks): ?>
            <div class="mt-2">
                <small class="text-muted d-block"><?= lang_line('purchase_request_remarks'); ?></small>
                <?= nl2br(htmlspecialchars($header->remarks)) ?>
            </div>
            <?php endif; ?>
            <?php if ($header->status === 'Approved'): ?>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approved_by'); ?></small>
                    <strong class="text-success"><?= htmlspecialchars($header->approver_name ?? '-') ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= lang_line('purchase_request_approval_date'); ?></small>
                    <strong><?= $header->approved_at ? date('d M Y H:i', strtotime($header->approved_at)) : '-' ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_detail_items'); ?></h6>
            <?php if ($can_edit): ?>
                <button type="button" class="btn btn-success btn-sm" id="btnShowAddItemRow">
                    <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('purchase_request_add_item_manual'); ?>
                </button>
                <?php if ($header->status === 'Draft'): // Tarik dari Permintaan Barang (2026-11-05) ?>
                <button type="button" class="btn btn-primary btn-sm ml-1" data-toggle="modal" data-target="#pullSourceModal">
                    <i class="fas fa-download fa-sm mr-1"></i> Tarik dari Permintaan Barang
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="itemTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('label_no'); ?></th>
                            <th><?= lang_line('purchase_request_item_code'); ?></th>
                            <th><?= lang_line('purchase_request_item_name'); ?></th>
                            <th><?= lang_line('purchase_request_item_type'); ?></th>
                            <th><?= lang_line('purchase_request_item_qty'); ?></th>
                            <th><?= lang_line('purchase_request_item_unit'); ?></th>
                            <th><?= lang_line('purchase_request_item_description'); ?></th>
                            <th><?= lang_line('purchase_request_item_remarks'); ?></th>
                            <?php if ($can_edit): ?><th><?= lang_line('label_action'); ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="itemTableBody">

                        <!-- Add Item Row (hidden) -->
                        <?php if ($can_edit): ?>
                        <tr id="addItemRow" style="display:none; background:#e8f5e9;">
                            <td class="text-center"><i class="fas fa-plus text-success"></i></td>
                            <td><input type="text" id="newItemCode" class="form-control form-control-sm" readonly placeholder="<?= lang_line('pr_new_item_placeholder_auto'); ?>" style="background:#f8f9fa"></td>
                            <td>
                                <select id="newItemSelect" class="form-control form-control-sm" style="width:100%">
                                    <option value=""><?= lang_line('purchase_request_search_item', '-- Cari Item --'); ?></option>
                                </select>
                            </td>
                            <td><span id="newItemType" class="badge badge-secondary">-</span></td>
                            <td><input type="number" id="newItemQty" class="form-control form-control-sm" value="1" min="0.01" step="any" style="width:90px"></td>
                            <td><span id="newItemUnit" class="text-muted">-</span></td>
                            <td><input type="text" id="newItemDesc" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_item_desc_placeholder', 'Deskripsi (opsional)'); ?>"></td>
                            <td><input type="text" id="newItemRemarks" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_item_remarks_placeholder', 'Remarks (opsional)'); ?>"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-success" id="btnSaveItem"><i class="fas fa-save"></i></button>
                                <button type="button" class="btn btn-sm btn-secondary" id="btnCancelItem"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Existing items -->
                        <?php if (empty($details)): ?>
                            <tr id="noItemRow"><td colspan="<?= $can_edit ? 9 : 8 ?>" class="text-center text-muted py-3"><?= lang_line('purchase_request_no_items'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($details as $i => $d): ?>
                            <tr id="row-<?= $d->id ?>">
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><code class="item-code"><?= htmlspecialchars($d->item_code ?? '-') ?></code></td>
                                <td class="item-name"><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    $tc = ['material'=>'primary','machine'=>'warning','inventory'=>'info','semifinished'=>'secondary'][$d->item_type] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $tc ?> item-type"><?= ucfirst($d->item_type) ?></span>
                                </td>
                                <td class="text-center">
                                    <strong class="item-qty"><?= number_format($d->qty, 2) ?></strong>
                                    <?php
                                    // Progres PO baris ini: sudah masuk PO berapa, dari PO mana.
                                    $po_ordered = (float) ($po_progress[$d->id]['ordered'] ?? 0);
                                    $po_list    = $po_progress[$d->id]['pos'] ?? [];
                                    $po_titles  = [];
                                    foreach ($po_list as $p) {
                                        $po_titles[] = $p['po_number'] . ' (' . number_format($p['qty'], 2)
                                            . (in_array($p['status'], ['Cancelled', 'Rejected'], true) ? ' — ' . $p['status'] . ', dilepas' : '')
                                            . ')';
                                    }
                                    ?>
                                    <?php if (!empty($po_list)): ?>
                                        <br>
                                        <?php if ((float) $d->qty - $po_ordered <= 0.005): ?>
                                            <small class="badge badge-success" title="<?= htmlspecialchars(implode(', ', $po_titles)) ?>">PO penuh</small>
                                        <?php else: ?>
                                            <small class="badge badge-warning" title="<?= htmlspecialchars(implode(', ', $po_titles)) ?>">di-PO <?= number_format($po_ordered, 2) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><small class="item-unit"><?= htmlspecialchars($d->item_unit ?? '-') ?></small></td>
                                <td><small class="item-desc"><?= htmlspecialchars($d->description ?? '-') ?></small></td>
                                <td><small class="text-muted item-remarks"><?= htmlspecialchars($d->remarks ?? '-') ?></small></td>
                                <?php if ($can_edit): ?>
                                <td class="text-center">
                                    <?php
                                    // Di mode Admin, baris yang sudah ditarik ke PO dikunci: qty-nya
                                    // sudah jadi acuan PO, mengubah/menghapusnya bikin PO menggantung.
                                    $row_locked = !empty($admin_edit_mode) && !empty($po_list);
                                    ?>
                                    <?php if ($row_locked): ?>
                                        <span class="badge badge-light border text-muted" title="<?= htmlspecialchars(implode(', ', $po_titles)) ?>">
                                            <i class="fas fa-lock"></i> <?= lang_line('prg_admin_row_locked_badge', 'Terkunci (sudah di-PO)'); ?>
                                        </span>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-edit-item"
                                        data-id="<?= $d->id ?>"
                                        data-qty="<?= $d->qty ?>"
                                        data-desc="<?= htmlspecialchars($d->description ?? '') ?>"
                                        data-remarks="<?= htmlspecialchars($d->remarks ?? '') ?>"
                                        data-item="<?= htmlspecialchars($d->item_type . '-' . $d->item_id) ?>"
                                        data-item-text="<?= htmlspecialchars('[' . strtoupper($d->item_type) . '] ' . ($d->item_code ?? '-') . ' - ' . ($d->item_name ?? '-')) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-item" data-id="<?= $d->id ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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

    <?php
        // Riwayat Approval + thread balasan Back to Draft (2026-09-06).
        // Partial bersama utk 6 halaman PR — lihat templates/approval_history.php.
        // Balasan di-POST ke controller UTAMA (bukan controller approval) krn
        // controller approval py gate check_access di constructor-nya.
        $this->load->view('templates/approval_history', [
            'logs'         => $approval_logs ?? [],
            'can_reply'    => $can_reply ?? false,
            'reply_action' => base_url('purchase_request_general/reply/' . $header->id),
            'owner_id'     => (int) ($header->requester_id ?? $header->created_by ?? 0),
        ]);
    ?>

</div>

<!-- Edit Header Modal -->
<div class="modal fade" id="editHeaderModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request_general/update_header/' . $header->id) ?>">
            <?php if (!empty($admin_edit_mode)): ?><input type="hidden" name="admin_edit" value="1"><?php endif; ?>
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i> <?= lang_line('purchase_request_edit_title'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('purchase_request_number'); ?></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($header->pr_number) ?>" disabled>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('purchase_request_date'); ?></label>
                            <input type="date" name="pr_date" class="form-control" value="<?= $header->pr_date ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_department_label', 'Department'); ?></label>
                        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($header->department ?? '') ?>" placeholder="<?= lang_line('purchase_request_department_placeholder', 'e.g. PPIC, Warehouse, Purchasing'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_purpose'); ?></label>
                        <input type="text" name="purpose" class="form-control" value="<?= htmlspecialchars($header->purpose ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_remarks'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header->remarks ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> <?= lang_line('purchase_request_reject_title', 'Tolak Purchase Request'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label><?= lang_line('purchase_request_rejection_reason', 'Alasan Penolakan'); ?> <span class="text-danger">*</span></label>
                    <textarea id="rejectionNotes" class="form-control" rows="4" placeholder="<?= lang_line('purchase_request_rejection_notes_placeholder', 'Tuliskan alasan penolakan...'); ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="rejectPr(<?= $header->id ?>)"><i class="fas fa-times mr-1"></i> <?= lang_line('purchase_request_reject_button', 'Tolak PR'); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><?= lang_line('purchase_request_edit_item_title', 'Edit Item'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editDetailId">
                <div class="form-group">
                    <label><?= lang_line('prg_admin_edit_item_label', 'Item / Kode Barang'); ?></label>
                    <select id="editItemSelect" class="form-control" style="width:100%"></select>
                    <small class="text-muted"><?= lang_line('prg_admin_edit_item_help', 'Kosongkan/biarkan kalau item tidak diganti. Mengganti item tidak mengubah status approval.'); ?></small>
                </div>
                <div class="form-group">
                    <label><?= lang_line('pr_label_qty_request'); ?> <span class="text-danger">*</span></label>
                    <input type="number" id="editQty" class="form-control" step="any" min="0.01">
                </div>
                <div class="form-group">
                    <label><?= lang_line('pr_label_description'); ?></label>
                    <input type="text" id="editDesc" class="form-control">
                </div>
                <div class="form-group mb-0">
                    <label><?= lang_line('pr_label_remarks'); ?></label>
                    <input type="text" id="editRemarks" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="saveEditItem()"><i class="fas fa-save mr-1"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('pr_btn_cancel'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Create PO Modal -->
<div class="modal fade" id="createPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="createPoForm" method="POST" action="<?= base_url('purchase_request_general/create_po_from_pr/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-arrow-right mr-2"></i> <?= lang_line('purchase_request_create_po_title', 'Buat Purchase Order'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-2"></i> <?= lang_line('purchase_request_create_po_select_supplier_info', 'Pilih supplier yang akan mengerjakan PR ini'); ?></p>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_create_po_select_supplier', 'Pilih Supplier'); ?> <span class="text-danger">*</span></label>
                        <select id="supplierSelect" name="supplier_id" class="form-control select2-supplier">
                            <option value=""><?= lang_line('purchase_request_create_po_select_supplier_placeholder', '-- Pilih Supplier --'); ?></option>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
                    <button type="submit" id="submitCreatePo" class="btn btn-primary" disabled><i class="fas fa-check mr-1"></i> <?= lang_line('purchase_request_create_po_button', 'Buat PO'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const I18N_PR = {
    err_prefix:           <?= json_encode(lang_line('pr_err_prefix')); ?>,
    alert_pick_item:      <?= json_encode(lang_line('pr_alert_pick_item_first')); ?>,
    alert_qty_gt_0:       <?= json_encode(lang_line('pr_alert_qty_gt_0')); ?>,
    alert_qty_positive:   <?= json_encode(lang_line('pr_alert_qty_positive')); ?>,
    err_save_item:        <?= json_encode(lang_line('pr_err_save_item')); ?>,
    confirm_delete_item:  <?= json_encode(lang_line('pr_confirm_delete_item')); ?>,
    success_deleted:      <?= json_encode(lang_line('pr_success_item_deleted')); ?>,
    err_delete_item:      <?= json_encode(lang_line('pr_err_delete_item')); ?>,
    success_updated:      <?= json_encode(lang_line('pr_success_item_updated')); ?>,
    err_update_item:      <?= json_encode(lang_line('pr_err_update_item')); ?>,
    confirm_approve:      <?= json_encode(lang_line('pr_confirm_approve')); ?>,
    no_items_msg:         <?= json_encode(lang_line('pr_no_items_msg')); ?>
};
var BASE_URL = '<?= base_url() ?>';
var PR_ID    = <?= $header->id ?>;
var CAN_EDIT = <?= $can_edit ? 'true' : 'false' ?>;
// Flag mode edit Admin. Ikut dikirim di tiap aksi tulis — server menolak
// perubahan pada PR non-Draft kalau flag ini tidak ada (lihat can_edit_pr()).
var ADMIN_EDIT = <?= !empty($admin_edit_mode) ? "'1'" : "''" ?>;
// Item yang dipilih di modal edit (kosong = item tidak diganti).
var _editItem = {};

$(document).ready(function() {
    // Select2 for edit modal supplier
    $('.select2-edit').select2({ dropdownParent: $('#editHeaderModal'), width: '100%' });

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

    // BOM SC select
    $('#sc_select_for_bom').on('change', function() {
        $('#btnLoadBom').prop('disabled', !$(this).val());
    });

    // Load BOM
    $('#btnLoadBom').on('click', function() {
        var sc_id = $('#sc_select_for_bom').val();
        if (!sc_id) return;
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading BOM...');
        $('#loadBomSpinner').removeClass('d-none');
        
        console.log('[Load BOM] Starting - SC ID:', sc_id, 'PR ID:', PR_ID);
        
        $.ajax({
            url: BASE_URL + 'purchase_request_general/load_bom_from_sc/' + PR_ID,
            type: 'POST',
            dataType: 'json',
            timeout: 30000,
            data: { sc_id: sc_id, admin_edit: ADMIN_EDIT },
            success: function(res) {
                console.log('[Load BOM] Response:', res);
                if (res && res.success) {
                    console.log('[Load BOM] Success - items count:', res.items ? res.items.length : 0);
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message || 'BOM berhasil dimuat dari SC.');
                    } else {
                        alert(res.message || 'BOM berhasil dimuat dari SC.');
                    }
                    
                    // Render table with items from response
                    if (res.items && res.items.length > 0) {
                        renderPrTable(res.items);
                        console.log('[Load BOM] Table re-rendered, items count:', res.items.length);
                    } else {
                        console.warn('[Load BOM] No items returned');
                        // Fallback: reload page
                        setTimeout(function() { 
                            console.log('[Load BOM] Falling back to reload');
                            location.reload(); 
                        }, 800);
                    }
                    
                    // Reset form
                    $('#sc_select_for_bom').val('').trigger('change');
                    $('#btnLoadBom').prop('disabled', true).html('<i class="fas fa-download mr-1"></i> Load BOM');
                } else {
                    console.error('[Load BOM] Server error:', res ? res.message : 'No response');
                    var errMsg = (res && res.message) ? res.message : 'Gagal memuat BOM.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errMsg);
                    } else {
                        alert(I18N_PR.err_prefix + ' ' + errMsg);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('[Load BOM] AJAX error:', {status: status, error: error, xhr: xhr});
                var errMsg = 'Gagal menghubungi server';
                if (status === 'timeout') errMsg += ' - Request timeout (terlalu lama)';
                if (xhr.status) errMsg += ' (status ' + xhr.status + ')';
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(errMsg);
                } else {
                    alert(errMsg);
                }
            },
            complete: function() {
                console.log('[Load BOM] Request complete');
                $('#loadBomSpinner').addClass('d-none');
                $btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i> Load BOM');
            }
        });
    });

    // Store selected item data for DOM insertion after save
    var _selectedItem = {};

    // Show add item row
    $('#btnShowAddItemRow').on('click', function() {
        $('#addItemRow').show();
        $('#noItemRow').hide();
        $(this).hide();
        // Init Select2 for item search (only once)
        if (!$('#newItemSelect').hasClass('select2-hidden-accessible')) {
            $('#newItemSelect').select2({
                placeholder: '-- Cari dan Pilih Item --',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: BASE_URL + 'purchase_request_general/search_items',
                    dataType: 'json',
                    delay: 300,
                    data: function(p) { return { term: p.term }; },
                    processResults: function(d) { return d; }
                }
            });
        }
        $('#newItemSelect').off('select2:select').on('select2:select', function(e) {
            var d = e.params.data;
            _selectedItem = d;
            $('#newItemCode').val(d.code || '');
            $('#newItemType').text(d.type || '').attr('class', 'badge badge-' + typeColor(d.type));
            $('#newItemUnit').text(d.unit || '-');
        });
        $('#newItemSelect').off('select2:clear').on('select2:clear', function() {
            _selectedItem = {};
            $('#newItemCode').val('');
            $('#newItemType').text('-').attr('class','badge badge-secondary');
            $('#newItemUnit').text('-');
        });
    });

    // Cancel add
    $('#btnCancelItem').on('click', function() {
        $('#addItemRow').hide();
        $('#btnShowAddItemRow').show();
        resetAddRow();
        _selectedItem = {};
    });

    // Save new item — update DOM directly, no page reload needed
    $('#btnSaveItem').on('click', function() {
        var item_val = $('#newItemSelect').val();
        var qty      = parseFloat($('#newItemQty').val());
        if (!item_val) { 
            if (typeof toastr !== 'undefined') {
                toastr.error('Pilih item terlebih dahulu');
            } else {
                alert(I18N_PR.alert_pick_item);
            }
            return; 
        }
        if (!qty || qty <= 0) { 
            if (typeof toastr !== 'undefined') {
                toastr.error('Qty harus lebih dari 0');
            } else {
                alert(I18N_PR.alert_qty_gt_0);
            }
            return; 
        }

        var desc    = $('#newItemDesc').val();
        var remarks = $('#newItemRemarks').val();
        var $btn    = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        console.log('[PR Add Item] Sending:', { item_id: item_val, qty: qty, description: desc, remarks: remarks });

        $.ajax({
            url:      BASE_URL + 'purchase_request_general/add_single_item/' + PR_ID,
            type:     'POST',
            dataType: 'json',
            timeout:  10000,
            data:     { item_id: item_val, qty: qty, description: desc, remarks: remarks, admin_edit: ADMIN_EDIT },
            success:  function(res) {
                console.log('[PR Add Item] Response:', res);
                if (res.success) {
                    console.log('[PR Add Item] Success - showing toastr');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.message || 'Item berhasil ditambahkan.');
                    } else {
                        alert(res.message || 'Item berhasil ditambahkan.');
                    }

                    if (res.updated) {
                        console.log('[PR Add Item] Updating existing row:', res.detail_id);
                        var $existingRow = $('#row-' + res.detail_id);
                        if ($existingRow.length) {
                            $existingRow.find('.item-qty').text(parseFloat(res.new_qty).toFixed(2));
                            $existingRow.find('.btn-edit-item').data('qty', res.new_qty).attr('data-qty', res.new_qty);
                            console.log('[PR Add Item] Row updated successfully');
                        } else {
                            console.warn('[PR Add Item] Row not found:', 'row-' + res.detail_id);
                        }
                    } else {
                        console.log('[PR Add Item] Adding new row:', res.detail_id);
                        var esc = function(s) { return $('<div>').text(String(s || '')).html(); };
                        var itemCode = _selectedItem.code || '';
                        var itemName = _selectedItem.name || '';
                        var itemType = _selectedItem.type || '';
                        var itemUnit = _selectedItem.unit || '-';
                        var tc       = typeColor(itemType);
                        var detailId = res.detail_id || 0;
                        var rowNo    = $('#itemTableBody tr[id^="row-"]').length + 1;
                        var typeLabel = itemType ? (itemType.charAt(0).toUpperCase() + itemType.slice(1)) : '-';

                        var itemKey  = _selectedItem.id || '';
                        var itemText = _selectedItem.text || '';

                        var editBtn = CAN_EDIT
                            ? '<button type="button" class="btn btn-xs btn-outline-primary btn-edit-item"' +
                              ' data-id="' + detailId + '" data-qty="' + qty +
                              '" data-desc="' + esc(desc) + '" data-remarks="' + esc(remarks) +
                              '" data-item="' + esc(itemKey) + '" data-item-text="' + esc(itemText) + '">' +
                              '<i class="fas fa-edit"></i></button> ' +
                              '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-item" data-id="' + detailId + '">' +
                              '<i class="fas fa-trash"></i></button>'
                            : '';

                        var newRow =
                            '<tr id="row-' + detailId + '">' +
                            '<td class="text-center">' + rowNo + '</td>' +
                            '<td><code class="item-code">' + esc(itemCode) + '</code></td>' +
                            '<td class="item-name">' + esc(itemName) + '</td>' +
                            '<td class="text-center"><span class="badge badge-' + tc + ' item-type">' + esc(typeLabel) + '</span></td>' +
                            '<td class="text-center"><strong class="item-qty">' + parseFloat(qty).toFixed(2) + '</strong></td>' +
                            '<td class="text-center"><small class="item-unit">' + esc(itemUnit) + '</small></td>' +
                            '<td><small class="item-desc">' + esc(desc || '-') + '</small></td>' +
                            '<td><small class="text-muted item-remarks">' + esc(remarks || '-') + '</small></td>' +
                            (CAN_EDIT ? '<td class="text-center">' + editBtn + '</td>' : '') +
                            '</tr>';

                        $('#noItemRow').remove();
                        $('#itemTableBody').append(newRow);
                        console.log('[PR Add Item] New row added');
                    }

                    console.log('[PR Add Item] Resetting form and hiding add row');
                    $('#addItemRow').hide();
                    $('#btnShowAddItemRow').show();
                    resetAddRow();
                    _selectedItem = {};
                } else {
                    console.error('[PR Add Item] Server error:', res.message);
                    if (typeof toastr !== 'undefined') {
                        toastr.error(res.message || 'Gagal menyimpan item.');
                    } else {
                        alert(I18N_PR.err_prefix + ' ' + (res.message || I18N_PR.err_save_item));
                    }
                }
            },
            error: function(xhr) {
                console.error('[PR Save] AJAX error:', xhr.status, xhr.responseText);
                var errMsg = 'Gagal menghubungi server (status ' + xhr.status + ').';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errMsg);
                } else {
                    alert(errMsg);
                }
            },
            complete: function() {
                console.log('[PR Add Item] Request complete - enabling button');
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i>');
            }
        });
    });

    // Edit item button
    $(document).on('click', '.btn-edit-item', function() {
        var $b = $(this);
        $('#editDetailId').val($b.data('id'));
        $('#editQty').val($b.data('qty'));
        $('#editDesc').val($b.data('desc'));
        $('#editRemarks').val($b.data('remarks'));

        // Pemilih item: boleh ganti item/kode barang, bukan cuma qty.
        var $sel = $('#editItemSelect');
        if (!$sel.hasClass('select2-hidden-accessible')) {
            $sel.select2({
                dropdownParent: $('#editItemModal'),
                width: '100%',
                minimumInputLength: 2,
                ajax: {
                    url: BASE_URL + 'purchase_request_general/search_items',
                    dataType: 'json',
                    delay: 300,
                    data: function(p) { return { term: p.term }; },
                    processResults: function(d) { return d; }
                }
            });
            $sel.on('select2:select', function(e) { _editItem = e.params.data; });
        }
        _editItem = {};
        // Option ditanam manual: select2 ajax belum punya data item ini sebelum
        // user mengetik, jadi tanpa ini field-nya tampil kosong.
        $sel.empty().append(new Option($b.data('item-text'), $b.data('item'), true, true)).trigger('change');

        $('#editItemModal').modal('show');
    });

    // Delete item button
    $(document).on('click', '.btn-delete-item', function() {
        var id   = $(this).data('id');
        var $row = $('#row-' + id);
        if (!confirm(I18N_PR.confirm_delete_item)) return;
        var $btn = $(this).prop('disabled', true);
        
        console.log('[PR Delete Item] Deleting item:', id);
        
        $.ajax({
            url:      BASE_URL + 'purchase_request_general/delete_item/' + id,
            type:     'POST',
            dataType: 'json',
            data:     { admin_edit: ADMIN_EDIT },
            success:  function(res) {
                console.log('[PR Delete Item] Response:', res);
                if (res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Item berhasil dihapus.');
                    } else {
                        alert(I18N_PR.success_deleted);
                    }
                    $row.fadeOut(300, function() { $(this).remove(); });
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(res.message || 'Gagal menghapus item.');
                    } else {
                        alert(I18N_PR.err_prefix + ' ' + (res.message || I18N_PR.err_delete_item));
                    }
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                console.error('[PR Delete] AJAX error:', xhr.status, xhr.responseText);
                var errMsg = 'Gagal menghubungi server (status ' + xhr.status + ')';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errMsg);
                } else {
                    alert(errMsg);
                }
                $btn.prop('disabled', false);
            }
        });
    });
});

function saveEditItem() {
    var id = $('#editDetailId').val();
    var qty = parseFloat($('#editQty').val());
    var newDesc = $('#editDesc').val();
    var newRemarks = $('#editRemarks').val();
    if (!qty || qty <= 0) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Qty harus > 0');
        } else {
            alert(I18N_PR.alert_qty_positive);
        }
        return; 
    }
    
    console.log('[PR Edit Item] Updating - ID:', id, 'Qty:', qty);
    
    var $btn = $('#editItemModal').find('.btn-primary').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
    $.ajax({
        url: BASE_URL + 'purchase_request_general/update_item',
        type: 'POST',
        dataType: 'json',
        data: {
            detail_id: id,
            qty: qty,
            description: newDesc,
            remarks: newRemarks,
            item_id: $('#editItemSelect').val() || '',
            admin_edit: ADMIN_EDIT,
        },
        success: function(res) {
            console.log('[PR Edit Item] Response:', res);
            if (res.success) {
                // Update DOM directly - update item-qty display
                var $row = $('#row-' + id);
                if ($row.length) {
                    $row.find('.item-qty').text(parseFloat(qty).toFixed(2));
                    $row.find('.item-desc').text(newDesc || '-');
                    $row.find('.item-remarks').text(newRemarks || '-');
                    $row.find('.btn-edit-item')
                        .data('qty', qty).attr('data-qty', qty)
                        .data('desc', newDesc).attr('data-desc', newDesc)
                        .data('remarks', newRemarks).attr('data-remarks', newRemarks);

                    // Item diganti → tulis ulang kode/nama/tipe/satuan barisnya.
                    if (_editItem.id) {
                        var typeLabel = _editItem.type ? (_editItem.type.charAt(0).toUpperCase() + _editItem.type.slice(1)) : '-';
                        $row.find('.item-code').text(_editItem.code || '-');
                        $row.find('.item-name').text(_editItem.name || '-');
                        $row.find('.item-type').text(typeLabel).attr('class', 'badge badge-' + typeColor(_editItem.type) + ' item-type');
                        $row.find('.item-unit').text(_editItem.unit || '-');
                        $row.find('.btn-edit-item')
                            .data('item', _editItem.id).attr('data-item', _editItem.id)
                            .data('item-text', _editItem.text || '').attr('data-item-text', _editItem.text || '');
                    }
                    console.log('[PR Edit Item] DOM updated');
                } else {
                    console.warn('[PR Edit Item] Row not found:', 'row-' + id);
                }
                if (typeof toastr !== 'undefined') {
                    toastr.success('Item berhasil diperbarui.');
                } else {
                    alert(I18N_PR.success_updated);
                }
                $('#editItemModal').modal('hide');
            } else {
                console.error('[PR Edit Item] Server error:', res.message);
                if (typeof toastr !== 'undefined') {
                    toastr.error(res.message || 'Gagal memperbarui item.');
                } else {
                    alert(I18N_PR.err_prefix + ' ' + (res.message || I18N_PR.err_update_item));
                }
            }
        },
        error: function(xhr) {
            console.error('[PR Edit Item] AJAX error:', xhr.status, xhr.responseText);
            var errMsg = 'Gagal menghubungi server (status ' + xhr.status + ')';
            if (typeof toastr !== 'undefined') {
                toastr.error(errMsg);
            } else {
                alert(errMsg);
            }
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
        }
    });
}

function approvepr(id) {
    if (!confirm(I18N_PR.confirm_approve)) return;
    $.post(BASE_URL + 'purchase_request_general/approve_pr/' + id, {}, function(res) {
        if (res.success) { toastr.success(res.message); setTimeout(function(){ location.reload(); }, 800); }
        else toastr.error(res.message);
    }, 'json');
}

function rejectPr(id) {
    var notes = $('#rejectionNotes').val().trim();
    if (!notes) { toastr.error('Alasan penolakan harus diisi.'); return; }
    $.post(BASE_URL + 'purchase_request_general/reject_pr/' + id, { rejection_notes: notes }, function(res) {
        if (res.success) { toastr.success(res.message); setTimeout(function(){ location.reload(); }, 800); }
        else toastr.error(res.message);
    }, 'json');
}

function typeColor(type) {
    var map = { material:'primary', machine:'warning', inventory:'info', semifinished:'secondary' };
    return map[type] || 'secondary';
}

function resetAddRow() {
    if ($('#newItemSelect').hasClass('select2-hidden-accessible')) {
        $('#newItemSelect').val(null).trigger('change');
    }
    $('#newItemCode').val('');
    $('#newItemType').text('-').attr('class','badge badge-secondary');
    $('#newItemUnit').text('-');
    $('#newItemQty').val(1);
    $('#newItemDesc').val('');
    $('#newItemRemarks').val('');
}

function renderPrTable(items) {
    console.log('[renderPrTable] Rendering', items.length, 'items');
    var $tbody = $('#itemTableBody');
    var html = '';
    
    if (!items || items.length === 0) {
        html = '<tr id="noItemRow"><td colspan="' + (CAN_EDIT ? 9 : 8) + '" class="text-center text-muted py-3">' + I18N_PR.no_items_msg + '</td></tr>';
        $tbody.html(html);
        return;
    }
    
    var esc = function(s) { return $('<div>').text(String(s || '')).html(); };
    
    items.forEach(function(item, idx) {
        var typeColorMap = {'material':'primary','machine':'warning','inventory':'info','semifinished':'secondary'};
        var tc = typeColorMap[item.item_type] || 'secondary';
        var typeLabel = item.item_type ? (item.item_type.charAt(0).toUpperCase() + item.item_type.slice(1)) : '-';
        
        var itemKey  = (item.item_type || '') + '-' + item.item_id;
        var itemText = '[' + String(item.item_type || '').toUpperCase() + '] ' + (item.item_code || '-') + ' - ' + (item.item_name || '-');

        var editBtn = CAN_EDIT
            ? '<button type="button" class="btn btn-xs btn-outline-primary btn-edit-item"' +
              ' data-id="' + item.id + '" data-qty="' + item.qty +
              '" data-desc="' + esc(item.description || '') + '" data-remarks="' + esc(item.remarks || '') +
              '" data-item="' + esc(itemKey) + '" data-item-text="' + esc(itemText) + '">' +
              '<i class="fas fa-edit"></i></button> ' +
              '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-item" data-id="' + item.id + '">' +
              '<i class="fas fa-trash"></i></button>'
            : '';
        
        var row =
            '<tr id="row-' + item.id + '">' +
            '<td class="text-center">' + (idx + 1) + '</td>' +
            '<td><code class="item-code">' + esc(item.item_code || '-') + '</code></td>' +
            '<td class="item-name">' + esc(item.item_name || '-') + '</td>' +
            '<td class="text-center"><span class="badge badge-' + tc + ' item-type">' + typeLabel + '</span></td>' +
            '<td class="text-center"><strong class="item-qty">' + parseFloat(item.qty).toFixed(2) + '</strong></td>' +
            '<td class="text-center"><small class="item-unit">' + esc(item.item_unit || '-') + '</small></td>' +
            '<td><small class="item-desc">' + esc(item.description || '-') + '</small></td>' +
            '<td><small class="text-muted item-remarks">' + esc(item.remarks || '-') + '</small></td>' +
            (CAN_EDIT ? '<td class="text-center">' + editBtn + '</td>' : '') +
            '</tr>';
        
        html += row;
    });
    
    $tbody.html(html);
    console.log('[renderPrTable] Table rendered successfully');
}
</script>

<?php if ($can_edit && $header->status === 'Draft') $this->load->view('purchase_request/_pull_source', [
    'pull_route' => 'purchase_request_general', 'pull_kind' => 'irf', 'pull_pr_id' => $header->id,
]); ?>
