<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0"><i class="fas fa-shopping-cart text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('purchase_order', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addHeaderModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat PO Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>No. PO</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($pos)): $no=1; foreach($pos as $po): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($po->po_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($po->nama_supplier); ?></td>
                            <td><?= date('d M Y', strtotime($po->po_date)); ?></td>
                            <td><span class="badge badge-info"><?= $po->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('purchase_order/detail/'.$po->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if ($po->status == 'Draft' && check_access('purchase_order', 'delete')): ?>
                                <a href="<?= base_url('purchase_order/delete_header/'.$po->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus PO ini? Semua item detail akan ikut terhapus jika ada!">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data Purchase Order</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Header PO Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier</label>
                                <select name="supplier_id" class="form-control" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    <?php foreach($suppliers as $s): ?>
                                        <option value="<?= $s->id; ?>"><?= htmlspecialchars($s->nama_supplier); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal PO</label>
                                <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                             <div class="form-group">
                                <label>Mode Pengiriman</label>
                                <select name="shipping_mode_code" class="form-control">
                                    <option value="">-- Pilih Mode --</option>
                                     <?php foreach($shipping_modes as $mode): ?>
                                        <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                         <div class="col-md-6">
                             <div class="form-group">
                                <label>Mode Pembayaran</label>
                                <select name="payment_mode_code" class="form-control">
                                     <option value="">-- Pilih Mode --</option>
                                     <?php foreach($payment_modes as $mode): ?>
                                        <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Currency</label>
                                <select name="currency_code" id="currency_code" class="form-control">
                                    <?php foreach($currencies as $curr): ?>
                                        <option value="<?= $curr->code; ?>" <?= $curr->code == 'IDR' ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($curr->description); ?> (<?= $curr->code; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                         <div class="col-md-6" id="exchange_rate_div" style="display:none;">
                             <div class="form-group">
                                <label>Kurs terhadap IDR</label>
                                <input type="text" name="exchange_rate" id="exchange_rate" class="form-control text-right" value="1.0000" placeholder="Contoh: 15000.50">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="has_ppn" name="has_ppn" value="1">
                        <label class="custom-control-label" for="has_ppn">Termasuk PPN 11%</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Detail</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>
<script>
$(document).ready(function() {
    const anExchangeRate = new AutoNumeric('#exchange_rate', {
        decimalCharacter : '.', digitGroupSeparator : ',', decimalPlaces: 4, minimumValue: '0'
    });

    function toggleKurs() {
        if ($('#currency_code').val() !== 'IDR') {
            $('#exchange_rate_div').show();
        } else {
            $('#exchange_rate_div').hide();
            anExchangeRate.set(1);
        }
    }
    toggleKurs();
    $('#currency_code').on('change', toggleKurs);
});
</script>