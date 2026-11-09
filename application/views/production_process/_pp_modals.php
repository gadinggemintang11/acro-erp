<?php
/**
 * Modal halaman tahap: Detail Scrap (asal scrap + qty/keterangan NG + buat MR), QC output,
 * dan Ubah Header. ID elemen dipertahankan dari versi lama karena dipakai
 * _pp_script.php. Butuh: $header.
 */
?>
<!-- ===================== Detail Scrap ===================== -->
<div class="modal fade pp-modal" id="scrapDetailModal" tabindex="-1" role="dialog" aria-labelledby="scrapDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scrapDetailModalLabel"><i class="fas fa-recycle mr-2"></i><?= lang_line('pp_scrap_modal_title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="pp-kv mb-2">
                    <div><span class="pp-label"><?= lang_line('pp_scrap_item_label'); ?></span><div class="v" id="scrapItemName">-</div></div>
                    <div><span class="pp-label"><?= lang_line('pp_scrap_qty_label'); ?></span><div class="v" id="scrapQtyTotal">0</div></div>
                    <div><span class="pp-label"><?= lang_line('pp2_scrap_source_total', 'Qty material asal scrap'); ?></span><div class="v" id="scrapSourceTotal">0</div></div>
                </div>

                <div class="pp-sect"><?= lang_line('pp_scrap_sources_title'); ?></div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover mb-0" id="scrapSourcesTable">
                        <thead class="thead-light small">
                            <tr>
                                <th style="width:34px" class="text-center"><input type="checkbox" id="scrapSourcesCheckAll" title="<?= htmlspecialchars(lang_line('pp_scrap_check_all')); ?>"></th>
                                <th><?= lang_line('pp_scrap_th_material_input'); ?></th>
                                <th><?= lang_line('pp_th_type'); ?></th>
                                <th class="text-right"><?= lang_line('pp_scrap_th_qty_input'); ?></th>
                                <th class="text-right"><?= lang_line('pp_scrap_th_qty_scrap'); ?></th>
                                <th><?= lang_line('pp2_scrap_notes', 'Keterangan NG'); ?></th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="scrapSourcesBody"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-2 mb-1" style="gap:.5rem">
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i><?= lang_line('pp_scrap_create_mr_info'); ?></small>
                    <button type="button" id="btnCreateMrFromScrap" class="btn btn-warning btn-sm" disabled>
                        <i class="fas fa-paper-plane mr-1"></i><?= lang_line('pp_scrap_create_mr_btn'); ?>
                    </button>
                </div>

                <form id="addScrapSourceForm" method="post" class="no-loader">
                    <div class="pp-sect"><?= lang_line('pp_scrap_add_title'); ?></div>
                    <div class="form-row">
                        <div class="col-md-7 form-group">
                            <label class="pp-label" for="sourceInputId"><?= lang_line('pp_scrap_select_input'); ?></label>
                            <select id="sourceInputId" name="source_input_id" class="form-control" required>
                                <option value=""><?= lang_line('pp_scrap_opt_select'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-5 form-group">
                            <label class="pp-label" for="sourceQty"><?= lang_line('pp_scrap_qty_from_input'); ?></label>
                            <input type="number" id="sourceQty" name="source_qty" step="0.0001" min="0" class="form-control" placeholder="0" required>
                            <small class="form-text text-muted"><?= lang_line('pp_scrap_max_available'); ?> <b id="maxSourceQty">0</b></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="pp-label" for="scrapNotes"><?= lang_line('pp2_scrap_notes', 'Keterangan NG'); ?> <span class="text-danger">*</span></label>
                        <input type="text" id="scrapNotes" name="notes" class="form-control" maxlength="255" required
                               placeholder="<?= htmlspecialchars(lang_line('pp2_scrap_notes_ph', 'Contoh: kain sobek saat dipotong, salah ukur pola…')); ?>">
                    </div>
                    <button type="submit" class="btn btn-pp"><i class="fas fa-plus mr-1"></i><?= lang_line('pp_btn_add_scrap_source'); ?></button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('pp_btn_close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== QC Output ===================== -->
<div class="modal fade pp-modal" id="qcModal" tabindex="-1" role="dialog" aria-labelledby="qcModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qcModalLabel"><i class="fas fa-clipboard-check mr-2"></i><?= lang_line('pp_qc_modal_title_full'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="qcForm" method="post" data-current-user="<?= htmlspecialchars((string) $this->session->userdata('fullname')); ?>">
                <div class="modal-body">
                    <div class="pp-kv mb-3" id="qcItemInfo">
                        <div><span class="pp-label"><?= lang_line('pp_th_item'); ?></span><div class="v" id="qcItemName">-</div></div>
                        <div><span class="pp-label"><?= lang_line('pp_th_qty'); ?></span><div class="v" id="qcItemQty">-</div></div>
                    </div>
                    <div class="form-group">
                        <label class="pp-label" for="qcDescription"><?= lang_line('pp_qc_desc_label'); ?> <span class="text-danger"><?= lang_line('pp_qc_desc_required'); ?></span></label>
                        <textarea id="qcDescription" name="qc_description" class="form-control" rows="4" placeholder="<?= htmlspecialchars(lang_line('pp_qc_desc_placeholder')); ?>" required></textarea>
                        <small class="form-text text-muted"><?= lang_line('pp_qc_desc_help'); ?></small>
                    </div>
                    <div class="form-group">
                        <label class="pp-label" for="qcPhotoUrl"><?= lang_line('pp_qc_photo_label'); ?></label>
                        <div class="input-group">
                            <input type="url" id="qcPhotoUrl" name="qc_photo_url" class="form-control" placeholder="<?= htmlspecialchars(lang_line('pp_qc_photo_placeholder_full')); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="btnOpenLink" onclick="openQcLink()" title="<?= htmlspecialchars(lang_line('pp_qc_photo_open_title')); ?>"><i class="fas fa-external-link-alt"></i></button>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= lang_line('pp_qc_photo_help_full'); ?></small>
                    </div>
                    <div id="viewOnlyDiv" class="pp-kv">
                        <div><span class="pp-label"><?= lang_line('pp_qc_checked_by_label'); ?></span><div class="v" id="qcCheckedByNameText">-</div><input type="hidden" id="qcCheckedByName"></div>
                        <div><span class="pp-label"><?= lang_line('pp_qc_checked_at_label'); ?></span><div class="v" id="qcCheckedAt">-</div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('pp_btn_close'); ?></button>
                    <button type="submit" id="qcSubmitBtn" class="btn btn-success"><i class="fas fa-save mr-1"></i><?= lang_line('pp_qc_save_btn'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
<!-- ===================== Ubah Header ===================== -->
<div class="modal fade pp-modal" id="ppHeaderModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('production_process/update_header/' . $header->id); ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen mr-2"></i><?= lang_line('pp2_btn_edit_header', 'Ubah Header'); ?> · <?= htmlspecialchars($header->production_number); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="pp-label" for="ppHdrDate"><?= lang_line('pp2_meta_prod_date', 'Tanggal Produksi'); ?> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="ppHdrDate" name="production_date" required value="<?= htmlspecialchars($header->production_date); ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label class="pp-label" for="ppHdrRemarks"><?= lang_line('pp2_meta_remarks', 'Catatan'); ?></label>
                        <textarea class="form-control" id="ppHdrRemarks" name="remarks" rows="3"><?= htmlspecialchars($header->remarks ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('pp_btn_close'); ?></button>
                    <button type="submit" class="btn btn-pp"><i class="fas fa-save mr-1"></i><?= lang_line('pp2_btn_save', 'Simpan'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
