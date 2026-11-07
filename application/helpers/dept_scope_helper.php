<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * CAKUPAN DEPARTEMEN (OWN DEPT vs ALL DEPT) — Role Level tahap 3
 * ------------------------------------------------------------
 * assets/NSI - ERP ROLE LEVEL.pdf (klien 2026-09-15). Izin di
 * role_permissions tetap per menu, ditambah kolom all_dept_actions:
 *   - aksi yang tercantum (atau '*')  -> berlaku untuk SEMUA departemen
 *   - aksi yang tidak tercantum       -> hanya departemen role tsb
 * Departemen role = roles.department_code; role lama tanpa departemen
 * memakai departemen di profil user (users.departemen).
 *
 * 'legacy' (isi awal semua baris lama saat migrasi): aksi tetap berlaku
 * semua departemen seperti dulu, tapi TIDAK dipakai untuk memperluas daftar
 * dokumen ($listing = true) supaya tampilan list user lama tidak berubah.
 *
 * User multi-role: cakupan = GABUNGAN semua role yang memberi izin itu.
 * Admin (is_admin) selalu semua departemen. DB yang belum dimigrasi
 * (kolom belum ada) diperlakukan semua departemen = perilaku lama.
 * ============================================================
 */

if (!function_exists('department_names')) {
    /** [kode => nama] master Department (common_code), di-cache per request. */
    function department_names() {
        static $map = null;
        if ($map !== null) return $map;
        $CI =& get_instance();
        $map = [];
        foreach ($CI->db->query("SELECT code, description FROM common_code WHERE group_code = 'Department' ORDER BY description")->result() as $r) {
            $map[$r->code] = $r->description;
        }
        return $map;
    }
}

if (!function_exists('department_code_aliases')) {
    /**
     * Kode/nama lama sebelum master Department baru -> kode baru (sama dengan
     * peta di db/migration_department_codes_v2.sql). Data yang belum
     * dikonversi masih menyimpan nilai lama ini.
     */
    function department_code_aliases() {
        return [
            'FIN ACT' => 'ACC', 'FINANCE & ACCOUNTING' => 'ACC', 'FAT' => 'ACC', 'ACCOUNTING' => 'ACC',
            'HRGA' => 'ADM', 'MTC' => 'ADM', 'MAINTENANCE' => 'ADM',
            'EXIM' => 'LOG', 'EXPORT IMPORT' => 'LOG', 'LOGISTIC' => 'LOG',
            'MANAGEMENT' => 'MGM', 'MANAGEMEN' => 'MGM', 'FACTORY' => 'MGM', 'OFFICE' => 'MGM', 'DIR' => 'MGM',
            'I E' => 'PIE', 'INDUSTRIAL ENGINEERING' => 'PIE',
            'PROD' => 'PRO', 'PRODUKSI' => 'PRO', 'PRODUCTION' => 'PRO',
            'PCH' => 'PUR', 'PURCHASING' => 'PUR',
            'WAREHOUSE & SHIPPING' => 'PWH',
            'Q A' => 'QAD', 'QUALITY ASSURANCE' => 'QAD',
            'GENERAL WAREHOUSE' => 'GWH',
        ];
    }
}

if (!function_exists('normalize_department_code')) {
    /**
     * Kode departemen dari nilai apa pun yang tersimpan: kode ("ADM"), nama
     * ("HRGA"), atau alias lama ("MANAGEMENT"). null kalau tidak dikenali.
     */
    function normalize_department_code($value) {
        $value = trim((string) $value);
        if ($value === '') return null;
        $names = department_names();
        if (isset($names[$value])) return $value;
        foreach ($names as $code => $name) {
            if (strcasecmp($name, $value) === 0 || strcasecmp($code, $value) === 0) return $code;
        }
        $alias = department_code_aliases()[strtoupper($value)] ?? null;
        return ($alias !== null && isset($names[$alias])) ? $alias : null;
    }
}

if (!function_exists('dept_scope')) {
    /**
     * Cakupan departemen user yang login untuk 1 aksi di 1 menu.
     *
     * @param bool $listing true = untuk memperluas daftar dokumen (baris 'legacy' diabaikan)
     * @return array ['all' => bool, 'depts' => string[], 'legacy' => bool]  (tanpa izin: all=false, depts=[])
     */
    function dept_scope($menu_key, $action, $listing = false) {
        static $cache = [];
        $k = strtolower($menu_key) . '|' . $action . '|' . (int) $listing;
        if (isset($cache[$k])) return $cache[$k];

        $none = ['all' => false, 'depts' => [], 'legacy' => false];
        if (!check_access($menu_key, $action)) return $cache[$k] = $none;
        if (is_admin() || !db_has_column('role_permissions', 'all_dept_actions')) {
            return $cache[$k] = ['all' => true, 'depts' => [], 'legacy' => false];
        }

        // Nama kolom aksi: alias check_access (approve/confirm dst) dipetakan ke kolomnya.
        $col_of = ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'submit' => 'submit',
                   'confirm' => 'confirm', 'approve' => 'confirm', 'head_dept' => 'head_dept',
                   'endorsement' => 'endorsement', 'approval' => 'approval'];
        $col = $col_of[$action] ?? null;
        if ($col === null) return $cache[$k] = $none;

        // Kolom `can_submit` baru ada setelah migration_role_submit_permission.sql.
        // Tanpa penjagaan ini, query di bawah memakai kolom yang belum ada dan
        // seluruh halaman yang memeriksa izin Submit mati dgn "Unknown column
        // 'rp.can_submit'" -- terjadi di IRF (dilaporkan user 2026-10-01).
        // Jatuh-baliknya disamakan dgn check_access() di auth_helper: sebelum
        // migrasi itu jalan, izin Submit mengikuti can_confirm.
        //
        // Yang diganti HANYA nama kolom di query; $col tetap 'submit' supaya
        // pencocokan all_dept_actions di bawah tidak ikut bergeser.
        $col_db = $col;
        if ($col_db === 'submit' && !db_has_column('role_permissions', 'can_submit')) {
            $col_db = 'confirm';
        }

        $CI =& get_instance();
        $ids = array_map('intval', current_role_ids());
        if (empty($ids)) return $cache[$k] = $none;
        $rows = $CI->db->query(
            'SELECT rp.can_' . $col_db . ' AS granted, rp.all_dept_actions, r.department_code
               FROM role_permissions rp
               JOIN menus m ON m.id = rp.menu_id
               JOIN roles r ON r.id = rp.role_id
              WHERE LOWER(m.menu_key) = ? AND rp.role_id IN (' . implode(',', $ids) . ')',
            [strtolower($menu_key)]
        )->result();

        $out = $none;
        $profile_dept = null;
        foreach ($rows as $r) {
            if ((int) $r->granted !== 1) continue;
            $acts = array_map('trim', explode(',', (string) $r->all_dept_actions));
            if (in_array('legacy', $acts, true)) {
                if (!$listing) return $cache[$k] = ['all' => true, 'depts' => [], 'legacy' => true];
                $out['legacy'] = true;
                continue;
            }
            if (in_array('*', $acts, true) || in_array($col, $acts, true)) {
                return $cache[$k] = ['all' => true, 'depts' => [], 'legacy' => false];
            }
            $dept = $r->department_code;
            if (empty($dept)) {
                if ($profile_dept === null) {
                    $u = $CI->db->query('SELECT departemen FROM users WHERE id = ?', [(int) $CI->session->userdata('user_id')])->row();
                    $profile_dept = $u ? (normalize_department_code($u->departemen) ?: '') : '';
                }
                $dept = $profile_dept;
            }
            if ($dept !== '' && !in_array($dept, $out['depts'], true)) $out['depts'][] = $dept;
        }
        return $cache[$k] = $out;
    }
}

if (!function_exists('can_dept_access')) {
    /** Boleh melakukan $action di $menu_key untuk dokumen milik departemen $doc_dept? */
    function can_dept_access($menu_key, $action, $doc_dept) {
        $s = dept_scope($menu_key, $action);
        if ($s['all']) return true;
        $code = normalize_department_code($doc_dept);
        return $code !== null && in_array($code, $s['depts'], true);
    }
}

if (!function_exists('dept_scope_sql')) {
    /**
     * Kondisi SQL untuk kolom departemen dokumen: '1=1' (semua), '1=0'
     * (tidak berhak) atau "kolom IN ('A','B')". $actions boleh array —
     * cakupan digabung (salah satu aksi cukup).
     */
    function dept_scope_sql($menu_key, $actions, $column, $listing = false) {
        $CI =& get_instance();
        $depts = [];
        foreach ((array) $actions as $a) {
            $s = dept_scope($menu_key, $a, $listing);
            if ($s['all']) return '1=1';
            $depts = array_merge($depts, $s['depts']);
        }
        $depts = array_values(array_unique($depts));
        if (empty($depts)) return '1=0';
        // Dokumen lama masih menyimpan kode/nama lama ('Q A', 'PROD', ...) —
        // tanpa ini HOD QAD tidak melihat IRF departemen 'Q A' di antriannya.
        $names  = department_names();
        $values = [];
        foreach ($depts as $d) {
            $values[] = $d;
            if (isset($names[$d])) $values[] = $names[$d];
        }
        foreach (department_code_aliases() as $old => $new) {
            if (in_array($new, $depts, true)) $values[] = $old;
        }
        $values = array_values(array_unique($values));
        return $column . ' IN (' . implode(',', array_map([$CI->db, 'escape'], $values)) . ')';
    }
}

if (!function_exists('dept_scope_is_legacy')) {
    /** True kalau salah satu role yang memberi izin ini masih berstatus 'legacy' (belum diatur ulang). */
    function dept_scope_is_legacy($menu_key, $actions) {
        foreach ((array) $actions as $a) {
            if (!empty(dept_scope($menu_key, $a, true)['legacy'])) return true;
        }
        return false;
    }
}

if (!function_exists('dept_options_for')) {
    /**
     * Pilihan departemen saat membuat dokumen (MAKER). ALL DEPT -> semua
     * departemen; OWN DEPT -> departemen dari role yang memberi izin.
     * Poin 8 (user 2026-09-15): user multi-role memilih departemen dokumen
     * dari role yang dimilikinya.
     *
     * @return array [kode => nama]
     */
    function dept_options_for($menu_key, $action = 'add') {
        $names = department_names();
        $s = dept_scope($menu_key, $action);
        if (!empty($s['legacy'])) {
            // Role lama (belum diatur ulang): tetap departemen profil seperti dulu.
            $own = default_dept_for($names);
            return ($own !== null && isset($names[$own])) ? [$own => $names[$own]] : $names;
        }
        if ($s['all']) return $names;
        $out = [];
        foreach ($s['depts'] as $d) $out[$d] = $names[$d] ?? $d;
        asort($out);
        return $out;
    }
}

if (!function_exists('default_dept_for')) {
    /** Departemen awal di form: departemen profil user kalau termasuk pilihan, kalau tidak pilihan pertama. */
    function default_dept_for(array $options) {
        $CI =& get_instance();
        $u = $CI->db->query('SELECT departemen FROM users WHERE id = ?', [(int) $CI->session->userdata('user_id')])->row();
        $own = $u ? normalize_department_code($u->departemen) : null;
        if ($own !== null && isset($options[$own])) return $own;
        return $options ? array_key_first($options) : null;
    }
}

if (!function_exists('users_in_dept_scope')) {
    /**
     * Dari daftar user id, sisakan yang cakupan izinnya mencakup $doc_dept.
     * Dipakai untuk notifikasi approval: HOD hanya diberi tahu dokumen
     * departemennya, pemegang ALL DEPT (Accounting, Direktur) tetap semua.
     */
    function users_in_dept_scope(array $user_ids, $menu_key, $column, $doc_dept) {
        if (empty($user_ids) || !db_has_column('role_permissions', 'all_dept_actions')) return $user_ids;
        $CI =& get_instance();
        $col = preg_replace('/^can_/', '', $column);
        $dept = normalize_department_code($doc_dept);
        $ids = implode(',', array_map('intval', $user_ids));
        $rows = $CI->db->query(
            'SELECT DISTINCT u.id, u.departemen, r.id AS role_id, r.department_code, rp.all_dept_actions
               FROM users u
               JOIN ' . user_roles_source_sql() . ' ur ON ur.user_id = u.id
               JOIN roles r ON r.id = ur.role_id
               JOIN role_permissions rp ON rp.role_id = r.id
               JOIN menus m ON m.id = rp.menu_id
              WHERE u.id IN (' . $ids . ') AND LOWER(m.menu_key) = ? AND rp.' . $column . ' = 1',
            [strtolower($menu_key)]
        )->result();
        $keep = [];
        foreach ($rows as $r) {
            $acts = array_map('trim', explode(',', (string) $r->all_dept_actions));
            $role_dept = $r->department_code ?: normalize_department_code($r->departemen);
            if ((int) $r->role_id === 1 || in_array('*', $acts, true) || in_array('legacy', $acts, true) || in_array($col, $acts, true) || ($dept !== null && $role_dept === $dept)) {
                $keep[(int) $r->id] = true;
            }
        }
        return array_keys($keep);
    }
}

if (!function_exists('approval_stage_scope_sql')) {
    /**
     * Kondisi SQL antrian approval 3-tahap yang sudah dibatasi departemen:
     *   (status tahap HOD AND dept dalam cakupan head_dept)
     *   OR (status tahap Endorse AND dept dalam cakupan endorsement) ...
     *
     * @param array $stage_map ['head_dept' => [status...], 'endorsement' => [...], 'approval' => [...]]
     * @return string '1=0' kalau user tidak punya tahap apa pun
     */
    function approval_stage_scope_sql($menu_key, $status_column, $dept_column, array $stage_map) {
        $CI =& get_instance();
        $parts = [];
        foreach ($stage_map as $action => $statuses) {
            if (!check_access($menu_key, $action) || empty($statuses)) continue;
            $scope = dept_scope_sql($menu_key, $action, $dept_column);
            if ($scope === '1=0') continue;
            $parts[] = '(' . $status_column . ' IN (' . implode(',', array_map([$CI->db, 'escape'], $statuses)) . ') AND ' . $scope . ')';
        }
        return $parts ? '(' . implode(' OR ', $parts) . ')' : '1=0';
    }
}

if (!function_exists('approval_action_for_status')) {
    /** Aksi approval (head_dept/endorsement/approval) yang berlaku untuk status dokumen, atau null. */
    function approval_action_for_status($status, array $stage_map) {
        foreach ($stage_map as $action => $statuses) {
            if (in_array($status, $statuses, true)) return $action;
        }
        return null;
    }
}

if (!function_exists('can_view_dept_doc')) {
    /**
     * Boleh membuka DETAIL/cetak satu dokumen? Aturannya sama dengan yang
     * tampil di daftar (tahap 3) — supaya dokumen departemen lain tidak bisa
     * dibuka hanya dengan mengetik URL-nya.
     *
     * Boleh kalau: Admin; pemilik dokumen; $open (mis. status sudah final &
     * memang terlihat semua orang di daftar, atau user Finance); role lama
     * 'legacy' (perilaku lama); atau punya izin view/edit/submit/delete di
     * menu dokumen ATAU izin approval, untuk departemen dokumen ini.
     */
    function can_view_dept_doc($menu_key, $approval_menu_key, $doc_dept, $is_owner = false, $open = false) {
        if ($is_owner || $open || is_admin()) return true;
        $doc_actions = ['view', 'edit', 'submit', 'delete'];
        $apr_actions = ['view', 'head_dept', 'endorsement', 'approval'];
        if (dept_scope_is_legacy($menu_key, $doc_actions) || ($approval_menu_key && dept_scope_is_legacy($approval_menu_key, $apr_actions))) {
            return true;
        }
        foreach ($doc_actions as $a) {
            if (can_dept_access($menu_key, $a, $doc_dept)) return true;
        }
        if ($approval_menu_key) {
            foreach ($apr_actions as $a) {
                if (can_dept_access($approval_menu_key, $a, $doc_dept)) return true;
            }
        }
        return false;
    }
}
