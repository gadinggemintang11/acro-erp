<?php
/**
 * Fungsi kecil tampilan Proses Produksi (dipakai index & halaman tahap).
 * Dibungkus function_exists karena partial bisa di-load lebih dari sekali.
 */
if (!function_exists('pp_stage_meta')) {
    /** Ikon & nada warna per tahap (kelas .tone-* di _pp_style.php). */
    function pp_stage_meta($stage = null)
    {
        $meta = [
            'cutting'    => ['icon' => 'fa-cut',          'tone' => 'tone-cutting'],
            'sewing'     => ['icon' => 'fa-tshirt',       'tone' => 'tone-sewing'],
            'assembling' => ['icon' => 'fa-puzzle-piece', 'tone' => 'tone-assembling'],
            'finishing'  => ['icon' => 'fa-box-open',     'tone' => 'tone-finishing'],
        ];
        if ($stage === null) return $meta;
        return $meta[$stage] ?? ['icon' => 'fa-cogs', 'tone' => 'tone-slate'];
    }
}

if (!function_exists('pp_fmt_dt')) {
    /** 2026-11-08 09:30:00 -> "08 Nov 2026 09:30" (kosong -> $empty). */
    function pp_fmt_dt($dt, $empty = '-', $with_time = true)
    {
        if (empty($dt) || strpos((string) $dt, '0000-00-00') === 0) return $empty;
        $ts = strtotime($dt);
        return $ts ? date($with_time ? 'd M Y H:i' : 'd M Y', $ts) : $empty;
    }
}

if (!function_exists('pp_fmt_range')) {
    /** Rentang ringkas: "08 Nov 09:30 – 15:00" (tanggal sama) / "08 Nov 09:30 – 09 Nov 11:00". */
    function pp_fmt_range($start, $end)
    {
        if (empty($start)) return '';
        $s = strtotime($start);
        $txt = date('d M Y H:i', $s);
        if (!empty($end)) {
            $e = strtotime($end);
            $txt .= ' – ' . (date('Y-m-d', $s) === date('Y-m-d', $e) ? date('H:i', $e) : date('d M Y H:i', $e));
        }
        return $txt;
    }
}

if (!function_exists('pp_duration')) {
    /** Durasi "2 hari 3 jam" / "5 jam 20 mnt" / "45 mnt". */
    function pp_duration($start, $end)
    {
        if (empty($start) || empty($end)) return '';
        $sec = strtotime($end) - strtotime($start);
        if ($sec <= 0) return '0 ' . lang_line('pp2_unit_min', 'mnt');
        $d = intdiv($sec, 86400); $h = intdiv($sec % 86400, 3600); $m = intdiv($sec % 3600, 60);
        $parts = [];
        if ($d) $parts[] = $d . ' ' . lang_line('pp2_unit_day', 'hari');
        if ($h) $parts[] = $h . ' ' . lang_line('pp2_unit_hour', 'jam');
        if ($m && !$d) $parts[] = $m . ' ' . lang_line('pp2_unit_min', 'mnt');
        return implode(' ', $parts) ?: '0 ' . lang_line('pp2_unit_min', 'mnt');
    }
}

if (!function_exists('pp_status_pill')) {
    /** Pill status dokumen produksi. */
    function pp_status_pill($status)
    {
        $map = [
            'Draft'       => ['pp-status--draft', 'pp_status_draft'],
            'In Progress' => ['pp-status--progress', 'pp_status_in_progress'],
            'Completed'   => ['pp-status--completed', 'pp_status_completed'],
        ];
        $cls   = $map[$status][0] ?? 'pp-status--other';
        $label = isset($map[$status]) ? lang_line($map[$status][1]) : $status;
        return '<span class="pp-status ' . $cls . '">' . htmlspecialchars((string) $label) . '</span>';
    }
}

if (!function_exists('pp_qty')) {
    /** Angka qty tanpa nol berlebih: 10 -> "10", 2.5 -> "2,5", 0.1234 -> "0,1234". */
    function pp_qty($n, $max_dec = 4)
    {
        $n = (float) $n;
        $s = number_format($n, $max_dec, ',', '.');
        if (strpos($s, ',') !== false) $s = rtrim(rtrim($s, '0'), ',');
        return $s;
    }
}
