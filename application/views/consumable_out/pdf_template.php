<?php
// Kop surat + footer identitas perusahaan, REPEATING di tiap halaman.
// Pola sama persis dgn purchase_order/pdf_po_letterhead.php & irf/pdf_template.php
// (user 2026-09-14: "print pdf di consumable out dikasih header dan footer sama
// kaya di Purchase_order"). Dua div position:fixed di awal isi halaman +
// @page margin selebar tinggi kop/footer supaya isi halaman tidak ketiban.
$letterhead_icon_h = 50;
$letterhead_font   = 24;
$letterhead_header = render_pdf_letterhead_header_html($letterhead_icon_h, $letterhead_font);
$letterhead_footer = render_pdf_letterhead_footer_html();
$letterhead_mt     = render_pdf_letterhead_header_margin($letterhead_icon_h, $letterhead_font);
$letterhead_mb     = render_pdf_letterhead_footer_margin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Item Release - <?= htmlspecialchars($header->doc_number); ?></title>
    <style>
        @page { margin: <?= $letterhead_mt ?>px 30px <?= $letterhead_mb ?>px 30px; }
        body { font-family: Arial, sans-serif; font-size: 10pt; margin: 0; }
        .header-title { text-align: center; font-size: 15pt; font-weight: bold; margin-bottom: 4px; }
        .header-subtitle { text-align: center; font-size: 10pt; margin-bottom: 18px; color: #555; }
        .info-table { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; vertical-align: top; font-size: 9.5pt; }
        .info-table .label { font-weight: bold; width: 130px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #333; padding: 5px 6px; font-size: 9pt; }
        .items-table th { background-color: #4472C4; color: white; font-weight: bold; text-align: center; }
        .items-table td.center { text-align: center; }
        .items-table td.right  { text-align: right; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 40px; page-break-inside: avoid; }
        .signature-table td { border: 1px solid #333; padding: 8px; text-align: center; width: 35%; vertical-align: top; }
        .sig-label { font-weight: bold; font-size: 9pt; background-color: #f2f2f2; vertical-align: middle; height: 30px; }
        .sig-space { height: 80px; vertical-align: bottom; }
        .sig-name  { font-size: 9pt; font-weight: bold; margin-bottom: 15px; margin-top: 5px; }
        .footer-note { margin-top: 12px; font-size: 8pt; color: #666; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 8.5pt; }
        .badge-issued    { background: #28a745; color: #fff; }
        .badge-draft     { background: #ffc107; color: #333; }
        .badge-cancelled { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div style="position: fixed; top: -<?= $letterhead_mt ?>px; left: 0px; right: 0px;"><?= $letterhead_header ?></div>
    <div style="position: fixed; bottom: -<?= $letterhead_mb ?>px; left: 0px; right: 0px;"><?= $letterhead_footer ?></div>

    <div class="header-title">PENGELUARAN BARANG HABIS PAKAI</div>
    <div class="header-subtitle">Consumable Issuance &nbsp;|&nbsp; <?= htmlspecialchars($header->doc_number); ?></div>

    <table class="info-table">
        <tr>
            <td class="label">No. Dokumen</td>
            <td>: <?= htmlspecialchars($header->doc_number); ?></td>
            <td class="label">Tanggal</td>
            <td>: <?= date('d F Y', strtotime($header->issuance_date)); ?></td>
        </tr>
        <tr>
            <td class="label">Departemen</td>
            <td>: <?= htmlspecialchars($header->department ?? '-'); ?></td>
            <td class="label">Penerima</td>
            <td>: <?= htmlspecialchars($header->recipient ?? '-'); ?></td>
        </tr>
        <tr>
            <td class="label">Keperluan</td>
            <td colspan="3">: <?= htmlspecialchars($header->purpose ?? '-'); ?></td>
        </tr>
        <?php if (!empty($header->notes)): ?>
        <tr>
            <td class="label">Catatan</td>
            <td colspan="3">: <?= htmlspecialchars($header->notes); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="4%">No.</th>
                <th width="15%">Kode Barang</th>
                <th width="42%">Nama Barang</th>
                <th width="10%">Satuan</th>
                <th width="13%">Qty Keluar</th>
                <th width="16%">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($details)): $no = 1; foreach ($details as $d): ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td class="center"><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                <td><?= htmlspecialchars($d->item_name ?? '-'); ?></td>
                <td class="center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                <td class="right"><?= number_format($d->qty_issued, 4); ?></td>
                <td><?= htmlspecialchars($d->notes ?? '-'); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="center">Tidak ada item.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $confirmed_sig = '';
    $created_sig = '';

    if (!empty($header->confirmed_by_signature)) {
        $sig_path = FCPATH . $header->confirmed_by_signature;
        if (file_exists($sig_path)) {
            $confirmed_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto;" />';
        }
    }

    if (!empty($header->created_by_signature)) {
        $sig_path = FCPATH . $header->created_by_signature;
        if (file_exists($sig_path)) {
            $created_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto;" />';
        }
    }
    ?>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td class="sig-label">Diserahkan Oleh</td>
            <td class="sig-label">Diterima Oleh</td>
            <td class="sig-label">Disetujui Oleh</td>
        </tr>
        <tr>
            <td class="sig-space"><?= $confirmed_sig; ?></td>
            <td class="sig-space">&nbsp;</td>
            <td class="sig-space"><?= $created_sig; ?></td>
        </tr>
        <tr>
            <td>
                <div class="sig-name"><?= htmlspecialchars($header->confirmed_by_name ?? '.....................'); ?></div>
            </td>
            <td>
                <div class="sig-name"><?= htmlspecialchars($header->recipient ?? '.....................'); ?></div>
            </td>
            <td>
                <div class="sig-name">.....................   </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dicetak pada: <?= date('d F Y H:i'); ?> &nbsp;|&nbsp; Dokumen ini sah tanpa tanda tangan basah apabila dicetak dari sistem <?= htmlspecialchars(company_info('app_name')) ?>.
    </div>
</body>
</html>
