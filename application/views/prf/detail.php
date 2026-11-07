<?php
    // Approval 1 tahap (2026-11-06): Submitted -> Disetujui (Head of Dept) -> Dibayar.
    // Tahap Endorsement & Final hanya ditampilkan utk Payment Request lama (alur 3 tahap).
    $prf_legacy_3stage = !empty($prf->endorsed_at) || in_array($prf->status, ['Approved Head Dept', 'Head Approved', 'Endorsed'], true);
    $prf_paid = ($prf->payment_status ?? '') === 'Paid' || $prf->status === 'Paid';
    $stage_steps = [
        ['key'=>'submitted',   'label'=>lang_line('prf_stage_submitted',   'Submitted'),      'by_field'=>'submitted_by',      'at_field'=>'submitted_at',      'log_level'=>'Submit',         'reached'=>!empty($prf->submitted_at) || !in_array($prf->status, ['Draft'])],
        ['key'=>'hod',         'label'=>lang_line($prf_legacy_3stage ? 'prf_stage_hod' : 'prf_stage_approved_hod', $prf_legacy_3stage ? 'HOD Approval' : 'Disetujui (Head of Dept)'), 'by_field'=>'hod_approved_by', 'at_field'=>'hod_approved_at', 'log_level'=>'Head of Dept', 'reached'=>!empty($prf->hod_approved_at)],
    ];
    if ($prf_legacy_3stage) {
        $stage_steps[] = ['key'=>'endorsement', 'label'=>lang_line('prf_stage_endorsement', 'Endorsement'),    'by_field'=>'endorsed_by',       'at_field'=>'endorsed_at',       'log_level'=>'Endorsement',    'reached'=>!empty($prf->endorsed_at)];
        $stage_steps[] = ['key'=>'final',       'label'=>lang_line('prf_stage_final',       'Final Approval'), 'by_field'=>'final_approved_by', 'at_field'=>'final_approved_at', 'log_level'=>'Final Approval', 'reached'=>!empty($prf->final_approved_at) || $prf->status === 'Approved' || $prf_paid];
    }
    // Dibayar: terisi otomatis dari Record Payment terakhir.
    $stage_steps[] = ['key'=>'paid', 'label'=>lang_line('prf_stage_paid', 'Dibayar'), 'by_name'=>$payment_signer->fullname ?? null, 'at'=>$payment_signer->payment_date ?? null, 'date_only'=>true, 'log_level'=>'', 'reached'=>$prf_paid || !empty($payment_signer)];

    // Map approval_logs by level so each stage card can show its catatan
    // (keep the *latest* log per level if there are multiple for the same stage).
    $log_by_level = [];
    if (!empty($approval_logs)) {
        foreach ($approval_logs as $lg) {
            $log_by_level[$lg->approval_level] = $lg;
        }
    }
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-1"><?php echo htmlspecialchars($prf->prf_number); ?></h1>
            <p class="text-muted"><?= lang_line('prf_lbl_status', 'Status'); ?>: <strong><?php echo $prf->status; ?></strong></p>
        </div>
        <div class="col-md-4 text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('prf_export_pdf', 'Export PDF'); ?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=L'); ?>">A4 Landscape</a>
                    <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=P'); ?>">A4 Portrait</a>
                    <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=L'); ?>">A5 Landscape</a>
                    <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=P'); ?>">A5 Portrait</a>
                </div>
            </div>
            <a href="<?php echo base_url('prf/export_excel/' . $prf->id); ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> <?= lang_line('prf_export_excel', 'Export Excel'); ?>
            </a>
            <a href="<?php echo base_url(!empty($back_url) ? $back_url : 'prf/list_all'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('prf_back', 'Kembali'); ?>
            </a>
            <?php // "PRF Selanjutnya" (ERP Revision Notes, [[erp_revision_prf_erf]]):
                  // selalu muncul selama masih ada PRF lain yang boleh dilihat
                  // user ini. Kalau dibuka dari antrian approval/payment,
                  // "next"-nya ikut urutan antrian itu (?queue= dibawa terus
                  // biar rantainya lanjut); kalau tidak, fallback next-by-id biasa.
                  // TIDAK floating (user klarifikasi 2026-09-03: HANYA Kembali
                  // yang floating & fungsinya balik ke menu list — Selanjutnya
                  // dibiarkan tombol biasa di toolbar). ?>
            <?php // Pasangan mundurnya (user 2026-09-16). "Sebelumnya" = PRF yang
                  // lebih baru (id lebih besar) kalau tanpa antrian, atau baris
                  // sebelum ini kalau dibuka dari antrian approval/payment. ?>
            <?php if (!empty($prev_prf_id)): ?>
                <a href="<?php echo base_url('prf/detail/' . $prev_prf_id) . (!empty($queue) ? '?queue=' . $queue : ''); ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> <?= lang_line('prf_prev_in_queue', 'Payment Request Sebelumnya'); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($next_prf_id)): ?>
                <a href="<?php echo base_url('prf/detail/' . $next_prf_id) . (!empty($queue) ? '?queue=' . $queue : ''); ?>" class="btn btn-primary">
                    <?= lang_line('prf_next_in_queue', 'Payment Request Selanjutnya'); ?> <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>?admin_edit=1"
                   class="btn btn-outline-warning btn-confirm no-loader mt-2 mt-md-0"
                   data-message="<?= htmlspecialchars(lang_line('prf_admin_unlock_confirm_title', 'Buka kunci edit Payment Request ini?')); ?>"
                   data-text="<?= htmlspecialchars(sprintf(lang_line('prf_admin_unlock_confirm_text', 'Payment Request sudah %s. Membuka kunci memungkinkan Anda memperbaiki item, nominal, tarik dari PO, dan lampiran sebagai Admin. Approval TIDAK direset.'), $prf->status)); ?>"
                   data-icon="warning"
                   data-confirm-text="<?= htmlspecialchars(lang_line('prf_admin_unlock_confirm_button', 'Ya, Buka Kunci')); ?>">
                    <i class="fas fa-lock"></i> <?= lang_line('prf_admin_unlock_button', 'Buka Kunci Edit (Admin)'); ?>
                </a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>" class="btn btn-warning no-loader mt-2 mt-md-0">
                    <i class="fas fa-lock-open"></i> <?= lang_line('prf_admin_lock_button', 'Selesai Edit (Kunci Lagi)'); ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($admin_edit_blocker)): ?>
                <small class="d-block text-muted mt-2">
                    <i class="fas fa-lock"></i> <?= htmlspecialchars($admin_edit_blocker); ?>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if (!empty($admin_edit_mode)): ?>
    <div class="alert alert-warning border-left-warning" role="alert">
        <h6 class="font-weight-bold mb-1"><i class="fas fa-user-shield mr-1"></i> <?= lang_line('prf_admin_banner_title', 'Mode Edit Admin'); ?></h6>
        <p class="mb-0 small">
            <?= sprintf(
                lang_line('prf_admin_banner_text', 'Payment Request ini berstatus <strong>%1$s</strong> dan normalnya terkunci. Sebagai Admin Anda bisa memperbaiki item/nominal (termasuk tarik dari PO) dan lampirannya, dan <strong>approval tidak direset</strong> &mdash; Payment Request tetap %2$s tanpa perlu submit &amp; approve ulang. Semua perubahan tercatat di Log Activity.'),
                htmlspecialchars($prf->status),
                htmlspecialchars($prf->status)
            ); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Header Info -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="m-0"><?= lang_line('prf_section_info', 'Informasi Payment Request'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_th_no', 'No. Payment Request'); ?></small>
                    <strong><?php echo htmlspecialchars($prf->prf_number); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_date', 'Tanggal Payment Request'); ?></small>
                    <strong><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_requester', 'Requester'); ?></small>
                    <strong><?php echo htmlspecialchars($prf->requester_name); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_status', 'Status'); ?></small>
                    <span class="badge badge-<?php echo $prf->status === 'Paid' ? 'success' : 'info'; ?> badge-lg">
                        <?php echo $prf->status; ?>
                    </span>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?></small>
                    <?php if (!empty($prf->due_date)):
                        $is_overdue = $prf->payment_status !== 'Paid' && strtotime($prf->due_date) < strtotime(date('Y-m-d')); ?>
                        <strong class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                            <?php echo date('d-m-Y', strtotime($prf->due_date)); ?>
                            <?php if ($is_overdue): ?><i class="fas fa-exclamation-triangle" title="<?= lang_line('prf_overdue', 'Sudah lewat jatuh tempo'); ?>"></i><?php endif; ?>
                        </strong>
                    <?php else: ?>
                        <strong class="text-muted">-</strong>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_supplier', 'Supplier / Vendor'); ?></small>
                    <strong><?php echo htmlspecialchars($prf->supplier_name ?? '-'); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_bank_name', 'Nama Bank'); ?></small>
                    <strong><?php echo htmlspecialchars($prf->bank_name ?: '-'); ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_bank_account', 'No. Rekening'); ?></small>
                    <strong><?php echo htmlspecialchars($prf->bank_account ?: '-'); ?></strong>
                </div>
            </div>
            <?php if($prf->notes): ?>
                <hr class="my-3">
                <div>
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_notes', 'Catatan'); ?></small>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($prf->notes)); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Items -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="m-0"><?= lang_line('prf_section_items', 'Item Payment Request'); ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= lang_line('prf_th_item_no', 'No'); ?></th>
                            <th><?= lang_line('prf_th_desc', 'Deskripsi'); ?></th>
                            <th class="text-right" style="width: 12%;"><?= lang_line('prf_th_qty', 'Qty'); ?></th>
                            <th class="text-center" style="width: 10%;"><?= lang_line('prf_th_unit', 'Unit'); ?></th>
                            <th class="text-right" style="width: 18%;"><?= lang_line('prf_th_unit_price', 'Harga Satuan'); ?></th>
                            <th class="text-right" style="width: 18%;"><?= lang_line('prf_th_subtotal', 'Subtotal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach($details as $detail): $is_ppn_line = ($detail->line_type ?? 'item') === 'ppn'; ?>
                            <tr class="<?= $is_ppn_line ? 'table-light' : ''; ?>">
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($detail->description); ?>
                                    <?php if ($is_ppn_line): ?>
                                        <br><small class="text-muted"><i class="fas fa-percent"></i> <?= lang_line('prf_hint_ppn_auto', 'PPN otomatis dari PO'); ?></small>
                                    <?php elseif (!empty($detail->source_po_number)): ?>
                                        <br><small class="text-muted"><i class="fas fa-file-invoice"></i> PO: <?php echo htmlspecialchars($detail->source_po_number); ?></small>
                                    <?php elseif (!empty($detail->source_ap_number)): ?>
                                        <br><small class="text-muted"><i class="fas fa-file-invoice-dollar"></i> AP Invoice: <?php echo htmlspecialchars($detail->source_ap_number); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><?php echo number_format($detail->qty, 2, ',', '.'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($detail->unit ?? '') ?: '-'; ?></td>
                                <td class="text-right">Rp <?php echo number_format($detail->unit_price, 2, ',', '.'); ?></td>
                                <td class="text-right">Rp <?php echo number_format($detail->subtotal, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light font-weight-bold">
                            <td colspan="5" class="text-right"><?= lang_line('prf_total', 'TOTAL'); ?>:</td>
                            <td class="text-right">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($is_hutang)): ?>
    <!-- Hutang & jurnal otomatis (Payment Request dari PO / AP Invoice, 2026-11-06) -->
    <div class="card mb-4 border-info">
        <div class="card-header d-flex justify-content-between align-items-center" style="background:#eef7fb">
            <h5 class="m-0"><i class="fas fa-book"></i> <?= lang_line('prf_acct_title', 'Hutang & Jurnal Otomatis'); ?></h5>
            <span class="badge badge-info"><?= lang_line('prf_badge_hutang', 'Payment Request Hutang'); ?></span>
        </div>
        <div class="card-body">
            <?php $acct_ap = $acct_links['ap'] ?? []; $acct_pay = $acct_links['payments'] ?? []; ?>
            <?php if (empty($acct_ap) && empty($acct_pay)): ?>
                <p class="mb-0 text-muted small"><i class="fas fa-info-circle"></i>
                    <?= lang_line('prf_acct_pending', 'Belum ada jurnal. Saat Payment Request ini di-approve, sistem otomatis mencatat AP Invoice + jurnal hutang untuk baris PO (Dr Persediaan/Beban/Aset sesuai pemetaan akun Accounting + PPN Masukan, Kr Utang Usaha). Saat Record Payment, jurnal pembayaran dibuat otomatis (Dr Utang Usaha, Kr Kas/Bank).'); ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr>
                            <th><?= lang_line('prf_acct_th_doc', 'Dokumen'); ?></th>
                            <th><?= lang_line('prf_acct_th_info', 'Keterangan'); ?></th>
                            <th class="text-right"><?= lang_line('prf_acct_th_amount', 'Nilai'); ?></th>
                            <th><?= lang_line('prf_acct_th_journal', 'Jurnal'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($acct_ap as $ap): $is_own = (int) $ap->prf_header_id === (int) $prf->id; ?>
                            <tr class="<?= $ap->status === 'Cancelled' ? 'text-muted' : ''; ?>">
                                <td><a href="<?= base_url('apinvoice/view/' . $ap->id); ?>" target="_blank"><i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($ap->invoice_no); ?></a></td>
                                <td>
                                    <?= $is_own ? lang_line('prf_acct_ap_auto', 'AP Invoice dibuat otomatis saat approve') : lang_line('prf_acct_ap_pulled', 'AP Invoice ditarik (jurnal hutang sudah ada sebelumnya)'); ?>
                                    <span class="badge badge-<?= $ap->status === 'Cancelled' ? 'secondary' : ($ap->payment_status === 'Paid' ? 'success' : 'warning'); ?>"><?= htmlspecialchars($ap->status === 'Cancelled' ? 'Cancelled' : $ap->payment_status); ?></span>
                                </td>
                                <td class="text-right">Rp <?= number_format((float) $ap->total_invoice, 2, ',', '.'); ?></td>
                                <td>
                                    <?php if (!empty($ap->journal_no)): ?><a href="<?= base_url('jurnalumum/view/' . $ap->journal_id); ?>" target="_blank"><?= htmlspecialchars($ap->journal_no); ?></a><?php else: ?>-<?php endif; ?>
                                    <?php if (!empty($ap->reversal_no)): ?><br><small><?= lang_line('prf_acct_reversed', 'Dibalik'); ?>: <a href="<?= base_url('jurnalumum/view/' . $ap->reversal_id); ?>" target="_blank"><?= htmlspecialchars($ap->reversal_no); ?></a></small><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($acct_pay as $pp): if (empty($pp->journal_no)) continue; ?>
                            <tr>
                                <td><a href="<?= base_url('appayment/view/' . $pp->ap_payment_id); ?>" target="_blank"><i class="fas fa-money-check-alt"></i> <?= htmlspecialchars($pp->payment_no); ?></a></td>
                                <td>
                                    <?= lang_line('prf_acct_payment', 'Pembayaran'); ?> <?= date('d-m-Y', strtotime($pp->payment_date)); ?>
                                    — <?= htmlspecialchars(trim(($pp->account_code ?? '') . ' ' . ($pp->account_name ?? ''))); ?>
                                    <?php if ((float) $pp->pph_amount > 0): ?><br><small>PPh 23: Rp <?= number_format((float) $pp->pph_amount, 2, ',', '.'); ?></small><?php endif; ?>
                                    <?php if ((float) $pp->transfer_fee > 0): ?><br><small><?= lang_line('prf_transfer_fee', 'Biaya Transfer Bank'); ?>: Rp <?= number_format((float) $pp->transfer_fee, 2, ',', '.'); ?></small><?php endif; ?>
                                </td>
                                <td class="text-right">Rp <?= number_format((float) $pp->payment_amount, 2, ',', '.'); ?></td>
                                <td><a href="<?= base_url('jurnalumum/view/' . $pp->journal_id); ?>" target="_blank"><?= htmlspecialchars($pp->journal_no); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Approval Trail (horizontal stepper) -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="m-0"><i class="fas fa-route mr-1"></i> <?= lang_line('prf_approval_title', 'Approval Workflow'); ?></h5>
        </div>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-around align-items-start">
                <?php foreach ($stage_steps as $idx => $step):
                    $reached = !empty($step['reached']);
                    $by_id   = isset($step['by_field']) ? ($prf->{$step['by_field']} ?? null) : null;
                    $at_raw  = array_key_exists('at', $step) ? $step['at'] : ($prf->{$step['at_field']} ?? null);
                    $by_name = $step['by_name'] ?? (($by_id && !empty($audit_users[(int)$by_id])) ? $audit_users[(int)$by_id] : null);
                    $log     = $log_by_level[$step['log_level']] ?? null;
                    $note    = ($log && !empty($log->notes)) ? trim($log->notes) : '';
                ?>
                    <div class="text-center flex-grow-1 px-2" style="min-width:130px;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                             style="width:42px;height:42px;background:<?= $reached ? '#1cc88a' : '#e3e6f0'; ?>;color:<?= $reached ? '#fff' : '#858796'; ?>;font-weight:bold;">
                            <i class="fas fa-<?= $reached ? 'check' : ($idx + 1); ?>"></i>
                        </div>
                        <div class="small font-weight-bold <?= $reached ? 'text-success' : 'text-muted'; ?>"><?= htmlspecialchars($step['label']); ?></div>
                        <?php if ($reached && $by_name): ?>
                            <div class="small text-muted"><?= htmlspecialchars($by_name); ?></div>
                            <div class="small text-muted"><?= $at_raw ? date(!empty($step['date_only']) ? 'd M Y' : 'd M Y H:i', strtotime($at_raw)) : ''; ?></div>
                        <?php elseif ($reached): ?>
                            <div class="small text-muted">&mdash;</div>
                        <?php endif; ?>
                        <?php if ($note !== ''): ?>
                            <div class="mt-2 px-2 py-1 small text-left"
                                 style="background:#f8f9fc;border-left:3px solid #1cc88a;border-radius:3px;color:#555;font-style:italic;word-break:break-word;">
                                <i class="fas fa-comment-dots text-muted mr-1"></i><?= nl2br(htmlspecialchars($note)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($idx < count($stage_steps) - 1): ?>
                        <div class="align-self-center text-muted d-none d-md-block" style="margin-top:14px;"><i class="fas fa-arrow-right"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Approval Actions: 1 tahap (HOD) sejak 2026-11-06; Endorsement/Final hanya utk PRF lama -->
            <?php if($can_approve_hod): ?>
                <hr class="my-4">
                <h6><i class="fas fa-user-tie text-primary mr-1"></i> <?= lang_line('prf_stage_single', 'Approval Head of Dept (1 tahap, langsung final)'); ?></h6>
                <form method="POST" action="<?php echo base_url('prf/approve_hod/' . $prf->id); ?>" class="form-inline">
                    <div class="form-group flex-grow-1 mr-2">
                        <textarea name="notes" class="form-control w-100" rows="2" placeholder="<?= lang_line('prf_ph_notes_optional', 'Catatan (opsional)'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" onclick="return confirm('<?= lang_line('prf_confirm_approve_single', 'Setujui Payment Request ini? Setelah disetujui langsung masuk antrian Pembayaran.'); ?>')">
                        <i class="fas fa-check"></i> <?= lang_line('prf_btn_approve_single', 'Approve'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if($can_approve_endorsement): ?>
                <hr class="my-4">
                <h6><i class="fas fa-stamp text-info mr-1"></i> <?= lang_line('prf_stage_2', 'Stage 2: Endorsement'); ?></h6>
                <form method="POST" action="<?php echo base_url('prf/approve_endorsement/' . $prf->id); ?>" class="form-inline">
                    <div class="form-group flex-grow-1 mr-2">
                        <textarea name="notes" class="form-control w-100" rows="2" placeholder="<?= lang_line('prf_ph_notes_optional', 'Catatan (opsional)'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" onclick="return confirm('<?= lang_line('prf_confirm_endorse', 'Endorse Payment Request ini?'); ?>')">
                        <i class="fas fa-check"></i> <?= lang_line('prf_btn_endorse', 'Endorse'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if($can_approve_final): ?>
                <hr class="my-4">
                <h6><i class="fas fa-check-double text-success mr-1"></i> <?= lang_line('prf_stage_3', 'Stage 3: Final Approval'); ?></h6>
                <form method="POST" action="<?php echo base_url('prf/approve_final/' . $prf->id); ?>" class="form-inline">
                    <div class="form-group flex-grow-1 mr-2">
                        <textarea name="notes" class="form-control w-100" rows="2" placeholder="<?= lang_line('prf_ph_notes_optional', 'Catatan (opsional)'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" onclick="return confirm('<?= lang_line('prf_confirm_final', 'Approve final Payment Request ini? Setelah ini akan masuk ke queue Pembayaran.'); ?>')">
                        <i class="fas fa-check-double"></i> <?= lang_line('prf_btn_approve_final', 'Approve Final'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if($can_return_reject): ?>
                <hr class="my-4">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-undo text-warning mr-1"></i> <?= lang_line('prf_return_section', 'Return to Draft'); ?></h6>
                        <form method="POST" action="<?php echo base_url('prf/return_to_draft/' . $prf->id); ?>">
                            <div class="form-group">
                                <textarea name="return_reason" class="form-control" rows="2" placeholder="<?= lang_line('prf_return_placeholder', 'Alasan return (wajib)'); ?>" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('<?= lang_line('prf_confirm_return', 'Kembalikan Payment Request ke Draft? Creator dapat merevisi & submit ulang.'); ?>')">
                                <i class="fas fa-undo"></i> <?= lang_line('prf_return_section', 'Return to Draft'); ?>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-times-circle text-danger mr-1"></i> <?= lang_line('prf_reject_section', 'Reject'); ?></h6>
                        <form method="POST" action="<?php echo base_url('prf/reject/' . $prf->id); ?>">
                            <div class="form-group">
                                <textarea name="reject_reason" class="form-control" rows="2" placeholder="<?= lang_line('prf_reject_placeholder', 'Alasan reject (wajib)'); ?>" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?= lang_line('prf_confirm_reject', 'Reject Payment Request ini? Tindakan ini final dan tidak dapat dibatalkan.'); ?>')">
                                <i class="fas fa-times"></i> <?= lang_line('prf_reject_section', 'Reject'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($prf->reject_reason) && $prf->status === 'Rejected'): ?>
                <hr class="my-4">
                <div class="alert alert-danger mb-0">
                    <strong><i class="fas fa-times-circle"></i> <?= lang_line('prf_alert_rejected', 'REJECTED'); ?>:</strong>
                    <?= nl2br(htmlspecialchars($prf->reject_reason)); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($prf->return_reason) && $prf->status === 'Draft' && !empty($prf->returned_to_draft_at)): ?>
                <hr class="my-4">
                <div class="alert alert-warning mb-0">
                    <strong><i class="fas fa-undo"></i> <?= lang_line('prf_alert_returned', 'RETURNED TO DRAFT'); ?>:</strong>
                    <?= nl2br(htmlspecialchars($prf->return_reason)); ?>
                    <small class="d-block mt-1">
                        <?= date('d-m-Y H:i', strtotime($prf->returned_to_draft_at)); ?>
                        <?php if (!empty($audit_users[(int)$prf->returned_to_draft_by])): ?>
                            <?= lang_line('prf_by', 'oleh'); ?> <?= htmlspecialchars($audit_users[(int)$prf->returned_to_draft_by]); ?>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>

            <!-- Riwayat & Balasan (Back to Draft 2-arah, ERP Revision Notes
                 2026-09-02 Fase 2 poin 11b) — requestor & approver bolak-balik
                 selagi status Draft. Beda dari widget "Approval Workflow" di
                 atas (itu cuma catatan TERAKHIR per stage), ini riwayat PENUH
                 kronologis termasuk semua balasan. -->
            <?php if (!empty($approval_logs)): ?>
            <hr class="my-4">
            <div id="riwayat-approval">
                <h6 class="mb-3"><i class="fas fa-history mr-1"></i> <?= lang_line('prf_history_title', 'Riwayat & Balasan'); ?></h6>
                <?php
                    $prf_log_style = [
                        'Submitted' => ['icon' => 'paper-plane', 'color' => 'info'],
                        'Approved'  => ['icon' => 'check',       'color' => 'success'],
                        'Returned'  => ['icon' => 'undo',        'color' => 'warning'],
                        'Rejected'  => ['icon' => 'times',       'color' => 'danger'],
                        'Reply'     => ['icon' => 'reply',       'color' => 'secondary'],
                    ];
                ?>
                <ul class="list-unstyled mb-3">
                    <?php foreach ($approval_logs as $log):
                        $s = $prf_log_style[$log->status] ?? ['icon' => 'circle', 'color' => 'secondary'];
                        $is_reply = ($log->status === 'Reply');
                        $reply_from_requestor = $is_reply && ((int)($log->approver_id ?? 0) === (int)$prf->requester_id);
                    ?>
                    <?php if ($is_reply): ?>
                    <li class="d-flex mb-3 <?= $reply_from_requestor ? 'flex-row-reverse text-right' : ''; ?>">
                        <div class="<?= $reply_from_requestor ? 'ml-2' : 'mr-2'; ?>" style="max-width:75%;">
                            <div class="px-3 py-2" style="background:<?= $reply_from_requestor ? '#e7f1ff' : '#f1f1f1'; ?>;border-radius:14px;<?= $reply_from_requestor ? 'border-top-right-radius:2px;' : 'border-top-left-radius:2px;'; ?>">
                                <div class="small font-weight-bold text-gray-800"><?= htmlspecialchars($log->approver_name ?: '-'); ?> <span class="font-weight-normal text-muted">— <?= htmlspecialchars($reply_from_requestor ? 'Requestor' : 'Approver'); ?></span></div>
                                <div class="small" style="word-break:break-word;"><?= nl2br(htmlspecialchars($log->notes ?? '')); ?></div>
                            </div>
                            <div class="small text-muted mt-1"><?= $log->created_at ? date('d M Y H:i', strtotime($log->created_at)) : ''; ?></div>
                        </div>
                    </li>
                    <?php else: ?>
                    <li class="d-flex mb-3">
                        <div class="mr-3 text-center" style="width:34px;">
                            <span class="badge badge-<?= $s['color']; ?> d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:50%;">
                                <i class="fas fa-<?= $s['icon']; ?>"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold text-<?= $s['color']; ?>"><?= htmlspecialchars($log->status); ?> <span class="text-muted font-weight-normal">(<?= htmlspecialchars($log->approval_level); ?>)</span></div>
                            <div class="small text-gray-800"><?= htmlspecialchars($log->approver_name ?: '-'); ?></div>
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
                <form method="POST" action="<?= base_url('prf/reply/' . $prf->id); ?>" class="pt-2 border-top">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold mb-1"><?= lang_line('prf_reply_label', 'Balas'); ?></label>
                        <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="<?= lang_line('prf_reply_placeholder', 'Tulis balasan/klarifikasi...'); ?>" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> <?= lang_line('prf_reply_send', 'Kirim Balasan'); ?></button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($is_admin) && (!empty($can_admin_return) || !empty($can_admin_delete))): ?>
                <hr class="my-4">
                <div class="border border-danger rounded p-3" style="background:#fff5f5;">
                    <h6 class="text-danger mb-2"><i class="fas fa-user-shield mr-1"></i> Admin Override</h6>
                    <p class="small text-muted mb-3">
                        Tombol di bawah ini bypass aturan status — hanya untuk Payment Request di tahap approval
                        (Submitted s/d Approved) atau terminal (Rejected/Cancelled).
                        Payment Request yang sudah Paid/Partial diblok otomatis supaya jurnal pembayaran aman.
                        Force Return reset semua approval (list item tetap).
                        Force Delete menghapus Payment Request beserta details, attachments, &amp; approval logs.
                    </p>
                    <div class="row">
                        <?php if (!empty($can_admin_return)): ?>
                            <div class="col-md-7">
                                <form method="POST" action="<?php echo base_url('prf/return_to_draft/' . $prf->id); ?>">
                                    <div class="form-group mb-2">
                                        <textarea name="return_reason" class="form-control form-control-sm" rows="2" placeholder="Alasan force return (wajib)" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-outline-warning btn-sm"
                                            onclick="return confirm('Force return Payment Request ke Draft? Semua approval & payment akan direset.');">
                                        <i class="fas fa-undo"></i> Force Return to Draft
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($can_admin_delete)): ?>
                            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                <form method="POST" action="<?php echo base_url('prf/delete/' . $prf->id); ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Hapus Payment Request ini permanen? Termasuk details, attachments, & payment tracking. Tindakan tidak bisa dibatalkan.');">
                                        <i class="fas fa-trash"></i> Force Delete Payment Request
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Tracking -->
    <?php
        $paid_total = 0;
        $fee_total = 0;
        if (!empty($payment_logs)) {
            foreach ($payment_logs as $pl) {
                // PPh 23 yang dipotong saat bayar ikut menutup tagihan (PRF hutang).
                $paid_total += (float)$pl->payment_amount + (float)($pl->pph_amount ?? 0);
                $fee_total  += (float)($pl->transfer_fee ?? 0);
            }
        }
        $outstanding = max(0, (float)$prf->subtotal - $paid_total);
    ?>
    <div class="card mb-4 border-<?php echo $prf->payment_status === 'Paid' ? 'success' : 'warning'; ?>">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: <?php echo $prf->payment_status === 'Paid' ? '#e8f5e9' : '#fff8e1'; ?>;">
            <h5 class="m-0"><i class="fas fa-wallet"></i> <?= lang_line('prf_payment_section', 'Pembayaran'); ?></h5>
            <?php if($can_record_payment): ?>
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#recordPaymentModal">
                    <i class="fas fa-money-bill-wave"></i> <?= lang_line('prf_btn_record_payment', 'Catat Pembayaran'); ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_total_billed', 'Total Tagihan'); ?></small>
                    <h5 class="mb-0">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></h5>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_paid', 'Dibayar'); ?></small>
                    <h5 class="mb-0 text-success">Rp <?php echo number_format($paid_total, 2, ',', '.'); ?></h5>
                    <?php if ($fee_total > 0): ?>
                        <small class="d-block text-muted">
                            + <?= lang_line('prf_th_transfer_fee', 'Biaya Transfer'); ?> Rp <?php echo number_format($fee_total, 2, ',', '.'); ?>
                            = <strong>Rp <?php echo number_format($paid_total + $fee_total, 2, ',', '.'); ?></strong>
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_outstanding', 'Outstanding'); ?></small>
                    <h5 class="mb-0 text-<?php echo $outstanding > 0 ? 'danger' : 'muted'; ?>">Rp <?php echo number_format($outstanding, 2, ',', '.'); ?></h5>
                </div>
                <div class="col-md-3">
                    <small class="d-block text-muted"><?= lang_line('prf_lbl_status', 'Status'); ?></small>
                    <span class="badge badge-<?php echo $prf->payment_status === 'Paid' ? 'success' : 'warning'; ?>" style="font-size: 14px; padding: 6px 12px;">
                        <?php echo $prf->payment_status; ?>
                    </span>
                    <?php if($prf->payment_date): ?>
                        <small class="d-block text-muted mt-1"><?= lang_line('prf_pay_date_short', 'Tgl'); ?>: <?php echo date('d-m-Y', strtotime($prf->payment_date)); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ((float)$prf->subtotal > 0): ?>
                <?php $pct = min(100, ($paid_total / (float)$prf->subtotal) * 100); ?>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-<?php echo $pct >= 100 ? 'success' : 'warning'; ?>" role="progressbar"
                         style="width: <?php echo $pct; ?>%"></div>
                </div>
            <?php endif; ?>

            <?php
                // Label bukti per slot: slot 1 = bukti bayar, slot 2 = bukti PPh 23.
                $proof_labels = [
                    1 => lang_line('prf_proof_payment', 'Bukti Bayar'),
                    2 => lang_line('prf_proof_pph23', 'Bukti PPh 23'),
                ];
            ?>
            <?php if(!empty($payment_logs)): ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('prf_th_date2', 'Tanggal'); ?></th>
                                <th><?= lang_line('prf_th_amount2', 'Jumlah'); ?></th>
                                <th><?= lang_line('prf_th_transfer_fee', 'Biaya Transfer'); ?></th>
                                <th><?= lang_line('prf_th_method', 'Metode'); ?></th>
                                <th><?= lang_line('prf_th_reference', 'Referensi'); ?></th>
                                <th><?= lang_line('prf_th_notes2', 'Catatan'); ?></th>
                                <th><?= lang_line('prf_th_proof', 'Bukti'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payment_logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($log->payment_date)); ?></td>
                                    <td>Rp <?php echo number_format($log->payment_amount, 2, ',', '.'); ?></td>
                                    <td><?php echo (float)($log->transfer_fee ?? 0) > 0 ? 'Rp ' . number_format($log->transfer_fee, 2, ',', '.') : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo htmlspecialchars($log->payment_method ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log->reference_number ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($log->notes ?? '-'); ?></td>
                                    <td>
                                        <?php
                                            $slot_paths = [
                                                1 => $log->payment_proof_path ?? null,
                                                2 => $log->payment_proof_path_2 ?? null,
                                            ];
                                            $has_any = !empty($slot_paths[1]) || !empty($slot_paths[2]);
                                        ?>
                                        <?php if (!$has_any && empty($can_upload_payment_proof)): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <?php foreach ($slot_paths as $slot => $spath): ?>
                                                <?php if (!empty($spath)): ?>
                                                    <span class="text-nowrap d-inline-block mb-1">
                                                        <a href="<?php echo base_url('prf/payment_proof/' . $log->id . '/' . $slot); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="<?= lang_line('prf_view_proof', 'Lihat Bukti'); ?>">
                                                            <i class="fas fa-file-invoice"></i> <?php echo $proof_labels[$slot]; ?>
                                                        </a>
                                                        <?php if (!empty($can_upload_payment_proof)): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#uploadProofModal" data-payment-id="<?php echo $log->id; ?>" data-slot="<?php echo $slot; ?>" data-label="<?php echo htmlspecialchars($proof_labels[$slot]); ?>" title="<?= lang_line('prf_replace_proof', 'Ganti Bukti'); ?>">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php elseif (!empty($can_upload_payment_proof)): ?>
                                                    <button type="button" class="btn btn-sm btn-success mb-1" data-toggle="modal" data-target="#uploadProofModal" data-payment-id="<?php echo $log->id; ?>" data-slot="<?php echo $slot; ?>" data-label="<?php echo htmlspecialchars($proof_labels[$slot]); ?>">
                                                        <i class="fas fa-upload"></i> <?= lang_line('prf_btn_upload', 'Upload'); ?> <?php echo $proof_labels[$slot]; ?>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= lang_line('prf_no_payment2', 'Belum ada pembayaran'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attachments -->
    <div class="card mb-4" id="lampiran">
        <?php
            // Satu file upload = satu baris lampiran. PDF multi-halaman tetap
            // satu entri; jumlah halamannya ditampilkan sebagai keterangan.
            $att_items = [];
            foreach ((isset($attachments) ? $attachments : []) as $att) {
                $is_img = ($att->file_type === 'image');
                $parts  = [$is_img
                    ? lang_line('prf_attach_type_image', 'Gambar')
                    : lang_line('prf_attach_type_pdf', 'PDF')];
                $page_count = isset($att->page_count) ? (int) $att->page_count : 0;
                if (!$is_img && $page_count > 0) {
                    $parts[] = $page_count . ' ' . lang_line('prf_attach_pages', 'halaman');
                }
                $parts[] = number_format($att->file_size / 1024, 1) . ' KB';
                $parts[] = date('d M Y', strtotime($att->created_at));

                $att_items[] = [
                    'row'  => $att,
                    'img'  => $is_img,
                    'url'  => base_url('prf/attachment/' . $att->id),
                    'dl'   => base_url('prf/attachment/' . $att->id) . '?dl=1',
                    'del'  => base_url('prf/delete_attachment/' . $att->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''),
                    'name' => htmlspecialchars($att->original_name, ENT_QUOTES),
                    'meta' => implode(' · ', $parts),
                ];
            }
        ?>
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0"><i class="fas fa-paperclip"></i> <?= lang_line('prf_attachments', 'Lampiran Payment Request'); ?>
                <span class="badge badge-secondary"><?php echo count($att_items); ?></span>
            </h5>
            <div class="d-flex align-items-center">
                <?php if (!empty($att_items)): ?>
                    <div class="btn-group btn-group-sm mr-2" id="prfAttachViewToggle" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-view="list" title="<?= lang_line('prf_attach_view_list', 'Tampilan daftar'); ?>">
                            <i class="fas fa-list"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-view="grid" title="<?= lang_line('prf_attach_view_grid', 'Tampilan grid'); ?>">
                            <i class="fas fa-th-large"></i>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($can_upload_attachment)): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#uploadAttachmentModal">
                        <i class="fas fa-upload"></i> <?= lang_line('prf_btn_upload_attachment', 'Upload Lampiran'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($att_items)): ?>
                <p class="text-muted mb-0"><i class="fas fa-info-circle"></i> <?= lang_line('prf_no_attachment', 'Belum ada lampiran. Upload bukti tagihan / invoice / foto pendukung di sini. Saat export PDF, lampiran akan tampil di halaman setelahnya.'); ?></p>
            <?php else: ?>
                <!-- Tampilan daftar -->
                <div id="prfAttachList" class="prf-attach-list">
                    <?php foreach ($att_items as $it): ?>
                        <div class="prf-attach-item d-flex align-items-center">
                            <div class="prf-attach-icon mr-3">
                                <?php if ($it['img']): ?>
                                    <img src="<?php echo base_url($it['row']->file_path); ?>" alt="" />
                                <?php else: ?>
                                    <i class="fas fa-file-pdf text-danger"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 text-truncate">
                                <div class="prf-attach-name text-truncate" title="<?php echo $it['name']; ?>"><?php echo $it['name']; ?></div>
                                <small class="text-muted"><?php echo $it['meta']; ?></small>
                            </div>
                            <div class="prf-attach-actions ml-3">
                                <button type="button" class="btn btn-sm btn-info prf-attach-preview" data-url="<?php echo $it['url']; ?>" data-type="<?php echo $it['row']->file_type; ?>" data-name="<?php echo $it['name']; ?>" title="<?= lang_line('prf_preview', 'Preview'); ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="<?php echo $it['dl']; ?>" class="btn btn-sm btn-outline-primary" title="<?= lang_line('prf_btn_download', 'Download'); ?>">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if (!empty($can_delete_attachment)): ?>
                                    <a href="<?php echo $it['del']; ?>" class="btn btn-sm btn-danger" title="<?= lang_line('prf_btn_delete', 'Hapus'); ?>" onclick="return confirm('<?= lang_line('prf_confirm_delete_attachment', 'Hapus lampiran ini?'); ?>');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tampilan grid -->
                <div id="prfAttachGrid" class="row" style="display:none;">
                    <?php foreach ($att_items as $it): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100">
                                <div class="card-body p-2 text-center">
                                    <?php if ($it['img']): ?>
                                        <a href="javascript:void(0)" class="prf-attach-preview" data-url="<?php echo $it['url']; ?>" data-type="image" data-name="<?php echo $it['name']; ?>">
                                            <img src="<?php echo base_url($it['row']->file_path); ?>" style="max-height:120px;max-width:100%;object-fit:contain;" />
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" class="prf-attach-preview d-block py-3" data-url="<?php echo $it['url']; ?>" data-type="pdf" data-name="<?php echo $it['name']; ?>">
                                            <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                        </a>
                                    <?php endif; ?>
                                    <small class="d-block text-truncate mt-2" title="<?php echo $it['name']; ?>">
                                        <?php echo $it['name']; ?>
                                    </small>
                                    <small class="text-muted d-block"><?php echo $it['meta']; ?></small>
                                </div>
                                <div class="card-footer p-1 text-center bg-light">
                                    <button type="button" class="btn btn-sm btn-info prf-attach-preview" data-url="<?php echo $it['url']; ?>" data-type="<?php echo $it['row']->file_type; ?>" data-name="<?php echo $it['name']; ?>" title="<?= lang_line('prf_preview', 'Preview'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="<?php echo $it['dl']; ?>" class="btn btn-sm btn-outline-primary" title="<?= lang_line('prf_btn_download', 'Download'); ?>">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php if (!empty($can_delete_attachment)): ?>
                                        <a href="<?php echo $it['del']; ?>" class="btn btn-sm btn-danger" title="<?= lang_line('prf_btn_delete', 'Hapus'); ?>" onclick="return confirm('<?= lang_line('prf_confirm_delete_attachment', 'Hapus lampiran ini?'); ?>');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script>
                (function(){
                    var toggle = document.getElementById('prfAttachViewToggle');
                    var list   = document.getElementById('prfAttachList');
                    var grid   = document.getElementById('prfAttachGrid');
                    if (!toggle || !list || !grid) return;

                    var KEY = 'prf_attach_view';

                    function apply(view) {
                        var isGrid = (view === 'grid');
                        list.style.display = isGrid ? 'none' : '';
                        grid.style.display = isGrid ? '' : 'none';
                        toggle.querySelectorAll('button').forEach(function(b){
                            b.classList.toggle('active', b.getAttribute('data-view') === view);
                        });
                    }

                    try { apply(localStorage.getItem(KEY) === 'grid' ? 'grid' : 'list'); } catch (e) { apply('list'); }

                    toggle.addEventListener('click', function(e){
                        var btn = e.target.closest('button[data-view]');
                        if (!btn) return;
                        var view = btn.getAttribute('data-view');
                        apply(view);
                        try { localStorage.setItem(KEY, view); } catch (e2) {}
                    });
                })();
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bukti Pembayaran (terpisah dari Lampiran Payment Request) -->
    <?php
    $payment_proofs = [];
    if (!empty($payment_logs)) {
        foreach ($payment_logs as $log) {
            if (!empty($log->payment_proof_path)) {
                $payment_proofs[] = ['id' => $log->id, 'slot' => 1, 'path' => $log->payment_proof_path, 'date' => $log->payment_date];
            }
            if (!empty($log->payment_proof_path_2 ?? '')) {
                $payment_proofs[] = ['id' => $log->id, 'slot' => 2, 'path' => $log->payment_proof_path_2, 'date' => $log->payment_date];
            }
        }
    }
    ?>
    <div class="card mb-4" id="bukti-bayar">
        <div class="card-header d-flex justify-content-between align-items-center" style="background:#e8f5e9;">
            <h5 class="m-0"><i class="fas fa-receipt"></i> <?= lang_line('prf_payment_proofs', 'Bukti Pembayaran'); ?>
                <span class="badge badge-secondary"><?php echo count($payment_proofs); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($payment_proofs)): ?>
                <p class="text-muted mb-0"><i class="fas fa-info-circle"></i> <?= lang_line('prf_no_payment_proof', 'Belum ada bukti pembayaran. File akan tampil di sini setelah Finance mencatat pembayaran beserta bukti transfer/kwitansi.'); ?></p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($payment_proofs as $pp): ?>
                        <?php
                            $proof_url  = base_url('prf/payment_proof/' . $pp['id'] . '/' . $pp['slot']);
                            $proof_name = basename($pp['path']);
                            $proof_ext  = strtolower(pathinfo($pp['path'], PATHINFO_EXTENSION));
                            $is_image   = in_array($proof_ext, ['jpg', 'jpeg', 'png']);
                        ?>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body p-2 text-center">
                                    <span class="badge badge-success mb-1"><?php echo $proof_labels[$pp['slot']] ?? ('Bukti ' . $pp['slot']); ?></span>
                                    <?php if ($is_image): ?>
                                        <a href="<?php echo $proof_url; ?>" target="_blank">
                                            <img src="<?php echo base_url($pp['path']); ?>" style="max-height:120px;max-width:100%;object-fit:contain;" />
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo $proof_url; ?>" target="_blank" class="d-block py-3">
                                            <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                        </a>
                                    <?php endif; ?>
                                    <small class="d-block text-truncate mt-2" title="<?php echo htmlspecialchars($proof_name); ?>">
                                        <?php echo htmlspecialchars($proof_name); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <?= lang_line('prf_pay_date_short', 'Tgl'); ?>: <?php echo date('d-m-Y', strtotime($pp['date'])); ?>
                                    </small>
                                </div>
                                <div class="card-footer p-1 text-center bg-light">
                                    <a href="<?php echo $proof_url; ?>" target="_blank" class="btn btn-sm btn-info" title="<?= lang_line('prf_preview', 'Preview'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!empty($can_upload_payment_proof)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#uploadProofModal" data-payment-id="<?php echo $pp['id']; ?>" data-slot="<?php echo $pp['slot']; ?>" data-label="<?php echo htmlspecialchars($proof_labels[$pp['slot']] ?? ''); ?>" title="<?= lang_line('prf_replace_proof', 'Ganti Bukti'); ?>">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col">
            <?php if(!empty($can_edit_nominal)): ?>
                <a href="<?php echo base_url('prf/edit/' . $prf->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''); ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    <?= !empty($admin_edit_mode)
                        ? lang_line('prf_admin_edit_button', 'Edit Item / Nominal (Admin)')
                        : lang_line('prf_btn_edit', 'Edit Nominal/Payment Request'); ?>
                </a>
            <?php endif; ?>
            <?php if(!empty($can_edit_payment)): ?>
                <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#editPaymentModal">
                    <i class="fas fa-money-check-alt"></i> Edit Pembayaran
                </button>
            <?php endif; ?>
            <?php if($can_submit): ?>
                <?php if (empty($attachments)): ?>
                <button type="button" class="btn btn-secondary" disabled title="<?= lang_line('prf_need_attachment_title', 'Upload attachment bukti/dokumen pendukung dulu sebelum submit'); ?>">
                    <i class="fas fa-paper-plane"></i> <?= lang_line('prf_btn_submit_approval', 'Submit untuk Approval'); ?>
                </button>
                <small class="text-danger d-block mt-1"><?= lang_line('prf_need_attachment', 'Upload attachment bukti/dokumen pendukung dulu.'); ?></small>
                <?php else: ?>
                <a href="<?php echo base_url('prf/submit/' . $prf->id); ?>" class="btn btn-primary" onclick="return confirm('<?= lang_line('prf_confirm_submit', 'Submit Payment Request untuk approval?'); ?>')">
                    <i class="fas fa-paper-plane"></i> <?= lang_line('prf_btn_submit_approval', 'Submit untuk Approval'); ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
    $latest_payment = null;
    if (!empty($payment_logs) && is_array($payment_logs)) {
        $latest_payment = $payment_logs[0];
    }
?>

<!-- Edit Payment Modal -->
<?php if(!empty($can_edit_payment) && !empty($latest_payment)): ?>
    <div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="<?php echo base_url('prf/update_payment/' . $prf->id); ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" value="<?php echo (int) $latest_payment->id; ?>">
                        <div class="form-group">
                            <label>Tanggal Pembayaran *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($latest_payment->payment_date)); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Pembayaran *</label>
                            <input type="number" name="payment_amount" class="form-control" value="<?php echo htmlspecialchars((string) $latest_payment->payment_amount); ?>" step="0.01" required <?= !empty($is_hutang) ? 'readonly' : ''; ?>>
                            <?php if (!empty($is_hutang)): ?><small class="text-muted"><?= lang_line('prf_pay_locked_hutang', 'Nominal & biaya sudah dijurnal otomatis — dikunci. Tanggal boleh dikoreksi (tanggal jurnal ikut berubah).'); ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Biaya Transfer Bank <small class="text-muted">(opsional)</small></label>
                            <input type="number" name="transfer_fee" class="form-control" value="<?php echo htmlspecialchars((string) ($latest_payment->transfer_fee ?? 0)); ?>" step="0.01" min="0" <?= !empty($is_hutang) ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <select name="payment_method" class="form-control">
                                <option value="" <?php echo empty($latest_payment->payment_method) ? 'selected' : ''; ?>>-- Pilih --</option>
                                <option value="Cash" <?php echo ($latest_payment->payment_method === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="Transfer" <?php echo ($latest_payment->payment_method === 'Transfer') ? 'selected' : ''; ?>>Transfer Bank</option>
                                <option value="Check" <?php echo ($latest_payment->payment_method === 'Check') ? 'selected' : ''; ?>>Check</option>
                                <option value="Other" <?php echo ($latest_payment->payment_method === 'Other') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Referensi</label>
                            <input type="text" name="reference_number" class="form-control" value="<?php echo htmlspecialchars($latest_payment->reference_number ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($latest_payment->notes ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Bukti Bayar 1 <small class="text-muted">(opsional, PDF/JPG/PNG, maks 10 MB)</small></label>
                            <input type="file" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="form-group">
                            <label>Bukti Bayar 2 <small class="text-muted">(opsional, PDF/JPG/PNG, maks 10 MB)</small></label>
                            <input type="file" name="payment_proof_2" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batalkan</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Record Payment Modal -->
<?php if($can_record_payment): ?>
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang_line('prf_pay_modal_title', 'Record Pembayaran'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="<?php echo base_url('prf/record_payment/' . $prf->id); ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?php if (!empty($prf->due_date)):
                            $modal_overdue = strtotime($prf->due_date) < strtotime(date('Y-m-d')); ?>
                            <div class="alert <?php echo $modal_overdue ? 'alert-danger' : 'alert-info'; ?> py-2">
                                <i class="fas fa-calendar-day"></i>
                                <?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?>:
                                <strong><?php echo date('d-m-Y', strtotime($prf->due_date)); ?></strong>
                                <?php if ($modal_overdue): ?>
                                    <span class="badge badge-light text-danger ml-1"><?= lang_line('prf_overdue', 'Sudah lewat jatuh tempo'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label><?= lang_line('prf_pay_date', 'Tanggal Pembayaran'); ?> *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <?php if (!empty($is_hutang)): ?>
                        <div class="form-group">
                            <label><?= lang_line('prf_pay_cash_account', 'Dibayar dari Akun Kas/Bank'); ?> *</label>
                            <select name="cash_account_id" class="form-control" required>
                                <option value=""><?= lang_line('prf_choose_method', '-- Pilih --'); ?></option>
                                <?php foreach (($cash_accounts ?? []) as $ca): ?>
                                    <option value="<?= (int) $ca->id; ?>"><?= htmlspecialchars($ca->account_code . ' - ' . $ca->account_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($cash_accounts)): ?><small class="text-danger"><?= lang_line('prf_pay_no_cash_account', 'Belum ada akun Aset di COA. Minta Accounting mengimport COA dulu.'); ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_pay_pph23', 'PPh 23 Dipotong'); ?> <small class="text-muted">(<?= lang_line('prf_optional', 'opsional'); ?>)</small></label>
                            <input type="number" id="prfPph" name="pph_amount" class="form-control" value="0" step="0.01" min="0">
                            <small class="form-text text-muted"><?= lang_line('prf_pay_pph23_hint', 'Untuk jasa yang dipotong PPh 23: Jumlah Pembayaran otomatis = total tagihan − PPh.'); ?></small>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label><?= lang_line('prf_pay_amount', 'Jumlah Pembayaran'); ?> *</label>
                            <input type="number" id="prfPaymentAmount" name="payment_amount" class="form-control" value="<?php echo $prf->subtotal; ?>" step="0.01" required <?= !empty($is_hutang) ? 'readonly' : ''; ?>>
                            <?php if (!empty($is_hutang)): ?><small class="form-text text-muted"><?= lang_line('prf_pay_full_hutang', 'Payment Request hutang dibayar penuh sesuai tagihan. Untuk bayar sebagian, buat Payment Request dengan qty/nominal lebih kecil.'); ?></small><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_transfer_fee', 'Biaya Transfer Bank'); ?> <small class="text-muted">(<?= lang_line('prf_optional', 'opsional'); ?>)</small></label>
                            <input type="number" id="prfTransferFee" name="transfer_fee" class="form-control" value="0" step="0.01" min="0">
                            <small class="form-text text-muted">
                                <?= lang_line('prf_transfer_fee_hint', 'Biaya admin bank bila transfer beda bank. Dicatat terpisah dari jumlah pembayaran.'); ?>
                                <?= lang_line('prf_total_cash_out', 'Total kas keluar'); ?>: <strong id="prfTotalCashOut">Rp 0</strong>
                            </small>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_pay_method', 'Metode Pembayaran'); ?></label>
                            <select name="payment_method" class="form-control">
                                <option value=""><?= lang_line('prf_choose_method', '-- Pilih --'); ?></option>
                                <option value="Cash"><?= lang_line('prf_method_cash', 'Cash'); ?></option>
                                <option value="Transfer"><?= lang_line('prf_method_transfer', 'Transfer Bank'); ?></option>
                                <option value="Check"><?= lang_line('prf_method_check', 'Check'); ?></option>
                                <option value="Other"><?= lang_line('prf_method_other', 'Lainnya'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_reference_no', 'No. Referensi (No. Transfer/Check)'); ?></label>
                            <input type="text" name="reference_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_lbl_notes', 'Catatan'); ?></label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_proof_payment', 'Bukti Bayar'); ?> <small class="text-muted">(PDF/JPG/PNG, maks 10 MB)</small></label>
                            <input type="file" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('prf_proof_pph23', 'Bukti PPh 23'); ?> <small class="text-muted">(<?= lang_line('prf_optional', 'opsional'); ?>, PDF/JPG/PNG, maks 10 MB)</small></label>
                            <input type="file" name="payment_proof_2" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="alert alert-info py-2 mb-0">
                            <i class="fas fa-info-circle"></i>
                            <?php if (!empty($is_hutang)): ?>
                                <?= lang_line('prf_pay_journal_note_hutang', 'Jurnal pembayaran dibuat & diposting otomatis: Dr Utang Usaha, Kr Kas/Bank (+ Dr Biaya Admin Bank bila ada biaya transfer, + Kr Utang PPh 23 bila dipotong). Status AP Invoice ikut menjadi Paid.'); ?>
                            <?php else: ?>
                                <?= lang_line('prf_pay_journal_note', 'Pembayaran ini hanya dicatat (record). Posting debit/kredit dilakukan manual di menu Jurnal Umum.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?= lang_line('prf_btn_cancel', 'Batalkan'); ?></button>
                        <button type="submit" class="btn btn-primary"><?= lang_line('prf_btn_save_payment', 'Simpan Pembayaran'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var amt = document.getElementById('prfPaymentAmount');
        var fee = document.getElementById('prfTransferFee');
        var out = document.getElementById('prfTotalCashOut');
        if (!amt || !fee || !out) return;
        function fmt(n){ return 'Rp ' + (n).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
        function recalc(){
            var total = (parseFloat(amt.value) || 0) + (parseFloat(fee.value) || 0);
            out.textContent = fmt(total);
        }
        amt.addEventListener('input', recalc);
        fee.addEventListener('input', recalc);
        // PRF hutang: Jumlah Pembayaran = total tagihan - PPh 23.
        var pph = document.getElementById('prfPph');
        if (pph) {
            var tagihan = <?= json_encode((float) $prf->subtotal); ?>;
            pph.addEventListener('input', function(){ amt.value = Math.max(0, tagihan - (parseFloat(pph.value) || 0)).toFixed(2); recalc(); });
        }
        recalc();
    });
    </script>
<?php endif; ?>

<!-- Upload Payment Proof Modal (untuk attach/replace bukti ke payment_tracking existing) -->
<?php if (!empty($can_upload_payment_proof)): ?>
<div class="modal fade" id="uploadProofModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> <?= lang_line('prf_upload_proof_title', 'Upload Bukti'); ?> <span id="uploadProofSlotLabel"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="uploadProofForm" method="POST" enctype="multipart/form-data" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('prf_pay_proof', 'Bukti Bayar'); ?> <small class="text-muted">(PDF/JPG/PNG, maks 10 MB)</small></label>
                        <input type="file" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <small class="text-muted"><?= lang_line('prf_replace_proof_note', 'Bukti lama akan diganti dengan file ini.'); ?></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?= lang_line('prf_btn_cancel', 'Batalkan'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang_line('prf_btn_upload', 'Upload'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var proofModal = document.getElementById('uploadProofModal');
    if (!proofModal) return;
    var base = '<?php echo base_url('prf/upload_payment_proof/'); ?>';
    $(proofModal).on('show.bs.modal', function(event){
        var btn = event.relatedTarget;
        if (!btn) return;
        var pid = btn.getAttribute('data-payment-id');
        var slot = btn.getAttribute('data-slot') || '1';
        var label = btn.getAttribute('data-label') || ('Bukti ' + slot);
        document.getElementById('uploadProofForm').action = base + pid + '/' + slot;
        var slotLabel = document.getElementById('uploadProofSlotLabel');
        if (slotLabel) slotLabel.textContent = '— ' + label;
    });
});
</script>
<?php endif; ?>

<!-- Attachment Preview Modal -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paperclip"></i> <span id="attachPreviewName"><?= lang_line('prf_preview_attachment', 'Preview Lampiran'); ?></span></h5>
                <div>
                    <a id="attachPreviewDownload" href="#" class="btn btn-sm btn-outline-primary" target="_blank" download>
                        <i class="fas fa-download"></i> <?= lang_line('prf_btn_download', 'Download'); ?>
                    </a>
                    <button type="button" class="close ml-2" data-dismiss="modal"><span>&times;</span></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background:#222;">
                <div id="attachPreviewBody" style="min-height: 75vh; display:flex; align-items:center; justify-content:center;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var el = e.target.closest('.prf-attach-preview');
    if (!el) return;
    e.preventDefault();
    var url  = el.getAttribute('data-url');
    var type = el.getAttribute('data-type');
    var name = el.getAttribute('data-name') || '<?= lang_line('prf_preview_attachment', 'Preview Lampiran'); ?>';
    var body = document.getElementById('attachPreviewBody');
    document.getElementById('attachPreviewName').textContent = name;
    document.getElementById('attachPreviewDownload').href = url;
    if (type === 'image') {
        body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:80vh;object-fit:contain;" />';
    } else {
        body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:80vh;border:0;background:#fff;"></iframe>';
    }
    $('#attachmentPreviewModal').modal('show');
});

$('#attachmentPreviewModal').on('hidden.bs.modal', function () {
    document.getElementById('attachPreviewBody').innerHTML = '';
});
</script>

<!-- Upload Attachment Modal -->
<?php if (!empty($can_upload_attachment)): ?>
<div class="modal fade" id="uploadAttachmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload"></i> <?= lang_line('prf_upload_title', 'Upload Lampiran'); ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="prfUploadAttachmentForm" method="POST" action="<?php echo base_url('prf/upload_attachment/' . $prf->id); ?>" enctype="multipart/form-data">
                <?php if (!empty($admin_edit_mode)): ?><input type="hidden" name="admin_edit" value="1"><?php endif; ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('prf_upload_file_lbl', 'File (PDF / JPG / PNG, max 50 MB) — bisa pilih beberapa sekaligus'); ?> *</label>
                        <input type="file" id="prfUploadFileInput" name="attachment" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" multiple required>
                        <small id="prfUploadPickedText" class="text-muted d-block mt-1"></small>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> <?= lang_line('prf_upload_info', 'Lampiran akan otomatis ditambahkan di halaman setelahnya saat export PDF.'); ?>
                    </small>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-magic"></i> <?= lang_line('prf_upload_pdf_autoconvert', 'PDF disimpan utuh sebagai satu lampiran. Kalau formatnya tidak bisa ditempel langsung ke export, sistem otomatis menyiapkan versi khusus export tanpa mengubah file asli.'); ?>
                    </small>
                    <div id="prfUploadProgress" class="mt-3" style="display:none;">
                        <div class="d-flex justify-content-between mb-1">
                            <small id="prfUploadStatusText" class="text-muted"></small>
                            <small id="prfUploadCounterText" class="text-muted"></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="prfUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="prfUploadCancelBtn" class="btn btn-outline-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                    <button type="submit" id="prfUploadSubmitBtn" class="btn btn-primary"><?= lang_line('prf_btn_upload', 'Upload'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PDF.js for client-side PDF -> PNG conversion (avoids FPDI free-version embed failure on PDF 1.5+) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function(){
    if (window['pdfjsLib']) {
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    var form        = document.getElementById('prfUploadAttachmentForm');
    var fileInput   = document.getElementById('prfUploadFileInput');
    var submitBtn   = document.getElementById('prfUploadSubmitBtn');
    var cancelBtn   = document.getElementById('prfUploadCancelBtn');
    var progressBox = document.getElementById('prfUploadProgress');
    var progressBar = document.getElementById('prfUploadProgressBar');
    var statusText  = document.getElementById('prfUploadStatusText');
    var counterText = document.getElementById('prfUploadCounterText');
    var pickedText  = document.getElementById('prfUploadPickedText');
    if (!form) return;

    var L = {
        sending   : '<?= lang_line('prf_upload_js_sending', 'Mengunggah file...'); ?>',
        rendering : '<?= lang_line('prf_upload_js_rendering', 'Menyiapkan versi export halaman'); ?>',
        assembling: '<?= lang_line('prf_upload_js_assembling', 'Merakit versi export...'); ?>',
        done      : '<?= lang_line('prf_upload_js_done', 'Selesai. Memuat ulang...'); ?>',
        failed    : '<?= lang_line('prf_upload_js_failed', 'Gagal mengunggah halaman'); ?>',
        pdfjs_missing : '<?= lang_line('prf_upload_js_pdfjs_missing', 'File sudah tersimpan, tapi versi untuk export gagal dibuat karena library PDF.js tidak dapat dimuat. Lampiran ini mungkin tampil sebagai halaman keterangan saat export PDF.'); ?>',
        render_failed : '<?= lang_line('prf_upload_js_render_failed', 'File sudah tersimpan, tapi versi untuk export gagal dibuat:'); ?>',
        pwd_needed : '<?= lang_line('prf_upload_js_pwd_needed', 'PDF ini terkunci. Masukkan password untuk membuka:'); ?>',
        pwd_wrong  : '<?= lang_line('prf_upload_js_pwd_wrong', 'Password salah. Coba lagi:'); ?>',
        pwd_cancel : '<?= lang_line('prf_upload_js_pwd_cancel', 'Upload dibatalkan: password tidak diisi.'); ?>',
        file_counter : '<?= lang_line('prf_upload_js_file_counter', 'File'); ?>',
        files_picked : '<?= lang_line('prf_upload_js_files_picked', 'file dipilih — akan diunggah berurutan.'); ?>'
    };

    var BASE = '<?php echo base_url(); ?>';

    function setProgress(pct, status, counter) {
        progressBox.style.display = 'block';
        progressBar.style.width = pct + '%';
        if (status  !== undefined) statusText.textContent  = status;
        if (counter !== undefined) counterText.textContent = counter;
    }

    function lockUI(lock) {
        submitBtn.disabled = lock;
        cancelBtn.disabled = lock;
        fileInput.disabled = lock;
    }

    // Render one PDF page to a JPEG Blob (good size/quality balance).
    function renderPageToBlob(pdf, pageNum) {
        return pdf.getPage(pageNum).then(function(page){
            // 2.0 scale ≈ ~144 DPI: sharp enough for printed forms / signatures.
            var viewport = page.getViewport({ scale: 2.0 });
            var canvas   = document.createElement('canvas');
            canvas.width  = viewport.width;
            canvas.height = viewport.height;
            var ctx = canvas.getContext('2d');
            // White background — JPEG has no alpha, transparent PDF areas would go black.
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function(){
                return new Promise(function(resolve){
                    canvas.toBlob(function(blob){ resolve(blob); }, 'image/jpeg', 0.88);
                });
            });
        });
    }

    function postForm(url, fd) {
        fd.append('ajax', '1');
        return fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(res){
            return res.json().catch(function(){
                throw new Error('HTTP ' + res.status);
            }).then(function(json){
                if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));
                return json;
            });
        });
    }

    function reloadSoon() {
        setProgress(100, L.done, '');
        setTimeout(function(){ window.location.reload(); }, 600);
    }

    // ---- progress lintas file -------------------------------------------
    // Tiap file dapat jatah bar yang sama; fileProgress() memetakan kemajuan
    // di dalam satu file (0..1) ke posisi bar keseluruhan.
    var queue = { idx: 0, total: 1 };

    function fileLabel() {
        return queue.total > 1 ? (L.file_counter + ' ' + (queue.idx + 1) + '/' + queue.total) : '';
    }

    function fileProgress(frac, status, counter) {
        var pct = Math.round(((queue.idx + Math.min(Math.max(frac, 0), 1)) / queue.total) * 100);
        var tail = fileLabel();
        if (counter && tail) tail += ' · ' + counter;
        else if (counter)    tail = counter;
        setProgress(pct, status, tail);
    }

    // Bikin versi export: render tiap halaman lalu minta server merakitnya
    // jadi satu PDF. Hanya dijalankan kalau server bilang file aslinya tidak
    // bisa dibaca FPDI — file aslinya sendiri sudah tersimpan utuh.
    // Resolve dengan pesan peringatan (string) kalau gagal — file aslinya
    // sudah tersimpan, jadi antrean file berikutnya tidak boleh ikut batal.
    function buildRenderVersion(file, attachmentId) {
        if (!window['pdfjsLib']) {
            return Promise.resolve(L.pdfjs_missing);
        }

        var pwdCancelled = false;
        return file.arrayBuffer().then(function(buf){
            var loadingTask = pdfjsLib.getDocument({ data: buf });
            // Encrypted/password-protected PDF: PDF.js fires this before it can
            // open the document. Prompt the user; re-prompt on wrong password;
            // destroy() (which rejects loadingTask.promise) if they cancel.
            loadingTask.onPassword = function(updatePassword, reason) {
                var msg = (reason === pdfjsLib.PasswordResponses.INCORRECT_PASSWORD)
                        ? L.pwd_wrong : L.pwd_needed;
                var pw = window.prompt(msg);
                if (pw === null || pw === '') {
                    pwdCancelled = true;
                    loadingTask.destroy();
                    return;
                }
                updatePassword(pw);
            };
            return loadingTask.promise;
        }).then(function(pdf){
            var total = pdf.numPages;
            var chain = Promise.resolve();
            for (var i = 1; i <= total; i++) (function(pageNum){
                chain = chain.then(function(){
                    fileProgress(
                        0.15 + ((pageNum - 1) / total) * 0.8,
                        L.rendering + ' ' + pageNum + '/' + total,
                        pageNum + ' / ' + total
                    );
                    return renderPageToBlob(pdf, pageNum).then(function(blob){
                        var fd = new FormData();
                        fd.append('page', blob, 'p' + pageNum + '.jpg');
                        fd.append('page_no', String(pageNum));
                        return postForm(BASE + 'prf/upload_attachment_page/' + attachmentId, fd)
                            .catch(function(err){
                                throw new Error(L.failed + ' ' + pageNum + ': ' + err.message);
                            });
                    });
                });
            })(i);
            return chain;
        }).then(function(){
            fileProgress(0.96, L.assembling, '');
            return postForm(BASE + 'prf/finalize_attachment_render/' + attachmentId, new FormData());
        }).then(function(){
            return null;    // sukses, tidak ada peringatan
        }).catch(function(err){
            return pwdCancelled ? L.pwd_cancel : (L.render_failed + ' ' + (err.message || String(err)));
        });
    }

    // Proses satu file: kirim aslinya, lalu siapkan versi export kalau perlu.
    // Resolve dengan pesan peringatan (string) atau null kalau mulus.
    function uploadFile(file) {
        var isPdf = (file.type === 'application/pdf') ||
                    (file.name.toLowerCase().slice(-4) === '.pdf');

        fileProgress(0.05, L.sending, file.name);

        var fd = new FormData();
        fd.append('attachment', file, file.name);

        return postForm(form.action, fd).then(function(json){
            if (!isPdf || !json.needs_render) return null;
            fileProgress(0.15, L.rendering, '');
            return buildRenderVersion(file, json.attachment_id);
        }).catch(function(err){
            // Kegagalan satu file tidak boleh membatalkan sisa antrean.
            return file.name + ': ' + (err.message || String(err));
        });
    }

    fileInput.addEventListener('change', function(){
        var n = fileInput.files ? fileInput.files.length : 0;
        pickedText.textContent = n > 1 ? (n + ' ' + L.files_picked) : '';
    });

    form.addEventListener('submit', function(e){
        var files = fileInput.files;
        if (!files || !files.length) return;  // let browser handle empty case

        e.preventDefault();
        lockUI(true);

        queue.idx   = 0;
        queue.total = files.length;

        var warnings = [];
        var chain    = Promise.resolve();

        Array.prototype.forEach.call(files, function(file, i){
            chain = chain.then(function(){
                queue.idx = i;
                return uploadFile(file).then(function(warn){
                    if (warn) warnings.push(warn);
                });
            });
        });

        chain.then(function(){
            if (warnings.length) alert(warnings.join('\n\n'));
            reloadSoon();
        }).catch(function(err){
            lockUI(false);
            setProgress(0, '', '');
            progressBox.style.display = 'none';
            alert(err.message || String(err));
        });
    });
})();
</script>
<?php endif; ?>

<style>
/* Daftar lampiran — satu baris per file, ala file manager */
.prf-attach-item {
    padding: 10px 4px;
    border-bottom: 1px solid #eef0f3;
}

.prf-attach-item:last-child {
    border-bottom: 0;
}

.prf-attach-item:hover {
    background: #f8f9fb;
}

.prf-attach-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.prf-attach-icon img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    border-radius: 3px;
}

.prf-attach-name {
    font-weight: 500;
}

.prf-attach-actions {
    flex: 0 0 auto;
    white-space: nowrap;
}

.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    display: flex;
    margin-left: 40px;
    position: relative;
}

.timeline-marker {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    position: absolute;
    left: -40px;
    top: 5px;
}
</style>
