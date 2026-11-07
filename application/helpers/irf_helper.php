<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generate Item Requisition Form (IRF) number.
 * Format: "IRF : <DEPT_CODE><5-digit-sequence>"
 *
 * Sequence is shared between mr_header.mr_number and
 * consumable_request_header.request_number, scoped per department code.
 */
if (!function_exists('generate_irf_number')) {
    /**
     * @param string|null $dept_code departemen dokumen yang dipilih di form (Role Level
     *        tahap 3). Kalau kosong, pakai departemen profil user seperti dulu.
     */
    function generate_irf_number($user_id = null, $dept_code_override = null) {
        $CI =& get_instance();
        $CI->load->database();

        if ($user_id === null) {
            $user_id = $CI->session->userdata('user_id');
        }

        $dept_code = 'XXX';
        if ($user_id) {
            $user = $CI->db->select('departemen')->from('users')->where('id', $user_id)->get()->row();
            if ($user && !empty($user->departemen)) {
                $cc = $CI->db
                    ->select('code')
                    ->from('common_code')
                    ->where('group_code', 'Department')
                    ->group_start()
                        ->where('description', $user->departemen)
                        ->or_where('code', $user->departemen)
                    ->group_end()
                    ->limit(1)
                    ->get()->row();
                if ($cc && !empty($cc->code)) {
                    $dept_code = strtoupper(preg_replace('/\s+/', '', $cc->code));
                }
            }
        }

        if (!empty($dept_code_override)) {
            $dept_code = strtoupper(preg_replace('/\s+/', '', $dept_code_override));
        }

        $sql = "
            SELECT MAX(seq) AS max_seq FROM (
                SELECT CAST(SUBSTRING(mr_number, ?) AS UNSIGNED) AS seq
                FROM mr_header
                WHERE mr_number LIKE ?
                UNION ALL
                SELECT CAST(SUBSTRING(request_number, ?) AS UNSIGNED) AS seq
                FROM consumable_request_header
                WHERE request_number LIKE ?
            ) t
        ";
        $offset = strlen($dept_code) + 1;
        $like   = $dept_code . '%';
        $row = $CI->db->query($sql, [$offset, $like, $offset, $like])->row();
        $next = ($row && $row->max_seq) ? ((int)$row->max_seq + 1) : 1;

        return $dept_code . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}

/**
 * Look up the most recent unit price (local_price) for an item from
 * approved/closed Purchase Orders. Returns 0.0 if none found.
 *
 * "Approved" in this system maps to po_header.status IN ('Confirmed', 'Closed').
 */
if (!function_exists('get_latest_po_price')) {
    function get_latest_po_price($item_id, $item_type) {
        $CI =& get_instance();
        $CI->load->database();
        $row = $CI->db
            ->select('pod.local_price')
            ->from('po_details pod')
            ->join('po_header poh', 'poh.id = pod.po_header_id', 'inner')
            ->where('pod.item_id', (int)$item_id)
            ->where('pod.item_type', $item_type)
            ->where_in('poh.status', ['Confirmed', 'Closed'])
            ->order_by('poh.po_date', 'DESC')
            ->order_by('poh.id', 'DESC')
            ->limit(1)
            ->get()->row();
        return $row ? (float)$row->local_price : 0.0;
    }
}

/**
 * Resolve user's department code (uppercase, no spaces) for IRF display.
 * Used in the PDF "IRF : DEPT XXXXXX" header.
 */
if (!function_exists('get_user_dept_code')) {
    function get_user_dept_code($user_id) {
        $CI =& get_instance();
        $CI->load->database();
        if (!$user_id) return '';
        $user = $CI->db->select('departemen')->from('users')->where('id', $user_id)->get()->row();
        if (!$user || empty($user->departemen)) return '';
        $cc = $CI->db
            ->select('code')
            ->from('common_code')
            ->where('group_code', 'Department')
            ->group_start()
                ->where('description', $user->departemen)
                ->or_where('code', $user->departemen)
            ->group_end()
            ->limit(1)
            ->get()->row();
        if (!$cc || empty($cc->code)) return '';
        return strtoupper(preg_replace('/\s+/', '', $cc->code));
    }
}

/**
 * Gabungkan "Bukti Serah Terima" dari dua sisi jadi SATU daftar.
 *
 * Konteks (user 2026-09-14): "Bukti pengeluaran itu adalah bukti penerimaan
 * dari user nya" -- secara proses ini SATU dokumen serah terima yang sama,
 * cuma bisa diunggah dari dua halaman (IRF oleh peminta, dokumen pengeluaran
 * oleh gudang). Berkasnya tetap disimpan di tabel modul masing-masing (satu
 * IRF bisa punya banyak dokumen pengeluaran, dan banyak pengeluaran tidak
 * punya IRF sama sekali), jadi yang disatukan cuma tampilannya.
 *
 * Tiap baris hasil dapat 3 penanda tambahan:
 *   owner        'request' | 'out'  -> modul pemilik berkas (penentu URL & hak hapus)
 *   source_label 'Requestor' | 'Gudang'
 *   doc_number   nomor dokumen pengeluaran, kalau berasal dari gudang
 */
if (!function_exists('irf_merge_handover_attachments')) {
    function irf_merge_handover_attachments($from_requestor, $from_warehouse) {
        $rows = [];
        foreach ((array) $from_requestor as $a) {
            $r = clone $a;
            $r->owner        = 'request';
            $r->source_label = 'Requestor';
            $r->doc_number   = null;
            $rows[] = $r;
        }
        foreach ((array) $from_warehouse as $a) {
            $r = clone $a;
            $r->owner        = 'out';
            $r->source_label = 'Gudang';
            $r->doc_number   = $a->doc_number ?? null;
            $rows[] = $r;
        }
        // Terbaru di atas, sama seperti urutan asli tiap modul.
        usort($rows, function ($x, $y) {
            return strcmp((string) ($y->created_at ?? ''), (string) ($x->created_at ?? ''));
        });
        return $rows;
    }
}

if (!function_exists('pr_menu_for_item_type')) {
    /**
     * Menu izin PR yang BENAR untuk sebuah baris outstanding IRF.
     *
     * PR yang lahir dari IRF tidak pernah bertipe Produksi: item jasa -> PR
     * Jasa, item asset (atau direklasifikasi Accounting) -> PR Asset, sisanya
     * -> PR General. Dulu semua jalur ini dijaga izin menu `purchase_request`
     * (PR PRODUKSI/MPR) — jadi gudang yang cuma punya izin PR General tidak
     * pernah melihat tombol "Buat PR Sisanya" (user 2026-09-20: "kenapa
     * sekarang ga ada ya?").
     */
    function pr_menu_for_item_type($item_type, $as_asset = false) {
        $item_type = $item_type ?: 'inventory';
        if ($as_asset && in_array($item_type, ['inventory', 'machine'], true)) return 'purchase_request_asset';
        if ($item_type === 'jasa')  return 'purchase_request_jasa';
        if ($item_type === 'asset') return 'purchase_request_asset';
        return 'purchase_request_general';
    }
}

if (!function_exists('can_create_pr_for_item_types')) {
    /**
     * Boleh membuat PR untuk SEMUA tipe item yang terwakili di baris-baris
     * ini? Dipakai untuk menampilkan tombol "Buat PR": satu klik membuat PR
     * untuk semua baris sekaligus, jadi izinnya harus lengkap — kalau tidak,
     * tombolnya tampil tapi ditolak waktu disubmit.
     *
     * @param array $item_types daftar item_type ('inventory'/'machine'/'asset'/'jasa')
     */
    function can_create_pr_for_item_types(array $item_types) {
        $types = array_unique(array_filter($item_types)) ?: ['inventory'];
        foreach ($types as $t) {
            if (!check_access(pr_menu_for_item_type($t), 'add')) return false;
        }
        return true;
    }
}

if (!function_exists('can_create_pr_from_irf')) {
    /** Sama seperti di atas, tapi tipe itemnya diambil dari detail 1 IRF. */
    function can_create_pr_from_irf($request_id) {
        $CI =& get_instance();
        $rows = $CI->db->select('item_type')->distinct()
            ->where('header_id', (int) $request_id)
            ->get('consumable_request_details')->result();
        return can_create_pr_for_item_types(array_map(function ($r) { return $r->item_type; }, $rows));
    }
}

if (!function_exists('pr_doc_url')) {
    /**
     * Tautan ke detail dokumen PR sesuai tipenya (4 modul terpisah).
     * null = user tidak boleh melihat modul itu -> tampilkan nomornya saja
     * tanpa link, supaya dia tidak mendarat di halaman 403.
     */
    function pr_doc_url($pr_type, $pr_id) {
        $menu = [
            'non_produksi' => 'purchase_request_general',
            'jasa'         => 'purchase_request_jasa',
            'asset'        => 'purchase_request_asset',
            'produksi'     => 'purchase_request',
        ][$pr_type] ?? 'purchase_request_general';

        return check_access($menu, 'view') ? site_url($menu . '/detail/' . (int) $pr_id) : null;
    }
}
