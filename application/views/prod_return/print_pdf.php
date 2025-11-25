<!DOCTYPE html>
<html>
<head>
    <title>Bukti Retur Material - <?= htmlspecialchars($return_data['header']['prod_return_no']); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .container { width: 95%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 0; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 4px; vertical-align: top; }
        .info-table-left { width: 50%; float: left; }
        .info-table-right { width: 50%; float: right; }

        .item-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .item-table th, .item-table td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left;
        }
        .item-table th { background-color: #f0f0f0; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .signatures { margin-top: 40px; width: 100%; }
        .sig-box { width: 30%; float: left; text-align: center; }
        .sig-box.receiver { float: right; }
        .sig-box p { margin-bottom: 60px; }

        .clearfix { clear: both; }
    </style>
</head>
<body>
    <?php 
        $header = $return_data['header']; 
        $details = $return_data['details'];
    ?>
    <div class="container">
        <div class="header">
            <h1>BUKTI RETUR MATERIAL DARI PRODUKSI</h1>
            <p>Nomor: <?= htmlspecialchars($header['prod_return_no']); ?></p>
        </div>

        <div class="info">
            <div class="info-table-left">
                <table class="info-table">
                    <tr>
                        <td width="120"><strong>Tgl Retur</strong></td>
                        <td width="10">:</td>
                        <td><?= date('d M Y', strtotime($header['return_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>No. Work Order</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($header['wo_number']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Diretur Oleh</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($header['returner_name'] ?: '-'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="info-table-right">
                <table class="info-table">
                    <tr>
                        <td width="120"><strong>Status</strong></td>
                        <td width="10">:</td>
                        <td><?= htmlspecialchars($header['status']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Diterima Oleh</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($header['receiver_name'] ?: '-'); ?></td>
                    </tr>
                     <tr>
                        <td><strong>Catatan</strong></td>
                        <td>:</td>
                        <td><?= htmlspecialchars($header['notes'] ?: '-'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="clearfix"></div>
        </div>

        <table class="item-table">
            <thead>
                <tr>
                    <th width="30">No.</th>
                    <th>Material</th>
                    <th width="60">Satuan</th>
                    <th width="80">Qty Retur</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($details)): $no = 1; foreach($details as $item): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($item['nama_material']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['satuan_desc']); ?></td>
                    <td class="text-right"><?= (float)$item['qty_return']; ?></td>
                    <td><?= htmlspecialchars($item['reason'] ?: '-'); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center">Tidak ada item detail.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signatures">
            <div class="sig-box sender">
                <p>Diretur Oleh (Produksi),</p>
                ( <?= htmlspecialchars($header['returner_name'] ?: '. . . . . . . . . . . . . . . . .'); ?> )
            </div>
            <div class="sig-box receiver">
                <p>Diterima Oleh (Gudang),</p>
                ( <?= htmlspecialchars($header['receiver_name'] ?: '. . . . . . . . . . . . . . . . .'); ?> )
            </div>
            <div class="clearfix"></div>
        </div>

    </div>
</body>
</html>