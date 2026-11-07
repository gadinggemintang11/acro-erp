<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
    // GMPO sekarang menu GABUNGAN General+Jasa+Asset (user 2026-09-05).
    // Klik "Create New GMPO" di sidebar HARUS langsung ke FORM ini (pola yg
    // sama spt IRF/PRF/ERF/MRF), DAN header+item digabung jadi 1 submit
    // (user susulan: "header dan buat detail itemnya gausa di pisah" --
    // pola staged PERSIS spt Material_request::store()/add.php, sesi yg
    // sama). Dropdown "Kategori" menentukan:
    //   1. Endpoint submit form (purchase_order_general/_jasa/_asset/store)
    //   2. Endpoint pencarian item (.../search_items) -- Jasa dibatasi Master
    //      Jasa, Asset dibatasi Master Asset, General bebas (lihat
    //      $search_item_type di controller).
    // KETIGA endpoint store()/search_items() itu SUDAH ADA di class dasar
    // Purchase_order (diwarisi semua subclass) -- 0 endpoint baru per
    // kategori, cuma JS yang pindah-pindah target.
    //
    // REVISI 2026-09-09: item manual DIMATIKAN utk ketiga kategori -- item
    // PO wajib ditarik dari PR. Baris input manual + picker Select2-nya
    // tidak dirender kalau $allow_manual_items false (server-side-nya
    // dijaga Purchase_order::store()/search_items()).
    $allow_manual_items = !empty($allow_manual_items);

    // 2026-10-02: view ini DIPAKAI ULANG oleh PMPO (Purchase_order::add()),
    // yang cuma punya satu kategori. Kalau $fixed_route diisi, dropdown
    // Kategori tidak dirender dan ketiga endpoint JS dikunci ke controller
    // itu saja -- tidak ada endpoint baru, hanya tujuannya yang tetap.
    $fixed_route = $fixed_route ?? null;           // mis. 'purchase_order'
    $fixed_kategori = $fixed_kategori ?? 'general'; // kunci utk peta URL JS
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h4 mb-0"><i class="fas fa-plus-circle text-primary mr-2"></i><?= lang_line('po_create_new', 'Buat Header Purchase Order'); ?></h1>
        </div>
        <div class="col text-right">
            <a href="<?= base_url($fixed_route ?: 'purchase_order_general'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('po_cancel', 'Batal'); ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $this->session->flashdata('warning'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <form method="post" id="gmpoAddForm" action="<?= base_url(($fixed_route ?: 'purchase_order_general') . '/store'); ?>">
        <input type="hidden" name="items" id="items_payload" value="[]">
        <input type="hidden" name="pulled_pr_rows" id="pulled_pr_payload" value="[]">

        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-gradient-primary text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle mr-2"></i><?= lang_line('po_header_info', 'Informasi Header'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!$fixed_route): // PMPO cuma satu kategori -> dropdown tidak perlu ?>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_kategori', 'Kategori'); ?> <span class="text-danger">*</span></label>
                        <select id="gmpo_kategori" class="form-control" required>
                            <option value="general">General</option>
                            <option value="jasa">Jasa</option>
                            <option value="asset">Asset</option>
                        </select>
                        <small class="form-text text-muted"><?= lang_line('po_kategori_hint', 'Menentukan jenis item yang bisa dipilih di bawah.'); ?></small>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_supplier', 'Supplier'); ?> <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-control select2-modal" required style="width: 100%;">
                            <option value=""><?= lang_line('po_supplier_select', '-- Pilih Supplier --'); ?></option>
                            <?php foreach($suppliers as $sup): ?>
                                <option value="<?= $sup->id; ?>"><?= htmlspecialchars($sup->nama_supplier); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_date', 'Tanggal PO'); ?> <span class="text-danger">*</span></label>
                        <input type="date" name="po_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_number_label', 'Nomor PO'); ?> <small class="text-muted"><?= lang_line('po_number_help', '(Biarkan kosong untuk auto-generate)'); ?></small></label>
                        <input type="text" name="po_number" class="form-control" placeholder="<?= lang_line('po_number_placeholder', 'Contoh: POG/ACRO/26110001'); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_shipping_mode', 'Mode Pengiriman'); ?></label>
                        <select name="shipping_mode_code" class="form-control select2-modal" style="width: 100%;">
                            <option value=""><?= lang_line('po_shipping_mode_select', '-- Pilih Mode --'); ?></option>
                            <?php foreach($shipping_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('po_payment_mode', 'Mode Pembayaran'); ?></label>
                        <select name="payment_mode_code" class="form-control select2-modal" style="width: 100%;">
                            <option value=""><?= lang_line('po_payment_mode_select', '-- Pilih Mode --'); ?></option>
                            <?php foreach($payment_modes as $mode): ?> <option value="<?= $mode->code; ?>"><?= htmlspecialchars($mode->description); ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                     <div class="col-md-4 form-group">
                        <label><?= lang_line('po_currency', 'Mata Uang'); ?> <span class="text-danger">*</span></label>
                        <select name="currency_code" id="add_currency_code" class="form-control select2-modal" required style="width: 100%;">
                            <?php foreach($currencies as $curr): ?> <option value="<?= $curr->code; ?>" <?= ($curr->code == 'IDR') ? 'selected' : ''; ?>><?= htmlspecialchars($curr->description); ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group" id="add_exchange_rate_div" style="display: none;">
                        <label><?= lang_line('po_exchange_rate', 'Kurs Tukar'); ?> (Rate) <span class="text-danger">*</span></label>
                        <input type="text" name="exchange_rate" class="form-control autonumeric-4dec text-right" value="1.0000">
                        <small id="add_kurs_info" class="form-text text-muted"><?= lang_line('pog_kurs_auto_info'); ?></small>
                    </div>
                </div>
                <div class="form-row">
                    <?php /*
                        PPN tiga langkah (klien 2026-10-02): centang kena PPN dulu,
                        lalu pilih harga sudah termasuk PPN atau belum, lalu isi
                        persennya -- tidak dipatok 11% lagi.
                          EXCLUDE : PPN dihitung dari subtotal, lalu ditambahkan
                          INCLUDE : PPN diambil dari total (harga sudah mengandungnya)
                    */ ?>
                    <div class="col-md-12">
                        <div class="border rounded p-2 mb-2 bg-light">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input ppn-toggle" id="add_has_ppn" name="has_ppn" value="1" >
                                <label class="form-check-label font-weight-bold" for="add_has_ppn"><?= lang_line('po_has_ppn_check', 'Kena PPN'); ?></label>
                            </div>
                            <div class="form-row ppn-detail" style="display:none;">
                                <div class="col-md-6 form-group mb-0">
                                    <label class="small mb-1"><?= lang_line('po_ppn_mode', 'Perhitungan PPN'); ?></label>
                                    <select name="ppn_inclusive" class="form-control form-control-sm">
                                        <option value="0" selected><?= lang_line('po_ppn_exclude', 'Exclude — harga belum termasuk PPN (PPN ditambahkan)'); ?></option>
                                        <option value="1" ><?= lang_line('po_ppn_include', 'Include — harga sudah termasuk PPN (PPN diambil dari total)'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-0">
                                    <label class="small mb-1"><?= lang_line('po_ppn_rate', 'Persen PPN'); ?></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="ppn_rate" class="form-control text-right" step="0.01" min="0" max="100" value="11">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label><?= lang_line('po_remarks', 'Catatan / Remarks'); ?></label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 mb-4">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-boxes mr-2"></i><?= lang_line('po_item_section', 'Item Barang'); ?> (<span id="item_count">0</span>)
                        </h6>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-info btn-sm" id="btn-open-pull-pr" data-toggle="modal" data-target="#pullPrModal">
                            <i class="fas fa-file-import mr-1"></i> <?= lang_line('po_pull_pr', 'Tarik dari PR'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="items_table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:35%;"><?= lang_line('po_th_item', 'Item'); ?> <span class="text-danger">*</span></th>
                                <th style="width:12%;"><?= lang_line('po_th_unit', 'Satuan'); ?></th>
                                <th style="width:15%;"><?= lang_line('po_th_qty', 'Qty'); ?> <span class="text-danger">*</span></th>
                                <th style="width:18%;"><?= lang_line('po_th_price', 'Harga Satuan'); ?> <span class="text-danger">*</span></th>
                                <th><?= lang_line('po_th_desc', 'Deskripsi'); ?></th>
                                <th style="width:8%;" class="text-center"><?= lang_line('common_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="items_body">
                            <tr id="empty_row">
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                    <?php if ($allow_manual_items): ?>
                                        <?= lang_line('po_no_item_yet', 'Belum ada item. Cari & tambahkan item di baris bawah.'); ?>
                                    <?php else: ?>
                                        <?= lang_line('po_no_item_yet_pr_only', 'Belum ada item. Klik "Tarik dari PR" untuk mengambil item dari Purchase Request.'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($allow_manual_items): ?>
                            <tr id="input_row" class="bg-light">
                                <td class="p-1">
                                    <select id="input_item" class="form-control form-control-sm" style="width:100%;">
                                        <option value="">-- <?= lang_line('po_search_item', 'Cari item...'); ?> --</option>
                                    </select>
                                </td>
                                <td class="p-1 text-center align-middle"><small id="input_unit" class="text-muted font-weight-bold">-</small></td>
                                <td class="p-1"><input type="number" id="input_qty" class="form-control form-control-sm text-right" placeholder="0.00" step="any" min="0.01"></td>
                                <td class="p-1"><input type="number" id="input_price" class="form-control form-control-sm text-right" placeholder="0" step="any" min="0"></td>
                                <td class="p-1"><input type="text" id="input_desc" class="form-control form-control-sm" placeholder="<?= lang_line('po_th_desc', 'Deskripsi'); ?>..."></td>
                                <td class="text-center align-middle">
                                    <button type="button" id="btn_add_item" class="btn btn-success btn-sm" disabled title="<?= lang_line('common_add', 'Tambah'); ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php /*
                            Ringkasan nilai PO -- dihitung ulang di sisi klien setiap kali
                            item berubah atau pengaturan PPN diubah. Rumusnya sengaja
                            dibuat sama persis dengan po_ppn_breakdown() di ppn_helper.php
                            supaya angka di layar == angka yang disimpan server.
                        */ ?>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="4" class="text-right align-middle font-weight-bold py-1"><?= lang_line('po_subtotal', 'Subtotal'); ?></td>
                                <td colspan="2" class="text-right align-middle font-weight-bold py-1"><span id="sum_subtotal">0.00</span></td>
                            </tr>
                            <tr id="sum_ppn_row" style="display:none;">
                                <td colspan="4" class="text-right align-middle py-1"><span id="sum_ppn_label">PPN</span></td>
                                <td colspan="2" class="text-right align-middle py-1"><span id="sum_ppn">0.00</span></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right align-middle font-weight-bold py-1"><?= lang_line('po_grand_total', 'Grand Total'); ?></td>
                                <td colspan="2" class="text-right align-middle font-weight-bold text-primary py-1"><span id="sum_total">0.00</span></td>
                            </tr>
                            <tr id="sum_pending_row" style="display:none;">
                                <td colspan="6" class="text-right small text-muted py-1"><i class="fas fa-info-circle mr-1"></i><span id="sum_pending_text"></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url($fixed_route ?: 'purchase_order_general'); ?>" class="btn btn-secondary"><?= lang_line('po_cancel', 'Batal'); ?></a>
            <button type="submit" class="btn btn-primary btn-load" id="btnSubmitPo"><i class="fas fa-save mr-1"></i> <?= lang_line('po_save', 'Simpan'); ?></button>
        </div>
    </form>
</div>

<?php
    // Modal "Tarik dari PR" — versi STAGED (PO belum tersimpan sama sekali
    // di titik ini, beda dari templates/partials/pull_pr_modal.php yg
    // dipakai halaman detail & SELALU butuh $header->id yg sudah ada).
    // Desain visual (grouping per-PR, klem qty, warning supplier beda)
    // SENGAJA ditiru dari partial itu, TAPI ditulis terpisah di sini biar
    // 0 resiko ke 4 halaman detail yg sudah pakai partial aslinya.
?>
<div class="modal fade" id="pullPrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i> <?= lang_line('po_pull_pr_title', 'Tarik Item dari Purchase Request'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('po_pull_pr_hint', 'Yang tampil hanya baris PR Approved yang masih punya sisa qty, sesuai Kategori yang dipilih di atas. Harga diisi belakangan di halaman detail setelah PO tersimpan.'); ?>
                </p>
                <div class="form-row mb-2">
                    <div class="col-md-5 mb-1">
                        <input type="text" id="pullPrSearch" class="form-control form-control-sm" placeholder="Cari no. PR / keperluan / nama item...">
                    </div>
                    <div class="col-md-3 mb-1">
                        <input type="date" id="pullPrFrom" class="form-control form-control-sm" title="Tanggal PR dari">
                    </div>
                    <div class="col-md-3 mb-1">
                        <input type="date" id="pullPrTo" class="form-control form-control-sm" title="Tanggal PR sampai">
                    </div>
                    <div class="col-md-1 mb-1">
                        <button type="button" id="pullPrRefresh" class="btn btn-sm btn-outline-secondary btn-block" title="Muat ulang"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:52vh; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="pullPrTable">
                        <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                            <tr class="text-center">
                                <th style="width:34px;"><input type="checkbox" id="pullPrCheckAll" title="Pilih semua"></th>
                                <th style="width:110px;">No. PR</th>
                                <th style="width:88px;">Tanggal</th>
                                <th style="width:100px;">Kode</th>
                                <th>Nama Item</th>
                                <th style="width:64px;">Satuan</th>
                                <th style="width:76px;">Qty PR</th>
                                <th style="width:84px;">Sudah PO</th>
                                <th style="width:76px;">Sisa</th>
                                <th style="width:104px;">Qty Diambil</th>
                                <th style="width:150px;">Supplier Usulan</th>
                            </tr>
                        </thead>
                        <tbody id="pullPrBody" class="small">
                            <tr><td colspan="11" class="text-center text-muted py-4">Memuat…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="pullPrSupplierWarn" class="alert alert-warning py-2 small mt-2 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted"><strong id="pullPrCount">0</strong> baris dipilih</small>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info" id="pullPrSubmit" disabled>
                        <i class="fas fa-file-import mr-1"></i> <?= lang_line('po_pull_pr_add', 'Tambahkan ke Daftar Item'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/autonumeric/4.6.0/autoNumeric.min.js"></script>

<script>
$(document).ready(function() {
    if ($('.select2-modal').length) {
        $('.select2-modal').select2();
    }

    var BASE_URL = '<?= base_url(); ?>';
    // false => baris input item manual tidak ada di DOM (item wajib dari PR).
    var MANUAL_ITEMS_ENABLED = <?= $allow_manual_items ? 'true' : 'false'; ?>;
    // Kalau dipakai PO Material, ketiga peta ini dikunci ke satu controller saja.
    var FIXED_ROUTE    = <?= $fixed_route ? json_encode($fixed_route) : 'null'; ?>;
    var FIXED_KATEGORI = <?= json_encode($fixed_kategori); ?>;

    function routeMap(suffix) {
        if (FIXED_ROUTE) {
            var m = {};
            m[FIXED_KATEGORI] = BASE_URL + FIXED_ROUTE + '/' + suffix;
            return m;
        }
        return {
            general: BASE_URL + 'purchase_order_general/' + suffix,
            jasa:    BASE_URL + 'purchase_order_jasa/'    + suffix,
            asset:   BASE_URL + 'purchase_order_asset/'   + suffix
        };
    }
    var STORE_URL_BY_KATEGORI  = routeMap('store');
    var SEARCH_URL_BY_KATEGORI = routeMap('search_items');
    var PR_PULL_URL_BY_KATEGORI = routeMap('pr_pull_items');

    // Item manual (item_id terisi, pr_detail_id null) DAN item tarikan PR
    // (pr_detail_id terisi, item_id/base_price null -- harga diisi belakangan
    // di halaman detail, konsisten dgn pull_from_pr() yg sudah ada) DITAMPUNG
    // di 1 array yang SAMA supaya tampil di 1 tabel, dipisah lagi pas submit.
    var stagedItems = []; // { key, item_id|null, pr_detail_id|null, text, unit, qty, base_price|null, description }
    var nextKey = 1;

    function currentKategori() {
        // PO Material tidak punya dropdown Kategori -> selalu kategori tetapnya.
        if (FIXED_ROUTE) return FIXED_KATEGORI;
        return $('#gmpo_kategori').val() || 'general';
    }

    // Item picker Select2 -- url AJAX-nya beda per Kategori (Jasa dibatasi
    // Master Jasa, Asset dibatasi Master Asset), jadi di-destroy+rebuild
    // tiap kali Kategori ganti (STATIC url string per instance -- paling
    // aman, tidak bergantung dukungan "url sbg function" di Select2).
    function initItemSelect2() {
        if (!MANUAL_ITEMS_ENABLED) return; // picker manual tidak dirender
        if ($('#input_item').data('select2')) { $('#input_item').select2('destroy'); }
        $('#input_item').select2({
            placeholder: '-- <?= lang_line("po_search_item", "Cari item..."); ?> --',
            minimumInputLength: 2,
            ajax: {
                url: SEARCH_URL_BY_KATEGORI[currentKategori()] || SEARCH_URL_BY_KATEGORI.general,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; }
            }
        });
    }
    initItemSelect2();

    // Kategori -> ganti action form, rebuild item-search Select2 ke endpoint
    // yang sesuai, & buang item yg sudah di-stage (item General/Jasa/Asset
    // tidak nyambung satu sama lain, source master-nya beda) supaya user
    // tidak salah campur kategori.
    $('#gmpo_kategori').on('change', function() {
        $('#gmpoAddForm').attr('action', STORE_URL_BY_KATEGORI[currentKategori()] || STORE_URL_BY_KATEGORI.general);
        if (stagedItems.length > 0) {
            stagedItems = [];
            renderItemsTable();
            Swal.fire({ icon: 'info', title: 'Item direset', text: 'Daftar item dikosongkan karena kategori diganti.', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        }
        initItemSelect2();
    });
    $('#gmpoAddForm').attr('action', STORE_URL_BY_KATEGORI[currentKategori()]);

    function validateInputRow() {
        var itemId = $('#input_item').val();
        var qty = parseFloat($('#input_qty').val());
        var price = parseFloat($('#input_price').val());
        var isValid = itemId && qty > 0 && !isNaN(price) && price >= 0;
        $('#btn_add_item').prop('disabled', !isValid);
        return isValid;
    }

    $('#input_item').on('change', function() {
        var data = $(this).select2('data')[0] || {};
        $('#input_unit').text(data.unit || '-');
        validateInputRow();
    });
    $('#input_qty, #input_price').on('input', validateInputRow);

    $('#btn_add_item').on('click', addItem);
    $('#input_qty, #input_price, #input_desc').on('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); if (!$('#btn_add_item').prop('disabled')) addItem(); }
    });

    function addItem() {
        if (!validateInputRow()) return;
        var selData = $('#input_item').select2('data')[0] || {};
        stagedItems.push({
            key: nextKey++,
            item_id: $('#input_item').val(),
            pr_detail_id: null,
            text: selData.text || '',
            unit: selData.unit || '',
            qty: parseFloat($('#input_qty').val()),
            base_price: parseFloat($('#input_price').val()),
            description: $('#input_desc').val() || ''
        });
        renderItemsTable();

        $('#input_item').val(null).trigger('change');
        $('#input_qty').val('');
        $('#input_price').val('');
        $('#input_desc').val('');
        $('#input_unit').text('-');
        setTimeout(function() { $('#input_item').select2('open'); }, 100);
    }

    $(document).on('click', '.btn-remove-staged-item', function() {
        var key = $(this).data('key');
        stagedItems = stagedItems.filter(function(it) { return it.key != key; });
        renderItemsTable();
    });

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    // ============ RINGKASAN NILAI (realtime) ============
    // Cerminan po_ppn_breakdown() di application/helpers/ppn_helper.php.
    function sumNum(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function sumFmt(n) {
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcTotals() {
        var subtotal = 0, pending = 0;
        stagedItems.forEach(function (it) {
            // Item tarikan PR belum berharga (base_price null) -- belum ikut dihitung.
            if (it.base_price === null || it.base_price === undefined || isNaN(parseFloat(it.base_price))) {
                pending++;
                return;
            }
            subtotal += sumNum(it.qty) * sumNum(it.base_price);
        });

        var hasPpn    = $('#add_has_ppn').is(':checked');
        var rate      = sumNum($('input[name="ppn_rate"]').val());
        var inclusive = String($('select[name="ppn_inclusive"]').val()) === '1';

        var dpp = subtotal, ppn = 0, total = subtotal;
        if (hasPpn && rate > 0) {
            if (inclusive) {
                total = subtotal;                       // harga sudah mengandung PPN
                ppn   = total * rate / (100 + rate);
                dpp   = total - ppn;
            } else {
                dpp   = subtotal;                       // PPN ditambahkan di atas subtotal
                ppn   = subtotal * rate / 100;
                total = subtotal + ppn;
            }
        }

        var curr = $('#add_currency_code').val() || '';
        var pre  = curr ? curr + ' ' : '';

        $('#sum_subtotal').text(pre + sumFmt(dpp));
        $('#sum_ppn').text(pre + sumFmt(ppn));
        $('#sum_total').text(pre + sumFmt(total));
        $('#sum_ppn_row').toggle(hasPpn && rate > 0);
        $('#sum_ppn_label').text('PPN ' + rate + '% (' + (inclusive ? 'Include' : 'Exclude') + ')');

        $('#sum_pending_row').toggle(pending > 0);
        if (pending > 0) {
            $('#sum_pending_text').text(pending + ' item dari PR belum ada harga, belum ikut dihitung.');
        }
    }

    $(document).on('change keyup', 'input[name="ppn_rate"]', recalcTotals);
    $(document).on('change', 'select[name="ppn_inclusive"], .ppn-toggle', recalcTotals);
    $('#add_currency_code').on('change', recalcTotals);
    recalcTotals(); // tampilkan 0.00 sejak halaman dibuka

    function renderItemsTable() {
        $('#items_table tbody tr.staged-item-row').remove();
        $('#empty_row').toggle(stagedItems.length === 0);

        stagedItems.forEach(function(item) {
            var isFromPr = !!item.pr_detail_id;
            var priceCell = isFromPr
                ? '<span class="text-muted font-italic small">' + '<?= lang_line("po_price_later", "diisi nanti"); ?>' + '</span>'
                : item.base_price.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            var nameCell = escapeHtml(item.text) + (isFromPr ? ' <span class="badge badge-info">PR</span>' : '');
            var row = '<tr class="staged-item-row">' +
                '<td>' + nameCell + '</td>' +
                '<td class="text-center">' + escapeHtml(item.unit) + '</td>' +
                '<td class="text-right">' + item.qty.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>' +
                '<td class="text-right">' + priceCell + '</td>' +
                '<td>' + escapeHtml(item.description) + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-danger btn-xs btn-remove-staged-item" data-key="' + item.key + '"><i class="fas fa-trash"></i></button></td>' +
                '</tr>';
            // Baris input manual jadi anchor kalau ada; kalau dimatikan
            // (item wajib dari PR), baris hasil tarik-PR di-append biasa.
            var $anchor = $('#input_row');
            if ($anchor.length) { $anchor.before(row); } else { $('#items_body').append(row); }
        });
        $('#item_count').text(stagedItems.length);
        recalcTotals();
    }

    // ============ TARIK DARI PR (staged) ============
    var pullPrSearchTimer = null;

    function pullPrEsc(s) { return $('<div>').text(s === null || s === undefined ? '' : s).html(); }
    function pullPrNum(v, dec) {
        var n = parseFloat(v) || 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function loadPullPrItems() {
        var $body = $('#pullPrBody');
        $body.html('<tr><td colspan="11" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat…</td></tr>');

        var url = PR_PULL_URL_BY_KATEGORI[currentKategori()] || PR_PULL_URL_BY_KATEGORI.general;
        $.getJSON(url, {
            search:    $('#pullPrSearch').val(),
            date_from: $('#pullPrFrom').val(),
            date_to:   $('#pullPrTo').val()
        }).done(function(res) {
            if (!res || !res.success) {
                $body.html('<tr><td colspan="11" class="text-center text-danger py-4">' + pullPrEsc((res && res.message) || 'Gagal memuat data.') + '</td></tr>');
                return;
            }
            renderPullPrItems(res.items || []);
        }).fail(function() {
            $body.html('<tr><td colspan="11" class="text-center text-danger py-4">Gagal menghubungi server.</td></tr>');
        });
    }

    function renderPullPrItems(items) {
        var $body = $('#pullPrBody');
        // Baris yang sudah di-stage sebelumnya jangan ditawarkan lagi.
        var stagedPrIds = stagedItems.filter(function(it) { return it.pr_detail_id; }).map(function(it) { return String(it.pr_detail_id); });
        items = items.filter(function(it) { return stagedPrIds.indexOf(String(it.pr_detail_id)) === -1; });

        if (!items.length) {
            $body.html('<tr><td colspan="11" class="text-center text-muted py-4">Tidak ada baris PR yang bisa ditarik (sudah di-stage semua, atau memang belum ada PR Approved dgn sisa qty utk kategori ini).</td></tr>');
            $('#pullPrCheckAll').prop('checked', false);
            updatePullPrState();
            return;
        }

        var html = '';
        var lastPr = null;
        var poSupplierId = parseInt($('select[name="supplier_id"]').val(), 10) || 0;

        items.forEach(function(it) {
            if (lastPr !== it.pr_number) {
                lastPr = it.pr_number;
                html += '<tr class="bg-light"><td colspan="11" class="py-1">' +
                        '<strong>' + pullPrEsc(it.pr_number) + '</strong>' +
                        '<span class="text-muted ml-2">' + pullPrEsc(it.pr_date || '') + '</span>' +
                        (it.department ? '<span class="text-muted ml-2">· ' + pullPrEsc(it.department) + '</span>' : '') +
                        (it.requester_name ? '<span class="text-muted ml-2">· ' + pullPrEsc(it.requester_name) + '</span>' : '') +
                        (it.purpose ? '<span class="text-muted ml-2">· ' + pullPrEsc(it.purpose) + '</span>' : '') +
                        '</td></tr>';
            }
            var remaining = parseFloat(it.remaining_qty) || 0;
            var prSupplierId = parseInt(it.pr_supplier_id, 10) || 0;
            var supplierClass = (!prSupplierId || !poSupplierId) ? 'text-muted' : (prSupplierId === poSupplierId ? 'text-success' : 'text-warning');

            html += '<tr data-supplier-id="' + prSupplierId + '" data-pr-detail-id="' + it.pr_detail_id + '" data-pr-number="' + pullPrEsc(it.pr_number) + '" ' +
                'data-item-code="' + pullPrEsc(it.item_code || '') + '" data-item-name="' + pullPrEsc(it.item_name || '') + '" data-unit="' + pullPrEsc(it.item_unit || '') + '">' +
                '<td class="text-center align-middle"><input type="checkbox" class="pull-pr-check" value="' + it.pr_detail_id + '"></td>' +
                '<td class="align-middle">' + pullPrEsc(it.pr_number) + '</td>' +
                '<td class="align-middle text-center">' + pullPrEsc(it.pr_date || '-') + '</td>' +
                '<td class="align-middle"><code>' + pullPrEsc(it.item_code || '-') + '</code></td>' +
                '<td class="align-middle">' + pullPrEsc(it.item_name || '-') + (it.description ? '<br><small class="text-muted">' + pullPrEsc(it.description) + '</small>' : '') + '</td>' +
                '<td class="align-middle text-center">' + pullPrEsc(it.item_unit || '-') + '</td>' +
                '<td class="align-middle text-right">' + pullPrNum(it.pr_qty, 2) + '</td>' +
                '<td class="align-middle text-right ' + (parseFloat(it.ordered_qty) > 0 ? 'text-warning font-weight-bold' : 'text-muted') + '">' + pullPrNum(it.ordered_qty, 2) + '</td>' +
                '<td class="align-middle text-right font-weight-bold">' + pullPrNum(remaining, 2) + '</td>' +
                '<td class="align-middle"><input type="number" class="form-control form-control-sm text-right pull-pr-qty" step="0.01" min="0.01" max="' + remaining + '" value="' + remaining + '" disabled></td>' +
                '<td class="align-middle small ' + supplierClass + '">' + pullPrEsc(it.pr_supplier_name || '-') + '</td>' +
                '</tr>';
        });

        $body.html(html);
        $('#pullPrCheckAll').prop('checked', false);
        updatePullPrState();
    }

    function updatePullPrState() {
        var $checked = $('#pullPrBody').find('.pull-pr-check:checked');
        $('#pullPrBody').find('.pull-pr-check').each(function() {
            $(this).closest('tr').find('.pull-pr-qty').prop('disabled', !this.checked);
        });
        $('#pullPrCount').text($checked.length);
        $('#pullPrSubmit').prop('disabled', $checked.length === 0);

        var poSupplierId = parseInt($('select[name="supplier_id"]').val(), 10) || 0;
        var mismatch = [];
        $checked.each(function() {
            var sid = parseInt($(this).closest('tr').data('supplier-id'), 10) || 0;
            if (sid && poSupplierId && sid !== poSupplierId) {
                var name = $.trim($(this).closest('tr').find('td').last().text());
                if (mismatch.indexOf(name) === -1) mismatch.push(name);
            }
        });
        if (mismatch.length) {
            $('#pullPrSupplierWarn').show().html('<i class="fas fa-exclamation-triangle mr-1"></i> Ada baris yang usulan supplier-nya berbeda dari Supplier yang dipilih di atas: <strong>' + pullPrEsc(mismatch.join(', ')) + '</strong>. Boleh dilanjutkan — supplier final ditentukan di header PO.');
        } else {
            $('#pullPrSupplierWarn').hide().empty();
        }
    }

    $('#pullPrModal').on('shown.bs.modal', loadPullPrItems);
    $('#pullPrRefresh').on('click', loadPullPrItems);
    $('#pullPrFrom, #pullPrTo').on('change', loadPullPrItems);
    $('#pullPrSearch').on('input', function() {
        clearTimeout(pullPrSearchTimer);
        pullPrSearchTimer = setTimeout(loadPullPrItems, 350);
    });
    $('#pullPrCheckAll').on('change', function() {
        $('#pullPrBody').find('.pull-pr-check').prop('checked', this.checked);
        updatePullPrState();
    });
    $('#pullPrBody').on('change', '.pull-pr-check', updatePullPrState);
    $('#pullPrBody').on('change', '.pull-pr-qty', function() {
        var max = parseFloat($(this).attr('max')) || 0;
        var val = parseFloat(this.value) || 0;
        if (val > max) this.value = max;
        if (val <= 0) this.value = max;
    });

    $('#pullPrSubmit').on('click', function() {
        var added = 0;
        $('#pullPrBody').find('.pull-pr-check:checked').each(function() {
            var $row = $(this).closest('tr');
            var qty = parseFloat($row.find('.pull-pr-qty').val()) || 0;
            if (qty <= 0) return;
            stagedItems.push({
                key: nextKey++,
                item_id: null,
                pr_detail_id: $row.data('pr-detail-id'),
                text: '[' + $row.data('pr-number') + '] ' + $row.data('item-code') + ' - ' + $row.data('item-name'),
                unit: $row.data('unit'),
                qty: qty,
                base_price: null,
                description: ''
            });
            added++;
        });
        renderItemsTable();
        $('#pullPrModal').modal('hide');
        if (added > 0) {
            Swal.fire({ icon: 'success', title: added + ' item dari PR ditambahkan', toast: true, position: 'top-end', timer: 1800, showConfirmButton: false });
        }
    });

    let anAddExchangeRate;
    const $exchangeRateInput = $('#add_exchange_rate_div input.autonumeric-4dec');

    if ($exchangeRateInput.length && typeof AutoNumeric === 'function') {
       anAddExchangeRate = new AutoNumeric($exchangeRateInput[0], { decimalPlaces: 4, modifyValueOnWheel: false });
    }

    function fetchAddKursFromPajak(currency) {
        const $info = $('#add_kurs_info');
        $info.removeClass('text-success text-danger').addClass('text-muted')
             .html('<i class="fas fa-spinner fa-spin"></i> Mengambil kurs pajak terbaru...');
        $.getJSON('<?= base_url('kurs_pajak/ajax_get_rate'); ?>', { currency: currency })
            .done(function(res) {
                if (res.ok && res.rate) {
                    if (anAddExchangeRate) { anAddExchangeRate.set(res.rate); }
                    else { $exchangeRateInput.val(res.rate); }
                    $info.removeClass('text-muted text-danger').addClass('text-success')
                         .html('<i class="fas fa-check-circle"></i> Auto dari Kurs Pajak ('
                              + res.effective_date_start + ' s/d ' + res.effective_date_end
                              + '). Boleh diubah manual bila perlu.');
                } else {
                    $info.removeClass('text-muted text-success').addClass('text-danger')
                         .html('<i class="fas fa-exclamation-triangle"></i> ' + (res.message || 'Kurs tidak tersedia, isi manual.'));
                }
            })
            .fail(function() {
                $info.removeClass('text-muted text-success').addClass('text-danger')
                     .html('<i class="fas fa-exclamation-triangle"></i> Gagal mengambil kurs, isi manual.');
            });
    }

    function toggleAddKurs() {
        const $rateDiv = $('#add_exchange_rate_div');
        const curr = $('#add_currency_code').val();

        if (curr !== 'IDR') {
            $rateDiv.slideDown();
            $exchangeRateInput.prop('required', true);
            fetchAddKursFromPajak(curr);
        } else {
            $rateDiv.slideUp();
            $exchangeRateInput.prop('required', false);
            if (anAddExchangeRate) { anAddExchangeRate.set(1); }
            else { $exchangeRateInput.val('1.0000'); }
        }
    }

    toggleAddKurs();
    $('#add_currency_code').on('change', toggleAddKurs);

    $('#gmpoAddForm').on('submit', function(event) {
        if (stagedItems.length === 0) {
            event.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Belum ada item', text: 'Tambahkan minimal 1 item sebelum menyimpan PO.' });
            return false;
        }
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
            $(this).addClass('was-validated');
            return false;
        }
        // Pisah 2 jalur simpan di backend: item manual -> Po_model::add_item()
        // (loop di store()), item tarikan PR -> Po_model::pull_pr_items()
        // (fungsi lama yg sudah teruji, klem sisa qty + isi pr_detail_id).
        var manualItems = stagedItems.filter(function(it) { return !it.pr_detail_id; })
            .map(function(it) { return { item_id: it.item_id, qty: it.qty, base_price: it.base_price, description: it.description }; });
        var prRows = stagedItems.filter(function(it) { return it.pr_detail_id; })
            .map(function(it) { return { pr_detail_id: it.pr_detail_id, qty: it.qty }; });

        $('#items_payload').val(JSON.stringify(manualItems));
        $('#pulled_pr_payload').val(JSON.stringify(prRows));
        $('#btnSubmitPo').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
    });
});
</script>

<style>
.select2-container--default .select2-selection--single { height: calc(1.5em + .75rem + 2px); padding: .375rem .75rem; border: 1px solid #d1d3e2; }
.btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>

<script>
/* Bagian rincian PPN (include/exclude + persen) hanya terbuka kalau "Kena PPN"
   dicentang -- alur yang diminta klien 2026-10-02: kena pajak dulu, baru
   include/exclude, baru persennya. */
$(function () {
    $(document).on('change', '.ppn-toggle', function () {
        $(this).closest('.border').find('.ppn-detail').toggle($(this).is(':checked'));
    });
});
</script>
