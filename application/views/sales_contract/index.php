<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0"><i class="fas fa-file-signature text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('sales_contract', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat SC Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. Kontrak</th>
                            <th>Pelanggan</th>
                            <th>Style</th>
                            <th>Tanggal</th>
                            <th>Qty</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($contracts)): foreach($contracts as $sc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($sc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->nama_customer); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->style_name); ?></td>
                            <td><?= date('d M Y', strtotime($sc->contract_date)); ?></td>
                            <td class="text-right"><?= number_format($sc->po_qty); ?></td>
                            <td class="text-right"><?= htmlspecialchars($sc->currency_code) . ' ' . number_format($sc->total_price, 2); ?></td>
                            <td><span class="badge badge-info"><?= $sc->status; ?></span></td>
                            <td>
                                <?php if (check_access('sales_contract', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $sc->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (check_access('sales_contract', 'delete')): ?>
                                <a href="<?= base_url('sales_contract/delete/'.$sc->id); ?>" class="btn btn-sm btn-danger btn-delete" data-message="Yakin ingin membatalkan Sales Contract ini?">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $sc->id; ?>" tabindex="-1" aria-hidden="true">
                            <?php $sc_detail = $this->Sc_model->get_by_id($sc->id); // Ambil detail lengkap untuk form ?>
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <form method="post" action="<?= base_url('sales_contract/update/'.$sc->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Sales Contract</h5>
                                            <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data Sales Contract</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('sales_contract/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Sales Contract Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Pelanggan</label>
                            <select name="customer_id" class="form-control" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= htmlspecialchars($c->nama_customer); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Style (Finished Good)</label>
                            <select name="fg_id" class="form-control" required>
                                <option value="">-- Pilih Style --</option>
                                <?php foreach($styles as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= htmlspecialchars($s->nama_barang . ' (' . $s->kode_barang . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 form-group">
                            <label>Tanggal Kontrak</label>
                            <input type="date" name="contract_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>LCHD Date</label>
                            <input type="date" name="lchd_date" class="form-control">
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-3 form-group">
                             <label>PO Qty</label>
                            <input type="number" name="po_qty" id="add_po_qty" class="form-control text-right" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Currency</label>
                            <select name="currency_code" class="form-control">
                                 <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>"><?= $curr->code; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-3 form-group">
                             <label>Harga Satuan</label>
                            <input type="text" name="unit_price" id="add_unit_price" class="form-control autonumeric text-right" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Total Price</label>
                            <input type="text" id="add_total_price" class="form-control autonumeric text-right" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Destination</label>
                        <input type="text" name="destination" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>
<script>
$(document).ready(function() {
    const anPrice = new AutoNumeric('#add_unit_price', { decimalPlaces: 2 });
    const anTotal = new AutoNumeric('#add_total_price', { decimalPlaces: 2 });

    function calculateTotal() {
        const qty = parseFloat($('#add_po_qty').val()) || 0;
        const price = anPrice.getNumber() || 0;
        anTotal.set(qty * price);
    }

    $('#add_po_qty, #add_unit_price').on('keyup change', calculateTotal);
});
</script>