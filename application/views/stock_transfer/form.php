<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt text-primary mr-2"></i><?= $title ?>
        </h3>
        <a href="<?= base_url('stock_transfer'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('co_back', 'Kembali'); ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('stock_transfer/store'); ?>" id="transferForm">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('st_doc_info', 'Informasi Transfer'); ?></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('st_lbl_number', 'No. Transfer'); ?></label>
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($transfer_number) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('st_lbl_date', 'Tanggal'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('st_lbl_from', 'Dari Kategori'); ?> <span class="text-danger">*</span></label>
                            <select name="from_category" id="fromCategory" class="form-control form-control-sm" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>" <?= ($c === $from_category) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('st_lbl_to', 'Ke Kategori'); ?> <span class="text-danger">*</span></label>
                            <select name="to_category" id="toCategory" class="form-control form-control-sm" required></select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?= lang_line('st_lbl_notes', 'Alasan / Catatan'); ?></label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="<?= lang_line('st_notes_ph', 'mis. koreksi cutover, pinjam sementara, dll'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('st_item_list', 'Barang yang Dipindah'); ?></h6>
                <button type="button" class="btn btn-success btn-sm" id="btnAddRow">
                    <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('co_add_item', 'Tambah Barang'); ?>
                </button>
                <?php // Barang tanpa stok di kategori asal tidak masuk daftar, jadi tidak
                      // bisa dicari — dulu tanpa keterangan apa pun (user 2026-09-21). ?>
                <small class="text-muted ml-2" id="itemsAvailInfo"></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="40%"><?= lang_line('co_th_item_name', 'Nama Barang'); ?></th>
                                <th width="15%"><?= lang_line('co_th_unit', 'Satuan'); ?></th>
                                <th width="20%"><?= lang_line('st_th_stock_source', 'Stock di Kategori Asal'); ?></th>
                                <th width="20%"><?= lang_line('st_th_qty_transfer', 'Qty Dipindah'); ?></th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0" id="noItemsMsg"><?= lang_line('co_click_to_add', 'Klik "Tambah Barang" untuk menambahkan item.'); ?></p>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary" id="btnSave">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('st_btn_save', 'Simpan & Eksekusi Transfer'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const baseUrl = '<?= base_url(); ?>';
const CATEGORIES = <?= json_encode($categories) ?>;
let items_list = <?= json_encode($items_list) ?>;
let rowIndex = 0;

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function syncToCategory() {
    const from = $('#fromCategory').val();
    const opts = CATEGORIES.filter(c => c !== from)
        .map(c => `<option value="${c}">${c}</option>`).join('');
    $('#toCategory').html(opts);
}

function buildOptionsHtml() {
    let html = '<option value="">-- Pilih Barang --</option>';
    items_list.forEach(function(item) {
        html += '<option value="' + item.item_key + '" '
            + 'data-satuan="' + escHtml(item.satuan) + '" '
            + 'data-stock="' + item.stock + '">'
            + escHtml(item.nama_barang) + ' [' + escHtml(item.kode_barang) + '] - Stock: ' + item.stock
            + '</option>';
    });
    return html;
}

// Dropdown barang dibuat searchable pakai Select2 (user 2026-09-21) — pola
// sama dengan form Pengeluaran Barang (consumable_out/form.php). Teks option
// sudah memuat nama + kode + stock, jadi pencariannya langsung kena ketiganya.
function initItemSelect($sel) {
    if (!$sel.length || typeof $.fn.select2 === 'undefined') return;
    $sel.select2({
        width: '100%',
        placeholder: <?= json_encode(lang_line('st_select2_ph', 'Cari barang (nama / kode)...')); ?>,
        allowClear: false
    });
}

// Select2 menyisipkan elemen sendiri di sebelah <select>; kalau barisnya
// dibuang tanpa destroy, sisa elemennya ikut tertinggal.
function destroyItemSelect($scope) {
    if (typeof $.fn.select2 === 'undefined') return;
    $scope.find('.item-select').each(function() {
        if ($(this).data('select2')) $(this).select2('destroy');
    });
}

function addRow() {
    const idx = rowIndex++;
    const tr = '<tr>'
        + '<td><select name="items[' + idx + '][item_id]" class="form-control form-control-sm item-select" required>' + buildOptionsHtml() + '</select></td>'
        + '<td><input type="text" class="form-control form-control-sm item-satuan" readonly></td>'
        + '<td><input type="number" class="form-control form-control-sm text-right item-stock" readonly value="0"></td>'
        + '<td><input type="number" name="items[' + idx + '][qty]" class="form-control form-control-sm text-right item-qty" min="0.0001" step="any" required></td>'
        + '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fas fa-times"></i></button></td>'
        + '</tr>';
    $('#itemsBody').append(tr);
    initItemSelect($('#itemsBody tr:last').find('.item-select'));
    $('#noItemsMsg').hide();
}

// Keterangan jumlah barang yang bisa dipilih untuk kategori asal terpilih.
function updateAvailInfo() {
    const n = items_list.length;
    const cat = $('#fromCategory').val();
    $('#itemsAvailInfo').html(n > 0
        ? '<i class="fas fa-info-circle mr-1"></i>' + n + ' barang punya stok di gudang <strong>' + cat + '</strong>. Barang dengan stok 0 tidak muncul di daftar.'
        : '<i class="fas fa-exclamation-triangle text-warning mr-1"></i>Tidak ada barang berstok di gudang <strong>' + cat + '</strong>, jadi daftar barangnya kosong.');
}

function refreshItemsList(callback) {
    $.get(baseUrl + 'stock_transfer/ajax_items_by_category', { from_category: $('#fromCategory').val() })
        .done(function(res) {
            if (res && res.success) { items_list = res.items; }
            updateAvailInfo();
            if (callback) callback();
        });
}

$(document).ready(function() {
    syncToCategory();
    updateAvailInfo();

    $('#fromCategory').on('change', function() {
        syncToCategory();
        // Kategori asal ganti -> stock yang relevan beda, kosongkan baris lama.
        destroyItemSelect($('#itemsBody'));
        $('#itemsBody').empty();
        $('#noItemsMsg').show();
        refreshItemsList();
    });

    $('#btnAddRow').on('click', addRow);

    $('#itemsBody').on('click', '.btn-remove-row', function() {
        const $row = $(this).closest('tr');
        destroyItemSelect($row);
        $row.remove();
        if ($('#itemsBody tr').length === 0) { $('#noItemsMsg').show(); }
    });

    $('#itemsBody').on('change', '.item-select', function() {
        const $row = $(this).closest('tr');
        const $opt = $(this).find(':selected');
        $row.find('.item-satuan').val($opt.data('satuan') || '');
        $row.find('.item-stock').val($opt.data('stock') || '0');
        $row.find('.item-qty').attr('max', $opt.data('stock') || 0);
    });

    $('#transferForm').on('submit', function(e) {
        let valid = true;
        let hasItems = false;
        $('#itemsBody tr').each(function() {
            const selVal = $(this).find('.item-select').val();
            const qty = parseFloat($(this).find('.item-qty').val() || 0);
            const stock = parseFloat($(this).find('.item-stock').val() || 0);
            if (selVal) {
                hasItems = true;
                if (qty <= 0 || qty > stock) {
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
            alert('<?= lang_line('st_qty_invalid', 'Qty harus > 0 dan tidak boleh melebihi stock yang tersedia di kategori asal.'); ?>');
            e.preventDefault();
            return false;
        }
    });
});
</script>
