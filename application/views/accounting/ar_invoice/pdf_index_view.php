<!DOCTYPE html><html><head><title><?= $title; ?></title><style>body{font-family:Arial,sans-serif;font-size:10px;} .table{width:100%;border-collapse:collapse;} .table th, .table td{border:1px solid #000;padding:5px;} .table th{background-color:#f2f2f2;text-align:center;} .text-right{text-align:right;} h3{text-align:center;}</style></head><body>
    <h3><?= $title; ?></h3>
    <table class="table">
        <thead>
            <tr>
                <th>No. Faktur</th><th>Tgl. Faktur</th><th>Customer</th><th>Tipe</th>
                <th class="text-right">Total Tagihan</th><th class="text-right">Sisa Piutang</th>
                <th>Status Bayar</th><th>Status Jurnal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $inv): 
                $tipe = '';
                if ($inv->source_type == 'SCRAP_OUT') {
                    $tipe = 'Scrap Out';
                } else {
                    $tipe = ($inv->customer_type == 'Lokal') ? 'FG (Lokal)' : 'FG (Ekspor)';
                }
            ?>
            <tr>
                <td><?= $inv->invoice_no; ?></td>
                <td><?= date('d-m-Y', strtotime($inv->invoice_date)); ?></td>
                <td><?= $inv->nama_customer; ?></td>
                <td><?= $tipe; ?></td>
                <td class="text-right"><?= number_format($inv->total_invoice, 2); ?></td>
                <td class="text-right"><?= number_format($inv->total_invoice - $inv-total_paid, 2); ?></td>
                <td><?= $inv->payment_status; ?></td>
                <td><?= $inv->status; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body></html>