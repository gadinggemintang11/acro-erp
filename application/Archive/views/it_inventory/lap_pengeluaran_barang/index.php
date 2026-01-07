<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-sign-out-alt text-danger mr-2"></i><?= $title; ?></h1>
        <div class="d-none d-sm-inline-block">
            <button type="button" id="btn-export-excel" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-excel mr-1"></i> Ekspor Excel</button>
            <button type="button" id="btn-export-pdf" class="btn btn-sm btn-danger shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Ekspor PDF</button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form id="filter-form" action="<?= base_url('LapPengeluaranBarang/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="tx_type">Tipe Pengeluaran</label>
                        <select name="tx_type" id="tx_type" class="form-control">
                            <option value="">Semua</option>
                            <?php foreach($tx_options as $code => $name): ?>
                                <option value="<?= $code; ?>" <?= $filters['tx_type'] == $code ? 'selected' : ''; ?>><?= $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search">Cari (Kode/Nama)</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Cari Item/Dokumen...">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> Tampilkan</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-danger text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-list-alt mr-1"></i> Data Pengeluaran Barang</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="pengeluaranTable" width="100%" cellspacing="0" style="font-size: 0.85rem;">
                    
                    <thead class="bg-dark text-white text-center">
                        <tr>
                            <th rowspan="2" class="align-middle">NO</th>
                            <th colspan="3" class="align-middle border-light">DATA DOKUMEN PABEAN</th>
                            <th colspan="3" class="align-middle">BUKTI PENGELUARAN BARANG</th>
                            <th colspan="4" class="align-middle">DETAIL BARANG</th>
                            <th colspan="2" class="align-middle">NILAI KELUAR</th>
                            <th rowspan="2" class="align-middle">AKSI</th>
                        </tr>
                        <tr>
                            <th class="border-light">JENIS BC</th>
                            <th class="border-light">NOMOR BC</th>
                            <th class="border-light">TANGGAL BC</th>
                            
                            <th>NOMOR PENGELUARAN</th>
                            <th>TANGGAL PENGELUARAN</th>
                            <th>PENERIMA BARANG</th>
                            
                            <th>KODE BARANG</th>
                            <th>NAMA BARANG</th>
                            <th class="text-right">JUMLAH</th>
                            <th>SATUAN</th>
                            
                            <th>MATA UANG</th>
                            <th class="text-right">NILAI BARANG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr><td colspan="14" class="text-center py-4">Tidak ada data pengeluaran ditemukan.</td></tr>
                        <?php else: $no = $start_no; foreach ($report_data as $row): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($row->jenis_bc ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row->nomor_bc ?? '-'); ?></td>
                                <td class="text-nowrap"><?= $row->tanggal_bc ? date('d-m-Y', strtotime($row->tanggal_bc)) : '-'; ?></td>
                                
                                <td class="font-weight-bold text-danger">
                                    <span class="badge badge-secondary mr-1">
                                        <?php 
                                            if ($row->tx_type_raw == 'production_out') echo 'SUBKON';
                                            elseif ($row->tx_type_raw == 'fg_out') echo 'FG';
                                            elseif ($row->tx_type_raw == 'OUT_SCRAP_BC') echo 'SCRAP';
                                            else echo 'ADJ OUT';
                                        ?>
                                    </span>
                                    <?= htmlspecialchars($row->nomor_pengeluaran); ?>
                                </td>
                                <td class="text-nowrap"><?= date('d-m-Y', strtotime($row->tanggal_pengeluaran)); ?></td>
                                <td><?= htmlspecialchars($row->partner_name ?? '-'); ?></td>

                                <td><?= htmlspecialchars($row->item_code); ?></td>
                                <td><?= htmlspecialchars($row->item_name); ?></td>
                                <td class="text-right text-danger font-weight-bold"><?= number_format($row->jumlah, 2); ?></td>
                                <td><?= htmlspecialchars($row->item_unit ?? '-'); ?></td> 
                                
                                <td class="text-nowrap font-weight-bold"><?= htmlspecialchars($row->mata_uang); ?></td>
                                <td class="text-right font-weight-bold">
                                    <?php 
                                        // Tampilkan Nilai Valuta Asing jika Mata Uang bukan IDR, atau nilai IDR jika IDR
                                        if (($row->mata_uang ?? 'IDR') != 'IDR' && ($row->nilai_barang_fc ?? 0) > 0) {
                                            echo number_format($row->nilai_barang_fc, 2);
                                        } else {
                                            echo number_format($row->nilai_barang_idr ?? 0, 2);
                                        }
                                    ?>
                                </td>
                                
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info btn-trace-detail" data-ledger-id="<?= $row->ledger_id; ?>" title="Lihat Detail Trace">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <?= $pagination; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="traceModal" tabindex="-1" role="dialog" aria-labelledby="traceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="traceModalLabel"><i class="fas fa-history mr-2"></i> Detail Transaksi Pengeluaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="traceModalBody">
                </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    function buildQueryString() {
        return $.param({
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            tx_type: $('#tx_type').val(),
            search: $('#search').val()
        });
    }

    // Inisialisasi DataTables
    $('#pengeluaranTable').DataTable({
        "paging": false,
        "searching": true, 
        "info": false,
        "order": [],
        "columnDefs": [
            { "targets": [0, 2, 3, 4, 5, 6, 13], "orderable": false }
        ]
    });
    
    // Inisialisasi Select2 untuk filter
    if ($.fn.select2) {
        $('#tx_type').select2({ placeholder: "Pilih Tipe Transaksi", allowClear: true, width: '100%' });
    }

    // Event listener untuk tombol Detail (Traceability)
    $('#pengeluaranTable').on('click', '.btn-trace-detail', function() {
        var ledgerId = $(this).data('ledger-id');
        var url = "<?= base_url('LapPengeluaranBarang/trace_detail'); ?>?ledger_id=" + ledgerId;

        $('#traceModalBody').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2">Memuat detail...</p></div>');
        $('#traceModal').modal('show');
        
        $.get(url, function(data) {
            $('#traceModalBody').html(data);
        }).fail(function(jqXHR) {
            var message = "Gagal memuat data. ";
            if (jqXHR.status === 500) {
                 message += "Internal Server Error. Cek log PHP.";
            }
            $('#traceModalBody').html('<div class="alert alert-danger text-center">' + message + '</div>');
        });
    });

    // Event listener untuk Export buttons
    $('#btn-export-excel').on('click', function() {
        var url = "<?= base_url('LapPengeluaranBarang/export_excel'); ?>?" + buildQueryString();
        window.open(url, '_blank');
    });

    $('#btn-export-pdf').on('click', function() {
        var url = "<?= base_url('LapPengeluaranBarang/export_pdf'); ?>?" + buildQueryString();
        window.open(url, '_blank');
    });
});
</script>