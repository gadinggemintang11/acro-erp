<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #afafafff; padding: 4px; }
        .table th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        h3, h4 { margin: 0 0 10px 0; }
    </style>
</head>
<body>
    <h3><?= htmlspecialchars($title); ?></h3>
    <h4>Item: <?= htmlspecialchars($item->name . ' (' . $item->code . ')'); ?></h4>

    <table class="table">
        <thead>
            <tr>
                <th>Tgl. Transaksi</th>
                <th>Tipe</th>
                <th>No. Dokumen</th>
                <th>Ref. Sumber</th>
                <th>Tipe BC</th>
                <th>No. BC</th>
                <th>Tgl. BC</th>
                <th>Keterangan</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trace)): ?>
                <tr><td colspan="11" class="text-center">Tidak ada transaksi.</td></tr>
            <?php else: foreach($trace as $t): ?>
                <?php
                    $doc_num = ''; $doc_ref = ''; $bc_type = ''; $bc_num = ''; $bc_date_formatted = ''; $notes = '';
                    switch($t->transaction_type) {
                        case 'in': $doc_num = $t->grn_number; $doc_ref = $t->po_number; $bc_type = $t->bc_type_code; $bc_num = $t->bc_number; $bc_date_formatted = $t->bc_date ? date('d-m-Y', strtotime($t->bc_date)) : ''; break;
                        case 'production_out': $doc_num = $t->mr_number; break;
                        case 'IN_RETURN_WIP': $doc_num = $t->prod_return_no; break;
                        case 'adjustment': $doc_num = $t->adjustment_doc; $notes = $t->adjustment_notes; break;
                    }
                ?>
                <tr>
                    <td><?= date('d-m-Y H:i', strtotime($t->transaction_date)); ?></td>
                    <td><?= htmlspecialchars($t->transaction_type); ?></td>
                    <td><?= htmlspecialchars($doc_num); ?></td>
                    <td><?= htmlspecialchars($doc_ref); ?></td>
                    <td><?= htmlspecialchars($bc_type); ?></td>
                    <td><?= htmlspecialchars($bc_num); ?></td>
                    <td><?= $bc_date_formatted; ?></td>
                    <td><?= htmlspecialchars($notes); ?></td>
                    <td class="text-right"><?= $t->qty_in > 0 ? number_format($t->qty_in, 2) : ''; ?></td>
                    <td class="text-right"><?= $t->qty_out > 0 ? number_format($t->qty_out, 2) : ''; ?></td>
                    <td class="text-right"><?= number_format($t->balance, 2); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
