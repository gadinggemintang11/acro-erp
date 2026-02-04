<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <style>
        @page {
            margin: 20mm 25mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            margin: 15px;
            padding: 10px;
        }
        
        .header-section {
            width: 100%;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #ccc;
        }
        
        .company-info {
            float: left;
            width: 35%;
            padding-right: 10px;
        }
        
        .po-title {
            text-align: center;
            width: 30%;
            float: left;
            padding: 0 10px;
        }
        
        .po-title h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            text-align: center;
        }
        
        .po-details {
            float: right;
            width: 35%;
            text-align: right;
            padding-left: 10px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9px;
        }
        
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8px;
            line-height: 1.1;
            padding: 8px 4px;
        }
        
        .main-table .item-name {
            text-align: left;
            padding-left: 8px;
        }
        
        .main-table .chinese-col {
            background-color: #fffacd;
            color: #d2691e;
            font-weight: bold;
            text-align: center;
        }
        
        .totals-section {
            width: 100%;
            margin: 10px 0;
        }
        
        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .totals-table td {
            border: 1px solid #000;
            padding: 4px;
        }
        
        .totals-table .label-col {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .totals-table .value-col {
            text-align: right;
            width: 100px;
        }
        
        .payment-delivery {
            clear: both;
            margin: 20px 0;
            font-size: 9px;
            padding: 10px 0;
        }
        
        .signature-section {
            clear: both;
            margin-top: 30px;
            width: 100%;
            page-break-inside: avoid;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 10px;
        }
        
        .signature-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            text-align: center;
            vertical-align: top;
            height: 80px;
        }
        
        .signature-table .header-cell {
            background-color: #f0f0f0;
            font-weight: bold;
            height: 30px;
            vertical-align: middle;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .chinese {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="header-section clearfix">
        <div class="company-info">
            <strong>SUPPLIER / <span class="chinese">供应商</span>:</strong><br>
            <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
            <?= htmlspecialchars($header->alamat ?? '') ?>
        </div>
        
        <div class="po-title">
            <?php if (file_exists('/Applications/XAMPP/xamppfiles/htdocs/mingbao_erp/assets/img/logo_mini.png')): ?>
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="data:image/png;base64,<?= base64_encode(file_get_contents('/Applications/XAMPP/xamppfiles/htdocs/mingbao_erp/assets/img/logo_mini.png')) ?>" alt="Logo" style="height: 40px; max-width: 80px;">
            </div>
            <?php endif; ?>
            <h1>PURCHASE<br>ORDER</h1>
        </div>
        
        <div class="po-details">
            <strong>Purchase No / <span class="chinese">采购编号</span>:</strong> <?= htmlspecialchars($header->po_number) ?><br>
            <strong>Purchase Date / <span class="chinese">采购日期</span>:</strong> <?= date('d-m-Y', strtotime($header->po_date)) ?><br>
            <br>
            <?= date('d-m-Y') ?>
        </div>
    </div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">No<br><span class="chinese">编号</span></th>
                <th style="width: 20%;">Nama Barang<br><span class="chinese">商品名称</span></th>
                <th style="width: 15%;">Chinese Translation<br><span class="chinese">中文翻译</span></th>
                <th style="width: 8%;">Qty<br><span class="chinese">数量</span></th>
                <th style="width: 8%;">Unit<br><span class="chinese">单位</span></th>
                <th style="width: 12%;">Harga<br><span class="chinese">价格</span></th>
                <th style="width: 12%;">Total<br><span class="chinese">小计</span></th>
                <th style="width: 8%;">Diskon<br><span class="chinese">折扣</span></th>
                <th style="width: 6%;">PPN<br><span class="chinese">增值税</span></th>
                <th style="width: 12%;">Sub Total<br><span class="chinese">总计</span></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($details)): ?>
                <?php $no = 1; ?>
                <?php foreach ($details as $item): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="item-name"><?= htmlspecialchars($item->item_name ?? $item->description) ?></td>
                    <td class="chinese-col chinese"><?= $translate_to_chinese($item->item_name ?? $item->description) ?></td>
                    <td><?= number_format($item->qty, 0) ?></td>
                    <td><?= htmlspecialchars($item->item_unit ?? 'PCS') ?></td>
                    <td style="text-align: right;">IDR <?= number_format($item->local_price, 0) ?></td>
                    <td style="text-align: right;">IDR <?= number_format($item->total_local, 0) ?></td>
                    <td>IDR 0</td>
                    <td>-</td>
                    <td style="text-align: right;">IDR <?= number_format($item->total_local, 0) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="totals-section clearfix">
        <?php 
        $subtotal = 0;
        if (!empty($details)) {
            foreach ($details as $item) {
                $subtotal += $item->total_local;
            }
        }
        $ppn = $header->has_ppn == 1 ? ($subtotal * 0.11) : 0;
        $grand_total = $subtotal + $ppn;
        ?>
        
        <table class="totals-table">
            <tr>
                <td class="label-col">Total Sebelum Pajak & Diskon / <span class="chinese">税前折扣前总额</span> :</td>
                <td class="value-col">IDR <?= number_format($subtotal, 0) ?></td>
            </tr>
            <tr>
                <td class="label-col">Diskon / <span class="chinese">折扣</span> :</td>
                <td class="value-col">IDR 0</td>
            </tr>
            <tr>
                <td class="label-col">PPN 11% / <span class="chinese">增值税11%</span> :</td>
                <td class="value-col"><?= $ppn > 0 ? 'IDR ' . number_format($ppn, 0) : '-' ?></td>
            </tr>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td class="label-col">Grand Total / <span class="chinese">总计</span> :</td>
                <td class="value-col">IDR <?= number_format($grand_total, 0) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="payment-delivery">
        <strong>Payment Terms / <span class="chinese">付款条件</span>:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?><br>
        <strong>Delivery / <span class="chinese">交货方式</span>:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?>
    </div>
    
    <table class="signature-table">
        <tr>
            <td class="header-cell">Disetujui<br><span class="chinese">批准</span></td>
            <td class="header-cell">Kepala Bagian<br><span class="chinese">部门主管</span></td>
            <td class="header-cell">Kepala Akuntansi<br><span class="chinese">会计主管</span></td>
            <td class="header-cell">Pemohon<br><span class="chinese">申请人</span></td>
        </tr>
        <tr>
            <td>
                <br><br><br><br>
                (...........................)<br>
                (DIREKSI)
            </td>
            <td>
                <br><br><br><br>
                (...........................)<br>
                (SPVS)
            </td>
            <td>
                <br><br><br><br>
                (...........................)<br>
                (FIN)
            </td>
            <td>
                <br><br><br><br>
                (...........................)<br>
                (PPB)
            </td>
        </tr>
    </table>
</body>
</html>