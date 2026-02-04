<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <!-- TEMPLATE VERSION: <?= isset($cache_buster) ? $cache_buster : time() ?> - BILINGUAL WITH CHINESE -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 8px;
            color: #000;
            line-height: 1.3;
        }
        
        @page {
            size: A4;
            margin: 12mm 20mm;
        }
        
        .container {
            width: 100%;
            margin: 0 auto;
        }
        
        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .header-left {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            font-size: 8px;
        }
        
        .header-center {
            display: table-cell;
            width: 30%;
            text-align: center;
            vertical-align: middle;
        }
        
        .header-right {
            display: table-cell;
            width: 35%;
            text-align: right;
            vertical-align: top;
            font-size: 8px;
        }
        
        .po-title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }
        
        .supplier-label {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 2px;
        }
        
        .supplier-name {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2px;
        }
        
        .po-meta-label {
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 1px;
        }
        
        .po-meta-value {
            font-size: 8px;
            margin-bottom: 3px;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        th {
            background-color: #cccccc;
            border: 1px solid #000;
            padding: 4px;
            font-weight: bold;
            text-align: center;
            font-size: 7px;
            line-height: 1.3;
        }
        
        td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 7px;
            vertical-align: middle;
        }
        
        .text-right {
            text-align: right;
            padding-right: 4px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .totals-row td {
            padding: 3px;
            font-size: 7px;
        }
        
        /* Payment Section */
        .payment-section {
            margin: 6px 0;
            font-size: 7px;
            line-height: 1.4;
        }
        
        .payment-section strong {
            font-weight: bold;
        }
        
        /* Signature Section */
        .signature-section {
            margin-top: 10px;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        
        .signature-table td {
            border: none;
            padding: 4px;
            text-align: center;
            font-size: 7px;
            width: 25%;
        }
        
        .sig-title {
            font-weight: bold;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        
        .sig-space {
            height: 40px;
        }
        
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-left">
                <div class="supplier-label">SUPPLIER :</div>
                <div class="supplier-name"><?= htmlspecialchars($header->nama_supplier); ?></div>
                <div style="font-size: 7px;"><?= htmlspecialchars(str_replace("\n", " ", $header->alamat ?? '')); ?></div>
            </div>
            <div class="header-center">
                <div class="po-title">PURCHASE<br>ORDER</div>
            </div>
            <div class="header-right">
                <div class="po-meta-label">Purchase No<br>采购单号</div>
                <div class="po-meta-value"><?= htmlspecialchars($header->po_number); ?></div>
                <div class="po-meta-label" style="margin-top: 4px;">Purchase Date<br>采购日期</div>
                <div class="po-meta-value"><?= date('d-m-Y', strtotime($header->po_date)); ?></div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">No<br>编号</th>
                    <th style="width: 12%;">Nama Barang<br>商品名称</th>
                    <th style="width: 12%;">中文翻译<br>Chinese</th>
                    <th style="width: 5%;">Qty<br>数量</th>
                    <th style="width: 4%;">Unit<br>单位</th>
                    <th style="width: 9%;">Harga<br>单价</th>
                    <th style="width: 9%;">Total<br>合计</th>
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
                        
                        $item_name = htmlspecialchars($d->item_name ?? '');
                        
                        // Translate to Chinese
                        $chinese_text = '';
                        $text_upper = strtoupper($item_name);
                        $translations = [
                            'MARKING' => '标记',
                            'STICKER' => '贴纸',
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
                            'EQUIPMENT' => '设备',
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
                ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= $item_name; ?></td>
                    <td class="text-center"><?= $chinese_text; ?></td>
                    <td class="text-right"><?= number_format($qty, 0); ?></td>
                    <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($price, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($discount, 0); ?></td>
                    <td class="text-center"><?= $header->has_ppn ? '11%' : '-'; ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($subtotal, 0); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="6" class="text-right">Total Sebelum Pajak & Diskon / 税前折扣前总额</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_before_discount, 0); ?></td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_discount, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="6" class="text-right">Diskon / 折扣</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_discount, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="6" class="text-right">PPN 11% / 增值税</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_ppn, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr class="totals-row">
                    <td colspan="6" class="text-right">Grand Total / 总计</td>
                    <td colspan="2" class="text-right"><?= htmlspecialchars($header->currency_code); ?> <?= number_format($total_subtotal, 0); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Payment Section -->
        <div class="payment-section">
            <strong>Payment Terms / 付款条款:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?><br>
            <strong>Delivery / 交货:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? '-'); ?><br>
            <?php if(!empty($header->remarks)): ?>
                <strong>Notes / 备注:</strong> <?= htmlspecialchars($header->remarks); ?><br>
            <?php endif; ?>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sig-title">Disetujui<br>批准</div>
                    </td>
                    <td>
                        <div class="sig-title">Kepala Bagian<br>行政主管</div>
                    </td>
                    <td>
                        <div class="sig-title">Kepala Akuntan<br>会计主管</div>
                    </td>
                    <td>
                        <div class="sig-title">Pemohon<br>申请人</div>
                    </td>
                </tr>
                <tr>
                    <td class="sig-space"></td>
                    <td class="sig-space"></td>
                    <td class="sig-space"></td>
                    <td class="sig-space"></td>
                </tr>
                <tr>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">(.......................)</td>
                    <td class="sig-line">(<?= htmlspecialchars($header->creator_name ?? 'Admin'); ?>)</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
