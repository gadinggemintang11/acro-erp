<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Dompdf tidak lagi dipanggil langsung dari sini -- export PDF lewat
// render_letterhead_pdf() di document_pdf_helper.php (autoload).

class Consumable_out extends MY_Controller {

    /** Sinkron dengan Consumable_request::CATEGORIES. */
    const CATEGORIES = ['General', 'Produksi'];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Consumable_out_model', 'Consumable_request_model', 'Consumable_outstanding_model', 'Inventory_model']);
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    // Helper permission per-kategori (category_menu_key/can_category/
    // normalize_category_slug/allowed_categories) sekarang ada di
    // MY_Controller — dipakai bareng oleh Consumable_out, Consumable_outstanding,
    // Irf_status, dll. Wrapper tipis di bawah cuma supaya pemanggil lama di
    // file ini ('consumable_out' sebagai base_menu_key) tidak perlu diubah semua.
    private function _can_category($category, $action) {
        return $this->can_category('consumable_out', $category, $action);
    }

    private function _normalize_category_slug($slug) {
        return $this->normalize_category_slug($slug);
    }

    private function _allowed_categories($action) {
        return $this->allowed_categories('consumable_out', $action);
    }

    /**
     * Tombol "Buat PR" di popup galat kalau stok tidak cukup.
     *
     * User 2026-09-09: "ketika ada popup seperti digambar sediakan juga button
     * untuk membuat PR nya" — supaya warehouse tidak perlu menutup popup lalu
     * mencari menunya sendiri. Popup-nya sendiri (SweetAlert dgn tombol
     * tambahan) sudah disiapkan di templates/footer.php, yang membaca
     * flashdata 'error_action' => ['label' => ..., 'url' => ...].
     *
     * Tombol HANYA muncul kalau ketiganya terpenuhi:
     *   1. galatnya memang soal stok kurang (bukan galat lain),
     *   2. dokumen ini berasal dari IRF — PR di app ini WAJIB lahir dari IRF
     *      ([[revision_irf_auto_pr_at_issuance]]), jadi pengeluaran ad-hoc
     *      tanpa IRF tidak punya baris outstanding untuk di-PR-kan; kalau
     *      tetap dikasih tombol, user mendarat di halaman kosong,
     *   3. user memang boleh membuat PR untuk SEMUA tipe item di IRF itu
     *      (PR dari IRF = General/Jasa/Asset, lihat pr_menu_for_item_type()).
     *
     * Tombolnya langsung MEMBUAT PR untuk seluruh sisa outstanding IRF ini
     * (Consumable_outstanding::create_pr_from_irf()), bukan sekadar mengantar
     * ke halaman daftar.
     *
     * Riwayat 2 koreksi di hari yang sama, keduanya soal "kok cuma 1 barang":
     *   1. Link versi pertama selalu menambahkan ?search=<nama item>. Filter
     *      itu cuma muat SATU nama, jadi kalau yang kurang 2 item, halaman
     *      tujuannya menyembunyikan yang lain.
     *   2. Filternya dicabut, tapi halaman IRF Outstanding tetap drill-down
     *      per barang — 2 barang berarti 2 kali proses & 2 PR terpisah.
     *      Padahal 1 PR memang boleh berisi banyak barang (user: "PR ya bisa
     *      dibuat beberapa barang kok"), dan tombol "Buat PR Sisanya" di
     *      halaman detail pengeluaran sudah melakukan persis itu — cuma tidak
     *      pernah tampil di kasus ini, karena dokumen pengeluarannya sendiri
     *      gagal disimpan waktu SEMUA item stoknya 0.
     * Sekarang jalurnya disamakan: satu klik = satu PR berisi semua sisanya.
     *
     * @return array|null  siap dipakai set_flashdata('error_action', ...)
     */
    private function _pr_action_for_shortage($result, $request_id, $category) {
        if (empty($result['short'])) return null;
        if (empty($request_id)) return null;
        if (!can_create_pr_from_irf($request_id)) return null;
        if (!$this->can_category('consumable_outstanding', $category, 'view')) return null;

        $short_items = array_values(array_filter((array) ($result['short_items'] ?? [])));

        $label = count($short_items) > 1
            ? sprintf(lang_line('co_shortage_make_pr_multi', 'Buat PR untuk %d Barang Kurang'), count($short_items))
            : lang_line('co_shortage_make_pr', 'Buat PR untuk Kekurangan');

        return [
            'label' => $label,
            'url'   => base_url('consumable_outstanding/create_pr_from_irf/' . (int) $request_id),
        ];
    }

    // -------------------------------------------------------
    // Daftar dokumen pengeluaran barang habis pakai
    // Diakses lewat 2 pintu sidebar terpisah:     
    //   consumable_out/index/general  -> khusus kategori General
    //   consumable_out/index/produksi -> khusus kategori Produksi
    // URL lama tanpa segment (consumable_out) tetap jalan sebagai gabungan,
    // dibatasi ke kategori yang memang boleh dilihat user tsb.
    // -------------------------------------------------------
    public function index($category_slug = null) {
        $locked_category = $this->_normalize_category_slug($category_slug);

        if ($locked_category !== null) {
            if (!$this->_can_category($locked_category, 'view')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
            $visible_categories = [$locked_category];
        } else {
            $visible_categories = $this->_allowed_categories('view');
            if (empty($visible_categories)) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        // Filter kategori dari dropdown (hanya relevan saat combined/tidak locked)
        // divalidasi ulang terhadap kategori yang memang boleh dilihat.
        $category_filter = $locked_category;
        if ($category_filter === null) {
            $requested = $this->input->get('category');
            if (!empty($requested) && in_array($requested, $visible_categories, true)) {
                $category_filter = $requested;
            }
        }

        $filters = [
            'search'     => $this->input->get('search'),
            'status'     => $this->input->get('status'),
            'category'   => $category_filter ?: $visible_categories,
            'date_from'  => $this->input->get('date_from'),
            'date_to'    => $this->input->get('date_to'),
        ];

        $title = lang_line('title_co_index');
        if ($locked_category) { $title .= ' - ' . $locked_category; }

        $can_add = $locked_category
            ? $this->_can_category($locked_category, 'add')
            : !empty($this->_allowed_categories('add'));

        $data = [
            'title'            => $title,
            'records'          => $this->Consumable_out_model->get_all($filters),
            'pending_irfs'     => $this->Consumable_out_model->get_pending_irfs($category_filter ?: $visible_categories),
            'filters'          => array_merge($filters, ['category' => $category_filter]),
            'categories'       => $visible_categories,
            'locked_category'  => $locked_category,
            'category_slug'    => $category_slug,
            'can_add'          => $can_add,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/index', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Form buat dokumen baru (dengan optional request_id)
    // -------------------------------------------------------
    public function add($category_slug = null) {
        $locked_category = $this->_normalize_category_slug($category_slug);

        $request_id = $this->input->get('request_id');
        $request_header = null;
        $request_items = [];

        if ($request_id) {
            $request_header = $this->Consumable_request_model->get_header($request_id);
            // Hanya IRF yang sudah lolos final approval boleh di-fulfill via Consumable_out.
            if (!$request_header || $request_header->status !== 'Approved') {
                if ($request_header && $request_header->status === 'Released') {
                    show_error('Permintaan Barang ini sudah selesai di-release seluruhnya.', 400);
                } else {
                    show_error('Permintaan Barang tidak valid atau belum di-approve final.', 400);
                }
                return;
            }
            // Kategori mengikuti IRF sumbernya (otoritatif), bukan slug URL —
            // supaya orang tidak bisa "masuk lewat pintu General" lalu diam-diam
            // memproses IRF Produksi.
            $irf_category = $request_header->category ?? 'General';
            if (!$this->_can_category($irf_category, 'add')) {
                show_error('Anda tidak memiliki izin untuk memproses pengeluaran kategori ' . $irf_category . '.', 403);
                return;
            }
            $locked_category = $irf_category;
            // Pre-fill with outstanding qty (qty_requested - already-issued) so user
            // doesn't have to manually compute remaining when issuing partially.
            $request_items = $this->Consumable_outstanding_model->get_outstanding_for_cr($request_id);
        } elseif ($locked_category !== null) {
            // Ad-hoc, masuk lewat pintu kategori tertentu.
            if (!$this->_can_category($locked_category, 'add')) {
                show_error('Anda tidak memiliki izin.', 403);
                return;
            }
        }

        // Dropdown kategori (ad-hoc & tidak locked) cuma tampilkan yang boleh ditambah.
        $addable_categories = $locked_category ? [$locked_category] : $this->_allowed_categories('add');
        if (empty($addable_categories)) {
            show_error('Anda tidak memiliki izin untuk membuat pengeluaran kategori manapun.', 403);
            return;
        }

        // Stok yang ditampilkan di picker item = saldo bucket kategori dokumen ini
        // (gudang General & Produksi fisik terpisah, jadi angkanya beda per kategori).
        // Kalau belum locked (edge case: akses lama tanpa segment kategori & user
        // punya akses ke >1 kategori), pakai kategori pertama yang boleh ditambah
        // sebagai default tampilan awal.
        $stock_category = $locked_category ?: $addable_categories[0];

        $data = [
            'title'           => lang_line('title_co_create'),
            'items_list'      => $this->Consumable_out_model->get_inventory_items_with_stock($stock_category),
            'doc_number'      => $this->Consumable_out_model->generate_doc_number(),
            'request_id'      => $request_id,
            'request_header'  => $request_header,
            'request_items'   => $request_items,
            'categories'      => $addable_categories,
            'locked_category' => $locked_category,
            'category_slug'   => $category_slug,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/form', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Simpan dokumen baru (dengan optional request_id)
    // -------------------------------------------------------
    public function store() {
        $user_id       = $this->session->userdata('user_id');
        $request_id    = $this->input->post('request_id');
        $issuance_date = $this->input->post('issuance_date');
        $department    = $this->input->post('department');
        $recipient     = $this->input->post('recipient');
        $purpose       = $this->input->post('purpose');
        $notes         = $this->input->post('notes');
        $items         = $this->input->post('items'); // array

        // Kategori: kalau pengeluaran ini fulfill sebuah IRF, ikuti kategori
        // IRF-nya (bukan input bebas) supaya konsisten dgn sumbernya. Kalau
        // ad-hoc (tanpa IRF), ambil dari pilihan user di form.
        if ($request_id) {
            $src_header = $this->Consumable_request_model->get_header($request_id);
            $category = $src_header->category ?? 'General';
        } else {
            $category = $this->input->post('category');
            if (!in_array($category, self::CATEGORIES, true)) {
                $category = 'General';
            }
        }

        // Titik pengecekan otoritatif: apapun slug/entry point-nya, kategori
        // aktual dokumen ini yang dicek terhadap Role Access.
        if (!$this->_can_category($category, 'add')) {
            show_error('Anda tidak memiliki izin untuk membuat pengeluaran kategori ' . $category . '.', 403);
            return;
        }

        if (empty($issuance_date)) {
            $this->session->set_flashdata('error', 'Tanggal pengeluaran wajib diisi.');
            redirect('consumable_out/add' . ($request_id ? '?request_id=' . $request_id : ''));
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal satu item harus ditambahkan.');
            redirect('consumable_out/add' . ($request_id ? '?request_id=' . $request_id : ''));
            return;
        }

        // Filter item kosong
        $valid_items = array_filter($items, function($i) {
            return !empty($i['item_id']) && isset($i['qty']) && (float)$i['qty'] > 0;
        });

        if (empty($valid_items)) {
            $this->session->set_flashdata('error', 'Minimal satu item dengan qty > 0 harus diisi.');
            redirect('consumable_out/add' . ($request_id ? '?request_id=' . $request_id : ''));
            return;
        }

        // Kalau pengeluaran ini fulfill sebuah IRF, barangnya WAJIB termasuk
        // yang diminta IRF itu (user 2026-09-25). Dropdown di form sudah
        // dikunci, tapi gate-nya diulang di sini karena form bisa dilewati.
        //
        // Tanpa gate ini, barang di luar IRF bisa dikeluarkan atas nama IRF
        // tsb: baris IRF aslinya tidak pernah terpotong, jadi IRF-nya
        // menggantung selamanya di antrian Item Release dan ikut terbawa ke
        // "Buat PR Sisanya". Barang di luar IRF tetap bisa dikeluarkan lewat
        // pengeluaran ad-hoc (tanpa request_id).
        if ($request_id) {
            $allowed = [];
            foreach ($this->Consumable_request_model->get_details($request_id) as $d) {
                $allowed[($d->item_type ?: 'inventory') . '-' . (int) $d->item_id] = true;
            }
            foreach ($valid_items as $item) {
                $ref = $this->Consumable_out_model->resolve_item_ref($item);
                $key = $ref['item_type'] . '-' . $ref['item_id'];
                if (!isset($allowed[$key])) {
                    $name = $item['item_name'] ?? $key;
                    $this->session->set_flashdata('error',
                        'Barang "' . $name . '" tidak ada di Permintaan Barang ini, jadi tidak bisa dikeluarkan atas namanya. '
                        . 'Pilih barang dari daftar Permintaan Barang, atau keluarkan lewat pengeluaran tanpa Permintaan Barang.');
                    redirect('consumable_out/add?request_id=' . (int) $request_id);
                    return;
                }
            }
        }

        $header_data = [
            'doc_number'    => $this->Consumable_out_model->generate_doc_number(),
            'request_id'    => $request_id ? (int)$request_id : null,
            'issuance_date' => $issuance_date,
            'department'    => $department,
            'category'      => $category,
            'recipient'     => $recipient,
            'purpose'       => $purpose,
            'notes'         => $notes,
            'status'        => 'Created',
            'created_by'    => $user_id,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $result = $this->Consumable_out_model->create($header_data, $valid_items);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Dokumen berhasil dibuat. No. Dok: ' . $result['doc_number'] . '. Silakan konfirmasi untuk memproses stok.');
            redirect('consumable_out/detail/' . $result['id']);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
            $action = $this->_pr_action_for_shortage($result, $request_id, $category);
            if ($action) { $this->session->set_flashdata('error_action', $action); }
            redirect('consumable_out/add' . ($request_id ? '?request_id=' . $request_id : ''));
        }
    }

    // -------------------------------------------------------
    // Konfirmasi dokumen → proses stok (FIFO) → status: Confirmed
    // -------------------------------------------------------
    public function confirm($id) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if (!$this->_can_category($header->category ?? 'General', 'confirm')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if ($header->status !== 'Created') {
            $this->session->set_flashdata('error', 'Hanya dokumen berstatus Created yang bisa dikonfirmasi.');
            redirect('consumable_out/detail/' . $id);
            return;
        }

        // Item Release: attachment "Bukti Pengeluaran" wajib diupload SETELAH
        // pengeluaran terjadi (bukan syarat sebelum Confirm) — pola sama
        // seperti Production_receive: aksi utama jalan dulu, baru ada
        // kewajiban tindak lanjut terpisah. Lihat gate di detail() view
        // (badge/reminder) — ERP Revision Notes 2026-09-02, Fase 2 poin 8.
        $user_id = $this->session->userdata('user_id');
        $result  = $this->Consumable_out_model->process_confirm($id, $user_id);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Dokumen berhasil dikonfirmasi dan stok telah dipotong.');
            // Notifikasi "Item Release" (ERP Revision Notes, [[erp_revision_cross_cutting]])
            // -> requestor IRF asal, kalau dokumen ini memang fulfill sebuah IRF
            // (bukan pengeluaran ad-hoc tanpa request_id).
            if (!empty($header->request_id)) {
                $this->load->model('Consumable_request_model');
                $irf = $this->Consumable_request_model->get_header($header->request_id);
                if ($irf) {
                    $this->notify_users(
                        [(int) $irf->created_by],
                        'Barang Dikeluarkan',
                        'Permintaan Barang ' . $irf->request_number . ': barang telah dikeluarkan gudang (No. Dok ' . $header->doc_number . ').',
                        'consumable_request/detail/' . $header->request_id,
                        'success', 'fa-box-open',
                        'consumable_out', (int) $id
                    );
                }
            }
        } else {
            $this->session->set_flashdata('error', 'Konfirmasi gagal: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
            $action = $this->_pr_action_for_shortage($result, $header->request_id ?? null, $header->category ?? 'General');
            if ($action) { $this->session->set_flashdata('error_action', $action); }
        }
        redirect('consumable_out/detail/' . $id);
    }

    // -------------------------------------------------------
    // Batalkan dokumen → reverse stok → status: Cancelled
    // -------------------------------------------------------
    public function cancel($id) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if (!$this->_can_category($header->category ?? 'General', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if (!in_array($header->status, ['Created', 'Confirmed'])) {
            $this->session->set_flashdata('error', 'Dokumen tidak dapat dibatalkan.');
            redirect('consumable_out/detail/' . $id);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $result  = $this->Consumable_out_model->process_cancel($id, $user_id);

        if ($result['success']) {
            $msg = $header->status === 'Confirmed'
                ? 'Dokumen dibatalkan dan stok telah dikembalikan.'
                : 'Dokumen berhasil dibatalkan.';
            $this->session->set_flashdata('success', $msg);
        } else {
            $this->session->set_flashdata('error', 'Pembatalan gagal: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
        }
        redirect('consumable_out/detail/' . $id);
    }

    // -------------------------------------------------------
    // Detail dokumen
    // -------------------------------------------------------
    public function detail($id) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if (!$this->_can_category($header->category ?? 'General', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        // "Buat PR Sisanya" (ERP Revision Notes 2026-09-03, [[revision_irf_auto_pr_at_issuance]]):
        // begitu dokumen pengeluaran ini Confirmed (barang sudah keluar dari
        // gudang), cek ulang outstanding IRF sumbernya — kalau qty diminta >
        // qty yang sudah dikeluarkan (issuance partial krn stock tidak cukup),
        // tampilkan tombol 1-klik yang langsung memanggil
        // Consumable_outstanding::create_pr() utk sisa baris yang masih
        // outstanding, tanpa warehouse harus pindah ke menu IRF Outstanding
        // terpisah. Sengaja 1 klik eksplisit (bukan otomatis nempel di
        // confirm()) — PR yang dihasilkan auto-approved & langsung nyangkut
        // di procurement, jadi warehouse harus sadar & sengaja memicunya.
        //
        // Qty PR per baris = sisa - yang bisa dipenuhi stok - yang sudah ada di
        // PR aktif (user 2026-09-15: "minta 10, stok 2 -> PR 8"). Tombolnya
        // hanya tampil kalau masih ada yang perlu di-PR-kan.
        $remaining_outstanding = [];
        $remaining_pr_qty = 0.0;
        if ($header->status === 'Confirmed' && !empty($header->request_id)) {
            $cr_details = $this->Consumable_request_model->get_details($header->request_id);
            $cr_detail_ids = array_map(function ($d) { return $d->id; }, $cr_details);
            if (!empty($cr_detail_ids)) {
                $map = $this->Consumable_outstanding_model->get_pr_need_map($cr_detail_ids);
                foreach ($map as $row) {
                    if ((float) $row->qty_outstanding > 0.0001) {
                        $remaining_outstanding[] = $row;
                        $remaining_pr_qty += (float) $row->qty_pr;
                    }
                }
            }
        }

        $data = [
            'title'                  => sprintf(lang_line('title_co_detail'), $header->doc_number),
            'header'                 => $header,
            'details'                => $this->Consumable_out_model->get_details($id),
            'attachments'            => $this->Consumable_out_model->get_attachments($id),
            'remaining_outstanding'  => $remaining_outstanding,
            'remaining_pr_qty'       => $remaining_pr_qty,
            // PR yang sudah dibuat utk baris sisa ini -> keterangan + link
            // (user 2026-09-20). Kosong kalau belum pernah di-PR-kan.
            'pr_docs'                => $remaining_outstanding
                ? $this->Consumable_outstanding_model->get_pr_docs_map(array_map(function ($r) {
                    return (int) $r->cr_detail_id;
                }, $remaining_outstanding))
                : [],
            // Izin PR mengikuti tipe item yang tersisa (General/Jasa/Asset),
            // bukan menu PR Produksi — lihat pr_menu_for_item_type().
            'can_create_pr'          => can_create_pr_for_item_types(array_map(function ($r) {
                return $r->item_type ?? 'inventory';
            }, $remaining_outstanding)),
            // Satu daftar "Bukti Serah Terima" -- bukti dari requestor & dari
            // gudang disatukan (user 2026-09-14: keduanya dokumen yang sama).
            // Kosong sebelah kalau pengeluaran ini ad-hoc tanpa IRF.
            'handover_attachments'   => irf_merge_handover_attachments(
                !empty($header->request_id)
                    ? $this->Consumable_request_model->get_attachments($header->request_id) : [],
                $this->Consumable_out_model->get_attachments($id)
            ),
            'request_number'         => !empty($header->request_id)
                ? (($irf_src = $this->Consumable_request_model->get_header($header->request_id)) ? $irf_src->request_number : null)
                : null,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/detail', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Hapus dokumen (hanya Draft)
    // -------------------------------------------------------
    public function delete($id) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if (!$this->_can_category($header->category ?? 'General', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $back = 'consumable_out/index/' . strtolower($header->category ?? 'General');

        if ($header->status !== 'Created') {
            $this->session->set_flashdata('error', 'Hanya dokumen berstatus Created yang bisa dihapus.');
            redirect($back);
            return;
        }

        $this->Consumable_out_model->delete($id);
        $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        redirect($back);
    }

    // -------------------------------------------------------
    // Export PDF
    // -------------------------------------------------------
    public function export_pdf($id) {
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if (!$this->_can_category($header->category ?? 'General', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $data = [
            'header'  => $header,
            'details' => $this->Consumable_out_model->get_details($id),
        ];

        // Lewat render_letterhead_pdf(): selain merapikan duplikasi, opsi
        // chroot + isRemoteEnabled di sana WAJIB supaya logo kop surat (path
        // absolut di FCPATH) mau dimuat Dompdf. Options polos yang dipakai
        // sebelumnya bikin kop terbit tanpa gambar.
        $filename = 'Bukti_Pengeluaran_' . str_replace('/', '-', $header->doc_number) . '.pdf';
        render_letterhead_pdf('consumable_out/pdf_template', $data, $filename);
    }

    // -------------------------------------------------------
    // AJAX: Ambil detail item inventaris
    // -------------------------------------------------------
    public function ajax_get_item($item_id) {
        if (!$this->input->is_ajax_request()) { show_404(); }

        $item = $this->Consumable_out_model->get_inventory_item($item_id);
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    // -------------------------------------------------------
    // Attachments — "Bukti Pengeluaran" (Item Release, Fase 2 poin 8)
    // Pola sama persis dengan Erf::upload_attachment()/delete_attachment().
    // -------------------------------------------------------
    public function upload_attachment($id) {
        $id = (int) $id;
        if ($id <= 0) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }
        if (!$this->_can_category($header->category ?? 'General', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $upload_path = FCPATH . 'uploads/consumable_out_attachments/';
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
            redirect('consumable_out/detail/' . $id);
            return;
        }

        $f = $this->upload->data();
        $this->Consumable_out_model->add_attachment([
            'consumable_out_header_id' => $id,
            'file_name'     => $f['file_name'],
            'original_name' => $f['orig_name'],
            'file_path'     => 'uploads/consumable_out_attachments/' . $f['file_name'],
            'file_type'     => $f['file_type'],
            'file_size'     => $f['file_size'] * 1024, // KB to bytes (CI raw is in kb)
            'uploaded_by'   => $this->session->userdata('user_id'),
        ]);
        $this->session->set_flashdata('success', 'Attachment berhasil di-upload.');
        redirect('consumable_out/detail/' . $id);
    }

    public function delete_attachment($attachment_id) {
        $att = $this->Consumable_out_model->get_attachment((int) $attachment_id);
        if (!$att) { show_404(); }

        $header = $this->Consumable_out_model->get_header($att->consumable_out_header_id);
        if (!$header || !$this->_can_category($header->category ?? 'General', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $full = FCPATH . $att->file_path;
        if (is_file($full)) { @unlink($full); }

        $this->Consumable_out_model->delete_attachment((int) $attachment_id);
        $this->session->set_flashdata('success', 'Attachment dihapus.');
        redirect('consumable_out/detail/' . $att->consumable_out_header_id);
    }

    public function attachment($attachment_id) {
        $att = $this->Consumable_out_model->get_attachment((int) $attachment_id);
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
