<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration_runner_model — mesin di balik halaman "Migrasi Database".
 *
 * Dibuat 2026-09-06 atas permintaan user: di server production aksesnya lewat
 * cPanel (TIDAK ada shell), jadi `db/deploy_migrations.sh` tidak bisa dipakai
 * di sana, padahal migrasi yang menunggu sudah puluhan.
 *
 * PRINSIP KEAMANAN (sengaja dibuat sempit):
 *  1. TIDAK menerima SQL dari input user sama sekali. Yang bisa dijalankan
 *     HANYA file yang terdaftar di `db/deploy_migrations.sh` — daftar itu
 *     dibaca langsung dari skrip-nya supaya cuma ada SATU sumber kebenaran
 *     (tidak ada daftar kembar yang bisa beda).
 *  2. Nama file divalidasi ketat (`migration_*.sql`, tanpa "/" atau "..").
 *  3. Riwayat dicatat di `schema_migrations` supaya tidak terjalan dua kali.
 *
 * SOAL "SUDAH TERLANJUR DIJALANKAN MANUAL": kondisi DB production tidak
 * diketahui pasti — sebagian migrasi mungkin sudah dijalankan lewat
 * phpMyAdmin. Karena itu galat yang artinya "objeknya memang sudah ada"
 * (kode 1050/1060/1061/1062/1091/1826) TIDAK dianggap gagal, tapi dicatat
 * sebagai `skipped`. Galat lain menghentikan proses.
 *
 * CATATAN JUJUR soal transaksi: MySQL melakukan implicit commit di tiap DDL
 * (CREATE/ALTER TABLE), jadi migrasi yang gagal di tengah TIDAK bisa
 * di-rollback otomatis — perintah sebelumnya sudah terlanjur commit. Karena
 * itu halamannya mewajibkan konfirmasi "sudah backup" dulu.
 */
class Migration_runner_model extends CI_Model {

    /** Galat yang artinya "objek sudah ada" -> dilewati, bukan gagal. */
    private static $ALREADY_EXISTS = [
        1050, // Table already exists
        1060, // Duplicate column name
        1061, // Duplicate key name
        1062, // Duplicate entry (unique)
        1091, // Can't DROP; doesn't exist
        1826, // Duplicate foreign key
    ];

    public function ensure_table() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(190) NOT NULL,
                `status` enum('applied','skipped','failed') NOT NULL DEFAULT 'applied',
                `statements_run` int(11) NOT NULL DEFAULT 0,
                `message` text DEFAULT NULL,
                `applied_by` int(11) DEFAULT NULL,
                `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_filename` (`filename`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    /**
     * Urutan migrasi diambil dari `db/deploy_migrations.sh` (SATU sumber
     * kebenaran). Deskripsi singkat diambil dari tabel di
     * `docs/PENDING_MIGRATIONS_MASTER.md` kalau ada.
     */
    public function list_migrations() {
        $this->ensure_table();

        $files = $this->_files_from_script();
        $desc  = $this->_descriptions_from_doc();

        $done = [];
        foreach ($this->db->get('schema_migrations')->result() as $r) {
            $done[$r->filename] = $r;
        }

        $out = [];
        foreach ($files as $i => $f) {
            $path = FCPATH . 'db/' . $f;
            $row  = $done[$f] ?? null;
            $out[] = (object) [
                'no'        => $i + 1,
                'filename'  => $f,
                'exists'    => is_file($path),
                'size'      => is_file($path) ? filesize($path) : 0,
                'desc'      => $desc[$f] ?? '',
                'status'    => $row ? $row->status : 'pending',
                'message'   => $row->message ?? '',
                'applied_at'=> $row->applied_at ?? null,
                'stmts'     => $row->statements_run ?? 0,
            ];
        }
        return $out;
    }

    /** Nama file valid & memang terdaftar di skrip. */
    public function is_allowed($filename) {
        if (!preg_match('/^migration_[A-Za-z0-9_\-]+\.sql$/', (string) $filename)) return false;
        return in_array($filename, $this->_files_from_script(), true);
    }

    /**
     * Jalankan 1 file migrasi.
     * @return array ['status'=>applied|skipped|failed, 'run'=>int, 'skipped'=>int, 'message'=>string]
     */
    public function run_one($filename, $user_id = null) {
        $this->ensure_table();

        if (!$this->is_allowed($filename)) {
            return ['status' => 'failed', 'run' => 0, 'skipped' => 0,
                    'message' => 'File tidak terdaftar di deploy_migrations.sh — ditolak.'];
        }
        $path = FCPATH . 'db/' . $filename;
        if (!is_file($path)) {
            return ['status' => 'failed', 'run' => 0, 'skipped' => 0, 'message' => 'File tidak ditemukan di folder db/.'];
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            return ['status' => 'failed', 'run' => 0, 'skipped' => 0, 'message' => 'File tidak bisa dibaca.'];
        }

        $statements = $this->split_statements($sql);
        $run = 0; $skipped = 0; $notes = [];

        // PENTING: dengan `db_debug = TRUE` (default CI3), query yang gagal
        // membuat CI3 memanggil show_error() dan MENGHENTIKAN request —
        // bukan melempar exception, jadi try/catch TIDAK menangkapnya
        // (ketahuan waktu uji migrasi yang kolomnya sudah ada). Dimatikan
        // sementara supaya query() mengembalikan FALSE dan galatnya bisa
        // kita klasifikasikan sendiri; dikembalikan lagi di akhir.
        $prev_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $restore = function () use ($prev_debug) { $this->db->db_debug = $prev_debug; };

        foreach ($statements as $idx => $stmt) {
            $ok = FALSE;
            try {
                $ok = $this->db->query($stmt);
            } catch (\Throwable $e) {
                $ok = FALSE;
            }
            if ($ok !== FALSE) { $run++; continue; }

            $err  = $this->db->error();
            $code = (int) ($err['code'] ?? 0);
            if (in_array($code, self::$ALREADY_EXISTS, true)) {
                $skipped++;
                $notes[] = "perintah #" . ($idx + 1) . ": sudah ada (kode $code), dilewati";
                continue;
            }
            $msg = 'GAGAL di perintah #' . ($idx + 1) . ' (kode ' . $code . '): '
                 . ($err['message'] ?? '-')
                 . ' | ' . $this->_preview($stmt);
            $restore();
            $this->_record($filename, 'failed', $run, $msg, $user_id);
            return ['status' => 'failed', 'run' => $run, 'skipped' => $skipped, 'message' => $msg];
        }

        $restore();

        // Semua perintah cuma "sudah ada" -> migrasi ini memang sudah pernah
        // dijalankan manual sebelumnya.
        $status  = ($run === 0 && $skipped > 0) ? 'skipped' : 'applied';
        $message = trim(implode('; ', $notes));
        $this->_record($filename, $status, $run, $message, $user_id);

        return ['status' => $status, 'run' => $run, 'skipped' => $skipped, 'message' => $message];
    }

    /** Tandai sudah dijalankan TANPA mengeksekusi (utk migrasi yg sudah manual). */
    public function mark_applied($filename, $user_id = null) {
        $this->ensure_table();
        if (!$this->is_allowed($filename)) return false;
        $this->_record($filename, 'skipped', 0, 'Ditandai manual oleh admin (tidak dieksekusi).', $user_id);
        return true;
    }

    public function forget($filename) {
        $this->ensure_table();
        if (!$this->is_allowed($filename)) return false;
        $this->db->where('filename', $filename)->delete('schema_migrations');
        return true;
    }

    /**
     * Pecah isi file jadi perintah-perintah SQL. Menghormati string ber-kutip
     * & komentar (-- , # , /* *\/) supaya titik-koma di dalamnya tidak ikut
     * memotong. Migrasi di repo ini semuanya DDL/DML biasa — tidak ada
     * DELIMITER/trigger/procedure (sudah dicek sebelum fitur ini dibuat).
     */
    public function split_statements($sql) {
        $out = []; $buf = '';
        $len = strlen($sql);
        $in_s = false;      // dalam string '...'
        $in_d = false;      // dalam string "..."
        $in_line = false;   // komentar -- atau #
        $in_block = false;  // komentar /* */

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $n = ($i + 1 < $len) ? $sql[$i + 1] : '';

            if ($in_line)  { if ($c === "\n") { $in_line = false; $buf .= $c; } continue; }
            if ($in_block) { if ($c === '*' && $n === '/') { $in_block = false; $i++; } continue; }

            if (!$in_s && !$in_d) {
                if ($c === '-' && $n === '-') { $in_line = true; $i++; continue; }
                if ($c === '#')               { $in_line = true; continue; }
                if ($c === '/' && $n === '*') { $in_block = true; $i++; continue; }
            }

            if ($c === "'" && !$in_d && !$this->_escaped($sql, $i)) { $in_s = !$in_s; $buf .= $c; continue; }
            if ($c === '"' && !$in_s && !$this->_escaped($sql, $i)) { $in_d = !$in_d; $buf .= $c; continue; }

            if ($c === ';' && !$in_s && !$in_d) {
                $t = trim($buf);
                if ($t !== '') $out[] = $t;
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        $t = trim($buf);
        if ($t !== '') $out[] = $t;
        return $out;
    }

    /** True kalau karakter di posisi $i didahului backslash ganjil (ter-escape). */
    private function _escaped($sql, $i) {
        $n = 0;
        for ($j = $i - 1; $j >= 0 && $sql[$j] === '\\'; $j--) $n++;
        return ($n % 2) === 1;
    }

    // ===================== INTERNAL =====================

    private function _record($filename, $status, $run, $message, $user_id) {
        $data = [
            'filename'       => $filename,
            'status'         => $status,
            'statements_run' => (int) $run,
            'message'        => $message !== '' ? mb_substr($message, 0, 2000) : null,
            'applied_by'     => $user_id ? (int) $user_id : null,
            'applied_at'     => date('Y-m-d H:i:s'),
        ];
        $exists = $this->db->where('filename', $filename)->count_all_results('schema_migrations');
        if ($exists) $this->db->where('filename', $filename)->update('schema_migrations', $data);
        else         $this->db->insert('schema_migrations', $data);
    }

    /** Daftar + URUTAN migrasi, dibaca dari db/deploy_migrations.sh. */
    private function _files_from_script() {
        static $cache = null;
        if ($cache !== null) return $cache;

        $path = FCPATH . 'db/deploy_migrations.sh';
        if (!is_file($path)) return $cache = [];
        $txt = file_get_contents($path);
        if ($txt === false) return $cache = [];

        // ambil isi array MIGRATIONS=( ... )
        if (!preg_match('/MIGRATIONS=\((.*?)\n\)/s', $txt, $m)) return $cache = [];
        preg_match_all('/migration_[A-Za-z0-9_\-]+\.sql/', $m[1], $mm);

        $seen = []; $out = [];
        foreach ($mm[0] as $f) { if (!isset($seen[$f])) { $seen[$f] = true; $out[] = $f; } }
        return $cache = $out;
    }

    /** Deskripsi singkat per file dari docs/PENDING_MIGRATIONS_MASTER.md. */
    private function _descriptions_from_doc() {
        $path = FCPATH . 'docs/PENDING_MIGRATIONS_MASTER.md';
        if (!is_file($path)) return [];
        $txt = file_get_contents($path);
        if ($txt === false) return [];

        $out = [];
        foreach (explode("\n", $txt) as $line) {
            if (strpos($line, '|') === false) continue;
            $cols = array_map('trim', explode('|', $line));
            // format: | no | `file.sql` | tanggal | judul | keterangan |
            if (count($cols) < 6) continue;
            if (!preg_match('/`(migration_[A-Za-z0-9_\-]+\.sql)`/', $cols[2], $m)) continue;
            $out[$m[1]] = $cols[4];
        }
        return $out;
    }

    private function _preview($stmt) {
        $s = preg_replace('/\s+/', ' ', trim($stmt));
        return mb_strlen($s) > 160 ? mb_substr($s, 0, 160) . '…' : $s;
    }
}
