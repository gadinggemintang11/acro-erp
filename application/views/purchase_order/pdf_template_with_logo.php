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
        
        .logo-container {
            text-align: center;
            border: 2px solid #333;
            background-color: #f8f8f8;
            padding: 10px;
            margin: 5px 0;
            height: 60px;
            display: table-cell;
            vertical-align: middle;
            width: 100%;
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
                    <strong>SUPPLIER / 供应商:</strong><br>
                    <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
                    <?= htmlspecialchars($header->alamat ?? '') ?>
                </td>
                <td width="30%">
                    <div class="logo-container">
                        <?php 
                        $logo_path = FCPATH . 'assets/img/logo_mini.png';
                        if (file_exists($logo_path)): ?>
                            <img src="data:image/png;base64,<?= base64_encode(file_get_contents($logo_path)) ?>" 
                                 style="max-height: 40px; max-width: 100px;" alt="Logo">
                        <?php else: ?>
                            <strong style="color: #666;">COMPANY LOGO</strong>
                        <?php endif; ?>
                    </div>
                    <h2 style="text-align: center; margin: 10px 0;">PURCHASE ORDER</h2>
                </td>
                <td width="35%" style="text-align: right;">
                    <strong>Purchase No / 采购编号:</strong><br>
                    <?= htmlspecialchars($header->po_number) ?><br><br>
                    <strong>Purchase Date / 采购日期:</strong><br>
                    <?= date('d-m-Y', strtotime($header->po_date)) ?><br><br>
                    <?= date('d-m-Y') ?>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No<br>编号</th>
                <th width="20%">Nama Barang<br>商品名称</th>
                <th width="15%">Chinese Translation<br>中文翻译</th>
                <th width="8%">Qty<br>数量</th>
                <th width="8%">Unit<br>单位</th>
                <th width="12%">Harga<br>价格</th>
                <th width="12%">Total<br>小计</th>
                <th width="8%">Diskon<br>折扣</th>
                <th width="6%">PPN<br>增值税</th>
                <th width="12%">Sub Total<br>总计</th>
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
                <td class="label">Total Sebelum Pajak & Diskon /<br>税前折扣前总额 :</td>
                <td class="value">IDR <?= number_format($subtotal, 0) ?></td>
            </tr>
            <tr>
                <td class="label">Diskon / 折扣 :</td>
                <td class="value">IDR 0</td>
            </tr>
            <tr>
                <td class="label">PPN 11% / 增值税11% :</td>
                <td class="value"><?= $ppn > 0 ? 'IDR ' . number_format($ppn, 0) : '-' ?></td>
            </tr>
            <tr style="font-weight: bold;">
                <td class="label" style="background-color: #f0f0f0;">Grand Total / 总计 :</td>
                <td class="value" style="background-color: #f0f0f0;">IDR <?= number_format($grand_total, 0) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="payment-info">
        <strong>Payment Terms / 付款条件:</strong> 
        <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?><br>
        <strong>Delivery / 交货方式:</strong> 
        <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?>
    </div>
    
    <div class="signatures">
        <table>
            <tr>
                <td class="header-cell">Disetujui<br>批准</td>
                <td class="header-cell">Kepala Bagian<br>部门主管</td>
                <td class="header-cell">Kepala Akuntansi<br>会计主管</td>
                <td class="header-cell">Pemohon<br>申请人</td>
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