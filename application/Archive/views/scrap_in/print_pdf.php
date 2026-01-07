<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title); ?></title>
    <style> /* (Gunakan style PDF Anda) */
        body { font-family: sans-serif; font-size: 11px; } .container { width: 95%; margin: auto; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;} 
        .header h3 { margin: 0; } .header p { margin: 2px 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; text-align: left; vertical-align: top;}
        .table th { background-color: #f2f2f2; text-align: center;}
        .text-center { text-align: center; } .text-right { text-align: right; }
        .info-table td { border: none; padding: 2px 5px;}
        .details ul { padding-left: 15px; margin: 0; } .details li { margin-bottom: 2px;}
        .signatures { margin-top: 30px; width: 100%; }
        .sig-box { width: 33%; float: left; text-align: center; } .sig-box p { margin-bottom: 50px; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <?php 
        $header = $data['header']; 
        $details = $data['details'];
    ?>
    <div class="container">
        <div class="header">
            <h3>BUKTI PENERIMAAN SCRAP</h3>
            <p>No. Dokumen: <?= htmlspecialchars($header->doc_no); ?></p>
        </div>

        <table class="table info-table" style="margin-bottom: 20px;">
             <tr>
                <td width="15%"><strong>Tgl Dokumen</strong></td><td width="1%">:</td><td width="34%"><?= date('d M Y', strtotime($header->doc_date)); ?></td>
                <td width="15%"><strong>Status</strong></td><td width="1%">:</td><td width="34%"><?= htmlspecialchars($header->status); ?></td>
            </tr>
             <tr>
                <td><strong>Dibuat Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->created_by_name ?: '-'); ?></td>
                <td><strong>Dikonfirmasi Oleh</strong></td><td>:</td><td><?= htmlspecialchars($header->confirmed_by_name ?: '-'); ?></td>
            </tr>
             <tr>
                <td><strong>Catatan</strong></td><td>:</td><td colspan="4"><?= nl2br(htmlspecialchars($header->notes ?: '-')); ?></td>
            </tr>
        </table>

        <table class="table details">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Kode Scrap</th>
                    <th>Nama Scrap</th>
                    <th width="8%">Satuan</th>
                    <th width="12%">Total Qty</th>
                    <th>Sumber Produksi / WO</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($details)): $no = 1; foreach($details as $item): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['scrap_code']); ?></td>
                    <td><?= htmlspecialchars($item['scrap_name']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['satuan_desc']); ?></td>
                    <td class="text-right"><?= (float)$item['qty']; ?></td>
                    <td>
                         <?php if (!empty($item['sources'])): ?>
                            <ul style="font-size: 9px;">
                                <?php foreach ($item['sources'] as $source): ?>
                                    <li>
                                        <?= htmlspecialchars($source['wo_number'] ?: 'N/A'); ?> 
                                        (Prod: <?= htmlspecialchars($source['production_number'] ?: 'N/A'); ?>) 
                                        - Qty: <?= (float)$source['qty_collected']; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada item detail.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signatures clearfix">
            <div class="sig-box">
                <p>Dibuat Oleh,</p>
                ( <?= htmlspecialchars($header->created_by_name ?: '. . . . . . . . . .'); ?> )
            </div>
            <div class="sig-box" style="float: right;">
                 <p>Dikonfirmasi Oleh,</p>
                ( <?= htmlspecialchars($header->confirmed_by_name ?: '. . . . . . . . . .'); ?> )
            </div>
        </div>
    </div>
</body>
</html>