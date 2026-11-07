<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Grinding Report - <?= htmlspecialchars($header->production_number ?? 'N/A'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'sun-extA', 'SimSun', 'DejaVu Sans', Arial, sans-serif; 
            color: #333; 
            background: #fff; 
            font-size: 9px; 
            line-height: 1.4;
        }
        
        .page { 
            padding: 16px 18px; 
            max-width: 210mm;
        }
        
        /* Header with Logo */
        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1d3557;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
        }
        
        .logo-section img {
            height: 25px;
            width: auto;
            object-fit: contain;
        }
        
        .company-info {
            display: flex;
            flex-direction: column;
        }
        
        .company-info .name {
            font-size: 14px;
            font-weight: bold;
            color: #1d3557;
            letter-spacing: 1px;
        }
        
        .company-info .tagline {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
        }
        
        .doc-header {
            text-align: right;
            flex: 1;
        }
        
        .doc-header .title {
            font-size: 13px;
            font-weight: bold;
            color: #1d3557;
            margin-bottom: 6px;
        }
        
        .doc-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 8px;
        }
        
        .doc-meta .item {
            display: flex;
            justify-content: space-between;
        }
        
        .doc-meta .label {
            font-weight: bold;
            color: #666;
        }
        
        .doc-meta .value {
            font-weight: bold;
            color: #333;
        }
        
        /* Info Boxes */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 12px 0 14px;
        }
        
        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px 12px;
        }
        
        .info-box .row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            margin-bottom: 5px;
        }
        
        .info-box .row:last-child {
            margin-bottom: 0;
        }
        
        .info-box .label {
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 45%;
        }
        
        .info-box .value {
            font-weight: 600;
            color: #222;
            text-align: right;
            width: 55%;
        }
        
        /* Section Headers */
        h3 {
            margin: 14px 0 8px;
            font-size: 10px;
            color: #fff;
            background: #1d3557;
            padding: 6px 10px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8px;
            border: 1px solid #1d3557;
        }
        
        table thead {
            background: #f0f0f0;
        }
        
        table th {
            font-weight: bold;
            color: #333;
            padding: 6px 6px;
            text-align: left;
            border: 1px solid #1d3557;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.4px;
            vertical-align: middle;
        }
        
        table td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        table tbody tr:hover {
            background: #f5f5f5;
        }
        
        .col-no { width: 20px; text-align: center; }
        .col-item { text-align: left; }
        .col-qty { width: 60px; text-align: right; font-weight: 600; padding-right: 10px; }
        .col-uom { width: 45px; text-align: center; }
        .col-center { text-align: center; width: 50px; }
        .col-source { width: 70px; text-align: left; font-size: 7px; }
        .col-name { text-align: left; }
        .col-notes { width: 80px; font-size: 7px; color: #666; }
        
        /* Summary Stats */
        .summary {
            display: flex;
            gap: 12px;
            margin: 10px 0 12px;
            justify-content: flex-end;
        }
        
        .stat-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            text-align: center;
            min-width: 90px;
        }
        
        .stat-box .number {
            font-weight: bold;
            font-size: 10px;
            color: #1d3557;
        }
        
        .stat-box .label {
            font-size: 7px;
            color: #666;
            margin-top: 3px;
            text-transform: uppercase;
        }
        
        /* Signature Table */
        .signature-section {
            margin-top: 20px;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #999;
        }
        
        .signature-table td {
            border: 1px solid #999;
            padding: 8px;
            height: 60px;
            text-align: center;
            vertical-align: top;
            font-size: 8px;
        }
        
        .signature-table .label-cell {
            background: #f0f0f0;
            font-weight: bold;
            color: #333;
        }
        
        .signature-table .sign-cell {
            font-family: 'DejaVu Sans', Arial;
            padding-top: 42px;
            font-weight: 500;
            color: #222;
            font-size: 8px;
        }
        
        /* Footer */
        footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 7px;
            color: #999;
            text-align: center;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-message {
            color: #999;
            padding: 12px;
            text-align: center;
            font-style: italic;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header-wrapper">
            <div class="logo-section">
                <img src="<?= FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png'); ?>" alt="<?= htmlspecialchars(company_info('company_name')) ?>" style="height: 45px; width: auto;">
                <div class="company-info">
                    <div class="name"><?= htmlspecialchars(company_info('company_name')) ?></div>
                    
                </div>
            </div>
            <div class="doc-header">
                <div class="title">GRINDING REPORT</div>
                <div class="doc-meta">
                    <div class="item">
                        <span class="label">No:</span>
                        <span class="value"><?= htmlspecialchars($header->production_number ?? '-'); ?></span>
                    </div>
                    <div class="item">
                        <span class="label">Date:</span>
                        <span class="value"><?= date('d M Y', strtotime($header->production_date ?? date('Y-m-d'))); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info Boxes -->
        <div class="info-section">
            <div class="info-box">
                <div class="row">
                    <span class="label">Work Order</span>
                    <span class="value"><?= htmlspecialchars($header->wo_number ?? '-'); ?></span>
                </div>
                <div class="row">
                    <span class="label">Finished Good</span>
                    <span class="value"><?= htmlspecialchars($header->fg_name ?? '-') . (isset($header->fg_code) ? ' (' . htmlspecialchars($header->fg_code) . ')' : ''); ?></span>
                </div>
                <div class="row">
                    <span class="label">Planned Qty</span>
                    <span class="value"><?= number_format($header->planned_qty ?? 0, 2); ?> <?= htmlspecialchars($header->fg_uom ?? ''); ?></span>
                </div>
            </div>
            <div class="info-box">
                <div class="row">
                    <span class="label">Sales Order Number</span>
                    <span class="value"><?= htmlspecialchars($header->sc_number ?? '-'); ?></span>
                </div>
                <div class="row">
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="status-badge status-<?php 
                            $status = strtolower($header->status ?? '');
                            if (strpos($status, 'completed') !== false || strpos($status, 'selesai') !== false) echo 'completed';
                            elseif (strpos($status, 'cancel') !== false || strpos($status, 'batal') !== false) echo 'cancelled';
                            else echo 'pending';
                        ?>">
                            <?= htmlspecialchars($header->status ?? '-'); ?>
                        </span>
                    </span>
                </div>
                <div class="row">
                    <span class="label">Remarks</span>
                    <span class="value"><?= htmlspecialchars(substr($header->remarks ?? '', 0, 30)); ?></span>
                </div>
            </div>
        </div>

        <?php
            $total_input_qty = 0.0;
            foreach ($grinding_inputs ?? [] as $r) {
                $total_input_qty += (float)($r->qty_used ?? 0);
            }
            $total_output_qty = 0.0;
            $total_scrap_qty = 0.0;
            foreach ($grinding_outputs ?? [] as $r) {
                if ($r->item_type == 'semifinished') {
                    $total_output_qty += (float)($r->qty_produced ?? 0);
                }
                if ($r->item_type == 'scrap') {
                    $qty = (float)($r->qty_produced ?? 0);
                    $total_scrap_qty += $qty;
                }
            }
        ?>

        <!-- Section A: Input Usage -->
        <h3>A. Input Usage</h3>
        <table>
            <thead>
                <tr>
                    <th class="col-no">#</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-uom">Unit</th>
                    <th class="col-name">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($grinding_inputs)): $i = 1; foreach ($grinding_inputs as $in): ?>
                <tr>
                    <td class="col-no"><?= $i++; ?></td>
                    <td class="col-item">
                        <strong><?= htmlspecialchars($in->item_name ?? '-'); ?></strong>
                        <?php if (!empty($in->item_code)): ?><br><small style="color:#999;"><?= htmlspecialchars($in->item_code); ?></small><?php endif; ?>
                    </td>
                    <td class="col-qty"><?= number_format($in->qty_used ?? 0, 2); ?></td>
                    <td class="col-uom"><?= htmlspecialchars($in->item_unit ?? ''); ?></td>
                    <td class="col-notes"><?= !empty($in->notes) ? htmlspecialchars(substr($in->notes, 0, 50)) : '-'; ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="empty-message">No input usage recorded</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <div class="stat-box">
                <div class="number"><?= number_format($total_input_qty, 2); ?></div>
                <div class="label">Total Input</div>
            </div>
        </div>

        <!-- Section B: Outputs -->
        <h3>B. Outputs</h3>
        <table>
            <thead>
                <tr>
                    <th class="col-no">#</th>
                    <th class="col-item">Item Result</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-uom">Unit</th>
                    <th class="col-name">By</th>
                    <th class="col-name">QC</th>
                </tr>
            </thead>
            <tbody>
                <?php $j = 1; $has_output = false; foreach ($grinding_outputs ?? [] as $out): if ($out->item_type == 'semifinished'): $has_output = true; ?>
                <tr>
                    <td class="col-no"><?= $j++; ?></td>
                    <td class="col-item">
                        <strong><?= htmlspecialchars($out->item_name ?? '-'); ?></strong>
                        <?php if (!empty($out->item_code)): ?><br><small style="color:#999;"><?= htmlspecialchars($out->item_code); ?></small><?php endif; ?>
                    </td>
                    <td class="col-qty"><?= number_format($out->qty_produced ?? 0, 2); ?></td>
                    <td class="col-uom"><?= htmlspecialchars($out->item_unit ?? ''); ?></td>
                    <td class="col-name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                    <td class="col-name"><?= !empty($out->qc_name) ? htmlspecialchars($out->qc_name) : '-'; ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_output): ?><tr><td colspan="6" class="empty-message">No outputs recorded</td></tr><?php endif; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="stat-box">
                <div class="number"><?= number_format($total_output_qty, 2); ?></div>
                <div class="label">SemiFinished</div>
            </div>
            <div class="stat-box">
                <div class="number" style="color: #dc3545;"><?= number_format($total_scrap_qty, 2); ?></div>
                <div class="label">Scrap</div>
            </div>
        </div>

        <!-- Section C: Scrap -->
        <h3>C. Scrap</h3>
        <table>
            <thead>
                <tr>
                    <th class="col-no">#</th>
                    <th class="col-item">Scrap Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-uom">Unit</th>
                    <th class="col-name">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php $k = 1; $has_scrap = false; foreach ($grinding_outputs ?? [] as $out): if ($out->item_type == 'scrap'): $has_scrap = true; ?>
                <tr>
                    <td class="col-no"><?= $k++; ?></td>
                    <td class="col-item">
                        <strong><?= htmlspecialchars($out->item_name ?? '-'); ?></strong>
                        <?php if (!empty($out->item_code)): ?><br><small style="color:#999;"><?= htmlspecialchars($out->item_code); ?></small><?php endif; ?>
                    </td>
                    <td class="col-qty"><?= number_format($out->qty_produced ?? 0, 2); ?></td>
                    <td class="col-uom"><?= htmlspecialchars($out->item_unit ?? ''); ?></td>
                    <td class="col-name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_scrap): ?><tr><td colspan="5" class="empty-message">No scrap recorded</td></tr><?php endif; ?>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <?php
                $operator_candidates = [];
                foreach (['creator_name', 'ppic_name', 'produksi_name', 'created_by_name', 'requester_name'] as $f) {
                    if (!empty($header->{$f})) $operator_candidates[] = $header->{$f};
                }
                if (!empty($grinding_inputs ?? []) && !empty($grinding_inputs[0])) {
                    $in0 = $grinding_inputs[0];
                    if (!empty($in0->receiver_name)) $operator_candidates[] = $in0->receiver_name;
                    if (!empty($in0->created_by_name)) $operator_candidates[] = $in0->created_by_name;
                }
                if (!empty($grinding_outputs ?? []) && !empty($grinding_outputs[0])) {
                    $out0 = $grinding_outputs[0];
                    if (!empty($out0->receiver_name)) $operator_candidates[] = $out0->receiver_name;
                    if (!empty($out0->created_by_name)) $operator_candidates[] = $out0->created_by_name;
                }
                $operator_name = '(............................... )';
                foreach ($operator_candidates as $c) {
                    if (!empty($c)) {
                        $operator_name = htmlspecialchars($c);
                        break;
                    }
                }

                $qc_name = '(............................... )';
                if (!empty($header->qc_name)) {
                    $qc_name = htmlspecialchars($header->qc_name);
                } else {
                    if (!empty($grinding_outputs ?? [])) {
                        foreach ($grinding_outputs as $o) {
                            if (!empty($o->qc_name)) {
                                $qc_name = htmlspecialchars($o->qc_name);
                                break;
                            }
                        }
                    }
                    if ($qc_name == '(............................... )' && !empty($grinding_inputs ?? [])) {
                        foreach ($grinding_inputs as $i) {
                            if (!empty($i->qc_name)) {
                                $qc_name = htmlspecialchars($i->qc_name);
                                break;
                            }
                        }
                    }
                }

                $supervisor_name = '(............................... )';
                if (!empty($header->confirmer_name)) {
                    $supervisor_name = htmlspecialchars($header->confirmer_name);
                } elseif (!empty($header->approved_by)) {
                    $supervisor_name = htmlspecialchars($header->approved_by);
                }
            ?>
            <table class="signature-table">
                <tr>
                    <td class="label-cell">Operator<br>(Production)</td>
                    <td class="label-cell">Quality Control<br>(QC)</td>
                    <td class="label-cell">Approved By<br>(Supervisor)</td>
                </tr>
                <tr>
                    <td class="sign-cell"><?= $operator_name; ?></td>
                    <td class="sign-cell"><?= $qc_name; ?></td>
                    <td class="sign-cell"><?= $supervisor_name; ?></td>
                </tr>
            </table>
        </div>

        <footer>
            Generated: <?= date('d M Y H:i'); ?> | Page 1
        </footer>
    </div>
</body>
</html>
            <div class="box">
                <div class="row"><div class="label">Work Order</div><div class="value"><?= htmlspecialchars($header->wo_number ?? '-'); ?></div></div>
                <div class="row"><div class="label">Finished Good</div><div class="value"><?= htmlspecialchars($header->fg_name ?? '-') . (isset($header->fg_code) ? ' (' . htmlspecialchars($header->fg_code) . ')' : ''); ?></div></div>
                <div class="row"><div class="label">Qty</div><div class="value"><?= number_format($header->planned_qty ?? 0, 2); ?> <?= htmlspecialchars($header->fg_uom ?? ''); ?></div></div>
            </div>
            <div class="box">
                <div class="row"><div class="label">Sales Order Number</div><div class="value"><?= htmlspecialchars($header->sc_number ?? '-'); ?></div></div>
                <div class="row"><div class="label">Status</div><div class="value" style="color:<?= $header->status === 'Completed' ? '#28a745' : '#ff9800'; ?>"><?= htmlspecialchars($header->status ?? '-'); ?></div></div>
                <div class="row"><div class="label">Remarks</div><div class="value"><?= htmlspecialchars($header->remarks ?? '-'); ?></div></div>
            </div>
        </section>

        <?php
            $total_input_qty = 0.0; foreach ($grinding_inputs ?? [] as $r){ $total_input_qty += (float)($r->qty_used ?? 0); }
            $total_output_qty = 0.0; $total_scrap_qty = 0.0; foreach ($grinding_outputs ?? [] as $r){ 
                if ($r->item_type == 'semifinished') $total_output_qty += (float)($r->qty_produced ?? 0); 
                if ($r->item_type == 'scrap') {
                    // For scrap items, qty is stored in qty_produced, not qty_scrap (qty_scrap is always 0 by default)
                    $qty = (float)($r->qty_produced ?? 0);
                    $total_scrap_qty += $qty;
                }
            }
        ?>

        <h3 class="section">A. INPUT USAGE</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">#</th>
                    <th class="name">Item</th>
                    <th class="center">Batch</th>
                    <th class="right">Qty</th>
                    <th class="center">UoM</th>
                    <th class="center">Source</th>
                    <th class="name">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($grinding_inputs)): $i=1; foreach($grinding_inputs as $in): ?>
                <tr>
                    <td class="no"><?= $i++; ?></td>
                    <td class="name"><strong><?= htmlspecialchars($in->item_name ?? '-'); ?></strong><?php if(isset($in->item_code)) echo ' (' . htmlspecialchars($in->item_code) . ')'; ?></td>
                    <td class="center"><?php if (!empty($in->source_output_id)) echo '#'.htmlspecialchars($in->source_output_id); elseif (!empty($in->batch_number)) echo htmlspecialchars($in->batch_number); else echo '-'; ?></td>
                    <td class="right"><strong><?= number_format($in->qty_used ?? 0, 2); ?></strong></td>
                    <td class="center"><?= htmlspecialchars($in->uom_code ?? ''); ?></td>
                    <td class="center"><?php if(!empty($in->source_output_id)) echo 'Sewing'; elseif(!empty($in->mr_detail_id)) echo 'MR'; else echo '-'; ?></td>
                    <td class="name"><small><?= htmlspecialchars($in->notes ?? ''); ?></small></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" class="center" style="color:var(--muted); padding:10px; font-style:italic;">No input usage recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="stat"><div class="num"><?= number_format($total_input_qty,2); ?></div><div class="label">Total Input</div></div>
        </div>

        <h3 class="section">B. OUTPUTS</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">#</th>
                    <th class="name">Item Result</th>
                    <th class="right">Qty</th>
                    <th class="center">UoM</th>
                    <th class="name">By</th>
                    <th class="name">QC</th>
                </tr>
            </thead>
            <tbody>
                <?php $j=1; $has_output=false; foreach($grinding_outputs ?? [] as $out): if ($out->item_type == 'semifinished'): $has_output=true; ?>
                <tr>
                    <td class="no"><?= $j++; ?></td>
                    <td class="name"><strong><?= htmlspecialchars($out->item_name ?? '-'); ?></strong><?php if(isset($out->item_code)) echo ' (' . htmlspecialchars($out->item_code) . ')'; ?></td>
                    <td class="right"><strong><?= number_format($out->qty_produced ?? 0, 2); ?></strong></td>
                    <td class="center"><?= htmlspecialchars($out->uom_code ?? ''); ?></td>
                    <td class="name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                    <td class="name"><?= !empty($out->qc_name) ? htmlspecialchars($out->qc_name) : '-'; ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_output): ?><tr><td colspan="6" class="center" style="color:var(--muted); padding:10px; font-style:italic;">No outputs recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="stat"><div class="num"><?= number_format($total_output_qty,2); ?></div><div class="label">Finished</div></div>
            <div class="stat"><div class="num" style="color:#dc3545;"><?= number_format($total_scrap_qty,2); ?></div><div class="label">Scrap</div></div>
        </div>

        <h3 class="section">C. SCRAP</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">#</th>
                    <th class="name">Scrap Item</th>
                    <th class="right">Qty</th>
                    <th class="center">UoM</th>
                    <th class="name">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php $k=1; $has_scrap=false; foreach($grinding_outputs ?? [] as $out): if ($out->item_type == 'scrap'): $has_scrap=true; ?>
                <tr>
                    <td class="no"><?= $k++; ?></td>
                    <td class="name"><strong><?= htmlspecialchars($out->item_name ?? '-'); ?></strong><?php if(isset($out->item_code)) echo ' (' . htmlspecialchars($out->item_code) . ')'; ?></td>
                    <td class="right"><strong><?= number_format($out->qty_produced ?? 0, 2); ?></strong></td>
                    <td class="center"><?= htmlspecialchars($out->uom_code ?? ''); ?></td>
                    <td class="name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_scrap): ?><tr><td colspan="5" class="center" style="color:var(--muted); padding:10px; font-style:italic;">No scrap recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>

            <div class="sign">
                    <table class="signature-table">
                        <?php
                            // Determine operator name from multiple possible sources: prefer header->creator_name (user who created the record)
                            $operator_candidates = array();
                            // header-level common fields (prioritize creator_name)
                            foreach (array('creator_name','ppic_name','produksi_name','created_by_name','requester_name') as $f) {
                                if (!empty($header->{$f})) $operator_candidates[] = $header->{$f};
                            }
                            // first input receiver/creator
                            if (!empty($grinding_inputs ?? []) && !empty($grinding_inputs[0])) {
                                $in0 = $grinding_inputs[0];
                                if (!empty($in0->receiver_name)) $operator_candidates[] = $in0->receiver_name;
                                if (!empty($in0->created_by_name)) $operator_candidates[] = $in0->created_by_name;
                            }
                            // first output receiver
                            if (!empty($grinding_outputs ?? []) && !empty($grinding_outputs[0])) {
                                $out0 = $grinding_outputs[0];
                                if (!empty($out0->receiver_name)) $operator_candidates[] = $out0->receiver_name;
                                if (!empty($out0->created_by_name)) $operator_candidates[] = $out0->created_by_name;
                            }
                            $operator_name = '(............................... )';
                            foreach ($operator_candidates as $c) { if (!empty($c)) { $operator_name = htmlspecialchars($c); break; } }

                            // Determine QC name: header-level or from outputs (prefer explicit qc_name)
                            $qc_name = '(............................... )';
                            if (!empty($header->qc_name)) { $qc_name = htmlspecialchars($header->qc_name); }
                            else {
                                if (!empty($grinding_outputs ?? [])) {
                                    foreach ($grinding_outputs as $o) { if (!empty($o->qc_name)) { $qc_name = htmlspecialchars($o->qc_name); break; } }
                                }
                                if ($qc_name == '(............................... )' && !empty($grinding_inputs ?? [])) {
                                    foreach ($grinding_inputs as $i) { if (!empty($i->qc_name)) { $qc_name = htmlspecialchars($i->qc_name); break; } }
                                }
                            }

                            // Supervisor / approver (prefer confirmer_name)
                            $supervisor_name = '(............................... )';
                            if (!empty($header->confirmer_name)) $supervisor_name = htmlspecialchars($header->confirmer_name);
                            elseif (!empty($header->approved_by)) $supervisor_name = htmlspecialchars($header->approved_by);
                        ?>
                        <tr>
                            <td class="label">Operator<br>(Production)</td>
                            <td class="label">Quality Control<br>(QC)</td>
                            <td class="label">Approved By<br>(Supervisor)</td>
                        </tr>
                        <tr>
                            <td class="name"><?= $operator_name; ?></td>
                            <td class="name"><?= $qc_name; ?></td>
                            <td class="name"><?= $supervisor_name; ?></td>
                        </tr>
                    </table>
            </div>

        <footer>
            <span>Generated: <?= date('d M Y H:i'); ?></span> &nbsp;|&nbsp; <span>Page <span class="page-number"></span></span>
        </footer>
    </div>
</body>
</html>
