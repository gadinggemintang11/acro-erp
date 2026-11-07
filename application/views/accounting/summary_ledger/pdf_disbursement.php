<?php
// Logo kop dari Developer Mode. Dulu menunjuk sidebar.png (gambar sidebar,
// bukan logo dokumen) -- disamakan dgn kop PDF lain supaya satu slot
// mengatur semuanya.
$logo_path = FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png');
$logo_data = '';
if (file_exists($logo_path)) {
    $logo_data = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
}

$total_debit  = 0;
$total_credit = 0;
foreach ($details as $d) {
    $total_debit  += (float) $d->debit_local;
    $total_credit += (float) $d->kredit_local;
}

$fmt = fn($v) => number_format((float) $v, 2, '.', ',');

// Nilai PPN/VAT dari header (untuk ditampilkan di baris recap "VAT 11%").
$ppn_amount  = !empty($header->ppn_enabled) ? (float) ($header->ppn_amount ?? 0) : 0;
$ppn_percent = (float) ($header->ppn_percent ?? 0);
// Nilai WHT (PPh 23 & 4(2)) — dari header. WHT jadi baris jurnal (Cr Hutang PPh),
// jadi sudah termasuk di total_credit; di footer ditampilkan sebagai recap.
$wht23_amount = (float) ($header->wht23_amount ?? 0);
$wht23_rate   = (float) ($header->wht23_rate ?? 0);
$wht42_amount = (float) ($header->wht42_amount ?? 0);
$wht42_rate   = (float) ($header->wht42_rate ?? 0);
$total_wht    = $wht23_amount + $wht42_amount;
// Data vendor (snapshot di header).
$vendor_name = $header->vendor_name ?? '';
$vendor_bank = $header->vendor_bank_name ?? '';
$vendor_acc  = $header->vendor_bank_account ?? '';

// Account Bank ditulis "no. rekening / atas nama" biar jelas rekening itu punya siapa.
// Contoh: 8715853830 / Arjuna Catering
$vendor_acc_line = (trim((string) $vendor_acc) !== '' && trim((string) $vendor_name) !== '')
    ? trim((string) $vendor_acc) . ' / ' . trim((string) $vendor_name)
    : trim((string) $vendor_acc);

// Format angka rate tanpa trailing nol (11.00 -> 11, 2.00 -> 2).
$rate_fmt  = fn($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$vat_pct   = $ppn_percent > 0 ? $rate_fmt($ppn_percent) : '11';
$wht23_pct = $rate_fmt($wht23_rate);
$wht42_pct = $rate_fmt($wht42_rate);

// Total Paid = total kredit dikurangi total WHT (dipotong dari pembayaran ke vendor).
$total_paid  = $total_credit - $total_wht;
$is_balanced = (round($total_debit, 2) == round($total_credit, 2));

// Tanggal: Document Date & Due Date (format dd/mm/yy seperti contoh).
$doc_date = !empty($header->document_date) ? date('d/m/y', strtotime($header->document_date))
          : (!empty($header->journal_date) ? date('d/m/y', strtotime($header->journal_date)) : '');
$due_date = !empty($header->due_date) ? date('d/m/y', strtotime($header->due_date)) : '';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #222; }
    table { border-collapse: collapse; width: 100%; }
    .no-border td, .no-border th { border: none; padding: 1px 3px; }
    .frame { border: 1px solid #000; }
    .frame td, .frame th { border: 1px solid #000; padding: 2px 5px; vertical-align: top; }
    .center { text-align: center; }
    .right { text-align: right; }
    .b { font-weight: bold; }
    .title-voucher { text-align: center; font-size: 13pt; font-weight: bold; padding: 4px 0; }
    .header-row td { vertical-align: middle; }
    .company-name { font-size: 12pt; font-weight: bold; }
    .company-meta { font-size: 7.5pt; line-height: 1.25; }
    .label-cell { padding: 2px 5px; font-size: 8pt; }
    .signature-table td { vertical-align: bottom; padding-bottom: 2px; }
    .signature-name { border-top: 1px solid #000; padding-top: 2px; }
</style>
</head>
<body>

<!-- ============ HEADER FRAME ============ -->
<table class="frame">
    <tr class="header-row">
        <td style="width: 90px;" class="center">
            <?php if ($logo_data): ?>
                <img src="<?= $logo_data; ?>" style="width: 70px; height: auto;">
            <?php endif; ?>
        </td>
        <td style="width: 60%;">
            <div class="company-name"><?= htmlspecialchars(company_info('company_name')) ?></div>
            <div class="company-meta">
                <?= implode('<br>', array_map('htmlspecialchars', company_contact_lines())) ?>
            </div>
        </td>
        <td style="padding: 0;">
            <table class="no-border" style="width: 100%;">
                <tr>
                    <td class="label-cell b" style="width: 110px; border-bottom: 1px solid #000;">Voucher No</td>
                    <td class="label-cell" style="border-bottom: 1px solid #000;">:&nbsp; </td>
                </tr>
                <tr>
                    <td class="label-cell b" style="border-bottom: 1px solid #000;">Document Date</td>
                    <td class="label-cell" style="border-bottom: 1px solid #000;">:&nbsp; <?= $doc_date; ?></td>
                </tr>
                <tr>
                    <td class="label-cell b">Due Date</td>
                    <td class="label-cell">:&nbsp; <?= $due_date; ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ============ TITLE ============ -->
<table class="frame" style="border-top: none;">
    <tr><td class="title-voucher">Voucher Disbursement</td></tr>
</table>

<!-- ============ INFO ============ -->
<table class="frame" style="border-top: none;">
    <tr>
        <td style="padding: 10px 16px;">
            <table class="no-border" style="width: 100%;">
                <tr>
                    <td style="width: 180px;" class="b">Vendor Name</td>
                    <td style="width: 10px;">:</td>
                    <td><?= htmlspecialchars($vendor_name); ?></td>
                </tr>
                <tr>
                    <td class="b">Description of Transaction</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($header->description); ?></td>
                </tr>
                <tr><td colspan="3" style="height: 8px;"></td></tr>
                <tr>
                    <td class="b">Bank Name</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($vendor_bank); ?></td>
                </tr>
                <tr>
                    <td class="b">Account Bank</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($vendor_acc_line); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ============ ACCOUNTING JOURNAL ============ -->
<table class="frame" style="border-top: none;">
    <tr><td class="b" style="padding: 6px 10px;">Accounting Journal</td></tr>
</table>

<table class="frame" style="border-top: none;">
    <thead>
        <tr>
            <th class="center" style="width: 40px;">No</th>
            <th class="center" style="width: 110px;">GL Account</th>
            <th class="center">GL Description</th>
            <th class="center" style="width: 130px;">Debit</th>
            <th class="center" style="width: 130px;">Credit</th>
            <th class="center" style="width: 60px;">Checked</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($details as $d): ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->account_code ?? '-'); ?></td>
                <?php
                    // GL Description disamakan dgn output Excel: "account_name — description".
                    $gl_desc = trim((string) ($d->account_name ?? ''));
                    if (!empty($d->description)) {
                        $gl_desc .= ($gl_desc !== '' ? ' — ' : '') . $d->description;
                    }
                ?>
                <td><?= htmlspecialchars($gl_desc); ?></td>
                <td class="right"><?= ((float) $d->debit_local > 0) ? $fmt($d->debit_local) : ''; ?></td>
                <td class="right"><?= ((float) $d->kredit_local > 0) ? $fmt($d->kredit_local) : ''; ?></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        <?php
        // Padding rows minimal supaya tabel tidak terlalu pendek (max 3 baris padding)
        $min_rows = 3;
        $missing  = max(0, $min_rows - count($details));
        for ($k = 0; $k < $missing; $k++): ?>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="b">Total</td>
            <td class="right b"><?= $fmt($total_debit); ?></td>
            <td class="right b"><?= $fmt($total_credit); ?></td>
            <td class="center b"><?= $is_balanced ? 'TRUE' : 'FALSE'; ?></td>
        </tr>
        <tr>
            <td colspan="3" class="b">VAT <?= $vat_pct; ?>%</td>
            <td></td>
            <td class="right"><?= $ppn_amount > 0 ? $fmt($ppn_amount) : ''; ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="b">WHT 23<?= $wht23_rate > 0 ? ' (' . $wht23_pct . '%)' : ''; ?></td>
            <td></td>
            <td class="right"><?= $wht23_amount > 0 ? $fmt($wht23_amount) : ''; ?></td>
            <td></td>
        </tr>
        <?php if ($wht42_amount > 0): ?>
        <tr>
            <td colspan="3" class="b">WHT 4(2)<?= $wht42_rate > 0 ? ' (' . $wht42_pct . '%)' : ''; ?></td>
            <td></td>
            <td class="right"><?= $fmt($wht42_amount); ?></td>
            <td></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td colspan="3" class="b">Total Paid</td>
            <td></td>
            <td class="right b"><?= $fmt($total_paid); ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<!-- ============ SIGNATURES ============ -->
<table class="frame signature-table" style="margin-top: 4px;">
    <tr>
        <td class="center b" style="width: 25%;">Prepared By</td>
        <td class="center b" style="width: 25%;">Checked By</td>
        <td class="center b" style="width: 25%;">Approved By</td>
        <td class="center b" style="width: 25%;">Paid By</td>
    </tr>
    <tr>
        <td style="height: 38px;"></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td class="center signature-name">&nbsp;</td>
        <td class="center signature-name">&nbsp;</td>
        <td class="center signature-name">&nbsp;</td>
        <td class="center signature-name">&nbsp;</td>
    </tr>
</table>

<!-- ============ REF NO JOURNAL (di bawah blok tanda tangan, sama spt Excel) ============ -->
<table class="no-border" style="margin-top: 6px;">
    <tr><td class="b" style="text-align: left; padding: 1px 3px;">Ref No Journal : <?= htmlspecialchars($header->journal_no); ?></td></tr>
    <tr><td style="text-align: left; padding: 1px 3px; font-size: 9px; color: #666;">Dicetak pada : <?= date('d/m/Y H:i'); ?> WIB</td></tr>
</table>

</body>
</html>
