<?php
/**
 * Modal "Tarik dari Permintaan Barang / Material Request" untuk halaman detail PR.
 * Dibuat 2026-11-05 (rebrand Acropoly): PR bisa dibuat manual lalu menarik
 * baris permintaan yang sudah Approved -- pola sama dgn PO "Tarik dari PR".
 *
 * Variabel:
 *   $pull_route  route controller PR (purchase_request_general / _jasa / _asset / purchase_request)
 *   $pull_kind   'irf' (Permintaan Barang) | 'mr' (Material Request)
 *   $pull_pr_id  id PR
 *
 * Endpoint: {route}/pull_list/{id} (GET, JSON) & {route}/pull_items/{id} (POST rows[id]=qty).
 * Qty maksimal per baris = kebutuhan PR yang tersisa (sisa permintaan - stok - PR lain).
 */
$pull_label = $pull_kind === 'mr' ? 'Material Request' : 'Permintaan Barang';
?>
<div class="modal fade" id="pullSourceModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-download mr-2"></i> Tarik dari <?= $pull_label ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Daftar baris <?= $pull_label ?> yang sudah <strong>Approved</strong> dan masih butuh dibelikan.
                    "Qty PR" sudah dikurangi stok gudang &amp; qty yang sudah ada di PR lain, jadi tidak bisa dobel.
                </p>
                <div class="input-group input-group-sm mb-2" style="max-width:360px">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                    <input type="text" id="pullSearch" class="form-control" placeholder="Cari nomor / nama barang...">
                </div>
                <div class="table-responsive" style="max-height:55vh">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light text-center small">
                            <tr>
                                <th style="width:32px"><input type="checkbox" id="pullCheckAll"></th>
                                <th>No. <?= $pull_label ?></th>
                                <th>Dept</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Sat.</th>
                                <th>Sisa Diminta</th>
                                <th>Dari Stok</th>
                                <th>Di PR Lain</th>
                                <th style="width:110px">Qty PR</th>
                            </tr>
                        </thead>
                        <tbody id="pullRows">
                            <tr><td colspan="10" class="text-center text-muted py-3">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <span class="mr-auto small text-muted" id="pullSelectedInfo">0 baris dipilih</span>
                <button type="button" class="btn btn-primary" id="pullSubmit" disabled><i class="fas fa-download mr-1"></i> Tarik ke PR</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    if (!$) return;
    var LIST_URL = <?= json_encode(base_url($pull_route . '/pull_list/' . (int) $pull_pr_id)) ?>;
    var PULL_URL = <?= json_encode(base_url($pull_route . '/pull_items/' . (int) $pull_pr_id)) ?>;
    var esc = function (v) { return $('<div>').text(v == null ? '' : String(v)).html(); };
    var num = function (v) { return (Math.round(parseFloat(v || 0) * 10000) / 10000).toLocaleString('id-ID'); };
    var notify = function (ok, msg) {
        if (window.Swal) Swal.fire(ok ? 'Berhasil' : 'Gagal', msg, ok ? 'success' : 'error');
        else alert(msg);
    };

    function refreshInfo() {
        var n = $('#pullRows .pull-check:checked').length;
        $('#pullSelectedInfo').text(n + ' baris dipilih');
        $('#pullSubmit').prop('disabled', n === 0);
    }

    $('#pullSourceModal').on('show.bs.modal', function () {
        $('#pullRows').html('<tr><td colspan="10" class="text-center text-muted py-3">Memuat...</td></tr>');
        $('#pullCheckAll').prop('checked', false);
        $('#pullSearch').val('');
        refreshInfo();
        $.getJSON(LIST_URL).done(function (res) {
            if (!res || !res.success) {
                $('#pullRows').html('<tr><td colspan="10" class="text-center text-danger py-3">' + esc(res && res.message || 'Gagal memuat data') + '</td></tr>');
                return;
            }
            if (!res.rows.length) {
                $('#pullRows').html('<tr><td colspan="10" class="text-center text-muted py-3">Tidak ada <?= $pull_label ?> yang masih butuh PR.</td></tr>');
                return;
            }
            var html = '';
            $.each(res.rows, function (_, r) {
                html += '<tr data-search="' + esc((r.request_number + ' ' + r.item_code + ' ' + r.item_name).toLowerCase()) + '">'
                    + '<td class="text-center"><input type="checkbox" class="pull-check" value="' + r.id + '"></td>'
                    + '<td class="small font-weight-bold">' + esc(r.request_number) + '</td>'
                    + '<td class="small">' + esc(r.department || '-') + '</td>'
                    + '<td class="small">' + esc(r.item_code || '-') + '</td>'
                    + '<td class="small">' + esc(r.item_name) + (r.category ? ' <span class="badge badge-light">' + esc(r.category) + '</span>' : '') + '</td>'
                    + '<td class="small text-center">' + esc(r.item_unit || '') + '</td>'
                    + '<td class="small text-right">' + num(r.qty_outstanding) + '</td>'
                    + '<td class="small text-right">' + num(r.qty_from_stock) + '</td>'
                    + '<td class="small text-right">' + num(r.qty_on_pr) + '</td>'
                    + '<td><input type="number" class="form-control form-control-sm text-right pull-qty" step="any" min="0" max="' + r.qty_pr + '" value="' + r.qty_pr + '" data-id="' + r.id + '"></td>'
                    + '</tr>';
            });
            $('#pullRows').html(html);
        }).fail(function () {
            $('#pullRows').html('<tr><td colspan="10" class="text-center text-danger py-3">Gagal memuat data.</td></tr>');
        });
    });

    $('#pullSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('#pullRows tr[data-search]').each(function () { $(this).toggle($(this).data('search').indexOf(q) !== -1); });
    });
    $('#pullCheckAll').on('change', function () {
        $('#pullRows tr[data-search]:visible .pull-check').prop('checked', this.checked);
        refreshInfo();
    });
    $(document).on('change', '#pullRows .pull-check', refreshInfo);

    $('#pullSubmit').on('click', function () {
        var data = {};
        $('#pullRows .pull-check:checked').each(function () {
            var id = $(this).val();
            var q = parseFloat($('#pullRows .pull-qty[data-id="' + id + '"]').val() || 0);
            if (q > 0) data['rows[' + id + ']'] = q;
        });
        if ($.isEmptyObject(data)) { notify(false, 'Isi qty > 0 pada baris yang dipilih.'); return; }
        var $btn = $(this).prop('disabled', true);
        $.post(PULL_URL, data, null, 'json').done(function (res) {
            if (res && res.success) {
                $('#pullSourceModal').modal('hide');
                if (window.Swal) Swal.fire('Berhasil', res.message, 'success').then(function () { location.reload(); });
                else { alert(res.message); location.reload(); }
            } else {
                notify(false, (res && res.message) || 'Gagal menarik item.');
                $btn.prop('disabled', false);
            }
        }).fail(function () { notify(false, 'Gagal menghubungi server.'); $btn.prop('disabled', false); });
    });
});
</script>
