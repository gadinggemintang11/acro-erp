<?php
/**
 * PDF Voucher — kertas A4 PORTRAIT dibagi 2 (permintaan user 2026-11-25):
 * tiap setengah halaman berisi 1 voucher landscape (= A5 landscape), dengan
 * garis potong di tengah. Cetak 1 voucher -> di setengah atas; cetak banyak
 * (Jurnal Umum -> pilih -> "Cetak Voucher") -> 2 voucher per lembar.
 * Voucher dengan baris jurnal terlalu banyak untuk setengah halaman dicetak
 * 1 halaman penuh supaya tidak terpotong.
 *
 * Variabel: $vouchers = [['header' => ..., 'details' => [...]], ...]
 *           (kompatibel lama: $header + $details untuk 1 voucher).
 * Isi 1 voucher: _voucher_block.php
 */
$logo_path = FCPATH . site_asset('logo_pdf', 'assets/img/logo_pdf.png');
$logo_data = file_exists($logo_path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path)) : '';

if (empty($vouchers)) $vouchers = [['header' => $header, 'details' => $details]];

// Perkiraan "baris" voucher (baris jurnal + baris yang membungkus karena teks panjang).
// Terukur: tiap baris jurnal ~5,3mm; >7 baris tidak muat setengah A4 (tanda tangan terpotong).
$HALF_MAX_UNITS = 7;
$units = function ($b) {
    $u = 0;
    foreach ($b['details'] as $d) {
        $len = mb_strlen(trim(($d->account_name ?? '') . ' — ' . ($d->description ?? '')));
        $u += max(1, (int) ceil($len / 56));   // kolom GL Description ~56 karakter per baris
    }
    $u  = max($u, 2);
    $u += max(0, (int) ceil(mb_strlen((string) ($b['header']->description ?? '')) / 50) - 1); // Description ~50 karakter per baris
    if ((float) ($b['header']->wht42_amount ?? 0) > 0) $u++;
    return $u;
};

// Susun halaman: maks 2 voucher setengah-halaman per lembar; voucher besar = 1 lembar penuh.
$pages = []; $cur = [];
foreach ($vouchers as $b) {
    if ($units($b) > $HALF_MAX_UNITS) {
        if ($cur) { $pages[] = $cur; $cur = []; }
        $pages[] = [['b' => $b, 'full' => true]];
        continue;
    }
    $cur[] = ['b' => $b, 'full' => false];
    if (count($cur) === 2) { $pages[] = $cur; $cur = []; }
}
if ($cur) $pages[] = $cur;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /* Garis potong tepat di tengah kertas: 8mm + 136mm + 4.5mm = 148.5mm. Area isi s/d 293mm. */
    @page { margin: 8mm 10mm 4mm 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #222; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    .page-break { page-break-after: always; }
    .slot { height: 136mm; overflow: hidden; }
    .slot-full { height: 282mm; overflow: hidden; }
    /* Tinggi total 10mm (4.5 padding + 5.5). Pakai padding, BUKAN margin-top di anak:
       margin anak "bocor" ke luar kotak (margin collapsing) -> slot kedua terdorong ke halaman baru. */
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
    .title-voucher { text-align: center; font-size: 10.5pt; font-weight: bold; padding: 1mm 0 !important; }
    .jt th { text-align: center; font-weight: bold; }
    .jt td.section { padding: 0.8mm 1.5mm; }
    .ref { margin-top: 1mm; font-size: 6.8pt; }
    .ref .printed { color: #666; font-size: 6pt; }
</style>
</head>
<body>
<?php foreach ($pages as $pi => $slots): $last_page = ($pi === count($pages) - 1); ?>
    <div class="<?= $last_page ? '' : 'page-break'; ?>">
    <?php if (!empty($slots[0]['full'])): ?>
        <div class="slot-full">
            <?php $this->load->view('accounting/summary_ledger/_voucher_block', ['header' => $slots[0]['b']['header'], 'details' => $slots[0]['b']['details'], 'logo_data' => $logo_data, 'full' => true]); ?>
        </div>
    <?php else: ?>
        <div class="slot">
            <?php $this->load->view('accounting/summary_ledger/_voucher_block', ['header' => $slots[0]['b']['header'], 'details' => $slots[0]['b']['details'], 'logo_data' => $logo_data, 'full' => false]); ?>
        </div>
        <div class="cut"><div class="cut-line">- - - - - - - - - - - - - - - - potong di sini - - - - - - - - - - - - - - - -</div></div>
        <?php if (!empty($slots[1])): ?>
        <div class="slot">
            <?php $this->load->view('accounting/summary_ledger/_voucher_block', ['header' => $slots[1]['b']['header'], 'details' => $slots[1]['b']['details'], 'logo_data' => $logo_data, 'full' => false]); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
<?php endforeach; ?>
</body>
</html>
