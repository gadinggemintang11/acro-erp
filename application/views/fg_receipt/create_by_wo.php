<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <!-- HEADER -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-plus-circle text-success mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
        </a>
    </div>

    <form action="<?= base_url('fg_receipt/store'); ?>" method="POST" id="form-fg-receipt">
        <!-- ================= HEADER CARD ================= -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-file-alt mr-1"></i> Informasi Penerimaan Barang Jadi</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="receipt_date">Tanggal Terima <span class="text-danger">*</span></label>
                        <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-8 form-group">
                        <label for="wo_header_id_select">Pilih Work Order <span class="text-danger">*</span></label>
                        <select name="wo_header_id_select" id="wo_header_id_select" class="form-control select2-wo-receipt" required style="width:100%;">
                            <option value="">-- Cari No Work Order --</option>
                        </select>
                        <input type="hidden" name="wo_header_id" id="wo_header_id">
                        <input type="hidden" name="production_header_id" id="production_header_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>No. Produksi</label>
                        <input type="text" id="info_production_number" class="form-control" readonly value="-">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>No. Sales Contract</label>
                        <input type="text" id="info_sc_number" class="form-control" readonly value="-">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Catatan / Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="1"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= DETAIL ITEM ================= -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-box-open mr-1"></i> Item Barang Jadi Siap Diterima</h6>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="receipt-items-table">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>Item FG</th>
                                <th width="12%">Qty Sisa</th>
                                <th width="12%">Qty Diterima</th>
                                <th width="8%">Unit</th>
                                <th width="20%">Catatan</th>
                                <th width="8%">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="empty-row">
                                <td colspan="6" class="text-center text-muted">Pilih Work Order terlebih dahulu untuk menampilkan item.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success btn-load">
                    <i class="fas fa-save mr-1"></i> Simpan Penerimaan (Draft)
                </button>
                <a href="<?= base_url('fg_receipt'); ?>" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </form>
</div>

<!-- ================= MODAL TEMPATAN (DYNAMIC) ================= -->
<div id="dynamic-modals-container"></div>

<!-- ================= JS SECTION ================= -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>.select2-container { width: 100% !important; }</style>

<script>
$(document).ready(function() {

    // ========== SELECT2 Work Order ==========
    $('.select2-wo-receipt').select2({
        placeholder: '-- Cari No Work Order --',
        minimumInputLength: 2,
        ajax: {
            url: '<?= base_url("fg_receipt/ajax_search_wo"); ?>',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
            cache: true
        }
    }).on('select2:select', function(e) {
        const data = e.params.data;

        $('#wo_header_id').val(data.id);
        $('#production_header_id').val(data.production_header_id);
        $('#info_production_number').val(data.production_number);
        $('#info_sc_number').val(data.sc_number);

        // Kosongkan tabel dan modal sebelumnya
        $('#receipt-items-table tbody').html('<tr><td colspan="6" class="text-center text-muted">Memuat item...</td></tr>');
        $('#dynamic-modals-container').empty();

        // ========== Load Item by AJAX ==========
        $.get('<?= base_url("fg_receipt/ajax_get_receivable_items/"); ?>' + data.production_header_id, function(response) {
            $('#receipt-items-table tbody').empty();
            $('#dynamic-modals-container').empty();

            if (response.success && response.items.length > 0) {
                $.each(response.items, function(index, item) {

                    // ========== Siapkan tombol & modal size breakdown ==========
                    let sizeBtn = '<span class="text-muted">-</span>';

                    if (item.size_breakdown && item.size_breakdown !== 'null') {
                        let sizes = [];
                        try {
                            sizes = JSON.parse(item.size_breakdown);
                        } catch(e) { sizes = []; }

                        if (sizes.length > 0) {
                            let modalId = 'sizeModal_' + item.prod_output_id;
                            let rows = sizes.map(s => `<tr><td class="text-center">${s.size}</td><td class="text-center">${s.qty}</td></tr>`).join('');

                            // Tombol View
                            sizeBtn = `<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#${modalId}">
                                            <i class="fas fa-ruler"></i> View
                                        </button>`;

                            // Modal HTML
                            let modalHTML = `
                                <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white py-2">
                                                <h6 class="modal-title">Breakdown Size - ${item.fg_name}</h6>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body p-2">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead class="thead-light text-center">
                                                        <tr><th>Size</th><th>Qty</th></tr>
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

                    // ========== Row Tabel Item ==========
                    let newRow = `
                        <tr>
                            <td class="align-middle">
                                ${item.fg_name} (${item.fg_code})
                                <input type="hidden" name="details[${item.prod_output_id}][fg_id]" value="${item.fg_id}">
                                <input type="hidden" name="details[${item.prod_output_id}][uom]" value="${item.uom}">
                            </td>
                            <td class="text-right align-middle">${parseFloat(item.remaining_qty).toFixed(2)}</td>
                            <td class="align-middle">
                                <input type="number" step="0.01" min="0" max="${item.remaining_qty}"
                                       name="details[${item.prod_output_id}][qty_received]"
                                       class="form-control form-control-sm text-right item-qty-received"
                                       value="0" required>
                            </td>
                            <td class="text-center align-middle">${item.uom}</td>
                            <td class="align-middle">
                                <input type="text" name="details[${item.prod_output_id}][notes]" class="form-control form-control-sm">
                            </td>
                            <td class="text-center align-middle">${sizeBtn}</td>
                        </tr>
                    `;
                    $('#receipt-items-table tbody').append(newRow);
                });
            } else {
                $('#receipt-items-table tbody').html('<tr><td colspan="6" class="text-center text-danger">Tidak ada item FG siap diterima.</td></tr>');
            }
        }, 'json').fail(function() {
            $('#receipt-items-table tbody').html('<tr><td colspan="6" class="text-center text-danger">Gagal memuat data item.</td></tr>');
        });
    });

    // ========== Validasi Qty ==========
    $('#receipt-items-table').on('input', '.item-qty-received', function() {
        const maxQty = parseFloat($(this).attr('max')) || 0;
        const val = parseFloat($(this).val()) || 0;

        if (val > maxQty) {
            alert('Qty diterima tidak boleh melebihi Qty Sisa (' + maxQty.toFixed(2) + ')');
            $(this).val(maxQty.toFixed(2));
        }
        if (val < 0) $(this).val(0);
    });
});
</script>
