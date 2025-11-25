<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:12px;} .table{width:100%;border-collapse:collapse;} .table th,.table td{border:1px solid #999;padding:8px;} .table-sm th,.table-sm td{padding:5px;} .table-noborder th,.table-noborder td{border:none;padding:4px;} h2{text-align:center;} .card{border:1px solid #ddd;border-radius:5px;margin-bottom:15px;} .card-header{background-color:#f7f7f7;padding:10px;font-weight:bold;border-bottom:1px solid #ddd;} .card-body{padding:10px;} .text-right{text-align:right;} .text-center{text-align:center;}</style></head><body>
    <h2><?= $title; ?></h2>
    <p style="text-align:center;font-size:14px;"><strong>No: <?= $detail->delivery_number; ?></strong></p>
    
    <div class="card">
        <div class="card-header">Info Dokumen Pengiriman</div>
        <div class="card-body">
            <table class="table table-noborder">
                <tr><td style="width:30%;">Tgl. Pengiriman:</td><td><strong><?= date('d M Y H:i', strtotime($detail->shipped_at)); ?></strong></td></tr>
                <tr><td>Dikirim Oleh:</td><td><strong><?= $detail->shipped_by_user; ?></strong></td></tr>
                <tr><td>Qty Dikirim:</td><td><strong style="font-size:1.2em;"><?= number_format($detail->qty_shipped, 2); ?> PCS</strong></td></tr>
                <tr><td>Catatan:</td><td><strong><?= $detail->notes ?: '-'; ?></strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Info Customer</div>
        <div class="card-body">
            <table class="table table-noborder">
                <tr><td style="width:30%;">Nama Customer:</td><td><strong><?= $detail->nama_customer; ?></strong></td></tr>
                <tr><td>Alamat:</td><td><strong><?= $detail->customer_address; ?></strong></td></tr>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Info Dokumen Pendukung</div>
        <div class="card-body">
            <table class="table table-noborder">
                <tr><td style="width:30%;">Sales Contract:</td><td><strong><?= $detail->sc_number; ?></strong></td></tr>
                <tr><td>Jenis BC:</td><td><strong><?= $detail->bc_type_description; ?> (<?= $detail->bc_type_id; ?>)</strong></td></tr>
                <tr><td>No. BC:</td><td><strong><?= $detail->bc_number; ?></strong></td></tr>
                <tr><td>Tgl. BC:</td><td><strong><?= $detail->bc_date ? date('d M Y', strtotime($detail->bc_date)) : '-'; ?></strong></td></tr>
            </table>
        </div>
    </div>
</body></html>