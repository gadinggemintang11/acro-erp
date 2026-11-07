<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i><?= lang_line('co_create_title', 'Buat Pengeluaran Barang Habis Pakai'); ?>
        </h3>
        <a href="<?= base_url('consumable_out'); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('co_back', 'Kembali'); ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('consumable_out/store'); ?>" id="consumableForm">
        <?php if ($request_id): ?>
            <input type="hidden" name="request_id" value="<?= (int)$request_id ?>">
        <?php endif; ?>

        <!-- Request Info (jika dari request) -->
        <?php if ($request_header): ?>
        <div class="card shadow mb-4 border-info">
            <div class="card-header py-3 bg-info">
                <h6 class="m-0 font-weight-bold text-white"><?= lang_line('co_from_request', 'Dari Request'); ?>: <?= htmlspecialchars($request_header->request_number) ?></h6>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong><?= lang_line('co_lbl_request_date', 'Tanggal Request'); ?>:</strong> <?= date('d M Y', strtotime($request_header->request_date)) ?></p>
                <p class="mb-1"><strong><?= lang_line('co_lbl_department', 'Departemen'); ?>:</strong> <?= htmlspecialchars($request_header->department ?? '-') ?></p>
                <p class="mb-1"><strong><?= lang_line('cr_category', 'Kategori Kebutuhan'); ?>:</strong> <span class="badge badge-<?= ($request_header->category ?? 'General') === 'Produksi' ? 'dark' : 'secondary' ?>"><?= htmlspecialchars($request_header->category ?? 'General') ?></span></p>
                <p class="mb-1"><strong><?= lang_line('co_lbl_purpose', 'Keperluan'); ?>:</strong> <?= htmlspecialchars($request_header->purpose ?? '-') ?></p>
                <p class="mb-0 text-muted small">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('co_qty_prefill_info', 'Qty pre-filled adalah <strong>outstanding</strong> (request &minus; sudah dikeluarkan). Jika stock kurang, isi sebagian — sisanya akan tetap muncul di menu Outstanding Request.'); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Header Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('co_doc_info', 'Informasi Dokumen'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('co_doc_no', 'No. Dokumen'); ?></label>
                            <input type="text" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($doc_number); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('co_issuance_date', 'Tanggal Pengeluaran'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="issuance_date" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('co_department', 'Departemen / Bagian'); ?></label>
                            <input type="text" name="department" class="form-control form-control-sm"
                                   placeholder="<?= lang_line('co_department_ph', 'contoh: Produksi, GA, HRD...'); ?>" maxlength="100"
                                   value="<?= htmlspecialchars($request_header->department ?? '') ?>">
                        </div>
                    </div>
                    <?php if (!$request_header): ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cr_category', 'Kategori Kebutuhan'); ?> <span class="text-danger">*</span></label>
                            <select name="category" class="form-control form-control-sm" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('co_category_hint', 'Pengeluaran ad-hoc (tanpa Permintaan Barang) — pilih tujuan pemakaiannya.'); ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('co_recipient', 'Nama Penerima'); ?></label>
                            <input type="text" name="recipient" class="form-control form-control-sm"
                                   placeholder="<?= lang_line('co_recipient_ph', 'Nama penerima barang'); ?>" maxlength="100"
                                   value="<?= htmlspecialchars($request_header->requestor_name ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('co_purpose', 'Keperluan / Tujuan Penggunaan'); ?></label>
                            <input type="text" name="purpose" class="form-control form-control-sm"
                                   placeholder="<?= lang_line('co_purpose_ph', 'Keterangan keperluan...'); ?>" maxlength="255"
                                   value="<?= htmlspecialchars($request_header->purpose ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('co_notes', 'Catatan Tambahan'); ?></label>
                            <input type="text" name="notes" class="form-control form-control-sm"
                                   placeholder="<?= lang_line('co_notes_ph', 'Catatan (opsional)'); ?>" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Lines -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('co_item_list', 'Daftar Barang'); ?></h6>
                <?php
                    // Tombol "Tambah Barang" DISEMBUNYIKAN kalau pengeluaran ini
                    // memenuhi sebuah IRF (user 2026-09-09: "button add item
                    // disini di hilangi saja") — barisnya sudah ditentukan IRF
                    // sumbernya, menambah item di luar itu merusak penelusuran
                    // IRF -> pengeluaran -> outstanding -> PR.
                    // Untuk pengeluaran AD-HOC (tanpa IRF) tombolnya TETAP ada:
                    // dicek di data, 24 dari 52 dokumen dibuat lewat jalur itu,
                    // dan tanpa tombol ini jalur tsb cuma bisa 1 item per dokumen.
                ?>
                <?php if (!$request_id): ?>
                <button type="button" class="btn btn-success btn-sm" id="btnAddRow">
                    <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('co_add_item', 'Tambah Barang'); ?>
                </button>
                <?php else: ?>
                <span class="small text-muted">
                    <i class="fas fa-lock mr-1"></i><?= lang_line('co_items_from_irf', 'Item mengikuti Permintaan Barang sumber'); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="itemsTable">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="30%"><?= lang_line('co_th_item_name', 'Nama Barang'); ?></th>
                                <th width="12%"><?= lang_line('co_th_code', 'Kode'); ?></th>
                                <th width="10%"><?= lang_line('co_th_unit', 'Satuan'); ?></th>
                                <th width="12%"><?= lang_line('co_th_stock', 'Stock'); ?></th>
                                <th width="13%"><?= lang_line('co_th_qty_done', 'Sudah Keluar'); ?></th>
                                <th width="15%"><?= lang_line('co_th_qty_out', 'Qty Dikeluarkan'); ?></th>
                                <th width="16%"><?= lang_line('co_th_notes', 'Catatan Item'); ?></th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Rows appended by JS -->
                        </tbody>
                    </table>
                </div>
                <?php if (!$request_id): ?><p class="text-muted small mb-0" id="noItemsMsg"><?= lang_line('co_click_to_add', 'Klik "Tambah Barang" untuk menambahkan item.'); ?></p><?php endif; ?>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-load" id="btnSave">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('co_save', 'Simpan Pengeluaran'); ?>
                </button>
            </div>
        </div>

    </form>
</div>

<style>
/* Samakan tampilan Select2 dengan input form-control-sm di tabel item */
.item-select + .select2-container .select2-selection--single {
    height: calc(1.5em + 0.5rem + 2px);
    border: 1px solid #ced4da;
}
.item-select + .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: calc(1.5em + 0.5rem);
    padding-left: 8px;
    font-size: 0.875rem;
}
.item-select + .select2-container .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.5rem);
}
.select2-dropdown { font-size: 0.875rem; }
</style>

<!-- Hidden item data for JS -->
<script>
// `key` = "type-id" (mis. "machine-15"). Dipakai sebagai value <option> karena
// item bisa berasal dari 2 master (inventories & machines) yang id-nya bentrok.
var inventoryItems = <?= json_encode(array_map(function($i) {
    return [
        'id'        => $i->id,
        'key'       => $i->item_key ?? ('inventory-' . $i->id),
        'type'      => $i->item_type ?? 'inventory',
        'kode'      => $i->kode_barang,
        'nama'      => $i->nama_barang,
        'satuan'    => $i->satuan ?? '-',
        'kategori'  => $i->kategori ?? '',
        'stock'     => (float)($i->stock ?? 0),
    ];
}, $items_list)); ?>;

var ITEM_GROUPS = [
    { type: 'inventory', label: <?= json_encode(lang_line('cr_group_inventory', 'General')); ?> },
    { type: 'machine',   label: <?= json_encode(lang_line('cr_group_machine', 'Mesin / Sparepart')); ?> },
];

var requestItems = <?= json_encode(array_map(function($i) {
    $qty = isset($i->qty_outstanding) ? (float)$i->qty_outstanding : (float)($i->qty_requested ?? 0);
    return [
        'item_id'    => $i->item_id,
        'item_type'  => $i->item_type ?? 'inventory',
        'item_key'   => ($i->item_type ?? 'inventory') . '-' . $i->item_id,
        'item_code'  => $i->item_code,
        'item_name'  => $i->item_name,
        'item_unit'  => $i->item_unit,
        'qty'        => $qty,
        'qty_requested'   => isset($i->qty_requested) ? (float)$i->qty_requested : null,
        'qty_issued'      => isset($i->qty_issued) ? (float)$i->qty_issued : null,
        'qty_outstanding' => isset($i->qty_outstanding) ? (float)$i->qty_outstanding : null,
        'notes'      => $i->notes ?? '',
    ];
}, $request_items ?? [])) ?>;

var rowIndex = 0;

$(document).ready(function() {

    // Saat form dibuka dari sebuah Permintaan Barang, pilihan barang DIKUNCI ke item yang
    // memang diminta Permintaan Barang itu (user 2026-09-25).
    //
    // Sebelumnya dropdown berisi SELURUH master barang, sehingga barang yang
    // tidak pernah diminta bisa dikeluarkan atas nama sebuah Permintaan Barang. Akibatnya
    // baris Permintaan Barang aslinya tidak pernah terpotong: Permintaan Barang-nya menggantung selamanya
    // di antrian Item Release dan ikut terbawa waktu "Buat PR Sisanya".
    // Terbukti terjadi -- mis. CO/202606/0004 mengeluarkan Masking Tape atas
    // nama PROD00002, padahal Permintaan Barang itu cuma minta Stapler & Sticky Note.
    //
    // Pengeluaran barang di luar Permintaan Barang tetap bisa, lewat pintu pengeluaran
    // ad-hoc (tanpa request_id). Sisi server ikut menolak -- lihat
    // Consumable_out::store().
    var IRF_MODE = requestItems.length > 0;
    var allowedKeys = {};
    requestItems.forEach(function(it) { allowedKeys[it.item_key] = true; });
    var pickableItems = IRF_MODE
        ? inventoryItems.filter(function(item) { return allowedKeys[item.key] === true; })
        : inventoryItems;

    var optionsHtml = '<option value=""><?= lang_line('co_select_item_ph', '-- Pilih Barang --'); ?></option>';
    ITEM_GROUPS.forEach(function(g) {
        var rows = pickableItems.filter(function(item) { return item.type === g.type; });
        if (!rows.length) return;
        optionsHtml += '<optgroup label="' + escHtml(g.label) + '">';
        rows.forEach(function(item) {
            optionsHtml += '<option value="' + item.key + '" '
                + 'data-kode="' + escHtml(item.kode) + '" '
                + 'data-nama="' + escHtml(item.nama) + '" '
                + 'data-satuan="' + escHtml(item.satuan) + '" '
                + 'data-stock="' + item.stock + '">'
                + escHtml(item.nama) + ' [' + escHtml(item.kode) + '] - Stock: ' + item.stock
                + '</option>';
        });
        optionsHtml += '</optgroup>';
    });

    $('#btnAddRow').on('click', function() {
        addRow(optionsHtml);
        $('#noItemsMsg').hide();
    });

    $('#itemsBody').on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#itemsBody tr').length === 0) {
            $('#noItemsMsg').show();
        }
    });

    $('#itemsBody').on('change', '.item-select', function() {
        var $row  = $(this).closest('tr');
        var $opt  = $(this).find(':selected');
        $row.find('.item-kode').val($opt.data('kode') || '');
        $row.find('.item-satuan').val($opt.data('satuan') || '');
        $row.find('.item-stock').val($opt.data('stock') || '0');
    });

    $('#consumableForm').on('submit', function(e) {
        var valid = true;
        var hasItems = false;

        $('#itemsBody tr').each(function() {
            var selVal = $(this).find('.item-select').val();
            var qty    = parseFloat($(this).find('.item-qty').val() || 0);
            if (selVal) {
                hasItems = true;
                if (qty <= 0) {
                    $(this).find('.item-qty').addClass('is-invalid');
                    valid = false;
                } else {
                    $(this).find('.item-qty').removeClass('is-invalid');
                }
            }
        });

        if (!hasItems) {
            alert('<?= lang_line('co_min_item', 'Minimal satu barang harus ditambahkan.'); ?>');
            e.preventDefault();
            return false;
        }

        if (!valid) {
            alert('<?= lang_line('co_qty_gt_zero', 'Qty harus lebih dari 0 untuk semua baris.'); ?>');
            e.preventDefault();
            return false;
        }
    });

    var outstandingItems = requestItems.filter(function(it) {
        return it.qty_outstanding === null || parseFloat(it.qty_outstanding) > 0;
    });
    if (outstandingItems.length > 0) {
        outstandingItems.forEach(function(item) {
            addRow(optionsHtml, item);
        });
        $('#noItemsMsg').hide();
    } else if (requestItems.length > 0) {
        alert('<?= lang_line('co_all_issued', 'Semua item dari request ini sudah fully issued. Tidak ada qty outstanding.'); ?>');
        addRow(optionsHtml);
        $('#noItemsMsg').hide();
    } else {
        addRow(optionsHtml);
        $('#noItemsMsg').hide();
    }
});

function addRow(optionsHtml, requestItem) {
    var idx = rowIndex++;
    var itemId = requestItem ? (requestItem.item_key || requestItem.item_id) : '';
    var itemQty = requestItem ? requestItem.qty : '1';
    var itemNotes = requestItem ? requestItem.notes : '';

    // "Sudah Keluar": qty yang SUDAH dikeluarkan utk baris Permintaan Barang ini di dokumen
    // pengeluaran sebelumnya, ditampilkan sbg "sudah / diminta" (user
    // 2026-09-25: "andai permintaan 10 tapi stock 5, yang dikeluarin 5 dulu").
    // Kolom Qty Dikeluarkan memang sudah di-prefill dgn sisanya saja, tapi
    // tanpa angka ini user tidak punya cara melihat kenapa angkanya bukan 10.
    // Baris ad-hoc (pengeluaran tanpa Permintaan Barang) tidak punya riwayat -> "-".
    var issuedCell = '<span class="text-muted">-</span>';
    if (requestItem && requestItem.qty_requested !== null && requestItem.qty_requested !== undefined) {
        var qIssued = parseFloat(requestItem.qty_issued || 0);
        var qReq    = parseFloat(requestItem.qty_requested || 0);
        issuedCell = '<span class="' + (qIssued > 0 ? 'font-weight-bold text-success' : 'text-muted') + '">'
                   + fmtQty(qIssued) + '</span>'
                   + '<span class="text-muted"> / ' + fmtQty(qReq) + '</span>';
    }

    var tr = '<tr>'
        + '<td>'
        + '<select name="items[' + idx + '][item_id]" class="form-control form-control-sm item-select" required>'
        + optionsHtml
        + '</select>'
        + '</td>'
        + '<td><input type="text" name="items[' + idx + '][item_code]" class="form-control form-control-sm item-kode" readonly placeholder="-"></td>'
        + '<td><input type="text" name="items[' + idx + '][item_unit]" class="form-control form-control-sm item-satuan" readonly placeholder="-"></td>'
        + '<td><input type="number" class="form-control form-control-sm text-right item-stock" readonly placeholder="0" value="0"></td>'
        + '<td class="text-right align-middle small">' + issuedCell + '</td>'
        + '<td><input type="number" name="items[' + idx + '][qty]" class="form-control form-control-sm text-right item-qty" min="0.0001" step="any" value="' + itemQty + '" required></td>'
        + '<td><input type="text" name="items[' + idx + '][notes]" class="form-control form-control-sm" placeholder="<?= lang_line('co_notes_short_ph', 'Catatan...'); ?>" value="' + escHtml(itemNotes) + '"></td>'
        + '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fas fa-times"></i></button></td>'
        + '</tr>';
    $('#itemsBody').append(tr);
    var $row = $('#itemsBody tr:last');
    initItemSelect($row.find('.item-select'));

    if (requestItem && itemId) {
        $row.find('.item-select').val(itemId).trigger('change');
    }
}

// Jadikan dropdown barang searchable pakai Select2.
function initItemSelect($sel) {
    if (!$sel.length || typeof $.fn.select2 === 'undefined') return;
    $sel.select2({
        width: '100%',
        placeholder: <?= json_encode(lang_line('co_select2_ph', 'Cari barang (nama / kode)...')); ?>,
        allowClear: false
    });
}

// Buang nol desimal yang tidak perlu: 5.0000 -> 5, 2.5000 -> 2.5.
function fmtQty(n) {
    var v = parseFloat(n);
    if (!isFinite(v)) return '0';
    return String(parseFloat(v.toFixed(4)));
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
