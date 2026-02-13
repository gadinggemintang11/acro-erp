<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('appayment/store'); ?>" method="POST" id="payment-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Pembayaran</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Pembayaran</label>
                            <input type="text" name="payment_no" class="form-control" value="<?= $payment_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tgl. Pembayaran *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Supplier *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value="">-- Pilih Supplier --</option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= $s->nama_supplier; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="No. Cek / BG / Transfer Ref">
                        </div>
                    </div>
                </div>
                
                <!-- ✨ NEW: Header-level account selection -->
                <hr class="my-3">
                <p class="font-weight-bold mb-3">📊 Akun untuk Pembayaran (Pilih 1 set untuk seluruh batch):</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Akun DEBET (Hutang Berkurang) *</label>
                            <select id="debet_account_id" name="debet_account_id" class="form-control" required>
                                <option value="">-- Pilih Akun Hutang --</option>
                                <?php foreach($debet_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Filter: Akun 2.1.x (Hutang Usaha)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Akun KREDIT (Kas/Bank Berkurang) *</label>
                            <select id="kredit_account_id" name="kredit_account_id" class="form-control" required>
                                <option value="">-- Pilih Akun Kas/Bank --</option>
                                <?php foreach($kredit_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Filter: Akun 1.101.x (Kas) atau 1.102.x (Bank)</small>
                        </div>
                    </div>
                </div>
                
                <!-- ✨ NEW: PPh Withholding Tax Section -->
                <hr class="my-3">
                <p class="font-weight-bold mb-3">💰 PPh 23 Potong Pajak (Opsional):</p>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tarif PPh 23 (%)</label>
                            <input type="number" id="pph_rate" name="pph_rate" class="form-control" 
                                   value="0" min="0" max="100" step="0.01" placeholder="0">
                            <small class="form-text text-muted">Contoh: 2, 2.5, 3, 5 (kosongkan jika tidak ada)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Jumlah PPh</label>
                            <input type="text" id="pph_amount" class="form-control text-right" readonly>
                            <small class="form-text text-muted">Otomatis: Total × (Tarif / 100)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Akun Hutang PPh (2.302.x)</label>
                            <select id="pph_account_id" name="pph_account_id" class="form-control">
                                <option value="">-- Pilih Akun (jika ada PPh) --</option>
                                <?php foreach($pph_payable_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Hanya jika ada tarif PPh di atas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detail Faktur (Hanya faktur yang sudah di-Posting)</h6>
                <small class="text-muted d-block mt-2">✅ Masukkan jumlah pembayaran untuk setiap faktur. Akun mapping di atas akan diterapkan ke semua faktur.</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="invoice-table">
                        <thead class="thead-light">
                            <tr>
                                <th>No. Faktur</th>
                                <th>Tgl. Faktur</th>
                                <th class="text-right">Total Utang</th>
                                <th class="text-right">Sudah Dibayar</th>
                                <th class="text-right">Sisa Utang</th>
                                <th style="width: 20%;">Jumlah Bayar *</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <tr>
                                <td colspan="6" class="text-center">Pilih supplier untuk memuat daftar faktur.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-200">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">Total Pembayaran</td>
                                <td>
                                    <input type="text" id="total_payment" class="form-control text-right font-weight-bold" readonly>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('appayment'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Pembayaran (Draft)</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    // Saat supplier diganti
    $('#supplier_id').change(function() {
        var supplierId = $(this).val();
        var tbody = $('#detail-body');
        
        if (!supplierId) {
            tbody.html('<tr><td colspan="6" class="text-center">Pilih supplier untuk memuat daftar faktur.</td></tr>');
            return;
        }

        tbody.html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>');

        $.ajax({
            url: "<?= base_url('appayment/ajax_get_unpaid_invoices'); ?>",
            type: "GET",
            data: { supplier_id: supplierId },
            dataType: "json",
            success: function(response) {
                tbody.html('');
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(inv) {
                        var sisaUtang = parseFloat(inv.sisa_utang).toFixed(2);
                        
                        var row = `
                            <tr>
                                <td>${inv.invoice_no}</td>
                                <td>${inv.invoice_date}</td>
                                <td class="text-right">${parseFloat(inv.total_invoice).toFixed(2)}</td>
                                <td class="text-right">${parseFloat(inv.total_paid).toFixed(2)}</td>
                                <td class="text-right">${sisaUtang}</td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="details[${inv.id}][amount_paid]" 
                                               class="form-control text-right payment-amount" 
                                               value="0" min="0" max="${sisaUtang}" step="0.01" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary btn-sm btn-pay-full" data-amount="${sisaUtang}">Full</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html('<tr><td colspan="6" class="text-center">Tidak ada faktur yang belum lunas untuk supplier ini.</td></tr>');
                }
                calculateTotal();
            },
            error: function() {
                tbody.html('<tr><td colspan="6" class="text-center text-danger">Error memuat data.</td></tr>');
            }
        });
    });

    // Kalkulasi total
    $('#detail-body').on('input', '.payment-amount', calculateTotal);

    // ✨ NEW: Kalkulasi PPh amount saat tarif berubah
    $('#pph_rate').on('change', calculatePPh);

    // Tombol "Full"
    $('#detail-body').on('click', '.btn-pay-full', function() {
        var amount = $(this).data('amount');
        $(this).closest('tr').find('.payment-amount').val(amount).trigger('input');
    });

    function calculateTotal() {
        var total = 0;
        $('.payment-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_payment').val(total.toFixed(2));
        calculatePPh(); // ✨ NEW: Hitung PPh ulang saat total berubah
    }
    
    // ✨ NEW: Fungsi hitung PPh
    function calculatePPh() {
        var total = parseFloat($('#total_payment').val()) || 0;
        var pph_rate = parseFloat($('#pph_rate').val()) || 0;
        var pph_amount = total * (pph_rate / 100);
        $('#pph_amount').val(pph_amount.toFixed(2));
        
        // Tampilkan/sembunyikan akun PPh berdasarkan ada tidaknya tarif
        if (pph_rate > 0) {
            $('#pph_account_id').prop('disabled', false).prop('required', true);
        } else {
            $('#pph_account_id').prop('disabled', true).prop('required', false).val('');
        }
    }
});
</script>
