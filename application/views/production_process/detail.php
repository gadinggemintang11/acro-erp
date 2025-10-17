<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?>: <?= htmlspecialchars($header->production_number); ?></h1>
    
    <?php if($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi Header</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Work Order:</strong> <?= htmlspecialchars($header->wo_number); ?></p>
                    <p><strong>Sales Contract:</strong> <?= htmlspecialchars($header->sc_number); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Finished Good:</strong> <?= htmlspecialchars($header->fg_name); ?></p>
                    <p><strong>Qty Direncanakan:</strong> <?= number_format($header->planned_qty, 2); ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Tgl Produksi:</strong> <?= date('d-m-Y', strtotime($header->production_date)); ?></p>
                    <p><strong>Status:</strong> <span class="badge badge-secondary"><?= htmlspecialchars($header->status); ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs" id="prodTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="cutting-tab" data-toggle="tab" href="#cutting" role="tab" aria-controls="cutting" aria-selected="true">Tahap 1: Cutting</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" id="assembly-tab" data-toggle="tab" href="#assembly" role="tab" aria-controls="assembly" aria-selected="false">Tahap 2: Assembly (TBD)</a>
                </li>
            </ul>
            
            <div class="tab-content" id="prodTabsContent">
                <div class="tab-pane fade show active" id="cutting" role="tabpanel" aria-labelledby="cutting-tab">
                    <div class="row mt-3">

                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold text-success">Input Pemakaian Material (WIP)</h6>
                                </div>
                                <div class="card-body">
                                    
                                    <?php if($header->status == 'Draft'): ?>
                                    <h6 class="text-dark">Form Input Pemakaian</h6>
                                    <form action="<?= base_url('production_process/save_material_usage/' . $header->id); ?>" method="POST" id="form-material-usage">
                                        <input type="hidden" name="mr_detail_id" id="usage-mr-detail-id">
                                        <input type="hidden" name="item_id" id="usage-item-id">
                                        <input type="hidden" name="item_type" id="usage-item-type">
                                        
                                        <div class="form-group">
                                            <label for="usage-item-name">Nama Item (Klik "Gunakan" di bawah)</label>
                                            <input type="text" class="form-control" id="usage-item-name" readonly>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="usage-qty">Qty Terpakai</label>
                                                <input type="number" step="0.0001" class="form-control" name="qty_used" id="usage-qty" required>
                                                <small id="usage-max-info" class="form-text text-muted"></small>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="usage-notes">Catatan</label>
                                                <input type="text" class="form-control" name="notes">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success" id="btn-save-usage" disabled>Simpan Pemakaian</button>
                                    </form>
                                    <hr>
                                    <?php endif; ?>

                                    <h6 class="text-dark">Bahan Baku Tersedia (WIP per Batch)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>Item (Batch ID)</th>
                                                    <th>Diterima</th>
                                                    <th>Terpakai</th>
                                                    <th>Sisa Stok</th>
                                                    <?php if($header->status == 'Draft'): ?>
                                                    <th>Aksi</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($wip_materials as $batch): ?>
                                                <tr>
                                                    <td>
                                                        <?= htmlspecialchars($batch->item_name); ?>
                                                        <small class="d-block text-muted">Batch (MR): #<?= $batch->mr_detail_id; ?></small>
                                                    </td>
                                                    <td><?= number_format($batch->total_received_wip, 4); ?></td>
                                                    <td><?= number_format($batch->total_used, 4); ?></td>
                                                    <td><strong class="text-primary"><?= number_format($batch->remaining_qty, 4); ?></strong></td>
                                                    <?php if($header->status == 'Draft'): ?>
                                                    <td>
                                                        <?php if($batch->remaining_qty > 0): ?>
                                                            <button class="btn btn-primary btn-sm btn-use-wip"
                                                                data-mr_detail_id="<?= $batch->mr_detail_id; ?>"
                                                                data-item_id="<?= $batch->item_id; ?>"
                                                                data-item_type="<?= $batch->item_type; ?>"
                                                                data-item_name="<?= htmlspecialchars($batch->item_name); ?>"
                                                                data-remaining="<?= $batch->remaining_qty; ?>">
                                                                Gunakan
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>
                                    <h6 class="text-dark">Daftar Pemakaian Material (Tercatat)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Catatan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cutting_inputs as $in): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($in->item_name); ?></td>
                                                    <td><?= number_format($in->qty_used, 4); ?></td>
                                                    <td><?= htmlspecialchars($in->notes); ?></td>
                                                    <td>
                                                        <?php if($header->status == 'Draft'): ?>
                                                            <a href="<?= base_url('production_process/delete_material_usage/' . $header->id . '/' . $in->id); ?>" 
                                                               class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini? Stok akan dikembalikan.')">
                                                               <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                </div>
                            </div>
                        </div><div class="col-lg-6">
                            
                            <?php if($header->status == 'Draft'): ?>
                            <div class="card shadow mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold text-info">Input Hasil Jadi (Semi-Finished)</h6>
                                </div>
                                <div class="card-body">
                                    <form action="<?= base_url('production_process/save_cutting_output/' . $header->id); ?>" method="POST">
                                        <div class="form-group">
                                            <label for="select-sf-item">Item Semi-Finished</label>
                                            <select class="form-control" id="select-sf-item" name="item_id" required style="width: 100%;"></select>
                                        </div>
                                        <div class="form-group">
                                            <label for="output-sf-qty">Qty Dihasilkan</label>
                                            <input type="number" step="0.0001" class="form-control" name="qty" id="output-sf-qty" required>
                                        </div>
                                        <button type="submit" class="btn btn-info">Simpan Hasil Jadi</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold text-warning">Input Scrap</h6>
                                </div>
                                <div class="card-body">
                                    <form action="<?= base_url('production_process/save_cutting_output/' . $header->id); ?>" method="POST">
                                        <div class="form-group">
                                            <label for="select-scrap-item">Item Scrap</label>
                                            <select class="form-control" id="select-scrap-item" name="item_id" required style="width: 100%;"></select>
                                        </div>
                                        <div class="form-group">
                                            <label for="output-scrap-qty">Qty Scrap</label>
                                            <input type="number" step="0.0001" class="form-control" name="qty" id="output-scrap-qty" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning">Simpan Scrap</button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="card shadow">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold text-primary">Daftar Total Output (Hasil & Scrap)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>Waktu</th>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Tipe</th>
                                                    <th>QC</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cutting_outputs as $out): ?>
                                                <tr>
                                                    <td><?= date('d/m H:i', strtotime($out->received_at)); ?></td>
                                                    <td><?= htmlspecialchars($out->item_name); ?></td>
                                                    <td>
                                                        <?php 
                                                        $qty_out = ($out->item_type == 'scrap') ? $out->qty_scrap : $out->qty_produced;
                                                        echo number_format($qty_out, 4); 
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= ($out->item_type == 'scrap') ? 'warning' : 'info'; ?>">
                                                            <?= htmlspecialchars($out->item_type); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if($out->qc_checked_by): ?>
                                                            <span class="badge badge-success">OK</span>
                                                            <?php if($header->status == 'Draft'): ?>
                                                                <a href="<?= base_url('production_process/unconfirm_qc_cutting/' . $header->id . '/' . $out->id); ?>" 
                                                                   class="btn btn-outline-secondary btn-sm" onclick="return confirm('Batalkan status QC?')">
                                                                   Batal
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php elseif($header->status == 'Draft'): ?>
                                                            <a href="<?= base_url('production_process/confirm_qc_cutting/' . $header->id . '/' . $out->id); ?>" 
                                                               class="btn btn-outline-success btn-sm" onclick="return confirm('Konfirmasi QC?')">
                                                               QC
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($header->status == 'Draft'): ?>
                                                            <?php if(!$out->qc_checked_by): // Hanya bisa hapus jika BELUM di-QC ?>
                                                            <a href="<?= base_url('production_process/delete_cutting_output/' . $header->id . '/' . $out->id); ?>" 
                                                               class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini? Stok akan dikembalikan.')">
                                                               <i class="fas fa-trash"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div> </div></div></div></div>
    </div>
</div>

<script>
// Pastikan script ini dieksekusi SETELAH jQuery dan Select2.min.js di-load
$(document).ready(function() {

    // === LOGIKA UNTUK FORM INPUT MATERIAL ===
    // (Script ini sudah benar dari sebelumnya)
    // Akan berfungsi jika error Select2 sudah diperbaiki
    $('.btn-use-wip').on('click', function() {
        console.log("Tombol 'Gunakan' diklik."); // Debug
        
        // Ambil data dari tombol
        var mrDetailId = $(this).data('mr_detail_id');
        var itemId = $(this).data('item_id');
        var itemType = $(this).data('item_type');
        var itemName = $(this).data('item_name');
        var remainingQty = parseFloat($(this).data('remaining'));

        // Masukkan data ke form
        $('#usage-mr-detail-id').val(mrDetailId);
        $('#usage-item-id').val(itemId);
        $('#usage-item-type').val(itemType);
        $('#usage-item-name').val(itemName + ' (Batch: #' + mrDetailId + ', Sisa: ' + remainingQty + ')');
        
        $('#usage-qty').attr('max', remainingQty);
        $('#usage-qty').val(remainingQty);
        $('#usage-max-info').text('Sisa stok batch ini: ' + remainingQty);

        $('#btn-save-usage').prop('disabled', false);
        $('#usage-qty').focus();
    });

    // Validasi Qty input (TETAP SAMA)
    $('#usage-qty').on('input', function() {
        var qty = parseFloat($(this).val());
        var max = parseFloat($(this).attr('max'));
        if (qty > max) {
            $(this).val(max);
        }
    });


    // === (BARU) LOGIKA UNTUK 2 FORM OUTPUT ===

    // Inisialisasi Select2 untuk dropdown Semi-Finished
    $('#select-sf-item').select2({
        placeholder: 'Cari Semi-Finished Goods...',
        width: '100%',
        ajax: {
            url: "<?= base_url('production_process/search_semifinished'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // Inisialisasi Select2 untuk dropdown Scrap
    $('#select-scrap-item').select2({
        placeholder: 'Cari Item Scrap...',
        width: '100%',
        ajax: {
            url: "<?= base_url('production_process/search_scrap'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

});
</script>