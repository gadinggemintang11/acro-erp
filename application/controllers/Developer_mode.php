<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Developer Mode" — editor teks/terjemahan lewat UI (ERP Revision Notes,
 * user 2026-09-03). Lihat catatan lengkap di Developer_mode_model.php &
 * application/helpers/language_helper.php.
 */
class Developer_mode extends MY_Controller {

    const LANGUAGES = ['indonesian', 'english', 'chinese'];

    /**
     * Slot logo/gambar yang bisa diganti (ERP Revision Notes, user
     * 2026-09-03). SENGAJA daftar manual (bukan auto-discovery kayak
     * teks) — jumlahnya terbatas & jarang nambah. Tambah slot baru = 1
     * baris di sini + panggil site_asset($key, $default) di view terkait.
     */
    const ASSET_SLOTS = [
        'logo_sidebar_full' => ['label' => 'Logo Sidebar (Besar)', 'default' => 'assets/img/logo_full.png',
            'hint' => 'Tampil di atas sidebar saat menu lebar.'],
        'logo_sidebar_mini' => ['label' => 'Logo Sidebar (Kecil / Mode Ciut)', 'default' => 'assets/img/logo_mini.png',
            'hint' => 'Tampil saat sidebar diciutkan.'],
        // Slot tambahan (user 2026-10-08: "di login kan ada logo, di pdf pdf
        // juga ada logo"). Logo PDF SENGAJA dibatasi PNG/JPG: Dompdf & Mpdf
        // tidak merender SVG/WEBP, jadi logo akan hilang diam-diam dari
        // SELURUH dokumen kalau format itu diizinkan.
        'logo_login'        => ['label' => 'Logo Halaman Login', 'default' => 'assets/img/logo_mini.png',
            'hint' => 'Lingkaran logo di tengah kartu login.'],
        'logo_pdf'          => ['label' => 'Logo Kop PDF', 'default' => 'assets/img/logo_pdf.png',
            'hint' => 'Dipakai di kop SEMUA dokumen PDF (Permintaan Barang, Material Request, PR, PO, Reimburse Request, Payment Request, Surat Jalan). Hanya PNG atau JPG.',
            'types' => 'jpg|jpeg|png'],
        'favicon'           => ['label' => 'Favicon (Ikon Tab Browser)', 'default' => 'assets/img/logo_mini.png',
            'hint' => 'Ikon kecil di tab browser & pintasan layar utama.'],
        // Dua stempel ini punya TULISAN yang menyatu di dalam gambarnya --
        // termasuk nama perusahaan. Ganti nama perusahaan lewat isian di atas
        // TIDAK mengubahnya, jadi harus bisa diganti gambarnya sendiri.
        'stamp_approved'    => ['label' => 'Stempel Approval', 'default' => 'assets/img/ttd.png',
            'hint' => 'Dipakai di kolom tanda tangan PDF untuk approver yang BELUM mengunggah tanda tangannya. Tulisan "ERP APPROVED" + nama perusahaan menyatu di gambarnya. Hanya PNG atau JPG.',
            'types' => 'jpg|jpeg|png'],
        'stamp_completed'   => ['label' => 'Stempel LUNAS / Completed', 'default' => 'assets/img/stamp.png',
            'hint' => 'Cap pada cetakan Reimburse Request & Payment Request yang status bayarnya sudah Paid. Hanya PNG atau JPG.',
            'types' => 'jpg|jpeg|png'],
    ];

    /**
     * Teks identitas perusahaan yang bisa diubah user (tabel site_settings).
     * Nilai bawaannya TIDAK ditulis di sini -- satu-satunya tempatnya ada di
     * company_info() (site_asset_helper.php), supaya tidak ada dua sumber
     * kebenaran yang bisa menyimpang.
     */
    const SETTING_FIELDS = [
        'app_name'        => ['label' => 'Nama Aplikasi', 'type' => 'text',
            'hint' => 'Judul tab browser, kartu halaman login, dan footer aplikasi.'],
        'company_name'    => ['label' => 'Nama Perusahaan', 'type' => 'text',
            'hint' => 'Muncul di kop PDF, export Excel, halaman login & footer aplikasi.'],
        'company_address' => ['label' => 'Alamat', 'type' => 'textarea',
            'hint' => 'Boleh beberapa baris — satu baris di sini = satu baris di kaki dokumen.'],
        'company_phone'   => ['label' => 'Telepon', 'type' => 'text',
            'hint' => 'Baris terakhir di kaki dokumen PDF.'],
        'company_email'   => ['label' => 'Email Purchasing', 'type' => 'text',
            'hint' => 'Dicetak di kop Purchase Order (PO & PO General). Kosongkan kalau tidak perlu.'],
    ];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Developer_mode_model');
        $this->load->helper(['url', 'form']);
    }

    /**
     * Nyala/matikan overlay "klik teks di halaman utk edit" (footer.php).
     * Disimpan di session (per-user, per-browser-session) — bukan
     * per-request, jadi tetap aktif selagi jelajah beberapa halaman.
     */
    public function toggle() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $this->session->set_userdata('devmode_active', !$this->session->userdata('devmode_active'));
        // Tiap kali dinyalakan/dimatikan, mode inline direset ke "belum pilih"
        // (user 2026-09-06) — dulu overlay edit teks langsung aktif begitu
        // Developer Mode nyala, sekarang user memilih dulu mau mengedit apa.
        $this->session->set_userdata('devmode_mode', '');

        $this->_redirect_back();
    }

    /**
     * Pilih mode inline Developer Mode: 'text' (klik teks utk edit) atau
     * 'column' (tombol Atur Kolom di halaman list). Mengirim mode yang SAMA
     * dengan yang sedang aktif = mematikannya (toggle) — supaya user bisa
     * kembali ke keadaan "tidak ada overlay" tanpa mematikan Developer Mode.
     */
    public function set_mode() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (!$this->session->userdata('devmode_active')) {
            $this->_redirect_back();
            return;
        }

        $mode = (string) $this->input->get('mode');
        if (!in_array($mode, ['text', 'column'], true)) $mode = '';
        if ($mode !== '' && $mode === (string) $this->session->userdata('devmode_mode')) {
            $mode = ''; // klik mode yang sedang aktif = matikan
        }
        $this->session->set_userdata('devmode_mode', $mode);

        $this->_redirect_back();
    }

    /** Kembali ke halaman asal (dipakai toggle() & set_mode()). */
    private function _redirect_back() {
        $redirect = (string) $this->input->get('redirect');
        if ($redirect === '' || strpos($redirect, base_url()) !== 0) {
            redirect(base_url());
            return;
        }
        redirect($redirect);
    }

    public function index() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'module'          => $this->input->get('module'),
            'search'          => $this->input->get('search'),
            'customized_only' => $this->input->get('customized_only') ? true : false,
        ];

        $page     = max(1, (int) $this->input->get('page'));
        $per_page = 50;
        $offset   = ($page - 1) * $per_page;

        $total = $this->Developer_mode_model->count_keys($filters);
        $rows  = $this->Developer_mode_model->get_grouped($filters, $per_page, $offset);

        // Nilai AKTIF (custom kalau ada, else nilai app_lang.php asli, else
        // default snapshot) - biar admin lihat "efeknya beneran apa", bukan
        // cuma custom_value mentah yang bisa jadi NULL.
        foreach ($rows as $row) {
            foreach (self::LANGUAGES as $lg) {
                $entry = $row->$lg;
                $entry->live_value = ($entry->custom_value !== null && $entry->custom_value !== '')
                    ? $entry->custom_value
                    : $this->Developer_mode_model->get_live_value($row->lang_key, $lg, $entry->default_value);
                $entry->is_custom = ($entry->custom_value !== null && $entry->custom_value !== '');
            }
        }

        $data = [
            'title'       => lang_line('devmode_title', 'Developer Mode - Editor Bahasa'),
            'rows'        => $rows,
            'modules'     => $this->Developer_mode_model->get_modules(),
            'filters'     => $filters,
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => (int) ceil($total / max(1, $per_page)),
            'can_edit'    => check_access('developer_mode', 'edit'),
            'languages'   => self::LANGUAGES,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('developer_mode/index', $data);
        $this->load->view('templates/footer');
    }

    /** AJAX: simpan 1 custom_value (1 key, 1 bahasa). */
    public function save() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $lang_key = trim((string) $this->input->post('lang_key'));
        $language = trim((string) $this->input->post('language'));
        $value    = (string) $this->input->post('value');

        if ($lang_key === '' || !in_array($language, self::LANGUAGES, true)) {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $user_id = $this->session->userdata('user_id');
        $this->Developer_mode_model->save_override($lang_key, $language, $value, $user_id);
        return $this->_json(['success' => true, 'message' => 'Tersimpan.', 'value' => ($value === '' ? null : $value)]);
    }

    /** AJAX: reset 1 key utk 1 bahasa (balik ke default app_lang.php). */
    public function reset() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $lang_key = trim((string) $this->input->post('lang_key'));
        $language = trim((string) $this->input->post('language'));

        if ($lang_key === '' || !in_array($language, self::LANGUAGES, true)) {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $this->Developer_mode_model->reset_override($lang_key, $language);
        $live = $this->Developer_mode_model->get_live_value($lang_key, $language, '');
        return $this->_json(['success' => true, 'message' => 'Direset ke default.', 'value' => $live]);
    }

    /**
     * "Kelola Logo & Gambar" — daftar slot dari ASSET_SLOTS + path
     * aktifnya sekarang (custom kalau ada, else default).
     */
    public function assets() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $slots = [];
        foreach (self::ASSET_SLOTS as $key => $meta) {
            $current = site_asset($key, $meta['default']);
            $slots[] = (object) [
                'key'          => $key,
                'label'        => $meta['label'],
                'hint'         => $meta['hint'] ?? '',
                'default_path' => $meta['default'],
                'current_path' => $current,
                'is_custom'    => $current !== $meta['default'],
            ];
        }

        $settings = [];
        foreach (self::SETTING_FIELDS as $key => $meta) {
            $settings[] = (object) [
                'key'     => $key,
                'label'   => $meta['label'],
                'type'    => $meta['type'],
                'hint'    => $meta['hint'] ?? '',
                'value'   => company_info($key),
                'default' => site_setting($key, '__TIDAK_ADA__') === '__TIDAK_ADA__',
            ];
        }

        $data = [
            'title'    => lang_line('devmode_assets_title', 'Developer Mode - Logo, Gambar & Identitas'),
            'slots'    => $slots,
            'settings' => $settings,
            'can_edit' => check_access('developer_mode', 'edit'),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('developer_mode/assets', $data);
        $this->load->view('templates/footer');
    }

    /** Upload/ganti 1 slot gambar. */
    public function upload_asset() {
        if (!check_access('developer_mode', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $key = (string) $this->input->post('asset_key');
        if (!isset(self::ASSET_SLOTS[$key])) {
            $this->session->set_flashdata('error', 'Slot gambar tidak valid.');
            redirect('developer_mode/assets');
            return;
        }

        $upload_path = FCPATH . 'uploads/site_assets/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => self::ASSET_SLOTS[$key]['types'] ?? 'jpg|jpeg|png|gif|svg|webp',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . strip_tags($this->upload->display_errors('', '')));
            redirect('developer_mode/assets');
            return;
        }

        $f = $this->upload->data();
        $relative_path = 'uploads/site_assets/' . $f['file_name'];

        $existing = $this->db->where('asset_key', $key)->get('site_assets')->row();
        $save = [
            'file_path'     => $relative_path,
            'original_name' => $f['orig_name'],
            'uploaded_by'   => $this->session->userdata('user_id'),
            'uploaded_at'   => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $this->db->where('id', $existing->id)->update('site_assets', $save);
        } else {
            $save['asset_key']  = $key;
            $save['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('site_assets', $save);
        }

        $this->session->set_flashdata('success', 'Gambar berhasil diganti.');
        redirect('developer_mode/assets');
    }

    /**
     * Simpan identitas perusahaan (user 2026-10-08). Baris DIHAPUS kalau
     * isiannya dikosongkan -- dengan begitu nilainya kembali ke bawaan di
     * company_info(), bukan jadi string kosong yang bikin kop PDF melompong.
     */
    public function save_settings() {
        if (!check_access('developer_mode', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (!$this->db->table_exists('site_settings')) {
            $this->session->set_flashdata('error',
                'Tabel site_settings belum ada — jalankan migration_site_settings.sql lewat halaman /db_migration dulu.');
            redirect('developer_mode/assets');
            return;
        }

        $uid = $this->session->userdata('user_id');
        foreach (self::SETTING_FIELDS as $key => $meta) {
            $value = trim((string) $this->input->post($key));
            // Normalkan akhiran baris Windows supaya jumlah baris alamat di
            // PDF sama persis dengan yang diketik user.
            $value = str_replace("\r\n", "\n", $value);

            $existing = $this->db->where('setting_key', $key)->get('site_settings')->row();
            if ($value === '') {
                if ($existing) $this->db->where('id', $existing->id)->delete('site_settings');
                continue;
            }
            if ($existing) {
                $this->db->where('id', $existing->id)->update('site_settings',
                    ['setting_value' => $value, 'updated_by' => $uid, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $this->db->insert('site_settings', [
                    'setting_key' => $key, 'setting_value' => $value,
                    'updated_by' => $uid, 'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        add_log('UPDATE', 'Developer Mode', 'Mengubah identitas perusahaan (nama/alamat/telepon).');
        $this->session->set_flashdata('success', 'Identitas perusahaan tersimpan.');
        redirect('developer_mode/assets');
    }

    /** Kembalikan 1 slot ke gambar default (hapus override). */
    public function reset_asset() {
        if (!check_access('developer_mode', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $key = (string) $this->input->post('asset_key');
        if (isset(self::ASSET_SLOTS[$key])) {
            $this->db->where('asset_key', $key)->update('site_assets', ['file_path' => null]);
            $this->session->set_flashdata('success', 'Dikembalikan ke gambar default.');
        }
        redirect('developer_mode/assets');
    }

    /**
     * "Kelola Kolom" — daftar halaman list yang sudah "dipasangi"
     * ui_column_visible() (baru IRF List sbg pilot), tiap kolomnya bisa
     * di-checklist tampil/sembunyi.
     */
    public function columns() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $pages = [];
        foreach ($this->Developer_mode_model->get_column_pages() as $page_key) {
            $pages[] = (object) [
                'page_key' => $page_key,
                'columns'  => $this->Developer_mode_model->get_columns_for_page($page_key),
            ];
        }

        $data = [
            'title'    => lang_line('devmode_columns_title', 'Developer Mode - Kelola Kolom List'),
            'pages'    => $pages,
            'can_edit' => check_access('developer_mode', 'edit'),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('developer_mode/columns', $data);
        $this->load->view('templates/footer');
    }

    /** AJAX: nyala/matikan 1 kolom di 1 halaman list. */
    public function save_column() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $page_key   = trim((string) $this->input->post('page_key'));
        $column_key = trim((string) $this->input->post('column_key'));
        $is_hidden  = $this->input->post('is_hidden') ? 1 : 0;

        if ($page_key === '' || $column_key === '') {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $this->Developer_mode_model->set_column_hidden($page_key, $column_key, $is_hidden, $this->session->userdata('user_id'));
        return $this->_json(['success' => true, 'message' => 'Tersimpan.']);
    }

    /**
     * Label dropdown utk halaman "Atur Urutan Sidebar" — SAMA persis dgn
     * $sidebar_section_labels di templates/sidebar.php, MINUS
     * 'document_sharing' (link biasa, bukan dropdown, tidak py submenu).
     */
    const DROPDOWN_LABELS = [
        'order'        => 'Sales Order Number',
        'permintaan'   => 'Permintaan Barang',
        'produksi'     => 'Production',
        'warehouse'    => 'Warehouse',
        'purchase'     => 'Purchasing',
        'qa'           => 'Quality Control & Assurances',
        'fg'           => 'Finished Goods',
        'exim'         => 'Shipping & Transportation',
        'scrap'        => 'Scrap',
        'subkon'       => 'Subkon',
        'it_inventory' => 'IT Inventory [BC]',
        // Finance & Accounting dipecah jadi 6 dropdown (2026-11-09).
        'acc_fund'     => 'Pengajuan Dana (PRF & ERF)',
        'accounting'   => 'Kas, Bank & Jurnal',
        'acc_apar'     => 'Hutang & Piutang',
        'acc_report'   => 'Laporan Keuangan',
        'acc_stock'    => 'Stock Opname',
        'acc_setting'  => 'Pengaturan Akuntansi',
        'master'       => 'Master Database',
        'system'       => 'System Setup',
    ];

    /**
     * "Atur Urutan Sidebar" (user 2026-09-06, + follow-up "bukan cuma core
     * operation tapi semuanya baik menu utama & sub menu") — drag-and-drop
     * urutan 15 menu utama + submenu di dalam tiap dropdown. Lihat
     * migration_sidebar_order.sql & application/helpers/sidebar_order_helper.php.
     */
    /**
     * Copot / pasang 1 menu sidebar (AJAX). MURNI TAMPILAN — menu yang
     * dicopot cuma hilang dari sidebar; URL & izin Role Access tidak berubah.
     * User 2026-09-07: "harusnya bisa copot dan pasang menu sidebar juga".
     */
    public function toggle_sidebar_hidden() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json_out(['success' => false, 'message' => 'Anda tidak memiliki izin.']);
        }
        $scope    = (string) $this->input->post('scope');           // 'section' | 'item'
        $key      = (string) $this->input->post('key');
        $dropdown = $this->input->post('dropdown_key') ?: null;
        $hidden   = (int) $this->input->post('hidden') === 1;

        if (!in_array($scope, ['section', 'item'], true) || $key === '') {
            return $this->_json_out(['success' => false, 'message' => 'Parameter tidak valid.']);
        }

        $ok = $this->Developer_mode_model->set_hidden(
            $scope, $key, $hidden, $this->session->userdata('user_id'), $dropdown
        );
        if (!$ok) {
            return $this->_json_out(['success' => false,
                'message' => 'Fitur ini butuh migrasi database "migration_sidebar_hidden.sql" dijalankan dulu (buka /db_migration).']);
        }

        add_log('DEVMODE', 'Sidebar', ($hidden ? 'Copot' : 'Pasang') . ' menu sidebar: ' . $scope . ' "' . $key . '"'
            . ($dropdown ? ' (dropdown ' . $dropdown . ')' : ''));

        return $this->_json_out(['success' => true, 'hidden' => $hidden]);
    }

    private function _json_out($data) {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    /**
     * "Atur PDF" — setelan tata letak template PDF (PRF/ERF).
     *
     * TAHAP 1 sengaja berupa kontrol ANGKA, bukan kanvas geser-geser bebas.
     * Alasan lengkapnya di helpers/pdf_layout_helper.php: template-nya
     * berbasis tabel HTML dan Mpdf tidak andal untuk posisi absolut, jadi
     * kanvas bebas berarti menulis ulang template & kehilangan pagination
     * otomatis. Dengan kontrol angka, alur halaman tetap diurus Mpdf.
     */
    public function pdf_layout() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $templates = [];
        foreach (pdf_layout_templates() as $key => $tpl) {
            $templates[] = (object) [
                'key'      => $key,
                'label'    => $tpl['label'],
                'settings' => $tpl['settings'],
                'values'   => pdf_layout($key),
                'sample'   => $this->_pdf_layout_sample($key),
                // Peta teks-PDF -> setelan, utk editor klik-per-elemen.
                'hotspots' => function_exists('pdf_layout_hotspots') ? pdf_layout_hotspots($key) : [],
            ];
        }

        $data = [
            'title'     => 'Atur Tata Letak PDF',
            'templates' => $templates,
            'groups'    => pdf_layout_groups(),
            'ready'     => pdf_layout_ready(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('developer_mode/pdf_layout', $data);
        $this->load->view('templates/footer');
    }

    /**
     * 1 dokumen contoh utk tombol Preview — diambil dari data ASLI supaya
     * yang dilihat user benar-benar hasil Mpdf, bukan tiruan HTML.
     * NULL kalau memang belum ada dokumennya.
     */
    private function _pdf_layout_sample($template_key) {
        $map = [
            'prf' => ['table' => 'prf', 'route' => 'prf/export_pdf'],
            'erf' => ['table' => 'erf_header', 'route' => 'erf/export_pdf'],
        ];
        if (!isset($map[$template_key])) return null;
        if (!$this->db->table_exists($map[$template_key]['table'])) return null;

        $row = $this->db->select('id')->order_by('id', 'DESC')->limit(1)
                        ->get($map[$template_key]['table'])->row();
        return $row ? ($map[$template_key]['route'] . '/' . $row->id) : null;
    }

    /**
     * Preview Langsung — render dokumen contoh memakai nilai yang SEDANG
     * diutak-atik user (belum disimpan), lalu lempar ke jalur export PDF
     * yang ASLI supaya hasilnya dijamin sama dengan cetakan sungguhan.
     *
     * Nilai preview dititipkan lewat session + flag ?pdf_preview=1; export
     * PDF biasa (tanpa flag itu) mengabaikannya total — lihat
     * pdf_layout_preview_values().
     */
    public function preview_pdf($template_key) {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $tpl = pdf_layout_templates()[$template_key] ?? null;
        if (!$tpl) show_404();

        $values = [];
        foreach ($tpl['settings'] as $sk => $meta) {
            $v = $this->input->get($sk);
            if ($v === null || $v === '') continue;
            $values[$sk] = max($meta['min'], min($meta['max'], (float) $v));
        }
        pdf_layout_set_preview($template_key, $values);

        $sample = $this->_pdf_layout_sample($template_key);
        if (!$sample) {
            show_error('Belum ada dokumen ' . strtoupper($template_key) . ' yang bisa dijadikan contoh preview.', 404);
            return;
        }
        redirect($sample . '?pdf_preview=1');
    }

    public function save_pdf_layout() {
        if (!check_access('developer_mode', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (!pdf_layout_ready()) {
            $this->session->set_flashdata('error', 'Tabel pdf_layout_overrides belum ada. Jalankan migrasi db/migration_pdf_layout.sql dulu.');
            redirect('developer_mode/pdf_layout');
            return;
        }

        $key      = (string) $this->input->post('template_key');
        $settings = $this->input->post('settings');
        $spec     = pdf_layout_templates()[$key]['settings'] ?? null;

        if (!$spec || !is_array($settings)) {
            $this->session->set_flashdata('error', 'Template tidak dikenal.');
            redirect('developer_mode/pdf_layout');
            return;
        }

        $user_id = $this->session->userdata('user_id');
        foreach ($settings as $sk => $val) {
            if (!isset($spec[$sk])) continue;              // buang kiriman asing
            if ($val === '' || $val === null) continue;
            pdf_layout_save($key, $sk, $val, $user_id);
        }

        add_log('UPDATE', 'Developer Mode', 'Ubah tata letak PDF template "' . $key . '".');
        $this->session->set_flashdata('success', 'Tata letak PDF disimpan. Klik Preview untuk melihat hasilnya.');
        redirect('developer_mode/pdf_layout');
    }

    public function reset_pdf_layout() {
        if (!check_access('developer_mode', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $key = (string) $this->input->post('template_key');
        if (isset(pdf_layout_templates()[$key])) {
            pdf_layout_reset($key);
            add_log('UPDATE', 'Developer Mode', 'Reset tata letak PDF template "' . $key . '" ke bawaan.');
            $this->session->set_flashdata('success', 'Dikembalikan ke tata letak bawaan.');
        }
        redirect('developer_mode/pdf_layout');
    }

    public function sidebar_order() {
        if (!check_access('developer_mode', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $dropdowns = [];
        foreach (self::DROPDOWN_LABELS as $key => $label) {
            $items = $this->Developer_mode_model->get_items_for_dropdown($key);
            if (empty($items)) continue; // belum pernah ke-render sama sekali (user blm buka menu ini) -- jgn tampilkan kartu kosong

            // Level-3 (user 2026-09-06: "masih ada sub lagi") -- kalau 1 item
            // adalah GRUP (prefix 'grp_', mis. isi "FINANCE" di Accounting),
            // ambil jg urutan item DI DALAM grup itu (scope tersimpan sbg
            // "dropdown_key::item_key", lihat sidebar_order_helper.php).
            foreach ($items as $it) {
                $it->children = [];
                if (strpos($it->item_key, 'grp_') === 0) {
                    $it->children = $this->Developer_mode_model->get_items_for_dropdown($key . '::' . $it->item_key);
                }
            }

            $dropdowns[] = (object) ['key' => $key, 'label' => $label, 'items' => $items];
        }

        $data = [
            'title'     => lang_line('devmode_sidebar_order_title', 'Developer Mode - Atur Urutan Sidebar'),
            'sections'  => $this->Developer_mode_model->get_sidebar_sections(),
            'dropdowns' => $dropdowns,
            'can_edit'  => check_access('developer_mode', 'edit'),
            // Fitur "copot & pasang" (2026-09-07) butuh kolom is_hidden dari
            // migration_sidebar_hidden.sql — kalau migrasinya belum jalan,
            // tombolnya tidak ditampilkan (bukan error).
            'hidden_ready' => $this->Developer_mode_model->hidden_feature_ready(),
            // Pindah antar segmen (2026-09-16) butuh migration_sidebar_item_parent.sql.
            'parent_ready' => $this->Developer_mode_model->parent_feature_ready(),
            // Ganti teks menu langsung dari halaman ini (user 2026-09-16:
            // "edit text sidebar harusnya bisa juga di atur sidebar ya").
            'section_lang_keys' => function_exists('sidebar_section_lang_keys') ? sidebar_section_lang_keys() : [],
            'active_language'   => get_current_language(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('developer_mode/sidebar_order', $data);
        $this->load->view('templates/footer');
    }

    /** AJAX: simpan SELURUH urutan baru sekaligus (bukan 1 per 1). */
    public function save_sidebar_order() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $ordered_keys = $this->input->post('order');
        if (!is_array($ordered_keys) || empty($ordered_keys)) {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $ok = $this->Developer_mode_model->save_sidebar_order($ordered_keys, $this->session->userdata('user_id'));
        return $this->_json(['success' => $ok, 'message' => $ok ? 'Urutan sidebar tersimpan.' : 'Gagal menyimpan.']);
    }

    /** AJAX: simpan urutan submenu 1 dropdown sekaligus (bukan 1 per 1). */
    public function save_item_order() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $dropdown_key = trim((string) $this->input->post('dropdown_key'));
        $ordered_keys = $this->input->post('order');
        if ($dropdown_key === '' || !is_array($ordered_keys) || empty($ordered_keys)) {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $ok = $this->Developer_mode_model->save_item_order($dropdown_key, $ordered_keys, $this->session->userdata('user_id'));
        return $this->_json(['success' => $ok, 'message' => $ok ? 'Urutan submenu tersimpan.' : 'Gagal menyimpan.']);
    }

    /** AJAX: pindahkan 1 item ke segmen lain di dropdown yang sama. */
    public function save_item_parent() {
        if (!check_access('developer_mode', 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $dropdown_key = trim((string) $this->input->post('dropdown_key'));
        $item_key     = trim((string) $this->input->post('item_key'));
        $parent_key   = trim((string) $this->input->post('parent_key'));   // '' = tanpa segmen
        $from_scope   = trim((string) $this->input->post('from_scope'));

        if ($dropdown_key === '' || $item_key === '' || $from_scope === '') {
            return $this->_json(['success' => false, 'message' => 'Data tidak valid.']);
        }
        if (strpos($item_key, 'grp_') === 0) {
            return $this->_json(['success' => false, 'message' => 'Segmen tidak bisa dimasukkan ke dalam segmen lain.']);
        }
        if ($parent_key !== '' && strpos($parent_key, 'grp_') !== 0) {
            return $this->_json(['success' => false, 'message' => 'Segmen tujuan tidak dikenal.']);
        }

        $ok = $this->Developer_mode_model->save_item_parent($dropdown_key, $item_key, $parent_key, $from_scope, $this->session->userdata('user_id'));
        return $this->_json(['success' => $ok, 'message' => $ok ? 'Menu dipindahkan.' : 'Gagal memindahkan.']);
    }

    private function _json($data) {
        while (ob_get_level()) { ob_end_clean(); }
        @ini_set('display_errors', '0');
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
