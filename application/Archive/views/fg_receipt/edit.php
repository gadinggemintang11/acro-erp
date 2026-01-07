<div class="container-fluid">
    <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-edit text-warning mr-2"></i>Edit Penerimaan Barang Jadi</h3>
    <form action="<?= base_url('fg_receipt/update/' . $receipt_header->id); ?>" method="POST" id="form-fg-receipt">
        <div class="card shadow mb-4">
             <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning">Informasi Penerimaan</h6></div>
             <div class="card-body">
                 <div class="row">
                     <div class="col-md-4 form-group">
                         <label>No Penerimaan</label>
                         <input type="text" class="form-control" value="<?= htmlspecialchars($receipt_header->receipt_number); ?>" readonly>
                     </div>
                    <div class="col-md-4 form-group">
                         <label for="receipt_date">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="<?= htmlspecialchars($receipt_header->receipt_date); ?>" required>
                    </div>
                     <div class="col-md-4 form-group">
                         <label for="remarks">Catatan / Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="1"><?= htmlspecialchars($receipt_header->remarks); ?></textarea>
                    </div>
                     </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-warning">Item Barang Jadi Diterima</h6>
                </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="receipt-items-table" width="100%">
                        <thead class="thead-light">
                             <tr>
                                <th width="35%">Output Produksi FG</th>
                                <th>Item FG</th>
                                <th width="15%">Qty Sisa + Diterima Ini</th>
                                <th width="15%">Qty Diterima <span class="text-danger">*</span></th>
                                <th width="10%">Unit</th>
                                <th>Notes</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php $itemIndex = 0; foreach($receipt_details as $detail): ?>
                            <tr>
                                <td class="align-middle">
                                     <?= $detail->prod_output_id ? ('Prod Output #' . $detail->prod_output_id) : '-'; ?>
                                     <input type="hidden" name="details[<?= $itemIndex ?>][prod_output_id]" value="<?= $detail->prod_output_id; ?>">
                                     <input type="hidden" name="details[<?= $itemIndex ?>][fg_id]" value="<?= $detail->fg_id; ?>">
                                     <input type="hidden" name="details[<?= $itemIndex ?>][uom]" value="<?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?>">
                                </td>
                                <td class="align-middle"><?= htmlspecialchars($detail->fg_name); ?> (<?= htmlspecialchars($detail->fg_code); ?>)</td>
                                <td class="align-middle text-right">
                                    <?php
                                        // TODO: Perlu query sisa sebenarnya dari prod output + qty yg sudah diterima di dokumen ini
                                        $display_sisa = '-'; // Placeholder, perlu data sisa real
                                        echo $display_sisa;
                                    ?>
                                </td>
                                <td>
                                    <input type="number" step="0.0001" name="details[<?= $itemIndex ?>][qty_received]" class="form-control form-control-sm text-right item-qty-received" required min="0.0001" value="<?= (float)$detail->qty_received; ?>"
                                           max=""> </td>
                                <td class="align-middle text-center"><?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?></td>
                                <td>
                                    <input type="text" name="details[<?= $itemIndex ?>][notes]" class="form-control form-control-sm" value="<?= htmlspecialchars($detail->notes); ?>">
                                </td>
                                <td class="text-center">
                                     <button type="button" class="btn btn-danger btn-sm remove-item-row" title="Hapus Item"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                             <?php $itemIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
             <div class="card-footer text-right">
                 <button type="submit" class="btn btn-warning btn-load"> <i class="fas fa-save mr-1"></i> Update Penerimaan
                 </button>
                 <a href="<?= base_url('fg_receipt/show/' . $receipt_header->id); ?>" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </form>
</div>
<?php $this->load->view('templates/footer'); ?>
<script>
$(document).ready(function() {
    // Logika hapus baris
     $('#receipt-items-table').on('click', '.remove-item-row', function() {
        if ($('#receipt-items-table tbody tr').length > 1) { // Hanya hapus jika > 1 baris
            $(this).closest('tr').remove();
        } else {
            Swal.fire('Info', 'Minimal harus ada satu item detail.', 'info');
        }
    });

     // Logika validasi Qty (Sama seperti create)
      $('#receipt-items-table').on('change', '.item-qty-received', function() { /* ... */ });
});
</script>