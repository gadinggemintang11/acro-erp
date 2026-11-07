<?php
// Nomor ditampilkan utuh (format Acropoly, config/doc_number.php) -- tidak lagi
// dipecah jadi kode departemen + urut.

$date_yy = date('y', strtotime($prf->prf_date));
$date_mm = date('m', strtotime($prf->prf_date));
$date_dd = date('d', strtotime($prf->prf_date));

$paper_size  = isset($paper_size)  ? $paper_size  : 'A4';
$orientation = isset($orientation) ? $orientation : 'L';
$paper_class = 'paper-' . $paper_size . ' orient-' . $orientation;
// Dikurangi dari (6/8/10/14) -- header/footer letterhead (2026-09-06) makan
// margin tambahan tetap di tiap halaman (repeating), jadi baris kosong
// pengisi ini (cuma utk tampilan form, tidak esensial) perlu dikurangi biar
// dokumen dgn item sedikit tetap muat 1 halaman (dites nyata pakai PRF
// id 117 di DB, 2 item -- lihat [[payment_completed_stamp]]).
$min_rows    = ($paper_size === 'A5')
    ? (($orientation === 'L') ? 3 : 5)
    : (($orientation === 'L') ? 5 : 10);

// --- Setelan tata letak dari Developer Mode (2026-09-10) ---
// Semua angka di bawah punya nilai bawaan yang SAMA PERSIS dengan versi
// hardcode sebelumnya, jadi tanpa override apa pun cetakannya tidak berubah
// sedikit pun. Lihat helpers/pdf_layout_helper.php.
$L   = function_exists('pdf_layout') ? pdf_layout('prf') : [];
$fb  = (float) ($L['font_body']  ?? 11);   // isi
$ft  = (float) ($L['font_title'] ?? 14);   // judul
$fi  = (float) ($L['font_table'] ?? 10);   // tabel item
$cp  = (float) ($L['cell_pad']   ?? 4);    // padding sel
$sh  = (float) ($L['sig_height'] ?? 110);  // tinggi kotak ttd
// Varian A5 mengikuti pola asli template: 2px lebih kecil dari A4, biar
// penyesuaian user tetap terbawa ke kedua ukuran kertas.
$fb5 = max(5, $fb - 2); $ft5 = max(6, $ft - 2);
$fi5 = max(4, $fi - 2); $cp5 = max(0, $cp - 2);
// 0 = ikut bawaan per ukuran kertas (perilaku lama dipertahankan).
if (!empty($L['min_rows'])) { $min_rows = (int) $L['min_rows']; }

// Lebar kolom tabel rincian (Developer Mode 2026-09-14). Nilai dari user
// diperlakukan sbg PERBANDINGAN lalu dinormalkan ke 100% di sini, jadi tiap
// slider bisa digeser sendiri tanpa user harus menjaga totalnya tetap 100.
$cw = [
    (float) ($L['col_no']       ?? 6),
    (float) ($L['col_desc']     ?? 42),
    (float) ($L['col_qty']      ?? 9),
    (float) ($L['col_unit']     ?? 9),
    (float) ($L['col_price']    ?? 17),
    (float) ($L['col_subtotal'] ?? 17),
];
$cw_sum = array_sum($cw);
if ($cw_sum <= 0) { $cw = [6, 42, 9, 9, 17, 17]; $cw_sum = 100; }
$cwp = array_map(function ($w) use ($cw_sum) { return round($w * 100 / $cw_sum, 2); }, $cw);

// Lebar kolom label di tabel keterangan. Selisih varian kertas dipertahankan
// persis seperti versi hardcode-nya (A5 -20, Portrait -15, A5+Portrait -35).
$ilw   = (float) ($L['info_label_w'] ?? 115);
$ilw5  = max(50, $ilw - 20);
$ilwP  = max(50, $ilw - 15);
$ilw5P = max(50, $ilw - 35);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        <?php /* @page margin SENGAJA TIDAK dideklarasikan sama sekali di
                 sini -- lihat catatan panjang di erf/pdf_template.php +
                 Prf.php::export_pdf(): rule @page apa pun (walau cuma
                 left/right) bikin Mpdf reset margin_top/bottom ke default
                 bawaan, gagalin SetHTMLHeader()/SetHTMLFooter() tampil.
                 Semua 4 margin dikontrol dari controller via Mpdf
                 constructor, per varian kertas (A4/A5, Landscape/Portrait). */ ?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: sun-extA, DejaVu Sans, Arial, sans-serif;
            font-size: <?= $fb ?>px;
            color: #000;
        }
        .prf-no { text-align: right; font-weight: bold; font-size: 13px; margin-bottom: 6px; }
        .prf-no .dept { color: #d00; }

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
        .items td.unit { text-align: center; }
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

        /* ===== A5 paper (compact) ===== */
        .paper-A5 { font-size: <?= $fb5 ?>px; }
        .paper-A5 .prf-no { font-size: 11px; }
        .paper-A5 .title { font-size: <?= $ft5 ?>px; margin-top: 4px; }
        .paper-A5 .subtitle { font-size: <?= $fb5 ?>px; margin-bottom: 4px; }
        .paper-A5 .info-table td { font-size: <?= $fb5 ?>px; padding: 2px; }
        .paper-A5 .info-label { width: <?= $ilw5 ?>px; }
        .paper-A5 .date-box { width: 22px; height: 14px; line-height: 14px; margin-right: 2px; }
        .paper-A5 .date-cell-label { font-size: <?= $fb5 ?>px; padding: 0 6px 0 2px; }
        .paper-A5 .dashed { margin: 4px 0 3px 0; }
        .paper-A5 .items th, .paper-A5 .items td { font-size: <?= $fi5 ?>px; padding: <?= $cp5 ?>px <?= $cp5 + 2 ?>px; }
        .paper-A5 .items td.desc { font-size: <?= $fi5 ?>px; padding: <?= $cp5 ?>px <?= $cp5 + 2 ?>px; }
        .paper-A5 .items tr.row-empty td { height: 14px; }
        .paper-A5 .sig-table { margin-top: 8px; }
        .paper-A5 .sig-table td { font-size: 7px; padding: 2px; }
        .paper-A5 .sig-header { line-height: 1.2; }
        .paper-A5 .sig-space { height: 70px; }
        .paper-A5 .sig-image { max-height: 64px; }
        .paper-A5 .sig-name { font-size: 7px; padding: 2px; }
        .paper-A5 .sig-date { font-size: 7px; padding: 2px; }

        /* ===== Portrait: kertas lebih sempit, layout dipadatkan ===== */
        .orient-P .info-label { width: <?= $ilwP ?>px; }
        .orient-P .sig-table td { padding: 3px 2px; }
        .orient-P .sig-header { font-size: 8px; }
        .paper-A4.orient-P .items th, .paper-A4.orient-P .items td { padding: 4px 4px; }
        .paper-A5.orient-P { font-size: 8px; }
        .paper-A5.orient-P .company-name { font-size: 11px; }
        .paper-A5.orient-P .title { font-size: 11px; }
        .paper-A5.orient-P .info-label { width: <?= $ilw5P ?>px; }
        .paper-A5.orient-P .date-box { width: 18px; height: 13px; line-height: 13px; }
        .paper-A5.orient-P .date-cell-label { font-size: 8px; padding: 0 3px 0 1px; }
        .paper-A5.orient-P .items th, .paper-A5.orient-P .items td { font-size: 7px; padding: 2px 3px; }
        .paper-A5.orient-P .items tr.row-empty td { height: 12px; }
        .paper-A5.orient-P .sig-table td { font-size: 6px; padding: 2px 1px; }
        .paper-A5.orient-P .sig-space { height: 56px; }
        .paper-A5.orient-P .sig-image { max-height: 52px; }
    </style>
</head>
<body class="<?php echo $paper_class; ?>">
    <!-- Company header/footer letterhead sekarang REPEATING page
         header/footer, di-set dari controller via Mpdf::SetHTMLHeader()/
         SetHTMLFooter() (lihat Prf.php::export_pdf()) -- di sini cuma
         nomor dokumen (+ stempel payment kalau ada). -->
    <div class="prf-no">
        Payment Request :&nbsp;&nbsp;<?php echo htmlspecialchars($prf->prf_number); ?>
        <?php if (!empty($payment_stamp_html)): ?>
            <div style="margin-top: 8px; text-align: right;"><?php echo $payment_stamp_html; ?></div>
        <?php endif; ?>
    </div>

    <div class="title">PAYMENT REQUEST</div>
    <div class="subtitle">[付款申請表]</div>

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
            <td class="info-line"><?php echo htmlspecialchars(isset($department_code) ? $department_code : ($prf->department_id ?? '')); ?></td>
        </tr>
        <tr>
            <td class="info-label">NAME [姓名]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($prf->requester_name); ?></td>
        </tr>
        <tr>
            <td class="info-label">VENDOR [供應商]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($prf->supplier_name ?? ''); ?></td>
        </tr>
        <tr>
            <td class="info-label">BANK [銀行]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php echo htmlspecialchars($prf->bank_name ?? ''); ?></td>
        </tr>
        <tr>
            <td class="info-label">ACCOUNT [賬號]</td>
            <td class="info-colon">:</td>
            <td class="info-line"><?php
                // "no. rekening / atas nama". Pemilik rekening diambil dari Nama Akun
                // yang diisi di Master Supplier (disalin ke PRF saat dibuat).
                // Sebelum 2026-10-02 ini DITEBAK dari nama vendor -- sering keliru
                // karena rekening bisa atas nama pemilik usaha, bukan nama badan
                // usahanya. Kalau Nama Akun belum diisi, jatuh kembali ke nama vendor
                // supaya PRF lama tetap tercetak seperti sebelumnya.
                $acc_no    = trim((string) ($prf->bank_account ?? ''));
                $acc_owner = trim((string) ($prf->bank_account_name ?? ''));
                if ($acc_owner === '') $acc_owner = trim((string) ($prf->supplier_name ?? ''));
                $acc_line  = ($acc_no !== '' && $acc_owner !== '') ? $acc_no . ' / ' . $acc_owner : $acc_no;
                echo htmlspecialchars($acc_line);
            ?></td>
        </tr>
    </table>

    <div class="dashed"></div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: <?= $cwp[0] ?>%;">NO<br>[號]</th>
                <th style="width: <?= $cwp[1] ?>%;">DESCRIPTION<br>[描述]</th>
                <th style="width: <?= $cwp[2] ?>%;">QTY<br>[數量]</th>
                <th style="width: <?= $cwp[3] ?>%;">UNIT<br>[單位]</th>
                <th style="width: <?= $cwp[4] ?>%;">UNIT PRICE<br>[單價]</th>
                <th style="width: <?= $cwp[5] ?>%;">SUBTOTAL<br>[小計]</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; $row_count = 0; ?>
            <?php foreach ($details as $detail): ?>
                <tr>
                    <td class="no"><?php echo $no; ?></td>
                    <td class="desc"><?php echo htmlspecialchars($detail->description); ?></td>
                    <td class="qty"><?php echo number_format($detail->qty, 2, '.', ','); ?></td>
                    <td class="unit"><?php echo htmlspecialchars($detail->unit ?? '') ?: '-'; ?></td>
                    <td class="price">Rp <?php echo number_format($detail->unit_price, 2, '.', ','); ?></td>
                    <td class="subtotal">Rp <?php echo number_format($detail->subtotal, 2, '.', ','); ?></td>
                </tr>
                <?php $no++; $row_count++; ?>
            <?php endforeach; ?>

            <?php for ($i = $row_count; $i < $min_rows; $i++): ?>
                <tr class="row-empty">
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            <?php endfor; ?>

            <tr class="total-row">
                <td colspan="4" class="label" style="border: none;"></td>
                <td class="label" style="text-align: center;">TOTAL [総]</td>
                <td class="subtotal">Rp <?php echo number_format($prf->subtotal, 2, '.', ','); ?></td>
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
                <?php echo $requester_signature ?? ''; ?>
            </td>
            <td class="sig-space">
                <?php echo $approver_signature ?? ''; ?>
            </td>
            <td class="sig-space">
                <?php echo $payer_signature ?? ''; ?>
            </td>
        </tr>
        <tr>
            <td class="sig-name"><?php echo htmlspecialchars($requester_name); ?></td>
            <td class="sig-name"><?php echo htmlspecialchars($approver_name ?? '-'); ?></td>
            <td class="sig-name"><?php echo htmlspecialchars($payer_name ?? '-'); ?></td>
        </tr>
        <tr>
            <td class="sig-date">Date: <?php echo !empty($requester_date) ? date('d/m/Y', strtotime($requester_date)) : '___/___/___'; ?></td>
            <td class="sig-date">Date: <?php echo !empty($approver_date) ? date('d/m/Y', strtotime($approver_date)) : '___/___/___'; ?></td>
            <td class="sig-date">Date: <?php echo !empty($payer_date) ? date('d/m/Y', strtotime($payer_date)) : '___/___/___'; ?></td>
        </tr>
    </table>
</body>
</html>
