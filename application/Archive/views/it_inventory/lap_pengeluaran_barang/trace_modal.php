<?php
// --- Helper untuk menentukan Data Transaksi ---
if (!empty($trace)) {
    $tx_type = $trace->transaction_type; 
    $partner_name = $trace->partner_name ?? 'N/A';
    
    // --- Logika Penentuan Dokumen Sumber & BC ---
    $doc_no = $trace->source_doc_no ?? '-';
    $doc_date = $trace->source_doc_date ? date('d M Y', strtotime($trace->source_doc_date)) : '-';
    $item_code = $trace->item_code ?? '-';
    $item_name = $trace->item_name ?? '-';
    $item_unit = $trace->item_unit ?? '-';
    $sales_contract_ref = $trace->sales_contract_ref ?? '-';
    $nilai_keluar = number_format(($trace->qty_out ?? 0) * ($trace->unit_value ?? 0), 2);
    
    // BC Data
    $bc_desc = $trace->jenis_bc_desc ?? '-';
    $bc_number = $trace->nomor_bc_ref ?? '-';
    $bc_date = $trace->tanggal_bc_ref ? date('d M Y', strtotime($trace->tanggal_bc_ref)) : '-';
}
?>

<?php if (empty($trace)): ?>
    <div class="alert alert-warning text-center">Data transaksi tidak ditemukan.</div>
<?php else: ?>
    <div class="modal-content">
        <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="fas fa-history mr-2"></i> Traceability: <?= htmlspecialchars($item_name); ?></h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= base_url('LapPengeluaranBarang/export_trace_excel/' . ($trace->ledger_id ?? '0')); ?>" target="_blank" class="btn btn-sm btn-success shadow-sm me-2">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
                <a href="<?= base_url('LapPengeluaranBarang/export_trace_pdf/' . ($trace->ledger_id ?? '0')); ?>" target="_blank" class="btn btn-sm btn-danger shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                </a>
            </div>

        <h6 class="text-danger font-weight-bold mb-3">
            <i class="fas fa-arrow-up"></i> Tipe Transaksi: <?= strtoupper($tx_type); ?>
        </h6>

        <div class="row">
            <div class="col-md-5">
                <table class="table table-sm table-borderless table-item-info">
                    <tr><td colspan="2" class="font-weight-bold border-bottom">INFO ITEM</td></tr>
                    <tr><td width="50%">Kode Barang</td><td>: <strong><?= $item_code; ?></strong></td></tr>
                    <tr><td>Nama Barang</td><td>: <?= $item_name; ?></td></tr>
                    <tr><td>Qty Keluar</td><td>: <strong><?= number_format($trace->qty_out ?? 0, 2); ?> <?= $item_unit; ?></strong></td></tr>
                    <tr><td>Nilai/Unit (HPP)</td><td>: Rp <?= number_format($trace->unit_value ?? 0, 2); ?></td></tr>
                    <tr><td>Total Nilai</td><td>: <strong class="text-danger">Rp <?= $nilai_keluar; ?></strong></td></tr>
                </table>
            </div>

            <div class="col-md-7">
                <table class="table table-sm table-borderless table-doc-info">
                    <tr><td colspan="2" class="font-weight-bold border-bottom">DOKUMEN PENDUKUNG</td></tr>
                    <tr><td width="35%">Partner</td><td>: <strong><?= $partner_name; ?></strong></td></tr>
                    <tr><td>No. Dokumen</td><td>: <strong><?= $doc_no; ?></strong></td></tr>
                    <tr><td>Tgl Dokumen</td><td>: <?= $doc_date; ?></td></tr>
                    <tr><td>Sales Contract</td><td>: <?= $sales_contract_ref; ?></td></tr>
                </table>
            </div>
        </div>
        
        <h6 class="text-warning font-weight-bold mt-3 mb-2"><i class="fas fa-file-invoice"></i> Data Dokumen Pabean</h6>
        <table class="table table-sm table-striped">
            <tbody>
                <tr>
                    <td width="30%">Jenis BC</td>
                    <td>: <strong><?= htmlspecialchars($bc_desc); ?></strong></td>
                </tr>
                <tr>
                    <td>Nomor BC</td>
                    <td>: <?= htmlspecialchars($bc_number); ?></td>
                </tr>
                <tr>
                    <td>Tanggal BC</td>
                    <td>: <?= $bc_date; ?></td>
                </tr>
            </tbody>
        </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
    </div>
<?php endif; ?>