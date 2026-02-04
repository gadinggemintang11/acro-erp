<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <style>
        body {
            font-family: sun-extA, DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .header,
        .footer {
            width: 100%;
            text-align: center;
            position: fixed;
        }

        .header {
            top: 0px;
        }

        .footer {
            bottom: 0px;
            font-size: 8px;
        }

        .pagenum:before {
            content: counter(page);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mb-1 {
            margin-bottom: 0.25rem;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            text-decoration: underline;
        }

        /* Tambah underline */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table td {
            border: none;
            padding: 2px 0;
        }

        .no-border {
            border: none !important;
        }

        /* Class untuk tanda tangan */
        .notes {
            margin-top: 20px;
            font-size: 9px;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            width: 80px;
            height: 80px;
        }
    </style>
</head>

<body>
    <!-- Logo image disabled for DomPDF compatibility - DomPDF has permission issues with external images -->
    <!-- <div class="logo">
        <img src="<?= FCPATH . 'assets/img/fav.png'; ?>" alt="Company Logo">
    </div> -->
    <h1>PURCHASE ORDER<br><span style="font-size: 14px;">采购订单</span></h1>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Kepada Yth:</strong><br>
                <strong><?= htmlspecialchars($header->nama_supplier); ?></strong><br>
                <?= nl2br(htmlspecialchars($header->alamat ?? '-')); ?><br>
                Telp: <?= htmlspecialchars($header->telepon ?? '-'); ?>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <strong>Nomor PO:</strong> <?= htmlspecialchars($header->po_number); ?><br>
                <strong>Tanggal:</strong> <?= date('d F Y', strtotime($header->po_date)); ?><br>
                <strong>Currency:</strong> <?= htmlspecialchars($header->currency_code); ?>
                <?php if ($header->currency_code != 'IDR'): ?>
                    (Kurs: <?= number_format((float)$header->exchange_rate, 4); ?>)
                <?php endif; ?><br>
                <strong>Pembayaran:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?><br>
                <strong>Pengiriman:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?>
            </td>
        </tr>
    </table>


    <table>
        <thead>
            <tr>
                <th style="width:5%">No<br>序号</th>
                <th style="width:15%">Kode<br>代码</th>
                <th>Nama Barang & Deskripsi<br>产品名称及描述</th>
                <th style="width:10%">Qty<br>数量</th>
                <th style="width:10%">Satuan<br>单位</th>
                <th style="width:15%">Harga (<?= $header->currency_code; ?>)<br>价格 (<?= $header->currency_code; ?>)</th>
                <th style="width:15%">Total (<?= $header->currency_code; ?>)<br>总计 (<?= $header->currency_code; ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total_base = 0;
            $grand_total_local = 0; // Inisialisasi total local
            foreach ($details as $d):
                $total_base = (float)($d->qty ?? 0) * (float)($d->base_price ?? 0);
                $grand_total_base += $total_base;
                $grand_total_local += (float)($d->total_local ?? 0); // Akumulasi total local
            ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($d->item_code); ?></td>
                    <td>
                        <?= htmlspecialchars($d->item_name); ?>
                        <?php if (!empty($d->description)): ?>
                            <br><small><i><?= nl2br(htmlspecialchars($d->description)); // Gunakan nl2br juga 
                                            ?></i></small>
                        <?php endif; ?>
                        <?php if (!empty($d->sc_number)): // Tampilkan SC jika ada 
                        ?>
                            <br><small><i>(Ref: <?= htmlspecialchars($d->sc_number); ?>)</i></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?= number_format((float)($d->qty ?? 0), 4, '.', ','); ?></td>
                    <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                    <td class="text-right"><?= number_format((float)($d->base_price ?? 0), 2, '.', ','); ?></td>
                    <td class="text-right"><?= number_format($total_base, 2, '.', ','); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right no-border" style="border-bottom: 1px solid #ddd !important;"><strong>Subtotal (<?= $header->currency_code; ?>)</strong></td>
                <td class="text-right" style="border-bottom: 1px solid #ddd !important;"><strong><?= number_format($grand_total_base, 2, '.', ','); ?></strong></td>
            </tr>
            <?php
            $ppn_amount = $header->has_ppn ? $grand_total_base * 0.11 : 0;
            $total_after_ppn_base = $grand_total_base + $ppn_amount;
            ?>
            <?php if ($header->has_ppn): ?>
                <tr>
                    <td colspan="6" class="text-right no-border">PPN 11%</td>
                    <td class="text-right"><?= number_format($ppn_amount, 2, '.', ','); ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right no-border"><strong>Total Setelah PPN (<?= $header->currency_code; ?>)</strong></td>
                    <td class="text-right"><strong><?= number_format($total_after_ppn_base, 2, '.', ','); ?></strong></td>
                </tr>
            <?php endif; ?>

            <?php if ($header->currency_code != 'IDR'): ?>
                <?php
                // Logika estimasi total IDR (sama seperti di view detail.php)
                $grand_total_final_local = $grand_total_local * ($header->has_ppn ? 1.11 : 1);
                ?>
                <tr>
                    <td colspan="6" class="text-right no-border" style="background-color: #f2f2f2;"><strong>Estimasi Grand Total (IDR)</strong></td>
                    <td class="text-right" style="background-color: #f2f2f2;"><strong><?= number_format($grand_total_final_local, 2, '.', ','); ?></strong></td>
                </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <?php if (!empty($header->remarks)): ?>
        <div class="notes">
            <strong>Catatan:</strong><br>
            <?= nl2br(htmlspecialchars($header->remarks)); ?>
        </div>
    <?php endif; ?>

    <table style="margin-top: 40px; border: none;">
        <tr>
            <td style="width: 50%; text-align: center; border: none;">Disetujui Oleh,</td>
            <td style="width: 50%; text-align: center; border: none;">Dibuat Oleh,</td>
        </tr>
        <tr>
            <td style="height: 60px; border: none;"></td>
            <td style="border: none;"></td>
        </tr>
        <tr>
            <td style="text-align: center; border: none;">(.......................)</td>
            <td style="text-align: center; border: none;">( <?= htmlspecialchars($header->creator_name ?? 'Admin'); ?> )</td>
        </tr>
        <tr>
            <td style="text-align: center; border: none; font-size: 9px;">(Purchasing / Manager)</td>
            <td style="text-align: center; border: none; font-size: 9px;">(PPIC / Staff)</td>
        </tr>
    </table>

</body>

</html>