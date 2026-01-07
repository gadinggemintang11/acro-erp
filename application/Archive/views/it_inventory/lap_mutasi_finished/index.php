<div class="container-fluid">
    <!-- Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold text-dark mb-0">
            <i class="fas fa-boxes text-primary me-2"></i><?= htmlspecialchars($title); ?>
        </h3>
    </div>

    <!-- Filter Periode -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h6 class="fw-bold text-primary mb-0"><i class="fa fa-filter me-1"></i> Filter Periode</h6>
        </div>
        <div class="card-body pt-0">
            <form action="<?= base_url('LapMutasiFinished/index'); ?>" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label text-muted small">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm shadow-none border-secondary-subtle" value="<?= htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label text-muted small">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm shadow-none border-secondary-subtle" value="<?= htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100 shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rekapitulasi -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom py-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-chart-line me-1 text-primary"></i>
                Rekapitulasi Stok: <?= date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date)); ?>
            </h6>
            <div class="d-flex gap-2">
                <a href="<?= base_url('LapMutasiFinished/export_rekap_excel?' . http_build_query($_GET)); ?>" class="btn btn-success btn-sm shadow-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="<?= base_url('LapMutasiFinished/export_rekap_pdf?' . http_build_query($_GET)); ?>" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="rekapTable" class="table table-hover table-sm align-middle text-nowrap" width="100%">
                    <thead class="text-center align-middle sticky-top">
                        <tr>
                            <th rowspan="2">NO</th>
                            <th rowspan="2">KODE</th>
                            <th rowspan="2">NAMA BARANG</th>
                            <th rowspan="2">SATUAN</th>
                            <th rowspan="2" class="fw-bold">SALDO AWAL</th>
                            <th colspan="3" class="fw-bold border-start">MUTASI</th>
                            <th rowspan="2" class="fw-bold text-white bg-primary">SALDO AKHIR</th>
                            <th colspan="2" class="fw-bold border-start">STOCK OPNAME</th>
                            <th rowspan="2">AKSI</th>
                        </tr>
                        <tr>
                            <th>MASUK</th>
                            <th>KELUAR</th>
                            <th>ADJ</th>
                            <th>FISIK</th>
                            <th>SELISIH</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $no = 1;
                        $total_saldo_awal = $total_masuk = $total_keluar = $total_adj = $total_saldo_akhir = 0;
                        if (!empty($rekap_data)):
                            foreach ($rekap_data as $row):
                                $total_saldo_awal += $row->saldo_awal;
                                $total_masuk      += $row->pemasukan;
                                $total_keluar     += $row->pengeluaran;
                                $total_adj        += $row->penyesuaian;
                                $total_saldo_akhir+= $row->saldo_akhir;

                                $selisih_color = 'text-muted';
                                if ($row->jumlah_selisih !== NULL) {
                                    if ($row->jumlah_selisih > 0) $selisih_color = 'text-danger fw-semibold';
                                    elseif ($row->jumlah_selisih < 0) $selisih_color = 'text-success fw-semibold';
                                }

                                $detail_url = base_url('LapMutasiFinished/detail/' . $row->id . '?' . http_build_query($_GET));
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row->kode_barang); ?></td>
                            <td><?= htmlspecialchars($row->nama_barang); ?></td>
                            <td class="text-center"><?= htmlspecialchars($row->satuan); ?></td>
                            <td class="text-end"><?= number_format($row->saldo_awal, 2); ?></td>
                            <td class="text-end text-success"><?= number_format($row->pemasukan, 2); ?></td>
                            <td class="text-end text-danger"><?= number_format($row->pengeluaran, 2); ?></td>
                            <td class="text-end text-info"><?= number_format($row->penyesuaian, 2); ?></td>
                            <td class="text-end text-primary fw-bold"><?= number_format($row->saldo_akhir, 2); ?></td>
                            <td class="text-end"><?= $row->hasil_pencacahan ? number_format($row->hasil_pencacahan, 2) : '<span class="text-muted small">(N/A)</span>'; ?></td>
                            <td class="text-end <?= $selisih_color; ?>">
                                <?= $row->jumlah_selisih !== NULL ? number_format($row->jumlah_selisih, 2) : '<span class="text-muted small">(N/A)</span>'; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= $detail_url; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="12" class="text-center text-muted py-3">Tidak ada data untuk periode yang dipilih.</td></tr>
                        <?php endif; ?>
                    </tbody>

                    <?php if (!empty($rekap_data)): ?>
                    <tfoot class="fw-bold border-top">
                        <tr>
                            <td colspan="4" class="text-center bg-light">TOTAL</td>
                            <td class="text-end"><?= number_format($total_saldo_awal, 2); ?></td>
                            <td class="text-end text-success"><?= number_format($total_masuk, 2); ?></td>
                            <td class="text-end text-danger"><?= number_format($total_keluar, 2); ?></td>
                            <td class="text-end text-info"><?= number_format($total_adj, 2); ?></td>
                            <td class="text-end text-primary"><?= number_format($total_saldo_akhir, 2); ?></td>
                            <td colspan="3" class="bg-light"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <div class="alert alert-light border mt-4 shadow-sm">
                <h6 class="fw-bold text-primary mb-2"><i class="fas fa-info-circle me-1"></i> Catatan:</h6>
                <ul class="small mb-0 ps-3 text-secondary">
                    <li><b>(N/A)</b> artinya barang tidak termasuk dalam sesi Stock Opname berstatus “Completed”.</li>
                    <li><b>Selisih</b> = Saldo Akhir (Sistem) − Hasil Pencacahan (Fisik).</li>
                    <li><span class="text-danger">Merah</span> = sistem lebih besar dari fisik; <span class="text-success">Hijau</span> = sistem lebih kecil dari fisik.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.3/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.3/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.3/js/buttons.print.min.js"></script>

<script>
$(function() {
    $('#rekapTable').DataTable({
        scrollX: true,
        pageLength: 25,
        order: [],
        dom: '<"d-flex justify-content-between align-items-center mb-2"Bf>rtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel me-1"></i> Excel', className: 'btn btn-sm btn-success shadow-sm' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf me-1"></i> PDF', className: 'btn btn-sm btn-danger shadow-sm', orientation: 'landscape', pageSize: 'A4' },
            { extend: 'print', text: '<i class="fas fa-print me-1"></i> Print', className: 'btn btn-sm btn-secondary shadow-sm' }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Cari barang...",
            lengthMenu: "Tampilkan _MENU_ baris",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            zeroRecords: "Tidak ada data ditemukan",
            paginate: { previous: "←", next: "→" }
        }
    });
});
</script>

<style>
.table thead {
    background-color: #5C5F72 !important; /* Warna abu elegan */
    color: #fff !important;
}
.table th, .table td {
    font-size: 13px;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: #f6f7fb !important;
    transition: 0.25s;
}
.table tfoot td {
    background-color: #f1f3f5;
    font-weight: 600;
}
.dataTables_wrapper .dataTables_filter input {
    border-radius: 6px;
    border: 1px solid #ced4da;
    padding: 4px 8px;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.25rem 0.5rem;
}
.card {
    border-radius: 10px;
}
</style>
