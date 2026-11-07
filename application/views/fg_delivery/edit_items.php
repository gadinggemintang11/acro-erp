<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/*
 * Ubah barang pada pengiriman FG yang masih Draft.
 *
 * Dibuat menyusul create_detail.php: halaman hanya berisi barang yang memang
 * ada di pengiriman ini, penambahan lewat modal yang dikelompokkan per IPO.
 *
 * SATU HAL PENTING soal angka "sisa":
 * get_fg_ready_for_customer() mengurangi qty yang sudah masuk pengiriman lain
 * TERMASUK Draft -- artinya qty yang ADA DI PENGIRIMAN INI SENDIRI ikut
 * terpotong. Karena menyimpan berarti mengganti seluruh baris (insert_delivery
 * menghapus lalu menulis ulang), qty milik pengiriman ini harus DIKEMBALIKAN
 * dulu ke sisa. Tanpa itu, membuka Edit lalu menyimpan tanpa mengubah apa pun
 * bisa ditolak karena qty-nya dianggap melebihi sisa.
 *
 * Baris lama yang barangnya sudah tidak muncul di daftar siap kirim TETAP
 * ditampilkan, supaya tidak diam-diam hilang saat disimpan.
 */
$existing = $existing_details_map ?? [];

// Sisa per (sc_id, fg_id) dari daftar siap kirim + kembalikan qty milik dokumen ini.
$sisa_map = [];
foreach ($fg_ready as $f) {
    $k = $f->sc_id . '_' . $f->fg_id;
    $sisa_map[$k] = (float) $f->current_stock;
}
foreach ($existing as $d) {
    $k = ($d->sales_contract_id ?? '') . '_' . $d->fg_id;
    $sisa_map[$k] = ($sisa_map[$k] ?? 0) + (float) $d->qty_shipped;
}

$rows_by_ipo = [];
foreach ($fg_ready as $f) {
    $rows_by_ipo[$f->sc_number ?: '-'][] = $f;
}
$total_baris = count($fg_ready);
?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit mr-2"></i><?= lang_line('fgd_edit_detail_title', 'Edit Detail Pengiriman'); ?>
        </h1>
        <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-secondary btn-sm shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('fgd_btn_cancel', 'Batal'); ?>
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-alt mr-2"></i><?= lang_line('fgd_header_info', 'Informasi Pengiriman'); ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block"><?= lang_line('fgd_label_customer', 'Customer'); ?></small>
                    <strong><?= htmlspecialchars($header->nama_customer) ?></strong>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block"><?= lang_line('fgd_label_do_date', 'Tanggal DO'); ?></small>
                    <strong><?= htmlspecialchars($header->delivery_date) ?></strong>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block"><?= lang_line('fgd_label_address', 'Alamat Pengiriman'); ?></small>
                    <strong><?= htmlspecialchars($header->customer_address ?: '-') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <?= form_open('fg_delivery/update_items', ['id' => 'fgdForm']) ?>
    <input type="hidden" name="fg_delivery_header_id" value="<?= $header->id ?>">

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-truck-loading mr-2"></i><?= lang_line('fgd_picked_section', 'Barang yang Akan Dikirim'); ?>
                (<span id="fgdCount">0</span>)
            </h6>
            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#pickFgModal" <?= $total_baris ? '' : 'disabled'; ?>>
                <i class="fas fa-list mr-1"></i> <?= lang_line('fgd_btn_pick', 'Pilih Barang dari Sales Order'); ?>
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th style="width:130px;"><?= lang_line('fgd_th_sc', 'No. Sales Order'); ?></th>
                            <th style="width:140px;"><?= lang_line('fgd_th_wo', 'No. WO'); ?></th>
                            <th style="width:110px;"><?= lang_line('fgd_th_item_code', 'Kode FG'); ?></th>
                            <th><?= lang_line('fgd_th_item_name', 'Nama FG'); ?></th>
                            <th style="width:96px;"><?= lang_line('fgd_th_stock', 'Sisa'); ?></th>
                            <th style="width:120px;"><?= lang_line('fgd_th_qty_ship', 'Qty Kirim'); ?></th>
                            <th style="width:70px;"><?= lang_line('fgd_th_uom', 'Satuan'); ?></th>
                            <th style="width:96px;"><?= lang_line('fgd_th_nw', 'NW'); ?></th>
                            <th style="width:96px;"><?= lang_line('fgd_th_gw', 'GW'); ?></th>
                            <th style="width:180px;"><?= lang_line('fgd_th_notes', 'Catatan'); ?></th>
                            <th style="width:60px;"><?= lang_line('common_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="fgdPicked">
                        <?php foreach ($existing as $d):
                            $dedup = ($d->sales_contract_id ?? '') . '_' . $d->fg_id;
                            $key   = 'ex' . $d->id;
                            $max   = $sisa_map[$dedup] ?? (float) $d->qty_shipped;
                            $n     = 'details[' . $key . ']';
                            // Nomor WO tidak ada di baris pengiriman; ambil dari daftar
                            // siap kirim kalau kebetulan ada.
                            $wono = '-';
                            foreach ($fg_ready as $f) {
                                if (($f->sc_id . '_' . $f->fg_id) === $dedup) { $wono = $f->wo_number; break; }
                            }
                        ?>
                        <tr data-key="<?= $key ?>" data-dedup="<?= htmlspecialchars($dedup) ?>">
                            <td><?= htmlspecialchars($d->sc_number ?: '-') ?></td>
                            <td><?= htmlspecialchars($wono) ?></td>
                            <td><?= htmlspecialchars($d->kode_barang) ?></td>
                            <td>
                                <?= htmlspecialchars($d->nama_barang) ?>
                                <input type="hidden" name="<?= $n ?>[fg_id]" value="<?= (int) $d->fg_id ?>">
                                <input type="hidden" name="<?= $n ?>[sales_contract_id]" value="<?= htmlspecialchars($d->sales_contract_id ?? '') ?>">
                                <input type="hidden" name="<?= $n ?>[so_detail_id]" value="<?= htmlspecialchars($d->so_detail_id ?? '') ?>">
                                <input type="hidden" name="<?= $n ?>[uom]" value="<?= htmlspecialchars($d->uom ?? '') ?>">
                            </td>
                            <td class="text-right text-muted"><?= number_format($max, 2) ?></td>
                            <td>
                                <input type="number" name="<?= $n ?>[qty_shipped]" class="form-control form-control-sm text-right picked-qty"
                                       step="0.01" min="0.01" max="<?= $max ?>" value="<?= (float) $d->qty_shipped ?>" required>
                            </td>
                            <td class="text-center"><?= htmlspecialchars($d->uom ?? '-') ?></td>
                            <td><input type="number" step="0.0001" min="0" name="<?= $n ?>[nw]" class="form-control form-control-sm text-right"
                                       value="<?= isset($d->nw) && $d->nw !== null ? (float) $d->nw : '' ?>"></td>
                            <td><input type="number" step="0.0001" min="0" name="<?= $n ?>[gw]" class="form-control form-control-sm text-right"
                                       value="<?= isset($d->gw) && $d->gw !== null ? (float) $d->gw : '' ?>"></td>
                            <td><input type="text" name="<?= $n ?>[notes]" class="form-control form-control-sm" value="<?= htmlspecialchars($d->notes ?? '') ?>"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm fgd-del"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="fgdEmpty" style="<?= count($existing) ? 'display:none;' : '' ?>">
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                <?php if ($total_baris): ?>
                                    <?= lang_line('fgd_empty_pick', 'Belum ada barang dipilih. Klik "Pilih Barang dari Sales Order" di atas.'); ?>
                                <?php else: ?>
                                    <?= lang_line('fgd_none_ready', 'Tidak ada barang jadi siap kirim untuk customer ini. Pastikan Penerimaan Barang Jadi sudah disetujui.'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light text-right">
            <a href="<?= site_url('fg_delivery/show/'.$header->id) ?>" class="btn btn-secondary btn-sm mr-1"><?= lang_line('common_cancel', 'Batal'); ?></a>
            <button type="submit" class="btn btn-primary btn-sm" id="fgdSave">
                <i class="fas fa-save mr-1"></i> <?= lang_line('fgd_btn_update_items', 'Update Items'); ?>
            </button>
        </div>
    </div>

    <?= form_close(); ?>
</div>

<?php /* ── Modal: barang siap kirim, dikelompokkan per IPO ── */ ?>
<div class="modal fade" id="pickFgModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list mr-2"></i><?= lang_line('fgd_modal_title', 'Barang Jadi Siap Kirim'); ?>
                    — <?= htmlspecialchars($header->nama_customer) ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('fgd_modal_hint_edit', 'Sisa sudah memperhitungkan qty yang saat ini ada di pengiriman ini, jadi aman menambah atau mengurangi.'); ?>
                </p>
                <div class="form-row mb-2">
                    <div class="col-md-12">
                        <input type="text" id="fgdSearch" class="form-control form-control-sm"
                               placeholder="<?= lang_line('fgd_modal_search_ph', 'Cari No. Sales Order / No. WO / kode / nama barang...'); ?>">
                    </div>
                </div>
                <div class="table-responsive" style="max-height:54vh; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="fgdModalTable">
                        <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                            <tr class="text-center">
                                <th style="width:34px;"><input type="checkbox" id="fgdCheckAll"></th>
                                <th style="width:140px;"><?= lang_line('fgd_th_wo', 'No. WO'); ?></th>
                                <th style="width:150px;"><?= lang_line('fgd_th_receipt', 'No. Penerimaan'); ?></th>
                                <th style="width:110px;"><?= lang_line('fgd_th_item_code', 'Kode FG'); ?></th>
                                <th><?= lang_line('fgd_th_item_name', 'Nama FG'); ?></th>
                                <th style="width:90px;"><?= lang_line('fgd_th_received', 'Diterima'); ?></th>
                                <th style="width:100px;"><?= lang_line('fgd_th_shipped', 'Sudah Kirim'); ?></th>
                                <th style="width:90px;"><?= lang_line('fgd_th_stock', 'Sisa'); ?></th>
                                <th style="width:120px;"><?= lang_line('fgd_th_qty_ship', 'Qty Kirim'); ?></th>
                                <th style="width:70px;"><?= lang_line('fgd_th_uom', 'Satuan'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fg_ready)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-4">
                                    <?= lang_line('fgd_none_ready', 'Tidak ada barang jadi siap kirim untuk customer ini.'); ?>
                                </td></tr>
                            <?php else: foreach ($rows_by_ipo as $ipo => $rows): ?>
                                <tr class="table-primary fgd-group">
                                    <td colspan="10" class="font-weight-bold">
                                        <i class="fas fa-file-invoice mr-1"></i> Sales Order <?= htmlspecialchars($ipo) ?>
                                        <span class="badge badge-secondary ml-1"><?= count($rows) ?> barang</span>
                                    </td>
                                </tr>
                                <?php foreach ($rows as $f):
                                    $dedup = $f->sc_id . '_' . $f->fg_id;
                                    $key   = $f->sc_id . '_' . $f->wo_id . '_' . $f->fg_id;
                                    $maxv  = $sisa_map[$dedup] ?? (float) $f->current_stock;
                                    $hay   = strtolower(trim($f->sc_number . ' ' . $f->wo_number . ' ' . $f->kode_barang . ' ' . $f->nama_barang));
                                ?>
                                <tr class="fgd-row" data-hay="<?= htmlspecialchars($hay) ?>" data-ipo="<?= htmlspecialchars($ipo) ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="fgd-chk"
                                               data-key="<?= $key ?>"
                                               data-dedup="<?= htmlspecialchars($dedup) ?>"
                                               data-fg="<?= (int) $f->fg_id ?>"
                                               data-sc="<?= (int) $f->sc_id ?>"
                                               data-sod="<?= htmlspecialchars($f->so_detail_id ?? '') ?>"
                                               data-scno="<?= htmlspecialchars($f->sc_number) ?>"
                                               data-wono="<?= htmlspecialchars($f->wo_number) ?>"
                                               data-code="<?= htmlspecialchars($f->kode_barang) ?>"
                                               data-name="<?= htmlspecialchars($f->nama_barang) ?>"
                                               data-uom="<?= htmlspecialchars($f->satuan) ?>"
                                               data-max="<?= $maxv ?>">
                                    </td>
                                    <td><?= htmlspecialchars($f->wo_number) ?></td>
                                    <td><small><?= htmlspecialchars($f->receipt_number) ?></small></td>
                                    <td><?= htmlspecialchars($f->kode_barang) ?></td>
                                    <td><?= htmlspecialchars($f->nama_barang) ?></td>
                                    <td class="text-right"><?= number_format($f->total_received, 2) ?></td>
                                    <td class="text-right text-muted"><?= number_format($f->already_shipped, 2) ?></td>
                                    <td class="text-right font-weight-bold text-success"><?= number_format($maxv, 2) ?></td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm text-right fgd-qty"
                                               step="0.01" min="0" max="<?= $maxv ?>" value="<?= $maxv ?>">
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($f->satuan) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="fgdModalWarn" class="alert alert-warning py-2 small mt-2 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted"><strong id="fgdModalCount">0</strong> <?= lang_line('fgd_modal_selected', 'barang dipilih'); ?></small>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                    <button type="button" class="btn btn-primary" id="fgdApply" disabled>
                        <i class="fas fa-check mr-1"></i> <?= lang_line('fgd_btn_apply', 'Masukkan ke Pengiriman'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var I18N_FGD = {
        over:   <?= json_encode(lang_line('fgd_js_over', 'Qty kirim melebihi sisa pada')); ?>,
        remove: <?= json_encode(lang_line('fgd_js_remove', 'Hapus dari pengiriman')); ?>
    };

    function fmt(n) { return (Math.round((parseFloat(n) || 0) * 100) / 100).toLocaleString('id-ID'); }
    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function modalRefresh() {
        var $sel = $('.fgd-chk:checked');
        $('#fgdModalCount').text($sel.length);
        var lebih = [];
        $sel.each(function () {
            var qty = parseFloat($(this).closest('tr').find('.fgd-qty').val() || 0);
            if (qty > parseFloat($(this).data('max') || 0) + 0.001) lebih.push($(this).data('code'));
        });
        var $w = $('#fgdModalWarn');
        if (lebih.length) $w.text(I18N_FGD.over + ' ' + lebih.join(', ')).show(); else $w.hide();
        $('#fgdApply').prop('disabled', $sel.length === 0 || lebih.length > 0);
    }

    $('#fgdModalTable').on('change', '.fgd-chk', modalRefresh);
    $('#fgdModalTable').on('input', '.fgd-qty', modalRefresh);
    $('#fgdCheckAll').on('change', function () {
        $('.fgd-row:visible').find('.fgd-chk').prop('checked', $(this).is(':checked'));
        modalRefresh();
    });

    $('#fgdSearch').on('input', function () {
        var q = ($(this).val() || '').toLowerCase().trim();
        $('.fgd-row').each(function () {
            $(this).toggle(q === '' || ($(this).data('hay') || '').indexOf(q) !== -1);
        });
        $('.fgd-group').each(function () {
            var ipo = $(this).next('.fgd-row').data('ipo');
            $(this).toggle($('.fgd-row[data-ipo="' + ipo + '"]:visible').length > 0);
        });
    });

    $('#fgdApply').on('click', function () {
        $('.fgd-chk:checked').each(function () {
            var d   = $(this).data();
            var qty = parseFloat($(this).closest('tr').find('.fgd-qty').val() || 0);
            if (qty <= 0) return;

            // Barang yang sama (Sales Order + FG) sudah ada di tabel -> qty-nya ditimpa,
            // termasuk baris yang memang sudah tersimpan sebelumnya.
            var $ada = $('#fgdPicked tr[data-dedup="' + d.dedup + '"]');
            if ($ada.length) { $ada.find('.picked-qty').val(qty); return; }

            var n = 'details[' + d.key + ']';
            $('#fgdPicked').append(
                '<tr data-key="' + d.key + '" data-dedup="' + esc(d.dedup) + '">'
              +   '<td>' + esc(d.scno) + '</td><td>' + esc(d.wono) + '</td><td>' + esc(d.code) + '</td>'
              +   '<td>' + esc(d.name)
              +       '<input type="hidden" name="' + n + '[fg_id]" value="' + d.fg + '">'
              +       '<input type="hidden" name="' + n + '[sales_contract_id]" value="' + d.sc + '">'
              +       '<input type="hidden" name="' + n + '[so_detail_id]" value="' + esc(d.sod) + '">'
              +       '<input type="hidden" name="' + n + '[uom]" value="' + esc(d.uom) + '">'
              +   '</td>'
              +   '<td class="text-right text-muted">' + fmt(d.max) + '</td>'
              +   '<td><input type="number" name="' + n + '[qty_shipped]" class="form-control form-control-sm text-right picked-qty"'
              +       ' step="0.01" min="0.01" max="' + d.max + '" value="' + qty + '" required></td>'
              +   '<td class="text-center">' + esc(d.uom) + '</td>'
              +   '<td><input type="number" step="0.0001" min="0" name="' + n + '[nw]" class="form-control form-control-sm text-right"></td>'
              +   '<td><input type="number" step="0.0001" min="0" name="' + n + '[gw]" class="form-control form-control-sm text-right"></td>'
              +   '<td><input type="text" name="' + n + '[notes]" class="form-control form-control-sm"></td>'
              +   '<td class="text-center"><button type="button" class="btn btn-danger btn-sm fgd-del" title="' + esc(I18N_FGD.remove) + '">'
              +       '<i class="fas fa-trash"></i></button></td>'
              + '</tr>'
            );
        });
        $('.fgd-chk').prop('checked', false);
        $('#fgdCheckAll').prop('checked', false);
        modalRefresh();
        pickedRefresh();
        $('#pickFgModal').modal('hide');
    });

    $('#fgdPicked').on('click', '.fgd-del', function () {
        $(this).closest('tr').remove();
        pickedRefresh();
    });

    function pickedRefresh() {
        var n = $('#fgdPicked tr[data-key]').length;
        $('#fgdCount').text(n);
        $('#fgdEmpty').toggle(n === 0);
        // update_items menolak kalau tidak ada item sama sekali -- dicegah di sini.
        $('#fgdSave').prop('disabled', n === 0);
    }

    $('#fgdForm').on('submit', function (e) {
        var bad = [];
        $('#fgdPicked tr[data-key]').each(function () {
            var $q = $(this).find('.picked-qty');
            if (parseFloat($q.val() || 0) > parseFloat($q.attr('max')) + 0.001) {
                bad.push($(this).find('td').eq(2).text());
                $q.addClass('is-invalid');
            } else { $q.removeClass('is-invalid'); }
        });
        if (bad.length) {
            e.preventDefault();
            if (window.Swal) Swal.fire('', I18N_FGD.over + ' ' + bad.join(', '), 'warning');
            else alert(I18N_FGD.over + ' ' + bad.join(', '));
        }
    });

    pickedRefresh();
});
</script>
