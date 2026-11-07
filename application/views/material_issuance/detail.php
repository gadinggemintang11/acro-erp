<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-dolly-flatbed text-primary mr-2"></i><?= lang_line('material_issuance_process', 'Proses Pengeluaran Material'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
            <?php if($header->status == 'Issued' || $header->status == 'Received'): ?>
                <span class="badge badge-success ml-2"><?= htmlspecialchars($header->issuance_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php
                // Check if there are any remaining items to issue
                $has_remaining = false;
                foreach($details as $d) {
                    $issued_so_far = (float)($d->issued_qty ?? 0);
                    $remaining_qty = $d->requested_qty - $issued_so_far;
                    if ($remaining_qty > 0) {
                        $has_remaining = true;
                        break;
                    }
                }
                // Item Release: attachment "Bukti Pengeluaran" wajib diupload
                // SETELAH pengeluaran terjadi (bukan syarat sebelum), pola
                // sama dgn Production_receive (aksi dulu, tindak lanjut nyusul).
                // Fase 2 poin 8.
                $needs_attachment = (in_array($header->status, ['Partial Issued', 'Partial Received', 'Issued', 'Received']) && empty($attachments));
            ?>
            <?php if ((in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received']) && $has_remaining) && check_access('material_issuance', 'edit')): ?>
                <button type="submit" form="issuanceForm" class="btn btn-primary btn-sm mr-1" id="btnConfirmIssuanceHead">
                    <i class="fas fa-check-circle fa-sm"></i>
                    <?php if (in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received'])): ?>
                        <?= lang_line('material_issuance_confirm', 'Confirm Issuance'); ?>
                    <?php else: ?>
                        <?= $has_remaining ? lang_line('material_issuance_issue_again', 'Keluarkan Lagi') : lang_line('material_issuance_confirm', 'Confirm Issuance'); ?>
                    <?php endif; ?>
                </button>
            <?php endif; ?>
            <?php if ($header->status == 'Issued' && check_access('material_issuance', 'edit')): ?>
                <a href="<?= base_url('material_issuance/reverse_issuance/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('<?= lang_line('material_issuance_reverse_confirm', 'Yakin ingin membatalkan pengeluaran ini? Stok akan dikembalikan.'); ?>')">
                    <i class="fas fa-undo fa-sm"></i> <?= lang_line('material_issuance_reverse', 'Batal Keluarkan (Un-issue)'); ?>
                </a>
            <?php endif; ?>
            <?php if (($header->status == 'Issued' || $header->status == 'Received') && check_access('material_issuance', 'view')): ?>
                <a href="<?= base_url('material_issuance/export_pdf/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" target="_blank">
                    <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('material_issuance_export_pdf', 'Export PDF'); ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('material_issuance'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('material_issuance_back', 'Kembali'); ?>
            </a>
        </div>
    </div>
    <?php
        // Kartu "Buat PR Sisanya" — sama dengan jalur IRF/general (user
        // 2026-09-21). Tampil kalau MRF masih bersisa DAN ada yang perlu
        // di-PR-kan, atau sekadar memberi tahu PR-nya sudah pernah dibuat.
        $mi_need_pr  = (float) ($remaining_pr_qty ?? 0) > 0.0001;
        $mi_has_docs = !empty($pr_docs);
    ?>
    <?php if (!empty($remaining_outstanding) && !empty($can_create_pr) && ($mi_need_pr || $mi_has_docs)): ?>
    <div class="card border-left-<?= $mi_need_pr ? 'warning' : 'info' ?> shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <?php if ($mi_need_pr): ?>
                    <i class="fas fa-shopping-cart text-warning mr-1"></i>
                    <strong><?= lang_line('mi_remaining_pr_title', 'Stock belum cukup:'); ?></strong>
                    <?= lang_line('mi_remaining_pr_desc', 'Material Request ini masih punya sisa yang belum dikeluarkan. Qty PR = sisa dikurangi stok gudang dan qty yang sudah ada di PR sebelumnya.'); ?>
                    <?php else: ?>
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    <strong><?= lang_line('mi_remaining_nopr_title', 'Masih ada sisa Material Request:'); ?></strong>
                    <?= lang_line('mi_remaining_nopr_desc', 'PR untuk sisa ini sudah dibuat, atau sisanya sudah bisa dipenuhi dari stok gudang.'); ?>
                    <?php endif; ?>
                </div>
                <?php if ($mi_need_pr): ?>
                <form method="POST" action="<?= base_url('material_outstanding/create_pr') ?>" class="mt-2 mt-sm-0 ml-sm-3 flex-shrink-0"
                      onsubmit="return confirm('<?= htmlspecialchars(lang_line('mi_remaining_pr_confirm', 'Buat Purchase Request untuk semua sisa outstanding Material Request ini?'), ENT_QUOTES) ?>');">
                    <input type="hidden" name="redirect_to" value="<?= base_url('material_issuance/detail/' . $header->id) ?>">
                    <?php foreach ($remaining_outstanding as $r): ?>
                        <input type="hidden" name="mr_detail_ids[]" value="<?= (int) $r->mr_detail_id ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('mi_btn_remaining_pr', 'Buat PR Sisanya'); ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm mb-0 small">
                    <thead>
                        <tr class="text-muted">
                            <th><?= lang_line('mi_rem_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('mi_rem_th_item', 'Material'); ?></th>
                            <th class="text-right"><?= lang_line('mi_rem_th_outstanding', 'Sisa Material Request'); ?></th>
                            <th class="text-right"><?= lang_line('mi_rem_th_stock', 'Dari Stok'); ?></th>
                            <th class="text-right"><?= lang_line('mi_rem_th_on_pr', 'Sudah di PR'); ?></th>
                            <th class="text-right"><?= lang_line('mi_rem_th_pr', 'Perlu PR'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($remaining_outstanding as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r->item_code ?? '-') ?></td>
                            <td><?= htmlspecialchars($r->item_name ?? '-') ?></td>
                            <td class="text-right"><?= number_format((float) $r->qty_outstanding, 2) ?></td>
                            <td class="text-right" title="<?= htmlspecialchars(sprintf(lang_line('mi_rem_stock_hint', 'Stok gudang saat ini: %s'), number_format((float) $r->stock_available, 2))) ?>"><?= number_format((float) $r->qty_from_stock, 2) ?></td>
                            <td class="text-right">
                                <?= number_format((float) $r->qty_on_pr, 2) ?>
                                <?php $this->load->view('templates/partials/pr_doc_links', ['docs' => $pr_docs[(int) $r->mr_detail_id] ?? []]); ?>
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

    <?php if ($needs_attachment): ?>
    <div class="alert alert-warning py-2 mb-3">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong><?= lang_line('material_issuance_need_attachment_title2', 'Belum selesai:'); ?></strong>
        <?= lang_line('material_issuance_need_attachment2', 'Barang sudah dikeluarkan — upload attachment bukti pengeluaran di bawah untuk menyelesaikan dokumen ini.'); ?>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong><?= lang_line('material_issuance_mr_number', 'No. Material Request'); ?>:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_request_date', 'Tanggal'); ?>:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_status', 'Status'); ?>:</strong>
                    <?php
                        $color = 'secondary';
                        if ($header->status == 'Approved')          $color = 'warning';
                        if ($header->status == 'Partial Issued')    $color = 'info';
                        if ($header->status == 'Partial Received')  $color = 'info';
                        if ($header->status == 'Issued')             $color = 'success';
                        if ($header->status == 'Received')           $color = 'primary';
                    ?>
                    <span class="badge badge-<?= $color; ?>"><?= $header->status; ?></span>
                </div>
            </div>
             <div class="row mt-2">
                <div class="col-md-4"><strong><?= lang_line('material_issuance_requester', 'Dibuat Oleh'); ?>:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_issued_by', 'Disiapkan Oleh'); ?>:</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_received_by', 'Diterima Oleh'); ?>:</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <form id="issuanceForm" method="post" action="<?= base_url('material_issuance/process_issuance/'.$header->id); ?>">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('material_issuance_items_issuance', 'Item Pengeluaran'); ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="itemIssuanceTable">
                        <thead class="thead-light text-center">
                            <tr>
                                <th><?= lang_line('material_issuance_wo', 'WO'); ?></th>
                                <th><?= lang_line('material_issuance_item_code', 'Kode Item'); ?></th>
                                <th><?= lang_line('material_issuance_item_name', 'Nama Item'); ?></th>
                                <th><?= lang_line('material_issuance_unit', 'Satuan'); ?></th>
                                <th style="width:12%"><?= lang_line('material_issuance_qty_request', 'Qty Diminta'); ?></th>
                                <th style="width:12%"><?= lang_line('material_issuance_qty_issued_so_far'); ?></th>
                                <th style="width:12%"><?= lang_line('material_issuance_qty_remaining'); ?></th>
                                <th style="width:12%"><?= lang_line('material_issuance_available_stock', 'Stok Tersedia'); ?></th>
                                <th style="width:12%"><?= lang_line('material_issuance_qty_issue', 'Qty Keluarkan Sekarang'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($details)): foreach($details as $d):
                                $issued_so_far = (float)($d->issued_qty ?? 0);
                                $remaining_qty = $d->requested_qty - $issued_so_far;
                                // For first issuance (Submitted), default to remaining. For re-issuance (Issued), default to 0
                                $default_qty = (in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received'])) ? max(0, $remaining_qty) : 0;
                            ?>
                            <tr>
                                <td class="text-center"><?= htmlspecialchars($d->wo_number); ?></td>
                                <td><?= htmlspecialchars($d->item_code); ?></td>
                                <td><?= htmlspecialchars($d->item_name); ?></td>
                                <td class="text-center"><?= htmlspecialchars($d->item_unit); ?></td>
                                <td class="text-right qty-requested"><?= number_format($d->requested_qty, 2); ?></td>
                                <td class="text-right" style="background-color: #f0f0f0;"><?= number_format($issued_so_far, 2); ?></td>
                                <td class="text-right" style="background-color: #fff9e6;"><strong><?= number_format($remaining_qty, 2); ?></strong></td>

                                <td class="text-right stock-on-hand-cell">
                                    <?= number_format($d->total_available_stock, 2); ?>
                                </td>
                                <td>
                                    <?php if((in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received']) && ($remaining_qty > 0 || in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received']))) && check_access('material_issuance', 'edit')): ?>
                                    <input type="number" step="0.01" min="0"
                                           name="items[<?= $d->detail_id; ?>][qty]"
                                           class="form-control form-control-sm text-right qty-issued"
                                           value="<?= number_format($default_qty, 2, '.', ''); ?>"
                                           data-maxstock="<?= $d->total_available_stock; ?>"
                                           data-remaining="<?= $remaining_qty; ?>"
                                           required> <?php else: ?>
                                        <p class="form-control-static text-right mb-0">-</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-5"><?= lang_line('material_issuance_no_items', 'Item Permintaan Barang tidak ditemukan'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received']) && $has_remaining): ?>
            <div class="card-footer text-right">
                <button type="submit" form="issuanceForm" class="btn btn-primary btn-load" id="btnConfirmIssuanceFoot">
                    <i class="fas fa-check-circle mr-1"></i>
                    <?php if (in_array($header->status, ['Approved', 'Partial Issued', 'Partial Received'])): ?>
                        <?= lang_line('material_issuance_confirm_issuance', 'Konfirmasi Pengeluaran'); ?>
                    <?php else: ?>
                        <?= $has_remaining ? lang_line('material_issuance_issue_again', 'Keluarkan Lagi') : lang_line('material_issuance_confirm_issuance', 'Konfirmasi Pengeluaran'); ?>
                    <?php endif; ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Attachments: Bukti Pengeluaran (Item Release, wajib diupload SETELAH pengeluaran terjadi) -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-paperclip mr-1"></i> <?= lang_line('material_issuance_attachments_title', 'Bukti Pengeluaran'); ?> (<?= count($attachments); ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (check_access('material_issuance', 'edit')): ?>
            <form method="POST" action="<?= base_url('material_issuance/upload_attachment/' . $header->id); ?>" enctype="multipart/form-data" class="mb-3">
                <div class="form-group mb-0">
                    <label for="attachment" class="small font-weight-bold"><?= lang_line('material_issuance_lbl_choose_file', 'Pilih File'); ?></label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="attachment" id="attachment" class="form-control-file" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> <?= lang_line('material_issuance_btn_upload', 'Upload'); ?></button>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <?php if (empty($attachments)): ?>
                <p class="text-muted text-center py-3 mb-0"><?= lang_line('material_issuance_no_attachment', 'Belum ada attachment.'); ?></p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th><?= lang_line('material_issuance_th_att_file', 'File'); ?></th><th><?= lang_line('material_issuance_th_att_type', 'Type'); ?></th><th><?= lang_line('material_issuance_th_att_size', 'Size'); ?></th><th><?= lang_line('material_issuance_th_att_uploaded', 'Uploaded'); ?></th><th width="140"><?= lang_line('label_action', 'Aksi'); ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $a): ?>
                        <tr>
                            <td><i class="fas fa-file mr-1"></i> <?= htmlspecialchars($a->original_name); ?></td>
                            <td class="small"><?= htmlspecialchars($a->file_type); ?></td>
                            <td class="small"><?= number_format($a->file_size / 1024, 1); ?> KB</td>
                            <td class="small"><?= date('d M Y H:i', strtotime($a->created_at)); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link text-info btn-view-attachment" data-id="<?= $a->id; ?>" data-name="<?= htmlspecialchars($a->original_name); ?>" data-url="<?= base_url('material_issuance/attachment/' . $a->id); ?>"><i class="fas fa-eye mr-1"></i> <?= lang_line('material_issuance_btn_view', 'View'); ?></button>
                                <?php if ($header->status !== 'Received' && check_access('material_issuance', 'edit')): ?>
                                <a href="<?= base_url('material_issuance/delete_attachment/' . $a->id); ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('<?= lang_line('material_issuance_confirm_delete_att', 'Hapus attachment?'); ?>');"><i class="fas fa-trash"></i> <?= lang_line('material_issuance_btn_delete_att', 'Hapus'); ?></a>
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
                    <p><?= lang_line('material_issuance_att_loading', 'Loading...'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <a id="attachmentDownload" href="#" class="btn btn-primary" download><i class="fas fa-download mr-1"></i> <?= lang_line('material_issuance_att_download', 'Download'); ?></a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('material_issuance_att_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

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

            const attachmentBody = document.getElementById('attachmentBody');
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (imageExtensions.includes(fileExtension)) {
                attachmentBody.innerHTML = '<img src="' + fileUrl + '" style="max-width: 100%; height: auto;" class="rounded" />';
            } else if (fileExtension === 'pdf') {
                attachmentBody.innerHTML = '<embed src="' + fileUrl + '" type="application/pdf" style="width: 100%; height: 500px;" />';
            } else {
                attachmentBody.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-file fa-3x mb-3 text-secondary"></i><p><?= lang_line('material_issuance_att_no_preview', 'Preview tidak tersedia untuk tipe file ini.'); ?></p><p class="small"><?= lang_line('material_issuance_att_click_download', 'Klik Download untuk membuka file.'); ?></p></div>';
            }

            $('#attachmentModal').modal('show');
        });
    });
});
</script>
<?php $this->load->view('templates/footer'); // Panggil footer utama ?>

<script>
$(document).ready(function() {
    
    // 1. Fungsi untuk validasi Qty Input (Lebih Sederhana)
    function validateQty(input) {
        const currentVal = parseFloat(input.val() || 0);
        const maxStock = parseFloat(input.attr('data-maxstock') || 0); // Baca dari 'data-maxstock'
        
        let isValid = true;
        
        if (currentVal < 0) { 
            input.val(0); // Jangan input minus
            isValid = true; // Anggap valid
        } else if (currentVal > maxStock) {
            input.removeClass('is-valid').addClass('is-invalid'); // Tandai merah jika > stok
            isValid = false;
        } else {
            input.removeClass('is-invalid').addClass('is-valid'); // Tandai hijau
            isValid = true;
        }
        return isValid;
    }

    // 2. Validasi semua input saat halaman dimuat (jika status Submitted, Issued, atau Received dengan sisa qty)
    const status = '<?= $header->status; ?>';
    if (status === 'Approved' || status === 'Partial Issued' || status === 'Partial Received' || status === 'Issued' || status === 'Received') {
        let allValidOnLoad = true;
        let hasRemainingQty = false;
        $('.qty-issued').each(function() {
            if (!validateQty($(this))) {
                allValidOnLoad = false;
            }
            const remainingQty = parseFloat($(this).data('remaining') || 0);
            if (remainingQty > 0) {
                hasRemainingQty = true;
            }
        });

        // Check if all items are fully issued (sisa qty = 0)
        let allFullyIssued = !hasRemainingQty;

        // Disable form & button hanya jika semua items fully issued (sisa qty = 0)
        if (allFullyIssued) {
            $('.qty-issued').prop('readonly', true).addClass('form-control-plaintext');
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', true).html(
                '<i class="fas fa-check-circle mr-1"></i> <?= lang_line('material_issuance_all_issued', 'Semua Material Sudah Dikeluarkan'); ?>'
            );
        } else {
            // Form dapat diedit karena masih ada sisa qty
            // Nonaktifkan tombol simpan jika ada yg tidak valid saat load
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', !allValidOnLoad);
            if(!allValidOnLoad){
                 alert('<?= lang_line('material_issuance_stock_warning', 'PERHATIAN: Beberapa item memiliki stok tersedia yang lebih kecil dari qty diminta. Qty dikeluarkan telah disesuaikan.'); ?>');
            }
        }

        // 3. Tambahkan event listener 'input'
        $('#itemIssuanceTable').on('input', '.qty-issued', function() {
            let allValid = true;
            $('.qty-issued').each(function() {
                 // Panggil validateQty untuk setiap input saat ada perubahan
                 if (!validateQty($(this))) {
                     allValid = false;
                 }
            });
            // Aktifkan/nonaktifkan tombol simpan berdasarkan validitas semua input
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', !allValid);
        });
        
        // 4. Konfirmasi sebelum submit form
        $('#issuanceForm').on('submit', function(e) {
            let confirmationText = "<?= lang_line('material_issuance_confirm_submit', 'Yakin ingin mengkonfirmasi pengeluaran ini? Stok akan dipotong.'); ?>";
            let hasInvalid = false;
            
            $('.qty-issued').each(function() {
                 if ($(this).hasClass('is-invalid')) { // Cek kelas invalid
                     hasInvalid = true;
                 }
                 // Cek jika issue < request (Peringatan)
                 if (parseFloat($(this).val() || 0) < parseFloat($(this).data('requested') || 0)) {
                     confirmationText = "<?= lang_line('material_issuance_qty_less_warning', 'PERHATIAN: Ada Qty Dikeluarkan lebih kecil dari Qty Diminta. Yakin lanjut?'); ?>";
                 }
            });
            
            if(hasInvalid) {
                 alert('<?= lang_line('material_issuance_qty_exceed_error', 'Terdapat item dengan Qty Dikeluarkan melebihi Stok Tersedia. Harap perbaiki.'); ?>');
                 e.preventDefault(); // Hentikan submit
                 return false;
            }
            
            if (!confirm(confirmationText)) {
                 e.preventDefault(); // Hentikan submit jika user klik 'Cancel'
                 return false;
            }
            
            // Jika lolos, tambahkan spinner ke tombol
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> <?= lang_line('material_issuance_processing', 'Memproses...'); ?>');
        });
    }
});
</script>

<style>
/* Style validasi input */
.qty-issued.is-invalid { 
    border-color: #e74a3b; 
    background-image: none; /* Hilangkan ikon ceklis/silang bootstrap */
}
.qty-issued.is-valid { 
    border-color: #1cc88a; 
    background-image: none; /* Hilangkan ikon ceklis/silang bootstrap */
}
.btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>