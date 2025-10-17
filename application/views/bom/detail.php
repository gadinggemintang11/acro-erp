<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <?php if ($header->status == 'Draft' && check_access('bom', 'edit')): ?>
             <a href="<?= base_url('bom/confirm_bom/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('Yakin ingin mengaktifkan BOM ini? Setelah aktif, BOM akan digunakan di Work Order.');">
                <i class="fas fa-check-circle fa-sm"></i> Aktifkan BOM
             </a>
             <?php endif; ?>
             <a href="<?= base_url('bom/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> Export PDF
            </a>
            <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>Style:</strong> <?= htmlspecialchars($header->fg_name); ?></div>
                <div class="col-md-4"><strong>Kode:</strong> <?= htmlspecialchars($header->fg_code); ?></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-info"><?= $header->status; ?></span></div>
             </div>
             </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Komponen Material</h6>
            <?php if ($header->status == 'Draft' && check_access('bom', 'edit')): ?>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addComponentModal">
                <i class="fas fa-plus fa-sm"></i> Tambah Komponen
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Tipe</th>
                            <th>Kode Komponen</th>
                            <th>Nama Komponen</th>
                            <th>Qty Dibutuhkan</th>
                            <th>Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center"><?= ucfirst($d->item_type); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td class="text-right"><?= number_format($d->quantity_required, 4); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('bom', 'edit')): ?>
                                <a href="<?= base_url('bom/delete_item/'.$d->bom_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada komponen ditambahkan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($header->status == 'Draft'): ?>
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= base_url('bom/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Komponen ke BOM</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Komponen (Material / Semifinished)</label>
                        <select name="component_id" id="component_search" class="form-control" required style="width: 100%;">
                             <option value="">-- Cari Kode / Nama Komponen --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Qty Dibutuhkan (Per 1 Unit FG)</label>
                        <input type="number" step="any" name="quantity_required" class="form-control" required placeholder="Contoh: 0.75">
                         <small class="form-text text-muted">Masukkan jumlah komponen yang dibutuhkan untuk membuat 1 unit/pair <?= htmlspecialchars($header->fg_name); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Simpan Komponen</button>
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
    // Hanya inisialisasi jika status Draft
    if ('<?= $header->status; ?>' === 'Draft') {
        $('#component_search').select2({
            placeholder: '-- Cari Kode / Nama Komponen --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("bom/search_components"); ?>',
                dataType: 'json', delay: 250, data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
            },
            dropdownParent: $('#addComponentModal')
        });
    }
});
</script>
<style>
/* Style Select2 di dalam modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; }
</style>