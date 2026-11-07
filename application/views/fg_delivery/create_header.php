<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-weight-bold text-primary mb-0">
            <i class="fas fa-file-signature mr-2"></i><?= lang_line('fgd_create_header_title', 'Buat Header Pengeluaran FG'); ?>
        </h3>
        <a href="<?= site_url('fg_delivery') ?>" class="btn btn-light border">
            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('fgd_btn_back', 'Kembali'); ?>
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="row">
                <div class="col">
                    <h5 class="mb-0"><?= lang_line('fgd_header_card_title', 'Header Pengiriman Barang Jadi'); ?></h5>
                    <small class="text-muted"><?= lang_line('fgd_header_subtitle', 'Isi data header sebelum menambahkan detail pengiriman.'); ?></small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?= form_open('fg_delivery/store_header', ['class' => 'row']) ?>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_delivery_date', 'Tanggal Pengeluaran'); ?></label>
                    <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_customer', 'Customer'); ?></label>
                    <select name="customer_id" id="customer_id" class="form-control" required>
                        <option value=""><?= lang_line('fgd_select_customer', '-- Pilih Customer --'); ?></option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>"><?= htmlspecialchars($c->nama_customer) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_shipping_addr', 'Alamat Pengiriman'); ?></label>
                    <input type="text" name="shipping_address" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_bc_type', 'BC Type'); ?></label>
                    <select name="bc_type_id" id="bc_type_id" class="form-control">
                        <option value=""><?= lang_line('fgd_select_bc', '-- Pilih BC --'); ?></option>
                        <?php foreach($bc_types as $b): ?>
                            <option value="<?= $b->id ?>"><?= $b->code ?> - <?= $b->description ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_bc_number', 'Nomor BC'); ?></label>
                    <input type="text" name="bc_number" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_bc_date', 'Tanggal BC'); ?></label>
                    <input type="date" name="bc_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold"><?= lang_line('fgd_label_shipping_method', 'Metode Pengiriman'); ?></label>
                    <input type="text" name="shipping_method" value="Truck" class="form-control">
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <a href="<?= site_url('fg_delivery') ?>" class="btn btn-light border mr-2"> <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('fgd_btn_cancel', 'Batal'); ?></a>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i> <?= lang_line('fgd_btn_save_continue', 'Simpan & Lanjutkan'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>

</div>

<!-- Select2 assets (CDN) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const I18N_FGD_HEAD = {
    select_customer: <?= json_encode(lang_line('fgd_select_customer', '-- Pilih Customer --')); ?>,
    select_bc:       <?= json_encode(lang_line('fgd_select_bc', '-- Pilih BC --')); ?>
};

// Initialize Select2 (requires jQuery and Select2 assets)
document.addEventListener('DOMContentLoaded', function(){
        try {
            if (window.jQuery && jQuery().select2) {
                // Use AJAX-powered Select2 for customer to support large datasets
                $('#customer_id').select2({
                    placeholder: I18N_FGD_HEAD.select_customer,
                    width: '100%',
                    ajax: {
                        url: '<?= site_url('fg_delivery/customer_search'); ?>',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) { return { q: params.term }; },
                        processResults: function(data) { return data; },
                        cache: true
                    },
                    minimumInputLength: 1
                });

                $('#bc_type_id').select2({ placeholder: I18N_FGD_HEAD.select_bc, allowClear: true, width: '100%' });
            }
        } catch (e) { console.warn('Select2 init failed', e); }

    // when customer changes, fetch default shipping address
    var custEl = document.getElementById('customer_id');
    var shippingInput = document.querySelector('input[name="shipping_address"]');

    function setShippingFromResponse(resp){
        if(!resp) return;
        // support multiple possible field names from DB
        var addr = resp.alamat || resp.address || resp.customer_address || resp.alamat_customer || resp.shipping_address || resp.alamat_pengiriman || resp.alamat_kirim;
        if(addr && shippingInput) shippingInput.value = addr;
    }

    if (custEl) {
        // handle native change (works with normal select and Select2)
        custEl.addEventListener('change', function(){
            var cid = this.value;
            if(!cid) return;
            fetch('<?= site_url("fg_delivery/ajax_get_customer/") ?>' + cid)
                .then(r=>r.json())
                .then(j=>{ setShippingFromResponse(j); })
                .catch(err=>console.warn('Customer fetch failed', err));
        });

        // also fetch address on load if a customer is pre-selected (edit case)
        if (custEl.value) {
            fetch('<?= site_url("fg_delivery/ajax_get_customer/") ?>' + custEl.value)
                .then(r=>r.json())
                .then(j=>{ setShippingFromResponse(j); })
                .catch(err=>console.warn('Customer fetch failed', err));
        }

        // if Select2 is in use, also listen for select2:select event to be safe
        if (window.jQuery && jQuery().select2) {
            $('#customer_id').on('select2:select', function(e){
                var data = e.params && e.params.data;
                var cid = data && data.id;
                if(!cid) return;
                fetch('<?= site_url("fg_delivery/ajax_get_customer/") ?>' + cid)
                    .then(r=>r.json())
                    .then(j=>{ setShippingFromResponse(j); })
                    .catch(err=>console.warn('Customer fetch failed', err));
            });
        }
    }
});
</script>
