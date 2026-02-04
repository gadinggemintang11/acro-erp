<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
            font-family: 'DejaVu Sans', Arial, 'Microsoft YaHei', 'SimSun', sans-serif;
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
        
        .supplier-section {
            clear: both;
            margin: 15px 0;
        }
        
        .supplier-label {
            font-weight: bold;
            margin-bottom: 5px;
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
    </style>
</head>
<body>
    <div class="header-section clearfix">
        <div class="company-info">
            <strong>SUPPLIER / &#20379;&#24212;&#21830;:</strong><br>
            <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
            <?= htmlspecialchars($header->alamat ?? '') ?>
        </div>
        
        <div class="po-title">
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="/Applications/XAMPP/xamppfiles/htdocs/mingbao_erp/assets/img/logo_mini.png" alt="Logo" style="height: 40px; max-width: 80px;">
            </div>
            <h1>PURCHASE<br>ORDER</h1>
        </div>
        
        <div class="po-details">
            <strong>Purchase No / &#37319;&#36141;&#32534;&#21495;:</strong> <?= htmlspecialchars($header->po_number) ?><br>
            <strong>Purchase Date / &#37319;&#36141;&#26085;&#26399;:</strong> <?= date('d-m-Y', strtotime($header->po_date)) ?><br>
            <br>
            <?= date('d-m-Y') ?>
        </div>
    </div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">No<br>&#32534;&#21495;</th>
                <th style="width: 20%;">Nama Barang<br>&#21830;&#21697;&#21517;&#31216;</th>
                <th style="width: 15%;">Chinese Translation<br>&#20013;&#25991;&#32763;&#35793;</th>
                <th style="width: 8%;">Qty<br>&#25968;&#37327;</th>
                <th style="width: 8%;">Unit<br>&#21333;&#20301;</th>
                <th style="width: 12%;">Harga<br>&#20215;&#26684;</th>
                <th style="width: 12%;">Total<br>&#23567;&#35745;</th>
                <th style="width: 8%;">Diskon<br>&#25240;&#25187;</th>
                <th style="width: 6%;">PPN<br>&#22686;&#20540;&#31246;</th>
                <th style="width: 12%;">Sub Total<br>&#24635;&#35745;</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($details)): ?>
                <?php $no = 1; ?>
                <?php foreach ($details as $item): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="item-name"><?= htmlspecialchars($item->item_name ?? $item->description) ?></td>
                    <td class="chinese-col"><?= $translate_to_chinese($item->item_name ?? $item->description) ?></td>
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
                <td class="label-col">Total Sebelum Pajak & Diskon / &#31246;&#21069;&#25240;&#25187;&#21069;&#24635;&#39069; :</td>
                <td class="value-col">IDR <?= number_format($subtotal, 0) ?></td>
            </tr>
            <tr>
                <td class="label-col">Diskon / &#25240;&#25187; :</td>
                <td class="value-col">IDR 0</td>
            </tr>
            <tr>
                <td class="label-col">PPN 11% / &#22686;&#20540;&#31246;11% :</td>
                <td class="value-col"><?= $ppn > 0 ? 'IDR ' . number_format($ppn, 0) : '-' ?></td>
            </tr>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td class="label-col">Grand Total / &#24635;&#35745; :</td>
                <td class="value-col">IDR <?= number_format($grand_total, 0) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="payment-delivery">
        <strong>Payment Terms / &#20184;&#27454;&#26465;&#20214;:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?><br>
        <strong>Delivery / &#20132;&#36135;&#26041;&#24335;:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?>
    </div>
    
    <table class="signature-table">
        <tr>
            <td class="header-cell">Disetujui<br>&#25209;&#20934;</td>
            <td class="header-cell">Kepala Bagian<br>&#37096;&#38376;&#20027;&#31649;</td>
            <td class="header-cell">Kepala Akuntansi<br>&#20250;&#35745;&#20027;&#31649;</td>
            <td class="header-cell">Pemohon<br>&#30003;&#35831;&#20154;</td>
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