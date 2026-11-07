<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * AUTH HELPER UNTUK NEOKABER SYSTEM
 * ------------------------------------------------------------
 * Berisi fungsi:
 *  - check_login()   → memastikan user login
 *  - check_access()  → validasi hak akses role ke modul
 * ============================================================
 */

if (!function_exists('check_login')) {
    /**
     * Cek apakah user sudah login.
     * Jika belum, redirect ke halaman login.
     * Juga validasi session_token untuk single device login.
     * Juga cek apakah harus ganti password (first login).
     */
    function check_login()
    {
        $CI =& get_instance();
        if (!$CI->session->userdata('logged_in')) {
            $CI->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth/login');
        }

        // Validasi session_token (single device login protection)
        $session_token = $CI->session->userdata('session_token');
        $user_id = $CI->session->userdata('user_id');

        if ($session_token && $user_id) {
            // Kolom approval_status baru ada setelah migrasi user_type
            // (#35). Di DB yang belum dimigrasi, kolomnya dilewati supaya
            // login & navigasi tetap jalan — lihat db_has_column().
            $has_approval = db_has_column('users', 'approval_status');
            $db_token = $CI->db->select($has_approval ? 'session_token, approval_status' : 'session_token')
                               ->where('id', $user_id)
                               ->get('users')
                               ->row();

            if (!$db_token || $db_token->session_token !== $session_token) {
                // Token tidak cocok → user telah login dari device lain
                $CI->session->sess_destroy();
                $CI->session->set_flashdata('force_logout', true);
                redirect('auth/login');
            }

            // Defense-in-depth: kalau akun ini ke-reject SETELAH sesi login
            // sedang berjalan (mis. perubahan data-nya sendiri ditolak
            // Accounting/Director), putus sesi di request berikutnya —
            // bukan cuma dicegat pas login awal. Lihat memory
            // user_type_approval_feature.md.
            if ($has_approval && $db_token && ($db_token->approval_status ?? '') === 'rejected') {
                $CI->session->sess_destroy();
                $CI->session->set_flashdata('error', 'Akun Anda ditolak/dinonaktifkan. Silakan hubungi Administrator.');
                redirect('auth/login');
            }
        }

        // Cek apakah user harus ganti password (first login)
        if ($CI->session->userdata('must_change_password')) {
            $current_controller = strtolower($CI->uri->segment(1));
            $current_method = strtolower($CI->uri->segment(2));

            // Hanya boleh akses halaman change_password dan save_password
            if ($current_controller !== 'auth' || ($current_method !== 'change_password' && $current_method !== 'save_password')) {
                redirect('auth/change_password');
            }
        }
    }
}

if (!function_exists('check_access')) {
    /**
     * Cek hak akses role terhadap modul.
     *
     * Permissions di-cache per-request via static var:
     * - First call → 1 query SELECT semua permission untuk role aktif
     * - Subsequent calls → cuma lookup di array (no DB hit)
     *
     * Ini penting karena sidebar memanggil check_access ratusan kali per halaman.
     * Sebelumnya: ~102 query DB per page load. Setelah cache: 1 query saja.
     *
     * @param string $menu_key - key modul misal 'commoncode'
     * @param string $action   - view|add|edit|delete|confirm|head_dept|endorsement|approval
     */
    function check_access($menu_key, $action = 'view')
    {
        static $cache = null;

        $CI =& get_instance();
        // Hak akses = GABUNGAN semua role user (Role Level, 2026-09-15) —
        // 1 user bisa py beberapa role posisi, mis. Logistic-Officer +
        // General Warehouse-Supervisor. Lihat current_role_ids().
        $role_ids = current_role_ids();

        if (empty($role_ids)) return false;

        // Build cache once per request: dict { lower(menu_key) => permission_row }.
        // Normalize ke lowercase supaya lookup case-insensitive (mirror MySQL default collation).
        // Penting: ada menu di DB yang disimpan dengan kapital beda (mis. 'Material_issuance'
        // sementara code pakai 'material_issuance'). Tanpa normalize, isset() di PHP gagal.
        if ($cache === null) {
            $cache = [];
            // can_submit baru ada setelah migration_role_submit_permission.sql.
            $has_submit = db_has_column('role_permissions', 'can_submit');
            $rows = $CI->db
                ->select("m.menu_key, rp.can_view, rp.can_add, rp.can_edit, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval, rp.can_delete" . ($has_submit ? ", rp.can_submit" : ""))
                ->from("role_permissions rp")
                ->join("menus m", "rp.menu_id = m.id", "inner")
                ->where_in("rp.role_id", $role_ids)
                ->get()->result();
            foreach ($rows as $r) {
                $key = strtolower($r->menu_key);
                // Menu duplikat (mis. menu_key 'master_material' muncul 2x) ATAU
                // menu yang sama dari beberapa role milik user yang sama.
                // Pakai OR-merge supaya hak akses yang lebih luas menang.
                if (isset($cache[$key])) {
                    $prev = $cache[$key];
                    $prev->can_view        = max($prev->can_view,        $r->can_view);
                    $prev->can_add         = max($prev->can_add,         $r->can_add);
                    $prev->can_edit        = max($prev->can_edit,        $r->can_edit);
                    $prev->can_confirm     = max($prev->can_confirm,     $r->can_confirm);
                    $prev->can_head_dept   = max($prev->can_head_dept,   $r->can_head_dept);
                    $prev->can_endorsement = max($prev->can_endorsement, $r->can_endorsement);
                    $prev->can_approval    = max($prev->can_approval,    $r->can_approval);
                    $prev->can_delete      = max($prev->can_delete,      $r->can_delete);
                    if (isset($r->can_submit)) $prev->can_submit = max($prev->can_submit ?? 0, $r->can_submit);
                } else {
                    $cache[$key] = $r;
                }
            }
        }

        $lookup = strtolower($menu_key);
        if (!isset($cache[$lookup])) return false;
        $permission = $cache[$lookup];

        switch ($action) {
            case 'view': return $permission->can_view == 1;
            case 'add': return $permission->can_add == 1;
            case 'edit': return $permission->can_edit == 1;
            case 'delete': return $permission->can_delete == 1;
            // SUBMIT punya izin sendiri (Role Level tahap 2, 2026-09-15) — PDF
            // ROLE LEVEL membedakan Maker/Submit/Approve. Di DB yang belum
            // dimigrasi kembali ke perilaku lama (can_confirm).
            case 'submit':
                return isset($permission->can_submit)
                    ? $permission->can_submit == 1
                    : (!empty($permission->can_confirm) && $permission->can_confirm == 1);
            // Confirm/approve actions are gated by the dedicated can_confirm flag.
            case 'confirm':
            case 'approve':
            case 'approve_head':
            case 'approve_finance':
            case 'record_payment':
                return !empty($permission->can_confirm) && $permission->can_confirm == 1;
            case 'head_dept':
                return !empty($permission->can_head_dept) && $permission->can_head_dept == 1;
            case 'endorsement':
                return !empty($permission->can_endorsement) && $permission->can_endorsement == 1;
            case 'approval':
                return !empty($permission->can_approval) && $permission->can_approval == 1;
            default: return false;
        }
    }
}

if (!function_exists('is_admin')) {
    /**
     * True kalau SALAH SATU role user yang login adalah Admin (role id 1).
     * Dipakai untuk override khusus mis. force return-to-draft & force delete
     * di modul PRF/ERF/PR/PO/IRF pada status yang biasanya read-only.
     */
    function is_admin() {
        return in_array(1, current_role_ids(), true);
    }
}

/**
 * ============================================================
 * ROLE LEVEL — 1 user bisa punya BANYAK role (departemen x posisi)
 * ------------------------------------------------------------
 * Permintaan klien 2026-09-15 (assets/NSI - ERP ROLE LEVEL.pdf). Role user
 * disimpan di tabel `user_roles`; `users.role_id` tetap ada sebagai "role
 * utama". Semua fungsi di bawah membaca GABUNGAN keduanya, jadi:
 *  - DB yang belum menjalankan migration_role_position.sql tetap jalan
 *    persis seperti dulu (cuma users.role_id).
 *  - Perubahan role langsung berlaku di request berikutnya, tanpa login
 *    ulang (sengaja tidak disimpan di session).
 * ============================================================
 */
if (!function_exists('db_table_exists')) {
    function db_table_exists($table) {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        $CI =& get_instance();
        $CI->load->database();
        try {
            $cache[$table] = $CI->db->table_exists($table);
        } catch (\Throwable $e) {
            $cache[$table] = false;
        }
        return $cache[$table];
    }
}

if (!function_exists('user_role_ids')) {
    /** Semua role id milik 1 user (user_roles + users.role_id), di-cache per request. */
    function user_role_ids($user_id) {
        static $cache = [];
        $user_id = (int) $user_id;
        if ($user_id <= 0) return [];
        if (isset($cache[$user_id])) return $cache[$user_id];

        $CI =& get_instance();
        $CI->load->database();
        $ids = [];
        $row = $CI->db->query('SELECT role_id FROM users WHERE id = ?', [$user_id])->row();
        if ($row && (int) $row->role_id > 0) $ids[] = (int) $row->role_id;
        if (db_table_exists('user_roles')) {
            foreach ($CI->db->query('SELECT role_id FROM user_roles WHERE user_id = ?', [$user_id])->result() as $r) {
                $ids[] = (int) $r->role_id;
            }
        }
        return $cache[$user_id] = array_values(array_unique($ids));
    }
}

if (!function_exists('current_role_ids')) {
    /** Role id user yang sedang login. Fallback ke session role_id (mis. sebelum user_id tersedia). */
    function current_role_ids() {
        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid > 0) {
            $ids = user_role_ids($uid);
            if (!empty($ids)) return $ids;
        }
        $rid = (int) $CI->session->userdata('role_id');
        return $rid > 0 ? [$rid] : [];
    }
}

if (!function_exists('current_role_names')) {
    /** Nama semua role user yang login (urut nama), untuk label & pengecekan berbasis nama role. */
    function current_role_names() {
        static $names = null;
        if ($names !== null) return $names;
        $ids = current_role_ids();
        if (empty($ids)) return $names = [];
        $CI =& get_instance();
        $rows = $CI->db->query('SELECT role_name FROM roles WHERE id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY role_name')->result();
        return $names = array_map(function ($r) { return $r->role_name; }, $rows);
    }
}

if (!function_exists('current_user_has_role_name')) {
    /** True kalau SALAH SATU role user bernama $names (string atau array, case-insensitive). */
    function current_user_has_role_name($names) {
        $want = array_map('strtolower', (array) $names);
        foreach (current_role_names() as $n) {
            if (in_array(strtolower($n), $want, true)) return true;
        }
        return false;
    }
}

if (!function_exists('user_roles_source_sql')) {
    /**
     * Tabel turunan (user_id, role_id) untuk query mentah yang perlu
     * "user mana saja yang punya role X / izin Y". Contoh:
     *   JOIN " . user_roles_source_sql() . " ur ON ur.user_id = u.id
     */
    function user_roles_source_sql() {
        $legacy = 'SELECT id AS user_id, role_id FROM users WHERE role_id IS NOT NULL AND role_id > 0';
        return db_table_exists('user_roles')
            ? '(SELECT user_id, role_id FROM user_roles UNION ' . $legacy . ')'
            : '(' . $legacy . ')';
    }
}

/**
 * ============================================================
 * TIPE USER (users.user_type) — super_admin / executive / accounting / user
 * ------------------------------------------------------------
 * Ditambahkan 2026-09-06. `user_type` TIDAK ikut disimpan di session waktu
 * login (lihat Auth.php), jadi dibaca dari DB & di-cache per-request —
 * sengaja tidak ditaruh di session supaya perubahan tipe user langsung
 * berlaku tanpa user harus login ulang.
 *
 * Aturan yang dijaga (user 2026-09-06):
 *  - Executive boleh membuat user Executive → di-approve Director.
 *  - Super Admin TIDAK boleh membuat user Executive, cuma user biasa →
 *    di-approve Accounting (Director tetap dapat notifikasi).
 *  - Super Admin tidak boleh melihat transaksi user Executive di Log Activity.
 * ============================================================
 */
if (!function_exists('db_has_column')) {
    /**
     * Apakah sebuah kolom SUDAH ada di DB? Hasilnya di-cache per-request.
     *
     * Dipakai supaya JALUR LOGIN tetap hidup di database yang MIGRASINYA
     * BELUM DIJALANKAN. Ditemukan 2026-09-06 waktu user meng-import DB
     * production ke lokal untuk menguji halaman /db_migration: tabel `users`
     * di production belum punya `user_type`/`approval_status`, sedangkan kode
     * baru membacanya saat login DAN di check_login() (tiap halaman) — jadi
     * begitu kode di-deploy, aplikasi mati total dan halaman migrasinya
     * sendiri TIDAK BISA DIBUKA. Ayam-dan-telur yang bikin deploy mustahil.
     *
     * Setelah migrasi dijalankan, semua kembali berperilaku normal.
     */
    function db_has_column($table, $column) {
        static $cache = [];
        $k = $table . '.' . $column;
        if (isset($cache[$k])) return $cache[$k];
        $CI =& get_instance();
        $CI->load->database();
        try {
            $cache[$k] = $CI->db->field_exists($column, $table);
        } catch (\Throwable $e) {
            $cache[$k] = false;
        }
        return $cache[$k];
    }
}

if (!function_exists('current_user_type')) {
    function current_user_type() {
        static $cached = null;
        if ($cached !== null) return $cached;

        $CI  =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid <= 0) return $cached = '';

        $CI->load->database();
        if (!db_has_column('users', 'user_type')) return $cached = 'user';
        $row = $CI->db->select('user_type')->where('id', $uid)->get('users')->row();
        return $cached = ($row && !empty($row->user_type)) ? $row->user_type : 'user';
    }
}

if (!function_exists('is_accounting_user')) {
    /**
     * User bertipe Accounting: melihat SEMUA PRF
     * di halaman List PRF (user 2026-09-22) -- bagian keuangan memang perlu
     * pandangan menyeluruh, bukan cuma dokumen departemennya sendiri.
     */
    function is_accounting_user() {
        return current_user_type() === 'accounting';
    }
}

if (!function_exists('is_super_admin_user')) {
    function is_super_admin_user() {
        return current_user_type() === 'super_admin';
    }
}

/**
 * ============================================================
 * OVERRIDE ADMIN UNTUK EDIT PO PASCA-APPROVE
 * ------------------------------------------------------------
 * Alur normal: PO cuma bisa diedit selama status Draft. Begitu Submitted
 * (menunggu approval) atau Confirmed (approved) semuanya terkunci, dan satu-
 * satunya jalan koreksi adalah return_to_draft -> edit -> submit -> approve
 * ulang. Untuk koreksi kecil (salah remarks, salah kurs, harga typo) siklus
 * itu terlalu mahal, jadi Admin diberi jalur edit langsung.
 *
 * Dipakai bareng oleh 3 modul PO yang strukturnya kembar:
 * purchase_order (produksi), purchase_order_general (jasa), purchase_order_asset.
 * ============================================================
 */

if (!function_exists('po_admin_editable_statuses')) {
    /**
     * Status non-Draft yang masih boleh disentuh Admin.
     * Sengaja TIDAK termasuk Completed/Cancelled/Rejected -- PO di status itu
     * sudah jadi dokumen final atau mati, mengeditnya tidak punya arti bisnis.
     */
    function po_admin_editable_statuses() {
        return ['Submitted', 'Confirmed'];
    }
}

if (!function_exists('can_unlock_po_admin_edit')) {
    /**
     * True kalau user BOLEH membuka kunci edit PO ini (Admin + status terkunci).
     * Cuma menentukan tampil/tidaknya tombol "Buka Mode Edit", bukan izin menulis.
     */
    function can_unlock_po_admin_edit($header) {
        if (!$header || empty($header->status)) return false;
        return is_admin() && in_array($header->status, po_admin_editable_statuses(), true);
    }
}

if (!function_exists('po_admin_edit_requested')) {
    /**
     * True kalau request ini secara eksplisit minta mode edit Admin (flag admin_edit=1).
     *
     * Sengaja TIDAK disimpan di session: PO yang sudah di-approve harus terkunci
     * secara default tiap kali dibuka, supaya Admin tidak sengaja mengubahnya
     * hanya karena kebetulan login sebagai Admin. Flag ikut di URL detail dan
     * ikut dikirim ulang di tiap aksi tulis, jadi POST dari tab lama yang tidak
     * membawa flag akan ditolak.
     */
    function po_admin_edit_requested() {
        $CI =& get_instance();
        return $CI->input->get('admin_edit') === '1' || $CI->input->post('admin_edit') === '1';
    }
}

if (!function_exists('can_edit_po_header')) {
    /**
     * Header PO boleh diedit kalau:
     *  - status Draft dan role punya izin edit modul (alur normal), ATAU
     *  - user Admin, status Submitted/Confirmed, DAN mode edit Admin sudah
     *    diaktifkan eksplisit (lihat po_admin_edit_requested).
     *
     * @param object|null $header  row po_header
     * @param string      $module  menu_key modul, mis. 'purchase_order_asset'
     */
    function can_edit_po_header($header, $module) {
        if (!$header || empty($header->status)) return false;
        if ($header->status === 'Draft') return check_access($module, 'edit');
        return can_unlock_po_admin_edit($header) && po_admin_edit_requested();
    }
}

if (!function_exists('can_edit_po_items')) {
    /**
     * Baris item mengikuti izin header, TAPI override Admin gugur begitu barang
     * sudah diterima: qty/harga baris yang sudah punya GRN (atau unit yang sudah
     * masuk asset_register) dipakai sebagai acuan penerimaan & hutang, jadi
     * mengubah atau menghapusnya bikin data penerimaan menggantung.
     * Header tetap bisa diedit di kondisi ini karena tidak memengaruhi baris.
     *
     * @param bool $has_receipts hasil check_if_po_has_receipts() / check_if_po_has_asset_register()
     */
    function can_edit_po_items($header, $module, $has_receipts = false) {
        if (!can_edit_po_header($header, $module)) return false;
        if ($header->status === 'Draft') return true;
        return !$has_receipts;
    }
}

if (!function_exists('is_po_admin_edit')) {
    /**
     * True kalau mode edit Admin sedang AKTIF: user berhak membukanya dan sudah
     * membukanya lewat tombol. Dipakai untuk banner peringatan di view, untuk
     * menandai baris log, dan sebagai syarat izin di can_edit_po_header().
     */
    function is_po_admin_edit($header) {
        return can_unlock_po_admin_edit($header) && po_admin_edit_requested();
    }
}

/**
 * ============================================================
 * OVERRIDE ADMIN UNTUK EDIT PR PASCA-APPROVE
 * ------------------------------------------------------------
 * Pola persis sama dengan blok PO di atas, cuma statusnya beda: PR terkunci
 * begitu keluar dari Draft, dan satu-satunya jalan koreksi adalah return ke
 * Draft -> edit -> submit -> approve ulang 3 stage. Kalau yang kurang cuma
 * satu item, itu kemahalan. Blok ini memberi Admin pintu untuk menambah /
 * memperbaiki item TANPA mereset approval — status PR tidak disentuh sama
 * sekali, jadi tanda tangan HOD/Endorsement/Final tetap berlaku.
 * ============================================================
 */

if (!function_exists('pr_admin_editable_statuses')) {
    /**
     * Status non-Draft yang masih boleh disentuh Admin.
     * Rejected sengaja TIDAK masuk: PR ditolak itu dokumen mati, koreksinya
     * lewat return-to-draft / bikin PR baru, bukan diedit diam-diam.
     */
    function pr_admin_editable_statuses() {
        return ['Submitted', 'Approved Head Dept', 'Endorsed', 'Approved'];
    }
}

if (!function_exists('can_unlock_pr_admin_edit')) {
    /**
     * True kalau user BOLEH membuka kunci edit PR ini (Admin + status terkunci).
     * Cuma menentukan tampil/tidaknya tombol "Buka Kunci Edit", bukan izin menulis.
     */
    function can_unlock_pr_admin_edit($header) {
        if (!$header || empty($header->status)) return false;
        return is_admin() && in_array($header->status, pr_admin_editable_statuses(), true);
    }
}

if (!function_exists('pr_admin_edit_requested')) {
    /**
     * True kalau request ini secara eksplisit minta mode edit Admin (flag admin_edit=1).
     *
     * Sengaja TIDAK disimpan di session: PR yang sudah di-approve harus terkunci
     * secara default tiap kali dibuka, supaya Admin tidak sengaja mengubahnya
     * hanya karena kebetulan login sebagai Admin. Flag ikut di URL detail dan
     * ikut dikirim ulang di tiap aksi tulis (termasuk AJAX item), jadi POST dari
     * tab lama yang tidak membawa flag akan ditolak.
     */
    function pr_admin_edit_requested() {
        $CI =& get_instance();
        return $CI->input->get('admin_edit') === '1' || $CI->input->post('admin_edit') === '1';
    }
}

if (!function_exists('is_pr_admin_edit')) {
    /**
     * True kalau mode edit Admin sedang AKTIF: user berhak membukanya dan sudah
     * membukanya lewat tombol. Dipakai untuk banner peringatan di view dan
     * sebagai syarat izin di can_edit_pr().
     */
    function is_pr_admin_edit($header) {
        return can_unlock_pr_admin_edit($header) && pr_admin_edit_requested();
    }
}

/**
 * ============================================================
 * OVERRIDE ADMIN UNTUK EDIT PRF PASCA-APPROVE
 * ------------------------------------------------------------
 * Sama semangatnya dengan blok PR di bawah: PRF terkunci begitu keluar dari
 * Draft, dan satu-satunya koreksi resmi adalah Force Return to Draft yang
 * mereset SEMUA approval. Untuk salah satu item / salah nominal itu kemahalan.
 * Mode ini membuka form edit PRF (termasuk "Pull dari PO") tanpa menyentuh
 * status maupun kolom approval — tanda tangan HOD/Endorsement/Final tetap sah.
 *
 * Dua rem yang tidak bisa ditembus, karena keduanya sudah jadi angka di
 * pembukuan dan mengubah PRF-nya bikin data keuangan tidak cocok:
 *  - PRF sudah dibayar sebagian/lunas (payment_status Paid/Partial)
 *  - PRF sudah ditarik jadi Jurnal Umum (journal_header_id terisi)
 * ============================================================
 */

if (!function_exists('prf_admin_editable_statuses')) {
    /**
     * Status non-Draft yang masih boleh disentuh Admin. Termasuk nama status
     * legacy 2-stage ('Head Approved', 'Finance Approved') supaya PRF lama ikut
     * tertolong. Rejected/Cancelled sengaja tidak masuk: itu dokumen mati.
     */
    function prf_admin_editable_statuses() {
        return ['Submitted', 'Approved Head Dept', 'Head Approved', 'Endorsed', 'Approved', 'Finance Approved'];
    }
}

if (!function_exists('prf_admin_edit_blocker')) {
    /**
     * Alasan kenapa PRF ini TIDAK boleh dibuka kuncinya, atau null kalau boleh.
     * Dipakai juga di view untuk menjelaskan ke Admin kenapa tombolnya absen.
     */
    function prf_admin_edit_blocker($header) {
        if (!$header) return 'Payment Request tidak ditemukan.';
        if (in_array($header->payment_status ?? 'Unpaid', ['Paid', 'Partial'], true)) {
            return 'Payment Request ini sudah ada pembayaran (payment status: ' . $header->payment_status . '). Nominalnya dikunci supaya tidak beda dengan kas yang sudah keluar.';
        }
        // Payment Request hutang (tarik PO / AP Invoice) sudah membentuk AP Invoice
        // & jurnal saat approve -- item/nominal dikunci (2026-11-06).
        if (($header->source_type ?? 'MANUAL') === 'HUTANG') {
            return 'Payment Request hutang (dari PO / AP Invoice) sudah terhubung ke daftar hutang & jurnal otomatis. Untuk koreksi, kembalikan ke Draft (jurnal hutang dibalik otomatis) lalu edit.';
        }
        if (!empty($header->journal_header_id)) {
            return 'Payment Request ini sudah ditarik jadi Jurnal Umum. Hapus dulu draft jurnalnya kalau memang harus dikoreksi.';
        }
        return null;
    }
}

if (!function_exists('can_unlock_prf_admin_edit')) {
    /**
     * True kalau user BOLEH membuka kunci edit PRF ini (Admin + status terkunci
     * + tidak ada pembayaran/jurnal). Cuma soal tampil/tidaknya tombol.
     */
    function can_unlock_prf_admin_edit($header) {
        if (!$header || empty($header->status)) return false;
        if (!is_admin()) return false;
        if (!in_array($header->status, prf_admin_editable_statuses(), true)) return false;
        return prf_admin_edit_blocker($header) === null;
    }
}

if (!function_exists('prf_admin_edit_requested')) {
    /**
     * True kalau request ini eksplisit minta mode edit Admin (flag admin_edit=1).
     * Tidak disimpan di session — sama alasannya dengan PO/PR: PRF yang sudah
     * di-approve harus terkunci lagi tiap kali halaman dibuka.
     */
    function prf_admin_edit_requested() {
        $CI =& get_instance();
        return $CI->input->get('admin_edit') === '1' || $CI->input->post('admin_edit') === '1';
    }
}

if (!function_exists('is_prf_admin_edit')) {
    /** True kalau mode edit Admin untuk PRF ini sedang AKTIF. */
    function is_prf_admin_edit($header) {
        return can_unlock_prf_admin_edit($header) && prf_admin_edit_requested();
    }
}

if (!function_exists('erf_admin_editable_statuses')) {
    /**
     * Status non-Draft yang boleh disentuh Admin untuk ERF.
     */
    function erf_admin_editable_statuses() {
        return ['Submitted', 'Approved Head Dept', 'Head Approved', 'Endorsed', 'Approved'];
    }
}

if (!function_exists('erf_admin_edit_blocker')) {
    /**
     * Alasan kenapa ERF tidak boleh dibuka kuncinya (atau null kalau boleh).
     */
    function erf_admin_edit_blocker($header) {
        if (!$header) return 'Reimburse Request tidak ditemukan.';
        if (in_array($header->payment_status ?? 'Unpaid', ['Paid', 'Partial'], true)) {
            return 'Reimburse Request ini sudah ada pembayaran (payment status: ' . ($header->payment_status ?? 'Unknown') . ').';
        }
        return null;
    }
}

if (!function_exists('can_unlock_erf_admin_edit')) {
    /**
     * True kalau user BOLEH membuka kunci edit ERF ini (Admin + status terkunci
     * + tidak ada payment). Cuma untuk tampil/tidaknya tombol.
     */
    function can_unlock_erf_admin_edit($header) {
        if (!$header || empty($header->status)) return false;
        if (!is_admin()) return false;
        if (!in_array($header->status, erf_admin_editable_statuses(), true)) return false;
        return erf_admin_edit_blocker($header) === null;
    }
}

if (!function_exists('erf_admin_edit_requested')) {
    function erf_admin_edit_requested() {
        $CI =& get_instance();
        return $CI->input->get('admin_edit') === '1' || $CI->input->post('admin_edit') === '1';
    }
}

if (!function_exists('is_erf_admin_edit')) {
    function is_erf_admin_edit($header) {
        return can_unlock_erf_admin_edit($header) && erf_admin_edit_requested();
    }
}

if (!function_exists('can_edit_pr')) {
    /**
     * PR boleh diedit (header maupun item) kalau:
     *  - status Draft dan role punya izin edit modul (alur normal), ATAU
     *  - user Admin, status terkunci tapi masih di daftar pr_admin_editable_statuses(),
     *    DAN mode edit Admin sudah diaktifkan eksplisit.
     *
     * Catatan: baris yang sudah ditarik ke PO tetap terkunci walau mode Admin
     * aktif — lihat Purchase_request_model::detail_has_po().
     *
     * @param object|null $header  row pr_header
     * @param string      $module  menu_key modul, mis. 'purchase_request_general'
     */
    function can_edit_pr($header, $module) {
        if (!$header || empty($header->status)) return false;
        if ($header->status === 'Draft') return check_access($module, 'edit');
        return is_pr_admin_edit($header);
    }
}

/**
 * ============================================================
 * THREAD BALASAN "BACK TO DRAFT" (IRF / PRF / ERF / PO)
 * ------------------------------------------------------------
 * Koreksi 2026-09-06 (user: "yang sekarang seperti sudah langsung bisa
 * chatingan, padahal maksud saya baru terbuka kalau di-back to draft, dan
 * yang chat cuma user 1 & user 2").
 *
 * ATURAN LAMA (salah): kotak balasan muncul di SEMUA dokumen berstatus
 * Draft — termasuk draft baru yang belum pernah disubmit — dan boleh diisi
 * SIAPA PUN yang kebetulan punya permission approval modul itu.
 *
 * ATURAN BARU (benar):
 *  1. Thread cuma TERBUKA kalau dokumen ini memang dikembalikan lewat aksi
 *     Back to Draft (`returned_to_draft_at` terisi) — bukan draft baru,
 *     bukan draft hasil unsubmit sendiri oleh pembuatnya.
 *  2. Pesertanya CUMA 2 orang: pembuat/requestor dokumen (user 1) dan
 *     approver yang menekan Back to Draft itu (user 2). Approver lain yang
 *     kebetulan sama-sama punya izin approval TIDAK ikut membalas.
 *  3. Tertutup lagi begitu dokumen disubmit ulang (status bukan Draft).
 *
 * Riwayat/log-nya sendiri tetap bisa DIBACA siapa saja yang boleh membuka
 * halaman detail — yang dibatasi di sini cuma hak MEMBALAS.
 * ============================================================
 */
if (!function_exists('doc_returned_to_draft')) {
    /**
     * True kalau status Draft dokumen ini memang hasil aksi Back to Draft
     * (bukan draft baru / hasil unsubmit). Dibandingkan dengan submitted_at
     * supaya return lama tidak "menghidupkan" lagi thread setelah dokumen
     * disubmit ulang lalu di-unsubmit sendiri oleh pembuatnya.
     */
    function doc_returned_to_draft($header) {
        if (!$header || empty($header->returned_to_draft_at) || empty($header->returned_to_draft_by)) {
            return false;
        }
        $submitted_at = $header->submitted_at ?? null;
        if (empty($submitted_at)) return true;
        return strtotime($header->returned_to_draft_at) >= strtotime($submitted_at);
    }
}

if (!function_exists('can_reply_return_thread')) {
    /**
     * True kalau user yang sedang login boleh MENGIRIM balasan pada thread
     * Back to Draft dokumen ini (lihat aturan lengkap di blok komentar atas).
     *
     * @param object|null $header      row header dokumen (IRF/PRF/ERF/PO/PR)
     * @param string|null $status_now  status efektif kalau pemanggil sudah
     *                                 menormalisasinya sendiri (mis. PRF yang
     *                                 punya status legacy)
     */
    function can_reply_return_thread($header, $status_now = null) {
        if (!$header) return false;

        $status = $status_now ?? ($header->status ?? '');
        if ($status !== 'Draft') return false;
        if (!doc_returned_to_draft($header)) return false;

        $CI =& get_instance();
        $uid = (int) $CI->session->userdata('user_id');
        if ($uid <= 0) return false;

        // user 1: pembuat dokumen (nama kolomnya beda antar modul —
        // requester_id di IRF/PRF/ERF/PR, created_by di PO)
        $owner_ids = array_filter([
            (int) ($header->requester_id ?? 0),
            (int) ($header->created_by ?? 0),
        ]);
        // user 2: approver yang mengembalikannya
        $returner_id = (int) $header->returned_to_draft_by;

        return in_array($uid, $owner_ids, true) || $uid === $returner_id;
    }
}
