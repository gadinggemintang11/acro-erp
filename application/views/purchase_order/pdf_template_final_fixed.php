<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #000;
        }
        
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .header table {
            width: 100%;
            border: none;
        }
        
        .header td {
            border: none;
            vertical-align: top;
            padding: 5px;
        }
        
        .logo-box {
            text-align: center;
            border: 2px solid #333;
            background-color: #f8f8f8;
            padding: 15px;
            margin: 10px 0;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 9px;
        }
        
        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .item-name {
            text-align: left !important;
        }
        
        .chinese-col {
            background-color: #fffacd;
            color: #d2691e;
            font-weight: bold;
        }
        
        .totals {
            width: 40%;
            float: right;
            margin: 20px 0;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9px;
        }
        
        .totals .label {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .totals .value {
            text-align: right;
        }
        
        .payment-info {
            clear: both;
            margin: 20px 0;
            padding: 10px 0;
        }
        
        .signatures {
            clear: both;
            margin-top: 30px;
        }
        
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signatures td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            height: 80px;
        }
        
        .signatures .header-cell {
            background-color: #f0f0f0;
            font-weight: bold;
            height: 30px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td width="35%">
                    <strong>SUPPLIER / &#20379;&#24212;&#21830;:</strong><br>
                    <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
                    <?= htmlspecialchars($header->alamat ?? '') ?>
                </td>
                <td width="30%">
                    <div class="logo-box">
                        <strong>COMPANY LOGO</strong>
                    </div>
                    <h2 style="text-align: center; margin: 10px 0;">PURCHASE ORDER</h2>
                </td>
                <td width="35%" style="text-align: right;">
                    <strong>Purchase No / &#37319;&#36141;&#32534;&#21495;:</strong><br>
                    <?= htmlspecialchars($header->po_number) ?><br><br>
                    <strong>Purchase Date / &#37319;&#36141;&#26085;&#26399;:</strong><br>
                    <?= date('d-m-Y', strtotime($header->po_date)) ?><br><br>
                    <?= date('d-m-Y') ?>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No<br>&#32534;&#21495;</th>
                <th width="20%">Nama Barang<br>&#21830;&#21697;&#21517;&#31216;</th>
                <th width="15%">Chinese Translation<br>&#20013;&#25991;&#32763;&#35793;</th>
                <th width="8%">Qty<br>&#25968;&#37327;</th>
                <th width="8%">Unit<br>&#21333;&#20301;</th>
                <th width="12%">Harga<br>&#20215;&#26684;</th>
                <th width="12%">Total<br>&#23567;&#35745;</th>
                <th width="8%">Diskon<br>&#25240;&#25187;</th>
                <th width="6%">PPN<br>&#22686;&#20540;&#31246;</th>
                <th width="12%">Sub Total<br>&#24635;&#35745;</th>
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
    
    <div class="totals">
        <table>
            <tr>
                <td class="label">Total Sebelum Pajak & Diskon /<br>&#31246;&#21069;&#25240;&#25187;&#21069;&#24635;&#39069; :</td>
                <td class="value">IDR <?= number_format($subtotal, 0) ?></td>
            </tr>
            <tr>
                <td class="label">Diskon / &#25240;&#25187; :</td>
                <td class="value">IDR 0</td>
            </tr>
            <tr>
                <td class="label">PPN 11% / &#22686;&#20540;&#31246;11% :</td>
                <td class="value"><?= $ppn > 0 ? 'IDR ' . number_format($ppn, 0) : '-' ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td class="label" style="background-color: #f0f0f0;">Grand Total / &#24635;&#35745; :</td>
                <td class="value" style="background-color: #f0f0f0;">IDR <?= number_format($grand_total, 0) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="payment-info">
        <strong>Payment Terms / &#20184;&#27454;&#26465;&#20214;:</strong> 
        <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?><br>
        <strong>Delivery / &#20132;&#36135;&#26041;&#24335;:</strong> 
        <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?>
    </div>
    
    <div class="signatures">
        <table>
            <tr>
                <td class="header-cell">Disetujui<br>&#25209;&#20934;</td>
                <td class="header-cell">Kepala Bagian<br>&#37096;&#38376;&#20027;&#31649;</td>
                <td class="header-cell">Kepala Akuntansi<br>&#20250;&#35745;&#20027;&#31649;</td>
                <td class="header-cell">Pemohon<br>&#30003;&#35831;&#20154;</td>
            </tr>
            <tr>
                <td>
                    <br><br><br>
                    (...........................)<br>
                    (DIREKSI)
                </td>
                <td>
                    <br><br><br>
                    (...........................)<br>
                    (SPVS)
                </td>
                <td>
                    <br><br><br>
                    (...........................)<br>
                    (FIN)
                </td>
                <td>
                    <br><br><br>
                    (...........................)<br>
                    (PPB)
                </td>
            </tr>
        </table>
    </div>
</body>
</html>