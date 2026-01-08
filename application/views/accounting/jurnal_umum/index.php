<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('jurnalumum/batch_import'); ?>" class="btn btn-success btn-icon-split mr-2">
                <span class="icon text-white-50"><i class="fas fa-file-excel"></i></span>
                <span class="text">Batch Import</span>
            </a>
            <a href="<?= base_url('jurnalumum/create'); ?>" class="btn btn-primary btn-icon-split">
                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                <span class="text">Buat Jurnal Baru</span>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter & Pencarian</h6>
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
                        <option value="Posted">Posted</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold d-block">&nbsp;</label>
                    <button type="button" id="filterBtn" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                    <button type="button" id="resetBtn" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                    <button type="button" id="exportBtn" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="journalTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="15%">No. Jurnal</th>
                            <th width="12%">Tanggal</th>
                            <th width="30%">Keterangan</th>
                            <th width="12%" class="text-right">Mata Uang</th>
                            <th width="13%" class="text-right">Total</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($journals as $j): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>" class="text-primary font-weight-bold">
                                    <?= $j->journal_no; ?>
                                </a>
                            </td>
                            
                            <td data-order="<?= strtotime($j->journal_date); ?>">
                                <?= date('d/m/Y', strtotime($j->journal_date)); ?>
                            </td>
                            
                            <td><?= $j->description; ?></td>
                            
                            <td class="text-right">
                                <small class="text-muted"><?= $j->currency ?? 'IDR'; ?></small>
                            </td>
                            
                            <td class="text-right">
                                <strong><?= number_format($j->total_debit, 0, ',', '.'); ?></strong>
                            </td>
                            
                            <td class="text-center">
                                <?php
                                    $badge = 'secondary';
                                    if ($j->status == 'Draft') $badge = 'warning';
                                    if ($j->status == 'Posted') $badge = 'success';
                                    if ($j->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $j->status; ?></span>
                            </td>
                            
                            <td class="text-center">
                                <a href="<?= base_url('jurnalumum/view/' . $j->id); ?>" class="btn btn-info btn-circle btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($j->status == 'Draft'): ?>
                                <a href="<?= base_url('jurnalumum/delete/' . $j->id); ?>" class="btn btn-danger btn-circle btn-sm" title="Hapus" onclick="return confirm('Anda yakin ingin menghapus jurnal DRAFT ini?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Script -->
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#journalTable').DataTable({
        "order": [[1, "desc"]], // Sort by Tanggal descending (newest first)
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
            { "orderable": false, "targets": 6 } // Disable sorting on Aksi column
        ],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Custom Date Range Filter
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var startDate = $('#startDate').val();
            var endDate = $('#endDate').val();
            var status = $('#filterStatus').val();
            
            // Get date from column 1 (Tanggal) - data-order attribute
            var dateStr = $(table.row(dataIndex).node()).find('td:eq(1)').attr('data-order');
            var rowDate = dateStr ? new Date(parseInt(dateStr) * 1000) : null;
            
            // Get status from column 5
            var rowStatus = data[5] || '';
            
            var dateValid = true;
            var statusValid = true;
            
            // Check date range
            if (startDate && rowDate) {
                var start = new Date(startDate);
                start.setHours(0,0,0,0);
                if (rowDate < start) dateValid = false;
            }
            
            if (endDate && rowDate) {
                var end = new Date(endDate);
                end.setHours(23,59,59,999);
                if (rowDate > end) dateValid = false;
            }
            
            // Check status
            if (status && rowStatus.indexOf(status) === -1) {
                statusValid = false;
            }
            
            return dateValid && statusValid;
        }
    );

    // Apply Filter Button
    $('#filterBtn').on('click', function() {
        table.draw();
    });

    // Reset Filter Button
    $('#resetBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#filterStatus').val('');
        table.search('').draw();
    });

    // Export to Excel
    $('#exportBtn').on('click', function() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var status = $('#filterStatus').val();
        
        var url = '<?= base_url('jurnalumum/export_list_excel'); ?>?';
        if (startDate) url += 'start_date=' + startDate + '&';
        if (endDate) url += 'end_date=' + endDate + '&';
        if (status) url += 'status=' + status;
        
        window.location.href = url;
    });

    // Auto-filter on Enter key
    $('#startDate, #endDate, #filterStatus').on('keypress', function(e) {
        if (e.which == 13) {
            $('#filterBtn').click();
        }
    });
});
</script>