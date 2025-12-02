<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-plus-circle text-success mr-2"></i><?= lang_line('subkon_in_create_title', 'Buat Penerimaan Subkon Baru'); ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('subkon_in_info_header', 'Informasi Penerimaan Subkon (GRN)'); ?></h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_in/save_header'); ?>" method="POST" id="create-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subkon_order_id"><?= lang_line('subkon_in_select_contract', 'Pilih Kontrak Subkon (Confirmed)'); ?> <span class="text-danger">*</span></label>
                            <select id="subkon_order_id" name="subkon_order_id" class="form-control" style="width: 100%;" required></select>
                        </div>
                        <div class="form-group">
                            <label for="receipt_date"><?= lang_line('subkon_in_receipt_date_label', 'Tanggal Terima'); ?> <span class="text-danger">*</span></label>
                            <input type="date" id="receipt_date" name="receipt_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-secondary mb-3"><?= lang_line('subkon_in_bc_section_title', 'Dokumen Pabean (Wajib Lapor)'); ?></h6>
                        <div class="form-group">
                            <label for="bc_type_code"><?= lang_line('subkon_in_bc_type', 'Jenis Dokumen Pabean'); ?> <span class="text-danger">*</span></label>
                            <select id="bc_type_code" name="bc_type_code" class="form-control" required>
                                <option value=""><?= lang_line('subkon_in_bc_type_placeholder', '-- Pilih Jenis BC --'); ?></option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-8">
                                <label for="bc_number"><?= lang_line('subkon_in_bc_number', 'Nomor Dokumen Pabean'); ?> <span class="text-danger">*</span></label>
                                <input type="text" id="bc_number" name="bc_number" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bc_date"><?= lang_line('subkon_in_bc_date', 'Tanggal BC'); ?> <span class="text-danger">*</span></label>
                                <input type="date" id="bc_date" name="bc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                <div class="text-right">
                    <a href="<?= base_url('subkon_in'); ?>" class="btn btn-secondary"><?= lang_line('subkon_in_cancel', 'Batal'); ?></a>
                    <button type="submit" class="btn btn-success btn-load">
                        <i class="fas fa-save mr-1"></i> <?= lang_line('subkon_in_save', 'Simpan & Lanjut Item'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#subkon_order_id').select2({
        placeholder: '<?= lang_line('subkon_in_select_contract_placeholder', 'Cari No. Kontrak Subkon...'); ?>',
        ajax: {
            url: "<?= base_url('subkon_in/ajax_search_confirmed_orders'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    $('#bc_type_code').select2();
});
</script>