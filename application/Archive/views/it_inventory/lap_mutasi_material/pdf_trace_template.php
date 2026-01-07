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
                <th>Tgl. Produksi</th>
                <th>No. Produksi</th>
                <th>No. WO</th>
                <th>Arah Jejak</th>
                <th>Item Terkait</th>
                <th class="text-right">Qty Terkait</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trace)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada jejak produksi ditemukan.</td></tr>
            <?php else: foreach($trace as $t): ?>
                <?php
                    $badge_text = ($t->direction == 'input') ? 'DIBUAT DARI' : 'DIPAKAI UNTUK';
                ?>
                <tr>
                    <td><?= date('d-m-Y', strtotime($t->transaction_date)); ?></td>
                    <td><?= htmlspecialchars($t->production_number); ?></td>
                    <td><?= htmlspecialchars($t->wo_number); ?></td>
                    <td>
                        <?= $badge_text; ?>
                        <br>
                        <small>(Qty Asal: <?= (float)$t->original_qty; ?> <?= htmlspecialchars($t->original_satuan); ?>)</small>
                    </td>
                    <td><?= htmlspecialchars($t->related_item_name); ?></td>
                    <td class="text-right">
                        <?= (float)$t->related_qty; ?> <?= htmlspecialchars($t->related_satuan); ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</body>
</html>
