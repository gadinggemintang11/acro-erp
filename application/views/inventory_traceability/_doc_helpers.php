<?php
// Dipakai lima halaman dokumen di modul ini supaya tampilannya seragam.
$fmt_qty   = function ($v) { return number_format((float) $v, 2, ',', '.'); };
$fmt_money = function ($v) { return 'Rp ' . number_format((float) $v, 0, ',', '.'); };
$status_badge = function ($s) {
    $map = [
        'Draft' => 'secondary', 'Submitted' => 'info', 'Approved Head Dept' => 'info', 'Endorsed' => 'info',
        'Approved' => 'success', 'Released' => 'success', 'Rejected' => 'danger', 'Cancelled' => 'danger',
        'Created' => 'secondary', 'Confirmed' => 'success', 'Completed' => 'success', 'Closed' => 'dark',
    ];
    return '<span class="badge badge-' . ($map[$s] ?? 'light') . '">' . htmlspecialchars($s ?? '-') . '</span>';
};
// Sorot baris barang yang sedang ditelusuri, supaya mudah ditemukan di dokumen
// yang isinya banyak baris.
$is_traced = function ($row) use ($item) {
    return $item && (int) ($row->item_id ?? 0) === (int) $item->id && ($row->item_type ?? 'inventory') === 'inventory';
};
$d = function ($v) { return $v ? date('d M Y', strtotime($v)) : '-'; };
