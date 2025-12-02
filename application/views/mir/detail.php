<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-plane-departure text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mir_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                <a href="<?= base_url('mir/approve_mir/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('<?= lang_line('mir_confirm_approve', 'Yakin ingin approve MIR ini? Setelah diapprove, data tidak bisa diedit.'); ?>');">
                    <i class="fas fa-check-circle fa-sm"></i> <?= lang_line('mir_approve', 'Approve MIR'); ?>
                </a>
                 <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail">
                    <i class="fas fa-edit fa-sm"></i> <?= lang_line('mir_edit_header', 'Edit Header'); ?>
                 </button>
            <?php elseif ($header->status == 'Approved' && check_access('mir', 'edit')): ?>
                 <a href="<?= base_url('mir/unapprove_mir/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('<?= lang_line('mir_confirm_unapprove', 'Yakin ingin membatalkan approval MIR ini? Pastikan belum ada penerimaan barang.'); ?>');">
                    <i class="fas fa-undo fa-sm"></i> <?= lang_line('mir_unapprove', 'Unapprove'); ?> (Back to Draft)
                </a>
            <?php endif; ?>
            <a href="<?= base_url('mir/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('mir_export_pdf', 'PDF'); ?>
            </a>
            <a href="<?= base_url('mir'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('mir_back_to_list', 'Kembali ke Daftar'); ?>
            </a>
        </div>
    </div>

     <?php // ... (Tambahkan div alert untuk flashdata success/error/warning) ... ?>

    <div class="card shadow mb-4">
         <div class="card-header py-3">
             <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('mir_header_info', 'Informasi Import Request'); ?></h6>
         </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="35%"><strong><?= lang_line('mir_number', 'Nomor MIR'); ?></strong></td><td>: <?= htmlspecialchars($header->mir_number); ?></td></tr>
                        <tr><td><strong><?= lang_line('mir_supplier', 'Supplier'); ?></strong></td><td>: <?= htmlspecialchars($header->nama_supplier ?? '-'); ?></td></tr>
                         <tr><td><strong><?= lang_line('mir_requester', 'Requester'); ?></strong></td><td>: <?= htmlspecialchars($header->requester_name ?? '-'); ?></td></tr>
                    </table>
                </div>
                 <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                        <tr><td width="35%"><strong><?= lang_line('mir_request_date', 'Tanggal Request'); ?></strong></td><td>: <?= date('d M Y', strtotime($header->request_date)); ?></td></tr>
                        <tr><td><strong><?= lang_line('mir_status', 'Status'); ?></strong></td><td>:
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

    <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-success">
                <i class="fas fa-magic mr-1"></i><?= lang_line('mir_load_from_bom', 'Quick Load dari Sales Contract BOM'); ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle mr-1"></i>
                <small>Pilih Sales Contract untuk memuat semua material dari BOM Finished Goods yang ada di SC tersebut. 
                Quantity akan otomatis dihitung: <strong>BOM Qty × SC Qty</strong></small>
            </div>
            <form id="loadBomForm" class="form-inline">
                <div class="form-group mr-2" style="flex: 1; min-width: 300px;">
                    <select id="sc_select_for_bom" class="form-control form-control-sm w-100">
                        <option value=""><?= lang_line('mir_select_sc', '-- Pilih Sales Contract --'); ?></option>
                        <?php foreach($open_contracts as $sc): ?>
                            <option value="<?= $sc->id; ?>"><?= htmlspecialchars($sc->sc_number . ' - ' . ($sc->customer_name ?? 'N/A')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="btnLoadBom" class="btn btn-success btn-sm" disabled>
                    <i class="fas fa-download mr-1"></i><?= lang_line('mir_load_from_bom', 'Load BOM'); ?> dari SC
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('mir_items', 'Detail Item Request'); ?></h6>
            <?php // Tombol Tambah Item hanya jika Draft ?>
            <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
            <button type="button" class="btn btn-success btn-sm" id="btnShowAddItemRow">
                <i class="fas fa-plus fa-sm"></i> <?= lang_line('mir_add_item', 'Tambah Item'); ?> Manual
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="itemTable">
                     <thead class="thead-light text-center">
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2"><?= lang_line('mir_item_code', 'Kode Item'); ?></th>
                            <th rowspan="2"><?= lang_line('mir_item_name', 'Nama Item'); ?></th>
                            <th rowspan="2"><?= lang_line('mir_item_type', 'Tipe'); ?></th>
                            <th rowspan="2"><?= lang_line('mir_sales_contract', 'Sales Contract'); ?></th>
                            <th colspan="3">Quantity</th>
                            <th rowspan="2">Remarks</th>
                            <th rowspan="2"><?= lang_line('mir_actions', 'Aksi'); ?></th>
                        </tr>
                        <tr>
                            <th><?= lang_line('mir_qty', 'Qty'); ?> Request</th>
                            <th><?= lang_line('mir_allowance', 'Allowance'); ?></th>
                            <th><?= lang_line('mir_total_qty', 'Total Qty'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                        <!-- Row untuk Add Item (Hidden by default) -->
                        <tr id="addItemRow" style="display: none; background-color: #e8f5e9;">
                            <td class="text-center"><i class="fas fa-plus text-success"></i></td>
                            <td>
                                <input type="text" id="newItemCode" class="form-control form-control-sm" readonly placeholder="Auto" style="background: #f8f9fa;">
                            </td>
                            <td>
                                <select id="newItemSelect" class="form-control form-control-sm" style="width: 100%;">
                                    <option value=""><?= lang_line('mir_search_items', '-- Cari dan Pilih Item --'); ?></option>
                                </select>
                            </td>
                            <td>
                                <span id="newItemType" class="badge badge-secondary">-</span>
                            </td>
                            <td class="text-center">-</td>
                            <td>
                                <input type="number" id="newItemQty" class="form-control form-control-sm" value="1" min="0.01" step="any" style="width: 90px;">
                            </td>
                            <td>
                                <input type="number" id="newItemAllowance" class="form-control form-control-sm" value="0" min="0" step="any" style="width: 90px;">
                            </td>
                            <td>
                                <input type="text" id="newItemTotal" class="form-control form-control-sm" readonly style="width: 90px; background: #f8f9fa; font-weight: bold;">
                            </td>
                            <td>
                                <input type="text" id="newItemRemarks" class="form-control form-control-sm" placeholder="Remarks (opsional)">
                            </td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-success btn-xs" id="btnSaveNewItem" title="Simpan">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-secondary btn-xs" id="btnCancelNewItem" title="Batal">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
                        <tr>
                             <td class="text-center"><?= $no++; ?></td>
                             <td><?= htmlspecialchars($d->item_code); ?></td>
                             <td><?= htmlspecialchars($d->item_name); ?></td>
                             <td class="text-center"><span class="badge badge-info"><?= ucfirst($d->item_type); ?></span></td>
                             <td><?= htmlspecialchars($d->sc_number ?? '-'); ?></td>
                             <td class="text-right">
                                 <span class="qty-display-<?= $d->id; ?>"><?= number_format($d->qty, 2); ?></span>
                                 <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                                 <input type="number" step="any" class="form-control form-control-sm qty-input-<?= $d->id; ?>" value="<?= $d->qty; ?>" style="display: none; width: 90px;" min="0.01">
                                 <?php endif; ?>
                             </td>
                             <td class="text-right">
                                 <span class="allowance-display-<?= $d->id; ?>"><?= number_format($d->allowance ?? 0, 2); ?></span>
                                 <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                                 <input type="number" step="any" class="form-control form-control-sm allowance-input-<?= $d->id; ?>" value="<?= $d->allowance ?? 0; ?>" style="display: none; width: 90px;" min="0">
                                 <?php endif; ?>
                             </td>
                             <td class="text-right">
                                 <strong class="text-primary"><?= number_format(($d->qty + ($d->allowance ?? 0)), 2); ?></strong>
                             </td>
                             <td><?= htmlspecialchars($d->remarks); ?></td>
                             <td class="text-center">
                                 <?php if ($header->status == 'Draft' && check_access('mir', 'edit')): ?>
                                 <button type="button" class="btn btn-warning btn-xs btn-edit-qty" data-id="<?= $d->id; ?>" title="Edit Qty & Allowance"> <i class="fas fa-edit"></i> </button>
                                 <button type="button" class="btn btn-success btn-xs btn-save-qty" data-id="<?= $d->id; ?>" style="display: none;" title="Simpan"> <i class="fas fa-check"></i> </button>
                                 <button type="button" class="btn btn-secondary btn-xs btn-cancel-qty" data-id="<?= $d->id; ?>" style="display: none;" title="Batal"> <i class="fas fa-times"></i> </button>
                                 <a href="<?= base_url('mir/delete_item/'.$d->mir_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete" title="Hapus Item"> <i class="fas fa-trash"></i> </a>
                                 <?php else: echo '-'; endif; ?>
                             </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr id="noDataRow"><td colspan="10" class="text-center text-muted py-5">Belum ada item detail.</td></tr>
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
    <div class="modal-dialog modal-xl">
        <form id="addItemForm" method="post" action="<?= base_url('mir/add_item/'.$header->id); ?>">
            <div class="modal-content border-0 shadow">
                 <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Pilih Item untuk Ditambahkan ke MIR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <small>Centang item yang ingin ditambahkan. Item yang sudah ada di MIR akan dilewati otomatis.</small>
                    </div>
                    
                    <!-- Filter Pencarian -->
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" id="itemFilterSearch" class="form-control" placeholder="Cari berdasarkan kode atau nama item...">
                        </div>
                    </div>

                    <!-- Tab untuk filter berdasarkan tipe -->
                    <ul class="nav nav-tabs mb-3" id="itemTypeTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#allItems" role="tab">
                                <i class="fas fa-list"></i> Semua (<?= count($available_items); ?>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#materialItems" role="tab">
                                <i class="fas fa-box"></i> Material
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#semifinishedItems" role="tab">
                                <i class="fas fa-cubes"></i> Semi-Finished
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#machineItems" role="tab">
                                <i class="fas fa-cogs"></i> Mesin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#inventoryItems" role="tab">
                                <i class="fas fa-warehouse"></i> Inventory
                            </a>
                        </li>
                    </ul>

                    <!-- Item List dengan Checkbox -->
                    <div class="tab-content" id="itemTypeTabContent" style="max-height: 400px; overflow-y: auto;">
                        <div class="tab-pane fade show active" id="allItems" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="50"><input type="checkbox" id="selectAllItems"> Semua</th>
                                            <th>Kode</th>
                                            <th>Nama Item</th>
                                            <th>Tipe</th>
                                            <th>Unit</th>
                                            <th width="100">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemListBody">
                                        <?php foreach($available_items as $item): ?>
                                        <tr class="item-row" data-type="<?= $item->type; ?>" 
                                            data-code="<?= strtolower($item->code); ?>" 
                                            data-name="<?= strtolower($item->name); ?>">
                                            <td>
                                                <input type="checkbox" name="items[]" value="<?= $item->id.'|'.$item->type; ?>" class="item-checkbox">
                                            </td>
                                            <td><?= htmlspecialchars($item->code); ?></td>
                                            <td><?= htmlspecialchars($item->name); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $item->type == 'material' ? 'primary' : 
                                                         ($item->type == 'semifinished' ? 'info' : 
                                                         ($item->type == 'machine' ? 'warning' : 'secondary')); 
                                                ?>"><?= ucfirst($item->type); ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($item->unit ?? '-'); ?></td>
                                            <td>
                                                <input type="number" step="any" name="qty[<?= $item->id.'|'.$item->type; ?>]" class="form-control form-control-sm item-qty" value="1" min="0.01" disabled>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Tab panels untuk tipe lainnya akan difilter via JS -->
                    </div>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-check-circle text-success"></i> 
                            <span id="selectedCount">0</span> item dipilih
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="btn btn-success btn-load" id="btnSubmitItems" disabled>
                        <i class="fas fa-plus mr-1"></i> Tambahkan <span id="submitCount">0</span> Item
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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

        // Select2 untuk SC selection di Load BOM
        if ($('#sc_select_for_bom').length) {
            $('#sc_select_for_bom').select2({
                placeholder: '-- Pilih Sales Contract --',
                allowClear: true,
                width: '100%'
            });

            // Enable/disable button berdasarkan selection
            $('#sc_select_for_bom').on('change', function() {
                $('#btnLoadBom').prop('disabled', !$(this).val());
            });
        }

        // Handler untuk Load BOM dari SC
        $('#btnLoadBom').on('click', function() {
            const scId = $('#sc_select_for_bom').val();
            if (!scId) {
                Swal.fire('Error', 'Pilih Sales Contract terlebih dahulu', 'error');
                return;
            }

            Swal.fire({
                title: 'Memuat BOM...',
                text: 'Mengambil data material dari BOM Finished Goods...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: '<?= base_url("mir/load_bom_from_sc/".$header->id); ?>',
                type: 'POST',
                data: { sc_id: scId },
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload(); // Reload halaman untuk menampilkan item baru
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat BOM: ' + xhr.responseText, 'error');
                }
            });
        });

        // === INLINE ADD ITEM (SIMPLE) ===
        let newItemData = {}; // Store selected item data

        // Auto-calculate total qty on change
        function updateTotalQty() {
            const qty = parseFloat($('#newItemQty').val()) || 0;
            const allowance = parseFloat($('#newItemAllowance').val()) || 0;
            const total = qty + allowance;
            $('#newItemTotal').val(total.toFixed(2));
        }

        $('#newItemQty, #newItemAllowance').on('input change', updateTotalQty);

        // Show add item row
        $('#btnShowAddItemRow').on('click', function() {
            $('#noDataRow').hide(); // Hide "no data" row if exists
            $('#addItemRow').show();
            updateTotalQty(); // Initial calculation
            $('#newItemSelect').select2('open'); // Auto-open dropdown
        });

        // Initialize Select2 for item selection
        $('#newItemSelect').select2({
            placeholder: '-- Cari Nama Item --',
            minimumInputLength: 2,
            ajax: {
                url: '<?= base_url("mir/search_items"); ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data.results };
                }
            }
        }).on('select2:select', function(e) {
            const data = e.params.data;
            const parts = data.id.split('|');
            const itemId = parts[0];
            const itemType = parts[1];
            
            // Ambil detail item untuk menampilkan kode dan tipe
            $.ajax({
                url: '<?= base_url("mir/get_item_detail"); ?>',
                type: 'GET',
                data: { item_id: itemId, item_type: itemType },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#newItemCode').val(response.data.code);
                        $('#newItemType').text(response.data.type.toUpperCase())
                            .removeClass('badge-secondary badge-primary badge-info badge-warning')
                            .addClass('badge-' + (response.data.type == 'material' ? 'primary' : 
                                     (response.data.type == 'semifinished' ? 'info' : 
                                     (response.data.type == 'machine' ? 'warning' : 'secondary'))));
                        
                        newItemData = {
                            item_id: itemId,
                            item_type: itemType,
                            code: response.data.code,
                            name: response.data.name
                        };
                        
                        $('#newItemQty').focus().select();
                    }
                }
            });
        });

        // Cancel add item
        $('#btnCancelNewItem').on('click', function() {
            resetAddItemRow();
        });

        // Save new item
        $('#btnSaveNewItem').on('click', function() {
            const qty = parseFloat($('#newItemQty').val());
            const allowance = parseFloat($('#newItemAllowance').val()) || 0;
            const remarks = $('#newItemRemarks').val();

            console.log('Saving item with:', {qty: qty, allowance: allowance, remarks: remarks});

            if (!newItemData.item_id) {
                Swal.fire('Error', 'Pilih item terlebih dahulu', 'error');
                return;
            }

            if (!qty || qty <= 0) {
                Swal.fire('Error', 'Quantity harus lebih dari 0', 'error');
                return;
            }

            Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const postData = {
                item_id: newItemData.item_id,
                item_type: newItemData.item_type,
                qty: qty,
                allowance: allowance,
                remarks: remarks
            };

            console.log('POST data:', postData);

            $.ajax({
                url: '<?= base_url("mir/add_single_item/".$header->id); ?>',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Item berhasil ditambahkan',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire('Error', 'Terjadi kesalahan: ' + xhr.responseText, 'error');
                }
            });
        });

        function resetAddItemRow() {
            $('#addItemRow').hide();
            $('#newItemSelect').val(null).trigger('change');
            $('#newItemCode').val('');
            $('#newItemType').text('-').removeClass().addClass('badge badge-secondary');
            $('#newItemQty').val('1');
            $('#newItemAllowance').val('0');
            $('#newItemTotal').val('1.00');
            $('#newItemRemarks').val('');
            newItemData = {};
        }

        // === EDIT QTY & ALLOWANCE INLINE ===
        // Edit qty button
        $('.btn-edit-qty').on('click', function() {
            const id = $(this).data('id');
            $('.qty-display-' + id).hide();
            $('.qty-input-' + id).show().focus().select();
            $('.allowance-display-' + id).hide();
            $('.allowance-input-' + id).show();
            $(this).hide();
            $('.btn-save-qty[data-id="' + id + '"]').show();
            $('.btn-cancel-qty[data-id="' + id + '"]').show();
        });

        // Cancel edit qty
        $('.btn-cancel-qty').on('click', function() {
            const id = $(this).data('id');
            $('.qty-input-' + id).hide();
            $('.qty-display-' + id).show();
            $('.allowance-input-' + id).hide();
            $('.allowance-display-' + id).show();
            $('.btn-edit-qty[data-id="' + id + '"]').show();
            $(this).hide();
            $('.btn-save-qty[data-id="' + id + '"]').hide();
        });

        // Save qty and allowance
        $('.btn-save-qty').on('click', function() {
            const id = $(this).data('id');
            const newQty = $('.qty-input-' + id).val();
            const newAllowance = $('.allowance-input-' + id).val();

            console.log('Updating item', id, 'with qty:', newQty, 'allowance:', newAllowance);

            if (!newQty || parseFloat(newQty) <= 0) {
                Swal.fire('Error', 'Quantity harus lebih dari 0', 'error');
                return;
            }

            Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const postData = { 
                detail_id: id,
                qty: newQty,
                allowance: newAllowance || 0
            };

            console.log('Update POST data:', postData);

            $.ajax({
                url: '<?= base_url("mir/update_item_qty"); ?>',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        // Reload untuk update total qty (karena computed column)
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Quantity berhasil diupdate',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Gagal update quantity', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire('Error', 'Terjadi kesalahan: ' + xhr.responseText, 'error');
                }
            });
        });

    } // End if Draft
});
</script>
<style>
/* Style Select2 di modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051 !important; }
.btn-xs { padding: .1rem .3rem; font-size: .7rem; line-height: 1; border-radius: .2rem; }
</style>