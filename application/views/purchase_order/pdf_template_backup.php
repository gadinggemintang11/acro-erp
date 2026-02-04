<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { 
            width: 100%;
            margin: 0;
            padding: 0;
        }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 8px;
            line-height: 1.2;
            background: white;
        }
        @page {
            size: A4;
            margin: 10mm 15mm;
        }
        .page { 
            width: 100%;
            padding: 0;
            margin: 0;
            page-break-after: avoid;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 3px; 
            text-align: left; 
            vertical-align: middle;
            font-size: 7px;
        }
        th { 
            background-color: #d3d3d3; 
            font-weight: bold;
            text-align: center;
            line-height: 1.2;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header-wrapper {
            margin-bottom: 6px;
        }
        .header-wrapper td {
            border: none;
            padding: 2px;
        }
        .supplier-info { 
            font-size: 8px;
            line-height: 1.2;
        }
        .po-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 0;
            line-height: 1;
        }
        .po-meta {
            text-align: right;
            font-size: 8px;
            line-height: 1.3;
        }
        .items-table {
            margin: 4px 0;
            font-size: 7px;
        }
        .items-table th { padding: 3px 2px; }
        .items-table td { padding: 3px 2px; }
        .totals-row { background-color: #d3d3d3; font-weight: bold; }
        .payment-section {
            margin-top: 6px;
            font-size: 7px;
            line-height: 1.3;
        }
        .payment-section strong { font-weight: bold; }
        .signature-section {
            margin-top: 8px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-top: 10px;
        }
        .signature-table td {
            border: none;
            text-align: center;
            padding: 4px;
            height: auto;
        }
        .sig-title {
            font-weight: bold;
            padding-bottom: 2px;
        }
        .sig-box {
            height: 35px;
            border: none;
        }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 1px;
            font-size: 6px;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header: Supplier Info + Title + PO Meta -->
        <table class="header-wrapper">
            <tr>
                <td style="width: 30%;">
                    <div class="supplier-info">
                        <strong style="font-size: 9px;">SUPPLIER :</strong><br>
                        <strong style="font-size: 8px;"><?= htmlspecialchars($header->nama_supplier); ?></strong><br>
                        <span style="font-size: 7px;"><?= htmlspecialchars(str_replace("\n", " ", $header->alamat ?? '')); ?></span>
                    </div>
                </td>
                <td style="width: 40%; text-align: center;">
                    <div class="po-title">PURCHASE ORDER</div>
                </td>
                <td style="width: 30%;">
                    <div class="po-meta">
                        <div><strong>Purchase No 采购单号</strong></div>
                        <div><?= htmlspecialchars($header->po_number); ?></div>
                        <div style="margin-top: 3px;"><strong>Purchase Date 采购日期</strong></div>
                        <div><?= date('d-m-Y', strtotime($header->po_date)); ?></div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 3%;">No<br>编号</th>
                    <th style="width: 12%;">Nama Barang<br>商品名称</th>
                    <th style="width: 10%;">商品<br>中文</th>
                    <th style="width: 5%;">Qty<br>数量</th>
                    <th style="width: 4%;">Unit<br>单位</th>
                    <th style="width: 10%;">Harga<br>单价</th>
                    <th style="width: 10%;">Total<br>合计</th>
                    <th style="width: 8%;">Diskon<br>折扣</th>
                    <th style="width: 5%;">PPN<br>税</th>
                    <th style="width: 10%;">Sub Total<br>小计</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $no = 1; 
                    $total_before_discount = 0;
                    $total_discount = 0;
                    $total_ppn = 0;
                    $total_subtotal = 0;
                    
                    foreach($details as $d): 
                        $qty = (float)($d->qty ?? 0);
                        $price = (float)($d->base_price ?? 0);
                        $total = $qty * $price;
                        $discount = (float)($d->discount_amount ?? 0);
                        $after_discount = $total - $discount;
                        $ppn = $header->has_ppn ? $after_discount * 0.11 : 0;
                        $subtotal = $after_discount + $ppn;
                        
                        $total_before_discount += $total;
                        $total_discount += $discount;
                        $total_ppn += $ppn;
                        $total_subtotal += $subtotal;
                        
                        // Get item name
                        $item_name = htmlspecialchars($d->item_name ?? '');
                    ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= $item_name; ?></td>
                    <td><?php 
                        $chinese_text = '';
                        if (is_callable($translate_to_chinese)) {
                            $chinese_text = $translate_to_chinese($item_name);
                        } else {
                            // Fallback translation
                            $text_upper = strtoupper($item_name);
                            $translations = [
                                'CLEANER' => '清洁剂',
                                'TREATMENT' => '处理',
                                'PVC' => 'PVC',
                                'STABILIZER' => '稳定剂',
                                'PLASTIC' => '塑料',
                                'RESIN' => '树脂',
                                'POWDER' => '粉末',
                                'ADDITIVE' => '添加剂',
                                'CHEMICAL' => '化学',
                                'COMPOUND' => '复合',
                                'MATERIAL' => '材料',
                                'METAL' => '金属',
                                'RUBBER' => '橡胶',
                                'PAINT' => '油漆',
                                'COATING' => '涂层',
                                'MACHINE' => '机器',
                            ];
                            foreach($translations as $en => $cn) {
                                if (stripos($text_upper, $en) !== false) {
                                    $chinese_text = $cn;
                                    break;
                                }
                            }
                            if (empty($chinese_text)) {
                                $chinese_text = htmlspecialchars($item_name);
                            }
                        }
                        echo $chinese_text;
                    ?></td>
                    <td class="text-right"><?= number_format($qty, 0); ?></td>
                    <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($price, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($discount, 0); ?></td>
                    <td class="text-center"><?= $header->has_ppn ? '11%' : '-'; ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?><?= number_format($subtotal, 0); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="6" class="text-right" style="padding-right: 5px;">Total Sebelum Pajak & Diskon</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_before_discount, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_discount, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right" style="padding-right: 5px;">Diskon</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_discount, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right" style="padding-right: 5px;">PPN 11%</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_ppn, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="6" class="text-right" style="padding-right: 5px;">Grand Total</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_subtotal, 0); ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>


        <!-- Payment Terms -->
        <div class="payment-section">
            <strong>Payment Terms / 付款条款 :</strong> <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?><br>
            <strong>Delivery / 交货 :</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?><br>
            <?php if(!empty($header->remarks)): ?>
                <strong>Notes / 备注 :</strong> <?= nl2br(htmlspecialchars($header->remarks)); ?>
            <?php endif; ?>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td style="width: 25%;" class="sig-title">Disetujui / 批准</td>
                    <td style="width: 25%;" class="sig-title">Kepala Bagian / 行政主管</td>
                    <td style="width: 25%;" class="sig-title">Kepala Akuntan / 会计主管</td>
                    <td style="width: 25%;" class="sig-title">Pemohon / 申请人</td>
                </tr>
                <tr>
                    <td class="sig-box"></td>
                    <td class="sig-box"></td>
                    <td class="sig-box"></td>
                    <td class="sig-box"></td>
                </tr>
                <tr>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">( <?= htmlspecialchars($header->creator_name ?? 'Admin'); ?> )</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>