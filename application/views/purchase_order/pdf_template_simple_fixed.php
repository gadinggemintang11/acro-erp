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
        
        table {
            border-collapse: collapse;
        }
        
        .header-row {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .header-row td {
            border: none;
            vertical-align: top;
            padding: 10px;
        }
        
        .logo-box {
            text-align: center;
            border: 2px solid #333;
            background-color: #f8f8f8;
            padding: 15px;
            margin: 5px 0;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .main-table td,
        .main-table th {
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
            text-align: left;
        }
        
        .chinese-col {
            background-color: #fffacd;
            color: #d2691e;
            font-weight: bold;
        }
        
        .totals-table {
            width: 250px;
            margin: 20px 0 20px auto;
        }
        
        .totals-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9px;
        }
        
        .label {
            text-align: right;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .value {
            text-align: right;
        }
        
        .payment {
            margin: 20px 0;
            clear: both;
        }
        
        .signatures td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            height: 80px;
            width: 25%;
        }
        
        .sig-header {
            background-color: #f0f0f0;
            font-weight: bold;
            height: 30px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <table class="header-row" width="100%">
        <tr>
            <td width="35%">
                <strong>SUPPLIER / 供应商:</strong><br>
                <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
                <?= htmlspecialchars($header->alamat ?? '') ?>
            </td>
            <td width="30%">
                <div class="logo-box">
                    <?php 
                    $logo_path = FCPATH . 'assets/img/logo_mini.png';
                    if (file_exists($logo_path)): ?>
                        <img src="data:image/png;base64,<?= base64_encode(file_get_contents($logo_path)) ?>" 
                             style="max-height: 40px; max-width: 100px;" alt="Logo">
                    <?php else: ?>
                        <strong>COMPANY LOGO</strong>
                    <?php endif; ?>
                </div>
                <div class="title">PURCHASE ORDER</div>
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
    
    <!-- Main Table -->
    <table class="main-table" width="100%">
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
    </table>
    
    <!-- Totals Section -->
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
        <tr>
            <td class="label" style="background-color: #f0f0f0; font-weight: bold;">Grand Total / 总计 :</td>
            <td class="value" style="background-color: #f0f0f0; font-weight: bold;">IDR <?= number_format($grand_total, 0) ?></td>
        </tr>
    </table>
    
    <!-- Payment Info -->
    <div class="payment">
        <strong>Payment Terms / 付款条件:</strong> 
        <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?><br>
        <strong>Delivery / 交货方式:</strong> 
        <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?>
    </div>
    
    <!-- Signatures -->
    <table class="signatures" width="100%">
        <tr>
            <td class="sig-header">Disetujui<br>批准</td>
            <td class="sig-header">Kepala Bagian<br>部门主管</td>
            <td class="sig-header">Kepala Akuntansi<br>会计主管</td>
            <td class="sig-header">Pemohon<br>申请人</td>
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
</body>
</html>