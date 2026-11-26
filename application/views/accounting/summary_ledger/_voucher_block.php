<?php
/**
 * Satu voucher ringkas ukuran setengah A4 portrait (= A5 landscape, 190 x ~138 mm),
 * dipakai oleh pdf_disbursement.php (2 voucher per lembar A4, ada garis potong).
 * Variabel: $header, $details (dari Summary_ledger_model::get_journal_for_pdf),
 * $logo_data (base64, opsional).
 */
$total_debit  = 0;
$total_credit = 0;
foreach ($details as $d) {
    $total_debit  += (float) $d->debit_local;
    $total_credit += (float) $d->kredit_local;
}
$fmt = function ($v) { return number_format((float) $v, 2, '.', ','); };

// PPN/VAT & WHT (PPh 23 / 4(2)) dari header; WHT sudah termasuk total kredit
// (baris Cr Hutang PPh), jadi Total Paid = total kredit - WHT.
$ppn_amount   = !empty($header->ppn_enabled) ? (float) ($header->ppn_amount ?? 0) : 0;
$ppn_percent  = (float) ($header->ppn_percent ?? 0);
$wht23_amount = (float) ($header->wht23_amount ?? 0);
$wht23_rate   = (float) ($header->wht23_rate ?? 0);
$wht42_amount = (float) ($header->wht42_amount ?? 0);
$wht42_rate   = (float) ($header->wht42_rate ?? 0);
$total_wht    = $wht23_amount + $wht42_amount;
$total_paid   = $total_credit - $total_wht;
$is_balanced  = (round($total_debit, 2) == round($total_credit, 2));

$rate_fmt  = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); };
$vat_pct   = $ppn_percent > 0 ? $rate_fmt($ppn_percent) : '11';

// Vendor (snapshot di header / dilengkapi dari dokumen asal). Account Bank = "no. rek / atas nama".
$vendor_name  = trim((string) ($header->vendor_name ?? ''));
$vendor_bank  = trim((string) ($header->vendor_bank_name ?? ''));
$vendor_acc   = trim((string) ($header->vendor_bank_account ?? ''));
$vendor_owner = trim((string) ($header->vendor_bank_owner ?? '')) ?: $vendor_name;
$vendor_acc_line = ($vendor_acc !== '' && $vendor_owner !== '') ? $vendor_acc . ' / ' . $vendor_owner : $vendor_acc;

$doc_date = !empty($header->document_date) ? date('d/m/y', strtotime($header->document_date))
          : (!empty($header->journal_date) ? date('d/m/y', strtotime($header->journal_date)) : '');
$due_date = !empty($header->due_date) ? date('d/m/y', strtotime($header->due_date)) : '';

$min_rows = !empty($full) ? 6 : 2; // baris kosong minimal di tabel jurnal
?>
<table class="frame">
    <tr>
        <td class="center vmid" style="width: 20mm;">
            <?php if (!empty($logo_data)): ?><img src="<?= $logo_data; ?>" style="width: 15mm; height: auto;"><?php endif; ?>
        </td>
        <td class="vmid">
            <div class="company-name"><?= htmlspecialchars(company_info('company_name')); ?></div>
            <div class="company-meta"><?= implode('<br>', array_map('htmlspecialchars', company_contact_lines())); ?></div>
        </td>
        <td style="width: 56mm; padding: 0;">
            <table class="inner">
                <tr><td class="b" style="width: 23mm;">Voucher No</td><td>: </td></tr>
                <tr><td class="b">Document Date</td><td>: <?= $doc_date; ?></td></tr>
                <tr><td class="b last">Due Date</td><td class="last">: <?= $due_date; ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="frame bt0">
    <tr><td class="title-voucher">Voucher</td></tr>
</table>

<table class="frame bt0">
    <tr>
        <td style="padding: 1.5mm 2.5mm;">
            <table class="info">
                <tr>
                    <td class="b" style="width: 24mm;">Vendor Name</td><td style="width: 2mm;">:</td><td><?= htmlspecialchars($vendor_name); ?></td>
                    <td class="b" style="width: 20mm;">Bank Name</td><td style="width: 2mm;">:</td><td style="width: 55mm;"><?= htmlspecialchars($vendor_bank); ?></td>
                </tr>
                <tr>
                    <td class="b">Description</td><td>:</td><td><?= htmlspecialchars((string) $header->description); ?></td>
                    <td class="b">Account Bank</td><td>:</td><td><?= htmlspecialchars($vendor_acc_line); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="frame bt0 jt">
    <thead>
        <tr><td colspan="6" class="b section">Accounting Journal</td></tr>
        <tr>
            <th style="width: 7mm;">No</th>
            <th style="width: 20mm;">GL Account</th>
            <th>GL Description</th>
            <th style="width: 27mm;">Debit</th>
            <th style="width: 27mm;">Credit</th>
            <th style="width: 13mm;">Checked</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($details as $d):
            $gl_desc = trim((string) ($d->account_name ?? ''));
            if (!empty($d->description)) $gl_desc .= ($gl_desc !== '' ? ' — ' : '') . $d->description; ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->account_code ?? '-'); ?></td>
                <td><?= htmlspecialchars($gl_desc); ?></td>
                <td class="right"><?= ((float) $d->debit_local > 0) ? $fmt($d->debit_local) : ''; ?></td>
                <td class="right"><?= ((float) $d->kredit_local > 0) ? $fmt($d->kredit_local) : ''; ?></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($k = count($details); $k < $min_rows; $k++): ?>
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
            <td></td><td class="right"><?= $ppn_amount > 0 ? $fmt($ppn_amount) : ''; ?></td><td></td>
        </tr>
        <tr>
            <td colspan="3" class="b">WHT 23<?= $wht23_rate > 0 ? ' (' . $rate_fmt($wht23_rate) . '%)' : ''; ?></td>
            <td></td><td class="right"><?= $wht23_amount > 0 ? $fmt($wht23_amount) : ''; ?></td><td></td>
        </tr>
        <?php if ($wht42_amount > 0): ?>
        <tr>
            <td colspan="3" class="b">WHT 4(2)<?= $wht42_rate > 0 ? ' (' . $rate_fmt($wht42_rate) . '%)' : ''; ?></td>
            <td></td><td class="right"><?= $fmt($wht42_amount); ?></td><td></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td colspan="3" class="b">Total Paid</td>
            <td></td><td class="right b"><?= $fmt($total_paid); ?></td><td></td>
        </tr>
    </tfoot>
</table>

<table class="frame sig" style="margin-top: 1.2mm;">
    <tr>
        <td class="center b" style="width: 25%;">Prepared By</td>
        <td class="center b" style="width: 25%;">Checked By</td>
        <td class="center b" style="width: 25%;">Approved By</td>
        <td class="center b" style="width: 25%;">Paid By</td>
    </tr>
    <tr><td style="height: <?= !empty($full) ? '18mm' : '11mm'; ?>;"></td><td></td><td></td><td></td></tr>
    <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
</table>

<div class="ref">
    <b>Ref No Journal : <?= htmlspecialchars($header->journal_no); ?></b>
    <span class="printed">&nbsp;&nbsp;·&nbsp;&nbsp;Dicetak pada : <?= date('d/m/Y H:i'); ?> WIB</span>
</div>
