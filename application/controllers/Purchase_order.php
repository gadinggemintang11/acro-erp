<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

/**
 * Purchase_order — CONTROLLER DASAR bersama utk SEMUA 4 tipe PO (Produksi/
 * General/Jasa/Asset). User 2026-09-05 minta digabung ("Purchase order
 * asset,jasa,general bukannya harusnya jadi 1 aja?") setelah lihat
 * duplikasi 99% identik di 4 file terpisah — dikonfirmasi via `diff`
 * sebelum refactor ini (lihat memory po_controllers_merge.md).
 *
 * `Purchase_order_general`/`_jasa`/`_asset` (MASIH 3 file terpisah secara
 * fisik!) jadi SUBCLASS TIPIS dari class ini — cuma override beberapa
 * PROPERTI/hook method (lihat daftar di bawah) + boleh tambah method UNIK
 * milik tipe itu sendiri (mis. `complete_service()` cuma ada di
 * `Purchase_order_jasa`). URL, menu sidebar, dan permission Role Access
 * SAMA SEKALI TIDAK BERUBAH — CI3 routing tetap berdasar nama FILE/CLASS,
 * jadi `purchase_order_general/index` dst tetap jalan persis seperti
 * sebelumnya, dan hak akses yang sudah dikonfigurasi user di Role Access
 * TIDAK PERLU dipetakan ulang sama sekali.
 *
 * Properti yang WAJIB di-override tiap subclass:
 *   $po_type            nilai kolom po_header.type
 *                        ('produksi'|'non_produksi'|'jasa'|'asset')
 *   $menu_key           menu_key utk check_access()/can_edit_po_header()/dst
 *                        (menu Role Access — 'purchase_order_general' dst)
 *   $approval_menu_key  menu_key modul approval terkait (utk notify_users
 *                        saat submit — 'purchase_order_approval_general' dst,
 *                        PERHATIKAN pola penamaannya TIDAK simetris dgn
 *                        $menu_key, base = 'purchase_order_approval' tanpa
 *                        akhiran apa pun)
 *
 * Properti OPSIONAL (default kalau tidak di-override = milik tipe produksi):
 *   $module_label       label utk add_log() ADMIN_EDIT
 *   $search_item_type   batasi Po_model::search_all_items() ke 1 tipe item
 *                        (jasa/asset), null = semua tipe (produksi/general)
 *   $completion_label   frasa "sudah ada ___" di pesan galat item terkunci
 *
 * Hook method yang boleh di-override:
 *   _extra_construct_models()          model tambahan yg cuma dipakai tipe
 *                                        itu (Sc_model/Bom_model utk
 *                                        produksi+general, Asset_register_model
 *                                        utk asset)
 *   _has_receipts($id)                  default check_if_po_has_receipts(),
 *                                        asset override ke
 *                                        check_if_po_has_asset_register()
 *   _extra_detail_data($header, $id)    data tambahan khusus view detail
 *                                        (open_contracts/service_items/dst)
 *   _title_index() / _title_detail()    judul halaman
 *
 * BUG yang KETEMU+DIPERBAIKI sekalian pas konsolidasi ini (SENGAJA, bukan
 * regresi tidak sadar — dicatat di memory po_controllers_merge.md):
 * - `Purchase_order_general` SEBELUMNYA py beberapa pesan/log yg salah tulis
 *   "Jasa" (mis. add_log label "Purchase Order Jasa", pesan error "jasa pada
 *   PO ini sudah ada penyelesaian") — jelas sisa copy-paste dari
 *   `Purchase_order_jasa`. Sekarang benar "Purchase Order Non Produksi"/
 *   "penerimaan barang (GRN)".
 * - Produksi & General SEBELUMNYA TIDAK validasi `$header->type` cocok dgn
 *   controller yang diakses (Jasa & Asset SUDAH, sejak awal ditulis
 *   terpisah) — sekarang SEMUA tipe konsisten divalidasi di setiap method
 *   yang menerima $id (cegah PO tipe lain "bisa dibuka" lewat controller yg
 *   salah, mis. buka PO Jasa lewat purchase_order/detail/{id}).
 * - `return_to_draft()`/`force_delete()` (Admin override) & `search_items()`/
 *   `export_list_excel()`/`export_list_pdf()`/`debug_remarks()` SEBELUMNYA
 *   cuma ada di Produksi & General — sekarang berlaku SEMUA tipe (Jasa &
 *   Asset kebagian kemampuan yg sama, konsisten). `export_list_excel()`/
 *   `export_list_pdf()` sekalian diberi filter `type` (sebelumnya TIDAK
 *   difilter type sama sekali walau dipanggil dari controller Produksi —
 *   bug lama, sekarang scoped benar per tipe).
 */
class Purchase_order extends MY_Controller {

    protected $po_type = 'produksi';
    protected $menu_key = 'purchase_order';
    protected $approval_menu_key = 'purchase_order_approval';
    protected $module_label = 'Purchase Order';
    protected $search_item_type = null;
    protected $completion_label = 'penerimaan barang (GRN)';

    /**
     * Boleh tidaknya item DIKETIK MANUAL (cari item bebas dari master) di PO
     * ini. false = item WAJIB ditarik dari PR ("Tarik dari PR").
     *
     * Default true utk PO Produksi (PMPO) yang memang punya jalur sah
     * sendiri: item datang dari BOM/Sales Contract lewat load_bom_from_sc().
     * Di-override false oleh GMPO (General/Jasa/Asset) -- user 2026-09-09:
     * "PO hanya bisa tarik PR gabisa manual input item tak berdasar", pola
     * sama spt PR yang sudah diwajibkan berasal dari IRF sebelumnya.
     *
     * Gate-nya dipasang di 3 pintu masuk item manual: search_items()
     * (sumber data picker), ajax_add_item() (baris inline halaman detail),
     * dan store() (array `items` dari form Buat PO). Penarikan PR
     * (pr_pull_items/pull_from_pr) & edit qty/harga baris yang sudah ada
     * (ajax_update_item) TIDAK terpengaruh.
     */
    protected $allow_manual_items = true;

    public function __construct() {
        parent::__construct();
        check_login();

        $this->load->model(array_merge([
            'Po_model',
            'Supplier_model',
            'Commoncode_model',
        ], $this->_extra_construct_models()));

        $this->load->helper(['url', 'translation']);
        $this->load->library('user_agent');
        $this->load->library('session');
        $this->load->library('form_validation');
    }

    /** Model tambahan yg cuma dipakai tipe tertentu — override per subclass. */
    protected function _extra_construct_models() {
        return ['Sc_model', 'Bom_model']; // default: Produksi (dipakai juga General)
    }

    /**
     * Daftar po_type yang MASUK ke listing controller ini (index/export list).
     * Default: cuma tipe sendiri. Di-override oleh General jadi gabungan
     * non_produksi+jasa+asset (user 2026-09-05: sidebar Jasa & Asset digabung
     * jadi 1 menu "GENERAL MATERIAL"/GMPO — lihat memory
     * po_controllers_merge.md bagian "Konsolidasi sidebar").
     *
     * PENTING: ini CUMA dipakai utk method yang LIST/agregat (index(),
     * export_list_excel(), export_list_pdf()). Method yang operasi ke 1
     * PO spesifik (detail/update_header/submit_po/dst) TETAP strict cocok
     * $this->po_type — supaya baris Jasa/Asset di list gabungan tetap
     * "lempar" ke controller aslinya (purchase_order_jasa/purchase_order_asset)
     * yang punya UI/aksi unik (complete_service/receive_asset), BUKAN
     * ditangani di sini.
     */
    protected function _included_types() {
        return [$this->po_type];
    }

    /** Cek "sudah ada penerimaan/penyelesaian?" — default GRN, Asset override. */
    protected function _has_receipts($id) {
        return $this->Po_model->check_if_po_has_receipts($id);
    }

    /** Data tambahan khusus view detail — override per subclass. */
    protected function _extra_detail_data($header, $id) {
        return ['open_contracts' => $this->Sc_model->get_open_contracts()];
    }

    protected function _title_index() {
        return lang_line('title_po_index', 'Purchase Order Produksi');
    }

    protected function _title_detail() {
        return lang_line('title_po_detail', 'Detail Purchase Order');
    }

    /**
     * Menampilkan daftar PO (Halaman Index)
     */
    public function index() {
        if (!check_access($this->menu_key, 'view')) { show_error('Izin Ditolak.', 403); }

        $filters = [
            'search' => $this->input->get('search'),
            'type'   => $this->_included_types(),
        ];

        $data = [
            'title'            => $this->_title_index(),
            'pos'              => $this->Po_model->get_all_po($filters),
            'suppliers'        => $this->Supplier_model->get_all(['status' => 'active']),
            'shipping_modes'   => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'    => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'       => $this->Commoncode_model->get_by_group('CURRENCY'),
            'filters'          => $filters,
            'po_type'          => $this->po_type,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($this->menu_key . '/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Menampilkan halaman detail PO
     */
    public function detail($id) {
        if (!check_access($this->menu_key, 'view')) { show_error('Izin Ditolak.', 403); }
        $id = (int)$id;
        if ($id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) show_404();

        // Izin edit dihitung sekali di sini lalu dipakai view untuk semua gate,
        // supaya aturan override Admin tidak tersebar sebagai kondisi inline.
        $has_receipts = $this->_has_receipts($id);

        $data = array_merge([
            'title'            => $this->_title_detail(),
            'header'           => $header,
            'details'          => $this->Po_model->get_po_details_by_id($id),
            'source_prs'       => $this->Po_model->get_source_prs($id),
            'has_receipts'     => $has_receipts,
            'can_edit_header'  => can_edit_po_header($header, $this->menu_key),
            'can_edit_item'    => can_edit_po_items($header, $this->menu_key, $has_receipts),
            // false => view menyembunyikan tombol "Tambah Baris Item"
            // (item wajib dari PR). Lihat properti $allow_manual_items.
            'allow_manual_items' => $this->allow_manual_items,
            'admin_edit_mode'  => is_po_admin_edit($header),
            'can_unlock_admin' => can_unlock_po_admin_edit($header),
            'shipping_modes'   => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'    => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'       => $this->Commoncode_model->get_by_group('CURRENCY'),
            // Riwayat Approval + Back to Draft 2-arah/reply (ERP Revision Notes,
            // [[erp_revision_cross_cutting]]) -- requestor bisa lihat & balas
            // alasan Back to Draft langsung dari halaman detail PO miliknya sendiri.
            'approval_logs'    => $this->Po_model->get_approval_logs($id),
            // Cuma terbuka kalau PO ini memang dikembalikan lewat Back to Draft,
            // dan cuma utk 2 orang: pembuat PO & approver yang mengembalikannya
            // (koreksi 2026-09-06 — lihat can_reply_return_thread() di auth_helper).
            'can_reply'        => can_reply_return_thread($header),
            // Data untuk warna status badge
            'status_colors'    => [
                'Draft' => 'warning', 'Submitted' => 'info', 'Confirmed' => 'primary',
                'Completed' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary'
            ]
        ], $this->_extra_detail_data($header, $id));

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($this->menu_key . '/detail', $data);
        // Footer di-load oleh view detail.php
    }

    /**
     * Validasi input header PO dari POST + bangun array data siap-insert.
     * Ditarik jadi 1 fungsi (dulu cuma di dalam add_header()) supaya dipakai
     * bersama oleh add_header() (lama, header doang) DAN store() (baru,
     * header+item sekaligus — user 2026-09-05 minta "header dan buat detail
     * itemnya gausa dipisah").
     *
     * @return array|null null kalau validasi gagal (flashdata error SUDAH di-set).
     */
    protected function _validate_and_build_header_data() {
        // 1. Bersihkan input kurs (exchange_rate) dari koma
        $exchange_rate_input = $this->input->post('exchange_rate');
        if ($exchange_rate_input) {
            $_POST['exchange_rate'] = preg_replace('/[^\d.]/', '', $exchange_rate_input);
        }

        // 2. Set aturan validasi
        $this->form_validation->set_rules('supplier_id', 'Supplier', 'required|numeric|greater_than[0]', ['greater_than' => 'Supplier wajib dipilih.']);
        $this->form_validation->set_rules('po_date', 'Tanggal PO', 'required');
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');

        // 3. Aturan validasi kurs (divalidasi setelah dibersihkan)
        if ($this->input->post('currency_code') != 'IDR') {
            $this->form_validation->set_rules('exchange_rate', 'Kurs Tukar', 'required|numeric|greater_than[0]', ['greater_than' => 'Kurs Tukar harus lebih besar dari 0 jika mata uang bukan IDR.']);
        } else {
            $_POST['exchange_rate'] = 1;
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors('<p class="mb-1">', '</p>'));
            return null;
        }

        $currency_code = $this->input->post('currency_code');
        $exchange_rate = (float)$this->input->post('exchange_rate');
        $po_number = trim($this->input->post('po_number'));
        $remarks_input = $this->input->post('remarks');

        $data = [
            'po_number'           => $po_number,
            'supplier_id'         => $this->input->post('supplier_id'),
            'po_date'             => $this->input->post('po_date'),
            'type'                => $this->po_type,
            'shipping_mode_code'  => $this->input->post('shipping_mode_code') ?: NULL,
            'payment_mode_code'   => $this->input->post('payment_mode_code') ?: NULL,
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            // Persen & cara hitung PPN ikut disimpan (klien 2026-10-02):
            // 0 = exclude (PPN ditambahkan di atas subtotal)
            // 1 = include (PPN diambil dari total, harga sudah mengandungnya)
            // Tanpa centang PPN keduanya tidak dipakai, tapi tetap disimpan apa
            // adanya supaya pilihan terakhir user tidak hilang saat dibuka lagi.
            'ppn_rate'            => (float) ($this->input->post('ppn_rate') ?: 0),
            'ppn_inclusive'       => $this->input->post('ppn_inclusive') ? 1 : 0,
            'remarks'             => !empty($remarks_input) ? trim($remarks_input) : '',
            'status'              => 'Draft',
            'created_by'          => $this->session->userdata('user_id') ?? null
        ];

        // Kolom ppn_inclusive baru ada setelah migration_po_ppn_inclusive.sql --
        // dibuang kalau belum ada supaya DB yang belum dimigrasi tidak gagal.
        if (!db_has_column('po_header', 'ppn_inclusive')) unset($data['ppn_inclusive']);

        return $data;
    }

    /**
     * Menyimpan header PO baru dari modal (LAMA — header doang, item
     * ditambah belakangan di halaman detail). Dipertahankan (dead-code aman
     * kalau ada link/bookmark lama) — jalur BARU yang dipakai form
     * `add.php` sekarang adalah store() di bawah.
     */
    /**
     * Form "Buat PMPO" — halaman TERSENDIRI, bukan modal di atas list.
     *
     * Menu sidebar berlabel "Create New PMPO" dulu mendarat di halaman daftar
     * lalu user harus menekan tombol lagi untuk memunculkan modal. User sudah
     * meminta pola ini diseragamkan sejak 2026-09-05 untuk GMPO, dan 2026-10-02
     * untuk PMPO: "view purchase order ini waktu diklik harusnya langsung kolom
     * inputan ya gausa list, sama kan dengan Purchase_order_general".
     *
     * View-nya SENGAJA memakai purchase_order_general/add.php yang sudah ada --
     * bentuk formnya identik, cuma PMPO tidak punya pilihan Kategori. Dengan
     * $fixed_route, dropdown itu tidak dirender dan endpoint JS dikunci ke
     * controller ini. Tidak ada endpoint baru: store(), search_items() &
     * pr_pull_items() memang sudah ada di class ini.
     *
     * Subclass General/Jasa/Asset meng-override add()-nya sendiri, jadi
     * perilaku mereka tidak berubah sama sekali.
     */
    public function add() {
        if (!check_access($this->menu_key, 'add')) { show_error('Izin Ditolak.', 403); }

        $data = [
            'title'              => lang_line('po_create_new_pmpo', 'Buat Purchase Order Produksi'),
            'suppliers'          => $this->Supplier_model->get_all(['status' => 'active']),
            'shipping_modes'     => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'      => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'         => $this->Commoncode_model->get_by_group('CURRENCY'),
            'allow_manual_items' => $this->allow_manual_items,
            'fixed_route'        => $this->menu_key,
            'fixed_kategori'     => 'produksi',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('purchase_order_general/add', $data);
        $this->load->view('templates/footer');
    }

    public function add_header() {
        if (!check_access($this->menu_key, 'add')) { show_error('Izin Ditolak.', 403); }
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $data = $this->_validate_and_build_header_data();
        if ($data === null) { redirect($this->menu_key); return; }

        $new_po_id = $this->Po_model->insert_header($data);
        if ($new_po_id) {
            $this->session->set_flashdata('success', 'Header PO berhasil dibuat. Silakan tambahkan item detail.');
            redirect($this->menu_key . '/detail/' . $new_po_id);
        } else {
            $db_error = $this->db->error();
            log_message('error','Gagal insert header PO: '. print_r($db_error, true));
            if ($this->Po_model->check_po_number_exists($data['po_number']) && !empty($data['po_number'])) {
                $this->session->set_flashdata('error', 'Nomor PO sudah ada! Gunakan nomor yang berbeda atau biarkan kosong untuk auto-generate.');
            } else {
                $this->session->set_flashdata('error', 'Gagal membuat header PO! Terjadi kesalahan database.');
            }
            redirect($this->menu_key);
        }
    }

    /**
     * Simpan PO — header + SEMUA item SEKALIGUS dalam 1 submit (user
     * 2026-09-05: "header dan buat detail itemnya gausa di pisah"). Item
     * ditahan (staged) di browser dulu lewat JS di `add.php` — dikirim
     * sbg JSON pas klik "Simpan", BUKAN AJAX per-item spt ajax_add_item()
     * (yg tetap dipakai kalau nambah item BELAKANGAN di halaman detail,
     * tidak diubah). Sengaja pola SAMA PERSIS dgn
     * Material_request::store() (MRF, dikerjakan sesi yg sama).
     */
    public function store() {
        if (!check_access($this->menu_key, 'add')) { show_error('Izin Ditolak.', 403); }
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $data = $this->_validate_and_build_header_data();
        if ($data === null) { redirect($this->menu_key . '/add'); return; }

        $items    = json_decode((string) $this->input->post('items'), true);
        $pr_rows  = json_decode((string) $this->input->post('pulled_pr_rows'), true);
        $has_items   = !empty($items) && is_array($items);
        $has_pr_rows = !empty($pr_rows) && is_array($pr_rows);

        // Tipe PO yang wajib tarik-PR: array `items` dari form diabaikan
        // total (bukan cuma disembunyikan di UI) supaya POST manual/halaman
        // ter-cache tidak bisa menyelundupkan item tanpa dasar PR.
        if (!$this->allow_manual_items && $has_items) {
            log_message('info', "[{$this->menu_key}] store(): " . count($items) . ' item manual diabaikan (PO wajib tarik dari PR).');
            $items = [];
            $has_items = false;
        }

        if (!$has_items && !$has_pr_rows) {
            $this->session->set_flashdata('error', $this->allow_manual_items
                ? 'Minimal satu item harus ditambahkan (cari manual atau tarik dari PR).'
                : 'Minimal satu item harus ditarik dari PR.');
            redirect($this->menu_key . '/add');
            return;
        }

        $new_po_id = $this->Po_model->insert_header($data);
        if (!$new_po_id) {
            $db_error = $this->db->error();
            log_message('error', 'Gagal insert header PO (store): ' . print_r($db_error, true));
            if ($this->Po_model->check_po_number_exists($data['po_number']) && !empty($data['po_number'])) {
                $this->session->set_flashdata('error', 'Nomor PO sudah ada! Gunakan nomor yang berbeda atau biarkan kosong untuk auto-generate.');
            } else {
                $this->session->set_flashdata('error', 'Gagal membuat header PO! Terjadi kesalahan database.');
            }
            redirect($this->menu_key . '/add');
            return;
        }

        // 1) Baris yang ditarik dari PR (checkbox "Tarik dari PR" di form) --
        // pakai Po_model::pull_pr_items() yang SUDAH ADA (klamp sisa qty asli,
        // isi pr_header_id/pr_detail_id utk traceability) -- BUKAN reimplement
        // logic-nya di sini. Harga dikosongkan (0), diisi belakangan di
        // halaman detail -- pola SAMA PERSIS spt pull_from_pr() yang jalan
        // dari detail page.
        $pr_pull_result = null;
        if ($has_pr_rows) {
            $rows = Po_model::build_pull_rows(
                array_column($pr_rows, 'pr_detail_id'),
                array_combine(array_column($pr_rows, 'pr_detail_id'), array_column($pr_rows, 'qty'))
            );
            $pr_pull_result = $this->Po_model->pull_pr_items($new_po_id, $rows, ['Draft']);
        }

        // 2) Item yang dicari manual (search_items()) -- pola SAMA PERSIS
        // dgn ajax_add_item(), cuma dipanggil loop di sini.
        $exchange_rate = (float) ($data['exchange_rate'] ?: 1);
        $success_count = 0;
        if ($has_items) {
            foreach ($items as $it) {
                $qty = (float) ($it['qty'] ?? 0);
                $base_price = (float) ($it['base_price'] ?? 0);
                $item_id_raw = $it['item_id'] ?? null;
                if ($qty <= 0 || empty($item_id_raw)) continue;

                $item_info = $this->Po_model->get_item_details($item_id_raw);
                if (!$item_info) continue;

                $local_price = $base_price * $exchange_rate;
                $inserted = $this->Po_model->add_item([
                    'po_header_id'      => $new_po_id,
                    'item_id'           => $item_id_raw,
                    'item_type'         => $item_info->item_type,
                    'qty'               => $qty,
                    // Harga satuan 4 desimal, total 2 (user 2026-09-10) —
                    // lihat db/migration_po_price_4dec.sql. Dibulatkan eksplisit
                    // di sini biar tidak bergantung pembulatan diam-diam MySQL
                    // waktu masuk kolom.
                    'base_price'        => round($base_price, 4),
                    'local_price'       => round($local_price, 4),
                    'total_local'       => round($qty * $local_price, 2),
                    'sales_contract_id' => null,
                    'description'       => trim((string) ($it['description'] ?? '')),
                ]);
                if ($inserted) $success_count++;
            }
        }

        $pr_added = $pr_pull_result['added'] ?? 0;
        $pr_merged = $pr_pull_result['merged'] ?? 0;
        $total_from_pr = $pr_added + $pr_merged;

        if ($success_count > 0 || $total_from_pr > 0) {
            $msg = "PO berhasil dibuat";
            $parts = [];
            if ($success_count > 0) $parts[] = "{$success_count} item manual";
            if ($total_from_pr > 0) $parts[] = "{$total_from_pr} item dari PR";
            $msg .= ' dengan ' . implode(' + ', $parts) . '.';
            if (!empty($pr_pull_result['skipped'])) {
                $msg .= ' (' . count($pr_pull_result['skipped']) . ' baris PR dilewati: ' . implode('; ', $pr_pull_result['skipped']) . ')';
            }
            $this->session->set_flashdata('success', $msg);
        } else {
            $this->session->set_flashdata('warning', 'PO berhasil dibuat, tapi tidak ada item yang tersimpan. Silakan tambahkan item di halaman ini.');
        }
        redirect($this->menu_key . '/detail/' . $new_po_id);
    }

    /**
     * Update header PO (Draft, atau status Submitted/Confirmed khusus Admin)
     */
    public function update_header($id) {
        $id = (int)$id; if ($id <= 0) show_404();
        if ($this->input->method() !== 'post') { show_error('Metode Ilegal.', 405); return; }

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) { show_404(); return; }
        if (!can_edit_po_header($header, $this->menu_key)) {
            $this->session->set_flashdata('error', 'Header PO tidak bisa diedit (status bukan Draft dan Anda bukan Admin)!');
            redirect($this->menu_key . '/detail/' . $id);
            return;
        }
        $admin_edit = is_po_admin_edit($header);

        $this->form_validation->set_rules('po_date', 'Tanggal PO', 'required', ['required' => 'Tanggal PO wajib diisi.']);
        $this->form_validation->set_rules('currency_code', 'Mata Uang', 'required');
        if ($this->input->post('currency_code') != 'IDR') {
            $this->form_validation->set_rules('exchange_rate', 'Kurs Tukar', 'required|numeric|greater_than[0]',['greater_than' => 'Kurs Tukar harus lebih besar dari 0 jika mata uang bukan IDR.']);
        } else {
            $_POST['exchange_rate'] = 1;
        }
        if ($this->form_validation->run() == FALSE) {
             $this->session->set_flashdata('error', validation_errors('<p class="mb-1">', '</p>'));
             redirect($this->menu_key . '/detail/' . $id . ($admin_edit ? '?admin_edit=1' : '')); return;
        }

        $currency_code = $this->input->post('currency_code');
        $exchange_rate_input = preg_replace('/[^\d.]/', '', $this->input->post('exchange_rate'));
        $exchange_rate = ($currency_code == 'IDR') ? 1 : (float)$exchange_rate_input;
        $remarks_raw = $this->input->post('remarks');
        $remarks_final = !empty($remarks_raw) ? trim($remarks_raw) : '';

        $data = [
            'po_date'             => $this->input->post('po_date'),
            'shipping_mode_code'  => $this->input->post('shipping_mode_code') ?: NULL,
            'payment_mode_code'   => $this->input->post('payment_mode_code') ?: NULL,
            'currency_code'       => $currency_code,
            'exchange_rate'       => $exchange_rate,
            'has_ppn'             => $this->input->post('has_ppn') ? 1 : 0,
            // Persen & cara hitung PPN ikut disimpan (klien 2026-10-02):
            // 0 = exclude (PPN ditambahkan di atas subtotal)
            // 1 = include (PPN diambil dari total, harga sudah mengandungnya)
            // Tanpa centang PPN keduanya tidak dipakai, tapi tetap disimpan apa
            // adanya supaya pilihan terakhir user tidak hilang saat dibuka lagi.
            'ppn_rate'            => (float) ($this->input->post('ppn_rate') ?: 0),
            'ppn_inclusive'       => $this->input->post('ppn_inclusive') ? 1 : 0,
            'remarks'             => $remarks_final,
        ];

        // Status yang lolos guard di atas ikut jadi syarat WHERE, jadi kalau PO
        // keburu pindah status di request lain update-nya batal, bukan menabrak.

        // Kolom ppn_inclusive baru ada setelah migration_po_ppn_inclusive.sql --
        // dibuang kalau belum ada supaya DB yang belum dimigrasi tidak gagal.
        if (!db_has_column('po_header', 'ppn_inclusive')) unset($data['ppn_inclusive']);

        $result = $this->Po_model->update_header($id, $data, [$header->status]);

        if ($result) {
            if ($admin_edit) {
                add_log('ADMIN_EDIT', $this->module_label, 'Edit header PO ' . $header->po_number . ' pada status ' . $header->status . ' (override Admin).');
                $this->session->set_flashdata('success', 'Header PO berhasil diperbarui (override Admin pada status ' . $header->status . ').');
            } else {
                $this->session->set_flashdata('success', 'Header PO berhasil diperbarui!');
            }
        } else {
             if ($this->db->affected_rows() == 0) {
                 $this->session->set_flashdata('warning', 'Tidak ada perubahan data header.');
             } else {
                 $db_error = $this->db->error();
                 log_message('error','Gagal update header PO ID '.$id.': '. print_r($db_error, true));
                 $this->session->set_flashdata('error', 'Gagal memperbarui header PO! Terjadi kesalahan database: ' . $db_error['message']);
             }
        }
        redirect($this->menu_key . '/detail/' . $id . ($admin_edit ? '?admin_edit=1' : ''));
    }

    /**
     * Hapus header PO (hanya Draft dan item kosong)
     */
    public function delete_header($id) {
        if (!check_access($this->menu_key, 'delete')) { show_error('Izin Ditolak.', 403); }
        $id = (int)$id; if ($id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) {
            $this->session->set_flashdata('error', 'PO tidak ditemukan!');
            redirect($this->menu_key); return;
        }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya PO status Draft yang bisa dihapus!');
            redirect($this->menu_key . '/detail/' . $id); return;
        }

        if ($this->Po_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'PO berhasil dihapus!');
            redirect($this->menu_key);
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO! Pastikan tidak ada item detail atau terjadi kesalahan database.');
            redirect($this->menu_key . '/detail/' . $id);
        }
    }

    /**
     * AJAX: Mencari item untuk Select2 inline
     */
    public function search_items() {
        // Picker item manual dimatikan utk tipe PO yang wajib tarik-PR --
        // balikin hasil kosong (bukan error) supaya Select2 tetap aman kalau
        // ada halaman lama yang masih memanggil endpoint ini.
        if (!$this->allow_manual_items) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['results' => []]));
            return;
        }

        $term = $this->input->get('q');
        $results_formatted = [];

        if ($term && strlen($term) >= 2) {
            $items_from_model = $this->search_item_type
                ? $this->Po_model->search_all_items($term, $this->search_item_type)
                : $this->Po_model->search_all_items($term);

            foreach ($items_from_model as $item) {
                if (is_object($item) && isset($item->item_type, $item->id, $item->name, $item->code)) {
                     $results_formatted[] = [
                        'id'        => $item->item_type . '-' . $item->id,
                        'text'      => $item->name . ' (' . $item->code . ') - [' . ucfirst($item->item_type) . ']',
                        'code'      => $item->code,
                        'unit'      => $item->unit ?? '',
                        'item_type' => $item->item_type
                    ];
                } else {
                    log_message('error', '[search_items] Invalid item structure from model: ' . print_r($item, true));
                }
            }
        }

        $output = ['results' => $results_formatted];
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($output));
    }

    /**
     * Fungsi AJAX untuk menyimpan item baru dari baris inline
     */
    public function ajax_add_item($po_header_id) {
        $this->output->set_content_type('application/json');

        $po_header_id = (int)$po_header_id;
        if ($po_header_id <= 0) {
             echo json_encode(['status' => 'error', 'message' => 'Invalid PO ID.']); exit;
        }
        if ($this->input->method() !== 'post') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid method.']); exit;
        }
        if (!$this->allow_manual_items) {
            echo json_encode(['status' => 'error', 'message' => $this->_manual_items_error()]); exit;
        }

        $header = $this->Po_model->get_po_header_by_id($po_header_id);
        if (!$header || $header->type !== $this->po_type) {
             echo json_encode(['status' => 'error', 'message' => 'PO tidak ditemukan.']); exit;
        }
        $has_receipts = $this->_has_receipts($po_header_id);
        if (!can_edit_po_items($header, $this->menu_key, $has_receipts)) {
             $msg = $has_receipts && is_po_admin_edit($header)
                 ? "Item tidak bisa ditambah karena PO ini sudah ada {$this->completion_label}."
                 : 'PO tidak dalam status Draft atau Anda tidak punya akses.';
             echo json_encode(['status' => 'error', 'message' => $msg]); exit;
        }
        $admin_edit = is_po_admin_edit($header);

        $this->form_validation->set_rules('item_id', 'Item', 'required');
        $this->form_validation->set_rules('qty', 'Quantity', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('base_price', 'Harga Satuan', 'required|numeric|greater_than_equal_to[0]');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'message' => validation_errors()]); exit;
        }

        $item_info = $this->Po_model->get_item_details($this->input->post('item_id'));
        if (!$item_info) {
             echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan.']); exit;
        }

        $base_price = (float)$this->input->post('base_price');
        $exchange_rate = (float)$header->exchange_rate ?: 1;
        $local_price = $base_price * $exchange_rate;
        $qty = (float)$this->input->post('qty');

        $detail_data = [
            'po_header_id'      => $po_header_id,
            'item_id'           => $this->input->post('item_id'),
            'item_type'         => $item_info->item_type,
            'qty'               => $qty,
            'base_price'        => round($base_price, 4),   // 4 desimal (migration_po_price_4dec)
            'local_price'       => round($local_price, 4),
            'total_local'       => round($qty * $local_price, 2),
            'sales_contract_id' => $this->input->post('sales_contract_id') ?: NULL,
            'description'       => trim($this->input->post('description'))
        ];

        $insert_id = $this->Po_model->add_item($detail_data);

        $output = [];
        if ($insert_id) {
            if ($admin_edit) {
                add_log('ADMIN_EDIT', $this->module_label, 'Tambah item pada PO ' . $header->po_number . ' di status ' . $header->status . ' (override Admin).');
            }
            $output = ['status' => 'success', 'message' => 'Item berhasil ditambahkan. Halaman akan dimuat ulang.', 'detail_id' => $insert_id];
        } else {
             $error_msg = $this->db->error()['message'] ?? 'Gagal menyimpan item ke database.';
             $output = ['status' => 'error', 'message' => $error_msg];
        }
        $output[$this->security->get_csrf_token_name()] = $this->security->get_csrf_hash();
        echo json_encode($output); exit;
    }

    /**
     * Menghapus item (Draft, atau status Submitted/Confirmed khusus Admin
     * selama PO belum punya penerimaan/penyelesaian)
     */
    public function delete_item($po_id, $detail_id) {
        $po_id = (int)$po_id; $detail_id = (int)$detail_id;
        if ($po_id <= 0 || $detail_id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header || $header->type !== $this->po_type) { show_404(); return; }

        $has_receipts = $this->_has_receipts($po_id);
        if (!can_edit_po_items($header, $this->menu_key, $has_receipts)) {
            $this->session->set_flashdata('error', $has_receipts && is_po_admin_edit($header)
                ? "Item tidak bisa dihapus karena PO ini sudah ada {$this->completion_label}!"
                : 'Item tidak bisa dihapus karena status PO bukan Draft!');
            redirect($this->menu_key . '/detail/' . $po_id);
            return;
        }
        $admin_edit = is_po_admin_edit($header);

        if ($this->Po_model->delete_item($detail_id)) {
            if ($admin_edit) {
                add_log('ADMIN_EDIT', $this->module_label, 'Hapus item #' . $detail_id . ' dari PO ' . $header->po_number . ' di status ' . $header->status . ' (override Admin).');
            }
            $this->session->set_flashdata('success', 'Item berhasil dihapus dari PO.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item!');
        }
        redirect($this->menu_key . '/detail/' . $po_id . ($admin_edit ? '?admin_edit=1' : ''));
    }

    // --- ALUR KERJA STATUS ---
    // Pastikan semua memanggil $this->Po_model->update_status(...)
    public function submit_po($id) {
        if (!check_access($this->menu_key, 'submit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        $allowed_initial_statuses = ['Draft', NULL];
        if (!$header || $header->type !== $this->po_type || !in_array($header->status, $allowed_initial_statuses, TRUE)) {
             $this->session->set_flashdata('warning', 'Hanya PO status Draft yang bisa disubmit!');
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        $details = $this->Po_model->get_po_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'PO tidak bisa disubmit karena belum memiliki item detail!');
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Submitted', $this->session->userdata('user_id'))) {
            $this->session->set_flashdata('success', 'Purchase Order berhasil disubmit.');
            // Notifikasi "PO Created" (ERP Revision Notes, [[erp_revision_cross_cutting]])
            // -> final-approval permission (PO 1-stage, cuma 1 tier approval).
            $recipients = $this->users_with_permission($this->approval_menu_key, 'can_approval');
            $this->notify_users(
                $recipients,
                'PO Baru Menunggu Approval',
                'PO ' . $header->po_number . ' menunggu approval Anda.',
                $this->approval_menu_key . '/detail/' . $id,
                'info', 'fa-file-invoice',
                $this->menu_key, (int) $id
            );
        } else {
            $this->session->set_flashdata('error', 'Gagal submit Purchase Order!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
    public function confirm_po($id) {
        if (!check_access($this->menu_key, 'confirm')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type || $header->status != 'Submitted') {
             $this->session->set_flashdata('warning', 'Hanya PO status Submitted yang bisa dikonfirmasi!');
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        $userId = $this->session->userdata('user_id') ?? null;
        if ($this->Po_model->update_status($id, 'Confirmed', $userId)) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dikonfirmasi (Approved).');
        } else {
             $this->session->set_flashdata('error', 'Gagal mengkonfirmasi Purchase Order!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
    public function unconfirm_po($id) {
        if (!check_access($this->menu_key, 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type || $header->status != 'Confirmed') {
             $this->session->set_flashdata('warning', 'Hanya PO status Confirmed yang bisa dibatalkan konfirmasinya!');
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        $is_received = $this->_has_receipts($id);
        if ($is_received) {
             $this->session->set_flashdata('error', "PO tidak bisa dibatalkan konfirmasinya karena sudah ada {$this->completion_label}!");
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Submitted', NULL, TRUE)) {
             $this->session->set_flashdata('success', 'Konfirmasi Purchase Order berhasil dibatalkan (Status: Submitted).');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi Purchase Order!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
    public function reject_po($id) {
        if (!check_access($this->menu_key, 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type || $header->status != 'Submitted') {
             $this->session->set_flashdata('warning', 'Hanya PO status Submitted yang bisa dikembalikan ke Draft!');
             redirect($this->menu_key . '/detail/' . $id); return;
        }
        if ($this->Po_model->update_status($id, 'Draft')) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dikembalikan ke Draft.');
        } else {
             $this->session->set_flashdata('error', 'Gagal mengembalikan PO ke Draft!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }
     public function cancel_po($id) {
        if (!check_access($this->menu_key, 'delete')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); return; }
        $header = $this->Po_model->get_po_header_by_id($id);
        $allowed_statuses_to_cancel = ['Draft', 'Submitted', 'Confirmed'];
        if (!$header || $header->type !== $this->po_type || !in_array($header->status, $allowed_statuses_to_cancel, TRUE)) {
             $this->session->set_flashdata('warning', 'Hanya PO status Draft, Submitted, atau Confirmed yang bisa dibatalkan!');
             redirect($this->agent->referrer() ?? $this->menu_key . '/detail/' . $id); return;
        }
        if ($header->status == 'Confirmed') {
             $is_received = $this->_has_receipts($id);
             if ($is_received) {
                 $this->session->set_flashdata('error', "PO tidak bisa dibatalkan karena sudah ada {$this->completion_label}!");
                 redirect($this->menu_key . '/detail/' . $id); return;
             }
        }
        if ($this->Po_model->update_status($id, 'Cancelled')) {
             $this->session->set_flashdata('success', 'Purchase Order berhasil dibatalkan (Cancelled).');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan Purchase Order!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    /**
     * Kembalikan PO ke Draft dari status apapun (Draft/Submitted/Confirmed).
     * Khusus Admin. Dulu cuma ada di Produksi & General — sekarang berlaku
     * SEMUA tipe (lihat catatan bug di docblock class).
     */
    public function return_to_draft($id) {
        if (!is_admin()) { show_error('Anda tidak memiliki izin (khusus Admin).', 403); return; }
        $id = (int)$id; if ($id <= 0) { show_404(); return; }

        $header = $this->Po_model->get_po_header_by_id($id);
        $allowed = ['Draft', 'Submitted', 'Confirmed'];
        if (!$header || $header->type !== $this->po_type || !in_array($header->status, $allowed, TRUE)) {
            $this->session->set_flashdata('warning', 'PO tidak bisa dikembalikan ke Draft dari status ini.');
            redirect($this->menu_key . '/detail/' . $id); return;
        }

        if ($header->status === 'Confirmed') {
            if ($this->_has_receipts($id)) {
                $this->session->set_flashdata('error', "PO tidak bisa dikembalikan ke Draft karena sudah ada {$this->completion_label}!");
                redirect($this->menu_key . '/detail/' . $id); return;
            }
        }

        if ($header->status === 'Draft') {
            $this->session->set_flashdata('warning', 'PO sudah dalam status Draft.');
            redirect($this->menu_key . '/detail/' . $id); return;
        }

        if ($this->Po_model->update_status($id, 'Draft', NULL, TRUE)) {
            $this->session->set_flashdata('success', 'Purchase Order berhasil dikembalikan ke Draft.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengembalikan PO ke Draft!');
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    /**
     * Hapus permanen PO beserta semua item detailnya. Khusus Admin. Dulu
     * cuma ada di Produksi & General — sekarang berlaku SEMUA tipe.
     */
    public function force_delete($id) {
        if (!is_admin()) { show_error('Anda tidak memiliki izin (khusus Admin).', 403); return; }
        $id = (int)$id; if ($id <= 0) { show_404(); return; }

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) {
            $this->session->set_flashdata('error', 'PO tidak ditemukan!');
            redirect($this->menu_key); return;
        }

        $allowed = ['Draft', 'Submitted', 'Confirmed', 'Cancelled'];
        if (!in_array($header->status, $allowed, TRUE)) {
            $this->session->set_flashdata('error', 'PO yang sudah Completed tidak bisa dihapus!');
            redirect($this->menu_key . '/detail/' . $id); return;
        }

        if (in_array($header->status, ['Confirmed', 'Submitted'])) {
            if ($this->_has_receipts($id)) {
                $this->session->set_flashdata('error', "PO tidak bisa dihapus karena sudah ada {$this->completion_label}!");
                redirect($this->menu_key . '/detail/' . $id); return;
            }
        }

        // Hapus semua detail terlebih dahulu, kemudian header
        $this->db->where('po_header_id', $id)->delete('po_details');
        if ($this->db->where('id', $id)->delete('po_header')) {
            $this->session->set_flashdata('success', 'Purchase Order ' . $header->po_number . ' berhasil dihapus permanen.');
            redirect($this->menu_key);
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus Purchase Order!');
            redirect($this->menu_key . '/detail/' . $id);
        }
    }

    // --- FUNGSI EXPORT (letterhead resmi utk cetak PO ke supplier) ---
    /**
     * Cetak PO — letterhead "Purchasing Order" resmi (user 2026-09-05, harus
     * mirip 100% dgn contoh dokumen asli PT New Specialife Industries).
     * render_po_letterhead_pdf()/build_po_letterhead_data() di
     * document_pdf_helper.php — dipakai SEMUA 4 tipe PO lewat class dasar
     * ini, tidak perlu di-override sama sekali.
     */
    public function export_pdf($id) {
        if (!check_access($this->menu_key, 'view')) { show_error('Izin Ditolak.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);

        // Satu PO bisa berisi item dari beberapa PR (fitur tarik multi-PR) —
        // dipakai utk isi field "REF." saja di letterhead ini.
        $source_prs = $this->Po_model->get_source_prs($id);
        $ref_text = !empty($source_prs) ? implode(', ', array_column($source_prs, 'pr_number')) : '';

        $data = build_po_letterhead_data($header, $details, $ref_text);

        $filename = 'PO_' . str_replace([':', ' ', '/', '\\'], ['', '_', '-', '-'], $header->po_number) . '.pdf';
        render_po_letterhead_pdf($data, $filename);
    }
    public function export_excel($id) {
        if (!check_access($this->menu_key, 'view')) { show_error('Izin Ditolak.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) show_404();
        $details = $this->Po_model->get_po_details_by_id($id);

        // Judul sheet & label kolom item — cuma Jasa & Asset yg beda dari
        // generik "PURCHASE ORDER"/"Kode Barang"/"Nama Barang" (Produksi &
        // General SELALU pakai yang generik, persis perilaku asli).
        $sheet_title = in_array($this->po_type, ['jasa', 'asset'], true)
            ? 'PURCHASE ORDER ' . strtoupper($this->po_type)
            : 'PURCHASE ORDER';
        $item_label = $this->po_type === 'jasa' ? 'Jasa' : ($this->po_type === 'asset' ? 'Asset' : 'Barang');

        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', $sheet_title); $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16); $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('A3', 'Nomor PO:'); $sheet->setCellValue('B3', $header->po_number);
        $sheet->setCellValue('E3', 'Tanggal:'); $sheet->setCellValue('F3', date('d-m-Y', strtotime($header->po_date)));
        $sheet->setCellValue('A4', 'Supplier:'); $sheet->setCellValue('B4', $header->nama_supplier);
        $sheet->setCellValue('E4', 'Currency:'); $sheet->setCellValue('F4', $header->currency_code . ($header->currency_code != 'IDR' ? ' (Kurs: '.number_format($header->exchange_rate, 4).')' : ''));
        $startRow = 7;
        $sheet->setCellValue('A'.$startRow, 'No'); $sheet->setCellValue('B'.$startRow, 'Kode ' . $item_label); $sheet->setCellValue('C'.$startRow, 'Nama ' . $item_label); $sheet->setCellValue('D'.$startRow, 'Satuan'); $sheet->setCellValue('E'.$startRow, 'Qty'); $sheet->setCellValue('F'.$startRow, 'Harga (' . $header->currency_code . ')'); $sheet->setCellValue('G'.$startRow, 'Total (' . $header->currency_code . ')');
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->setBold(true); $sheet->getStyle('A'.$startRow.':G'.$startRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); $sheet->getStyle('A'.$startRow.':G'.$startRow)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $rowNum = $startRow + 1; $no = 1; $grand_total_base = 0;
        foreach ($details as $d) {
             $total_base = $d->qty * $d->base_price;
             $sheet->setCellValue('A'.$rowNum, $no++); $sheet->setCellValue('B'.$rowNum, $d->item_code); $sheet->setCellValue('C'.$rowNum, $d->item_name); $sheet->setCellValue('D'.$rowNum, $d->item_unit); $sheet->setCellValue('E'.$rowNum, $d->qty); $sheet->setCellValue('F'.$rowNum, $d->base_price); $sheet->setCellValue('G'.$rowNum, $total_base);
             $sheet->getStyle('E'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $sheet->getStyle('F'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.0000'); /* harga satuan 4 desimal (migration_po_price_4dec) */ $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $grand_total_base += $total_base; $rowNum++;
        }
         $sheet->getStyle('A'.$startRow.':G'.($rowNum-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
         $sheet->setCellValue('F'.$rowNum, 'Subtotal:'); $sheet->setCellValue('G'.$rowNum, $grand_total_base); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $rowNum++;
        $ppn_calc = po_ppn_from_header($header, $grand_total_base);
        if ($ppn_calc['applied']) {
             // Subtotal yang dicetak di atas adalah jumlah baris barang. Kalau PPN
             // INCLUDE, baris barang itu sudah mengandung PPN -- jadi subtotalnya
             // ditimpa dgn DPP supaya Subtotal + PPN = Grand Total tetap benar.
             if ($ppn_calc['inclusive']) {
                 $sheet->setCellValue('G'.($rowNum-1), $ppn_calc['dpp']);
             }
             $ppn_amount = $ppn_calc['ppn'];
             $sheet->setCellValue('F'.$rowNum, po_ppn_label($ppn_calc['rate'], $ppn_calc['inclusive']) . ':'); $sheet->setCellValue('G'.$rowNum, $ppn_amount); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); $rowNum++;
             $sheet->setCellValue('F'.$rowNum, 'Grand Total:'); $sheet->setCellValue('G'.$rowNum, $ppn_calc['total']); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true)->setSize(12); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        } else {
              $sheet->setCellValue('F'.$rowNum, 'Grand Total:'); $sheet->setCellValue('G'.$rowNum, $grand_total_base); $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true)->setSize(12); $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        }
         $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        foreach (range('A', 'G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
        $writer = new Xlsx($spreadsheet); $filename = 'PO_'.str_replace(['/','\\'], '-', $header->po_number).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="'. $filename.'"'); header('Cache-Control: max-age=0');
        $writer->save('php://output'); exit;
    }

    /**
     * Export List PO dengan Filter. Dulu cuma ada di Produksi & General
     * (TANPA filter type sama sekali — bug lama, PO Produksi export_list
     * ikut nampilin semua tipe PO tercampur). Sekarang berlaku SEMUA tipe
     * DAN discoping filter `type` = $this->po_type, jadi tiap tipe cuma
     * export daftarnya sendiri.
     */
    public function export_list_excel()
    {
        if (!check_access($this->menu_key, 'view')) {
            show_error('Izin Ditolak.', 403);
        }

        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');

        $this->db->select('po_header.*, suppliers.nama_supplier');
        $this->db->from('po_header');
        $this->db->join('suppliers', 'po_header.supplier_id = suppliers.id', 'left');
        $this->db->where_in('po_header.type', $this->_included_types());

        if (!empty($start_date)) {
            $this->db->where('po_header.po_date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('po_header.po_date <=', $end_date);
        }
        if (!empty($status)) {
            $this->db->where('po_header.status', $status);
        }

        $this->db->order_by('po_header.po_date', 'DESC');
        $pos = $this->db->get()->result();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'DAFTAR PURCHASE ORDER');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $filterInfo = 'Periode: ';
        if ($start_date && $end_date) {
            $filterInfo .= date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        } elseif ($start_date) {
            $filterInfo .= 'Dari ' . date('d/m/Y', strtotime($start_date));
        } elseif ($end_date) {
            $filterInfo .= 'Sampai ' . date('d/m/Y', strtotime($end_date));
        } else {
            $filterInfo .= 'Semua';
        }
        if ($status) {
            $filterInfo .= ' | Status: ' . $status;
        }

        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $sheet->setCellValue('A4', 'No.');
        $sheet->setCellValue('B4', 'Nomor PO');
        $sheet->setCellValue('C4', 'Tanggal');
        $sheet->setCellValue('D4', 'Supplier');
        $sheet->setCellValue('E4', 'Currency');
        $sheet->setCellValue('F4', 'Total Amount');
        $sheet->setCellValue('G4', 'Status');

        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:G4')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 5;
        $no = 1;

        foreach ($pos as $po) {
            $this->db->select('SUM(qty * base_price) as total');
            $this->db->from('po_details');
            $this->db->where('po_header_id', $po->id);
            $total_query = $this->db->get()->row();
            $total_amount = $total_query ? $total_query->total : 0;

            $total_amount = po_ppn_from_header($po, $total_amount)['total'];

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $po->po_number);
            $sheet->setCellValue('C' . $row, date('d/m/Y', strtotime($po->po_date)));
            $sheet->setCellValue('D' . $row, $po->nama_supplier);
            $sheet->setCellValue('E' . $row, $po->currency_code ?? 'IDR');
            $sheet->setCellValue('F' . $row, (float)$total_amount);
            $sheet->setCellValue('G' . $row, $po->status);

            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(12);

        $filename = 'Daftar_PO_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export List PO ke PDF dengan Filter (lihat catatan filter `type` di
     * export_list_excel()).
     */
    public function export_list_pdf()
    {
        if (!check_access($this->menu_key, 'view')) {
            show_error('Izin Ditolak.', 403);
        }

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $status     = $this->input->get('status');

        $this->db->select('po_header.*, suppliers.nama_supplier,
            COALESCE(SUM(po_details.qty * po_details.base_price), 0) as subtotal');
        $this->db->from('po_header');
        $this->db->join('suppliers', 'po_header.supplier_id = suppliers.id', 'left');
        $this->db->join('po_details', 'po_header.id = po_details.po_header_id', 'left');
        $this->db->where_in('po_header.type', $this->_included_types());
        if (!empty($start_date)) { $this->db->where('po_header.po_date >=', $start_date); }
        if (!empty($end_date))   { $this->db->where('po_header.po_date <=', $end_date); }
        if (!empty($status))     { $this->db->where('po_header.status', $status); }
        $this->db->group_by('po_header.id');
        $this->db->order_by('po_header.po_date', 'DESC');
        $pos = $this->db->get()->result();

        $status_colors = [
            'Draft'     => 'orange',
            'Submitted' => 'blue',
            'Confirmed' => 'green',
            'Completed' => 'green',
            'Cancelled' => 'red',
            'Rejected'  => 'red',
        ];

        $rows = '';
        $no   = 1;
        foreach ($pos as $po) {
            $total = po_ppn_from_header($po, (float) $po->subtotal)['total'];
            $color = $status_colors[$po->status] ?? '#333';
            $rows .= '<tr>
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($po->po_number) . '</td>
                <td>' . date('d/m/Y', strtotime($po->po_date)) . '</td>
                <td>' . htmlspecialchars($po->nama_supplier ?? '-') . '</td>
                <td style="text-align:center">' . htmlspecialchars($po->currency_code ?? 'IDR') . '</td>
                <td style="text-align:right">' . number_format($total, 0, ',', '.') . '</td>
                <td style="text-align:center;color:' . $color . ';font-weight:bold">' . htmlspecialchars($po->status) . '</td>
            </tr>';
        }

        $filter_info = '';
        if ($start_date || $end_date) {
            $filter_info = 'Periode: ' . ($start_date ? date('d/m/Y', strtotime($start_date)) : '...') . ' s/d ' . ($end_date ? date('d/m/Y', strtotime($end_date)) : '...');
        }
        if ($status) { $filter_info .= ($filter_info ? ' | ' : '') . 'Status: ' . $status; }

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 4px; font-size: 14px; }
            p.info { text-align: center; font-size: 9px; color: #555; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #4472C4; color: #fff; padding: 5px 4px; text-align: left; }
            td { padding: 4px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f5f5f5; }
        </style>
        <h2>DAFTAR PURCHASE ORDER</h2>
        <p class="info">' . ($filter_info ? htmlspecialchars($filter_info) . ' | ' : '') . 'Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th width="4%">No</th>
                <th width="15%">PO Number</th>
                <th width="10%">Tanggal</th>
                <th width="27%">Supplier</th>
                <th width="8%">Mata Uang</th>
                <th width="18%">Subtotal</th>
                <th width="12%">Status</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';

        $temp_dir = APPPATH . 'cache/pdf_temp/';
        if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }
        $mpdf = new Mpdf(['tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('PO_List_' . date('Ymd') . '.pdf', 'D');
        exit;
    }

    /**
     * DIAGNOSTIK: Debug remarks field
     */
    public function debug_remarks() {
        if (!check_access($this->menu_key, 'view')) { show_error('Izin Ditolak.', 403); }

        $output = [
            'php_version'           => phpversion(),
            'max_input_vars'        => ini_get('max_input_vars'),
            'post_max_size'         => ini_get('post_max_size'),
            'upload_max_filesize'   => ini_get('upload_max_filesize'),
            'memory_limit'          => ini_get('memory_limit'),
            'php_sapi_name'         => php_sapi_name(),
            'os'                    => php_uname(),
            'post_keys'             => array_keys($_POST),
            'post_count'            => count($_POST),
            'remarks_exists'        => isset($_POST['remarks']),
            'remarks_value'         => $_POST['remarks'] ?? 'NOT SET',
            'remarks_length'        => strlen($_POST['remarks'] ?? ''),
            'db_last_error'         => $this->db->error(),
        ];

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * AJAX: Update an existing PO item (price, qty, description) — Draft, atau
     * status Submitted/Confirmed khusus Admin selama belum ada penerimaan/penyelesaian
     */
    public function ajax_update_item($detail_id) {
        $detail_id = (int)$detail_id;

        $detail = $this->Po_model->get_detail_by_id($detail_id);
        if (!$detail) {
            return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);
        }

        $header = $this->Po_model->get_po_header_by_id($detail->po_header_id);
        if (!$header || $header->type !== $this->po_type) {
            return $this->_json(['success' => false, 'message' => 'PO tidak ditemukan']);
        }

        $has_receipts = $this->_has_receipts($detail->po_header_id);
        if (!can_edit_po_items($header, $this->menu_key, $has_receipts)) {
            return $this->_json(['success' => false, 'message' => $has_receipts && is_po_admin_edit($header)
                ? "Item terkunci karena PO sudah ada {$this->completion_label}"
                : 'PO tidak dalam status Draft']);
        }
        $admin_edit = is_po_admin_edit($header);

        $qty        = (float)$this->input->post('qty');
        $base_price = (float)preg_replace('/[^\d.]/', '', $this->input->post('base_price') ?? '0');
        $description = trim($this->input->post('description') ?? '');

        if ($qty <= 0) {
            return $this->_json(['success' => false, 'message' => 'Qty harus lebih dari 0']);
        }
        if ($base_price < 0) {
            return $this->_json(['success' => false, 'message' => 'Harga tidak boleh negatif']);
        }

        $exchange_rate = (float)($header->exchange_rate ?: 1);
        $local_price   = $base_price * $exchange_rate;

        $this->Po_model->update_item($detail_id, [
            'qty'         => $qty,
            'base_price'  => round($base_price, 4),   // 4 desimal (migration_po_price_4dec)
            'local_price' => round($local_price, 4),
            'total_local' => round($qty * $local_price, 2),
            'total'       => round($qty * $local_price, 2),
            'description' => $description,
        ]);

        if ($admin_edit) {
            add_log('ADMIN_EDIT', $this->module_label, 'Edit item #' . $detail_id . ' pada PO ' . $header->po_number . ' di status ' . $header->status . ' (qty=' . $qty . ', harga=' . $base_price . ', override Admin).');
        }

        return $this->_json([
            'success'   => true,
            'message'   => 'Item berhasil diperbarui.',
            'new_qty'   => $qty,
            'new_price' => $base_price,
            'new_total' => $qty * $base_price,
        ]);
    }

    // ===================== HELPER =====================

    /** Pesan seragam saat item manual ditolak (PO wajib tarik dari PR). */
    protected function _manual_items_error() {
        return 'Input item manual dinonaktifkan untuk ' . $this->module_label
             . '. Item harus ditarik dari PR ("Tarik dari PR").';
    }

    protected function _json($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * AJAX: Load BOM items from Sales Contract into PO details. Konsep BOM/
     * Sales Contract cuma berlaku utk Produksi & General (barang manufaktur)
     * — Jasa & Asset tidak "dibuat dari BOM", jadi method ini dijaga cuma
     * jalan utk 2 tipe itu (tetap ada di class dasar/warisan, tapi 404
     * fungsional utk Jasa/Asset kalau somehow ke-akses).
     */
    public function load_bom_from_sc($po_id) {
        $this->output->set_content_type('application/json');

        if (!in_array($this->po_type, ['produksi', 'non_produksi'], true)) {
            echo json_encode(['success' => false, 'message' => 'Fitur ini tidak berlaku untuk tipe PO ini.']); return;
        }

        $po_id = (int)$po_id;

        $sc_id = (int)$this->input->post('sc_id');
        if (!$sc_id) {
            echo json_encode(['success' => false, 'message' => 'Sales Contract tidak valid']); return;
        }

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header || $header->type !== $this->po_type) {
            echo json_encode(['success' => false, 'message' => 'PO tidak ditemukan']); return;
        }
        if (!can_edit_po_items($header, $this->menu_key, $this->_has_receipts($po_id))) {
            echo json_encode(['success' => false, 'message' => 'PO tidak dalam status Draft atau item sudah terkunci penerimaan barang']); return;
        }

        $sc_details = $this->Sc_model->get_items_by_contract($sc_id);
        if (empty($sc_details)) {
            echo json_encode(['success' => false, 'message' => 'Sales Contract tidak memiliki item']); return;
        }

        $inserted_count = 0;
        $updated_count  = 0;
        $errors = [];

        foreach ($sc_details as $sc_item) {
            if ($sc_item->item_type !== 'finished') continue;

            $fg_id  = $sc_item->fg_id;
            $sc_qty = (float)$sc_item->qty;

            $bom_items = $this->Bom_model->get_bom_by_fg($fg_id);
            if (empty($bom_items)) {
                $errors[] = ($sc_item->nama_barang ?? 'FG') . ': tidak ada BOM';
                continue;
            }

            foreach ($bom_items as $bom) {
                $calculated_qty = (float)$bom->qty * $sc_qty;
                $existing = $this->Po_model->check_existing_item($po_id, $bom->material_id, $bom->item_type);

                if ($existing) {
                    $this->Po_model->update_item_qty($existing->id, (float)$existing->qty + $calculated_qty);
                    $updated_count++;
                } else {
                    $this->Po_model->insert_item_direct([
                        'po_header_id'     => $po_id,
                        'item_id'          => $bom->material_id,
                        'item_type'        => $bom->item_type,
                        'sales_contract_id'=> $sc_id,
                        'qty'              => $calculated_qty,
                        'base_price'       => 0,
                        'local_price'      => 0,
                        'total_local'      => 0,
                        'total'            => 0,
                        'description'      => 'Auto BOM dari: ' . ($sc_item->nama_barang ?? ''),
                    ]);
                    $inserted_count++;
                }
            }
        }

        $msg = '';
        if ($inserted_count > 0) $msg .= "$inserted_count item baru ditambahkan. ";
        if ($updated_count  > 0) $msg .= "$updated_count item qty diperbarui. ";
        if (!empty($errors))     $msg .= implode('; ', $errors);

        if ($inserted_count > 0 || $updated_count > 0) {
            echo json_encode(['success' => true,  'message' => trim($msg) ?: 'Selesai.']);
        } else {
            echo json_encode(['success' => false, 'message' => trim($msg) ?: 'Tidak ada material yang ditambahkan.']);
        }
    }


    // ============================================================
    // TARIK ITEM DARI PR (multi-PR, sebagian item)
    //
    // Satu PO boleh menarik baris dari beberapa PR sekaligus, dan tiap baris
    // boleh diambil sebagian. Sisa qty dihitung on-the-fly dari po_details,
    // jadi PO yang dibatalkan/dihapus otomatis melepas jatahnya kembali.
    // ============================================================

    /**
     * AJAX: baris PR Approved bertipe $this->po_type yang masih punya sisa
     * qty, untuk isi modal "Tarik dari PR".
     */
    public function pr_pull_items() {
        if (!check_access($this->menu_key, 'edit') && !check_access($this->menu_key, 'add')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak.']);
        }

        $this->load->model('Purchase_request_model');
        $items = $this->Purchase_request_model->get_pullable_items($this->po_type, [
            'pr_header_id' => $this->input->get('pr_header_id'),
            'search'       => $this->input->get('search'),
            'date_from'    => $this->input->get('date_from'),
            'date_to'      => $this->input->get('date_to'),
        ]);

        return $this->_json(['success' => true, 'items' => $items]);
    }

    /**
     * Simpan pilihan dari modal ke po_details.
     *
     * Status yang diizinkan diambil dari status PO saat ini: kalau
     * can_edit_po_items() sudah meloloskan (Draft, atau override Admin), maka
     * penarikan pun boleh. Qty tetap diklamp ulang ke sisa sebenarnya di
     * Po_model::pull_pr_items(), jadi angka dari form tidak bisa over-order.
     */
    public function pull_from_pr($po_id) {
        $po_id = (int) $po_id;
        if (!check_access($this->menu_key, 'edit')) { show_error('Izin Ditolak.', 403); }

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header || $header->type !== $this->po_type) show_404();

        if (!can_edit_po_items($header, $this->menu_key, $this->_has_receipts($po_id))) {
            $this->session->set_flashdata('error', 'Item PO ini terkunci, tidak bisa menarik item PR lagi.');
            return redirect($this->menu_key . '/detail/' . $po_id);
        }

        $rows   = Po_model::build_pull_rows($this->input->post('pr_detail_ids'), $this->input->post('pull_qty'));
        $result = $this->Po_model->pull_pr_items($po_id, $rows, [$header->status]);

        $this->session->set_flashdata(!empty($result['success']) ? 'success' : 'error', $result['message']);
        redirect($this->menu_key . '/detail/' . $po_id);
    }
} // End Class
