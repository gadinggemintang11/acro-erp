<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Permintaan Barang: <?= $header->pr_number ?></title>
    <style>
        @page { margin: 15px 20px; }
        body {
            font-family: sun-extA, DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        table { border-collapse: collapse; }

        .header-section {
            width: 100%;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-block {
            width: 60%;
            font-size: 9px;
            line-height: 1.3;
        }

        .company-block strong {
            font-size: 10px;
        }

        .title-block {
            width: 100%;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .subtitle {
            text-align: center;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .irf-section {
            display: flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }

        .irf-label {
            font-weight: bold;
            font-size: 9px;
            margin: 0;
        }

        .irf-box {
            border: 2px dashed #000;
            padding: 3px 6px;
            text-align: center;
            min-width: 100px;
        }

        .irf-box p {
            margin: 0;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        .clear { clear: both; }

        .divider {
            border-top: 3px double #000;
            margin: 8px 0;
            width: 100%;
        }

        .info-row {
            margin-bottom: 5px;
            font-size: 9px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }

        .info-value {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 300px;
            padding: 1px 3px;
        }

        .date-inputs {
            display: inline-block;
        }

        .date-box {
            border-bottom: 1px solid #000;
            width: 30px;
            padding: 1px 2px;
            text-align: center;
            display: inline-block;
            margin: 0 3px;
        }

        .purpose-section {
            margin: 8px 0;
        }

        .purpose-label {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 3px;
        }

        .purpose-box {
            border: 1px solid #000;
            padding: 4px;
            min-height: 35px;
            font-size: 9px;
        }

        .items-table {
            width: 100%;
            border: 1px solid #000;
            margin: 8px 0;
        }

        .items-table th {
            background-color: #f5f5f5;
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8px;
            height: 18px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .grand-total {
            text-align: right;
            font-weight: bold;
            font-size: 9px;
            margin: 6px 0;
        }

        .grand-total-value {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 120px;
            padding: 1px 3px;
            text-align: right;
        }

        .sig-table {
            width: 100%;
            border: 1px solid #000;
            margin-top: 10px;
        }

        .sig-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8px;
            height: auto;
        }

        .sig-header {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 4px;
            height: 16px;
        }

        .sig-space {
            min-height: 50px;
            vertical-align: bottom;
        }

        .sig-image {
            max-height: 40px;
            max-width: 90%;
        }

        .sig-name {
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 2px;
            margin-top: 3px;
        }

        .sig-date {
            font-size: 7px;
            padding-top: 2px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-section">
    <div class="company-block">
        <p><strong><?= htmlspecialchars(company_info('company_name')) ?></strong></p>
        <?php foreach (company_contact_lines() as $cl): ?>
        <p><?= htmlspecialchars($cl) ?></p>
        <?php endforeach; ?>
    </div>
    <div class="irf-section">
        <span class="irf-label">Permintaan Barang :</span>
        <div class="irf-box">
            <p>DEPT<?php echo str_pad($header->id, 6, '0', STR_PAD_LEFT); ?></p>
        </div>
    </div>
    <div class="clear"></div>
</div>

<div class="title-block">PERMINTAAN BARANG</div>
<div class="subtitle">领料单</div>

<div class="divider"></div>

<!-- DATE ROW -->
<div class="info-row">
    <span class="info-label">Date [日期] :</span>
    <span class="date-inputs">
        <span class="date-box"><?php echo date('y', strtotime($header->pr_date)); ?></span>
        <span style="font-size: 8px;">YY [年]</span>
        <span class="date-box"><?php echo date('m', strtotime($header->pr_date)); ?></span>
        <span style="font-size: 8px;">MM [月]</span>
        <span class="date-box"><?php echo date('d', strtotime($header->pr_date)); ?></span>
        <span style="font-size: 8px;">DD [日]</span>
    </span>
</div>

<!-- DEPARTMENT ROW -->
<div class="info-row">
    <span class="info-label">Department [部门] :</span>
    <span class="info-value"><?php echo htmlspecialchars($header->department ?? ''); ?></span>
</div>

<!-- PURPOSE SECTION -->
<div class="purpose-section">
    <div class="purpose-label">Purpose of Requisition [领料用途] :</div>
    <div class="purpose-box">
        <?php echo htmlspecialchars($header->purpose ?? ''); ?>
    </div>
</div>

<!-- ITEMS TABLE -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 12%;">Item Code</th>
            <th style="width: 33%;">Material Description [物品名称]</th>
            <th style="width: 15%;">Quantity [数量]</th>
            <th style="width: 15%;">Unit Price [报价]</th>
            <th style="width: 20%;">Amount [金额]</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($details)) {
            $no = 1;
            foreach ($details as $d) {
                echo '<tr>';
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($d->item_code ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($d->item_name ?? '') . '</td>';
                echo '<td class="text-right">' . (!empty($d->qty) ? number_format($d->qty, 2) : '') . '</td>';
                echo '<td class="text-right">-</td>';
                echo '<td class="text-right">-</td>';
                echo '</tr>';
            }
            $existing = count($details);
        } else {
            $existing = 0;
        }

        // Fill remaining rows (18 total)
        for ($i = $existing; $i < 18; $i++) {
            echo '<tr><td class="text-center"></td><td></td><td></td><td></td><td></td><td></td></tr>';
        }
        ?>
    </tbody>
</table>

<!-- GRAND TOTAL -->
<div class="grand-total">
    GRAND TOTAL [总计] : <span class="grand-total-value"></span>
</div>

<!-- SIGNATURES -->
<table class="sig-table">
    <tr>
        <td class="sig-header" style="width: 16.66%;">Applicant<br>[申请人]</td>
        <td class="sig-header" style="width: 16.66%;">Head of Dept.<br>[部门主管]</td>
        <td class="sig-header" style="width: 16.66%;">Endorsement<br>[认可人]</td>
        <td class="sig-header" style="width: 16.66%;">Approve by<br>[核准人]</td>
        <td class="sig-header" style="width: 16.66%;">Receiver<br>[收款人]</td>
        <td class="sig-header" style="width: 16.66%;">Prepared by<br>[编制人]</td>
    </tr>
    <tr>
        <td class="sig-space">
            <?php
            if (!empty($header->requester_signature)) {
                $sig_path = FCPATH . $header->requester_signature;
                if (file_exists($sig_path)) {
                    echo '<img src="' . $sig_path . '" class="sig-image">';
                }
            }
            ?>
        </td>
        <td class="sig-space"></td>
        <td class="sig-space"></td>
        <td class="sig-space">
            <?php
            if (!empty($header->approver_signature)) {
                $sig_path = FCPATH . $header->approver_signature;
                if (file_exists($sig_path)) {
                    echo '<img src="' . $sig_path . '" class="sig-image">';
                }
            }
            ?>
        </td>
        <td class="sig-space"></td>
        <td class="sig-space"></td>
    </tr>
    <tr>
        <td class="sig-name"><?php echo htmlspecialchars($header->requester_name ?? ''); ?></td>
        <td class="sig-name"></td>
        <td class="sig-name"></td>
        <td class="sig-name"><?php echo htmlspecialchars($header->approver_name ?? ''); ?></td>
        <td class="sig-name"></td>
        <td class="sig-name"></td>
    </tr>
    <tr>
        <td class="sig-date">Date: <?php echo date('m/d/Y', strtotime($header->pr_date)); ?></td>
        <td class="sig-date">Date: ___/___/___</td>
        <td class="sig-date">Date: ___/___/___</td>
        <td class="sig-date">Date: <?php echo date('m/d/Y', strtotime($header->pr_date)); ?></td>
        <td class="sig-date">Date: ___/___/___</td>
        <td class="sig-date">Date: ___/___/___</td>
    </tr>
</table>

</body>
</html>
