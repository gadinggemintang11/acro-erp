<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background-color: #f2f2f2; text-align: left; width: 35%; }
        h2 { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($title); ?></h2>

    <table class="table">
        <tbody>
            <tr>
                <th>Jenis Transaksi</th>
                <td><?= htmlspecialchars($trace->transaction_type ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Tanggal Transaksi</th>
                <td><?= $trace->transaction_date ? date('d M Y H:i:s', strtotime($trace->transaction_date)) : '-'; ?></td>
            </tr>
            <tr>
                <th>Dokumen Sumber</th>
                <td><?= htmlspecialchars(($trace->source_doc_type ?? '-') . ' / ' . ($trace->source_doc_no ?? $trace->source_doc_id ?? '-')); ?></td>
            </tr>
            <tr>
                <th>Partner (Customer/Supplier)</th>
                <td><?= htmlspecialchars($trace->partner_name ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Kode Barang</th>
                <td><?= htmlspecialchars($trace->item_code ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Nama Barang</th>
                <td><?= htmlspecialchars($trace->item_name ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Jumlah Keluar</th>
                <td><?= number_format((float)($trace->jumlah ?? 0), 2); ?> <?= htmlspecialchars($trace->item_unit ?? ''); ?></td>
            </tr>
            <tr>
                <th>Nilai Barang (IDR)</th>
                <td><?= number_format((float)($trace->nilai_barang_idr ?? 0), 2); ?></td>
            </tr>
            <tr>
                <th>Jenis BC</th>
                <td><?= htmlspecialchars($trace->jenis_bc_desc ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Nomor BC</th>
                <td><?= htmlspecialchars($trace->nomor_bc_ref ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Sales Order Number</th>
                <td><?= htmlspecialchars($trace->sales_contract_ref ?? '-'); ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
