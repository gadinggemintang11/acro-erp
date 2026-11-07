<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Approval_history_model — "Riwayat Approval Saya" untuk SEMUA modul ber-approval.
 *
 * Dibuat 2026-09-06 atas permintaan user: "di situ kan hanya menampilkan list
 * daftar yang akan di approve, nah seharusnya di bagian bawah ada riwayat yang
 * user itu approve." Jadi tiap halaman antrian approval sekarang punya kartu
 * kedua di bawahnya: dokumen apa saja yang SUDAH ditindak oleh user yang
 * sedang login (approve HOD / endorse / approve final / reject).
 *
 * Kenapa 1 model dipakai bersama, bukan 1 query per modul: kolom pelaku
 * approval di SEMUA tabel header ternyata seragam
 * (`hod_approved_by/at`, `endorsed_by/at`, `final_approved_by/at`,
 * `rejected_by/at`) — dicek langsung ke skema 8 tabel sebelum ditulis. Jadi
 * pemanggil cukup kirim nama tabel + nama kolom nomor dokumen + route detail.
 *
 * Sumber datanya sengaja KOLOM HEADER, bukan tabel *_approval_logs — karena
 * tabel log tidak ada di semua modul (mis. IPO/Sales Contract & MRF tidak
 * punya), sedangkan kolom pelaku ini ada di semuanya.
 */
class Approval_history_model extends CI_Model {

    /**
     * Label per tahap. `approved_by` sengaja ditaruh terakhir & dibuang
     * otomatis kalau tabelnya juga punya `final_approved_by` — di modul yang
     * punya keduanya (IRF/PR) approve final mengisi DUA kolom itu sekaligus,
     * kalau tidak dibuang riwayatnya jadi dobel untuk 1 aksi yang sama.
     */
    private static $STAGES = [
        'hod_approved_by'   => ['at' => 'hod_approved_at',   'label' => 'Approve HOD',     'color' => 'info'],
        'endorsed_by'       => ['at' => 'endorsed_at',       'label' => 'Endorse',         'color' => 'primary'],
        'final_approved_by' => ['at' => 'final_approved_at', 'label' => 'Approve Final',   'color' => 'success'],
        'approved_by'       => ['at' => 'approved_at',       'label' => 'Approve',         'color' => 'success'],
        'rejected_by'       => ['at' => 'rejected_at',       'label' => 'Reject',          'color' => 'danger'],
    ];

    /**
     * @param array $cfg   [
     *     'table'  => nama tabel header (wajib),
     *     'number' => kolom nomor dokumen (wajib),
     *     'date'   => kolom tanggal dokumen (opsional),
     *     'where'  => filter tambahan ['kolom' => nilai|array] (opsional,
     *                 dipakai modul yang 1 tabel dipakai beberapa menu —
     *                 mis. po_header/pr_header yang dipisah per `type`),
     * ]
     * @param int   $user_id
     * @param int   $limit
     * @return array baris: id, doc_number, doc_status, doc_date, my_action,
     *                      my_action_color, my_action_at
     */
    public function get_my_history(array $cfg, $user_id, $limit = 15) {
        $user_id = (int) $user_id;
        $table   = $cfg['table']  ?? null;
        $number  = $cfg['number'] ?? null;
        if ($user_id <= 0 || !$table || !$number) return [];
        if (!$this->db->table_exists($table)) return [];

        $date_col = $cfg['date'] ?? null;
        if ($date_col && !$this->db->field_exists($date_col, $table)) $date_col = null;

        $stages = self::$STAGES;
        if ($this->db->field_exists('final_approved_by', $table)) {
            unset($stages['approved_by']);
        }

        // Filter tambahan (mis. type = produksi) — ditempel ke setiap bagian UNION.
        $extra = '';
        foreach (($cfg['where'] ?? []) as $col => $val) {
            if (!$this->db->field_exists($col, $table)) continue;
            if (is_array($val)) {
                if (empty($val)) continue;
                $escaped = implode(',', array_map([$this->db, 'escape'], $val));
                $extra .= " AND `{$col}` IN ({$escaped})";
            } else {
                $extra .= " AND `{$col}` = " . $this->db->escape($val);
            }
        }

        $parts = [];
        foreach ($stages as $by_col => $meta) {
            if (!$this->db->field_exists($by_col, $table))    continue;
            if (!$this->db->field_exists($meta['at'], $table)) continue;

            $parts[] = "SELECT `id`,"
                . " `{$number}` AS doc_number,"
                . " `status` AS doc_status,"
                . ($date_col ? " `{$date_col}` AS doc_date," : " NULL AS doc_date,")
                . ' ' . $this->db->escape($meta['label']) . ' AS my_action,'
                . ' ' . $this->db->escape($meta['color']) . ' AS my_action_color,'
                . " `{$meta['at']}` AS my_action_at"
                . " FROM `{$table}`"
                . " WHERE `{$by_col}` = {$user_id} AND `{$meta['at']}` IS NOT NULL"
                . $extra;
        }
        if (empty($parts)) return [];

        $sql = implode("\nUNION ALL\n", $parts)
             . "\nORDER BY my_action_at DESC LIMIT " . (int) $limit;

        return $this->db->query($sql)->result();
    }
}
