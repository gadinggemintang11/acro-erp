<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perhitungan PPN Purchase Order — SATU tempat.
 *
 * Permintaan klien 2026-10-02:
 *   - PPN EXCLUDE : harga barang belum termasuk PPN.
 *                   PPN dihitung DARI SUBTOTAL, lalu ditambahkan.
 *   - PPN INCLUDE : harga barang sudah termasuk PPN.
 *                   PPN DIAMBIL DARI NILAI TOTAL (subtotal itu sendiri),
 *                   bukan ditambahkan di atasnya.
 *   - Persentasenya bisa diisi sendiri, tidak harus 11%.
 *
 * Sebelum ini perhitungannya tersebar di banyak berkas dan beberapa di
 * antaranya mematok 11% (0.11 / 1.11) walau kolom `ppn_rate` sudah ada dan
 * terisi 10 maupun 11 -- jadi angka di daftar PO, halaman detail, cetak PDF
 * dan export Excel bisa berbeda-beda untuk dokumen yang sama. Semuanya
 * sekarang memanggil fungsi ini.
 */

if (!function_exists('po_ppn_breakdown')) {
    /**
     * @param float      $subtotal  jumlah nilai baris barang (setelah diskon)
     * @param bool|int   $has_ppn   dokumen ini kena PPN atau tidak
     * @param float      $rate      persen PPN, mis. 11 atau 12
     * @param bool|int   $inclusive true = harga sudah termasuk PPN
     *
     * @return array [
     *   'dpp'       => dasar pengenaan pajak (nilai barang tanpa PPN),
     *   'ppn'       => nilai PPN-nya,
     *   'total'     => yang harus dibayar,
     *   'rate'      => persen yang dipakai,
     *   'inclusive' => bool,
     *   'applied'   => bool, true kalau PPN benar-benar dihitung
     * ]
     */
    function po_ppn_breakdown($subtotal, $has_ppn, $rate = 0, $inclusive = false) {
        $subtotal  = (float) $subtotal;
        $rate      = (float) $rate;
        $inclusive = (bool) $inclusive;

        // Tanpa PPN, atau persentasenya tidak masuk akal -> tidak dihitung.
        // (PO lama bisa punya has_ppn=1 tapi ppn_rate 0; lebih baik tidak
        //  memunculkan baris PPN bernilai nol daripada menebak 11%.)
        if (empty($has_ppn) || $rate <= 0) {
            return [
                'dpp'       => round($subtotal, 2),
                'ppn'       => 0.0,
                'total'     => round($subtotal, 2),
                'rate'      => $rate,
                'inclusive' => $inclusive,
                'applied'   => false,
            ];
        }

        if ($inclusive) {
            // Harga sudah termasuk PPN: subtotal ITU totalnya.
            $total = $subtotal;
            $ppn   = $total * $rate / (100 + $rate);
            $dpp   = $total - $ppn;
        } else {
            // Harga belum termasuk PPN: PPN ditambahkan di atas subtotal.
            $dpp   = $subtotal;
            $ppn   = $subtotal * $rate / 100;
            $total = $subtotal + $ppn;
        }

        return [
            'dpp'       => round($dpp, 2),
            'ppn'       => round($ppn, 2),
            'total'     => round($total, 2),
            'rate'      => $rate,
            'inclusive' => $inclusive,
            'applied'   => true,
        ];
    }
}

if (!function_exists('po_ppn_label')) {
    /**
     * Label siap pakai, mis. "PPN 11%" atau "PPN 11% (termasuk)".
     * Dipakai halaman detail, cetak PDF & export supaya tulisannya seragam
     * dan TIDAK lagi menulis "11%" secara harfiah.
     */
    function po_ppn_label($rate, $inclusive = false, $prefix = 'PPN') {
        $rate = (float) $rate;
        $txt  = $prefix . ' ' . rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%';
        return $inclusive ? $txt . ' (termasuk)' : $txt;
    }
}

if (!function_exists('po_ppn_from_header')) {
    /**
     * Pintasan: hitung dari object/array header PO apa adanya.
     * `ppn_inclusive` baru ada setelah migration_po_ppn_inclusive.sql --
     * kalau belum, dianggap 0 (exclude) = perilaku lama.
     */
    function po_ppn_from_header($header, $subtotal) {
        $get = function ($k) use ($header) {
            if (is_array($header))  return $header[$k] ?? null;
            if (is_object($header)) return $header->$k ?? null;
            return null;
        };
        return po_ppn_breakdown(
            $subtotal,
            $get('has_ppn'),
            $get('ppn_rate'),
            $get('ppn_inclusive')
        );
    }
}
