<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PO Report - <?= htmlspecialchars($po_header->po_number ?? 'N/A'); ?></title>
    <style>
        @page { margin: 25px 35px; }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            font-size: 10px; 
            color: #333; 
        }
        .header-title { 
            text-align: center; 
            font-size: 16pt; 
            font-weight: bold; 
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .header-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 20px;
            color: #555;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-table { 
            width: 100%; 
            margin-bottom: 15px; 
            border-collapse: collapse;
        }
        .info-table td { 
            padding: 5px; 
            vertical-align: top;
            font-size: 9px;
        }
        .info-table .label { 
            font-weight: bold; 
            width: 120px;
            color: #666;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        .items-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px;
        }
        .items-table th, .items-table td { 
            border: 1px solid #333; 
            padding: 6px; 
            font-size: 8px;
        }
        .items-table th { 
            background-color: #4472C4; 
            color: white; 
            font-weight: bold; 
            text-align: center;
        }
        .items-table td.center { text-align: center; }
        .items-table td.right { text-align: right; }
        .items-table td.left { text-align: left; }
        .grn-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            margin-left: 20px;
        }
        .grn-table th, .grn-table td {
            border: 1px solid #666;
            padding: 4px;
            font-size: 8px;
        }
        .grn-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .status-badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .status-open { background-color: #FFF3CD; color: #856404; }
        .status-closed { background-color: #D4EDDA; color: #155724; }
        .status-partial { background-color: #D1ECF1; color: #0C5460; }
        .footer-note {
            margin-top: 20px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-title">PURCHASE ORDER REPORT</div>
    <div class="header-subtitle">Laporan PO & Status Penerimaan</div>

    <!-- PO Info -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">No. PO:</td>
                <td><strong><?= htmlspecialchars($po_header->po_number ?? '-'); ?></strong></td>
                <td class="label">Tanggal PO:</td>
                <td><?= $po_header->po_date ? date('d/m/Y', strtotime($po_header->po_date)) : '-'; ?></td>
            </tr>
            <tr>
                <td class="label">Supplier:</td>
                <td colspan="3"><strong><?= htmlspecialchars($po_header->nama_supplier ?? '-'); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Alamat:</td>
                <td colspan="3"><?= htmlspecialchars($po_header->supplier_address ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td>
                    <span class="status-badge <?= ($po_header->status == 'Open' ? 'status-open' : 'status-closed'); ?>">
                        <?= strtoupper($po_header->status ?? 'N/A'); ?>
                    </span>
                </td>
                <td class="label">Currency:</td>
                <td><?= htmlspecialchars($po_header->currency_code ?? 'IDR'); ?></td>
            </tr>
            <?php if (!empty($po_header->remarks)): ?>
            <tr>
                <td class="label">Catatan:</td>
                <td colspan="3"><?= htmlspecialchars($po_header->remarks); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Detail Items -->
    <div class="section-title">Detail Item PO & Status Penerimaan</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Kode Material</th>
                <th style="width: 25%;">Nama Material</th>
                <th style="width: 8%;">Qty PO</th>
                <th style="width: 7%;">Unit</th>
                <th style="width: 12%;">Harga</th>
                <th style="width: 12%;">Total</th>
                <th style="width: 8%;">Qty Terima</th>
                <th style="width: 8%;">Sisa</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($po_details)): ?>
                <?php 
                $no = 1; 
                $grand_total = 0;
                foreach ($po_details as $item): 
                    $total = $item->qty * $item->base_price;
                    $grand_total += $total;
                    $qty_received = (float)($item->qty_received ?? 0);
                    $sisa = $item->qty - $qty_received;
                ?>
                <tr>
                    <td class="center"><?= $no++; ?></td>
                    <td class="left"><?= htmlspecialchars($item->kode_material ?? '-'); ?></td>
                    <td class="left"><?= htmlspecialchars($item->nama_material ?? '-'); ?></td>
                    <td class="right"><?= number_format($item->qty, 2, ',', '.'); ?></td>
                    <td class="center"><?= htmlspecialchars($item->satuan ?? 'PCS'); ?></td>
                    <td class="right"><?= number_format($item->base_price, 2, ',', '.'); ?></td>
                    <td class="right"><?= number_format($total, 2, ',', '.'); ?></td>
                    <td class="right"><?= number_format($qty_received, 2, ',', '.'); ?></td>
                    <td class="right"><?= number_format($sisa, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="6" class="right">GRAND TOTAL:</td>
                    <td class="right"><?= number_format($grand_total, 2, ',', '.'); ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="center">Tidak ada item dalam PO ini</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- GRN History -->
    <?php if (!empty($grns)): ?>
    <div class="section-title">Riwayat Penerimaan (GRN)</div>
    <?php foreach ($grns as $grn): ?>
        <div style="margin-bottom: 15px;">
            <p style="margin: 5px 0; font-weight: bold;">
                <strong>GRN:</strong> <?= htmlspecialchars($grn->grn_number); ?> | 
                <strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($grn->grn_date)); ?> | 
                <strong>Status:</strong> <?= htmlspecialchars($grn->status ?? '-'); ?>
            </p>
            
            <table class="grn-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">No</th>
                        <th style="width: 35%;">Material</th>
                        <th style="width: 15%;">Qty Terima</th>
                        <th style="width: 15%;">Qty Reject</th>
                        <th style="width: 10%;">Unit</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grn_details = $this->Po_report_model->get_grn_details($po_header->id, $grn->id);
                    if (!empty($grn_details)):
                        $n = 1;
                        foreach ($grn_details as $gd):
                    ?>
                    <tr>
                        <td class="center"><?= $n++; ?></td>
                        <td class="left"><?= htmlspecialchars($gd->nama_material ?? $gd->kode_material ?? '-'); ?></td>
                        <td class="right"><?= number_format($gd->receive_qty, 2, ',', '.'); ?></td>
                        <td class="right">0,00</td>
                        <td class="center"><?= htmlspecialchars($gd->satuan ?? 'PCS'); ?></td>
                        <td class="center">
                            <span class="status-badge <?= ($gd->status == 'Approved' ? 'status-closed' : 'status-open'); ?>">
                                <?= strtoupper($gd->status ?? 'N/A'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr><td colspan="6" class="center">Tidak ada detail GRN</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="section-title">Riwayat Penerimaan (GRN)</div>
    <p style="text-align: center; color: #999; font-style: italic;">Belum ada penerimaan untuk PO ini</p>
    <?php endif; ?>

    <div class="footer-note">
        Dokumen dicetak pada: <?= date('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>
