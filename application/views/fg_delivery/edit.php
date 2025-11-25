<div class="container-fluid">
    <h3 class="h4 mb-3 text-gray-800">
        <i class="fas fa-edit text-warning mr-2"></i> Edit Delivery Order
    </h3>

    <form method="post" action="<?= site_url('fg_delivery/update/'.$delivery_header->id); ?>">
        <div class="card mb-3">
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-4">
                        <label>Tanggal Kirim</label>
                        <input type="date" name="delivery_date" value="<?= $delivery_header->delivery_date; ?>" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label>Alamat Pengiriman</label>
                        <input type="text" name="shipping_address" value="<?= htmlspecialchars($delivery_header->shipping_address); ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><b>Daftar Item</b></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Qty Kirim</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($delivery_details as $i => $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d->kode_barang); ?></td>
                            <td><?= htmlspecialchars($d->nama_barang); ?></td>
                            <td><?= htmlspecialchars($d->satuan); ?></td>
                            <td><input type="number" step="0.001" name="details[<?= $i; ?>][qty_shipped]" value="<?= $d->qty_shipped; ?>" class="form-control"></td>
                            <td><input type="text" name="details[<?= $i; ?>][notes]" value="<?= htmlspecialchars($d->notes); ?>" class="form-control"></td>
                            <input type="hidden" name="details[<?= $i; ?>][fg_id]" value="<?= $d->fg_id; ?>">
                            <input type="hidden" name="details[<?= $i; ?>][uom]" value="<?= $d->satuan; ?>">
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3 text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
        </div>
    </form>
</div>
