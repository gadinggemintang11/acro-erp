<div class="container-fluid">
    <h3 class="h4 mb-3 text-gray-800">
        <i class="fas fa-box-open text-primary mr-2"></i> Pengiriman Barang Jadi - <?= htmlspecialchars($source_document->source_number); ?>
    </h3>

    <form method="post" action="<?= site_url('fg_delivery/store'); ?>">
        <input type="hidden" name="sales_contract_id" value="<?= $source_document->id; ?>">
        <input type="hidden" name="customer_id" value="<?= $source_document->customer_id; ?>">

        <div class="card mb-3">
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-4">
                        <label>Tanggal Pengiriman</label>
                        <input type="date" name="delivery_date" class="form-control" required value="<?= date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-8">
                        <label>Alamat Pengiriman</label>
                        <input type="text" name="shipping_address" class="form-control" value="<?= htmlspecialchars($source_document->shipping_address); ?>">
                    </div>
                </div>

                <div class="form-row mt-3">
                    <div class="col-md-6">
                        <label>Nomor BC</label>
                        <input type="text" name="bc_number" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal BC</label>
                        <input type="date" name="bc_date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Tipe BC</label>
                        <select name="bc_type_id" class="form-control">
                            <option value="">-- Pilih --</option>
                            <?php foreach($bc_types as $b): ?>
                            <option value="<?= $b->id; ?>"><?= htmlspecialchars($b->code_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label>Keterangan</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><b>Daftar Item</b></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th width="120">Qty Kirim</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($source_items as $i => $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item->kode_barang); ?></td>
                            <td><?= htmlspecialchars($item->nama_barang); ?></td>
                            <td><?= htmlspecialchars($item->satuan); ?></td>
                            <td>
                                <input type="number" name="details[<?= $i; ?>][qty_shipped]" min="0" step="0.001" class="form-control">
                                <input type="hidden" name="details[<?= $i; ?>][fg_id]" value="<?= $item->fg_id; ?>">
                                <input type="hidden" name="details[<?= $i; ?>][uom]" value="<?= $item->satuan; ?>">
                            </td>
                            <td><input type="text" name="details[<?= $i; ?>][notes]" class="form-control"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3 text-right">
            <button type="submit" name="status" value="Draft" class="btn btn-secondary"><i class="fas fa-save"></i> Simpan Draft</button>
            <button type="submit" name="status" value="Shipped" class="btn btn-success"><i class="fas fa-paper-plane"></i> Simpan & Kirim</button>
        </div>
    </form>
</div>
