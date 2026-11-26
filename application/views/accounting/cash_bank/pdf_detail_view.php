<?php
/**
 * PDF Bukti Kas/Bank Masuk/Keluar. Desain mengikuti Voucher Summary Ledger
 * (summary_ledger/pdf_disbursement.php), permintaan user 2026-11-26:
 * kertas A4 PORTRAIT dibagi 2 -> bukti landscape di setengah atas + garis potong.
 * Isi = data Cash Bank saja (tanpa field khusus voucher seperti Vendor/Due Date).
 * Detail terlalu banyak untuk setengah halaman -> dicetak 1 halaman penuh.
 *
 * Variabel: $title, $header (CashBank_model::get_header_by_id), $details.
 */
$logo_path = FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png');
$logo_data = file_exists($logo_path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path)) : '';

$fmt      = function ($v) { return number_format((float) $v, 2, '.', ','); };
$is_idr   = ($header->currency == 'IDR');
$sig_last = ($header->transaction_type == 'IN') ? 'Penyetor' : 'Penerima';

// Perkiraan tinggi tabel detail (baris + baris yang membungkus karena teks panjang).
// Kolom Nama Akun ~28 karakter/baris, Deskripsi ~48 karakter/baris, Keterangan ~60.
$units = 0;
foreach ($details as $d) {
    $units += max(1, (int) ceil(mb_strlen((string) $d->account_name) / 28), (int) ceil(mb_strlen((string) $d->description) / 48));
}
$units += max(0, (int) ceil(mb_strlen((string) $header->description) / 60) - 1);
if (!$is_idr) $units++;
$full     = ($units > 9);
$min_rows = $full ? 6 : 2; // baris kosong minimal di tabel detail
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($title); ?></title>
<style>
    /* Garis potong tepat di tengah kertas: 8mm + 136mm + 4.5mm = 148.5mm (sama dengan voucher). */
    @page { margin: 8mm 10mm 4mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #222; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    .slot { height: 136mm; overflow: hidden; }
    .slot-full { height: 282mm; overflow: hidden; }
    .cut { height: 5.5mm; padding-top: 4.5mm; }
    .cut-line { border-top: 1px dashed #888; text-align: center; font-size: 6pt; color: #888; padding-top: 0.5mm; }

    .frame { border: 1px solid #000; }
    .frame td, .frame th { border: 1px solid #000; padding: 0.6mm 1.5mm; vertical-align: top; }
    .bt0 { border-top: none; }
    .frame .inner td { border: none; border-bottom: 1px solid #000; padding: 0.9mm 1.5mm; }
    .frame .inner td.last { border-bottom: none; }
    .frame .info td { border: none; padding: 0.3mm 1mm; }
    .vmid { vertical-align: middle !important; }
    .center { text-align: center; }
    .right { text-align: right; }
    .b { font-weight: bold; }
    .company-name { font-size: 10pt; font-weight: bold; }
    .company-meta { font-size: 6.3pt; line-height: 1.2; }
    .title-doc { text-align: center; font-size: 10.5pt; font-weight: bold; padding: 1mm 0 !important; }
    .dt th { text-align: center; font-weight: bold; }
    .dt td.section { padding: 0.8mm 1.5mm; }
    .printed { margin-top: 1mm; color: #666; font-size: 6pt; }
</style>
</head>
<body>
<div class="<?= $full ? 'slot-full' : 'slot'; ?>">

<table class="frame">
    <tr>
        <td class="center vmid" style="width: 20mm;">
            <?php if ($logo_data): ?><img src="<?= $logo_data; ?>" style="width: 15mm; height: auto;"><?php endif; ?>
        </td>
        <td class="vmid">
            <div class="company-name"><?= htmlspecialchars(company_info('company_name')); ?></div>
            <div class="company-meta"><?= implode('<br>', array_map('htmlspecialchars', company_contact_lines())); ?></div>
        </td>
        <td style="width: 56mm; padding: 0;">
            <table class="inner">
                <tr><td class="b" style="width: 20mm;">No. Dokumen</td><td>: <?= htmlspecialchars($header->doc_no); ?></td></tr>
                <tr><td class="b">Tanggal</td><td>: <?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
                <tr><td class="b last">Mata Uang</td><td class="last">: <?= htmlspecialchars($header->currency); ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="frame bt0">
    <tr><td class="title-doc"><?= htmlspecialchars($title); ?></td></tr>
</table>

<table class="frame bt0">
    <tr>
        <td style="padding: 1.5mm 2.5mm;">
            <table class="info">
                <tr>
                    <td class="b" style="width: 24mm;">Akun Kas/Bank</td><td style="width: 2mm;">:</td><td><?= htmlspecialchars($header->payment_account_name); ?></td>
                    <td class="b" style="width: 12mm;">Kurs</td><td style="width: 2mm;">:</td><td style="width: 40mm;"><?= number_format((float) $header->exchange_rate, 4); ?></td>
                </tr>
                <tr>
                    <td class="b">Keterangan</td><td>:</td><td colspan="4"><?= htmlspecialchars((string) $header->description); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="frame bt0 dt">
    <thead>
        <tr><td colspan="5" class="b section">Detail Transaksi</td></tr>
        <tr>
            <th style="width: 7mm;">No</th>
            <th style="width: 22mm;">Kode Akun</th>
            <th style="width: 48mm;">Nama Akun</th>
            <th>Deskripsi</th>
            <th style="width: 32mm;">Jumlah (<?= htmlspecialchars($header->currency); ?>)</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($details as $d): ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= htmlspecialchars((string) $d->account_code); ?></td>
                <td><?= htmlspecialchars((string) $d->account_name); ?></td>
                <td><?= htmlspecialchars((string) $d->description); ?></td>
                <td class="right"><?= $fmt($d->amount); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($k = count($details); $k < $min_rows; $k++): ?>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="b">Total (<?= htmlspecialchars($header->currency); ?>)</td>
            <td class="right b"><?= $fmt($header->total_amount); ?></td>
        </tr>
        <?php if (!$is_idr): ?>
        <tr>
            <td colspan="4" class="b">(Ekuivalen IDR)</td>
            <td class="right">Rp <?= $fmt($header->total_amount_local); ?></td>
        </tr>
        <?php endif; ?>
    </tfoot>
</table>

<table class="frame" style="margin-top: 1.2mm;">
    <tr>
        <td class="center b" style="width: 25%;">Dibuat Oleh</td>
        <td class="center b" style="width: 25%;">Diperiksa Oleh</td>
        <td class="center b" style="width: 25%;">Disetujui Oleh</td>
        <td class="center b" style="width: 25%;"><?= $sig_last; ?></td>
    </tr>
    <tr><td style="height: <?= $full ? '18mm' : '11mm'; ?>;"></td><td></td><td></td><td></td></tr>
    <tr>
        <td class="center"><?= !empty($header->creator_name) ? htmlspecialchars($header->creator_name) : '&nbsp;'; ?></td>
        <td></td><td></td><td></td>
    </tr>
</table>

<div class="printed">Dicetak pada : <?= date('d/m/Y H:i'); ?> WIB</div>

</div>
<?php if (!$full): ?>
<div class="cut"><div class="cut-line">- - - - - - - - - - - - - - - - potong di sini - - - - - - - - - - - - - - - -</div></div>
<?php endif; ?>
</body>
</html>
