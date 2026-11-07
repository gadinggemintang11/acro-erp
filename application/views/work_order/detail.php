<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?>: <?= htmlspecialchars($header->wo_number); ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('work_order_detail_title', 'Detail Work Order'); ?></h6>
            <div>
                <?php if ($header->status == 'Planned'): ?>
                    <a href="<?= base_url('work_order/confirm_wo/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm('<?= lang_line('work_order_confirm_msg', 'Yakin konfirmasi Work Order ini? Status akan diubah menjadi In Progress.'); ?>')">
                        <i class="fas fa-check"></i> <?= lang_line('work_order_confirm_btn', 'Konfirmasi WO'); ?>
                    </a>
                <?php elseif ($header->status == 'In Progress'): ?>
                    <a href="<?= base_url('work_order/unconfirm_wo/' . $header->id); ?>" class="btn btn-warning btn-sm" onclick="return confirm('<?= lang_line('work_order_unconfirm_msg', 'Yakin batalkan konfirmasi? Status akan kembali ke Planned.'); ?>')">
                        <i class="fas fa-times"></i> <?= lang_line('work_order_unconfirm_btn', 'Batalkan Konfirmasi'); ?>
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('work_order/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('work_order_export_pdf', 'Export PDF'); ?>
                </a>
            </div>
        </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-6">
                    <p><strong><?= lang_line('work_order_lbl_ipo', 'Sales Order Number'); ?>:</strong> <?= htmlspecialchars($header->sc_number); ?></p>
                    <p><strong><?= lang_line('work_order_lbl_customer', 'Customer'); ?>:</strong> <?= htmlspecialchars($header->nama_customer); ?></p>
                    <p><strong><?= lang_line('work_order_lbl_fg', 'Finished Good'); ?>:</strong> <?= htmlspecialchars($header->fg_name); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><?= lang_line('work_order_qty_plan', 'Qty Rencana'); ?>:</strong> <?= number_format($header->planned_qty, 2); ?></p>
                    <p><strong><?= lang_line('work_order_start_date', 'Tgl Mulai'); ?>:</strong> <?= date('d-m-Y', strtotime($header->planned_start_date)); ?></p>
                    <p><strong><?= lang_line('label_status', 'Status'); ?>:</strong>
                        <span class="badge badge-<?= ($header->status == 'Planned') ? 'info' : 'success'; ?>">
                            <?= htmlspecialchars($header->status); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info"><?= lang_line('work_order_bom_ref_title', 'Referensi Bill of Materials (BOM)'); ?></h6>
        </div>
        <div class="card-body">
            <p class="text-muted small"><?= sprintf(lang_line('work_order_bom_ref_desc', 'Ini adalah daftar material referensi dari BOM untuk <strong>%s</strong>. Gunakan ini sebagai panduan saat menambahkan material di bawah.'), htmlspecialchars($header->fg_name)); ?></p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px;"><?= lang_line('label_no', 'No'); ?></th>
                            <th><?= lang_line('work_order_th_component_code', 'Kode Komponen'); ?></th>
                            <th><?= lang_line('work_order_th_component_name', 'Nama Komponen'); ?></th>
                            <th><?= lang_line('work_order_th_type', 'Tipe'); ?></th>
                            <th><?= lang_line('work_order_th_qty_per_fg', 'Qty (per 1 FG)'); ?></th>
                            <th><?= lang_line('work_order_th_unit', 'Satuan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bom_components)): ?>
                            <?php foreach ($bom_components as $i => $bom): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1; ?></td>
                                <td><?= htmlspecialchars($bom->item_code); ?></td>
                                <td><?= htmlspecialchars($bom->item_name); ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($bom->item_type); ?></span></td>
                                <td><?= number_format($bom->quantity_required, 4); ?></td>
                                <td><?= htmlspecialchars($bom->unit_measure); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center"><?= lang_line('work_order_no_bom', 'Tidak ada data BOM aktif yang ditemukan untuk Finished Good ini.'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('work_order_material_need_title', 'Kebutuhan Material (Rencana WO)'); ?></h6>
            <?php /*
                Dulu tombol ini hanya muncul kalau material WO masih KOSONG,
                karena "Load from BOM" menarik seluruh BOM sekaligus. Sekarang
                material bisa dipilih sebagian, jadi tombolnya tetap tersedia
                selama WO masih Planned — untuk menarik sisanya kemudian.
            */ ?>
            <?php if($header->status == 'Planned' && !empty($bom_components)): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btn-load-bom" data-wo-id="<?= $header->id; ?>" data-fg-id="<?= $header->fg_id; ?>" data-planned-qty="<?= $header->planned_qty; ?>">
                <i class="fas fa-upload"></i> <?= lang_line('work_order_load_from_bom', 'Load from BOM'); ?>
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">

            <?php if($header->status == 'Planned'): ?>
            <form action="<?= base_url('work_order/add_material/' . $header->id); ?>" method="POST" class="form-inline mb-3">
                <div class="form-group mr-2 flex-grow-1">
                    <label for="select-component" class="sr-only"><?= lang_line('work_order_lbl_component', 'Komponen'); ?></label>
                    <select class="form-control" id="select-component" name="component_item" required style="width: 100%;"></select>
                </div>
                <div class="form-group mr-2">
                    <input type="number" step="0.01" class="form-control" name="planned_qty" placeholder="<?= lang_line('sc_qty', 'Qty'); ?>" required>
                </div>
                <button type="submit" class="btn btn-success"><?= lang_line('work_order_btn_add', 'Tambah'); ?></button>
            </form>
            <hr>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:50px;"><?= lang_line('label_no', 'No'); ?></th>
                            <th><?= lang_line('work_order_th_code', 'Kode'); ?></th>
                            <th><?= lang_line('work_order_th_component_name', 'Nama Komponen'); ?></th>
                            <th><?= lang_line('work_order_th_type', 'Tipe'); ?></th>
                            <th><?= lang_line('work_order_th_qty_plan', 'Qty Rencana'); ?></th>
                            <th><?= lang_line('work_order_th_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($planned_materials)): ?>
                            <?php foreach ($planned_materials as $i => $mat): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1; ?></td>
                                <td><?= htmlspecialchars($mat->item_code); ?></td>
                                <td><?= htmlspecialchars($mat->item_name); ?></td>
                                <td><?= htmlspecialchars($mat->item_type); ?></td>
                                <td><?= number_format($mat->planned_qty, 4); ?></td>
                                <td><?= htmlspecialchars($mat->unit_measure); ?></td>
                                <td>
                                    <?php if($header->status == 'Planned'): ?>
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editMaterialModal"
                                            onclick="editMaterial(<?= $mat->id; ?>, <?= $mat->planned_qty; ?>, '<?= htmlspecialchars($mat->item_name); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="<?= base_url('work_order/delete_material/' . $header->id . '/' . $mat->id); ?>"
                                       class="btn btn-danger btn-sm" onclick="return confirm('<?= lang_line('work_order_confirm_del_material', 'Yakin hapus material ini?'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="7" class="text-center"><?= lang_line('work_order_no_planned_material', 'Belum ada material yang direncanakan.'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Material Qty -->
<div class="modal fade" id="editMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('work_order_edit_material_title', 'Edit Material Quantity'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editMaterialForm">
                    <input type="hidden" id="editMaterialId" name="detail_id">
                    <input type="hidden" id="editWoId" name="wo_id" value="<?= $header->id; ?>">

                    <div class="form-group">
                        <label><?= lang_line('work_order_lbl_material', 'Material'); ?></label>
                        <p id="editMaterialName" class="form-control-plaintext font-weight-bold">-</p>
                    </div>

                    <div class="form-group">
                        <label for="editQty"><?= lang_line('work_order_th_qty_plan', 'Qty Rencana'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="editQty" name="planned_qty" step="0.01" min="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveMaterialEdit()">
                    <i class="fas fa-save"></i> <?= lang_line('work_order_save_changes', 'Simpan Perubahan'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview BOM sebelum load material -->
<div class="modal fade" id="modalBomPreview" tabindex="-1" role="dialog" aria-labelledby="modalBomPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBomPreviewLabel"><?= lang_line('work_order_load_materials_from_bom', 'Load Materials from BOM'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('work_order_bom_modal_hint', 'Centang material yang mau direncanakan. Qty usulan = Qty/FG &times; Qty Rencana WO, dan masih bisa diubah. Material yang sudah direncanakan di WO ini tidak bisa dicentang lagi. Permintaan barang ke gudang dilakukan nanti lewat Material Request.'); ?>
                </p>
                <div class="table-responsive" style="max-height:54vh; overflow-y:auto;">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                            <tr class="text-center">
                                <th style="width:34px;"><input type="checkbox" id="bomCheckAll" title="<?= lang_line('common_select_all', 'Pilih semua'); ?>"></th>
                                <th><?= lang_line('work_order_lbl_component', 'Komponen'); ?></th>
                                <th style="width:90px;"><?= lang_line('work_order_th_qty_fg', 'Qty/FG'); ?></th>
                                <th style="width:100px;"><?= lang_line('work_order_bom_th_suggested', 'Qty Usulan'); ?></th>
                                <th style="width:120px;"><?= lang_line('work_order_bom_th_qty_planned', 'Qty Direncanakan'); ?></th>
                                <th style="width:70px;"><?= lang_line('work_order_th_unit', 'Satuan'); ?></th>
                                <th style="width:100px;"><?= lang_line('work_order_bom_th_stock', 'Stok'); ?></th>
                                <th style="width:110px;"><?= lang_line('work_order_bom_th_status', 'Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="bomBody" class="small">
                            <tr><td colspan="8" class="text-center text-muted py-4"><?= lang_line('common_loading', 'Memuat…'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="bomStockWarn" class="alert alert-warning py-2 small mt-2 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted"><strong id="bomCount">0</strong> <?= lang_line('work_order_bom_selected', 'material dipilih'); ?></small>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-load" disabled>
                        <i class="fas fa-check mr-1"></i> <?= lang_line('work_order_load_material_btn', 'Load Material'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#select-component').select2({
        placeholder: '<?= lang_line('work_order_search_material', 'Cari Material / Semi-Finished...'); ?>',
        width: '100%',
        ajax: {
            url: "<?= base_url('work_order/ajax_search_components'); ?>",
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // =====================================================================
    // Modal "Load from BOM"
    //
    // Dulu modal ini hanya pratinjau: seluruh BOM ditarik sekaligus, qty tidak
    // bisa diubah, dan tombolnya hilang begitu WO punya material (user
    // 2026-09-30: "sebaiknya kasih checkbox di modal nya dan informasinya
    // mungkin bisa di tambahi dan qty mungkin bisa di edit").
    //
    // Sekarang: pilih per baris, qty bisa diubah, ditambah kolom stok & status.
    // Material yang sudah ada di WO ini dikunci supaya tidak dobel, sehingga
    // modal boleh dibuka lagi untuk menarik sisanya.
    // =====================================================================
    var I18N_BOM = {
        loading:   <?= json_encode(lang_line('common_loading', 'Memuat…')); ?>,
        failed:    <?= json_encode(lang_line('work_order_alert_no_bom', 'Gagal mengambil data BOM')); ?>,
        added:     <?= json_encode(lang_line('work_order_bom_already', 'Sudah direncanakan')); ?>,
        avail:     <?= json_encode(lang_line('work_order_bom_new', 'Belum direncanakan')); ?>,
        no_stock:  <?= json_encode(lang_line('work_order_bom_stock_short', 'Stok saat ini kurang dari qty yang direncanakan')); ?>,
        warn_pre:  <?= json_encode(lang_line('work_order_bom_stock_warn', 'Stok saat ini tidak mencukupi untuk')); ?>,
        warn_post: <?= json_encode(lang_line('work_order_bom_stock_warn_post', 'material. Ini hanya perencanaan — rencana tetap bisa disimpan, kekurangannya diminta nanti lewat Material Request.')); ?>,
        none_sel:  <?= json_encode(lang_line('work_order_bom_none_selected', 'Belum ada material yang dicentang.')); ?>
    };

    function bomFmt(n) {
        n = parseFloat(n || 0);
        return (Math.round(n * 10000) / 10000).toLocaleString('id-ID');
    }
    function bomEsc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function bomRefreshState() {
        var $rows = $('#bomBody .bom-check:checked');
        $('#bomCount').text($rows.length);
        $('#btn-confirm-load').prop('disabled', $rows.length === 0);

        // Peringatan stok dihitung dari qty yang BENAR-BENAR dicentang.
        var kurang = 0;
        $rows.each(function () {
            var $tr  = $(this).closest('tr');
            var qty  = parseFloat($tr.find('.bom-qty').val() || 0);
            var stok = parseFloat($tr.data('stock') || 0);
            if (qty > stok + 0.0001) kurang++;
        });
        var $warn = $('#bomStockWarn');
        if (kurang > 0) {
            $warn.text(I18N_BOM.warn_pre + ' ' + kurang + ' ' + I18N_BOM.warn_post).show();
        } else {
            $warn.hide();
        }
    }

    $('#btn-load-bom').on('click', function (e) {
        e.preventDefault();
        var woId = $(this).data('wo-id');

        $('#bomBody').html('<tr><td colspan="8" class="text-center text-muted py-4">' + I18N_BOM.loading + '</td></tr>');
        $('#bomCheckAll').prop('checked', false);
        $('#bomStockWarn').hide();
        $('#btn-confirm-load').data('wo-id', woId).prop('disabled', true);
        $('#modalBomPreview').modal('show');

        $.getJSON("<?= base_url('work_order/ajax_get_bom_preview'); ?>", {
            wo_id:       woId,
            fg_id:       $(this).data('fg-id'),
            planned_qty: $(this).data('planned-qty')
        }).done(function (res) {
            if (!res || !res.success) {
                $('#bomBody').html('<tr><td colspan="8" class="text-center text-danger py-4">' +
                    bomEsc((res && res.message) || I18N_BOM.failed) + '</td></tr>');
                return;
            }

            var html = '';
            res.materials.forEach(function (m) {
                var sudah  = m.already_qty > 0.0001;
                var kurang = !sudah && m.suggested_qty > m.stock_qty + 0.0001;

                html += '<tr' + (sudah ? ' class="table-secondary text-muted"' : '') +
                        ' data-stock="' + m.stock_qty + '">'
                     +  '<td class="text-center">'
                     +      '<input type="checkbox" class="bom-check"' + (sudah ? ' disabled' : ' checked') +
                            ' data-id="' + m.item_id + '" data-type="' + bomEsc(m.item_type) + '"></td>'
                     +  '<td><strong>' + bomEsc(m.item_code) + '</strong><br>' + bomEsc(m.item_name)
                     +      '<br><small class="text-muted">(' + bomEsc(m.item_type) + ')</small></td>'
                     +  '<td class="text-right">' + bomFmt(m.qty_per_fg) + '</td>'
                     +  '<td class="text-right">' + bomFmt(m.suggested_qty) + '</td>'
                     +  '<td>'
                     +      (sudah
                            ? '<span class="text-muted">' + bomFmt(m.already_qty) + '</span>'
                            : '<input type="number" step="0.0001" min="0" class="form-control form-control-sm text-right bom-qty" value="' + m.suggested_qty + '">')
                     +  '</td>'
                     +  '<td class="text-center">' + bomEsc(m.unit_measure) + '</td>'
                     +  '<td class="text-right ' + (kurang ? 'text-danger font-weight-bold' : '') + '"'
                     +      (kurang ? ' title="' + bomEsc(I18N_BOM.no_stock) + '"' : '') + '>'
                     +      bomFmt(m.stock_qty) + '</td>'
                     +  '<td class="text-center">'
                     +      (sudah
                            ? '<span class="badge badge-secondary">' + I18N_BOM.added + '</span>'
                            : '<span class="badge badge-success">' + I18N_BOM.avail + '</span>')
                     +  '</td>'
                     +  '</tr>';
            });
            $('#bomBody').html(html);
            $('#bomCheckAll').prop('checked', $('#bomBody .bom-check:not(:disabled)').length > 0);
            bomRefreshState();
        }).fail(function () {
            $('#bomBody').html('<tr><td colspan="8" class="text-center text-danger py-4">' + I18N_BOM.failed + '</td></tr>');
        });
    });

    // Centang semua hanya mengenai baris yang memang bisa dicentang.
    $('#bomCheckAll').on('change', function () {
        $('#bomBody .bom-check:not(:disabled)').prop('checked', $(this).is(':checked'));
        bomRefreshState();
    });
    $('#bomBody').on('change', '.bom-check', bomRefreshState);
    $('#bomBody').on('input', '.bom-qty', bomRefreshState);

    $('#btn-confirm-load').on('click', function () {
        var woId  = $(this).data('wo-id');
        var items = [];

        $('#bomBody .bom-check:checked').each(function () {
            var $tr = $(this).closest('tr');
            var qty = parseFloat($tr.find('.bom-qty').val() || 0);
            if (qty > 0) {
                items.push({
                    item_id:   $(this).data('id'),
                    item_type: $(this).data('type'),
                    qty:       qty
                });
            }
        });

        if (!items.length) {
            alert('⚠️ ' + I18N_BOM.none_sel);
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $.post("<?= base_url('work_order/ajax_add_materials_from_bom'); ?>",
               { wo_id: woId, items: items }, null, 'json')
            .done(function (res) {
                if (res && res.success) {
                    location.reload();
                } else {
                    alert('❌ ' + ((res && res.message) || I18N_BOM.failed));
                    $btn.prop('disabled', false);
                }
            })
            .fail(function () {
                alert('❌ ' + I18N_BOM.failed);
                $btn.prop('disabled', false);
            });
    });
});

function editMaterial(materialId, currentQty, materialName) {
    document.getElementById('editMaterialId').value = materialId;
    document.getElementById('editQty').value = currentQty;
    document.getElementById('editMaterialName').textContent = materialName;
}

function saveMaterialEdit() {
    const detailId = document.getElementById('editMaterialId').value;
    const woId = document.getElementById('editWoId').value;
    const newQty = document.getElementById('editQty').value;

    if (!detailId) {
        alert('⚠️ <?= lang_line('work_order_alert_material_id_not_found', 'Material ID tidak ditemukan'); ?>');
        return;
    }

    if (!newQty || parseFloat(newQty) <= 0) {
        alert('⚠️ <?= lang_line('work_order_alert_qty_gt_zero', 'Qty harus lebih besar dari 0'); ?>');
        return;
    }

    $.ajax({
        url: "<?= base_url('work_order/update_material'); ?>",
        type: 'POST',
        dataType: 'json',
        data: {
            wo_id: woId,
            detail_id: detailId,
            planned_qty: newQty
        },
        success: function(response) {
            if(response && response.success) {
                alert('✅ <?= lang_line('work_order_alert_material_updated', 'Material berhasil diupdate'); ?>');
                $('#editMaterialModal').modal('hide');
                location.reload();
            } else {
                alert('❌ ' + (response.message || '<?= lang_line('work_order_alert_update_failed', 'Update gagal'); ?>'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', { status, error, xhr });
            alert('❌ <?= lang_line('work_order_alert_update_failed_console', 'Gagal update material. Lihat console untuk detail.'); ?>');
        }
    });
}
</script>
