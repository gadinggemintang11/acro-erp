<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Role_template — menerjemahkan isi assets/NSI - ERP ROLE LEVEL.pdf
 * (config/role_level_template.php) menjadi hak akses per menu.
 *
 * Role Level tahap 2 (klien 2026-09-15). Aturan penerapan:
 *  - Menu yang dipetakan ke dokumen PDF ("override") ditentukan PENUH oleh
 *    template: kolom yang tidak diberi PDF di-set 0.
 *  - Aksi MONITORING hanya MENAIKKAN View di menu lain; izin lain di menu
 *    itu tidak disentuh (modul di luar PDF tetap diatur manual).
 *  - Menu di luar keduanya sama sekali tidak disentuh.
 *  - Aksi ALL DEPT dicatat di 'scope' -> role_permissions.all_dept_actions;
 *    sisanya hanya berlaku di departemen role (tahap 3, dept_scope_helper).
 */
class Role_template {

    const COLUMNS = ['view', 'add', 'edit', 'submit', 'confirm', 'head_dept', 'endorsement', 'approval', 'delete'];

    protected $CI;
    protected $cfg;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('role_level_template', TRUE);
        $this->cfg = $this->CI->config->item('role_level_template');
    }

    public function departments() { return $this->cfg['role_template_departments']; }
    public function documents()   { return $this->cfg['role_template_documents']; }
    public function actions()     { return $this->cfg['role_template_actions']; }

    /**
     * Nama departemen singkat untuk nama role, dari judul PDF tanpa kata
     * "Department" (mis. "Accounting"). Deskripsi di master Department
     * production panjang ("ACCOUNTING DEPT. inc. FINANCE;COST CONTROLER;TAX"),
     * tidak enak dipakai sebagai nama role. null kalau departemen tidak ada di PDF.
     */
    public function dept_label($department_code) {
        $name = $this->cfg['role_template_departments'][$department_code]['pdf_name'] ?? null;
        return $name ? trim(preg_replace('/\s*Department$/i', '', $name)) : null;
    }

    /** [kode => label singkat] semua departemen di PDF. */
    public function dept_labels() {
        $out = [];
        foreach (array_keys($this->cfg['role_template_departments']) as $code) $out[$code] = $this->dept_label($code);
        return $out;
    }

    public function has($department_code, $position_code) {
        foreach ($this->split_positions($position_code) as $pos) {
            if (!isset($this->cfg['role_template_departments'][$department_code]['positions'][$pos])) return false;
        }
        return $position_code !== '';
    }

    /**
     * Satu role boleh merangkap dua posisi, ditulis "SUPERVISOR+HOD".
     * Dipakai untuk orang yang membuat & mengajukan dokumen SEKALIGUS
     * menyetujuinya sebagai Head of Dept (user 2026-09-16: "PIE Supervisor
     * juga bisa approve head of dept, production supervisor juga").
     */
    public function split_positions($position_code) {
        $parts = array_filter(array_map('trim', explode('+', (string) $position_code)));
        return array_values($parts);
    }

    /**
     * Aturan satu posisi, sudah termasuk aturan poin 4 (Admin/Clerk ikut
     * SUBMIT kalau departemennya tidak punya Supervisor).
     *
     * @return array|null ['actions' => [AKSI => ['own'=>[], 'all'=>[]]], 'submit_fallback' => bool]
     */
    public function rules($department_code, $position_code) {
        if (!$this->has($department_code, $position_code)) return null;
        $positions = $this->cfg['role_template_departments'][$department_code]['positions'];
        $codes     = $this->split_positions($position_code);
        $fb        = $this->cfg['role_template_submit_fallback'];

        // Role rangkap: gabungan aksi kedua posisi (dokumen digabung per aksi).
        $actions = [];
        foreach ($codes as $code) {
            foreach ($positions[$code] as $action => $scope) {
                foreach (['own', 'all'] as $bucket) {
                    $actions[$action][$bucket] = array_values(array_unique(array_merge(
                        $actions[$action][$bucket] ?? [], $scope[$bucket] ?? []
                    )));
                }
            }
        }

        $fallback = false;
        if (in_array($fb['position'], $codes, true) && !isset($positions[$fb['if_missing']]) && !isset($actions['SUBMIT']) && isset($actions['MAKER'])) {
            $actions['SUBMIT'] = $actions['MAKER'];
            $fallback = true;
        }
        return ['actions' => $actions, 'submit_fallback' => $fallback];
    }

    /**
     * Akses tambahan di luar dokumen PDF untuk satu posisi (user 2026-09-16:
     * "tambahkan akses penting misal master data inventory untuk general
     * warehouse dll"). Lihat 'role_template_extras' di config.
     *
     * Director & Asst. Director tidak ikut (MONITORING sudah memberi View
     * semua menu); Admin/Clerk tidak dapat hak hapus.
     *
     * @return array [menu_key => [kolom, ...]]
     */
    public function extras($department_code, $position_code) {
        // Saklar di config: dimatikan sejak template dipakai untuk MENYESUAIKAN
        // role yang sudah ada (2026-09-17), karena role itu sudah punya menu
        // kerjanya sendiri dan yang boleh disentuh hanya menu yang disebut PDF.
        if (empty($this->cfg['role_template_apply_extras'])) return [];

        $cfg_extras = $this->cfg['role_template_extras'] ?? [];
        if (!in_array($position_code, $this->cfg['role_template_extra_positions'] ?? [], true)) return [];

        $levels    = $this->cfg['role_template_extra_levels'] ?? [];
        $no_delete = in_array($position_code, $this->cfg['role_template_extra_no_delete'] ?? [], true);
        $menus     = array_merge($cfg_extras['*'] ?? [], $cfg_extras[$department_code] ?? []);

        $out = [];
        foreach ($menus as $menu => $level) {
            $cols = $levels[$level] ?? $levels['view'];
            if ($no_delete) $cols = array_values(array_diff($cols, ['delete']));
            $out[strtolower($menu)] = $cols;
        }
        return $out;
    }

    /** Ringkasan akses tambahan per tingkat, untuk ditampilkan: ['manage' => [menu, ...], ...] */
    public function extras_by_level($department_code) {
        $cfg_extras = $this->cfg['role_template_extras'] ?? [];
        $out = [];
        foreach (array_merge($cfg_extras['*'] ?? [], $cfg_extras[$department_code] ?? []) as $menu => $level) {
            $out[$level][] = $menu;
        }
        return $out;
    }

    /** Dokumen PDF yang disebut untuk 1 posisi (gabungan semua aksinya). */
    public function documents_of($department_code, $position_code) {
        $rules = $this->rules($department_code, $position_code);
        if ($rules === null) return [];
        $docs = [];
        foreach ($rules['actions'] as $action => $scope) {
            if ($action === 'MONITORING') continue;
            foreach (array_merge($scope['own'] ?? [], $scope['all'] ?? []) as $d) $docs[$d] = true;
        }
        return array_keys($docs);
    }

    /** Menu milik sekumpulan dokumen PDF. */
    public function menus_of_documents(array $doc_keys) {
        $docs = $this->documents();
        $menus = [];
        foreach ($doc_keys as $dk) {
            foreach (array_keys($docs[$dk]['menus'] ?? []) as $m) $menus[$m] = true;
        }
        return array_keys($menus);
    }

    /** Semua menu yang dikendalikan penuh oleh template (gabungan menu seluruh dokumen). */
    public function covered_menus() {
        $menus = [];
        foreach ($this->documents() as $doc) {
            foreach (array_keys($doc['menus']) as $m) $menus[$m] = true;
        }
        return array_keys($menus);
    }

    /**
     * Kolom yang MEMANG DIATUR PDF untuk sebuah menu = gabungan kolom dari
     * semua aksi PDF pada menu itu, dikurangi 'view'.
     *
     * 'view' tidak pernah masuk: di PDF tidak ada aksi "View" — kolom itu cuma
     * ikut terbawa setiap kali sebuah aksi diberikan. Jadi hak lihat yang sudah
     * ada di DB tidak boleh dicabut oleh template (user 2026-09-17: "yang sudah
     * ada sekarang gaperlu di cabut kecuali dimention di pdf").
     *
     * Efeknya: halaman baca-saja (status/outstanding/laporan, spec '*' => view)
     * tidak punya kolom terkendali sama sekali -> template hanya menaikkan View
     * di situ, tidak pernah mengurangi apa pun.
     */
    public function governed_columns($menu_key) {
        foreach ($this->documents() as $doc) {
            if (!isset($doc['menus'][$menu_key])) continue;
            $cols = [];
            foreach ($doc['menus'][$menu_key] as $action => $spec) {
                if ($action === '*') continue;
                foreach ($spec as $c) if ($c !== 'view') $cols[$c] = true;
            }
            return array_keys($cols);
        }
        return [];
    }

    /**
     * Hak akses hasil template untuk 1 posisi.
     *
     * @param string[] $all_menu_keys semua menu_key di DB (untuk MONITORING)
     * @return array|null [
     *   'override'     => [menu_key => [kolom => 0|1, ...]],
     *   'scope'        => [menu_key => 'kolom,kolom' | '*' | ''],  // aksi ALL DEPT (tahap 3)
     *   'monitor_view' => [menu_key, ...]   // cukup View = 1, kolom lain tidak diubah
     *   'notes'        => [string, ...],
     *   'submit_fallback' => bool,
     * ]
     */
    public function build($department_code, $position_code, array $all_menu_keys = []) {
        $rules = $this->rules($department_code, $position_code);
        if ($rules === null) return null;
        $docs = $this->documents();

        // Menu mana yang boleh DITULIS ULANG oleh template:
        //   'position' (bawaan) -> hanya dokumen yang memang disebut PDF UNTUK
        //        posisi ini. Dokumen lain — walau muncul di PDF untuk posisi
        //        lain — dibiarkan apa adanya (user 2026-09-17: "jika sebelumnya
        //        uda diatur di db tapi ga di mention di pdf biarkan saja").
        //   'all' -> semua menu dokumen PDF ditulis ulang untuk semua posisi.
        $scope_mode = $this->cfg['role_template_override_scope'] ?? 'position';
        $writable = $scope_mode === 'all'
            ? $this->covered_menus()
            : $this->menus_of_documents($this->documents_of($department_code, $position_code));

        $override = [];
        foreach ($writable as $m) {
            $override[$m] = array_fill_keys(self::COLUMNS, 0);
        }

        $notes = [];
        $all_cols = [];   // menu => [kolom => true] yang berlaku ALL DEPT
        $monitoring = false;
        foreach ($rules['actions'] as $action => $scope) {
            if ($action === 'MONITORING') { $monitoring = true; continue; }
            $all_docs = $scope['all'] ?? [];
            $doc_keys = array_unique(array_merge($scope['own'] ?? [], $all_docs));
            foreach ($doc_keys as $dk) {
                if (!isset($docs[$dk])) continue;
                if (!empty($docs[$dk]['note'])) $notes[$docs[$dk]['label'] . ': ' . $docs[$dk]['note']] = true;
                foreach ($docs[$dk]['menus'] as $menu => $spec) {
                    $cols = array_merge($spec[$action] ?? [], $spec['*'] ?? []);
                    foreach ($cols as $c) {
                        $override[$menu][$c] = 1;
                        if (in_array($dk, $all_docs, true)) $all_cols[$menu][$c] = true;
                    }
                }
            }
        }

        // Akses tambahan di luar PDF (master data, stok, jurnal, dll). Digabung
        // dengan hak dari PDF — tidak pernah mengurangi. Menu tambahan yang
        // tidak dipakai dokumen PDF ikut dikendalikan penuh oleh template
        // (masuk $override), supaya "Isi dari Template PDF" hasilnya utuh.
        foreach ($this->extras($department_code, $position_code) as $menu => $cols) {
            if (!isset($override[$menu])) $override[$menu] = array_fill_keys(self::COLUMNS, 0);
            foreach ($cols as $c) {
                $override[$menu][$c] = 1;
                // Master data & stok tidak dipecah per departemen.
                $all_cols[$menu][$c] = true;
            }
        }

        $monitor_view = [];
        if ($monitoring) {
            // Monitoring = "all activity at the ERP": View berlaku semua departemen.
            $exclude = $this->cfg['role_template_monitoring_exclude'];
            foreach ($override as $m => $cols) {
                if (!in_array($m, $exclude, true)) { $override[$m]['view'] = 1; $all_cols[$m]['view'] = true; }
            }
            foreach ($all_menu_keys as $m) {
                $m = strtolower($m);
                if (!isset($override[$m]) && !in_array($m, $exclude, true)) $monitor_view[] = $m;
            }
        }

        $scope = [];
        foreach ($override as $m => $cols) {
            $granted = array_keys(array_filter($cols));
            $all = array_values(array_intersect($granted, array_keys($all_cols[$m] ?? [])));
            $scope[$m] = ($granted && count($all) === count($granted)) ? '*' : implode(',', $all);
        }

        // Kolom yang boleh DIKURANGI saat template diterapkan massal. Kolom di
        // luar daftar ini hanya bisa ditambah, tidak pernah dicabut.
        $revoke_mode = $this->cfg['role_template_revoke'] ?? 'pdf_actions';
        $keep = [];
        foreach ($this->split_positions($position_code) as $pos) {
            foreach ($this->cfg['role_template_keep'][$department_code . '.' . $pos] ?? [] as $m => $cols) {
                $keep[$m] = array_merge($keep[$m] ?? [], $cols);
            }
        }
        $governed = [];
        foreach ($override as $m => $cols) {
            $governed[$m] = array_values(array_diff(
                $revoke_mode === 'all_columns' ? array_diff(self::COLUMNS, ['view']) : $this->governed_columns($m),
                $keep[$m] ?? []
            ));
        }

        return [
            'override'        => $override,
            'governed'        => $governed,
            'scope'           => $scope,
            'monitor_view'    => $monitor_view,
            'notes'           => array_keys($notes),
            'submit_fallback' => $rules['submit_fallback'],
        ];
    }

    /**
     * Ringkasan terbaca manusia, format mengikuti PDF:
     *   [['action' => 'Maker', 'own' => 'IRF, PRF', 'all' => 'PO', 'fallback' => false], ...]
     */
    public function summary($department_code, $position_code) {
        $rules = $this->rules($department_code, $position_code);
        if ($rules === null) return [];
        $docs = $this->documents();
        $label = function ($keys) use ($docs) {
            $out = [];
            foreach ($keys as $k) $out[] = $k === '*' ? 'All activity at the ERP' : ($docs[$k]['label'] ?? $k);
            return $out ? implode(', ', array_unique($out)) : '-';
        };
        $rows = [];
        foreach ($rules['actions'] as $action => $scope) {
            $rows[] = [
                'action'   => $this->cfg['role_template_actions'][$action] ?? $action,
                'own'      => $label($scope['own'] ?? []),
                'all'      => $label($scope['all'] ?? []),
                'fallback' => $action === 'SUBMIT' && $rules['submit_fallback'],
            ];
        }
        return $rows;
    }
}
