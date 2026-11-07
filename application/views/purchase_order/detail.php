<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-file-invoice-dollar text-primary mr-2"></i><?= htmlspecialchars($title ?? 'Detail PO'); ?>
            <?php if (isset($header) && $header): ?>
                <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->po_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
        <?php if (isset($header) && $header): // Hanya tampilkan jika header ada ?>
            <?php if ($header->status == 'Draft' && check_access('purchase_order', 'submit')): ?>
                <a href="<?= base_url('purchase_order/submit_po/'.$header->id); ?>" class="btn btn-info btn-sm mr-1 btn-submit" data-message="<?= lang_line('po_confirm_submit', 'Yakin ingin submit PO ini? Setelah disubmit, header tidak bisa diedit.'); ?>" data-icon="info" data-confirm-text="Ya, Submit"> <i class="fas fa-paper-plane fa-sm"></i> <?= lang_line('po_submit', 'Submit PO'); ?> </a>
            <?php endif; ?>
            <?php if ($header->status == 'Submitted'): ?>
                <span class="badge badge-info mr-2 p-2"><i class="fas fa-clock mr-1"></i> <?= lang_line('po_waiting_approval', 'Menunggu PO Approval'); ?></span>
            <?php endif; ?>
             <?php if ($header->status == 'Confirmed' && check_access('purchase_order', 'edit')): ?>
                 <a href="<?= base_url('purchase_order/unconfirm_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="<?= lang_line('po_confirm_unconfirm', 'Yakin ingin membatalkan konfirmasi PO ini?'); ?>" data-text="<?= lang_line('po_confirm_unconfirm_text', 'Pastikan belum ada penerimaan barang.'); ?>" data-icon="warning" data-confirm-text="Ya, Batalkan Konfirmasi"> <i class="fas fa-times-circle fa-sm"></i> <?= lang_line('po_unconfirm', 'Unconfirm'); ?> </a>
            <?php endif; ?>
             <?php if (in_array($header->status, ['Draft', 'Submitted', 'Confirmed']) && check_access('purchase_order', 'delete')): ?>
                 <a href="<?= base_url('purchase_order/cancel_po/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1 btn-confirm" data-message="<?= lang_line('po_confirm_cancel', 'Yakin ingin membatalkan (cancel) PO ini?'); ?>" data-text="<?= lang_line('po_confirm_cancel_text', 'Aksi ini tidak bisa diurungkan.'); ?>" data-icon="error" data-confirm-text="Ya, Batalkan PO"> <i class="fas fa-ban fa-sm"></i> <?= lang_line('po_cancel_po', 'Cancel PO'); ?> </a>
            <?php endif; ?>
             <?php if (in_array($header->status, ['Draft', 'Submitted', 'Confirmed']) && is_admin()): ?>
                 <button type="button" class="btn btn-outline-danger btn-sm mr-1" id="btn-batalkan-po"
                     data-return-url="<?= base_url('purchase_order/return_to_draft/'.$header->id); ?>"
                     data-delete-url="<?= base_url('purchase_order/force_delete/'.$header->id); ?>"
                     data-status="<?= htmlspecialchars($header->status); ?>"
                     data-po-number="<?= htmlspecialchars($header->po_number); ?>">
                     <i class="fas fa-user-shield fa-sm"></i> <?= lang_line('po_force_action', 'Force Return/Delete'); ?>
                 </button>
            <?php endif; ?>
            <?php if (!empty($can_edit_header)): ?>
                 <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail"> <i class="fas fa-edit fa-sm"></i> <?= lang_line('po_edit', 'Edit Header'); ?><?= !empty($admin_edit_mode) ? ' (Admin)' : ''; ?> </button>
             <?php endif; ?>
             
            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                 <a href="<?= base_url('purchase_order/detail/'.$header->id); ?>?admin_edit=1" class="btn btn-outline-warning btn-sm mr-1 btn-confirm no-loader"
                    data-message="Buka kunci edit PO ini?"
                    data-text="PO sudah <?= htmlspecialchars($header->status); ?>. Membuka kunci memungkinkan Anda mengubahnya sebagai Admin. Approval TIDAK direset."
                    data-icon="warning" data-confirm-text="Ya, Buka Kunci">
                     <i class="fas fa-lock fa-sm"></i> Buka Kunci Edit (Admin)
                 </a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                 <a href="<?= base_url('purchase_order/detail/'.$header->id); ?>" class="btn btn-warning btn-sm mr-1 no-loader">
                     <i class="fas fa-lock-open fa-sm"></i> Selesai Edit (Kunci Lagi)
                 </a>
            <?php endif; ?>
            <a href="<?= base_url('purchase_order/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1 no-loader"> <i class="fas fa-file-excel fa-sm"></i> <?= lang_line('po_export_excel', 'Excel'); ?> </a>
            <a href="<?= base_url('purchase_order/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1 no-loader"> <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('po_export_pdf', 'PDF'); ?> </a>
        <?php endif; // End check header exists ?>
            <a href="<?= base_url('purchase_order'); ?>" class="btn btn-secondary btn-sm btn-load"> <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('po_back_to_list', 'Kembali ke Daftar'); ?> </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?> <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?> <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>

    <script>
        // Display flashdata as SweetAlert
        const flashSuccess = "<?= addslashes($this->session->flashdata('success') ?? ''); ?>";
        const flashError = "<?= addslashes($this->session->flashdata('error') ?? ''); ?>";
        const flashWarning = "<?= addslashes($this->session->flashdata('warning') ?? ''); ?>";
        
        if (flashSuccess && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: '<?= lang_line('po_success', 'Berhasil!'); ?>', text: flashSuccess, timer: 2000, showConfirmButton: false });
        }
        if (flashError && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: '<?= lang_line('po_failed', 'Gagal!'); ?>', text: flashError });
        }
        if (flashWarning && typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: '<?= lang_line('po_warning', 'Peringatan!'); ?>', text: flashWarning });
        }
    </script>
    <?php if ($this->session->flashdata('error')): ?> <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?> <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div> <?php endif; ?>

    <?php if (isset($header) && $header): ?>
     <div class="card shadow mb-4">
        <div class="card-header py-3"> <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('po_header_info', 'Informasi Purchase Order'); ?></h6> </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong><?= lang_line('po_number', 'Nomor PO'); ?></strong></td><td style="width:2%">:</td><td><?= htmlspecialchars($header->po_number); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_supplier', 'Supplier'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->nama_supplier); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_supplier_address', 'Alamat Supplier'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->alamat ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_supplier_phone', 'Telepon Supplier'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->telepon ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_created_by', 'Dibuat Oleh'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->creator_name ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_approved_by', 'Dikonfirmasi Oleh'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->confirmer_name ?? '-'); ?> <?= $header->confirmed_at ? '('.date('d M Y H:i', strtotime($header->confirmed_at)).')' : ''; ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                     <table class="table table-sm table-borderless mb-0">
                         <tr><td style="width:35%;"><strong><?= lang_line('po_date', 'Tanggal PO'); ?></strong></td><td style="width:2%">:</td><td><?= date('d M Y', strtotime($header->po_date)); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_status', 'Status'); ?></strong></td><td>:</td><td><?php $color = $status_colors[$header->status] ?? 'secondary'; ?><span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($header->status); ?></span></td></tr>
                         <tr><td><strong><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('po_currency', 'Mata Uang'); ?></strong></td><td>:</td><td><?= htmlspecialchars($header->currency_desc ?? '-'); ?> <?= $header->currency_code != 'IDR' ? '('.lang_line('po_exchange_rate', 'Kurs').': '.number_format((float)$header->exchange_rate, 4).')' : ''; ?></td></tr>
                         <tr><td><strong><?= lang_line('po_has_ppn_label', 'PPN'); ?></strong></td><td>:</td><td><?php
                            if (!empty($header->has_ppn)) {
                                echo '<span class="badge badge-success">'
                                   . htmlspecialchars(po_ppn_label($header->ppn_rate ?? 0, !empty($header->ppn_inclusive)))
                                   . '</span>';
                            } else {
                                echo '<span class="badge badge-secondary">' . lang_line('po_no', 'Tidak') . '</span>';
                            }
                         ?></td></tr>
                    </table>
                </div>
            </div>
             <?php if (!empty($source_prs)): ?>
             <hr class="mt-2 mb-2">
             <p class="mb-0 small">
                 <strong>Dari Purchase Request:</strong>
                 <?php foreach ($source_prs as $spr): ?>
                     <a href="<?= base_url('purchase_request/detail/' . $spr->id); ?>" class="badge badge-light border mr-1"
                        title="<?= htmlspecialchars($spr->purpose ?? ''); ?>">
                         <i class="fas fa-file-alt"></i> <?= htmlspecialchars($spr->pr_number); ?>
                         <?php if (isset($spr->line_count)): ?><span class="text-muted">(<?= (int) $spr->line_count; ?> baris)</span><?php endif; ?>
                     </a>
                 <?php endforeach; ?>
             </p>
             <?php endif; ?>

             <?php if(!empty($header->remarks)): ?> <hr class="mt-2 mb-2"><p class="mb-0 small"><strong><?= lang_line('po_remarks', 'Catatan / Remarks'); ?>:</strong><br> <?= nl2br(htmlspecialchars($header->remarks)); ?></p><?php endif; ?>
        </div>
    </div>

    <?php
        // Riwayat Approval + Back to Draft 2-arah/reply (ERP Revision Notes,
        // [[erp_revision_cross_cutting]]) -- requestor SEBELUMNYA tidak punya
        // visibilitas sama sekali ke approval_logs/return_reason di halaman
        // detail PO miliknya sendiri (cuma ada di purchase_order_approval/detail.php,
        // yg tidak ke-link dari sini) -- ditambah supaya requestor bisa lihat
        // alasan Back to Draft & balas langsung dari halaman ini.
    ?>
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
            <form method="POST" action="<?= base_url('purchase_order_approval/reply/' . $header->id); ?>" class="mt-3 pt-3 border-top">
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
        <div class="alert alert-danger"><?= lang_line('po_error_load_header', 'Gagal memuat data header PO. Silakan kembali ke daftar.'); ?></div>
    <?php endif; // End check header exists ?>


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
            <i class="fas fa-lock mr-1"></i> Baris item terkunci: PO ini sudah ada penerimaan barang (GRN).
            Hanya data header yang masih bisa diubah.
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($header) && $header && !empty($can_edit_item) && !empty($open_contracts)): ?>
    <div class="card shadow mb-3 border-left-success">
        <div class="card-header py-2 bg-light">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-magic mr-1"></i> <?= lang_line('po_load_bom_title', 'Muat Item dari BOM Sales Order Number'); ?></h6>
        </div>
        <div class="card-body py-2">
            <div class="form-inline gap-2">
                <select id="bom-sc-select" class="form-control form-control-sm mr-2" style="min-width:260px;">
                    <option value=""><?= lang_line('po_select_sales_contract', '-- Pilih Sales Order Number --'); ?></option>
                    <?php foreach ($open_contracts as $sc): ?>
                        <option value="<?= $sc->id ?>"><?= htmlspecialchars($sc->sc_number) ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="btn-load-bom" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-download mr-1"></i> <?= lang_line('po_load_bom_button', 'Load BOM'); ?>
                </button>
                <span id="bom-load-result" class="small"></span>
            </div>
            <small class="text-muted d-block mt-1"><?= lang_line('po_load_bom_help', 'Qty otomatis = qty style SC × kebutuhan material di BOM. Harga diisi Purchasing.'); ?></small>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($header) && $header): // Hanya tampilkan jika header ada ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('po_items', 'Detail Item Purchase Order'); ?></h6>
            <?php if (!empty($can_edit_item)): ?>
            <div class="btn-group">
                <button class="btn btn-info btn-sm" type="button" data-toggle="modal" data-target="#pullPrModal">
                    <i class="fas fa-file-import fa-sm"></i> Tarik dari PR
                </button>
            <button class="btn btn-success btn-sm" type="button" id="add-item-row-btn">
                <i class="fas fa-plus fa-sm"></i> <?= lang_line('po_add_item', 'Tambah Baris Item'); ?>
            </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <style>
                    /* table-layout fixed: lebar kolom mengikuti <th>, bukan konten terpanjang.
                       overflow-wrap memaksa URL panjang tanpa spasi patah di dalam kolomnya. */
                    #po-items-table { table-layout: fixed; min-width: 1000px; }
                    #po-items-table td { overflow-wrap: break-word; word-wrap: break-word; }
                </style>
                <table class="table table-bordered table-sm mb-0 table-hover" id="po-items-table">
                    <thead class="thead-light text-center">
                        <tr>
                            <th style="width: 3%;"><?= lang_line('po_no', 'No'); ?></th>
                            <th style="width: 8%;"><?= lang_line('po_item_code', 'Kode Item'); ?></th>
                            <th style="width: 14%;"><?= lang_line('po_item_name', 'Nama Item'); ?></th>
                            <th style="width: 6%;"><?= lang_line('po_unit', 'Satuan'); ?></th>
                            <th style="width: 26%;"><?= lang_line('po_description', 'Deskripsi Tambahan'); ?></th>
                            <th style="width: 10%;"><?= lang_line('po_sales_contract', 'Sales Order Number'); ?></th>
                            <th style="width: 8%;"><?= lang_line('po_qty', 'Qty'); ?></th>
                            <th style="width: 10%;"><?= lang_line('po_base_price', 'Harga Satuan'); ?> (<?= $header->currency_code; ?>)</th>
                            <th style="width: 10%;"><?= lang_line('po_total', 'Total'); ?> (<?= $header->currency_code; ?>)</th>
                            <th style="width: 5%;"><?= lang_line('po_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="po-item-details-body">
                        <?php
                             $grand_total_base = 0; $grand_total_local = 0; $no = 1;
                             // $can_edit_item dikirim controller (sudah memperhitungkan override Admin
                             // dan kunci penerimaan barang); fallback false kalau view dipanggil tanpanya.
                             $can_edit_item = !empty($can_edit_item);
                             if(!empty($details)):
                                 foreach($details as $d):
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
                            <td class="text-center"><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
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
                                    <a href="<?= base_url('purchase_order/delete_item/'.$d->po_header_id.'/'.$d->id) . (!empty($admin_edit_mode) ? '?admin_edit=1' : ''); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus" data-message="Yakin hapus item <?= htmlspecialchars($d->item_name ?? 'ini'); ?>?"><i class="fas fa-trash"></i></a>
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
                            <tr id="no-item-row"><td colspan="10" class="text-center text-muted py-5"><?= lang_line('po_no_items', 'Belum ada item detail. Silakan tambahkan item.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <tfoot id="po-item-details-footer">
                         <?php
                            // Perhitungan PPN dipusatkan di po_ppn_breakdown() (ppn_helper),
                            // dipakai juga oleh cetak PDF, export Excel & daftar PO. Dulu di
                            // sini dipatok 0.11/1.11 walau ppn_rate sudah ada isinya.
                            // Kalau PPN INCLUDE, Subtotal yang ditampilkan adalah DPP --
                            // harga baris sudah mengandung PPN, jadi Subtotal + PPN = Total.
                            $ppn_calc   = po_ppn_from_header($header, $grand_total_base);
                            $ppn_base   = $ppn_calc['ppn'];
                            $subtotal_base = $ppn_calc['dpp'];
                            $grand_total_final_base = $ppn_calc['total'];

                            $ppn_calc_local = po_ppn_from_header($header, $grand_total_local);
                            $grand_total_final_local = $ppn_calc_local['total'];
                         ?>
                        <tr class="bg-light font-weight-bold"> <td colspan="8" class="text-right"><?= lang_line('po_subtotal', 'Subtotal'); ?> (<?= $header->currency_code; ?>)</td> <td class="text-right" id="subtotal-base"><?= number_format($subtotal_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if($ppn_calc['applied']): // baris PPN hanya kalau benar-benar dihitung ?>
                            <tr class="bg-light"> <td colspan="8" class="text-right"><?= htmlspecialchars(po_ppn_label($ppn_calc['rate'], $ppn_calc['inclusive'])); ?> (<?= $header->currency_code; ?>)</td> <td class="text-right" id="ppn-base"><?= number_format($ppn_base, 2, '.', ','); ?></td> <td></td> </tr> 
                         <?php endif; ?>
                          <tr class="bg-primary text-white font-weight-bold"> <td colspan="8" class="text-right"><?= lang_line('po_grand_total', 'Grand Total'); ?> (<?= $header->currency_code; ?>)</td> <td class="text-right" id="grandtotal-base"><?= number_format($grand_total_final_base, 2, '.', ','); ?></td> <td></td> </tr>
                         <?php if($header->currency_code != 'IDR'): ?> <tr class="bg-info text-white font-weight-bold"> <td colspan="8" class="text-right"><?= lang_line('po_estimated_grand_total_idr', 'Estimasi Grand Total (IDR)'); ?></td> <td class="text-right" id="grandtotal-local"><?= number_format($grand_total_final_local, 2, '.', ','); ?></td> <td></td> </tr> <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; // End check header exists ?>

</div>

<?php if (isset($header) && $header && !empty($can_edit_header)): ?>
<div class="modal fade" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order/update_header/'.$header->id); ?>" class="needs-validation" novalidate> 
             <div class="modal-content border-0 shadow">
                 <div class="modal-header bg-warning text-dark"> <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('po_edit', 'Edit Header PO'); ?></h5> <button type="button" class="close text-dark" data-dismiss="modal">&times;</button> </div>
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
                                 <label for="edit_po_date"><?= lang_line('po_date', 'Tanggal PO'); ?> <span class="text-danger">*</span></label>
                                 <input type="date" class="form-control form-control-sm" id="edit_po_date" name="po_date" value="<?= date('Y-m-d', strtotime($header->po_date)); ?>" required>
                             </div>
                         </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                 <label for="edit_supplier_id"><?= lang_line('po_supplier', 'Supplier'); ?> <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($header->nama_supplier); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                 <input type="hidden" name="supplier_id" value="<?= $header->supplier_id; ?>">
                                 <small class="form-text text-muted"><?= lang_line('po_supplier_note', 'Supplier sudah dipilih saat pembuatan PO'); ?></small>
                             </div>
                         </div>
                     </div>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_shipping_mode"><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?> <span class="text-danger">*</span></label>
                                <select name="shipping_mode_code" id="edit_shipping_mode" class="form-control form-control-sm select2-modal" required style="width: 100%;">
                                    <option value=""><?= lang_line('po_shipping_mode_select', '-- Pilih Mode --'); ?></option>
                                    <?php foreach(($shipping_modes ?? []) as $mode): ?>
                                        <option value="<?= $mode->code; ?>" <?= (isset($header->shipping_mode_code) && $header->shipping_mode_code == $mode->code) ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_payment_mode"><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?> <span class="text-danger">*</span></label>
                                <select name="payment_mode_code" id="edit_payment_mode" class="form-control form-control-sm select2-modal" required style="width: 100%;">
                                    <option value=""><?= lang_line('po_payment_mode_select', '-- Pilih Mode --'); ?></option>
                                    <?php foreach(($payment_modes ?? []) as $mode): ?>
                                        <option value="<?= $mode->code; ?>" <?= (isset($header->payment_mode_code) && $header->payment_mode_code == $mode->code) ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                     </div>
                     <div class="row">
                         <div class="col-md-6">
                             <div class="form-group">
                                 <label for="edit_currency_code"><?= lang_line('po_currency', 'Mata Uang'); ?> <span class="text-danger">*</span></label>
                                 <select class="form-control form-control-sm" id="edit_currency_code" name="currency_code" required>
                                     <option value="IDR" <?= ($header->currency_code == 'IDR') ? 'selected' : ''; ?>>Rupiah (IDR)</option>
                                     <option value="USD" <?= ($header->currency_code == 'USD') ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                     <option value="EUR" <?= ($header->currency_code == 'EUR') ? 'selected' : ''; ?>>Euro (EUR)</option>
                                     <option value="CNY" <?= ($header->currency_code == 'CNY') ? 'selected' : ''; ?>>Yuan (CNY)</option>
                                     <option value="JPY" <?= ($header->currency_code == 'JPY') ? 'selected' : ''; ?>>Yen (JPY)</option>
                                     <option value="SGD" <?= ($header->currency_code == 'SGD') ? 'selected' : ''; ?>>Singapore Dollar (SGD)</option>
                                 </select>
                             </div>
                         </div>
                         <div class="col-md-6" id="edit_exchange_rate_div" style="<?= ($header->currency_code == 'IDR') ? 'display:none;' : ''; ?>">
                             <div class="form-group">
                                 <label for="edit_exchange_rate"><?= lang_line('po_exchange_rate', 'Kurs'); ?> <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control form-control-sm autonumeric-4dec-edit" id="edit_exchange_rate" name="exchange_rate" value="<?= number_format((float)$header->exchange_rate, 4, '.', ''); ?>" <?= ($header->currency_code != 'IDR') ? 'required' : ''; ?>>
                                 <small class="form-text text-muted"><?= lang_line('po_exchange_rate_help', 'Contoh: 1 USD = 15000.0000 IDR'); ?></small>
                             </div>
                         </div>
                     </div>
                     <div class="row">
                    <?php /*
                        PPN tiga langkah (klien 2026-10-02): centang kena PPN dulu,
                        lalu pilih harga sudah termasuk PPN atau belum, lalu isi
                        persennya -- tidak dipatok 11% lagi.
                          EXCLUDE : PPN dihitung dari subtotal, lalu ditambahkan
                          INCLUDE : PPN diambil dari total (harga sudah mengandungnya)
                    */ ?>
                    <div class="col-md-12">
                        <div class="border rounded p-2 mb-2 bg-light">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input ppn-toggle" id="edit_has_ppn" name="has_ppn" value="1" <?= $header->has_ppn ? 'checked' : '' ?>>
                                <label class="form-check-label font-weight-bold" for="edit_has_ppn"><?= lang_line('po_has_ppn_check', 'Kena PPN'); ?></label>
                            </div>
                            <div class="form-row ppn-detail" style="<?= $header->has_ppn ? '' : 'display:none;' ?>">
                                <div class="col-md-6 form-group mb-0">
                                    <label class="small mb-1"><?= lang_line('po_ppn_mode', 'Perhitungan PPN'); ?></label>
                                    <select name="ppn_inclusive" class="form-control form-control-sm">
                                        <option value="0" <?= empty($header->ppn_inclusive) ? 'selected' : '' ?>><?= lang_line('po_ppn_exclude', 'Exclude — harga belum termasuk PPN (PPN ditambahkan)'); ?></option>
                                        <option value="1" <?= !empty($header->ppn_inclusive) ? 'selected' : '' ?>><?= lang_line('po_ppn_include', 'Include — harga sudah termasuk PPN (PPN diambil dari total)'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label class="small mb-1"><?= lang_line('po_ppn_rate', 'Persen PPN'); ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="ppn_rate" class="form-control text-right" step="0.01" min="0" max="100" value="<?= htmlspecialchars((float) ($header->ppn_rate ?: 11)) ?>">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     </div>
                     <div class="row">
                         <div class="col-md-12">
                             <div class="form-group">
                                 <label for="edit_remarks"><?= lang_line('po_remarks', 'Catatan / Remarks'); ?></label>
                                 <textarea class="form-control form-control-sm" id="edit_remarks" name="remarks" rows="3" placeholder="<?= lang_line('po_optional', 'Opsional...'); ?>"><?= htmlspecialchars($header->remarks ?? ''); ?></textarea>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="modal-footer"> <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('po_save', 'Simpan Perubahan'); ?></button> <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button> </div>
             </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (isset($header) && $header): // Hanya render template jika header ada ?>
<template id="new-item-row-template">
    <tr class="new-item-row">
        <td class="text-center align-middle">*</td>
        <td><input type="text" class="form-control form-control-sm item-code-display" readonly style="background-color: #e9ecef;"></td> 
        <td><select name="item_id" class="form-control form-control-sm select2-inline-item" required style="width: 100%;"><option value=""><?= lang_line('po_search_item', '-- Cari Nama Item --'); ?></option></select></td>
        <td><input type="text" class="form-control form-control-sm item-unit-display text-center" readonly style="background-color: #e9ecef;"></td> 
        <td><input type="text" name="description" class="form-control form-control-sm" placeholder="<?= lang_line('po_optional', 'Opsional...'); ?>"></td>
        <td><select name="sales_contract_id" class="form-control form-control-sm select2-inline-sc" style="width: 100%;"><option value=""><?= lang_line('po_optional', '-- Opsional --'); ?></option><?php foreach(($open_contracts ?? []) as $sc): ?><option value="<?= $sc->id; ?>"><?= htmlspecialchars($sc->sc_number); ?></option><?php endforeach; ?></select></td>
        <td><input type="number" step="0.0001" name="qty" class="form-control form-control-sm text-right inline-qty" required min="0.0001" placeholder="0.0000"></td> 
        <td><input type="text" name="base_price" class="form-control form-control-sm autonumeric-2dec-inline text-right inline-price" required value="0.0000"></td>
        <td class="text-right align-middle inline-total">0.00</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-success btn-xs save-inline-item" title="<?= lang_line('po_save_item', 'Simpan Item Ini'); ?>"> <i class="fas fa-check"></i> </button>
            <button type="button" class="btn btn-danger btn-xs cancel-inline-item" title="<?= lang_line('po_cancel', 'Batal'); ?>"> <i class="fas fa-times"></i> </button>
        </td>
    </tr>
</template>
<?php endif; // End check header exists for template ?>

<?php $this->load->view('templates/footer'); // Load footer utama ?>

<?php
// Modal "Tarik Item dari PR" — markup + JS-nya dipakai bersama keempat varian PO.
if (isset($header) && $header && !empty($can_edit_item)) {
    $this->load->view('templates/partials/pull_pr_modal', [
        'header'        => $header,
        'pull_pr_route' => 'purchase_order',
    ]);
}
?>

<script>
// Pastikan script ini dijalankan setelah jQuery, Select2, AutoNumeric dimuat
$(function() { 
    
    // --- Variabel Global Halaman ---
    const currentStatus = <?= isset($header->status) ? json_encode($header->status) : '"unknown"'; ?>;
    const poHeaderId = <?= isset($header->id) ? $header->id : 0; ?>;
    // Dikirim controller: sudah memperhitungkan status, izin role, override Admin,
    // dan apakah baris terkunci penerimaan barang.
    const canEditItem = <?= !empty($can_edit_item) ? 'true' : 'false'; ?>;
    const adminEditFlag = <?= !empty($admin_edit_mode) ? '1' : '0'; ?>;

    // Language strings for JavaScript
    const LANG = {
        selectSalesContractFirst: '<?= lang_line('po_load_bom_select_first', 'Pilih Sales Order Number terlebih dahulu.'); ?>',
        loading: '<?= lang_line('po_load_bom_loading', 'Loading...'); ?>',
        serverError: '<?= lang_line('po_load_bom_server_error', 'Gagal menghubungi server.'); ?>',
        qtyZeroError: '<?= lang_line('po_qty_zero_error', 'Qty harus lebih dari 0'); ?>',
        priceNegativeError: '<?= lang_line('po_price_negative_error', 'Harga tidak boleh negatif'); ?>',
        itemSaveSuccess: '<?= lang_line('po_item_save_success', 'Item berhasil disimpan.'); ?>',
        itemSaveFailed: '<?= lang_line('po_item_save_failed', 'Gagal!'); ?>',
        deleteItemConfirm: '<?= lang_line('po_delete_item_confirm', 'Yakin hapus item'); ?>',
        loadBomButton: '<?= lang_line('po_load_bom_button', 'Load BOM'); ?>'
    };

    // HAPUS SEMUA REFERENSI CSRF
    console.log("[PO Detail] Document Ready. Status:", currentStatus, "ID:", poHeaderId);
    console.log("[PO Detail] CSRF Protection is DISABLED on this page's script.");

    // --- Inisialisasi Modal Edit Header ---
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
                console.log("[PO Detail] AutoNumeric & Currency Logic for Edit Modal Initialized.");

                    // Inisialisasi Select2 untuk dropdown di Edit Modal (shipping/payment)
                    try {
                        const $selectModalElements = $editModal.find('.select2-modal');
                        if ($selectModalElements.length && typeof $.fn.select2 === 'function') {
                            $selectModalElements.each(function() {
                                if ($(this).data('select2')) $(this).select2('destroy');
                                $(this).select2({ dropdownParent: $editModal, theme: 'bootstrap4', width: '100%' });
                            });
                            console.log('[PO Detail] Select2 initialized for edit modal fields.');
                        } else {
                            console.log('[PO Detail] Select2 not found or no elements to init in edit modal.');
                        }
                    } catch (e) { console.warn('[PO Detail] Error initializing Select2 in edit modal', e); }

                // Bootstrap validation for modal form + AJAX submit
                $editModal.find('form.needs-validation').on('submit', function(event) {
                    event.preventDefault(); // Prevent default form submission
                    event.stopPropagation();
                    
                    if (this.checkValidity() === false) { 
                        $(this).addClass('was-validated');
                        console.warn("[PO Detail] Form validation failed");
                        return false;
                    }
                    
                    // Form valid, submit via traditional form (since backend handles redirect)
                    console.log("[PO Detail] Form validation passed, submitting...");
                    $(this).addClass('was-validated');
                    
                    // Disable submit button to prevent double submission
                    const $submitBtn = $(this).find('button[type="submit"]');
                    const originalHtml = $submitBtn.html();
                    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                    
                    // Submit form after short delay
                    setTimeout(() => {
                        this.submit();
                    }, 200);
                });
           } catch(err) { console.error("[PO Detail] Error initializing AN/Currency for Edit Modal:", err); }
       } else { console.log("[PO Detail] AutoNumeric input not found or AutoNumeric lib not loaded."); }
    } else {
        console.log("[PO Detail] Edit modal not found, skipping init.");
    }

    // --- LOGIKA TAMBAH ITEM INLINE ---
    // Digate canEditItem, bukan status Draft: PO Confirmed yang dibuka Admin juga
    // perlu handler ini terpasang, kalau tidak tombol Tambah Item tidak bereaksi.
    if (canEditItem) {
        console.log("[PO Detail] Item editable, enabling inline add."); 
        const $itemTableBody = $('#po-item-details-body'); 
        const $itemTableFooter = $('#po-item-details-footer'); // Cache footer
        const templateNode = document.getElementById('new-item-row-template'); 
        let $noItemRow = $('#no-item-row'); 
        let inlineAutoNumericInstances = []; 

        if(!templateNode || !templateNode.content) { console.error("CRITICAL: Template #new-item-row-template not found!"); return; }
        console.log("[PO Detail] Template found:", templateNode);

        // --- Fungsi Inisialisasi Input di Baris Baru ---
        function initializeNewRowInputs($rowElement) { 
            console.log("[PO Detail] Initializing inputs for new row:", $rowElement[0]); 
            
            inlineAutoNumericInstances = inlineAutoNumericInstances.filter(instance => {
                if ($.contains($rowElement[0], instance.domElement)) {
                    try { instance.detach(); console.log("[PO Detail] Detached old AN."); } catch(e){ console.warn("[PO Detail] AN detach error", e); } return false; 
                } return true;
            });

            // 1. Inisialisasi Select2 Item Search
            const $itemSelectElement = $rowElement.find('.select2-inline-item'); 
            if (!$itemSelectElement.length) { console.error("[PO Detail] Select2 target '.select2-inline-item' NOT FOUND!"); return; }
            if (typeof $.fn.select2 === 'function') {
                 try {
                    if ($itemSelectElement.data('select2')) { $itemSelectElement.select2('destroy'); }
                    $itemSelectElement.select2({ 
                        placeholder: '<?= lang_line('po_search_item', '-- Cari Nama Item --'); ?>', minimumInputLength: 2, theme: 'bootstrap4', 
                        ajax: {
                            url: '<?= base_url("purchase_order/search_items"); ?>', dataType: 'json', delay: 400, 
                            data: function (params) { 
                                let query = { q: params.term };
                                console.log("[PO Detail] Select2 AJAX Data Sent:", query); 
                                return query; 
                            },
                            processResults: function (data, params) { 
                                console.log("[PO Detail] Select2 AJAX Response:", data); 
                                if (data && Array.isArray(data.results)) { return { results: data.results }; } 
                                else { console.error("[PO Detail] Select2: Invalid data structure:", data); return { results: [] }; }
                            },
                             error: function(jqXHR, status, error) { console.error("[PO Detail] Select2 AJAX Error:", status, error, jqXHR.status); }
                        },
                        dropdownParent: $(document.body) 
                    }).on('select2:select', function (e) {
                         var data = e.params.data; console.log("[PO Detail] Select2 Item Selected:", data); 
                         const $currentRow = $(this).closest('tr'); 
                         $currentRow.find('.item-code-display').val(data.code || '-'); 
                         $currentRow.find('.item-unit-display').val(data.unit || '-'); 
                         $currentRow.find('.inline-qty').focus();
                    }).on('select2:open', function (e) {
                         setTimeout(() => { document.querySelector('.select2-search__field')?.focus(); }, 50); 
                    });
                     console.log("[PO Detail] Select2 AJAX Initialized successfully."); 
                 } catch (err) { console.error("[PO Detail] Error during Select2 initialization:", err); }
            } else { console.error("[PO Detail] CRITICAL: jQuery.fn.select2 is NOT defined!"); }

            // 2. Inisialisasi Select2 Sales Order Number 
            const $scSelect = $rowElement.find('.select2-inline-sc'); 
            if ($scSelect.length > 0 && typeof $.fn.select2 === 'function') { /* ... (init SC) ... */ }
            else if ($scSelect.length > 0) { console.error("[PO Detail] Cannot initialize Select2 SC - function not found."); }

            // 3. Inisialisasi AutoNumeric Harga
            const priceInputElement = $rowElement.find('.autonumeric-2dec-inline')[0]; 
             if (priceInputElement && typeof AutoNumeric === 'function') { /* ... (init AN) ... */ }
             else if (priceInputElement) { console.error("[PO Detail] CRITICAL: AutoNumeric function is NOT defined!"); }
             else { console.warn("[PO Detail] AutoNumeric target not found."); }
        } // Akhir initializeNewRowInputs

        // --- Fungsi Hitung Total Baris ---
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

        // --- Event Listener Tombol "Tambah Baris Item" ---
        $('#add-item-row-btn').off('click').on('click', function() {
            console.log("[PO Detail] Add item row button clicked."); 
            $noItemRow = $('#no-item-row'); 
            if ($noItemRow.length) { console.log("[PO Detail] Removing 'no item' row."); $noItemRow.remove(); }
            if ($itemTableBody.find('.new-item-row').length > 0) {
                 alert('<?= lang_line('po_alert_finish_previous', 'Selesaikan atau batalkan penambahan item sebelumnya.'); ?>'); console.warn("[PO Detail] Add row blocked."); return;
            }
             if (!templateNode || !templateNode.content) { console.error("[PO Detail] Template node invalid!"); return; } 
             
             try {
                 const newRowFragment = templateNode.content.cloneNode(true);
                 // SELALU APPEND KE TBODY
                 $itemTableBody.append(newRowFragment); 
                 console.log("[PO Detail] Template appended to <tbody>.");
                 
                 const $appendedRow = $itemTableBody.find('tr.new-item-row').last(); 
                 
                 if ($appendedRow.length) {
                     console.log("[PO Detail] Row appended. Initializing inputs after delay...");
                     setTimeout(() => {
                          initializeNewRowInputs($appendedRow); 
                          $appendedRow.find('.select2-inline-item').select2('open');
                     }, 50); 
                     
                     $appendedRow.find('.inline-qty, .autonumeric-2dec-inline').on('input change keyup focusout', function() { 
                         calculateRowTotal($(this).closest('tr'));
                     });
                 } else { 
                     // Ini tidak seharusnya terjadi jika append ke tbody berhasil
                     console.error("[PO Detail] CRITICAL: New row not found after append!"); 
                 }
             } catch(err) { console.error("[PO Detail] Error cloning/appending template:", err); alert("Gagal menambahkan baris baru."); }
        });

        // --- Event Listener Tombol Save (✅) ---
        $itemTableBody.off('click', '.save-inline-item').on('click', '.save-inline-item', function() { 
             console.log("[PO Detail] Save button clicked."); 
             const button = $(this); 
             const $row = button.closest('tr.new-item-row'); // PERBAIKI: Definisikan $row
             if (!$row.length) { console.error("[PO Detail] Could not find parent row for save."); return; }
            
             const $itemSelect = $row.find('select[name="item_id"]'); 
             const $qtyInput = $row.find('input[name="qty"]');
             const $priceInput = $row.find('input[name="base_price"]');
             const $scSelect = $row.find('select[name="sales_contract_id"]');
             const $descInput = $row.find('input[name="description"]');
             
             const itemIdValue = $itemSelect.val();
             const qtyValue = parseFloat($qtyInput.val());
             let basePriceNumeric = 0;
             const anPriceInstance = AutoNumeric.getAutoNumericElement($priceInput[0]);
             if (anPriceInstance) { basePriceNumeric = anPriceInstance.getNumber(); } 
             else { basePriceNumeric = parseFloat(($priceInput.val() || '0').replace(/,/g, '')); } 
             
             // Validasi
             if (!itemIdValue) { alert('<?= lang_line('po_alert_select_item', 'Harap pilih Item.'); ?>'); if ($itemSelect.data('select2')) $itemSelect.select2('open'); return; }
             if (isNaN(qtyValue) || qtyValue <= 0) { alert('<?= lang_line('po_alert_fill_qty', 'Harap isi Qty (> 0).'); ?>'); $qtyInput.focus(); return; }
             if (isNaN(basePriceNumeric) || basePriceNumeric < 0) { alert('<?= lang_line('po_alert_price_negative', 'Harga Satuan tidak boleh negatif.'); ?>'); $priceInput.focus(); return; }
             console.log("[PO Detail] Validation passed.");

             // Data
             const dataToSend = {
                 item_id: itemIdValue, qty: qtyValue, base_price: basePriceNumeric, 
                 sales_contract_id: $scSelect.val() || null, description: $descInput.val(),
                 admin_edit: adminEditFlag
             };
             console.log("[PO Detail] Data to send:", dataToSend);

             // AJAX
             $.ajax({ 
                 url: `<?= base_url('purchase_order/ajax_add_item/'); ?>${poHeaderId}`, 
                 method: 'POST', data: dataToSend, dataType: 'json', 
                 beforeSend: function(){ 
                     button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>'); 
                     // PERBAIKAN: Gunakan $row
                     $row.find('.cancel-inline-item').prop('disabled', true); 
                 }, 
                 success: function(response){ 
                     console.log("[PO Detail] AJAX Save Success:", response);
                     if (response && response.status === 'success') { 
                         Swal.fire({ icon: 'success', title: '<?= lang_line('po_success', 'Berhasil!'); ?>', text: response.message || '<?= lang_line('po_item_saved', 'Item berhasil disimpan.'); ?>', timer: 1500, showConfirmButton: false })
                             .then(() => window.location.reload());
                     } else { 
                          Swal.fire({ icon: 'error', title: '<?= lang_line('po_failed', 'Gagal!'); ?>', text: response.message || '<?= lang_line('po_save_failed', 'Gagal menyimpan data.'); ?>' });
                          button.prop('disabled', false).html('<i class="fas fa-check"></i>'); 
                          $row.find('.cancel-inline-item').prop('disabled', false); // Gunakan $row
                     }
                 }, 
                 error: function(xhr, status, error){ 
                      console.error("[PO Detail] AJAX Save Error:", status, error, xhr.responseText); 
                      Swal.fire({ icon: 'error', title: '<?= lang_line('po_error', 'Error!'); ?>', text: '<?= lang_line('po_connection_error', 'Gagal menghubungi server. Periksa koneksi Anda.'); ?>' });
                      button.prop('disabled', false).html('<i class="fas fa-check"></i>'); 
                      $row.find('.cancel-inline-item').prop('disabled', false); // Gunakan $row
                 } 
             });
        }); 

        // --- Event Listener Tombol Cancel (❌) ---
         $itemTableBody.off('click', '.cancel-inline-item').on('click', '.cancel-inline-item', function() { /* ... (kode cancel sama) ... */ });

        // --- Event Listener Kalkulasi Total (Delegated) ---
        $itemTableBody.off('input change keyup focusout', '.new-item-row .inline-qty, .new-item-row .autonumeric-2dec-inline')
                     .on('input change keyup focusout', '.new-item-row .inline-qty, .new-item-row .autonumeric-2dec-inline', function() { /* ... (kode calculate total sama) ... */ });

    } else {
        console.log("[PO Detail] Item not editable, inline add disabled."); 
    } // End if canEditItem

    // --- BOM Quick Load ---
    $('#btn-load-bom').on('click', function() {
        const scId = $('#bom-sc-select').val();
        if (!scId) { alert(LANG.selectSalesContractFirst); return; }
        const $btn = $(this);
        const $result = $('#bom-load-result');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + LANG.loading);
        $result.html('').removeClass('text-success text-danger');
        $.post('<?= base_url('purchase_order/load_bom_from_sc/') ?>' + poHeaderId, { sc_id: scId, admin_edit: adminEditFlag })
            .done(function(res) {
                if (res.success) {
                    $result.addClass('text-success').html('<i class="fas fa-check-circle"></i> ' + res.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    $result.addClass('text-danger').html('<i class="fas fa-exclamation-circle"></i> ' + res.message);
                    $btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i> Load BOM');
                }
            })
            .fail(function() {
                $result.addClass('text-danger').html(LANG.serverError);
                $btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i> ' + LANG.loadBomButton);
            });
    });

    // --- Event Listener Tombol Lain (di luar if Draft) ---
    if (typeof Swal !== 'undefined') {
        $(document).off('click', '.btn-delete').on('click', '.btn-delete', function(e) { /* ... (kode SweetAlert delete sama) ... */ });
        $(document).off('click', 'a.btn-confirm, a.btn-submit').on('click', 'a.btn-confirm, a.btn-submit', function(e) { /* ... (kode SweetAlert confirm sama) ... */ });
    } else {
        console.warn("[PO Detail] SweetAlert is not loaded. Using basic confirm.");
         // ... (Fallback confirm() sama) ...
    }

    // --- Batalkan PO: 2 pilihan ---
    $('#btn-batalkan-po').on('click', function() {
        const returnUrl  = $(this).data('return-url');
        const deleteUrl  = $(this).data('delete-url');
        const poStatus   = $(this).data('status');
        const poNumber   = $(this).data('po-number');
        const isDraft    = poStatus === 'Draft';

        Swal.fire({
            title: 'Batalkan PO ' + poNumber + '?',
            text: 'Pilih tindakan yang ingin dilakukan:',
            icon: 'warning',
            showConfirmButton: !isDraft,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-undo mr-1"></i> Kembalikan ke Draft',
            denyButtonText: '<i class="fas fa-trash mr-1"></i> Hapus Data',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f6c23e',
            denyButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            reverseButtons: true,
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = returnUrl;
            } else if (result.isDenied) {
                Swal.fire({
                    title: 'Hapus Permanen?',
                    html: 'PO <strong>' + poNumber + '</strong> beserta semua item detailnya akan dihapus.<br><span class="text-danger">Aksi ini tidak dapat dibatalkan!</span>',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus Permanen',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#858796',
                }).then(function(r2) {
                    if (r2.isConfirmed) window.location.href = deleteUrl;
                });
            }
        });
    });

    // --- Inline Edit Existing Item ---
    const BASE_URL_PO = '<?= base_url() ?>';

    // ============ RINGKASAN NILAI (realtime) ============
    // Dulu edit-di-tempat cuma memperbarui kolom Total baris itu, sedangkan
    // Subtotal/PPN/Grand Total di kaki tabel tetap angka lama dari server.
    // Rumus di bawah cerminan po_ppn_breakdown() di ppn_helper.php.
    const PO_PPN = {
        has:       <?= !empty($header->has_ppn) ? 'true' : 'false'; ?>,
        rate:      <?= (float) ($header->ppn_rate ?? 0); ?>,
        inclusive: <?= !empty($header->ppn_inclusive) ? 'true' : 'false'; ?>,
        fx:        <?= (float) ($header->exchange_rate ?: 1); ?>
    };

    function poFmt(n) {
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcPoTotals() {
        var subtotal = 0;
        $('#po-item-details-body tr[data-detail-id] .cell-total').each(function () {
            var n = parseFloat($(this).text().replace(/,/g, ''));
            if (!isNaN(n)) subtotal += n;
        });

        var dpp = subtotal, ppn = 0, total = subtotal;
        if (PO_PPN.has && PO_PPN.rate > 0) {
            if (PO_PPN.inclusive) {
                total = subtotal;
                ppn   = total * PO_PPN.rate / (100 + PO_PPN.rate);
                dpp   = total - ppn;
            } else {
                dpp   = subtotal;
                ppn   = subtotal * PO_PPN.rate / 100;
                total = subtotal + ppn;
            }
        }

        $('#subtotal-base').text(poFmt(dpp));
        $('#ppn-base').text(poFmt(ppn));
        $('#grandtotal-base').text(poFmt(total));
        // Baris estimasi IDR cuma ada kalau mata uang PO bukan IDR.
        $('#grandtotal-local').text(poFmt(total * PO_PPN.fx));
    }
    const $poBody = $('#po-item-details-body');

    // Enter edit mode
    $poBody.on('click', '.btn-edit-inline', function() {
        const $row = $(this).closest('tr');
        $row.addClass('editing');
        $row.find('.display-val').hide();
        $row.find('.edit-input').show();
        $row.find('.btn-view-mode').hide();
        $row.find('.btn-edit-mode').show();
        $row.find('.cell-qty .edit-input').focus().select();
    });

    // Cancel edit mode
    $poBody.on('click', '.btn-cancel-inline', function() {
        const $row = $(this).closest('tr');
        $row.removeClass('editing');
        $row.find('.display-val').show();
        $row.find('.edit-input').hide();
        $row.find('.btn-view-mode').show();
        $row.find('.btn-edit-mode').hide();
    });

    // Save inline edit
    $poBody.on('click', '.btn-save-inline', function() {
        const $btn  = $(this);
        const detailId = $btn.data('id');
        const $row  = $btn.closest('tr');

        const qty   = parseFloat($row.find('.cell-qty .edit-input').val());
        const price = parseFloat($row.find('.cell-price .edit-input').val()) || 0;
        const desc  = $row.find('.cell-desc .edit-input').val();

        if (!qty || qty <= 0) { alert(LANG.qtyZeroError); $row.find('.cell-qty .edit-input').focus(); return; }
        if (isNaN(price) || price < 0) { alert(LANG.priceNegativeError); $row.find('.cell-price .edit-input').focus(); return; }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $row.find('.btn-cancel-inline').prop('disabled', true);

        $.post(BASE_URL_PO + 'purchase_order/ajax_update_item/' + detailId, {
            qty: qty, base_price: price, description: desc, admin_edit: adminEditFlag
        }, function(res) {
            if (res && res.success) {
                // Update display values in-place (no page reload)
                const fmtQty   = qty.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4});
                const fmtPrice = price.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4}); // harga satuan 4 desimal; fmtTotal di bawah TETAP 2
                const fmtTotal = (qty * price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                $row.find('.cell-qty .display-val').text(fmtQty);
                $row.find('.cell-price .display-val').text(fmtPrice).removeClass('text-danger font-weight-bold');
                $row.find('.cell-price').removeClass('price-zero');
                $row.find('.cell-total').text(fmtTotal);
                $row.find('.cell-desc .display-val').text(desc || '-');

                // Exit edit mode
                $row.removeClass('editing');
                $row.find('.display-val').show();
                $row.find('.edit-input').hide();
                $row.find('.btn-view-mode').show();
                $row.find('.btn-edit-mode').hide();

                recalcPoTotals(); // kaki tabel ikut berubah tanpa reload
                if (typeof toastr !== 'undefined') toastr.success(LANG.itemSaveSuccess);
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

}); // End $(function() { ... })
</script>


<style> 
/* Style Select2 agar pas di tabel & tema bootstrap */
.select2-container--bootstrap4 .select2-selection--single { height: calc(1.5em + .5rem + 2px) !important; padding: .25rem .5rem !important; font-size: .875rem; line-height: 1.5; border-radius: .2rem; border: 1px solid #d1d3e2;}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .5rem) !important; }
.select2-container--bootstrap4 .select2-dropdown { z-index: 1051 !important; border-color: #d1d3e2;} 
.select2-container { width: 100% !important; } 

/* Style untuk baris baru */
.new-item-row input, .new-item-row select { font-size: 0.8rem; height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem;}
.new-item-row .btn-xs { padding: .15rem .4rem; font-size: .7rem; line-height: 1.2;}
.saving { opacity: 0.6; pointer-events: none; } 

/* Style input readonly */
.new-item-row input[readonly] { background-color: #e9ecef !important; border: 1px solid #d1d3e2 !important; box-shadow: none !important; cursor: default; color: #6e707e; }
.new-item-row .item-code-display, .new-item-row .item-unit-display { text-align: center; }

/* Sembunyikan panah di input number */
input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* Inline edit inputs in existing rows */
tr .edit-input { font-size: 0.8rem; height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; min-width: 70px; }
tr .cell-qty .edit-input { width: 90px; }
tr .cell-price .edit-input { width: 110px; }
tr .cell-desc .edit-input { width: 100%; }
tr.editing { background-color: #fffbea !important; }
td.price-zero { background-color: #fff3cd; }
</style>

<script>
/* Bagian rincian PPN (include/exclude + persen) hanya terbuka kalau "Kena PPN"
   dicentang -- alur yang diminta klien 2026-10-02: kena pajak dulu, baru
   include/exclude, baru persennya. */
$(function () {
    $(document).on('change', '.ppn-toggle', function () {
        $(this).closest('.border').find('.ppn-detail').toggle($(this).is(':checked'));
    });
});
</script>
