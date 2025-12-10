<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($title ?? 'Proses Produksi - Finishing'); ?></title>

<!-- Dependencies -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
.select2-container { width: 100% !important; z-index: 1050; }
.select2-dropdown { z-index: 1051 !important; }
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.5em + .75rem + 2px) !important;
    padding: .375rem .75rem !important;
    line-height: 1.5;
    border-radius: .25rem;
}
.btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
.table th, .table td { vertical-align: middle; padding: 0.5rem; }
.size-row input { margin-right: 4px; }
</style>
</head>

<body id="page-top">
<div class="container-fluid">

    <!-- HEADER -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-tasks text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->production_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/confirm/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('Yakin ingin konfirmasi proses ini?');">
                    <i class="fas fa-check-circle fa-sm"></i> Konfirmasi Proses
                </a>
            <?php elseif ($header->status == 'In Progress' && check_access('production_process', 'edit')): ?>
                <a href="<?= base_url('production_process/complete_production/'.$header->id); ?>" class="btn btn-success btn-sm mr-1" onclick="return confirm('Yakin ingin menyelesaikan proses ini? Pastikan output FG sudah tercatat.');">
                    <i class="fas fa-flag-checkered fa-sm"></i> Selesaikan Produksi
                </a>
                <a href="<?= base_url('production_process/unconfirm/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Batalkan konfirmasi proses ini?');">
                    <i class="fas fa-undo fa-sm"></i> Batal Konfirmasi
                </a>
            <?php endif; ?>
            <a href="<?= base_url('production_process/export_finishing_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> PDF (Finishing)
            </a>
            <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali</a>
        </div>
    </div>

    <!-- TAB NAVIGATION -->
    <ul class="nav nav-tabs nav-fill mb-4">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/preparation/'.$header->id); ?>">1. Preparation</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/assembling/'.$header->id); ?>">2. Assembling</a></li>
        <li class="nav-item"><a class="nav-link active font-weight-bold" href="#">3. Finishing</a></li>
    </ul>

    <!-- FLASH MESSAGES -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <!-- INFO HEADER -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>No. Produksi:</strong> <?= htmlspecialchars($header->production_number); ?></div>
                <div class="col-md-4"><strong>No. WO:</strong> <?= htmlspecialchars($header->wo_number); ?></div>
                <div class="col-md-4"><strong>Status:</strong>
                    <span class="badge badge-<?= $header->status == 'Draft' ? 'warning' : ($header->status == 'Completed' ? 'success' : 'primary'); ?>"><?= $header->status; ?></span>
                </div>
            </div>
        </div>
    </div>

<div class="row">

<!-- ================= LEFT SIDE ================= -->
<div class="col-lg-6">

    <!-- INPUT DARI ASSEMBLING -->
    <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white py-3"><h6 class="m-0 font-weight-bold">Input dari Assembling (Hasil QC)</h6></div>
        <div class="card-body">
            <form id="form-prev-input" method="post" action="<?= base_url('production_process/save_finishing_assembling_input/'.$header->id); ?>">
                <div class="form-group">
                    <label for="prev_output_selector">Pilih Hasil Assembling</label>
                    <select name="source_output_id" id="prev_output_selector" class="form-control select2-wip" required>
                        <option value="">-- Pilih Output Assembling --</option>
                        <?php if(!empty($available_previous_outputs)): foreach($available_previous_outputs as $out): if((float)$out->remaining_qty > 0): ?>
                        <option value="<?= htmlspecialchars($out->output_id); ?>"
                                data-maxqty="<?= htmlspecialchars($out->remaining_qty); ?>"
                                data-itemid="<?= htmlspecialchars($out->item_id); ?>"
                                data-itemtype="<?= htmlspecialchars($out->item_type); ?>">
                            <?= htmlspecialchars($out->item_name.' - Sisa: '.number_format($out->remaining_qty,4)); ?>
                        </option>
                        <?php endif; endforeach; else: ?><option disabled>-- Tidak ada output tersedia --</option><?php endif; ?>
                    </select>
                </div>

                <!-- === NEW: Hidden item_id & item_type wajib dikirim ke controller === -->
                <input type="hidden" name="item_id" id="prev_item_id" value="">
                <input type="hidden" name="item_type" id="prev_item_type" value="">

                <div class="row">
                    <div class="col-md-6">
                        <label for="prev_qty_used">Qty Digunakan</label>
                        <input type="text" name="qty_used" id="prev_qty_used" class="form-control" required inputmode="decimal" pattern="[0-9.,]+" placeholder="Misal: 2 atau 1.5 atau 1,5">
                        <small>Maks: <span id="prev_max_qty">0.0000</span></small>
                    </div>
                    <div class="col-md-6">
                        <label for="prev_notes">Catatan</label>
                        <input type="text" name="notes" id="prev_notes" class="form-control">
                    </div>
                </div>
                <button type="submit" id="btn-prev-submit" class="btn btn-info btn-sm mt-2"><i class="fas fa-plus mr-1"></i> Tambah Input</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- INPUT MATERIAL TAMBAHAN -->
    <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white py-3"><h6 class="m-0 font-weight-bold">Input Material Tambahan</h6></div>
        <div class="card-body">
            <form id="form-add-input" method="post" action="<?= base_url('production_process/save_finishing_additional_input/'.$header->id); ?>">
                <input type="hidden" name="item_id" id="add_item_id">
                <input type="hidden" name="item_type" id="add_item_type">
                <div class="form-group">
                    <label for="add_batch_selector">Pilih Batch Material</label>
                    <select name="batch_id" id="add_batch_selector" class="form-control select2-wip" required>
                        <option value="">-- Pilih Batch --</option>
                        <?php if(!empty($available_batches)): foreach($available_batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch->batch_id); ?>" 
                                data-itemid="<?= htmlspecialchars($batch->item_id); ?>"
                                data-itemtype="<?= htmlspecialchars($batch->item_type); ?>"
                                data-maxqty="<?= htmlspecialchars($batch->remaining_qty); ?>"
                                data-cost="<?= htmlspecialchars($batch->cost_from_batch ?? 0); ?>">
                            <?= htmlspecialchars($batch->item_name.' ('.$batch->item_code.')'); ?>
                            | Batch: <?= htmlspecialchars($batch->grn_number ?? 'WIP'); ?>
                            | Sisa: <?= number_format($batch->remaining_qty,4).' '.$batch->item_unit; ?>
                        </option>
                        <?php endforeach; else: ?><option disabled>-- Tidak ada batch --</option><?php endif; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6"><label for="add_qty_used">Qty Digunakan</label><input type="text" step="any" name="qty_used" id="add_qty_used" class="form-control" required inputmode="decimal" pattern="[0-9.,]+"><small>Sisa: <span id="add_max_qty">0.0000</span></small></div>
                    <div class="col-md-6"><label for="add_notes">Catatan</label><input type="text" name="notes" id="add_notes" class="form-control"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-plus mr-1"></i> Tambah Input</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- DAFTAR INPUT -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Input Finishing</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 text-center">
                    <thead class="thead-light"><tr><th>Item</th><th>Tipe</th><th>Qty</th><th>Sumber</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($current_inputs)): ?><tr><td colspan="5" class="text-muted">Belum ada input</td></tr>
                        <?php else: foreach($current_inputs as $in): ?>
                        <tr><td><?= htmlspecialchars($in->item_name); ?></td><td><?= htmlspecialchars($in->item_type); ?></td><td><?= number_format($in->qty_used,4); ?></td><td><?= $in->stock_on_hand_id ? 'Gudang' : 'Assembling'; ?></td>
                            <td><?php if($header->status=='Draft'): ?><a href="<?= base_url('production_process/delete_finishing_input/'.$header->id.'/'.$in->id); ?>" class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></a><?php else: echo '-'; endif; ?></td></tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ================= RIGHT SIDE ================= -->
<div class="col-lg-6">

    <!-- OUTPUT FINISHED GOODS -->
    <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white py-3"><h6 class="m-0 font-weight-bold">Output Finished Goods (Sepatu + Size)</h6></div>
        <div class="card-body">
            <form method="post" action="<?= base_url('production_process/save_finishing_output/'.$header->id); ?>">
                <div class="form-group"><label for="output_fg">Pilih Barang Jadi</label>
                    <select name="item_id" id="output_fg" class="form-control select2-ajax-fg" required><option value="">-- Cari Barang Jadi --</option></select>
                </div>
                <div class="form-group"><label for="output_qty">Total Qty Dihasilkan</label><input type="text" step="any" name="qty" id="output_qty" class="form-control" required inputmode="decimal" pattern="[0-9.,]+"></div>

                <div class="form-group">
                    <label>Ukuran & Qty per Size</label>
                    <div id="size-container">
                        <div class="input-group mb-2 size-row">
                            <input type="text" name="size[]" class="form-control col-4" placeholder="Size (misal 39)" required>
                            <input type="text" step="any" name="size_qty[]" class="form-control col-4" placeholder="Qty" required inputmode="decimal" pattern="[0-9.,]+">
                            <div class="input-group-append"><button type="button" class="btn btn-danger remove-size"><i class="fas fa-minus"></i></button></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="add-size"><i class="fas fa-plus"></i> Tambah Size</button>
                </div>

                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah FG</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- OUTPUT SCRAP -->
    <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark py-3"><h6 class="m-0 font-weight-bold">Output Scrap</h6></div>
        <div class="card-body">
            <form method="post" action="<?= base_url('production_process/save_finishing_output/'.$header->id); ?>">
                <div class="form-group"><label for="output_scrap">Pilih Item Scrap</label><select name="item_id" id="output_scrap" class="form-control select2-ajax-scrap" required><option value="">-- Cari Scrap --</option></select></div>
                <div class="form-group"><label for="scrap_qty">Qty Scrap</label><input type="text" step="any" name="qty" id="scrap_qty" class="form-control" required inputmode="decimal" pattern="[0-9.,]+"></div>
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Scrap</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- DAFTAR OUTPUT -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Output Finishing</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 text-center">
                    <thead class="thead-light"><tr><th>Item</th><th>Tipe</th><th>Qty</th><th>QC</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($current_outputs)): ?><tr><td colspan="5" class="text-muted">Belum ada output</td></tr>
                        <?php else: foreach($current_outputs as $out): ?>
                        <tr>
                            <td class="text-left">
                                <?php $encoded = base64_encode($out->size_breakdown ?? ''); ?>
                                <a href="javascript:void(0)" 
                                   class="text-primary font-weight-bold show-size-detail" 
                                   data-sizes="<?= $encoded; ?>" 
                                   data-item="<?= htmlspecialchars(($out->item_name ?? 'Tidak diketahui').' ('.$out->item_code.')'); ?>">
                                   <?= htmlspecialchars(($out->item_name ?? 'Tidak diketahui').' ('.$out->item_code.')'); ?>
                                </a>
                            </td>
                            <td><span class="badge badge-<?= $out->item_type=='scrap'?'warning text-dark':'success'; ?>"><?= ucfirst($out->item_type); ?></span></td>
                            <td><?= number_format($out->qty_produced,4); ?></td>
                            <td><?= $out->qc_checked_by ? '<span class="badge badge-success">QC Pass</span>' : '<span class="badge badge-secondary">Pending</span>'; ?></td>
                            <td>
                                <?php if($header->status=='Draft'): ?>
                                    <?php if(!$out->qc_checked_by): ?><a href="<?= base_url('production_process/confirm_qc_finishing/'.$header->id.'/'.$out->id); ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i></a>
                                    <?php else: ?><a href="<?= base_url('production_process/unconfirm_qc_finishing/'.$header->id.'/'.$out->id); ?>" class="btn btn-warning btn-xs"><i class="fas fa-undo"></i></a><?php endif; ?>
                                    <a href="<?= base_url('production_process/delete_finishing_output/'.$header->id.'/'.$out->id); ?>" class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></a>
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

<!-- ================= SCRIPTS ================= -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function(){
    // Init Select2
    $('.select2-wip').select2({theme:'bootstrap4', width: '100%'});
    $('.select2-ajax-fg').select2({
        theme:'bootstrap4',width:'100%',minimumInputLength:1,
        ajax:{url:'<?= base_url("production_process/search_finished_goods"); ?>',dataType:'json',delay:250,
        data:params=>({q:params.term}),
        processResults:data=>({results:$.map(data.results,i=>{i.id=i.id+'|finished_goods';return i;})})}
    });
    $('.select2-ajax-scrap').select2({
        theme:'bootstrap4',width:'100%',minimumInputLength:1,
        ajax:{url:'<?= base_url("production_process/search_scrap"); ?>',dataType:'json',delay:250,
        data:params=>({q:params.term}),
        processResults:data=>({results:$.map(data.results,i=>{i.id=i.id+'|scrap';return i;})})}
    });

    // Tambah Size Dynamic Row
    $('#add-size').on('click',()=>$('#size-container').append(`
    <div class="input-group mb-2 size-row">
        <input type="text" name="size[]" class="form-control col-4" placeholder="Size (misal 40)" required>
        <input type="text" step="any" name="size_qty[]" class="form-control col-4" placeholder="Qty" required inputmode="decimal" pattern="[0-9.,]+">
        <div class="input-group-append"><button type="button" class="btn btn-danger remove-size"><i class="fas fa-minus"></i></button></div>
    </div>`));
    $('body').on('click','.remove-size',function(){$(this).closest('.size-row').remove();});

    // Delete Confirmation
    $('body').on('click','.btn-delete',function(e){e.preventDefault();let u=$(this).attr('href');
        Swal.fire({title:'Hapus data?',text:'Data akan dihapus permanen.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, hapus!'})
        .then(r=>{if(r.isConfirmed)window.location.href=u;});
    });

    // Show Detail Size Breakdown
    $('body').on('click', '.show-size-detail', function() {
        const itemName = $(this).data('item');
        const b64 = $(this).data('sizes');
        if (!b64) {
            Swal.fire({icon:'info',title:'Tidak ada data size',text:'Item ini belum memiliki detail ukuran.',confirmButtonText:'OK'});
            return;
        }
        let decoded = '';
        try { decoded = atob(b64); } catch(e){ decoded=''; }
        if(!decoded){
            Swal.fire({icon:'info',title:'Tidak ada data size',text:'Format data size tidak valid.',confirmButtonText:'OK'});
            return;
        }
        let sizes;
        try { sizes = JSON.parse(decoded); } catch(e){ sizes=[]; }
        if(!Array.isArray(sizes) || sizes.length===0){
            Swal.fire({icon:'info',title:'Tidak ada data size',text:'Belum ada breakdown ukuran untuk item ini.',confirmButtonText:'OK'});
            return;
        }
        let html='<table class="table table-sm table-bordered text-center mb-0"><thead><tr><th>Size</th><th>Qty</th></tr></thead><tbody>';
        sizes.forEach(s=>{html+=`<tr><td>${s.size}</td><td>${parseFloat(s.qty).toFixed(2)}</td></tr>`;});
        html+='</tbody></table>';
        Swal.fire({title:`Detail Size<br><small>${itemName}</small>`,html:html,width:420,confirmButtonText:'Tutup'});
    });

    // Update max qty display & set hidden item_id/item_type when selecting previous output
    $('#prev_output_selector').on('change', function(){
        let sel = $(this).find(':selected');
        let max = parseFloat(sel.data('maxqty')) || 0;
        $('#prev_max_qty').text(max.toFixed(4));

        // fill hidden fields for controller
        $('#prev_item_id').val(sel.data('itemid') || '');
        $('#prev_item_type').val(sel.data('itemtype') || '');
    });

    // Update add batch max qty and set hidden item_id/item_type
    $('#add_batch_selector').on('change', function(){
        let sel = $(this).find(':selected');
        let max = parseFloat(sel.data('maxqty'))||0;
        $('#add_max_qty').text(max.toFixed(4));
        $('#add_item_id').val(sel.data('itemid')||'');
        $('#add_item_type').val(sel.data('itemtype')||'');
    });

    // Utility: normalize numeric string (convert comma to dot and trim)
    function normalizeNumberString(s){
        if (s === null || s === undefined) return '';
        return String(s).replace(/\s+/g,'').replace(/,/g,'.');
    }

    // Validate prev-input form before submit
    $('#form-prev-input').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        const selected = $('#prev_output_selector').find(':selected');
        if (!selected || !selected.val()) {
            Swal.fire({icon:'warning',title:'Pilih Hasil Assembling',text:'Silakan pilih output assembling terlebih dahulu.'});
            return;
        }

        // ensure hidden item fields are set (safety)
        if (!$('#prev_item_id').val() || !$('#prev_item_type').val()) {
            // try to populate from option data (in case user uses keyboard)
            $('#prev_item_id').val(selected.data('itemid')||'');
            $('#prev_item_type').val(selected.data('itemtype')||'');
        }

        let max = parseFloat(selected.data('maxqty')) || 0;
        let qtyRaw = normalizeNumberString($('#prev_qty_used').val());
        let qty = parseFloat(qtyRaw);

        if (isNaN(qty) || qty <= 0) {
            Swal.fire({icon:'error',title:'Qty tidak valid',text:'Masukkan jumlah (angka) > 0.'});
            return;
        }
        if (qty > max + 0.00001) {
            Swal.fire({icon:'warning',title:'Qty Melebihi Sisa',text:'Qty yang dimasukkan melebihi sisa Assembling (' + max.toFixed(4) + ').'});
            return;
        }

        // replace the input value with normalized dot-format to be safe for server
        $('#prev_qty_used').val(qty);

        // disable submit to prevent double-submit
        $('#btn-prev-submit').prop('disabled', true);
        $form.off('submit'); // remove this handler to allow normal submit
        $form.submit(); // submit normally
    });

    // Normalize numeric inputs on the add-input and FG forms (so server receives dot decimal)
    $('#form-add-input').on('submit', function(e){
        const qtyRaw = normalizeNumberString($('#add_qty_used').val());
        const qty = parseFloat(qtyRaw);
        if (isNaN(qty) || qty <= 0) {
            e.preventDefault(); Swal.fire({icon:'error',title:'Qty tidak valid',text:'Masukkan jumlah (angka) > 0.'}); return;
        }
        $('#add_qty_used').val(qty);
    });

    // Also normalize FG and scrap qty fields (optional client-side check)
    $('#output_qty, #scrap_qty').on('blur', function(){
        const v = normalizeNumberString($(this).val());
        if (v !== '') {
            const n = parseFloat(v);
            if (!isNaN(n)) $(this).val(n);
        }
    });

});
</script>
</body>
</html>
