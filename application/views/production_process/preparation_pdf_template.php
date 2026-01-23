<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Preparation Report - <?= htmlspecialchars($header->production_number ?? 'N/A'); ?></title>
    <style>
        :root{ --brand:#1d3557; --muted:#6c757d; --accent:#457b9d; --border:#e9ecef; }
        html,body{ margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; color:#222; }
        .page{ padding:24px 28px; font-size:11px; }
        header.top{ display:flex; align-items:center; justify-content:space-between; border-bottom:3px solid var(--accent); padding-bottom:12px; margin-bottom:18px; }
        .company{ display:flex; align-items:center; gap:12px; }
        .company img{ height:48px; object-fit:contain; }
        .company .name{ font-size:16px; font-weight:700; color:var(--brand); }
        .doc-title{ text-align:right; }
        .doc-title h2{ margin:0; font-size:14px; color:var(--brand); }
        .meta{ display:flex; gap:12px; margin-top:6px; justify-content:flex-end; }
        .meta .small{ color:var(--muted); font-size:10px; }

        .info{ display:flex; gap:18px; margin:18px 0 22px; }
        .info .box{ flex:1; background:#fff; border:1px solid var(--border); padding:10px 12px; border-radius:4px; }
        .info .row{ display:flex; justify-content:space-between; padding:4px 0; }
        .info .label{ color:var(--muted); font-weight:600; width:140px; }
        .info .value{ font-weight:600; color:#111; text-align:right; }

        h3.section{ margin:18px 0 8px; font-size:13px; color:var(--brand); border-bottom:1px solid var(--border); padding-bottom:6px; }
        table.grid{ width:100%; border-collapse:collapse; margin-bottom:14px; }
        table.grid th, table.grid td{ padding:8px 10px; border-bottom:1px solid var(--border); vertical-align:middle; }
        table.grid thead th{ background:#fbfbfc; font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:0.6px; }
        table.grid tbody tr:nth-child(even) td{ background:#fff; }
        .no{ width:36px; text-align:center; }
        .name{ text-align:left; }
        .right{ text-align:right; }
        .center{ text-align:center; }

        .summary{ display:flex; gap:12px; justify-content:flex-end; margin-top:6px; }
        .summary .stat{ background:#fff; border:1px solid var(--border); padding:8px 12px; border-radius:4px; min-width:140px; }
        .stat .num{ font-weight:700; font-size:13px; color:var(--brand); }
        .stat .label{ font-size:11px; color:var(--muted); }

    /* Signature area: MR-style 1 row 3 cols */
    .signature-table { margin-top: 18px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    .signature-table td { border: 1px solid #999; text-align: center; vertical-align: top; padding: 8px; height: 80px; }
    .signature-table td.label { font-size: 11px; font-weight: 700; background-color: #f2f2f2; }
    .signature-table td.name { font-size: 11px; padding-top: 40px; }

        footer{ position:fixed; bottom:16px; left:0; right:0; font-size:10px; color:var(--muted); text-align:center; }
        .page-number:after { content: counter(page); }

        /* Avoid breaking table rows */
        thead { display: table-header-group }
        tfoot { display: table-row-group }
        tr { page-break-inside: avoid }
    </style>
</head>
<body>
    <div class="page">
        <?php
        // Prefer web-accessible absolute URL for Dompdf (requires isRemoteEnabled = true)
        if (empty($company_logo)) {
            if (function_exists('base_url')) {
                $company_logo = base_url('assets/img/logo_full_biru.png');
            } else {
                // Fallback to local filesystem path (file:///) for environments where base_url() is not available
                $fsLogo = 'C:\\xampp\\htdocs\\neokaber\\assets\\img\\logo_full_biru.png';
                if (file_exists($fsLogo)) {
                    $company_logo = 'file:///' . str_replace('\\', '/', $fsLogo);
                } else {
                    $company_logo = '';
                }
            }
        }
        ?>
        <header class="top">
            <div class="company">
                <?php if (!empty($company_logo)): ?>
                    <img src="<?= htmlspecialchars($company_logo); ?>" alt="logo">
                <?php endif; ?>

            </div>
            <div class="doc-title">
                <h2>Preparation Report</h2>
                <div class="meta">
                    <div class="small">No: <strong><?= htmlspecialchars($header->production_number ?? '-'); ?></strong></div>
                    <div class="small">Date: <strong><?= date('d M Y', strtotime($header->production_date ?? date('Y-m-d'))); ?></strong></div>
                </div>
            </div>
        </header>

        <section class="info">
            <div class="box">
                <div class="row"><div class="label">Work Order</div><div class="value"><?= htmlspecialchars($header->wo_number ?? '-'); ?></div></div>
                <div class="row"><div class="label">Finished Good</div><div class="value"><?= htmlspecialchars($header->fg_name ?? '-') . (isset($header->fg_code) ? ' (' . htmlspecialchars($header->fg_code) . ')' : ''); ?></div></div>
                <div class="row"><div class="label">Planned Qty</div><div class="value"><?= number_format($header->planned_qty ?? 0, 4); ?> <?= htmlspecialchars($header->fg_uom ?? ''); ?></div></div>
            </div>
            <div class="box">
                <div class="row"><div class="label">Sales Contract</div><div class="value"><?= htmlspecialchars($header->sc_number ?? '-'); ?></div></div>
                <div class="row"><div class="label">Status</div><div class="value"><?= htmlspecialchars($header->status ?? '-'); ?></div></div>
                <div class="row"><div class="label">Remarks</div><div class="value" style="font-weight:400; width:60%; text-align:right;"><?= nl2br(htmlspecialchars($header->remarks ?? '-')); ?></div></div>
            </div>
        </section>

        <?php
            $total_input_qty = 0.0; foreach ($preparation_inputs ?? [] as $r){ $total_input_qty += (float)($r->qty_used ?? 0); }
            $total_output_qty = 0.0; $total_scrap_qty = 0.0; foreach ($preparation_outputs ?? [] as $r){ if ($r->item_type == 'semifinished') $total_output_qty += (float)($r->qty_produced ?? 0); if ($r->item_type == 'scrap') $total_scrap_qty += (float)($r->qty_scrap ?? 0); }
        ?>

        <h3 class="section">A. Input Usage</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="name">Item</th>
                    <th class="center">Batch / Ref</th>
                    <th class="right">Qty Used</th>
                    <th class="center">UoM</th>
                    <th class="center">Source</th>
                    <th class="name">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($preparation_inputs)): $i=1; foreach($preparation_inputs as $in): ?>
                <tr>
                    <td class="no"><?= $i++; ?></td>
                    <td class="name"><?= htmlspecialchars($in->item_name ?? '-'); ?> <?= isset($in->item_code) ? ' (' . htmlspecialchars($in->item_code) . ')' : ''; ?></td>
                    <td class="center"><?php if (!empty($in->source_output_id)) echo '#'.htmlspecialchars($in->source_output_id); elseif (!empty($in->batch_number)) echo htmlspecialchars($in->batch_number); else echo '-'; ?></td>
                    <td class="right"><?= number_format($in->qty_used ?? 0, 4); ?></td>
                    <td class="center"><?= htmlspecialchars($in->uom_code ?? ''); ?></td>
                    <td class="center"><?php if(!empty($in->source_output_id)) echo 'Sewing'; elseif(!empty($in->mr_detail_id)) echo 'MR'; else echo '-'; ?></td>
                    <td class="name"><?= htmlspecialchars($in->notes ?? ''); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" class="center" style="color:var(--muted); padding:18px;">No input usage recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="stat"><div class="num"><?= number_format($total_input_qty,4); ?></div><div class="label">Total Input Qty</div></div>
        </div>

        <h3 class="section">B. Outputs</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="name">Item Result</th>
                    <th class="right">Qty</th>
                    <th class="center">UoM</th>
                    <th class="name">Produced By</th>
                    <th class="name">QC</th>
                </tr>
            </thead>
            <tbody>
                <?php $j=1; $has_output=false; foreach($preparation_outputs ?? [] as $out): if ($out->item_type == 'semifinished'): $has_output=true; ?>
                <tr>
                    <td class="no"><?= $j++; ?></td>
                    <td class="name"><?= htmlspecialchars($out->item_name ?? '-'); ?> <?= isset($out->item_code) ? ' (' . htmlspecialchars($out->item_code) . ')' : ''; ?></td>
                    <td class="right"><?= number_format($out->qty_produced ?? 0, 4); ?></td>
                    <td class="center"><?= htmlspecialchars($out->uom_code ?? ''); ?></td>
                    <td class="name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                    <td class="name"><?= !empty($out->qc_name) ? htmlspecialchars($out->qc_name) : '-'; ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_output): ?><tr><td colspan="6" class="center" style="color:var(--muted); padding:18px;">No finished outputs recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <div class="summary">
            <div class="stat"><div class="num"><?= number_format($total_output_qty,4); ?></div><div class="label">Total Finished Qty</div></div>
            <div class="stat"><div class="num" style="color:#c92a2a;"><?= number_format($total_scrap_qty,4); ?></div><div class="label">Total Scrap Qty</div></div>
        </div>

        <h3 class="section">C. Scrap</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="name">Scrap Item</th>
                    <th class="right">Qty</th>
                    <th class="center">UoM</th>
                    <th class="name">Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php $k=1; $has_scrap=false; foreach($preparation_outputs ?? [] as $out): if ($out->item_type == 'scrap'): $has_scrap=true; ?>
                <tr>
                    <td class="no"><?= $k++; ?></td>
                    <td class="name"><?= htmlspecialchars($out->item_name ?? '-'); ?> <?= isset($out->item_code) ? ' (' . htmlspecialchars($out->item_code) . ')' : ''; ?></td>
                    <td class="right"><?= number_format($out->qty_scrap ?? 0, 4); ?></td>
                    <td class="center"><?= htmlspecialchars($out->uom_code ?? ''); ?></td>
                    <td class="name"><?= htmlspecialchars($out->receiver_name ?? '-'); ?></td>
                </tr>
                <?php endif; endforeach; if (!$has_scrap): ?><tr><td colspan="5" class="center" style="color:var(--muted); padding:18px;">No scrap recorded.</td></tr><?php endif; ?>
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
                            if (!empty($preparation_inputs ?? []) && !empty($preparation_inputs[0])) {
                                $in0 = $preparation_inputs[0];
                                if (!empty($in0->receiver_name)) $operator_candidates[] = $in0->receiver_name;
                                if (!empty($in0->created_by_name)) $operator_candidates[] = $in0->created_by_name;
                            }
                            // first output receiver
                            if (!empty($preparation_outputs ?? []) && !empty($preparation_outputs[0])) {
                                $out0 = $preparation_outputs[0];
                                if (!empty($out0->receiver_name)) $operator_candidates[] = $out0->receiver_name;
                                if (!empty($out0->created_by_name)) $operator_candidates[] = $out0->created_by_name;
                            }
                            $operator_name = '(....................... )';
                            foreach ($operator_candidates as $c) { if (!empty($c)) { $operator_name = htmlspecialchars($c); break; } }

                            // Determine QC name: header-level or from outputs (prefer explicit qc_name)
                            $qc_name = '-';
                            if (!empty($header->qc_name)) { $qc_name = htmlspecialchars($header->qc_name); }
                            else {
                                if (!empty($preparation_outputs ?? [])) {
                                    foreach ($preparation_outputs as $o) { if (!empty($o->qc_name)) { $qc_name = htmlspecialchars($o->qc_name); break; } }
                                }
                                if ($qc_name == '-' && !empty($preparation_inputs ?? [])) {
                                    foreach ($preparation_inputs as $i) { if (!empty($i->qc_name)) { $qc_name = htmlspecialchars($i->qc_name); break; } }
                                }
                            }

                            // Supervisor / approver (prefer confirmer_name)
                            $supervisor_name = '(....................... )';
                            if (!empty($header->confirmer_name)) $supervisor_name = htmlspecialchars($header->confirmer_name);
                            elseif (!empty($header->approved_by)) $supervisor_name = htmlspecialchars($header->approved_by);
                        ?>
                        <tr>
                            <td class="label">Operator<br><small>(Input)</small></td>
                            <td class="label">Quality Control<br><small>(QC)</small></td>
                            <td class="label">Disetujui Oleh<br><small>(SPV)</small></td>
                        </tr>
                        <tr>
                            <td class="name"><?= $operator_name; ?></td>
                            <td class="name"><?= $qc_name === '-' ? '(....................... )' : $qc_name; ?></td>
                            <td class="name"><?= $supervisor_name; ?></td>
                        </tr>
                    </table>
            </div>

        <footer>
            Generated: <?= date('d M Y H:i'); ?> &nbsp;|&nbsp; Page <span class="page-number"></span>
        </footer>
    </div>
</body>
</html>
