<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-plus-circle text-success mr-2"></i><?= htmlspecialchars($title); ?> (Input Manual)</h3>
        <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke List
        </a>
    </div>

    <form action="<?= base_url('Scrap_In/store'); ?>" method="post"> 
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-success">Header Dokumen</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>No. Dokumen</label>
                        <input type="text" name="doc_no" class="form-control" value="<?= htmlspecialchars($doc_no); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Tgl Dokumen</label>
                        <input type="date" name="doc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Sumber Work Order (Opsional)</label>
                        <select name="work_order_id" class="form-control">
                            <option value="">-- Pilih WO --</option>
                            <?php foreach($work_orders as $wo): ?>
                                <option value="<?= $wo['id']; ?>"><?= htmlspecialchars($wo['wo_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
             <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success">Detail Item Scrap</h6>
                <button type="button" class="btn btn-primary btn-sm" id="btn-add-item">
                    <i class="fas fa-plus mr-1"></i> Tambah Item
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr class="text-center">
                                <th>Scrap Item</th>
                                <th width="150">Qty</th>
                                <th>Satuan</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="item-table-body">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success btn-load">
                    <i class="fas fa-save mr-1"></i> Simpan Draft
                </button>
            </div>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="scrap_id[]" class="form-control select-scrap" required>
                <option value="">-- Pilih Scrap --</option>
                <?php foreach($master_scrap as $scrap): ?>
                    <option value="<?= $scrap['id']; ?>" data-satuan="<?= htmlspecialchars($scrap['satuan_desc']); ?>">
                        <?= htmlspecialchars($scrap['scrap_name'] . ' (' . $scrap['scrap_code'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="qty[]" class="form-control text-right" step="0.0001" min="0.0001" required>
        </td>
        <td class="text-center satuan-cell">
            -
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-remove-item">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnAddItem = document.getElementById('btn-add-item');
    const tableBody = document.getElementById('item-table-body');
    const rowTemplate = document.getElementById('row-template');

    // Fungsi untuk menambah baris baru
    function addRow() {
        const newRow = rowTemplate.content.cloneNode(true);
        tableBody.appendChild(newRow);
        // Mungkin perlu inisialisasi select2 jika pakai
    }

    // Tambah baris awal saat load (opsional)
    // addRow(); 

    btnAddItem.addEventListener('click', addRow);

    tableBody.addEventListener('click', function(e) {
        // Hapus baris
        if (e.target.classList.contains('btn-remove-item') || e.target.closest('.btn-remove-item')) {
            // Jangan hapus jika hanya satu baris tersisa (opsional)
            // if (tableBody.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
            // }
        }
    });

    tableBody.addEventListener('change', function(e) {
        // Update satuan saat scrap dipilih
        if (e.target.classList.contains('select-scrap')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const satuan = selectedOption.dataset.satuan || '-';
            e.target.closest('tr').querySelector('.satuan-cell').textContent = satuan;
        }
    });
});
</script>