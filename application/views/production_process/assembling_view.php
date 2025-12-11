<div class="container-fluid">
    <!-- Page heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-puzzle-piece text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->production_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/confirm/' . $header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('Yakin ingin konfirmasi proses produksi ini?');">
                    <i class="fas fa-check-circle fa-sm"></i> Konfirmasi Proses
                </a>
            <?php elseif ($header->status == 'In Progress' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/complete_production/' . $header->id); ?>" class="btn btn-success btn-sm mr-1" onclick="return confirm('Yakin ingin menyelesaikan proses produksi ini?');">
                    <i class="fas fa-flag-checkered fa-sm"></i> Selesaikan Produksi
                </a>
                <a href="<?= base_url('production_process/unconfirm/' . $header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan konfirmasi?');">
                    <i class="fas fa-undo fa-sm"></i> Batal Konfirmasi
                </a>
            <?php endif; ?>
            <a href="<?= base_url('production_process/export_assembling_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF (Assembling)
            </a>
            <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali</a>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs nav-fill mb-4">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/preparation/' . $header->id); ?>">1. Preparation</a></li>
        <li class="nav-item"><a class="nav-link active font-weight-bold" href="#">2. Assembling</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/finishing/' . $header->id); ?>">3. Finishing</a></li>
    </ul>

    <!-- Flash messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Header card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>No. Produksi:</strong> <?= htmlspecialchars($header->production_number); ?></div>
                <div class="col-md-4"><strong>No. WO:</strong> <?= htmlspecialchars($header->wo_number); ?></div>
                <div class="col-md-4"><strong>Status:</strong>
                    <?php
                    $status_badge = 'secondary';
                    if ($header->status == 'Draft') $status_badge = 'warning';
                    if ($header->status == 'In Progress') $status_badge = 'primary';
                    if ($header->status == 'Completed') $status_badge = 'success';
                    ?>
                    <span class="badge badge-<?= $status_badge; ?>"><?= $header->status; ?></span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4"><strong>Barang Jadi:</strong> <?= htmlspecialchars($header->fg_name); ?></div>
                <div class="col-md-4"><strong>Tgl Produksi:</strong> <?= date('d M Y', strtotime($header->production_date)); ?></div>
                <div class="col-md-4"><strong>Qty Rencana:</strong> <?= number_format($header->planned_qty, 2); ?></div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="row">
        <div class="col-lg-6">
            <!-- Input dari previous stage (Sewing) -->
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white py-3">
                        <h6 class="m-0 font-weight-bold">Input dari <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?> (Hasil Semi-Finished)</h6>
                    </div>
                    <div class="card-body">
                        <form id="formInputPrev" method="post" action="<?= base_url('production_process/save_assembling_preparation_input/' . $header->id); ?>">
                            <input type="hidden" name="item_id" id="prev_item_id">
                            <input type="hidden" name="item_type" id="prev_item_type">
                            <div class="form-group">
                                <label for="prev_output_selector">Pilih Hasil <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?> (Hanya yang Lolos QC)</label>
                                <select name="source_output_id" id="prev_output_selector" class="form-control select2-wip" required>
                                    <option value="">-- Pilih Item Hasil <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?> --</option>
                                    <?php if (!empty($available_previous_outputs)): ?>
                                        <?php foreach ($available_previous_outputs as $out): ?>
                                            <?php if ($out->remaining_qty > 0.00001): ?>
                                                <option value="<?= $out->output_id; ?>"
                                                    data-itemid="<?= $out->item_id; ?>"
                                                    data-itemtype="<?= $out->item_type; ?>"
                                                    data-maxqty="<?= $out->remaining_qty; ?>">
                                                    <?= htmlspecialchars($out->item_name . ' (' . $out->item_code . ') - Sisa: ' . number_format($out->remaining_qty, 4) . ' ' . htmlspecialchars($out->item_unit)); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>-- Tidak ada hasil <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?> yang tersedia/lolos QC --</option>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Harap pilih hasil <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?>.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="prev_qty_used">Qty Digunakan</label>
                                    <input type="number" step="any" name="qty_used" id="prev_qty_used" class="form-control" required min="0.00001" max="0">
                                    <small class="form-text text-muted">Maks: <span id="prev_max_qty">0.0000</span></small>
                                    <div class="invalid-feedback">Qty harus diisi dan tidak melebihi sisa.</div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="prev_notes">Catatan</label>
                                    <input type="text" name="notes" id="prev_notes" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Input <?= htmlspecialchars($previous_stage_name ?? 'Preparation'); ?></button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Input Material Tambahan -->
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="m0 font-weight-bold">Input Material Tambahan (dari Gudang)</h6>
                    </div>
                    <div class="card-body">
                        <form id="formInputAdd" method="post" action="<?= base_url('production_process/save_assembling_additional_input/' . $header->id); ?>">
                            <input type="hidden" name="item_id" id="add_item_id">
                            <input type="hidden" name="item_type" id="add_item_type">
                            <div class="form-group">
                                <label for="add_batch_selector">Pilih Material (Batch Sesuai FIFO/FEFO)</label>
                                <select name="batch_id" id="add_batch_selector" class="form-control select2-wip" required>
                                    <option value="">-- Pilih Batch Material --</option>
                                    <?php if (!empty($available_batches)): ?>
                                        <?php foreach ($available_batches as $batch): ?>
                                            <option value="<?= $batch->batch_id; ?>"
                                                data-itemid="<?= $batch->item_id; ?>"
                                                data-itemtype="<?= $batch->item_type; ?>"
                                                data-maxqty="<?= $batch->remaining_qty; ?>">
                                                <?= htmlspecialchars($batch->item_name . ' (' . $batch->item_code . ')'); ?>
                                                | Batch: <?= htmlspecialchars($batch->grn_number ?? 'N/A'); ?>
                                                | Sisa: <?= number_format($batch->remaining_qty, 4) . ' ' . htmlspecialchars($batch->item_unit); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>-- Tidak ada batch material yang tersedia --</option>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Harap pilih batch material.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="add_qty_used">Qty Digunakan</label>
                                    <input type="number" step="any" name="qty_used" id="add_qty_used" class="form-control" required min="0.00001" max="0">
                                    <small class="form-text text-muted">Sisa Stok Batch: <span id="add_max_qty">0.0000</span></small>
                                    <div class="invalid-feedback">Qty harus diisi dan tidak melebihi sisa stok.</div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="add_notes">Catatan</label>
                                    <input type="text" name="notes" id="add_notes" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Input Material</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabel Input -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Daftar Input Assembling</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light text-center">
                                <tr>
                                    <th>Item</th>
                                    <th>Tipe</th>
                                    <th>Qty</th>
                                    <th>Catatan</th>
                                    <th>Sumber</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($current_inputs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-2">Belum ada input</td>
                                    </tr>
                                    <?php else: foreach ($current_inputs as $in): ?>
                                        <tr class="text-center">
                                            <td class="text-left"><?= htmlspecialchars($in->item_name); ?></td>
                                            <td><span class="badge badge-secondary"><?= htmlspecialchars($in->item_type); ?></span></td>
                                            <td class="text-right"><?= number_format($in->qty_used, 4); ?></td>
                                            <td class="text-left"><?= htmlspecialchars($in->notes); ?></td>
                                            <td>
                                                <?php if ($in->stock_on_hand_id): echo 'Gudang (Batch)'; ?>
                                                <?php elseif ($in->source_output_id): echo 'Output ' . htmlspecialchars($previous_stage_name ?? 'Preparation'); ?>
                                                <?php else: echo '-';
                                                endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($header->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                                    <a href="<?= base_url('production_process/delete_assembling_input/' . $header->id . '/' . $in->id); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus Input" data-message="Yakin hapus input <?= htmlspecialchars(addslashes($in->item_name)); ?>? Stok akan dikembalikan jika relevan."><i class="fas fa-trash fa-xs"></i></a>
                                                <?php else: echo '-';
                                                endif; ?>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="col-lg-6">
            <!-- Output Semi-Finished -->
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-success text-white py-3">
                        <h6 class="m-0 font-weight-bold">Output Hasil Jadi (Semi-Finished)</h6>
                    </div>
                    <div class="card-body">
                        <form id="formOutput" method="post" action="<?= base_url('production_process/save_assembling_output/' . $header->id); ?>">
                            <div class="form-group">
                                <label for="output_item_sf">Pilih Item Semi-Finished</label>
                                <select name="item_id" id="output_item_sf" class="form-control select2-ajax-semifinished" required style="width:100%;">
                                    <option value="">-- Cari Semi-Finished --</option>
                                </select>
                                <div class="invalid-feedback">Harap pilih item semi-finished.</div>
                            </div>
                            <div class="form-group">
                                <label for="output_qty_sf">Qty Dihasilkan</label>
                                <input type="number" step="any" name="qty" id="output_qty_sf" class="form-control" required min="0.00001">
                                <div class="invalid-feedback">Qty harus diisi dan lebih dari 0.</div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Hasil</button>
                        </form>
                    </div>
                </div>

                <!-- Output Scrap -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark py-3">
                        <h6 class="m-0 font-weight-bold">Output Hasil Scrap</h6>
                    </div>
                    <div class="card-body">
                        <form id="formOutputScrap" method="post" action="<?= base_url('production_process/save_assembling_output/' . $header->id); ?>">
                            <div class="form-group">
                                <label for="output_item_scrap">Pilih Item Scrap</label>
                                <select name="item_id" id="output_item_scrap" class="form-control select2-ajax-scrap" required style="width:100%;">
                                    <option value="">-- Cari Scrap --</option>
                                </select>
                                <div class="invalid-feedback">Harap pilih item scrap.</div>
                            </div>
                            <div class="form-group">
                                <label for="output_qty_scrap">Qty Scrap</label>
                                <input type="number" step="any" name="qty" id="output_qty_scrap" class="form-control" required min="0.00001">
                                <div class="invalid-feedback">Qty harus diisi dan lebih dari 0.</div>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Scrap</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabel Output -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Daftar Output Assembling</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light text-center">
                                <tr>
                                    <th>Item</th>
                                    <th>Tipe</th>
                                    <th>Qty</th>
                                    <th>QC Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($current_outputs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-2">Belum ada output</td>
                                    </tr>
                                    <?php else: foreach ($current_outputs as $out): ?>
                                        <tr class="text-center">
                                            <td class="text-left"><?= htmlspecialchars($out->item_name); ?></td>
                                            <td><span class="badge badge-<?= $out->item_type == 'scrap' ? 'warning text-dark' : 'success'; ?>"><?= htmlspecialchars(ucfirst($out->item_type)); ?></span></td>
                                            <td class="text-right"><?= number_format($out->item_type == 'scrap' ? $out->qty_scrap : $out->qty_produced, 4); ?></td>
                                            <td>
                                                <?php if ($out->qc_checked_by): ?>
                                                    <span class="badge badge-success" title="Checked by <?= htmlspecialchars($out->qc_name ?? 'N/A'); ?> at <?= $out->qc_checked_at ? date('d/m/y H:i', strtotime($out->qc_checked_at)) : '-'; ?>">
                                                        Lolos QC <i class="fas fa-check-circle fa-xs ml-1"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $allow_qc_action = ($header->status == 'Draft' || $header->status == 'In Progress');
                                                $allow_delete_action = ($header->status == 'Draft');
                                                // use 'qc' key or 'add' depending on your permission map. Keep both checks for compatibility.
                                                $has_qc_access = check_access('production_process', 'qc') || check_access('production_process', 'add');
                                                $has_delete_access = check_access('production_process', 'delete');
                                                $button_shown = false;
                                                ?>

                                                <?php if ($allow_qc_action && $has_qc_access): $button_shown = true; ?>
                                                    <?php if (!$out->qc_checked_by): ?>
                                                        <a href="<?= base_url('production_process/confirm_qc_assembling/' . $header->id . '/' . $out->id); ?>"
                                                            class="btn btn-success btn-xs" title="Konfirmasi QC"
                                                            onclick="return confirm('Konfirmasi Lolos QC untuk item <?= htmlspecialchars(addslashes($out->item_name)); ?>?')">
                                                            <i class="fas fa-check fa-xs"></i> QC Pass
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= base_url('production_process/unconfirm_qc_assembling/' . $header->id . '/' . $out->id); ?>"
                                                            class="btn btn-warning btn-xs" title="Batal QC"
                                                            onclick="return confirm('Batalkan status Lolos QC untuk item <?= htmlspecialchars(addslashes($out->item_name)); ?>?')">
                                                            <i class="fas fa-undo fa-xs"></i> Batal QC
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if ($allow_delete_action && $has_delete_access): $button_shown = true; ?>
                                                    <a href="<?= base_url('production_process/delete_assembling_output/' . $header->id . '/' . $out->id); ?>"
                                                        class="btn btn-danger btn-xs btn-delete ml-1" title="Hapus Output"
                                                        data-message="Yakin ingin menghapus output <?= htmlspecialchars(addslashes($out->item_name)); ?>? Stok akan dikembalikan jika relevan.">
                                                        <i class="fas fa-trash fa-xs"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (!$button_shown): echo '-';
                                                endif; ?>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- end right col -->
    </div> <!-- end row -->
</div>
<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<!-- JS libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // --- Inisialisasi Select2 (basic) ---
        $('.select2-wip').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // --- Select2 Ajax: Semi-Finished ---
        $('.select2-ajax-semifinished').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '-- Cari Semi-Finished --',
            minimumInputLength: 1,
            ajax: {
                url: '<?= base_url("production_process/search_semifinished"); ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    // Pastikan setiap item memiliki format id "ID|semifinished"
                    var processedData = $.map(data.results || [], function(item) {
                        // If item.id already contains '|', avoid double-appending
                        if (typeof item.id === 'string' && item.id.indexOf('|') === -1) {
                            item.id = item.id + '|semifinished';
                        }
                        return item;
                    });
                    return {
                        results: processedData
                    };
                },
                error: function() {
                    Swal.fire('Error', 'Gagal memuat data Semi-Finished.', 'error');
                }
            }
        });

        // --- Select2 Ajax: Scrap ---
        $('.select2-ajax-scrap').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '-- Cari Scrap --',
            minimumInputLength: 1,
            ajax: {
                url: '<?= base_url("production_process/search_scrap"); ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    var processedData = $.map(data.results || [], function(item) {
                        if (typeof item.id === 'string' && item.id.indexOf('|') === -1) {
                            item.id = item.id + '|scrap';
                        }
                        return item;
                    });
                    return {
                        results: processedData
                    };
                },
                error: function() {
                    Swal.fire('Error', 'Gagal memuat data Scrap.', 'error');
                }
            }
        });

        // --- Prev output selector (previous stage outputs) ---
        $('#prev_output_selector').on('change', function() {
            var selected = $(this).find('option:selected');
            var maxQty = parseFloat(selected.data('maxqty') || 0);
            var itemId = selected.data('itemid') || '';
            var itemType = selected.data('itemtype') || '';
            $('#prev_qty_used').attr('max', maxQty).val('').removeClass('is-invalid');
            $('#prev_max_qty').text(maxQty.toLocaleString(undefined, {
                minimumFractionDigits: 4,
                maximumFractionDigits: 4
            }));
            $('#prev_item_id').val(itemId);
            $('#prev_item_type').val(itemType);
            $('#prev_qty_used').trigger('input');
        });
        $('#prev_qty_used').on('input', function() {
            var qty = parseFloat($(this).val() || 0);
            var maxQty = parseFloat($(this).attr('max') || 0);
            if (isNaN(qty)) qty = 0;
            if (isNaN(maxQty)) maxQty = 0;
            if (qty > maxQty) {
                $(this).val(maxQty);
            }
            if (qty < 0) {
                $(this).val(0);
            }
        });
        $('#prev_output_selector').trigger('change');

        // --- Add batch selector ---
        $('#add_batch_selector').on('change', function() {
            var selected = $(this).find('option:selected');
            var maxQty = parseFloat(selected.data('maxqty') || 0);
            var itemId = selected.data('itemid') || '';
            var itemType = selected.data('itemtype') || '';
            $('#add_qty_used').attr('max', maxQty).val('').removeClass('is-invalid');
            $('#add_max_qty').text(maxQty.toLocaleString(undefined, {
                minimumFractionDigits: 4,
                maximumFractionDigits: 4
            }));
            $('#add_item_id').val(itemId);
            $('#add_item_type').val(itemType);
            $('#add_qty_used').trigger('input');
        });
        $('#add_qty_used').on('input', function() {
            var qty = parseFloat($(this).val() || 0);
            var maxQty = parseFloat($(this).attr('max') || 0);
            if (isNaN(qty)) qty = 0;
            if (isNaN(maxQty)) maxQty = 0;
            if (qty > maxQty) {
                $(this).val(maxQty);
            }
            if (qty < 0) {
                $(this).val(0);
            }
        });
        $('#add_batch_selector').trigger('change');

        // --- Generic client-side validation + fail-safe for item_id format ---
        $('#formInputPrev, #formInputAdd, #formOutput, #formOutputScrap').on('submit', function(event) {
            var form = this;
            var isValid = true;

            // Basic required checks
            $(form).find('[required]').each(function() {
                var field = $(this);
                field.removeClass('is-invalid');
                var val = field.val();
                var isNumber = field.is('input[type="number"]');
                if (isNumber) {
                    var n = parseFloat(val);
                    if (isNaN(n) || n <= 0) {
                        isValid = false;
                        field.addClass('is-invalid');
                    }
                } else {
                    if (!val || val === '') {
                        isValid = false;
                        field.addClass('is-invalid');
                    }
                }
            });

            // Additional qty vs max checks for input forms
            if (form.id === 'formInputPrev') {
                var qty = parseFloat($('#prev_qty_used').val() || 0);
                var max = parseFloat($('#prev_qty_used').attr('max') || 0);
                if (isNaN(qty) || qty <= 0 || qty > max) {
                    isValid = false;
                    $('#prev_qty_used').addClass('is-invalid');
                }
            }
            if (form.id === 'formInputAdd') {
                var qty = parseFloat($('#add_qty_used').val() || 0);
                var max = parseFloat($('#add_qty_used').attr('max') || 0);
                if (isNaN(qty) || qty <= 0 || qty > max) {
                    isValid = false;
                    $('#add_qty_used').addClass('is-invalid');
                }
            }

            // Fail-safe: ensure item_id select from Select2 includes |TYPE; if not, append before submit
            // For output forms:
            if (form.id === 'formOutput') {
                var sf = $('#output_item_sf');
                var v = sf.val();
                if (v && v.indexOf('|') === -1) {
                    sf.val(v + '|semifinished');
                }
            }
            if (form.id === 'formOutputScrap') {
                var sc = $('#output_item_scrap');
                var v2 = sc.val();
                if (v2 && v2.indexOf('|') === -1) {
                    sc.val(v2 + '|scrap');
                }
            }

            // Submit handling
            if (!isValid) {
                event.preventDefault();
                Swal.fire({
                    title: 'Input Tidak Valid',
                    text: 'Harap periksa kembali field yang wajib diisi dan Qty.',
                    icon: 'warning',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                // Disable submit to prevent double-post
                $(form).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            }
        });

        // --- SweetAlert delete confirmation ---
        $('body').on('click', '.btn-delete', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var message = $(this).data('message') || 'Yakin ingin menghapus data ini?';
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // show loading toast then redirect
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu...',
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        allowOutsideClick: false
                    });
                    window.location.href = url;
                }
            });
        });

    }); // end document.ready
</script>