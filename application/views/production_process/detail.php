<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= $title; ?>: <?= htmlspecialchars($header->production_number); ?></h1>
    <div>
        <?php if ($header->status == 'Draft') : ?>
            <a href="<?= base_url('production_process/confirm/' . $header->id); ?>" class="btn btn-success btn-icon-split btn-sm" onclick="return confirm('Anda yakin ingin konfirmasi dokumen ini? Stok akan dikunci.')">
                <span class="icon text-white-50"><i class="fas fa-check"></i></span>
                <span class="text">Confirm</span>
            </a>
        <?php elseif ($header->status == 'In Progress') : ?>
            <a href="<?= base_url('production_process/unconfirm/' . $header->id); ?>" class="btn btn-warning btn-icon-split btn-sm" onclick="return confirm('Anda yakin ingin membatalkan konfirmasi? Status akan kembali ke Draft.')">
                <span class="icon text-white-50"><i class="fas fa-undo"></i></span>
                <span class="text">Un-Confirm</span>
            </a>
        <?php endif; ?>

        <a href="<?= base_url('production_process/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-icon-split btn-sm">
            <span class="icon text-white-50"><i class="fas fa-file-pdf"></i></span>
            <span class="text">Export PDF</span>
        </a>
        <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-icon-split btn-sm">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Detail Header</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td style="width: 150px;"><strong>No. Produksi</strong></td>
                        <td>: <?= htmlspecialchars($header->production_number); ?></td>
                    </tr>
                    <tr>
                        <td><strong>No. Work Order</strong></td>
                        <td>: <?= htmlspecialchars($header->wo_number); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Finished Good</strong></td>
                        <td>: <?= htmlspecialchars($header->fg_name); ?> (<?= isset($header->fg_code) ? htmlspecialchars($header->fg_code) : 'N/A'; ?>)</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td style="width: 150px;"><strong>Tgl. Produksi</strong></td>
                        <td>: <?= date('d-m-Y', strtotime($header->production_date)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Qty Rencana</strong></td>
                        <td>: <?= number_format($header->planned_qty, 4); ?> <?= isset($header->fg_uom) ? htmlspecialchars($header->fg_uom) : 'UOM?'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>:
                            <?php
                                $badge = 'secondary';
                                if ($header->status == 'Draft') $badge = 'warning';
                                if ($header->status == 'In Progress') $badge = 'primary';
                                if ($header->status == 'Completed') $badge = 'success';
                            ?>
                            <span class="badge badge-<?= $badge; ?>"><?= htmlspecialchars($header->status); ?></span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-12">
                <strong>Remarks:</strong>
                <p><?= nl2br(htmlspecialchars($header->remarks ?: '-')); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <ul class="nav nav-tabs card-header-tabs" id="productionStageTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="cutting-tab" data-toggle="tab" href="#cutting" role="tab" aria-controls="cutting" aria-selected="true">
                    <i class="fas fa-cut"></i> 1. Cutting
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="sewing-tab" data-toggle="tab" href="#sewing" role="tab" aria-controls="sewing" aria-selected="false">
                    <i class="fas fa-tshirt"></i> 2. Sewing
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="productionStageTabContent">

            <div class="tab-pane fade show active" id="cutting" role="tabpanel" aria-labelledby="cutting-tab">

                <h5 class="mt-3">A. Input Material (dari MR)</h5>
                <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_material_usage/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-5">
                            <label for="cutting_mr_detail_id">Material (Batch) - (Sisa Stok Gudang)</label>
                            <select name="mr_detail_id" id="cutting_mr_detail_id" class="form-control" required>
                                <option value="">-- Pilih Material (Batch) --</option>
                                <?php foreach ($wip_materials as $mat) : ?>
                                    <option value="<?= $mat->mr_detail_id; ?>" data-item-id="<?= $mat->item_id; ?>" data-item-type="<?= $mat->item_type; ?>">
                                        <?= htmlspecialchars($mat->item_name); ?>
                                        (<?= isset($mat->item_code) ? htmlspecialchars($mat->item_code) : 'N/A'; ?>)
                                        - Batch: <?= isset($mat->batch_number) ? htmlspecialchars($mat->batch_number) : 'N/A'; ?>
                                        (Sisa: <?= number_format($mat->remaining_qty, 4); ?> <?= isset($mat->uom_code) ? htmlspecialchars($mat->uom_code) : 'UOM?'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="item_id" id="cutting_item_id" value="">
                            <input type="hidden" name="item_type" id="cutting_item_type" value="">
                        </div>
                        <div class="col-md-3">
                            <label for="cutting_qty_used">Qty Terpakai</label>
                            <input type="number" step="0.0001" name="qty_used" class="form-control" required id="cutting_qty_used">
                        </div>
                        <div class="col-md-3">
                            <label for="cutting_notes">Notes</label>
                            <input type="text" name="notes" class="form-control" id="cutting_notes">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block" title="Gunakan"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-info">Input dibekukan karena status bukan 'Draft'.</div>
                <?php endif; ?>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm" width="100%">
                        <thead class="thead-light">
                            <tr>
                                <th>Item</th>
                                <th>Batch</th>
                                <th>Qty Terpakai</th>
                                <th>Notes</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cutting_inputs as $in) : ?>
                            <tr>
                                <td><?= htmlspecialchars($in->item_name); ?> (<?= isset($in->item_code) ? htmlspecialchars($in->item_code) : 'N/A'; ?>)</td>
                                <td><?= isset($in->batch_number) ? htmlspecialchars($in->batch_number) : 'N/A'; ?></td>
                                <td><?= number_format($in->qty_used, 4); ?> <?= isset($in->uom_code) ? htmlspecialchars($in->uom_code) : 'UOM?'; ?></td>
                                <td><?= htmlspecialchars($in->notes); ?></td>
                                <td>
                                    <?php if ($header->status == 'Draft') : ?>
                                    <a href="<?= base_url('production_process/delete_material_usage/' . $header->id . '/' . $in->id); ?>"
                                       class="btn btn-danger btn-circle btn-sm" onclick="return confirm('Hapus data ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr>

                <h5 class="mt-3">B. Output Hasil Produksi (Semi-Finished)</h5>
                <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_cutting_output/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-7">
                            <label for="cutting_output_semifinished">Item (Semi-Finished)</label>
                            <select name="item_id" id="cutting_output_semifinished" class="form-control select2-ajax-semifinished" required>
                                <option value="">-- Cari Item Semi-Finished --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="cutting_output_semifinished_qty">Qty</label>
                            <input type="number" step="0.0001" name="qty" class="form-control" required id="cutting_output_semifinished_qty">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <h5 class="mt-3">C. Output Scrap</h5>
                <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_cutting_output/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-7">
                            <label for="cutting_output_scrap">Item (Scrap)</label>
                            <select name="item_id" id="cutting_output_scrap" class="form-control select2-ajax-scrap" required>
                                <option value="">-- Cari Item Scrap --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="cutting_output_scrap_qty">Qty</label>
                            <input type="number" step="0.0001" name="qty" class="form-control" required id="cutting_output_scrap_qty">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <h5 class="mt-4">D. Daftar Total Output (Hasil & Scrap)</h5>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm" width="100%">
                        <thead class="thead-light">
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Status QC</th>
                                <th>Diinput Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cutting_outputs as $out) : ?>
                            <tr>
                                <td><?= htmlspecialchars($out->item_name); ?> (<?= isset($out->item_code) ? htmlspecialchars($out->item_code) : 'N/A'; ?>)</td>
                                <td>
                                    <?php if ($out->item_type == 'semifinished') : ?>
                                        <span class="badge badge-info">Semi-Finished</span>
                                    <?php else : ?>
                                        <span class="badge badge-danger">Scrap</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format(($out->item_type == 'scrap') ? $out->qty_scrap : $out->qty_produced, 4); ?> <?= isset($out->uom_code) ? htmlspecialchars($out->uom_code) : 'UOM?'; ?></td>
                                <td>
                                    <?php if (isset($out->qc_checked_at) && $out->qc_checked_at) : ?>
                                        <span class="badge badge-success">QC Passed</span>
                                    <?php else : ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($out->receiver_name); ?></td>
                                <td>
                                    <?php if ($header->status == 'Draft' && (!isset($out->qc_checked_at) || !$out->qc_checked_at)) : ?>
                                        <a href="<?= base_url('production_process/confirm_qc_cutting/' . $header->id . '/' . $out->id); ?>"
                                           class="btn btn-success btn-circle btn-sm" title="Konfirmasi QC">
                                            <i class="fas fa-check-double"></i>
                                        </a>
                                        <a href="<?= base_url('production_process/delete_cutting_output/' . $header->id . '/' . $out->id); ?>"
                                           class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Hapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php elseif ($header->status == 'Draft' && (isset($out->qc_checked_at) && $out->qc_checked_at)) : ?>
                                        <a href="<?= base_url('production_process/unconfirm_qc_cutting/' . $header->id . '/' . $out->id); ?>"
                                           class="btn btn-warning btn-circle btn-sm" title="Batal QC">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="sewing" role="tabpanel" aria-labelledby="sewing-tab">

                <h5 class="mt-3">A. Input Hasil Cutting</h5>
                <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_sewing_cutting_input/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-5">
                            <label for="sewing_source_output_id">Hasil Cutting (Batch)</label>
                            <select name="source_output_id" class="form-control" required id="sewing_source_output_id">
                                <option value="">-- Pilih Hasil Cutting --</option>
                                <?php foreach ($available_cutting_outputs as $cut_out) : ?>
                                    <option value="<?= $cut_out->output_id; ?>" data-item-id="<?= $cut_out->item_id; ?>" data-item-type="<?= $cut_out->item_type; ?>">
                                        <?= htmlspecialchars($cut_out->item_name); ?>
                                        (Sisa: <?= number_format($cut_out->remaining_qty, 4); ?> <?= isset($cut_out->uom_code) ? htmlspecialchars($cut_out->uom_code) : 'UOM?'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="item_id" id="sewing_cut_item_id" value="">
                            <input type="hidden" name="item_type" id="sewing_cut_item_type" value="">
                        </div>
                        <div class="col-md-3">
                            <label for="sewing_cut_qty_used">Qty Terpakai</label>
                            <input type="number" step="0.0001" name="qty_used" class="form-control" required id="sewing_cut_qty_used">
                        </div>
                        <div class="col-md-3">
                            <label for="sewing_cut_notes">Notes</label>
                            <input type="text" name="notes" class="form-control" id="sewing_cut_notes">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-info">Input dibekukan karena status bukan 'Draft'.</div>
                <?php endif; ?>

                <h5 class="mt-4">B. Input Material Tambahan (Aksesoris, Benang, dll)</h5>
                 <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_sewing_additional_input/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-5">
                            <label for="sewing_mr_detail_id">Material (Batch)</label>
                            <select name="mr_detail_id" class="form-control" required id="sewing_mr_detail_id">
                                <option value="">-- Pilih Material Tambahan --</option>
                                <?php foreach ($additional_materials as $mat) : ?>
                                    <option value="<?= $mat->mr_detail_id; ?>" data-item-id="<?= $mat->item_id; ?>" data-item-type="<?= $mat->item_type; ?>">
                                        <?= htmlspecialchars($mat->item_name); ?>
                                        (<?= isset($mat->item_code) ? htmlspecialchars($mat->item_code) : 'N/A'; ?>)
                                        - Batch: <?= isset($mat->batch_number) ? htmlspecialchars($mat->batch_number) : 'N/A'; ?>
                                        (Sisa: <?= number_format($mat->remaining_qty, 4); ?> <?= isset($mat->uom_code) ? htmlspecialchars($mat->uom_code) : 'UOM?'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="item_id" id="sewing_add_item_id" value="">
                            <input type="hidden" name="item_type" id="sewing_add_item_type" value="">
                        </div>
                        <div class="col-md-3">
                            <label for="sewing_add_qty_used">Qty Terpakai</label>
                            <input type="number" step="0.0001" name="qty_used" class="form-control" required id="sewing_add_qty_used">
                        </div>
                        <div class="col-md-3">
                            <label for="sewing_add_notes">Notes</label>
                            <input type="text" name="notes" class="form-control" id="sewing_add_notes">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm" width="100%">
                        <thead class="thead-light"><tr><th>Sumber Input</th><th>Item</th><th>Batch / Ref</th><th>Qty Terpakai</th><th>Notes</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($sewing_inputs as $in) : ?>
                            <tr>
                                <td><?php if ($in->source_output_id) : ?><span class="badge badge-info">Dari Cutting</span><?php else : ?><span class="badge badge-secondary">Material (MR)</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($in->item_name); ?> (<?= isset($in->item_code) ? htmlspecialchars($in->item_code) : 'N/A'; ?>)</td>
                                <td><?php if ($in->source_output_id) : ?>Ref: Output #<?= $in->source_output_id; ?><?php else : ?>Batch: <?= isset($in->batch_number) ? htmlspecialchars($in->batch_number) : 'N/A'; ?><?php endif; ?></td>
                                <td><?= number_format($in->qty_used, 4); ?> <?= isset($in->uom_code) ? htmlspecialchars($in->uom_code) : 'UOM?'; ?></td>
                                <td><?= htmlspecialchars($in->notes); ?></td>
                                <td><?php if ($header->status == 'Draft') : ?><a href="<?= base_url('production_process/delete_sewing_input/' . $header->id . '/' . $in->id); ?>" class="btn btn-danger btn-circle btn-sm" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr>

                <h5 class="mt-3">C. Output Hasil Sewing</h5>
                <?php if ($header->status == 'Draft') : ?>
                <form action="<?= base_url('production_process/save_sewing_output/' . $header->id) ?>" method="POST">
                    <div class="form-row">
                        <div class="col-md-7">
                            <label for="sewing_output_item">Item (Semi-Finished / Scrap)</label>
                            <select name="item_id" id="sewing_output_item" class="form-control select2-ajax-outputs" required>
                                <option value="">-- Cari Item --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sewing_output_qty">Qty</label>
                            <input type="number" step="0.0001" name="qty" class="form-control" required id="sewing_output_qty">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm" width="100%">
                        <thead class="thead-light"><tr><th>Item</th><th>Type</th><th>Qty</th><th>Status QC</th><th>Diinput Oleh</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($sewing_outputs as $out) : ?>
                            <tr>
                                <td><?= htmlspecialchars($out->item_name); ?> (<?= isset($out->item_code) ? htmlspecialchars($out->item_code) : 'N/A'; ?>)</td>
                                <td><?php if ($out->item_type == 'semifinished') : ?><span class="badge badge-info">Semi-Finished</span><?php else : ?><span class="badge badge-danger">Scrap</span><?php endif; ?></td>
                                <td><?= number_format(($out->item_type == 'scrap') ? $out->qty_scrap : $out->qty_produced, 4); ?> <?= isset($out->uom_code) ? htmlspecialchars($out->uom_code) : 'UOM?'; ?></td>
                                <td><?php if (isset($out->qc_checked_at) && $out->qc_checked_at) : ?><span class="badge badge-success">QC Passed</span><?php else : ?><span class="badge badge-warning">Pending</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($out->receiver_name); ?></td>
                                <td>
                                    <?php if ($header->status == 'Draft' && (!isset($out->qc_checked_at) || !$out->qc_checked_at)) : ?>
                                        <a href="<?= base_url('production_process/confirm_qc_sewing/' . $header->id . '/' . $out->id); ?>" class="btn btn-success btn-circle btn-sm" title="Konfirmasi QC"><i class="fas fa-check-double"></i></a>
                                        <a href="<?= base_url('production_process/delete_sewing_output/' . $header->id . '/' . $out->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a>
                                    <?php elseif ($header->status == 'Draft' && (isset($out->qc_checked_at) && $out->qc_checked_at)) : ?>
                                        <a href="<?= base_url('production_process/unconfirm_qc_sewing/' . $header->id . '/' . $out->id); ?>" class="btn btn-warning btn-circle btn-sm" title="Batal QC"><i class="fas fa-undo"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div> </div> </div> <script>
$(document).ready(function() {

    // --- Fungsi Helper Select2 (Tidak berubah) ---
    function formatRepo(repo) {
        if (repo.loading) return repo.text;
        if (repo.children) { return $('<strong>' + repo.text + '</strong>'); }
        return $('<div>' + repo.text + '</div>');
    }
    function formatRepoSelection(repo) {
        return repo.text || repo.id;
    }

    // --- Inisialisasi Select2 ---

    // Fungsi untuk inisialisasi Select2 di tab Cutting
    function initCuttingSelect2() {
        console.log('Initializing Cutting Select2...'); // Debug log
        // Hanya inisialisasi jika belum ada
        if ( !$('#cutting_output_semifinished').data('select2') ) {
            $('#cutting_output_semifinished').select2({
                width: '100%',
                ajax: {
                    url: '<?= base_url("production_process/search_semifinished") ?>',
                    dataType: 'json', delay: 250, data: function(params) { return { q: params.term }; },
                    processResults: function(data) { return { results: data.results }; }, cache: true
                },
                placeholder: '-- Cari Item Semi-Finished --', minimumInputLength: 1,
                templateResult: formatRepo, templateSelection: formatRepoSelection
            });
        }
        if ( !$('#cutting_output_scrap').data('select2') ) {
            $('#cutting_output_scrap').select2({
                width: '100%',
                ajax: {
                    url: '<?= base_url("production_process/search_scrap") ?>',
                    dataType: 'json', delay: 250, data: function(params) { return { q: params.term }; },
                    processResults: function(data) { return { results: data.results }; }, cache: true
                },
                placeholder: '-- Cari Item Scrap --', minimumInputLength: 1,
                templateResult: formatRepo, templateSelection: formatRepoSelection
            });
        }
    }

    // Fungsi untuk inisialisasi Select2 di tab Sewing
    function initSewingSelect2() {
        console.log('Initializing Sewing Select2...'); // Debug log
        // Hanya inisialisasi jika belum ada
        if ( !$('#sewing_output_item').data('select2') ) {
            $('#sewing_output_item').select2({
                width: '100%',
                ajax: {
                    url: '<?= base_url("production_process/search_all_outputs") ?>',
                    dataType: 'json', delay: 250, data: function(params) { return { q: params.term }; },
                    processResults: function(data) { return { results: data.results }; }, cache: true
                },
                placeholder: '-- Cari Item (Semi-Finished / Scrap) --', minimumInputLength: 1,
                templateResult: formatRepo, templateSelection: formatRepoSelection
            });
        }
    }

    // --- Event Listeners untuk hidden fields (Tidak berubah) ---
    $('#cutting_mr_detail_id').change(function() {
        var selected = $(this).find('option:selected');
        $('#cutting_item_id').val(selected.data('item-id'));
        $('#cutting_item_type').val(selected.data('item-type'));
    });

    $('#sewing_source_output_id').change(function() {
        var selected = $(this).find('option:selected');
        $('#sewing_cut_item_id').val(selected.data('item-id'));
        $('#sewing_cut_item_type').val(selected.data('item-type'));
    });

    $('#sewing_mr_detail_id').change(function() {
        var selected = $(this).find('option:selected');
        $('#sewing_add_item_id').val(selected.data('item-id'));
        $('#sewing_add_item_type').val(selected.data('item-type'));
    });


    // --- Logika untuk Navigasi Tab (ROBUST/KUAT) ---

    // 1. Simpan hash di URL saat tab diganti
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var hash = $(e.target).attr('href');
        if(history.pushState) {
            history.pushState(null, null, hash);
        } else {
            window.location.hash = hash;
        }
        console.log('Tab shown:', hash); // Debug log

        // 2. Panggil initializer yang TEPAT untuk tab yang BARU AKTIF
        if (hash === '#sewing') {
            initSewingSelect2();
        } else if (hash === '#cutting') {
            initCuttingSelect2();
        }
    });

    // 3. Tangani pemuatan halaman awal
    var initialHash = window.location.hash;
    console.log('Initial hash:', initialHash); // Debug log

    if (initialHash === '#sewing') {
        // Pindahkan tab ke sewing JIKA hash-nya sewing
        $('a[href="#sewing"]').tab('show');
        // Panggil initSewingSelect2 secara manual setelah jeda
        setTimeout(initSewingSelect2, 150);
    } else {
        // Jika hash tidak ada, atau #cutting, inisialisasi tab cutting
        initCuttingSelect2();
        // Pastikan tab cutting aktif (jika hash aneh)
        if ($('#cutting-tab').length) {
            $('#cutting-tab').tab('show');
        }
    }

});
</script>