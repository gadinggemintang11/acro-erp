<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Zz_pdf_diff extends CI_Controller {
    private function out($t) { fwrite(STDERR, $t . "\n"); }
    public function index() {
        if (!is_cli()) show_404();
        $_SESSION = ['user_id' => 1, 'role_id' => 1];
        $this->load->model('Role_model'); $this->load->library('role_template');
        $cols = ['view','add','edit','submit','confirm','head_dept','endorsement','approval','delete'];
        $label = ['view'=>'Lihat','add'=>'Buat','edit'=>'Ubah','submit'=>'Submit','confirm'=>'Confirm','head_dept'=>'Approve HOD','endorsement'=>'Endorse','approval'=>'Final Approve','delete'=>'Hapus'];
        $menus = []; foreach ($this->db->select('id, menu_key, menu_name')->get('menus')->result() as $m) $menus[(int)$m->id] = $m;
        $keys = array_map(function($m){ return $m->menu_key; }, $menus);

        $roles = $this->db->query("SELECT id, role_name, department_code, position_code FROM roles WHERE department_code IS NOT NULL AND position_code IS NOT NULL ORDER BY department_code, position_code")->result();
        $tot_add = 0; $tot_del = 0;
        foreach ($roles as $r) {
            $build = $this->role_template->build($r->department_code, $r->position_code, $keys);
            if (!$build) { $this->out("  {$r->role_name}: tidak ada aturan PDF"); continue; }

            $now = [];
            foreach ($this->db->get_where('role_permissions', ['role_id' => $r->id])->result() as $p) {
                $k = $menus[(int)$p->menu_id]->menu_key ?? null; if (!$k) continue;
                foreach ($cols as $c) if (!empty($p->{'can_'.$c})) $now[$k][$c] = 1;
            }
            $add = []; $del = [];
            foreach ($build['override'] as $k => $c2) {
                foreach ($cols as $c) {
                    $want = !empty($c2[$c]); $have = !empty($now[$k][$c]);
                    if ($want && !$have) $add[] = ($menus_name = $k) . ':' . $label[$c];
                    if (!$want && $have) $del[] = $k . ':' . $label[$c];
                }
            }
            // Pisahkan: pencabutan hak APPROVAL (berisiko) vs sekadar "Lihat".
            $del_approval = array_values(array_filter($del, function ($x) {
                return preg_match('/(Approve HOD|Endorse|Final Approve|Confirm)$/', $x);
            }));
            $del_view = array_values(array_filter($del, function ($x) { return substr($x, -5) === 'Lihat'; }));
            $del_other = array_values(array_diff($del, $del_approval, $del_view));

            // Pencabutan yang TIDAK berdampak: kolom approval di halaman
            // status/laporan (aplikasi tidak pernah memeriksanya) dan kolom
            // approval di menu dokumen utama (yang diperiksa menu *_approval).
            $inert_menu = '/(_status|_list|_outstanding_|irf_status_|material_outstanding)/';
            $has_appr_menu = ['consumable_request','prf','erf','material_request','sales_contract','purchase_order','purchase_order_general','purchase_order_jasa','purchase_order_asset'];
            $inert = 0;
            foreach ($del as $x) {
                [$mk, $act] = explode(':', $x, 2);
                $is_appr = preg_match('/(Approve HOD|Endorse|Final Approve|Confirm)$/', $act);
                if (preg_match($inert_menu, $mk) && $is_appr) { $inert++; continue; }
                if ($is_appr && in_array($mk, $has_appr_menu, true)) $inert++;
            }
            $GLOBALS['inert'] = ($GLOBALS['inert'] ?? 0) + $inert;

            $mon = 0;
            foreach ($build['monitor_view'] as $k) if (empty($now[$k]['view'])) $mon++;
            $tot_add += count($add) + $mon; $tot_del += count($del);
            $this->out(sprintf("\n%-30s %s/%s  -> tambah %d, cabut %d%s",
                $r->role_name, $r->department_code, $r->position_code, count($add) + $mon, count($del),
                $mon ? " (termasuk $mon menu View dari Monitoring)" : ''));
            foreach ($del_view as $x) { [$mk,] = explode(':', $x, 2); $GLOBALS['viewdel'][$mk] = ($GLOBALS['viewdel'][$mk] ?? 0) + 1; }
            $this->out(sprintf('   rincian cabut: %d hak approval/confirm, %d hak "Lihat", %d lainnya (Buat/Ubah/Submit/Hapus)',
                count($del_approval), count($del_view), count($del_other)));
            if ($del_approval) $this->out('   >> APPROVAL DICABUT: ' . implode(', ', $del_approval));
            if ($del_other) $this->out('   >> AKSI DICABUT    : ' . implode(', ', array_slice($del_other, 0, 14)) . (count($del_other) > 14 ? ' ... +' . (count($del_other) - 14) : ''));
        }
        if (!empty($GLOBALS['viewdel'])) { arsort($GLOBALS['viewdel']); $this->out("\nMenu yang kehilangan hak LIHAT (menu => berapa role):"); foreach ($GLOBALS['viewdel'] as $mk => $n) $this->out(sprintf('   %-42s %d role', $mk, $n)); }
        $this->out(sprintf("\nTOTAL: tambah %d izin, cabut %d izin.\n  dari yang dicabut: %d tidak berdampak (kolom approval di halaman status/laporan & di menu dokumen utama yang approval-nya dijaga menu *_approval), %d sisanya nyata.", $tot_add, $tot_del, $GLOBALS['inert'] ?? 0, $tot_del - ($GLOBALS['inert'] ?? 0)));
    }
}
