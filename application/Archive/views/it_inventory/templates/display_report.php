<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<style>
    /* === Tampilan Modern & Elegan === */
    /* ... (CSS Anda sebelumnya) ... */
</style>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header d-flex flex-row align-items-center justify-content-between bg-light py-3 border-bottom">
        <h6 class="m-0 fw-bold text-dark">
            <i class="fas fa-file-import text-primary me-2"></i> Hasil Laporan: Laporan Pemasukan Barang
        </h6>
        <div>
            <a href="<?= base_url($this->uri->segment(1).'/export_excel?' . http_build_query($filters)); ?>" target="_blank" class="btn btn-success btn-sm shadow-sm me-1">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="<?= base_url($this->uri->segment(1).'/export_pdf?' . http_build_query($filters)); ?>" target="_blank" class="btn btn-danger btn-sm shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

   <div class="card-body">
        <div class="alert alert-light border mb-3 shadow-sm">
            <i class="fas fa-calendar-alt text-primary me-1"></i>
            Periode: <strong><?= date('d M Y', strtotime($filters['start_date'])); ?></strong> s/d 
            <strong><?= date('d M Y', strtotime($filters['end_date'])); ?></strong>

            <?php if (!empty($filters['bc_type'])): ?>
                <span class="ms-3"><i class="fas fa-file-code me-1 text-muted"></i> Jenis BC:</span>
                <strong><?= htmlspecialchars($filters['bc_type']); ?></strong>
            <?php endif; ?>

            <?php if (!empty($filters['search'])): ?>
                <span class="ms-3"><i class="fas fa-search me-1 text-muted"></i> Pencarian:</span>
                <strong><?= htmlspecialchars($filters['search']); ?></strong>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle text-nowrap" id="reportDataTable">
                <thead class="text-center align-middle">
                    <tr>
                        <th rowspan="2">No</th>
                        <th colspan="3">Data Dokumen Pabean</th>
                        <th colspan="2">Bukti Penerimaan Barang</th>
                        <th rowspan="2">Pengirim Barang</th>
                        <th rowspan="2">Kode Barang</th>
                        <th rowspan="2">Nama Barang</th>
                        <th rowspan="2">Jumlah</th>
                        <th rowspan="2">Satuan</th>
                        <th rowspan="2">Mata Uang</th>
                        <th rowspan="2">Nilai Barang</th>
                        <th rowspan="2">Aksi</th>
                    </tr>
                    <tr>
                        <th>Jenis BC</th>
                        <th>Nomor BC</th>
                        <th>Tanggal BC</th>
                        <th>Nomor Penerimaan</th>
                        <th>Tanggal Penerimaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report_data)): ?>
                        <tr>
                            <td colspan="14" class="text-center text-muted py-3">
                                <i class="fas fa-info-circle me-1"></i> Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td class="text-center"><?= $start_no++; ?></td>
                                <td><?= htmlspecialchars($row['bc_type_code'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['bc_number'] ?? '-'); ?></td>
                                <td><?= isset($row['bc_date']) ? date('d/m/Y', strtotime($row['bc_date'])) : '-'; ?></td>
                                <td><?= htmlspecialchars($row['grn_number'] ?? '-'); ?></td>
                                <td><?= isset($row['grn_date']) ? date('d/m/Y', strtotime($row['grn_date'])) : '-'; ?></td>
                                <td><?= htmlspecialchars($row['nama_supplier'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['item_code'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['item_name'] ?? '-'); ?></td>
                                <td class="text-end"><?= isset($row['qty_in']) ? number_format($row['qty_in'], 2) : '-'; ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['item_unit'] ?? '-'); ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['currency_code'] ?? 'IDR'); ?></td>
                                <td class="text-end"><?= isset($row['unit_value_idr']) ? number_format($row['unit_value_idr'], 2) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if(isset($row['ledger_id'])): ?>
                                        <button class="btn btn-outline-primary btn-sm btn-trace"
                                            data-ledgerid="<?= $row['ledger_id']; ?>"
                                            data-itemid="<?= $row['item_id'] ?? ''; ?>"
                                            data-itemname="<?= htmlspecialchars($row['item_name'] ?? 'Item'); ?>"
                                            data-toggle="modal" data-target="#traceabilityModal" 
                                            title="Lihat Riwayat (Trace)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

       <div class="mt-3"><?= $pagination; ?></div>

        <p class="text-muted small mt-2">
            Menampilkan <strong><?= count($report_data); ?></strong> dari total <strong><?= $total_rows; ?></strong> baris data.
        </p>
    </div>
</div>

<!-- Modal Traceability -->
<div class="modal fade" id="traceabilityModal" tabindex="-1" role="dialog" aria-labelledby="traceabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-search-location me-2"></i> Traceability Detail - 
                    <span id="traceItemName"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div id="traceContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p class="text-muted mb-0">Memuat data...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ JS original tetap dipertahankan agar tombol berfungsi -->
<script>
$(document).ready(function() {
    $('.btn-trace').on('click', function() {
        var button = $(this);
        var ledgerId = button.data('ledgerid');
        var itemId = button.data('itemid');
        var itemName = button.data('itemname');

        $('#traceItemName').text(itemName);
        $('#traceContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat data...</p></div>');

        var traceUrl = "<?= base_url($this->uri->segment(1) . '/traceability'); ?>";

        $.ajax({
            url: traceUrl,
            type: "GET",
            dataType: "json",
            data: { ledger_id: ledgerId, item_id: itemId },
            success: function(response) {
                if (response.success) {
                    $('#traceContent').html(response.html);
                } else {
                    $('#traceContent').html('<div class="alert alert-danger">Gagal memuat detail: ' + (response.message || 'Terjadi kesalahan.') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#traceContent').html('<div class="alert alert-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</div>');
            }
        });
    });

    $('#traceabilityModal').on('hidden.bs.modal', function () {
        $('#traceContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat data...</p></div>');
        $('#traceItemName').text('');
    });
});
</script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>




    $(document).ready(function() {
        $('#reportDataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
            },
            responsive: true
        });
    });
</script>
<style>
.table thead {
    background-color: #5C5F72 !important;
    color: #fff !important;
}


</style>
