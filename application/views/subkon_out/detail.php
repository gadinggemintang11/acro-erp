<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= lang_line('subkon_out_detail_title', 'Detail Pengeluaran Subkon'); ?>: <?= $header->subkon_out_no; ?></h1>

    <div class="row mb-3">
        <div class="col-md-12 text-right">
            <a href="<?= base_url('subkon_out'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('subkon_out_back', 'Kembali ke Daftar'); ?>
            </a>
            <?php if($header->status == 'Draft'): ?>
                <a href="<?= base_url('subkon_out/confirm/' . $header->id); ?>" class="btn btn-success btn-confirm" data-message="<?= lang_line('subkon_out_confirm_message', 'Anda yakin ingin mengonfirmasi Surat Jalan ini? Stok akan dipotong.'); ?>">
                    <i class="fas fa-check-circle mr-1"></i> <?= lang_line('subkon_out_confirm', 'Konfirmasi Pengeluaran'); ?>
                </a>
            <?php elseif($header->status == 'Confirmed'): ?>
                <a href="<?= base_url('subkon_out/unconfirm/' . $header->id); ?>" class="btn btn-warning btn-confirm" data-message="<?= lang_line('subkon_out_unconfirm_message', 'Anda yakin ingin membatalkan konfirmasi? Stok akan dikembalikan. (Hanya jika belum diterima subkon)'); ?>">
                    <i class="fas fa-times-circle mr-1"></i> <?= lang_line('subkon_out_unconfirm', 'Batal Konfirmasi'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-info text-white">
            <h6 class="m-0 font-weight-bold"><?= lang_line('subkon_out_do_info', 'Informasi Surat Jalan'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong><?= lang_line('subkon_out_do_number', 'No. Surat Jalan'); ?>:</strong> <span class="badge badge-primary"><?= htmlspecialchars($header->subkon_out_no); ?></span></p>
                    <p class="mb-1"><strong><?= lang_line('subkon_out_contract', 'No. Kontrak'); ?>:</strong> <?= htmlspecialchars($header->subkon_order_no); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong><?= lang_line('subkon_out_supplier', 'Supplier'); ?>:</strong> <?= htmlspecialchars($header->nama_supplier); ?></p>
                    <p class="mb-1"><strong><?= lang_line('subkon_out_shipping_date', 'Tgl Kirim'); ?>:</strong> <?= date('d M Y', strtotime($header->shipping_date)); ?></p>
                    <p class="mb-1"><strong><?= lang_line('subkon_out_status', 'Status'); ?>:</strong> <span class="badge badge-<?= ($header->status == 'Confirmed' ? 'success' : 'secondary'); ?>"><?= $header->status; ?></span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('subkon_out_bom_needed', 'Kebutuhan Material (BOM) Kontrak'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-striped" style="font-size: 0.8rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('subkon_out_item_name', 'Item (Material/SFG)'); ?></th>
                                    <th class="text-right"><?= lang_line('subkon_out_remaining_need', 'Sisa Butuh'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bom_needed as $bom): ?>
                                    <tr class="<?= $bom->sisa_butuh <= 0 ? 'table-success' : ''; ?>">
                                        <td><?= htmlspecialchars($bom->item_name); ?></td>
                                        <td class="text-right font-weight-bold"><?= number_format($bom->sisa_butuh, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>

                    <?php if($header->status == 'Draft'): ?>
                    <h6 class="font-weight-bold text-primary"><?= lang_line('subkon_out_add_item_title', 'Tambah Item Pengeluaran'); ?></h6>
                    <form action="<?= base_url('subkon_out/add_item/' . $header->id); ?>" method="POST">
                        <div class="form-group">
                            <label><?= lang_line('subkon_out_select_bom_item', 'Pilih Item dari BOM'); ?></label>
                            <select id="item_id_type_selector" name="item_id_type" class="form-control" required>
                                <option value=""><?= lang_line('subkon_out_select_item_placeholder', '-- Pilih Item --'); ?></option>
                                <?php foreach($bom_needed as $bom): ?>
                                    <?php // Hanya tampilkan item yang masih ada sisa kebutuhan ?>
                                    <?php if($bom->sisa_butuh > 0): ?> 
                                    <option value="<?= $bom->item_id . '|' . $bom->item_type; ?>" 
                                            data-type="<?= $bom->item_type; ?>" 
                                            data-itemid="<?= $bom->item_id; ?>">
                                        <?= htmlspecialchars($bom->item_name); ?> (<?= lang_line('subkon_out_remaining', 'Sisa'); ?>: <?= (float)$bom->sisa_butuh; ?>)
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="batch-selector-group" style="display: none;">
                            <label><?= lang_line('subkon_out_select_batch', 'Pilih Batch/Lot Material (Stok Gudang)'); ?></label>
                            <select id="stock_on_hand_id" name="stock_on_hand_id" class="form-control">
                                <option value=""><?= lang_line('subkon_out_loading_batch', 'Memuat batch...'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><?= lang_line('subkon_out_qty_ship', 'Qty Kirim'); ?></label>
                            <input type="number" name="qty_kirim" class="form-control" step="0.01" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-load btn-block"><?= lang_line('subkon_out_add_item', 'Tambah Item'); ?></button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info text-center"><?= lang_line('subkon_out_confirmed_alert', 'DO Subkon sudah dikonfirmasi. Tidak bisa menambah/mengubah item.'); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info"><?= lang_line('subkon_out_items_in_do', 'Item di Surat Jalan Ini'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" style="font-size: 0.9rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('subkon_out_no', 'No'); ?></th>
                                    <th><?= lang_line('subkon_out_item_code', 'Kode Item'); ?></th>
                                    <th><?= lang_line('subkon_out_item_full_name', 'Nama Item'); ?></th>
                                    <th class="text-right"><?= lang_line('subkon_out_qty_ship', 'Qty Kirim'); ?></th>
                                    <th class="text-right"><?= lang_line('subkon_out_unit_value', 'Nilai/Pcs (IDR)'); ?></th>
                                    <th class="text-right"><?= lang_line('subkon_out_total_value', 'Total Nilai (IDR)'); ?></th>
                                    <?php if($header->status == 'Draft'): ?><th class="text-center"><?= lang_line('subkon_out_actions', 'Aksi'); ?></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detail_items)): ?>
                                    <tr><td colspan="7" class="text-center"><?= lang_line('subkon_out_no_items', 'Belum ada item di Surat Jalan ini'); ?></td></tr>
                                <?php else: $no = 1; $grand_total = 0; foreach ($detail_items as $item): $grand_total += $item->total_value; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= htmlspecialchars($item->item_code); ?></td>
                                        <td><?= htmlspecialchars($item->item_name); ?></td>
                                        <td class="text-right"><?= number_format($item->qty_kirim, 2); ?></td>
                                        <td class="text-right"><?= number_format($item->unit_value, 2); ?></td>
                                        <td class="text-right font-weight-bold"><?= number_format($item->total_value, 2); ?></td>
                                        <?php if($header->status == 'Draft'): ?>
                                        <td class="text-center">
                                            <a href="<?= base_url('subkon_out/delete_item/' . $header->id . '/' . $item->id); ?>" class="btn btn-danger btn-sm btn-delete" data-message="<?= lang_line('subkon_out_delete_confirm', 'Yakin ingin menghapus item ini?'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light">
                                    <td colspan="5" class="text-right font-weight-bold"><?= lang_line('subkon_out_total_goods_value', 'Total Nilai Barang Keluar'); ?></td>
                                    <td class="text-right font-weight-bold"><?= number_format($grand_total, 2); ?></td>
                                    <?php if($header->status == 'Draft'): ?><td></td><?php endif; ?>
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

<script>
$(document).ready(function() {
    
    // Inisialisasi Select2 untuk dropdown item
    $('#item_id_type_selector').select2();

    // Event listener saat item diganti
    $('#item_id_type_selector').on('change', function() {
        var $selectedOption = $(this).find('option:selected');
        var itemType = $selectedOption.data('type');
        var itemId = $selectedOption.data('itemid');
        
        var $batchGroup = $('#batch-selector-group');
        var $batchSelect = $('#stock_on_hand_id');

        if (itemType === 'material') {
            $batchGroup.show();
            $batchSelect.prop('required', true).prop('disabled', true).html('<option value="">Memuat batch...</option>');
            
            // Panggil AJAX untuk ambil batch
            $.ajax({
                url: "<?= base_url('subkon_out/ajax_get_material_batches'); ?>",
                type: "GET",
                data: { item_id: itemId },
                dataType: "json",
                success: function(response) {
                    $batchSelect.prop('disabled', false).empty();
                    $batchSelect.append('<option value=""><?= lang_line('subkon_out_select_batch_placeholder', '-- Pilih Batch/Lot --'); ?></option>');
                    if (response.results && response.results.length > 0) {
                        response.results.forEach(function(batch) {
                            $batchSelect.append(
                                '<option value="' + batch.stock_on_hand_id + '">' +
                                '<?= lang_line('subkon_out_batch_grn', 'Batch/GRN'); ?>: ' + batch.grn_number + 
                                ' (<?= lang_line('subkon_out_available', 'Sisa'); ?>: ' + parseFloat(batch.available_qty) + ')' +
                                ' (<?= lang_line('subkon_out_cost', 'Cost'); ?>: ' + parseFloat(batch.unit_value) + ')' +
                                '</option>'
                            );
                        });
                    } else {
                        $batchSelect.html('<option value=""><?= lang_line('subkon_out_no_batch', '-- Stok batch tidak ditemukan --'); ?></option>');
                    }
                }
            });

        } else if (itemType === 'semifinished') {
            $batchGroup.hide();
            $batchSelect.prop('required', false).empty();
        } else {
            $batchGroup.hide();
            $batchSelect.prop('required', false).empty();
        }
    });

    // Konfirmasi (SweetAlert)
    $('.btn-confirm, .btn-delete').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var message = $(this).data('message');
        
        Swal.fire({
            title: '<?= lang_line('subkon_out_confirm_title', 'Anda yakin?'); ?>',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= lang_line('subkon_out_confirm_yes', 'Ya, Lanjutkan!'); ?>',
            cancelButtonText: '<?= lang_line('cancel', 'Batal'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

});
</script>