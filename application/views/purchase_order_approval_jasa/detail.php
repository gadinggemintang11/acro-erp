<?php
    $can_approve = !empty($can_approve_hod) || !empty($can_approve_endorsement) || !empty($can_approve_final);
    // Kolom "Aksi" di tabel item TIDAK lagi ikut $can_approve (lihat catatan di
    // dalam tabel) — satu variabel ini yang menentukan <th>, colspan baris
    // kosong, dan colspan tfoot supaya lebar kolomnya tetap sinkron.
    $show_item_action = !empty($can_edit_po_item_inline);
    $type_label  = $po_type_label ?? 'PO Jasa';
?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-clipboard-check text-primary mr-2"></i>
                <?= htmlspecialchars($type_label); ?> Approval &mdash; Detail
            </h3>
            <small class="text-muted">Alur approval 3 tahap: HOD &rarr; Endorsement &rarr; Approval Final</small>
        </div>
        <div>
            <?php
                // Pintu ke halaman PO utama utk EDIT (user 2026-09-10). Datanya
                // disiapkan Purchase_order_approval::detail() — aturannya sama
                // persis dgn tombol di halaman PO itu sendiri, jadi tidak ada
                // jalur edit baru yang lolos dari gate aslinya.
            ?>
            <?php if (!empty($can_edit_po_draft)): ?>
                <a href="<?= base_url($po_edit_route) ?>" class="btn btn-warning btn-sm mr-1">
                    <i class="fas fa-edit fa-sm"></i> Edit PO
                </a>
            <?php elseif (!empty($can_unlock_po_admin)): ?>
                <a href="<?= base_url($po_edit_route) ?>?admin_edit=1"
                   class="btn btn-outline-warning btn-sm mr-1 btn-confirm no-loader"
                   data-message="Buka kunci edit PO ini?"
                   data-text="PO sudah <?= htmlspecialchars($header->status) ?>. Membuka kunci memungkinkan Anda memperbaiki header/item sebagai Admin. Approval TIDAK direset."
                   data-icon="warning" data-confirm-text="Ya, Buka Kunci">
                    <i class="fas fa-lock fa-sm"></i> Buka Kunci Edit (Admin)
                </a>
            <?php endif; ?>
            <a href="<?= base_url('purchase_order_approval_jasa/export_detail_excel/'.$header->id) ?>" target="_blank" class="btn btn-success btn-sm no-loader mr-1"><i class="fas fa-file-excel fa-sm"></i> Excel</a>
            <a href="<?= base_url('purchase_order_approval_jasa/export_detail_pdf/'.$header->id) ?>" target="_blank" class="btn btn-danger btn-sm no-loader mr-1"><i class="fas fa-file-pdf fa-sm"></i> PDF</a>
            <a href="<?= base_url('purchase_order_approval_jasa') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Header Info -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-invoice-dollar mr-1"></i> <?= htmlspecialchars($header->po_number) ?>
            </h6>
            <?php
            $badge_map = [
                'Draft'=>'warning','Submitted'=>'info',
                'Approved Head Dept'=>'primary','Endorsed'=>'primary',
                'Confirmed'=>'success','Closed'=>'dark',
                'Cancelled'=>'secondary','Rejected'=>'danger'
            ];
            $badge = $badge_map[$header->status] ?? 'secondary';
            ?>
            <span class="badge badge-<?= $badge ?> p-2"><?= $header->status ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="40%"><strong>Supplier</strong></td><td>: <?= htmlspecialchars($header->nama_supplier ?? '-') ?></td></tr>
                        <tr><td><strong>Tanggal PO</strong></td><td>: <?= date('d M Y', strtotime($header->po_date)) ?></td></tr>
                        <tr><td><strong>Dibuat Oleh</strong></td><td>: <?= htmlspecialchars($header->creator_name ?? '-') ?></td></tr>
                        <tr><td><strong>Mata Uang</strong></td><td>: <?= htmlspecialchars($header->currency_code ?? 'IDR') ?>
                            <?php if ($header->currency_code != 'IDR'): ?>
                                <small class="text-muted">(Kurs: <?= number_format((float)$header->exchange_rate, 4) ?>)</small>
                            <?php endif; ?>
                        </td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td width="40%"><strong>Mode Pengiriman</strong></td><td>: <?= htmlspecialchars($header->shipping_mode_desc ?? '-') ?></td></tr>
                        <tr><td><strong>Mode Pembayaran</strong></td><td>: <?= htmlspecialchars($header->payment_mode_desc ?? '-') ?></td></tr>
                        <tr><td><strong>PPN 11%</strong></td><td>: <?= $header->has_ppn ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>' ?></td></tr>
                        <?php if ($header->status === 'Confirmed' && $header->confirmer_name): ?>
                        <tr><td><strong>Disetujui Oleh</strong></td><td>: <?= htmlspecialchars($header->confirmer_name) ?> <small class="text-muted">(<?= date('d M Y H:i', strtotime($header->confirmed_at)) ?>)</small></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php if (!empty($header->remarks)): ?>
                <hr class="mt-2 mb-2">
                <p class="mb-0 small"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($header->remarks)) ?></p>
            <?php endif; ?>
            <?php if (!empty($header->rejection_notes)): ?>
                <div class="alert alert-warning mt-2 mb-0 py-2">
                    <strong><i class="fas fa-exclamation-triangle mr-1"></i> Catatan Penolakan:</strong> <?= nl2br(htmlspecialchars($header->rejection_notes)) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Detail Item Jasa <span class="badge badge-secondary" id="item-count"><?= count($details) ?></span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 table-hover">
                    <thead class="thead-light text-center">
                        <tr>
                            <th width="4%">No</th>
                            <th width="14%">Kode</th>
                            <th>Nama Jasa</th>
                            <th width="8%">Satuan</th>
                            <th width="10%">Qty</th>
                            <th width="14%">Harga (<?= htmlspecialchars($header->currency_code ?? 'IDR') ?>)</th>
                            <th width="14%">Total (<?= htmlspecialchars($header->currency_code ?? 'IDR') ?>)</th>
                            <?php if ($can_approve): ?><th width="10%">Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="po-approval-items">
                        <?php if (empty($details)): ?>
                            <tr id="no-item-row"><td colspan="<?= $show_item_action ? 8 : 7 ?>" class="text-center text-muted py-4">Tidak ada item</td></tr>
                        <?php else: ?>
                            <?php $no = 1; $grand_total = 0; foreach ($details as $d): ?>
                            <?php $row_total = (float)$d->qty * (float)$d->base_price; $grand_total += $row_total; ?>
                            <tr data-detail-id="<?= $d->id ?>">
                                <td class="text-center row-no"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d->item_code ?? '-') ?></td>
                                <td><?= htmlspecialchars($d->item_name ?? '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-') ?></td>
                                <td class="text-right item-qty"><?= number_format((float)$d->qty, 4, '.', ',') ?></td>
                                <td class="text-right item-price"><?= number_format((float)$d->base_price, 4, '.', ',') ?></td>
                                <td class="text-right item-total"><?= number_format($row_total, 2, '.', ',') ?></td>
                                <?php // Kolom aksi item: dulu digating $can_approve — muncul begitu
                                      // saja untuk pemegang izin approval, tanpa buka kunci
                                      // (user 2026-09-10). Sekarang ikut kunci Admin yg sama
                                      // dgn halaman PO, dan gugur kalau barangnya sudah diterima. ?>
                                <?php if (!empty($can_edit_po_item_inline)): ?>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-xs mr-1 btn-edit-po-item"
                                        data-id="<?= $d->id ?>"
                                        data-qty="<?= (float)$d->qty ?>"
                                        data-price="<?= (float)$d->base_price ?>"
                                        data-desc="<?= htmlspecialchars($d->description ?? '') ?>"
                                        data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-xs btn-delete-po-item"
                                        data-id="<?= $d->id ?>"
                                        data-name="<?= htmlspecialchars($d->item_name ?? '') ?>"
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php
                        $ppn = $header->has_ppn ? $grand_total * 0.11 : 0;
                        $grand_final = $grand_total + $ppn;
                        ?>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="<?= $show_item_action ? 5 : 4 ?>" class="text-right">Subtotal</td>
                            <td class="text-right" id="subtotal-display"><?= number_format($grand_total, 2, '.', ',') ?></td>
                            <td colspan="<?= $show_item_action ? 2 : 1 ?>"></td>
                        </tr>
                        <?php if ($header->has_ppn): ?>
                        <tr class="bg-light">
                            <td colspan="<?= $show_item_action ? 5 : 4 ?>" class="text-right">PPN 11%</td>
                            <td class="text-right"><?= number_format($ppn, 2, '.', ',') ?></td>
                            <td colspan="<?= $show_item_action ? 2 : 1 ?>"></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="bg-primary text-white font-weight-bold">
                            <td colspan="<?= $show_item_action ? 5 : 4 ?>" class="text-right">Grand Total (<?= $header->currency_code ?>)</td>
                            <td class="text-right"><?= number_format($grand_final, 2, '.', ',') ?></td>
                            <td colspan="<?= $show_item_action ? 2 : 1 ?>"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== 3-Stage Approval Trail ===== -->
    <?php
        $stage_steps = [
            ['key'=>'submitted',   'label'=>'Submitted',   'by_field'=>'submitted_by',      'at_field'=>'submitted_at',      'reached'=>!empty($header->submitted_at) || !in_array($header->status, ['Draft'])],
            ['key'=>'hod',         'label'=>'HOD Approval','by_field'=>'hod_approved_by',   'at_field'=>'hod_approved_at',   'reached'=>!empty($header->hod_approved_at)],
            ['key'=>'endorsement', 'label'=>'Endorsement', 'by_field'=>'endorsed_by',       'at_field'=>'endorsed_at',       'reached'=>!empty($header->endorsed_at)],
            ['key'=>'final',       'label'=>'Final Approval','by_field'=>'final_approved_by','at_field'=>'final_approved_at','reached'=>!empty($header->final_approved_at) || $header->status === 'Confirmed'],
        ];
        // PO 1-stage (ERP Revision Notes 2026-09-02, Fase 2 poin 7): HOD &
        // Endorsement SELALU dilewati sistem sekarang (lihat Po_model::update_status()),
        // bukan cuma utk requester tertentu lagi -> trail cukup Submitted -> Final Approval.
        $stage_steps = array_values(array_filter($stage_steps, function($s) {
            return !in_array($s['key'], ['hod', 'endorsement'], true);
        }));
    ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-route mr-1"></i> Approval Trail</h6>
        </div>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-around align-items-start">
                <?php foreach ($stage_steps as $idx => $step):
                    $reached = !empty($step['reached']);
                    $by_id   = $header->{$step['by_field']} ?? null;
                    $at_raw  = $header->{$step['at_field']} ?? null;
                    $by_name = ($by_id && !empty($audit[$by_id])) ? $audit[$by_id] : null;
                ?>
                    <div class="text-center flex-grow-1 px-2" style="min-width:130px;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                             style="width:42px;height:42px;background:<?= $reached ? '#1cc88a' : '#e3e6f0'; ?>;color:<?= $reached ? '#fff' : '#858796'; ?>;font-weight:bold;">
                            <i class="fas fa-<?= $reached ? 'check' : ($idx + 1); ?>"></i>
                        </div>
                        <div class="small font-weight-bold <?= $reached ? 'text-success' : 'text-muted'; ?>"><?= htmlspecialchars($step['label']); ?></div>
                        <?php if ($reached && $by_name): ?>
                            <div class="small text-muted"><?= htmlspecialchars($by_name); ?></div>
                            <div class="small text-muted"><?= $at_raw ? date('d M Y H:i', strtotime($at_raw)) : ''; ?></div>
                        <?php elseif ($reached): ?>
                            <div class="small text-muted">—</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($idx < count($stage_steps) - 1): ?>
                        <div class="align-self-center text-muted d-none d-md-block" style="margin-top:14px;">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($header->reject_reason)): ?>
            <div class="alert alert-danger mt-3 mb-0 py-2">
                <strong><i class="fas fa-times-circle mr-1"></i> Rejected:</strong>
                <?= nl2br(htmlspecialchars($header->reject_reason)); ?>
                <?php if (!empty($header->rejected_by) && !empty($audit[$header->rejected_by])): ?>
                    <small class="text-muted ml-2">oleh <?= htmlspecialchars($audit[$header->rejected_by]); ?> pada <?= date('d M Y H:i', strtotime($header->rejected_at)); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($header->return_reason)): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <strong><i class="fas fa-undo mr-1"></i> Returned to Draft:</strong>
                <?= nl2br(htmlspecialchars($header->return_reason)); ?>
                <?php if (!empty($header->returned_to_draft_by) && !empty($audit[$header->returned_to_draft_by])): ?>
                    <small class="text-muted ml-2">oleh <?= htmlspecialchars($audit[$header->returned_to_draft_by]); ?> pada <?= date('d M Y H:i', strtotime($header->returned_to_draft_at)); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Riwayat Approval (History, ERP Revision Notes 2026-09-02 Fase 2 poin 11;
         Back to Draft 2-arah/reply, [[erp_revision_cross_cutting]]) ===== -->
    <?php if (!empty($approval_logs) || !empty($can_reply)): ?>
    <?php
        $po_log_meta = [
            'Submit'           => ['label' => 'Submitted',        'icon' => 'paper-plane',    'color' => 'info'],
            'Approve HOD'      => ['label' => 'Approved (Head of Dept)', 'icon' => 'check',   'color' => 'success'],
            'Endorse'          => ['label' => 'Endorsed',         'icon' => 'file-signature', 'color' => 'primary'],
            'Approve Final'    => ['label' => 'Final Approved',   'icon' => 'check-double',   'color' => 'success'],
            'Return to Draft'  => ['label' => 'Returned to Draft','icon' => 'undo',           'color' => 'warning'],
            'Reject'           => ['label' => 'Rejected',         'icon' => 'times',          'color' => 'danger'],
            'Reply'            => ['label' => 'Balasan',          'icon' => 'reply',          'color' => 'secondary'],
        ];
    ?>
    <div class="card shadow-sm mb-4" id="riwayat-approval">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> Riwayat Approval</h6>
        </div>
        <div class="card-body">
            <ul class="list-unstyled mb-0">
                <?php foreach ($approval_logs as $log):
                    $m = $po_log_meta[$log->action] ?? ['label' => $log->action, 'icon' => 'circle', 'color' => 'secondary'];
                    $is_reply = ($log->action === 'Reply');
                    $reply_from_requestor = $is_reply && ((int) ($log->actor_id ?? 0) === (int) $header->created_by);
                ?>
                <?php if ($is_reply): ?>
                <li class="d-flex mb-3 <?= $reply_from_requestor ? 'flex-row-reverse text-right' : ''; ?>">
                    <div class="<?= $reply_from_requestor ? 'ml-2' : 'mr-2'; ?>" style="max-width:75%;">
                        <div class="px-3 py-2" style="background:<?= $reply_from_requestor ? '#e7f1ff' : '#f1f1f1'; ?>;border-radius:14px;<?= $reply_from_requestor ? 'border-top-right-radius:2px;' : 'border-top-left-radius:2px;'; ?>">
                            <div class="small font-weight-bold text-gray-800"><?= htmlspecialchars($log->actor_name ?: '-'); ?> <span class="font-weight-normal text-muted">— <?= $reply_from_requestor ? 'Requestor' : 'Approver'; ?></span></div>
                            <div class="small" style="word-break:break-word;"><?= nl2br(htmlspecialchars($log->notes ?? '')); ?></div>
                        </div>
                        <div class="small text-muted mt-1"><?= $log->created_at ? date('d M Y H:i', strtotime($log->created_at)) : ''; ?></div>
                    </div>
                </li>
                <?php else: ?>
                <li class="d-flex mb-3">
                    <div class="mr-3 text-center" style="width:34px;">
                        <span class="badge badge-<?= $m['color']; ?> d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:50%;">
                            <i class="fas fa-<?= $m['icon']; ?>"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="font-weight-bold text-<?= $m['color']; ?>"><?= htmlspecialchars($m['label']); ?></div>
                        <div class="small text-gray-800"><?= htmlspecialchars($log->actor_name ?: '-'); ?></div>
                        <div class="small text-muted"><?= $log->created_at ? date('d M Y H:i', strtotime($log->created_at)) : ''; ?></div>
                        <?php if (!empty($log->notes)): ?>
                            <div class="mt-1 px-2 py-1 small" style="background:#f8f9fc;border-left:3px solid #d1d3e2;border-radius:3px;color:#555;font-style:italic;word-break:break-word;">
                                <i class="fas fa-comment-dots text-muted mr-1"></i><?= nl2br(htmlspecialchars($log->notes)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <?php if (!empty($can_reply)): ?>
            <form method="POST" action="<?= base_url('purchase_order_approval_jasa/reply/' . $header->id); ?>" class="mt-3 pt-3 border-top">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Balas</label>
                    <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Tulis balasan/klarifikasi..." required></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> Kirim Balasan</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== Action Buttons (3-stage gated) ===== -->
    <?php if (!empty($can_approve_hod) || !empty($can_approve_endorsement) || !empty($can_approve_final) || !empty($can_return_reject)): ?>
    <div class="card shadow-sm mb-4 border-left-info">
        <div class="card-body py-3">
            <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-gavel mr-1"></i> Keputusan Approval</h6>

            <?php if (!empty($can_approve_hod)): ?>
                <a href="<?= base_url('purchase_order_approval_jasa/approve_hod/' . $header->id); ?>"
                   class="btn btn-success mr-2"
                   onclick="return confirm('Approve <?= htmlspecialchars($type_label); ?> ini sebagai Head of Dept?');">
                    <i class="fas fa-check mr-1"></i> Approve (Head of Dept)
                </a>
            <?php endif; ?>

            <?php if (!empty($can_approve_endorsement)): ?>
                <a href="<?= base_url('purchase_order_approval_jasa/approve_endorsement/' . $header->id); ?>"
                   class="btn btn-success mr-2"
                   onclick="return confirm('Endorse <?= htmlspecialchars($type_label); ?> ini?');">
                    <i class="fas fa-stamp mr-1"></i> Endorse
                </a>
            <?php endif; ?>

            <?php if (!empty($can_approve_final)): ?>
                <a href="<?= base_url('purchase_order_approval_jasa/approve_final/' . $header->id); ?>"
                   class="btn btn-success mr-2"
                   onclick="return confirm('Final approve <?= htmlspecialchars($type_label); ?>? Setelah ini PO menjadi Confirmed dan siap diselesaikan jasanya.');">
                    <i class="fas fa-check-double mr-1"></i> Final Approve
                </a>
            <?php endif; ?>

            <?php if (!empty($can_return_reject)): ?>
                <button type="button" class="btn btn-warning mr-2" data-toggle="modal" data-target="#returnDraftModal">
                    <i class="fas fa-undo mr-1"></i> Return to Draft
                </button>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times-circle mr-1"></i> Reject
                </button>
            <?php endif; ?>

            <?php if (empty($can_approve_hod) && empty($can_approve_endorsement) && empty($can_approve_final) && !empty($can_return_reject)): ?>
                <div class="mt-2 small text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Anda tidak punya akses untuk stage <strong><?= htmlspecialchars($header->status); ?></strong>.
                    Hanya bisa Return atau Reject.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($header->status === 'Confirmed'): ?>
    <div class="card border-success shadow-sm mb-4">
        <div class="card-body py-3 text-success">
            <i class="fas fa-check-circle fa-lg mr-2"></i>
            <strong><?= htmlspecialchars($type_label); ?> Telah Disetujui (Final)</strong>
            <?php
                $final_by_id = $header->final_approved_by ?? $header->confirmed_by ?? null;
                $final_by    = ($final_by_id && !empty($audit[$final_by_id])) ? $audit[$final_by_id] : ($header->confirmer_name ?? null);
                $final_at    = $header->final_approved_at ?? $header->confirmed_at ?? null;
            ?>
            <?php if ($final_by): ?>
                oleh <strong><?= htmlspecialchars($final_by); ?></strong>
                <?php if ($final_at): ?> pada <?= date('d M Y H:i', strtotime($final_at)); ?><?php endif; ?>
            <?php endif; ?>
            <br><small class="text-muted">PO ini sudah bisa diselesaikan jasanya lewat tombol "Selesaikan Jasa" di halaman detail PO.</small>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Edit Item Modal -->
<?php // Ikut $show_item_action, bukan $can_approve: kalau tidak, Admin yang
      // membuka kunci tapi kebetulan tidak punya izin approval di menu ini
      // akan melihat tombol edit/hapus TANPA modal & JS-nya ikut ter-render
      // (tombolnya jadi mati). ?>
<?php if ($show_item_action): ?>
<div class="modal fade" id="editPoItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i> Edit Item Jasa</h5>
                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="poa-edit-id">
                <p class="font-weight-bold mb-3" id="poa-edit-name"></p>
                <div class="form-group">
                    <label>Qty <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" class="form-control" id="poa-edit-qty">
                </div>
                <div class="form-group">
                    <label>Harga Satuan (<?= htmlspecialchars($header->currency_code ?? 'IDR') ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" min="0" class="form-control" id="poa-edit-price" placeholder="0.0000">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <input type="text" class="form-control" id="poa-edit-desc" placeholder="Opsional...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btn-save-poa-item">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (form POST) -->
<form method="post" action="<?= base_url('purchase_order_approval_jasa/reject/' . $header->id); ?>">
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> Reject <?= htmlspecialchars($type_label); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">PO akan ditandai Rejected dan tidak bisa dilanjutkan.</p>
                <div class="form-group mb-0">
                    <label>Alasan Reject <span class="text-danger">*</span></label>
                    <textarea name="reject_reason" class="form-control" rows="4" required placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times-circle mr-1"></i> Reject
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>
</form>

<!-- Return to Draft Modal (form POST) -->
<form method="post" action="<?= base_url('purchase_order_approval_jasa/return_to_draft/' . $header->id); ?>">
<div class="modal fade" id="returnDraftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-undo mr-2"></i> Return to Draft</h5>
                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">PO akan dikembalikan ke status Draft. Creator bisa revisi & submit ulang.</p>
                <div class="form-group mb-0">
                    <label>Alasan Return <span class="text-danger">*</span></label>
                    <textarea name="return_reason" class="form-control" rows="4" required placeholder="Jelaskan alasan pengembalian..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-undo mr-1"></i> Return to Draft
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>
</form>
<?php endif; ?>

<script>
const BASE_URL = '<?= base_url() ?>';
const PO_ID    = <?= (int)$header->id ?>;

$(function() {

    // --- Edit Item ---
    $(document).on('click', '.btn-edit-po-item', function() {
        const $btn = $(this);
        $('#poa-edit-id').val($btn.data('id'));
        $('#poa-edit-name').text($btn.data('name'));
        $('#poa-edit-qty').val($btn.data('qty'));
        $('#poa-edit-price').val($btn.data('price'));
        $('#poa-edit-desc').val($btn.data('desc'));
        $('#editPoItemModal').modal('show');
    });

    $('#btn-save-poa-item').on('click', function() {
        const detailId = $('#poa-edit-id').val();
        const qty      = parseFloat($('#poa-edit-qty').val());
        const price    = parseFloat($('#poa-edit-price').val());
        const desc     = $('#poa-edit-desc').val();

        if (!qty || qty <= 0) { alert('Qty harus lebih dari 0'); return; }
        if (isNaN(price) || price < 0) { alert('Harga tidak boleh negatif'); return; }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.post(BASE_URL + 'purchase_order_approval_jasa/edit_item/' + detailId, {
            qty: qty, base_price: price, description: desc
        }, function(res) {
            if (res && res.success) {
                const $row = $('[data-detail-id="' + detailId + '"]');
                $row.find('.item-qty').text(qty.toFixed(4).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                $row.find('.item-price').text(price.toFixed(4).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                $row.find('.item-total').text((qty * price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                $row.find('.btn-edit-po-item').data('qty', qty).data('price', price).data('desc', desc);
                $('#editPoItemModal').modal('hide');
                if (typeof toastr !== 'undefined') toastr.success(res.message);
                else alert(res.message);
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
            } else {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                else alert(res.message);
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
            }
        }, 'json').fail(function() {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error!', text: 'Gagal menghubungi server.' });
            $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
        });
    });

    // --- Delete Item ---
    $(document).on('click', '.btn-delete-po-item', function() {
        const detailId = $(this).data('id');
        const itemName = $(this).data('name');
        const $row = $(this).closest('tr');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning', title: 'Hapus Item?',
                text: '"' + itemName + '" akan dihapus dari PO ini.',
                showCancelButton: true, confirmButtonColor: '#e3342f',
                confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
            }).then(result => {
                if (!result.isConfirmed) return;
                _doDeleteItem(detailId, $row);
            });
        } else {
            if (!confirm('Hapus item ini?')) return;
            _doDeleteItem(detailId, $row);
        }
    });

    function _doDeleteItem(detailId, $row) {
        $.post(BASE_URL + 'purchase_order_approval_jasa/delete_item/' + detailId, {}, function(res) {
            if (res && res.success) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                    $('#po-approval-items tr').each(function(i) {
                        $(this).find('.row-no').text(i + 1);
                    });
                    const cnt = $('#po-approval-items tr').length;
                    $('#item-count').text(cnt);
                    if (cnt === 0) {
                        $('#po-approval-items').html('<tr id="no-item-row"><td colspan="8" class="text-center text-muted py-4">Tidak ada item</td></tr>');
                    }
                });
                if (typeof toastr !== 'undefined') toastr.success(res.message);
            } else {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
            }
        }, 'json').fail(function() {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error!', text: 'Gagal menghubungi server.' });
        });
    }

});
</script>
