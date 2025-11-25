<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-plus-circle text-primary mr-2"></i><?= $title; ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi Pengeluaran Subkon</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_out/save_header'); ?>" method="POST" id="create-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subkon_order_id">Pilih Kontrak Subkon (Hanya status Confirmed) <span class="text-danger">*</span></label>
                            <select id="subkon_order_id" name="subkon_order_id" class="form-control" style="width: 100%;" required></select>
                        </div>
                        <div class="form-group">
                            <label for="shipping_date">Tanggal Kirim <span class="text-danger">*</span></label>
                            <input type="date" id="shipping_date" name="shipping_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bc_type_code">Jenis Dokumen Pabean <span class="text-danger">*</span></label>
                            <select id="bc_type_code" name="bc_type_code" class="form-control" required>
                                <option value="">-- Pilih Jenis BC --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= htmlspecialchars($bc->code); ?>"><?= htmlspecialchars($bc->description); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-8">
                                <label for="bc_number">Nomor Dokumen Pabean <span class="text-danger">*</span></label>
                                <input type="text" id="bc_number" name="bc_number" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="bc_date">Tanggal BC <span class="text-danger">*</span></label>
                                <input type="date" id="bc_date" name="bc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="notes">Catatan (Opsional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <hr>
                <div class="text-right">
                    <a href="<?= base_url('subkon_out'); ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary btn-load">
                        <i class="fas fa-save mr-1"></i> Simpan & Lanjut Tambah Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#subkon_order_id').select2({
        placeholder: 'Cari No. Kontrak atau Nama Supplier...',
        ajax: {
            url: "<?= base_url('subkon_out/ajax_search_confirmed_orders'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // Inisialisasi Select2 standar untuk BC Type
    $('#bc_type_code').select2();
});
</script>