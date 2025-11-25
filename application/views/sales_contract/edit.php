<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?> - <?= html_escape($contract->sc_number); ?></h1>
        <a href="<?= base_url('sales_contract'); ?>" class="btn btn-secondary btn-sm shadow-sm">
             <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Daftar
        </a>
    </div>

    <!-- Tampilkan Flashdata Error jika ada -->
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Edit Sales Contract</h6>
        </div>
        <div class="card-body">
            <!-- Ganti action form ke method update -->
            <?= form_open('sales_contract/update/' . $contract->id); ?> 
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sc_number">Nomor SC</label>
                        <input type="text" class="form-control" id="sc_number" value="<?= html_escape($contract->sc_number); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="customer_id">Pelanggan <span class="text-danger">*</span></label>
                        <select class="form-control" id="customer_id" name="customer_id" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer->id; ?>" <?= ($contract->customer_id == $customer->id) ? 'selected' : ''; ?>>
                                    <?= html_escape($customer->nama_customer); ?> (<?= html_escape($customer->kode_customer); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fg_id">Style (Finished Good) <span class="text-danger">*</span></label>
                        <select class="form-control" id="fg_id" name="fg_id" required>
                            <option value="">-- Pilih Style --</option>
                            <?php foreach ($styles as $style): ?>
                                <option value="<?= $style->id; ?>" <?= ($contract->fg_id == $style->id) ? 'selected' : ''; ?>>
                                    <?= html_escape($style->nama_barang); ?> (<?= html_escape($style->kode_barang); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                     <div class="form-group">
                        <label for="contract_date">Tanggal Kontrak <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="contract_date" name="contract_date" value="<?= html_escape($contract->contract_date); ?>" required>
                    </div>

                     <div class="form-group">
                        <label for="lchd_date">LCHD (Last Cargo Handover Date) <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="lchd_date" name="lchd_date" value="<?= html_escape($contract->lchd_date); ?>" required>
                    </div>

                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="po_qty">PO Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="po_qty" name="po_qty" value="<?= html_escape($contract->po_qty); ?>" required min="1">
                    </div>

                    <div class="form-group">
                        <label for="currency_code">Mata Uang <span class="text-danger">*</span></label>
                        <select class="form-control" id="currency_code" name="currency_code" required>
                            <option value="">-- Pilih Mata Uang --</option>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?= $currency->code; ?>" <?= ($contract->currency_code == $currency->code) ? 'selected' : ''; ?>>
                                    <?= html_escape($currency->code); ?> - <?= html_escape($currency->description); ?> <!-- PASTIKAN INI ->description -->
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($currencies)): ?>
                            <small class="text-danger">Data mata uang (CURRENCY) tidak ditemukan di Common Code.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="unit_price">Harga Satuan <span class="text-danger">*</span></label>
                        <!-- Gunakan type="text" untuk format, validasi di backend -->
                        <input type="text" class="form-control text-right" id="unit_price" name="unit_price" value="<?= number_format($contract->unit_price, 2, '.', ','); ?>" required>
                    </div>

                     <div class="form-group">
                        <label for="total_price">Total Harga</label>
                        <input type="text" class="form-control text-right" id="total_price" value="<?= number_format($contract->total_price, 2, '.', ','); ?>" readonly>
                         <small class="form-text text-muted">Akan terhitung otomatis.</small>
                    </div>

                    <div class="form-group">
                        <label for="destination">Tujuan</label>
                        <input type="text" class="form-control" id="destination" name="destination" value="<?= html_escape($contract->destination); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <?php foreach ($status_list as $status): ?>
                                <option value="<?= $status; ?>" <?= ($contract->status == $status) ? 'selected' : ''; ?>>
                                    <?= $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <hr>

            <div class="text-right">
                <button type="submit" class="btn btn-primary shadow-sm">
                    <i class="fas fa-save fa-sm"></i> Simpan Perubahan
                </button>
                <a href="<?= base_url('sales_contract'); ?>" class="btn btn-secondary shadow-sm">
                    Batal
                </a>
            </div>

            <?= form_close(); ?>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- Script tambahan untuk kalkulasi total dan format angka -->
<script>
$(document).ready(function() {
    function formatNumber(num) {
        return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function calculateTotal() {
        let qty = parseInt($('#po_qty').val()) || 0;
        // Bersihkan format sebelum kalkulasi
        let priceStr = $('#unit_price').val().replace(/,/g, ''); 
        let price = parseFloat(priceStr) || 0;
        let total = qty * price;
        $('#total_price').val(formatNumber(total));
    }

    // Kalkulasi saat Qty atau Harga berubah
    $('#po_qty, #unit_price').on('input change', calculateTotal);

    // Format harga saat user selesai mengetik
    $('#unit_price').on('blur', function() {
        let priceStr = $(this).val().replace(/,/g, '');
        let price = parseFloat(priceStr) || 0;
        $(this).val(formatNumber(price)); // Tampilkan dengan format
    });

    // Panggil kalkulasi saat halaman load
    calculateTotal(); 
});
</script>
