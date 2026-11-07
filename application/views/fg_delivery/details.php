<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h3 class="h5 mb-0 text-gray-800"><i class="fas fa-boxes"></i> Isi Detail - <?= htmlspecialchars($header->delivery_number) ?></h3>
        <div>
            <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-sm btn-secondary">Lihat Detail</a>
            <a href="<?= site_url('fg_delivery') ?>" class="btn btn-sm btn-light">Daftar DO</a>
        </div>
    </div>

    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <table class="table table-sm table-borderless">
                <tr><th>Nomor DO</th><td><?= htmlspecialchars($header->delivery_number) ?></td>
                <th>Tanggal</th><td><?= htmlspecialchars($header->delivery_date) ?></td></tr>
                <tr><th>Customer</th><td><?= htmlspecialchars($header->customer_name) ?></td>
                <th>Metode</th><td><?= htmlspecialchars($header->shipping_method) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Form tambah detail -->
    <div class="card mb-3">
        <div class="card-header"><b>Tambah Item</b></div>
        <div class="card-body">
            <form action="<?= site_url('fg_delivery/store_detail/'.$header->id) ?>" method="post">
                <div id="detail-rows">
                    <div class="form-row align-items-end detail-row mb-2">
                        <div class="col-md-3">
                            <label>Sales Order Number (opsional)</label>
                            <input type="text" name="details[0][sales_contract_id]" class="form-control sc-autocomplete" placeholder="Isi ID Sales Order atau kosongkan">
                        </div>
                        <div class="col-md-3">
                            <label>Barang FG</label>
                            <select name="details[0][fg_id]" class="form-control">
                                <option value="">-- Pilih FG --</option>
                                <?php foreach($available_fg as $fg): ?>
                                    <option value="<?= $fg->id ?>"><?= htmlspecialchars($fg->kode_barang.' - '.$fg->nama_barang) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Qty</label>
                            <input type="number" step="0.001" name="details[0][qty_shipped]" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>UOM</label>
                            <input type="text" name="details[0][uom]" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-success btn-add-row">+ Tambah Baris</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <button type="submit" class="btn btn-primary">Simpan Detail</button>
                    <a href="<?= site_url('fg_delivery/ship/'.$header->id) ?>" class="btn btn-success" onclick="return confirm('Yakin ingin kirim / ship?')">Ship</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel detail yang sudah ada -->
    <div class="card">
        <div class="card-header"><b>Detail Yang Tersimpan</b></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr><th>#</th><th>SC</th><th>Kode</th><th>Nama</th><th>Qty</th><th>UOM</th><th>Catatan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($details)): $no=1; foreach($details as $d): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($d->sc_number ?? '-') ?></td>
                        <td><?= htmlspecialchars($d->kode_barang) ?></td>
                        <td><?= htmlspecialchars($d->nama_barang) ?></td>
                        <td><?= number_format($d->qty_shipped,3) ?></td>
                        <td><?= htmlspecialchars($d->uom) ?></td>
                        <td><?= htmlspecialchars($d->notes) ?></td>
                        <td>
                            <a href="<?= site_url('fg_delivery/delete_detail/'.$d->id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus detail?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center text-muted">Belum ada detail</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let rowIndex = 1;
$('.btn-add-row').on('click', function(){
    const rowHtml = $('.detail-row').first().clone();
    rowHtml.find('input,select').each(function(){
        const name = $(this).attr('name');
        if (!name) return;
        const newName = name.replace(/\[0\]/, '['+rowIndex+']');
        $(this).attr('name', newName).val('');
    });
    $('#detail-rows').append(rowHtml);
    rowIndex++;
});
</script>
