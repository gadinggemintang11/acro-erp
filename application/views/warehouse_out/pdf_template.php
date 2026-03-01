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
                $total_qty += $detail->qty;
                $total_value += $detail->total_value;
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $detail->kode_barang; ?></td>
                    <td><?php echo $detail->nama_barang; ?></td>
                    <td style="text-align: right;"><?php echo number_format($detail->qty, 2); ?></td>
                    <td><?php echo $detail->satuan; ?></td>
                    <td style="text-align: right;">Rp <?php echo number_format($detail->unit_value, 2); ?></td>
                    <td style="text-align: right;">Rp <?php echo number_format($detail->total_value, 2); ?></td>
                    <td><?php echo $detail->grn_number ?: '-'; ?></td>
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

    <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #666;">
        <p>Dokumen ini dicetak pada <?php echo date('d M Y H:i:s'); ?></p>
    </div>
</body>
</html>
