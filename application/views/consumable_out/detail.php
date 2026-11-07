<?php
    // Kategori spesifik-record menentukan izin (OR-fallback ke permission
    // lama 'consumable_out' selama masa transisi role belum dikonfigurasi ulang).
    $cat_menu_key = 'consumable_out_' . strtolower($header->category ?? 'General');
    $can_confirm = check_access($cat_menu_key, 'confirm') || check_access('consumable_out', 'confirm');
    $can_edit    = check_access($cat_menu_key, 'edit')    || check_access('consumable_out', 'edit');
    $can_delete  = check_access($cat_menu_key, 'delete')  || check_access('consumable_out', 'delete');
    $back_url    = 'consumable_out/index/' . strtolower($header->category ?? 'General');
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i><?= lang_line('co_d_title'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->doc_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Created' && $can_confirm): ?>
            <a href="<?= base_url('consumable_out/confirm/' . $header->id); ?>"
               class="btn btn-success btn-sm mr-1"
               onclick="return confirm('<?= htmlspecialchars(lang_line('co_d_confirm_msg'), ENT_QUOTES); ?>');">
                <i class="fas fa-check-circle fa-sm mr-1"></i> <?= lang_line('co_d_btn_confirm'); ?>
            </a>
            <?php endif; ?>
            <?php if (in_array($header->status, ['Created','Confirmed']) && $can_edit):
                $cancel_msg = lang_line('co_d_cancel_msg') . ($header->status == 'Confirmed' ? lang_line('co_d_cancel_msg_extra') : '');
            ?>
            <a href="<?= base_url('consumable_out/cancel/' . $header->id); ?>"
               class="btn btn-warning btn-sm mr-1"
               onclick="return confirm('<?= htmlspecialchars($cancel_msg, ENT_QUOTES); ?>');">
                <i class="fas fa-ban fa-sm mr-1"></i> <?= lang_line('co_d_btn_cancel'); ?>
            </a>
            <?php endif; ?>
            <a href="<?= base_url('consumable_out/export_pdf/' . $header->id); ?>"
               class="btn btn-info btn-sm mr-1" target="_blank">
                <i class="fas fa-file-pdf fa-sm mr-1"></i> <?= lang_line('co_d_btn_pdf'); ?>
            </a>
            <?php if ($header->status == 'Created' && $can_delete): ?>
            <a href="<?= base_url('consumable_out/delete/' . $header->id); ?>"
               class="btn btn-danger btn-sm mr-1"
               onclick="return confirm('<?= htmlspecialchars(lang_line('co_d_delete_msg'), ENT_QUOTES); ?>');">
                <i class="fas fa-trash fa-sm mr-1"></i> <?= lang_line('co_d_btn_delete'); ?>
            </a>
            <?php endif; ?>
            <a href="<?= base_url($back_url); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('co_d_btn_back'); ?>
            </a>
        </div>
    </div>
    <?php if ($header->status == 'Confirmed' && empty($handover_attachments)): ?>
    <div class="alert alert-warning py-2 mb-3">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong><?= lang_line('co_d_need_attachment_title2', 'Belum selesai:'); ?></strong>
        <?= lang_line('co_d_need_attachment2', 'Barang sudah dikeluarkan — upload attachment bukti pengeluaran di bawah untuk menyelesaikan dokumen ini.'); ?>
    </div>
    <?php endif; ?>

    <!-- Buat PR Sisanya (ERP Revision Notes 2026-09-03, [[revision_irf_auto_pr_at_issuance]]):
         muncul begitu dokumen Confirmed TAPI qty yang dikeluarkan lebih kecil
         dari qty yang diminta di Permintaan Barang sumbernya (stock tidak cukup) — 1 klik
         langsung bikin PR utk sisanya lewat Consumable_outstanding::create_pr()
         yang sudah ada, tanpa pindah menu ke Outstanding Permintaan Barang. -->
    <?php if (!empty($remaining_outstanding) && !empty($can_create_pr)): ?>
    <?php $need_pr = (float) ($remaining_pr_qty ?? 0) > 0.0001; ?>
    <div class="card border-left-<?= $need_pr ? 'warning' : 'info' ?> shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <?php if ($need_pr): ?>
                    <i class="fas fa-shopping-cart text-warning mr-1"></i>
                    <strong><?= lang_line('co_d_remaining_pr_title', 'Stock belum cukup:'); ?></strong>
                    <?= lang_line('co_d_remaining_pr_desc2', 'Permintaan Barang ini masih punya sisa yang belum dikeluarkan. Qty PR = sisa dikurangi stok gudang dan qty yang sudah ada di PR sebelumnya.'); ?>
                    <?php else: ?>
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    <strong><?= lang_line('co_d_remaining_nopr_title', 'Masih ada sisa Permintaan Barang:'); ?></strong>
                    <?php if (!empty($pr_docs)): ?><span class="badge badge-info"><i class="fas fa-link mr-1"></i><?= lang_line('co_d_pr_made_badge', 'PR sudah dibuat'); ?></span><?php endif; ?>
                    <?= lang_line('co_d_remaining_nopr_desc', 'Sisanya sudah bisa dipenuhi dari stok gudang atau sudah ada di PR sebelumnya, jadi tidak perlu PR lagi. Keluarkan dari antrian Permintaan Barang di halaman pengeluaran.'); ?>
                    <?php endif; ?>
                </div>
                <?php if ($need_pr): ?>
                <form method="POST" action="<?= base_url('consumable_outstanding/create_pr') ?>" class="mt-2 mt-sm-0 ml-sm-3 flex-shrink-0"
                      onsubmit="return confirm('<?= htmlspecialchars(lang_line('co_d_remaining_pr_confirm', 'Buat Purchase Request untuk semua sisa outstanding Permintaan Barang ini?'), ENT_QUOTES) ?>');">
                    <input type="hidden" name="redirect_to" value="<?= base_url('consumable_out/detail/' . $header->id) ?>">
                    <?php foreach ($remaining_outstanding as $r): ?>
                        <input type="hidden" name="cr_detail_ids[]" value="<?= (int) $r->cr_detail_id ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('co_d_btn_remaining_pr', 'Buat PR Sisanya'); ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm mb-0 small">
                    <thead>
                        <tr class="text-muted">
                            <th><?= lang_line('co_d_rem_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('co_d_rem_th_item', 'Barang'); ?></th>
                            <th class="text-right"><?= lang_line('co_d_rem_th_outstanding', 'Sisa Permintaan Barang'); ?></th>
                            <th class="text-right"><?= lang_line('co_d_rem_th_stock', 'Dari Stok'); ?></th>
                            <th class="text-right"><?= lang_line('co_d_rem_th_on_pr', 'Sudah di PR'); ?></th>
                            <th class="text-right"><?= lang_line('co_d_rem_th_pr', 'Perlu PR'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($remaining_outstanding as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r->item_code) ?></td>
                            <td><?= htmlspecialchars($r->item_name) ?></td>
                            <td class="text-right"><?= number_format((float) $r->qty_outstanding, 2) ?></td>
                            <td class="text-right" title="<?= htmlspecialchars(sprintf(lang_line('co_d_rem_stock_hint', 'Stok gudang saat ini: %s'), number_format((float) $r->stock_available, 2))) ?>"><?= number_format((float) $r->qty_from_stock, 2) ?></td>
                            <td class="text-right">
                                <?= number_format((float) $r->qty_on_pr, 2) ?>
                                <?php $this->load->view('templates/partials/pr_doc_links', ['docs' => $pr_docs[(int) $r->cr_detail_id] ?? []]); ?>
                            </td>
                            <td class="text-right font-weight-bold <?= (float) $r->qty_pr > 0 ? 'text-warning' : 'text-muted' ?>"><?= number_format((float) $r->qty_pr, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- Header Info -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_doc_no'); ?></strong><br>
                    <span class="text-primary font-weight-bold"><?= htmlspecialchars($header->doc_number); ?></span>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_date'); ?></strong><br>
                    <?= date('d M Y', strtotime($header->issuance_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_status'); ?></strong><br>
                    <?php
                        $badge = 'secondary';
                        if ($header->status == 'Created')   $badge = 'warning';
                        if ($header->status == 'Confirmed') $badge = 'success';
                        if ($header->status == 'Cancelled') $badge = 'danger';
                    ?>
                    <span class="badge badge-<?= $badge; ?>"><?= $header->status; ?></span>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_created_by'); ?></strong><br>
                    <?= htmlspecialchars($header->created_by_name ?? '-'); ?>
                    <?php if ($header->created_at): ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($header->created_at)); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_department'); ?></strong>
                    <?= htmlspecialchars($header->department ?? '-'); ?>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('cr_category', 'Kategori'); ?></strong><br>
                    <span class="badge badge-<?= ($header->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($header->category ?? 'General') ?></span>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_recipient'); ?></strong>
                    <?= htmlspecialchars($header->recipient ?? '-'); ?>
                </div>
                <div class="col-md-3">
                    <strong><?= lang_line('co_d_lbl_confirmed_by'); ?></strong>
                    <?= htmlspecialchars($header->confirmed_by_name ?? '-'); ?>
                    <?php if ($header->confirmed_at): ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($header->confirmed_at)); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($header->purpose)): ?>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong><?= lang_line('co_d_lbl_purpose'); ?></strong>
                    <?= htmlspecialchars($header->purpose); ?>
                </div>
                <div class="col-md-6">
                    <strong><?= lang_line('co_d_lbl_notes'); ?></strong>
                    <?= htmlspecialchars($header->notes ?? '-'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('co_d_items_card_title'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('co_d_th_no'); ?></th>
                            <th><?= lang_line('co_d_th_item_code'); ?></th>
                            <th><?= lang_line('co_d_th_item_name'); ?></th>
                            <th><?= lang_line('co_d_th_unit'); ?></th>
                            <th><?= lang_line('co_d_th_qty_issued'); ?></th>
                            <th><?= lang_line('co_d_th_notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): $no = 1; foreach ($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                            <td>
                                <?= htmlspecialchars($d->item_name ?? '-'); ?>
                                <?php if (($d->item_type ?? 'inventory') === 'machine'): ?>
                                    <span class="badge badge-info ml-1"><?= lang_line('cr_group_machine', 'Mesin / Sparepart'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($d->qty_issued, 4); ?></td>
                            <td><?= htmlspecialchars($d->notes ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3"><?= lang_line('co_d_no_item'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bukti Serah Terima. Dokumen yang sama dengan yang tampil di halaman Permintaan Barang:
         gudang mengeluarkan, peminta menerima & menandatangani. Diunggah dari
         sisi mana pun, muncul di kedua halaman (user 2026-09-14). -->
    <?php $handover_done = !empty($handover_attachments); ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-signature mr-1"></i>
                <?= lang_line('co_d_handover_title', 'Bukti Serah Terima'); ?>
                (<?= count($handover_attachments); ?>)
                <?php if (!empty($request_number)): ?>
                    <span class="badge badge-light border ml-1">Permintaan Barang <?= htmlspecialchars($request_number); ?></span>
                <?php endif; ?>
            </h6>
            <?php if ($handover_done): ?>
                <span class="badge badge-success"><i class="fas fa-check mr-1"></i><?= lang_line('co_d_handover_done', 'Selesai'); ?></span>
            <?php elseif ($header->status == 'Confirmed'): ?>
                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i><?= lang_line('co_d_handover_pending', 'Belum selesai'); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (in_array($header->status, ['Created', 'Confirmed']) && $can_edit): ?>
            <form method="POST" action="<?= base_url('consumable_out/upload_attachment/' . $header->id); ?>" enctype="multipart/form-data" class="mb-3">
                <div class="form-group mb-0">
                    <label for="attachment" class="small font-weight-bold"><?= lang_line('co_d_lbl_choose_file', 'Pilih File'); ?></label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="attachment" id="attachment" class="form-control-file" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> <?= lang_line('co_d_btn_upload', 'Upload'); ?></button>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <?php if (empty($handover_attachments)): ?>
                <p class="text-muted text-center py-3 mb-0"><?= lang_line('co_d_handover_none', 'Belum ada bukti serah terima.'); ?></p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('co_d_th_att_file', 'File'); ?></th>
                            <th><?= lang_line('co_d_handover_source', 'Diunggah dari'); ?></th>
                            <th><?= lang_line('co_d_th_att_size', 'Size'); ?></th>
                            <th><?= lang_line('co_d_th_att_uploaded', 'Uploaded'); ?></th>
                            <th width="140"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($handover_attachments as $a): ?>
                        <?php $own = ($a->owner === 'out'); ?>
                        <tr>
                            <td><i class="fas fa-file mr-1"></i> <?= htmlspecialchars($a->original_name); ?></td>
                            <td class="small">
                                <?= htmlspecialchars($a->source_label); ?>
                                <?php if (!empty($a->doc_number) && $a->doc_number !== $header->doc_number): ?>
                                    <span class="text-muted">(<?= htmlspecialchars($a->doc_number); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= number_format($a->file_size / 1024, 1); ?> KB</td>
                            <td class="small"><?= date('d M Y H:i', strtotime($a->created_at)); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link text-info btn-view-attachment"
                                        data-id="<?= $a->id; ?>"
                                        data-name="<?= htmlspecialchars($a->original_name); ?>"
                                        data-url="<?= base_url(($own ? 'consumable_out' : 'consumable_request') . '/attachment/' . $a->id); ?>">
                                    <i class="fas fa-eye mr-1"></i> <?= lang_line('co_d_btn_view', 'View'); ?>
                                </button>
                                <?php // Hapus hanya untuk berkas milik halaman ini -- bukti yang
                                      // diunggah requestor dihapus dari halaman IRF. ?>
                                <?php if ($own && in_array($header->status, ['Created', 'Confirmed']) && $can_edit): ?>
                                <a href="<?= base_url('consumable_out/delete_attachment/' . $a->id); ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('<?= lang_line('co_d_confirm_delete_att', 'Hapus attachment?'); ?>');"><i class="fas fa-trash"></i> <?= lang_line('co_d_btn_delete_att', 'Hapus'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- Modal: View Attachment -->
<div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-file mr-2"></i> <span id="attachmentName"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="attachmentBody" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p><?= lang_line('co_d_att_loading', 'Loading...'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="attachmentPrint" class="btn btn-outline-primary"><i class="fas fa-print mr-1"></i> <?= lang_line('co_d_att_print', 'Print'); ?></button>
                <a id="attachmentDownload" href="#" class="btn btn-primary" download><i class="fas fa-download mr-1"></i> <?= lang_line('co_d_att_download', 'Download'); ?></a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('co_d_att_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-view-attachment').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const fileId = this.getAttribute('data-id');
            const fileName = this.getAttribute('data-name');
            const fileUrl = this.getAttribute('data-url');
            const fileExtension = fileName.split('.').pop().toLowerCase();

            document.getElementById('attachmentName').textContent = fileName;
            const downloadBtn = document.getElementById('attachmentDownload');
            downloadBtn.href = fileUrl;
            downloadBtn.setAttribute('download', fileName);

            // Print: berkas di-load ke iframe tersembunyi lalu dicetak. Sengaja
            // iframe (bukan window.open) supaya tidak kena pemblokir popup, dan
            // karena berkasnya satu domain jadi contentWindow.print() diizinkan.
            // Kalau peramban tetap menolak, jatuh ke buka tab baru (user 2026-09-14).
            const printBtn = document.getElementById('attachmentPrint');
            if (printBtn) {
                printBtn.onclick = function (ev) {
                    ev.preventDefault();
                    let frame = document.getElementById('attachmentPrintFrame');
                    if (!frame) {
                        frame = document.createElement('iframe');
                        frame.id = 'attachmentPrintFrame';
                        frame.style.display = 'none';
                        document.body.appendChild(frame);
                    }
                    frame.onload = function () {
                        try { frame.contentWindow.focus(); frame.contentWindow.print(); }
                        catch (err) { window.open(fileUrl, '_blank'); }
                    };
                    frame.src = fileUrl;
                };
            }

            const attachmentBody = document.getElementById('attachmentBody');
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (imageExtensions.includes(fileExtension)) {
                attachmentBody.innerHTML = '<img src="' + fileUrl + '" style="max-width: 100%; height: auto;" class="rounded" />';
            } else if (fileExtension === 'pdf') {
                attachmentBody.innerHTML = '<embed src="' + fileUrl + '" type="application/pdf" style="width: 100%; height: 500px;" />';
            } else {
                attachmentBody.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-file fa-3x mb-3 text-secondary"></i><p><?= lang_line('co_d_att_no_preview', 'Preview tidak tersedia untuk tipe file ini.'); ?></p><p class="small"><?= lang_line('co_d_att_click_download', 'Klik Download untuk membuka file.'); ?></p></div>';
            }

            $('#attachmentModal').modal('show');
        });
    });
});
</script>
