<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-industry text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->wo_number); ?></span>
        </h3>
        <div>
             <?php if ($header->status == 'Planned' && check_access('work_order', 'edit')): ?>
             <a href="<?= base_url('work_order/confirm_wo/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" onclick="return confirm('Yakin ingin konfirmasi WO ini? Status akan menjadi In Progress.');">
                <i class="fas fa-play-circle fa-sm"></i> Confirm WO (Start)
             </a>
             <?php elseif ($header->status == 'In Progress' && check_access('work_order', 'edit')): ?>
             <a href="<?= base_url('work_order/unconfirm_wo/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan konfirmasi WO ini? Status akan kembali ke Planned.');">
                <i class="fas fa-undo fa-sm"></i> Unconfirm WO
             </a>
             <?php endif; ?>

             <a href="<?= base_url('work_order/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel fa-sm"></i> Excel
            </a>
             <a href="<?= base_url('work_order/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF
            </a>
            <a href="<?= base_url('work_order'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>No. WO:</strong> <?= htmlspecialchars($header->wo_number); ?></div>
                <div class="col-md-4"><strong>No. SC:</strong> <?= htmlspecialchars($header->sc_number); ?></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-<?= $header->status == 'Completed' ? 'success' : ($header->status == 'In Progress' ? 'warning' : 'info') ?>"><?= $header->status; ?></span></div>
             </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong>Style:</strong> <?= htmlspecialchars($header->fg_name . ' (' . $header->fg_code . ')'); ?></div>
                 <div class="col-md-4"><strong>Qty Rencana:</strong> <?= number_format($header->planned_qty); ?></div>
                 <div class="col-md-4"><strong>Pelanggan:</strong> <?= htmlspecialchars($header->nama_customer); ?></div>
             </div>
              <div class="row mt-2">
                 <div class="col-md-4"><strong>Tgl Mulai Rencana:</strong> <?= $header->planned_start_date ? date('d M Y', strtotime($header->planned_start_date)) : '-'; ?></div>
                 <div class="col-md-4"><strong>Tgl Selesai Rencana:</strong> <?= $header->planned_end_date ? date('d M Y', strtotime($header->planned_end_date)) : '-'; ?></div>
             </div>
              <hr>
              <div class="row mt-2 text-muted small">
                  <div class="col-md-6">Dibuat oleh: <?= htmlspecialchars($header->creator_name ?? '-'); ?> pada <?= $header->created_at ? date('d/m/Y H:i', strtotime($header->created_at)) : '-'; ?></div>
                  <div class="col-md-6 text-md-right">Dikonfirmasi oleh: <?= htmlspecialchars($header->confirmer_name ?? '-'); ?> pada <?= $header->confirmed_at ? date('d/m/Y H:i', strtotime($header->confirmed_at)) : '-'; ?></div>
              </div>
              <?php if($header->remarks): ?> <hr> <p class="mb-0"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?></p> <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-secondary">Referensi Komponen dari BOM</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.85em;">
                            <thead class="thead-light"> <tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Qty/Unit</th><th>Satuan</th></tr> </thead>
                            <tbody>
                                <?php if(!empty($bom_components)): foreach($bom_components as $bom): ?>
                                <tr>
                                    <td><?= htmlspecialchars($bom->item_code); ?></td> <td><?= htmlspecialchars($bom->item_name); ?></td>
                                    <td><?= ucfirst($bom->item_type); ?></td> <td class="text-right"><?= number_format($bom->quantity_required, 4); ?></td>
                                    <td><?= htmlspecialchars($bom->unit_measure ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; else: ?> <tr><td colspan="5" class="text-center text-muted py-3">BOM tidak ditemukan/aktif.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
             <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Rencana Penggunaan Material</h6>
                    <?php if (in_array($header->status, ['Planned']) && check_access('work_order', 'edit')): // Hanya bisa edit jika status Planned ?>
                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addMaterialModal"> <i class="fas fa-plus fa-sm"></i> Tambah Material </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light"> <tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Total Qty</th><th>Satuan</th><th>Aksi</th></tr> </thead>
                            <tbody>
                                <?php if(!empty($planned_materials)): foreach($planned_materials as $mat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mat->item_code); ?></td> <td><?= htmlspecialchars($mat->item_name); ?></td>
                                    <td><?= ucfirst($mat->item_type); ?></td> <td class="text-right"><?= number_format($mat->planned_qty, 4); ?></td>
                                    <td><?= htmlspecialchars($mat->unit_measure ?? '-'); ?></td>
                                    <td class="text-center">
                                         <?php if (in_array($header->status, ['Planned']) && check_access('work_order', 'edit')): ?>
                                        <a href="<?= base_url('work_order/delete_material/'.$mat->wo_header_id.'/'.$mat->id); ?>" class="btn btn-danger btn-xs btn-delete"> <i class="fas fa-trash"></i> </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?> <tr><td colspan="6" class="text-center text-muted py-3">Belum ada material.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($header->status == 'Planned'): ?>
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= base_url('work_order/add_material/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Material ke Rencana WO</h5>
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
                        <label>Total Qty Direncanakan</label>
                        <input type="number" step="any" name="planned_qty" class="form-control" required placeholder="0.0000">
                        <small class="form-text text-muted">Total kebutuhan komponen untuk memproduksi <?= number_format($header->planned_qty); ?> unit <?= htmlspecialchars($header->fg_name); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Simpan Material</button>
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
    if ('<?= $header->status; ?>' === 'Planned') {
        $('#component_search').select2({
            placeholder: '-- Cari Kode / Nama Komponen --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("work_order/search_components"); ?>',
                dataType: 'json', delay: 250, data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
            },
            dropdownParent: $('#addMaterialModal')
        });
    }
});
</script>
<style>
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; }
.table-sm td, .table-sm th { padding: .4rem; }
</style>