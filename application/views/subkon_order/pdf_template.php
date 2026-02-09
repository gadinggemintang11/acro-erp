<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kontrak Subkon: <?= htmlspecialchars($header->subkon_order_no); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 25px;
        }
        h1 {
            text-align: center;
            color: #444;
            font-size: 20px;
            border-bottom: 2px solid #555;
            padding-bottom: 6px;
            margin-bottom: 25px;
        }
        h3 {
            margin-top: 25px;
            color: #444;
            font-size: 15px;
            border-left: 4px solid #5C5F72;
            padding-left: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 7px;
            vertical-align: middle;
        }
        thead th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: 600;
        }
        tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .header-table {
            border: none;
            margin-bottom: 10px;
        }
        .header-table td {
            border: none;
            padding: 3px 2px;
            font-size: 12px;
        }
        .total-box {
            width: 45%;
            float: right;
            margin-top: 25px;
            border: 1px solid #a1c4fd;
            border-radius: 6px;
            background: #f0f8ff;
            padding: 8px 15px;
        }
        .total-box table {
            width: 100%;
            border: none;
        }
        .total-box td {
            border: none;
            padding: 4px 0;
        }
        .total-label {
            text-align: left;
            font-weight: 600;
            color: #444;
        }
        .total-value {
            text-align: right;
            font-weight: bold;
            color: #0d6efd;
        }
        .signature-section {
            width: 100%;
            margin-top: 60px;
            text-align: center;
        }
        .signature-section td {
            width: 33%;
            padding-top: 50px;
            font-size: 12px;
        }
        .footer-note {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 40px;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <h1>KONTRAK SUBKON (SUBKON ORDER)</h1>

    <table class="header-table">
        <tr>
            <td style="width: 18%;">Nomor Kontrak</td>
            <td style="width: 32%;">: <strong><?= htmlspecialchars($header->subkon_order_no); ?></strong></td>
            <td style="width: 18%;">Tanggal Order</td>
            <td style="width: 32%;">: <?= date('d M Y', strtotime($header->order_date)); ?></td>
        </tr>
        <tr>
            <td>Supplier</td>
            <td>: <?= htmlspecialchars($header->nama_supplier); ?></td>
            <td>Status</td>
            <td>: <?= htmlspecialchars($header->status ?? '-'); ?></td>
        </tr>
        <tr>
            <td>Pajak (%)</td>
            <td>: <?= number_format($header->pajak_persen, 2); ?>%</td>
            <td>Catatan</td>
            <td>: <?= htmlspecialchars($header->notes ?? '-'); ?></td>
        </tr>
    </table>

    <h3>Daftar Item Hasil (Output)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Nama Item</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 10%;">Satuan</th>
                <th style="width: 15%;" class="text-right">Jasa / pcs (Rp)</th>
                <th style="width: 15%;" class="text-right">Total Jasa (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $grand_total = 0;
            foreach ($output_items as $item): 
                $grand_total += $item->total_jasa_item;
            ?>
            <tr>
                <td style="text-align:center;"><?= $no++; ?></td>
                <td><?= htmlspecialchars($item->item_name); ?></td>
                <td class="text-right"><?= number_format($item->qty, 2); ?></td>
                <td class="text-center"><?= htmlspecialchars($item->satuan); ?></td>
                <td class="text-right"><?= number_format($item->jasa_per_pcs, 2); ?></td>
                <td class="text-right font-weight-bold"><?= number_format($item->total_jasa_item, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Bill of Material (BOM)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Item</th>
                <th>Nama Material</th>
                <th style="width: 12%;" class="text-right">Qty / pcs</th>
                <th style="width: 12%;" class="text-right">Total Qty</th>
                <th style="width: 10%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bom_items_grouped)): ?>
                <tr><td colspan="6" style="text-align:center;">Tidak ada data BOM</td></tr>
            <?php else: 
                $no = 1;
                foreach ($bom_items_grouped as $bom_group): 
                    foreach ($bom_group as $bom): ?>
                        <tr>
                            <td style="text-align:center;"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($bom->item_code); ?></td>
                            <td><?= htmlspecialchars($bom->item_name); ?></td>
                            <td class="text-right"><?= number_format($bom->qty_per_pcs, 3); ?></td>
                            <td class="text-right"><?= number_format($bom->total_qty, 3); ?></td>
                            <td class="text-center"><?= htmlspecialchars($bom->satuan); ?></td>
                        </tr>
                    <?php endforeach; 
                endforeach;
            endif; ?>
        </tbody>
    </table>

    <?php 
        $pajak = ($header->pajak_persen / 100) * $grand_total;
        $grand_final = $grand_total + $pajak;
    ?>
    <div class="total-box">
        <table>
            <tr>
                <td class="total-label">Total Jasa</td>
                <td class="total-value">Rp <?= number_format($grand_total, 2); ?></td>
            </tr>
            <tr>
                <td class="total-label">Pajak (<?= number_format($header->pajak_persen, 2); ?>%)</td>
                <td class="total-value">Rp <?= number_format($pajak, 2); ?></td>
            </tr>
            <tr>
                <td class="total-label">Grand Total</td>
                <td class="total-value" style="font-size:13px; color:#004aad;">Rp <?= number_format($grand_final, 2); ?></td>
            </tr>
        </table>
    </div>

    <div style="clear:both;"></div>

    <table class="signature-section">
        <tr>
            <td>Disiapkan Oleh,</td>
            <td>Disetujui Oleh,</td>
            <td>Dikonfirmasi Vendor,</td>
        </tr>
        <tr>
            <td>( <?= htmlspecialchars($this->session->userdata('nama') ?? '....................'); ?> )</td>
            <td>( .................... )</td>
            <td>( .................... )</td>
        </tr>
    </table>

    <div class="footer-note">
        Dicetak pada <?= date('d M Y H:i'); ?> | Sistem IT Inventory
    </div>

</body>
</html>
