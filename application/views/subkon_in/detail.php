<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-box-open text-success mr-2"></i><?= $title; ?></h1>

    <div class="row mb-3">
        <div class="col-md-12 text-right">
            <a href="<?= base_url('subkon_in'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>
            <?php if($header->status == 'Draft'): ?>
                <a href="<?= base_url('subkon_in/confirm/' . $header->id); ?>" class="btn btn-success btn-confirm" data-message="Anda yakin ingin mengonfirmasi Penerimaan ini? Stok dan HPP Jasa akan dicatat.">
                    <i class="fas fa-check-circle mr-1"></i> Konfirmasi Penerimaan
                </a>
            <?php elseif($header->status == 'Completed'): ?>
                <a href="<?= base_url('subkon_in/unconfirm/' . $header->id); ?>" class="btn btn-warning btn-confirm" data-message="Anda yakin ingin membatalkan konfirmasi? Stok akan dikembalikan.">
                    <i class="fas fa-times-circle mr-1"></i> Batal Konfirmasi
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-info text-white">
            <h6 class="m-0 font-weight-bold">Informasi Penerimaan & BC</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>No. Penerimaan:</strong> <span class="badge badge-primary"><?= htmlspecialchars($header->subkon_in_no); ?></span></p>
                    <p class="mb-1"><strong>Supplier:</strong> <?= htmlspecialchars($header->nama_supplier); ?></p>
                    <p class="mb-1"><strong>Kontrak Ref:</strong> <?= htmlspecialchars($header->subkon_order_no); ?></p>
                    <p class="mb-1"><strong>Status:</strong> <span class="badge badge-<?= ($header->status == 'Completed' ? 'success' : 'secondary'); ?>"><?= $header->status; ?></span></p>
                </div>
                <div class="col-md-6 text-right">
                    <p class="mb-1"><strong>Jenis BC:</strong> <?= htmlspecialchars($header->bc_type_code); ?></p>
                    <p class="mb-1"><strong>No. BC:</strong> <?= htmlspecialchars($header->bc_number); ?></p>
                    <p class="mb-1"><strong>Tgl BC:</strong> <?= date('d M Y', strtotime($header->bc_date)); ?></p>
                    <p class="mb-1"><strong>Tgl Terima:</strong> <?= date('d M Y', strtotime($header->receipt_date)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Item Hasil yang Dipesan & Sisa</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-striped" style="font-size: 0.8rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Item Hasil</th>
                                    <th class="text-right">Sisa Terima</th>
                                    <th>Jasa/Pcs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $item_options = []; foreach($output_items as $item): $item_options[] = $item; ?>
                                    <tr class="<?= $item->remaining_qty <= 0 ? 'table-success' : ''; ?>">
                                        <td><?= htmlspecialchars($item->item_name); ?></td>
                                        <td class="text-right font-weight-bold"><?= number_format($item->remaining_qty, 2); ?></td>
                                        <td>Rp <?= number_format($item->jasa_per_pcs, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>

                    <?php if($header->status == 'Draft'): ?>
                    <h6 class="font-weight-bold text-primary">Tambah Item Diterima</h6>
                    <form action="<?= base_url('subkon_in/add_item/' . $header->id); ?>" method="POST">
                        <div class="form-group">
                            <label>Pilih Item Hasil</label>
                            <select id="output_id_selector" name="output_id" class="form-control" required>
                                <option value="">-- Pilih Item --</option>
                                <?php foreach($item_options as $item): ?>
                                    <?php if($item->remaining_qty > 0): ?>
                                    <option value="<?= $item->id; ?>" data-maxqty="<?= (float)$item->remaining_qty; ?>" data-itemname="<?= htmlspecialchars($item->item_name); ?>">
                                        <?= htmlspecialchars($item->item_name); ?> (Sisa: <?= number_format($item->remaining_qty, 2); ?>)
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Qty Diterima</label>
                            <input type="number" id="qty_received_input" name="qty_received" class="form-control" step="0.01" required>
                            <small class="text-danger" id="qty_warning"></small>
                        </div>
                        <button type="submit" class="btn btn-success btn-load btn-block" id="add_item_btn">Tambah Item</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info text-center">GRN Subkon sudah dikonfirmasi. Tidak bisa menambah/mengubah item.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Item yang Sudah Diterima (Dicatat)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" style="font-size: 0.9rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Item Diterima</th>
                                    <th class="text-right">Qty Terima</th>
                                    <th class="text-right">Jasa/Pcs</th>
                                    <th class="text-right">Total Nilai Jasa</th>
                                    <?php if($header->status == 'Draft'): ?><th class="text-center">Aksi</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detail_items)): ?>
                                    <tr><td colspan="6" class="text-center">Belum ada item yang dicatat di GRN ini.</td></tr>
                                <?php else: $no = 1; $grand_total_jasa = 0; foreach ($detail_items as $item): $grand_total_jasa += $item->total_value_jasa; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= htmlspecialchars($item->output_name); ?></td>
                                        <td class="text-right"><?= number_format($item->qty_received, 2); ?></td>
                                        <td class="text-right"><?= number_format($item->jasa_per_pcs, 2); ?></td>
                                        <td class="text-right font-weight-bold"><?= number_format($item->total_value_jasa, 2); ?></td>
                                        <?php if($header->status == 'Draft'): ?>
                                        <td class="text-center">
                                            <a href="<?= base_url('subkon_in/delete_item/' . $header->id . '/' . $item->id); ?>" class="btn btn-danger btn-sm btn-delete" data-message="Yakin ingin menghapus item ini?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light">
                                    <td colspan="4" class="text-right font-weight-bold">Total Nilai Jasa (HPP)</td>
                                    <td class="text-right font-weight-bold text-primary"><?= number_format($grand_total_jasa, 2); ?></td>
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
    
    // Inisialisasi Select2
    $('#output_id_selector').select2();

    // Event listener saat Qty diterima diubah
    $('#qty_received_input').on('keyup change', function() {
        validateQtyReceived();
    });
    
    // Event listener saat Item Hasil diganti
    $('#output_id_selector').on('change', function() {
        validateQtyReceived();
    });

    function validateQtyReceived() {
        var $selector = $('#output_id_selector');
        var $input = $('#qty_received_input');
        var $warning = $('#qty_warning');
        var $button = $('#add_item_btn');
        
        var selectedOption = $selector.find('option:selected');
        var maxQty = parseFloat(selectedOption.data('maxqty')) || 0;
        var qty = parseFloat($input.val()) || 0;
        var itemName = selectedOption.data('itemname') || 'Item';

        $warning.empty();
        $button.prop('disabled', false);

        if (qty > maxQty) {
            $warning.html('<strong>Error:</strong> Qty diterima (' + qty + ') melebihi sisa yang dipesan (' + maxQty.toFixed(2) + ').');
            $button.prop('disabled', true);
        } else if (qty <= 0 && $selector.val() !== '') {
            $warning.text('Qty diterima harus lebih dari nol.');
            $button.prop('disabled', true);
        }
    }

    // Konfirmasi (SweetAlert)
    $('.btn-confirm, .btn-delete').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var message = $(this).data('message');
        
        Swal.fire({
            title: 'Anda yakin?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Panggil validasi saat pertama kali load
    validateQtyReceived();
});
</script>