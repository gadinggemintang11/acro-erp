<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ini adalah Controller Induk (Master Controller)
 * Semua controller Anda akan extend ke sini, bukan ke CI_Controller
 */
class MY_Controller extends CI_Controller {

    /**
     * Nama modul yang akan dicatat di log.
     * Override (timpa) properti ini di setiap controller anak.
     * @var string
     */
    protected $module_name = 'Undefined Module';

    public function __construct() {
        parent::__construct();
        
        // Load helper log secara otomatis untuk semua controller
        $this->load->helper('log_helper');
        
        // Setup multi-language
        $this->_setup_language();
    }

    /**
     * Fungsi wrapper untuk logging yang "lebih cerdas".
     * Fungsi ini akan otomatis mengambil nama modul dari properti $module_name.
     *
     * @param string $action      Aksi (CREATE, UPDATE, DELETE, APPROVE, etc.)
     * @param string $description Deskripsi detail dari aksi
     */
    protected function _log($action, $description) {
        
        // Kita tidak perlu memanggil helper `add_log()` secara manual lagi.
        // Kita panggil fungsi global `add_log()` dari helper.
        add_log($action, $this->module_name, $description);
    }

    /**
     * Setup bahasa berdasarkan session atau cookie
     */
    private function _setup_language() {
        // Cek apakah ada request untuk ganti bahasa
        if ($this->input->get('lang')) {
            $lang = $this->input->get('lang');
            $this->session->set_userdata('site_lang', $lang);
            
            // Set cookie untuk 30 hari
            setcookie('site_lang', $lang, time() + (86400 * 30), '/');
        }
        
        // Ambil bahasa dari session atau cookie atau default
        $lang = $this->session->userdata('site_lang');
        if (!$lang) {
            $lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'indonesian';
        }
        
        // Validasi bahasa yang tersedia
        $available_langs = ['indonesian', 'english', 'chinese'];
        if (!in_array($lang, $available_langs)) {
            $lang = 'indonesian';
        }
        
        // Set bahasa CodeIgniter
        $this->config->set_item('language', $lang);
        $this->lang->load('app', $lang);
    }

    /**
     * Helper function untuk translate text
     * 
     * @param string $key Language key
     * @param string $default Default text jika key tidak ditemukan
     * @return string Translated text
     */
    protected function lang($key, $default = '') {
        $text = $this->lang->line($key);
        return $text ? $text : ($default ? $default : $key);
    }

    // =====================================================================
    // Helper permission per-kategori (General/Produksi) — dipakai bareng oleh
    // modul yang sidebar-nya dipecah 2 pintu per kategori (Consumable_out,
    // Consumable_outstanding, Irf_status, dst). Menu-key spesifik kategori
    // selalu berpola "<base_menu_key>_general" / "<base_menu_key>_produksi",
    // dengan fallback OR ke permission lama `$base_menu_key` (blanket) supaya
    // role yang belum sempat dikonfigurasi ulang di Role Access tidak
    // mendadak kehilangan akses begitu menu dipecah.
    // =====================================================================

    /** '<base_menu_key>_general' / '<base_menu_key>_produksi' sesuai kategori. */
    protected function category_menu_key($base_menu_key, $category) {
        $suffix = ($category === 'Produksi') ? 'produksi' : 'general';
        return $base_menu_key . '_' . $suffix;
    }

    /** OR-fallback: menu spesifik kategori ATAU permission lama yang blanket. */
    protected function can_category($base_menu_key, $category, $action) {
        return check_access($this->category_menu_key($base_menu_key, $category), $action)
            || check_access($base_menu_key, $action);
    }

    /** URL segment ('general'/'produksi') -> 'General'/'Produksi', atau null. */
    protected function normalize_category_slug($slug) {
        $slug = strtolower((string) $slug);
        if ($slug === 'general')  return 'General';
        if ($slug === 'produksi') return 'Produksi';
        return null;
    }

    /** Daftar kategori (dari General/Produksi) yang boleh diakses user ini. */
    protected function allowed_categories($base_menu_key, $action) {
        $allowed = [];
        foreach (['General', 'Produksi'] as $c) {
            if ($this->can_category($base_menu_key, $c, $action)) $allowed[] = $c;
        }
        return $allowed;
    }

    // =====================================================================
    // Tombol "Kembali" kontekstual — dipakai halaman detail yang punya lebih
    // dari satu pintu masuk (mis. PRF/ERF detail bisa dibuka dari list_all
    // ATAU dari payment/reimbursement queue). Tanpa ini tombol Kembali selalu
    // balik ke satu URL default yang di-hardcode, walaupun user datangnya
    // dari halaman lain (bug: PRF payment -> detail -> Kembali malah ke
    // prf/list_all, bukan balik ke prf/payment).
    // =====================================================================

    /**
     * Tentukan URL tombol "Kembali" secara kontekstual.
     * Prioritas: query string eksplisit ?from=... (kalau view pengirim link
     * sudah dilengkapi), lalu HTTP_REFERER browser (link biasa <a href>
     * otomatis kirim ini), baru fallback ke $default kalau keduanya kosong
     * atau tidak valid.
     *
     * Hasil divalidasi harus diawali salah satu path di $allowed_prefixes
     * (mis. ['prf/']) supaya tidak jadi open-redirect / nyasar ke domain
     * lain kalau referer datang dari luar app.
     */
    protected function resolve_back_url($default, array $allowed_prefixes) {
        $candidate = $this->input->get('from');
        if (empty($candidate)) {
            $referer = $this->input->server('HTTP_REFERER');
            if (!empty($referer) && strpos($referer, base_url()) === 0) {
                $candidate = substr($referer, strlen(base_url()));
            }
        }
        $candidate = ltrim((string) $candidate, '/');
        foreach ($allowed_prefixes as $prefix) {
            $prefix = ltrim($prefix, '/');
            if ($candidate !== '' && strpos($candidate, $prefix) === 0) {
                return $candidate;
            }
        }
        return $default;
    }

    // =====================================================================
    // Notifikasi topbar (ERP Revision Notes, [[erp_revision_cross_cutting]]).
    // Bell icon + dropdown SUDAH ADA dari sebelumnya, tapi isinya cuma feed
    // generik dari user_logs — helper di bawah dipakai titik-titik trigger
    // approval (submit/approve stage/back-to-draft/release dst) utk kirim
    // notifikasi BERTARGET ke tabel `notifications` yang baru (mark-as-read
    // & link beneran jalan, beda dari yang lama). Mulai dipakai di IRF dulu
    // (Consumable_request/Consumable_out), pola sama utk direplikasi ke
    // PRF/ERF/PO setelah teruji.
    // =====================================================================

    /**
     * Kirim 1 notifikasi ke satu atau beberapa user sekaligus.
     * $link = path relatif TANPA base_url (mis. 'consumable_request/detail/5'),
     * di-resolve base_url()-nya di sisi view/JS saat dirender.
     */
    protected function notify_users(array $user_ids, $title, $message = '', $link = null, $type = 'info', $icon = 'fa-bell', $source_module = null, $source_id = null) {
        $user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids), function ($id) { return $id > 0; })));
        if (empty($user_ids)) return;

        $this->load->model('Notification_model');
        foreach ($user_ids as $uid) {
            $this->Notification_model->create([
                'user_id'       => $uid,
                'title'         => $title,
                'message'       => $message,
                'type'          => $type,
                'icon'          => $icon,
                'link'          => $link,
                'source_module' => $source_module,
                'source_id'     => $source_id,
            ]);
        }
    }

    /**
     * Resolve daftar user_id yang punya kolom permission tertentu
     * (can_view/can_add/can_head_dept/can_endorsement/can_approval/dst)
     * bernilai 1 di menu_key tertentu — dipakai utk menentukan siapa yang
     * berhak menerima notifikasi "menunggu approval Anda" tanpa perlu
     * daftar recipient di-hardcode manual.
     */
    protected function users_with_permission($menu_key, $permission_column) {
        $allowed_cols = ['can_view', 'can_add', 'can_edit', 'can_delete', 'can_confirm', 'can_head_dept', 'can_endorsement', 'can_approval'];
        if (!in_array($permission_column, $allowed_cols, true)) return [];

        // Semua role user dihitung (Role Level 2026-09-15), bukan cuma role utama.
        $sql = "
            SELECT DISTINCT u.id
            FROM users u
            INNER JOIN " . user_roles_source_sql() . " ur ON ur.user_id = u.id
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN menus m ON m.id = rp.menu_id
            WHERE m.menu_key = ? AND rp.$permission_column = 1
        ";
        $query = $this->db->query($sql, [$menu_key]);
        return $query ? array_map(function ($r) { return (int) $r->id; }, $query->result()) : [];
    }

    /**
     * Sama seperti users_with_permission(), tapi utk modul yang menu-nya
     * di-split per kategori General/Produksi (pola category_menu_key()) —
     * gabungan user yang py akses lewat menu spesifik-kategori ATAU
     * blanket lama, konsisten dgn can_category().
     */
    protected function users_with_category_permission($base_menu_key, $category, $permission_column) {
        $specific = $this->users_with_permission($this->category_menu_key($base_menu_key, $category), $permission_column);
        $blanket  = $this->users_with_permission($base_menu_key, $permission_column);
        return array_values(array_unique(array_merge($specific, $blanket)));
    }

}