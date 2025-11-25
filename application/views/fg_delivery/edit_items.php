<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-edit me-2"></i>Edit Detail Pengiriman
        </h3>
        <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Batal
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <strong>Customer:</strong> <?= htmlspecialchars($header->nama_customer) ?><br>
            <strong>Tanggal DO:</strong> <?= htmlspecialchars($header->delivery_date) ?><br>
            <strong>Alamat:</strong> <?= htmlspecialchars($header->customer_address ?? '-') ?>
        </div>
    </div>

    <?= form_open('fg_delivery/update_items') ?>
    <input type="hidden" name="fg_delivery_header_id" value="<?= $header->id ?>">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <strong>Pilih Item FG Siap Kirim</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Pilih</th>
                            <th>No</th>
                            <th>SC</th>
                            <th>WO</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Stock</th>
                            <th>Qty Kirim</th>
                            <th>UOM</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($fg_ready as $f):
                            $existing_item = $existing_details_map[$f->fg_id] ?? null;
                            $is_checked = ($existing_item !== null);
                            $qty_val = $is_checked ? $existing_item->qty_shipped : '';
                            $notes_val = $is_checked ? $existing_item->notes : '';
                            $is_disabled = !$is_checked;
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="chk" data-fg="<?= $f->fg_id ?>" <?= $is_checked ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($f->sc_number) ?></td>
                            <td><?= htmlspecialchars($f->wo_number) ?></td>
                            <td><?= htmlspecialchars($f->kode_barang) ?></td>
                            <td><?= htmlspecialchars($f->nama_barang) ?></td>
                            <td class="text-end">
                                <?php 
                                    // Show current stock + already shipped qty for context
                                    $stock_display = $f->current_stock;
                                    if($is_checked) {
                                        $stock_display += $qty_val;
                                    }
                                    echo number_format($stock_display, 2);
                                ?>
                            </td>
                            <td>
                                <input type="hidden" name="details[<?= $f->fg_id ?>][fg_id]" value="<?= $f->fg_id ?>">
                                <input type="hidden" name="details[<?= $f->fg_id ?>][sales_contract_id]" value="<?= $f->sc_id ?>">
                                <input type="number" name="details[<?= $f->fg_id ?>][qty_shipped]" value="<?= $qty_val ?>" step="0.01" min="0" max="<?= $stock_display ?>" class="form-control form-control-sm qty" <?= $is_disabled ? 'disabled' : '' ?>>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($f->satuan) ?></td>
                            <td><input type="text" name="details[<?= $f->fg_id ?>][notes]" value="<?= $notes_val ?>" class="form-control form-control-sm" <?= $is_disabled ? 'disabled' : '' ?>></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end mt-4">
        <button class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Items</button>
    </div>

    <?= form_close(); ?>

</div>

<script>
document.querySelectorAll('.chk').forEach(chk => {
    chk.addEventListener('change', () => {
        const tr = chk.closest('tr');
        const fg = chk.dataset.fg;
        const qty = tr.querySelector(`[name="details[${fg}][qty_shipped]"]`);
        const notes = tr.querySelector(`[name="details[${fg}][notes]"]`);
        if (chk.checked) {
            qty.disabled = false;
            notes.disabled = false;
        } else {
            qty.disabled = true; qty.value = '';
            notes.disabled = true; notes.value = '';
        }
    });
});
</script>
