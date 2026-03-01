<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .header-info { margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; }
        .header-info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th { background-color: #f0f0f0; border: 1px solid #999; padding: 8px; text-align: left; }
        table td { border: 1px solid #999; padding: 8px; }
        .total-row { background-color: #f9f9f9; font-weight: bold; }
    </style>
</head>
<body>
    <h2>RETUR SUPPLIER</h2>

    <div class="header-info">
        <p><strong>Nomor Retur:</strong> <?php echo $header->out_number ?? '-'; ?></p>
        <p><strong>Tanggal:</strong> <?php echo isset($header->created_at) ? date('d M Y', strtotime($header->created_at)) : '-'; ?></p>
        <p><strong>Status:</strong> <?php echo $header->status ?? '-'; ?></p>
        <?php if (!empty($header->reference_note)): ?>
            <p><strong>Referensi / Catatan:</strong> <?php echo nl2br(htmlspecialchars($header->reference_note)); ?></p>
        <?php endif; ?>
        <?php if (!empty($header->bc_number) || !empty($header->bc_type_code)): ?>
            <p><strong>BC:</strong> <?php echo htmlspecialchars(($header->bc_type_code ?? '') . ' ' . ($header->bc_number ?? '')); ?> <?php echo !empty($header->bc_date) ? '('.htmlspecialchars($header->bc_date).')' : ''; ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Satuan</th>
                <th>QTY PO/MIR</th>
                <th>QTY DITERIMA</th>
                <th>QTY RETUR</th>
                <th>Unit Value</th>
                <th>Total Value</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_qty = 0;
            $total_value = 0;
            $no = 1;
            foreach ($details as $d):
                $qty = isset($d->qty_return) ? (float)$d->qty_return : 0;
                $unit_value = isset($d->unit_value) ? (float)$d->unit_value : (float)($d->unit_value_idr ?? 0);
                $total_line = $qty * $unit_value;
                $total_qty += $qty;
                $total_value += $total_line;
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($d->item_code ?? ($d->kode_barang ?? '-')); ?></td>
                    <td><?php echo htmlspecialchars($d->item_name ?? ($d->nama_barang ?? '-')); ?></td>
                    <td><?php echo htmlspecialchars($d->unit ?? ($d->satuan ?? '-')); ?></td>
                    <td style="text-align:right"><?php echo number_format((float)($d->qty_po ?? 0),2); ?></td>
                    <td style="text-align:right"><?php echo number_format((float)($d->qty_received ?? 0),2); ?></td>
                    <td style="text-align:right"><?php echo number_format($qty,2); ?></td>
                    <td style="text-align:right">Rp <?php echo number_format($unit_value,2); ?></td>
                    <td style="text-align:right">Rp <?php echo number_format($total_line,2); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="6" style="text-align:right">TOTAL:</td>
                <td style="text-align:right"><?php echo number_format($total_qty,2); ?></td>
                <td></td>
                <td style="text-align:right">Rp <?php echo number_format($total_value,2); ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($header->reference_note)): ?>
        <div style="margin-top: 15px;">
            <p><strong>Catatan:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($header->reference_note)); ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #666;">
        <p>Dicetak pada <?php echo date('d M Y H:i:s'); ?></p>
    </div>
</body>
</html>
