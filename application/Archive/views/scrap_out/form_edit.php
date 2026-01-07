<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-edit text-warning mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <a href="<?= base_url('Scrap_Out/view/'.$header->id); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
        </a>
    </div>

    <form action="<?= base_url('Scrap_Out/update/'.$header->id); ?>" method="post" id="scrapOutForm">
        <div class="card shadow mb-4 border-0">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-file-invoice mr-2"></i>Edit Header Dokumen</h6>
            </div>
            <div class="card-body pb-2">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><b>No. Dokumen</b></label>
                        <input type="text" name="doc_no" class="form-control" value="<?= htmlspecialchars($header->doc_no); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><b>Tgl Dokumen</b></label>
                        <input type="date" name="doc_date" class="form-control" value="<?= $header->doc_date; ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><b>Tipe BC</b></label>
                        <select name="bc_type_code" class="form-control select2" required>
                            <option value="">-- Pilih Tipe BC --</option>
                            <?php foreach($bc_types as $bc): ?>
                                <option value="<?= $bc['code']; ?>" <?= ($header->bc_type_code == $bc['code']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($bc['code'] . ' - ' . $bc['description']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><b>No. BC</b></label>
                        <input type="text" name="bc_number" class="form-control" value="<?= htmlspecialchars($header->bc_number); ?>" placeholder="Contoh: 099812">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><b>Tgl BC</b></label>
                        <input type="date" name="bc_date" class="form-control" value="<?= $header->bc_date; ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><b>Tujuan (Customer)</b></label>
                        <select name="destination" class="form-control select2" required>
                            <option value="">-- Pilih Customer --</option>
                            <?php foreach($customers as $cust): ?>
                                <option value="<?= $cust['id']; ?>" <?= ($header->destination == $cust['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cust['nama_customer']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label><b>Catatan</b></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Tambahkan catatan jika diperlukan..."><?= htmlspecialchars($header->notes); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4 border-0">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
                <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-cogs mr-2"></i>Edit Detail Item Scrap</h6>
                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-add-item">
                    <i class="fas fa-plus mr-1"></i> Tambah Item dari Stok
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>Scrap Item</th>
                                <th width="120">Stok Tersedia</th> <th width="130">Qty Keluar</th>
                                <th width="100">Satuan</th>
                                <th width="150">Nilai Jual / Unit (IDR)</th> <th width="60">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="item-table-body">
                            <?php 
                            if(!empty($details)): 
                                foreach($details as $d): 
                                    // Asumsi $d->scrap_code, $d->qty, $d->sales_value_per_unit sudah tersedia
                                    // Asumsi $d->available_qty dan $d->satuan_desc juga di-join di model
                            ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="scrap_id[]" value="<?= $d->scrap_id; ?>">
                                    <?= htmlspecialchars($d->scrap_name).' ('.$d->scrap_code.')'; ?>
                                </td>
                                <td class="text-right align-middle">
                                    <?= number_format($d->available_qty ?? 0, 2); ?>
                                </td> 
                                <td>
                                    <input type="number" name="qty[]" class="form-control text-right" 
                                           value="<?= (float)$d->qty; ?>" step="0.0001" min="0.0001" required>
                                </td>
                                <td class="text-center align-middle"><?= htmlspecialchars($d->satuan_desc); ?></td>
                                <td>
                                    <input type="number" name="sales_value_per_unit[]" class="form-control text-right" 
                                           value="<?= number_format($d->sales_value_per_unit, 2, '.', ''); ?>" step="0.01" min="0">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-item"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr class="text-center text-muted"><td colspan="6">Belum ada item.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-warning btn-sm btn-load"><i class="fas fa-save mr-1"></i> Update Draft</button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="modal-add-item" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-recycle mr-2"></i>Pilih Item Scrap (Stok Tersedia)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Scrap</th>
                                <th>Satuan</th>
                                <th>Qty Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="stock-table-body">
                            <tr><td colspan="5" class="text-center text-muted">Memuat stok...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('item-table-body');
    const modalStockBody = document.getElementById('stock-table-body');
    const modal = $('#modal-add-item');
    const totalItemDisplay = document.getElementById('total-item'); // Pastikan Anda memiliki ini jika menggunakan fungsi update

    // === Load stok saat modal dibuka ===
    modal.on('show.bs.modal', function () {
        modalStockBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Memuat stok...</td></tr>';
        
        fetch('<?= base_url("Scrap_Out/ajax_get_scrap_stock") ?>')
            .then(response => response.json())
            .then(result => {
                modalStockBody.innerHTML = '';
                if (result.status === 'success' && result.data.length > 0) {
                    result.data.forEach(item => {
                        modalStockBody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td>${item.scrap_code}</td>
                                <td>${item.scrap_name}</td>
                                <td>${item.satuan_desc}</td>
                                <td class="text-right">${parseFloat(item.available_qty).toFixed(2)}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-primary btn-sm btn-pilih"
                                        data-id="${item.scrap_id}"
                                        data-code="${item.scrap_code}"
                                        data-name="${item.scrap_name}"
                                        data-satuan="${item.satuan_desc}"
                                        data-stok="${item.available_qty}">
                                        Pilih
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    modalStockBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada stok scrap tersedia.</td></tr>';
                }
            })
            .catch(() => modalStockBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data stok.</td></tr>');
    });

    // === Tambah item ke tabel utama (Event Delegation) ===
    modalStockBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-pilih');
        if (btn) {
            const id = btn.dataset.id;
            
            // Cek duplikasi di form utama
            if (document.querySelector(`#item-table-body input[name="scrap_id[]"][value="${id}"]`)) {
                alert('Item ini sudah ditambahkan.');
                return;
            }

            const placeholder = tableBody.querySelector('.text-muted');
            if (placeholder) placeholder.remove();
            
            const newRow = `
                <tr>
                    <td>
                        <input type="hidden" name="scrap_id[]" value="${id}">
                        ${btn.dataset.name} (${btn.dataset.code})
                    </td>
                    <td class="text-right align-middle">${parseFloat(btn.dataset.stok).toFixed(2)}</td>
                    <td>
                        <input type="number" name="qty[]" class="form-control form-control-sm text-right" 
                            step="0.0001" min="0.0001" max="${btn.dataset.stok}" required>
                    </td>
                    <td class="text-center align-middle">${btn.dataset.satuan}</td>
                    <td>
                        <input type="number" name="sales_value_per_unit[]" class="form-control form-control-sm text-right" 
                            step="0.01" min="0" value="0.00">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-item" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', newRow);
            updateTotalItem();
            modal.modal('hide');
        }
    });

    // === Hapus item (Event Delegation) ===
    tableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-item');
        if (btn) {
            btn.closest('tr').remove();
            if (tableBody.children.length === 0) {
                tableBody.innerHTML = '<tr class="text-center text-muted"><td colspan="6">Belum ada item.</td></tr>';
            }
            updateTotalItem();
        }
    });

    // === Update total item ===
    function updateTotalItem() {
        const count = document.querySelectorAll('#item-table-body input[name="scrap_id[]"]').length;
        totalItemDisplay.textContent = count;
    }

    // === Validasi form sebelum submit ===
    document.getElementById('scrapOutForm').addEventListener('submit', function(e) {
        const items = document.querySelectorAll('input[name="scrap_id[]"]');
        if (items.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada satu item scrap.');
        }
    });
});
</script>