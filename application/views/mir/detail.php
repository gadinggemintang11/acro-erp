<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mir_number); ?></span>
        </h3>
        <div>
            <a href="<?= base_url('mir/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> Export PDF
            </a>
            <a href="<?= base_url('mir'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>Tanggal:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong>Supplier:</strong> <?= htmlspecialchars($header->nama_supplier ?? 'N/A'); ?></div>
                <div class="col-md-4"><strong>Tipe Bayar:</strong> <?= htmlspecialchars($header->payment_type_desc ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Item Detail</h6>
            <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addItemModal">
                <i class="fas fa-plus fa-sm"></i> Tambah Item
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
             <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Sales Contract</th>
                            <th>Kode Item</th>
                            <th>Nama Item</th>
                            <th>Qty</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td class="text-right"><?= number_format($d->qty, 2); ?></td>
                            <td><?= htmlspecialchars($d->remarks); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                                <a href="<?= base_url('mir/delete_item/'.$d->mir_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada item ditambahkan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= base_url('mir/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Item ke MIR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Item (Ketik untuk mencari)</label>
                        <select name="item_id" id="item_search" class="form-control" required style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label>Alokasi Sales Contract (Opsional)</label>
                        <select name="sales_contract_id" class="form-control">
                            <option value="">-- Tanpa Kontrak --</option>
                            <?php foreach($open_contracts as $sc): ?>
                                <option value="<?= $sc->id; ?>"><?= htmlspecialchars($sc->sc_number . ' - ' . $sc->style_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Qty</label>
                        <input type="number" step="any" name="qty" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan Item (Opsional)</label>
                        <input type="text" name="remarks" class="form-control">
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

<?php $this->load->view('templates/footer'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#item_search').select2({
        placeholder: '-- Cari Kode / Nama Item --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("mir/search_items"); ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
        },
        dropdownParent: $('#addItemModal')
    });
});
</script>
<style>
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; }
</style>