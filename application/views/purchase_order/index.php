<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <?php if (check_access('purchase_order', 'add')): ?>
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addHeaderModal">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> <?= lang_line('po_create_new', 'Buat PO Baru'); ?>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
     <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('po_title', 'Daftar Purchase Order'); ?></h6>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label class="small font-weight-bold">Dari Tanggal</label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">Sampai Tanggal</label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Status</label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value="">Semua Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Submitted">Submitted</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold d-block">&nbsp;</label>
                    <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                    <button type="button" id="resetBtn" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    <button type="button" id="exportBtn" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="poTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th width="15%"><?= lang_line('po_number', 'Nomor PO'); ?></th>
                            <th width="12%"><?= lang_line('po_date', 'Tanggal PO'); ?></th>
                            <th width="25%"><?= lang_line('po_supplier', 'Supplier'); ?></th>
                            <th width="15%">Total Amount</th>
                            <th width="10%"><?= lang_line('po_status', 'Status'); ?></th>
                            <th width="23%"><?= lang_line('po_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($pos)): foreach ($pos as $po): ?>
                        <tr>
                            <td class="text-center">
                                <a href="<?= base_url('purchase_order/detail/' . $po->id) ?>" class="font-weight-bold text-primary">
                                    <?= htmlspecialchars($po->po_number) ?>
                                </a>
                            </td>
                            <td class="text-center" data-order="<?= strtotime($po->po_date); ?>">
                                <?= date('d/m/Y', strtotime($po->po_date)) ?>
                            </td>
                            <td><?= htmlspecialchars($po->nama_supplier) ?></td>
                            <td class="text-right">
                                <?php 
                                    $total = isset($po->total_amount) ? $po->total_amount : 0;
                                    echo '<strong>' . number_format($total, 0, ',', '.') . '</strong>';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $status_colors = [ 'Draft' => 'warning', 'Submitted' => 'info', 'Confirmed' => 'primary', 'Completed' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary' ];
                                    $color = $status_colors[$po->status] ?? 'dark';
                                ?>
                                <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($po->status); ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('purchase_order/detail/' . $po->id) ?>" class="btn btn-info btn-sm btn-load" title="<?= lang_line('po_detail_title', 'Lihat Detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($po->status == 'Draft' && check_access('purchase_order', 'delete')): ?>
                                <a href="<?= base_url('purchase_order/delete_header/' . $po->id) ?>" class="btn btn-danger btn-sm btn-delete ml-1" title="<?= lang_line('po_delete', 'Hapus PO'); ?>" data-message="<?= lang_line('po_confirm_delete_header', 'Yakin hapus PO Draft ini?'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center"><?= lang_line('po_no_data', 'Belum ada data Purchase Order.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php if (check_access('purchase_order', 'add')): ?>
<div class="modal fade" id="addHeaderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= base_url('purchase_order/add_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('po_create_new', 'Buat Header Purchase Order'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('po_supplier', 'Supplier'); ?> <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-control select2-modal" required style="width: 100%;">
                                <option value=""><?= lang_line('po_supplier_select', '-- Pilih Supplier --'); ?></option>
                                <?php foreach($suppliers as $sup): ?>
                                    <option value="<?= $sup->id; ?>"><?= htmlspecialchars($sup->nama_supplier); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('po_date', 'Tanggal PO'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nomor PO <small class="text-muted">(Biarkan kosong untuk auto-generate)</small></label>
                            <input type="text" name="po_number" class="form-control" placeholder="Contoh: PO/MLI/202602/0001">
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></label>
                            <select name="shipping_mode_code" class="form-control select2-modal" style="width: 100%;">
                                <option value=""><?= lang_line('po_shipping_mode_select', '-- Pilih Mode --'); ?></option>
                                <?php foreach($shipping_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></label>
                            <select name="payment_mode_code" class="form-control select2-modal" style="width: 100%;">
                                <option value=""><?= lang_line('po_payment_mode_select', '-- Pilih Mode --'); ?></option>
                                <?php foreach($payment_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                         <div class="col-md-6 form-group">
                            <label><?= lang_line('po_currency', 'Mata Uang'); ?> <span class="text-danger">*</span></label>
                            <select name="currency_code" id="add_currency_code" class="form-control select2-modal" required style="width: 100%;">
                                <?php foreach($currencies as $curr): ?> <option value="<?= $curr->code; ?>" <?= ($curr->code == 'IDR') ? 'selected' : ''; ?>><?= htmlspecialchars($curr->description); ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group" id="add_exchange_rate_div" style="display: none;">
                            <label><?= lang_line('po_exchange_rate', 'Kurs Tukar'); ?> (Rate) <span class="text-danger">*</span></label>
                            <input type="text" name="exchange_rate" class="form-control autonumeric-4dec text-right" value="1.0000">
                        </div>
                    </div>
                     <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="add_has_ppn" name="has_ppn" value="1">
                        <label class="form-check-label" for="add_has_ppn"><?= lang_line('po_has_ppn', 'Termasuk PPN 11%'); ?></label>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('po_remarks', 'Catatan / Remarks'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-arrow-right mr-1"></i> <?= lang_line('po_save', 'Simpan'); ?> & <?= lang_line('po_detail_title', 'Lanjut'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('po_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable (jika belum di footer global)
    // Cek jika #dataTable ada sebelum inisialisasi
    if ($('#dataTable').length) {
        $('#dataTable').DataTable(); 
    }

    // Inisialisasi Select2 di dalam Modal Tambah
    if ($('.select2-modal').length) {
        $('.select2-modal').select2({
            dropdownParent: $('#addHeaderModal') // Penting agar dropdown muncul di atas modal
        });
    }

    // Inisialisasi AutoNumeric untuk kurs di modal tambah
    let anAddExchangeRate; 
    const $exchangeRateInput = $('#add_exchange_rate_div input.autonumeric-4dec'); // Cache input

    if ($exchangeRateInput.length && typeof AutoNumeric === 'function') {
       anAddExchangeRate = new AutoNumeric($exchangeRateInput[0], { decimalPlaces: 4, modifyValueOnWheel: false });
    } else if ($exchangeRateInput.length) {
        console.error("AutoNumeric library is not loaded, but input exists.");
    }

    // Tampilkan/Sembunyikan input kurs berdasarkan mata uang
    function toggleAddKurs() {
        const $rateDiv = $('#add_exchange_rate_div'); // Cache div
        
        if ($('#add_currency_code').val() !== 'IDR') { 
            $rateDiv.slideDown(); 
            // ✅ PERBAIKAN: Tambahkan 'required'
            $exchangeRateInput.prop('required', true); 
        } else { 
            $rateDiv.slideUp(); 
            // ✅ PERBAIKAN: Hapus 'required'
            $exchangeRateInput.prop('required', false); 
            
            // Set nilai ke 1 jika ada instance AutoNumeric
            if (anAddExchangeRate) {
                anAddExchangeRate.set(1); 
            } else {
                $exchangeRateInput.val('1.0000'); // Fallback
            }
        }
    }
    
    // Panggil saat load dan saat currency berubah
    toggleAddKurs(); 
    $('#add_currency_code').on('change', toggleAddKurs);

    // Konfirmasi Hapus (jika belum ada di script global footer)
    // Gunakan event delegation agar lebih aman
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault(); 
        var url = $(this).attr('href');
        var message = $(this).data('message') || 'Yakin ingin menghapus data ini?';
        
        // Gunakan SweetAlert jika ada, jika tidak, gunakan confirm()
        if (typeof Swal !== 'undefined') {
             Swal.fire({
              title: message, text: 'Data yang dihapus tidak dapat dikembalikan!', icon: 'warning',
              showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
              confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796', reverseButtons: true
            }).then((result) => {
              if (result.isConfirmed) {
                if(typeof showLoader === 'function') showLoader('Menghapus...'); // Panggil loader jika ada
                window.location.href = url; 
              }
            });
        } else {
            // Fallback confirm biasa
            if (confirm(message)) {
                if(typeof showLoader === 'function') showLoader('Menghapus...');
                window.location.href = url; 
            }
        }
    });

    // Tambahkan validasi Bootstrap ke modal (opsional tapi bagus)
    $('#addHeaderModal form').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // ========================================
    // DataTables Configuration for PO Table
    // ========================================
    var poTable = $('#poTable').DataTable({
        "order": [[1, "desc"]], // Sort by Date descending (newest first)
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Data tidak ditemukan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": 5 } // Disable sorting on Aksi column
        ],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Custom Date Range & Status Filter
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            // Only apply to poTable
            if (settings.nTable.id !== 'poTable') return true;
            
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var filterStatus = $('#filterStatus').val();
            
            // Get date from column 1 (data-order attribute)
            var dateStr = $(poTable.row(dataIndex).node()).find('td:eq(1)').attr('data-order');
            var rowDate = dateStr ? new Date(parseInt(dateStr) * 1000) : null;
            
            // Get status from column 4 (text content)
            var rowStatus = data[4] || '';
            
            var dateValid = true;
            var statusValid = true;
            
            // Check date range
            if (startDate && rowDate) {
                var start = new Date(startDate);
                start.setHours(0, 0, 0, 0);
                if (rowDate < start) dateValid = false;
            }
            
            if (endDate && rowDate) {
                var end = new Date(endDate);
                end.setHours(23, 59, 59, 999);
                if (rowDate > end) dateValid = false;
            }
            
            // Check status
            if (filterStatus && rowStatus.indexOf(filterStatus) === -1) {
                statusValid = false;
            }
            
            return dateValid && statusValid;
        }
    );

    // Apply Filter Button
    $('#filterBtn').on('click', function() {
        poTable.draw();
    });

    // Reset Filter Button
    $('#resetBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#filterStatus').val('');
        poTable.search('').draw();
    });

    // Export to Excel with Filters
    $('#exportBtn').on('click', function() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var status = $('#filterStatus').val();
        
        var url = '<?= base_url('purchase_order/export_list_excel'); ?>?';
        var params = [];
        
        if (startDate) params.push('start_date=' + startDate);
        if (endDate) params.push('end_date=' + endDate);
        if (status) params.push('status=' + status);
        
        window.location.href = url + params.join('&');
    });

    // Auto-filter on Enter key
    $('#startDate, #endDate, #filterStatus').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#filterBtn').click();
        }
    });

    // Change event for instant filter (optional)
    $('#filterStatus').on('change', function() {
        $('#filterBtn').click();
    });

});
</script>

<style>
/* Pastikan Select2 di modal tampil benar */
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.select2-dropdown { z-index: 1051 !important; } 
</style>