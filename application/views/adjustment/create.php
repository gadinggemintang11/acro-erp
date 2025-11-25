<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>
    
    <form action="<?= base_url('adjustment/store'); ?>" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Dokumen</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>No. Dokumen</label>
                            <input type="text" name="doc_no" class="form-control" value="<?= $doc_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tanggal Dokumen *</label>
                            <input type="date" name="doc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Catatan (Alasan Penyesuaian) *</label>
                            <input type="text" name="notes" class="form-control" placeholder="cth: Hasil Stock Opname Gudang A" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detail Item</h6>
                <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Baris</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="detail-table">
                        <thead>
                            <tr>
                                <th>Tipe Item *</th>
                                <th>Nama Item *</th>
                                <th>Tipe Penyesuaian *</th>
                                <th>Qty *</th>
                                <th>Detail (Batch/Nilai) *</th>
                                <th>Satuan</th>
                                <th>Keterangan</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right">
            <a href="<?= base_url('adjustment'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan sebagai Draft</button>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="details[0][item_type]" class="form-control item-type" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="material">Material</option>
                <option value="semifinished">Semifinished</option>
                <option value="finished_goods">Finished Goods</option>
                <option value="inventory">Inventory</option>
                <option value="machine">Machine</option>
            </select>
        </td>
        <td>
            <select name="details[0][item_id]" class="form-control item-id" required disabled>
                <option value="">-- Pilih Tipe Dulu --</option>
            </select>
        </td>
        <td>
            <select name="details[0][adjustment_type]" class="form-control adjustment-type" required>
                <option value="IN">IN (Masuk)</option>
                <option value="OUT">OUT (Keluar)</option>
            </select>
        </td>
        <td>
            <input type="number" name="details[0][qty]" class="form-control qty" min="0.0001" step="0.0001" required>
        </td>
        <td>
            <div class="detail-placeholder">
                <input type="number" name="details[0][unit_value]" class="form-control unit-value" min="0" step="0.01" placeholder="Masukkan Nilai (Rp)" required>
                <select name="details[0][stock_on_hand_id]" class="form-control stock-on-hand-id" style="display: none;" disabled>
                    <option value="">-- Pilih Batch --</option>
                </select>
            </div>
        </td>
        <td>
            <input type="text" class="form-control item-unit" readonly>
        </td>
        <td>
            <input type="text" name="details[0][remarks]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
</template>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let rowIndex = 0;
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");

    function addRow() {
        const newRow = template.content.cloneNode(true);
        newRow.querySelector("tr").setAttribute("data-row-id", rowIndex);

        // Update name attribute to be unique
        newRow.querySelectorAll("input, select").forEach(el => {
            el.name = el.name.replace(/details\[0\]/g, `details[${rowIndex}]`);
        });
        
        detailBody.appendChild(newRow);
        rowIndex++;
    }

    // Tambah baris pertama saat load
    addRow();

    // Event listener untuk tombol "Tambah Baris"
    document.getElementById("add-row").addEventListener("click", addRow);

    // Event delegation untuk body tabel
    detailBody.addEventListener("click", function(e) {
        // Hapus baris
        if (e.target.closest(".remove-row")) {
            e.target.closest("tr").remove();
        }
    });

    detailBody.addEventListener("change", function(e) {
        const row = e.target.closest("tr");
        if (!row) return;

        // 1. Saat Tipe Item berubah -> load item
        if (e.target.classList.contains("item-type")) {
            const itemType = e.target.value;
            const itemIdSelect = row.querySelector(".item-id");
            const itemUnitInput = row.querySelector(".item-unit");
            
            itemIdSelect.innerHTML = '<option value="">Memuat...</option>';
            itemIdSelect.disabled = true;
            itemUnitInput.value = '';

            if (itemType) {
                fetch(`<?= base_url('adjustment/ajax_get_items'); ?>?type=${itemType}`)
                    .then(response => response.json())
                    .then(items => {
                        itemIdSelect.innerHTML = '<option value="">-- Pilih Item --</option>';
                        items.forEach(item => {
                            const option = new Option(`${item.code} - ${item.name}`, item.id);
                            option.dataset.unit = item.unit;
                            itemIdSelect.appendChild(option);
                        });
                        itemIdSelect.disabled = false;
                        updateDetailInput(row); // Update tampilan input batch/value
                    });
            } else {
                itemIdSelect.innerHTML = '<option value="">-- Pilih Tipe Dulu --</option>';
                updateDetailInput(row);
            }
        }

        // 2. Saat Item berubah -> set unit
        if (e.target.classList.contains("item-id")) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            row.querySelector(".item-unit").value = selectedOption.dataset.unit || '';
            updateDetailInput(row); // Update juga jika item berubah
        }
        
        // 3. Saat Tipe Penyesuaian berubah
        if (e.target.classList.contains("adjustment-type")) {
            updateDetailInput(row);
        }
    });

    // Fungsi untuk mengubah input (Batch vs Value)
    function updateDetailInput(row) {
        const itemType = row.querySelector(".item-type").value;
        const adjType = row.querySelector(".adjustment-type").value;
        const itemId = row.querySelector(".item-id").value;
        
        const valueInput = row.querySelector(".unit-value");
        const batchSelect = row.querySelector(".stock-on-hand-id");

        // Reset
        valueInput.style.display = 'none';
        valueInput.required = false;
        valueInput.disabled = true;
        
        batchSelect.style.display = 'none';
        batchSelect.required = false;
        batchSelect.disabled = true;
        batchSelect.innerHTML = '<option value="">-- Pilih Batch --</option>';

        if (adjType === 'IN') {
            // Tipe IN: Selalu minta Unit Value
            valueInput.style.display = 'block';
            valueInput.required = true;
            valueInput.disabled = false;
        
        } else { // Tipe OUT
            if (itemType === 'material') {
                // Tipe OUT & Material: Minta Batch
                batchSelect.style.display = 'block';
                batchSelect.required = true;
                batchSelect.disabled = false;
                
                // Load batches
                if (itemId) {
                    batchSelect.innerHTML = '<option value="">Memuat batch...</option>';
                    fetch(`<?= base_url('adjustment/ajax_get_batches'); ?>?item_id=${itemId}`)
                        .then(response => response.json())
                        .then(batches => {
                            batchSelect.innerHTML = '<option value="">-- Pilih Batch --</option>';
                            batches.forEach(batch => {
                                const text = `Qty: ${batch.available_qty} (Value: ${batch.unit_value}) - GRN: ${batch.grn_header_id || 'ADJ'}`;
                                batchSelect.appendChild(new Option(text, batch.id));
                            });
                        });
                }
            } else {
                // Tipe OUT & Non-Material: Tidak perlu input apa-apa
                // Value akan diambil dari ledger terakhir di backend
            }
        }
    }
});
</script>