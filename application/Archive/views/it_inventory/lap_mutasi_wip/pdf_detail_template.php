<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; }
        .table th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
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
                <th>Dokumen Sumber (Traceability)</th>
                <th class="text-right">Masuk</th>
                <th class="text-right">Keluar</th>
                <th class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trace)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada transaksi WIP ditemukan.</td></tr>
            <?php else: foreach($trace as $t): ?>
                <?php
                    $doc_info = '';
                    switch($t->transaction_type) {
                        case 'wip_in':
                            $doc_info = ($item_type == 'material') ? "MR No: {$t->mr_number}" : "Prod. No: {$t->prod_num_in} (Stage: {$t->stage_in})";
                            break;
                        case 'wip_out':
                            $doc_info = "Prod. No: {$t->prod_num_out} (Stage: {$t->stage_out})";
                            break;
                        case 'IN_RETURN_WIP':
                            $doc_info = "Return No: {$t->prod_return_no}";
                            break;
                    }
                ?>
                <tr>
                    <td><?= date('d-m-Y H:i', strtotime($t->transaction_date)); ?></td>
                    <td><?= htmlspecialchars($t->transaction_type); ?></td>
                    <td><?= $doc_info; ?></td>
                    <td class="text-right"><?= $t->qty_in > 0 ? number_format($t->qty_in, 2) : ''; ?></td>
                    <td class="text-right"><?= $t->qty_out > 0 ? number_format($t->qty_out, 2) : ''; ?></td>
                    <td class="text-right"><?= number_format($t->balance, 2); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
