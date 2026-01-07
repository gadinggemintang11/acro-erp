<!DOCTYPE html>
<html>
<head>
    <title>Surat Jalan Subkon: <?= htmlspecialchars($header->subkon_out_no); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #999; padding: 8px; }
        thead th { background-color: #f2f2f2; }
        .header-table { border: none; }
        .header-table td { border: none; padding: 2px; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
    </style>
</head>
<body>
    <h1>SURAT JALAN SUBKON</h1>
    
    <table class="header-table">
        <tr>
            <td style="width: 15%;">No. Surat Jalan</td>
            <td style="width: 35%;">: <strong><?= htmlspecialchars($header->subkon_out_no); ?></strong></td>
            <td style="width: 15%;">No. Kontrak</td>
            <td style="width: 35%;">: <?= htmlspecialchars($header->subkon_order_no); ?></td>
        </tr>
        <tr>
            <td>Tanggal Kirim</td>
            <td>: <?= date('d M Y', strtotime($header->shipping_date)); ?></td>
            <td>Supplier</td>
            <td>: <?= htmlspecialchars($header->nama_supplier); ?></td>
        </tr>
    </table>

    <hr>
    <h3>Detail Item yang Dikirim</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Item</th>
                <th>Nama Item</th>
                <th class="text-right">Qty Kirim</th>
                <th>Satuan</th>
                <th class="text-right">Nilai / Pcs (IDR)</th>
                <th class="text-right">Total Nilai (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($detail_items)): ?>
                <tr><td colspan="7" style="text-align: center;">Tidak ada item.</td></tr>
            <?php else: $no = 1; $grand_total = 0; foreach ($detail_items as $item): $grand_total += $item->total_value; ?>
                <tr>
                    <td style="text-align: center;"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($item->item_code); ?></td>
                    <td><?= htmlspecialchars($item->item_name); ?></td>
                    <td class="text-right"><?= number_format($item->qty_kirim, 2); ?></td>
                    <td><?= htmlspecialchars($item->satuan); ?></td>
                    <td class="text-right"><?= number_format($item->unit_value, 2); ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($item->total_value, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background-color: #f2f2f2;">
                <td colspan="6" class="text-right font-weight-bold">Total Nilai Barang Keluar (IDR)</td>
                <td class="text-right font-weight-bold"><?= number_format($grand_total, 2); ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>