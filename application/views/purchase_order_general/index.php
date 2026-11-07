<?php
    // GMPO sekarang menu GABUNGAN General+Jasa+Asset (user 2026-09-05,
    // sidebar Jasa & Asset digabung ke sini) -- tiap baris po_header py
    // `type` masing2 (non_produksi/jasa/asset), jadi link Detail/Delete
    // per baris HARUS di-route ke controller ASLI tipe itu (bukan selalu
    // purchase_order_general), krn detail Jasa/Asset py UI unik
    // (Selesaikan Jasa/Terima Aset) yg cuma ada di controller masing2.
    $po_type_routes = [
        'non_produksi' => ['route' => 'purchase_order_general', 'menu_key' => 'purchase_order_general', 'label' => 'General', 'badge' => 'secondary'],
        'jasa'         => ['route' => 'purchase_order_jasa',    'menu_key' => 'purchase_order_jasa',    'label' => 'Jasa',    'badge' => 'info'],
        'asset'        => ['route' => 'purchase_order_asset',   'menu_key' => 'purchase_order_asset',   'label' => 'Asset',   'badge' => 'dark'],
    ];
?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <?php if (check_access('purchase_order_general', 'add')): ?>
        <a href="<?= base_url('purchase_order_general/add'); ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> <?= lang_line('po_create_new', 'Buat PO Baru'); ?>
        </a>
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
                    <label class="small font-weight-bold"><?= lang_line('po_filter_date_from'); ?></label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold"><?= lang_line('po_filter_date_to'); ?></label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold"><?= lang_line('po_filter_status'); ?></label>
                    <select id="filterStatus" class="form-control form-control-sm">
                        <option value=""><?= lang_line('text_all_status'); ?></option>
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
                        <i class="fas fa-filter"></i> <?= lang_line('po_filter_apply'); ?>
                    </button>
                    <button type="button" id="resetBtn" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> <?= lang_line('po_filter_reset'); ?>
                    </button>
                    <button type="button" id="exportBtn" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> <?= lang_line('po_export_excel'); ?>
                    </button>
                    <button type="button" id="exportPdfBtn" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> <?= lang_line('po_export_pdf'); ?>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="poTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th width="13%"><?= lang_line('po_number', 'Nomor PO'); ?></th>
                            <th width="9%"><?= lang_line('po_kategori', 'Kategori'); ?></th>
                            <th width="11%"><?= lang_line('po_date', 'Tanggal PO'); ?></th>
                            <th width="22%"><?= lang_line('po_supplier', 'Supplier'); ?></th>
                            <th width="14%"><?= lang_line('po_total_amount', 'Total Amount'); ?></th>
                            <th width="9%"><?= lang_line('po_status', 'Status'); ?></th>
                            <th width="22%"><?= lang_line('po_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($pos)): foreach ($pos as $po): ?>
                        <?php
                            $rt = $po_type_routes[$po->type] ?? $po_type_routes['non_produksi'];
                            $can_delete_row = check_access($rt['menu_key'], 'delete');
                        ?>
                        <tr>
                            <td class="text-center">
                                <a href="<?= base_url($rt['route'] . '/detail/' . $po->id) ?>" class="font-weight-bold text-primary">
                                    <?= htmlspecialchars($po->po_number) ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-<?= $rt['badge']; ?>"><?= $rt['label']; ?></span>
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
                                <a href="<?= base_url($rt['route'] . '/detail/' . $po->id) ?>" class="btn btn-info btn-sm btn-load" title="<?= lang_line('po_detail_title', 'Lihat Detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($po->status == 'Draft' && $can_delete_row): ?>
                                <a href="<?= base_url($rt['route'] . '/delete_header/' . $po->id) ?>" class="btn btn-danger btn-sm btn-delete ml-1" title="<?= lang_line('po_delete', 'Hapus PO'); ?>" data-message="<?= lang_line('po_confirm_delete_header', 'Yakin hapus PO Draft ini?'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center"><?= lang_line('po_no_data', 'Belum ada data Purchase Order.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
    // Modal "Buat Header PO" DIPINDAH ke halaman tersendiri
    // purchase_order_general/add.php (user 2026-09-05: "HARUSNYA SAAT KLIK
    // BUAT GMPO LANGSUNG MUNCUL FORM", pola sama spt IRF/PRF/ERF/MRF).
    // Select2/AutoNumeric/toggle-kurs-nya ikut pindah kesana.
?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable (jika belum di footer global)
    // Cek jika #dataTable ada sebelum inisialisasi
    if ($('#dataTable').length) {
        $('#dataTable').DataTable();
    }

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
                if(typeof showLoader === 'function') showLoader('<?= lang_line('po_delete_loading', 'Menghapus...'); ?>'); // Panggil loader jika ada
                window.location.href = url; 
              }
            });
        } else {
            // Fallback confirm biasa
            if (confirm(message)) {
                if(typeof showLoader === 'function') showLoader('<?= lang_line('po_delete_loading', 'Menghapus...'); ?>');
                window.location.href = url; 
            }
        }
    });


    // ========================================
    // DataTables Configuration for PO Table
    // ========================================
    var poTable = $('#poTable').DataTable({
        "order": [[2, "desc"]], // Sort by Date descending (newest first) -- kolom 2 sejak kolom Kategori ditambah
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang_line('po_datatable_all', 'Semua'); ?>"]],
        "language": {
            "lengthMenu": "<?= lang_line('po_datatable_length_menu', 'Tampilkan _MENU_ data per halaman'); ?>",
            "zeroRecords": "<?= lang_line('po_datatable_zero_records', 'Data tidak ditemukan'); ?>",
            "info": "<?= lang_line('po_datatable_info', 'Menampilkan halaman _PAGE_ dari _PAGES_'); ?>",
            "infoEmpty": "<?= lang_line('po_datatable_info_empty', 'Tidak ada data tersedia'); ?>",
            "infoFiltered": "<?= lang_line('po_datatable_info_filtered', '(difilter dari _MAX_ total data)'); ?>",
            "search": "<?= lang_line('po_datatable_search', 'Cari:'); ?>",
            "paginate": {
                "first": "<?= lang_line('po_datatable_first', 'Pertama'); ?>",
                "last": "<?= lang_line('po_datatable_last', 'Terakhir'); ?>",
                "next": "<?= lang_line('po_datatable_next', 'Selanjutnya'); ?>",
                "previous": "<?= lang_line('po_datatable_previous', 'Sebelumnya'); ?>"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Disable sorting on Aksi column (kolom 6 sejak Kategori ditambah)
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
            
            // Get date from column 2 (data-order attribute) -- kolom Kategori
            // baru geser Tanggal dari indeks 1 ke 2.
            var dateStr = $(poTable.row(dataIndex).node()).find('td:eq(2)').attr('data-order');
            var rowDate = dateStr ? new Date(parseInt(dateStr) * 1000) : null;

            // Get status from column 5 (text content) -- geser dari 4 ke 5.
            var rowStatus = data[5] || '';
            
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

        var url = '<?= base_url('purchase_order_general/export_list_excel'); ?>?';
        var params = [];

        if (startDate) params.push('start_date=' + startDate);
        if (endDate) params.push('end_date=' + endDate);
        if (status) params.push('status=' + status);

        window.location.href = url + params.join('&');
    });

    // Export to PDF with Filters
    $('#exportPdfBtn').on('click', function() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var status = $('#filterStatus').val();

        var url = '<?= base_url('purchase_order_general/export_list_pdf'); ?>?';
        var params = [];

        if (startDate) params.push('start_date=' + startDate);
        if (endDate) params.push('end_date=' + endDate);
        if (status) params.push('status=' + status);

        window.open(url + params.join('&'), '_blank');
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