<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-box-open"></i> Pilih Barang Jadi untuk Dikirim - <?= htmlspecialchars($header->delivery_number) ?></h4>
        <div>
            <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-secondary btn-sm">Kembali ke Header</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <table class="table table-sm table-borderless">
                <tr><th>Nomor DO</th><td><?= htmlspecialchars($header->delivery_number) ?></td><th>Tanggal</th><td><?= htmlspecialchars($header->delivery_date) ?></td></tr>
                <tr><th>Customer</th><td><?= htmlspecialchars($header->customer_name ?? '-') ?></td><th>BC Type</th><td><?= htmlspecialchars($header->bc_type_id ?? '-') ?></td></tr>
            </table>
        </div>
    </div>

    <form method="post" action="<?= site_url('fg_delivery/store_items/'.$header->id) ?>">
        <div class="card">
            <div class="card-header">
                <strong>Daftar Barang Jadi Siap Dikirim</strong>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th>Pilih</th><th>SC Number</th><th>WO Number</th><th>Production No</th>
                            <th>Kode FG</th><th>Nama Barang</th><th>Stok</th><th>Qty Kirim</th><th>UOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($available_items)): foreach($available_items as $it): ?>
                        <tr>
                            <td class="text-center" style="width:80px;">
                                <input type="checkbox" name="details[<?= $it->receipt_detail_id ?>][checked]" value="1">
                                <input type="hidden" name="details[<?= $it->receipt_detail_id ?>][fg_id]" value="<?= $it->fg_id ?>">
                                <input type="hidden" name="details[<?= $it->receipt_detail_id ?>][uom]" value="<?= htmlspecialchars($it->satuan) ?>">
                            </td>
                            <td><?= htmlspecialchars($it->sc_number ?? '-') ?></td>
                            <td><?= htmlspecialchars($it->wo_number ?? '-') ?></td>
                            <td><?= htmlspecialchars($it->production_number ?? '-') ?></td>
                            <td><?= htmlspecialchars($it->kode_barang) ?></td>
                            <td><?= htmlspecialchars($it->nama_barang) ?></td>
                            <td class="text-right"><?= number_format($it->current_stock ?? 0,2) ?></td>
                            <td style="width:130px;"><input type="number" step="0.001" min="0" name="details[<?= $it->receipt_detail_id ?>][qty_shipped]" class="form-control form-control-sm"></td>
                            <td><?= htmlspecialchars($it->satuan) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center text-muted">Tidak ada FG siap dikirim untuk customer ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-right">
                <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-light">Batal</a>
                <button class="btn btn-success">Simpan Detail</button>
            </div>
        </div>
    </form>
</div>
