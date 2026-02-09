<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= lang_line('stock_ledger_pdf_title', 'Laporan Stok Barang'); ?> - <?= $periode ?></title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        h1 { text-align: center; margin-bottom: 0px; font-size: 14px; text-decoration: underline; text-transform: uppercase;}
        h2 { text-align: center; margin-top: 2px; margin-bottom: 10px; font-size: 11px; font-weight: normal;}
        p { margin: 0; }
        
        .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .item-table th, .item-table td { border: 1px solid #777; padding: 4px; text-align: left; vertical-align: top; }
        .item-table th { background-color: #EAEAEA; font-weight: bold; text-align: center;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .footer { position: fixed; bottom: 0px; left: 0px; right: 0px; height: 30px; font-size: 8px; text-align: center; border-top: 0.5px solid #ccc; padding-top: 5px;}
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
    <h1><?= lang_line('stock_ledger_pdf_title', 'Laporan Stok Barang'); ?></h1>
    <h2><?= lang_line('stock_ledger_pdf_period', 'Periode'); ?>: <?= htmlspecialchars($periode) ?></h2>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:10%"><?= lang_line('stock_ledger_warehouse_col', 'Gudang'); ?></th>
                <th style="width:12%"><?= lang_line('stock_ledger_item_code', 'Kode Barang'); ?></th>
                <th><?= lang_line('stock_ledger_item_name', 'Nama Barang'); ?></th>
                <th style="width:8%"><?= lang_line('stock_ledger_unit', 'Satuan'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_opening', 'Stok Awal'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_in', 'Masuk'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_return', 'Return'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_out', 'Keluar'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_adjustment', 'Adjustment'); ?></th>
                <th style="width:10%"><?= lang_line('stock_ledger_pdf_closing', 'Stok Akhir'); ?></th>
            </tr>
        </thead>
        <tbody>
             <?php if(!empty($report)): foreach($report as $row): ?>
            <tr>
                <td><?= ucfirst($row->item_type); ?></td>
                <td><?= htmlspecialchars($row->item_code); ?></td>
                <td><?= htmlspecialchars($row->item_name); ?></td>
                <td class="text-center"><?= htmlspecialchars($row->item_unit ?? '-'); ?></td>
                <td class="text-right"><?= number_format($row->opening_stock, 2); ?></td>
                <td class="text-right"><?= $row->receive_qty > 0 ? number_format($row->receive_qty, 2) : '-'; ?></td>
                <td class="text-right"><?= $row->return_qty > 0 ? number_format($row->return_qty, 2) : '-'; ?></td>
                <td class="text-right"><?= $row->out_qty > 0 ? number_format($row->out_qty, 2) : '-'; ?></td>
                <td class="text-right"><?= $row->adjustment_qty != 0 ? number_format($row->adjustment_qty, 2) : '-'; ?></td>
                <td class="text-right"><?= number_format($row->closing_stock, 2); ?></td>
            </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center" style="padding: 20px;"><?= lang_line('stock_ledger_pdf_no_data', '- Tidak ada data stok ditemukan -'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <?= lang_line('stock_ledger_pdf_printed', 'Dicetak pada'); ?>: <?= date('d/m/Y H:i:s'); ?> | <?= lang_line('stock_ledger_pdf_page', 'Hal'); ?> <span class="page"></span>
    </div>
</body>
</html>