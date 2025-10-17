<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-dolly-flatbed text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
            <?php if($header->status == 'Issued' || $header->status == 'Received'): ?>
                <span class="badge badge-success ml-2"><?= htmlspecialchars($header->issuance_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php if ($header->status == 'Submitted' && check_access('material_issuance', 'edit')): ?>
                 <button type="submit" form="issuanceForm" class="btn btn-primary btn-sm mr-1" id="btnConfirmIssuance">
                    <i class="fas fa-check-circle fa-sm"></i> Confirm Issuance
                 </button>
            <?php elseif ($header->status == 'Issued' && check_access('material_issuance', 'edit')): ?>
                 <a href="<?= base_url('material_issuance/reverse_issuance/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('Yakin ingin membatalkan pengeluaran ini? Stok akan dikembalikan.');">
                    <i class="fas fa-undo fa-sm"></i> Batal Keluarkan (Un-issue)
                 </a>
            <?php endif; ?>
            <a href="<?= base_url('material_issuance'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>No. MR:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong>Tanggal:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-<?= $header->status == 'Issued' ? 'success' : ($header->status == 'Received' ? 'primary' : 'warning'); ?>"><?= $header->status; ?></span></div>
             </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong>Dibuat Oleh:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Disiapkan Oleh:</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong>Diterima Oleh:</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
             </div>
        </div>
    </div>

    <form id="issuanceForm" method="post" action="<?= base_url('material_issuance/process_issuance/'.$header->id); ?>">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Item Pengeluaran</h6>
            </div>
            <div class="card-body">
                 <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="itemIssuanceTable">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>WO</th>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th>Satuan</th>
                                <th style="width:15%">Qty Diminta</th>
                                <th style="width:15%">Stok Tersedia</th>
                                <th style="width:15%">Qty Dikeluarkan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($details)): foreach($details as $d): ?>
                            <tr>
                                <td class="text-center"><?= htmlspecialchars($d->wo_number); ?></td>
                                <td><?= htmlspecialchars($d->item_code); ?></td>
                                <td><?= htmlspecialchars($d->item_name); ?></td>
                                <td class="text-center"><?= htmlspecialchars($d->item_unit); ?></td>
                                <td class="text-right qty-requested"><?= number_format($d->requested_qty, 4); ?></td>
                                
                                <td class="text-right stock-on-hand-cell" data-itemid="<?= $d->item_id; ?>" data-itemtype="<?= $d->item_type; ?>">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </td>
                                
                                <td>
                                    <?php if($header->status == 'Submitted'): ?>
                                    <input type="number" step="any" min="0"
                                           name="items[<?= $d->detail_id; ?>][qty]" 
                                           class="form-control form-control-sm text-right qty-issued" 
                                           value="<?= number_format($d->requested_qty, 4, '.', ''); ?>" 
                                           data-max="0"
                                           data-requested="<?= $d->requested_qty; ?>">
                                    <?php else: ?>
                                        <p class="form-control-static text-right mb-0"><?= number_format($d->issued_qty ?? 0, 4); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">Item MR tidak ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
             <?php if ($header->status == 'Submitted'): ?>
            <div class="card-footer text-right">
                 <button type="submit" form="issuanceForm" class="btn btn-primary btn-load" id="btnConfirmIssuance">
                    <i class="fas fa-check-circle mr-1"></i> Konfirmasi Pengeluaran
                 </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php $this->load->view('templates/footer'); ?>

<script>
$(document).ready(function() {
    
    // 1. Fungsi untuk mengambil stok via AJAX
    function fetchStock(cell) {
        const item_id = cell.data('itemid');
        const item_type = cell.data('itemtype');
        const inputField = cell.closest('tr').find('.qty-issued');
        const requestedQty = parseFloat(inputField.data('requested') || 0);

        $.ajax({
            url: '<?= base_url("material_issuance/ajax_get_stock"); ?>',
            type: 'GET',
            dataType: 'json',
            data: { item_id: item_id, item_type: item_type },
            success: function(response) {
                if(response.success) {
                    const stock = parseFloat(response.stock);
                    cell.html(stock.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4}));
                    inputField.attr('data-max', stock); 
                    if (requestedQty > stock) { inputField.val(stock); }
                    validateQty(inputField); 
                } else { cell.html('<span class="text-danger">Error</span>'); }
            },
            error: function() { cell.html('<span class="text-danger">Error</span>'); }
        });
    }

    // 2. Fungsi untuk validasi Qty Input
    function validateQty(input) {
        const currentVal = parseFloat(input.val() || 0);
        const maxStock = parseFloat(input.attr('data-max') || 0);
        if (currentVal > maxStock) { input.removeClass('is-valid').addClass('is-invalid'); }
        else if (currentVal < 0) { input.val(0); input.removeClass('is-valid').addClass('is-valid'); }
        else { input.removeClass('is-invalid').addClass('is-valid'); }
        
        let allValid = true;
        $('.qty-issued').each(function() {
            if ($(this).hasClass('is-invalid')) { allValid = false; }
        });
        $('#btnConfirmIssuance').prop('disabled', !allValid);
    }

    // 3. Panggil AJAX hanya jika status Submitted
    if ('<?= $header->status; ?>' === 'Submitted') {
        $('#itemIssuanceTable .stock-on-hand-cell').each(function() {
            fetchStock($(this));
        });

        $('#itemIssuanceTable').on('input', '.qty-issued', function() {
            validateQty($(this));
        });
        
        $('#issuanceForm').on('submit', function(e) {
            let confirmationText = "Yakin ingin mengkonfirmasi pengeluaran ini? Stok akan dipotong.";
            let hasInvalid = false;
            $('.qty-issued').each(function() {
                 validateQty($(this)); 
                 if ($(this).hasClass('is-invalid')) { hasInvalid = true; }
                 if (parseFloat($(this).val() || 0) < parseFloat($(this).data('requested') || 0)) {
                    confirmationText = "PERHATIAN: Ada Qty Dikeluarkan lebih kecil dari Qty Diminta. Yakin lanjut?";
                 }
            });
            if(hasInvalid) {
                alert('Terdapat item dengan Qty Dikeluarkan melebihi Stok Tersedia. Harap perbaiki.');
                e.preventDefault(); return false;
            }
            if (!confirm(confirmationText)) {
                e.preventDefault(); return false;
            }
        });
    }
});
</script>
<style>
.qty-issued.is-invalid { border-color: #e74a3b; background-color: #fdd; }
.qty-issued.is-valid { border-color: #1cc88a; }
.btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>