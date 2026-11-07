<?php
/**
 * PO PDF — letterhead resmi "Purchasing Order" (user 2026-09-05, minta cetak
 * PO mirip 100% dengan contoh dokumen resmi PT New Specialife Industries).
 * BEDA dari template universal application/views/irf/pdf_template.php (yang
 * dipakai IRF/PRF/ERF/MRF & sebelumnya PO juga) — surat resmi ke SUPPLIER
 * (issue to/attn/tel, harga USD+IDR, payment/delivery term, dual sign block),
 * bukan form approval internal. Dipanggil via render_po_letterhead_pdf()
 * (application/helpers/document_pdf_helper.php), dipakai SEMUA 4 varian PO
 * (base/general/jasa/asset) krn strukturnya sama persis.
 *
 * Expected variables (lihat pemanggil di controller Purchase_order*.php):
 *   po_number, po_date (raw date string), supplier_name, supplier_attn,
 *   supplier_tel, ref_text, buyer_name, items (array: item_code, item_name,
 *   qty, unit_price_usd|null, unit_price_idr, amount), show_usd_column,
 *   currency_code, subtotal_idr, has_ppn, ppn_rate, ppn_amount_idr,
 *   grand_total_idr (SUDAH termasuk PPN kalau has_ppn), payment_term,
 *   delivery_term, remarks, authorized_name, authorized_signature_html
 */
// Dari Developer Mode (user 2026-10-08). Dulu lewat lang_line
// 'po_letterhead_company_name' -- key itu tidak pernah terisi di berkas bahasa
// mana pun maupun di lang_overrides, jadi tidak ada kustomisasi yang hilang.
$company_name = company_info('company_name');

// Header/footer letterhead identitas perusahaan (logo_full_biru.png + teks
// "PT. NEW SPECIALIFE INDUSTRIES" + alamat) -- user 2026-09-06: ganti header
// logo+teks inline dgn header resmi + footer alamat, REPEATING di tiap
// halaman. Sama persis pola & fungsi yg dipakai irf/pdf_template.php (lihat
// komentar di sana + render_pdf_letterhead_header_margin() di
// document_pdf_helper.php).
$letterhead_icon_h  = 50;
$letterhead_font    = 24;
$letterhead_header  = render_pdf_letterhead_header_html($letterhead_icon_h, $letterhead_font);
$letterhead_footer  = render_pdf_letterhead_footer_html();
$letterhead_mt      = render_pdf_letterhead_header_margin($letterhead_icon_h, $letterhead_font);
$letterhead_mb      = render_pdf_letterhead_footer_margin();

$po_date_fmt = !empty($po_date) ? date('d-M-y', strtotime($po_date)) : '';

// Simbol mata uang di kolom "Unit Price(...)" — contoh resmi user pakai
// "US$" (bukan kode ISO "USD"). Cuma dipetakan utk USD (kasus paling umum
// di data PO nyata); mata uang lain fallback ke kode ISO apa adanya.
$currency_symbol_map = ['USD' => 'US$'];
$currency_symbol = $currency_symbol_map[$currency_code ?? ''] ?? ($currency_code ?? '');
$show_usd_column = !empty($show_usd_column);

// PPN (user 2026-09-06: "kalau ada PPN kenapa masih Without TAX?") --
// grand_total_idr dari build_po_letterhead_data() SUDAH termasuk PPN kalau
// has_ppn; subtotal_idr/ppn_amount_idr dipakai utk breakdown 2 baris
// tambahan sebelum Total Amount, caption di bawahnya menyesuaikan.
$has_ppn        = !empty($has_ppn);
$ppn_rate       = (float) ($ppn_rate ?? 0);
// Sejak 2026-10-02 PPN bisa INCLUDE (harga barang sudah termasuk PPN) --
// ditandai di cetakan supaya supplier tahu PPN diambil dari total, bukan
// ditambahkan. Dihitung di build_po_letterhead_data() lewat po_ppn_breakdown().
$ppn_inclusive  = !empty($ppn_inclusive);
$ppn_rate_txt   = rtrim(rtrim(number_format($ppn_rate, 2), '0'), '.');
$subtotal_idr   = (float) ($subtotal_idr ?? 0);
$ppn_amount_idr = (float) ($ppn_amount_idr ?? 0);
$grand_total_idr = (float) ($grand_total_idr ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($po_number) ?></title>
    <style>
        @page { margin: <?= $letterhead_mt ?>px 30px <?= $letterhead_mb ?>px 30px; }
        body { font-family: 'Courier New', 'DejaVu Sans Mono', monospace; font-size: 10px; color: #1a1a1a; }

        .doc-title {
            text-align: center; font-size: 20px; font-weight: bold; margin: 6px 0 14px 0;
            font-family: 'DejaVu Sans', sans-serif; color: #333;
        }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10px; }
        .info-table td { vertical-align: top; padding: 1px 0; }
        .info-table .lbl { width: 62px; white-space: nowrap; }
        .info-table .colon { width: 10px; }
        .info-right .lbl { width: 70px; }
        .val-strong { color: #1a4b8c; }

        .item-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .item-table th, .item-table td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; }
        .item-table th { background-color: #fff; font-weight: bold; text-align: center; font-size: 9.5px; }
        .item-table td.no { text-align: center; width: 6%; }
        .item-table td.qty { text-align: center; width: 9%; }
        .item-table td.price-usd { text-align: right; width: 15%; color: #777; }
        .item-table td.price-idr { text-align: right; width: 18%; }
        .item-table td.total-idr { text-align: right; width: 18%; }

        .total-row-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .total-row-table td { padding: 2px 0; font-size: 10.5px; }
        .total-label { text-align: right; font-weight: bold; }
        .total-value { text-align: right; font-weight: bold; width: 22%; border-top: 1px solid #000; border-bottom: 3px double #000; padding-top: 3px !important; }
        <?php /* baris breakdown Subtotal/PPN (muncul cuma kalau has_ppn) --
                 sengaja TANPA border ganda spt .total-value, itu cuma utk
                 baris Total Amount final. */ ?>
        .subtotal-label { text-align: right; font-weight: normal; }
        .subtotal-value { text-align: right; font-weight: normal; width: 22%; }
        .without-tax { text-align: right; font-size: 9px; margin-bottom: 14px; }

        .terms-box { border: 1px solid #000; padding: 8px 10px; font-size: 9.5px; margin-bottom: 18px; line-height: 1.6; }

        .sign-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sign-table td { width: 50%; vertical-align: top; font-size: 9.5px; padding: 0 10px; }
        <?php /* .sign-line kanan (AUTHORIZED SIGNATURE) diisi stempel ttd
                 (render_signature_stamp_html(), default 80px) -- 46px lama
                 kekecilan, stempel meluber nabrak .sign-company di
                 bawahnya (user 2026-09-06, screenshot). Dinaikkan ke 90px
                 supaya cukup lega. */ ?>
        .sign-line { border-bottom: 1px solid #000; height: 90px; margin-top: 4px; }
        .sign-company { margin-top: 6px; font-weight: bold; }
        <?php /* Nama penanda tangan (user 2026-09-09: "bawah stamp nya kasih
                 nama user nya"). Ditaruh DI BAWAH garis tanda tangan, di atas
                 nama perusahaan — pola lazim blok tanda tangan. Kosong sendiri
                 kalau PO belum di-approve, karena $authorized_name memang cuma
                 diisi setelah approval (build_po_letterhead_data()). */ ?>
        .sign-name { margin-top: 4px; font-size: 9.5px; }
    </style>
</head>
<body>
    <div style="position: fixed; top: -<?= $letterhead_mt ?>px; left: 0px; right: 0px;"><?= $letterhead_header ?></div>
    <div style="position: fixed; bottom: -<?= $letterhead_mb ?>px; left: 0px; right: 0px;"><?= $letterhead_footer ?></div>

    <div class="doc-title"><?= lang_line('po_letterhead_title', 'Purchasing Order') ?></div>

    <table class="info-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <table class="info-table">
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_issue_to', 'ISSUE TO'); ?></td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($supplier_name ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_attn_to', 'ATTN TO'); ?></td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($supplier_attn ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_tel', 'TEL'); ?></td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($supplier_tel ?? '') ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="info-table info-right">
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_po_no', 'P.O. NO.'); ?></td>
                        <td class="colon">:</td>
                        <td class="val-strong"><?= htmlspecialchars($po_number ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_date', 'DATE'); ?></td>
                        <td class="colon">:</td>
                        <td class="val-strong"><?= htmlspecialchars($po_date_fmt) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_ref', 'REF.'); ?></td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($ref_text ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl"><?= lang_line('po_letterhead_buyer', 'BUYER'); ?></td>
                        <td class="colon">:</td>
                        <td class="val-strong"><?= htmlspecialchars($buyer_name ?? '') ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:6%;"><?= lang_line('po_letterhead_th_item', 'ITEM'); ?></th>
                <th><?= lang_line('po_letterhead_th_description', 'DESCRIPTION'); ?></th>
                <th style="width:9%;"><?= lang_line('po_letterhead_th_qty', 'QTY(PCS)'); ?></th>
                <?php if ($show_usd_column): ?>
                    <th style="width:15%;"><?= sprintf(lang_line('po_letterhead_th_unit_price_ccy', 'Unit Price(%s)'), htmlspecialchars($currency_symbol)); ?></th>
                <?php endif; ?>
                <th style="width:18%;"><?= lang_line('po_letterhead_th_unit_price_idr', 'Unit Price(IDR)'); ?></th>
                <th style="width:18%;"><?= lang_line('po_letterhead_th_total_idr', 'Total Amount(IDR)'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): foreach ($items as $it): ?>
                <tr>
                    <td class="no"><?= htmlspecialchars($it['no'] ?? '') ?></td>
                    <td>
                        <?= htmlspecialchars($it['item_code'] ?? '') ?><br>
                        <?= htmlspecialchars($it['item_name'] ?? '') ?>
                    </td>
                    <td class="qty"><?= number_format((float) ($it['qty'] ?? 0), (fmod((float) ($it['qty'] ?? 0), 1) ? 2 : 0)) ?></td>
                    <?php if ($show_usd_column): ?>
                        <?php /* Harga SATUAN 4 desimal (user 2026-09-10, migration_po_price_4dec);
                                 kolom Amount & seluruh baris total di bawah TETAP 2 desimal. */ ?>
                        <td class="price-usd"><?= isset($it['unit_price_usd']) ? htmlspecialchars($currency_symbol) . '&nbsp;' . number_format((float) $it['unit_price_usd'], 4) : '-' ?></td>
                    <?php endif; ?>
                    <td class="price-idr">IDR&nbsp;&nbsp;<?= number_format((float) ($it['unit_price_idr'] ?? 0), 4) ?></td>
                    <td class="total-idr">IDR&nbsp;&nbsp;<?= number_format((float) ($it['amount'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="<?= $show_usd_column ? 6 : 5 ?>" style="text-align:center; padding: 16px;">- <?= lang_line('po_letterhead_no_item', 'Tidak ada item'); ?> -</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="total-row-table">
        <?php if ($has_ppn): ?>
        <tr>
            <td class="subtotal-label"><?= lang_line('po_letterhead_subtotal', 'Subtotal'); ?>:</td>
            <td class="subtotal-value">IDR&nbsp;&nbsp;<?= number_format($subtotal_idr, 2) ?></td>
        </tr>
        <tr>
            <td class="subtotal-label"><?= sprintf(lang_line('po_letterhead_ppn', 'PPN (%s%%)'), $ppn_rate_txt) ?><?= $ppn_inclusive ? ' ' . lang_line('po_letterhead_ppn_incl', '— termasuk harga') : '' ?>:</td>
            <td class="subtotal-value">IDR&nbsp;&nbsp;<?= number_format($ppn_amount_idr, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="total-label"><?= lang_line('po_letterhead_total_amount', 'Total Amount'); ?>:</td>
            <td class="total-value">IDR&nbsp;&nbsp;<?= number_format($grand_total_idr, 2) ?></td>
        </tr>
    </table>
    <?php if ($has_ppn): ?>
        <div class="without-tax"><?= $ppn_inclusive
            ? sprintf(lang_line('po_letterhead_tax_included', 'Price Incl. PPN %s%%'), $ppn_rate_txt)
            : sprintf(lang_line('po_letterhead_incl_tax', 'Incl. PPN %s%%'), $ppn_rate_txt) ?></div>
    <?php else: ?>
        <div class="without-tax"><?= lang_line('po_letterhead_without_tax', 'Without TAX'); ?></div>
    <?php endif; ?>

    <div class="terms-box">
        <strong><?= lang_line('po_letterhead_payment_term', 'PAYMENT TERM'); ?> :</strong> <?= htmlspecialchars($payment_term ?? '') ?><br>
        <strong><?= lang_line('po_letterhead_delivery_term', 'DELIVERY TERM'); ?> :</strong> <?= htmlspecialchars($delivery_term ?? '') ?><br>
        <?php if (!empty($remarks)): ?>
            <strong><?= lang_line('po_letterhead_remarks', 'REMARKS'); ?>:</strong> <?= nl2br(htmlspecialchars($remarks)) ?>
        <?php else: ?>
            <strong><?= lang_line('po_letterhead_remarks', 'REMARKS'); ?>:</strong>
        <?php endif; ?>
    </div>

    <table class="sign-table">
        <tr>
            <td>
                <?= lang_line('po_letterhead_sign_return', 'PLEASE SIGN AND RETURN COPY'); ?> :
                <div class="sign-line"></div>
                <div class="sign-company"><?= htmlspecialchars($supplier_name ?? '') ?></div>
            </td>
            <td>
                <?= lang_line('po_letterhead_authorized_sign', 'AUTHORIZED SIGNATURE'); ?> :
                <div class="sign-line"><?= $authorized_signature_html ?? '' ?></div>
                <?php if (!empty($authorized_name)): ?>
                    <div class="sign-name"><?= htmlspecialchars($authorized_name) ?></div>
                <?php endif; ?>
                <div class="sign-company"><?= htmlspecialchars($company_name) ?></div>
            </td>
        </tr>
    </table>

</body>
</html>
