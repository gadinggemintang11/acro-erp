<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary mr-2"></i><?= lang_line('warehouse_in_detail', 'Detail GRN'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->grn_number); ?></span>
        </h3>
        <div>
            <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
                 <!-- TOMBOL PILIH MIR (untuk Base On = MIR) -->
                 <?php if ($header->base_on == 'MIR'): ?>
                 <button type="button" class="btn btn-info btn-sm mr-1" data-toggle="modal" data-target="#mirAutoLoadModal" title="Pilih dan load items dari beberapa MIR">
                     <i class="fas fa-file-import mr-1"></i> Pilih & Load MIR
                 </button>
                 <?php endif; ?>

                 <!-- TOMBOL PILIH PO (untuk Base On = PO) -->
                 <?php if ($header->base_on == 'PO'): ?>
                 <button type="button" class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#poAutoLoadModal" title="Pilih dan load items dari beberapa PO">
                     <i class="fas fa-file-import mr-1"></i> Pilih & Load PO
                 </button>
                 <?php endif; ?>

                 <!-- TOMBOL GOODS RETURN -->
                 <?php if ($header->base_on == 'Goods Return'): ?>
                 <button type="button" class="btn btn-warning btn-sm mr-1" id="btnLoadGoodsReturn">
                     <i class="fas fa-undo mr-1"></i> Pilih & Load Goods Return
                 </button>
                 <?php endif; ?>

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
                            <th><?= lang_line('warehouse_in_unit', 'Satuan'); ?></th> <th><?= lang_line('warehouse_in_receive_qty', 'Qty Terima'); ?></th> <th><?= lang_line('warehouse_in_unit_value', 'Nilai Satuan (IDR)'); ?></th> <th><?= lang_line('warehouse_in_total_value', 'Total Nilai (IDR)'); ?></th> <th><?= lang_line('warehouse_in_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if(!empty($details)): $no = 1; $grand_total_value = 0; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->source_doc_number); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td><?= htmlspecialchars($d->satuan ?? '-'); ?></td>
                            <td class="text-right"><?= number_format($d->receive_qty, 2); ?></td>
                            <td class="text-right"><?= number_format($d->unit_value ?? 0, 2); ?></td>
                            <td class="text-right"><?= number_format($d->total_value ?? 0, 2); ?></td>
                            <td class="text-center">
                                <?php if ($header->status == 'Draft' && check_access('warehouse_in', 'edit')): ?>
                                    <?php
                                        // Samakan dengan controller: hanya role Admin (lihat Warehouse_in::update_mir_item).
                                        $is_admin = is_admin();
                                    ?>
                                    <?php if ($header->base_on == 'MIR'): ?>
                                    <!-- Hanya Admin yang bisa edit dan delete MIR items -->
                                    <?php if ($is_admin): ?>
                                    <button type="button" class="btn btn-warning btn-xs btn-sm mr-1" data-toggle="modal" data-target="#editMirItemModal"
                                            data-detail-id="<?= $d->id; ?>" data-receive-qty="<?= $d->receive_qty; ?>" data-unit-value="<?= $d->unit_value; ?>"
                                            data-item-name="<?= htmlspecialchars($d->item_name); ?>" data-is-admin="1">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="<?= base_url('warehouse_in/delete_item/'.$d->grn_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete"> <i class="fas fa-trash"></i> </a>
                                    <?php else: ?>
                                    <!-- Non-admin: Tidak ada button -->
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <!-- Item PO: edit qty (dibatasi sisa PO) + data BC per baris -->
                                    <button type="button" class="btn btn-warning btn-xs btn-sm mr-1" data-toggle="modal" data-target="#editQtyOnlyModal"
                                            data-detail-id="<?= $d->id; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="<?= base_url('warehouse_in/delete_item/'.$d->grn_header_id.'/'.$d->id); ?>" class="btn btn-danger btn-xs btn-delete"> <i class="fas fa-trash"></i> </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $grand_total_value += ($d->total_value ?? 0); endforeach; ?>
                         <tr class="bg-light font-weight-bold">
                            <td colspan="7" class="text-right"><?= lang_line('warehouse_in_grand_total', 'Grand Total Nilai (IDR)'); ?></td>
                            <td class="text-right"><?= number_format($grand_total_value, 2); ?></td> <td></td>
                        </tr>
                        <?php else: ?> <tr><td colspan="9" class="text-center text-muted py-5"><?= lang_line('warehouse_in_no_items', 'Belum ada item yang diterima'); ?></td></tr>
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

<!-- Modal MIR Auto-Load (untuk status Draft dengan base_on = MIR) - NOW SUPPORTS MULTI-MIR -->
<?php if ($header->base_on == 'MIR' && $header->status == 'Draft' && empty($details)): ?>
<div class="modal fade" id="mirAutoLoadModal" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i>Pilih MIR untuk Auto-Load Items (Bisa Pilih Beberapa)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 1;">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Pilih satu atau lebih MIR</strong> untuk secara otomatis memuat semua item yang diminta. Anda tinggal mengonfirmasi penerimaan tanpa perlu input manual item satu per satu.</p>
                
                <div class="form-group">
                    <div class="input-group mb-3">
                        <input type="text" id="mirSearchInput" class="form-control" placeholder="Cari MIR nomor...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="mirSearchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="mirSelectAllBtn">
                        <i class="fas fa-check-square"></i> Pilih Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="mirDeselectAllBtn">
                        <i class="fas fa-square"></i> Batal Pilih Semua
                    </button>
                </div>

                <!-- MIR Selection Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-3" id="mirSelectionTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="mirSelectAllCheckbox" class="form-check-input"></th>
                                <th>Nomor MIR</th>
                                <th>Tanggal</th>
                                <th class="text-center">Item</th>
                            </tr>
                        </thead>
                        <tbody id="mirListBody">
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat data MIR...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Selected Summary -->
                <div class="alert alert-info d-none" id="mirSelectionSummary" role="alert">
                    <strong>Total dipilih:</strong> <span id="mirSelectedCount">0</span> MIR | 
                    <span id="mirTotalItemCount">0</span> item
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info btn-load" id="mirAutoLoadBtn">
                    <i class="fas fa-check mr-1"></i> Load Items dari MIR Terpilih
                </button>
                <button type="button" class="btn btn-secondary" id="mirSkipBtn">Skip untuk Sekarang</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal PO Auto-Load (untuk Base On = PO) -->
<?php if ($header->base_on == 'PO' && $header->status == 'Draft' && check_access('warehouse_in','edit')): ?>
<!-- Modal Pilih Item PO. User 2026-09-14: bisa pilih item & qty tertentu
     (penerimaan partial), dan tiap baris menampilkan status penerimaannya. -->
<div class="modal fade" id="poAutoLoadModal" tabindex="-1" role="dialog" aria-labelledby="poAutoLoadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="poAutoLoadModalLabel">
                    <i class="fas fa-file-import mr-2"></i>Pilih Item PO yang Diterima
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem;">
                    <div class="input-group" style="max-width:360px;">
                        <input type="text" id="poSearchInput" class="form-control form-control-sm" placeholder="Cari nomor PO atau supplier...">
                        <div class="input-group-append">
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="poSearchBtn"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="custom-control custom-switch ml-2">
                        <input type="checkbox" class="custom-control-input" id="poShowDone">
                        <label class="custom-control-label small" for="poShowDone">Tampilkan PO yang sudah diterima penuh</label>
                    </div>
                </div>
                <p class="small text-muted mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Centang item yang benar-benar datang, lalu sesuaikan qty-nya. Qty otomatis diisi sisa yang belum diterima dan tidak bisa melebihinya.
                    Barang Inventory dan Mesin ditampilkan sebagai informasi; penerimaannya lewat Receiving Item Form.
                </p>

                <div id="poErrorBox" class="alert alert-danger py-2 small d-none"></div>
                <div id="poListBody">
                    <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data PO...</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="small text-muted" id="poSelectionSummary">Belum ada item dipilih.</div>
                <div>
                    <button type="button" class="btn btn-secondary" id="poSkipBtn">Batal</button>
                    <button type="button" class="btn btn-success" id="poAutoLoadBtn" disabled>
                        <i class="fas fa-check mr-1"></i> Muat Item Terpilih
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Edit MIR Item (qty and/or unit_value) -->
<div class="modal fade" id="editMirItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editMirItemForm" method="post">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Item MIR</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editDetailId" name="detail_id" value="">
                    <input type="hidden" id="editIsAdmin" value="">
                    <p><strong>Item:</strong> <span id="editItemName"></span></p>
                    <div class="form-group" id="editQtyGroup">
                        <label><strong>Quantity Diterima</strong> <small class="text-muted">(Hanya Admin)</small></label>
                        <input type="number" step="any" id="editReceiveQty" name="receive_qty" class="form-control" required min="0">
                    </div>
                    <div class="form-group">
                        <label><strong>Nilai Satuan (IDR)</strong></label>
                        <input type="text" id="editUnitValue" name="unit_value" class="form-control autonumeric-edit text-right" value="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Item PO. User 2026-09-14: bisa edit qty "dll". Qty dibatasi
     sisa PO (dihitung server), data BC bisa diubah per baris, nilai satuan
     tetap mengikuti harga PO. -->
<div class="modal fade" id="editQtyOnlyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="editQtyOnlyForm" method="post" novalidate>
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Item Penerimaan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editQtyDetailId" name="detail_id" value="">
                    <div id="editItemLoading" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data item...</div>
                    <div id="editItemBody" class="d-none">
                        <div id="editItemError" class="alert alert-danger py-2 small d-none"></div>

                        <div class="mb-3">
                            <div class="small text-muted">PO <span id="editItemPo" class="font-weight-bold text-dark"></span> · <span id="editItemType"></span></div>
                            <div class="h6 mb-0"><span id="editItemCode" class="text-primary"></span> <span id="editQtyItemName"></span></div>
                        </div>

                        <div class="form-row text-center mb-3">
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2"><div class="small text-muted">Qty PO</div><div class="font-weight-bold" id="editStatPo">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2"><div class="small text-muted">Sudah diterima</div><div class="font-weight-bold" id="editStatReceived">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2" id="editStatDraftBox"><div class="small text-muted">Di draft lain</div><div class="font-weight-bold" id="editStatDraft">-</div></div></div>
                            <div class="col-6 col-md-3 mb-2"><div class="border rounded py-2 border-success"><div class="small text-muted">Maksimum baris ini</div><div class="font-weight-bold text-success" id="editStatMax">-</div></div></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="editQtyValue" class="font-weight-bold">Qty Diterima <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0.01" id="editQtyValue" name="receive_qty" class="form-control text-right" required>
                                    <div class="input-group-append"><span class="input-group-text" id="editItemUnit"></span></div>
                                    <div class="invalid-feedback" id="editQtyFeedback"></div>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Nilai Satuan (IDR)</label>
                                <input type="text" id="editQtyUnitValue" class="form-control text-right" disabled>
                                <small class="text-muted">Mengikuti harga di PO.</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="font-weight-bold">Total Nilai (IDR)</label>
                                <input type="text" id="editQtyTotalValue" class="form-control text-right" disabled>
                                <small class="text-muted">Dihitung otomatis.</small>
                            </div>
                        </div>

                        <div class="small font-weight-bold text-muted mb-2"><i class="fas fa-file-contract mr-1"></i> Data Kepabeanan (BC) baris ini</div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="editItemBcType">Tipe BC</label>
                                <select id="editItemBcType" name="bc_type_code" class="form-control">
                                    <option value="">-- Tanpa BC --</option>
                                    <?php foreach (($bc_types ?? []) as $bc): ?>
                                        <option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="editItemBcNumber">Nomor BC</label>
                                <input type="text" id="editItemBcNumber" name="bc_number" class="form-control" maxlength="50">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="editItemBcDate">Tanggal BC</label>
                                <input type="date" id="editItemBcDate" name="bc_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" id="editItemSave" disabled><i class="fas fa-save mr-1"></i> Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Qty (only for MIR items and admin users) -->
<div class="modal fade" id="editQtyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editQtyForm" method="post">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Quantity Penerimaan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editDetailId2" name="detail_id" value="">
                    <p><strong>Item:</strong> <span id="editItemName2"></span></p>
                    <div class="form-group">
                        <label><strong>Quantity Diterima</strong></label>
                        <input type="number" step="any" id="editReceiveQty2" name="receive_qty" class="form-control" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
    const grn_id = '<?= $header->id; ?>';
    const grnId = grn_id;  // Alias untuk compatibility

    // Show MIR Auto-Load Modal if base_on is MIR and no items yet
    <?php if ($header->base_on == 'MIR' && $header->status == 'Draft' && empty($details)): ?>
    setTimeout(function() {
        $('#mirAutoLoadModal').modal('show');
    }, 500);
    <?php endif; ?>

    // Show PO Auto-Load Modal if base_on is PO and no items yet
    <?php if ($header->base_on == 'PO' && $header->status == 'Draft' && empty($details)): ?>
    setTimeout(function() {
        $('#poAutoLoadModal').modal('show');
    }, 500);
    <?php endif; ?>

    // Initialize MIR Selector for Auto-Load
    if ($('#mir_selector').length) {
        $('#mir_selector').select2({
            placeholder: '-- Pilih MIR --',
            minimumInputLength: 1,
            ajax: {
                url: '<?= base_url("warehouse_in/search_source_docs"); ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, type: 'MIR' };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
            },
            dropdownParent: $('#mirAutoLoadModal')
        });

        // Handle Auto-Load button
        $('#mirAutoLoadBtn').on('click', function() {
            const mir_id = $('#mir_selector').val();
            if (!mir_id) {
                alert('Silakan pilih MIR terlebih dahulu.');
                return;
            }
            // Redirect to auto_load_mir_items endpoint
            window.location.href = '<?= base_url("warehouse_in/auto_load_mir_items/"); ?>' + grn_id + '/' + mir_id;
        });

        // Handle Skip button
        $('#mirSkipBtn').on('click', function() {
            $('#mirAutoLoadModal').modal('hide');
        });
    }

    // Handle Edit MIR Item Modal
    $('#editMirItemModal').on('show.bs.modal', function(event) {
        const btn = $(event.relatedTarget);
        const detail_id = btn.data('detail-id');
        const receive_qty = btn.data('receive-qty');
        const unit_value = btn.data('unit-value');
        const item_name = btn.data('item-name');
        const is_admin = btn.data('is-admin') == '1';

        $('#editDetailId').val(detail_id);
        $('#editItemName').text(item_name);
        $('#editReceiveQty').val(receive_qty);
        $('#editUnitValue').val(unit_value);
        $('#editIsAdmin').val(is_admin ? '1' : '0');

        // Show/hide quantity field based on admin status
        if (is_admin) {
            $('#editQtyGroup').show();
            $('#editReceiveQty').prop('required', true);
        } else {
            $('#editQtyGroup').hide();
            $('#editReceiveQty').prop('required', false);
        }

        // Reinitialize AutoNumeric for the edit unit value field
        if (typeof anEditUnitValue !== 'undefined') {
            anEditUnitValue.set(unit_value);
        }
    });

    // Handle Edit MIR Item Form Submission
    $('#editMirItemForm').on('submit', function(e) {
        e.preventDefault();
        const detail_id = $('#editDetailId').val();
        const is_admin = $('#editIsAdmin').val() == '1';
        const new_qty = is_admin ? $('#editReceiveQty').val() : null;
        const new_unit_value = $('#editUnitValue').val().replace(/,/g, ''); // Remove commas from formatted number

        // Choose endpoint based on admin status
        const endpoint = is_admin ?
            '<?= base_url("warehouse_in/update_mir_item"); ?>' :
            '<?= base_url("warehouse_in/update_mir_unit_value"); ?>';

        const data = is_admin ?
            { detail_id: detail_id, receive_qty: new_qty, unit_value: new_unit_value } :
            { detail_id: detail_id, unit_value: new_unit_value };

        // Send AJAX to update the item
        $.post(endpoint, data, function(response) {
            if (response.status === 'success') {
                alert('Item berhasil diperbarui. Silakan refresh halaman.');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Gagal mengirim request.');
        });
    });

    // ====== EDIT ITEM PO ======
    // Data diambil segar dari server tiap modal dibuka, jadi batas qty selalu
    // mencerminkan GRN lain yang mungkin baru dikonfirmasi.
    (function () {
        const esc  = v => $('<div>').text(v == null ? '' : String(v)).html();
        const num  = v => Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
        const idr  = v => Number(v || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const TYPE = { material: 'Material', semifinished: 'Semi-finished', inventory: 'Inventory', machine: 'Mesin' };
        let unitValue = 0, maxQty = 0;

        // Total dihitung dari angka mentah, BUKAN dari teks yang sudah diformat.
        // Versi lama membaca ulang "1.500,00" dari kolom lalu membuang karakter
        // selain angka & titik -> jadi 1.50000 -> total salah jauh.
        function recalc() {
            const $q = $('#editQtyValue');
            const v = parseFloat($q.val());
            let msg = '';
            if (isNaN(v) || v <= 0) msg = 'Qty harus lebih dari 0.';
            else if (v > maxQty + 0.0001) msg = 'Melebihi maksimum ' + num(maxQty) + '.';
            $q.toggleClass('is-invalid', !!msg);
            $('#editQtyFeedback').text(msg);
            $('#editQtyTotalValue').val(idr((isNaN(v) ? 0 : v) * unitValue));
            $('#editItemSave').prop('disabled', !!msg);
        }

        $('#editQtyOnlyModal').on('show.bs.modal', function (event) {
            const id = $(event.relatedTarget).data('detail-id');
            $('#editQtyDetailId').val(id);
            $('#editItemBody, #editItemError').addClass('d-none');
            $('#editItemLoading').removeClass('d-none');
            $('#editItemSave').prop('disabled', true);

            $.getJSON('<?= base_url("warehouse_in/get_po_item_detail"); ?>', { detail_id: id }).done(function (r) {
                $('#editItemLoading').addClass('d-none');
                if (r.status !== 'success') {
                    $('#editItemBody').removeClass('d-none');
                    $('#editItemError').text(r.message || 'Gagal memuat item.').removeClass('d-none');
                    return;
                }
                const d = r.data;
                unitValue = Number(d.unit_value) || 0;
                maxQty    = Number(d.max_qty) || 0;
                $('#editItemPo').text(d.po_number || '-');
                $('#editItemType').text(TYPE[d.item_type] || d.item_type);
                $('#editItemCode').text(d.item_code || '');
                $('#editQtyItemName').text(d.item_name || '');
                $('#editItemUnit').text(d.unit || '');
                $('#editStatPo').text(num(d.qty_po));
                $('#editStatReceived').text(num(d.qty_received));
                $('#editStatDraft').text(num(d.qty_draft_other));
                $('#editStatDraftBox').attr('title', d.draft_other_numbers ? 'Tertahan di: ' + d.draft_other_numbers : '')
                                      .toggleClass('border-warning', d.qty_draft_other > 0);
                $('#editStatMax').text(num(maxQty));
                $('#editQtyValue').attr('max', maxQty).val(d.receive_qty);
                $('#editQtyUnitValue').val(idr(unitValue));
                $('#editItemBcType').val(d.bc_type_code || '');
                $('#editItemBcNumber').val(d.bc_number || '');
                $('#editItemBcDate').val(d.bc_date || '');
                $('#editItemBody').removeClass('d-none');
                recalc();
            }).fail(function () {
                $('#editItemLoading').addClass('d-none');
                $('#editItemBody').removeClass('d-none');
                $('#editItemError').text('Gagal menghubungi server.').removeClass('d-none');
            });
        });

        $('#editQtyValue').on('input', recalc);

        $('#editQtyOnlyForm').on('submit', function (e) {
            e.preventDefault();
            recalc();
            if ($('#editItemSave').prop('disabled')) return;
            const $btn = $('#editItemSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            $.post('<?= base_url("warehouse_in/update_po_item"); ?>', {
                detail_id:    $('#editQtyDetailId').val(),
                receive_qty:  $('#editQtyValue').val(),
                bc_type_code: $('#editItemBcType').val(),
                bc_number:    $('#editItemBcNumber').val(),
                bc_date:      $('#editItemBcDate').val()
            }, null, 'json').done(function (r) {
                if (r.status === 'success') { location.reload(); return; }
                $('#editItemError').text(r.message || 'Gagal menyimpan.').removeClass('d-none');
                if (r.max !== undefined) { maxQty = Number(r.max); $('#editStatMax').text(num(maxQty)); $('#editQtyValue').attr('max', maxQty); }
            }).fail(function () {
                $('#editItemError').text('Gagal menghubungi server.').removeClass('d-none');
            }).always(function () {
                $btn.html('<i class="fas fa-save mr-1"></i> Simpan');
                recalc();
            });
        });
    })();

    // Inisialisasi hanya jika status Draft
    if (currentStatus === 'Draft') {
        // Inisialisasi AutoNumeric hanya jika elemennya ada
        if ($('input[name="unit_value_mir"]').length) {
            const anMirValue = new AutoNumeric('input[name="unit_value_mir"]', { decimalPlaces: 2 });
             if (baseOnType === 'MIR') { anMirValue.set(0); } // Set default jika MIR
        }
        
        // Initialize AutoNumeric for edit unit value field
        if ($('#editUnitValue').length) {
            window.anEditUnitValue = new AutoNumeric('#editUnitValue', { decimalPlaces: 2 });
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

        // ====== MULTI-MIR SELECTION FEATURE ======
        if ($('#mirAutoLoadModal').length) {
            let mirList = []; // Store MIR data

            // Load available MIR numbers
            function loadMirList(searchTerm = '') {
                $.ajax({
                    url: '<?= base_url("warehouse_in/get_available_mir_numbers"); ?>',
                    type: 'GET',
                    data: { grn_id: grnId, q: searchTerm },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            mirList = response.data;
                            renderMirTable();
                            if (mirList.length === 0) {
                                $('#mirListBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada MIR yang tersedia</td></tr>');
                            }
                        } else {
                            $('#mirListBody').html('<tr><td colspan="4" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
                        }
                    },
                    error: function() {
                        $('#mirListBody').html('<tr><td colspan="4" class="text-center text-danger">Gagal memuat data MIR</td></tr>');
                    }
                });
            }

            // Render MIR table
            function renderMirTable() {
                let html = '';
                mirList.forEach(function(mir) {
                    html += '<tr class="mir-row" data-mir-id="' + mir.mir_id + '">';
                    html += '<td><input type="checkbox" class="form-check-input mir-checkbox" value="' + mir.mir_id + '"></td>';
                    html += '<td><strong>' + mir.mir_number + '</strong></td>';
                    html += '<td>' + new Date(mir.mir_date).toLocaleDateString('id-ID') + '</td>';
                    html += '<td class="text-center"><span class="badge badge-info">' + mir.available_items + '</span></td>';
                    html += '</tr>';
                });
                $('#mirListBody').html(html);
                
                // Attach checkbox handlers
                $('.mir-checkbox').on('change', updateMirSelection);
                $('#mirSelectAllCheckbox').off('change').on('change', toggleSelectAllMir);
            }

            // Update selection summary and buttons state
            function updateMirSelection() {
                const selected = $('.mir-checkbox:checked').length;
                const totalItems = mirList.reduce(function(sum, mir) {
                    return sum + ($(`.mir-checkbox[value="${mir.mir_id}"]:checked`).length > 0 ? mir.available_items : 0);
                }, 0);

                if (selected > 0) {
                    $('#mirSelectionSummary').removeClass('d-none');
                    $('#mirSelectedCount').text(selected);
                    $('#mirTotalItemCount').text(totalItems);
                    $('#mirAutoLoadBtn').prop('disabled', false);
                } else {
                    $('#mirSelectionSummary').addClass('d-none');
                    $('#mirAutoLoadBtn').prop('disabled', true);
                }

                // Update master checkbox state
                const allChecked = $('.mir-checkbox').length > 0 && $('.mir-checkbox:checked').length === $('.mir-checkbox').length;
                $('#mirSelectAllCheckbox').prop('checked', allChecked);
            }

            // Toggle select all
            function toggleSelectAllMir() {
                const isChecked = $('#mirSelectAllCheckbox').is(':checked');
                $('.mir-checkbox').prop('checked', isChecked);
                updateMirSelection();
            }

            // Search MIR
            $('#mirSearchBtn, #mirSearchInput').on('keypress', function(e) {
                if (e.type === 'click' || e.keyCode === 13) {
                    e.preventDefault();
                    loadMirList($('#mirSearchInput').val());
                }
            });

            // Select All / Deselect All buttons
            $('#mirSelectAllBtn').on('click', function() {
                $('.mir-checkbox').prop('checked', true);
                $('#mirSelectAllCheckbox').prop('checked', true);
                updateMirSelection();
            });

            $('#mirDeselectAllBtn').on('click', function() {
                $('.mir-checkbox').prop('checked', false);
                $('#mirSelectAllCheckbox').prop('checked', false);
                updateMirSelection();
            });

            // Load Items from Selected MIR
            $('#mirAutoLoadBtn').on('click', function() {
                const selectedMirIds = [];
                $('.mir-checkbox:checked').each(function() {
                    selectedMirIds.push($(this).val());
                });

                if (selectedMirIds.length === 0) {
                    alert('Silakan pilih minimal 1 MIR');
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Loading...');

                $.ajax({
                    url: '<?= base_url("warehouse_in/auto_load_multiple_mir_items"); ?>',
                    type: 'POST',
                    data: { grn_id: grnId, mir_ids: selectedMirIds },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Berhasil! ' + response.message);
                            location.reload();
                        } else if (response.status === 'warning') {
                            alert('Warning: ' + response.message);
                            if (response.error_mirs.length > 0) {
                                alert('Errors:\n- ' + response.error_mirs.join('\n- '));
                            }
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Gagal mengirim request ke server');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Load Items dari MIR Terpilih');
                    }
                });
            });

            // Skip button
            $('#mirSkipBtn').on('click', function() {
                $('#mirAutoLoadModal').modal('hide');
            });

            // Initialize: Load MIR list when modal is shown
            $('#mirAutoLoadModal').on('show.bs.modal', function() {
                if (mirList.length === 0) {
                    loadMirList();
                }
            });

            // Disable load button initially
            $('#mirAutoLoadBtn').prop('disabled', true);
        }

        // ====== PILIH ITEM PO (partial item & qty) ======
        // User 2026-09-14. Data & batas qty dihitung server di
        // Warehouse_in::get_available_po_numbers(); simpan lewat
        // load_selected_po_items() yang memvalidasi ulang semuanya.
        if ($('#poAutoLoadModal').length) {
            let poData = [];
            const picked = {};   // po_detail_id -> qty

            const STATUS = {
                not_received:  ['secondary', 'Belum diterima'],
                partial:       ['info',      'Diterima sebagian'],
                done:          ['success',   'Sudah diterima penuh'],
                in_this_grn:   ['primary',   'Sudah di GRN ini'],
                held_in_draft: ['warning',   'Tertahan di draft lain'],
                other_module:  ['light border', 'Lewat Receiving Item Form']
            };
            const TYPE = { material: 'Material', semifinished: 'Semi-finished', inventory: 'Inventory', machine: 'Mesin' };

            const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
            const num = v => Number(v || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            function loadPoList(q = '') {
                $('#poErrorBox').addClass('d-none').empty();
                $('#poListBody').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data PO...</div>');
                $.ajax({
                    url: '<?= base_url("warehouse_in/get_available_po_numbers"); ?>',
                    data: { grn_id: grnId, q: q }, dataType: 'json'
                }).done(function (r) {
                    if (r.status !== 'success') { $('#poListBody').html('<div class="text-danger text-center py-3">' + esc(r.message) + '</div>'); return; }
                    poData = r.data || [];
                    render();
                }).fail(function () {
                    $('#poListBody').html('<div class="text-danger text-center py-3">Gagal memuat data PO.</div>');
                });
            }

            function render() {
                const showDone = $('#poShowDone').is(':checked');
                const list = poData.filter(po => showDone || !po.fully_received);
                if (!list.length) {
                    $('#poListBody').html('<div class="text-center text-muted py-4">' +
                        (poData.length ? 'Semua PO sudah diterima penuh. Aktifkan tombol di atas untuk melihatnya.' : 'Tidak ada PO material yang siap diterima.') + '</div>');
                    updateSummary(); return;
                }

                let html = '';
                list.forEach(function (po) {
                    const canPick = po.receivable_lines > 0;
                    html += '<div class="card mb-2 po-card" data-po="' + po.po_id + '">'
                          + '<div class="card-header py-2 d-flex flex-wrap align-items-center" style="gap:.5rem;cursor:pointer" data-toggle="collapse" data-target="#poBody' + po.po_id + '">'
                          + '<input type="checkbox" class="po-all" ' + (canPick ? '' : 'disabled') + ' title="Pilih semua item yang bisa diterima di PO ini" onclick="event.stopPropagation()">'
                          + '<strong>' + esc(po.po_number) + '</strong>'
                          + '<span class="text-muted small">' + esc(po.supplier_name || '-') + ' · ' + esc(po.po_date || '-') + '</span>'
                          + '<span class="ml-auto"></span>'
                          + (canPick ? '<span class="badge badge-success">' + po.receivable_lines + ' item siap diterima</span>'
                                     : '<span class="badge badge-secondary">Tidak ada item tersisa</span>')
                          + (po.other_lines ? ' <span class="badge badge-light border" title="Diterima lewat Receiving Item Form">' + po.other_lines + ' item inventory/mesin</span>' : '')
                          + '<span class="small text-muted" style="min-width:120px">Diterima ' + po.progress + '%'
                          + '<div class="progress" style="height:5px"><div class="progress-bar bg-success" style="width:' + po.progress + '%"></div></div></span>'
                          + '</div>'
                          + '<div id="poBody' + po.po_id + '" class="collapse ' + (canPick ? 'show' : '') + '">'
                          + '<div class="table-responsive"><table class="table table-sm mb-0 small">'
                          + '<thead class="thead-light"><tr>'
                          + '<th width="30"></th><th>Barang</th><th>Tipe</th>'
                          + '<th class="text-right">Qty PO</th><th class="text-right">Sudah diterima</th>'
                          + '<th class="text-right">Di draft lain</th><th class="text-right">Di GRN ini</th>'
                          + '<th class="text-right">Sisa</th><th>Status</th><th width="120" class="text-right">Qty diterima sekarang</th>'
                          + '</tr></thead><tbody>';

                    po.lines.forEach(function (l) {
                        const st = STATUS[l.status_key] || ['secondary', l.status_key];
                        const on = picked[l.po_detail_id] !== undefined;
                        const draftTip = l.draft_other_numbers ? ' title="' + esc('Tertahan di: ' + l.draft_other_numbers) + '"' : '';
                        html += '<tr class="' + (l.receivable ? '' : 'text-muted') + '">'
                              + '<td><input type="checkbox" class="po-line" data-id="' + l.po_detail_id + '" data-max="' + l.qty_remaining + '" '
                              + (l.receivable ? '' : 'disabled') + (on ? ' checked' : '') + '></td>'
                              + '<td><div class="font-weight-bold">' + esc(l.item_code || '-') + '</div>' + esc(l.item_name || '-') + '</td>'
                              + '<td><span class="badge badge-light border">' + esc(TYPE[l.item_type] || l.item_type) + '</span></td>'
                              + '<td class="text-right">' + num(l.qty_po) + ' ' + esc(l.unit || '') + '</td>'
                              + '<td class="text-right">' + num(l.qty_received) + '</td>'
                              + '<td class="text-right"' + draftTip + '>' + num(l.qty_draft_other) + (l.qty_draft_other > 0 ? ' <i class="fas fa-info-circle text-warning"></i>' : '') + '</td>'
                              + '<td class="text-right">' + num(l.qty_this_grn) + '</td>'
                              + '<td class="text-right font-weight-bold">' + num(l.qty_remaining) + '</td>'
                              + '<td><span class="badge badge-' + st[0] + '">' + st[1] + '</span></td>'
                              + '<td class="text-right">' + (l.receivable
                                    ? '<input type="number" class="form-control form-control-sm text-right po-qty" data-id="' + l.po_detail_id + '" min="0.01" step="any" max="' + l.qty_remaining + '" value="' + (on ? picked[l.po_detail_id] : l.qty_remaining) + '" ' + (on ? '' : 'disabled') + '>'
                                    : '<span class="text-muted">-</span>')
                              + '</td></tr>';
                    });
                    html += '</tbody></table></div></div></div>';
                });
                $('#poListBody').html(html);
                syncPoChecks();
                updateSummary();
            }

            function syncPoChecks() {
                $('#poListBody .po-card').each(function () {
                    const lines = $(this).find('.po-line:not(:disabled)');
                    $(this).find('.po-all').prop('checked', lines.length > 0 && lines.filter(':checked').length === lines.length);
                });
            }

            function readQty($input) {
                const max = parseFloat($input.attr('max')) || 0;
                const v = parseFloat($input.val());
                const bad = isNaN(v) || v <= 0 || v > max + 0.0001;
                $input.toggleClass('is-invalid', bad);
                return bad ? null : v;
            }

            function updateSummary() {
                const ids = Object.keys(picked);
                let invalid = 0, total = 0;
                const poSet = {};
                ids.forEach(function (id) {
                    const $i = $('.po-qty[data-id="' + id + '"]');
                    const v = $i.length ? readQty($i) : null;
                    if (v === null) invalid++; else total += v;
                    const card = $i.closest('.po-card').data('po');
                    if (card) poSet[card] = true;
                });
                if (!ids.length) {
                    $('#poSelectionSummary').text('Belum ada item dipilih.');
                } else {
                    $('#poSelectionSummary').html('<strong>' + ids.length + '</strong> item dari <strong>' + Object.keys(poSet).length + '</strong> PO · total qty <strong>' + num(total) + '</strong>'
                        + (invalid ? ' · <span class="text-danger">' + invalid + ' qty tidak valid</span>' : ''));
                }
                $('#poAutoLoadBtn').prop('disabled', !ids.length || invalid > 0);
            }

            // Centang satu baris
            $(document).on('change', '#poListBody .po-line', function () {
                const id = $(this).data('id');
                const $q = $('.po-qty[data-id="' + id + '"]');
                if (this.checked) { picked[id] = parseFloat($q.val()) || 0; $q.prop('disabled', false).trigger('focus'); }
                else { delete picked[id]; $q.prop('disabled', true).removeClass('is-invalid').val($(this).data('max')); }
                syncPoChecks(); updateSummary();
            });

            // Centang semua baris yang bisa diterima di satu PO
            $(document).on('change', '#poListBody .po-all', function () {
                const on = this.checked;
                $(this).closest('.po-card').find('.po-line:not(:disabled)').each(function () {
                    if (this.checked !== on) { this.checked = on; $(this).trigger('change'); }
                });
            });

            // Ubah qty
            $(document).on('input', '#poListBody .po-qty', function () {
                const id = $(this).data('id');
                if (picked[id] !== undefined) picked[id] = parseFloat($(this).val()) || 0;
                updateSummary();
            });

            $('#poShowDone').on('change', render);
            $('#poSearchBtn').on('click', () => loadPoList($('#poSearchInput').val()));
            $('#poSearchInput').on('keypress', e => { if (e.which === 13) { e.preventDefault(); loadPoList($('#poSearchInput').val()); } });

            // Simpan
            $('#poAutoLoadBtn').on('click', function () {
                const items = Object.keys(picked).map(id => ({ po_detail_id: parseInt(id, 10), qty: picked[id] }));
                if (!items.length) return;
                const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat...');
                $('#poErrorBox').addClass('d-none').empty();
                $.ajax({
                    url: '<?= base_url("warehouse_in/load_selected_po_items"); ?>',
                    type: 'POST', dataType: 'json',
                    data: { grn_id: grnId, items: JSON.stringify(items) }
                }).done(function (r) {
                    if (r.status === 'success') { location.reload(); return; }
                    let msg = '<strong>' + esc(r.message || 'Gagal memuat item.') + '</strong>';
                    if (r.errors && r.errors.length) msg += '<ul class="mb-0 pl-3">' + r.errors.map(e => '<li>' + esc(e) + '</li>').join('') + '</ul>';
                    $('#poErrorBox').html(msg).removeClass('d-none');
                    $('#poAutoLoadModal .modal-body').scrollTop(0);
                }).fail(function () {
                    $('#poErrorBox').html('Gagal menghubungi server.').removeClass('d-none');
                }).always(function () {
                    $btn.html('<i class="fas fa-check mr-1"></i> Muat Item Terpilih');
                    updateSummary();
                });
            });

            $('#poSkipBtn').on('click', () => $('#poAutoLoadModal').modal('hide'));

            // Selalu ambil data segar saat modal dibuka: status penerimaan bisa
            // berubah karena GRN lain dikonfirmasi sementara halaman ini terbuka.
            $('#poAutoLoadModal').on('show.bs.modal', function () {
                Object.keys(picked).forEach(k => delete picked[k]);
                loadPoList($('#poSearchInput').val());
            });
        }

    } // End if currentStatus === 'Draft'
});
</script>
<!-- ===== GOODS RETURN MODAL ===== -->
<div class="modal fade" id="goodsReturnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-undo mr-2"></i>Pilih Goods Return (Retur Supplier)</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" id="grSearch" class="form-control" placeholder="Cari No. RTN, Supplier...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
                    <table class="table table-sm table-hover" id="grTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="30"></th>
                                <th>No. RTN</th>
                                <th class="text-center">Tipe</th>
                                <th>Supplier</th>
                                <th class="text-center">Tgl Return</th>
                                <th class="text-center">Total Items</th>
                                <th class="text-center">Total Qty Return</th>
                            </tr>
                        </thead>
                        <tbody id="grTableBody">
                            <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btnLoadGrConfirm">
                    <i class="fas fa-check"></i> Load Item dari Goods Return Terpilih
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var grn_id = <?= (int)$header->id ?>;
    var grData = [];

    function esc(s){ return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
    function fmt(n){ return (parseFloat(n)||0).toFixed(2); }

    var btnOpen = document.getElementById('btnLoadGoodsReturn');
    if (!btnOpen) return;

    btnOpen.addEventListener('click', function(){
        loadGrList('');
        $('#goodsReturnModal').modal('show');
    });

    function loadGrList(search) {
        document.getElementById('grTableBody').innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        fetch('<?= base_url('warehouse_in/get_available_goods_return') ?>?search=' + encodeURIComponent(search), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r){ return r.json(); })
        .then(function(j){
            if (j.status !== 'success') { document.getElementById('grTableBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">'+esc(j.message||'Error')+'</td></tr>'; return; }
            grData = j.data || [];
            renderGrTable(grData);
        }).catch(function(err){ console.error(err); document.getElementById('grTableBody').innerHTML = '<tr><td colspan="7" class="text-danger text-center">Gagal memuat data</td></tr>'; });
    }

    function typeBadge(baseOn) {
        if (baseOn === 'NG') return '<span class="badge badge-danger">NG QA</span>';
        if (baseOn === 'Production Return') return '<span class="badge badge-info">Production</span>';
        return '<span class="badge badge-secondary">'+esc(baseOn||'-')+'</span>';
    }

    function renderGrTable(rows) {
        var tbody = document.getElementById('grTableBody');
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada Goods Return yang tersedia</td></tr>'; return; }
        tbody.innerHTML = rows.map(function(r){
            return '<tr>'
                + '<td><input type="radio" name="grSelect" class="gr-radio" value="'+r.id+'"></td>'
                + '<td><strong>'+esc(r.out_number)+'</strong></td>'
                + '<td class="text-center">'+typeBadge(r.base_on)+'</td>'
                + '<td>'+esc(r.nama_supplier)+'</td>'
                + '<td class="text-center small">'+(r.out_date||'-')+'</td>'
                + '<td class="text-center">'+esc(r.total_items)+'</td>'
                + '<td class="text-center text-warning font-weight-bold">'+fmt(r.total_qty_return)+'</td>'
                + '</tr>';
        }).join('');
    }

    document.getElementById('grSearch').addEventListener('input', function(){
        var t = this.value.toLowerCase();
        document.querySelectorAll('#grTableBody tr').forEach(function(tr){
            tr.style.display = tr.textContent.toLowerCase().indexOf(t) === -1 ? 'none' : '';
        });
    });

    document.getElementById('btnLoadGrConfirm').addEventListener('click', function(){
        var sel = document.querySelector('.gr-radio:checked');
        if (!sel) { alert('Pilih satu Goods Return.'); return; }
        var wh_out_id = sel.value;

        var self = this;
        self.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        self.disabled = true;

        var fd = new FormData();
        fd.append('grn_id', grn_id);
        fd.append('wh_out_id', wh_out_id);

        fetch('<?= base_url('warehouse_in/auto_load_goods_return_items') ?>', {
            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r){ return r.json(); })
        .then(function(j){
            self.innerHTML = '<i class="fas fa-check"></i> Load Item';
            self.disabled = false;
            if (j.status === 'success') {
                $('#goodsReturnModal').modal('hide');
                alert(j.message);
                window.location.reload();
            } else {
                alert('Gagal: ' + (j.message || ''));
            }
        }).catch(function(err){ console.error(err); self.innerHTML = '<i class="fas fa-check"></i> Load Item'; self.disabled = false; alert('Error jaringan'); });
    });
})();
</script>

<style>
/* Style Select2 di dalam modal */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051; }
</style>