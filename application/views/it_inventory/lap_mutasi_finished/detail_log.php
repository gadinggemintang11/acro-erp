<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
            <h5 class="h5 mb-0 text-gray-600"><?= $item->kode_barang; ?></h5>
        </div>
        <a href="<?= base_url('LapMutasiFinished?' . http_build_query($_GET)); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Rekapitulasi
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                Periode: <?= date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date)); ?>
            </h6>
            <div>
                <a href="<?= base_url('LapMutasiFinished/export_detail_excel/' . $fg_id . '?' . http_build_query($_GET)); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Ekspor Excel
                </a>
                <a href="<?= base_url('LapMutasiFinished/export_detail_pdf/' . $fg_id . '?' . http_build_query($_GET)); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> Ekspor PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="detailLogTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center bg-gray-100">
                            <th colspan="4" class="text-left">SALDO AWAL PER <?= date('d M Y', strtotime($start_date)); ?></th>
                            <th colspan="3" class="text-right font-weight-bold"><?= number_format($saldo_awal, 2); ?></th>
                        </tr>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Dokumen</th>
                            <th>Keterangan</th>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Saldo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $saldo = $saldo_awal;
                        $total_masuk = 0;
                        $total_keluar = 0;
                        if (!empty($mutasi_data)):
                            foreach ($mutasi_data as $row): 
                                $saldo = $row->saldo_akhir; // Selalu gunakan saldo dari ledger
                                $total_masuk += $row->qty_in;
                                $total_keluar += $row->qty_out;
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($row->transaction_date)); ?></td>
                                <td><?= $row->doc_number; ?></td>
                                <td><?= $row->description; ?></td>
                                <td class="text-right text-success"><?= $row->qty_in > 0 ? number_format($row->qty_in, 2) : '-'; ?></td>
                                <td class="text-right text-danger"><?= $row->qty_out > 0 ? number_format($row->qty_out, 2) : '-'; ?></td>
                                <td class="text-right font-weight-bold"><?= number_format($saldo, 2); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-info btn-circle btn-sm btn-trace" 
                                            data-type="<?= $row->transaction_type; ?>"
                                            data-id="<?= $row->transaction_id; ?>"
                                            data-doc="<?= $row->doc_number; ?>"
                                            title="Deep Traceability">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada transaksi pada periode ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="thead-light">
                        <tr class="text-right font-weight-bold bg-gray-100">
                            <td colspan="4">TOTAL MUTASI</td>
                            <td class="text-success"><?= number_format($total_masuk, 2); ?></td>
                            <td class="text-danger"><?= number_format($total_keluar, 2); ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr class="text-right font-weight-bold bg-gray-100">
                            <td colspan="6">SALDO AKHIR PER <?= date('d M Y', strtotime($end_date)); ?></td>
                            <td><?= number_format($saldo, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
<div class="modal fade" id="traceabilityModal" tabindex="-1" role="dialog" aria-labelledby="traceabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="traceabilityModalLabel">Deep Traceability: ...</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div id="modal-export-buttons">
                    </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handler untuk tombol traceability
    const table = document.getElementById('detailLogTable');
    if (table) {
        table.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-trace');
            if (button) {
                const type = button.dataset.type;
                const id = button.dataset.id;
                const docNumber = button.dataset.doc;
                
                const modal = $('#traceabilityModal');
                const modalBody = modal.find('.modal-body');
                const modalTitle = modal.find('.modal-title');
                const modalExport = modal.find('#modal-export-buttons');

                modalTitle.text('Deep Traceability: ' + docNumber);
                modalBody.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2">Memuat data...</p></div>');
                modalExport.html(''); // Kosongkan tombol ekspor
                modal.modal('show');

                // Panggil AJAX
                $.ajax({
                    url: '<?= base_url("LapMutasiFinished/ajax_traceability_detail"); ?>',
                    type: 'GET',
                    data: { type: type, id: id },
                    success: function(response) {
                        modalBody.html(response);
                        // Tambahkan tombol ekspor berdasarkan tipe
                        let exportUrl = '';
                        if (type === 'fg_in') {
                            exportUrl = '<?= base_url("LapMutasiFinished/export_doc_penerimaan_pdf/"); ?>' + id;
                        } else if (type === 'fg_out') {
                            exportUrl = '<?= base_url("LapMutasiFinished/export_doc_pengiriman_pdf/"); ?>' + id;
                        }
                        
                        if(exportUrl) {
                            modalExport.html(
                                `<a href="${exportUrl}" target="_blank" class="btn btn-danger btn-sm">
                                    <i class="fas fa-file-pdf"></i> Ekspor Dokumen Ini (PDF)
                                 </a>`
                            );
                        }
                    },
                    error: function() {
                        modalBody.html('<div class="alert alert-danger">Gagal memuat data. Silakan coba lagi.</div>');
                    }
                });
            }
        });
    }
});
</script>