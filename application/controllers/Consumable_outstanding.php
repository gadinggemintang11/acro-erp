<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consumable_outstanding extends MY_Controller {

    /** Sinkron dengan Consumable_request::CATEGORIES. */
    const CATEGORIES = ['General', 'Produksi'];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Consumable_outstanding_model', 'Purchase_request_model', 'Consumable_request_model']);
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    /**
     * @param string|null $category_slug 'general'/'produksi' dari URL segment
     *        (pintu sidebar terkunci) — null berarti akses lama tanpa segment.
     */
    public function index($category_slug = null) {
        $locked_category = $this->normalize_category_slug($category_slug);

        if ($locked_category !== null) {
            if (!$this->can_category('consumable_outstanding', $locked_category, 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $visible_categories = [$locked_category];
        } else {
            $visible_categories = $this->allowed_categories('consumable_outstanding', 'view');
            // 'Jasa' (ERP Revision Notes 2026-09-03) TIDAK dapat pintu sidebar
            // sendiri spt General/Produksi (allowed_categories() di MY_Controller
            // cuma tahu 2 itu) — cukup gerbang blanket lama biar tidak
            // hilang/invisible dari list Outstanding.
            if (check_access('consumable_outstanding', 'view')) {
                $visible_categories[] = 'Jasa';
            }
            if (empty($visible_categories)) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        $category_filter = $locked_category;
        if ($category_filter === null) {
            $requested = $this->input->get('category');
            if (!empty($requested) && in_array($requested, $visible_categories, true)) {
                $category_filter = $requested;
            }
        }

        $filters = [
            'search'     => $this->input->get('search'),
            'department' => $this->input->get('department'),
            'category'   => $category_filter ?: $visible_categories,
            'date_from'  => $this->input->get('date_from'),
            'date_to'    => $this->input->get('date_to'),
        ];

        $title = lang_line('title_co_outstanding_index');
        if ($locked_category) { $title .= ' - ' . $locked_category; }

        $data = [
            'title'            => $title,
            'records'          => $this->Consumable_outstanding_model->get_grouped_by_item($filters),
            'departments'      => $this->Consumable_outstanding_model->get_departments(),
            'filters'          => array_merge($filters, ['category' => $category_filter]),
            'categories'       => $visible_categories,
            'locked_category'  => $locked_category,
            'category_slug'    => $category_slug,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_outstanding/index', $data);
        $this->load->view('templates/footer');
    }

    public function detail($item_id, $item_type = 'inventory', $category = null) {
        if (empty($item_id) || !is_numeric($item_id)) { show_404(); }
        // Item bisa berasal dari inventories/machines/assets/jasa — id-nya bisa
        // bentrok antar tabel. 'asset'/'jasa' (ERP Revision Notes 2026-09-03)
        // ditambahkan sejak item picker IRF diperluas ke Master Asset+Jasa.
        if (!in_array($item_type, ['inventory', 'machine', 'asset', 'jasa'], true)) { $item_type = 'inventory'; }
        // Item yang sama bisa outstanding di 2 kategori sekaligus (general & produksi).
        // 'Jasa' ditangani terpisah di bawah (tidak punya pintu sidebar sendiri).
        if ($category !== 'Jasa' && !in_array($category, self::CATEGORIES, true)) { $category = null; }

        if ($category === 'Jasa') {
            // Jasa tidak dipecah pintu sidebar spt General/Produksi — cukup
            // gerbang blanket lama.
            if (!check_access('consumable_outstanding', 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $category_filter = 'Jasa';
        } elseif ($category !== null) {
            if (!$this->can_category('consumable_outstanding', $category, 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $category_filter = $category;
        } else {
            // Tanpa kategori spesifik di URL -> batasi ke kategori yang memang
            // boleh dilihat user ini (bukan blanket gabungan lagi).
            $category_filter = $this->allowed_categories('consumable_outstanding', 'view');
            if (check_access('consumable_outstanding', 'view')) {
                $category_filter[] = 'Jasa';
            }
            if (empty($category_filter)) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        $rows = $this->Consumable_outstanding_model->get_breakdown_by_item($item_id, $item_type, $category_filter);

        if (empty($rows)) {
            $this->session->set_flashdata('error', 'Tidak ada outstanding untuk item ini.');
            redirect('consumable_outstanding');
            return;
        }

        $first = $rows[0];
        $total_outstanding = 0;
        foreach ($rows as $r) { $total_outstanding += (float)$r->qty_outstanding; }

        // Qty PR dikurangi stok gudang & PR sebelumnya (user 2026-09-15) —
        // ditampilkan per baris supaya jelas kenapa qty PR lebih kecil dari
        // outstanding. Stok per kategori dipakai JS utk total baris yang dicentang.
        $need = $this->Consumable_outstanding_model->get_pr_need_map(array_map(function ($r) { return (int) $r->cr_detail_id; }, $rows));
        $stock_by_category = [];
        foreach ($rows as $r) {
            $n = $need[(int) $r->cr_detail_id] ?? null;
            $r->qty_on_pr = $n ? (float) $n->qty_on_pr : 0.0;
            $r->qty_pr    = $n ? (float) $n->qty_pr : (float) $r->qty_outstanding;
            if ($n && !isset($stock_by_category[$r->category])) {
                $stock_by_category[$r->category] = (float) $n->stock_total;
            }
        }

        $data = [
            'title'             => sprintf(lang_line('title_co_outstanding_detail'), $first->item_name),
            'item_id'           => (int)$item_id,
            'item_type'         => $item_type,
            'category'          => $category,
            'item_code'         => $first->item_code,
            'item_name'         => $first->item_name,
            'item_unit'         => $first->item_unit,
            'rows'              => $rows,
            'total_outstanding' => $total_outstanding,
            'stock_by_category' => $stock_by_category,
            // PR yang sudah dibuat per baris -> keterangan + link (user 2026-09-20).
            'pr_docs'           => $this->Consumable_outstanding_model->get_pr_docs_map(
                array_map(function ($r) { return (int) $r->cr_detail_id; }, $rows)
            ),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_outstanding/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Create a Draft PR from selected outstanding CR detail rows.
     * POST: cr_detail_ids[] - array of consumable_request_details.id
     */
    /**
     * Buat SATU PR untuk SEMUA sisa outstanding milik 1 IRF — sekali klik.
     *
     * Latar belakang (user 2026-09-10: "PR ya bisa dibuat beberapa barang
     * kok"): tombol "Buat PR Sisanya" di halaman detail pengeluaran memang
     * sudah bisa memasukkan banyak barang ke 1 PR, TAPI tombol itu cuma
     * muncul kalau dokumen pengeluarannya berhasil dibuat lalu Confirmed
     * secara partial. Kalau SEMUA item stoknya 0, dokumennya ditolak sejak
     * awal (Consumable_out_model::create()) — tidak ada dokumen, jadi tombol
     * itu tidak pernah tampil, dan satu-satunya jalan tersisa adalah halaman
     * IRF Outstanding yang drill-down-nya per barang (1 PR per barang).
     *
     * Endpoint ini menutup celah itu: dipakai tombol "Buat PR" di popup galat
     * stok kurang (Consumable_out::_pr_action_for_shortage()), mengumpulkan
     * SEMUA baris IRF yang masih outstanding lalu menyerahkannya ke
     * create_pr() yang sudah ada — termasuk pengecekan izin, agregasi qty,
     * dan pemecahan per type (jasa/asset/non_produksi) di sana.
     */
    public function create_pr_from_irf($request_id) {
        $request_id = (int) $request_id;
        if ($request_id <= 0) { show_404(); return; }

        $irf = $this->Consumable_request_model->get_header($request_id);
        if (!$irf) { show_404(); return; }

        $details = $this->Consumable_request_model->get_details($request_id);
        $detail_ids = array_map(function ($d) { return (int) $d->id; }, $details);

        $outstanding_ids = [];
        if (!empty($detail_ids)) {
            foreach ($this->Consumable_outstanding_model->get_current_outstanding_map($detail_ids) as $row) {
                if ((float) $row->qty_outstanding > 0.0001) {
                    $outstanding_ids[] = (int) $row->cr_detail_id;
                }
            }
        }

        if (empty($outstanding_ids)) {
            $this->session->set_flashdata('error', 'Tidak ada sisa outstanding pada Permintaan Barang ' . ($irf->request_number ?? $request_id) . '.');
            redirect('consumable_request/detail/' . $request_id);
            return;
        }

        // Delegasi ke create_pr(). Sengaja lewat $_POST supaya SELURUH logika
        // di sana (izin per kategori, agregasi, split per type, redirect,
        // pesan sukses) dipakai apa adanya — bukan disalin ulang di sini,
        // yang cepat atau lambat pasti melenceng dari aslinya.
        $_POST['cr_detail_ids'] = $outstanding_ids;
        $_POST['redirect_to']   = base_url('consumable_request/detail/' . $request_id);
        return $this->create_pr();
    }

    public function create_pr() {
        // PR dari IRF selalu General/Jasa/Asset (tidak pernah Produksi), jadi
        // izinnya dicek per tipe baris di bawah. Di sini cukup pastikan user
        // boleh membuat SALAH SATU jenisnya.
        if (!check_access('purchase_request_general', 'add')
                && !check_access('purchase_request_jasa', 'add')
                && !check_access('purchase_request_asset', 'add')) {
            show_error('Anda tidak memiliki izin untuk membuat Purchase Request.', 403);
            return;
        }

        $cr_detail_ids = $this->input->post('cr_detail_ids');
        if (empty($cr_detail_ids) || !is_array($cr_detail_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu baris outstanding untuk dibuatkan PR.');
            redirect($this->input->post('redirect_to') ?: 'consumable_outstanding');
            return;
        }

        // Qty PR = outstanding - yang sudah ada di PR aktif - yang bisa dipenuhi
        // stok gudang (user 2026-09-15: "minta 10, stok 2 -> PR 8"). Lihat
        // Consumable_outstanding_model::get_pr_need_map().
        $map = $this->Consumable_outstanding_model->get_pr_need_map($cr_detail_ids);
        if (empty($map)) {
            $this->session->set_flashdata('error', 'Baris outstanding tidak ditemukan / sudah tidak valid.');
            redirect('consumable_outstanding');
            return;
        }

        // Baris yang dipilih bisa datang dari halaman kategori tertentu, tapi
        // form bisa saja "dibantu" URL/POST manual — cek ulang izin per
        // kategori yang benar-benar terwakili di baris yang dipilih. 'Jasa'
        // (ERP Revision Notes 2026-09-03) tidak dipecah pintu sidebar spt
        // General/Produksi — cukup gerbang blanket lama.
        foreach ($map as $row) {
            $row_category = !empty($row->category) ? $row->category : 'General';
            if ($row_category === 'Jasa') {
                if (!check_access('consumable_outstanding', 'view')) {
                    show_error('Anda tidak memiliki izin untuk outstanding kategori Jasa.', 403);
                    return;
                }
            } elseif (!$this->can_category('consumable_outstanding', $row_category, 'view')) {
                show_error('Anda tidak memiliki izin untuk outstanding kategori ' . $row_category . '.', 403);
                return;
            }
        }

        // Reklasifikasi Asset (ERP Revision Notes 2026-09-03,
        // [[revision_irf_auto_pr_at_issuance]]): Accounting bisa menandai
        // baris outstanding General/Produksi (item_type inventory/machine)
        // supaya PR-nya jadi type='asset' alih-alih 'non_produksi' — dicek
        // di titik PEMBUATAN PR ini (bukan belakangan saat PO/bayar hutang),
        // karena po_header.type='asset' harus sudah fix sejak PO diciptakan
        // (jalur akuntansi asset beda total dari GRN/AP Invoice biasa).
        // Cuma berlaku kalau user punya izin bikin PR Asset.
        $reclassify_asset = $this->input->post('reclassify_asset') ? true : false;
        $can_reclassify_asset = check_access('purchase_request_asset', 'add');
        if ($reclassify_asset && !$can_reclassify_asset) {
            show_error('Anda tidak memiliki izin untuk mereklasifikasi item jadi Asset.', 403);
            return;
        }

        // Aggregate by item_id + item_type + category (sum qty across CRs),
        // SEKALIGUS tentukan PR type per baris:
        // - item_type 'jasa'  -> type 'jasa' (butuh PO Jasa + GRN service)
        // - item_type 'asset' -> type 'asset' (sudah dipilih dari Master Asset
        //   sejak IRF dibuat, lihat item picker di [[revision_irf_auto_pr_at_issuance]])
        // - direklasifikasi Accounting (checkbox di atas) -> type 'asset'
        // - selain itu -> type 'non_produksi' (perilaku lama)
        // 1 PR HEADER cuma boleh 1 type — kalau baris yang dipilih ternyata
        // campuran, di-split jadi BEBERAPA PR (lihat $by_type di bawah).
        // item_type & category tetap wajib ikut jadi kunci per alasan lama:
        // id inventories/machines/assets/jasa bisa bentrok, dan kategori
        // General/Produksi tidak boleh nyampur pas diteruskan ke PO & SGR.
        $by_item      = [];
        $cr_numbers   = [];
        $first_dept   = null;
        $requestor_ids = [];

        $covered  = []; // baris yang sisanya sudah tertutup stok / PR sebelumnya
        $deducted = []; // baris yang qty PR-nya dikurangi stok / PR sebelumnya
        foreach ($map as $row) {
            $qty = (float)$row->qty_pr;
            if ($qty <= 0) {
                if ((float) $row->qty_outstanding > 0) {
                    $covered[] = $row->item_name . ' (' . $row->request_number . ')';
                }
                continue;
            }
            if ((float) $row->qty_from_stock > 0 || (float) $row->qty_on_pr > 0) {
                $deducted[] = $row->item_name . ': diminta sisa ' . (float) $row->qty_outstanding
                    . ((float) $row->qty_from_stock > 0 ? ', dari stok ' . (float) $row->qty_from_stock : '')
                    . ((float) $row->qty_on_pr > 0 ? ', sudah di PR lain ' . (float) $row->qty_on_pr : '')
                    . ', jadi PR ' . $qty;
            }

            $itype    = !empty($row->item_type) ? $row->item_type : 'inventory';
            $category = !empty($row->category) ? $row->category : 'General';

            if ($reclassify_asset && $can_reclassify_asset && in_array($itype, ['inventory', 'machine'], true)) {
                $pr_type = 'asset';
            } elseif ($itype === 'jasa') {
                $pr_type = 'jasa';
            } elseif ($itype === 'asset') {
                $pr_type = 'asset';
            } else {
                $pr_type = 'non_produksi';
            }

            // Izin sesuai jenis PR yang benar-benar akan dibuat.
            $pr_menu = pr_menu_for_item_type($itype, $reclassify_asset && $can_reclassify_asset);
            if (!check_access($pr_menu, 'add')) {
                show_error('Anda tidak memiliki izin untuk membuat PR jenis ini (' . $pr_menu . ').', 403);
                return;
            }

            $key = $itype . '-' . (int)$row->item_id . '-' . $category . '-' . $pr_type;
            if (!isset($by_item[$key])) {
                $by_item[$key] = [
                    'item_id'   => (int)$row->item_id,
                    'item_type' => $itype,
                    'category'  => $category,
                    'pr_type'   => $pr_type,
                    'item_code' => $row->item_code,
                    'item_name' => $row->item_name,
                    'item_unit' => $row->item_unit,
                    'qty'       => 0,
                    'cr_refs'   => [],
                    'cr_links'  => [],
                ];
            }
            $by_item[$key]['qty'] += $qty;
            $by_item[$key]['cr_refs'][] = $row->request_number;
            // Dicatat per cr_detail_id + qty supaya bisa ditulis structural ke
            // pr_detail_cr_links di bawah (bukan cuma teks di remarks) — lihat
            // db/migration_pr_detail_cr_links.sql & [[revision_irf_auto_pr_at_issuance]].
            $by_item[$key]['cr_links'][] = ['cr_detail_id' => (int) $row->cr_detail_id, 'qty' => $qty];
            $cr_numbers[$row->request_number] = true;
            if ($first_dept === null && !empty($row->department)) {
                $first_dept = $row->department;
            }
            if (!empty($row->created_by)) {
                $requestor_ids[(int)$row->created_by] = true;
            }
        }

        if (empty($by_item)) {
            $this->session->set_flashdata('error', !empty($covered)
                ? 'Tidak perlu PR: sisa ' . implode(', ', $covered) . ' sudah bisa dipenuhi dari stok gudang atau sudah ada di PR sebelumnya.'
                : 'Tidak ada qty outstanding > 0 pada baris yang dipilih.');
            redirect($this->input->post('redirect_to') ?: 'consumable_outstanding');
            return;
        }

        $cr_list = implode(', ', array_keys($cr_numbers));
        $user_id = $this->session->userdata('user_id');

        // Requester PR = requestor IRF aslinya, kalau semua baris yang dipilih
        // berasal dari satu orang yang sama. Kalau baris yang dipilih gabungan
        // dari beberapa requestor IRF berbeda (batch PR gabungan), field
        // requester tidak bisa mewakili satu orang — jatuhkan ke user yang
        // membuat PR ini (purchasing/gudang), sama seperti perilaku lama.
        $requester_id = (count($requestor_ids) === 1) ? array_key_first($requestor_ids) : $user_id;

        // Kelompokkan per pr_type — tiap type dapat PR HEADER sendiri.
        $rows_by_type = [];
        foreach ($by_item as $row) {
            $rows_by_type[$row['pr_type']][] = $row;
        }

        // Controller detail tujuan per type, dipakai utk redirect & pesan sukses.
        $detail_controller_by_type = [
            'non_produksi' => 'purchase_request_general',
            'jasa'         => 'purchase_request_jasa',
            'asset'        => 'purchase_request_asset',
        ];

        $this->db->trans_start();

        $created_prs = []; // [type => ['pr_number' => ..., 'pr_id' => ...]]
        $now = date('Y-m-d H:i:s');

        foreach ($rows_by_type as $pr_type => $rows) {
            // [TIDAK BERLAKU sejak 2026-11-05 -- PR kini dibuat Draft & wajib approval 1 tahap]
            // Catatan lama: PR ini sengaja langsung berstatus Approved (skip HOD/Endorsement/
            // Final): sumbernya adalah outstanding dari IRF yang SUDAH lolos
            // approval 3-stage IRF sendiri, jadi tidak perlu approval PR
            // terpisah. Jejak auditnya dicatat di `remarks` (nomor IRF sumber)
            // alih-alih memalsukan identitas approver IRF di kolom approval PR.
            $header_data = [
                // Nomor PR memakai departemen IRF sumbernya, bukan departemen
                // user yang menekan tombol -- lihat generate_pr_number().
                'pr_number'          => $this->Purchase_request_model->generate_pr_number($pr_type, $first_dept),
                'pr_date'            => date('Y-m-d'),
                'department'         => $first_dept ?: '',
                'type'               => $pr_type,
                'purpose'            => 'Pengadaan untuk outstanding consumable request',
                'remarks'            => 'Dibuat dari outstanding Permintaan Barang. Sumber: ' . $cr_list
                                        . ($pr_type === 'asset' && $reclassify_asset ? ' (direklasifikasi jadi Asset)' : ''),
                'requester_id'       => $requester_id,
                'created_by'         => $user_id,
                // Sejak 2026-11-05 PR WAJIB di-approve (1 tahap, spt PO) -- dibuat Draft,
                // pembuat memeriksa/menambah item lalu Submit. Dulu langsung 'Approved'.
                'status'             => 'Draft',

            ];

            $pr_id = $this->Purchase_request_model->insert_header($header_data);
            if (!$pr_id) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Gagal membuat header Purchase Request (' . $pr_type . ').');
                redirect('consumable_outstanding');
                return;
            }

            foreach ($rows as $row) {
                $remarks = '[' . $row['category'] . '] Outstanding dari: ' . implode(', ', array_unique($row['cr_refs']));
                $pr_detail_id = $this->Purchase_request_model->insert_item([
                    'pr_header_id' => $pr_id,
                    'item_id'      => $row['item_id'],
                    'item_type'    => $row['item_type'],
                    'category'     => $row['category'],
                    'qty'          => $row['qty'],
                    'description'  => '',
                    'remarks'      => $remarks,
                ]);

                // Link structural balik ke cr_detail asal (bukan cuma teks di
                // atas) — dipakai menelusuri penyelesaian Jasa balik ke IRF.
                // Lihat db/migration_pr_detail_cr_links.sql.
                if ($pr_detail_id) {
                    foreach ($row['cr_links'] as $link) {
                        $this->Purchase_request_model->link_cr_detail($pr_detail_id, $link['cr_detail_id'], $link['qty']);
                    }
                }
            }

            $created_prs[] = ['type' => $pr_type, 'pr_number' => $header_data['pr_number'], 'pr_id' => $pr_id];
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan database saat membuat PR.');
            redirect('consumable_outstanding');
            return;
        }

        // 1 PR dibuat -> langsung ke detailnya. Lebih dari 1 (baris yang
        // dipilih campuran General/Produksi + Jasa + Asset) -> tampilkan
        // semua nomor PR yang terbentuk, redirect balik ke Outstanding.
        $deducted_note = !empty($deducted)
            ? ' Qty PR sudah dikurangi stok gudang / PR sebelumnya: ' . implode('; ', $deducted) . '.'
            : '';

        if (count($created_prs) === 1) {
            $only = $created_prs[0];
            $this->session->set_flashdata('success',
                'Purchase Request ' . $only['pr_number'] . ' dibuat sebagai Draft. Periksa / tambah item, ' .
                'lalu Submit untuk approval sebelum bisa dibuatkan PO.' . $deducted_note);
            redirect(($detail_controller_by_type[$only['type']] ?? 'purchase_request_general') . '/detail/' . $only['pr_id']);
            return;
        }

        $numbers = implode(', ', array_map(function ($p) { return $p['pr_number'] . ' (' . $p['type'] . ')'; }, $created_prs));
        $this->session->set_flashdata('success',
            count($created_prs) . ' Purchase Request dibuat sebagai Draft -- Submit masing-masing untuk approval (baris yang dipilih ' .
            'campuran beberapa jenis, jadi dipecah per PR): ' . $numbers . '.' . $deducted_note);
        redirect('consumable_outstanding');
    }
}
