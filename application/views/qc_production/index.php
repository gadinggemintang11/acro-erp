<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$base = base_url();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header text-white" style="background-color: var(--brand-1);">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-check-square"></i> <?= lang_line('qcp_section_title', 'QC Production - Daftar Pemeriksaan Kualitas'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="<?= $base; ?>qc_production" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="search" class="mr-2"><?= lang_line('qcp_search_label', 'Pencarian:'); ?></label>
                                    <input type="text" class="form-control" id="search" name="search"
                                           placeholder="<?= lang_line('qcp_search_placeholder', 'Nomor Produksi / Item Name'); ?>"
                                           value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                                </div>

                                <div class="form-group mr-3">
                                    <label for="stage" class="mr-2"><?= lang_line('qcp_stage_label', 'Stage:'); ?></label>
                                    <select class="form-control" id="stage" name="stage">
                                        <option value=""><?= lang_line('qcp_all_stages', '-- Semua Stage --'); ?></option>
                                        <?php foreach ($stages as $st): ?>
                                            <option value="<?= $st; ?>" <?= ($filters['stage'] ?? '') == $st ? 'selected' : ''; ?>>
                                                <?= ucfirst($st); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group mr-3">
                                    <label for="date_from" class="mr-2"><?= lang_line('qcp_date_from', 'Dari:'); ?></label>
                                    <input type="date" class="form-control" id="date_from" name="date_from"
                                           value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>">
                                </div>

                                <div class="form-group mr-3">
                                    <label for="date_to" class="mr-2"><?= lang_line('qcp_date_to', 'Sampai:'); ?></label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                           value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>">
                                </div>

                                <button type="submit" class="btn mr-2" style="background-color: var(--brand-1); color: white;">
                                    <i class="fas fa-search"></i> <?= lang_line('qas_btn_filter', 'Filter'); ?>
                                </button>
                                <a href="<?= $base; ?>qc_production" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> <?= lang_line('btn_reset', 'Reset'); ?>
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Statistics Section -->
                    <?php if (!empty($qc_stats)): ?>
                    <div class="row mb-4">
                        <?php foreach ($qc_stats as $stat): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light border-left-primary">
                                <div class="card-body">
                                    <div class="text-primary font-weight-bold text-uppercase mb-1">
                                        <?= ucfirst($stat->stage_name); ?>
                                    </div>
                                    <div class="h5 mb-0">
                                        <span class="font-weight-bold"><?= number_format($stat->total_qc_records); ?></span> <?= lang_line('qcp_records', 'Records'); ?> |
                                        <span class="font-weight-bold"><?= number_format($stat->total_qty_checked, 2); ?></span> <?= lang_line('qcp_qty', 'Qty'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Table Section -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient-light">
                                <tr>
                                    <th style="width: 15%;"><?= lang_line('qcp_th_prod_number', 'Nomor Produksi'); ?></th>
                                    <th style="width: 12%;"><?= lang_line('qcp_th_stage', 'Stage'); ?></th>
                                    <th style="width: 20%;"><?= lang_line('qcp_th_item', 'Item'); ?></th>
                                    <th style="width: 8%;"><?= lang_line('qcp_th_qty_qc', 'Qty QC'); ?></th>
                                    <th style="width: 15%;"><?= lang_line('qcp_th_checked_by', 'Diperiksa Oleh'); ?></th>
                                    <th style="width: 12%;"><?= lang_line('qcp_th_qc_date', 'Tanggal QC'); ?></th>
                                    <th style="width: 10%;"><?= lang_line('qcp_th_photo', 'Foto'); ?></th>
                                    <th style="width: 8%;"><?= lang_line('label_action', 'Aksi'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($qc_records)): ?>
                                    <?php foreach ($qc_records as $qc): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($qc->production_number); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= ucfirst($qc->stage_name); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($qc->item_name); ?></strong>
                                                <?php if (!empty($qc->item_code)): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($qc->item_code); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success"><?= number_format($qc->qty_qc, 2); ?></span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($qc->checked_by_name ?? 'N/A'); ?></strong>
                                                <?php if (!empty($qc->checked_by_email)): ?>
                                                <div class="small text-muted"><?= htmlspecialchars($qc->checked_by_email); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($qc->qc_checked_at)): ?>
                                                <div><?= date('d/m/Y H:i', strtotime($qc->qc_checked_at)); ?></div>
                                                <div class="small text-muted"><?= date('H:i', strtotime($qc->qc_checked_at)); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($qc->qc_photo_url)): ?>
                                                <a href="<?= htmlspecialchars($qc->qc_photo_url); ?>" target="_blank" class="btn btn-sm btn-info" title="<?= lang_line('qcp_photo_tooltip', 'Buka Link Foto'); ?>" onclick="viewPhoto(event, '<?= htmlspecialchars($qc->qc_photo_url); ?>')">
                                                    <i class="fas fa-image"></i> <?= lang_line('qcp_btn_photo_view', 'Lihat'); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= lang_line('qcp_no_link', 'No Link'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $base; ?>qc_production/view/<?= $qc->id; ?>" class="btn btn-sm btn-primary" title="<?= lang_line('qcp_btn_detail_tooltip', 'Lihat Detail'); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> <?= lang_line('qcp_no_data', 'Tidak ada data QC Production'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Photo Preview -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="photoModalLabel"><?= lang_line('qcp_modal_photo_title', 'QC Photo Link'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="photoContainer" style="text-align: center; min-height: 400px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #ccc;"></i> <?= lang_line('qcp_loading', 'Loading...'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <a id="photoLinkBtn" href="#" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> <?= lang_line('qcp_btn_open_new_tab', 'Buka di Tab Baru'); ?>
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('qcp_btn_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
const I18N_QCP = {
    open_link: <?= json_encode(lang_line('qcp_btn_open_link', 'Buka Link')); ?>
};

function viewPhoto(event, photoUrl) {
    event.preventDefault();

    // Set link untuk "Buka di Tab Baru"
    document.getElementById('photoLinkBtn').href = photoUrl;

    // Try to detect if it's a Google Drive link or direct image
    let photoContainer = document.getElementById('photoContainer');

    if (photoUrl.includes('drive.google.com')) {
        // Google Drive link - show iframe or link
        let fileId = photoUrl.match(/\/d\/([^\/]+)/);
        if (fileId && fileId[1]) {
            // Create iframe for Google Drive preview
            let iframeUrl = `https://drive.google.com/file/d/${fileId[1]}/preview`;
            photoContainer.innerHTML = `<iframe src="${iframeUrl}" width="100%" height="500" style="border: none; border-radius: 4px;"></iframe>`;
        } else {
            photoContainer.innerHTML = `<div class="alert alert-info"><i class="fas fa-link"></i> Link: <a href="${photoUrl}" target="_blank">${photoUrl}</a></div>`;
        }
    } else if (photoUrl.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
        // Direct image link
        photoContainer.innerHTML = `<img src="${photoUrl}" style="max-width: 100%; max-height: 500px; border-radius: 4px;">`;
    } else {
        // Generic link
        photoContainer.innerHTML = `<div class="alert alert-info"><i class="fas fa-link"></i><br><strong>Link Preview</strong><br><a href="${photoUrl}" target="_blank" class="btn btn-primary mt-3"><i class="fas fa-external-link-alt"></i> ${I18N_QCP.open_link}</a></div>`;
    }

    $('#photoModal').modal('show');
}
</script>
