<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <!-- HEADER -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-tasks text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->production_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/confirm/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('Yakin ingin konfirmasi proses produksi ini? Status akan menjadi In Progress dan tidak bisa diubah lagi.');">
                    <i class="fas fa-check-circle fa-sm"></i> Konfirmasi Proses
                </a>
            <?php elseif ($header->status == 'In Progress' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/complete_production/'.$header->id); ?>" class="btn btn-success btn-sm mr-1" onclick="return confirm('Yakin ingin menyelesaikan proses produksi ini? Pastikan semua output sudah final.');">
                    <i class="fas fa-flag-checkered fa-sm"></i> Selesaikan Produksi
                </a>
                <a href="<?= base_url('production_process/unconfirm/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan konfirmasi?');">
                    <i class="fas fa-undo fa-sm"></i> Batal Konfirmasi
                </a>
            <?php endif; ?>

            <a href="<?= base_url('production_process/export_preparation_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF (Preparation)
            </a>
            <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs nav-fill mb-4">
        <li class="nav-item"><a class="nav-link active font-weight-bold" href="#">1. Preparation</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/assembling/'.$header->id); ?>">2. Assembling</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/finishing/'.$header->id); ?>">3. Finishing</a></li>
    </ul>

    <!-- ALERT -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- INFO PRODUKSI -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>No. Produksi:</strong> <?= htmlspecialchars($header->production_number); ?></div>
                <div class="col-md-4"><strong>No. WO:</strong> <?= htmlspecialchars($header->wo_number); ?></div>
                <div class="col-md-4"><strong>Status:</strong> 
                    <span class="badge badge-<?= ($header->status == 'Draft' ? 'secondary' : ($header->status == 'Completed' ? 'success' : 'primary')); ?>">
                        <?= $header->status; ?>
                    </span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4"><strong>Barang Jadi:</strong> <?= htmlspecialchars($header->fg_name); ?></div>
                <div class="col-md-4"><strong>Tanggal Produksi:</strong> <?= date('d M Y', strtotime($header->production_date)); ?></div>
                <div class="col-md-4"><strong>Qty Rencana:</strong> <?= number_format($header->planned_qty, 2); ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ================================= LEFT SIDE ================================= -->
        <div class="col-lg-6">

            <!-- INPUT MATERIAL -->
            <?php if ($header->status == 'Draft'): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white py-3"><h6 class="m-0 font-weight-bold">Input Material dari Gudang (Batch)</h6></div>
                <div class="card-body">
                    <form id="formInputMaterial" method="post" action="<?= base_url('production_process/save_preparation_material_usage/'.$header->id); ?>">
                        <input type="hidden" name="item_id" id="input_item_id">
                        <input type="hidden" name="item_type" id="input_item_type">
                        <div class="form-group">
                            <label>Pilih Material (Batch FIFO)</label>
                            <select name="batch_id" id="input_batch_selector" class="form-control select2-wip" required>
                                <option value="">-- Pilih Batch Material --</option>
                                <?php foreach($available_batches as $batch): ?>
                                <option value="<?= $batch->batch_id; ?>" 
                                        data-itemid="<?= $batch->item_id; ?>"
                                        data-itemtype="<?= $batch->item_type; ?>"
                                        data-maxqty="<?= $batch->remaining_qty; ?>"
                                        data-cost="<?= $batch->cost_from_batch; ?>">
                                    <?= htmlspecialchars($batch->item_name.' ('.$batch->item_code.') | Batch: '.($batch->grn_number ?? 'WIP').' | Sisa: '.number_format($batch->remaining_qty, 4).' '.$batch->item_unit); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Qty Digunakan</label>
                                <input type="number" step="any" name="qty_used" id="input_qty_used" class="form-control" required min="0.00001">
                                <small>Sisa: <span id="input_max_qty">0</span></small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Catatan</label>
                                <input type="text" name="notes" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Input</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- DAFTAR INPUT -->
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Input Preparation</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 text-center">
                            <thead class="thead-light"><tr><th>Item</th><th>Tipe</th><th>Qty</th><th>Catatan</th><th>Sumber</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php if(empty($preparation_inputs)): ?>
                                <tr><td colspan="6" class="text-muted">Belum ada input</td></tr>
                                <?php else: foreach($preparation_inputs as $in): ?>
                                <tr>
                                    <td><?= htmlspecialchars($in->item_name); ?></td>
                                    <td><span class="badge badge-secondary"><?= $in->item_type; ?></span></td>
                                    <td><?= number_format($in->qty_used, 4); ?></td>
                                    <td><?= htmlspecialchars($in->notes ?? '-'); ?></td>
                                    <td><?= $in->stock_on_hand_id ? 'Gudang' : 'Output Stage'; ?></td>
                                    <td>
                                        <?php if ($header->status == 'Draft'): ?>
                                            <a href="<?= base_url('production_process/delete_material_usage/'.$header->id.'/'.$in->id.'?stage=preparation'); ?>" class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></a>
                                        <?php else: echo '-'; endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================= RIGHT SIDE ================================= -->
        <div class="col-lg-6">

            <!-- OUTPUT HASIL -->
            <?php if ($header->status == 'Draft'): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white py-3"><h6 class="m-0 font-weight-bold">Output Hasil Jadi (Semi-Finished)</h6></div>
                <div class="card-body">
                    <form method="post" action="<?= base_url('production_process/save_preparation_output/'.$header->id); ?>">
                        <div class="form-group">
                            <label>Pilih Item Semi-Finished</label>
                            <select name="item_id" class="form-control select2-ajax-semifinished" required>
                                <option value="">-- Cari Semi-Finished --</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Qty Dihasilkan</label><input type="number" step="any" name="qty" class="form-control" required></div>
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Output</button>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark py-3"><h6 class="m-0 font-weight-bold">Output Scrap</h6></div>
                <div class="card-body">
                    <form method="post" action="<?= base_url('production_process/save_preparation_output/'.$header->id); ?>">
                        <div class="form-group"><label>Pilih Item Scrap</label><select name="item_id" class="form-control select2-ajax-scrap" required><option value="">-- Cari Scrap --</option></select></div>
                        <div class="form-group"><label>Qty Scrap</label><input type="number" step="any" name="qty" class="form-control" required></div>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Scrap</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- DAFTAR OUTPUT -->
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Output Preparation</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 text-center">
                            <thead class="thead-light"><tr><th>Item</th><th>Tipe</th><th>Qty</th><th>QC Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php if(empty($preparation_outputs)): ?>
                                <tr><td colspan="5" class="text-muted">Belum ada output</td></tr>
                                <?php else: foreach($preparation_outputs as $out): ?>
                                <tr>
                                    <td><?= htmlspecialchars($out->item_name); ?></td>
                                    <td><span class="badge badge-<?= $out->item_type == 'scrap' ? 'warning' : 'success'; ?>"><?= ucfirst($out->item_type); ?></span></td>
                                    <td><?= number_format($out->qty_produced, 4); ?></td>
                                    <td>
                                        <?php if($out->qc_checked_by): ?>
                                            <span class="badge badge-success"
                                                  title="Checked by <?= htmlspecialchars(isset($out->qc_name) ? $out->qc_name : ($out->qc_checked_by ?? 'QC')); ?> at <?= htmlspecialchars($out->qc_checked_at ?? '-'); ?>">
                                                Lolos QC
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($header->status=='Draft'): ?>
                                            <?php if(!$out->qc_checked_by): ?>
                                                <a href="<?= base_url('production_process/confirm_qc_preparation/'.$header->id.'/'.$out->id); ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i></a>
                                            <?php else: ?>
                                                <a href="<?= base_url('production_process/unconfirm_qc_preparation/'.$header->id.'/'.$out->id); ?>" class="btn btn-warning btn-xs"><i class="fas fa-undo"></i></a>
                                            <?php endif; ?>
                                            <a href="<?= base_url('production_process/delete_preparation_output/'.$header->id.'/'.$out->id); ?>" class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></a>
                                        <?php else: echo '-'; endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function(){
    $('.select2-wip').select2({theme:'bootstrap4',width:'100%'});
    $('.select2-ajax-semifinished').select2({theme:'bootstrap4',width:'100%',minimumInputLength:2,
        ajax:{url:'<?= base_url("production_process/search_semifinished"); ?>',dataType:'json',delay:250,
        data:params=>({q:params.term}),processResults:data=>({results:data.results})}});
    $('.select2-ajax-scrap').select2({theme:'bootstrap4',width:'100%',minimumInputLength:2,
        ajax:{url:'<?= base_url("production_process/search_scrap"); ?>',dataType:'json',delay:250,
        data:params=>({q:params.term}),processResults:data=>({results:data.results})}});

    $('#input_batch_selector').on('change',function(){
        let sel=$(this).find(':selected');
        let max=parseFloat(sel.data('maxqty')||0);
        $('#input_qty_used').attr('max',max).val('');
        $('#input_max_qty').text(max.toFixed(4));
        let itemId = sel.data('itemid')||'';
        let itemType = sel.data('itemtype')||'';
        $('#input_item_id').val(itemId);
        $('#input_item_type').val(itemType);
        console.log('Selected Item ID:', itemId);
        console.log('Selected Item Type:', itemType);
    });

    $('body').on('click','.btn-delete',function(e){
        e.preventDefault();let u=$(this).attr('href');
        Swal.fire({title:'Hapus data?',text:'Data akan dihapus permanen.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, hapus!'})
        .then(r=>{if(r.isConfirmed)window.location.href=u;});
    });
});
</script>
