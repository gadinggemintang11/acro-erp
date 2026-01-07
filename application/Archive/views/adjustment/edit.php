<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>
    
    <form action="<?= base_url('adjustment/update/' . $header->id); ?>" method="POST">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Dokumen</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>No. Dokumen</label>
                            <input type="text" name="doc_no" class="form-control" value="<?= $header->doc_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tanggal Dokumen *</label>
                            <input type="date" name="doc_date" class="form-control" value="<?= $header->doc_date; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Catatan (Alasan Penyesuaian) *</label>
                            <input type="text" name="notes" class="form-control" value="<?= $header->notes; ?>" required>
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
                            <?php foreach ($details as $i => $detail): ?>
                            <tr data-row-id="<?= $i; ?>">
                                <td>
                                    <select name="details[<?= $i; ?>][item_type]" class="form-control item-type" required>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="material" <?= $detail->item_type == 'material' ? 'selected' : ''; ?>>Material</option>
                                        <option value="semifinished" <?= $detail->item_type == 'semifinished' ? 'selected' : ''; ?>>Semifinished</option>
                                        <option value="finished_goods" <?= $detail->item_type == 'finished_goods' ? 'selected' : ''; ?>>Finished Goods</option>
                                        <option value="inventory" <?= $detail->item_type == 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                                        <option value="machine" <?= $detail->item_type == 'machine' ? 'selected' : ''; ?>>Machine</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="details[<?= $i; ?>][item_id]" class="form-control item-id" required>
                                        <option value="">-- Pilih Item --</option>
                                        <?php foreach($form_data[$i]['items'] as $item): ?>
                                            <option value="<?= $item->id; ?>" data-unit="<?= $item->unit; ?>" <?= $item->id == $detail->item_id ? 'selected' : ''; ?>>
                                                <?= $item->code; ?> - <?= $item->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="details[<?= $i; ?>][adjustment_type]" class="form-control adjustment-type" required>
                                        <option value="IN" <?= $detail->adjustment_type == 'IN' ? 'selected' : ''; ?>>IN (Masuk)</option>
                                        <option value="OUT" <?= $detail->adjustment_type == 'OUT' ? 'selected' : ''; ?>>OUT (Keluar)</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="details[<?= $i; ?>][qty]" class="form-control qty" value="<?= (float)$detail->qty; ?>" min="0.0001" step="0.0001" required>
                                </td>
                                <td>
                                    <div class="detail-placeholder">
                                        <input type="number" name="details[<?= $i; ?>][unit_value]" class="form-control unit-value" 
                                               value="<?= (float)$detail->unit_value; ?>" min="0" step="0.01" 
                                               placeholder="Masukkan Nilai (Rp)" 
                                               <?= $detail->adjustment_type == 'IN' ? 'required' : 'style="display: none;" disabled'; ?>>
                                        
                                        <select name="details[<?= $i; ?>][stock_on_hand_id]" class="form-control stock-on-hand-id" 
                                                <?= ($detail->adjustment_type == 'OUT' && $detail->item_type == 'material') ? 'required' : 'style="display: none;" disabled'; ?>>
                                            <option value="">-- Pilih Batch --</option>
                                            <?php foreach($form_data[$i]['batches'] as $batch): 
                                                // Tampilkan Qty batch + Qty yg sudah dipilih di item ini (jika edit)
                                                $current_qty = ($batch->id == $detail->stock_on_hand_id) ? $detail->qty : 0;
                                                $display_qty = $batch->available_qty + $current_qty;
                                                $text = "Qty: {$display_qty} (Value: {$batch->unit_value}) - GRN: {$batch->grn_header_id}";
                                            ?>
                                                <option value="<?= $batch->id; ?>" <?= $batch->id == $detail->stock_on_hand_id ? 'selected' : ''; ?>>
                                                    <?= $text; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control item-unit" value="<?= $detail->item_unit; ?>" readonly>
                                </td>
                                <td>
                                    <input type="text" name="details[<?= $i; ?>][remarks]" class="form-control" value="<?= $detail->remarks; ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right">
            <a href="<?= base_url('adjustment/view/' . $header->id); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Update Dokumen Draft</button>
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
    let rowIndex = <?= count($details); ?>; // <-- Perbedaan utama ada di sini
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");

    function addRow() {
        const newRow = template.content.cloneNode(true);
        newRow.querySelector("tr").setAttribute("data-row-id", rowIndex);
        newRow.querySelectorAll("input, select").forEach(el => {
            el.name = el.name.replace(/details\[0\]/g, `details[${rowIndex}]`);
        });
        detailBody.appendChild(newRow);
        rowIndex++;
    }

    document.getElementById("add-row").addEventListener("click", addRow);

    detailBody.addEventListener("click", function(e) {
        if (e.target.closest(".remove-row")) {
            e.target.closest("tr").remove();
        }
    });

    detailBody.addEventListener("change", function(e) {
        const row = e.target.closest("tr");
        if (!row) return;

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
                        updateDetailInput(row);
                    });
            } else {
                itemIdSelect.innerHTML = '<option value="">-- Pilih Tipe Dulu --</option>';
                updateDetailInput(row);
            }
        }

        if (e.target.classList.contains("item-id")) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            row.querySelector(".item-unit").value = selectedOption.dataset.unit || '';
            updateDetailInput(row);
        }
        
        if (e.target.classList.contains("adjustment-type")) {
            updateDetailInput(row);
        }
    });

    function updateDetailInput(row) {
        const itemType = row.querySelector(".item-type").value;
        const adjType = row.querySelector(".adjustment-type").value;
        const itemId = row.querySelector(".item-id").value;
        
        const valueInput = row.querySelector(".unit-value");
        const batchSelect = row.querySelector(".stock-on-hand-id");

        valueInput.style.display = 'none';
        valueInput.required = false;
        valueInput.disabled = true;
        
        batchSelect.style.display = 'none';
        batchSelect.required = false;
        batchSelect.disabled = true;
        
        // Hanya reset batch select jika ini baris baru (tidak punya data-row-id)
        // atau jika bukan tipe material
        const isNewRow = !row.hasAttribute('data-row-id') || row.getAttribute('data-row-id') >= <?= count($details); ?>;
        if(isNewRow || itemType != 'material') {
             batchSelect.innerHTML = '<option value="">-- Pilih Batch --</option>';
        }

        if (adjType === 'IN') {
            valueInput.style.display = 'block';
            valueInput.required = true;
            valueInput.disabled = false;
        
        } else { // Tipe OUT
            if (itemType === 'material') {
                batchSelect.style.display = 'block';
                batchSelect.required = true;
                batchSelect.disabled = false;
                
                // Hanya load batch jika ini baris baru
                if (isNewRow && itemId) {
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
            }
        }
    }
});
</script>