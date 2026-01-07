<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-recycle text-success mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke List
        </a>
    </div>

    <form action="<?= base_url('Scrap_In/process_collection'); ?>" method="post" id="collect-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Header Dokumen (Draft)</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>No. Dokumen (Draft)</label>
                        <input type="text" name="doc_no" class="form-control" value="<?= htmlspecialchars($doc_no); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Tgl Dokumen</label>
                        <input type="date" name="doc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                     <div class="col-md-4 form-group">
                        <label>Sumber</label>
                        <input type="text" class="form-control" value="Hasil Produksi (Otomatis)" readonly>
                    </div>
                </div>
                 <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
             <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success">Pilih Scrap dari Produksi (Belum Masuk Stok)</h6>
                 <div>
                     <button type="button" class="btn btn-sm btn-info" id="select-all">Pilih Semua</button>
                     <button type="button" class="btn btn-sm btn-warning" id="deselect-all">Lepas Semua</button>
                 </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr class="text-center">
                                <th width="30">Pilih</th>
                                <th>Kode Scrap</th>
                                <th>Nama Scrap</th>
                                <th>Satuan</th>
                                <th>Qty Scrap</th>
                                <th>No. Produksi</th>
                                <th>No. WO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($uncollected_scrap_rows)): ?>
                                <?php foreach($uncollected_scrap_rows as $scrap): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="selected_outputs[]" value="<?= $scrap['production_output_id']; ?>" class="scrap-checkbox">
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($scrap['scrap_code']); ?></td>
                                    <td><?= htmlspecialchars($scrap['scrap_name']); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($scrap['satuan_desc']); ?></td>
                                    <td class="text-right"><?= (float)$scrap['qty_scrap']; ?></td>
                                    <td class="text-center"><?= htmlspecialchars($scrap['production_number'] ?: '-'); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($scrap['wo_number'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Tidak ada scrap baru yang ditemukan dari hasil produksi.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                 <?php if(empty($uncollected_scrap_rows)): ?>
                    <div class="alert alert-info text-center">
                        Semua scrap yang dihasilkan produksi sudah masuk ke dalam dokumen Scrap In (Draft atau Confirmed).
                    </div>
                 <?php endif; ?>
            </div>
            <?php if(!empty($uncollected_scrap_rows)): ?>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success btn-load">
                    <i class="fas fa-save mr-1"></i> Buat Draft Penerimaan Scrap
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('collect-form');
    const selectAllBtn = document.getElementById('select-all');
    const deselectAllBtn = document.getElementById('deselect-all');
    const checkboxes = document.querySelectorAll('.scrap-checkbox');

    selectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
    });

    deselectAllBtn.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
    });

    form.addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.scrap-checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault(); // Hentikan submit
            alert('Pilih minimal satu baris scrap untuk dibuatkan draft.');
        }
    });
});
</script>