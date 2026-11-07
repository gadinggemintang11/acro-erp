<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 5px;
        }
        .header-info {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .header-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th {
            background-color: #f0f0f0;
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        table td {
            border: 1px solid #999;
            padding: 8px;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>PENGELUARAN BARANG GUDANG</h2>
    
    <div class="header-info">
        <p><strong>WO Number:</strong> <?php echo $header->wo_number; ?></p>
        <p><strong>Tanggal:</strong> <?php echo date('d M Y', strtotime($header->wo_date)); ?></p>
        <p><strong>Tipe Pengeluaran:</strong> <?php echo $header->wo_type; ?></p>
        <p><strong>Gudang:</strong> <?php echo $header->warehouse_type; ?></p>
        <p><strong>Partner:</strong> <?php echo $header->nama_supplier ?: $header->partner_name ?: '-'; ?></p>
        <p><strong>Status:</strong> <?php echo $header->status; ?></p>
        <?php if ($header->bc_number): ?>
            <p><strong>BC Info:</strong> <?php echo $header->bc_type_code . ' / ' . $header->bc_number . ' (' . $header->bc_date . ')'; ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Unit Value</th>
                <th>Total Value</th>
                <th>GRN Asal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_qty = 0;
            $total_value = 0;
            $no = 1;
            foreach ($details as $detail):
                $row_qty   = (float)($detail->qty_return ?? $detail->qty ?? 0);
                $row_unit  = (float)($detail->unit_value_idr ?? $detail->unit_value ?? 0);
                $row_total = $row_qty * $row_unit;
                $total_qty   += $row_qty;
                $total_value += $row_total;
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($detail->item_code ?? $detail->kode_barang ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($detail->item_name ?? $detail->nama_barang ?? '-'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($row_qty, 2); ?></td>
                    <td><?php echo htmlspecialchars($detail->unit ?? $detail->satuan ?? '-'); ?></td>
                    <td style="text-align: right;">Rp <?php echo number_format($row_unit, 2); ?></td>
                    <td style="text-align: right;">Rp <?php echo number_format($row_total, 2); ?></td>
                    <td><?php echo htmlspecialchars($detail->grn_number ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL:</td>
                <td style="text-align: right;"><?php echo number_format($total_qty, 2); ?></td>
                <td colspan="2"></td>
                <td style="text-align: right;">Rp <?php echo number_format($total_value, 2); ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <?php if ($header->notes): ?>
        <div style="margin-top: 15px;">
            <p><strong>Catatan:</strong></p>
            <p><?php echo nl2br($header->notes); ?></p>
        </div>
    <?php endif; ?>

    <?php
    $created_sig = '';
    $confirmed_sig = '';

    if (!empty($header->created_by_signature)) {
        $sig_path = FCPATH . $header->created_by_signature;
        if (file_exists($sig_path)) {
            $created_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto; margin-bottom: 5px;" />';
        }
    }

    if (!empty($header->confirmed_by_signature)) {
        $sig_path = FCPATH . $header->confirmed_by_signature;
        if (file_exists($sig_path)) {
            $confirmed_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto; margin-bottom: 5px;" />';
        }
    }
    ?>

    <table style="margin-top: 40px; border: none;">
        <tr style="border: none;">
            <td style="width: 33%; text-align: center; border: 1px solid #999; padding: 15px; vertical-align: bottom; height: 100px;">
                <div><?= $created_sig; ?></div>
                <strong><?php echo $header->created_by_name ?: '_________________'; ?></strong>
                <div style="font-size: 9px;">Dibuat Oleh</div>
            </td>
            <td style="width: 33%; text-align: center; border: 1px solid #999; padding: 15px; vertical-align: bottom; height: 100px;">
                <div>&nbsp;</div>
                <strong>_________________</strong>
                <div style="font-size: 9px;">Diperiksa Oleh</div>
            </td>
            <td style="width: 33%; text-align: center; border: 1px solid #999; padding: 15px; vertical-align: bottom; height: 100px;">
                <div><?= $confirmed_sig; ?></div>
                <strong><?php echo $header->confirmed_by_name ?: '_________________'; ?></strong>
                <div style="font-size: 9px;">Dikonfirmasi Oleh</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #666;">
        <p>Dokumen ini dicetak pada <?php echo date('d M Y H:i:s'); ?></p>
    </div>
</body>
</html>
