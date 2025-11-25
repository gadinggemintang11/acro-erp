<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delivery Order <?= $header->delivery_number ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #333; }
        h3 { text-align: center; color: #2a4d9b; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background-color: #f3f6ff; font-weight: bold; text-align: center; }
        .no-border td { border: none; }
        .badge {
            display:inline-block;
            padding:2px 6px;
            border-radius:4px;
            color:white;
            font-size:10pt;
        }
        .badge.success { background-color:#28a745; }
        .badge.secondary { background-color:#6c757d; }
        .badge.danger { background-color:#dc3545; }
    </style>
</head>
<body>
    <h3>DELIVERY ORDER<br><?= htmlspecialchars($header->delivery_number) ?></h3>

    <table class="no-border">
        <tr>
            <td width="25%"><strong>Customer</strong></td>
            <td width="45%">: <?= htmlspecialchars($header->nama_customer) ?></td>
            <td width="15%"><strong>Tanggal</strong></td>
            <td width="15%">: <?= htmlspecialchars($header->delivery_date) ?></td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td colspan="3">: <?= htmlspecialchars($header->customer_address ?? '-') ?></td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>
                <?php 
                    $cls = ($header->status=='Confirmed') ? 'success' : (($header->status=='Draft') ? 'secondary':'danger');
                ?>
                <span class="badge <?= $cls ?>"><?= htmlspecialchars($header->status) ?></span>
            </td>
            <td><strong>BC Type</strong></td>
            <td>: <?= htmlspecialchars($header->bc_type_desc ?? '-') ?></td>
        </tr>
        <tr>
            <td><strong>BC Number</strong></td>
            <td>: <?= htmlspecialchars($header->bc_number ?? '-') ?></td>
            <td><strong>BC Date</strong></td>
            <td>: <?= htmlspecialchars($header->bc_date ?? '-') ?></td>
        </tr>
    </table>

    <h4>Detail Item</h4>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>SC</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Qty Kirim</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($details as $d): ?>
            <tr>
                <td align="center"><?= $no++ ?></td>
                <td align="center"><?= htmlspecialchars($d->sc_number ?? '-') ?></td>
                <td><?= htmlspecialchars($d->kode_barang) ?></td>
                <td><?= htmlspecialchars($d->nama_barang) ?></td>
                <td align="right"><?= number_format($d->qty_shipped, 2) ?></td>
                <td align="center"><?= htmlspecialchars($d->uom) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br><br>
    <table class="no-border">
        <tr>
            <td width="70%"></td>
            <td width="30%" align="center">
                <strong>Dibuat oleh,</strong><br><br><br><br>
                (<?= htmlspecialchars($header->created_by_name ?? '_____________') ?>)
            </td>
        </tr>
    </table>
</body>
</html>
