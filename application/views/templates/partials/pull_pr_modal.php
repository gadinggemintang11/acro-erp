<?php
/**
 * Modal "Tarik Item dari PR" — dipakai bersama oleh keempat varian PO
 * (produksi, non-produksi, asset, jasa).
 *
 * Butuh dari view pemanggil:
 *   $pull_pr_route  string  segmen controller PO, mis. 'purchase_order_general'
 *   $header         object  header PO (id, supplier_id, nama_supplier)
 *
 * Baris yang muncul = baris PR ber-status Approved, bertipe sama dengan PO ini,
 * yang masih punya sisa qty. Sisa dihitung server-side dan diklamp ulang saat
 * simpan, jadi angka di form ini tidak bisa dipakai untuk over-order.
 */
$pull_pr_route = $pull_pr_route ?? 'purchase_order';
?>
<div class="modal fade" id="pullPrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="<?= base_url($pull_pr_route . '/pull_from_pr/' . $header->id); ?>" id="pullPrForm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-import mr-2"></i> Tarik Item dari Purchase Request
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Yang tampil hanya baris PR <strong>Approved</strong> yang masih punya sisa qty.
                        Boleh mencentang dari <strong>beberapa PR sekaligus</strong>, dan qty boleh dikurangi
                        untuk memesan sebagian saja — sisanya tetap bisa ditarik ke PO lain.
                    </p>

                    <div class="form-row mb-2">
                        <div class="col-md-5 mb-1">
                            <input type="text" id="pullPrSearch" class="form-control form-control-sm"
                                   placeholder="Cari no. PR / keperluan / nama item...">
                        </div>
                        <div class="col-md-3 mb-1">
                            <input type="date" id="pullPrFrom" class="form-control form-control-sm" title="Tanggal PR dari">
                        </div>
                        <div class="col-md-3 mb-1">
                            <input type="date" id="pullPrTo" class="form-control form-control-sm" title="Tanggal PR sampai">
                        </div>
                        <div class="col-md-1 mb-1">
                            <button type="button" id="pullPrRefresh" class="btn btn-sm btn-outline-secondary btn-block" title="Muat ulang">
                                <i class="fas fa-sync-alt"></i>
                            </button>
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
                        <button type="submit" class="btn btn-info" id="pullPrSubmit" disabled>
                            <i class="fas fa-file-import mr-1"></i> Tarik ke PO
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(function () {
    var PULL_PR_URL      = '<?= base_url($pull_pr_route . '/pr_pull_items'); ?>';
    var PO_SUPPLIER_ID   = <?= (int) ($header->supplier_id ?? 0); ?>;
    var PO_SUPPLIER_NAME = <?= json_encode($header->nama_supplier ?? '-'); ?>;

    var $body = $('#pullPrBody');
    var searchTimer = null;

    function esc(s) {
        return $('<div>').text(s === null || s === undefined ? '' : s).html();
    }

    function num(v, dec) {
        var n = parseFloat(v) || 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function loadItems() {
        $body.html('<tr><td colspan="11" class="text-center text-muted py-4">' +
                   '<i class="fas fa-spinner fa-spin mr-1"></i> Memuat…</td></tr>');

        $.getJSON(PULL_PR_URL, {
            search:    $('#pullPrSearch').val(),
            date_from: $('#pullPrFrom').val(),
            date_to:   $('#pullPrTo').val()
        }).done(function (res) {
            if (!res || !res.success) {
                $body.html('<tr><td colspan="11" class="text-center text-danger py-4">' +
                           esc((res && res.message) || 'Gagal memuat data.') + '</td></tr>');
                return;
            }
            renderItems(res.items || []);
        }).fail(function () {
            $body.html('<tr><td colspan="11" class="text-center text-danger py-4">Gagal menghubungi server.</td></tr>');
        });
    }

    function renderItems(items) {
        if (!items.length) {
            $body.html('<tr><td colspan="11" class="text-center text-muted py-4">' +
                       'Tidak ada baris PR yang bisa ditarik. Pastikan PR sudah <strong>Approved</strong>, ' +
                       'tipenya sama dengan PO ini, dan qty-nya belum habis masuk PO lain.</td></tr>');
            $('#pullPrCheckAll').prop('checked', false);
            updateState();
            return;
        }

        var html = '';
        var lastPr = null;

        items.forEach(function (it) {
            // Pemisah antar PR biar jelas baris ini datang dari PR mana.
            if (lastPr !== it.pr_number) {
                lastPr = it.pr_number;
                html += '<tr class="bg-light"><td colspan="11" class="py-1">' +
                        '<strong>' + esc(it.pr_number) + '</strong>' +
                        '<span class="text-muted ml-2">' + esc(it.pr_date || '') + '</span>' +
                        (it.department  ? '<span class="text-muted ml-2">· ' + esc(it.department) + '</span>' : '') +
                        (it.requester_name ? '<span class="text-muted ml-2">· ' + esc(it.requester_name) + '</span>' : '') +
                        (it.purpose     ? '<span class="text-muted ml-2">· ' + esc(it.purpose) + '</span>' : '') +
                        '</td></tr>';
            }

            var remaining = parseFloat(it.remaining_qty) || 0;
            html += '<tr data-supplier-id="' + (parseInt(it.pr_supplier_id, 10) || 0) + '">' +
                '<td class="text-center align-middle">' +
                    '<input type="checkbox" class="pull-pr-check" name="pr_detail_ids[]" value="' + it.pr_detail_id + '">' +
                '</td>' +
                '<td class="align-middle">' + esc(it.pr_number) + '</td>' +
                '<td class="align-middle text-center">' + esc(it.pr_date || '-') + '</td>' +
                '<td class="align-middle"><code>' + esc(it.item_code || '-') + '</code></td>' +
                '<td class="align-middle">' + esc(it.item_name || '-') +
                    (it.description ? '<br><small class="text-muted">' + esc(it.description) + '</small>' : '') +
                '</td>' +
                '<td class="align-middle text-center">' + esc(it.item_unit || '-') + '</td>' +
                '<td class="align-middle text-right">' + num(it.pr_qty, 2) + '</td>' +
                '<td class="align-middle text-right ' + (parseFloat(it.ordered_qty) > 0 ? 'text-warning font-weight-bold' : 'text-muted') + '">' +
                    num(it.ordered_qty, 2) + '</td>' +
                '<td class="align-middle text-right font-weight-bold">' + num(remaining, 2) + '</td>' +
                '<td class="align-middle">' +
                    '<input type="number" class="form-control form-control-sm text-right pull-pr-qty" ' +
                           'name="pull_qty[' + it.pr_detail_id + ']" step="0.01" min="0.01" ' +
                           'max="' + remaining + '" value="' + remaining + '" disabled>' +
                '</td>' +
                '<td class="align-middle small ' + supplierClass(it.pr_supplier_id) + '">' +
                    esc(it.pr_supplier_name || '-') + '</td>' +
            '</tr>';
        });

        $body.html(html);
        $('#pullPrCheckAll').prop('checked', false);
        updateState();
    }

    function supplierClass(prSupplierId) {
        var id = parseInt(prSupplierId, 10) || 0;
        if (!id || !PO_SUPPLIER_ID) return 'text-muted';
        return id === PO_SUPPLIER_ID ? 'text-success' : 'text-warning';
    }

    // Qty input hanya aktif kalau barisnya dicentang, supaya tidak ada angka
    // "terisi" yang sebenarnya tidak ikut dikirim.
    function updateState() {
        var $checked = $body.find('.pull-pr-check:checked');
        var count = $checked.length;

        $body.find('.pull-pr-check').each(function () {
            $(this).closest('tr').find('.pull-pr-qty').prop('disabled', !this.checked);
        });

        $('#pullPrCount').text(count);
        $('#pullPrSubmit').prop('disabled', count === 0);

        // PO hanya punya satu supplier. Menggabungkan PR yang usulan supplier-nya
        // beda tetap boleh (supplier final ditentukan di PO), tapi diingatkan.
        var mismatch = [];
        $checked.each(function () {
            var sid = parseInt($(this).closest('tr').data('supplier-id'), 10) || 0;
            if (sid && PO_SUPPLIER_ID && sid !== PO_SUPPLIER_ID) {
                var name = $.trim($(this).closest('tr').find('td').last().text());
                if (mismatch.indexOf(name) === -1) mismatch.push(name);
            }
        });

        if (mismatch.length) {
            $('#pullPrSupplierWarn').show().html(
                '<i class="fas fa-exclamation-triangle mr-1"></i> Ada baris yang usulan supplier-nya berbeda dari supplier PO ini (<strong>' +
                esc(PO_SUPPLIER_NAME) + '</strong>): ' + esc(mismatch.join(', ')) +
                '. Boleh dilanjutkan — supplier final ditentukan di PO.'
            );
        } else {
            $('#pullPrSupplierWarn').hide().empty();
        }
    }

    $('#pullPrModal').on('shown.bs.modal', loadItems);
    $('#pullPrRefresh, #pullPrFrom, #pullPrTo').on('click change', function (e) {
        if (e.type === 'click' && this.id !== 'pullPrRefresh') return;
        loadItems();
    });
    $('#pullPrSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadItems, 350);
    });

    $('#pullPrCheckAll').on('change', function () {
        $body.find('.pull-pr-check').prop('checked', this.checked);
        updateState();
    });
    $body.on('change', '.pull-pr-check', updateState);

    // Jaga qty tidak melebihi sisa di sisi klien juga (server tetap yang final).
    $body.on('change', '.pull-pr-qty', function () {
        var max = parseFloat($(this).attr('max')) || 0;
        var val = parseFloat(this.value) || 0;
        if (val > max) this.value = max;
        if (val <= 0)  this.value = max;
    });

    $('#pullPrForm').on('submit', function () {
        $('#pullPrSubmit').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin mr-1"></i> Menarik…');
    });
});
</script>
