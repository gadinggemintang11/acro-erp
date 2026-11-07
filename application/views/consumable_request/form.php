<div class="container-fluid">
    <h3 class="text-gray-800 mb-4"><i class="fas fa-plus-circle text-primary mr-2"></i><?= $title ?></h3>

    <form method="POST" action="<?= base_url('consumable_request/store') ?>" id="form_request">
        <div class="row">
            <div class="col-lg-8">
                <!-- Header Info -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light"><strong><?= lang_line('cr_info_request', 'Informasi Request'); ?></strong></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_no_request', 'No Request'); ?></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($request_number) ?>" disabled>
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_request_date', 'Tanggal Request'); ?> <span class="text-danger">*</span></label>
                                <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_department', 'Departemen'); ?></label>
                                <?php
                                  // Departemen dokumen = departemen yang boleh dibuatkan IRF oleh
                                  // role user (Role Level tahap 3). Lebih dari satu -> dipilih.
                                  $dept_options = $dept_options ?? [];
                                ?>
                                <?php if (count($dept_options) > 1): ?>
                                    <select name="department" class="form-control" required>
                                        <?php foreach ($dept_options as $code => $name): ?>
                                            <option value="<?= htmlspecialchars($code); ?>" <?= $code === ($dept_default ?? null) ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Pilih departemen pemilik Permintaan Barang — menentukan siapa yang submit & approve.</small>
                                <?php elseif (count($dept_options) === 1): ?>
                                    <input type="hidden" name="department" value="<?= htmlspecialchars(array_key_first($dept_options)); ?>">
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(reset($dept_options)); ?>" readonly>
                                <?php else: ?>
                                    <input type="text" class="form-control bg-light is-invalid" value="" readonly>
                                    <div class="invalid-feedback d-block">Role Anda belum punya departemen untuk membuat Permintaan Barang. Hubungi Admin.</div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_requestor_name', 'Nama Requestor'); ?></label>
                                <input type="text" name="requestor_name" class="form-control bg-light"
                                       value="<?= htmlspecialchars($current_user->fullname ?? ''); ?>"
                                       readonly title="<?= lang_line('cr_auto_from_profile', 'Otomatis dari profil user yang login'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><?= lang_line('cr_category', 'Kategori Kebutuhan'); ?> <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted"><?= lang_line('cr_category_hint', 'Menentukan barang mana yang muncul di pencarian item: General hanya memunculkan barang berjenis pemakaian General, Produksi hanya yang Produksi.'); ?></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('cr_purpose', 'Keperluan'); ?> <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="2" required placeholder="<?= lang_line('cr_purpose_ph', 'Tujuan pengambilan barang'); ?>"></textarea>
                        </div>
                        <div class="form-group">
                            <label><?= lang_line('cr_notes', 'Catatan'); ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?= lang_line('cr_notes_ph', 'Catatan tambahan (opsional)'); ?>"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <strong><?= lang_line('cr_item_section', 'Item Barang'); ?></strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="items_table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 32%;"><?= lang_line('cr_th_item', 'Barang'); ?></th>
                                        <th style="width: 10%;"><?= lang_line('cr_th_unit', 'Satuan'); ?></th>
                                        <th style="width: 15%;"><?= lang_line('cr_th_qty', 'Qty'); ?></th>
                                        <th style="width: 14%;" class="text-right" title="Otomatis dari PO terakhir barang ini">Harga (PO terakhir)</th>
                                        <th style="width: 14%;" class="text-right">Nilai</th>
                                        <th style="width: %;"></th><?php /* Kolom stock sengaja TANPA judul — labelnya menyatu di dalam sel ("Stock = 12.00"), user 2026-09-09 */ ?>
                                        <th style="width: 10%;"><?= lang_line('cr_th_action', 'Aksi'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="items_body">
                                    <tr class="text-center text-muted py-3">
                                        <td colspan="7"><?= lang_line('cr_no_item', 'Belum ada item. Klik tombol "Tambah Item" di atas'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-right">
                        <button type="button" class="btn btn-sm btn-primary" id="btn_add_item">
                            <i class="fas fa-plus"></i> <?= lang_line('cr_add_item', 'Tambah Item'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light"><strong><?= lang_line('cr_action_section', 'Aksi'); ?></strong></div>
                    <!-- Perkiraan nilai & jalur approval (user 2026-09-15). Final dihitung ulang saat submit. -->
                    <div class="card-body border-bottom small" id="irf_value_box">
                        <div class="d-flex justify-content-between"><span class="text-muted">Perkiraan nilai</span><strong id="irf_total_value">Rp 0</strong></div>
                        <div class="mt-2" id="irf_route_badge"><span class="badge badge-light border">Pilih barang dulu</span></div>
                        <div class="text-muted mt-1" id="irf_route_reason"></div>
                        <div class="text-muted mt-2" style="font-size:.75rem">Harga otomatis dari PO terakhir (disetujui). Harga & jalur approval dihitung ulang saat submit.</div>
                    </div>
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-save mr-1"></i> <?= lang_line('cr_btn_save', 'Simpan'); ?>
                        </button>
                        <a href="<?= base_url('consumable_request') ?>" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('cr_btn_back', 'Kembali'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
    // Modal "Tambah Item Baru" (user 2026-09-09): item yang belum ada di
    // master sering baru ketahuan setelah beberapa baris IRF terisi. Kalau
    // requestor pergi ke menu Master dulu, semua isian form ini hilang.
    // Jadi master-nya ditambah DI SINI (AJAX ke
    // Consumable_request::ajax_quick_add_item()), item baru langsung
    // disuntik ke dropdown, dan isian yang sudah ada TIDAK disentuh.
    // $quick_add dikirim dari controller: opsi kategori/satuan per jenis +
    // flag izin per master.
    $qa = $quick_add ?? [];
    $qa_any = !empty($qa['inventory']['can']) || !empty($qa['machine']['can'])
           || !empty($qa['asset']['can'])     || !empty($qa['jasa']['can']);
?>
<?php if ($qa_any): // tidak dirender sama sekali kalau user tidak berhak nambah master mana pun ?>
<div class="modal fade" id="quickAddItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i><?= lang_line('cr_quick_add_title', 'Tambah Item Baru ke Master'); ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('cr_quick_add_help', 'Item disimpan ke master sesuai jenis yang dipilih, lalu langsung terpakai di baris ini. Isian Permintaan Barang yang sudah Anda ketik tidak akan hilang.'); ?>
                </div>
                <div id="quick_add_alert" class="alert alert-danger py-2 small d-none"></div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_type', 'Jenis Item'); ?> <span class="text-danger">*</span></label>
                    <select id="qa_item_type" class="form-control">
                        <?php if (!empty($qa['inventory']['can'])): ?><option value="inventory"><?= lang_line('cr_group_inventory', 'General'); ?> — Master Barang</option><?php endif; ?>
                        <?php if (!empty($qa['machine']['can'])): ?><option value="machine"><?= lang_line('cr_group_machine', 'Mesin / Sparepart'); ?> — Master Mesin & Peralatan</option><?php endif; ?>
                        <?php if (!empty($qa['asset']['can'])): ?><option value="asset"><?= lang_line('cr_group_asset', 'Asset'); ?> — Master Asset</option><?php endif; ?>
                        <?php if (!empty($qa['jasa']['can'])): ?><option value="jasa"><?= lang_line('cr_group_jasa', 'Jasa'); ?> — Master Jasa</option><?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_name', 'Nama Item'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="qa_nama" class="form-control" maxlength="150" autocomplete="off">
                </div>

                <div class="form-group" id="qa_inv_type_wrap">
                    <label><?= lang_line('cr_quick_add_item_type', 'Jenis (Stock / Non-Stock)'); ?></label>
                    <select id="qa_inv_item_type" class="form-control">
                        <?php foreach (($qa['inventory']['item_type'] ?? []) as $it): ?>
                            <option value="<?= htmlspecialchars($it->code); ?>"><?= htmlspecialchars($it->description); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><?= lang_line('cr_quick_add_item_type_help', 'Menentukan kode MAIN (STK/ITM) pada kode barang baru.'); ?></small>
                </div>

                <div class="form-group" id="qa_usage_wrap">
                    <label><?= lang_line('inventory_usage_category', 'Jenis Pemakaian'); ?> <span class="text-danger">*</span></label>
                    <select id="qa_usage_category" class="form-control">
                        <option value="General">General</option>
                        <option value="Produksi">Produksi</option>
                    </select>
                    <small class="form-text text-muted" id="qa_usage_help"></small>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_category', 'Kategori'); ?> <span class="text-danger qa-req-kategori">*</span></label>
                    <select id="qa_kategori" class="form-control"></select>
                </div>

                <div class="form-group">
                    <label><?= lang_line('cr_quick_add_unit', 'Satuan'); ?></label>
                    <select id="qa_satuan" class="form-control">
                        <option value="">-- <?= lang_line('cr_quick_add_unit_default', 'Pakai default master'); ?> --</option>
                        <?php foreach (($qa['satuan'] ?? []) as $sat): ?>
                            <option value="<?= htmlspecialchars($sat->code); ?>"><?= htmlspecialchars($sat->description); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-0 d-none" id="qa_kode_wrap">
                    <label><?= lang_line('cr_quick_add_code', 'Kode'); ?> <span class="text-danger qa-req-kode d-none">*</span></label>
                    <input type="text" id="qa_kode" class="form-control" maxlength="50" autocomplete="off">
                    <small class="form-text text-muted" id="qa_kode_help"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('cr_btn_cancel', 'Batal'); ?></button>
                <button type="button" class="btn btn-primary" id="qa_submit">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('cr_quick_add_save', 'Simpan & Pakai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Dibungkus $(document).ready() — user 2026-09-04 lapor picker item di form
// ini "cuma dropdown, tidak bisa search" padahal kode-nya SUDAH manggil
// select2(). Root cause: script ini dulu jalan LANGSUNG begitu di-parse
// (tidak nunggu apa2), padahal library select2.min.js baru di-load belakangan
// di templates/footer.php (SETELAH konten form ini dlm urutan render
// controller: header,sidebar,topbar,form,footer) — jadi .select2() dipanggil
// SEBELUM fungsi itu ada, gagal diam2 (browser console error), <select> jatuh
// balik jadi dropdown polos HTML tanpa search. $(document).ready() menunda
// SEMUA eksekusi sampai seluruh dokumen (termasuk script select2 di footer)
// selesai di-parse & jalan duluan.
$(document).ready(function() {
const baseUrl = '<?= base_url(); ?>';
// Mutable: General & Produksi sekarang punya saldo stock beda (gudang fisik
// terpisah) — di-refresh via AJAX tiap kali dropdown Kategori diganti.
let items_list = <?= json_encode($items_list) ?>;
let item_row_counter = 0;

// Opsi + izin modal "Tambah Item Baru" (dikirim controller). Jenis item yang
// user-nya tidak punya izin 'add' di master-nya tidak muncul sama sekali.
const QUICK_ADD = <?= json_encode([
    'inventory' => ['can' => !empty($qa['inventory']['can']), 'kategori' => $qa['inventory']['kategori'] ?? []],
    'machine'   => ['can' => !empty($qa['machine']['can']),   'kategori' => $qa['machine']['kategori'] ?? []],
    'asset'     => ['can' => !empty($qa['asset']['can']),     'kategori' => $qa['asset']['kategori'] ?? []],
    'jasa'      => ['can' => !empty($qa['jasa']['can']),      'kategori' => $qa['jasa']['kategori'] ?? []],
]) ?>;
const QUICK_ADD_ENABLED = <?= $qa_any ? 'true' : 'false' ?>;
let $qaTargetSelect = null;
// Kata kunci terakhir yang diketik di kotak cari Select2 — dipakai sebagai
// nama awal di modal Tambah Item Baru (kotak cari bisa keburu hilang saat
// dropdown ditutup).
let qaLastTerm = '';
$(document).on('input', '.select2-search__field', function() { qaLastTerm = this.value; });

// Item bisa berasal dari 2 master (inventories & machines) yang id-nya bisa
// bentrok, jadi value <option> memakai item_key gabungan "type-id".
const ITEM_GROUPS = [
    { type: 'inventory', label: <?= json_encode(lang_line('cr_group_inventory', 'General')); ?> },
    { type: 'machine',   label: <?= json_encode(lang_line('cr_group_machine', 'Mesin / Sparepart')); ?> },
    { type: 'asset',     label: <?= json_encode(lang_line('cr_group_asset', 'Asset')); ?> },
    { type: 'jasa',      label: <?= json_encode(lang_line('cr_group_jasa', 'Jasa')); ?> },
];

function escHtmlItem(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function buildItemOptions() {
    return ITEM_GROUPS.map(g => {
        const rows = items_list.filter(i => i.item_type === g.type);
        if (!rows.length) return '';
        return '<optgroup label="' + escHtmlItem(g.label) + '">'
            + rows.map(i => `<option value="${i.item_key}">${escHtmlItem(i.kode_barang)} - ${escHtmlItem(i.nama_barang)}</option>`).join('')
            + '</optgroup>';
    }).join('');
}

function findItemByKey(key) {
    return items_list.find(i => i.item_key === key);
}

// Perkiraan nilai & jalur approval (user 2026-09-15). Aturan sama dengan
// Consumable_request_model::refresh_value_and_route(): ada barang belum
// pernah di-PO atau total >= 1 jt -> 3 tahap; selain itu 1 tahap (HOD).
function formatRp(v) { return 'Rp ' + Math.round(parseFloat(v) || 0).toLocaleString('id-ID'); }
function recalcIrfValue() {
    let total = 0, rows = 0, missing = [];
    $('#items_body tr[id^="item_"]').each(function () {
        const $r = $(this);
        if (!$r.data('has-item')) { $r.find('.item-value').html('<span class="text-muted">-</span>'); return; }
        rows++;
        const qty = parseFloat($r.find('.item-qty').val()) || 0;
        const price = $r.data('price');
        if (price === null || price === undefined) {
            missing.push(($r.find('.item-select option:selected').text() || '').split(' - ').slice(1).join(' - '));
            $r.find('.item-value').html('<span class="text-muted">-</span>');
        } else {
            total += qty * price;
            $r.find('.item-value').text(formatRp(qty * price));
        }
    });
    $('#irf_total_value').text(formatRp(total) + (missing.length ? ' +' : ''));
    // Approval Permintaan Barang selalu 1 tahap: Head of Dept / atasan (2026-11-05).
    if (!rows) {
        $('#irf_route_badge').html('<span class="badge badge-light border">Pilih barang dulu</span>'); $('#irf_route_reason').text(''); return;
    }
    $('#irf_route_badge').html('<span class="badge badge-success">Approval 1 tahap</span>');
    $('#irf_route_reason').text('Disetujui Head of Dept / atasan' + (missing.length ? ' — nilai belum lengkap (ada barang belum pernah di-PO)' : ''));
}

// Kolom Catatan per-item disembunyikan dari form input (ERP Revision Notes
// 2026-09-02) — datanya (consumable_request_details.notes) TETAP ada di DB
// (model sudah pakai `$item['notes'] ?? null`, aman tanpa input ini), cuma
// tidak dikoleksi lagi lewat form. Ditarik jadi fungsi (bukan cuma di dalam
// click handler) supaya bisa dipanggil otomatis: 1 baris kosong langsung
// tersedia begitu halaman dibuka, user tidak perlu klik "Tambah Item" dulu.
// Konfigurasi Select2 picker item — ditarik jadi fungsi karena dipakai ulang
// tiap kali daftar item di-refresh (tambah item baru / ganti kategori), yang
// mengharuskan select2 di-destroy lalu di-init lagi.
//
// Saat pencarian tidak ketemu, "No results found" diganti tombol + yang
// membuka modal Tambah Item Baru (user 2026-09-09). escapeMarkup dimatikan
// supaya tombol itu ter-render sebagai HTML — KOMPENSASINYA templateResult/
// templateSelection mengembalikan node jQuery ber-.text(), jadi nama item dari
// DB tetap di-escape (tidak jadi lubang XSS).
function initItemSelect2($select) {
    $select.select2({
        placeholder: '<?= lang_line('cr_select_item_ph', '-- Pilih Barang --'); ?>',
        allowClear: true,
        width: '100%',
        dropdownParent: $select.parent(),
        escapeMarkup: function(markup) { return markup; },
        templateResult: function(data) {
            if (data.loading) return data.text;
            return $('<span></span>').text(data.text);
        },
        templateSelection: function(data) {
            return $('<span></span>').text(data.text || '');
        },
        language: {
            // Select2 menaruh hasil fungsi ini lewat escapeMarkup (yang di atas
            // sudah dimatikan), jadi WAJIB balikin STRING HTML — objek jQuery
            // akan ter-render jadi "[object Object]". Teks dari lang_line
            // di-escape di sisi PHP karena masuk ke HTML mentah.
            noResults: function() {
                if (!QUICK_ADD_ENABLED) {
                    return '<span class="text-muted">'
                         + <?= json_encode(htmlspecialchars(lang_line('cr_no_item_found', 'Item tidak ditemukan'), ENT_QUOTES)); ?>
                         + '</span>';
                }
                return '<button type="button" class="btn btn-sm btn-primary btn-quick-add-item">'
                     + '<i class="fas fa-plus mr-1"></i>'
                     + <?= json_encode(htmlspecialchars(lang_line('cr_quick_add_btn', 'Tambah item baru ke master'), ENT_QUOTES)); ?>
                     + '</button>';
            }
        }
    });

    // Dicatat supaya tombol + di dalam dropdown tahu baris mana yang harus
    // diisi item barunya. Pakai namespace + .off() karena fungsi ini dipanggil
    // ulang tiap refresh daftar item (select2('destroy') TIDAK melepas handler
    // jQuery custom di elemen yang sama -> tanpa ini handler-nya menumpuk).
    $select.off('select2:open.qa').on('select2:open.qa', function() { $qaTargetSelect = $(this); });
}

// Ganti isi <option> SEMUA baris dgn items_list terbaru TANPA menghilangkan
// pilihan yang sudah dibuat user (inti permintaan "jangan menghilangkan item
// yang sudah di input"). select2 wajib di-destroy dulu; handler change milik
// tiap baris tetap menempel di elemen <select>-nya, jadi tidak perlu dipasang
// ulang.
function refreshAllItemOptions() {
    $('#items_body .item-select').each(function() {
        const $s = $(this);
        const current = $s.val();
        if ($s.data('select2')) { $s.select2('destroy'); }
        $s.html('<option value=""></option>' + buildItemOptions());
        if (current) { $s.val(current); }
        initItemSelect2($s);
    });
}

function addItemRow() {
    const $tbody = $('#items_body');
    $tbody.find('tr.text-center.text-muted').remove();

    const row_id = 'item_' + (++item_row_counter);
    const optionsHtml = '<option value=""></option>' + buildItemOptions();

    const $row = $(`
        <tr id="${row_id}">
            <td>
                <select name="items[${item_row_counter}][item_id]" class="form-control form-control-sm item-select" data-row="${row_id}" style="width:100%;">
                    ${optionsHtml}
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm item-unit" readonly></td>
            <td>
                <input type="number" name="items[${item_row_counter}][qty]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" required>
                <small class="text-info item-stock-warning d-none mt-1">
                    <i class="fas fa-info-circle"></i> <?= lang_line('cr_stock_warning', 'Qty melebihi stock — kelebihannya akan jadi <strong>outstanding</strong> (perlu PR).'); ?>
                </small>
            </td>
            <td class="text-right align-middle item-price"><span class="text-muted">-</span></td>
            <td class="text-right align-middle item-value"><span class="text-muted">-</span></td>
            <td class="align-middle">
                <small class="text-muted item-stock-box d-none">
                    <?= lang_line('cr_th_stock', 'Stock'); ?> = <span class="item-stock font-weight-bold"></span>
                </small>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger btn-remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `);
    $tbody.append($row);

    const $select = $row.find('.item-select');
    const $unit   = $row.find('.item-unit');
    const $stock  = $row.find('.item-stock');
    const $stockBox = $row.find('.item-stock-box');
    const $qty    = $row.find('.item-qty');
    const $warn   = $row.find('.item-stock-warning');

    initItemSelect2($select);

    $select.on('change', function() {
        // Cegah barang yang sama dipilih di 2 baris (user 2026-09-10: "input
        // Permintaan Barang 2 item ... hasil PR nya tetap 1 barang"). Semua modul di hilir
        // MENJUMLAHKAN baris dengan item yang sama jadi satu (PR, halaman
        // Outstanding), jadi 2 baris item kembar itu memang berakhir sebagai
        // 1 baris — dan itu yang bikin bingung. Ditolak di sini, saat masih
        // gampang diperbaiki. Server juga memeriksa ulang hal yang sama
        // (Consumable_request::_duplicate_item_error()), karena JS bisa
        // dilewati.
        if (this.value) {
            let dupe = null;
            $('#items_body .item-select').not(this).each(function () {
                if ($(this).val() === $select.val()) { dupe = this; return false; }
            });
            if (dupe) {
                const nama = (findItemByKey(this.value) || {}).nama_barang || 'Barang ini';
                if (typeof toastr !== 'undefined') {
                    toastr.warning(nama + ' sudah ada di baris lain. Tambahkan qty-nya di baris itu saja.');
                } else {
                    alert(nama + ' sudah ada di baris lain. Tambahkan qty-nya di baris itu saja.');
                }
                $select.val(null).trigger('change.select2');
                $unit.val(''); $stockBox.addClass('d-none');
                $qty.removeAttr('data-stock'); $warn.addClass('d-none');
                return;
            }
        }

        const item = this.value ? findItemByKey(this.value) : null;
        $row.data('price', item && item.last_price ? parseFloat(item.last_price) : null);
        $row.data('has-item', !!item);
        $row.find('.item-price').html(!item ? '<span class="text-muted">-</span>'
            : (item.last_price ? formatRp(item.last_price) + '<div class="text-muted" style="font-size:.7rem">' + escHtmlItem(item.last_po_number || '') + '</div>'
                               : '<span class="badge badge-warning">Belum pernah di-PO</span>'));
        recalcIrfValue();
        if (item) {
            $unit.val(item ? item.satuan : '');
            $stock.text(item ? parseFloat(item.stock).toFixed(2) : '0');
            $stockBox.removeClass('d-none');
            $qty.attr('data-stock', item ? item.stock : 0);
            checkStockWarning();
        } else {
            $unit.val('');
            $stock.text('');
            $stockBox.addClass('d-none');
            $qty.removeAttr('data-stock');
            $warn.addClass('d-none');
        }
    });

    $qty.on('input', function () { checkStockWarning(); recalcIrfValue(); });

    function checkStockWarning() {
        const stock = parseFloat($qty.attr('data-stock') || 0);
        const qty = parseFloat($qty.val() || 0);
        if (qty > stock && stock > 0) {
            $warn.removeClass('d-none');
            $qty.css('border-color', '#17a2b8');
        } else {
            $warn.addClass('d-none');
            $qty.css('border-color', '');
        }
    }

    $row.find('.btn-remove-item').on('click', function() {
        $select.select2('destroy');
        $row.remove();
        if ($('#items_body').children().length === 0) {
            $('#items_body').html('<tr class="text-center text-muted py-3"><td colspan="7"><?= lang_line('cr_no_item', 'Belum ada item. Klik tombol "Tambah Item" di atas'); ?></td></tr>');
        }
        recalcIrfValue();
    });
}

$('#btn_add_item').on('click', addItemRow);

// Langsung sediakan 1 baris kosong begitu halaman dibuka — user tidak perlu
// klik "Tambah Item" dulu buat mulai input.
addItemRow();

// Stock General & Produksi adalah 2 angka terpisah (gudang fisik beda), jadi
// ganti kategori WAJIB refresh daftar item+stock.
//
// Baris yang sudah diisi TIDAK dihapus borongan (user 2026-09-09: "jangan
// menghilangkan item yang sudah di input").
//
// CATATAN 2026-10-06: dulu ini aman tanpa syarat karena daftar ITEM-nya sama
// persis untuk semua kategori — yang berbeda hanya angka stock-nya. Sejak
// barang punya Jenis Pemakaian (General/Produksi), daftarnya IKUT berubah,
// jadi item yang sudah dipilih bisa tidak ada lagi di kategori yang baru.
// Kalau dibiarkan, refreshAllItemOptions() meng-set ulang value yang
// option-nya sudah tidak ada -> baris jadi kosong diam-diam padahal qty-nya
// masih terisi. Makanya baris semacam itu dideteksi & diberitahukan.
$('select[name="category"]').on('change', function() {
    const category = this.value;

    $.get(baseUrl + 'consumable_request/ajax_items_by_category', { category: category })
        .done(function(res) {
            if (res && res.success) {
                items_list = res.items;
            }
            if ($('#items_body .item-select').length === 0) {
                addItemRow();
                return;
            }

            // Item yang tidak tersedia lagi di kategori ini: dikosongkan,
            // namanya dikumpulkan dulu supaya bisa disebut ke user.
            const tersedia = {};
            items_list.forEach(function(i) { tersedia[i.item_key] = true; });
            const dibuang = [];
            $('#items_body .item-select').each(function() {
                const val = $(this).val();
                if (val && !tersedia[val]) {
                    dibuang.push($.trim($(this).find('option:selected').text()) || val);
                    $(this).val('');
                }
            });

            refreshAllItemOptions();
            // Picu handler change tiap baris supaya kolom Satuan/Stock &
            // peringatan qty ikut dihitung ulang dgn stock kategori baru.
            $('#items_body .item-select').trigger('change');

            if (dibuang.length) {
                const pesan = <?= json_encode(lang_line('cr_items_dropped_on_category_change', 'Item berikut tidak tersedia untuk kategori ini, jadi baris-nya dikosongkan:')); ?>
                    + '\n\n• ' + dibuang.join('\n• ');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: <?= json_encode(lang_line('cr_items_dropped_title', 'Sebagian item dikosongkan')); ?>,
                                text: pesan, confirmButtonText: 'OK' });
                } else {
                    alert(pesan);
                }
            }
        });
});

// ============ QUICK ADD ITEM KE MASTER ============
// Tombol + muncul di dalam dropdown saat pencarian item tidak ketemu.
$(document).on('click', '.btn-quick-add-item', function(e) {
    e.preventDefault();
    e.stopPropagation();

    // Kata kunci yg barusan diketik dipakai sbg nama awal — user tidak perlu
    // mengetik ulang.
    const term = $('.select2-container--open .select2-search__field').val() || qaLastTerm || '';
    if ($qaTargetSelect && $qaTargetSelect.data('select2')) { $qaTargetSelect.select2('close'); }

    $('#quick_add_alert').addClass('d-none').text('');
    $('#qa_nama').val(term);
    $('#qa_kode').val('');
    $('#qa_satuan').val('');
    // Nilai awal mengikuti Kategori Kebutuhan Permintaan Barang -- diset tiap kali modal
    // dibuka, bukan sekali saat halaman dimuat, karena kategorinya bisa
    // diganti user di tengah jalan.
    $('#qa_usage_category').val(qaIrfCategory());
    qaApplyType();
    $('#quickAddItemModal').modal('show');
    setTimeout(function() { $('#qa_nama').trigger('focus'); }, 300);
});

// Jenis item menentukan master tujuan -> daftar kategori & aturan kode ikut
// berubah (Master Mesin tidak punya kode otomatis, jadi kode wajib diisi).
// Kategori Permintaan Barang yang sedang dibuka -- jadi nilai awal "Jenis Pemakaian" di
// modal tambah item, dan jadi pembanding apakah item barunya boleh langsung
// dipakai di Permintaan Barang ini.
function qaIrfCategory() {
    return $('select[name="category"]').val() || 'General';
}

// Peringatan kalau user sengaja memilih jenis pemakaian yang berbeda dari
// kategori Permintaan Barang ini: item-nya tetap tersimpan ke master, tapi tidak akan
// muncul di pencarian item Permintaan Barang ini (persis aturan yang disaring di server).
// Server cuma menyaring daftar item utk kategori General & Produksi; 'Jasa'
// tidak punya padanan jenis pemakaian jadi daftarnya ditampilkan utuh. Aturan
// ini dipakai bersama oleh peringatan di modal DAN keputusan apakah item baru
// boleh langsung dipasang -- supaya keduanya tidak bisa menyimpang dari server.
function qaIrfDisaring() {
    const irf = qaIrfCategory();
    return irf === 'General' || irf === 'Produksi';
}

function qaUsageHint() {
    const $help = $('#qa_usage_help');
    if ($('#qa_item_type').val() !== 'inventory') { $help.text(''); return; }
    const dipilih = $('#qa_usage_category').val();
    const irf     = qaIrfCategory();
    if (!qaIrfDisaring() || dipilih === irf) {
        $help.removeClass('text-warning').addClass('text-muted')
             .text(<?= json_encode(lang_line('cr_quick_add_usage_help', 'Menentukan segmen GNR/PRD pada kode barang baru. Mengikuti Kategori Kebutuhan Permintaan Barang ini.')); ?>);
    } else {
        $help.removeClass('text-muted').addClass('text-warning')
             .text(<?= json_encode(lang_line('cr_quick_add_usage_warn', 'Berbeda dengan Kategori Kebutuhan Permintaan Barang ini — item tetap tersimpan ke master, tapi TIDAK akan muncul di pencarian item Permintaan Barang ini.')); ?>);
    }
}
$(document).on('change', '#qa_usage_category', qaUsageHint);

function qaApplyType() {
    const type = $('#qa_item_type').val();
    const conf = QUICK_ADD[type] || { kategori: [] };

    const opts = (type === 'inventory' || type === 'machine')
        ? '<option value="">-- <?= lang_line('cr_quick_add_pick_category', 'Pilih Kategori'); ?> --</option>'
        : '<option value="">-- <?= lang_line('cr_quick_add_category_default', 'Default master'); ?> --</option>';
    $('#qa_kategori').html(opts + (conf.kategori || []).map(function(k) {
        return $('<option></option>').attr('value', k.code).text(k.description).prop('outerHTML');
    }).join(''));

    $('#qa_inv_type_wrap').toggleClass('d-none', type !== 'inventory');
    $('#qa_usage_wrap').toggleClass('d-none', type !== 'inventory');
    qaUsageHint();
    $('.qa-req-kategori').toggleClass('d-none', !(type === 'inventory' || type === 'machine'));
    $('.qa-req-kode').toggleClass('d-none', type !== 'machine');

    // Kolom Kode cuma relevan utk Mesin/Sparepart -- itu SATU-SATUNYA master
    // yang tidak punya generator kode (lihat Consumable_request::_insert_master_item()).
    // Inventory/Asset/Jasa kodenya dibuat otomatis, jadi kolomnya disembunyikan
    // supaya user tidak bingung (user 2026-09-14). Nilainya ikut dikosongkan:
    // tanpa itu, kode yang terlanjur diketik utk Mesin akan tetap terkirim saat
    // user ganti jenis ke Inventory, dan generator otomatisnya jadi tidak jalan.
    const kodeManual = (type === 'machine');
    $('#qa_kode_wrap').toggleClass('d-none', !kodeManual);
    if (!kodeManual) { $('#qa_kode').val(''); }

    $('#qa_kode_help').text(type === 'machine'
        ? <?= json_encode(lang_line('cr_quick_add_code_machine', 'Wajib diisi — Master Mesin tidak punya kode otomatis. Contoh: MES-001')); ?>
        : <?= json_encode(lang_line('cr_quick_add_code_auto', 'Kosongkan untuk kode otomatis.')); ?>);
}
$('#qa_item_type').on('change', qaApplyType);
qaApplyType();

$('#qa_submit').on('click', function() {
    const $btn = $(this);
    const $alert = $('#quick_add_alert').addClass('d-none').text('');
    const payload = {
        item_type:     $('#qa_item_type').val(),
        nama:          $.trim($('#qa_nama').val()),
        kode:          $.trim($('#qa_kode').val()),
        kategori:      $('#qa_kategori').val() || '',
        satuan:        $('#qa_satuan').val() || '',
        inv_item_type: $('#qa_inv_item_type').val() || '',
        // Item baru harus lahir dengan Jenis Pemakaian = kategori Permintaan Barang yang
        // sedang dibuka, kalau tidak dia langsung tersaring keluar dari
        // daftar begitu tersimpan.
        irf_category:   qaIrfCategory(),
        usage_category: $('#qa_usage_category').val() || ''
    };
    if (!payload.nama) {
        $alert.removeClass('d-none').text('<?= lang_line('cr_quick_add_name_required', 'Nama item wajib diisi.'); ?>');
        return;
    }

    $btn.prop('disabled', true);
    $.post(baseUrl + 'consumable_request/ajax_quick_add_item', payload)
        .done(function(res) {
            if (!res || !res.success) {
                $alert.removeClass('d-none').text((res && res.message) || '<?= lang_line('cr_quick_add_failed', 'Gagal menyimpan item.'); ?>');
                return;
            }
            // Item baru masuk ke daftar & ke SEMUA dropdown, pilihan baris
            // lain tetap utuh, lalu langsung dipilih di baris pemicu.
            // Item inventory yang jenis pemakaiannya beda dgn kategori Permintaan Barang ini
            // TIDAK didorong ke daftar: server pun menyaringnya keluar, jadi
            // kalau tetap dimasukkan user bisa memilih item yang sebetulnya
            // bukan haknya kategori ini. Master lain (mesin/asset/jasa) tidak
            // punya jenis pemakaian -> usage_category null -> selalu dipakai.
            const bisaDipakai = !res.item.usage_category || !qaIrfDisaring()
                || res.item.usage_category === qaIrfCategory();

            if (bisaDipakai) {
                items_list.push(res.item);
                refreshAllItemOptions();
                if ($qaTargetSelect && $qaTargetSelect.length) {
                    $qaTargetSelect.val(res.item.item_key).trigger('change');
                }
            }
            $('#quickAddItemModal').modal('hide');

            if (typeof Swal === 'undefined') {
                if (!bisaDipakai) alert(res.message);
            } else if (bisaDipakai) {
                Swal.fire({ icon: 'success', title: res.message, toast: true, position: 'top-end',
                            timer: 2500, showConfirmButton: false });
            } else {
                // Sengaja BUKAN toast sekilas: user perlu membaca kenapa item
                // yang barusan dia buat tidak terpasang di baris ini.
                Swal.fire({ icon: 'warning', title: res.message,
                            text: <?= json_encode(lang_line('cr_quick_add_usage_saved_elsewhere', 'Jenis Pemakaian-nya berbeda dengan Kategori Kebutuhan Permintaan Barang ini, jadi item tidak dipasang di baris ini. Buka Master Barang kalau mau mengubahnya.')); ?>,
                            confirmButtonText: 'OK' });
            }
        })
        .fail(function() {
            $alert.removeClass('d-none').text('<?= lang_line('cr_quick_add_failed', 'Gagal menyimpan item.'); ?>');
        })
        .always(function() { $btn.prop('disabled', false); });
});

$('#form_request').on('submit', function(e) {
    const items = $('tr[id^="item_"]');
    if (items.length === 0) {
        e.preventDefault();
        alert('<?= lang_line('cr_min_item_required', 'Minimal satu item harus ditambahkan'); ?>');
        return false;
    }
});

}); // akhir $(document).ready()
</script>
