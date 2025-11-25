<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-industry text-primary mr-2"></i><?= $page_title ?? 'Laporan Mutasi WIP'; ?>
        </h1>
        <div class="d-none d-sm-inline-block">
            <button type="button" id="btn-export-excel" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-excel mr-1"></i> Ekspor Excel</button>
            <button type="button" id="btn-export-pdf" class="btn btn-sm btn-danger shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Ekspor PDF</button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form id="filter-form">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date">Mulai Tanggal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="item_type_filter">Tipe Item</label>
                        <select id="item_type_filter" name="item_type_filter" class="form-control">
                            <option value="">Semua Tipe</option>
                            <option value="material">Hanya Material</option>
                            <option value="semifinished">Hanya WIP (Semifinished)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search_filter">Cari (Kode / Nama)</label>
                        <input type="text" class="form-control" id="search_filter" placeholder="Cari...">
                    </div>
                </div>
                <button type="button" id="btn-filter" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Filter</button>
                <button type="button" id="btn-reset" class="btn btn-secondary"><i class="fas fa-sync-alt mr-1"></i> Reset</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Mutasi WIP</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="mainTable" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Tipe</th>
                            <th class="text-right">Saldo Awal</th>
                            <th class="text-right">Masuk (WIP)</th>
                            <th class="text-right">Keluar (WIP)</th>
                            <th class="text-right">Saldo Akhir</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mainModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" id="mainModalContent">
            </div>
    </div>
</div>

<div class="modal fade" id="recursiveModal" tabindex="-1" role="dialog" aria-labelledby="recursiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" id="recursiveModalContent">
            </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Helper untuk membuat query string
    function buildQueryString() {
        return $.param({
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            search: $('#search_filter').val(),
            item_type: $('#item_type_filter').val()
        });
    }

    var table = $('#mainTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "<?= base_url('LapMutasiWIP/ajax_list') ?>",
            "type": "GET",
            "data": function (d) {
                d.start = $('#start_date').val();
                d.end = $('#end_date').val();
                d.search = $('#search_filter').val();
                d.item_type = $('#item_type_filter').val();
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": null, "render": function (data, type, row, meta) { return meta.row + 1; }, "searchable": false, "orderable": false },
            { "data": "kode_barang" },
            { "data": "nama_barang" },
            { "data": "satuan" },
            { "data": "item_type", "render": function(data, type, row) { 
                var badge = (data == 'material') ? 'badge-primary' : 'badge-info';
                return '<span class="badge ' + badge + '">' + data.toUpperCase() + '</span>';
            }},
            { "data": "saldo_awal", "className": "text-right", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "total_masuk", "className": "text-right text-success font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "total_keluar", "className": "text-right text-danger font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { "data": "saldo_akhir", "className": "text-right font-weight-bold", "render": $.fn.dataTable.render.number('.', ',', 2) },
            { 
                "data": null,
                "className": "text-center text-nowrap",
                "searchable": false,
                "orderable": false,
                "render": function (data, type, row) {
                    var btnDetail = '<button type="button" class="btn btn-info btn-sm btn-detail" title="Kartu Stok WIP" data-id="'+row.id+'" data-type="'+row.item_type+'"><i class="fa fa-list-alt"></i></button>';
                    var btnTrace = '<button type="button" class="btn btn-secondary btn-sm btn-trace-recursive ml-1" title="Jejak Produksi (Traceability)" data-id="'+row.id+'" data-type="'+row.item_type+'"><i class="fa fa-sitemap"></i></button>';
                    return btnDetail + btnTrace;
                }
            }
        ],
        "order": [[1, "asc"]],
        "language": {
            "search": "Cari di tabel:",
            "processing": "Memuat...",
            "lengthMenu": "Tampilkan _MENU_ data",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(disaring dari _MAX_ total data)"
        }
    });

    // Handle klik tombol filter
    $('#btn-filter').on('click', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Handle klik tombol reset
    $('#btn-reset').on('click', function(e) {
        e.preventDefault();
        $('#filter-form')[0].reset();
        $('#item_type_filter').val('');
        table.ajax.reload();
    });
    
    // Handle Export
    $('#btn-export-excel').on('click', function() {
        window.open("<?= base_url('LapMutasiWIP/export_excel') ?>?" + buildQueryString(), '_blank');
    });

    $('#btn-export-pdf').on('click', function() {
        window.open("<?= base_url('LapMutasiWIP/export_pdf') ?>?" + buildQueryString(), '_blank');
    });

    // Load Modal untuk Kartu Stok WIP
    $('#mainTable tbody').on('click', '.btn-detail', function() {
        var itemId = $(this).data('id');
        var itemType = $(this).data('type');
        var url = "<?= base_url('LapMutasiWIP/detail/') ?>" + itemType + '/' + itemId;
        
        $('#mainModalContent').html('<div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Memuat detail...</p></div>');
        $('#mainModal').modal('show');
        
        $.get(url, function(data) {
            $('#mainModalContent').html(data);
        }).fail(function() {
            $('#mainModalContent').html('<div class="modal-body text-center"><p class="text-danger">Gagal memuat data. Silakan coba lagi.</p></div>');
        });
    });

    // Load Modal untuk Jejak Produksi (Trace Recursive)
    $('#mainTable tbody').on('click', '.btn-trace-recursive', function() {
        var itemId = $(this).data('id');
        var itemType = $(this).data('type');
        var url = "<?= base_url('LapMutasiWIP/trace_recursive/') ?>" + itemType + '/' + itemId;
        
        $('#recursiveModalContent').html('<div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Memuat traceability...</p></div>');
        $('#recursiveModal').modal('show');
        
        $.get(url, function(data) {
            $('#recursiveModalContent').html(data);
        }).fail(function() {
            $('#recursiveModalContent').html('<div class="modal-body text-center"><p class="text-danger">Gagal memuat data. Silakan coba lagi.</p></div>');
        });
    });
});
</script>