<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i> <?= lang_line('purchase_request_approval_title_detail', 'Review Purchase Request'); ?></h3>
            <p class="text-muted mb-0">
                <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                &nbsp;|&nbsp;
                <?php $badge = [
                    'Draft'              => 'secondary',
                    'Submitted'          => 'warning',
                    'Approved Head Dept' => 'info',
                    'Endorsed'           => 'primary',
                    'Approved'           => 'success',
                    'Rejected'           => 'danger',
                ][$header->status] ?? 'secondary'; ?>
                <span class="badge badge-<?= $badge ?> badge-lg"><?= $header->status ?></span>
            </p>
        </div>
        <div>
            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                <a href="<?= base_url('purchase_request_approval_general/detail/' . $header->id) ?>?admin_edit=1"
                   class="btn btn-sm btn-outline-warning btn-confirm no-loader mr-1"
                   data-message="<?= htmlspecialchars(lang_line('prg_admin_unlock_confirm_title', 'Buka kunci edit PR ini?')); ?>"
                   data-text="<?= htmlspecialchars(sprintf(lang_line('prg_admin_unlock_confirm_text', 'PR sudah %s. Membuka kunci memungkinkan Anda menambah/memperbaiki item sebagai Admin. Approval TIDAK direset.'), $header->status)); ?>"
                   data-icon="warning"
                   data-confirm-text="<?= htmlspecialchars(lang_line('prg_admin_unlock_confirm_button', 'Ya, Buka Kunci')); ?>">
                    <i class="fas fa-lock"></i> <?= lang_line('prg_admin_unlock_button', 'Buka Kunci Edit (Admin)'); ?>
                </a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                <a href="<?= base_url('purchase_request_approval_general/detail/' . $header->id) ?>" class="btn btn-sm btn-warning no-loader mr-1">
                    <i class="fas fa-lock-open"></i> <?= lang_line('prg_admin_lock_button', 'Selesai Edit (Kunci Lagi)'); ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('purchase_request_approval_general/export_detail_excel/'.$header->id) ?>" target="_blank" class="btn btn-success btn-sm no-loader mr-1"><i class="fas fa-file-excel fa-sm"></i> Excel</a>
            <a href="<?= base_url('purchase_request_approval_general/export_detail_pdf/'.$header->id) ?>" target="_blank" class="btn btn-danger btn-sm no-loader mr-1"><i class="fas fa-file-pdf fa-sm"></i> PDF</a>
            <a href="<?= base_url('purchase_request_approval_general') ?>" class="btn btn-sm btn-outline-secondary">
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

    <?php
        $reject_reason_display = $header->reject_reason ?? $header->rejection_notes ?? null;
        $rejecter_display      = $header->rejecter_name  ?? $header->approver_name  ?? null;
        $rejected_at_display   = $header->rejected_at    ?? $header->approved_at    ?? null;
    ?>
    <?php if ($header->status === 'Rejected' && $reject_reason_display): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle mr-1"></i>
            <strong><?= lang_line('purchase_request_approval_rejected_alert_text', 'Ditolak:'); ?></strong> <?= htmlspecialchars($reject_reason_display) ?>
            <?php if ($rejecter_display): ?>
                &mdash; <?= lang_line('purchase_request_approval_rejected_alert_by', 'oleh'); ?> <strong><?= htmlspecialchars($rejecter_display) ?></strong>
                <?php if ($rejected_at_display): ?>
                    <?= lang_line('purchase_request_approval_rejected_alert_on', 'pada'); ?> <?= date('d M Y H:i', strtotime($rejected_at_display)) ?>
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
        </div>
    <?php endif; ?>

    <!-- 3-Stage Approval Timeline -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold"><i class="fas fa-stream mr-1"></i> Workflow Approval</h6></div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3 mb-2">
                    <div class="<?= !empty($header->submitted_at) ? 'text-success' : 'text-muted' ?>">
                        <i class="fas fa-paper-plane fa-2x mb-1"></i>
                        <div><strong><?= lang_line('prag_stage_submitted'); ?></strong></div>
                        <?php if (!empty($header->submitted_at)): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($header->requester_name ?? '-') ?></small>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($header->submitted_at)) ?></small>
                        <?php else: ?>
                            <small class="text-muted">—</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="<?= !empty($header->hod_approved_at) ? 'text-success' : 'text-muted' ?>">
                        <i class="fas fa-user-check fa-2x mb-1"></i>
                        <div><strong><?= lang_line('prag_stage_hod'); ?></strong></div>
                        <?php if (!empty($header->hod_approved_at)): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($header->hod_approver_name ?? '-') ?></small>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($header->hod_approved_at)) ?></small>
                        <?php else: ?>
                            <small class="text-muted">—</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="<?= !empty($header->endorsed_at) ? 'text-success' : 'text-muted' ?>">
                        <i class="fas fa-thumbs-up fa-2x mb-1"></i>
                        <div><strong><?= lang_line('prag_stage_endorsement'); ?></strong></div>
                        <?php if (!empty($header->endorsed_at)): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($header->endorser_name ?? '-') ?></small>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($header->endorsed_at)) ?></small>
                        <?php else: ?>
                            <small class="text-muted">—</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="<?= !empty($header->final_approved_at) ? 'text-success' : 'text-muted' ?>">
                        <i class="fas fa-check-double fa-2x mb-1"></i>
                        <div><strong><?= lang_line('prag_stage_final'); ?></strong></div>
                        <?php if (!empty($header->final_approved_at)): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($header->final_approver_name ?? '-') ?></small>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($header->final_approved_at)) ?></small>
                        <?php elseif ($header->status === 'Approved' && !empty($header->approved_at)): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($header->approver_name ?? '-') ?></small>
                            <small class="text-muted"><?= date('d M Y H:i', strtotime($header->approved_at)) ?> <em>(legacy)</em></small>
                        <?php else: ?>
                            <small class="text-muted">—</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_approval_detail_items_title', 'Detail Item Request'); ?></h6>
            <div>
                <?php if (!empty($admin_edit_mode)): ?>
                    <button type="button" class="btn btn-success btn-sm mr-2" id="btnShowAddItemRow">
                        <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('purchase_request_add_item_manual', 'Tambah Item'); ?>
                    </button>
                <?php endif; ?>
                <span class="badge badge-primary" id="item-count"><?= count($details) ?> <?= lang_line('text_item', 'item'); ?></span>
            </div>
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
                            <th style="min-width:200px; max-width:280px;"><?= lang_line('purchase_request_approval_table_item_remarks', 'Remarks'); ?></th>
                            <?php if ($can_edit_items): ?><th width="90"><?= lang_line('label_action', 'Aksi'); ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="approvalItemBody">

                        <!-- Baris tambah item (mode edit Admin, tersembunyi sampai tombol diklik) -->
                        <?php if (!empty($admin_edit_mode)): ?>
                        <tr id="addItemRow" style="display:none; background:#e8f5e9;">
                            <td class="text-center"><i class="fas fa-plus text-success"></i></td>
                            <td><input type="text" id="newItemCode" class="form-control form-control-sm" readonly placeholder="<?= lang_line('pr_new_item_placeholder_auto', 'Otomatis'); ?>" style="background:#f8f9fa"></td>
                            <td style="min-width:220px">
                                <select id="newItemSelect" class="form-control form-control-sm" style="width:100%">
                                    <option value=""><?= lang_line('purchase_request_search_item', '-- Cari Item --'); ?></option>
                                </select>
                            </td>
                            <td class="text-center"><span id="newItemType" class="badge badge-secondary">-</span></td>
                            <td class="text-center"><small class="text-muted">-</small></td>
                            <td class="text-center"><input type="number" id="newItemQty" class="form-control form-control-sm" value="1" min="0.01" step="any" style="width:90px"></td>
                            <td class="text-center"><span id="newItemUnit" class="text-muted">-</span></td>
                            <td><input type="text" id="newItemDesc" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_item_desc_placeholder', 'Deskripsi (opsional)'); ?>"></td>
                            <td><input type="text" id="newItemRemarks" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_item_remarks_placeholder', 'Remarks (opsional)'); ?>"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-success" id="btnSaveItem"><i class="fas fa-save"></i></button>
                                <button type="button" class="btn btn-sm btn-secondary" id="btnCancelItem"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if (empty($details)): ?>
                            <tr id="noItemRow"><td colspan="<?= $can_edit_items ? 10 : 9 ?>" class="text-center text-muted py-3"><?= lang_line('pra_no_items'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($details as $i => $d): ?>
                            <?php $tc = ['material'=>'primary','machine'=>'warning','inventory'=>'info','semifinished'=>'secondary'][$d->item_type] ?? 'secondary'; ?>
                            <tr id="arow-<?= $d->id ?>">
                                <td class="text-center row-no"><?= $i + 1 ?></td>
                                <td><code class="item-code"><?= htmlspecialchars($d->item_code ?? '-') ?></code></td>
                                <td class="item-name"><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                                <td class="text-center"><span class="badge badge-<?= $tc ?> item-type"><?= ucfirst($d->item_type) ?></span></td>
                                <td class="text-center"><small><?= htmlspecialchars($d->sc_number ?? '-') ?></small></td>
                                <td class="text-center">
                                    <strong class="item-qty-display"><?= number_format($d->qty, 2) ?></strong>
                                    <small class="text-muted item-unit-inline"><?= htmlspecialchars($d->item_unit ?? '') ?></small>
                                </td>
                                <td class="text-center"><small class="item-unit"><?= htmlspecialchars($d->item_unit ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($d->description ?? '-') ?></small></td>
                                <td style="max-width:280px;"><small class="text-muted" style="display:block; word-break:break-word; overflow-wrap:anywhere; white-space:normal;"><?= htmlspecialchars($d->remarks ?? '-') ?></small></td>
                                <?php if ($can_edit_items): ?>
                                <td class="text-center">
                                    <?php
                                    // Baris yang sudah ditarik ke PO dikunci: qty-nya sudah jadi acuan
                                    // PO, mengubah/menghapusnya bikin baris PO menggantung.
                                    $po_list   = $po_progress[$d->id]['pos'] ?? [];
                                    $po_titles = [];
                                    foreach ($po_list as $p) {
                                        $po_titles[] = $p['po_number'] . ' (' . number_format($p['qty'], 2) . ')';
                                    }
                                    ?>
                                    <?php if (!empty($po_list)): ?>
                                        <span class="badge badge-light border text-muted" title="<?= htmlspecialchars(implode(', ', $po_titles)) ?>">
                                            <i class="fas fa-lock"></i> <?= lang_line('prg_admin_row_locked_badge', 'Terkunci (sudah di-PO)'); ?>
                                        </span>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-outline-warning btn-edit-qty"
                                            data-id="<?= $d->id ?>"
                                            data-qty="<?= $d->qty ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            data-item="<?= htmlspecialchars($d->item_type . '-' . $d->item_id) ?>"
                                            data-item-text="<?= htmlspecialchars('[' . strtoupper($d->item_type) . '] ' . ($d->item_code ?? '-') . ' - ' . ($d->item_name ?? '-')) ?>"
                                            title="<?= lang_line('pra_tooltip_edit_qty'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-item"
                                            data-id="<?= $d->id ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            title="<?= lang_line('pra_tooltip_delete_item'); ?>">
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

    <!-- Approval Action Bar (3-stage) -->
    <?php if ($can_head_dept || $can_endorsement || $can_approval || $can_return || $can_reject): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold"><?= lang_line('purchase_request_approval_decision_title', 'Keputusan'); ?></h6></div>
        <div class="card-body">
            <div class="d-flex flex-wrap" style="gap:.5rem">
                <?php if ($can_head_dept): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_general/approve_hod/' . $header->id) ?>"
                      onsubmit="return confirm('Approve PR ini sebagai Head of Dept?')" class="d-inline">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-user-check mr-1"></i> Approve as Head of Dept
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($can_endorsement): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_general/approve_endorsement/' . $header->id) ?>"
                      onsubmit="return confirm('Endorse PR ini?')" class="d-inline">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-thumbs-up mr-1"></i> Endorse
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($can_approval): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_general/approve_final/' . $header->id) ?>"
                      onsubmit="return confirm('Final approval untuk PR ini? Setelah ini PR siap diproses ke PO.')" class="d-inline">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-double mr-1"></i> Final Approve
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($can_return): ?>
                <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#returnModal">
                    <i class="fas fa-undo mr-1"></i> Return to Draft
                </button>
                <?php endif; ?>

                <?php if ($can_reject): ?>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times-circle mr-1"></i> Reject
                </button>
                <?php endif; ?>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Stage saat ini: <strong><?= htmlspecialchars($header->status) ?></strong>.
                Workflow: Submitted → Approved Head Dept → Endorsed → Approved.
            </small>
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
            <?php if (check_access('purchase_order_general', 'add')): ?>
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

    <!-- Admin Override -->
    <?php if (!empty($is_admin) && (!empty($can_admin_return) || !empty($can_admin_delete))): ?>
        <div class="card border-danger shadow-sm mt-3">
            <div class="card-body" style="background:#fff5f5;">
                <h6 class="text-danger mb-2"><i class="fas fa-user-shield mr-1"></i> Admin Override</h6>
                <p class="small text-muted mb-3">
                    Tombol di bawah bypass aturan status — untuk koreksi PR yang nyangkut di
                    approval (Submitted s/d Approved) atau sudah Rejected.
                    Force Return mengembalikan PR ke Draft &amp; mereset semua approval (item tetap).
                    Force Delete menghapus PR beserta detail item-nya.
                    <?php if (!empty($has_linked_po)): ?>
                        <br><span class="text-danger"><i class="fas fa-exclamation-triangle"></i>
                        PR ini sudah dikonversi ke PO — Force Delete dinonaktifkan supaya PO tidak orphan.</span>
                    <?php endif; ?>
                </p>
                <div class="row">
                    <?php if (!empty($can_admin_return)): ?>
                        <div class="col-md-7">
                            <form method="POST" action="<?= base_url('purchase_request_approval_general/return_to_draft/' . $header->id); ?>">
                                <div class="form-group mb-2">
                                    <textarea name="return_reason" class="form-control form-control-sm" rows="2" placeholder="Alasan force return (wajib)" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-warning btn-sm"
                                        onclick="return confirm('Force return PR ke Draft? Semua approval akan direset.');">
                                    <i class="fas fa-undo"></i> Force Return to Draft
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($can_admin_delete)): ?>
                        <div class="col-md-5 text-md-right mt-3 mt-md-0">
                            <form method="POST" action="<?= base_url('purchase_request_approval_general/delete_pr/' . $header->id); ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Hapus PR ini permanen? Termasuk semua detail item. Tindakan tidak bisa dibatalkan.');">
                                    <i class="fas fa-trash"></i> Force Delete PR
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

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
        'redirect_back' => 'approval',
        ]);
    ?>

</div>

<!-- Edit Qty Modal -->
<div class="modal fade" id="editQtyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:<?= !empty($admin_edit_mode) ? '520px' : '400px' ?>">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>
                    <?= !empty($admin_edit_mode)
                        ? lang_line('prg_admin_edit_item_title', 'Edit Item')
                        : lang_line('purchase_request_approval_edit_qty_title', 'Edit Qty Item'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted small" id="editQtyItemName"></p>
                <input type="hidden" id="editQtyDetailId">
                <?php if (!empty($admin_edit_mode)): ?>
                <!-- Ganti item/kode barang — hanya di mode edit Admin. Approver di
                     stage-nya tetap cuma boleh menyesuaikan qty seperti sebelumnya. -->
                <div class="form-group">
                    <label><?= lang_line('prg_admin_edit_item_label', 'Item / Kode Barang'); ?></label>
                    <select id="editItemSelect" class="form-control" style="width:100%"></select>
                    <small class="text-muted"><?= lang_line('prg_admin_edit_item_help', 'Kosongkan/biarkan kalau item tidak diganti. Mengganti item tidak mengubah status approval.'); ?></small>
                </div>
                <?php endif; ?>
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

<!-- Reject Modal (3-stage: form POST) -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request_approval_general/reject/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> <?= lang_line('purchase_request_approval_reject_modal_title', 'Tolak Purchase Request'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        PR yang di-reject <strong>tidak bisa direvisi</strong>. Gunakan "Return to Draft" jika ingin creator memperbaiki.
                    </div>
                    <div class="form-group mb-0">
                        <label><?= lang_line('prag_label_reject_reason'); ?> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="4" required
                                  placeholder="<?= htmlspecialchars(lang_line('prag_reject_reason_placeholder')); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i> Reject PR
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prag_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Return to Draft Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request_approval_general/return_to_draft/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i> Return to Draft</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?= lang_line('prag_return_note_detail'); ?>
                    </div>
                    <div class="form-group mb-0">
                        <label><?= lang_line('prag_return_reason_label'); ?> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="4" required
                                  placeholder="<?= htmlspecialchars(lang_line('prag_return_reason_placeholder')); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo mr-1"></i> Return to Draft
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prag_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Create PO Modal -->
<div class="modal fade" id="createPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 500px;">
        <form id="createPoForm" method="POST" action="<?= base_url('purchase_request_general/create_po_from_pr/' . $header->id) ?>">
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
var CAN_EDIT_ITEMS = <?= !empty($can_edit_items) ? 'true' : 'false' ?>;
// Flag mode edit Admin. Ikut dikirim di tiap aksi tulis — server menolak
// perubahan pada PR di luar stage approval kalau flag ini tidak ada.
var ADMIN_EDIT = <?= !empty($admin_edit_mode) ? "'1'" : "''" ?>;

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

    // ── Edit Item (qty, dan di mode Admin: item/kode barangnya juga) ──
    // Item yang sedang dipilih di modal; dipakai untuk menulis ulang sel baris
    // tanpa reload setelah item diganti.
    var _editItem = {};

    $(document).on('click', '.btn-edit-qty', function() {
        var $b  = $(this);
        var id  = $b.data('id');
        $('#editQtyDetailId').val(id);
        $('#editQtyValue').val($b.data('qty'));
        $('#editQtyItemName').text('Item: ' + $b.data('name'));
        _editItem = {};

        if (ADMIN_EDIT) {
            var $sel = $('#editItemSelect');
            if (!$sel.hasClass('select2-hidden-accessible')) {
                $sel.select2({
                    dropdownParent: $('#editQtyModal'),
                    width: '100%',
                    minimumInputLength: 2,
                    ajax: {
                        url: BASE_URL + 'purchase_request_approval_general/search_items',
                        dataType: 'json',
                        delay: 300,
                        data: function(p) { return { term: p.term }; },
                        processResults: function(d) { return d; }
                    }
                });
                $sel.on('select2:select', function(e) { _editItem = e.params.data; });
            }
            // Preselect item baris ini: option ditanam manual karena select2 ajax
            // tidak punya datanya sebelum user mengetik.
            $sel.empty().append(new Option($b.data('item-text'), $b.data('item'), true, true)).trigger('change');
        }

        $('#editQtyModal').modal('show');
        setTimeout(function(){ $('#editQtyValue').select(); }, 300);
    });

    $('#btnSaveQty').on('click', function() {
        var id  = $('#editQtyDetailId').val();
        var qty = parseFloat($('#editQtyValue').val());
        if (!qty || qty <= 0) { toastr.error(LANG.qtyErrorZero); return; }

        var payload = { qty: qty, admin_edit: ADMIN_EDIT };
        if (ADMIN_EDIT) payload.item_id = $('#editItemSelect').val() || '';

        var $btn     = $(this);
        var btnLabel = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url:      BASE_URL + 'purchase_request_approval_general/edit_item/' + id,
            type:     'POST',
            dataType: 'json',
            data:     payload,
            success:  function(res) {
                if (res.success) {
                    var $row = $('#arow-' + id);
                    $row.find('.item-qty-display').text(parseFloat(res.new_qty).toFixed(2));
                    $row.find('.btn-edit-qty').data('qty', res.new_qty).attr('data-qty', res.new_qty);

                    // Item diganti → tulis ulang kode/nama/tipe/satuan barisnya.
                    if (_editItem.id) {
                        var esc = function(s) { return $('<div>').text(String(s || '')).html(); };
                        var typeLabel = _editItem.type ? (_editItem.type.charAt(0).toUpperCase() + _editItem.type.slice(1)) : '-';
                        $row.find('.item-code').text(_editItem.code || '-');
                        $row.find('.item-name').text(_editItem.name || '-');
                        $row.find('.item-type').text(typeLabel).attr('class', 'badge badge-' + typeColor(_editItem.type) + ' item-type');
                        $row.find('.item-unit').text(_editItem.unit || '-');
                        $row.find('.item-unit-inline').text(_editItem.unit || '');
                        $row.find('.btn-edit-qty')
                            .attr('data-name', _editItem.name || '')
                            .attr('data-item', _editItem.id)
                            .attr('data-item-text', _editItem.text || '')
                            .data({ name: _editItem.name || '', item: _editItem.id, 'item-text': _editItem.text || '' });
                        $row.find('.btn-delete-item').attr('data-name', _editItem.name || '').data('name', _editItem.name || '');
                    }

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
                $btn.prop('disabled', false).html(btnLabel);
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
            url:      BASE_URL + 'purchase_request_approval_general/delete_item/' + id,
            type:     'POST',
            dataType: 'json',
            data:     { admin_edit: ADMIN_EDIT },
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
                            $('#approvalItemBody').html('<tr id="noItemRow"><td colspan="10" class="text-center text-muted py-3">' + LANG.allItemsDeleted + '</td></tr>');
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

    // ── Tambah Item (mode edit Admin) ─────────────────────
    var _selectedItem = {};

    function resetAddRow() {
        if ($('#newItemSelect').hasClass('select2-hidden-accessible')) {
            $('#newItemSelect').val(null).trigger('change');
        }
        $('#newItemCode').val('');
        $('#newItemType').text('-').attr('class', 'badge badge-secondary');
        $('#newItemUnit').text('-');
        $('#newItemQty').val(1);
        $('#newItemDesc').val('');
        $('#newItemRemarks').val('');
        _selectedItem = {};
    }

    function typeColor(type) {
        var map = { material:'primary', machine:'warning', inventory:'info', semifinished:'secondary' };
        return map[type] || 'secondary';
    }

    $('#btnShowAddItemRow').on('click', function() {
        $('#addItemRow').show();
        $('#noItemRow').hide();
        $(this).hide();
        if (!$('#newItemSelect').hasClass('select2-hidden-accessible')) {
            $('#newItemSelect').select2({
                placeholder: '-- Cari dan Pilih Item --',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: BASE_URL + 'purchase_request_approval_general/search_items',
                    dataType: 'json',
                    delay: 300,
                    data: function(p) { return { term: p.term }; },
                    processResults: function(d) { return d; }
                }
            });
        }
        $('#newItemSelect').off('select2:select').on('select2:select', function(e) {
            _selectedItem = e.params.data;
            $('#newItemCode').val(_selectedItem.code || '');
            $('#newItemType').text(_selectedItem.type || '-').attr('class', 'badge badge-' + typeColor(_selectedItem.type));
            $('#newItemUnit').text(_selectedItem.unit || '-');
        });
        $('#newItemSelect').off('select2:clear').on('select2:clear', resetAddRow);
    });

    $('#btnCancelItem').on('click', function() {
        $('#addItemRow').hide();
        $('#btnShowAddItemRow').show();
        if ($('#approvalItemBody tr[id^="arow-"]').length === 0) $('#noItemRow').show();
        resetAddRow();
    });

    $('#btnSaveItem').on('click', function() {
        var itemVal = $('#newItemSelect').val();
        var qty     = parseFloat($('#newItemQty').val());
        if (!itemVal) { toastr.error('Pilih item terlebih dahulu'); return; }
        if (!qty || qty <= 0) { toastr.error(LANG.qtyErrorZero); return; }

        var desc    = $('#newItemDesc').val();
        var remarks = $('#newItemRemarks').val();
        var $btn    = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url:      BASE_URL + 'purchase_request_approval_general/add_single_item/' + PR_ID,
            type:     'POST',
            dataType: 'json',
            data:     { item_id: itemVal, qty: qty, description: desc, remarks: remarks, admin_edit: ADMIN_EDIT },
            success:  function(res) {
                if (!res.success) { toastr.error(res.message || 'Gagal menyimpan item.'); return; }
                toastr.success(res.message);

                var esc = function(s) { return $('<div>').text(String(s || '')).html(); };

                if (res.updated) {
                    // Item sudah ada → qty barisnya yang bertambah.
                    var $row = $('#arow-' + res.detail_id);
                    $row.find('.item-qty-display').text(parseFloat(res.new_qty).toFixed(2));
                    $row.find('.btn-edit-qty').data('qty', res.new_qty).attr('data-qty', res.new_qty);
                } else {
                    var type      = _selectedItem.type || '';
                    var typeLabel = type ? (type.charAt(0).toUpperCase() + type.slice(1)) : '-';
                    var unit      = _selectedItem.unit || '-';
                    var actionTd  = CAN_EDIT_ITEMS
                        ? '<td class="text-center">' +
                          '<button type="button" class="btn btn-xs btn-outline-warning btn-edit-qty" data-id="' + res.detail_id +
                          '" data-qty="' + qty + '" data-name="' + esc(_selectedItem.name) +
                          '" data-item="' + esc(_selectedItem.id || '') + '" data-item-text="' + esc(_selectedItem.text || '') +
                          '"><i class="fas fa-edit"></i></button> ' +
                          '<button type="button" class="btn btn-xs btn-outline-danger btn-delete-item" data-id="' + res.detail_id +
                          '" data-name="' + esc(_selectedItem.name) + '"><i class="fas fa-trash"></i></button>' +
                          '</td>'
                        : '';

                    $('#noItemRow').remove();
                    $('#approvalItemBody').append(
                        '<tr id="arow-' + res.detail_id + '">' +
                        '<td class="text-center row-no">' + ($('#approvalItemBody tr[id^="arow-"]').length + 1) + '</td>' +
                        '<td><code class="item-code">' + esc(_selectedItem.code || '-') + '</code></td>' +
                        '<td class="item-name">' + esc(_selectedItem.name || '-') + '</td>' +
                        '<td class="text-center"><span class="badge badge-' + typeColor(type) + ' item-type">' + esc(typeLabel) + '</span></td>' +
                        '<td class="text-center"><small>-</small></td>' +
                        '<td class="text-center"><strong class="item-qty-display">' + qty.toFixed(2) + '</strong> <small class="text-muted item-unit-inline">' + esc(unit) + '</small></td>' +
                        '<td class="text-center"><small class="item-unit">' + esc(unit) + '</small></td>' +
                        '<td><small>' + esc(desc || '-') + '</small></td>' +
                        '<td style="max-width:280px;"><small class="text-muted" style="display:block; word-break:break-word; overflow-wrap:anywhere; white-space:normal;">' + esc(remarks || '-') + '</small></td>' +
                        actionTd +
                        '</tr>'
                    );
                    $('#item-count').text($('#approvalItemBody tr[id^="arow-"]').length + ' item');
                }

                $('#addItemRow').hide();
                $('#btnShowAddItemRow').show();
                resetAddRow();
            },
            error: function(xhr) { toastr.error(LANG.serverError + xhr.status + ').'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fas fa-save"></i>'); }
        });
    });

    // Approve / Reject / Return sekarang pakai form POST (lihat tombol & modal di atas).
    // Tidak ada handler AJAX di sini supaya pola identik dengan Permintaan Barang / Consumable_request.

    // Enter key in qty modal
    $('#editQtyValue').on('keypress', function(e) {
        if (e.which === 13) $('#btnSaveQty').click();
    });

});
</script>
