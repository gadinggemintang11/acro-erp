<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Proses Produksi - Sewing'); ?></title>
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
            line-height: 1.5; border-radius: .25rem;
        }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
        .is-invalid + .select2-container--bootstrap4 .select2-selection--single { border-color: #e74a3b; }
        .table th, .table td { vertical-align: middle; padding: 0.5rem; }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h4 mb-0 text-gray-800">
                        <i class="fas fa-cut text-primary mr-2"></i><?= htmlspecialchars($title); ?>
                        <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->production_number); ?></span>
                    </h3>
                    <div>
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                            <a href="<?= base_url('production_process/confirm/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1"
                               onclick="return confirm('Yakin ingin konfirmasi proses produksi ini?');">
                                <i class="fas fa-check-circle fa-sm"></i> Konfirmasi Proses
                            </a>
                        <?php elseif ($header->status == 'In Progress' && check_access('production_process', 'edit')): ?>
                            <a href="<?= base_url('production_process/complete_production/'.$header->id); ?>" class="btn btn-success btn-sm mr-1"
                               onclick="return confirm('Yakin ingin menyelesaikan proses produksi ini?');">
                                <i class="fas fa-flag-checkered fa-sm"></i> Selesaikan Produksi
                            </a>
                            <a href="<?= base_url('production_process/unconfirm/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1"
                               onclick="return confirm('Yakin ingin membatalkan konfirmasi?');">
                                <i class="fas fa-undo fa-sm"></i> Batal Konfirmasi
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('production_process/export_sewing_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                            <i class="fas fa-file-pdf fa-sm"></i> PDF (Sewing)
                        </a>
                        <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali</a>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-fill mb-4">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/cutting/'.$header->id); ?>">1. Cutting</a></li>
                    <li class="nav-item"><a class="nav-link active font-weight-bold" href="#">2. Sewing</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/assembling/'.$header->id); ?>">3. Assembling</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/finishing/'.$header->id); ?>">4. Finishing</a></li>
                </ul>

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

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4"><strong>No. Produksi:</strong> <?= htmlspecialchars($header->production_number); ?></div>
                            <div class="col-md-4"><strong>No. WO:</strong> <?= htmlspecialchars($header->wo_number); ?></div>
                            <div class="col-md-4"><strong>Status:</strong>
                                <?php
                                    $badge = 'secondary';
                                    if ($header->status == 'Draft') $badge = 'warning';
                                    if ($header->status == 'In Progress') $badge = 'primary';
                                    if ($header->status == 'Completed') $badge = 'success';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $header->status; ?></span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong>Barang Jadi:</strong> <?= htmlspecialchars($header->fg_name); ?></div>
                            <div class="col-md-4"><strong>Tgl Produksi:</strong> <?= date('d M Y', strtotime($header->production_date)); ?></div>
                            <div class="col-md-4"><strong>Qty Rencana:</strong> <?= number_format($header->planned_qty, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- ===================== LEFT COLUMN ===================== -->
                    <div class="col-lg-6">

                        <!-- Input dari Cutting -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-info text-white py-3"><h6 class="m-0 font-weight-bold">Input dari Cutting (Hasil QC OK)</h6></div>
                            <div class="card-body">
                                <form id="formInputPrev" method="post" action="<?= base_url('production_process/save_sewing_cutting_input/'.$header->id); ?>">
                                    <input type="hidden" name="item_id" id="prev_item_id">
                                    <input type="hidden" name="item_type" id="prev_item_type">

                                    <div class="form-group">
                                        <label for="prev_output_selector">Pilih Hasil Cutting (QC OK)</label>
                                        <select name="source_output_id" id="prev_output_selector" class="form-control select2-wip" required>
                                            <option value="">-- Pilih Output Cutting --</option>
                                            <?php if(!empty($available_previous_outputs)): ?>
                                                <?php foreach($available_previous_outputs as $out): ?>
                                                    <?php if($out->remaining_qty > 0): ?>
                                                    <option value="<?= $out->output_id; ?>"
                                                            data-itemid="<?= $out->item_id; ?>"
                                                            data-itemtype="<?= $out->item_type; ?>"
                                                            data-maxqty="<?= $out->remaining_qty; ?>">
                                                        <?= htmlspecialchars($out->item_name.' ('.$out->item_code.') - Sisa: '.number_format($out->remaining_qty,4).' '.$out->item_unit); ?>
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>-- Tidak ada hasil Cutting tersedia --</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Qty Digunakan</label>
                                            <input type="number" step="0.0001" name="qty_used" id="prev_qty_used" class="form-control" required min="0.0001" max="0">
                                            <small class="form-text text-muted">Maks: <span id="prev_max_qty">0.0000</span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Catatan</label>
                                            <input type="text" name="notes" id="prev_notes" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm mt-2"><i class="fas fa-plus mr-1"></i> Tambah Input Cutting</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Material Tambahan -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-primary text-white py-3"><h6 class="m-0 font-weight-bold">Input Material Tambahan (Gudang)</h6></div>
                            <div class="card-body">
                                <form id="formInputAdd" method="post" action="<?= base_url('production_process/save_sewing_additional_input/'.$header->id); ?>">
                                    <input type="hidden" name="item_id" id="add_item_id">
                                    <input type="hidden" name="item_type" id="add_item_type">
                                    <div class="form-group">
                                        <label>Pilih Material (Batch)</label>
                                        <select name="stock_on_hand_id" id="add_batch_selector" class="form-control select2-wip" required>
                                            <option value="">-- Pilih Batch Material --</option>
                                            <?php if(!empty($available_batches)): ?>
                                                <?php foreach($available_batches as $batch): ?>
                                                    <option value="<?= $batch->stock_on_hand_id; ?>"
                                                            data-itemid="<?= $batch->item_id; ?>"
                                                            data-itemtype="<?= $batch->item_type; ?>"
                                                            data-maxqty="<?= $batch->remaining_qty; ?>">
                                                        <?= htmlspecialchars($batch->item_name.' ('.$batch->item_code.') - Batch: '.($batch->grn_number ?? 'N/A').' - Sisa: '.number_format($batch->remaining_qty,4).' '.$batch->item_unit); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option disabled>-- Tidak ada batch material tersedia --</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Qty Digunakan</label>
                                            <input type="number" step="0.0001" name="qty_used" id="add_qty_used" class="form-control" required min="0.0001" max="0">
                                            <small class="form-text text-muted">Sisa Stok: <span id="add_max_qty">0.0000</span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Catatan</label>
                                            <input type="text" name="notes" id="add_notes" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-plus mr-1"></i> Tambah Material</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Daftar Input -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Input Sewing</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light text-center">
                                            <tr><th>Item</th><th>Tipe</th><th>Qty</th><th>Sumber</th><th>Catatan</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($current_inputs)): ?>
                                                <tr><td colspan="6" class="text-center text-muted py-2">Belum ada input</td></tr>
                                            <?php else: foreach($current_inputs as $in): ?>
                                                <tr class="text-center">
                                                    <td class="text-left"><?= htmlspecialchars($in->item_name); ?></td>
                                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($in->item_type); ?></span></td>
                                                    <td class="text-right"><?= number_format($in->qty_used, 4); ?></td>
                                                    <td><?= $in->source_output_id ? 'Cutting' : 'Gudang'; ?></td>
                                                    <td class="text-left"><?= htmlspecialchars($in->notes); ?></td>
                                                    <td>
                                                        <?php if ($header->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                                            <a href="<?= base_url('production_process/delete_sewing_input/'.$header->id.'/'.$in->id); ?>"
                                                               class="btn btn-danger btn-xs btn-delete"
                                                               data-message="Hapus input <?= htmlspecialchars(addslashes($in->item_name)); ?>?">
                                                               <i class="fas fa-trash"></i></a>
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

                    <!-- ===================== RIGHT COLUMN ===================== -->
                    <div class="col-lg-6">

                        <!-- Output Semi-Finished -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-success text-white py-3"><h6 class="m-0 font-weight-bold">Output Semi-Finished</h6></div>
                            <div class="card-body">
                                <form id="formOutput" method="post" action="<?= base_url('production_process/save_sewing_output/'.$header->id); ?>">
                                    <div class="form-group">
                                        <label>Pilih Item Semi-Finished</label>
                                        <select name="item_id" id="output_item_sf" class="form-control select2-ajax-semifinished" required style="width:100%;"></select>
                                    </div>
                                    <div class="form-group">
                                        <label>Qty Dihasilkan</label>
                                        <input type="number" step="0.0001" name="qty" id="output_qty_sf" class="form-control" required min="0.0001">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Output</button>
                                </form>
                            </div>
                        </div>

                        <!-- Output Scrap -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-warning text-dark py-3"><h6 class="m-0 font-weight-bold">Output Scrap</h6></div>
                            <div class="card-body">
                                <form id="formOutputScrap" method="post" action="<?= base_url('production_process/save_sewing_output/'.$header->id); ?>">
                                    <div class="form-group">
                                        <label>Pilih Item Scrap</label>
                                        <select name="item_id" id="output_item_scrap" class="form-control select2-ajax-scrap" required style="width:100%;"></select>
                                    </div>
                                    <div class="form-group">
                                        <label>Qty Scrap</label>
                                        <input type="number" step="0.0001" name="qty" id="output_qty_scrap" class="form-control" required min="0.0001">
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Scrap</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Daftar Output -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold">Daftar Output Sewing</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light text-center">
                                            <tr><th>Item</th><th>Tipe</th><th>Qty</th><th>QC Status</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($current_outputs)): ?>
                                                <tr><td colspan="5" class="text-center text-muted py-2">Belum ada output</td></tr>
                                            <?php else: foreach($current_outputs as $out): ?>
                                                <tr class="text-center">
                                                    <td class="text-left"><?= htmlspecialchars($out->item_name); ?></td>
                                                    <td><span class="badge badge-<?= $out->item_type == 'scrap' ? 'warning text-dark' : 'success'; ?>"><?= ucfirst($out->item_type); ?></span></td>
                                                    <td class="text-right"><?= number_format($out->item_type == 'scrap' ? $out->qty_scrap : $out->qty_produced,4); ?></td>
                                                    <td>
                                                        <?php if($out->qc_checked_by): ?>
                                                            <span class="badge badge-success">QC OK</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($header->status != 'Completed' && check_access('production_process', 'add')): ?>
                                                            <?php if(!$out->qc_checked_by): ?>
                                                                <a href="<?= base_url('production_process/confirm_qc_sewing/'.$header->id.'/'.$out->id); ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i></a>
                                                            <?php else: ?>
                                                                <a href="<?= base_url('production_process/unconfirm_qc_sewing/'.$header->id.'/'.$out->id); ?>" class="btn btn-warning btn-xs"><i class="fas fa-undo"></i></a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if ($header->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                                            <a href="<?= base_url('production_process/delete_sewing_output/'.$header->id.'/'.$out->id); ?>" class="btn btn-danger btn-xs btn-delete ml-1" data-message="Hapus output <?= htmlspecialchars(addslashes($out->item_name)); ?>?"><i class="fas fa-trash"></i></a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- END RIGHT -->
                </div><!-- END ROW -->
            </div><!-- END CONTAINER -->
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.select2-wip').select2({ theme: 'bootstrap4', width: '100%' });

    // Semi-finished AJAX
    $('.select2-ajax-semifinished').select2({
        theme: 'bootstrap4', width: '100%', placeholder: '-- Cari Semi-Finished --', minimumInputLength: 1,
        ajax: {
            url: '<?= base_url("production_process/search_semifinished"); ?>',
            dataType: 'json', delay: 250, data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        }
    });

    // Scrap AJAX
    $('.select2-ajax-scrap').select2({
        theme: 'bootstrap4', width: '100%', placeholder: '-- Cari Scrap --', minimumInputLength: 1,
        ajax: {
            url: '<?= base_url("production_process/search_scrap"); ?>',
            dataType: 'json', delay: 250, data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        }
    });

    // Cutting input
    $('#prev_output_selector').on('change', function() {
        const sel = $(this).find('option:selected');
        const max = parseFloat(sel.data('maxqty') || 0);
        $('#prev_item_id').val(sel.data('itemid') || '');
        $('#prev_item_type').val(sel.data('itemtype') || '');
        $('#prev_qty_used').attr('max', max).val('');
        $('#prev_max_qty').text(max.toFixed(4));
    });

    // Additional material
    $('#add_batch_selector').on('change', function() {
        const sel = $(this).find('option:selected');
        const max = parseFloat(sel.data('maxqty') || 0);
        $('#add_item_id').val(sel.data('itemid') || '');
        $('#add_item_type').val(sel.data('itemtype') || '');
        $('#add_qty_used').attr('max', max).val('');
        $('#add_max_qty').text(max.toFixed(4));
    });

    // SweetAlert delete
    $('body').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('message');
        Swal.fire({
            title: 'Konfirmasi', text: msg, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya', cancelButtonText: 'Batal'
        }).then(res => { if(res.isConfirmed) window.location.href = url; });
    });
});
</script>

</body>
</html>
