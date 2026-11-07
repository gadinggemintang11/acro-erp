<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-file-invoice-dollar text-primary mr-2"></i><?= htmlspecialchars($title ?? 'Detail PO Jasa'); ?>
            <?php if (isset($header) && $header): ?>
                <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->po_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
        <?php if (isset($header) && $header): ?>
            <?php if ($header->status == 'Draft' && check_access('purchase_order_jasa', 'submit')): ?>
                <a href="<?= base_url('purchase_order_jasa/submit_po/'.$header->id); ?>" class="btn btn-info btn-sm mr-1 btn-submit" data-message="Yakin ingin submit PO ini? Setelah disubmit, header tidak bisa diedit."> <i class="fas fa-paper-plane fa-sm"></i> Submit PO </a>
            <?php endif; ?>
            <?php if ($header->status == 'Submitted'): ?>
                <span class="badge badge-info mr-2 p-2"><i class="fas fa-clock mr-1"></i> Menunggu PO Approval</span>
            <?php endif; ?>
             <?php if ($header->status == 'Confirmed' && check_access('purchase_order_jasa', 'edit')): ?>
                 <a href="<?= base_url('purchase_order_jasa/unconfirm_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="Yakin ingin membatalkan konfirmasi PO ini?" data-text="Pastikan belum ada jasa yang diselesaikan."> <i class="fas fa-times-circle fa-sm"></i> Unconfirm </a>
            <?php endif; ?>
             <?php if (in_array($header->status, ['Draft', 'Submitted', 'Confirmed']) && check_access('purchase_order_jasa', 'delete')): ?>
                 <a href="<?= base_url('purchase_order_jasa/cancel_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="Yakin ingin membatalkan (cancel) PO ini?" data-text="Aksi ini tidak bisa diurungkan."> <i class="fas fa-ban fa-sm"></i> Cancel PO </a>
            <?php endif; ?>
            <?php if (!empty($can_edit_header)): ?>
                 <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail"> <i class="fas fa-edit fa-sm"></i> Edit Header </button>
             <?php endif; ?>

            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                 <a href="<?= base_url('purchase_order_jasa/detail/'.$header->id); ?>?admin_edit=1" class="btn btn-outline-warning btn-sm mr-1 btn-confirm no-loader"
                    data-message="Buka kunci edit PO ini?"
                    data-text="PO sudah <?= htmlspecialchars($header->status); ?>. Membuka kunci memungkinkan Anda mengubahnya sebagai Admin. Approval TIDAK direset."
                    data-icon="warning" data-confirm-text="Ya, Buka Kunci">
                     <i class="fas fa-lock fa-sm"></i> Buka Kunci Edit (Admin)
                 </a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                 <a href="<?= base_url('purchase_order_jasa/detail/'.$header->id); ?>" class="btn btn-warning btn-sm mr-1 no-loader">
                     <i class="fas fa-lock-open fa-sm"></i> Selesai Edit (Kunci Lagi)
                 </a>
            <?php endif; ?>
            <a href="<?= base_url('purchase_order_jasa/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1 no-loader"> <i class="fas fa-file-excel fa-sm"></i> Excel </a>
            <a href="<?= base_url('purchase_order_jasa/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1 no-loader"> <i class="fas fa-file-pdf fa-sm"></i> PDF </a>
        <?php endif; ?>
            <a href="<?= base_url('purchase_order_jasa'); ?>" class="btn btn-secondary btn-sm btn-load"> <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali ke Daftar </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?> <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?> <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>

    <script>
        const flashSuccess = "<?= addslashes($this->session->flashdata('success') ?? ''); ?>";
        const flashError = "<?= addslashes($this->session->flashdata('error') ?? ''); ?>";
        const flashWarning = "<?= addslashes($this->session->flashdata('warning') ?? ''); ?>";

        if (flashSuccess && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: flashSuccess, timer: 2000, showConfirmButton: false });
        }
        if (flashError && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: flashError });
        }
        if (flashWarning && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Peringatan!', text: flashWarning });
        }
    </script>

    <?php if (isset($header) && $header): ?>
     <div class="card shadow mb-4">
        <div class="card-header py-3"> <h6 class="m-0 font-weight-bold text-primary">Informasi Purchase Order Jasa</h6> </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong>Nomor PO</strong></td><td style="width:2%">:</td><td><?= htmlspecialchars($header->po_number); ?></td></tr>
                         <tr><td><strong>Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->nama_supplier); ?></td></tr>
                         <tr><td><strong>Alamat Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->alamat ?? '-'); ?></td></tr>
                         <tr><td><strong>Telepon Supplier</strong></td><td>:</td><td><?= htmlspecialchars($header->telepon ?? '-'); ?></td></tr>
                         <tr><td><strong>Dibuat Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->creator_name ?? '-'); ?></td></tr>
                         <tr><td><strong>Dikonfirmasi Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->confirmer_name ?? '-'); ?> <?= $header->confirmed_at ? '('.date('d M Y H:i', strtotime($header->confirmed_at)).')' : ''; ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                     <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong>Tanggal PO</strong></td><td style="width:2%">:</td><td><?= date('d M Y', strtotime($header->po_date)); ?></td></tr>
                         <tr><td><strong>Status</strong></td><td>:</td><td><?php $color = $status_colors[$header->status] ?? 'secondary'; ?><span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($header->status); ?></span></td></tr>
                         <tr><td><strong>Mode Pengiriman</strong></td><td>:</td><td><?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong>Mode Pembayaran</strong></td><td>:</td><td><?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong>Mata Uang</strong></td><td>:</td><td><?= htmlspecialchars($header->currency_desc ?? '-'); ?> <?= $header->currency_code != 'IDR' ? '(Kurs: '.number_format((float)$header->exchange_rate, 4).')' : ''; ?></td></tr>
                         <tr><td><strong>PPN 11%</strong></td><td>:</td><td><?= $header->has_ppn ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>'; ?></td></tr>
                    </table>
                </div>
            </div>
             <?php if (!empty($source_prs)): ?>
             <hr class="mt-2 mb-2">
             <p class="mb-0 small">
                 <strong>Dari Purchase Request:</strong>
                 <?php foreach ($source_prs as $spr): ?>
                     <a href="<?= base_url('purchase_request_jasa/detail/' . $spr->id); ?>" class="badge badge-light border mr-1"
                        title="<?= htmlspecialchars($spr->purpose ?? ''); ?>">
                         <i class="fas fa-file-alt"></i> <?= htmlspecialchars($spr->pr_number); ?>
                         <?php if (isset($spr->line_count)): ?><span class="text-muted">(<?= (int) $spr->line_count; ?> baris)</span><?php endif; ?>
                     </a>
                 <?php endforeach; ?>
             </p>
             <?php endif; ?>

             <?php if (!empty($header->remarks)): ?> <hr class="mt-2 mb-2"><p class="mb-0 small"><strong>Catatan / Remarks:</strong><br> <?= nl2br(htmlspecialchars($header->remarks)); ?></p><?php endif; ?>
        </div>
    </div>

    <?php // Riwayat Approval + Back to Draft 2-arah/reply, [[erp_revision_cross_cutting]] ?>
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
    <?php else: ?>
        <div class="alert alert-danger">Gagal memuat data header PO. Silakan kembali ke daftar.</div>
    <?php endif; ?>

    <?php if (!empty($admin_edit_mode) && !empty($can_edit_header)): ?>
    <div class="alert alert-warning border-left-warning" role="alert">
        <h6 class="font-weight-bold mb-1"><i class="fas fa-user-shield mr-1"></i> Mode Edit Admin</h6>
        <p class="mb-1 small">
            PO ini berstatus <strong><?= htmlspecialchars($header->status); ?></strong> dan normalnya terkunci.
            Sebagai Admin Anda tetap bisa memperbaikinya, dan <strong>approval tidak direset</strong> &mdash;
            PO tetap <?= htmlspecialchars($header->status); ?> tanpa perlu submit &amp; approve ulang.
            Semua perubahan tercatat di Log Activity.
        </p>
        <?php if (!empty($has_receipts)): ?>
        <p class="mb-0 small text-danger font-weight-bold">
            <i class="fas fa-lock mr-1"></i> Baris item terkunci: jasa pada PO ini sudah ada penyelesaian.
            Hanya data header yang masih bisa diubah.
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($header) && $header): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Item Jasa</h6>
            <?php if (!empty($can_edit_item)): ?>
            <div class="btn-group">
                <button class="btn btn-info btn-sm" type="button" data-toggle="modal" data-target="#pullPrModal">
                    <i class="fas fa-file-import fa-sm"></i> Tarik dari PR
                </button>
            <?php if (!empty($allow_manual_items)): // false utk GMPO: item wajib dari PR (user 2026-09-09) ?>
            <button class="btn btn-success btn-sm" type="button" id="add-item-row-btn">
                <i class="fas fa-plus fa-sm"></i> Tambah Baris Jasa
            </button>
            <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <style>
                    #po-items-table { table-layout: fixed; min-width: 900px; }
                    #po-items-table td { overflow-wrap: break-word; word-wrap: break-word; }
                </style>
                <table class="table table-bordered table-sm mb-0 table-hover" id="po-items-table">
                    <thead class="thead-light text-center">
                        <tr>
                            <th style="width: 3%;">No</th>
                            <th style="width: 10%;">Kode Jasa</th>
                            <th style="width: 18%;">Nama Jasa</th>
                            <th style="width: 7%;">Satuan</th>
                            <th style="width: 28%;">Deskripsi Tambahan</th>
                            <th style="width: 9%;">Qty</th>
                            <th style="width: 12%;">Harga Satuan (<?= $header->currency_code; ?>)</th>
                            <th style="width: 12%;">Total (<?= $header->currency_code; ?>)</th>
                            <th style="width: 6%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="po-item-details-body">
                        <?php
                             $grand_total_base = 0; $grand_total_local = 0; $no = 1;
                             // $can_edit_item dikirim controller (sudah memperhitungkan override Admin
                             // dan kunci penerimaan); fallback false kalau view dipanggil tanpanya.
                             $can_edit_item = !empty($can_edit_item);
                             if (!empty($details)):
                                 foreach ($details as $d):
                                     $row_total_base = (float)($d->qty ?? 0) * (float)($d->base_price ?? 0); $grand_total_base += $row_total_base; $grand_total_local += (float)($d->total_local ?? 0);
                                     $price_zero = (float)($d->base_price ?? 0) == 0;
                        ?>
                        <tr data-detail-id="<?= $d->id ?>">
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                            <td>
                                <?= htmlspecialchars($d->item_name ?? '-'); ?>
                                <?php if (!empty($d->pr_number)): ?>
                                    <br><small class="badge badge-light border text-muted pr-origin-badge"
                                               title="Baris ini ditarik dari <?= htmlspecialchars($d->pr_number); ?><?= isset($d->pr_qty) ? ' — qty di PR: ' . number_format((float) $d->pr_qty, 2) : ''; ?>">
                                        <i class="fas fa-file-import"></i> <?= htmlspecialchars($d->pr_number); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                            <td class="cell-desc">
                                <span class="display-val"><?= htmlspecialchars($d->description ?? '-'); ?></span>
                                <?php if ($can_edit_item): ?><input type="text" class="form-control form-control-sm edit-input" style="display:none" value="<?= htmlspecialchars($d->description ?? '') ?>"><?php endif; ?>
                            </td>
                            <td class="text-right cell-qty">
                                <span class="display-val"><?= number_format((float)($d->qty ?? 0), 4, '.', ','); ?></span>
                                <?php if ($can_edit_item): ?><input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm edit-input text-right" style="display:none" value="<?= (float)$d->qty ?>"><?php endif; ?>
                            </td>
                            <td class="text-right cell-price<?= $price_zero ? ' price-zero' : '' ?>">
                                <span class="display-val<?= $price_zero ? ' text-danger font-weight-bold' : '' ?>"><?= number_format((float)($d->base_price ?? 0), 4, '.', ','); ?><?= $price_zero ? ' ⚠' : '' ?></span>
                                <?php if ($can_edit_item): ?><input type="number" step="0.0001" min="0" class="form-control form-control-sm edit-input text-right" style="display:none" value="<?= (float)$d->base_price ?>"><?php endif; ?>
                            </td>
                            <td class="text-right cell-total"><?= number_format($row_total_base, 2, '.', ','); ?></td>
                            <td class="text-center">
                                <?php if ($can_edit_item): ?>
                                <div class="btn-view-mode">
                                    <button type="button" class="btn btn-warning btn-xs btn-edit-inline mr-1" data-id="<?= $d->id ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="<?= base_url('purchase_order_jasa/delete_item/'.$d->po_header_id.'/'.$d->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus" data-message="Yakin hapus item <?= htmlspecialchars($d->item_name ?? 'ini'); ?>?"><i class="fas fa-trash"></i></a>
                                </div>
                                <div class="btn-edit-mode" style="display:none">
                                    <button type="button" class="btn btn-success btn-xs btn-save-inline mr-1" data-id="<?= $d->id ?>" title="Simpan"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-secondary btn-xs btn-cancel-inline" data-id="<?= $d->id ?>" title="Batal"><i class="fas fa-times"></i></button>
                                </div>
                                <?php else: echo '-'; endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php else: ?>
                            <tr id="no-item-row"><td colspan="9" class="text-center text-muted py-5">Belum ada item jasa. Silakan tambahkan.</td></tr>
                        <?php endif; ?>
                    </tbody>

                    <tfoot id="po-item-details-footer">
                         <?php
                            $ppn_base = $header->has_ppn ? $grand_total_base * 0.11 : 0;
                            $grand_total_final_base = $grand_total_base + $ppn_base;
                            $grand_total_final_local = $grand_total_local * ($header->has_ppn ? 1.11 : 1);
                         ?>
                        <tr class="bg-light font-weight-bold"> <td colspan="7" class="text-right">Subtotal (<?= $header->currency_code; ?>)</td> <td class="text-right" id="subtotal-base"><?= number_format($grand_total_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if ($header->has_ppn): ?>
                            <tr class="bg-light"> <td colspan="7" class="text-right">PPN 11% (<?= $header->currency_code; ?>)</td> <td class="text-right" id="ppn-base"><?= number_format($ppn_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php endif; ?>
                          <tr class="bg-primary text-white font-weight-bold"> <td colspan="7" class="text-right">Grand Total (<?= $header->currency_code; ?>)</td> <td class="text-right" id="grandtotal-base"><?= number_format($grand_total_final_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if ($header->currency_code != 'IDR'): ?> <tr class="bg-info text-white font-weight-bold"> <td colspan="7" class="text-right">Estimasi Grand Total (IDR)</td> <td class="text-right" id="grandtotal-local"><?= number_format($grand_total_final_local, 2, '.', ','); ?></td> <td></td> </tr> <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($header) && $header && $header->status === 'Confirmed'): ?>
    <div class="card shadow mb-4 border-left-success">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-check-circle mr-1"></i> Selesaikan Jasa</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($service_items)): ?>
                <?php if (check_access('purchase_order_jasa', 'confirm')): ?>
                <p class="small text-muted">Tandai jasa yang sudah selesai dikerjakan. Setelah disimpan, jasa ini otomatis tersedia untuk ditagihkan di AP Invoice (pilih GRN saat membuat faktur ke supplier ini).</p>
                <form method="post" action="<?= base_url('purchase_order_jasa/complete_service/'.$header->id); ?>" id="complete-service-form">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-3">
                            <thead class="thead-light text-center">
                                <tr>
                                    <th width="4%"><input type="checkbox" id="check-all-service" checked></th>
                                    <th width="12%">Kode</th>
                                    <th>Nama Jasa</th>
                                    <th width="12%">Qty Dipesan</th>
                                    <th width="12%">Sisa</th>
                                    <th width="18%">Qty Diselesaikan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($service_items as $si): ?>
                                <tr>
                                    <td class="text-center"><input type="checkbox" class="service-check" checked></td>
                                    <td><?= htmlspecialchars($si->item_code ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($si->item_name ?? '-'); ?></td>
                                    <td class="text-right"><?= number_format((float)$si->ordered_qty, 4, '.', ','); ?></td>
                                    <td class="text-right"><?= number_format((float)$si->remaining_qty, 4, '.', ','); ?></td>
                                    <td>
                                        <input type="hidden" name="detail_id[]" value="<?= $si->detail_id; ?>">
                                        <input type="hidden" name="item_id[]" value="<?= $si->item_id; ?>">
                                        <input type="hidden" name="unit_price[]" value="<?= (float)$si->unit_price; ?>">
                                        <input type="number" step="0.0001" min="0" max="<?= (float)$si->remaining_qty; ?>"
                                               data-max="<?= (float)$si->remaining_qty; ?>"
                                               name="qty[]" class="form-control form-control-sm text-right service-qty-input"
                                               value="<?= (float)$si->remaining_qty; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-check mr-1"></i> Selesaikan Jasa Terpilih</button>
                </form>
                <?php else: ?>
                    <p class="text-muted mb-0"><i class="fas fa-lock mr-1"></i> Anda tidak punya izin untuk menyelesaikan jasa pada PO ini.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted mb-0"><i class="fas fa-info-circle mr-1"></i> Semua jasa pada PO ini sudah diselesaikan dan siap ditagihkan di AP.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if (isset($header) && $header && !empty($can_edit_header)): ?>
<div class="modal fade" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order_jasa/update_header/'.$header->id); ?>" class="needs-validation" novalidate>
             <div class="modal-content border-0 shadow">
                 <div class="modal-header bg-warning text-dark"> <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header PO</h5> <button type="button" class="close text-dark" data-dismiss="modal">&times;</button> </div>
                 <div class="modal-body">
                     <?php if (!empty($admin_edit_mode)): ?><input type="hidden" name="admin_edit" value="1"><?php endif; ?>
                     <?php if (!empty($admin_edit_mode)): ?>
                     <div class="alert alert-warning py-2 small mb-3">
                         <i class="fas fa-user-shield mr-1"></i>
                         Edit Admin pada PO berstatus <strong><?= htmlspecialchars($header->status); ?></strong>.
                         Approval tidak direset &mdash; PO tetap <?= htmlspecialchars($header->status); ?> setelah disimpan.
                     </div>
                     <?php endif; ?>
                     <div class="row">
                         <div class="col-md-6">
                             <div class="form-group">
                                 <label for="edit_po_date">Tanggal PO <span class="text-danger">*</span></label>
                                 <input type="date" class="form-control form-control-sm" id="edit_po_date" name="po_date" value="<?= date('Y-m-d', strtotime($header->po_date)); ?>" required>
                             </div>
                         </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                 <label for="edit_supplier_id">Supplier <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($header->nama_supplier); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                 <input type="hidden" name="supplier_id" value="<?= $header->supplier_id; ?>">
                                 <small class="form-text text-muted">Supplier sudah dipilih saat pembuatan PO</small>
                             </div>
                         </div>
                     </div>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_shipping_mode">Mode Pengiriman</label>
                                <select name="shipping_mode_code" id="edit_shipping_mode" class="form-control form-control-sm select2-modal" style="width: 100%;">
                                    <option value="">-- Pilih Mode --</option>
                                    <?php foreach (($shipping_modes ?? []) as $mode): ?>
                                        <option value="<?= $mode->code; ?>" <?= (isset($header->shipping_mode_code) && $header->shipping_mode_code == $mode->code) ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_payment_mode">Mode Pembayaran</label>
                                <select name="payment_mode_code" id="edit_payment_mode" class="form-control form-control-sm select2-modal" style="width: 100%;">
                                    <option value="">-- Pilih Mode --</option>
                                    <?php foreach (($payment_modes ?? []) as $mode): ?>
                                        <option value="<?= $mode->code; ?>" <?= (isset($header->payment_mode_code) && $header->payment_mode_code == $mode->code) ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                     </div>
                     <div class="row">
                         <div class="col-md-6">
                             <div class="form-group">
                                 <label for="edit_currency_code">Mata Uang <span class="text-danger">*</span></label>
                                 <select class="form-control form-control-sm" id="edit_currency_code" name="currency_code" required>
                                     <option value="IDR" <?= ($header->currency_code == 'IDR') ? 'selected' : ''; ?>>Rupiah (IDR)</option>
                                     <option value="USD" <?= ($header->currency_code == 'USD') ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                     <option value="EUR" <?= ($header->currency_code == 'EUR') ? 'selected' : ''; ?>>Euro (EUR)</option>
                                     <option value="CNY" <?= ($header->currency_code == 'CNY') ? 'selected' : ''; ?>>Yuan (CNY)</option>
                                     <option value="JPY" <?= ($header->currency_code == 'JPY') ? 'selected' : ''; ?>>Yen (JPY)</option>
                                     <option value="SGD" <?= ($header->currency_code == 'SGD') ? 'selected' : ''; ?>>SGD</option>
                                 </select>
                             </div>
                         </div>
                         <div class="col-md-6" id="edit_exchange_rate_div" style="<?= ($header->currency_code == 'IDR') ? 'display:none;' : ''; ?>">
                             <div class="form-group">
                                 <label for="edit_exchange_rate">Kurs <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control form-control-sm autonumeric-4dec-edit" id="edit_exchange_rate" name="exchange_rate" value="<?= number_format((float)$header->exchange_rate, 4, '.', ''); ?>" <?= ($header->currency_code != 'IDR') ? 'required' : ''; ?>>
                                 <small class="form-text text-muted">Contoh: 1 USD = 15000.0000 IDR</small>
                             </div>
                         </div>
                     </div>
                     <div class="row">
                         <div class="col-md-12">
                             <div class="form-group">
                                 <div class="custom-control custom-checkbox">
                                     <input type="checkbox" class="custom-control-input" id="edit_has_ppn" name="has_ppn" value="1" <?= $header->has_ppn ? 'checked' : ''; ?>>
                                     <label class="custom-control-label" for="edit_has_ppn">Termasuk PPN 11%</label>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="row">
                         <div class="col-md-12">
                             <div class="form-group">
                                 <label for="edit_remarks">Catatan / Remarks</label>
                                 <textarea class="form-control form-control-sm" id="edit_remarks" name="remarks" rows="3" placeholder="Opsional..."><?= htmlspecialchars($header->remarks ?? ''); ?></textarea>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="modal-footer"> <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button> <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button> </div>
             </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (isset($header) && $header): ?>
<template id="new-item-row-template">
    <tr class="new-item-row">
        <td class="text-center align-middle">*</td>
        <td><input type="text" class="form-control form-control-sm item-code-display" readonly style="background-color: #e9ecef;"></td>
        <td><select name="item_id" class="form-control form-control-sm select2-inline-item" required style="width: 100%;"><option value="">-- Cari Nama Jasa --</option></select></td>
        <td><input type="text" class="form-control form-control-sm item-unit-display text-center" readonly style="background-color: #e9ecef;"></td>
        <td><input type="text" name="description" class="form-control form-control-sm" placeholder="Opsional..."></td>
        <td><input type="number" step="0.0001" name="qty" class="form-control form-control-sm text-right inline-qty" required min="0.0001" placeholder="0.0000"></td>
        <td><input type="text" name="base_price" class="form-control form-control-sm autonumeric-2dec-inline text-right inline-price" required value="0.0000"></td>
        <td class="text-right align-middle inline-total">0.00</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-success btn-xs save-inline-item" title="Simpan Item Ini"> <i class="fas fa-check"></i> </button>
            <button type="button" class="btn btn-danger btn-xs cancel-inline-item" title="Batal"> <i class="fas fa-times"></i> </button>
        </td>
    </tr>
</template>
<?php endif; ?>

<?php $this->load->view('templates/footer'); ?>

<?php
// Modal "Tarik Item dari PR" — markup + JS-nya dipakai bersama keempat varian PO.
if (isset($header) && $header && !empty($can_edit_item)) {
    $this->load->view('templates/partials/pull_pr_modal', [
        'header'        => $header,
        'pull_pr_route' => 'purchase_order_jasa',
    ]);
}
?>

<script>
$(function() {

    const currentStatus = <?= isset($header->status) ? json_encode($header->status) : '"unknown"'; ?>;
    const poHeaderId = <?= isset($header->id) ? $header->id : 0; ?>;
    // Dikirim controller: sudah memperhitungkan status, izin role, override Admin,
    // dan apakah baris terkunci penerimaan barang.
    const canEditItem = <?= !empty($can_edit_item) ? 'true' : 'false'; ?>;
    const adminEditFlag = <?= !empty($admin_edit_mode) ? '1' : '0'; ?>;

    let anEditExchangeRate = null;
    const $editModal = $('#editHeaderModalDetail');
    if ($editModal.length) {
       const $editRateInput = $editModal.find('.autonumeric-4dec-edit');
       if ($editRateInput.length && typeof AutoNumeric === 'function') {
           try {
                anEditExchangeRate = new AutoNumeric($editRateInput[0], { decimalPlaces: 4, modifyValueOnWheel: false });
                const $editRateDiv = $editModal.find('#edit_exchange_rate_div');
                const $editRateInputElem = $editRateDiv.find('input');
                const $editCurrencySelect = $editModal.find('#edit_currency_code');

                const toggleEditKurs = () => {
                     const selectedCurrency = $editCurrencySelect.val();
                     if (selectedCurrency && selectedCurrency !== 'IDR') {
                         $editRateDiv.slideDown(); $editRateInputElem.prop('required', true);
                     } else {
                         $editRateDiv.slideUp(); $editRateInputElem.prop('required', false);
                         if (anEditExchangeRate) anEditExchangeRate.set(1); else $editRateInputElem.val('1.0000');
                     }
                }
                toggleEditKurs();
                $editCurrencySelect.on('change', toggleEditKurs);

                try {
                    const $selectModalElements = $editModal.find('.select2-modal');
                    if ($selectModalElements.length && typeof $.fn.select2 === 'function') {
                        $selectModalElements.each(function() {
                            if ($(this).data('select2')) $(this).select2('destroy');
                            $(this).select2({ dropdownParent: $editModal, theme: 'bootstrap4', width: '100%' });
                        });
                    }
                } catch (e) { console.warn('Error initializing Select2 in edit modal', e); }

                $editModal.find('form.needs-validation').on('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (this.checkValidity() === false) {
                        $(this).addClass('was-validated');
                        return false;
                    }
                    $(this).addClass('was-validated');
                    const $submitBtn = $(this).find('button[type="submit"]');
                    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                    setTimeout(() => { this.submit(); }, 200);
                });
           } catch (err) { console.error("Error initializing AN/Currency for Edit Modal:", err); }
       }
    }

    if (canEditItem) {
        const $itemTableBody = $('#po-item-details-body');
        const templateNode = document.getElementById('new-item-row-template');
        let $noItemRow = $('#no-item-row');
        let inlineAutoNumericInstances = [];

        if (!templateNode || !templateNode.content) { return; }

        function initializeNewRowInputs($rowElement) {
            inlineAutoNumericInstances = inlineAutoNumericInstances.filter(instance => {
                if ($.contains($rowElement[0], instance.domElement)) {
                    try { instance.detach(); } catch (e) {} return false;
                } return true;
            });

            const $itemSelectElement = $rowElement.find('.select2-inline-item');
            if (!$itemSelectElement.length) return;
            if (typeof $.fn.select2 === 'function') {
                 try {
                    if ($itemSelectElement.data('select2')) { $itemSelectElement.select2('destroy'); }
                    $itemSelectElement.select2({
                        placeholder: '-- Cari Nama Jasa --', minimumInputLength: 2, theme: 'bootstrap4',
                        ajax: {
                            url: '<?= base_url("purchase_order_jasa/search_items"); ?>', dataType: 'json', delay: 400,
                            data: function (params) { return { q: params.term }; },
                            processResults: function (data) {
                                if (data && Array.isArray(data.results)) { return { results: data.results }; }
                                return { results: [] };
                            }
                        },
                        dropdownParent: $(document.body)
                    }).on('select2:select', function (e) {
                         var data = e.params.data;
                         const $currentRow = $(this).closest('tr');
                         $currentRow.find('.item-code-display').val(data.code || '-');
                         $currentRow.find('.item-unit-display').val(data.unit || '-');
                         $currentRow.find('.inline-qty').focus();
                    }).on('select2:open', function (e) {
                         setTimeout(() => { document.querySelector('.select2-search__field')?.focus(); }, 50);
                    });
                 } catch (err) { console.error("Error during Select2 initialization:", err); }
            }

            const priceInputElement = $rowElement.find('.autonumeric-2dec-inline')[0];
            if (priceInputElement && typeof AutoNumeric === 'function') {
                try {
                    const anPrice = new AutoNumeric(priceInputElement, { decimalPlaces: 4, modifyValueOnWheel: false });
                    inlineAutoNumericInstances.push(anPrice);
                } catch (e) { console.error(e); }
            }
        }

        function calculateRowTotal($rowElement) {
             const qty = parseFloat($rowElement.find('.inline-qty').val()) || 0;
             let price = 0;
             const priceInput = $rowElement.find('.autonumeric-2dec-inline')[0];
             const anInstance = AutoNumeric.getAutoNumericElement(priceInput);
             if (anInstance) { price = anInstance.getNumber() || 0; }
             else { price = parseFloat(($(priceInput).val() || '0').replace(/,/g, '')); }
             const total = qty * price;
             $rowElement.find('.inline-total').text( total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) );
         }

        $('#add-item-row-btn').off('click').on('click', function() {
            $noItemRow = $('#no-item-row');
            if ($noItemRow.length) { $noItemRow.remove(); }
            if ($itemTableBody.find('.new-item-row').length > 0) {
                 alert('Selesaikan atau batalkan penambahan item sebelumnya.'); return;
            }
             if (!templateNode || !templateNode.content) { return; }

             try {
                 const newRowFragment = templateNode.content.cloneNode(true);
                 $itemTableBody.append(newRowFragment);
                 const $appendedRow = $itemTableBody.find('tr.new-item-row').last();

                 if ($appendedRow.length) {
                     setTimeout(() => {
                          initializeNewRowInputs($appendedRow);
                          $appendedRow.find('.select2-inline-item').select2('open');
                     }, 50);

                     $appendedRow.find('.inline-qty, .autonumeric-2dec-inline').on('input change keyup focusout', function() {
                         calculateRowTotal($(this).closest('tr'));
                     });
                 }
             } catch (err) { console.error("Error cloning/appending template:", err); alert("Gagal menambahkan baris baru."); }
        });

        $itemTableBody.off('click', '.save-inline-item').on('click', '.save-inline-item', function() {
             const button = $(this);
             const $row = button.closest('tr.new-item-row');
             if (!$row.length) return;

             const $itemSelect = $row.find('select[name="item_id"]');
             const $qtyInput = $row.find('input[name="qty"]');
             const $priceInput = $row.find('input[name="base_price"]');
             const $descInput = $row.find('input[name="description"]');

             const itemIdValue = $itemSelect.val();
             const qtyValue = parseFloat($qtyInput.val());
             let basePriceNumeric = 0;
             const anPriceInstance = AutoNumeric.getAutoNumericElement($priceInput[0]);
             if (anPriceInstance) { basePriceNumeric = anPriceInstance.getNumber(); }
             else { basePriceNumeric = parseFloat(($priceInput.val() || '0').replace(/,/g, '')); }

             if (!itemIdValue) { alert('Harap pilih jasa.'); if ($itemSelect.data('select2')) $itemSelect.select2('open'); return; }
             if (isNaN(qtyValue) || qtyValue <= 0) { alert('Harap isi Qty (> 0).'); $qtyInput.focus(); return; }
             if (isNaN(basePriceNumeric) || basePriceNumeric < 0) { alert('Harga Satuan tidak boleh negatif.'); $priceInput.focus(); return; }

             const dataToSend = {
                 item_id: itemIdValue, qty: qtyValue, base_price: basePriceNumeric, description: $descInput.val(),
                 admin_edit: adminEditFlag
             };

             $.ajax({
                 url: `<?= base_url('purchase_order_jasa/ajax_add_item/'); ?>${poHeaderId}`,
                 method: 'POST', data: dataToSend, dataType: 'json',
                 beforeSend: function() {
                     button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                     $row.find('.cancel-inline-item').prop('disabled', true);
                 },
                 success: function(response) {
                     if (response && response.status === 'success') {
                         Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message || 'Jasa berhasil disimpan.', timer: 1500, showConfirmButton: false })
                             .then(() => window.location.reload());
                     } else {
                          Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message || 'Gagal menyimpan data.' });
                          button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                          $row.find('.cancel-inline-item').prop('disabled', false);
                     }
                 },
                 error: function() {
                      Swal.fire({ icon: 'error', title: 'Error!', text: 'Gagal menghubungi server. Periksa koneksi Anda.' });
                      button.prop('disabled', false).html('<i class="fas fa-check"></i>');
                      $row.find('.cancel-inline-item').prop('disabled', false);
                 }
             });
        });

         $itemTableBody.off('click', '.cancel-inline-item').on('click', '.cancel-inline-item', function() {
             $(this).closest('tr.new-item-row').remove();
             if ($itemTableBody.find('tr').length === 0) {
                 $itemTableBody.append('<tr id="no-item-row"><td colspan="9" class="text-center text-muted py-5">Belum ada item jasa. Silakan tambahkan.</td></tr>');
             }
         });
    }

    if (typeof Swal !== 'undefined') {
        $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var message = $(this).data('message') || 'Yakin ingin menghapus data ini?';
            Swal.fire({
              title: message, text: 'Data yang dihapus tidak dapat dikembalikan!', icon: 'warning',
              showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
              confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796', reverseButtons: true
            }).then((result) => { if (result.isConfirmed) { window.location.href = url; } });
        });
        $(document).off('click', 'a.btn-confirm, a.btn-submit').on('click', 'a.btn-confirm, a.btn-submit', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var message = $(this).data('message') || 'Yakin ingin melanjutkan?';
            var text = $(this).data('text') || '';
            Swal.fire({
              title: message, text: text, icon: $(this).data('icon') || 'question',
              showCancelButton: true, confirmButtonText: $(this).data('confirm-text') || 'Ya', cancelButtonText: 'Batal',
              reverseButtons: true
            }).then((result) => { if (result.isConfirmed) { window.location.href = url; } });
        });
    }

    const $poBody = $('#po-item-details-body');

    $poBody.on('click', '.btn-edit-inline', function() {
        const $row = $(this).closest('tr');
        $row.addClass('editing');
        $row.find('.display-val').hide();
        $row.find('.edit-input').show();
        $row.find('.btn-view-mode').hide();
        $row.find('.btn-edit-mode').show();
        $row.find('.cell-qty .edit-input').focus().select();
    });

    $poBody.on('click', '.btn-cancel-inline', function() {
        const $row = $(this).closest('tr');
        $row.removeClass('editing');
        $row.find('.display-val').show();
        $row.find('.edit-input').hide();
        $row.find('.btn-view-mode').show();
        $row.find('.btn-edit-mode').hide();
    });

    $poBody.on('click', '.btn-save-inline', function() {
        const $btn  = $(this);
        const detailId = $btn.data('id');
        const $row  = $btn.closest('tr');

        const qty   = parseFloat($row.find('.cell-qty .edit-input').val());
        const price = parseFloat($row.find('.cell-price .edit-input').val()) || 0;
        const desc  = $row.find('.cell-desc .edit-input').val();

        if (!qty || qty <= 0) { alert('Qty harus lebih dari 0'); $row.find('.cell-qty .edit-input').focus(); return; }
        if (isNaN(price) || price < 0) { alert('Harga tidak boleh negatif'); $row.find('.cell-price .edit-input').focus(); return; }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $row.find('.btn-cancel-inline').prop('disabled', true);

        $.post('<?= base_url("purchase_order_jasa/ajax_update_item/") ?>' + detailId, {
            qty: qty, base_price: price, description: desc, admin_edit: adminEditFlag
        }, function(res) {
            if (res && res.success) {
                const fmtQty   = qty.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4});
                const fmtPrice = price.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4}); // harga satuan 4 desimal; fmtTotal di bawah TETAP 2
                const fmtTotal = (qty * price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                $row.find('.cell-qty .display-val').text(fmtQty);
                $row.find('.cell-price .display-val').text(fmtPrice).removeClass('text-danger font-weight-bold');
                $row.find('.cell-price').removeClass('price-zero');
                $row.find('.cell-total').text(fmtTotal);
                $row.find('.cell-desc .display-val').text(desc || '-');

                $row.removeClass('editing');
                $row.find('.display-val').show();
                $row.find('.edit-input').hide();
                $row.find('.btn-view-mode').show();
                $row.find('.btn-edit-mode').hide();

                if (typeof toastr !== 'undefined') toastr.success('Item berhasil diperbarui.');
            } else {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
                else alert(res.message);
            }
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            $row.find('.btn-cancel-inline').prop('disabled', false);
        }, 'json').fail(function() {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error!', text: 'Gagal menghubungi server.' });
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
            $row.find('.btn-cancel-inline').prop('disabled', false);
        });
    });

    // --- Selesaikan Jasa: checkbox toggles qty (tetap terkirim = 0 supaya array index selaras) ---
    $('#check-all-service').on('change', function() {
        $('.service-check').prop('checked', $(this).is(':checked')).trigger('change');
    });
    $(document).on('change', '.service-check', function() {
        const $row = $(this).closest('tr');
        const $qtyInput = $row.find('.service-qty-input');
        if ($(this).is(':checked')) {
            $qtyInput.prop('readonly', false).removeClass('bg-light').val($qtyInput.data('max'));
        } else {
            $qtyInput.prop('readonly', true).addClass('bg-light').val(0);
        }
    });

}); // End $(function() { ... })
</script>

<style>
.select2-container--bootstrap4 .select2-selection--single { height: calc(1.5em + .5rem + 2px) !important; padding: .25rem .5rem !important; font-size: .875rem; line-height: 1.5; border-radius: .2rem; border: 1px solid #d1d3e2;}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .5rem) !important; }
.select2-container--bootstrap4 .select2-dropdown { z-index: 1051 !important; border-color: #d1d3e2;}
.select2-container { width: 100% !important; }

.new-item-row input, .new-item-row select { font-size: 0.8rem; height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem;}
.new-item-row .btn-xs { padding: .15rem .4rem; font-size: .7rem; line-height: 1.2;}
.saving { opacity: 0.6; pointer-events: none; }

.new-item-row input[readonly] { background-color: #e9ecef !important; border: 1px solid #d1d3e2 !important; box-shadow: none !important; cursor: default; color: #6e707e; }
.new-item-row .item-code-display, .new-item-row .item-unit-display { text-align: center; }

input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

tr .edit-input { font-size: 0.8rem; height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; min-width: 70px; }
tr .cell-qty .edit-input { width: 90px; }
tr .cell-price .edit-input { width: 110px; }
tr .cell-desc .edit-input { width: 100%; }
tr.editing { background-color: #fffbea !important; }
td.price-zero { background-color: #fff3cd; }
</style>
