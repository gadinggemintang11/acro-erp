<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i>Buat Pengeluaran Barang Habis Pakai
        </h3>
        <a href="<?= base_url('consumable_out'); ?>" class="btn btn-secondary btn-sm btn-load">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('consumable_out/store'); ?>" id="consumableForm">

        <!-- Header Info -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Dokumen</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Dokumen</label>
                            <input type="text" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($doc_number); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Pengeluaran <span class="text-danger">*</span></label>
                            <input type="date" name="issuance_date" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Departemen / Bagian</label>
                            <input type="text" name="department" class="form-control form-control-sm"
                                   placeholder="contoh: Produksi, GA, HRD..." maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Nama Penerima</label>
                            <input type="text" name="recipient" class="form-control form-control-sm"
                                   placeholder="Nama penerima barang" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Keperluan / Tujuan Penggunaan</label>
                            <input type="text" name="purpose" class="form-control form-control-sm"
                                   placeholder="Keterangan keperluan..." maxlength="255">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Catatan Tambahan</label>
                            <input type="text" name="notes" class="form-control form-control-sm"
                                   placeholder="Catatan (opsional)" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Lines -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Barang</h6>
                <button type="button" class="btn btn-success btn-sm" id="btnAddRow">
                    <i class="fas fa-plus fa-sm mr-1"></i> Tambah Barang
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="itemsTable">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="35%">Nama Barang</th>
                                <th width="15%">Kode</th>
                                <th width="12%">Satuan</th>
                                <th width="15%">Qty Dikeluarkan</th>
                                <th width="18%">Catatan Item</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Rows appended by JS -->
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0" id="noItemsMsg">Klik "Tambah Barang" untuk menambahkan item.</p>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-load" id="btnSave">
                    <i class="fas fa-save mr-1"></i> Simpan Pengeluaran
                </button>
            </div>
        </div>

    </form>
</div>

<!-- Hidden item data for JS -->
<script>
var inventoryItems = <?= json_encode(array_map(function($i) {
    return [
        'id'        => $i->id,
        'kode'      => $i->kode_barang,
        'nama'      => $i->nama_barang,
        'satuan'    => $i->satuan ?? '-',
        'kategori'  => $i->kategori ?? '',
    ];
}, $items_list)); ?>;

var rowIndex = 0;

$(document).ready(function() {

    // Build select options once
    var optionsHtml = '<option value="">-- Pilih Barang --</option>';
    inventoryItems.forEach(function(item) {
        optionsHtml += '<option value="' + item.id + '" '
            + 'data-kode="' + escHtml(item.kode) + '" '
            + 'data-nama="' + escHtml(item.nama) + '" '
            + 'data-satuan="' + escHtml(item.satuan) + '">'
            + escHtml(item.nama) + ' [' + escHtml(item.kode) + ']'
            + '</option>';
    });

    // Add row
    $('#btnAddRow').on('click', function() {
        addRow(optionsHtml);
        $('#noItemsMsg').hide();
    });

    // Remove row
    $('#itemsBody').on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#itemsBody tr').length === 0) {
            $('#noItemsMsg').show();
        }
    });

    // When item selected, fill kode & satuan
    $('#itemsBody').on('change', '.item-select', function() {
        var $row  = $(this).closest('tr');
        var $opt  = $(this).find(':selected');
        $row.find('.item-kode').val($opt.data('kode') || '');
        $row.find('.item-satuan').val($opt.data('satuan') || '');
    });

    // Validate on submit
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
            alert('Minimal satu barang harus ditambahkan.');
            e.preventDefault();
            return false;
        }

        if (!valid) {
            alert('Qty harus lebih dari 0 untuk semua baris.');
            e.preventDefault();
            return false;
        }
    });

    // Add first row on load
    addRow(optionsHtml);
    $('#noItemsMsg').hide();
});

function addRow(optionsHtml) {
    var idx = rowIndex++;
    var tr = '<tr>'
        + '<td>'
        + '<select name="items[' + idx + '][item_id]" class="form-control form-control-sm item-select" required>'
        + optionsHtml
        + '</select>'
        + '</td>'
        + '<td><input type="text" name="items[' + idx + '][item_code]" class="form-control form-control-sm item-kode" readonly placeholder="-"></td>'
        + '<td><input type="text" name="items[' + idx + '][item_unit]" class="form-control form-control-sm item-satuan" readonly placeholder="-"></td>'
        + '<td><input type="number" name="items[' + idx + '][qty]" class="form-control form-control-sm text-right item-qty" min="0.0001" step="any" value="1" required></td>'
        + '<td><input type="text" name="items[' + idx + '][notes]" class="form-control form-control-sm" placeholder="Catatan..."></td>'
        + '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fas fa-times"></i></button></td>'
        + '</tr>';
    $('#itemsBody').append(tr);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
