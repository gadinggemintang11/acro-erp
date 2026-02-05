<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-file-alt"></i> Dokumen Adjustment
        <small class="text-muted">(<?= htmlspecialchars($header->doc_no); ?>)</small>
    </h5>
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <!-- Header Info -->
    <div class="row mb-3">
        <div class="col-md-6">
            <table class="table table-sm table-borderless">
                <tr><th width="120">No. Dokumen:</th><td><?= htmlspecialchars($header->doc_no); ?></td></tr>
                <tr><th>Tanggal:</th><td><?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
                <tr><th>Status:</th><td>
                    <span class="badge badge-<?= ($header->status == 'Confirmed') ? 'success' : 'warning'; ?>">
                        <?= htmlspecialchars($header->status); ?>
                    </span>
                </td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-sm table-borderless">
                <tr><th width="120">Dibuat Pada:</th><td><?= $header->created_at ? date('d M Y H:i', strtotime($header->created_at)) : '-'; ?></td></tr>
                <tr><th>Catatan:</th><td><?= htmlspecialchars($header->notes ?? '-'); ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Dokumen yang diupload -->
    <div class="card">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="fas fa-paperclip"></i> Dokumen Pendukung</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($documents)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama File</th>
                                <th>Deskripsi</th>
                                <th>Ukuran</th>
                                <th>Tanggal Upload</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <?php 
                                        $ext = pathinfo($doc->file_name, PATHINFO_EXTENSION);
                                        $icon = 'file';
                                        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) $icon = 'image';
                                        elseif (strtolower($ext) === 'pdf') $icon = 'pdf';
                                        elseif (in_array(strtolower($ext), ['doc','docx'])) $icon = 'word';
                                        elseif (in_array(strtolower($ext), ['xls','xlsx'])) $icon = 'excel';
                                        ?>
                                        <i class="fas fa-file-<?= $icon; ?> text-muted"></i>
                                        <?= htmlspecialchars($doc->file_name); ?>
                                    </td>
                                    <td><?= htmlspecialchars($doc->description ?? '-'); ?></td>
                                    <td><?= number_format($doc->file_size / 1024, 1); ?> KB</td>
                                    <td><?= date('d M Y H:i', strtotime($doc->uploaded_at)); ?></td>
                                    <td>
                                        <a href="<?= base_url('adjustment/view_document/' . $doc->id); ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Download" target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="previewImage('<?= base_url($doc->file_path); ?>')" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada dokumen yang diupload</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Items -->
    <div class="card mt-3">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="fas fa-list"></i> Detail Adjustment</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Item</th>
                            <th>Tipe</th>
                            <th>Adjustment</th>
                            <th>Qty</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="adjustmentDetailsTable">
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
    <a href="<?= base_url('adjustment/view/' . $header->id); ?>" class="btn btn-primary" target="_blank">
        <i class="fas fa-external-link-alt"></i> Lihat Detail Lengkap
    </a>
</div>

<!-- Modal untuk preview gambar -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Gambar</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" class="img-fluid" style="max-height: 500px;">
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load adjustment details
    loadAdjustmentDetails(<?= $header->id; ?>);
});

function loadAdjustmentDetails(adjustmentId) {
    $.get('<?= base_url("adjustment/ajax_get_details"); ?>', {adjustment_id: adjustmentId})
    .done(function(response) {
        if (response.success && response.data && response.data.length > 0) {
            let html = '';
            let no = 1;
            response.data.forEach(function(item) {
                let badgeClass = item.adjustment_type === 'IN' ? 'badge-success' : 'badge-danger';
                let typeIcon = item.adjustment_type === 'IN' ? 'fa-plus' : 'fa-minus';
                
                html += `<tr>
                    <td>${no++}</td>
                    <td>${item.item_name || item.item_code || '-'}</td>
                    <td><span class="badge badge-info">${item.item_type}</span></td>
                    <td><span class="badge ${badgeClass}"><i class="fas ${typeIcon}"></i> ${item.adjustment_type}</span></td>
                    <td class="text-right">${parseFloat(item.qty).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                    <td>${item.remarks || '-'}</td>
                </tr>`;
            });
            $('#adjustmentDetailsTable').html(html);
        } else {
            $('#adjustmentDetailsTable').html('<tr><td colspan="6" class="text-center text-muted">Tidak ada detail ditemukan</td></tr>');
        }
    })
    .fail(function() {
        $('#adjustmentDetailsTable').html('<tr><td colspan="6" class="text-center text-danger">Error loading data</td></tr>');
    });
}

function previewImage(imagePath) {
    $('#previewImage').attr('src', imagePath);
    $('#imagePreviewModal').modal('show');
}
</script>
