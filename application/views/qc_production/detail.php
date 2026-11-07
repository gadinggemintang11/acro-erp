<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$base = base_url();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Left Column: QC Detail -->
        <div class="col-md-8">
            <!-- Header Info Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-info-circle"></i> <?= lang_line('qcp_detail_title', 'Detail QC Production'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_prod_number', 'Nomor Produksi:'); ?></label>
                                <p><?= htmlspecialchars($qc_detail->production_number); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_wo_number', 'WO Number:'); ?></label>
                                <p><?= htmlspecialchars($qc_detail->wo_number ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_stage', 'Stage:'); ?></label>
                                <p>
                                    <span class="badge badge-info badge-lg">
                                        <?= ucfirst($qc_detail->stage_name); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_prod_date', 'Tanggal Produksi:'); ?></label>
                                <p><?= !empty($qc_detail->production_date) ? date('d/m/Y', strtotime($qc_detail->production_date)) : 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Item QC Info -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 font-weight-bold">
                                <i class="fas fa-box"></i> <?= lang_line('qcp_section_item_checked', 'Item yang Diperiksa'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_item_name', 'Nama Item:'); ?></label>
                                        <p><?= htmlspecialchars($qc_detail->item_name); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_item_code', 'Kode Item:'); ?></label>
                                        <p><?= htmlspecialchars($qc_detail->item_code ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_item_type', 'Tipe Item:'); ?></label>
                                        <p>
                                            <span class="badge badge-secondary">
                                                <?= ucfirst($qc_detail->item_type); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_qty_checked', 'Qty yang Diperiksa:'); ?></label>
                                        <p>
                                            <span class="badge badge-success badge-lg">
                                                <?= number_format($qc_detail->qty_qc, 2); ?>
                                            </span>
                                            <?php if (!empty($qc_detail->satuan)): ?>
                                                <span class="ml-2"><?= htmlspecialchars($qc_detail->satuan); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Size Breakdown (jika ada) -->
                            <?php if (!empty($size_breakdown)): ?>
                            <div class="form-group mt-3">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_size_breakdown', 'Breakdown Size:'); ?></label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th><?= lang_line('qcp_th_size', 'Size'); ?></th>
                                                <th><?= lang_line('qcp_qty', 'Qty'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($size_breakdown as $size): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($size['size']); ?></td>
                                                <td class="text-right"><?= number_format($size['qty'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- QC Information -->
                    <div class="card bg-light border-0">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 font-weight-bold">
                                <i class="fas fa-check-square"></i> <?= lang_line('qcp_section_qc_info', 'Informasi QC'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_checked_by', 'Diperiksa Oleh:'); ?></label>
                                        <p>
                                            <strong><?= htmlspecialchars($qc_detail->checked_by_name ?? 'N/A'); ?></strong>
                                            <?php if (!empty($qc_detail->checked_by_email)): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($qc_detail->checked_by_email); ?></div>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold"><?= lang_line('qcp_label_qc_datetime', 'Tanggal & Jam QC:'); ?></label>
                                        <p>
                                            <?php if (!empty($qc_detail->qc_checked_at)): ?>
                                                <div><?= date('d/m/Y H:i:s', strtotime($qc_detail->qc_checked_at)); ?></div>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold"><?= lang_line('qcp_label_qc_desc', 'Deskripsi QC:'); ?></label>
                                <div class="bg-white border rounded p-3">
                                    <?php if (!empty($qc_detail->qc_description)): ?>
                                        <?= nl2br(htmlspecialchars($qc_detail->qc_description)); ?>
                                    <?php else: ?>
                                        <em class="text-muted"><?= lang_line('qcp_no_desc', 'Tidak ada deskripsi'); ?></em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Photo & Input Items -->
        <div class="col-md-4">
            <!-- QC Photo Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-gradient-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-link"></i> <?= lang_line('qcp_section_photo_link', 'Link Foto QC'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($qc_detail->qc_photo_url)): ?>
                        <div id="photoPreview" style="margin-bottom: 15px; min-height: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background-color: #f9f9f9;">
                            <!-- Preview akan dimuat di sini -->
                            <div style="text-align: center; padding: 20px; color: #999;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>
                                <?= lang_line('qcp_loading_preview', 'Loading preview...'); ?>
                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($qc_detail->qc_photo_url); ?>" readonly>
                            <div class="input-group-append">
                                <a href="<?= htmlspecialchars($qc_detail->qc_photo_url); ?>" target="_blank" class="btn btn-success">
                                    <i class="fas fa-external-link-alt"></i> <?= lang_line('qcp_btn_open_link', 'Buka Link'); ?>
                                </a>
                            </div>
                        </div>
                        <small class="text-muted d-block">
                            <i class="fas fa-info-circle"></i> <?= lang_line('qcp_photo_hint', 'Klik "Buka Link" untuk membuka foto di tab baru'); ?>
                        </small>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?= lang_line('qcp_no_photo', 'Tidak ada link foto QC'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Input Materials Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-warning text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list"></i> <?= sprintf(lang_line('qcp_section_input_mat', 'Material Input (Stage %s)'), ucfirst($qc_detail->stage_name)); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($stage_inputs)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th><?= lang_line('qcp_th_item', 'Item'); ?></th>
                                        <th><?= lang_line('qcp_th_qty_used', 'Qty'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stage_inputs as $input): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars(substr($input->item_name, 0, 20)); ?></strong>
                                            <?php if (!empty($input->item_code)): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($input->item_code); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><?= number_format($input->qty_used, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($stage_inputs[0]->notes)): ?>
                            <div class="mt-3 alert alert-light">
                                <strong><?= lang_line('qcp_label_notes', 'Notes:'); ?></strong> <?= nl2br(htmlspecialchars($stage_inputs[0]->notes)); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-inbox"></i> <?= lang_line('qcp_no_input_mat', 'Tidak ada material input'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="d-flex gap-2">
                <a href="<?= $base; ?>qc_production" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?= lang_line('qcp_btn_back_to_list', 'Kembali ke Daftar'); ?>
                </a>
                <a href="<?= $base; ?>qc_production/export_pdf/<?= $qc_detail->id; ?>" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('qcp_btn_export_pdf', 'Export PDF'); ?>
                </a>
                <a href="<?= $base; ?>production_process/<?= $qc_detail->stage_name; ?>/<?= $qc_detail->production_header_id; ?>" class="btn btn-info">
                    <i class="fas fa-link"></i> <?= lang_line('qcp_btn_view_process', 'Lihat Proses Produksi'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load photo preview jika ada
    <?php if (!empty($qc_detail->qc_photo_url)): ?>
    loadPhotoPreview('<?= addslashes(htmlspecialchars($qc_detail->qc_photo_url)); ?>');
    <?php endif; ?>
});

function loadPhotoPreview(photoUrl) {
    let previewDiv = document.getElementById('photoPreview');

    if (photoUrl.includes('drive.google.com')) {
        // Google Drive link
        let fileId = photoUrl.match(/\/d\/([^\/]+)/);
        if (fileId && fileId[1]) {
            let iframeUrl = `https://drive.google.com/file/d/${fileId[1]}/preview`;
            previewDiv.innerHTML = `<iframe src="${iframeUrl}" width="100%" height="400" style="border: none; border-radius: 4px;"></iframe>`;
        } else {
            previewDiv.innerHTML = `<div class="alert alert-info" style="margin: 0;"><i class="fas fa-link"></i> Google Drive Link (Preview tidak tersedia)</div>`;
        }
    } else if (photoUrl.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
        // Direct image URL
        previewDiv.innerHTML = `<img src="${photoUrl}" style="max-width: 100%; max-height: 400px; border-radius: 4px; object-fit: contain;">`;
    } else if (photoUrl.includes('imgur') || photoUrl.includes('cloudinary') || photoUrl.includes('imgbb')) {
        // Image hosting services
        previewDiv.innerHTML = `<img src="${photoUrl}" style="max-width: 100%; max-height: 400px; border-radius: 4px; object-fit: contain;">`;
    } else {
        // Generic link
        previewDiv.innerHTML = `<div class="alert alert-info" style="margin: 0;"><i class="fas fa-link"></i> Link Preview</div>`;
    }
}
</script>
