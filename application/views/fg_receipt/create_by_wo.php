<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <!-- HEADER -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-plus-circle text-success mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('fgr_back_to_list', 'Kembali ke Daftar'); ?>
        </a>
    </div>

    <form action="<?= base_url('fg_receipt/store'); ?>" method="POST" id="form-fg-receipt">
        <!-- ================= HEADER CARD ================= -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-file-alt mr-1"></i> <?= lang_line('fgr_section_info', 'Informasi Penerimaan Barang Jadi'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="receipt_date"><?= lang_line('fgr_lbl_receipt_date', 'Tanggal Terima'); ?> <span class="text-danger">*</span></label>
                        <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-8 form-group">
                        <label for="fg_id_select"><?= lang_line('fgr_lbl_choose_fg', 'Pilih Barang Jadi (Model)'); ?> <span class="text-danger">*</span></label>
                        <select name="fg_id_select" id="fg_id_select" class="form-control select2-fg-receipt" required style="width:100%;">
                            <option value=""><?= lang_line('fgr_search_fg_ph', '-- Cari Kode / Nama Barang Jadi --'); ?></option>
                        </select>
                        <input type="hidden" name="fg_id" id="fg_id" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('fgr_lbl_fg_code', 'Kode FG'); ?></label>
                        <input type="text" id="info_fg_code" class="form-control" readonly value="-">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('fgr_lbl_fg_name', 'Nama FG'); ?></label>
                        <input type="text" id="info_fg_name" class="form-control" readonly value="-">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('fgr_lbl_remarks', 'Catatan / Remarks'); ?></label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="1"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= WO / Sales Order LIST ================= -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-clipboard-list mr-1"></i> <?= lang_line('fgr_section_wo_list', 'Daftar WO / Sales Order Siap Diterima'); ?></h6>
                <small class="text-white-50"><?= lang_line('fgr_wo_list_hint', 'Isi Qty Diterima > 0 untuk baris yang ingin diterima.'); ?></small>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="receipt-items-table">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="14%"><?= lang_line('fgr_th_wo_number', 'No. WO'); ?></th>
                                <th width="14%"><?= lang_line('fgr_th_ipo_number', 'No. Sales Order'); ?></th>
                                <th width="14%"><?= lang_line('fgr_th_production_no', 'No. Produksi'); ?></th>
                                <th width="9%"><?= lang_line('fgr_th_qty_remaining', 'Qty Sisa'); ?></th>
                                <th width="10%"><?= lang_line('fgr_th_qty_received', 'Qty Diterima'); ?></th>
                                <th width="7%"><?= lang_line('fgr_th_unit', 'Unit'); ?></th>
                                <th><?= lang_line('fgr_th_notes', 'Catatan'); ?></th>
                                <th width="7%"><?= lang_line('fgr_th_size', 'Size'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="empty-row">
                                <td colspan="8" class="text-center text-muted"><?= lang_line('fgr_no_items_select_fg', 'Pilih Barang Jadi (Model) terlebih dahulu untuk menampilkan daftar WO.'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success btn-load">
                    <i class="fas fa-save mr-1"></i> <?= lang_line('fgr_save_draft', 'Simpan Penerimaan (Draft)'); ?>
                </button>
                <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary"><?= lang_line('common_cancel', 'Batal'); ?></a>
            </div>
        </div>
    </form>
</div>

<!-- ================= MODAL TEMPATAN (DYNAMIC) ================= -->
<div id="dynamic-modals-container"></div>

<!-- ================= JS SECTION ================= -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .select2-container { width: 100% !important; }
    .wo-group-row { background-color: #f1f8e9; font-weight: 600; }
    .wo-group-row td { vertical-align: middle; }
</style>

<script>
$(document).ready(function() {

    $('.select2-fg-receipt').select2({
        placeholder: '<?= lang_line('fgr_search_fg_ph', '-- Cari Kode / Nama Barang Jadi --'); ?>',
        minimumInputLength: 1,
        ajax: {
            url: '<?= base_url("fg_receipt/ajax_search_fg"); ?>',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
            cache: true
        }
    }).on('select2:select', function(e) {
        const data = e.params.data;

        $('#fg_id').val(data.id);
        $('#info_fg_code').val(data.fg_code);
        $('#info_fg_name').val(data.fg_name);

        $('#receipt-items-table tbody').html('<tr><td colspan="8" class="text-center text-muted"><?= lang_line('fgr_loading_items', 'Memuat data...'); ?></td></tr>');
        $('#dynamic-modals-container').empty();

        $.get('<?= base_url("fg_receipt/ajax_get_receivable_wos_by_fg/"); ?>' + data.id, function(response) {
            $('#receipt-items-table tbody').empty();
            $('#dynamic-modals-container').empty();

            if (!response.success || !response.wos || response.wos.length === 0) {
                $('#receipt-items-table tbody').html('<tr><td colspan="8" class="text-center text-danger"><?= lang_line('fgr_no_wo_for_fg', 'Tidak ada WO yang punya output FG ini siap diterima.'); ?></td></tr>');
                return;
            }

            const outputsCountTpl = '<?= lang_line('fgr_outputs_count', '%d output siap diterima'); ?>';
            response.wos.forEach(function(wo) {
                const outputsLabel = outputsCountTpl.replace('%d', wo.outputs.length);
                // Group header row (WO + Sales Order + Production + total sisa)
                let groupRow = `
                    <tr class="wo-group-row">
                        <td>${escapeHtml(wo.wo_number || '-')}</td>
                        <td>${escapeHtml(wo.ipo_number || '-')}</td>
                        <td>${escapeHtml(wo.production_number || '-')}</td>
                        <td class="text-right">${parseFloat(wo.total_remaining || 0).toFixed(2)}</td>
                        <td colspan="4" class="text-muted small">${escapeHtml(outputsLabel)}</td>
                    </tr>`;
                $('#receipt-items-table tbody').append(groupRow);

                // Output rows
                wo.outputs.forEach(function(out) {
                    let sizeBtn = '<span class="text-muted">-</span>';
                    if (out.size_breakdown && out.size_breakdown !== 'null') {
                        let sizes = [];
                        try { sizes = JSON.parse(out.size_breakdown); } catch (e) { sizes = []; }
                        if (sizes.length > 0) {
                            let modalId = 'sizeModal_' + out.prod_output_id;
                            let rows = sizes.map(s => `<tr><td class="text-center">${escapeHtml(String(s.size))}</td><td class="text-center">${escapeHtml(String(s.qty))}</td></tr>`).join('');
                            sizeBtn = `<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#${modalId}">
                                            <i class="fas fa-ruler"></i> <?= lang_line('fgr_view', 'View'); ?>
                                       </button>`;
                            let modalHTML = `
                                <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white py-2">
                                                <h6 class="modal-title"><?= lang_line('fgr_modal_size_breakdown', 'Breakdown Size'); ?> - ${escapeHtml(wo.fg_name)}</h6>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body p-2">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="thead-light text-center">
                                                        <tr><th><?= lang_line('fgr_th_size', 'Size'); ?></th><th><?= lang_line('sc_qty', 'Qty'); ?></th></tr>
                                                    </thead>
                                                    <tbody>${rows}</tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            $('#dynamic-modals-container').append(modalHTML);
                        }
                    }

                    let idx = out.prod_output_id;
                    let row = `
                        <tr class="output-row">
                            <td class="text-muted small">└ ${escapeHtml(wo.wo_number)}</td>
                            <td class="text-muted small">${escapeHtml(wo.ipo_number || '-')}</td>
                            <td class="text-muted small">${escapeHtml(wo.production_number)}</td>
                            <td class="text-right">${parseFloat(out.remaining_qty).toFixed(2)}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="${out.remaining_qty}"
                                       name="details[${idx}][qty_received]"
                                       class="form-control form-control-sm text-right item-qty-received"
                                       value="0">
                                <input type="hidden" name="details[${idx}][prod_output_id]"       value="${out.prod_output_id}">
                                <input type="hidden" name="details[${idx}][wo_header_id]"         value="${wo.wo_header_id}">
                                <input type="hidden" name="details[${idx}][production_header_id]" value="${wo.production_header_id}">
                                <input type="hidden" name="details[${idx}][sales_contract_id]"    value="${wo.sales_contract_id || ''}">
                                <input type="hidden" name="details[${idx}][fg_id]"                value="${wo.fg_id}">
                                <input type="hidden" name="details[${idx}][uom]"                  value="${escapeHtml(wo.uom || '')}">
                            </td>
                            <td class="text-center">${escapeHtml(wo.uom || '-')}</td>
                            <td>
                                <input type="text" name="details[${idx}][notes]" class="form-control form-control-sm">
                            </td>
                            <td class="text-center">${sizeBtn}</td>
                        </tr>`;
                    $('#receipt-items-table tbody').append(row);
                });
            });
        }, 'json').fail(function() {
            $('#receipt-items-table tbody').html('<tr><td colspan="8" class="text-center text-danger"><?= lang_line('fgr_load_failed', 'Gagal memuat data item.'); ?></td></tr>');
        });
    });

    $('#receipt-items-table').on('input', '.item-qty-received', function() {
        const maxQty = parseFloat($(this).attr('max')) || 0;
        const val = parseFloat($(this).val()) || 0;
        if (val > maxQty) {
            alert('<?= lang_line('fgr_qty_exceeds', 'Qty diterima tidak boleh melebihi Qty Sisa (%s)'); ?>'.replace('%s', maxQty.toFixed(2)));
            $(this).val(maxQty.toFixed(2));
        }
        if (val < 0) $(this).val(0);
    });

    $('#form-fg-receipt').on('submit', function(e) {
        let hasQty = false;
        $('.item-qty-received').each(function() {
            if (parseFloat($(this).val()) > 0) { hasQty = true; return false; }
        });
        if (!hasQty) {
            e.preventDefault();
            alert('<?= lang_line('fgr_min_one_qty', 'Minimal 1 baris harus diisi Qty Diterima > 0.'); ?>');
        }
    });

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});
</script>
