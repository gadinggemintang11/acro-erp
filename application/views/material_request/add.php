<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
    .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h4 mb-0"><i class="fas fa-clipboard-list text-primary mr-2"></i><?= lang_line('material_request_create'); ?></h1>
        </div>
        <div class="col text-right">
            <a href="<?= base_url('material_request'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('btn_cancel'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php
        // Form 1-halaman: header (tanggal+catatan) DAN item (WO Reference +
        // tabel item) digabung jadi SATU form (user 2026-09-04 minta MRF
        // disamakan gaya IRF — "header dan form item nya jadi 1"). BEDA dari
        // IRF: item MRF WAJIB py wo_header_id (constraint asli
        // Mr_model::insert_item(), tidak diubah) & dicari via WO/komponen
        // produksi, bukan daftar inventory polos — jadi UI tetap pola
        // "WO Reference + input row + Load dari WO" yang sudah ada di
        // material_request/detail.php, cuma SEMUA item ditahan (staged) di
        // browser dulu, BUKAN langsung ke-insert per klik seperti di
        // detail.php. Baru dikirim SEKALIGUS (header+semua item) pas klik
        // "Simpan MRF" -> Material_request::store() (baru, tidak mengubah
        // create_header()/add_item_ajax() lama yg masih dipakai detail.php
        // utk MR yang sudah ada).
    ?>
    <form id="mrAddForm" method="post" action="<?= base_url('material_request/store'); ?>">
        <input type="hidden" name="items" id="items_payload" value="[]">

        <div class="card shadow mb-4">
            <div class="card-header bg-gradient-primary text-white">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle mr-2"></i><?= lang_line('material_request_header_info', 'Informasi Material Request'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label for="mr_request_date"><?= lang_line('material_request_date'); ?> *</label>
                            <input type="date" name="request_date" id="mr_request_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                            <div class="invalid-feedback"><?= lang_line('material_request_date'); ?> <?= lang_line('material_request_required_field'); ?>.</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Departemen *</label>
                            <?php $dept_options = $dept_options ?? []; // dari role user (Role Level tahap 3) ?>
                            <?php if (count($dept_options) > 1): ?>
                                <select name="department_code" class="form-control" required>
                                    <?php foreach ($dept_options as $code => $name): ?>
                                        <option value="<?= htmlspecialchars($code); ?>" <?= $code === ($dept_default ?? null) ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif (count($dept_options) === 1): ?>
                                <input type="hidden" name="department_code" value="<?= htmlspecialchars(array_key_first($dept_options)); ?>">
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(reset($dept_options)); ?>" readonly>
                            <?php else: ?>
                                <input type="text" class="form-control bg-light is-invalid" readonly>
                                <div class="invalid-feedback d-block">Role Anda belum punya departemen untuk membuat Material Request.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label for="mr_remarks"><?= lang_line('material_request_notes'); ?></label>
                            <textarea name="remarks" id="mr_remarks" class="form-control" rows="1"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">
                            <i class="fas fa-clipboard-check mr-2"></i><?= lang_line('material_request_wo_reference'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label><?= lang_line('material_request_select_wo'); ?></label>
                            <select id="wo_selector" class="form-control" style="width: 100%;">
                                <option value="">-- <?= lang_line('material_request_select_wo'); ?> --</option>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('material_request_wo_note'); ?></small>
                        </div>
                        <div id="wo_reference_div" class="mt-3" style="font-size: 0.9em;">
                            <p class="text-muted text-center"><i class="fas fa-info-circle mr-2"></i><?= lang_line('material_request_select_wo_help'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-boxes mr-2"></i><?= lang_line('material_request_items'); ?> (<span id="item_count">0</span>)
                                </h6>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-primary btn-sm mr-2" id="btn-load-wo-materials">
                                    <i class="fas fa-upload"></i> <?= lang_line('material_request_load_from_wo'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="row align-items-center mt-2">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-primary text-white">
                                            <i class="fas fa-clipboard-list mr-1"></i> <?= lang_line('material_request_table_wo'); ?>
                                        </span>
                                    </div>
                                    <select id="selected_wo_main" class="form-control form-control-sm" style="width: 60%;">
                                        <option value="">-- <?= lang_line('material_request_select_wo'); ?> --</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-sm btn-info" type="button" id="btn_apply_wo" disabled title="<?= lang_line('material_request_apply_wo_help'); ?>">
                                            <i class="fas fa-check"></i> <?= lang_line('common_apply'); ?>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-info-circle"></i> <?= lang_line('material_request_wo_auto_fill'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover mb-0" id="items_table">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="120"><?= lang_line('material_request_table_wo'); ?> <span class="text-danger">*</span></th>
                                        <th width="100"><?= lang_line('material_request_table_code'); ?> <span class="text-danger">*</span></th>
                                        <th><?= lang_line('material_request_table_name'); ?> <span class="text-danger">*</span></th>
                                        <th width="110" class="text-center"><?= lang_line('material_request_table_qty'); ?> <span class="text-danger">*</span></th>
                                        <th width="60"><?= lang_line('material_request_table_unit'); ?></th>
                                        <th width="100" class="text-right"><?= lang_line('material_request_table_stock'); ?></th>
                                        <th width="150"><?= lang_line('material_request_table_notes'); ?></th>
                                        <th width="70" class="text-center"><?= lang_line('common_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="items_tbody">
                                    <tr id="input_row" class="bg-light border-primary">
                                        <td class="p-1">
                                            <input type="text" id="input_wo_display" class="form-control form-control-sm" readonly placeholder="<?= lang_line('material_request_select_wo'); ?>" style="background: #fff; font-size: 0.8rem;">
                                            <input type="hidden" id="input_wo_id">
                                        </td>
                                        <td colspan="2" class="p-1">
                                            <select id="input_component" class="form-control form-control-sm" style="width: 100%;">
                                                <option value="">-- <?= lang_line('material_request_search_component'); ?> --</option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <input type="number" id="input_qty" class="form-control form-control-sm text-right" placeholder="0.00" step="any" min="0.01">
                                        </td>
                                        <td class="text-center align-middle">
                                            <small id="input_unit" class="text-muted font-weight-bold">-</small>
                                        </td>
                                        <td class="text-right align-middle">
                                            <small id="input_stock" class="text-muted font-weight-bold">-</small>
                                        </td>
                                        <td class="p-1">
                                            <input type="text" id="input_notes" class="form-control form-control-sm" placeholder="<?= lang_line('material_request_table_notes'); ?>...">
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" id="btn_add_item" class="btn btn-success btn-sm" title="<?= lang_line('material_request_add_item_help'); ?>" disabled>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="empty_row">
                                        <td colspan="8" class="text-center text-muted py-3" style="font-size: 0.9rem;">
                                            <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                                            <strong><?= lang_line('material_request_no_items'); ?></strong><br>
                                            <small><?= lang_line('material_request_select_wo_add_items'); ?></small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary"><?= lang_line('btn_cancel'); ?></a>
            <button type="submit" class="btn btn-primary" id="btnSubmitMr">
                <i class="fas fa-save mr-1"></i> <?= lang_line('material_request_save_create', 'Simpan Material Request'); ?>
            </button>
        </div>
    </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
.select2-container--default .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    border: 1px solid #d1d3e2;
}
.select2-dropdown { z-index: 1051; border: 1px solid #d1d3e2; }
#wo_reference_div .table-sm td, #wo_reference_div .table-sm th { padding: .3rem; font-size: 0.85em; }
#input_row { background-color: #f0f8ff !important; border-left: 3px solid var(--brand-1) !important; }
#input_row:hover { background-color: #e3f2fd !important; }
.existing-item { background-color: #fff; }
.existing-item:hover { background-color: #f8f9fc !important; }
.new-item { background-color: #d1ecf1 !important; transition: background-color 2s ease-out; }
</style>

<!-- Modal Load Materials from WO (staged, sama seperti detail.php TAPI push
     ke array browser, bukan AJAX insert langsung) -->
<div class="modal fade" id="modalLoadWOMaterials" tabindex="-1" role="dialog" aria-labelledby="modalLoadWOMaterialsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLoadWOMaterialsLabel"><?= lang_line('material_request_load_from_wo'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php /*
                    LANGKAH 1 — daftar Work Order.
                    Dulu di sini ada select2 yang baru memunculkan hasil setelah user
                    mengetik minimal 2 huruf, jadi tidak ada cara melihat WO mana saja
                    yang tersedia (user 2026-09-30: "moda load from wo juga di perbaiki
                    ya"). Sengaja dibuat DUA LANGKAH di dalam satu modal, bukan modal
                    di dalam modal — lebih sederhana dan tidak perlu penanganan
                    backdrop bertumpuk.
                */ ?>
                <div id="mr_wo_pick_step">
                    <div class="form-row mb-2">
                        <div class="col-md-10 mb-1">
                            <input type="text" id="mr_wo_search" class="form-control form-control-sm"
                                   placeholder="<?= lang_line('material_request_wo_search_ph', 'Cari No. WO / No. Sales Order / style...'); ?>">
                        </div>
                        <div class="col-md-2 mb-1">
                            <button type="button" id="mr_wo_refresh" class="btn btn-sm btn-outline-secondary btn-block">
                                <i class="fas fa-sync-alt mr-1"></i><?= lang_line('btn_refresh', 'Muat ulang'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:46vh; overflow-y:auto;">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light small" style="position:sticky; top:0; z-index:2;">
                                <tr class="text-center">
                                    <th style="width:140px;"><?= lang_line('material_request_wo_th_number', 'No. WO'); ?></th>
                                    <th style="width:130px;"><?= lang_line('material_request_wo_th_ipo', 'No. Sales Order'); ?></th>
                                    <th><?= lang_line('material_request_wo_th_style', 'Style / Finished Good'); ?></th>
                                    <th style="width:96px;"><?= lang_line('material_request_wo_th_qty', 'Qty Rencana'); ?></th>
                                    <th style="width:100px;"><?= lang_line('material_request_wo_th_start', 'Tgl Mulai'); ?></th>
                                    <th style="width:96px;"><?= lang_line('material_request_wo_th_material', 'Material'); ?></th>
                                    <th style="width:80px;"><?= lang_line('common_action', 'Aksi'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="mr_wo_body" class="small">
                                <tr><td colspan="7" class="text-center text-muted py-4"><?= lang_line('common_loading', 'Memuat…'); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php /* LANGKAH 2 — material rencana dari WO terpilih. */ ?>
                <div id="mr_wo_chosen" class="alert alert-info py-2 mb-3" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small">
                            <i class="fas fa-clipboard-check mr-1"></i>
                            <strong id="mr_wo_chosen_text">-</strong>
                            <span class="text-muted" id="mr_wo_chosen_sub"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="mr_wo_change">
                            <i class="fas fa-exchange-alt mr-1"></i><?= lang_line('material_request_wo_change', 'Ganti WO'); ?>
                        </button>
                    </div>
                </div>

                <input type="hidden" id="modal_wo_selector" value="">

                <div id="modal_materials_list" class="mt-2" style="display: none;">
                    <h6 class="font-weight-bold mb-3"><?= lang_line('material_request_modal_planned_materials'); ?>:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="chk-select-all" title="<?= lang_line('common_select_all'); ?>">
                                    </th>
                                    <th><?= lang_line('material_request_table_code'); ?></th>
                                    <th><?= lang_line('material_request_table_name'); ?></th>
                                    <th width="100"><?= lang_line('material_request_table_qty'); ?></th>
                                    <th width="100"><?= lang_line('material_request_table_stock'); ?></th>
                                    <th width="100"><?= lang_line('material_request_table_request_qty'); ?></th>
                                    <th width="70"><?= lang_line('material_request_table_unit'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="modal_materials_tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="modal_no_materials" class="alert alert-info mt-4" style="display: none;">
                    <i class="fas fa-info-circle mr-2"></i><?= lang_line('material_request_modal_select_wo_help'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="btn-confirm-load-materials">
                    <i class="fas fa-check"></i> <?= lang_line('material_request_load_selected_materials'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= base_url(); ?>';
</script>
<script src="<?= base_url('assets/js/mr_inline_input_staged.js'); ?>"></script>
