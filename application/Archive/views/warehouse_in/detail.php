<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary mr-2"></i><?= lang_line('warehouse_in_detail', 'Detail GRN'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->grn_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
                 <a href="<?= base_url('warehouse_in/confirm_grn/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('<?= lang_line('warehouse_in_confirm_action', 'Yakin konfirmasi GRN? Stok akan ditambahkan.'); ?>')">
                    <i class="fas fa-check-circle fa-sm"></i> <?= lang_line('warehouse_in_confirm', 'Confirm GRN'); ?>
                 </a>
            <?php elseif ($header->status == 'Completed' && check_access('warehouse_in', 'edit')): ?>
                 <a href="<?= base_url('warehouse_in/unconfirm_grn/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('<?= lang_line('warehouse_in_unconfirm_action', 'Yakin batalkan konfirmasi? Stok akan dikembalikan.'); ?>')">
                    <i class="fas fa-times-circle fa-sm"></i> <?= lang_line('warehouse_in_unconfirm', 'Unconfirm'); ?>
                 </a>
            <?php endif; ?>

             <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
             <button type="button" class="btn btn-warning btn-sm mr-1" data-toggle="modal" data-target="#editHeaderModalDetail">
                 <i class="fas fa-edit fa-sm"></i> <?= lang_line('warehouse_in_edit_header', 'Edit Header'); ?>
             </button>
             <?php endif; ?>

             <a href="<?= base_url('warehouse_in/export_excel/'.$header->id); ?>" target="_blank" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel fa-sm"></i> <?= lang_line('warehouse_in_export_excel', 'Excel'); ?>
            </a>
             <a href="<?= base_url('warehouse_in/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('warehouse_in_export_pdf', 'PDF'); ?>
            </a>
            <a href="<?= base_url('warehouse_in'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('warehouse_in_back_to_list', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_grn_date', 'Tanggal GRN'); ?>:</strong> <?= date('d M Y', strtotime($header->grn_date)); ?></div>
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_base_on', 'Dasar'); ?>:</strong> <?= $header->base_on; ?></div>
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_warehouse_type', 'Gudang'); ?>:</strong> <?= ucfirst($header->warehouse_type); ?></div>
            </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong><?= lang_line('warehouse_in_supplier', 'Supplier'); ?>:</strong> <?= htmlspecialchars($header->nama_supplier ?? '-'); ?></div>
                 <div class="col-md-4"><strong><?= lang_line('warehouse_in_delivery_note', 'No. Surat Jalan'); ?>:</strong> <?= htmlspecialchars($header->delivery_note_number ?? '-'); ?></div>
                 <div class="col-md-4"><strong><?= lang_line('warehouse_in_status', 'Status'); ?>:</strong> <span class="badge badge-<?= $header->status == 'Completed' ? 'success' : 'info'; ?>"><?= $header->status; ?></span></div>
             </div>
             <?php if($header->bc_type_code || $header->bc_number): ?> <hr>
              <div class="row">
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_bc_type', 'BC Type'); ?>:</strong> <?= htmlspecialchars($header->bc_type_code ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_bc_number', 'BC Number'); ?>:</strong> <?= htmlspecialchars($header->bc_number ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('warehouse_in_bc_date', 'BC Date'); ?>:</strong> <?= $header->bc_date ? date('d M Y', strtotime($header->bc_date)) : '-'; ?></div>
              </div>
             <?php endif; ?>
             <?php if($header->remarks): ?> <hr>
                 <p class="mb-0"><strong><?= lang_line('warehouse_in_remarks', 'Catatan'); ?>:</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?></p>
             <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('warehouse_in_received_items', 'Item Diterima'); ?></h6>
            <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addItemModal">
                <i class="fas fa-plus fa-sm"></i> <?= lang_line('warehouse_in_add_item', 'Terima Item Baru'); ?>
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
             <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('no', 'No'); ?></th> <th><?= lang_line('warehouse_in_source_doc', 'No. Sumber'); ?></th> <th><?= lang_line('warehouse_in_item_code', 'Kode Item'); ?></th> <th><?= lang_line('warehouse_in_item_name', 'Nama Item'); ?></th>
                            <th><?= lang_line('warehouse_in_receive_qty', 'Qty Terima'); ?></th> <th><?= lang_line('warehouse_in_unit_value', 'Nilai Satuan (IDR)'); ?></th> <th><?= lang_line('warehouse_in_total_value', 'Total Nilai (IDR)'); ?></th> <th><?= lang_line('warehouse_in_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if(!empty($details)): $no = 1; $grand_total_value = 0; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->source_doc_number); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td class="text-right"><?= number_format($d->receive_qty, 2); ?></td>
                            <td class="text-right"><?= number_format($d->unit_value ?? 0, 2); ?></td>
                            <td class="text-right"><?= number_format($d->total_value ?? 0, 2); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
                                <a href="<?= base_url('warehouse_in/delete_item/'.$d->grn_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete"> <i class="fas fa-trash"></i> </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $grand_total_value += ($d->total_value ?? 0); endforeach; ?>
                         <tr class="bg-light font-weight-bold">
                            <td colspan="6" class="text-right"><?= lang_line('warehouse_in_grand_total', 'Grand Total Nilai (IDR)'); ?></td>
                            <td class="text-right"><?= number_format($grand_total_value, 2); ?></td> <td></td>
                        </tr>
                        <?php else: ?> <tr><td colspan="8" class="text-center text-muted py-5"><?= lang_line('warehouse_in_no_items', 'Belum ada item yang diterima'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($header->status == 'Draft'): ?>
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addItemForm" method="post" action="<?= base_url('warehouse_in/add_item/'.$header->id); ?>">
             <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i><?= lang_line('warehouse_in_add_item', 'Terima Item Baru'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                             <label><?= lang_line('warehouse_in_select_source', 'Pilih Nomor'); ?> <?= $header->base_on; ?></label>
                             <select id="source_doc_search" name="source_doc_id" class="form-control" required style="width: 100%;">
                                 <option value=""><?= lang_line('warehouse_in_select_source', '-- Cari Nomor'); ?> <?= $header->base_on; ?> --</option>
                             </select>
                        </div>
                         <div class="col-md-6 form-group">
                             <label><?= lang_line('warehouse_in_select_item', 'Pilih Item dari'); ?> <?= $header->base_on; ?> (<?= lang_line('warehouse_in_warehouse_type', 'Gudang'); ?>: <?= ucfirst($header->warehouse_type); ?>)</label>
                             <select id="item_selector" name="item_selector" class="form-control" required disabled style="width: 100%;">
                                 <option value=""><?= lang_line('warehouse_in_select_source', '-- Pilih Nomor'); ?> <?= $header->base_on; ?> <?= lang_line('first', 'dulu'); ?> --</option>
                             </select>
                             <input type="hidden" id="item_price_hidden" name="item_price_hidden" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('warehouse_in_receive_qty', 'Qty Diterima'); ?></label>
                            <input type="number" step="any" id="receive_qty" name="receive_qty" class="form-control" required max="0">
                            <small id="maxQtyHelp" class="form-text text-muted"></small>
                        </div>
                        <div class="col-md-6 form-group" id="unit_value_mir_div" style="<?= $header->base_on == 'PO' ? 'display:none;' : ''; ?>">
                            <label><?= lang_line('warehouse_in_unit_value', 'Nilai Satuan Barang (IDR)'); ?></label>
                            <input type="text" name="unit_value_mir" class="form-control autonumeric text-right" value="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('warehouse_in_save', 'Simpan Penerimaan'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('warehouse_in_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($header->status == 'Draft'): ?>
<div class="modal fade" id="editHeaderModalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('warehouse_in/update_header/'.$header->id); ?>">
             <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Header GRN</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                     <div class="row">
                        <div class="col-md-6 form-group"><label>Nomor GRN</label><input type="text" class="form-control" value="<?= htmlspecialchars($header->grn_number); ?>" disabled></div>
                        <div class="col-md-6 form-group"><label>Tanggal Penerimaan</label><input type="date" name="grn_date" class="form-control" value="<?= $header->grn_date; ?>" required></div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group"><label>Dasar Penerimaan</label><input type="text" class="form-control" value="<?= $header->base_on; ?>" disabled></div>
                         <div class="col-md-6 form-group"><label>Penerimaan Gudang Untuk</label><input type="text" class="form-control" value="<?= ucfirst($header->warehouse_type); ?>" disabled></div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 form-group"><label>Nomor Surat Jalan Supplier</label><input type="text" name="delivery_note_number" class="form-control" value="<?= htmlspecialchars($header->delivery_note_number); ?>"></div>
                        <div class="col-md-6 form-group"><label>Supplier</label><input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier ?? '-'); ?>" disabled></div>
                   </div>
                     <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Tipe BC (Optional)</label>
                            <select name="bc_type_code" class="form-control">
                                <option value="">-- Tanpa BC --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= $header->bc_type_code == $bc->code ? 'selected' : ''; ?>><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-4 form-group"><label>Tanggal BC (Optional)</label><input type="date" name="bc_date" class="form-control" value="<?= $header->bc_date; ?>"></div>
                         <div class="col-md-4 form-group"><label>Nomor BC (Optional)</label><input type="text" name="bc_number" class="form-control" value="<?= htmlspecialchars($header->bc_number); ?>"></div>
                    </div>
                    <div class="form-group"><label>Keterangan</label><textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($header->remarks); ?></textarea></div>
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


<?php $this->load->view('templates/footer'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<script>
$(document).ready(function() {
    const baseOnType = '<?= $header->base_on; ?>';
    const warehouseType = '<?= $header->warehouse_type; ?>';
    const currentStatus = '<?= $header->status; ?>';

    // Inisialisasi hanya jika status Draft
    if (currentStatus === 'Draft') {
        // Inisialisasi AutoNumeric hanya jika elemennya ada
        if ($('input[name="unit_value_mir"]').length) {
            const anMirValue = new AutoNumeric('input[name="unit_value_mir"]', { decimalPlaces: 2 });
             if (baseOnType === 'MIR') { anMirValue.set(0); } // Set default jika MIR
        }
        if ($('.autonumeric-4dec').length) {
            const anEditExchangeRate = new AutoNumeric('.autonumeric-4dec', { decimalPlaces: 4 });
            // Pasang event handler kurs HANYA JIKA elemen edit ada
            if ($('#editHeaderModalDetail').length) {
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
            }
        }


        // Inisialisasi Select2 hanya jika elemennya ada
        if ($('#source_doc_search').length) {
            $('#source_doc_search').select2({
                placeholder: '-- Cari Nomor ' + baseOnType + ' --',
                minimumInputLength: 2,
                ajax: {
                    url: '<?= base_url("warehouse_in/search_source_docs"); ?>',
                    dataType: 'json', delay: 250, data: function (params) { return { q: params.term, type: baseOnType }; },
                    processResults: function (data) { return { results: data.results }; },
                },
                dropdownParent: $('#addItemModal')
            });
        }
        if ($('#item_selector').length) {
            $('#item_selector').select2({
                placeholder: '-- Pilih Item --',
                dropdownParent: $('#addItemModal')
            });
        }

        // Pasang event handler HANYA JIKA elemennya ada
        if ($('#source_doc_search').length) {
            $('#source_doc_search').on('change', function() {
                const sourceId = $(this).val();
                $('#item_selector').prop('disabled', true).html('<option value="">Memuat...</option>').trigger('change');
                if (sourceId) {
                    $.get('<?= base_url("warehouse_in/get_available_items"); ?>',
                        { source_type: baseOnType, source_id: sourceId, warehouse_type: warehouseType },
                        function(response) {
                            $('#item_selector').html(response).prop('disabled', false).trigger('change');
                            $('#receive_qty').val('').attr('max', 0); $('#maxQtyHelp').text('');
                        }).fail(function(){
                            $('#item_selector').html('<option value="">Gagal</option>').trigger('change');
                        });
                } else { $('#item_selector').html('<option value="">-- Pilih No '+baseOnType+' --</option>').trigger('change'); }
            });
        }

        if ($('#item_selector').length) {
            $('#item_selector').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const maxQty = parseFloat(selectedOption.data('maxqty') || 0);
                const price = parseFloat(selectedOption.data('price') || 0);
                $('#receive_qty').attr('max', maxQty).val('');
                $('#maxQtyHelp').text('Sisa qty: ' + maxQty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#item_price_hidden').val(price);
                $('#unit_value_mir_div').toggle(baseOnType === 'MIR');
                 // Pastikan anMirValue sudah diinisialisasi sebelum digunakan
                if (baseOnType === 'MIR' && typeof anMirValue !== 'undefined') {
                    anMirValue.set(0);
                }
            });
        }

        if ($('#receive_qty').length) {
            $('#receive_qty').on('input', function(){
                let currentVal = parseFloat($(this).val());
                let maxQtyAttr = $(this).attr('max'); // Ambil nilai max dari atribut
                let maxQty = parseFloat(maxQtyAttr || 0); // Konversi ke float, default 0 jika tidak ada
                if (isNaN(maxQty)) maxQty = 0; // Fallback jika NaN

                if(currentVal > maxQty){ $(this).val(maxQty); }
                else if (currentVal < 0) { $(this).val(0); }
            });
        }

    } // End if currentStatus === 'Draft'
});
</script>
<style>
/* Style Select2 di dalam modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; }
</style>