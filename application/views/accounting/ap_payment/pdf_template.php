<?php
/**
 * PDF Bukti Pembayaran Utang. Desain sama dengan Bukti Kas/Bank
 * (cash_bank/pdf_detail_view.php) yang mengikuti Voucher Summary Ledger,
 * permintaan user 2026-11-26: kertas A4 PORTRAIT dibagi 2 -> bukti landscape
 * di setengah atas + garis potong. Isi = data pembayaran yang sama seperti sebelumnya.
 * Faktur terlalu banyak untuk setengah halaman -> dicetak 1 halaman penuh.
 *
 * Variabel: $title, $header (ApPayment_model::get_header_by_id), $details.
 */
$logo_path = FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png');
$logo_data = file_exists($logo_path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path)) : '';

$fmt = function ($v) { return number_format((float) $v, 2, '.', ','); };

// Perkiraan tinggi tabel faktur (1 baris per faktur) + Catatan yang membungkus (~60 karakter/baris).
$units    = count($details) + max(0, (int) ceil(mb_strlen((string) $header->notes) / 60) - 1);
$full     = ($units > 9);
$min_rows = $full ? 6 : 2; // baris kosong minimal di tabel faktur
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
        <td style="width: 64mm; padding: 0;">
            <table class="inner">
                <tr><td class="b" style="width: 25mm;">No. Pembayaran</td><td>: <?= htmlspecialchars($header->payment_no); ?></td></tr>
                <tr><td class="b last">Tanggal Bayar</td><td class="last">: <?= date('d M Y', strtotime($header->payment_date)); ?></td></tr>
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
                    <td class="b" style="width: 31mm;">Dibayarkan Kepada</td><td style="width: 2mm;">:</td><td><?= htmlspecialchars((string) $header->nama_supplier); ?></td>
                    <td class="b" style="width: 17mm;">Akun Bayar</td><td style="width: 2mm;">:</td><td style="width: 55mm;"><?= htmlspecialchars((string) $header->payment_account_name); ?></td>
                </tr>
                <tr>
                    <td class="b">Catatan</td><td>:</td><td colspan="4"><?= htmlspecialchars($header->notes ?: '-'); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="frame bt0 dt">
    <thead>
        <tr><td colspan="5" class="b section">Detail Faktur yang Dibayar</td></tr>
        <tr>
            <th style="width: 7mm;">No</th>
            <th>No. Faktur</th>
            <th style="width: 28mm;">Tgl. Faktur</th>
            <th style="width: 36mm;">Total Faktur</th>
            <th style="width: 36mm;">Jumlah Dibayar</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($details as $d): ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= htmlspecialchars((string) $d->invoice_no); ?></td>
                <td class="center"><?= date('d M Y', strtotime($d->invoice_date)); ?></td>
                <td class="right"><?= $fmt($d->total_invoice); ?></td>
                <td class="right b"><?= $fmt($d->amount_paid); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($k = count($details); $k < $min_rows; $k++): ?>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="b">Total Pembayaran</td>
            <td class="right b"><?= $fmt($header->total_amount); ?></td>
        </tr>
    </tfoot>
</table>

<table class="frame" style="margin-top: 1.2mm;">
    <tr>
        <td class="center b" style="width: 25%;">Dibuat Oleh</td>
        <td class="center b" style="width: 25%;">Diperiksa Oleh</td>
        <td class="center b" style="width: 25%;">Disetujui Oleh</td>
        <td class="center b" style="width: 25%;">Penerima</td>
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
