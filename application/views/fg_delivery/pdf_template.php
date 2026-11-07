<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/*
 * Delivery Note — bentuknya mengikuti contoh yang dikirim user
 * (assets/DeliveryOrder_DO-202606-0008.pdf), dengan satu perbedaan yang
 * diminta: TANPA kolom Harga CMP / Nilai CMP / Harga FOB / Nilai FOB.
 * Diganti satu pasang kolom saja -- Harga dan Total Harga
 * (user 2026-09-30: "gausa ada harga cmp dan harga fob, kasih aja harga dan
 * total harga jaa").
 *
 * Kolom SC pada contoh ditulis IPO di sini, mengikuti sebutan yang dipakai di
 * seluruh sistem.
 *
 * NW / GW sengaja tetap ada tapi KOSONG: tidak ada sumber datanya di sistem
 * (finished_goods maupun baris pengiriman tidak menyimpan berat). Di contoh
 * pun nilainya 0.00. Kolomnya dipertahankan supaya bisa ditulis tangan saat
 * barang ditimbang di pintu keluar.
 *
 * Harga diambil dari IPO (sales_contracts.unit_price); total = harga x qty.
 */
// Kop & kaki memakai letterhead yang SAMA PERSIS dengan Purchase Order
// (user 2026-10-01: "untuk header dan footer samakan dengan PO"): logo
// logo_full_biru.png + "PT. NEW SPECIALIFE INDUSTRIES" di atas, alamat &
// telepon di bawah, keduanya BERULANG di tiap halaman lewat position:fixed.
// Pola & angka marginnya sama dengan purchase_order/pdf_po_letterhead.php.
$letterhead_icon_h = 50;
$letterhead_font   = 24;
$letterhead_header = render_pdf_letterhead_header_html($letterhead_icon_h, $letterhead_font);
$letterhead_footer = render_pdf_letterhead_footer_html();
$letterhead_mt     = render_pdf_letterhead_header_margin($letterhead_icon_h, $letterhead_font);
$letterhead_mb     = render_pdf_letterhead_footer_margin();

// Identitas dari Developer Mode (user 2026-10-08) -- dulu salinan sendiri di
// sini, jadi ganti nama/alamat perusahaan harus disisir ke berkas ini juga.
$company_name  = company_info('company_name');
$company_addr  = company_address_lines();

$cur = '';
$grand_qty = 0.0;
$grand_val = 0.0;
$grand_nw  = 0.0;
$grand_gw  = 0.0;
foreach ($details as $d) {
    $qty   = (float) $d->qty_shipped;
    $price = (float) ($d->unit_price ?? 0);
    $grand_qty += $qty;
    $grand_val += $qty * $price;
    $grand_nw  += (float) ($d->nw ?? 0);
    $grand_gw  += (float) ($d->gw ?? 0);
    if ($cur === '' && !empty($d->currency_code)) $cur = $d->currency_code;
}
$nf = function ($n, $dec = 2) { return number_format((float) $n, $dec, '.', ','); };
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delivery Note <?= htmlspecialchars($header->delivery_number) ?></title>
    <style>
        @page { margin: <?= $letterhead_mt ?>px 30px <?= $letterhead_mb ?>px 30px; }
        body  { font-family: Arial, 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }
        .co-name  { font-size: 13pt; font-weight: bold; }
        .co-addr  { font-size: 8.5pt; line-height: 1.35; }
        .doc-title{ font-size: 14pt; font-weight: bold; text-align: center;
                    letter-spacing: 1px; margin: 14px 0 4px; }
        .meta td  { padding: 1px 0; font-size: 9pt; }
        .party    { font-size: 9pt; line-height: 1.35; }
        .party b  { display: block; margin-bottom: 2px; }
        table.items { width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { border: 1px solid #000; padding: 3px 4px; font-size: 8pt;
                                         word-wrap: break-word; overflow-wrap: break-word; }
        table.items th { background: #eee; text-align: center; font-weight: bold; }
        .num  { text-align: right; }
        .ctr  { text-align: center; }
        .total-row td { font-weight: bold; background: #f7f7f7; }
        .sign { width: 100%; margin-top: 26px; border-collapse: collapse; }
        .sign td { font-size: 8.5pt; text-align: center; vertical-align: bottom;
                   padding-top: 2px; width: 20%; }
        .sign .line { border-top: 1px solid #000; padding-top: 3px; }
        .sign .slot { height: 52px; }
    </style>
</head>
<body>

    <div style="position: fixed; top: -<?= $letterhead_mt ?>px; left: 0px; right: 0px;"><?= $letterhead_header ?></div>
    <div style="position: fixed; bottom: -<?= $letterhead_mb ?>px; left: 0px; right: 0px;"><?= $letterhead_footer ?></div>

    <div class="doc-title">DELIVERY NOTE</div>

    <table class="meta" width="100%">
        <tr>
            <td width="60">No.</td>
            <td>: <strong><?= htmlspecialchars($header->delivery_number) ?></strong></td>
        </tr>
        <tr>
            <td>Date</td>
            <td>: <?= htmlspecialchars($header->delivery_date) ?></td>
        </tr>
    </table>

    <?php /* ── Pengirim & penerima ── */ ?>
    <table width="100%" style="border-collapse:collapse; margin-top:8px;">
        <tr>
            <td width="50%" class="party" style="vertical-align:top; padding-right:10px;">
                <b>Shipper:</b>
                <?= htmlspecialchars($company_name) ?><br>
                <?php foreach ($company_addr as $l): ?>
                    <?= htmlspecialchars($l) ?><br>
                <?php endforeach; ?>
            </td>
            <td width="50%" class="party" style="vertical-align:top;">
                <b>Send to:</b>
                <?= htmlspecialchars($header->nama_customer ?: '-') ?><br>
                <?= nl2br(htmlspecialchars($header->customer_address ?: '-')) ?>
            </td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse; margin-top:8px;">
        <tr>
            <td width="50%" class="party" style="vertical-align:top;">
                <b>BC Type:</b>
                <?= htmlspecialchars($header->bc_type_desc ?: ($header->bc_type_id ?: '-')) ?>
            </td>
            <td width="50%" class="party" style="vertical-align:top;">
                <b>BC Number:</b>
                <?= htmlspecialchars($header->bc_number ?: '-') ?>
                <?= !empty($header->bc_date) ? ' (' . htmlspecialchars($header->bc_date) . ')' : '' ?>
            </td>
        </tr>
    </table>

    <?php /* ── Rincian barang ── */ ?>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:13%;">Sales Order</th>
                <th style="width:10%;">Kode</th>
                <th style="width:17%;">Nama Barang</th>
                <th style="width:10%;">QTY</th>
                <th style="width:10%;">NW</th>
                <th style="width:10%;">GW</th>
                <th style="width:9%;">Harga<?= $cur ? '<br>(' . htmlspecialchars($cur) . ')' : '' ?></th>
                <th style="width:16%;">Total Harga<?= $cur ? '<br>(' . htmlspecialchars($cur) . ')' : '' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?>
                <tr><td colspan="9" class="ctr">Tidak ada item.</td></tr>
            <?php else: $i = 1; foreach ($details as $d):
                $qty   = (float) $d->qty_shipped;
                $price = (float) ($d->unit_price ?? 0);
            ?>
            <tr>
                <td class="ctr"><?= $i++ ?></td>
                <td><?= htmlspecialchars($d->sc_number ?: '-') ?></td>
                <td><?= htmlspecialchars($d->kode_barang ?: '-') ?></td>
                <td><?= htmlspecialchars($d->nama_barang ?: '-') ?></td>
                <td class="num"><?= $nf($qty) ?></td>
                <td class="num"><?= isset($d->nw) && $d->nw !== null ? $nf($d->nw) : '' ?></td>
                <td class="num"><?= isset($d->gw) && $d->gw !== null ? $nf($d->gw) : '' ?></td>
                <td class="num"><?= $price > 0 ? $nf($price) : '' ?></td>
                <td class="num"><?= $price > 0 ? $nf($qty * $price) : '' ?></td>
            </tr>
            <?php endforeach; endif; ?>
            <tr class="total-row">
                <td colspan="4" class="ctr">TOTAL</td>
                <td class="num"><?= $nf($grand_qty) ?></td>
                <td class="num"><?= $grand_nw > 0 ? $nf($grand_nw) : '' ?></td>
                <td class="num"><?= $grand_gw > 0 ? $nf($grand_gw) : '' ?></td>
                <td class="num"></td>
                <td class="num"><?= $grand_val > 0 ? $nf($grand_val) : '' ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($header->remarks)): ?>
    <div style="margin-top:8px; font-size:8.5pt;">
        <b>Remarks:</b> <?= nl2br(htmlspecialchars($header->remarks)) ?>
    </div>
    <?php endif; ?>

    <?php /* ── Tanda tangan ── */ ?>
    <table class="sign">
        <tr>
            <td class="slot"></td><td class="slot"></td><td class="slot"></td>
            <td class="slot"></td><td class="slot"></td>
        </tr>
        <tr>
            <td class="line">Prepared by:</td>
            <td class="line">Warehouse Manager:</td>
            <td class="line">Exim SPV:</td>
            <td class="line">Security:</td>
            <td class="line">Driver:</td>
        </tr>
    </table>

</body>
</html>
