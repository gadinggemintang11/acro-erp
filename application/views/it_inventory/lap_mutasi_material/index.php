<div class="container-fluid" id="main-container">

    <!-- Heading + Dark Mode Toggle -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold mb-0 text-dark" id="page-title">
            <i class="fas fa-exchange-alt text-primary me-2"></i><?= $page_title; ?>
        </h3>
        <div>
            <button id="themeToggle" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-moon me-1"></i> <span>Dark Mode</span>
            </button>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- FILTER -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h6 class="fw-bold text-primary mb-0">
                <i class="fa fa-filter me-1"></i> Filter Laporan
            </h6>
        </div>
        <div class="card-body pt-0">
            <form class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label text-muted small">Tanggal Mulai</label>
                    <input type="date" id="start_date" class="form-control form-control-sm shadow-none" value="<?= htmlspecialchars($start); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label text-muted small">Tanggal Selesai</label>
                    <input type="date" id="end_date" class="form-control form-control-sm shadow-none" value="<?= htmlspecialchars($end); ?>">
                </div>
                <div class="col-md-3">
                    <label for="item_type" class="form-label text-muted small">Tipe Item</label>
                    <select id="item_type" class="form-select form-select-sm shadow-none">
                        <option value="">Semua Tipe</option>
                        <option value="material">Material</option>
                        <option value="semifinished">Semifinished</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-sm btn-primary w-100 shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom py-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-line me-1 text-primary"></i> Rekapitulasi Mutasi</h6>
            <div class="d-flex gap-2">
                <button id="btn-export-excel" class="btn btn-success btn-sm shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
                <button id="btn-export-pdf" class="btn btn-danger btn-sm shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle text-nowrap" id="reportTable" width="100%">
                    <thead class="text-center align-middle">
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Kode</th>
                            <th rowspan="2">Nama Barang</th>
                            <th rowspan="2">Satuan</th>
                            <th rowspan="2">Tipe</th>
                            <th rowspan="2">Saldo Awal</th>
                            <th colspan="4">Mutasi</th>
                            <th rowspan="2">Saldo Akhir (Sistem)</th>
                            <th colspan="2">Stock Opname</th>
                            <th rowspan="2">Aksi</th>
                        </tr>
                        <tr>
                            <th>Pemasukan</th>
                            <th>Retur Prod.</th>
                            <th>Pengeluaran</th>
                            <th>Penyesuaian</th>
                            <th>Hasil Fisik</th>
                            <th>Selisih</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="alert alert-light border mt-4 shadow-sm">
                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-info-circle me-1"></i> Catatan:</h6>
                <ul class="small mb-0 ps-3 text-secondary">
                    <li><b>(N/A)</b> artinya barang tidak termasuk dalam sesi Stock Opname 'Completed'.</li>
                    <li><b>Selisih</b> = Saldo Akhir (Sistem) − Hasil Pencacahan (Fisik).</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="main-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-2 text-muted">Memuat data...</p>
            </div>
        </div>
    </div>
</div>

<!-- Datatables -->
<script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js'); ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap4.min.js'); ?>"></script>

<script>
$(function() {
    const table = $('#reportTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "<?= base_url('LapMutasiMaterial/ajax_list'); ?>",
            type: "GET",
            data: d => {
                d.start = $('#start_date').val();
                d.end = $('#end_date').val();
                d.item_type = $('#item_type').val();
            },
            dataSrc: "data"
        },
        columns: [
            { data: null, render: (d,t,r,m)=> m.row+1, className: "text-center" },
            { data: "kode_barang" },
            { data: "nama_barang" },
            { data: "satuan", className: "text-center" },
            { 
                data: "item_type", className: "text-center",
                render: d => d==='material'
                    ? '<span class="badge bg-info text-white">Material</span>'
                    : '<span class="badge bg-warning text-dark">Semifinished</span>'
            },
            { data: "saldo_awal", className: "text-end", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "pemasukan", className: "text-end text-success", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "retur", className: "text-end text-primary", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "pengeluaran", className: "text-end text-danger", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "penyesuaian", className: "text-end text-info", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "saldo_akhir", className: "text-end fw-bold text-primary bg-light", render: $.fn.dataTable.render.number('.', ',', 2) },
            { data: "hasil_pencacahan", className: "text-end", render: d=>d!==null?$.fn.dataTable.render.number('.', ',', 2).display(d):'<i class="text-muted">(N/A)</i>' },
            { data: "jumlah_selisih", className: "text-end fw-bold",
                render: (d)=> {
                    if(d===null) return '<i class="text-muted">(N/A)</i>';
                    const val=$.fn.dataTable.render.number('.', ',', 2).display(d);
                    return `<span class="${d>0?'text-danger':d<0?'text-success':'text-secondary'}">${val}</span>`;
                }
            },
            { data: null, className: "text-center", orderable: false,
                render: (d,t,r)=> `
                    <button class="btn btn-outline-info btn-sm btn-detail me-1" data-type="${r.item_type}" data-id="${r.id}" title="Lihat Kartu Stok">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="btn btn-outline-primary btn-sm btn-trace" data-type="${r.item_type}" data-id="${r.id}" title="Trace Alur Produksi">
                        <i class="fas fa-sitemap"></i>
                    </button>`
            }
        ],
        language: {
            processing: '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Memuat data...</p></div>',
            emptyTable: "Tidak ada data dengan filter ini",
            zeroRecords: "Tidak ditemukan data",
            search: "_INPUT_",
            searchPlaceholder: "Cari barang..."
        }
    });

    $('#btn-filter').click(function(e) {
        e.preventDefault();
        // Redirect ke halaman index dengan parameter GET agar filter tersimpan
        const params = $.param({ 
            start: $('#start_date').val(), 
            end: $('#end_date').val(), 
            item_type: $('#item_type').val() 
        });
        window.location.href = '<?= base_url("LapMutasiMaterial"); ?>?' + params;
    });
    
    const getExportUrl = () => '?' + $.param({ start:$('#start_date').val(), end:$('#end_date').val(), item_type:$('#item_type').val(), search:table.search() });
    $('#btn-export-excel').click(()=> window.location.href = '<?= base_url("LapMutasiMaterial/export_excel"); ?>' + getExportUrl());
    $('#btn-export-pdf').click(()=> window.open('<?= base_url("LapMutasiMaterial/export_pdf"); ?>' + getExportUrl(), '_blank'));

    $('#reportTable tbody').on('click', '.btn-detail, .btn-trace', function() {
        const url = $(this).hasClass('btn-detail')
            ? `<?= base_url('LapMutasiMaterial/detail'); ?>/${$(this).data('type')}/${$(this).data('id')}`
            : `<?= base_url('LapMutasiMaterial/trace_recursive'); ?>/${$(this).data('type')}/${$(this).data('id')}`;
        $('#main-modal').modal('show').find('.modal-content')
            .html('<div class="modal-body text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2 text-muted">Memuat data...</p></div>')
            .load(url);
    });

    // 🌗 DARK MODE TOGGLE
    const container = $('#main-container');
    $('#themeToggle').click(function(){
        container.toggleClass('dark-mode');
        const isDark = container.hasClass('dark-mode');
        $(this).find('i').toggleClass('fa-moon fa-sun');
        $(this).find('span').text(isDark ? 'Light Mode' : 'Dark Mode');
    });
});
</script>

<style>
/* ===== Light Mode ===== */
.table thead { background-color: #5C5F72 !important; color: #fff !important; }
.table th, .table td { font-size: 13px; vertical-align: middle; }
.table-hover tbody tr:hover { background-color: #f7f8fa !important; transition: 0.2s; }
.alert-light { border-left: 4px solid #5C5F72; }
.card { border-radius: 10px; transition: 0.3s; }
.dataTables_filter input { border-radius: 6px; padding: 4px 8px; border: 1px solid #ced4da; }

/* ===== Dark Mode ===== */
.dark-mode {
    background-color: #1f2025;
    color: #e0e0e0;
}
.dark-mode .card {
    background-color: #2a2c33;
    color: #eaeaea;
    border-color: #444;
}
.dark-mode .table thead {
    background-color: #444655 !important;
    color: #fff;
}
.dark-mode .table-hover tbody tr:hover {
    background-color: #3a3c46 !important;
}
.dark-mode .btn-outline-primary,
.dark-mode .btn-outline-info {
    color: #eaeaea;
    border-color: #6c757d;
}
.dark-mode .alert-light {
    background-color: #2a2c33;
    color: #cfcfcf;
    border-color: #555;
    border-left: 4px solid #888;
}
.dark-mode input,
.dark-mode select {
    background-color: #3a3b42;
    color: #ddd;
    border-color: #555;
}
.dark-mode .form-label {
    color: #bbb;
}
.dark-mode #page-title {
    color: #fff !important;
}
.dark-mode .dataTables_filter input {
    background-color: #3b3b44;
    color: #ddd;
    border-color: #666;
}
</style>
