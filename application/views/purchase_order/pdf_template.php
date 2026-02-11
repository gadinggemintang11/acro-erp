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
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            border: 2px solid #000000;
        }

        .detail-table th,
        .detail-table td {
            border: 2px solid #000000;
        }

        .payment-table th,
        .payment-table td {
            border: 2px solid #000000;
        }

        .signature-table th,
        .signature-table td {
            border: 2px solid #000000;
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
            margin-bottom: 10px;
        }


        .logo img {
            width: 900px !important;
            height: 900px !important;
            /* max-width: 35px !important;
            max-height: 35px !important; */
        }
    </style>
</head>

<body>
    <!-- Header with Company Info and PO Title -->
    <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 0; border: none;">
                <div style="margin: 10px 0;">
                    <img src="<?= FCPATH . 'assets/img/fav.png'; ?>" alt="Company Logo" style="width: 35px; height: 35px; max-width: 35px; max-height: 35px;">
                </div>
                <div style="font-size: 11px; line-height: 1.4;">
                    <strong style="font-size: 12px;">PT. MINGBAO LIANCHUANG INTERNATIONAL</strong><br>
                    Jalan Kawasan Industri Candi 11B No. 8<br>
                    Kel. Bambankerep, Kec. Ngaliyan, Kota Semarang
                    purchasing@mingbaolianchuanginternational.com
                </div>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: center; padding: 0; border: none;">
                <div style="text-align: center; padding-right: 10px;">
                    <div style="font-size: 16px; font-weight: bold;">PURCHASE ORDER</div>
                    <div style="font-size: 12px; text-decoration: underline;">采购订单</div>
                    <div style="margin-top: 10px; font-size: 10px; line-height: 1.6; text-align: right;">
                        <div><strong>Nomor PO 采购编号:</strong> <?= htmlspecialchars($header->po_number); ?></div>
                        <div><strong>Tanggal 采购日期:</strong> <?= date('d F Y', strtotime($header->po_date)); ?></div>
                        <div><strong>Currency:</strong> <?= htmlspecialchars($header->currency_code); ?>
                            <?php if ($header->currency_code != 'IDR'): ?>
                                (Kurs: <?= number_format((float)$header->exchange_rate, 4); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 100%;">
                <strong>Kepada Yth:</strong><br>
                <strong><?= htmlspecialchars($header->nama_supplier); ?></strong><br>
                <?= nl2br(htmlspecialchars($header->alamat ?? '-')); ?><br>
                Telp: <?= htmlspecialchars($header->telepon ?? '-'); ?>
            </td>
        </tr>
    </table>


    <table class="detail-table">
        <thead>
            <tr>
                <th style="width:4%">No<br>序号</th>
                <th style="width:12%">Kode<br>代码</th>
                <th style="width:20%">Nama Barang<br>产品名称</th>
                <th style="width:8%">Qty<br>数量</th>
                <th style="width:8%">Satuan<br>单位</th>
                <th style="width:12%">Harga Satuan(<?= $header->currency_code; ?>)<br>单价</th>
                <th style="width:12%">Total (<?= $header->currency_code; ?>)<br>合计</th>
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
                            <br><small><i><?= nl2br(htmlspecialchars($d->description)); ?></i></small>
                        <?php endif; ?>
                        <?php if (!empty($d->sc_number)): ?>
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
                <td colspan="6" class="text-right"><strong>Subtotal 税前及折扣前总额(<?= $header->currency_code; ?>)</strong></td>
                <td class="text-right"><strong><?= number_format($grand_total_base, 2, '.', ','); ?></strong></td>
            </tr>
            <?php
            $ppn_amount = $header->has_ppn ? $grand_total_base * 0.11 : 0;
            $total_after_ppn_base = $grand_total_base + $ppn_amount;
            ?>
            <?php if ($header->has_ppn): ?>
                <tr>
                    <td colspan="6" class="text-right">PPN 11% 增值税</td>
                    <td class="text-right"><?= number_format($ppn_amount, 2, '.', ','); ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><strong>Grand Total 总计 (<?= $header->currency_code; ?>)</strong></td>
                    <td class="text-right"><strong><?= number_format($total_after_ppn_base, 2, '.', ','); ?></strong></td>
                </tr>
            <?php endif; ?>

            <?php if ($header->currency_code != 'IDR'): ?>
                <?php
                // Logika estimasi total IDR (sama seperti di view detail.php)
                $grand_total_final_local = $grand_total_local * ($header->has_ppn ? 1.11 : 1);
                ?>
                <tr>
                    <td colspan="6" class="text-right"><strong>Estimasi Grand Total (IDR)</strong></td>
                    <td class="text-right"><strong><?= number_format($grand_total_final_local, 2, '.', ','); ?></strong></td>
                </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <?php if (!empty($header->remarks)): ?>
        <table class="detail-table" style="margin-top: 15px; margin-bottom: 0;">
            <tr>
                <td style="padding: 10px;">
                    <strong>Catatan / 备注:</strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;">
                    <?= nl2br(htmlspecialchars($header->remarks)); ?>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <!-- Payment and Delivery Info -->
    <table class="payment-table" style="margin-top: 10px; margin-bottom: 0;">
        <tr>
            <td style="width: 50%; padding: 10px;">
                <strong>Pembayaran / 付款条款:</strong>
            </td>
            <td style="width: 50%; padding: 10px;">
                <strong>Pengiriman / 交货:</strong>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 10px;">
                <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?>
            </td>
            <td style="width: 50%; padding: 10px;">
                <?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?>
            </td>
        </tr>
    </table>

    <!-- Signature Table -->
    <table class="signature-table" style="margin-top: 15px;">
        <tr>
            <td style="width: 25%; text-align: center; padding: 10px;">
                <strong>Disetujui<br>批准</strong>
            </td>
            <td style="width: 25%; text-align: center; padding: 10px;">
                <strong>Kepala Bagian<br>行政主管</strong>
            </td>
            <td style="width: 25%; text-align: center; padding: 10px;">
                <strong>Kepala Akuntan<br>会计主管</strong>
            </td>
            <td style="width: 25%; text-align: center; padding: 10px;">
                <strong>Pemohon<br>申请人</strong>
            </td>
        </tr>
        <tr>
            <td style="width: 25%; text-align: center; padding: 40px 10px;">
                &nbsp;
            </td>
            <td style="width: 25%; text-align: center; padding: 40px 10px;">
                &nbsp;
            </td>
            <td style="width: 25%; text-align: center; padding: 40px 10px;">
                &nbsp;
            </td>
            <td style="width: 25%; text-align: center; padding: 40px 10px;">
                &nbsp;
            </td>
        </tr>
        <tr>
            <td style="width: 25%; text-align: center; padding: 10px; font-size: 9px;">
                Tanda Tangan / 签名
            </td>
            <td style="width: 25%; text-align: center; padding: 10px; font-size: 9px;">
                Tanda Tangan / 签名
            </td>
            <td style="width: 25%; text-align: center; padding: 10px; font-size: 9px;">
                Tanda Tangan / 签名
            </td>
            <td style="width: 25%; text-align: center; padding: 10px; font-size: 9px;">
                Tanda Tangan / 签名
            </td>
        </tr>
    </table>

</body>

</html>