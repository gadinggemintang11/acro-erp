<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?>: <?= htmlspecialchars($header->wo_number); ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Work Order</h6>
            <div>
                <?php if ($header->status == 'Planned'): ?>
                    <a href="<?= base_url('work_order/confirm_wo/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm('Yakin konfirmasi Work Order ini? Status akan diubah menjadi In Progress.')">
                        <i class="fas fa-check"></i> Konfirmasi WO
                    </a>
                <?php elseif ($header->status == 'In Progress'): ?>
                    <a href="<?= base_url('work_order/unconfirm_wo/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Yakin batalkan konfirmasi? Status akan kembali ke Planned.')">
                        <i class="fas fa-times"></i> Batalkan Konfirmasi
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('work_order/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6">
                    <p><strong>Sales Contract:</strong> <?= htmlspecialchars($header->sc_number); ?></p>
                    <p><strong>Customer:</strong> <?= htmlspecialchars($header->nama_customer); ?></p>
                    <p><strong>Finished Good:</strong> <?= htmlspecialchars($header->fg_name); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Qty Rencana:</strong> <?= number_format($header->planned_qty, 2); ?></p>
                    <p><strong>Tgl Mulai:</strong> <?= date('d-m-Y', strtotime($header->planned_start_date)); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge badge-<?= ($header->status == 'Planned') ? 'info' : 'success'; ?>">
                            <?= htmlspecialchars($header->status); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info">Referensi Bill of Materials (BOM)</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small">Ini adalah daftar material referensi dari BOM untuk <strong><?= htmlspecialchars($header->fg_name); ?></strong>. Gunakan ini sebagai panduan saat menambahkan material di bawah.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead>
                        <tr>
                            <th>Kode Komponen</th>
                            <th>Nama Komponen</th>
                            <th>Tipe</th>
                            <th>Qty (per 1 FG)</th>
                            <th>Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bom_components)): ?>
                            <?php foreach ($bom_components as $bom): ?>
                            <tr>
                                <td><?= htmlspecialchars($bom->item_code); ?></td>
                                <td><?= htmlspecialchars($bom->item_name); ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($bom->item_type); ?></span></td>
                                <td><?= number_format($bom->quantity_required, 4); ?></td>
                                <td><?= htmlspecialchars($bom->unit_measure); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data BOM aktif yang ditemukan untuk Finished Good ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Kebutuhan Material (Rencana WO)</h6>
        </div>
        <div class="card-body">
            
            <?php if($header->status == 'Planned'): ?>
            <form action="<?= base_url('work_order/add_material/' . $header->id); ?>" method="POST" class="form-inline mb-3">
                <div class="form-group mr-2 flex-grow-1">
                    <label for="select-component" class="sr-only">Komponen</label>
                    <select class="form-control" id="select-component" name="component_item" required style="width: 100%;"></select>
                </div>
                <div class="form-group mr-2">
                    <input type="number" step="0.01" class="form-control" name="planned_qty" placeholder="Qty" required>
                </div>
                <button type="submit" class="btn btn-success">Tambah</button>
            </form>
            <hr>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Komponen</th>
                            <th>Tipe</th>
                            <th>Qty Rencana</th>
                            <th>Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($planned_materials)): ?>
                            <?php foreach ($planned_materials as $mat): ?>
                            <tr>
                                <td><?= htmlspecialchars($mat->item_code); ?></td>
                                <td><?= htmlspecialchars($mat->item_name); ?></td>
                                <td><?= htmlspecialchars($mat->item_type); ?></td>
                                <td><?= number_format($mat->planned_qty, 4); ?></td>
                                <td><?= htmlspecialchars($mat->unit_measure); ?></td>
                                <td>
                                    <?php if($header->status == 'Planned'): ?>
                                    <a href="<?= base_url('work_order/delete_material/' . $header->id . '/' . $mat->id); ?>" 
                                       class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus material ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="6" class="text-center">Belum ada material yang direncanakan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#select-component').select2({
        placeholder: 'Cari Material / Semi-Finished...',
        width: '100%',
        ajax: {
            url: "<?= base_url('work_order/ajax_search_components'); ?>",
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