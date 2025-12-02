<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-dolly-flatbed text-primary mr-2"></i><?= lang_line('material_issuance_process', 'Proses Pengeluaran Material'); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
            <?php if($header->status == 'Issued' || $header->status == 'Received'): ?>
                <span class="badge badge-success ml-2"><?= htmlspecialchars($header->issuance_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php if ($header->status == 'Submitted' && check_access('material_issuance', 'edit')): ?>
                <button type="submit" form="issuanceForm" class="btn btn-primary btn-sm mr-1" id="btnConfirmIssuanceHead">
                    <i class="fas fa-check-circle fa-sm"></i> <?= lang_line('material_issuance_confirm', 'Confirm Issuance'); ?>
                </button>
            <?php elseif ($header->status == 'Issued' && check_access('material_issuance', 'edit')): ?>
                <a href="<?= base_url('material_issuance/reverse_issuance/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('<?= lang_line('material_issuance_reverse_confirm', 'Yakin ingin membatalkan pengeluaran ini? Stok akan dikembalikan.'); ?>')">
                    <i class="fas fa-undo fa-sm"></i> <?= lang_line('material_issuance_reverse', 'Batal Keluarkan (Un-issue)'); ?>
                </a>
            <?php endif; ?>
            <?php if (($header->status == 'Issued' || $header->status == 'Received') && check_access('material_issuance', 'view')): ?>
                <a href="<?= base_url('material_issuance/export_pdf/'.$header->id); ?>" class="btn btn-info btn-sm mr-1" target="_blank">
                    <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('material_issuance_export_pdf', 'Export PDF'); ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('material_issuance'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('material_issuance_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong><?= lang_line('material_issuance_mr_number', 'No. MR'); ?>:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_request_date', 'Tanggal'); ?>:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_status', 'Status'); ?>:</strong> 
                    <?php $color = 'warning';
                    if ($header->status == 'Issued') $color = 'success';
                    if ($header->status == 'Received') $color = 'primary'; ?>
                    <span class="badge badge-<?= $color; ?>"><?= $header->status; ?></span>
                </div>
            </div>
             <div class="row mt-2">
                <div class="col-md-4"><strong><?= lang_line('material_issuance_requester', 'Dibuat Oleh'); ?>:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_issued_by', 'Disiapkan Oleh'); ?>:</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                <div class="col-md-4"><strong><?= lang_line('material_issuance_received_by', 'Diterima Oleh'); ?>:</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <form id="issuanceForm" method="post" action="<?= base_url('material_issuance/process_issuance/'.$header->id); ?>">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('material_issuance_items_issuance', 'Item Pengeluaran'); ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="itemIssuanceTable">
                        <thead class="thead-light text-center">
                            <tr>
                                <th><?= lang_line('material_issuance_wo', 'WO'); ?></th>
                                <th><?= lang_line('material_issuance_item_code', 'Kode Item'); ?></th>
                                <th><?= lang_line('material_issuance_item_name', 'Nama Item'); ?></th>
                                <th><?= lang_line('material_issuance_unit', 'Satuan'); ?></th>
                                <th style="width:15%"><?= lang_line('material_issuance_qty_request', 'Qty Diminta'); ?></th>
                                <th style="width:15%"><?= lang_line('material_issuance_available_stock', 'Stok Tersedia'); ?></th>
                                <th style="width:15%"><?= lang_line('material_issuance_qty_issue', 'Qty Dikeluarkan'); ?></th>
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
                                
                                <td class="text-right stock-on-hand-cell">
                                    <?= number_format($d->total_available_stock, 4); ?>
                                </td>
                                <td>
                                    <?php if($header->status == 'Submitted'): ?>
                                    <input type="number" step="any" min="0"
                                           name="items[<?= $d->detail_id; ?>][qty]" 
                                           class="form-control form-control-sm text-right qty-issued" 
                                           value="<?= number_format($d->requested_qty, 4, '.', ''); // Default = Qty Diminta ?>" 
                                           data-maxstock="<?= $d->total_available_stock; // Ambil stok dari data ?>"
                                           data-requested="<?= $d->requested_qty; ?>"
                                           required> <?php else: ?>
                                        <p class="form-control-static text-right mb-0"><?= number_format($d->issued_qty ?? 0, 4); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5"><?= lang_line('material_issuance_no_items', 'Item MR tidak ditemukan'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($header->status == 'Submitted'): ?>
            <div class="card-footer text-right">
                <button type="submit" form="issuanceForm" class="btn btn-primary btn-load" id="btnConfirmIssuanceFoot">
                    <i class="fas fa-check-circle mr-1"></i> <?= lang_line('material_issuance_confirm_issuance', 'Konfirmasi Pengeluaran'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php $this->load->view('templates/footer'); // Panggil footer utama ?>

<script>
$(document).ready(function() {
    
    // 1. Fungsi untuk validasi Qty Input (Lebih Sederhana)
    function validateQty(input) {
        const currentVal = parseFloat(input.val() || 0);
        const maxStock = parseFloat(input.attr('data-maxstock') || 0); // Baca dari 'data-maxstock'
        
        let isValid = true;
        
        if (currentVal < 0) { 
            input.val(0); // Jangan input minus
            isValid = true; // Anggap valid
        } else if (currentVal > maxStock) {
            input.removeClass('is-valid').addClass('is-invalid'); // Tandai merah jika > stok
            isValid = false;
        } else {
            input.removeClass('is-invalid').addClass('is-valid'); // Tandai hijau
            isValid = true;
        }
        return isValid;
    }

    // 2. Validasi semua input saat halaman dimuat (jika status Submitted)
    if ('<?= $header->status; ?>' === 'Submitted') {
        let allValidOnLoad = true;
        $('.qty-issued').each(function() {
            if (!validateQty($(this))) {
                allValidOnLoad = false;
            }
        });
        // Nonaktifkan tombol simpan jika ada yg tidak valid saat load
        $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', !allValidOnLoad);
        if(!allValidOnLoad){
             alert('<?= lang_line('material_issuance_stock_warning', 'PERHATIAN: Beberapa item memiliki stok tersedia yang lebih kecil dari qty diminta. Qty dikeluarkan telah disesuaikan.'); ?>');
        }

        // 3. Tambahkan event listener 'input'
        $('#itemIssuanceTable').on('input', '.qty-issued', function() {
            let allValid = true;
            $('.qty-issued').each(function() {
                 // Panggil validateQty untuk setiap input saat ada perubahan
                 if (!validateQty($(this))) {
                     allValid = false;
                 }
            });
            // Aktifkan/nonaktifkan tombol simpan berdasarkan validitas semua input
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', !allValid);
        });
        
        // 4. Konfirmasi sebelum submit form
        $('#issuanceForm').on('submit', function(e) {
            let confirmationText = "<?= lang_line('material_issuance_confirm_submit', 'Yakin ingin mengkonfirmasi pengeluaran ini? Stok akan dipotong.'); ?>";
            let hasInvalid = false;
            
            $('.qty-issued').each(function() {
                 if ($(this).hasClass('is-invalid')) { // Cek kelas invalid
                     hasInvalid = true;
                 }
                 // Cek jika issue < request (Peringatan)
                 if (parseFloat($(this).val() || 0) < parseFloat($(this).data('requested') || 0)) {
                     confirmationText = "<?= lang_line('material_issuance_qty_less_warning', 'PERHATIAN: Ada Qty Dikeluarkan lebih kecil dari Qty Diminta. Yakin lanjut?'); ?>";
                 }
            });
            
            if(hasInvalid) {
                 alert('<?= lang_line('material_issuance_qty_exceed_error', 'Terdapat item dengan Qty Dikeluarkan melebihi Stok Tersedia. Harap perbaiki.'); ?>');
                 e.preventDefault(); // Hentikan submit
                 return false;
            }
            
            if (!confirm(confirmationText)) {
                 e.preventDefault(); // Hentikan submit jika user klik 'Cancel'
                 return false;
            }
            
            // Jika lolos, tambahkan spinner ke tombol
            $('#btnConfirmIssuanceHead, #btnConfirmIssuanceFoot').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> <?= lang_line('material_issuance_processing', 'Memproses...'); ?>');
        });
    }
});
</script>

<style>
/* Style validasi input */
.qty-issued.is-invalid { 
    border-color: #e74a3b; 
    background-image: none; /* Hilangkan ikon ceklis/silang bootstrap */
}
.qty-issued.is-valid { 
    border-color: #1cc88a; 
    background-image: none; /* Hilangkan ikon ceklis/silang bootstrap */
}
.btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>