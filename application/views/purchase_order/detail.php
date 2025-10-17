<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->po_number); ?></span>
        </h3>
        <div>
             <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
             <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModal">
                 <i class="fas fa-edit fa-sm"></i> Edit Header
             </button>
             <?php endif; ?>

             <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): // Sesuaikan permission jika perlu ?>
             <a href="<?= base_url('purchase_order/submit_po/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" onclick="return confirm('Yakin ingin submit PO ini? Setelah disubmit, PO tidak bisa diedit lagi.');">
                <i class="fas fa-check-circle fa-sm"></i> Submit PO
             </a>
             <?php endif; ?>

             <a href="<?= base_url('purchase_order/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel fa-sm"></i> Excel
            </a>
             <a href="<?= base_url('purchase_order/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF
            </a>
            <a href="<?= base_url('purchase_order'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Supplier:</strong> <?= htmlspecialchars($header->nama_supplier); ?></p>
                    <p class="mb-1"><strong>Alamat:</strong> <?= htmlspecialchars($header->alamat); ?></p>
                    <p class="mb-0"><strong>Telepon:</strong> <?= htmlspecialchars($header->telepon); ?></p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-1"><strong>Tanggal PO:</strong> <?= date('d M Y', strtotime($header->po_date)); ?></p>
                    <p class="mb-1"><strong>Status:</strong> <span class="badge badge-info"><?= $header->status; ?></span></p>
                    <p class="mb-0"><strong>PPN 11%:</strong> <?= $header->has_ppn ? 'Ya' : 'Tidak'; ?></p>
                </div>
            </div>
             <hr>
             <div class="row">
                <div class="col-md-4"><strong>Pengiriman:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?></div>
                <div class="col-md-4"><strong>Pembayaran:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?></div>
                <div class="col-md-4">
                    <strong>Currency:</strong> <?= htmlspecialchars($header->currency_desc ?? $header->currency_code); ?>
                    <?php if($header->currency_code != 'IDR'): ?>
                        (Kurs: <?= number_format($header->exchange_rate, 4); ?>)
                    <?php endif; ?>
                </div>
            </div>
             <?php if($header->remarks): ?>
                 <hr>
                 <p class="mb-0"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?></p>
             <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Item Detail</h6>
            <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
            <button class="btn btn-success btn-sm shadow-sm" data-toggle="modal" data-target="#addItemModal">
                <i class="fas fa-plus fa-sm"></i> Tambah Item
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th style="width:5%">No</th>
                            <th style="width:15%">Sales Contract</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th style="width:10%">Qty</th>
                            <th style="width:15%">Harga (IDR)</th>
                            <th style="width:15%">Total (IDR)</th>
                            <th style="width:5%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): $no = 1; $grand_total_local = 0; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?><br><small class="text-muted"><?= htmlspecialchars($d->description ?? ''); ?></small></td>
                            <td class="text-right"><?= number_format($d->qty, 2); ?></td>
                            <td class="text-right"><?= number_format($d->local_price ?? 0, 2); ?></td>
                            <td class="text-right"><?= number_format($d->total_local ?? 0, 2); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('purchase_order', 'edit')): ?>
                                <a href="<?= base_url('purchase_order/delete_item/'.$d->po_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $grand_total_local += ($d->total_local ?? 0); endforeach; ?>
                        
                        <tr class="bg-light">
                            <td colspan="6" class="text-right font-weight-bold">Subtotal (IDR)</td>
                            <td class="text-right font-weight-bold"><?= number_format($grand_total_local, 2); ?></td>
                            <td></td>
                        </tr>
                        <?php if($header->has_ppn): $ppn_amount = $grand_total_local * 0.11; ?>
                        <tr class="bg-light">
                            <td colspan="6" class="text-right font-weight-bold">PPN 11%</td>
                            <td class="text-right font-weight-bold"><?= number_format($ppn_amount, 2); ?></td>
                            <td></td>
                        </tr>
                        <tr class="bg-light font-weight-bold" style="font-size: 1.1em;">
                            <td colspan="6" class="text-right">Grand Total (IDR)</td>
                            <td class="text-right"><?= number_format($grand_total_local + $ppn_amount, 2); ?></td>
                            <td></td>
                        </tr>
                        <?php endif; ?>

                        <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada item ditambahkan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= base_url('purchase_order/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Item ke PO</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Item (Ketik untuk mencari)</label>
                        <select name="item_id" id="item_search" class="form-control" required style="width: 100%;">
                            <option value="">-- Cari Kode / Nama Item --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alokasi Sales Contract (Opsional)</label>
                        <select name="sales_contract_id" class="form-control">
                            <option value="">-- Tanpa Kontrak --</option>
                            <?php foreach($open_contracts as $sc): ?>
                                <option value="<?= $sc->id; ?>">
                                    <?= htmlspecialchars($sc->sc_number . ' - ' . $sc->style_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Tambahan (Opsional)</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Qty</label>
                                <input type="number" step="any" name="qty" class="form-control text-right" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Harga Satuan (<?= htmlspecialchars($header->currency_code); ?>)</label>
                                <input type="text" name="base_price" class="form-control autonumeric text-right" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Simpan Item</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order/update_header/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header PO</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier); ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal PO</label>
                                <input type="date" name="po_date" class="form-control" value="<?= $header->po_date; ?>" required>
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
                                        <option value="<?= $mode->code; ?>" <?= $header->shipping_mode_code == $mode->code ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
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
                                        <option value="<?= $mode->code; ?>" <?= $header->payment_mode_code == $mode->code ? 'selected' : ''; ?>><?= htmlspecialchars($mode->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Currency</label>
                                <select name="currency_code" id="edit_currency_code" class="form-control">
                                     <?php foreach($currencies as $curr): ?>
                                        <option value="<?= $curr->code; ?>" <?= $header->currency_code == $curr->code ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($curr->description); ?> (<?= $curr->code; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                         <div class="col-md-6" id="edit_exchange_rate_div">
                             <div class="form-group">
                                <label>Kurs terhadap IDR</label>
                                <input type="text" name="exchange_rate" id="edit_exchange_rate" class="form-control autonumeric-4dec text-right" value="<?= number_format($header->exchange_rate, 4, '.', ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header->remarks); ?></textarea>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="edit_has_ppn" name="has_ppn" value="1" <?= $header->has_ppn ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="edit_has_ppn">Termasuk PPN 11%</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<script>
$(document).ready(function() {
    const anBasePrice = new AutoNumeric('.autonumeric', {
        decimalCharacter : '.', digitGroupSeparator : ',', decimalPlaces: 2, minimumValue: '0'
    });
    const anEditExchangeRate = new AutoNumeric('.autonumeric-4dec', {
        decimalCharacter : '.', digitGroupSeparator : ',', decimalPlaces: 4, minimumValue: '0'
    });

    function toggleEditKurs() {
        if ($('#edit_currency_code').val() !== 'IDR') {
            $('#edit_exchange_rate_div').show();
        } else {
            $('#edit_exchange_rate_div').hide();
            if (typeof anEditExchangeRate !== 'undefined') anEditExchangeRate.set(1);
        }
    }
    toggleEditKurs();
    $('#edit_currency_code').on('change', toggleEditKurs);

    $('#item_search').select2({
        placeholder: '-- Cari Kode / Nama Item --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("purchase_order/search_items"); ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        dropdownParent: $('#addItemModal')
    });
});
</script>

<style>
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0;}
.select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
.select2-dropdown { z-index: 1051; border: 1px solid #d1d3e2; }
.table-sm td, .table-sm th { padding: .5rem; }
</style>