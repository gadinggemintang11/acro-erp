<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:0;} .container{width:90%;margin:20px auto;} h2{text-align:center;margin-bottom:0;} p{text-align:center;margin-top:0;}.table{width:100%;border-collapse:collapse;margin-top:20px;} .table th, .table td{border:1px solid #000;padding:8px;} .table th{background-color:#f2f2f2;text-align:center;} .text-right{text-align:right;} .header-table{width:100%;margin-bottom:30px;} .header-table td{padding:5px;} .total-table{width:50%;float:right;margin-top:20px;} .total-table td{padding:5px;}</style></head><body>
    <div class="container">
        <h2><?= $title; ?></h2>
        <p><?= $header->doc_no; ?></p>
        
        <table class="header-table">
            <tr>
                <td style="width:20%;"><strong>Tanggal:</strong></td>
                <td style="width:40%;"><?= date('d M Y', strtotime($header->doc_date)); ?></td>
                <td style="width:20%;"><strong>Mata Uang:</strong></td>
                <td style="width:20%;"><?= $header->currency; ?></td>
            </tr>
            <tr>
                <td><strong>Akun Kas/Bank:</strong></td>
                <td><?= $header->payment_account_name; ?></td>
                <td><strong>Kurs:</strong></td>
                <td><?= number_format($header->exchange_rate, 4); ?></td>
            </tr>
             <tr>
                <td><strong>Keterangan:</strong></td>
                <td colspan="3"><?= $header->description; ?></td>
            </tr>
        </table>

        <h4>Detail Transaksi</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Jumlah (<?= $header->currency; ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($details as $d): ?>
                <tr>
                    <td><?= $d->account_code; ?></td>
                    <td><?= $d->account_name; ?></td>
                    <td><?= $d->description; ?></td>
                    <td class="text-right"><?= number_format($d->amount, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <table class="table total-table">
            <tr style="font-weight:bold;font-size:1.1em;background:#f2f2f2;">
                <th>Total (<?= $header->currency; ?>)</th>
                <td class="text-right"><?= number_format($header->total_amount, 2); ?></td>
            </tr>
            <?php if($header->currency != 'IDR'): ?>
            <tr style="font-size:0.9em;">
                <th>(Ekuivalen IDR)</th>
                <td class="text-right">Rp <?= number_format($header-total_amount_local, 2); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</body></html>