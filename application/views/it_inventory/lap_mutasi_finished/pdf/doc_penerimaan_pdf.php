<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:12px;} .table{width:100%;border-collapse:collapse;} .table th,.table td{border:1px solid #999;padding:8px;} .table-sm th,.table-sm td{padding:5px;} .table-noborder th,.table-noborder td{border:none;padding:4px;} h2{text-align:center;} .card{border:1px solid #ddd;border-radius:5px;margin-bottom:15px;} .card-header{background-color:#f7f7f7;padding:10px;font-weight:bold;border-bottom:1px solid #ddd;} .card-body{padding:10px;} .text-right{text-align:right;} .text-center{text-align:center;}</style></head><body>
    <h2><?= $title; ?></h2>
    <p style="text-align:center;font-size:14px;"><strong>No: <?= $detail->receipt_number; ?></strong></p>
    
    <div class="card">
        <div class="card-header">Info Dokumen Penerimaan</div>
        <div class="card-body">
            <table class="table table-noborder">
                <tr><td style="width:30%;">Tgl. Konfirmasi:</td><td><strong><?= date('d M Y H:i', strtotime($detail->confirmed_at)); ?></strong></td></tr>
                <tr><td>Diterima Oleh:</td><td><strong><?= $detail->confirmed_by_user; ?></strong></td></tr>
                <tr><td>Qty Diterima:</td><td><strong style="font-size:1.2em;"><?= number_format($detail->qty_received, 2); ?> PCS</strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Info Sumber Produksi</div>
        <div class="card-body">
            <table class="table table-noborder">
                <tr><td style="width:30%;">No. Produksi:</td><td><strong><?= $detail->production_number; ?></strong></td></tr>
                <tr><td>No. Work Order:</td><td><strong><?= $detail->wo_number; ?></strong></td></tr>
                <tr><td>No. Sales Order:</td><td><strong><?= $detail->sc_number; ?></strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Bahan Baku Digunakan</div>
        <div class="card-body">
            <table class="table table-sm">
                <thead class="text-center">
                    <tr>
                        <th>Kode Material</th><th>Nama Material</th><th>Qty Issued (MR)</th><th>Qty Terpakai (Prod)</th><th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($materials)): foreach ($materials as $mat): ?>
                    <tr>
                        <td><?= $mat['kode_material']; ?></td>
                        <td><?= $mat['nama_material']; ?></td>
                        <td class="text-right"><?= number_format($mat['issued_qty'], 2); ?></td>
                        <td class="text-right"><?= number_format($mat['qty_terpakai'], 2); ?></td>
                        <td class="text-center"><?= $mat['satuan']; ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">Data material tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body></html>