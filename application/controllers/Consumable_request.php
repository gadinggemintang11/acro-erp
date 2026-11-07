<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consumable_request extends MY_Controller {

    /**
     * Status non-Draft yang masih boleh diedit/dihapus item-nya oleh Admin
     * (override). Rejected & Cancelled sengaja dikecualikan (dokumen mati).
     */
    const ADMIN_EDITABLE_STATUSES = ['Submitted', 'Approved Head Dept', 'Endorsed', 'Approved', 'Released'];

    /**
     * Kategori kebutuhan IRF. Barang yang sama (mis. sapu) bisa dipakai untuk
     * kebutuhan office (General) maupun lantai produksi (Produksi) — stock &
     * master item tetap satu, kategori ini cuma menempel di transaksi supaya
     * daftar/outstanding/pengeluaran bisa difilter & di-follow-up terpisah.
     *
     * 'Jasa' (ERP Revision Notes 2026-09-02, [[revision_irf_auto_pr_at_issuance]]):
     * kategori ke-3, TIDAK PERNAH lewat Consumable_out/stock_ledger (jasa
     * bukan barang fisik) — begitu approved, otomatis 100% outstanding &
     * cuma bisa lanjut lewat PR (mekanisme outstanding yg sudah ada).
     * TIDAK ADA pilihan "Asset" di sini — itu keputusan Accounting belakangan
     * (lihat [[revision_irf_auto_pr_at_issuance]]), bukan pilihan requestor.
     */
    const CATEGORIES = ['General', 'Produksi', 'Jasa'];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Consumable_request_model', 'Consumable_out_model']);
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('consumable_request', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $filters = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status', TRUE),
            'category' => $this->input->get('category', TRUE),
            'outstanding_only' => $this->input->get('outstanding_only') ? true : false,
            'visibility' => [
                'user_id'         => $this->session->userdata('user_id'),
                'can_head_dept'   => check_access('consumable_request_approval', 'head_dept'),
                'can_endorsement' => check_access('consumable_request_approval', 'endorsement'),
                'can_approval'    => check_access('consumable_request_approval', 'approval'),
            ],
        ];

        // Pagination 20 baris/halaman (user 2026-10-05). Halaman di luar
        // rentang ditarik balik ke halaman terakhir supaya ?page=99 tidak
        // memberi tabel kosong yang membingungkan.
        $per_page    = 20;
        $total_rows  = $this->Consumable_request_model->count_all($filters);
        $total_pages = max(1, (int) ceil($total_rows / $per_page));
        $page        = max(1, (int) $this->input->get('page'));
        if ($page > $total_pages) $page = $total_pages;
        $offset      = ($page - 1) * $per_page;

        $data = [
            'title' => lang_line('title_cr_create'),
            'requests' => $this->Consumable_request_model->get_all($filters, $per_page, $offset),
            'filters' => $filters,
            'statuses' => ['Draft', 'Submitted', 'Approved Head Dept', 'Endorsed', 'Approved', 'Released', 'Rejected', 'Cancelled'],  // 2 status antara ditambah saat IRF kembali 3 stage (2026-09-10)
            'categories' => self::CATEGORIES,
            'current_page' => $page,
            'total_pages'  => $total_pages,
            'total_rows'   => $total_rows,
            'per_page'     => $per_page,
            'row_offset'   => $offset,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_request/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * IRF APPROVAL — list IRF yang menunggu aksi approval user (3-stage).
     */
    public function approval() {
        if (!check_access('consumable_request_approval', 'view')) {
            show_error('Anda tidak memiliki akses ke modul Approval Permintaan Barang.', 403);
        }

        $can_head_dept   = check_access('consumable_request_approval', 'head_dept');
        $can_endorsement = check_access('consumable_request_approval', 'endorsement');
        $can_approval    = check_access('consumable_request_approval', 'approval');

        $data = [
            'title'           => lang_line('title_cr_approval'),
            'requests'        => $this->Consumable_request_model->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval),
            'can_head_dept'   => $can_head_dept,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'consumable_request_header',
                'number' => 'request_number',
                'date'   => 'request_date',
            ]),
            'my_history_route'    => 'consumable_request/detail',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_request/approval', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('consumable_request', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        // Auto-fill departemen & nama requestor dari user yang login.
        $user_id = $this->session->userdata('user_id');
        $current_user = $this->db->select('fullname, departemen')
            ->from('users')->where('id', $user_id)->get()->row();

        $data = [
            'title' => lang_line('title_cr_create_form'),
            'items_list' => $this->Consumable_request_model->get_inventory_items_with_stock(),
            'request_number' => $this->Consumable_request_model->generate_request_number(),
            'current_user' => $current_user,
            'categories' => self::CATEGORIES,
            // Departemen dokumen dipilih dari role user (Role Level tahap 3, poin 8).
            'dept_options' => dept_options_for('consumable_request', 'add'),
            // Pemohon yang IRF-nya selalu langsung ke Final Approve (Johanes).
        ];
        $data['dept_default'] = default_dept_for($data['dept_options']);

        // Bekal modal "Tambah Item Baru" (quick add master, user 2026-09-09).
        // Bentuknya sama persis dgn yang dipakai detail() saat edit -- lihat
        // _quick_add_config().
        $data['quick_add'] = $this->_quick_add_config();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_request/form', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Bekal modal "Tambah Item Baru" (quick add master): pilihan kategori &
     * satuan per jenis item + izin per master.
     *
     * Dipakai DUA halaman: form Create (add()) dan halaman detail saat mode
     * edit (detail()). User 2026-09-22: "ketika kondisinya edit gabisa" --
     * tombol + di dropdown memang cuma pernah dipasang di form Create,
     * padahal item yang belum ada di master sama seringnya baru ketahuan
     * waktu IRF draft diperbaiki.
     *
     * Semuanya common_code (baris pendek), jadi dikirim sekaligus di page
     * load -- lebih murah daripada 4 request AJAX saat modal dibuka.
     */
    private function _quick_add_config() {
        $this->load->model(['Inventory_model', 'Commoncode_model']);
        return [
            'satuan'    => $this->Commoncode_model->get_by_group('SATUAN'),
            'inventory' => [
                'kategori'  => $this->Inventory_model->get_sub_kategori_list(),
                'item_type' => $this->Inventory_model->get_item_type_list(),
                'can'       => check_access('master_inventory', 'add'),
            ],
            'machine'   => [
                'kategori' => $this->Commoncode_model->get_by_group('MACHINE_CATEGORY'),
                'can'      => check_access('master_machine', 'add'),
            ],
            'asset'     => [
                'kategori' => $this->Commoncode_model->get_by_group('ASSET_CATEGORY'),
                'can'      => check_access('master_asset', 'add'),
            ],
            'jasa'      => [
                'kategori' => $this->Commoncode_model->get_by_group('JASA_CATEGORY'),
                'can'      => check_access('master_jasa', 'add'),
            ],
        ];
    }

    /**
     * Tolak kalau ada barang yang SAMA dipilih di lebih dari satu baris.
     *
     * User 2026-09-10: "saya input IRF 2 item dan 2-nya ga ada stock ... hasil
     * PR nya tetap 1 barang". Ternyata IRF-nya memang berisi barang yang SAMA
     * dua kali (MTC0127 qty 2 + qty 1), dan semua modul di hilir memang
     * menggabungkannya jadi satu baris qty 3 — PR, halaman Outstanding, sampai
     * editor item PR yang terang-terangan menolak item kembar
     * ("Item tersebut sudah ada di baris lain PR ini"). Jadi hasilnya BENAR,
     * cuma tampak seperti "cuma 1 barang" dan itu bikin bingung.
     *
     * Daripada membiarkan penggabungan itu terjadi diam-diam jauh di hilir,
     * duplikatnya dicegat di sini — di titik user masih bisa memperbaikinya.
     *
     * @return string|null pesan galat, atau null kalau aman
     */
    private function _duplicate_item_error($valid_items) {
        $seen = [];
        foreach ($valid_items as $item) {
            $ref = $this->Consumable_request_model->resolve_item_ref($item);
            $key = $ref['item_type'] . '-' . $ref['item_id'];
            if (isset($seen[$key])) {
                $name = $item['item_name'] ?? ('#' . $ref['item_id']);
                return 'Barang "' . $name . '" dipilih lebih dari sekali. '
                     . 'Satu barang cukup satu baris — gabungkan qty-nya jadi satu, '
                     . 'karena sistem memang menjumlahkannya waktu dibuatkan PR.';
            }
            $seen[$key] = true;
        }
        return null;
    }

    public function store() {
        if (!check_access('consumable_request', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $user_id = $this->session->userdata('user_id');
        $request_date = $this->input->post('request_date');
        $department = normalize_department_code($this->input->post('department'));
        if ($department === null || !isset(dept_options_for('consumable_request', 'add')[$department])) {
            $this->session->set_flashdata('error', 'Departemen Permintaan Barang tidak valid atau di luar departemen role Anda.');
            redirect('consumable_request/add');
            return;
        }
        $requestor_name = $this->input->post('requestor_name');
        $category = $this->input->post('category');
        $purpose = $this->input->post('purpose');
        $notes = $this->input->post('notes');
        $items = $this->input->post('items');

        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'General';
        }

        if (empty($request_date)) {
            $this->session->set_flashdata('error', 'Tanggal request wajib diisi.');
            redirect('consumable_request/add');
            return;
        }

        // Keperluan wajib diisi; Catatan opsional (user 2026-09-15).
        if (trim((string) $purpose) === '') {
            $this->session->set_flashdata('error', 'Keperluan wajib diisi.');
            redirect('consumable_request/add');
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal satu item harus ditambahkan.');
            redirect('consumable_request/add');
            return;
        }

        $valid_items = array_filter($items, function($i) {
            return !empty($i['item_id']) && isset($i['qty']) && (float)$i['qty'] > 0;
        });

        if (empty($valid_items)) {
            $this->session->set_flashdata('error', 'Minimal satu item dengan qty > 0 harus diisi.');
            redirect('consumable_request/add');
            return;
        }

        if ($dup = $this->_duplicate_item_error($valid_items)) {
            $this->session->set_flashdata('error', $dup);
            redirect('consumable_request/add');
            return;
        }

        // Cek outstanding per item - requestor tidak boleh bikin IRF baru jika ada outstanding
        foreach ($valid_items as $item) {
            $ref = $this->Consumable_request_model->resolve_item_ref($item);
            $outstanding_irf = $this->Consumable_request_model->check_outstanding_by_item(
                $user_id, $ref['item_id'], $ref['item_type'], $category
            );
            if ($outstanding_irf) {
                $this->session->set_flashdata('error', 'Item ini masih memiliki outstanding di Permintaan Barang ' . htmlspecialchars($outstanding_irf->request_number) . '. Selesaikan dulu.');
                redirect('consumable_request/add');
                return;
            }
        }

        $header_data = [
            // Nomor mengikuti departemen dokumen yang dipilih (bukan profil user).
            'request_number' => $this->Consumable_request_model->generate_request_number($department),
            'request_date' => $request_date,
            'department' => $department,
            'requestor_name' => $requestor_name,
            'category' => $category,
            'purpose' => $purpose,
            'notes' => $notes,
            'status' => 'Draft',
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $result = $this->Consumable_request_model->create($header_data, $valid_items);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Request berhasil dibuat. No: ' . $result['request_number']);
            redirect('consumable_request/detail/' . $result['id']);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
            redirect('consumable_request/add');
        }
    }

    /**
     * Update IRF saat status masih Draft. Inline edit dari detail.php.
     */
    /**
     * AJAX: daftar item + stock utk kategori tertentu — dipanggil dari form
     * add() setiap kali dropdown "Kategori Kebutuhan" diganti, karena stock
     * General & Produksi sekarang benar-benar 2 angka terpisah (gudang
     * fisik terpisah), bukan gabungan.
     */
    public function ajax_items_by_category() {
        if (!$this->input->is_ajax_request()) { show_404(); }

        $category = $this->input->get('category');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'General';
        }

        $items = $this->Consumable_request_model->get_inventory_items_with_stock($category);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'items' => $items]);
    }

    /**
     * AJAX: bikin item master BARU langsung dari form IRF, tanpa pindah
     * halaman.
     *
     * Masalah yang dipecahkan (user 2026-09-09): item yang belum terdaftar di
     * master baru ketahuan pas requestor sudah mengetik beberapa baris item.
     * Kalau dia pergi ke menu Master dulu, seluruh isi form IRF hilang. Jadi
     * penambahan master-nya dilakukan di tempat (modal), lalu item baru
     * disuntik ke dropdown tanpa reload -> baris yang sudah diisi aman.
     *
     * Nulis ke master yang SESUAI jenis item (dropdown IRF memang gabungan 4
     * master, lihat Consumable_request_model::get_inventory_items_with_stock()):
     *   inventory -> inventories (Master_inventory)   machine -> machines (Master_machine)
     *   asset     -> assets      (Master_asset)       jasa    -> jasa     (Master_jasa)
     * Material produksi TIDAK ada di sini karena IRF memang tidak memuatnya
     * (itu ranah MRF/Material_request).
     *
     * Izin dicek DUA lapis: 'consumable_request add' (boleh bikin IRF) DAN
     * 'master_* add' sesuai jenisnya -- jadi fitur ini tidak jadi pintu
     * belakang buat user yang tidak berhak menyentuh master.
     */
    public function ajax_quick_add_item() {
        header('Content-Type: application/json');
        if (!$this->input->is_ajax_request() || $this->input->method() !== 'post') { show_404(); }

        // Modal ini dipanggil dari form Create DAN dari halaman detail saat
        // mode edit -- dua jalur sah dgn izin berbeda ('add' vs 'edit'), jadi
        // dua-duanya diterima. Lapis kedua (master_* add, di bawah) yang jadi
        // penjaga sesungguhnya atas data master.
        if (!check_access('consumable_request', 'add') && !check_access('consumable_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin.']);
            return;
        }

        $type = (string) $this->input->post('item_type');
        $master_menu = [
            'inventory' => ['menu' => 'master_inventory', 'label' => 'Master Barang (Inventory)'],
            'machine'   => ['menu' => 'master_machine',   'label' => 'Master Mesin & Peralatan'],
            'asset'     => ['menu' => 'master_asset',     'label' => 'Master Asset'],
            'jasa'      => ['menu' => 'master_jasa',      'label' => 'Master Jasa'],
        ];
        if (!isset($master_menu[$type])) {
            echo json_encode(['success' => false, 'message' => 'Jenis item tidak dikenal.']);
            return;
        }
        if (!check_access($master_menu[$type]['menu'], 'add')) {
            echo json_encode(['success' => false,
                'message' => 'Anda tidak punya izin menambah data di ' . $master_menu[$type]['label'] . '.']);
            return;
        }

        $nama     = trim((string) $this->input->post('nama'));
        $kode     = trim((string) $this->input->post('kode'));
        $kategori = trim((string) $this->input->post('kategori'));
        $satuan   = trim((string) $this->input->post('satuan'));

        if ($nama === '') {
            echo json_encode(['success' => false, 'message' => 'Nama item wajib diisi.']);
            return;
        }

        // Nama yang sudah ada di master ditolak -- kasus paling sering justru
        // "item-nya ADA tapi statusnya nonaktif / namanya beda dikit", dan
        // bikin kembaran malah bikin stock terpecah 2 baris.
        if ($dupe = $this->_find_master_item_by_name($type, $nama)) {
            echo json_encode(['success' => false, 'message' =>
                'Item dengan nama itu sudah ada di master: ' . $dupe['kode'] . ' - ' . $dupe['nama']
                . ($dupe['active'] ? '. Cari lagi di daftar item.' : '. Statusnya NONAKTIF — aktifkan dulu lewat menu master.')]);
            return;
        }

        $result = $this->_insert_master_item($type, $nama, $kode, $kategori, $satuan);
        if (empty($result['success'])) {
            echo json_encode($result);
            return;
        }

        add_log('CREATE', $master_menu[$type]['label'],
            'Menambah item baru dari form Permintaan Barang: ' . $result['item']['nama_barang'] . ' (Kode: ' . $result['item']['kode_barang'] . ')');

        echo json_encode([
            'success' => true,
            'message' => 'Item ' . $result['item']['kode_barang'] . ' berhasil ditambahkan ke master.',
            'item'    => $result['item'],
        ]);
    }

    /**
     * Cari item bernama sama (persis, case-insensitive) di master $type.
     * Balikin ['kode','nama','active'] atau null.
     */
    private function _find_master_item_by_name($type, $nama) {
        $conf = [
            'inventory' => ['tbl' => 'inventories', 'kode' => 'kode_barang', 'nama' => 'nama_barang', 'aktif' => 'Active'],
            'machine'   => ['tbl' => 'machines',    'kode' => 'kode_mesin',  'nama' => 'nama_mesin',  'aktif' => 'Active'],
            'asset'     => ['tbl' => 'assets',      'kode' => 'kode_asset',  'nama' => 'nama_asset',  'aktif' => 'active'],
            'jasa'      => ['tbl' => 'jasa',        'kode' => 'kode_jasa',   'nama' => 'nama_jasa',   'aktif' => 'active'],
        ][$type];

        $row = $this->db->select("{$conf['kode']} AS kode, {$conf['nama']} AS nama, status")
            ->from($conf['tbl'])
            ->where("LOWER(TRIM({$conf['nama']}))", strtolower($nama))
            ->limit(1)->get()->row();

        if (!$row) return null;
        return [
            'kode'   => $row->kode,
            'nama'   => $row->nama,
            'active' => strcasecmp((string) $row->status, $conf['aktif']) === 0,
        ];
    }

    /**
     * Insert ke master sesuai jenis, memakai model masing-masing (bukan query
     * langsung) supaya aturan penomoran kode tetap satu sumber dengan menu
     * master aslinya. Balikin ['success'=>bool, 'message'?, 'item'?] dengan
     * bentuk `item` PERSIS sama seperti baris
     * get_inventory_items_with_stock() -> bisa langsung didorong ke dropdown.
     */
    private function _insert_master_item($type, $nama, $kode, $kategori, $satuan) {
        $fail = function($msg) { return ['success' => false, 'message' => $msg]; };
        $usage = null; // cuma terisi utk item inventory

        switch ($type) {
            case 'inventory':
                $this->load->model('Inventory_model');
                if ($kategori === '') return $fail('Kategori wajib dipilih untuk item Inventory.');
                // Jenis item (Stock/Non-Stock) ikut form master aslinya;
                // default Stock spt kolom DB.
                $inv_type = $this->input->post('inv_item_type') ?: 'Stock';
                if (!in_array($inv_type, ['Stock', 'Non-Stock'], true)) $inv_type = 'Stock';
                // Jenis pemakaian: dipilih sendiri di modal (user 2026-10-07
                // minta pilihannya ditampakkan). Kalau tidak dikirim -- form
                // lama / DB belum dimigrasi -- jatuh ke kategori IRF yang
                // sedang dibuka, supaya item baru tidak langsung tersaring
                // keluar dari daftar begitu tersimpan.
                // Dihitung SEBELUM generate_kode() karena ikut jadi segmen
                // GNR/PRD di kodenya.
                $usage = Inventory_model::clean_usage_category(
                    $this->input->post('usage_category') ?: $this->input->post('irf_category'));
                if ($kode === '') $kode = $this->Inventory_model->generate_kode($inv_type, $kategori, $usage);
                if ($this->_master_kode_exists('inventories', 'kode_barang', $kode)) return $fail("Kode '$kode' sudah dipakai.");
                $new_id = $this->Inventory_model->insert([
                    'kode_barang' => $kode,
                    'nama_barang' => $nama,
                    'kategori'    => $kategori,
                    'item_type'   => $inv_type,
                    'satuan'      => $satuan ?: null,
                    'status'      => 'Active',
                    'usage_category' => $usage,
                ]);
                if (!$new_id) return $fail('Gagal menyimpan item ke Master Barang.');
                break;

            case 'machine':
                $this->load->model('Machine_model');
                if ($kategori === '') return $fail('Kategori wajib dipilih untuk item Mesin/Peralatan.');
                // Master Mesin tidak punya generator kode (kode existing pun
                // bebas, mis. "Prod_19"), jadi kode WAJIB diisi user -- sama
                // seperti form Master_machine aslinya.
                if ($kode === '') return $fail('Kode mesin wajib diisi (Master Mesin tidak punya kode otomatis).');
                if ($this->_master_kode_exists('machines', 'kode_mesin', $kode)) return $fail("Kode '$kode' sudah dipakai.");
                if (!$this->Machine_model->insert([
                    'kode_mesin' => $kode,
                    'nama_mesin' => $nama,
                    'kategori'   => $kategori,
                    'satuan'     => $satuan ?: 'UNIT',
                    'status'     => 'Active',
                ])) return $fail('Gagal menyimpan item ke Master Mesin & Peralatan.');
                $new_id = $this->db->insert_id();
                break;

            case 'asset':
                $this->load->model('Asset_model');
                if ($kode === '') $kode = $this->Asset_model->generate_kode();
                if ($this->_master_kode_exists('assets', 'kode_asset', $kode)) return $fail("Kode '$kode' sudah dipakai.");
                if (!$this->Asset_model->insert([
                    'kode_asset' => $kode,
                    'nama_asset' => $nama,
                    'kategori'   => $kategori ?: 'LAINNYA',
                    'satuan'     => $satuan ?: 'UNIT',
                    'status'     => 'active',
                ])) return $fail('Gagal menyimpan item ke Master Asset.');
                $new_id = $this->db->insert_id();
                break;

            case 'jasa':
            default:
                $this->load->model('Jasa_model');
                if ($kode === '') $kode = $this->Jasa_model->generate_kode();
                if ($this->_master_kode_exists('jasa', 'kode_jasa', $kode)) return $fail("Kode '$kode' sudah dipakai.");
                if (!$this->Jasa_model->insert([
                    'kode_jasa' => $kode,
                    'nama_jasa' => $nama,
                    'kategori'  => $kategori ?: 'Lainnya',
                    'satuan'    => $satuan ?: 'Lot',
                    'status'    => 'active',
                ])) return $fail('Gagal menyimpan item ke Master Jasa.');
                $new_id = $this->db->insert_id();
                break;
        }

        return ['success' => true, 'item' => [
            'id'          => (int) $new_id,
            'item_key'    => $type . '-' . (int) $new_id,
            'item_type'   => $type,
            'kode_barang' => $kode,
            'nama_barang' => $nama,
            // Fallback satuan disamakan dgn COALESCE di query dropdown.
            'satuan'      => $satuan ?: ($type === 'jasa' ? 'Lot' : ($type === 'inventory' ? '' : 'UNIT')),
            'kategori'    => $kategori,
            'stock'       => 0, // item baru, belum ada stock_ledger
            // Dipakai browser utk memutuskan item ini boleh langsung dipakai
            // di IRF yang sedang dibuka atau tidak (null = master selain
            // inventory, yang memang tidak disaring per kategori).
            'usage_category' => $usage,
        ]];
    }

    private function _master_kode_exists($table, $column, $kode) {
        return (bool) $this->db->where($column, $kode)->limit(1)->get($table)->row();
    }

    public function update($id) {
        $admin = is_admin();
        if (!$admin && !check_access('consumable_request', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) show_404();

        // Draft -> yang punya izin edit untuk departemen IRF ini (tahap 3). Non-Draft -> hanya Admin (override).
        if (!$admin && !can_dept_access('consumable_request', 'edit', $header->department)) {
            show_error('Permintaan Barang ini milik departemen lain — Anda tidak berhak mengubahnya.', 403);
        }
        $admin_override = $admin && $header->status !== 'Draft'
            && in_array($header->status, self::ADMIN_EDITABLE_STATUSES, true);
        if ($header->status !== 'Draft' && !$admin_override) {
            $this->session->set_flashdata('error', 'Hanya request Draft yang bisa diubah.');
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $request_date   = $this->input->post('request_date');
        $department     = $this->input->post('department');
        $requestor_name = $this->input->post('requestor_name');
        $category       = $this->input->post('category');
        $purpose        = $this->input->post('purpose');
        $notes          = $this->input->post('notes');
        $items          = $this->input->post('items');

        if (!in_array($category, self::CATEGORIES, true)) {
            $category = $header->category ?? 'General';
        }

        if (empty($request_date)) {
            $this->session->set_flashdata('error', 'Tanggal request wajib diisi.');
            redirect('consumable_request/detail/' . $id);
            return;
        }
        if (trim((string) $purpose) === '') {
            $this->session->set_flashdata('error', 'Keperluan wajib diisi.');
            redirect('consumable_request/detail/' . $id . '?edit=1');
            return;
        }
        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal satu item harus ada.');
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $valid_items = array_filter($items, function($i) {
            return !empty($i['item_id']) && isset($i['qty']) && (float)$i['qty'] > 0;
        });
        if (empty($valid_items)) {
            $this->session->set_flashdata('error', 'Minimal satu item dengan qty > 0 harus diisi.');
            redirect('consumable_request/detail/' . $id);
            return;
        }

        if ($dup = $this->_duplicate_item_error($valid_items)) {
            $this->session->set_flashdata('error', $dup);
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $header_data = [
            'request_date'   => $request_date,
            // Departemen dokumen tidak berubah lewat edit biasa (menentukan siapa
            // yang submit/approve). Admin boleh mengoreksinya.
            'department'     => ($admin && normalize_department_code($department)) ? normalize_department_code($department) : $header->department,
            'requestor_name' => $requestor_name,
            'category'       => $category,
            'purpose'        => $purpose,
            'notes'          => $notes,
        ];

        $result = $this->Consumable_request_model->update(
            $id, $header_data, $valid_items, $admin_override, $this->session->userdata('user_id')
        );
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Request berhasil diperbarui.');
        } else {
            $this->session->set_flashdata('error', $result['message'] ?? 'Gagal memperbarui.');
        }
        redirect('consumable_request/detail/' . $id);
    }

    public function detail($id) {
        if (!check_access('consumable_request', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). IRF yang sudah
        // final tetap terbuka (sama dengan daftar) supaya gudang bisa memprosesnya.
        if (!can_view_dept_doc('consumable_request', 'consumable_request_approval', $header->department,
                (int) $header->created_by === (int) $this->session->userdata('user_id'),
                in_array($header->status, ['Approved', 'Released', 'Rejected', 'Cancelled'], true))) {
            show_error('Permintaan Barang ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $is_draft_editable = $header->status === 'Draft' && can_dept_access('consumable_request', 'edit', $header->department);

        // Override khusus Admin: boleh edit/hapus daftar item walau IRF sudah lewat
        // status Draft (mis. sudah Approved). Konsisten dgn force-edit di PRF/ERF.
        $can_admin_edit = is_admin() && in_array($header->status, self::ADMIN_EDITABLE_STATUSES, true);

        $editable  = $is_draft_editable || $can_admin_edit;
        $edit_mode = $editable && (int)$this->input->get('edit') === 1;

        // Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
        // requestor & approver mana pun bisa reply selagi status Draft.
        $user_id = $this->session->userdata('user_id');
        $is_requestor = ((int) $header->created_by === (int) $user_id);
        $is_approver_any = check_access('consumable_request_approval', 'head_dept')
            || check_access('consumable_request_approval', 'endorsement')
            || check_access('consumable_request_approval', 'approval');
        // Thread balasan cuma terbuka setelah IRF dikembalikan lewat Back to
        // Draft, pesertanya requestor + approver yang mengembalikan saja
        // (koreksi 2026-09-06 — lihat can_reply_return_thread() di auth_helper).
        $can_reply = can_reply_return_thread($header);

        // IRF lama (dibuat sebelum aturan nilai berlaku) tidak punya harga
        // tersimpan, sehingga dulu SEMUA barangnya tampil "Belum pernah di-PO"
        // walau POnya ada (user 2026-09-16). Untuk baris seperti itu harga PO
        // terakhir dicari saat halaman dibuka -- sebagai ACUAN saja, tidak
        // disimpan & tidak mengubah jalur approval dokumen lama.
        $details = $this->Consumable_request_model->get_details($id);
        $live_prices = [];
        $need_ref = [];
        foreach ($details as $d) {
            if (property_exists($d, 'unit_price') && $d->unit_price === null) {
                $need_ref[] = ['item_id' => $d->item_id, 'item_type' => $d->item_type ?: 'inventory'];
            }
        }
        if ($need_ref) $live_prices = $this->Consumable_request_model->get_last_po_prices($need_ref);
        // Kalau IRF ini sendiri sudah pernah jadi PR -> PO, harga PO ITU yang
        // paling tepat (user 2026-09-16) -- dipakai mendahului "PO terakhir
        // barang ini" di tampilan.
        $irf_po_prices = $need_ref ? $this->Consumable_request_model->get_po_prices_for_irf($id) : [];

        // Tombol Approve di detail HANYA kalau dibuka dari antrian IRF Approval
        // (user 2026-09-17) — dari daftar IRF, notifikasi, dll tetap tanpa
        // tombol. Tahapnya mengikuti status IRF, dan dicek sama persis dengan
        // endpoint approve_*: izin tahap itu + cakupan departemen.
        $approval_action = null;
        $from_approval = $this->input->get('from') === 'approval';
        if ($from_approval) {
            $stage = approval_action_for_status($header->status, Consumable_request_model::APPROVAL_STAGE_MAP);
            if ($stage && check_access('consumable_request_approval', $stage)
                    && can_dept_access('consumable_request_approval', $stage, $header->department)) {
                $approval_action = $stage;
            }
        }

        $data = [
            'approval_action' => $approval_action,
            'from_approval' => $from_approval,
            'title' => sprintf(lang_line('title_cr_detail'), $header->request_number),
            'header' => $header,
            'details' => $details,
            'live_prices' => $live_prices,
            'irf_po_prices' => $irf_po_prices,
            'can_submit' => $header->status === 'Draft' && can_dept_access('consumable_request', 'submit', $header->department),
            'can_delete' => can_dept_access('consumable_request', 'delete', $header->department),
            'department_name' => department_names()[$header->department] ?? $header->department,
            'can_edit' => $is_draft_editable,
            'can_admin_edit' => $can_admin_edit,
            'can_reply' => $can_reply,
            'edit_mode' => $edit_mode,
            'is_admin_edit_mode' => $edit_mode && !$is_draft_editable && $can_admin_edit,
            'items_list' => $edit_mode ? $this->Consumable_request_model->get_inventory_items_with_stock($header->category ?? 'General') : [],
            // Tombol + "Tambah item baru ke master" di dropdown item juga
            // berlaku saat IRF diperbaiki, bukan cuma waktu dibuat.
            'quick_add' => $edit_mode ? $this->_quick_add_config() : [],
            'approval_logs' => $this->Consumable_request_model->get_approval_logs($id),
            'categories' => self::CATEGORIES,
            'attachments' => $this->Consumable_request_model->get_attachments($id),
            // "Bukti pengeluaran itu adalah bukti penerimaan dari user nya"
            // (user 2026-09-14) -- secara proses SATU dokumen serah terima,
            // cuma bisa diunggah dari dua halaman. Di sini keduanya disatukan
            // jadi satu daftar; berkasnya TIDAK disalin, tetap milik modul
            // masing-masing. Lihat irf_merge_handover_attachments().
            'handover_attachments' => irf_merge_handover_attachments(
                $this->Consumable_request_model->get_attachments($id),
                $this->Consumable_out_model->get_attachments_by_request($id)
            ),
            // Dokumen pengeluaran milik IRF ini -> tombol Export PDF per dokumen.
            'issuance_docs' => $this->Consumable_out_model->get_headers_by_request($id),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_request/detail', $data);
        $this->load->view('templates/footer');
    }

    public function submit($id) {
        // Izin Submit terpisah dari Edit (Role Level tahap 2) — Admin/Clerk
        // boleh membuat & mengedit, yang submit Supervisor.
        if (!check_access('consumable_request', 'submit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($id) || !is_numeric($id)) show_404();

        $current = $this->Consumable_request_model->get_header($id);
        if (!$current) show_404();
        if (!can_dept_access('consumable_request', 'submit', $current->department)) {
            show_error('Anda tidak berhak submit Permintaan Barang departemen ini.', 403);
        }

        $result = $this->Consumable_request_model->submit($id, $this->session->userdata('user_id'));
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Request berhasil disubmit');
            // Notifikasi "New IRF" (ERP Revision Notes, [[erp_revision_cross_cutting]])
            // -> semua user yang punya akses HOD approval utk consumable_request.
            $header = $this->Consumable_request_model->get_header($id);
            if ($header) {
                // Hanya HOD departemen IRF ini (atau pemegang ALL DEPT) — tahap 3.
                $recipients = users_in_dept_scope(
                    $this->users_with_permission('consumable_request_approval', 'can_head_dept'),
                    'consumable_request_approval', 'can_head_dept', $header->department
                );
                $this->notify_users(
                    $recipients,
                    'Permintaan Barang Baru Menunggu Approval',
                    'Permintaan Barang ' . $header->request_number . ' dari ' . ($header->requestor_name ?: '-') . ' menunggu approval Anda (Head of Dept).',
                    'consumable_request/detail/' . $id,
                    'info', 'fa-file-alt',
                    'consumable_request', $id
                );
            }
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('consumable_request/detail/' . $id);
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase 2 poin 11b):
     * requestor & approver bolak-balik reply selagi status masih Draft
     * (window revisi terbuka setelah return/reject). Ditutup lagi begitu
     * requestor submit ulang (status berubah dari Draft).
     */
    public function reply($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) show_404();

        $user_id = $this->session->userdata('user_id');

        // Cuma pembuat IRF & approver yang mengembalikannya, dan cuma selama
        // IRF ini masih Draft hasil Back to Draft (bukan draft baru).
        if (!can_reply_return_thread($header)) {
            $this->session->set_flashdata('error', 'Balasan cuma bisa dikirim oleh pembuat Permintaan Barang & approver yang mengembalikannya, selagi Permintaan Barang masih Draft hasil Back to Draft.');
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            $this->session->set_flashdata('error', 'Pesan balasan tidak boleh kosong.');
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $this->Consumable_request_model->add_reply($id, $user_id, $message);
        $this->session->set_flashdata('success', 'Balasan terkirim.');
        redirect('consumable_request/detail/' . $id . '#riwayat-approval');
    }

    public function delete($id) {
        if (!check_access('consumable_request', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header || $header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya request Draft yang bisa dihapus.');
            redirect('consumable_request');
            return;
        }
        if (!can_dept_access('consumable_request', 'delete', $header->department)) {
            show_error('Permintaan Barang ini milik departemen lain — Anda tidak berhak menghapusnya.', 403);
        }

        $this->Consumable_request_model->delete($id);
        $this->session->set_flashdata('success', 'Request berhasil dihapus.');
        redirect('consumable_request');
    }

    // ==========================================================
    // APPROVAL WORKFLOW — 3 stage (sama dengan PMR)
    // Permission check pakai menu `consumable_request_approval`.
    // ==========================================================

    /**
     * Tolak (403) kalau IRF bukan departemen yang boleh ditangani user di
     * tahap approval ini (tahap 3). $action = head_dept|endorsement|approval,
     * atau null = tahap yang sesuai status IRF saat ini (return/reject).
     */
    private function _require_approval_scope($id, $action = null) {
        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) show_404();
        $action = $action ?: approval_action_for_status($header->status, Consumable_request_model::APPROVAL_STAGE_MAP);
        if ($action === null || !can_dept_access('consumable_request_approval', $action, $header->department)) {
            show_error('Permintaan Barang ini bukan departemen yang bisa Anda tangani di tahap approval ini.', 403);
        }
        return $header;
    }

    private function _approval_redirect($id) {
        $back = $this->input->post('redirect') ?: $this->input->get('redirect');
        if ($back === 'approval') {
            redirect('consumable_request/approval');
        }
        redirect('consumable_request/detail/' . $id);
    }

    public function approve_hod($id) {
        if (!check_access('consumable_request_approval', 'head_dept')) {
            show_error('Anda tidak memiliki akses untuk approve sebagai Head of Dept.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $this->_require_approval_scope($id, 'head_dept');
        $user_id = $this->session->userdata('user_id');
        $res = $this->Consumable_request_model->approve_hod($id, $user_id);
        if ($res['success']) {
            if (!empty($res['final'])) {
                // Sejak 2026-11-05 approval Permintaan Barang selalu 1 tahap: HOD = final.
                $this->session->set_flashdata('success', 'Permintaan Barang di-approve final (approval 1 tahap) dan siap dikeluarkan gudang.');
                $this->_notify_final_approved($id);
            } else {
                $this->session->set_flashdata('success', 'Permintaan Barang berhasil di-approve Head of Dept, lanjut ke Endorsement.');
                $this->_notify_next_stage($id, 'consumable_request_approval', 'can_endorsement', 'Endorsement');
            }
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    public function approve_endorsement($id) {
        if (!check_access('consumable_request_approval', 'endorsement')) {
            show_error('Anda tidak memiliki akses untuk endorsement.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $this->_require_approval_scope($id, 'endorsement');
        $user_id = $this->session->userdata('user_id');
        $res = $this->Consumable_request_model->approve_endorsement($id, $user_id);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'Permintaan Barang berhasil di-endorse.');
            $this->_notify_next_stage($id, 'consumable_request_approval', 'can_approval', 'Final Approval');
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    public function approve_final($id) {
        if (!check_access('consumable_request_approval', 'approval')) {
            show_error('Anda tidak memiliki akses untuk final approval.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $this->_require_approval_scope($id, 'approval');
        $user_id = $this->session->userdata('user_id');
        $res = $this->Consumable_request_model->approve_final($id, $user_id);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'Permintaan Barang final di-approve.');
            $this->_notify_final_approved($id);
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    /**
     * Notifikasi "Approved" (ke requestor) + "Outstanding Task" (ke gudang
     * kategori yang sesuai, siap diproses) — ERP Revision Notes,
     * [[erp_revision_cross_cutting]]. Dipakai final approve 3 tahap maupun
     * approval HOD pada jalur 1 tahap.
     */
    private function _notify_final_approved($id) {
        $header = $this->Consumable_request_model->get_header($id);
        if ($header) {
            $this->notify_users(
                [(int) $header->created_by],
                'Permintaan Barang Disetujui',
                'Permintaan Barang ' . $header->request_number . ' Anda telah disetujui sepenuhnya, siap diproses gudang.',
                'consumable_request/detail/' . $id,
                'success', 'fa-check-circle',
                'consumable_request', $id
            );
            $category = $header->category ?: 'General';
            $warehouse_users = $this->users_with_category_permission('consumable_out', $category, 'can_add');
            $this->notify_users(
                $warehouse_users,
                'Permintaan Barang Siap Diproses',
                'Permintaan Barang ' . $header->request_number . ' (' . $category . ') sudah disetujui, siap dikeluarkan gudang.',
                'consumable_out/add?request_id=' . $id,
                'warning', 'fa-warehouse',
                'consumable_request', $id
            );
        }
    }

    /**
     * Helper kecil dipakai approve_hod()/approve_endorsement() — notifikasi
     * "Waiting Approval" ke user yang berhak approve stage BERIKUTNYA.
     */
    private function _notify_next_stage($id, $approval_menu_key, $permission_column, $stage_label) {
        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) return;
        $recipients = users_in_dept_scope(
            $this->users_with_permission($approval_menu_key, $permission_column),
            $approval_menu_key, $permission_column, $header->department
        );
        $this->notify_users(
            $recipients,
            'Permintaan Barang Menunggu Approval',
            'Permintaan Barang ' . $header->request_number . ' menunggu approval Anda (' . $stage_label . ').',
            'consumable_request/detail/' . $id,
            'info', 'fa-file-alt',
            'consumable_request', $id
        );
    }

    public function return_to_draft($id) {
        $can_action = check_access('consumable_request_approval', 'head_dept')
                   || check_access('consumable_request_approval', 'endorsement')
                   || check_access('consumable_request_approval', 'approval');
        if (!$can_action) show_error('Anda tidak memiliki akses untuk return Permintaan Barang.', 403);
        if (empty($id) || !is_numeric($id)) show_404();
        $this->_require_approval_scope($id);

        $reason = trim($this->input->post('return_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id');
        $res = $this->Consumable_request_model->return_to_draft($id, $user_id, $reason);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'Permintaan Barang dikembalikan ke Draft. Creator dapat merevisi & submit ulang.');
            // Notifikasi "Back to Draft" -> requestor.
            $header = $this->Consumable_request_model->get_header($id);
            if ($header) {
                $this->notify_users(
                    [(int) $header->created_by],
                    'Permintaan Barang Dikembalikan ke Draft',
                    'Permintaan Barang ' . $header->request_number . ' dikembalikan utk direvisi: "' . $reason . '"',
                    'consumable_request/detail/' . $id,
                    'warning', 'fa-undo',
                    'consumable_request', $id
                );
            }
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    public function reject($id) {
        $can_action = check_access('consumable_request_approval', 'head_dept')
                   || check_access('consumable_request_approval', 'endorsement')
                   || check_access('consumable_request_approval', 'approval');
        if (!$can_action) show_error('Anda tidak memiliki akses untuk reject Permintaan Barang.', 403);
        if (empty($id) || !is_numeric($id)) show_404();
        $this->_require_approval_scope($id);

        $reason = trim($this->input->post('reject_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id');
        $res = $this->Consumable_request_model->reject($id, $user_id, $reason);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'Permintaan Barang berhasil di-reject.');
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    public function export_pdf($id) {
        if (!check_access('consumable_request', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }
        if (empty($id) || !is_numeric($id)) show_404();

        require_once FCPATH . 'vendor/autoload.php';

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). IRF yang sudah
        // final tetap terbuka (sama dengan daftar) supaya gudang bisa memprosesnya.
        if (!can_view_dept_doc('consumable_request', 'consumable_request_approval', $header->department,
                (int) $header->created_by === (int) $this->session->userdata('user_id'),
                in_array($header->status, ['Approved', 'Released', 'Rejected', 'Cancelled'], true))) {
            show_error('Permintaan Barang ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }
        $details = $this->Consumable_request_model->get_details($id);

        // Trace IRF -> PR (from outstanding) -> PO -> GRN
        $trace = $this->_get_irf_trace($header->request_number);
        $po_id = $trace['po'] ? (int)$trace['po']->id : 0;

        $items = [];
        $no = 1;
        foreach ($details as $d) {
            $unit_price = 0;
            if ($po_id) {
                $po_line = $this->db->select('local_price')
                    ->from('po_details')
                    ->where('po_header_id', $po_id)
                    ->where('item_id', $d->item_id)
                    ->where('item_type', $d->item_type ?? 'inventory')
                    ->limit(1)->get()->row();
                if ($po_line) { $unit_price = (float)$po_line->local_price; }
            }
            $qty = (float)$d->qty_requested;
            $items[] = [
                'no'         => $no++,
                'item_code'  => $d->item_code,
                'item_name'  => $d->item_name,
                'qty'        => $qty,
                'unit'       => $d->item_unit,
                'unit_price' => $unit_price,
                'amount'     => $unit_price * $qty,
                'notes'      => $d->notes,
            ];
        }

        // Department: show common_code description if header->department holds a code
        $department = $header->department ?? '';
        if (!empty($department)) {
            $cc = $this->db->select('description')->from('common_code')
                ->where('group_code', 'Department')
                ->group_start()
                    ->where('code', $department)
                    ->or_where('description', $department)
                ->group_end()
                ->limit(1)->get()->row();
            if ($cc) { $department = $cc->description; }
        }

        // Signatures sekarang ambil dari approval workflow IRF langsung
        // (mirip PMR), bukan trace PR/PO/GRN. Lebih akurat & konsisten.
        $signatures = $this->_build_irf_signatures($header, $trace);

        $data = [
            'irf_number'   => $header->request_number,
            'request_date' => $header->request_date,
            'department'   => $department,
            'purpose'      => $header->purpose,
            'items'        => $items,
            'form_type'    => 'General',
            'signatures'   => $signatures,
            'signature_labels' => [
                ['Applicant',  '申請人'],
                ['Approve by (Head of Dept.)', '部門主管核准'],
                ['Receiver',   '收款人'],
            ],
        ];

        $filename = "PB_" . str_replace([':', ' ', '/'], ['', '_', '-'], $header->request_number) . ".pdf";
        render_document_pdf($data, $filename);
    }

    /**
     * Trace IRF (consumable_request) downstream:
     *   IRF -> PR (created from outstanding, identified via pr_header.remarks LIKE %request_number%)
     *        -> PO (po_header.pr_header_id atau po_details.pr_header_id)
     *        -> GRN (grn linked to PO via grn_details.source_doc_type='PO')
     */
    private function _get_irf_trace($request_number) {
        $result = ['pr' => null, 'po' => null, 'grn' => null];
        if (empty($request_number)) return $result;

        $pr = $this->db->select('id, created_by, created_at, approved_by, approved_at, status, pr_number')
            ->from('pr_header')
            ->like('remarks', $request_number)
            ->order_by('id', 'DESC')->limit(1)->get()->row();
        if (!$pr) return $result;
        $result['pr'] = $pr;

        // Lewat model, bukan query langsung: PR bisa jadi cuma menyumbang
        // sebagian baris ke PO gabungan, sehingga jejaknya ada di po_details
        // dan bukan di po_header.pr_header_id.
        $this->load->model('Po_model');
        $po = $this->Po_model->get_latest_po_for_pr($pr->id);
        if (!$po) return $result;
        $result['po'] = $po;

        $grn = $this->db->select('grn.id, grn.confirmed_by, grn.confirmed_at, grn.created_by, grn.created_at, grn.grn_date, grn.grn_number')
            ->from('grn_header grn')
            ->join('grn_details gd', 'gd.grn_header_id = grn.id', 'inner')
            ->join('po_details pod', "pod.id = gd.source_doc_detail_id AND gd.source_doc_type = 'PO'", 'inner')
            ->where('pod.po_header_id', $po->id)
            ->order_by('grn.id', 'DESC')->limit(1)->get()->row();
        if ($grn) { $result['grn'] = $grn; }

        return $result;
    }

    /**
     * 5-signature array for IRF General PDF.
     * Slots (sejak 2026-11-05, approval 1 tahap): Applicant, Head of Dept (final), Receiver.
     *
     * Sumber data:
     * - Applicant, Head of Dept, Endorsement, Approve by → diambil dari approval
     *   workflow IRF langsung (consumable_request_header) — mirip PMR.
     * - Receiver → tetap dari GRN trace (karena IRF general tidak punya konsep
     *   "diterima" sendiri; barang sampai ke user via GRN supplier).
     */
    private function _build_irf_signatures($header, $trace) {
        // render_signature_stamp_html() (document_pdf_helper.php, autoload) --
        // stempel "ERP Approved" generik kalau user tidak punya ttd personal,
        // kalau user belum punya ttd personal; kalau punya, ttd-nya saja.
        $sig_for = function($user_id, $date_raw) {
            $blank = ['name' => '', 'signature_html' => '', 'date' => ''];
            if (empty($user_id)) return $blank;
            $u = $this->db->select('fullname, signature_path')
                ->from('users')->where('id', $user_id)->get()->row();
            if (!$u) return $blank;
            return [
                'name'           => $u->fullname,
                'signature_html' => render_signature_stamp_html($u->signature_path),
                'date'           => $date_raw ? date('d/m/Y', strtotime($date_raw)) : '',
            ];
        };

        $applicant    = $sig_for($header->created_by        ?? null, $header->submitted_at      ?? ($header->created_at ?? null));
        $head_of_dept = $sig_for($header->hod_approved_by   ?? null, $header->hod_approved_at   ?? null);

        // Receiver tetap dari GRN trace (kalau ada); kalau tidak, kosong.
        $receiver = ['name' => '', 'signature_html' => '', 'date' => ''];
        if (!empty($trace['grn'])) {
            $signer = !empty($trace['grn']->confirmed_by) ? $trace['grn']->confirmed_by : ($trace['grn']->created_by ?? null);
            $when   = !empty($trace['grn']->confirmed_at) ? $trace['grn']->confirmed_at : ($trace['grn']->grn_date ?? null);
            $receiver = $sig_for($signer, $when);
        }

        // Approval 1 tahap (2026-11-05): Applicant -> Head of Dept / atasan (final) -> Receiver.
        return [$applicant, $head_of_dept, $receiver];
    }

    /**
     * Export PDF "Bukti Pengeluaran Barang" dari halaman IRF (user 2026-09-14).
     *
     * Sengaja endpoint SENDIRI, bukan menautkan ke Consumable_out::export_pdf():
     * requestor belum tentu punya izin lihat modul pengeluaran, padahal dokumen
     * ini justru dokumen dia (bukti serah terima yang harus dia tanda tangani
     * lalu unggah balik). Jadi gerbangnya pakai izin IRF, plus syarat dokumen
     * pengeluaran itu memang milik IRF yang bersangkutan.
     *
     * Template & isinya PERSIS sama dengan export dari modul pengeluaran
     * (consumable_out/pdf_template) supaya tidak ada dua versi dokumen.
     */
    public function export_issuance_pdf($co_id) {
        if (!check_access('consumable_request', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }
        $co_id = (int) $co_id;
        if ($co_id <= 0) show_404();

        $co = $this->Consumable_out_model->get_header($co_id);
        if (!$co || empty($co->request_id)) show_404();

        // Dokumen pengeluaran harus benar-benar milik sebuah IRF yang ada.
        $irf = $this->Consumable_request_model->get_header($co->request_id);
        if (!$irf) show_404();

        $data = [
            'header'  => $co,
            'details' => $this->Consumable_out_model->get_details($co_id),
        ];

        // Template & renderer SAMA dgn export dari modul pengeluaran, supaya
        // tidak ada dua versi dokumen yang beda bentuk.
        render_letterhead_pdf(
            'consumable_out/pdf_template',
            $data,
            'Bukti_Pengeluaran_' . str_replace('/', '-', $co->doc_number) . '.pdf'
        );
    }

    // -------------------------------------------------------
    // Attachments — "Bukti Penerimaan" oleh requestor (Item Release lanjutan,
    // Fase 2 poin 8). Pola sama persis dengan Erf::upload_attachment()/
    // delete_attachment() dan Consumable_out::upload_attachment()/delete_attachment().
    // -------------------------------------------------------
    public function upload_attachment($id) {
        if (!check_access('consumable_request', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        $id = (int) $id;
        if ($id <= 0) { show_404(); }

        $header = $this->Consumable_request_model->get_header($id);
        if (!$header) { show_404(); }

        $upload_path = FCPATH . 'uploads/consumable_request_attachments/';
        if (!is_dir($upload_path)) { @mkdir($upload_path, 0755, true); }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|jpg|jpeg|png|doc|docx|xls|xlsx',
            'max_size'      => 5120,
            'encrypt_name'  => TRUE,
        ];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('attachment')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('consumable_request/detail/' . $id);
            return;
        }

        $f = $this->upload->data();
        $this->Consumable_request_model->add_attachment([
            'consumable_request_header_id' => $id,
            'file_name'     => $f['file_name'],
            'original_name' => $f['orig_name'],
            'file_path'     => 'uploads/consumable_request_attachments/' . $f['file_name'],
            'file_type'     => $f['file_type'],
            'file_size'     => $f['file_size'] * 1024, // KB to bytes (CI raw is in kb)
            'uploaded_by'   => $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Bukti penerimaan berhasil di-upload.');
        redirect('consumable_request/detail/' . $id);
    }

    public function delete_attachment($attachment_id) {
        if (!check_access('consumable_request', 'edit')) { show_error('Tidak ada izin.', 403); return; }
        $att = $this->Consumable_request_model->get_attachment((int) $attachment_id);
        if (!$att) { show_404(); }

        $full = FCPATH . $att->file_path;
        if (is_file($full)) { @unlink($full); }

        $this->Consumable_request_model->delete_attachment((int) $attachment_id);
        $this->session->set_flashdata('success', 'Attachment dihapus.');
        redirect('consumable_request/detail/' . $att->consumable_request_header_id);
    }

    public function attachment($attachment_id) {
        $att = $this->Consumable_request_model->get_attachment((int) $attachment_id);
        if (!$att) { show_404(); }

        $full = FCPATH . $att->file_path;
        if (!is_file($full)) { show_404(); }

        header('Content-Type: ' . $att->file_type);
        header('Content-Disposition: inline; filename="' . $att->original_name . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
}
