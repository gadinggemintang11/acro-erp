<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * ATUR TATA LETAK PDF — Developer Mode (2026-09-10)
 * ------------------------------------------------------------
 * User minta Developer Mode bisa mengatur template PDF PRF/ERF: "bisa atur
 * letak digeser-geser dan dibesarkan font atau dll".
 *
 * KENAPA KONTROL ANGKA, BUKAN KANVAS GESER-GESER:
 *
 *  1. Template PRF & ERF itu TABEL HTML (PRF cuma 3 <table> untuk seluruh
 *     halaman), bukan elemen berkoordinat. Membuatnya bisa digeser bebas
 *     berarti menulis ulang jadi posisi absolut — dan begitu absolut, baris
 *     item TIDAK lagi tumbuh sendiri mengikuti jumlah barang, header/footer
 *     berulang hilang, dan page-break harus dihitung manual.
 *
 *  2. Mpdf di proyek ini SUDAH terbukti tidak andal untuk posisi absolut:
 *     overlay teks di dalam sel tabel gagal (lihat catatan di
 *     document_pdf_helper.php ~baris 381, garis "DATE" di cap COMPLETED
 *     terpaksa dibiarkan kosong).
 *
 *  3. Editor geser-geser butuh preview langsung. Merender Mpdf tiap kali
 *     digeser terlalu lambat, jadi preview-nya pasti pakai HTML biasa —
 *     yang hasilnya BEDA dengan Mpdf. User mengatur di layar lalu cetakannya
 *     bergeser. Lebih buruk daripada tidak ada fitur.
 *
 * Dengan kontrol angka, alur halaman tetap diurus Mpdf sendiri, jadi
 * pengaturan user tidak bisa merusak pagination.
 *
 * CATATAN TEKNIS: nilai TIDAK dikirim sebagai CSS variable (var(--x)) —
 * Mpdf tidak mendukung custom property. Angkanya di-interpolasi PHP langsung
 * ke dalam blok <style> template.
 *
 * Aman sebelum migrasi: kalau tabel `pdf_layout_overrides` belum ada,
 * semua fungsi di sini balik ke nilai bawaan (PDF tampil persis seperti
 * sebelum fitur ini ada).
 * ============================================================
 */

if (!function_exists('pdf_layout_templates')) {
    /**
     * Template yang bisa diatur + daftar setelannya.
     *
     * Tiap setelan: label, nilai bawaan, satuan, batas min/max, dan
     * penjelasan singkat. Batas min/max BUKAN formalitas — font terlalu besar
     * bikin dokumen jadi 2 halaman (PRF A5-Landscape sudah pernah kena, lihat
     * $min_rows di prf/pdf_template.php), font terlalu kecil tidak terbaca.
     */
    function pdf_layout_templates() {
        $common = [
            'font_body'   => ['group' => 'head', 'label' => 'Ukuran tulisan isi',   'default' => 11, 'min' => 6,  'max' => 16, 'unit' => 'px',
                              'hint'  => 'Teks umum di seluruh dokumen.'],
            'font_title'  => ['group' => 'head', 'label' => 'Ukuran tulisan judul', 'default' => 14, 'min' => 8,  'max' => 24, 'unit' => 'px',
                              'hint'  => 'Judul besar di tengah atas.'],
            'font_table'  => ['group' => 'table', 'label' => 'Ukuran tulisan tabel', 'default' => 10, 'min' => 5,  'max' => 14, 'unit' => 'px',
                              'hint'  => 'Paling berpengaruh ke panjang dokumen.'],
            'cell_pad'    => ['group' => 'table', 'label' => 'Kerapatan baris tabel', 'default' => 4,  'min' => 0,  'max' => 12, 'unit' => 'px',
                              'hint'  => 'Kerapatan baris. Kecilkan kalau item banyak.'],
            // Margin kiri/kanan bebas diatur. ATAS & BAWAH sengaja cuma
            // "tambahan", bukan nilai mutlak: keduanya dihitung dari TINGGI
            // header/footer letterhead yang berulang di tiap halaman
            // (render_pdf_letterhead_header_margin()). Kalau diisi bebas dan
            // kekecilan, isi dokumen akan ketimpa kop surat.
            'margin_lr'       => ['group' => 'foot', 'label' => 'Jarak tepi kiri & kanan', 'default' => 6.6, 'min' => 0, 'max' => 40, 'unit' => 'mm',
                                  'hint'  => 'Bawaan 6,6 mm (25px).'],
            'margin_extra_top'    => ['group' => 'foot', 'label' => 'Tambahan jarak atas',  'default' => 0, 'min' => 0, 'max' => 30, 'unit' => 'mm',
                                      'hint'  => 'DITAMBAHKAN di atas ruang kop surat, bukan menggantikannya.'],
            'margin_extra_bottom' => ['group' => 'foot', 'label' => 'Tambahan jarak bawah', 'default' => 0, 'min' => 0, 'max' => 30, 'unit' => 'mm',
                                      'hint'  => 'DITAMBAHKAN di atas ruang footer alamat.'],
            'sig_height'  => ['group' => 'foot', 'label' => 'Tinggi kotak tanda tangan', 'default' => 110, 'min' => 30, 'max' => 200, 'unit' => 'px',
                              'hint'  => 'Ruang kosong untuk tanda tangan di blok approval.'],
            // "Tata letak" bagian atas: lebar kolom label (DATE/DEPARTMENT/dst)
            // di tabel keterangan. Varian A5/Portrait ikut mengecil dgn selisih
            // tetap seperti template aslinya, jadi satu slider cukup.
            'info_label_w' => ['group' => 'head', 'label' => 'Lebar kolom label keterangan', 'default' => 115, 'min' => 60, 'max' => 220, 'unit' => 'px',
                               'hint'  => 'Kolom kiri berisi DATE, DEPARTMENT, dan sejenisnya. Besarkan kalau labelnya terpotong.'],
        ];

        // Lebar kolom tabel rincian. Nilainya PERBANDINGAN, bukan persen mutlak:
        // template menormalkan totalnya jadi 100% sendiri, jadi tiap slider bisa
        // digeser bebas tanpa harus dihitung supaya pas 100 (user 2026-09-14:
        // "kolom, besar kolom harus bisa diatur").
        $col_hint = 'Perbandingan lebar antar kolom. Tidak harus berjumlah 100, sisanya dihitung otomatis.';
        $cols = function (array $defs) use ($col_hint) {
            $out = [];
            foreach ($defs as $key => [$label, $default]) {
                $out[$key] = ['group' => 'cols', 'label' => $label, 'default' => $default,
                              'min' => 2, 'max' => 70, 'unit' => '%', 'hint' => $col_hint];
            }
            return $out;
        };

        return [
            'prf' => [
                'label'    => 'Payment Request — Payment Request Form',
                'route'    => 'prf/export_pdf',
                'settings' => $common + [
                    'min_rows' => ['group' => 'table', 'label' => 'Baris kosong pengisi', 'default' => 0, 'min' => 0, 'max' => 20, 'unit' => 'baris',
                                   'hint' => '0 = ikut bawaan per ukuran kertas (A4-L 5, A4-P 10, A5-L 3, A5-P 5). Isi angka untuk memaksa jumlah baris pengisi — kecilkan kalau dokumen jadi 2 halaman.'],
                ] + $cols([
                    'col_no'       => ['Kolom NO',          6],
                    'col_desc'     => ['Kolom DESCRIPTION', 42],
                    'col_qty'      => ['Kolom QTY',         9],
                    'col_unit'     => ['Kolom UNIT',        9],
                    'col_price'    => ['Kolom UNIT PRICE',  17],
                    'col_subtotal' => ['Kolom SUBTOTAL',    17],
                ]),
            ],
            'erf' => [
                'label'    => 'Reimburse Request — Expense Request Form',
                'route'    => 'erf/export_pdf',
                'settings' => $common + $cols([
                    'col_no'       => ['Kolom NO',           5],
                    'col_desc'     => ['Kolom DESCRIPTION',  35],
                    'col_date'     => ['Kolom EXPENSE DATE', 10],
                    'col_category' => ['Kolom CATEGORY',     13],
                    'col_price'    => ['Kolom UNIT PRICE',   16],
                    'col_amount'   => ['Kolom AMOUNT',       16],
                ]),
            ],
        ];
    }
}

if (!function_exists('pdf_layout_hotspots')) {
    /**
     * Peta "teks di PDF -> setelan mana" untuk editor klik-per-elemen.
     *
     * User 2026-09-14: "setiap element bisa di klik di teksnya jangan
     * 'bagian atas' dll, kurang detail". Sebelumnya editor cuma punya 3 kotak
     * persentase kasar (0-26%, 26-72%, 72-100%) yang ditebak manual dan tidak
     * ikut bergeser waktu setelan diubah.
     *
     * Sekarang posisi tiap tulisan diambil dari PDF.js getTextContent(), jadi
     * kotak klik-nya PERSIS menempel di teks yang sedang tercetak, ikut
     * bergerak sendiri kalau font/lebar kolom diubah. Yang perlu didefinisikan
     * di sini cuma pemetaan teks -> setting_key.
     *
     * URUTAN PENTING: dievaluasi dari atas, yang pertama cocok dipakai.
     * "UNIT PRICE" HARUS di atas "UNIT", "EXPENSE DATE" di atas "DATE".
     *
     * m = 'exact' dipakai untuk teks pendek yang rawan nyangkut di kata lain
     * (mis. "NO").
     */
    function pdf_layout_hotspots($template_key) {
        // Blok tanda tangan & label keterangan sama di kedua template.
        $sign = [
            ['t' => 'APPLICANT',     'k' => 'sig_height'],
            ['t' => 'HEAD OF DEPT',  'k' => 'sig_height'],
            ['t' => 'ENDORSE',       'k' => 'sig_height'],
            ['t' => 'APPROVED BY',   'k' => 'sig_height'],
        ];
        $info = [
            ['t' => 'DEPARTMENT', 'k' => 'info_label_w'],
            ['t' => 'PENERIMA',   'k' => 'info_label_w'],
            ['t' => 'VENDOR',     'k' => 'info_label_w'],
            ['t' => 'ACCOUNT',    'k' => 'info_label_w'],
            ['t' => 'NAME',       'k' => 'info_label_w'],
            ['t' => 'BANK',       'k' => 'info_label_w'],
            ['t' => 'DATE',       'k' => 'info_label_w'],
        ];

        if ($template_key === 'prf') {
            return array_merge([
                ['t' => 'PAYMENT REQUEST', 'k' => 'font_title'],
                ['t' => 'UNIT PRICE',  'k' => 'col_price'],
                ['t' => 'SUBTOTAL',    'k' => 'col_subtotal'],
                ['t' => 'DESCRIPTION', 'k' => 'col_desc'],
                ['t' => 'QTY',         'k' => 'col_qty'],
                ['t' => 'UNIT',        'k' => 'col_unit'],
                ['t' => 'NO',          'k' => 'col_no', 'm' => 'exact'],
                ['t' => 'TOTAL',       'k' => 'font_table'],
            ], $sign, $info);
        }

        if ($template_key === 'erf') {
            return array_merge([
                ['t' => 'REIMBURSE REQUEST', 'k' => 'font_title'],
                ['t' => 'UNIT PRICE',   'k' => 'col_price'],
                ['t' => 'EXPENSE DATE', 'k' => 'col_date'],
                ['t' => 'DESCRIPTION',  'k' => 'col_desc'],
                ['t' => 'CATEGORY',     'k' => 'col_category'],
                ['t' => 'GRAND TOTAL',  'k' => 'font_table'],
                ['t' => 'AMOUNT',       'k' => 'col_amount'],
                ['t' => 'NO',           'k' => 'col_no', 'm' => 'exact'],
            ], $sign, $info);
        }

        return [];
    }
}

if (!function_exists('pdf_layout_groups')) {
    /** Pengelompokan setelan — dipakai editor Preview Langsung. */
    function pdf_layout_groups() {
        return [
            'head'  => ['label' => 'Bagian Atas — judul & keterangan', 'icon' => 'fa-heading',
                        'hint'  => 'Judul besar, subjudul, dan tabel keterangan di atas.'],
            'table' => ['label' => 'Tabel Rincian',                     'icon' => 'fa-table',
                        'hint'  => 'Paling berpengaruh ke jumlah halaman.'],
            'cols'  => ['label' => 'Lebar Kolom Tabel',                 'icon' => 'fa-columns',
                        'hint'  => 'Perbandingan lebar tiap kolom. Totalnya dinormalkan otomatis jadi 100%.'],
            'foot'  => ['label' => 'Tanda Tangan & Tepi Kertas',        'icon' => 'fa-signature',
                        'hint'  => 'Kotak tanda tangan dan jarak ke tepi kertas.'],
        ];
    }
}

if (!function_exists('pdf_layout_ready')) {
    /** Tabel override sudah ada? (aman dipanggil sebelum migrasi) */
    function pdf_layout_ready() {
        static $ready = null;
        if ($ready !== null) return $ready;
        $CI =& get_instance();
        $ready = $CI->db->table_exists('pdf_layout_overrides');
        return $ready;
    }
}

if (!function_exists('pdf_layout')) {
    /**
     * Setelan efektif 1 template: bawaan + override dari DB, sudah dijepit
     * ke batas min/max supaya nilai nyeleneh di DB tidak merusak dokumen.
     *
     * @return array<string,float> setting_key => nilai
     */
    function pdf_layout($template_key) {
        static $cache = [];

        $preview = pdf_layout_preview_values($template_key);

        // Mode preview tidak boleh ikut di-cache: nilainya berubah tiap kali
        // user menggeser slider.
        if (empty($preview) && isset($cache[$template_key])) return $cache[$template_key];

        $templates = pdf_layout_templates();
        $spec = $templates[$template_key]['settings'] ?? [];

        $out = [];
        foreach ($spec as $k => $meta) { $out[$k] = $meta['default']; }

        if (!empty($spec) && pdf_layout_ready()) {
            $CI =& get_instance();
            $rows = $CI->db->where('template_key', $template_key)->get('pdf_layout_overrides')->result();
            foreach ($rows as $r) {
                if (!isset($spec[$r->setting_key])) continue;   // setelan tak dikenal -> abaikan
                $v = (float) $r->setting_value;
                $v = max($spec[$r->setting_key]['min'], min($spec[$r->setting_key]['max'], $v));
                $out[$r->setting_key] = $v;
            }
        }

        // Nilai preview menimpa paling akhir (belum tersimpan ke DB).
        foreach ($preview as $k => $v) {
            if (!isset($spec[$k])) continue;
            $out[$k] = max($spec[$k]['min'], min($spec[$k]['max'], (float) $v));
        }

        if (empty($preview)) { $cache[$template_key] = $out; }
        return $out;
    }
}

if (!function_exists('pdf_layout_margins')) {
    /**
     * Margin siap dioper ke constructor Mpdf.
     * DIPISAH dari pdf_layout() karena margin TIDAK boleh lewat CSS: rule
     * @page apa pun bikin Mpdf reset margin_top/bottom ke bawaan dan
     * menggagalkan SetHTMLHeader()/SetHTMLFooter() — sudah kejadian, lihat
     * catatan di prf/pdf_template.php.
     */
    function pdf_layout_margins($template_key, $computed_top = null, $computed_bottom = null) {
        $L = pdf_layout($template_key);
        $top    = ($computed_top    === null) ? 10 : (float) $computed_top;
        $bottom = ($computed_bottom === null) ? 10 : (float) $computed_bottom;
        return [
            'margin_top'    => round($top    + (float) ($L['margin_extra_top']    ?? 0), 1),
            'margin_bottom' => round($bottom + (float) ($L['margin_extra_bottom'] ?? 0), 1),
            'margin_left'   => (float) ($L['margin_lr'] ?? 6.6),
            'margin_right'  => (float) ($L['margin_lr'] ?? 6.6),
        ];
    }
}


if (!function_exists('pdf_layout_preview_values')) {
    /**
     * Nilai "belum disimpan" untuk mode Preview Langsung di Developer Mode.
     *
     * Disimpan di SESSION lalu dokumennya dibuka lewat jalur export PDF yang
     * ASLI (Prf::export_pdf / Erf::export_pdf) — bukan renderer tiruan —
     * supaya yang dilihat user benar-benar sama dengan yang tercetak.
     *
     * Sengaja DIKUNCI DUA LAPIS supaya tidak pernah bocor ke cetakan biasa:
     *   1. cuma dibaca kalau request membawa ?pdf_preview=1, dan
     *   2. isinya ditulis hanya oleh Developer_mode::preview_pdf().
     * Export PDF normal (tanpa parameter itu) mengabaikannya total.
     */
    function pdf_layout_preview_values($template_key) {
        $CI =& get_instance();
        if (!isset($CI->input) || !$CI->input->get('pdf_preview')) return [];
        if (!isset($CI->session)) return [];
        $all = $CI->session->userdata('pdf_layout_preview');
        if (!is_array($all) || empty($all[$template_key]) || !is_array($all[$template_key])) return [];
        return $all[$template_key];
    }
}

if (!function_exists('pdf_layout_set_preview')) {
    /** Simpan nilai preview (dipanggil Developer_mode::preview_pdf saja). */
    function pdf_layout_set_preview($template_key, array $values) {
        $CI =& get_instance();
        $all = $CI->session->userdata('pdf_layout_preview');
        if (!is_array($all)) $all = [];
        $all[$template_key] = $values;
        $CI->session->set_userdata('pdf_layout_preview', $all);
    }
}

if (!function_exists('pdf_layout_save')) {
    /** Simpan 1 setelan. Nilai sama dengan bawaan -> barisnya DIHAPUS. */
    function pdf_layout_save($template_key, $setting_key, $value, $user_id = null) {
        if (!pdf_layout_ready()) return false;

        $templates = pdf_layout_templates();
        $spec = $templates[$template_key]['settings'][$setting_key] ?? null;
        if (!$spec) return false;

        $CI =& get_instance();
        $v = max($spec['min'], min($spec['max'], (float) $value));

        // Kembali ke bawaan = tidak perlu menyimpan apa-apa. Tabel tetap
        // ramping & "Reset" tinggal menghapus baris.
        if (abs($v - (float) $spec['default']) < 0.0001) {
            $CI->db->where(['template_key' => $template_key, 'setting_key' => $setting_key])
                   ->delete('pdf_layout_overrides');
            return true;
        }

        $exist = $CI->db->where(['template_key' => $template_key, 'setting_key' => $setting_key])
                        ->get('pdf_layout_overrides')->row();
        $data = [
            'setting_value' => (string) $v,
            'updated_by'    => $user_id,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if ($exist) {
            $CI->db->where('id', $exist->id)->update('pdf_layout_overrides', $data);
        } else {
            $CI->db->insert('pdf_layout_overrides', $data + [
                'template_key' => $template_key,
                'setting_key'  => $setting_key,
            ]);
        }
        return true;
    }
}

if (!function_exists('pdf_layout_reset')) {
    /** Hapus semua override 1 template -> balik ke bawaan. */
    function pdf_layout_reset($template_key) {
        if (!pdf_layout_ready()) return false;
        $CI =& get_instance();
        $CI->db->where('template_key', $template_key)->delete('pdf_layout_overrides');
        return true;
    }
}
