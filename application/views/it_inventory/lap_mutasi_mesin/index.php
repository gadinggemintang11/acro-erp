<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tools text-primary mr-2"></i><?= $page_title ?? 'Laporan Mutasi Mesin'; ?>
        </h1>
        <div class="d-none d-sm-inline-block">
            <button type="button" id="btn-export-excel" class="btn btn-sm btn-success shadow-sm">
                <i class="fas fa-file-excel mr-1"></i> Ekspor Excel
            </button>
            <button type="button" id="btn-export-pdf" class="btn btn-sm btn-danger shadow-sm">
                <i class="fas fa-file-pdf mr-1"></i> Ekspor PDF
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form id="filter-form">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="start_date">Mulai Tanggal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="search_filter">Cari (Kode / Nama)</label>
                        <input type="text" class="form-control" id="search_filter" placeholder="Cari mesin...">
                    </div>
                </div>
                <button type="button" id="btn-filter" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Filter</button>
                <button type="button" id="btn-reset" class="btn btn-secondary"><i class="fas fa-sync-alt mr-1"></i> Reset</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Mutasi Mesin</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="mainTable" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Kode Mesin</th>
                            <th rowspan="2">Nama Mesin</th>
                            <th rowspan="2">Satuan</th>
                            <th rowspan="2" class="text-right">Saldo Awal</th>
                            <th rowspan="2" class="text-right">Masuk</th>
                            <th rowspan="2" class="text-right">Keluar</th>
                            <th rowspan="2" class="text-right">Penyesuaian</th>
                            <th rowspan="2" class="text-right">Saldo Akhir</th>
                            <th colspan="2" class="text-center">Stock Opname</th>
                            <th rowspan="2">Keterangan</th>
                            <th rowspan="2" class="text-center">Aksi</th>
                        </tr>
                        <tr>
                            <th class="text-right">Hasil Fisik</th>
                            <th class="text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="mainModalContent">
        </div>
    </div>
</div>

<!-- Modal untuk Dokumen Adjustment -->
<div class="modal fade" id="adjustment-documents-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-2 text-muted">Memuat dokumen...</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    function buildQueryString() {
        return $.param({
            start: $('#start_date').val(),
            end: $('#end_date').val(),
            search: $('#search_filter').val()
        });
    }

    var table = $('#mainTable').DataTable({
        "processing": true,
        "serverSide": false, 
        "ajax": {
            "url": "<?= base_url('LapMutasiMesin/ajax_list') ?>",
            "type": "GET",
            "data": function (d) {
                d.start = $('#start_date').val();
                d.end = $('#end_date').val();
                d.search = $('#search_filter').val();
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": null, "render": function (data, type, row, meta) { return meta.row + 1; }, "searchable": false, "orderable": false },
            { "data": "kode_mesin" },
            { "data": "nama_mesin" },
            { "data": "satuan" },
            { "data": "saldo_awal", "className": "text-right", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "total_masuk", "className": "text-right text-success font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "total_keluar", "className": "text-right text-danger font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "total_penyesuaian", "className": "text-right text-warning font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "saldo_akhir", "className": "text-right font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "hasil_pencacahan", "className": "text-right", "render": function(data) {
                return data !== null ? $.fn.dataTable.render.number('.', ',', 2).display(data) : '<i class="text-muted">(N/A)</i>';
            }},
            { "data": "jumlah_selisih", "className": "text-right font-weight-bold", "render": function(data) {
                if (data === null) return '<i class="text-muted">(N/A)</i>';
                var val = $.fn.dataTable.render.number('.', ',', 2).display(data);
                var className = data > 0 ? 'text-danger' : (data < 0 ? 'text-success' : 'text-secondary');
                return '<span class="' + className + '">' + val + '</span>';
            }},
            { "data": null, "className": "text-left", "render": function(data, type, row) {
                if (row.hasil_pencacahan === null) return '-';
                if (row.jumlah_selisih == 0) return 'Sesuai';
                return 'Selisih: ' + $.fn.dataTable.render.number('.', ',', 2).display(Math.abs(row.jumlah_selisih));
            }},
            { 
                "data": null,
                "className": "text-center text-nowrap",
                "searchable": false,
                "orderable": false,
                "render": function (data, type, row) {
                    // Menggunakan ikon modern
                    return '<button class="btn btn-info btn-sm btn-detail" title="Kartu Stok Mesin" data-id="'+row.id+'"><i class="fas fa-list-alt"></i></button>';
                }
            }
        ],
        "order": [[1, "asc"]],
        "language": { "search": "Cari di tabel:", "processing": "Memuat...", "lengthMenu": "Tampilkan _MENU_ data", "zeroRecords": "Tidak ada data yang cocok", "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", "infoEmpty": "Tidak ada data", "infoFiltered": "(disaring dari _MAX_ total data)" }
    });

    $('#btn-filter').on('click', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btn-reset').on('click', function(e) {
        e.preventDefault();
        $('#filter-form')[0].reset();
        table.ajax.reload();
    });

    $('#btn-export-excel').on('click', function() {
        var url = "<?= base_url('LapMutasiMesin/export_excel') ?>?" + buildQueryString();
        window.open(url, '_blank');
    });

    $('#btn-export-pdf').on('click', function() {
        var url = "<?= base_url('LapMutasiMesin/export_pdf') ?>?" + buildQueryString();
        window.open(url, '_blank');
    });

    $('#mainTable tbody').on('click', '.btn-detail', function() {
        var itemId = $(this).data('id');
        var url = "<?= base_url('LapMutasiMesin/detail/') ?>" + itemId;
        
        $('#mainModalContent').html('<div class="modal-body text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Memuat detail...</p></div>');
        $('#mainModal').modal('show');
        
        $.get(url, function(data) {
            $('#mainModalContent').html(data);
        }).fail(function() {
            $('#mainModalContent').html('<div class="modal-body text-center"><p class="text-danger">Gagal memuat data. Silakan coba lagi.</p></div>');
        });
    });

    // Function untuk menampilkan dokumen adjustment
    window.viewAdjustmentDocuments = function(adjustmentId) {
        $('#adjustment-documents-modal').modal('show').find('.modal-content')
            .html('<div class="modal-body text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2 text-muted">Memuat dokumen...</p></div>')
            .load(`<?= base_url('LapMutasiMesin/modal_adjustment_documents/'); ?>${adjustmentId}`);
    };
});
</script>