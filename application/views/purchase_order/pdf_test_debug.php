<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DEBUG TEST TEMPLATE <?= time() ?></title>
    <style>
        body {
            background-color: red !important;
            color: white !important;
            font-size: 20px !important;
        }
        h1 {
            text-align: center;
            background: blue;
            color: yellow;
            padding: 20px;
        }
    </style>
</head>
<body>
    <h1>🔥 DEBUG TEST - IF YOU SEE THIS, TEMPLATE LOADING WORKS 🔥</h1>
    <h2>Purchase Order: <?= htmlspecialchars($header->po_number ?? 'TEST') ?></h2>
    <p>Current Time: <?= date('Y-m-d H:i:s') ?></p>
    <p style="font-size: 24px; color: yellow;">中文测试 - CHINESE TEST</p>
    
    <?php if (!empty($details)): ?>
        <table style="width: 100%; border: 2px solid yellow; background: green;">
            <tr style="background: purple; color: white;">
                <th>Item</th>
                <th>中文翻译 Chinese</th>
                <th>Qty</th>
            </tr>
            <?php foreach($details as $item): ?>
            <tr style="background: orange; color: black;">
                <td><?= htmlspecialchars($item->item_name) ?></td>
                <td style="color: red; font-weight: bold;"><?= $translate_to_chinese($item->item_name) ?></td>
                <td><?= htmlspecialchars($item->qty) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>