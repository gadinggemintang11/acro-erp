<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 20px; 
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
        }
        td, th { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: center; 
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
        }
        .no-border { 
            border: none; 
        }
        .left { 
            text-align: left; 
        }
        .right { 
            text-align: right; 
        }
        .logo-area {
            text-align: center;
            border: 2px solid #000;
            padding: 20px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td class="no-border left" width="30%">
                <strong>SUPPLIER / 供应商:</strong><br>
                <?= htmlspecialchars($header->nama_supplier ?? '') ?><br>
                <?= htmlspecialchars($header->alamat ?? '') ?>
            </td>
            <td class="no-border" width="40%">
                <div class="logo-area">
                    <?php 
                    $logo_path = '/Applications/XAMPP/xamppfiles/htdocs/mingbao_erp/assets/img/logo_mini.png';
                    if (file_exists($logo_path)): 
                        $logo_data = base64_encode(file_get_contents($logo_path));
                    ?>
                        <img src="data:image/png;base64,<?= $logo_data ?>" style="max-height: 50px; max-width: 120px;" alt="Company Logo">
                    <?php else: ?>
                        <strong>LOGO COMPANY</strong>
                    <?php endif; ?>
                </div>
                <h2 style="text-align: center; margin: 10px 0;">PURCHASE ORDER</h2>
            </td>
            <td class="no-border right" width="30%">
                <strong>Purchase No / 采购编号:</strong><br>
                <?= htmlspecialchars($header->po_number) ?><br><br>
                <strong>Date / 日期:</strong><br>
                <?= date('d-m-Y', strtotime($header->po_date)) ?>
            </td>
        </tr>
    </table>

    <!-- Main Table -->
    <table>
        <tr>
            <th width="5%">No<br>编号</th>
            <th width="25%">Nama Barang<br>商品名称</th>
            <th width="15%">Chinese<br>中文翻译</th>
            <th width="8%">Qty<br>数量</th>
            <th width="8%">Unit<br>单位</th>
            <th width="15%">Harga<br>价格</th>
            <th width="15%">Total<br>小计</th>
            <th width="9%">Sub Total<br>总计</th>
        </tr>
        <?php if (!empty($details)): ?>
            <?php $no = 1; ?>
            <?php foreach ($details as $item): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td class="left"><?= htmlspecialchars($item->item_name ?? $item->description) ?></td>
                <td style="background-color: #fffacd; color: #d2691e; font-weight: bold;">
                    <?= $translate_to_chinese($item->item_name ?? $item->description) ?>
                </td>
                <td><?= number_format($item->qty, 0) ?></td>
                <td><?= htmlspecialchars($item->item_unit ?? 'PCS') ?></td>
                <td class="right">IDR <?= number_format($item->local_price, 0) ?></td>
                <td class="right">IDR <?= number_format($item->total_local, 0) ?></td>
                <td class="right">IDR <?= number_format($item->total_local, 0) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Totals -->
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
    
    <table style="width: 300px; margin: 20px 0 20px auto;">
        <tr>
            <td class="right" style="background-color: #f0f0f0;"><strong>Subtotal:</strong></td>
            <td class="right"><strong>IDR <?= number_format($subtotal, 0) ?></strong></td>
        </tr>
        <tr>
            <td class="right" style="background-color: #f0f0f0;"><strong>PPN 11%:</strong></td>
            <td class="right"><strong><?= $ppn > 0 ? 'IDR ' . number_format($ppn, 0) : '-' ?></strong></td>
        </tr>
        <tr>
            <td class="right" style="background-color: #f0f0f0;"><strong>Grand Total:</strong></td>
            <td class="right"><strong>IDR <?= number_format($grand_total, 0) ?></strong></td>
        </tr>
    </table>

    <!-- Payment Terms -->
    <p><strong>Payment Terms / 付款条件:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? 'CASH') ?></p>
    <p><strong>Delivery / 交货方式:</strong> <?= htmlspecialchars($header->shipping_mode_desc ?? 'TRUCK') ?></p>

    <!-- Signatures -->
    <table style="margin-top: 30px;">
        <tr>
            <th width="25%">Disetujui<br>批准</th>
            <th width="25%">Kepala Bagian<br>部门主管</th>
            <th width="25%">Kepala Akuntansi<br>会计主管</th>
            <th width="25%">Pemohon<br>申请人</th>
        </tr>
        <tr style="height: 80px;">
            <td><br><br><br>(.......................)<br>(DIREKSI)</td>
            <td><br><br><br>(.......................)<br>(SPVS)</td>
            <td><br><br><br>(.......................)<br>(FIN)</td>
            <td><br><br><br>(.......................)<br>(PPB)</td>
        </tr>
    </table>

</body>
</html>