<?php
/**
 * Skrip halaman tahap Proses Produksi (redesign 2026-11-08).
 * Semua pengikatan event di DOMContentLoaded: footer memuat jQuery KEDUA
 * beserta Bootstrap/Select2/SweetAlert/toastr, jadi plugin baru tersedia di
 * window.$ setelah footer selesai (lihat catatan di templates/footer.php).
 * Fungsi yang dipanggil dari onclick (initQcModal, initScrapDetailModal,
 * deleteScrapSource, openQcLink) sengaja global.
 */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<script>
var I18N_PP = {
    confirm_title:    <?= json_encode(lang_line('pp_sw_confirm_title')); ?>,
    delete_title:     <?= json_encode(lang_line('pp_sw_delete_title')); ?>,
    yes:              <?= json_encode(lang_line('pp_sw_yes')); ?>,
    yes_delete:       <?= json_encode(lang_line('pp_sw_yes_delete')); ?>,
    cancel:           <?= json_encode(lang_line('pp_sw_cancel')); ?>,
    delete_default:   <?= json_encode(lang_line('pp_sw_delete_default_text')); ?>,
    delete_scrap:     <?= json_encode(lang_line('pp_sw_delete_scrap_text')); ?>,
    add_scrap:        <?= json_encode(lang_line('pp_sw_add_scrap_text')); ?>,
    save_qc:          <?= json_encode(lang_line('pp_sw_save_qc_text')); ?>,
    saving:           <?= json_encode(lang_line('pp_sw_saving')); ?>,
    please_wait:      <?= json_encode(lang_line('pp_sw_please_wait')); ?>,
    invalid_title:    <?= json_encode(lang_line('pp_sw_invalid_title')); ?>,
    invalid_text:     <?= json_encode(lang_line('pp_sw_invalid_text')); ?>,
    qty_invalid_title:<?= json_encode(lang_line('pp_qty_invalid_title')); ?>,
    qty_invalid_text: <?= json_encode(lang_line('pp_qty_invalid_text')); ?>,
    qty_exceed_title: <?= json_encode(lang_line('pp_qty_exceed_title')); ?>,
    qty_exceed_max:   <?= json_encode(lang_line('pp2_qty_exceed_max', 'Qty melebihi sisa yang tersedia (maks. %s).')); ?>,
    time_order:       <?= json_encode(lang_line('pp2_time_order_err', 'Waktu selesai tidak boleh sebelum waktu mulai.')); ?>,
    mr_title:         <?= json_encode(lang_line('pp_mr_create_title')); ?>,
    mr_yes:           <?= json_encode(lang_line('pp_mr_create_yes')); ?>,
    mr_warn_semi:     <?= json_encode(lang_line('pp_mr_create_warn_semi')); ?>,
    mr_warn_default:  <?= json_encode(lang_line('pp_mr_create_warn_default')); ?>,
    mr_success:       <?= json_encode(lang_line('pp_mr_success_title')); ?>,
    mr_success_link:  <?= json_encode(lang_line('pp_mr_success_link')); ?>,
    mr_failed:        <?= json_encode(lang_line('pp_mr_failed')); ?>,
    mr_processing:    <?= json_encode(lang_line('pp_mr_processing')); ?>,
    err_title:        <?= json_encode(lang_line('pp_err_title')); ?>,
    err_network:      <?= json_encode(lang_line('pp_err_network')); ?>,
    err_network_mr:   <?= json_encode(lang_line('pp_err_network_mr')); ?>,
    err_delete:       <?= json_encode(lang_line('pp_err_delete')); ?>,
    err_save:         <?= json_encode(lang_line('pp_err_save')); ?>,
    err_load_scrap:   <?= json_encode(lang_line('pp_err_load_scrap')); ?>,
    err_load_prefix:  <?= json_encode(lang_line('pp_err_load_scrap_prefix')); ?>,
    no_scrap_source:  <?= json_encode(lang_line('pp_scrap_no_source')); ?>,
    locked_tooltip:   <?= json_encode(lang_line('pp_scrap_locked_tooltip')); ?>,
    cannot_delete_mr: <?= json_encode(lang_line('pp_scrap_cannot_delete')); ?>,
    no_input_avail:   <?= json_encode(lang_line('pp_scrap_no_input_available')); ?>,
    scrap_opt_select: <?= json_encode(lang_line('pp_scrap_opt_select')); ?>,
    create_mr_btn:    <?= json_encode(lang_line('pp_scrap_create_mr_btn')); ?>,
    photo_open_alert: <?= json_encode(lang_line('pp_qc_photo_open_alert')); ?>,
    search_semi:      <?= json_encode(lang_line('pp_opt_search_semi')); ?>,
    search_scrap:     <?= json_encode(lang_line('pp_opt_search_scrap')); ?>,
    size_title:       <?= json_encode(lang_line('pp_size_modal_title')); ?>,
    size_empty_title: <?= json_encode(lang_line('pp_size_no_data_title')); ?>,
    size_empty_text:  <?= json_encode(lang_line('pp_size_no_breakdown_text')); ?>,
    th_size:          <?= json_encode(lang_line('pp_th_size')); ?>,
    th_qty:           <?= json_encode(lang_line('pp_th_qty')); ?>,
    close:            <?= json_encode(lang_line('pp_btn_close')); ?>
};
var PP_URL = {
    save_qc:      <?= json_encode(base_url('production_process/save_qc_')); ?>,
    scrap_src:    <?= json_encode(base_url('production_process/ajax_get_scrap_sources')); ?>,
    save_scrap:   <?= json_encode(base_url('production_process/save_scrap_source')); ?>,
    del_scrap:    <?= json_encode(base_url('production_process/delete_scrap_source')); ?>,
    create_mr:    <?= json_encode(base_url('production_process/create_mr_from_scrap/')); ?>,
    mr_detail:    <?= json_encode(base_url('material_request/detail/')); ?>,
    search_semi:  <?= json_encode(base_url('production_process/search_semifinished')); ?>,
    search_scrap: <?= json_encode(base_url('production_process/search_scrap')); ?>
};

/** "1.234,5" / "1234,5" / "1234.5" -> 1234.5 (NaN kalau kosong). */
function ppNum(v) {
    var s = String(v == null ? '' : v).replace(/\s+/g, '');
    if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) s = s.replace(/\./g, '');
    return parseFloat(s.replace(/,/g, '.'));
}
function ppFmt(n) { n = parseFloat(n || 0); return (Math.round(n * 10000) / 10000).toLocaleString('id-ID', { maximumFractionDigits: 4 }); }
function ppEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
function ppLocalDT(d) {
    function p(x) { return (x < 10 ? '0' : '') + x; }
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
}

function openQcLink() {
    var link = ($('#qcPhotoUrl').val() || '').trim();
    if (!link) { Swal.fire({ icon: 'info', text: I18N_PP.photo_open_alert }); return; }
    window.open(link, '_blank', 'noopener');
}

/** Buka modal QC. viewOnly = tampilkan hasil QC yang sudah ada. */
function initQcModal(headerId, outputId, stage, viewOnly) {
    var $row = $('tr[data-output-id="' + outputId + '"]');
    $('#qcForm').attr('action', PP_URL.save_qc + stage + '/' + headerId + '/' + outputId);
    $('#qcItemName').text($.trim($row.find('.pp-item-name').first().text()) || '-');
    $('#qcItemQty').text($.trim($row.find('td.num').first().text()) || '-');
    if (viewOnly) {
        $('#qcSubmitBtn').hide();
        $('#qcDescription').prop('disabled', true).val($row.data('qc-description') || '');
        $('#qcPhotoUrl').prop('disabled', true).val($row.data('qc-photo') || '');
        $('#qcCheckedByNameText').text($row.data('qc-checked-by') || '-');
        $('#qcCheckedAt').text($row.data('qc-checked-at') || '-');
    } else {
        $('#qcSubmitBtn').show();
        $('#qcDescription').prop('disabled', false).val('');
        $('#qcPhotoUrl').prop('disabled', false).val('');
        $('#qcCheckedByNameText').text($('#qcForm').data('current-user') || '-');
        $('#qcCheckedAt').text(new Date().toLocaleString('id-ID'));
    }
    $('#qcModal').modal('show');
}

/** Modal Detail Scrap: asal scrap (material input) + qty & keterangan NG, buat MR. */
function initScrapDetailModal(headerId, outputId, stage) {
    fetch(PP_URL.scrap_src + '?output_id=' + encodeURIComponent(outputId) + '&stage=' + encodeURIComponent(stage), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function (data) {
            if (data.error) { Swal.fire(I18N_PP.err_title, data.error || I18N_PP.err_load_scrap, 'error'); return; }
            $('#scrapItemName').text(data.item_name || '-');
            $('#scrapQtyTotal').text(ppFmt(data.qty_scrap));
            $('#scrapSourceTotal').text(ppFmt(data.total_source_qty));

            var $tb = $('#scrapSourcesBody').empty();
            if (data.sources && data.sources.length) {
                data.sources.forEach(function (src) {
                    var type = src.item_type || '-';
                    var mrActive = src.mr_status && src.mr_status !== 'Draft' && src.mr_status !== 'Rejected';
                    var check = mrActive
                        ? '<span title="' + ppEsc(I18N_PP.locked_tooltip) + '" class="text-muted"><i class="fas fa-lock"></i></span>'
                        : '<input type="checkbox" class="scrap-src-check" value="' + src.id + '" data-item-type="' + ppEsc(type) + '">';
                    var mr = mrActive
                        ? '<br><a href="' + PP_URL.mr_detail + src.mr_header_id + '" target="_blank" class="badge badge-success font-weight-normal"><i class="fas fa-paper-plane"></i> ' + ppEsc(src.mr_number) + ' (' + ppEsc(src.mr_status) + ')</a>'
                        : '';
                    $tb.append('<tr>'
                        + '<td class="text-center">' + check + '</td>'
                        + '<td>' + ppEsc(src.source_item_name || 'Unknown') + mr + '</td>'
                        + '<td><span class="badge badge-light">' + ppEsc(type) + '</span></td>'
                        + '<td class="text-right">' + ppFmt(src.source_qty_input) + '</td>'
                        + '<td class="text-right font-weight-bold">' + ppFmt(src.source_qty) + '</td>'
                        + '<td><small class="text-muted">' + (src.notes ? ppEsc(src.notes) : '-') + '</small></td>'
                        + '<td class="text-right"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="deleteScrapSource(' + src.id + ',' + headerId + ',\'' + stage + '\')"'
                        + (mrActive ? ' disabled title="' + ppEsc(I18N_PP.cannot_delete_mr) + '"' : '') + '><i class="fas fa-trash-alt"></i></button></td>'
                        + '</tr>');
                });
            } else {
                $tb.append('<tr><td colspan="7" class="text-center text-muted py-3">' + ppEsc(I18N_PP.no_scrap_source) + '</td></tr>');
            }
            $('#btnCreateMrFromScrap').data('prod-id', headerId).prop('disabled', true);
            $('#scrapSourcesCheckAll').prop({ checked: false, indeterminate: false });

            var $sel = $('#sourceInputId').empty().append('<option value="">' + ppEsc(I18N_PP.scrap_opt_select) + '</option>');
            if (data.available_inputs && data.available_inputs.length) {
                data.available_inputs.forEach(function (inp) {
                    var rem = parseFloat(inp.remaining_qty || 0);
                    $sel.append('<option value="' + inp.input_id + '" data-max-qty="' + rem + '">'
                        + ppEsc((inp.item_name || 'Unknown') + (inp.item_code ? ' (' + inp.item_code + ')' : '')) + ' — ' + ppFmt(rem) + '</option>');
                });
            } else {
                $sel.append('<option value="" disabled>' + ppEsc(I18N_PP.no_input_avail) + '</option>');
            }
            $('#maxSourceQty').text('0');
            $('#addScrapSourceForm').attr('action', PP_URL.save_scrap + '/' + headerId + '/' + outputId + '/' + stage);
            $('#scrapDetailModal').data({ headerId: headerId, outputId: outputId, stage: stage });
            if (!$('#scrapDetailModal').hasClass('show')) $('#scrapDetailModal').modal('show');
        })
        .catch(function (err) { Swal.fire(I18N_PP.err_title, I18N_PP.err_load_prefix + err.message, 'error'); });
}

function deleteScrapSource(sourceId, headerId) {
    Swal.fire({ title: I18N_PP.delete_title, text: I18N_PP.delete_scrap, icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#e11d48', confirmButtonText: I18N_PP.yes_delete || I18N_PP.yes, cancelButtonText: I18N_PP.cancel })
    .then(function (res) {
        if (!res.isConfirmed) return;
        var ctx = $('#scrapDetailModal').data();
        $.ajax({ url: PP_URL.del_scrap + '/' + sourceId + '/' + headerId, type: 'POST', dataType: 'json' })
            .done(function (resp) {
                if (resp.status === 'success') { if (window.toastr) toastr.success(resp.message); initScrapDetailModal(ctx.headerId, ctx.outputId, ctx.stage); }
                else Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.err_delete, 'error');
            })
            .fail(function () { Swal.fire(I18N_PP.err_title, I18N_PP.err_network, 'error'); });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var hasS2 = !!($.fn && $.fn.select2);

    // ---------- Select2 ----------
    if (hasS2) {
        $('.pp-select').select2({ theme: 'bootstrap4', width: '100%' });
        function ajaxS2($el, url, type, ph) {
            $el.select2({
                theme: 'bootstrap4', width: '100%', placeholder: ph, minimumInputLength: 1, allowClear: false,
                ajax: { url: url, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; },
                    processResults: function (d) {
                        return { results: (d.results || []).map(function (it) {
                            if (typeof it.id === 'string' && it.id.indexOf('|') === -1) it.id += '|' + type;
                            return it;
                        }) };
                    } }
            });
        }
        ajaxS2($('.pp-select-semi'), PP_URL.search_semi, 'semifinished', I18N_PP.search_semi);
        ajaxS2($('.pp-select-scrap'), PP_URL.search_scrap, 'scrap', I18N_PP.search_scrap);
    }

    // ---------- Pilihan batch / hasil tahap sebelumnya -> item & sisa ----------
    $('.pp-qty-form[data-select]').each(function () {
        var $form = $(this), $sel = $($form.data('select')), $qty = $($form.data('qty'));
        $sel.on('change', function () {
            var $o = $sel.find('option:selected'), max = parseFloat($o.data('maxqty') || 0);
            $form.find('input[type=hidden][name=item_id]').val($o.data('itemid') || '');
            $form.find('input[type=hidden][name=item_type]').val($o.data('itemtype') || '');
            $form.find('.pp-max').text(ppFmt(max));
            $form.find('.pp-unit').text($o.data('unit') || '');
            $qty.data('max', $o.val() ? max : null).removeClass('is-invalid');
        }).trigger('change');
    });

    // ---------- Validasi & kirim form bahan / hasil ----------
    $('.pp-qty-form').on('submit', function (e) {
        var $form = $(this), $qty = $($form.data('qty')), ok = true;
        $form.find('select[required]').each(function () {
            var bad = !$(this).val();
            $(this).toggleClass('is-invalid', bad);
            $(this).next('.select2').find('.select2-selection').css('border-color', bad ? '#e11d48' : '');
            if (bad) ok = false;
        });
        var q = ppNum($qty.val()), max = $qty.data('max');
        if (isNaN(q) || q <= 0) { ok = false; $qty.addClass('is-invalid'); }
        if (!ok) { e.preventDefault(); Swal.fire({ icon: 'warning', title: I18N_PP.invalid_title, text: I18N_PP.invalid_text, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); return; }
        if (max !== null && max !== undefined && q > parseFloat(max) + 0.00001) {
            e.preventDefault(); $qty.addClass('is-invalid');
            Swal.fire({ icon: 'warning', title: I18N_PP.qty_exceed_title, text: I18N_PP.qty_exceed_max.replace('%s', ppFmt(max)) });
            return;
        }
        $qty.val(q); // kirim format titik desimal
        $form.find('select[name=item_id]').each(function () {
            var v = $(this).val();
            if (v && v.indexOf('|') === -1) $(this).append(new Option(v, v + '|' + ($(this).hasClass('pp-select-scrap') ? 'scrap' : 'semifinished'), true, true));
        });
        $form.find('button[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + I18N_PP.saving);
    });
    $(document).on('input', '.pp-qty-form input', function () { $(this).removeClass('is-invalid'); });

    // ---------- Konfirmasi hapus & aksi dokumen ----------
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        Swal.fire({ title: I18N_PP.delete_title, text: $(this).data('message') || I18N_PP.delete_default, icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#e11d48', confirmButtonText: I18N_PP.yes_delete || I18N_PP.yes, cancelButtonText: I18N_PP.cancel })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: I18N_PP.please_wait, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });
    $(document).on('click', '.pp-confirm-link', function (e) {
        e.preventDefault();
        var url = $(this).attr('href'), danger = !!$(this).data('danger');
        Swal.fire({ title: $(this).data('title') || I18N_PP.confirm_title, text: $(this).data('text') || '', icon: danger ? 'warning' : 'question', showCancelButton: true,
            confirmButtonColor: danger ? '#e11d48' : getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155',
            confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel })
        .then(function (r) { if (r.isConfirmed) { Swal.fire({ title: I18N_PP.please_wait, allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } }); window.location.href = url; } });
    });

    // ---------- Waktu proses ----------
    $(document).on('click', '.pp-now', function (e) {
        e.preventDefault();
        $($(this).data('target')).val(ppLocalDT(new Date())).trigger('change');
    });
    $('#ppStartedAt').on('change', function () { $('#ppFinishedAt').attr('min', $(this).val()); });
    $('#ppTimeForm').on('submit', function (e) {
        var s = $('#ppStartedAt').val(), f = $('#ppFinishedAt').val();
        if (s && f && f < s) { e.preventDefault(); Swal.fire({ icon: 'warning', text: I18N_PP.time_order }); return; }
        $(this).find('button[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + I18N_PP.saving);
    });

    // ---------- Rincian size (Finishing) ----------
    $(document).on('click', '.show-size-detail', function () {
        var sizes = [];
        try { sizes = JSON.parse(atob($(this).data('sizes') || '')); } catch (err) { sizes = []; }
        if (!Array.isArray(sizes) || !sizes.length) { Swal.fire({ icon: 'info', title: I18N_PP.size_empty_title, text: I18N_PP.size_empty_text }); return; }
        var html = '<table class="table table-sm table-bordered text-center mb-0"><thead><tr><th>' + ppEsc(I18N_PP.th_size) + '</th><th>' + ppEsc(I18N_PP.th_qty) + '</th></tr></thead><tbody>';
        sizes.forEach(function (s) { html += '<tr><td>' + ppEsc(s.size) + '</td><td>' + ppFmt(s.qty) + '</td></tr>'; });
        Swal.fire({ title: I18N_PP.size_title, html: '<div class="small text-muted mb-2">' + ppEsc($(this).data('item')) + '</div>' + html + '</tbody></table>', width: 420, confirmButtonText: I18N_PP.close });
    });

    // ---------- QC ----------
    $('#qcForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        Swal.fire({ title: I18N_PP.confirm_title, text: I18N_PP.save_qc, icon: 'question', showCancelButton: true,
            confirmButtonColor: '#10b981', confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel })
        .then(function (r) { if (r.isConfirmed) { if (window.showLoader) window.showLoader(); form.submit(); } });
    });

    // ---------- Asal scrap ----------
    $('#sourceInputId').on('change', function () {
        var max = parseFloat($(this).find('option:selected').data('max-qty') || 0);
        $('#sourceQty').attr('max', max).val('').trigger('input');
        $('#maxSourceQty').text(ppFmt(max));
    });
    $(document).on('change', '.scrap-src-check', function () {
        var n = $('.scrap-src-check:checked').length, total = $('.scrap-src-check').length;
        $('#btnCreateMrFromScrap').prop('disabled', n === 0);
        $('#scrapSourcesCheckAll').prop({ checked: n > 0 && n === total, indeterminate: n > 0 && n < total });
    });
    $('#scrapSourcesCheckAll').on('change', function () {
        $('.scrap-src-check').prop('checked', $(this).prop('checked')).first().trigger('change');
    });
    $('#btnCreateMrFromScrap').on('click', function () {
        var prodId = $(this).data('prod-id');
        var $checked = $('.scrap-src-check:checked');
        var ids = $checked.map(function () { return $(this).val(); }).get();
        if (!ids.length) return;
        var hasSemi = $checked.filter(function () { return $(this).data('item-type') === 'semifinished'; }).length > 0;
        Swal.fire({ title: I18N_PP.mr_title, html: hasSemi ? I18N_PP.mr_warn_semi : I18N_PP.mr_warn_default.replace('%d', ids.length),
            icon: hasSemi ? 'warning' : 'question', showCancelButton: true, confirmButtonText: I18N_PP.mr_yes, cancelButtonText: I18N_PP.cancel })
        .then(function (res) {
            if (!res.isConfirmed) return;
            var $btn = $('#btnCreateMrFromScrap').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + I18N_PP.mr_processing);
            $.ajax({ url: PP_URL.create_mr + prodId, type: 'POST', data: { 'scrap_source_ids[]': ids }, dataType: 'json' })
                .done(function (resp) {
                    if (resp.status === 'success') {
                        Swal.fire({ title: I18N_PP.mr_success, icon: 'success',
                            html: ppEsc(resp.message) + '<br><br><a href="' + PP_URL.mr_detail + resp.mr_id + '" target="_blank">' + ppEsc(I18N_PP.mr_success_link) + ' ' + ppEsc(resp.mr_number) + ' <i class="fas fa-external-link-alt"></i></a>' })
                        .then(function () { $('#scrapDetailModal').modal('hide'); });
                    } else Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.mr_failed, 'error');
                })
                .fail(function () { Swal.fire(I18N_PP.err_title, I18N_PP.err_network_mr, 'error'); })
                .always(function () { $btn.html('<i class="fas fa-paper-plane mr-1"></i>' + I18N_PP.create_mr_btn); });
        });
    });
    $('#addScrapSourceForm').on('submit', function (e) {
        e.preventDefault(); e.stopPropagation();
        var $form = $(this);
        Swal.fire({ title: I18N_PP.confirm_title, text: I18N_PP.add_scrap, icon: 'question', showCancelButton: true,
            confirmButtonColor: '#10b981', confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel })
        .then(function (res) {
            if (!res.isConfirmed) return;
            var ctx = $('#scrapDetailModal').data();
            $.ajax({ url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json' })
                .done(function (resp) {
                    if (resp.status === 'success') {
                        if (window.toastr) toastr.success(resp.message);
                        $form[0].reset();
                        initScrapDetailModal(ctx.headerId, ctx.outputId, ctx.stage);
                    } else Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.err_save, 'error');
                })
                .fail(function () { Swal.fire(I18N_PP.err_title, I18N_PP.err_network, 'error'); });
        });
    });

});
</script>
