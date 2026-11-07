<?php
/**
 * Shared document PDF template (IRF / PR / PO / etc.)
 *
 * Expected variables:
 *   $irf_number     string  document number (e.g. "ADM00001", "PRPRO - 000001")
 *   $request_date   string|date
 *   $department     string
 *   $purpose        string
 *   $items          array of objects/arrays with keys:
 *                     no, item_code, item_name, qty, unit, unit_price, amount, notes
 *   $signatures     array of 5 sub-arrays with keys: name, signature_html, date
 *                     order: applicant, head_dept, endorment, approve_by, receiver
 *   $form_type      string  e.g. 'Material', 'General' (used in default IRF title)
 *
 * Optional overrides (for PR/PO/etc.):
 *   $form_title     string  fully overrides the title (e.g. "PURCHASE REQUEST")
 *   $form_subtitle  string  Chinese subtitle (default "领料单")
 *   $number_label   string  prefix label of the number box (default "Permintaan Barang")
 */
$form_title    = $form_title    ?? ('PERMINTAAN BARANG' . (!empty($form_type) ? ' - ' . strtoupper($form_type) : ''));
$form_subtitle = $form_subtitle ?? '领料单';
$number_label  = $number_label  ?? 'Permintaan Barang';

// Header/footer letterhead identitas perusahaan (logo_full_biru.png + teks
// "PT. NEW SPECIALIFE INDUSTRIES" + alamat) -- user 2026-09-06: ganti header
// teks inline dgn header resmi + footer alamat, REPEATING di tiap halaman.
// Dipakai bareng oleh IRF/MRF/PR (semua varian) krn semuanya lewat
// render_document_pdf() yg nge-load template SATU ini. Dompdf: fixed-position
// div + @page margin dgn offset negatif (lihat render_pdf_letterhead_header_margin()
// di document_pdf_helper.php utk penjelasan angkanya).
$letterhead_icon_h  = 50;
$letterhead_font    = 24;
$letterhead_header  = render_pdf_letterhead_header_html($letterhead_icon_h, $letterhead_font);
$letterhead_footer  = render_pdf_letterhead_footer_html();
$letterhead_mt      = render_pdf_letterhead_header_margin($letterhead_icon_h, $letterhead_font);
$letterhead_mb      = render_pdf_letterhead_footer_margin();

$grand_total = 0.0;
foreach ($items as $it) {
    $grand_total += (float)($it['amount'] ?? 0);
}

$d = $request_date ? strtotime($request_date) : null;
$yy = $d ? date('y', $d) : '';
$mm = $d ? date('m', $d) : '';
$dd = $d ? date('d', $d) : '';
?>
<?php
$font_regular = FCPATH . 'assets/fonts/noto-sans-sc/NotoSansSC-Regular.ttf';
$font_bold    = FCPATH . 'assets/fonts/noto-sans-sc/NotoSansSC-Bold.ttf';
$font_regular_url = file_exists($font_regular) ? 'file://' . $font_regular : '';
$font_bold_url    = file_exists($font_bold) ? 'file://' . $font_bold : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($irf_number) ?></title>
    <style>
        <?php if ($font_regular_url): ?>
        @font-face {
            font-family: 'NotoSansSC';
            font-style: normal;
            font-weight: normal;
            src: url('<?= $font_regular_url ?>') format('truetype');
        }
        @font-face {
            font-family: 'NotoSansSC';
            font-style: normal;
            font-weight: bold;
            src: url('<?= $font_bold_url ?>') format('truetype');
        }
        <?php endif; ?>
        @page { margin: <?= $letterhead_mt ?>px 25px <?= $letterhead_mb ?>px 25px; }
        body { font-family: 'NotoSansSC', 'DejaVu Sans', sans-serif; font-size: 9px; color: #000; }
        .irf-no-box {
            border: 1px dashed #000; padding: 5px 10px; font-size: 11px; font-weight: bold;
            display: inline-block; min-width: 120px; text-align: center; vertical-align: middle;
        }
        .irf-no-label { font-weight: bold; font-size: 11px; vertical-align: middle; }
        .form-title { text-align: center; font-size: 16px; font-weight: bold; margin: 8px 0 2px 0; }
        .form-subtitle { text-align: center; font-size: 11px; margin-bottom: 12px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 9px; }
        .info-table td { padding: 3px 0; vertical-align: middle; }
        .info-label { font-weight: bold; width: 22%; }
        .info-colon { width: 1%; }
        .info-input { border: 1px solid #000; padding: 3px 6px; }
        .date-box { border: 1px solid #000; padding: 2px 8px; margin: 0 3px; display: inline-block; min-width: 25px; text-align: center; }
        .dashed-divider { border-top: 1.5px dashed #000; margin: 8px 0; }
        .item-table { width: 100%; border-collapse: collapse; }
        .item-table th, .item-table td { border: 1px solid #000; padding: 4px; vertical-align: top; }
        .item-table th { background-color: #f2f2f2; font-weight: bold; text-align: center; font-size: 9px; }
        .item-table td.no { text-align: center; width: 5%; }
        .item-table td.qty { text-align: right; width: 10%; }
        .item-table td.price { text-align: right; width: 14%; }
        .item-table td.amount { text-align: right; width: 14%; }
        .grand-total-row td { font-weight: bold; background-color: #f2f2f2; }
        .signatures { margin-top: 20px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .signatures td { border: 1px solid #000; text-align: center; vertical-align: middle; padding: 6px 4px; font-size: 9px; }
        .sig-header { padding: 6px 4px; }
        .sig-label { font-weight: bold; }
        .sig-cn { font-size: 9px; color: #000; padding-top: 2px; }
        .sig-box { height: 65px; vertical-align: middle; padding: 4px; }
        .sig-name-line { padding-top: 2px; font-size: 9px; }
        .sig-date { font-size: 8px; padding-top: 2px; }
    </style>
</head>
<body>
    <div style="position: fixed; top: -<?= $letterhead_mt ?>px; left: 0px; right: 0px;"><?= $letterhead_header ?></div>
    <div style="position: fixed; bottom: -<?= $letterhead_mb ?>px; left: 0px; right: 0px;"><?= $letterhead_footer ?></div>

    <div style="text-align: right; margin-bottom: 5px; padding-top: 6px; line-height: 1.4;">
        <span class="irf-no-label"><?= htmlspecialchars($number_label) ?></span> :
        <span class="irf-no-box"><?= htmlspecialchars($irf_number) ?></span>
    </div>

    <div class="form-title"><?= htmlspecialchars($form_title) ?></div>
    <?php if (!empty($form_subtitle)): ?>
        <div class="form-subtitle"><?= htmlspecialchars($form_subtitle) ?></div>
    <?php endif; ?>

    <table class="info-table">
        <tr>
            <td class="info-label">Date [日期]</td>
            <td class="info-colon">:</td>
            <td>
                <span class="date-box"><?= htmlspecialchars($yy) ?></span> YY [年]
                <span class="date-box"><?= htmlspecialchars($mm) ?></span> MM [月]
                <span class="date-box"><?= htmlspecialchars($dd) ?></span> DD [日]
            </td>
        </tr>
        <tr>
            <td class="info-label">Department [部門]</td>
            <td class="info-colon">:</td>
            <td><div class="info-input"><?= htmlspecialchars($department ?? '') ?></div></td>
        </tr>
        <?php if (!empty($supplier)): ?>
        <tr>
            <td class="info-label">Supplier [供应商]</td>
            <td class="info-colon">:</td>
            <td><div class="info-input"><?= htmlspecialchars($supplier) ?></div></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="info-label">Purpose of Requisition [领料用途]</td>
            <td class="info-colon">:</td>
            <td><div class="info-input" style="min-height: 28px;"><?= nl2br(htmlspecialchars($purpose ?? '')) ?></div></td>
        </tr>
    </table>

    <div class="dashed-divider"></div>

    <?php
        // Untuk PMR (Material Request), 2 kolom terakhir diganti:
        //   Unit Price → IPO Number (sc_number dari WO yang menaungi material ini)
        //   Amount     → Remark (notes dari mr_details)
        // Untuk IRF General (consumable), tetap pakai Unit Price + Amount.
        $is_pmr = (isset($form_type) && $form_type === 'Material');
    ?>
    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th>Material Description<br><span style="font-weight: normal; font-size: 8px;">[物品名称]</span></th>
                <th style="width:10%;">Quantity<br><span style="font-weight: normal; font-size: 8px;">[数量]</span></th>
                <?php if ($is_pmr): ?>
                    <th style="width:18%;">Sales Order Number<br></th>
                    <th style="width:22%;">Remark<br><span style="font-weight: normal; font-size: 8px;">[備註]</span></th>
                <?php else: ?>
                    <th style="width:14%;">Unit Price<br><span style="font-weight: normal; font-size: 8px;">[报价]</span></th>
                    <th style="width:14%;">Amount<br><span style="font-weight: normal; font-size: 8px;">[金額]</span></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $i => $it): ?>
                    <tr>
                        <td class="no"><?= ($it['no'] ?? ($i + 1)) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($it['item_code'] ?? '') ?></strong> - <?= htmlspecialchars($it['item_name'] ?? '') ?>
                            <?php if (!$is_pmr && !empty($it['notes'])): ?>
                                <br><span style="font-size: 8px; color: #555; word-break: break-all;"><?= nl2br(htmlspecialchars($it['notes'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="qty">
                            <?= number_format((float)($it['qty'] ?? 0), 2) ?>
                            <?php if (!empty($it['unit'])): ?>
                                <small style="color: #555;"> <?= htmlspecialchars($it['unit']) ?></small>
                            <?php endif; ?>
                        </td>
                        <?php if ($is_pmr): ?>
                            <td style="text-align: center;">
                                <?= !empty($it['sc_number']) ? htmlspecialchars($it['sc_number']) : '-' ?>
                            </td>
                            <td style="font-size: 9px; word-break: break-all;">
                                <?= !empty($it['notes']) ? nl2br(htmlspecialchars($it['notes'])) : '-' ?>
                            </td>
                        <?php else: ?>
                            <td class="price">
                                <?= ((float)($it['unit_price'] ?? 0)) > 0 ? number_format((float)$it['unit_price'], 2) : '-' ?>
                            </td>
                            <td class="amount">
                                <?= ((float)($it['amount'] ?? 0)) > 0 ? number_format((float)$it['amount'], 2) : '-' ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php $blank_rows = max(0, 8 - count($items)); ?>
                <?php for ($i = 0; $i < $blank_rows; $i++): ?>
                    <tr>
                        <td class="no">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="qty">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; padding: 20px;">- Tidak ada item -</td></tr>
            <?php endif; ?>
            <?php if (!$is_pmr): ?>
                <tr class="grand-total-row">
                    <td colspan="4" style="text-align: right;">GRAND TOTAL [縂計]&nbsp;:</td>
                    <td class="amount"><?= $grand_total > 0 ? number_format($grand_total, 2) : '-' ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
        // Caller boleh override label kolom signature (mis. Material Request hanya 4 kolom tanpa Receiver).
        $sig_labels = isset($signature_labels) && is_array($signature_labels) && !empty($signature_labels)
            ? $signature_labels
            : [
                ['Applicant',    '申請人'],
                ['Head of Dept.', '部門主管'],
                ['Endorment',    '認可人'],
                ['Approve by',   '核准人'],
                ['Receiver',     '收款人'],
            ];
        $sig_count = count($sig_labels);
        $sig_width = (int) floor(100 / max(1, $sig_count));
    ?>
    <table class="signatures">
        <tr>
            <?php foreach ($sig_labels as $lbl): ?>
                <td class="sig-header" style="width: <?= $sig_width ?>%;">
                    <div class="sig-label"><?= htmlspecialchars($lbl[0]) ?></div>
                    <div class="sig-cn"><?= htmlspecialchars($lbl[1]) ?></div>
                </td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php for ($i = 0; $i < $sig_count; $i++): ?>
                <td class="sig-box">
                    <?= !empty($signatures[$i]['signature_html']) ? $signatures[$i]['signature_html'] : '&nbsp;' ?>
                </td>
            <?php endfor; ?>
        </tr>
        <tr>
            <?php for ($i = 0; $i < $sig_count; $i++): ?>
                <td>
                    <div class="sig-name-line">
                        <?= !empty($signatures[$i]['name']) ? htmlspecialchars($signatures[$i]['name']) : '(_______________)' ?>
                    </div>
                    <div class="sig-date">
                        <?= !empty($signatures[$i]['date']) ? htmlspecialchars($signatures[$i]['date']) : '' ?>
                    </div>
                </td>
            <?php endfor; ?>
        </tr>
    </table>
</body>
</html>
