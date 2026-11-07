<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i> Review Purchase Request Jasa</h3>
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
            <a href="<?= base_url('purchase_request_approval_jasa/export_detail_excel/'.$header->id) ?>" target="_blank" class="btn btn-success btn-sm no-loader mr-1"><i class="fas fa-file-excel fa-sm"></i> Excel</a>
            <a href="<?= base_url('purchase_request_approval_jasa/export_detail_pdf/'.$header->id) ?>" target="_blank" class="btn btn-danger btn-sm no-loader mr-1"><i class="fas fa-file-pdf fa-sm"></i> PDF</a>
            <a href="<?= base_url('purchase_request_approval_jasa') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
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
            <strong>Ditolak:</strong> <?= htmlspecialchars($reject_reason_display) ?>
            <?php if ($rejecter_display): ?>
                &mdash; oleh <strong><?= htmlspecialchars($rejecter_display) ?></strong>
                <?php if ($rejected_at_display): ?>
                    pada <?= date('d M Y H:i', strtotime($rejected_at_display)) ?>
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
                        <div><strong>Submitted</strong></div>
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
                        <div><strong>Head of Dept</strong></div>
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
                        <div><strong>Endorsement</strong></div>
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
                        <div><strong>Final Approval</strong></div>
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
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold">Informasi Purchase Request</h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">PR Number</small>
                    <strong><?= htmlspecialchars($header->pr_number) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Tanggal PR</small>
                    <strong><?= date('d M Y', strtotime($header->pr_date)) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Requester</small>
                    <strong><?= htmlspecialchars($header->requester_name ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Supplier (Usulan)</small>
                    <strong><?= htmlspecialchars($header->nama_supplier ?? 'Belum ditentukan'); ?></strong>
                </div>
            </div>
            <?php if ($header->purpose): ?>
            <hr class="my-3">
            <div>
                <small class="text-muted d-block">Keperluan / Tujuan</small>
                <strong><?= htmlspecialchars($header->purpose) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($header->remarks): ?>
            <div class="mt-2">
                <small class="text-muted d-block">Keterangan</small>
                <?= nl2br(htmlspecialchars($header->remarks)) ?>
            </div>
            <?php endif; ?>
            <?php if ($header->status === 'Approved'): ?>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block">Disetujui Oleh</small>
                    <strong class="text-success"><?= htmlspecialchars($header->approver_name ?? '-') ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Tanggal Approval</small>
                    <strong><?= $header->approved_at ? date('d M Y H:i', strtotime($header->approved_at)) : '-' ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($can_approve): ?>
    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle mr-1"></i>
        <strong>Mode Review:</strong> Anda dapat mengedit qty atau menghapus item yang tidak disetujui sebelum memberi keputusan.
    </div>
    <?php endif; ?>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold">Detail Item Jasa</h6>
            <span class="badge badge-primary" id="item-count"><?= count($details) ?> item</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="approvalItemTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Jasa</th>
                            <th>Qty Request</th>
                            <th>Satuan</th>
                            <th>Deskripsi</th>
                            <th style="min-width:200px; max-width:280px;">Remarks</th>
                            <?php if ($can_approve): ?><th width="90">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="approvalItemBody">
                        <?php if (empty($details)): ?>
                            <tr id="noItemRow"><td colspan="<?= $can_approve ? 8 : 7 ?>" class="text-center text-muted py-3">Tidak ada item</td></tr>
                        <?php else: ?>
                            <?php foreach ($details as $i => $d): ?>
                            <tr id="arow-<?= $d->id ?>">
                                <td class="text-center row-no"><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($d->item_code ?? '-') ?></code></td>
                                <td><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                                <td class="text-center">
                                    <strong class="item-qty-display"><?= number_format($d->qty, 2) ?></strong>
                                </td>
                                <td class="text-center"><small><?= htmlspecialchars($d->item_unit ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($d->description ?? '-') ?></small></td>
                                <td style="max-width:280px;"><small class="text-muted" style="display:block; word-break:break-word; overflow-wrap:anywhere; white-space:normal;"><?= htmlspecialchars($d->remarks ?? '-') ?></small></td>
                                <?php if ($can_approve): ?>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-warning btn-edit-qty"
                                            data-id="<?= $d->id ?>"
                                            data-qty="<?= $d->qty ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            title="Edit Qty">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete-item"
                                            data-id="<?= $d->id ?>"
                                            data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                            title="Hapus Item">
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

    <!-- Approval Action Bar (3-stage) -->
    <?php if ($can_head_dept || $can_endorsement || $can_approval || $can_return || $can_reject): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light"><h6 class="mb-0 font-weight-bold">Keputusan</h6></div>
        <div class="card-body">
            <div class="d-flex flex-wrap" style="gap:.5rem">
                <?php if ($can_head_dept): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_jasa/approve_hod/' . $header->id) ?>"
                      onsubmit="return confirm('Approve PR ini sebagai Head of Dept?')" class="d-inline">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-user-check mr-1"></i> Approve as Head of Dept
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($can_endorsement): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_jasa/approve_endorsement/' . $header->id) ?>"
                      onsubmit="return confirm('Endorse PR ini?')" class="d-inline">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-thumbs-up mr-1"></i> Endorse
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($can_approval): ?>
                <form method="POST" action="<?= base_url('purchase_request_approval_jasa/approve_final/' . $header->id) ?>"
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
                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> PR Telah Disetujui</span>
                <?php if ($header->approver_name): ?>
                    <small class="text-muted ml-2">oleh <strong><?= htmlspecialchars($header->approver_name) ?></strong>
                    <?= $header->approved_at ? 'pada ' . date('d M Y H:i', strtotime($header->approved_at)) : '' ?></small>
                <?php endif; ?>
            </div>
            <?php if (check_access('purchase_order_jasa', 'add')): ?>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createPoModal">
                <i class="fas fa-shopping-cart mr-1"></i> Buat PO dari PR
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($header->status === 'Rejected'): ?>
    <div class="alert alert-danger border-0 shadow-sm">
        <i class="fas fa-times-circle mr-1"></i> PR ini telah ditolak dan tidak dapat diproses lebih lanjut.
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
                            <form method="POST" action="<?= base_url('purchase_request_approval_jasa/return_to_draft/' . $header->id); ?>">
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
                            <form method="POST" action="<?= base_url('purchase_request_approval_jasa/delete_pr/' . $header->id); ?>">
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
            'reply_action' => base_url('purchase_request_jasa/reply/' . $header->id),
            'owner_id'     => (int) ($header->requester_id ?? $header->created_by ?? 0),
        'redirect_back' => 'approval',
        ]);
    ?>

</div>

<!-- Edit Qty Modal -->
<div class="modal fade" id="editQtyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i> Edit Qty Item</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted small" id="editQtyItemName"></p>
                <input type="hidden" id="editQtyDetailId">
                <div class="form-group mb-0">
                    <label>Qty yang Disetujui <span class="text-danger">*</span></label>
                    <input type="number" id="editQtyValue" class="form-control" step="any" min="0.01" placeholder="0">
                    <small class="text-muted">Ubah qty sesuai kebutuhan yang disetujui</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btnSaveQty">
                    <i class="fas fa-save mr-1"></i> Simpan Qty
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (3-stage: form POST) -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request_approval_jasa/reject/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> Tolak Purchase Request</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        PR yang di-reject <strong>tidak bisa direvisi</strong>. Gunakan "Return to Draft" jika ingin creator memperbaiki.
                    </div>
                    <div class="form-group mb-0">
                        <label>Alasan Reject <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="4" required
                                  placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i> Reject PR
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Return to Draft Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request_approval_jasa/return_to_draft/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i> Return to Draft</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        PR akan dikembalikan ke status Draft. Creator dapat merevisi & submit ulang.
                    </div>
                    <div class="form-group mb-0">
                        <label>Alasan Return <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="4" required
                                  placeholder="Jelaskan alasan pengembalian..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo mr-1"></i> Return to Draft
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Create PO Modal -->
<div class="modal fade" id="createPoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 500px;">
        <form id="createPoForm" method="POST" action="<?= base_url('purchase_request_jasa/create_po_from_pr/' . $header->id) ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-arrow-right mr-2"></i> Buat Purchase Order Jasa</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-2"></i> Pilih supplier yang akan mengerjakan PR ini</p>

                    <div class="form-group">
                        <label>Pilih Supplier <span class="text-danger">*</span></label>
                        <select id="supplierSelect" name="supplier_id" class="form-control select2-supplier">
                            <option value="">-- Pilih Supplier --</option>
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

                    <hr class="my-3">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="ppnCheckbox" name="include_ppn" value="1">
                            <label class="custom-control-label" for="ppnCheckbox">
                                <strong>Tambahkan Pajak (PPN / PPh)</strong>
                            </label>
                        </div>
                    </div>

                    <div id="ppnRateGroup" class="form-group" style="display: none;">
                        <label>Tarif Pajak (%)</label>
                        <input type="number" id="ppnRate" name="ppn_rate" class="form-control" value="11" min="0" max="100" step="0.01" placeholder="11">
                        <small class="text-muted">Contoh: 11 (PPN 11%), 2 (PPH 2%), atau nilai custom lainnya</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" id="submitCreatePo" class="btn btn-primary" disabled><i class="fas fa-check mr-1"></i> Buat PO</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
var BASE_URL  = '<?= base_url() ?>';
var PR_ID     = <?= $header->id ?>;
var CAN_APPROVE = <?= $can_approve ? 'true' : 'false' ?>;

$(document).ready(function() {

    $('.select2-supplier').select2({ dropdownParent: $('#createPoModal'), width: '100%' });

    $('#supplierSelect').on('change', function() {
        $('#submitCreatePo').prop('disabled', !$(this).val());
    });

    $('#ppnCheckbox').on('change', function() {
        $('#ppnRateGroup').toggle($(this).is(':checked'));
    });

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
        if (!qty || qty <= 0) { toastr.error('Qty harus lebih dari 0'); return; }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url:      BASE_URL + 'purchase_request_approval_jasa/edit_item/' + id,
            type:     'POST',
            dataType: 'json',
            data:     { qty: qty },
            success:  function(res) {
                if (res.success) {
                    var $row = $('#arow-' + id);
                    $row.find('.item-qty-display').text(parseFloat(res.new_qty).toFixed(2));
                    $row.find('.btn-edit-qty').data('qty', res.new_qty).attr('data-qty', res.new_qty);
                    $('#editQtyModal').modal('hide');
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || 'Gagal memperbarui qty.');
                }
            },
            error: function(xhr) {
                toastr.error('Gagal menghubungi server (' + xhr.status + ').');
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

        if (!confirm('Delete "' + name + '"?\n\nItem yang dihapus tidak akan masuk ke PO jika PR disetujui.')) return;

        $btn.prop('disabled', true);
        $.ajax({
            url:      BASE_URL + 'purchase_request_approval_jasa/delete_item/' + id,
            type:     'POST',
            dataType: 'json',
            success:  function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        $('#approvalItemBody tr[id^="arow-"]').each(function(i) {
                            $(this).find('.row-no').text(i + 1);
                        });
                        var remaining = $('#approvalItemBody tr[id^="arow-"]').length;
                        $('#item-count').text(remaining + ' item');
                        if (remaining === 0) {
                            $('#approvalItemBody').html('<tr id="noItemRow"><td colspan="8" class="text-center text-muted py-3">Semua item telah dihapus.</td></tr>');
                        }
                    });
                } else {
                    toastr.error(res.message || 'Gagal menghapus item.');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                toastr.error('Gagal menghubungi server (' + xhr.status + ').');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#editQtyValue').on('keypress', function(e) {
        if (e.which === 13) $('#btnSaveQty').click();
    });

});
</script>
