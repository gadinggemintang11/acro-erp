<div class="container-fluid">
    <!-- Hidden fields for JavaScript -->
    <input type="hidden" id="mr_header_id" value="<?= $header->id; ?>">
    <input type="hidden" id="is_draft" value="<?= $header->status == 'Draft' ? '1' : '0'; ?>">
    <input type="hidden" id="base_url" value="<?= base_url(); ?>">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                 <a href="<?= base_url('material_request/submit_mr/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" onclick="return confirm('Yakin ingin submit MR ini ke Gudang?');">
                    <i class="fas fa-paper-plane fa-sm"></i> Submit ke Gudang
                 </a>
            <?php elseif ($header->status == 'Submitted' && check_access('material_request', 'edit')): ?>
                 <a href="<?= base_url('material_request/unsubmit_mr/'.$header->id); ?>" class="btn btn-warning btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan submit MR ini?');">
                    <i class="fas fa-undo fa-sm"></i> Batal Submit
                 </a>
            <?php endif; ?>
            
             <a href="<?= base_url('material_request/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> Export PDF
            </a>
            <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>No. MR:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong>Tanggal:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-<?= $header->status == 'Draft' ? 'secondary' : 'info'; ?>"><?= $header->status; ?></span></div>
             </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong>Dibuat Oleh:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Disiapkan (Gudang):</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Diterima (Produksi):</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
             </div>
             <?php if($header->remarks): ?> <hr> <p class="mb-0"><strong>Catatan:</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?></p> <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-clipboard-check mr-2"></i>Referensi Work Order
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($header->status == 'Draft'): ?>
                    <div class="form-group">
                        <label>Pilih WO untuk melihat referensi BOM:</label>
                        <select id="wo_selector" class="form-control" style="width: 100%;">
                            <option value="">-- Cari No. Work Order --</option>
                        </select>
                        <small class="form-text text-muted">Referensi akan tetap tampil setelah menambah item</small>
                    </div>
                    <?php endif; ?>
                    <div id="wo_reference_div" class="mt-3" style="font-size: 0.9em;">
                        <p class="text-muted text-center"><i class="fas fa-info-circle mr-2"></i>Pilih Work Order untuk menampilkan referensi BOM dan Rencana Material.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
             <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-boxes mr-2"></i>Item Permintaan Material (<span id="item_count"><?= count($details); ?></span>)
                            </h6>
                        </div>
                        <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-primary btn-sm mr-2" id="btn-load-wo-materials" data-mr-id="<?= $header->id; ?>">
                                <i class="fas fa-upload"></i> Load from WO Materials
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                    <div class="row align-items-center mt-2">
                        <div class="col-md-6">
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="fas fa-clipboard-list mr-1"></i> Work Order
                                    </span>
                                </div>
                                <select id="selected_wo_main" class="form-control form-control-sm" style="width: 60%;">
                                    <option value="">-- Pilih WO --</option>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-info" type="button" id="btn_apply_wo" disabled title="Terapkan WO ke baris input">
                                        <i class="fas fa-check"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Pilih WO sekali, otomatis terisi di baris input baru
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0" id="items_table">
                            <thead class="thead-light"> 
                                <tr>
                                    <th width="120">WO <span class="text-danger">*</span></th>
                                    <th width="100">Kode <span class="text-danger">*</span></th>
                                    <th>Nama Item <span class="text-danger">*</span></th>
                                    <th width="110" class="text-center">Qty <span class="text-danger">*</span></th>
                                    <th width="60">Unit</th>
                                    <th width="150">Catatan</th>
                                    <th width="70" class="text-center">Aksi</th>
                                </tr> 
                            </thead>
                            <tbody id="items_tbody">
                                <?php if(!empty($details)): foreach($details as $d): ?>
                                <tr data-id="<?= $d->id; ?>" class="existing-item">
                                    <td><small><?= htmlspecialchars($d->wo_number); ?></small></td>
                                    <td><small><?= htmlspecialchars($d->item_code); ?></small></td>
                                    <td><small><?= htmlspecialchars($d->item_name); ?></small></td>
                                    <td class="text-right"><?= number_format($d->requested_qty, 2); ?></td>
                                    <td><small><?= htmlspecialchars($d->unit_measure ?? '-'); ?></small></td>
                                    <td><small><?= htmlspecialchars($d->notes ?? ''); ?></small></td>
                                    <td class="text-center">
                                         <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                                        <button class="btn btn-danger btn-xs btn-delete-item" data-id="<?= $d->id; ?>" data-name="<?= htmlspecialchars($d->item_name); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                                
                                <?php if ($header->status == 'Draft' && check_access('material_request', 'edit')): ?>
                                <!-- Input Row - Always visible for quick add -->
                                <tr id="input_row" class="bg-light border-primary">
                                    <td class="p-1">
                                        <input type="text" id="input_wo_display" class="form-control form-control-sm" readonly placeholder="Pilih WO di atas" style="background: #fff; font-size: 0.8rem;">
                                        <input type="hidden" id="input_wo_id">
                                    </td>
                                    <td colspan="2" class="p-1">
                                        <select id="input_component" class="form-control form-control-sm" style="width: 100%;">
                                            <option value="">-- Cari & Pilih Material/Component --</option>
                                        </select>
                                    </td>
                                    <td class="p-1">
                                        <input type="number" id="input_qty" class="form-control form-control-sm text-right" placeholder="0.00" step="any" min="0.01">
                                    </td>
                                    <td class="text-center align-middle">
                                        <small id="input_unit" class="text-muted font-weight-bold">-</small>
                                    </td>
                                    <td class="p-1">
                                        <input type="text" id="input_notes" class="form-control form-control-sm" placeholder="Catatan...">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" id="btn_add_item" class="btn btn-success btn-sm" title="Tambah Item (Enter)" disabled>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (empty($details)): ?>
                                <tr id="empty_row">
                                    <td colspan="7" class="text-center text-muted py-3" style="font-size: 0.9rem;">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                        <strong>Belum ada item.</strong><br>
                                        <small>Pilih WO di atas, lalu isi baris biru untuk menambah item</small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Inisialisasi Select2 untuk pencarian WO (di Referensi)
    $('#wo_selector').select2({
        placeholder: '-- Cari No. Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("material_request/search_open_wos"); ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        }
    });

    // Event saat WO di Referensi dipilih
    $('#wo_selector').on('change', function() {
        const woId = $(this).val();
        $('#wo_reference_div').html('<p class="text-center text-muted">Memuat referensi...</p>');
        if (woId) {
            $.get('<?= base_url("material_request/get_wo_references_json"); ?>', { wo_id: woId }, function(response) {
                if(response.success) {
                    $('#wo_reference_div').html(response.html);
                } else {
                    $('#wo_reference_div').html('<p class="text-center text-danger">Gagal memuat referensi.</p>');
                }
            }, 'json').fail(function(){
                 $('#wo_reference_div').html('<p class="text-center text-danger">Error AJAX.</p>');
            });
        } else {
             $('#wo_reference_div').html('<p class="text-muted text-center">Pilih Work Order untuk menampilkan referensi.</p>');
        }
    });

    // Inisialisasi Select2 untuk Modal Tambah Item (HANYA JIKA STATUS DRAFT)
    if ('<?= $header->status; ?>' === 'Draft') {
        
        // ✅ FIX: Kesalahan copy-paste dihapus dari sini.
        $('#wo_selector_modal').select2({
            placeholder: '-- Cari No. Work Order --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("material_request/search_open_wos"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            },
            dropdownParent: $('#addItemModal')
        });

        $('#component_search').select2({
            placeholder: '-- Cari Kode / Nama Komponen --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("material_request/search_components"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
            },
            dropdownParent: $('#addItemModal')
        });
    }
});
</script>

<style>
/* Style Select2 di dalam modal */
.select2-container--default .select2-selection--single { 
    height: calc(1.5em + .75rem + 2px); 
    padding: .375rem .75rem; 
    border: 1px solid #d1d3e2; 
}
.select2-dropdown { 
    z-index: 1051; 
    border: 1px solid #d1d3e2; 
}
/* Style untuk tabel referensi agar lebih kecil */
#wo_reference_div .table-sm td, 
#wo_reference_div .table-sm th { 
    padding: .3rem; 
    font-size: 0.85em;
}
/* Style temp items */
#temp_items_container {
    background-color: #f8f9fc;
    border: 1px dashed #4e73df;
    border-radius: 5px;
    padding: 10px;
    animation: slideDown 0.3s ease-out;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
.new-item {
    background-color: #d1ecf1 !important;
    transition: background-color 2s ease-out;
}
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
/* Input row styling */
#input_row {
    background-color: #f0f8ff !important;
    border-left: 3px solid #4e73df !important;
}
#input_row:hover {
    background-color: #e3f2fd !important;
}
#input_row input:focus,
#input_row .select2-container--open {
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}
/* Make existing items slightly different color */
.existing-item {
    background-color: #fff;
}
.existing-item:hover {
    background-color: #f8f9fc !important;
}
</style>

<!-- Modal Load Materials from WO -->
<div class="modal fade" id="modalLoadWOMaterials" tabindex="-1" role="dialog" aria-labelledby="modalLoadWOMaterialsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLoadWOMaterialsLabel">Load Materials from WO</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><strong>Select Work Order:</strong></label>
                    <select id="modal_wo_selector" class="form-control" style="width: 100%;">
                        <option value="">-- Cari No. Work Order --</option>
                    </select>
                </div>
                
                <div id="modal_materials_list" class="mt-4" style="display: none;">
                    <h6 class="font-weight-bold mb-3">Planned Materials:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="chk-select-all" title="Select All">
                                    </th>
                                    <th>Code</th>
                                    <th>Material Name</th>
                                    <th width="100">Planned Qty</th>
                                    <th width="100">Request Qty</th>
                                    <th width="70">Unit</th>
                                </tr>
                            </thead>
                            <tbody id="modal_materials_tbody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="modal_no_materials" class="alert alert-info mt-4" style="display: none;">
                    <i class="fas fa-info-circle mr-2"></i>Select a Work Order to view planned materials.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-load-materials">
                    <i class="fas fa-check"></i> Load Selected Materials
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= base_url(); ?>';
</script>
<script src="<?= base_url('assets/js/mr_inline_input.js'); ?>"></script>

<!-- ✅ NEW: Script untuk Load from WO Materials -->
<script>
$(document).ready(function() {
    // Initialize Select2 untuk modal WO selector
    $('#modal_wo_selector').select2({
        placeholder: 'Cari No. Work Order...',
        width: '100%',
        ajax: {
            url: BASE_URL + 'material_request/search_open_wos',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data.results };
            }
        }
    });

    // Load materials ketika WO dipilih
    $('#modal_wo_selector').on('select2:select', function(e) {
        var wo_id = e.params.data.id;
        loadWOMaterials(wo_id);
    });

    // Select All checkbox
    $('#chk-select-all').on('change', function() {
        var is_checked = $(this).is(':checked');
        $('#modal_materials_tbody input[type="checkbox"]').prop('checked', is_checked);
    });

    // Button: Load materials
    $('#btn-load-wo-materials').on('click', function() {
        // Reset modal state
        $('#modal_materials_tbody').html('');
        $('#modal_materials_list').hide();
        $('#modal_no_materials').html('<i class="fas fa-info-circle mr-2"></i>Select a Work Order to view planned materials.').show();
        $('#chk-select-all').prop('checked', false);
        $('#modalLoadWOMaterials').modal('show');
    });

    // Button: Confirm load materials
    $('#btn-confirm-load-materials').on('click', function() {
        confirmLoadMaterials();
    });

    // Function: Load WO materials
    function loadWOMaterials(wo_id) {
        $.ajax({
            url: BASE_URL + 'material_request/ajax_get_wo_materials?wo_id=' + wo_id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var html = '';
                    response.materials.forEach(function(mat) {
                        html += '<tr>';
                        html += '<td class="text-center"><input type="checkbox" class="material-checkbox" data-id="' + mat.id + '" data-type="' + mat.type + '" data-planned-qty="' + mat.planned_qty + '"></td>';
                        html += '<td><small>' + mat.code + '</small></td>';
                        html += '<td><small>' + mat.name + '</small></td>';
                        html += '<td class="text-right"><small>' + parseFloat(mat.planned_qty).toFixed(2) + '</small></td>';
                        html += '<td><input type="number" step="0.01" class="form-control form-control-sm request-qty" value="' + parseFloat(mat.planned_qty).toFixed(2) + '" min="0"></td>';
                        html += '<td><small>' + mat.unit + '</small></td>';
                        html += '</tr>';
                    });
                    
                    $('#modal_materials_tbody').html(html);
                    $('#modal_materials_list').show();
                    $('#modal_no_materials').hide();
                    $('#chk-select-all').prop('checked', false);
                } else {
                    $('#modal_materials_list').hide();
                    $('#modal_no_materials').html('<i class="fas fa-exclamation-circle mr-2"></i>' + response.message).show();
                }
            },
            error: function() {
                $('#modal_materials_list').hide();
                $('#modal_no_materials').html('<i class="fas fa-exclamation-circle mr-2"></i>Error loading materials').show();
            }
        });
    }

    // Function: Confirm load materials
    function confirmLoadMaterials() {
        var mr_id = $('#btn-load-wo-materials').data('mr-id');
        var wo_id = $('#modal_wo_selector').val();
        
        if (!wo_id) {
            alert('⚠️ Please select a Work Order');
            return;
        }

        var selected_items = [];
        $('#modal_materials_tbody input[type="checkbox"]:checked').each(function() {
            var row = $(this).closest('tr');
            var qty = parseFloat(row.find('.request-qty').val()) || 0;
            
            if (qty > 0) {
                selected_items.push({
                    id: $(this).data('id'),
                    type: $(this).data('type'),
                    qty: qty
                });
            }
        });

        if (selected_items.length === 0) {
            alert('⚠️ Please select at least one material');
            return;
        }

        $.ajax({
            url: BASE_URL + 'material_request/ajax_add_materials_from_wo/' + mr_id,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                wo_id: wo_id,
                selected_items: selected_items
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    $('#modalLoadWOMaterials').modal('hide');
                    location.reload();
                } else {
                    alert('❌ ' + response.message);
                }
            },
            error: function() {
                alert('❌ Error loading materials');
            }
        });
    }
});
</script>