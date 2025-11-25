<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-plane-departure text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mir_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                <a href="<?= base_url('mir/approve_mir/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('Yakin ingin approve MIR ini? Setelah diapprove, data tidak bisa diedit.');">
                    <i class="fas fa-check-circle fa-sm"></i> Approve MIR
                </a>
                 <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail">
                    <i class="fas fa-edit fa-sm"></i> Edit Header
                 </button>
            <?php elseif ($header->status == 'Approved' && check_access('mir', 'edit')): ?>
                 <a href="<?= base_url('mir/unapprove_mir/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan approval MIR ini? Pastikan belum ada penerimaan barang.');">
                    <i class="fas fa-undo fa-sm"></i> Unapprove (Back to Draft)
                </a>
            <?php endif; ?>
            <a href="<?= base_url('mir/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF
            </a>
            <a href="<?= base_url('mir'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali ke Daftar
            </a>
        </div>
    </div>

     <?php // ... (Tambahkan div alert untuk flashdata success/error/warning) ... ?>

    <div class="card shadow mb-4">
         <div class="card-header py-3">
             <h6 class="m-0 font-weight-bold text-primary">Informasi Import Request</h6>
         </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="35%"><strong>Nomor MIR</strong></td><td>: <?= htmlspecialchars($header->mir_number); ?></td></tr>
                        <tr><td><strong>Supplier</strong></td><td>: <?= htmlspecialchars($header->nama_supplier ?? '-'); ?></td></tr>
                         <tr><td><strong>Requester</strong></td><td>: <?= htmlspecialchars($header->requester_name ?? '-'); ?></td></tr>
                    </table>
                </div>
                 <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                        <tr><td width="35%"><strong>Tanggal Request</strong></td><td>: <?= date('d M Y', strtotime($header->request_date)); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td>:
                             <?php $status_colors = ['Draft'=>'secondary', 'Approved'=>'primary', 'Partially Received'=>'warning', 'Completed'=>'success', 'Cancelled'=>'danger'];
                                   $color = $status_colors[$header->status] ?? 'dark'; ?>
                            <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($header->status); ?></span></td>
                        </tr>
                        <?php if($header->status == 'Approved' || $header->approved_by): ?>
                        <tr><td><strong>Approved By</strong></td><td>: <?= htmlspecialchars($header->approver_name ?? '-'); ?></td></tr>
                         <tr><td><strong>Approved At</strong></td><td>: <?= $header->approved_at ? date('d M Y H:i', strtotime($header->approved_at)) : '-'; ?></td></tr>
                        <?php endif; ?>
                         </table>
                </div>
            </div>
             <?php if($header->remarks): ?> <hr><p class="mb-0"><strong>Remarks:</strong><br> <?= nl2br(htmlspecialchars($header->remarks)); ?></p><?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Detail Item Request</h6>
            <?php // Tombol Tambah Item hanya jika Draft ?>
            <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addItemModal">
                <i class="fas fa-plus fa-sm"></i> Tambah Item
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                     <thead class="thead-light text-center">
                        <tr>
                            <th>No</th> <th>Kode Item</th> <th>Nama Item</th> <th>Tipe</th>
                            <th>Sales Contract</th> <th>Qty Request</th> <th>Remarks</th>
                            <th>Aksi</th> <?php // Kolom Aksi ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
                        <tr>
                             <td class="text-center"><?= $no++; ?></td>
                             <td><?= htmlspecialchars($d->item_code); ?></td>
                             <td><?= htmlspecialchars($d->item_name); ?></td>
                             <td class="text-center"><span class="badge badge-info"><?= ucfirst($d->item_type); ?></span></td>
                             <td><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
                             <td class="text-right"><?= number_format($d->qty, 2); ?></td>
                             <td><?= htmlspecialchars($d->remarks); ?></td>
                             <td class="text-center">
                                 <?php // Tombol Hapus hanya jika Draft ?>
                                <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                                <a href="<?= base_url('mir/delete_item/'.$d->mir_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus Item"> <i class="fas fa-trash"></i> </a>
                                <?php else: echo '-'; endif; ?>
                             </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada item detail.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
<div class="modal fade" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('mir/update_header/'.$header->id); ?>">
             <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header MIR</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group"><label>Nomor MIR</label><input type="text" class="form-control" value="<?= htmlspecialchars($header->mir_number); ?>" disabled></div>
                        <div class="col-md-6 form-group"><label>Tanggal Request <span class="text-danger">*</span></label><input type="date" name="request_date" class="form-control" value="<?= $header->request_date; ?>" required></div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group">
                            <label for="supplier_id">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-control select2-modal-edit" required style="width: 100%;">
                                <option value="">-- Pilih Supplier --</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier->id; ?>" <?= $header->supplier_id == $supplier->id ? 'selected' : ''; ?>><?= htmlspecialchars($supplier->nama_supplier); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group"><label>Requester</label><input type="text" class="form-control" value="<?= htmlspecialchars($header->requester_name); ?>" disabled></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group"><label>Tipe Pembayaran</label>
                            <select name="payment_type_code" class="form-control">
                                <option value="">-- Pilih Tipe --</option>
                                <?php foreach($payment_types as $pt): ?>
                                    <option value="<?= $pt->code; ?>" <?= $header->payment_type_code == $pt->code ? 'selected' : ''; ?>><?= htmlspecialchars($pt->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-6 form-group"><label>Mata Uang</label>
                            <select name="currency_code" class="form-control">
                                <option value="">-- Pilih Currency --</option>
                                 <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= $header->currency_code == $curr->code ? 'selected' : ''; ?>><?= htmlspecialchars($curr->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header->remarks); ?></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addItemForm" method="post" action="<?= base_url('mir/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                 <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Item ke MIR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label>Cari Item (Material, Mesin, Inv, Semi-Finished)</label>
                        <select id="item_search" name="item_id" class="form-control" required style="width: 100%;">
                            <option value="">-- Ketik Nama atau Kode Item --</option>
                        </select>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Quantity Request <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="qty" class="form-control" required min="0.01">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Sales Contract (Opsional)</label>
                            <select name="sales_contract_id" class="form-control select2-add-item" style="width: 100%;">
                                <option value="">-- Tanpa Sales Contract --</option>
                                <?php foreach($open_contracts as $sc): ?>
                                    <option value="<?= $sc->id; ?>"><?= htmlspecialchars($sc->sc_number . ' - ' . ($sc->customer_name ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="form-group">
                        <label>Remarks Item (Opsional)</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="btn btn-success btn-load"><i class="fas fa-plus mr-1"></i> Tambahkan Item</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>


<?php $this->load->view('templates/footer'); ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const currentStatus = '<?= $header->status; ?>';

    // Inisialisasi hanya jika status Draft
    if (currentStatus === 'Draft') {
        // Select2 untuk Supplier di modal Edit
        if ($('.select2-modal-edit').length) {
             $('.select2-modal-edit').select2({ dropdownParent: $('#editHeaderModalDetail') });
        }
        // Select2 untuk Item Search di modal Add
        if ($('#item_search').length) {
            $('#item_search').select2({
                placeholder: '-- Ketik Nama atau Kode Item --', minimumInputLength: 2,
                ajax: {
                    url: '<?= base_url("mir/search_items"); ?>', dataType: 'json', delay: 250,
                    data: function (params) { return { q: params.term }; },
                    processResults: function (data) { return { results: data.results }; },
                },
                dropdownParent: $('#addItemModal')
            });
        }
        // Select2 untuk Sales Contract di modal Add
        if ($('.select2-add-item').length) {
             $('.select2-add-item').select2({ placeholder: '-- Tanpa Sales Contract --', allowClear: true, dropdownParent: $('#addItemModal') });
        }
    } // End if Draft
});
</script>
<style>
/* Style Select2 di modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051 !important; }
.btn-xs { padding: .1rem .3rem; font-size: .7rem; line-height: 1; border-radius: .2rem; }
</style>