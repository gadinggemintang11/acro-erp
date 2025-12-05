<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0"><i class="fas fa-file-signature text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('sales_contract', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('sc_create_new', 'Buat SC Baru'); ?>
            </button>
        <?php endif; ?>
    </div>

     <!-- Tampilkan Flashdata Success/Error -->
    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('warning'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>


    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable"> <!-- Tambahkan ID untuk datatable jika perlu -->
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sc_contract_no', 'No. Kontrak'); ?></th>
                            <th><?= lang_line('sc_customer', 'Pelanggan'); ?></th>
                            <th><?= lang_line('sc_style', 'Style'); ?></th>
                            <th><?= lang_line('sc_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('sc_qty', 'Qty'); ?></th>
                            <th><?= lang_line('sc_total_price', 'Total Price'); ?></th>
                            <th><?= lang_line('sc_status', 'Status'); ?></th>
                            <th width="100"><?= lang_line('sc_actions', 'Aksi'); ?></th> <!-- Perkecil lebar kolom aksi -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($contracts)): foreach($contracts as $sc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($sc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->nama_customer); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->style_name); ?></td>
                            <td><?= date('d M Y', strtotime($sc->contract_date)); ?></td>
                            <td class="text-right"><?= number_format($sc->po_qty); ?></td>
                            <td class="text-right"><?= htmlspecialchars($sc->currency_code) . ' ' . number_format($sc->total_price, 2, '.', ','); ?></td>
                            <td>
                                <?php 
                                    $status_class = 'info'; // Default
                                    if ($sc->status == 'Closed') $status_class = 'success';
                                    elseif ($sc->status == 'Cancelled') $status_class = 'danger';
                                ?>
                                <span class="badge badge-<?= $status_class; ?>"><?= htmlspecialchars($sc->status); ?></span>
                            </td>
                            <td>
                                <!-- REVISI: Tombol Edit diubah menjadi Link ke halaman edit -->
                                <?php if (check_access('sales_contract', 'edit')): ?>
                                <a href="<?= base_url('sales_contract/edit/'.$sc->id); ?>" class="btn btn-sm btn-warning text-white" title="<?= lang_line('sc_edit', 'Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                
                                <!-- Tombol Delete (Tetap menggunakan SweetAlert) -->
                                <?php if (check_access('sales_contract', 'delete')): ?>
                                <a href="<?= base_url('sales_contract/delete/'.$sc->id); ?>" class="btn btn-sm btn-danger btn-delete" 
                                   data-message="<?= lang_line('sc_confirm_cancel', 'Yakin ingin membatalkan Sales Contract'); ?> <?= htmlspecialchars($sc->sc_number); ?>?" title="<?= lang_line('sc_delete', 'Batalkan'); ?>">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('sc_no_data', 'Belum ada data Sales Contract'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL TAMBAH (Add Modal) - REVISI CSRF & JS -->
<!-- ============================================== -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('sales_contract/add'); ?>" id="formAddSc">
             <!-- TAMBAHAN: CSRF Token -->
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addModalLabel"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('sc_create_new_modal_title', 'Buat Sales Contract Baru'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="add_customer_id"><?= lang_line('sc_customer', 'Pelanggan'); ?> <span class="text-danger">*</span></label>
                            <select name="customer_id" id="add_customer_id" class="form-control" required>
                                <option value=""><?= lang_line('sc_customer_select', '-- Pilih Pelanggan --'); ?></option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= htmlspecialchars($c->nama_customer); ?> (<?= htmlspecialchars($c->kode_customer); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="add_fg_id"><?= lang_line('sc_style', 'Style'); ?> (Finished Good) <span class="text-danger">*</span></label>
                            <select name="fg_id" id="add_fg_id" class="form-control" required>
                                <option value=""><?= lang_line('sc_style_select', '-- Pilih Style --'); ?></option>
                                <?php foreach($styles as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= htmlspecialchars($s->nama_barang . ' (' . $s->kode_barang . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 form-group">
                            <label for="add_contract_date"><?= lang_line('sc_contract_date', 'Tanggal Kontrak'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="contract_date" id="add_contract_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="add_lchd_date"><?= lang_line('sc_lchd_date', 'LCHD Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="lchd_date" id="add_lchd_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-3 form-group">
                             <label for="add_po_qty"><?= lang_line('sc_po_qty', 'PO Qty'); ?> <span class="text-danger">*</span></label>
                            <input type="number" name="po_qty" id="add_po_qty" class="form-control text-right" required min="1">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="add_currency_code"><?= lang_line('sc_currency', 'Currency'); ?> <span class="text-danger">*</span></label>
                            <select name="currency_code" id="add_currency_code" class="form-control" required>
                                 <?php foreach($currencies as $curr): ?>
                                     <option value="<?= $curr->code; ?>" <?= ($curr->code == 'USD') ? 'selected' : ''; ?>><?= $curr->code; ?></option> <!-- Default ke USD -->
                                 <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-3 form-group">
                             <label for="add_unit_price"><?= lang_line('sc_unit_price', 'Harga Satuan'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="unit_price" id="add_unit_price" class="form-control autonumeric text-right" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="add_total_price"><?= lang_line('sc_total_price', 'Total Price'); ?></label>
                            <input type="text" id="add_total_price" class="form-control autonumeric text-right" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_destination"><?= lang_line('sc_destination', 'Destination'); ?></label>
                        <input type="text" name="destination" id="add_destination" class="form-control">
                    </div>
                    <!-- Hapus field deskripsi jika tidak ada di DB -->
                    <!-- <div class="form-group">
                        <label for="add_description">Deskripsi</label>
                        <textarea name="description" id="add_description" class="form-control" rows="2"></textarea>
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('sc_save', 'Simpan'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('sc_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- EDIT MODAL DIHAPUS DARI SINI -->
<!-- ============================================== -->

<!-- Script Autonumeric & Kalkulasi untuk Modal Add -->
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
$(document).ready(function() {
    // --- Inisialisasi AutoNumeric untuk Modal Add ---
    let anPriceAdd, anTotalAdd;
    
    // Hanya inisialisasi jika modal ada
    if ($('#add_unit_price').length) {
        anPriceAdd = new AutoNumeric('#add_unit_price', AutoNumeric.getPredefinedOptions().numericPos.dotDecimalCharCommaSeparator); // Opsi format Indonesia/Eropa
        anTotalAdd = new AutoNumeric('#add_total_price', AutoNumeric.getPredefinedOptions().numericPos.dotDecimalCharCommaSeparator);
    }
   
    function calculateTotalAdd() {
        if (!anPriceAdd || !anTotalAdd) return; // Pastikan instance ada
        const qty = parseFloat($('#add_po_qty').val()) || 0;
        const price = anPriceAdd.getNumber() || 0; 
        anTotalAdd.set(qty * price);
    }

    // Panggil kalkulasi saat qty atau harga di modal add berubah
    $('#add_po_qty, #add_unit_price').on('keyup change input', calculateTotalAdd);
    
    // --- SweetAlert untuk Delete ---
    // (Pindahkan listener delete dari footer ke sini jika belum)
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const message = $(this).data('message') || 'Yakin ingin menghapus data ini?';

        Swal.fire({
            title: '<?= lang_line("sc_confirm_delete_title", "Konfirmasi Hapus"); ?>',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<?= lang_line("sc_confirm_yes", "Ya, Hapus!"); ?>',
            cancelButtonText: '<?= lang_line("sc_confirm_no", "Batal"); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tampilkan loader jika perlu
                // showLoader(); // Pastikan fungsi showLoader ada
                window.location.href = href;
            }
        });
    });

});
</script>
