<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-edit me-2"></i>Edit Header Pengeluaran FG
        </h3>
        <a href="<?= site_url('fg_delivery/show/' . $header->id) ?>" class="btn btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?= form_open('fg_delivery/update_header/' . $header->id, ['id' => 'formEditHeader']) ?>
            <div class="row g-3">
                <div class="col-md-4 mb-3">
                    <label for="delivery_date" class="form-label fw-semibold">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" value="<?= htmlspecialchars($header->delivery_date) ?>" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label for="customer_id" class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select select2-customer" required>
                        <option value="">-- Pilih Customer --</option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($c->id == $header->customer_id) ? 'selected' : '' ?>><?= htmlspecialchars($c->nama_customer) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mb-3">
                    <label for="shipping_address" class="form-label fw-semibold">Alamat Pengiriman</label>
                    <textarea name="shipping_address" id="shipping_address" class="form-control" rows="2" placeholder="Alamat lengkap pengiriman"><?= htmlspecialchars($header->shipping_address) ?></textarea>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="bc_type_id" class="form-label fw-semibold">BC Type</label>
                    <select name="bc_type_id" id="bc_type_id" class="form-select select2-bc-type">
                        <option value="">-- Pilih BC --</option>
                        <?php foreach($bc_types as $b): ?>
                            <option value="<?= $b->id ?>" <?= ($b->id == $header->bc_type_id) ? 'selected' : '' ?>><?= htmlspecialchars($b->code) ?> - <?= htmlspecialchars($b->description) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="bc_number" class="form-label fw-semibold">Nomor BC</label>
                    <input type="text" name="bc_number" id="bc_number" class="form-control" value="<?= htmlspecialchars($header->bc_number) ?>" placeholder="Nomor Bea Cukai">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="bc_date" class="form-label fw-semibold">Tanggal BC</label>
                    <input type="date" name="bc_date" id="bc_date" class="form-control" value="<?= htmlspecialchars($header->bc_date) ?>">
                </div>
                
                <div class="col-12 mb-3">
                    <label for="shipping_method" class="form-label fw-semibold">Metode Pengiriman</label>
                    <input type="text" name="shipping_method" id="shipping_method" value="<?= htmlspecialchars($header->shipping_method) ?>" class="form-control" placeholder="Contoh: Truck, Container, dll.">
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="fas fa-save me-1"></i> Update Header
                </button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>

</div>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for customer and BC type dropdowns
    $('.select2-customer').select2({
        theme: 'bootstrap4',
        placeholder: '-- Pilih Customer --',
        allowClear: true
    });
    $('.select2-bc-type').select2({
        theme: 'bootstrap4',
        placeholder: '-- Pilih BC --',
        allowClear: true
    });

    // Auto-fill shipping address based on customer selection
    $('#customer_id').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            fetch('<?= site_url("fg_delivery/ajax_get_customer/") ?>' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data && data.alamat) {
                        $('#shipping_address').val(data.alamat);
                    } else {
                        $('#shipping_address').val('');
                    }
                })
                .catch(error => {
                    console.error('Error fetching customer address:', error);
                    $('#shipping_address').val('');
                });
        } else {
            $('#shipping_address').val('');
        }
    });
});
</script>
