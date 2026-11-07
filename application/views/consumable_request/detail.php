<?php
$editing = !empty($edit_mode);
// Kolom Catatan di tampilan list (read-only) sengaja disembunyikan kalau
// tidak ada satupun item yang punya catatan — declutter, bukan dihapus
// fungsinya (data notes per-item tetap tersimpan & dipakai). Permintaan
// user 2026-09-02: "kolom Catatan dihilangkan kalau tidak diperlukan" ->
// khusus tampilan list, BUKAN saat form input (lihat erp_revision_priority_checklist).
$has_item_notes = false;
if (!empty($details)) {
    foreach ($details as $__d) {
        if (trim((string) ($__d->notes ?? '')) !== '') { $has_item_notes = true; break; }
    }
}
?>
<div class="container-fluid">
    <h3 class="text-gray-800 mb-4"><i class="fas fa-inbox text-primary mr-2"></i><?= $title ?></h3>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <?php if ($editing): ?>
    <?php if (!empty($is_admin_edit_mode)): ?>
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fas fa-user-shield fa-lg mr-2"></i>
            <div>
                <strong><?= lang_line('cr_admin_edit_mode', 'Mode Edit Admin'); ?>.</strong>
                <?= sprintf(lang_line('cr_admin_edit_warning', 'Anda mengubah daftar item Permintaan Barang berstatus <strong>%s</strong>. Perubahan ini akan tercatat di Riwayat Approval.'), htmlspecialchars($header->status)); ?>
            </div>
        </div>
    <?php endif; ?>
    <form method="POST" action="<?= base_url('consumable_request/update/' . $header->id) ?>" id="form_request">
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Header Info -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong><?= lang_line('cr_info_request', 'Informasi Request'); ?></strong>
                    <div>
                        <span class="badge badge-<?= $header->category === 'Produksi' ? 'dark' : 'secondary' ?>">
                            <?= htmlspecialchars($header->category ?? 'General') ?>
                        </span>
                        <span class="badge badge-<?= ['Draft'=>'warning','Approved Head Dept'=>'info','Endorsed'=>'primary','Approved'=>'success','Released'=>'primary'][$header->status] ?? 'info' ?>">
                            <?= $header->status ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($editing): ?>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_no_request', 'No Request'); ?></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($header->request_number) ?>" disabled>
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_request_date', 'Tanggal Request'); ?> <span class="text-danger">*</span></label>
                                <input type="date" name="request_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime($header->request_date))) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_department', 'Departemen'); ?></label>
                                <input type="text" name="department" class="form-control bg-light"
                                       value="<?= htmlspecialchars($header->department ?? '') ?>"
                                       readonly title="<?= lang_line('cr_auto_from_profile', 'Otomatis dari profil user yang login'); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_requestor_name', 'Nama Requestor'); ?></label>
                                <input type="text" name="requestor_name" class="form-control bg-light"
                                       value="<?= htmlspecialchars($header->requestor_name ?? '') ?>"
                                       readonly title="<?= lang_line('cr_auto_from_profile', 'Otomatis dari profil user yang login'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_category', 'Kategori Kebutuhan'); ?> <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>" <?= ($header->category === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('cr_purpose', 'Keperluan'); ?> <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="2" required><?= htmlspecialchars($header->purpose ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('cr_notes', 'Catatan'); ?></label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($header->notes ?? '') ?></textarea>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?= lang_line('cr_lbl_no_request', 'No Request'); ?>:</strong> <?= htmlspecialchars($header->request_number) ?></p>
                                <p><strong><?= lang_line('cr_lbl_date', 'Tanggal'); ?>:</strong> <?= date('d M Y', strtotime($header->request_date)) ?></p>
                                <p><strong><?= lang_line('cr_department', 'Departemen'); ?>:</strong> <?= htmlspecialchars(($department_name ?? '') ?: ($header->department ?? '-')) ?></p>
                                <p><strong><?= lang_line('cr_category', 'Kategori Kebutuhan'); ?>:</strong> <?= htmlspecialchars($header->category ?? 'General') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><?= lang_line('cr_lbl_requestor', 'Requestor'); ?>:</strong> <?= htmlspecialchars($header->requestor_name ?? '-') ?></p>
                                <p><strong><?= lang_line('cr_lbl_created_by', 'Dibuat Oleh'); ?>:</strong> <?= htmlspecialchars($header->created_by_name ?? '-') ?></p>
                                <p><strong><?= lang_line('cr_lbl_created_at', 'Dibuat Tanggal'); ?>:</strong> <?= $header->created_at ? date('d M Y H:i', strtotime($header->created_at)) : '-' ?></p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong><?= lang_line('cr_lbl_purpose', 'Keperluan'); ?>:</strong></label>
                            <p><?= nl2br(htmlspecialchars($header->purpose ?? '-')) ?></p>
                        </div>
                        <div class="form-group">
                            <label><strong><?= lang_line('cr_lbl_notes', 'Catatan'); ?>:</strong></label>
                            <p><?= nl2br(htmlspecialchars($header->notes ?? '-')) ?></p>
                        </div>
                        <?php if (property_exists($header, 'total_value')): ?>
                <?php // Approval 1 tahap: Head of Dept / atasan (2026-11-05). Nilai = harga PO terakhir (informasi). ?>
                <div class="alert alert-success py-2 mb-0 small">
                    <strong>Nilai Permintaan Barang<?= $header->status !== 'Draft' ? '' : ' (perkiraan)'; ?>:</strong>
                    <?= $header->total_value !== null ? 'Rp ' . number_format((float) $header->total_value, 0, ',', '.') : '-'; ?>
                    <span class="mx-1">·</span>
                    <strong>Approval 1 tahap</strong> (Head of Dept / atasan)
                </div>
                <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Items -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light">
                    <strong><?= lang_line('cr_item_list', 'Daftar Item'); ?></strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <?php if ($editing): ?>
                        <table class="table table-sm mb-0" id="items_table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 37%;"><?= lang_line('cr_th_item', 'Barang'); ?></th>
                                    <th style="width: 15%;"><?= lang_line('cr_th_unit', 'Satuan'); ?></th>
                                    <th style="width: 23%;"><?= lang_line('cr_th_qty', 'Qty'); ?></th>
                                    <th style="width: 15%;"></th><?php /* Kolom stock sengaja TANPA judul — labelnya menyatu di dalam sel, sama spt form Buat IRF */ ?>
                                    <th style="width: 10%;"><?= lang_line('cr_th_action', 'Aksi'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="items_body">
                                <?php if (empty($details)): ?>
                                    <tr class="text-center text-muted py-3 empty-row">
                                        <td colspan="5"><?= lang_line('cr_no_item', 'Belum ada item. Klik tombol "Tambah Item" di atas'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('cr_th_code', 'Kode'); ?></th>
                                    <th><?= lang_line('cr_th_item_name', 'Nama Barang'); ?></th>
                                    <th><?= lang_line('cr_th_unit', 'Satuan'); ?></th>
                                    <th><?= lang_line('cr_th_qty', 'Qty'); ?></th>
                                    <?php $has_price = !empty($details) && property_exists($details[0], 'unit_price'); ?>
                                    <?php if ($has_price): ?>
                                    <th class="text-right">Harga (PO terakhir)</th>
                                    <th class="text-right">Nilai</th>
                                    <?php endif; ?>
                                    <?php if ($has_item_notes): ?>
                                    <th><?= lang_line('cr_th_notes', 'Catatan'); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($details)): ?>
                                    <tr><td colspan="<?= $has_item_notes ? 5 : 4 ?>" class="text-center text-muted py-3"><?= lang_line('cr_no_item_simple', 'Tidak ada item'); ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($details as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d->item_code) ?></td>
                                        <td>
                                            <?= htmlspecialchars($d->item_name) ?>
                                            <?php if (($d->item_type ?? 'inventory') === 'machine'): ?>
                                                <span class="badge badge-info ml-1"><?= lang_line('cr_group_machine', 'Mesin / Sparepart'); ?></span>
                                            <?php elseif (($d->item_type ?? '') === 'asset'): ?>
                                                <span class="badge badge-warning ml-1"><?= lang_line('cr_group_asset', 'Asset'); ?></span>
                                            <?php elseif (($d->item_type ?? '') === 'jasa'): ?>
                                                <span class="badge badge-secondary ml-1"><?= lang_line('cr_group_jasa', 'Jasa'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($d->item_unit) ?></td>
                                        <td class="text-right"><?= number_format($d->qty_requested, 2) ?></td>
                                        <?php if ($has_price): ?>
                                        <?php
                                            // Harga acuan untuk baris yang belum punya harga tersimpan (IRF lama).
                                            // Urutan: PO yang lahir dari IRF ini -> PO terakhir barang ini.
                                            $ref = $irf_po_prices[(int) $d->id] ?? null;
                                            $ref_from_this_irf = (bool) $ref;
                                            if (!$ref) $ref = $live_prices[($d->item_type ?: 'inventory') . '-' . (int) $d->item_id] ?? null;
                                        ?>
                                        <td class="text-right">
                                            <?php if ($d->unit_price !== null): ?>
                                                Rp <?= number_format((float) $d->unit_price, 0, ',', '.'); ?>
                                            <?php elseif ($ref): ?>
                                                <?php
                                                    $ref_hint = $ref_from_this_irf
                                                        ? sprintf(lang_line('cr_price_from_irf_po', 'Harga dari PO yang dibuat untuk Permintaan Barang ini (%s, %s).'), $ref->po_number, date('d M Y', strtotime($ref->po_date)))
                                                        : sprintf(lang_line('cr_price_ref_hint', 'Harga dari PO terakhir barang ini (%s, %s). Permintaan Barang ini dibuat sebelum aturan nilai berlaku, jadi nilainya tidak ikut dihitung.'), $ref->po_number, date('d M Y', strtotime($ref->po_date)));
                                                ?>
                                                <span class="<?= $ref_from_this_irf ? 'text-info' : 'text-muted' ?>" title="<?= htmlspecialchars($ref_hint); ?>">
                                                    <?= $ref_from_this_irf ? '' : '± ' ?>Rp <?= number_format((float) $ref->unit_price, 0, ',', '.'); ?>
                                                    <?php if ($ref_from_this_irf): ?><i class="fas fa-link small ml-1" title="<?= htmlspecialchars($ref->po_number); ?>"></i><?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning" title="<?= htmlspecialchars(lang_line('cr_price_none_hint', 'Tidak ditemukan PO berstatus Confirmed/Closed yang memuat barang ini dengan harga di atas nol.')); ?>">Belum pernah di-PO</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($d->unit_price !== null): ?>
                                                Rp <?= number_format((float) $d->unit_price * (float) $d->qty_requested, 0, ',', '.'); ?>
                                            <?php elseif ($ref): ?>
                                                <span class="<?= $ref_from_this_irf ? 'text-info' : 'text-muted' ?>"><?= $ref_from_this_irf ? '' : '± ' ?>Rp <?= number_format((float) $ref->unit_price * (float) $d->qty_requested, 0, ',', '.'); ?></span>
                                            <?php else: ?>-<?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($has_item_notes): ?>
                                        <td><?= htmlspecialchars($d->notes ?? '-') ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if ($has_price): ?>
                                    <tr class="font-weight-bold">
                                        <td colspan="5" class="text-right">Total Nilai</td>
                                        <td class="text-right">
                                            <?php if (($header->total_value ?? null) === null): ?>
                                                <span class="text-muted" title="<?= htmlspecialchars(lang_line('cr_total_old_hint', 'Permintaan Barang ini dibuat sebelum aturan nilai berlaku, jadi nilainya tidak pernah dihitung. Angka di atas hanya acuan harga PO terakhir.')); ?>">—</span>
                                            <?php else: ?>
                                                Rp <?= number_format((float) $header->total_value, 0, ',', '.'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($has_item_notes): ?><td></td><?php endif; ?>
                                    </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($editing): ?>
                <div class="card-footer bg-white text-right">
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_item">
                        <i class="fas fa-plus"></i> <?= lang_line('cr_add_item', 'Tambah Item'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bukti Serah Terima. Satu dokumen yang sama dilihat dari dua sisi:
                 gudang mengeluarkan, peminta menerima & menandatangani (user
                 2026-09-14: "Bukti pengeluaran itu adalah bukti penerimaan dari
                 user nya"). Permintaan dianggap SELESAI begitu buktinya terunggah,
                 dari sisi mana pun. Berkas tetap disimpan di modul pengunggahnya. -->
            <?php if ($header->status === 'Released' || !empty($handover_attachments) || !empty($issuance_docs)): ?>
            <?php $handover_done = !empty($handover_attachments); ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-file-signature mr-1"></i>
                        <?= lang_line('cr_handover_title', 'Bukti Serah Terima'); ?>
                        (<?= count($handover_attachments); ?>)
                    </strong>
                    <?php if ($handover_done): ?>
                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i><?= lang_line('cr_handover_done', 'Selesai'); ?></span>
                    <?php elseif ($header->status === 'Released'): ?>
                        <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i><?= lang_line('cr_handover_pending', 'Belum selesai'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">

                    <?php if (!$handover_done && $header->status === 'Released'): ?>
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong><?= lang_line('cr_receipt_need_title', 'Belum selesai:'); ?></strong>
                        <?= lang_line('cr_handover_need', 'Cetak bukti pengeluaran di bawah, minta tanda tangan penerima, lalu unggah kembali di sini untuk menyelesaikan permintaan ini.'); ?>
                    </div>
                    <?php endif; ?>

                    <?php // Dokumen pengeluaran milik IRF ini. Bisa lebih dari satu kalau
                          // barangnya dikeluarkan bertahap, jadi tombol cetaknya per dokumen. ?>
                    <?php if (!empty($issuance_docs)): ?>
                    <div class="small font-weight-bold text-muted mb-2">
                        <i class="fas fa-dolly-flatbed mr-1"></i>
                        <?= lang_line('cr_issuance_docs_title', 'Dokumen Pengeluaran Barang'); ?>
                        (<?= count($issuance_docs); ?>)
                    </div>
                    <table class="table table-sm mb-3">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('cr_issuance_att_doc', 'No. Dokumen'); ?></th>
                                <th><?= lang_line('cr_issuance_docs_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('cr_issuance_docs_status', 'Status'); ?></th>
                                <th width="150"><?= lang_line('label_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuance_docs as $doc): ?>
                            <tr>
                                <td class="small"><i class="fas fa-file-alt mr-1"></i> <?= htmlspecialchars($doc->doc_number); ?></td>
                                <td class="small"><?= !empty($doc->issuance_date) ? date('d M Y', strtotime($doc->issuance_date)) : '-'; ?></td>
                                <td class="small">
                                    <span class="badge badge-<?= $doc->status === 'Confirmed' ? 'success' : ($doc->status === 'Cancelled' ? 'secondary' : 'warning'); ?>">
                                        <?= htmlspecialchars($doc->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('consumable_request/export_issuance_pdf/' . $doc->id); ?>"
                                       target="_blank" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('cr_issuance_docs_pdf', 'Export PDF'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if ($header->status === 'Released' && check_access('consumable_request', 'edit')): ?>
                    <form method="POST" action="<?= base_url('consumable_request/upload_attachment/' . $header->id); ?>" enctype="multipart/form-data" class="mb-3">
                        <div class="form-group mb-0">
                            <label for="attachment" class="small font-weight-bold"><?= lang_line('cr_handover_upload', 'Unggah Bukti Serah Terima (sudah ditandatangani)'); ?></label>
                            <div class="input-group input-group-sm">
                                <input type="file" name="attachment" id="attachment" class="form-control-file" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> <?= lang_line('cr_receipt_btn_upload', 'Upload'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>

                    <?php if (empty($handover_attachments)): ?>
                        <p class="text-muted text-center py-3 mb-0"><?= lang_line('cr_handover_none', 'Belum ada bukti serah terima.'); ?></p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('cr_receipt_th_file', 'File'); ?></th>
                                    <th><?= lang_line('cr_handover_source', 'Diunggah dari'); ?></th>
                                    <th><?= lang_line('cr_receipt_th_size', 'Size'); ?></th>
                                    <th><?= lang_line('cr_receipt_th_uploaded', 'Uploaded'); ?></th>
                                    <th width="140"><?= lang_line('label_action', 'Aksi'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($handover_attachments as $a): ?>
                                <?php $own = ($a->owner === 'request'); ?>
                                <tr>
                                    <td><i class="fas fa-file mr-1"></i> <?= htmlspecialchars($a->original_name); ?></td>
                                    <td class="small">
                                        <?= htmlspecialchars($a->source_label); ?>
                                        <?php if (!empty($a->doc_number)): ?>
                                            <span class="text-muted">(<?= htmlspecialchars($a->doc_number); ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= number_format($a->file_size / 1024, 1); ?> KB</td>
                                    <td class="small"><?= date('d M Y H:i', strtotime($a->created_at)); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-info btn-view-attachment"
                                                data-id="<?= $a->id; ?>"
                                                data-name="<?= htmlspecialchars($a->original_name); ?>"
                                                data-url="<?= base_url(($own ? 'consumable_request' : 'consumable_out') . '/attachment/' . $a->id); ?>">
                                            <i class="fas fa-eye mr-1"></i> <?= lang_line('cr_receipt_btn_view', 'View'); ?>
                                        </button>
                                        <?php // Hapus hanya untuk berkas milik halaman ini -- bukti yang
                                              // diunggah gudang dihapus dari dokumen pengeluarannya. ?>
                                        <?php if ($own && check_access('consumable_request', 'edit')): ?>
                                        <a href="<?= base_url('consumable_request/delete_attachment/' . $a->id); ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('<?= lang_line('cr_receipt_confirm_delete', 'Hapus bukti penerimaan ini?'); ?>');"><i class="fas fa-trash"></i> <?= lang_line('cr_receipt_btn_delete', 'Hapus'); ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat Approval -->
            <?php if (!empty($approval_logs)): ?>
            <?php
                $log_meta = [
                    'submit'        => ['label' => lang_line('cr_log_submit',        'Submitted'),               'icon' => 'paper-plane',    'color' => 'info'],
                    'approve_hod'   => ['label' => lang_line('cr_log_approve_hod',   'Approved (Head of Dept)'), 'icon' => 'check',          'color' => 'success'],
                    'endorse'       => ['label' => lang_line('cr_log_endorse',       'Endorsed'),                'icon' => 'file-signature', 'color' => 'primary'],
                    'approve_final' => ['label' => lang_line('cr_log_approve_final', 'Final Approved'),          'icon' => 'check-double',   'color' => 'success'],
                    'reject'        => ['label' => lang_line('cr_log_reject',        'Rejected'),                'icon' => 'times',          'color' => 'danger'],
                    'return'        => ['label' => lang_line('cr_log_return',        'Returned to Draft'),       'icon' => 'undo',           'color' => 'warning'],
                    'admin_edit'    => ['label' => lang_line('cr_log_admin_edit',    'Item Diedit (Admin)'),     'icon' => 'user-shield',    'color' => 'warning'],
                    'reply'         => ['label' => lang_line('cr_log_reply',         'Balasan'),                 'icon' => 'reply',          'color' => 'secondary'],
                ];
            ?>
            <div class="card border-0 shadow-sm mb-3" id="riwayat-approval">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-history mr-1"></i> <?= lang_line('cr_approval_history', 'Riwayat Approval'); ?></strong>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($approval_logs as $log):
                            $m = $log_meta[$log->action] ?? ['label' => $log->action, 'icon' => 'circle', 'color' => 'secondary'];
                            $is_reply = ($log->action === 'reply');
                            // Chat bubble: kanan kalau yang nulis requestor, kiri kalau bukan (approver).
                            $reply_from_requestor = $is_reply && ((int)($log->actor_id ?? 0) === (int)$header->created_by);
                        ?>
                        <?php if ($is_reply): ?>
                        <li class="d-flex mb-3 <?= $reply_from_requestor ? 'flex-row-reverse text-right' : ''; ?>">
                            <div class="<?= $reply_from_requestor ? 'ml-2' : 'mr-2'; ?>" style="max-width:75%;">
                                <div class="px-3 py-2" style="background:<?= $reply_from_requestor ? '#e7f1ff' : '#f1f1f1'; ?>;border-radius:14px;<?= $reply_from_requestor ? 'border-top-right-radius:2px;' : 'border-top-left-radius:2px;'; ?>">
                                    <div class="small font-weight-bold text-gray-800"><?= htmlspecialchars($log->actor_name ?: '-'); ?> <span class="font-weight-normal text-muted">— <?= htmlspecialchars($reply_from_requestor ? 'Requestor' : 'Approver'); ?></span></div>
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
                    <form method="POST" action="<?= base_url('consumable_request/reply/' . $header->id); ?>" class="mt-3 pt-3 border-top">
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold mb-1"><?= lang_line('cr_reply_label', 'Balas'); ?></label>
                            <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="<?= lang_line('cr_reply_placeholder', 'Tulis balasan/klarifikasi...'); ?>" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> <?= lang_line('cr_reply_send', 'Kirim Balasan'); ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light"><strong><?= lang_line('cr_action_section', 'Aksi'); ?></strong></div>
                <div class="card-body text-center">
                    <?php if ($editing): ?>
                        <button type="submit" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-save mr-1"></i> <?= lang_line('cr_save_changes', 'Simpan Perubahan'); ?>
                        </button>
                        <a href="<?= base_url('consumable_request/detail/' . $header->id) ?>" class="btn btn-secondary btn-block mb-2">
                            <i class="fas fa-times mr-1"></i> <?= lang_line('cr_cancel', 'Batal'); ?>
                        </a>
                    <?php elseif ($header->status === 'Draft'): ?>
                        <?php if (!empty($can_edit)): ?>
                            <a href="<?= base_url('consumable_request/detail/' . $header->id . '?edit=1') ?>" class="btn btn-warning btn-block mb-2">
                                <i class="fas fa-edit mr-1"></i> <?= lang_line('cr_edit', 'Edit'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($can_submit)): ?>
                            <a href="<?= base_url('consumable_request/submit/' . $header->id) ?>"
                               class="btn btn-info btn-block mb-2"
                               onclick="return confirm('<?= lang_line('cr_confirm_submit', 'Submit Permintaan Barang ini? Setelah submit, Permintaan Barang tidak bisa diedit lagi.'); ?>');">
                                <i class="fas fa-paper-plane mr-1"></i> <?= lang_line('cr_submit', 'Submit'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($can_delete)): ?>
                            <a href="<?= base_url('consumable_request/delete/' . $header->id) ?>" class="btn btn-danger btn-block mb-2" onclick="return confirm('<?= lang_line('cr_confirm_delete', 'Hapus request ini?'); ?>')">
                                <i class="fas fa-trash mr-1"></i> <?= lang_line('cr_delete', 'Hapus'); ?>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($header->status === 'Approved'): ?>
                        <div class="alert alert-success small mb-2">
                            <i class="fas fa-check-circle mr-1"></i> <?= lang_line('cr_irf_approved', 'Permintaan Barang sudah final approved & siap diproses.'); ?>
                        </div>
                        <?php $co_cat_key = 'consumable_out_' . strtolower($header->category ?? 'General'); ?>
                        <?php if (check_access($co_cat_key, 'add') || check_access('consumable_out', 'add')): ?>
                        <a href="<?= base_url('consumable_out/add?request_id=' . $header->id) ?>" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-arrow-right mr-1"></i> <?= lang_line('cr_process_issuance', 'Proses Pengeluaran'); ?>
                        </a>
                        <?php endif; ?>
                    <?php elseif ($header->status === 'Released'): ?>
                        <div class="alert alert-primary small mb-2">
                            <i class="fas fa-box-open mr-1"></i> <?= lang_line('cr_irf_released', 'Seluruh item Permintaan Barang ini sudah di-release dari gudang.'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$editing && !empty($approval_action)):
                        // Dibuka dari antrian IRF Approval — tombol & pesan sama
                        // dengan di antrian, sesudah approve kembali ke antrian.
                        $appr = [
                            'head_dept'   => ['approve_hod',         'btn-success', 'fa-check',        lang_line('cra_btn_approve'),                         sprintf(lang_line('cra_msg_approve_confirm'), $header->request_number)],
                            'endorsement' => ['approve_endorsement', 'btn-primary', 'fa-thumbs-up',    lang_line('cra_btn_endorse', 'Endorse'),              sprintf(lang_line('cra_msg_endorse_confirm', 'Endorse Permintaan Barang %s?'), $header->request_number)],
                            'approval'    => ['approve_final',       'btn-success', 'fa-check-double', lang_line('cra_btn_approve_final', 'Approve Final'), sprintf(lang_line('cra_msg_approve_confirm'), $header->request_number)],
                        ][$approval_action];
                    ?>
                        <a href="<?= base_url('consumable_request/' . $appr[0] . '/' . $header->id . '?redirect=approval') ?>"
                           class="btn <?= $appr[1] ?> btn-block mb-2" id="crApproveBtn"
                           data-message="<?= htmlspecialchars($appr[4]) ?>">
                            <i class="fas <?= $appr[2] ?> mr-1"></i> <?= $appr[3] ?>
                        </a>
                        <script>
                        document.getElementById('crApproveBtn').addEventListener('click', function (e) {
                            e.preventDefault();
                            var href = this.getAttribute('href'), msg = this.getAttribute('data-message');
                            if (typeof Swal === 'undefined') { if (confirm(msg)) window.location.href = href; return; }
                            Swal.fire({
                                title: <?= json_encode(lang_line('cra_swal_confirm_title'), JSON_UNESCAPED_UNICODE); ?>, text: msg, icon: 'question',
                                showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
                                confirmButtonText: <?= json_encode(lang_line('cra_swal_yes'), JSON_UNESCAPED_UNICODE); ?>,
                                cancelButtonText: <?= json_encode(lang_line('cra_swal_cancel'), JSON_UNESCAPED_UNICODE); ?>
                            }).then(function (r) { if (r.isConfirmed) window.location.href = href; });
                        });
                        </script>
                    <?php endif; ?>

                    <?php if (!$editing && !empty($can_admin_edit)): ?>
                        <a href="<?= base_url('consumable_request/detail/' . $header->id . '?edit=1') ?>" class="btn btn-warning btn-block mb-2"
                           title="<?= lang_line('cr_admin_edit_hint', 'Override Admin: edit/hapus daftar item walau Permintaan Barang sudah lewat Draft'); ?>">
                            <i class="fas fa-user-shield mr-1"></i> <?= lang_line('cr_admin_edit_btn', 'Edit Item (Admin)'); ?>
                        </a>
                    <?php endif; ?>

                    <a href="<?= base_url('consumable_request/export_pdf/' . $header->id) ?>" target="_blank" class="btn btn-outline-danger btn-block mb-2">
                        <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('cr_export_pdf', 'Export PDF (Permintaan Barang)'); ?>
                    </a>
                    <?php // Dibuka dari antrian approval -> Kembali ke antrian itu, bukan IRF List (user 2026-09-17) ?>
                    <a href="<?= base_url(!empty($from_approval) ? 'consumable_request/approval' : 'consumable_request') ?>" class="btn btn-secondary btn-block" id="crBackBtn">
                        <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('cr_btn_back', 'Kembali'); ?>
                    </a>
                    <script>
                    // Sembunyikan "Kembali" kalau halaman ini dibuka di TAB BARU
                    // (user 2026-09-09). Tab baru dari target=_blank rel=noopener
                    // selalu mulai dengan history.length === 1 -> tidak ada halaman
                    // sebelumnya di tab ini, jadi tombol Kembali cuma bikin bingung
                    // (navigasi tetap tersedia lewat sidebar). Kalau detail dibuka
                    // dari alur normal (approval queue, notifikasi, dsb) history-nya
                    // > 1 dan tombolnya tetap tampil seperti biasa.
                    // Script sengaja INLINE di sini (bukan di block script bawah)
                    // supaya jalan sebelum paint -> tombolnya tidak sempat berkedip.
                    // CATATAN (bug user 2026-09-09 "masih ada yang floating"):
                    // tombolnya HARUS di-REMOVE, bukan sekadar display:none.
                    // footer.php mengubah tombol back jadi sticky-floating dgn
                    // menambah class .btn-back-floating, dan CSS class itu punya
                    // `display: inline-block !important` (assets/css/style.css)
                    // yang MENGALAHKAN inline style display:none — jadi tombolnya
                    // muncul lagi begitu halaman discroll. Dengan di-remove,
                    // script floating di footer juga tidak menemukannya sama
                    // sekali (script ini jalan lebih dulu, inline saat parse).
                    (function () {
                        try {
                            if (window.history.length <= 1) {
                                var b = document.getElementById('crBackBtn');
                                if (b && b.parentNode) { b.parentNode.removeChild(b); }
                            }
                        } catch (e) { /* biarkan tombol tampil kalau browser rewel */ }
                    })();
                    </script>
                </div>
            </div>
        </div>
    </div>

    <?php if ($editing): ?>
    </form>
    <?php endif; ?>
</div>

<!-- Modal: View Bukti Penerimaan -->
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
                    <p><?= lang_line('cr_receipt_att_loading', 'Loading...'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="attachmentPrint" class="btn btn-outline-primary"><i class="fas fa-print mr-1"></i> <?= lang_line('cr_receipt_att_print', 'Print'); ?></button>
                <a id="attachmentDownload" href="#" class="btn btn-primary" download><i class="fas fa-download mr-1"></i> <?= lang_line('cr_receipt_att_download', 'Download'); ?></a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('cr_receipt_att_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
    // Modal "Tambah Item Baru" -- kembaran milik form Create (lihat
    // consumable_request/form.php). User 2026-09-22: tombol + di dropdown
    // item cuma jalan waktu IRF dibuat, padahal item yang belum terdaftar di
    // master sama seringnya baru ketahuan waktu IRF draft DIPERBAIKI.
    // $quick_add dikirim controller hanya saat $edit_mode.
    $qa = $quick_add ?? [];
    $qa_any = !empty($qa['inventory']['can']) || !empty($qa['machine']['can'])
           || !empty($qa['asset']['can'])     || !empty($qa['jasa']['can']);
?>
<?php if ($editing && $qa_any): ?>
<div class="modal fade" id="quickAddItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i><?= lang_line('cr_quick_add_title', 'Tambah Item Baru ke Master'); ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('cr_quick_add_help', 'Item disimpan ke master sesuai jenis yang dipilih, lalu langsung terpakai di baris ini. Isian Permintaan Barang yang sudah Anda ketik tidak akan hilang.'); ?>
                </div>
                <div id="quick_add_alert" class="alert alert-danger py-2 small d-none"></div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_type', 'Jenis Item'); ?> <span class="text-danger">*</span></label>
                    <select id="qa_item_type" class="form-control">
                        <?php if (!empty($qa['inventory']['can'])): ?><option value="inventory"><?= lang_line('cr_group_inventory', 'General'); ?> — Master Barang</option><?php endif; ?>
                        <?php if (!empty($qa['machine']['can'])): ?><option value="machine"><?= lang_line('cr_group_machine', 'Mesin / Sparepart'); ?> — Master Mesin & Peralatan</option><?php endif; ?>
                        <?php if (!empty($qa['asset']['can'])): ?><option value="asset"><?= lang_line('cr_group_asset', 'Asset'); ?> — Master Asset</option><?php endif; ?>
                        <?php if (!empty($qa['jasa']['can'])): ?><option value="jasa"><?= lang_line('cr_group_jasa', 'Jasa'); ?> — Master Jasa</option><?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_name', 'Nama Item'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="qa_nama" class="form-control" maxlength="150" autocomplete="off">
                </div>

                <div class="form-group" id="qa_inv_type_wrap">
                    <label><?= lang_line('cr_quick_add_item_type', 'Jenis (Stock / Non-Stock)'); ?></label>
                    <select id="qa_inv_item_type" class="form-control">
                        <?php foreach (($qa['inventory']['item_type'] ?? []) as $it): ?>
                            <option value="<?= htmlspecialchars($it->code); ?>"><?= htmlspecialchars($it->description); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><?= lang_line('cr_quick_add_item_type_help', 'Menentukan kode MAIN (STK/ITM) pada kode barang baru.'); ?></small>
                </div>

                <div class="form-group" id="qa_usage_wrap">
                    <label><?= lang_line('inventory_usage_category', 'Jenis Pemakaian'); ?> <span class="text-danger">*</span></label>
                    <select id="qa_usage_category" class="form-control">
                        <option value="General">General</option>
                        <option value="Produksi">Produksi</option>
                    </select>
                    <small class="form-text text-muted" id="qa_usage_help"></small>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_category', 'Kategori'); ?> <span class="text-danger qa-req-kategori">*</span></label>
                    <select id="qa_kategori" class="form-control"></select>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_unit', 'Satuan'); ?></label>
                    <select id="qa_satuan" class="form-control">
                        <option value="">-- <?= lang_line('cr_quick_add_unit_default', 'Pakai default master'); ?> --</option>
                        <?php foreach (($qa['satuan'] ?? []) as $sat): ?>
                            <option value="<?= htmlspecialchars($sat->code); ?>"><?= htmlspecialchars($sat->description); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-0 d-none" id="qa_kode_wrap">
                    <label><?= lang_line('cr_quick_add_code', 'Kode'); ?> <span class="text-danger qa-req-kode d-none">*</span></label>
                    <input type="text" id="qa_kode" class="form-control" maxlength="50" autocomplete="off">
                    <small class="form-text text-muted" id="qa_kode_help"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('cr_btn_cancel', 'Batal'); ?></button>
                <button type="button" class="btn btn-primary" id="qa_submit">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('cr_quick_add_save', 'Simpan & Pakai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-view-attachment').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
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
                attachmentBody.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-file fa-3x mb-3 text-secondary"></i><p><?= lang_line('cr_receipt_att_no_preview', 'Preview tidak tersedia untuk tipe file ini.'); ?></p><p class="small"><?= lang_line('cr_receipt_att_click_download', 'Klik Download untuk membuka file.'); ?></p></div>';
            }

            $('#attachmentModal').modal('show');
        });
    });
});
</script>

<?php if ($editing): ?>
<script>
const baseUrl = '<?= base_url(); ?>';
// Mutable: General & Produksi sekarang punya saldo stock beda (gudang fisik
// terpisah) — di-refresh via AJAX tiap kali dropdown Kategori diganti.
let items_list = <?= json_encode($items_list) ?>;
const existing_details = <?= json_encode($details) ?>;
let item_row_counter = 0;

// ---- Bekal modal "Tambah Item Baru" (kembaran form Create) ----
const QUICK_ADD = <?= json_encode([
    'inventory' => ['kategori' => $qa['inventory']['kategori'] ?? []],
    'machine'   => ['kategori' => $qa['machine']['kategori'] ?? []],
    'asset'     => ['kategori' => $qa['asset']['kategori'] ?? []],
    'jasa'      => ['kategori' => $qa['jasa']['kategori'] ?? []],
]) ?>;
const QUICK_ADD_ENABLED = <?= $qa_any ? 'true' : 'false' ?>;
// Baris mana yang harus diisi item barunya, + kata kunci terakhir yang diketik
// (dipakai sbg nama awal supaya user tidak perlu mengetik ulang).
let $qaTargetSelect = null;
let qaLastTerm = '';
$(document).on('input', '.select2-search__field', function() { qaLastTerm = this.value; });

// Value <option> memakai item_key gabungan "type-id" karena id inventories &
// machines bisa bentrok. Lihat Consumable_request_model::parse_item_ref().
const ITEM_GROUPS = [
    { type: 'inventory', label: <?= json_encode(lang_line('cr_group_inventory', 'General')); ?> },
    { type: 'machine',   label: <?= json_encode(lang_line('cr_group_machine', 'Mesin / Sparepart')); ?> },
    { type: 'asset',     label: <?= json_encode(lang_line('cr_group_asset', 'Asset')); ?> },
    { type: 'jasa',      label: <?= json_encode(lang_line('cr_group_jasa', 'Jasa')); ?> },
];

function escHtmlItem(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function buildItemOptions() {
    return ITEM_GROUPS.map(g => {
        const rows = items_list.filter(i => i.item_type === g.type);
        if (!rows.length) return '';
        return '<optgroup label="' + escHtmlItem(g.label) + '">'
            + rows.map(i => `<option value="${i.item_key}">${escHtmlItem(i.kode_barang)} - ${escHtmlItem(i.nama_barang)}</option>`).join('')
            + '</optgroup>';
    }).join('');
}

function findItemByKey(key) {
    return items_list.find(i => i.item_key === key);
}

function detailItemKey(d) {
    return (d.item_type || 'inventory') + '-' + d.item_id;
}

// Konfigurasi Select2 picker item. Ditarik jadi fungsi karena dipakai ulang
// tiap kali daftar item di-refresh (tambah item baru), yang mengharuskan
// select2 di-destroy lalu di-init lagi.
//
// Saat pencarian tidak ketemu, "No results found" diganti tombol + yang
// membuka modal Tambah Item Baru. escapeMarkup dimatikan supaya tombol itu
// ter-render sbg HTML -- KOMPENSASINYA templateResult/templateSelection
// mengembalikan node jQuery ber-.text(), jadi nama item dari DB tetap
// di-escape (tidak jadi lubang XSS).
function initItemSelect2($select) {
    $select.select2({
        placeholder: '<?= lang_line('cr_select_item_ph', '-- Pilih Barang --'); ?>',
        allowClear: true,
        width: '100%',
        dropdownParent: $select.parent(),
        escapeMarkup: function(markup) { return markup; },
        templateResult: function(data) {
            if (data.loading) return data.text;
            return $('<span></span>').text(data.text);
        },
        templateSelection: function(data) {
            return $('<span></span>').text(data.text || '');
        },
        language: {
            // Select2 menaruh hasil fungsi ini lewat escapeMarkup (yang di atas
            // sudah dimatikan), jadi WAJIB balikin STRING HTML -- objek jQuery
            // akan ter-render jadi "[object Object]".
            noResults: function() {
                if (!QUICK_ADD_ENABLED) {
                    return '<span class="text-muted">'
                         + <?= json_encode(htmlspecialchars(lang_line('cr_no_item_found', 'Item tidak ditemukan'), ENT_QUOTES)); ?>
                         + '</span>';
                }
                return '<button type="button" class="btn btn-sm btn-primary btn-quick-add-item">'
                     + '<i class="fas fa-plus mr-1"></i>'
                     + <?= json_encode(htmlspecialchars(lang_line('cr_quick_add_btn', 'Tambah item baru ke master'), ENT_QUOTES)); ?>
                     + '</button>';
            }
        }
    });

    // Pakai namespace + .off() karena fungsi ini dipanggil ulang tiap refresh
    // (select2('destroy') TIDAK melepas handler jQuery custom di elemen yang
    // sama -> tanpa ini handler-nya menumpuk).
    $select.off('select2:open.qa').on('select2:open.qa', function() { $qaTargetSelect = $(this); });
}

// Ganti isi <option> SEMUA baris dgn items_list terbaru TANPA menghilangkan
// pilihan yang sudah dibuat user. select2 wajib di-destroy dulu; handler
// change milik tiap baris tetap menempel di elemen <select>-nya, jadi tidak
// perlu dipasang ulang.
function refreshAllItemOptions() {
    $('#items_body .item-select').each(function() {
        const $s = $(this);
        const current = $s.val();
        if ($s.data('select2')) { $s.select2('destroy'); }
        $s.html('<option value=""></option>' + buildItemOptions());
        if (current) { $s.val(current); }
        initItemSelect2($s);
    });
}

function buildItemRow(prefill) {
    const row_id = 'item_' + (++item_row_counter);
    const optionsHtml = '<option value=""></option>' + buildItemOptions();

    const $row = $(`
        <tr id="${row_id}">
            <td>
                <select name="items[${item_row_counter}][item_id]" class="form-control form-control-sm item-select" style="width:100%;">
                    ${optionsHtml}
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm item-unit" readonly></td>
            <td>
                <input type="number" name="items[${item_row_counter}][qty]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" required>
                <small class="text-info item-stock-warning d-none mt-1">
                    <i class="fas fa-info-circle"></i> <?= lang_line('cr_stock_warning_short', 'Qty melebihi stock — kelebihannya akan jadi <strong>outstanding</strong>.'); ?>
                </small>
            </td>
            <td class="align-middle">
                <!-- font-size .875rem = samakan dgn .form-control-sm di kolom
                     sebelahnya (<small> bawaan Bootstrap 80% x 1rem = 12.8px,
                     kelihatan lebih kecil — user 2026-09-09). -->
                <span class="text-muted item-stock-box d-none" style="font-size: .875rem;">
                    <?= lang_line('cr_th_stock', 'Stock'); ?> = <span class="item-stock font-weight-bold"></span>
                </span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `);
    $('#items_body').append($row);

    const $select = $row.find('.item-select');
    const $unit   = $row.find('.item-unit');
    const $stock  = $row.find('.item-stock');
    const $stockBox = $row.find('.item-stock-box');
    const $qty    = $row.find('.item-qty');
    const $warn   = $row.find('.item-stock-warning');

    initItemSelect2($select);

    function checkStockWarning() {
        const stock = parseFloat($qty.attr('data-stock') || 0);
        const qty = parseFloat($qty.val() || 0);
        if (qty > stock && stock > 0) {
            $warn.removeClass('d-none');
            $qty.css('border-color', '#17a2b8');
        } else {
            $warn.addClass('d-none');
            $qty.css('border-color', '');
        }
    }

    $select.on('change', function() {
        const item = this.value ? findItemByKey(this.value) : null;
        if (item) {
            $unit.val(item ? item.satuan : '');
            $stock.text(item ? parseFloat(item.stock).toFixed(2) : '0');
            $stockBox.removeClass('d-none');
            $qty.attr('data-stock', item ? item.stock : 0);
            checkStockWarning();
        } else {
            $unit.val('');
            $stock.text('');
            $stockBox.addClass('d-none');
            $qty.removeAttr('data-stock');
            $warn.addClass('d-none');
        }
    });
    $qty.on('input', checkStockWarning);

    $row.find('.btn-remove-item').on('click', function() {
        $select.select2('destroy');
        $row.remove();
        if ($('#items_body tr[id^="item_"]').length === 0) {
            $('#items_body').html('<tr class="text-center text-muted py-3 empty-row"><td colspan="5"><?= lang_line('cr_no_item', 'Belum ada item. Klik tombol "Tambah Item" di atas'); ?></td></tr>');
        }
    });

    if (prefill) {
        $select.val(detailItemKey(prefill)).trigger('change');
        $qty.val(prefill.qty_requested);
    }
}

$(function() {
    $('#btn_add_item').on('click', function() {
        $('#items_body tr.empty-row').remove();
        buildItemRow(null);
    });

    if (existing_details && existing_details.length > 0) {
        $('#items_body tr.empty-row').remove();
        existing_details.forEach(d => buildItemRow(d));
    } else {
        // Langsung sediakan 1 baris kosong kalau memang belum ada item sama
        // sekali — user tidak perlu klik "Tambah Item" dulu.
        $('#items_body tr.empty-row').remove();
        buildItemRow(null);
    }

    // Stock General & Produksi sekarang 2 angka terpisah (gudang fisik beda).
    // Ganti kategori -> refresh daftar item+stock, kosongkan item yg sudah
    // ada (stock lama sudah tidak relevan/salah bucket, harus dipilih ulang).
    $('select[name="category"]').on('change', function() {
        const category = this.value;
        if (!confirm('<?= lang_line('cr_category_change_confirm', 'Ganti kategori akan mengosongkan daftar item (stock beda per kategori). Lanjutkan?'); ?>')) {
            $(this).val('<?= htmlspecialchars($header->category ?? 'General') ?>');
            return;
        }
        $('#items_body tr[id^="item_"] .item-select').each(function() {
            if ($(this).data('select2')) { $(this).select2('destroy'); }
        });
        $('#items_body').html('<tr class="text-center text-muted py-3 empty-row"><td colspan="5"><?= lang_line('cr_no_item', 'Belum ada item. Klik tombol "Tambah Item" di atas'); ?></td></tr>');

        $.get(baseUrl + 'consumable_request/ajax_items_by_category', { category: category })
            .done(function(res) {
                if (res && res.success) { items_list = res.items; }
                $('#items_body tr.empty-row').remove();
                buildItemRow(null);
            });
    });

    $('#form_request').on('submit', function(e) {
        const items = $('#items_body tr[id^="item_"]');
        if (items.length === 0) {
            e.preventDefault();
            alert('<?= lang_line('cr_min_item_required2', 'Minimal satu item harus ada'); ?>');
            return false;
        }
    });

    // ============ QUICK ADD ITEM KE MASTER ============
    // Tombol + muncul di dalam dropdown saat pencarian item tidak ketemu.
    if (QUICK_ADD_ENABLED) { qaApplyType(); }
    $('#qa_item_type').on('change', qaApplyType);

    $(document).on('click', '.btn-quick-add-item', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const term = $('.select2-container--open .select2-search__field').val() || qaLastTerm || '';
        if ($qaTargetSelect && $qaTargetSelect.data('select2')) { $qaTargetSelect.select2('close'); }

        $('#quick_add_alert').addClass('d-none').text('');
        $('#qa_nama').val(term);
        $('#qa_kode').val('');
        $('#qa_satuan').val('');
        // Nilai awal mengikuti Kategori Kebutuhan Permintaan Barang -- diset tiap kali modal
        // dibuka, bukan sekali saat halaman dimuat, karena kategorinya bisa
        // diganti user di tengah jalan.
        $('#qa_usage_category').val(qaIrfCategory());
        qaApplyType();
        $('#quickAddItemModal').modal('show');
        setTimeout(function() { $('#qa_nama').trigger('focus'); }, 300);
    });

    $('#qa_submit').on('click', function() {
        const $btn = $(this);
        const $alert = $('#quick_add_alert').addClass('d-none').text('');
        const payload = {
            item_type:     $('#qa_item_type').val(),
            nama:          $.trim($('#qa_nama').val()),
            kode:          $.trim($('#qa_kode').val()),
            kategori:      $('#qa_kategori').val() || '',
            satuan:        $('#qa_satuan').val() || '',
            inv_item_type: $('#qa_inv_item_type').val() || '',
            // Jenis Pemakaian item baru = kategori Permintaan Barang ini. Di halaman detail
            // dropdown kategori cuma ada saat mode edit, jadi nilai header
            // dipakai sebagai cadangan.
            irf_category:   qaIrfCategory(),
            usage_category: $('#qa_usage_category').val() || ''
        };
        if (!payload.nama) {
            $alert.removeClass('d-none').text('<?= lang_line('cr_quick_add_name_required', 'Nama item wajib diisi.'); ?>');
            return;
        }

        $btn.prop('disabled', true);
        $.post(baseUrl + 'consumable_request/ajax_quick_add_item', payload)
            .done(function(res) {
                if (!res || !res.success) {
                    $alert.removeClass('d-none').text((res && res.message) || '<?= lang_line('cr_quick_add_failed', 'Gagal menyimpan item.'); ?>');
                    return;
                }
                // Item baru masuk ke daftar & ke SEMUA dropdown, pilihan baris
                // lain tetap utuh, lalu langsung dipilih di baris pemicu.
                // Item inventory yang jenis pemakaiannya beda dgn kategori Permintaan Barang ini
                // TIDAK didorong ke daftar: server pun menyaringnya keluar, jadi
                // kalau tetap dimasukkan user bisa memilih item yang sebetulnya
                // bukan haknya kategori ini. Master lain (mesin/asset/jasa) tidak
                // punya jenis pemakaian -> usage_category null -> selalu dipakai.
                const bisaDipakai = !res.item.usage_category || !qaIrfDisaring()
                    || res.item.usage_category === qaIrfCategory();

                if (bisaDipakai) {
                    items_list.push(res.item);
                    refreshAllItemOptions();
                    if ($qaTargetSelect && $qaTargetSelect.length) {
                        $qaTargetSelect.val(res.item.item_key).trigger('change');
                    }
                }
                $('#quickAddItemModal').modal('hide');

                if (typeof Swal === 'undefined') {
                    if (!bisaDipakai) alert(res.message);
                } else if (bisaDipakai) {
                    Swal.fire({ icon: 'success', title: res.message, toast: true, position: 'top-end',
                                timer: 2500, showConfirmButton: false });
                } else {
                    // Sengaja BUKAN toast sekilas: user perlu membaca kenapa item
                    // yang barusan dia buat tidak terpasang di baris ini.
                    Swal.fire({ icon: 'warning', title: res.message,
                                text: <?= json_encode(lang_line('cr_quick_add_usage_saved_elsewhere', 'Jenis Pemakaian-nya berbeda dengan Kategori Kebutuhan Permintaan Barang ini, jadi item tidak dipasang di baris ini. Buka Master Barang kalau mau mengubahnya.')); ?>,
                                confirmButtonText: 'OK' });
                }
            })
            .fail(function() {
                $alert.removeClass('d-none').text('<?= lang_line('cr_quick_add_failed', 'Gagal menyimpan item.'); ?>');
            })
            .always(function() { $btn.prop('disabled', false); });
    });
});

// Jenis item menentukan master tujuan -> daftar kategori & aturan kode ikut
// berubah (Master Mesin tidak punya generator kode, jadi kode wajib diisi).
// Kategori Permintaan Barang yang sedang dibuka -- jadi nilai awal "Jenis Pemakaian" di
// modal tambah item, dan jadi pembanding apakah item barunya boleh langsung
// dipakai di Permintaan Barang ini.
function qaIrfCategory() {
    return $('select[name="category"]').val() || "<?= htmlspecialchars($header->category ?? 'General') ?>";
}

// Peringatan kalau user sengaja memilih jenis pemakaian yang berbeda dari
// kategori Permintaan Barang ini: item-nya tetap tersimpan ke master, tapi tidak akan
// muncul di pencarian item Permintaan Barang ini (persis aturan yang disaring di server).
// Server cuma menyaring daftar item utk kategori General & Produksi; 'Jasa'
// tidak punya padanan jenis pemakaian jadi daftarnya ditampilkan utuh. Aturan
// ini dipakai bersama oleh peringatan di modal DAN keputusan apakah item baru
// boleh langsung dipasang -- supaya keduanya tidak bisa menyimpang dari server.
function qaIrfDisaring() {
    const irf = qaIrfCategory();
    return irf === 'General' || irf === 'Produksi';
}

function qaUsageHint() {
    const $help = $('#qa_usage_help');
    if ($('#qa_item_type').val() !== 'inventory') { $help.text(''); return; }
    const dipilih = $('#qa_usage_category').val();
    const irf     = qaIrfCategory();
    if (!qaIrfDisaring() || dipilih === irf) {
        $help.removeClass('text-warning').addClass('text-muted')
             .text(<?= json_encode(lang_line('cr_quick_add_usage_help', 'Menentukan segmen GNR/PRD pada kode barang baru. Mengikuti Kategori Kebutuhan Permintaan Barang ini.')); ?>);
    } else {
        $help.removeClass('text-muted').addClass('text-warning')
             .text(<?= json_encode(lang_line('cr_quick_add_usage_warn', 'Berbeda dengan Kategori Kebutuhan Permintaan Barang ini — item tetap tersimpan ke master, tapi TIDAK akan muncul di pencarian item Permintaan Barang ini.')); ?>);
    }
}
$(document).on('change', '#qa_usage_category', qaUsageHint);

function qaApplyType() {
    const type = $('#qa_item_type').val();
    const conf = QUICK_ADD[type] || { kategori: [] };

    const opts = (type === 'inventory' || type === 'machine')
        ? '<option value="">-- <?= lang_line('cr_quick_add_pick_category', 'Pilih Kategori'); ?> --</option>'
        : '<option value="">-- <?= lang_line('cr_quick_add_category_default', 'Default master'); ?> --</option>';
    $('#qa_kategori').html(opts + (conf.kategori || []).map(function(k) {
        return $('<option></option>').attr('value', k.code).text(k.description).prop('outerHTML');
    }).join(''));

    $('#qa_inv_type_wrap').toggleClass('d-none', type !== 'inventory');
    $('#qa_usage_wrap').toggleClass('d-none', type !== 'inventory');
    qaUsageHint();
    $('.qa-req-kategori').toggleClass('d-none', !(type === 'inventory' || type === 'machine'));
    $('.qa-req-kode').toggleClass('d-none', type !== 'machine');

    // Kolom Kode cuma relevan utk Mesin/Sparepart -- itu SATU-SATUNYA master
    // yang tidak punya generator kode (lihat _insert_master_item()).
    const kodeManual = (type === 'machine');
    $('#qa_kode_wrap').toggleClass('d-none', !kodeManual);
    if (!kodeManual) { $('#qa_kode').val(''); }

    $('#qa_kode_help').text(type === 'machine'
        ? <?= json_encode(lang_line('cr_quick_add_code_machine', 'Wajib diisi — Master Mesin tidak punya kode otomatis. Contoh: MES-001')); ?>
        : <?= json_encode(lang_line('cr_quick_add_code_auto', 'Kosongkan untuk kode otomatis.')); ?>);
}
</script>
<?php endif; ?>
