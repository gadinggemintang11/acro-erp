<?php
    $grand_total = 0;
    foreach ($details as $d) $grand_total += ((float)$d->qty * (float)$d->unit_price);

    // Nomor ditampilkan utuh (format Acropoly, config/doc_number.php) -- tidak lagi
    // dipecah jadi kode departemen + urut.

    // --- Setelan tata letak dari Developer Mode (2026-09-10) ---
    // Nilai bawaan identik dengan versi hardcode sebelumnya, jadi tanpa
    // override cetakannya tidak berubah. Lihat helpers/pdf_layout_helper.php.
    $L  = function_exists('pdf_layout') ? pdf_layout('erf') : [];
    $fb = (float) ($L['font_body']  ?? 11);
    $ft = (float) ($L['font_title'] ?? 14);
    $fi = (float) ($L['font_table'] ?? 10);
    $cp = (float) ($L['cell_pad']   ?? 4);
    $sh = (float) ($L['sig_height'] ?? 110);

    $date_yy = date('Y', strtotime($header->erf_date));
    $date_mm = date('m', strtotime($header->erf_date));
    $date_dd = date('d', strtotime($header->erf_date));

    // Prefer kode departemen (mis. "ADM") yang di-resolve controller dari common_code.
    // Fallback ke name lalu ke raw value supaya field tidak pernah kosong.
    if (!isset($department_code) || $department_code === '' || $department_code === null) {
        $department_code = !empty($department_name) ? $department_name : (!empty($header->department_id) ? $header->department_id : '');
    }

    $min_rows = 8;

    // Lebar kolom tabel rincian (Developer Mode 2026-09-14). Nilai user
    // diperlakukan sbg PERBANDINGAN lalu dinormalkan ke 100% di sini.
    $cw = [
        (float) ($L['col_no']       ?? 5),
        (float) ($L['col_desc']     ?? 35),
        (float) ($L['col_date']     ?? 10),
        (float) ($L['col_category'] ?? 13),
        (float) ($L['col_price']    ?? 16),
        (float) ($L['col_amount']   ?? 16),
    ];
    $cw_sum = array_sum($cw);
    if ($cw_sum <= 0) { $cw = [5, 35, 10, 13, 16, 16]; $cw_sum = 95; }
    $cwp = array_map(function ($w) use ($cw_sum) { return round($w * 100 / $cw_sum, 2); }, $cw);

    $ilw = (float) ($L['info_label_w'] ?? 115);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        <?php /* @page margin SENGAJA TIDAK dideklarasikan sama sekali di
                 sini (dicoba cuma set margin-left/right, ternyata Mpdf
                 reset margin-top/bottom ke default bawaan-nya sendiri,
                 bikin SetHTMLHeader()/SetHTMLFooter() dari controller jadi
                 tidak kebagian tempat & GAGAL tampil sama sekali). Semua 4
                 margin (termasuk left/right) dikontrol dari controller
                 (Erf.php) via Mpdf constructor margin_top/bottom/left/right
                 supaya sinkron dgn tinggi header/footer letterhead -- lihat
                 render_pdf_letterhead_header_margin() di document_pdf_helper.php. */ ?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: sun-extA, DejaVu Sans, Arial, sans-serif;
            font-size: <?= $fb ?>px;
            color: #000;
        }
        .erf-no { text-align: right; font-weight: bold; font-size: 13px; margin-bottom: 6px; }
        .erf-no .dept { color: #d00; }

        .title { text-align: center; font-weight: bold; font-size: <?= $ft ?>px; margin-top: 6px; }
        .subtitle { text-align: center; font-weight: bold; font-size: <?= $fb ?>px; margin-bottom: 6px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .info-table td { padding: 3px 2px; font-size: <?= $fb ?>px; vertical-align: middle; }
        .info-label { font-weight: bold; width: <?= $ilw ?>px; }
        .info-colon { width: 8px; font-weight: bold; }
        .info-line { border-bottom: 1px solid #000; }
        .date-box {
            display: inline-block;
            border: 1px solid #000;
            width: 26px;
            height: 16px;
            text-align: center;
            font-weight: bold;
            line-height: 16px;
            margin-right: 3px;
        }
        .date-cell-label { font-weight: bold; font-size: <?= $fb ?>px; padding: 0 8px 0 2px; }

        .dashed {
            border-top: 1px dashed #000;
            margin: 6px 0 4px 0;
            height: 0;
        }

        .items { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .items th, .items td {
            border: 1px solid #000;
            padding: <?= $cp ?>px <?= $cp + 2 ?>px;
            font-size: <?= $fi ?>px;
        }
        .items th {
            font-weight: bold;
            text-align: center;
            background: #fff;
            line-height: 1.25;
        }
        .items td.no { text-align: center; }
        .items td.desc { font-size: <?= $fi ?>px; padding: <?= $cp ?>px <?= $cp + 2 ?>px; }
        .items td.qty, .items td.price, .items td.subtotal { text-align: right; }
        .items tr.row-empty td { height: 20px; }
        .items tr.total-row td {
            font-weight: bold;
            border: 1px solid #000;
        }
        .items tr.total-row td.label {
            border: none;
            text-align: right;
            padding-right: 8px;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 15px;
        }
        .sig-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 9px;
            width: 33.33%;
        }
        .sig-header {
            background-color: #f5f5f5;
            font-weight: bold;
            line-height: 1.3;
        }
        .sig-space {
            height: <?= $sh ?>px;
            vertical-align: middle;
        }
        .sig-image {
            max-height: <?= max(20, $sh - 10) ?>px;
            max-width: 90%;
        }
        .sig-name {
            font-size: 9px;
            padding: 3px;
            min-height: 12px;
        }
        .sig-date {
            font-size: 9px;
            padding: 3px;
            text-align: left;
        }
    </style>
</head>
<body>
    <!-- Company header/footer letterhead sekarang REPEATING page
         header/footer, di-set dari controller via Mpdf::SetHTMLHeader()/
         SetHTMLFooter() (lihat Erf.php::export_pdf()) -- di sini cuma
         nomor dokumen (+ stempel payment kalau ada). -->
    <div class="erf-no">
        Reimburse Request :&nbsp;&nbsp;<?php echo htmlspecialchars($header->erf_number); ?>
        <?php if (!empty($payment_stamp_html)): ?>
            <div style="margin-top: 8px; text-align: right;"><?php echo $payment_stamp_html; ?></div>
        <?php endif; ?>
    </div>

    <div class="title">REIMBURSE REQUEST</div>
    <div class="subtitle">[費用報銷單]</div>

    
    <!-- Date / Department / Name -->
    <table class="info-table">
        <tr>
            <td class="info-label">DATE [日期]</td>
            <td class="info-colon">:</td>
            <td>
                <span class="date-box"><?php echo $date_yy; ?></span>
                <span class="date-cell-label">YY [年]</span>
                <span class="date-box"><?php echo $date_mm; ?></span>
                <span class="date-cell-label">MM [月]</span>
                <span class="date-box"><?php echo $date_dd; ?></span>
                <span class="date-cell-label">DD [日]</span>
            </td>
        </tr>
        <tr>
            <td class="info-label">DEPARTMENT [部門]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($department_code); ?></td>
        </tr>
        <tr>
            <td class="info-label">NAME [姓名]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($header->requester_name); ?></td>
        </tr>
        <tr>
            <td class="info-label">PENERIMA [收款人]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($header->karyawan_name ?? ''); ?></td>
        </tr>
        <tr>
            <td class="info-label">BANK [銀行]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($header->bank_name ?? ''); ?></td>
        </tr>
        <tr>
            <td class="info-label">ACCOUNT [賬號]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($header->bank_account ?? ''); ?></td>
        </tr>
    </table>

    <div class="dashed"></div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: <?= $cwp[0] ?>%;">NO<br>[號]</th>
                <th style="width: <?= $cwp[1] ?>%;">DESCRIPTION<br>[描述]</th>
                <th style="width: <?= $cwp[2] ?>%;">EXPENSE DATE<br>[日期]</th>
                <th style="width: <?= $cwp[3] ?>%;">CATEGORY<br>[類別]</th>
                <th style="width: <?= $cwp[4] ?>%;">UNIT PRICE<br>[單價]</th>
                <th style="width: <?= $cwp[5] ?>%;">AMOUNT<br>[金額]</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; $row_count = 0; ?>
            <?php foreach ($details as $detail): ?>
                <tr>
                    <td class="no"><?php echo $no; ?></td>
                    <td class="desc"><?php echo htmlspecialchars($detail->description); ?></td>
                    <td><?php echo $detail->expense_date ? date('d M Y', strtotime($detail->expense_date)) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($detail->category ?: '-'); ?></td>
                    <td class="price">Rp <?php echo number_format($detail->unit_price, 2, '.', ','); ?></td>
                    <td class="subtotal">Rp <?php echo number_format((float)$detail->qty * (float)$detail->unit_price, 2, '.', ','); ?></td>
                </tr>
                <?php $no++; $row_count++; ?>
            <?php endforeach; ?>

            <?php for ($i = $row_count; $i < $min_rows; $i++): ?>
                <tr class="row-empty">
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            <?php endfor; ?>

            <tr class="total-row">
                <td colspan="4" class="label" style="border: none; text-align: right; padding-right: 8px;">GRAND TOTAL [縂計]</td>
                <td class="label" style="text-align: center;"></td>
                <td class="subtotal">Rp <?php echo number_format($grand_total, 2, '.', ','); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures (3 kolom, 2026-11-06): Pemohon, Disetujui (Head of Dept), Dibayar (otomatis dari Record Payment). -->
    <table class="sig-table">
        <tr>
            <td class="sig-header">Pemohon<br>[申請人]</td>
            <td class="sig-header">Disetujui<br>[核准人]</td>
            <td class="sig-header">Dibayar<br>[付款人]</td>
        </tr>
        <tr>
            <td class="sig-space">
                <?php echo $sig_requester['signature_html'] ?? ''; ?>
            </td>
            <td class="sig-space">
                <?php echo $sig_approved['signature_html'] ?? ''; ?>
            </td>
            <td class="sig-space">
                <?php echo $sig_paid['signature_html'] ?? ''; ?>
            </td>
        </tr>
        <tr>
            <td class="sig-name"><?php echo htmlspecialchars($sig_requester['name']); ?></td>
            <td class="sig-name"><?php echo htmlspecialchars($sig_approved['name'] ?? '-'); ?></td>
            <td class="sig-name"><?php echo htmlspecialchars($sig_paid['name'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td class="sig-date">Date: <?php echo !empty($sig_requester['date']) ? date('d/m/Y', strtotime($sig_requester['date'])) : '___/___/___'; ?></td>
            <td class="sig-date">Date: <?php echo !empty($sig_approved['date']) ? date('d/m/Y', strtotime($sig_approved['date'])) : '___/___/___'; ?></td>
            <td class="sig-date">Date: <?php echo !empty($sig_paid['date']) ? date('d/m/Y', strtotime($sig_paid['date'])) : '___/___/___'; ?></td>
        </tr>
    </table>
</body>
</html>
