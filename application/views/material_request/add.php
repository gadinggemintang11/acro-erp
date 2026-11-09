<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Buat Material Request -- redesign 2026-11-08 (user: "rombak total menjadi
 * modern dan profesional ... berfungsi optimal juga dengan pull wo").
 *
 * Alur:  1) isi informasi  2) pilih satu/lebih Work Order (modal daftar WO
 *        In Progress)  3) tarik rencana material WO -- qty default = SISA
 *        kebutuhan (rencana - sudah diminta di MR lain)  4) tambah item di luar
 *        rencana bila perlu  5) simpan.
 *
 * Semua item ditahan di browser lalu dikirim sekaligus ke
 * Material_request::store() (field `items` = JSON [{wo_header_id,
 * component_id, component_type, qty, notes}]). Item ganda (WO + item sama)
 * digabung -- di browser maupun di server (unique key mr_wo_component_unique).
 *
 * Data: ajax_wo_list (daftar WO), ajax_wo_plan (rencana + sudah diminta +
 * stok), search_components (cari item manual).
 */
$this->load->view('production_process/_pp_style');
$dept_options = $dept_options ?? [];
// Mode 'edit' (Material_request::edit): form yang sama, terisi MR Draft yang ada.
$is_edit  = (($mode ?? 'create') === 'edit') && !empty($header);
$form_url = $is_edit ? base_url('material_request/update/' . $header->id) : base_url('material_request/store');
$back_url = $is_edit ? base_url('material_request/detail/' . $header->id) : base_url('material_request');
$prefill  = ['mr_id' => 0, 'wos' => [], 'items' => []];
if ($is_edit) {
    $prefill['mr_id'] = (int) $header->id;
    foreach ($details ?? [] as $d) {
        $w = (int) $d->wo_header_id;
        if (!isset($prefill['wos'][$w])) {
            $prefill['wos'][$w] = ['id' => $w, 'wo_number' => (string) $d->wo_number, 'sc_number' => (string) $d->sc_number, 'fg_name' => (string) $d->style_name];
        }
        $prefill['items'][] = [
            'wo' => $w, 'id' => (int) $d->component_item_id, 'type' => $d->component_item_type,
            'code' => (string) $d->item_code, 'name' => (string) $d->item_name,
            'unit' => (string) ($d->unit_measure ?: $d->item_unit), 'stock' => (float) $d->stock,
            'qty' => (float) $d->requested_qty, 'notes' => (string) $d->notes,
        ];
    }
    $prefill['wos'] = array_values($prefill['wos']);
}
$flash_icons  = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<style>
.mr2 .mr-sticky { position: sticky; top: 1rem; }
.mr2 .mr-wo { display: flex; gap: .75rem; align-items: center; padding: .75rem .85rem; border: 1px solid var(--pp-line); border-radius: 12px; margin-bottom: .6rem; cursor: pointer; transition: border-color .15s, box-shadow .15s; background: var(--pp-card); }
.mr2 .mr-wo:hover { border-color: var(--brand-2); }
.mr2 .mr-wo.is-active { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14); }
.mr2 .mr-wo-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: rgba(var(--brand-rgb), .1); color: var(--brand-1); flex: 0 0 auto; }
.mr2 .mr-wo.is-active .mr-wo-ico { background: var(--brand-1); color: #fff; }
.mr2 .mr-wo-body { min-width: 0; flex: 1; }
.mr2 .mr-wo-no { font-weight: 700; font-size: .88rem; }
.mr2 .mr-wo-sub { font-size: .74rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mr2 .mr-wo-x { border: 0; background: transparent; color: var(--pp-muted); padding: .25rem .4rem; border-radius: 8px; }
.mr2 .mr-wo-x:hover { color: #e11d48; background: rgba(244,63,94,.08); }
.mr2 .mr-empty-big { text-align: center; padding: 2.5rem 1rem; color: var(--pp-muted); }
.mr2 .mr-empty-big i { font-size: 2.4rem; opacity: .3; display: block; margin-bottom: .75rem; }
.mr2 .mr-empty-big b { color: var(--pp-text); display: block; margin-bottom: .25rem; font-size: .95rem; }
.mr2 .mr-plan-wrap { max-height: 440px; overflow-y: auto; }
.mr2 .mr-plan-wrap thead th { position: sticky; top: 0; z-index: 2; }
.mr2 .mr-qty { width: 110px; text-align: right; display: inline-block; }
.mr2 .mr-notes { min-width: 140px; }
.mr2 tr.is-in-mr td { background: rgba(16,185,129,.05); }
.mr2 tr.mr-group td { background: var(--pp-soft); font-size: .78rem; font-weight: 700; color: var(--pp-text); padding-top: .55rem; padding-bottom: .55rem; }
.mr2 .mr-flag { display: inline-flex; align-items: center; gap: .25rem; font-size: .66rem; font-weight: 700; padding: .1rem .45rem; border-radius: 6px; margin-top: .2rem; margin-right: .2rem; white-space: nowrap; }
.mr2 .mr-flag--stock { background: rgba(244,63,94,.1); color: #be123c; }
.mr2 .mr-flag--plan { background: rgba(245,158,11,.14); color: #b45309; }
.mr2 .mr-flag--extra { background: rgba(99,102,241,.12); color: #4338ca; }
body.dark-mode .mr2 .mr-flag--stock { color: #fda4af; } body.dark-mode .mr2 .mr-flag--plan { color: #fcd34d; } body.dark-mode .mr2 .mr-flag--extra { color: #a5b4fc; }
.mr2 .mr-sum { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
.mr2 .mr-sum > div { background: var(--pp-soft); border: 1px solid var(--pp-line); border-radius: 12px; padding: .6rem .75rem; }
.mr2 .mr-sum .n { font-size: 1.3rem; font-weight: 800; line-height: 1.1; }
.mr2 .mr-sum .l { font-size: .7rem; color: var(--pp-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.mr2 .mr-sum .warn .n { color: #d97706; }
.mr2 .mr-bar { position: sticky; bottom: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .85rem 1.1rem; margin-top: .5rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: 0 -6px 24px rgba(15,23,42,.08); }
.mr2 .mr-bar-info { font-size: .85rem; color: var(--pp-muted); }
.mr2 .mr-bar-info b { color: var(--pp-text); }
.mr2 .mr-add { display: grid; grid-template-columns: minmax(0, 1fr) 120px minmax(0, .7fr) auto; gap: .6rem; align-items: end; }
@media (max-width: 991.98px) { .mr2 .mr-add { grid-template-columns: 1fr 1fr; } .mr2 .mr-sticky { position: static; } }
.mr-wo-row { cursor: pointer; }
.mr-wo-row.is-picked td { opacity: .55; }
#mrWoModal .table td, #mrWoModal .table th { vertical-align: middle; }
</style>

<div class="container-fluid pp2 mr2">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-dolly-flatbed"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker">Material Request<?= $is_edit ? ' · Draft' : ''; ?></div>
                    <h1 class="pp-hero-title"><?= $is_edit ? sprintf(lang_line('mr2_title_edit', 'Edit Material Request %s'), htmlspecialchars($header->mr_number)) : lang_line('material_request_create'); ?></h1>
                    <div class="pp-hero-subline"><?= $is_edit ? lang_line('mr2_hero_sub_edit', 'Ubah informasi & item, lalu simpan perubahan. Submit dilakukan di halaman detail.') : lang_line('mr2_hero_sub', 'Pilih Work Order, tarik rencana materialnya, sesuaikan qty, lalu simpan.'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($is_edit): ?>
                    <a href="<?= $back_url; ?>" class="btn pp-btn-glass"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('mr2_btn_back_detail', 'Kembali ke Detail'); ?></a>
                <?php else: ?>
                    <a href="<?= base_url('material_request'); ?>" class="btn pp-btn-glass"><i class="fas fa-list mr-1"></i><?= lang_line('mr2_btn_list', 'Daftar MR'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert">
            <i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>
        </div>
    <?php endif; endforeach; ?>

    <form id="mrAddForm" method="post" action="<?= $form_url; ?>" novalidate>
        <input type="hidden" name="items" id="items_payload" value="[]">

        <div class="row">
            <!-- ================= KIRI: informasi, WO, ringkasan ================= -->
            <div class="col-xl-4">
                <div class="mr-sticky">
                    <div class="pp-card">
                        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-info"></i></span><?= lang_line('material_request_header_info', 'Informasi Material Request'); ?></h2></div>
                        <div class="pp-card-body">
                            <div class="form-row">
                                <div class="col-6 form-group">
                                    <label class="pp-label" for="mr_request_date"><?= lang_line('material_request_date'); ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="request_date" id="mr_request_date" class="form-control" value="<?= $is_edit ? htmlspecialchars($header->request_date) : date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-6 form-group">
                                    <label class="pp-label"><?= lang_line('mr2_department', 'Departemen'); ?> <span class="text-danger">*</span></label>
                                    <?php if (count($dept_options) > 1): ?>
                                        <select name="department_code" id="mr_dept" class="form-control" required>
                                            <?php foreach ($dept_options as $code => $name): ?>
                                                <option value="<?= htmlspecialchars($code); ?>" <?= $code === ($dept_default ?? null) ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif (count($dept_options) === 1): ?>
                                        <input type="hidden" name="department_code" id="mr_dept" value="<?= htmlspecialchars(array_key_first($dept_options)); ?>">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars(reset($dept_options)); ?>" readonly title="<?= htmlspecialchars(reset($dept_options)); ?>">
                                    <?php else: ?>
                                        <input type="hidden" id="mr_dept" value="">
                                        <input type="text" class="form-control is-invalid" readonly>
                                        <small class="text-danger d-block mt-1"><?= lang_line('mr2_no_dept', 'Role Anda belum punya departemen untuk membuat Material Request.'); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="pp-label" for="mr_remarks"><?= lang_line('material_request_notes'); ?></label>
                                <textarea name="remarks" id="mr_remarks" class="form-control" rows="2" placeholder="<?= htmlspecialchars(lang_line('mr2_remarks_ph', 'Keperluan / catatan untuk gudang (opsional)')); ?>"><?= $is_edit ? htmlspecialchars((string) $header->remarks) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pp-card">
                        <div class="pp-card-head">
                            <h2><span class="pp-ico"><i class="fas fa-clipboard-list"></i></span><span>Work Order
                                <span class="d-block pp-sub"><?= lang_line('mr2_wo_sub', 'Klik WO untuk melihat & menarik rencana materialnya'); ?></span></span></h2>
                        </div>
                        <div class="pp-card-body">
                            <div id="mrWoList"></div>
                            <button type="button" class="btn btn-pp btn-block" id="btnPickWo"><i class="fas fa-plus mr-1"></i><?= lang_line('mr2_btn_pick_wo', 'Pilih Work Order'); ?></button>
                        </div>
                    </div>

                    <div class="pp-card">
                        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-chart-pie"></i></span><?= lang_line('mr2_summary', 'Ringkasan'); ?></h2></div>
                        <div class="pp-card-body">
                            <div class="mr-sum">
                                <div><div class="n" id="sumItems">0</div><div class="l"><?= lang_line('mr2_sum_items', 'Item'); ?></div></div>
                                <div><div class="n" id="sumWos">0</div><div class="l">Work Order</div></div>
                                <div class="warn"><div class="n" id="sumStock">0</div><div class="l"><?= lang_line('mr2_sum_stock', 'Stok kurang'); ?></div></div>
                                <div class="warn"><div class="n" id="sumPlan">0</div><div class="l"><?= lang_line('mr2_sum_plan', 'Lebihi rencana'); ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= KANAN: rencana WO & item ================= -->
            <div class="col-xl-8">
                <div class="pp-card" id="mrPlanCard">
                    <div class="pp-card-head">
                        <h2><span class="pp-ico"><i class="fas fa-sitemap"></i></span><span>
                            <span id="planTitle"><?= lang_line('mr2_plan_title', 'Rencana Material WO'); ?></span>
                            <span class="d-block pp-sub" id="planSub"><?= lang_line('mr2_plan_sub_empty', 'Pilih Work Order untuk menampilkan rencana materialnya'); ?></span></span></h2>
                        <div class="d-flex flex-wrap" style="gap:.4rem" id="planActions" hidden>
                            <button type="button" class="btn btn-light btn-sm" id="btnPlanRemaining"><i class="fas fa-magic mr-1"></i><?= lang_line('mr2_btn_fill_remaining', 'Isi sesuai sisa'); ?></button>
                            <button type="button" class="btn btn-pp btn-sm" id="btnPlanAdd" disabled><i class="fas fa-arrow-down mr-1"></i><?= lang_line('mr2_btn_add_selected', 'Tambahkan terpilih'); ?> (<span id="planSelCount">0</span>)</button>
                        </div>
                    </div>
                    <div id="planBody">
                        <div class="mr-empty-big"><i class="fas fa-clipboard-list"></i><b><?= lang_line('mr2_plan_empty_title', 'Belum ada Work Order dipilih'); ?></b><?= lang_line('mr2_plan_empty_text', 'Klik "Pilih Work Order" di kiri. Rencana material WO akan muncul di sini lengkap dengan sisa kebutuhan & stok gudang.'); ?></div>
                    </div>
                </div>

                <div class="pp-card">
                    <div class="pp-card-head">
                        <h2><span class="pp-ico"><i class="fas fa-boxes"></i></span><span><?= lang_line('material_request_items'); ?> (<span id="item_count">0</span>)
                            <span class="d-block pp-sub"><?= lang_line('mr2_items_sub', 'Qty & catatan bisa diubah langsung di tabel'); ?></span></span></h2>
                    </div>
                    <div class="pp-card-body" style="border-bottom:1px solid var(--pp-line)">
                        <div class="mr-add">
                            <div>
                                <label class="pp-label"><?= lang_line('mr2_add_manual', 'Tambah item di luar rencana'); ?> <span class="text-muted" style="font-weight:500;text-transform:none" id="manualWoHint"></span></label>
                                <select id="input_component" class="form-control" style="width:100%" disabled></select>
                            </div>
                            <div>
                                <label class="pp-label" for="input_qty">Qty <small class="text-muted text-lowercase" id="input_meta"></small></label>
                                <input type="text" inputmode="decimal" id="input_qty" class="form-control text-right" placeholder="0" disabled autocomplete="off">
                            </div>
                            <div>
                                <label class="pp-label" for="input_notes"><?= lang_line('material_request_table_notes'); ?></label>
                                <input type="text" id="input_notes" class="form-control" maxlength="255" disabled>
                            </div>
                            <div><button type="button" class="btn btn-pp" id="btn_add_item" disabled title="<?= htmlspecialchars(lang_line('mr2_add', 'Tambah')); ?>"><i class="fas fa-plus"></i></button></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="pp-table" id="items_table">
                            <thead><tr>
                                <th><?= lang_line('material_request_table_name'); ?></th>
                                <th class="num"><?= lang_line('material_request_table_stock'); ?></th>
                                <th class="num"><?= lang_line('material_request_table_qty'); ?> <span class="text-danger">*</span></th>
                                <th><?= lang_line('material_request_table_notes'); ?></th>
                                <th></th>
                            </tr></thead>
                            <tbody id="items_tbody">
                                <tr><td colspan="5" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('material_request_no_items'); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mr-bar">
            <div class="mr-bar-info" id="barInfo"><?= lang_line('mr2_bar_empty', 'Belum ada item.'); ?></div>
            <div class="d-flex" style="gap:.5rem">
                <a href="<?= $back_url; ?>" class="btn btn-light"><?= lang_line('btn_cancel'); ?></a>
                <button type="submit" class="btn btn-pp px-4" id="btnSubmitMr"><i class="fas fa-save mr-1"></i><?= $is_edit ? lang_line('mr2_btn_save_changes', 'Simpan Perubahan') : lang_line('material_request_save_create', 'Simpan Material Request'); ?></button>
            </div>
        </div>
    </form>
</div>

<!-- ===================== Modal pilih Work Order ===================== -->
<div class="modal fade pp-modal" id="mrWoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-list mr-2"></i><?= lang_line('mr2_modal_wo_title', 'Pilih Work Order'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center mb-3" style="gap:.5rem">
                    <div class="flex-grow-1" style="min-width:240px">
                        <input type="text" id="mrWoSearch" class="form-control" placeholder="<?= htmlspecialchars(lang_line('material_request_wo_search_ph', 'Cari No. WO / No. Sales Order / style...')); ?>">
                    </div>
                    <button type="button" class="btn btn-light" id="mrWoRefresh"><i class="fas fa-sync-alt mr-1"></i><?= lang_line('btn_refresh', 'Muat ulang'); ?></button>
                </div>
                <p class="small text-muted mb-2"><i class="fas fa-info-circle mr-1"></i><?= lang_line('mr2_modal_wo_hint', 'Hanya WO berstatus In Progress. Bisa memilih lebih dari satu WO dalam satu Material Request.'); ?></p>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light small">
                            <tr>
                                <th><?= lang_line('material_request_wo_th_number', 'No. WO'); ?></th>
                                <th><?= lang_line('material_request_wo_th_ipo', 'No. Sales Order'); ?></th>
                                <th><?= lang_line('material_request_wo_th_style', 'Style / Finished Good'); ?></th>
                                <th class="text-right"><?= lang_line('material_request_wo_th_qty', 'Qty Rencana'); ?></th>
                                <th class="text-center"><?= lang_line('material_request_wo_th_start', 'Tgl Mulai'); ?></th>
                                <th class="text-center"><?= lang_line('material_request_wo_th_material', 'Material'); ?></th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody id="mrWoBody" class="small">
                            <tr><td colspan="7" class="text-center text-muted py-4"><?= lang_line('common_loading', 'Memuat…'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal"><?= lang_line('common_close', 'Tutup'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var MR_PREFILL = <?= json_encode($prefill); ?>;
var MR_URL = {
    wo_list:  <?= json_encode(base_url('material_request/ajax_wo_list')); ?>,
    wo_plan:  <?= json_encode(base_url('material_request/ajax_wo_plan')); ?>,
    search:   <?= json_encode(base_url('material_request/search_components')); ?>
};
var MR_T = {
    loading:      <?= json_encode(lang_line('common_loading', 'Memuat…')); ?>,
    wo_empty:     <?= json_encode(lang_line('production_wo_empty', 'Tidak ada Work Order yang cocok.')); ?>,
    wo_failed:    <?= json_encode(lang_line('production_wo_failed', 'Gagal memuat daftar Work Order.')); ?>,
    pick:         <?= json_encode(lang_line('production_wo_btn_choose', 'Pilih')); ?>,
    picked:       <?= json_encode(lang_line('mr2_picked', 'Dipilih')); ?>,
    no_plan_wo:   <?= json_encode(lang_line('mr2_no_plan_wo', 'Belum ada rencana material')); ?>,
    plan_title:   <?= json_encode(lang_line('mr2_plan_title', 'Rencana Material WO')); ?>,
    plan_none:    <?= json_encode(lang_line('mr2_plan_none', 'WO ini belum punya rencana material. Tambahkan item manual di bawah.')); ?>,
    th_item:      <?= json_encode(lang_line('material_request_table_name')); ?>,
    th_plan:      <?= json_encode(lang_line('mr2_th_plan', 'Rencana')); ?>,
    th_requested: <?= json_encode(lang_line('mr2_th_requested', 'Sudah diminta')); ?>,
    th_remaining: <?= json_encode(lang_line('mr2_th_remaining', 'Sisa')); ?>,
    th_stock:     <?= json_encode(lang_line('material_request_table_stock')); ?>,
    th_qty:       <?= json_encode(lang_line('mr2_th_req_qty', 'Qty diminta')); ?>,
    in_mr:        <?= json_encode(lang_line('mr2_in_mr', 'Sudah di MR ini')); ?>,
    fully_req:    <?= json_encode(lang_line('mr2_fully_requested', 'Sudah diminta penuh')); ?>,
    flag_stock:   <?= json_encode(lang_line('mr2_flag_stock', 'Stok kurang')); ?>,
    flag_plan:    <?= json_encode(lang_line('mr2_flag_plan', 'Melebihi sisa rencana')); ?>,
    flag_extra:   <?= json_encode(lang_line('mr2_flag_extra', 'Di luar rencana WO')); ?>,
    no_items:     <?= json_encode(lang_line('material_request_no_items')); ?>,
    added:        <?= json_encode(lang_line('mr2_toast_added', '%d item ditambahkan')); ?>,
    updated:      <?= json_encode(lang_line('mr2_toast_updated', '%d item diperbarui')); ?>,
    merged:       <?= json_encode(lang_line('mr2_toast_merged', 'Item sudah ada untuk WO ini — qty dijumlahkan.')); ?>,
    pick_wo_first:<?= json_encode(lang_line('mr2_pick_wo_first', 'pilih WO dulu')); ?>,
    for_wo:       <?= json_encode(lang_line('mr2_for_wo', 'untuk %s')); ?>,
    remove_wo:    <?= json_encode(lang_line('mr2_remove_wo', 'Hapus WO %s beserta %d item-nya dari MR ini?')); ?>,
    search_ph:    <?= json_encode(lang_line('material_request_search_component', 'Cari material / komponen')); ?>,
    stock:        <?= json_encode(lang_line('material_request_table_stock')); ?>,
    bar:          <?= json_encode(lang_line('mr2_bar', '<b>%1$d</b> item dari <b>%2$d</b> Work Order')); ?>,
    bar_empty:    <?= json_encode(lang_line('mr2_bar_empty', 'Belum ada item.')); ?>,
    err_title:    <?= json_encode(lang_line('pp_err_title', 'Error')); ?>,
    v_date:       <?= json_encode(lang_line('mr2_v_date', 'Tanggal permintaan wajib diisi.')); ?>,
    v_dept:       <?= json_encode(lang_line('mr2_v_dept', 'Departemen wajib diisi.')); ?>,
    v_items:      <?= json_encode(lang_line('mr2_v_items', 'Tambahkan minimal satu item.')); ?>,
    v_qty:        <?= json_encode(lang_line('mr2_v_qty', 'Qty setiap item harus lebih dari 0.')); ?>,
    warn_title:   <?= json_encode(lang_line('mr2_warn_title', 'Periksa sebelum menyimpan')); ?>,
    warn_stock:   <?= json_encode(lang_line('mr2_warn_stock', '%d item melebihi stok gudang saat ini.')); ?>,
    warn_plan:    <?= json_encode(lang_line('mr2_warn_plan', '%d item melebihi sisa rencana WO.')); ?>,
    warn_go:      <?= json_encode(lang_line('mr2_warn_go', 'Tetap simpan')); ?>,
    back:         <?= json_encode(lang_line('mr2_back', 'Periksa lagi')); ?>,
    yes:          <?= json_encode(lang_line('pp_sw_yes', 'Ya')); ?>,
    cancel:       <?= json_encode(lang_line('pp_sw_cancel', 'Batal')); ?>,
    saving:       <?= json_encode(lang_line('pp_sw_saving', 'Menyimpan...')); ?>,
    leave:        <?= json_encode(lang_line('mr2_leave', 'Item yang belum disimpan akan hilang.')); ?>
};

document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var state = { wos: {}, woOrder: [], active: null, plans: {}, items: [] };
    var dirty = false;   // ada perubahan yang belum disimpan
    var woRows = [];   // hasil terakhir daftar WO di modal
    var submitting = false;

    // ---------- util ----------
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function num(v) { var s = String(v == null ? '' : v).replace(/\s+/g, ''); if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) s = s.replace(/\./g, ''); var n = parseFloat(s.replace(/,/g, '.')); return isNaN(n) ? 0 : n; }
    function fmt(n) { n = parseFloat(n || 0); return (Math.round(n * 10000) / 10000).toLocaleString('id-ID', { maximumFractionDigits: 4 }); }
    function r4(n) { return Math.round((parseFloat(n) || 0) * 10000) / 10000; }
    function key(wo, id, type) { return wo + '|' + id + '|' + type; }
    function findItem(wo, id, type) { var k = key(wo, id, type); for (var i = 0; i < state.items.length; i++) if (state.items[i].key === k) return state.items[i]; return null; }
    function planLine(wo, id, type) { var lines = state.plans[wo] || []; for (var i = 0; i < lines.length; i++) if (lines[i].id == id && lines[i].type === type) return lines[i]; return null; }
    function toast(type, msg) { if (window.toastr) toastr[type](msg); }
    function sprintf(t) { var a = Array.prototype.slice.call(arguments, 1), i = 0; return t.replace(/%(\d)\$[sd]|%[sd]/g, function (m, p) { return esc(p ? a[p - 1] : a[i++]); }); }

    // ---------- WO terpilih ----------
    function renderWoList() {
        var $l = $('#mrWoList').empty();
        if (!state.woOrder.length) {
            $l.html('<div class="text-center small mb-3" style="color:var(--pp-muted)"><i class="fas fa-clipboard mr-1"></i>' + esc(MR_T.pick_wo_first) + '</div>');
        }
        state.woOrder.forEach(function (id) {
            var w = state.wos[id], n = state.items.filter(function (it) { return it.wo == id; }).length;
            $l.append('<div class="mr-wo' + (state.active == id ? ' is-active' : '') + '" data-id="' + id + '">'
                + '<span class="mr-wo-ico"><i class="fas fa-clipboard-check"></i></span>'
                + '<span class="mr-wo-body"><span class="mr-wo-no d-block">' + esc(w.wo_number) + '</span>'
                + '<span class="mr-wo-sub d-block" title="' + esc(w.fg_name) + '">' + esc((w.sc_number ? w.sc_number + ' · ' : '') + (w.fg_name || '-')) + '</span></span>'
                + '<span class="pp-chip" title="Item">' + n + '</span>'
                + '<button type="button" class="mr-wo-x" data-remove="' + id + '" title="Hapus"><i class="fas fa-times"></i></button></div>');
        });
        var w = state.active ? state.wos[state.active] : null;
        $('#input_component, #input_qty, #input_notes').prop('disabled', !w);
        $('#manualWoHint').text(w ? MR_T.for_wo.replace('%s', w.wo_number || '') : '(' + MR_T.pick_wo_first + ')');
        updateAddBtn();
    }
    function addWo(w, makeActive) {
        if (!state.wos[w.id]) { state.wos[w.id] = w; state.woOrder.push(w.id); }
        if (makeActive !== false) setActive(w.id); else renderWoList();
    }
    function setActive(id) {
        state.active = id; renderWoList();
        if (state.plans[id]) renderPlan(); else loadPlan(id);
    }
    $('#mrWoList').on('click', '.mr-wo', function (e) {
        if ($(e.target).closest('[data-remove]').length) return;
        setActive($(this).data('id'));
    });
    $('#mrWoList').on('click', '[data-remove]', function () {
        var id = $(this).data('remove'), w = state.wos[id];
        var n = state.items.filter(function (it) { return it.wo == id; }).length;
        function go() {
            state.items = state.items.filter(function (it) { return it.wo != id; });
            delete state.wos[id]; delete state.plans[id];
            state.woOrder = state.woOrder.filter(function (x) { return x != id; });
            if (state.active == id) state.active = state.woOrder[0] || null;
            renderWoList(); renderItems();
            if (state.active) setActive(state.active); else renderPlanEmpty();
        }
        if (!n) return go();
        Swal.fire({ icon: 'warning', text: MR_T.remove_wo.replace('%s', w.wo_number).replace('%d', n), showCancelButton: true,
            confirmButtonColor: '#e11d48', confirmButtonText: MR_T.yes, cancelButtonText: MR_T.cancel }).then(function (r) { if (r.isConfirmed) go(); });
    });

    // ---------- modal daftar WO ----------
    function loadWoList() {
        var $b = $('#mrWoBody').html('<tr><td colspan="7" class="text-center text-muted py-4">' + esc(MR_T.loading) + '</td></tr>');
        $.getJSON(MR_URL.wo_list, { search: $('#mrWoSearch').val() || '' }).done(function (res) {
            if (!res || !res.success) { $b.html('<tr><td colspan="7" class="text-center text-danger py-4">' + esc((res && res.message) || MR_T.wo_failed) + '</td></tr>'); return; }
            if (!res.rows.length) { $b.html('<tr><td colspan="7" class="text-center text-muted py-4">' + esc(MR_T.wo_empty) + '</td></tr>'); return; }
            var html = '';
            woRows = res.rows;
            res.rows.forEach(function (r, idx) {
                var picked = !!state.wos[r.id];
                html += '<tr class="mr-wo-row' + (picked ? ' is-picked' : '') + '" data-idx="' + idx + '">'
                    + '<td><b>' + esc(r.wo_number) + '</b></td>'
                    + '<td>' + (esc(r.sc_number) || '<span class="text-muted">-</span>') + '</td>'
                    + '<td>' + esc(r.fg_name || '-') + ' <span class="text-muted">' + esc(r.fg_code ? '(' + r.fg_code + ')' : '') + '</span></td>'
                    + '<td class="text-right">' + fmt(r.planned_qty) + '</td>'
                    + '<td class="text-center">' + esc(r.planned_start_date || '-') + '</td>'
                    + '<td class="text-center">' + (r.material_count ? '<span class="badge badge-light">' + r.material_count + '</span>' : '<span class="text-warning" title="' + esc(MR_T.no_plan_wo) + '"><i class="fas fa-exclamation-triangle"></i> 0</span>') + '</td>'
                    + '<td class="text-right">' + (picked ? '<span class="badge badge-success">' + esc(MR_T.picked) + '</span>' : '<button type="button" class="btn btn-sm btn-pp mr-wo-pick"><i class="fas fa-check mr-1"></i>' + esc(MR_T.pick) + '</button>') + '</td>'
                    + '</tr>';
            });
            $b.html(html);
        }).fail(function () { $b.html('<tr><td colspan="7" class="text-center text-danger py-4">' + esc(MR_T.wo_failed) + '</td></tr>'); });
    }
    $('#btnPickWo').on('click', function () { $('#mrWoModal').modal('show'); });
    $('#mrWoModal').on('shown.bs.modal', function () { loadWoList(); $('#mrWoSearch').trigger('focus'); });
    $('#mrWoRefresh').on('click', loadWoList);
    var woTimer = null;
    $('#mrWoSearch').on('input', function () { clearTimeout(woTimer); woTimer = setTimeout(loadWoList, 300); });
    $('#mrWoBody').on('click', '.mr-wo-row', function () {
        var r = woRows[$(this).data('idx')];
        if (!r) return;
        if (state.wos[r.id]) { setActive(r.id); $('#mrWoModal').modal('hide'); return; }
        addWo(r); $('#mrWoModal').modal('hide');
    });

    // ---------- rencana material WO ----------
    function renderPlanEmpty() {
        $('#planActions').prop('hidden', true);
        $('#planTitle').text(MR_T.plan_title);
        $('#planSub').text(<?= json_encode(lang_line('mr2_plan_sub_empty', 'Pilih Work Order untuk menampilkan rencana materialnya')); ?>);
        $('#planBody').html('<div class="mr-empty-big"><i class="fas fa-clipboard-list"></i><b>' + esc(<?= json_encode(lang_line('mr2_plan_empty_title', 'Belum ada Work Order dipilih')); ?>) + '</b>' + esc(<?= json_encode(lang_line('mr2_plan_empty_text', 'Klik "Pilih Work Order" di kiri. Rencana material WO akan muncul di sini lengkap dengan sisa kebutuhan & stok gudang.')); ?>) + '</div>');
    }
    function loadPlan(id) {
        if (state.active == id) $('#planBody').html('<div class="mr-empty-big"><i class="fas fa-spinner fa-spin"></i>' + esc(MR_T.loading) + '</div>');
        $.getJSON(MR_URL.wo_plan, { wo_id: id, exclude_mr: MR_PREFILL.mr_id || 0 }).done(function (res) {
            if (!res || !res.success) { if (state.active == id) $('#planBody').html('<div class="mr-empty-big text-danger"><i class="fas fa-exclamation-circle"></i>' + esc((res && res.message) || MR_T.wo_failed) + '</div>'); return; }
            var hasItems = state.items.some(function (it) { return it.wo == id; });
            if (res.wo.status !== 'In Progress' && hasItems) {
                // Edit MR lama yang WO-nya sudah tidak In Progress: item tetap dipertahankan.
                toast('warning', <?= json_encode(lang_line('mr2_wo_not_in_progress', 'Work Order %s tidak berstatus In Progress, tidak bisa dipakai untuk Material Request.')); ?>.replace('%s', res.wo.wo_number));
            } else if (res.wo.status !== 'In Progress') {
                // Hanya WO In Progress yang boleh diminta materialnya (sama dgn daftar WO).
                Swal.fire({ icon: 'warning', text: <?= json_encode(lang_line('mr2_wo_not_in_progress', 'Work Order %s tidak berstatus In Progress, tidak bisa dipakai untuk Material Request.')); ?>.replace('%s', res.wo.wo_number) });
                delete state.wos[id]; state.woOrder = state.woOrder.filter(function (x) { return x != id; });
                state.active = state.woOrder[0] || null; renderWoList();
                if (state.active) setActive(state.active); else renderPlanEmpty();
                return;
            }
            state.wos[id] = $.extend({}, state.wos[id] || {}, res.wo);
            state.plans[id] = res.lines || [];
            if (state.active == id) { renderWoList(); renderPlan(); }
            renderItems();
        }).fail(function () { if (state.active == id) $('#planBody').html('<div class="mr-empty-big text-danger"><i class="fas fa-exclamation-circle"></i>' + esc(MR_T.wo_failed) + '</div>'); });
    }
    function renderPlan() {
        var id = state.active, w = state.wos[id], lines = state.plans[id] || [];
        $('#planTitle').text(MR_T.plan_title + ' · ' + w.wo_number);
        $('#planSub').text([w.fg_name || '', w.planned_qty ? fmt(w.planned_qty) + ' ' + (w.fg_unit || '') : '', w.sc_number || ''].filter(Boolean).join(' · '));
        if (!lines.length) {
            $('#planActions').prop('hidden', true);
            $('#planBody').html('<div class="mr-empty-big"><i class="fas fa-box-open"></i>' + esc(MR_T.plan_none) + '</div>');
            return;
        }
        $('#planActions').prop('hidden', false);
        var html = '<div class="mr-plan-wrap"><table class="pp-table"><thead><tr>'
            + '<th style="width:36px"><input type="checkbox" id="planAll"></th>'
            + '<th>' + esc(MR_T.th_item) + '</th><th class="num">' + esc(MR_T.th_plan) + '</th><th class="num">' + esc(MR_T.th_requested) + '</th>'
            + '<th class="num">' + esc(MR_T.th_remaining) + '</th><th class="num">' + esc(MR_T.th_stock) + '</th><th class="num">' + esc(MR_T.th_qty) + '</th></tr></thead><tbody>';
        lines.forEach(function (l, i) {
            var inMr = findItem(id, l.id, l.type);
            var def = inMr ? inMr.qty : l.remaining;
            var check = !inMr && l.remaining > 0;
            html += '<tr class="' + (inMr ? 'is-in-mr' : '') + '" data-i="' + i + '">'
                + '<td><input type="checkbox" class="plan-chk"' + (check ? ' checked' : '') + '></td>'
                + '<td><div class="pp-item-name">' + esc(l.name || '-') + '</div><div class="pp-item-code">' + esc(l.code) + ' · ' + esc(l.type) + '</div>'
                + (inMr ? '<span class="pp-chip pp-chip--ok mt-1"><i class="fas fa-check"></i>' + esc(MR_T.in_mr) + ': ' + fmt(inMr.qty) + '</span>' : '')
                + (!inMr && l.remaining <= 0 ? '<span class="pp-chip mt-1">' + esc(MR_T.fully_req) + '</span>' : '') + '</td>'
                + '<td class="num">' + fmt(l.planned) + ' <span class="pp-item-code">' + esc(l.unit) + '</span></td>'
                + '<td class="num"' + (l.mr_numbers ? ' title="' + esc(l.mr_numbers) + '"' : '') + '>' + (l.requested > 0 ? fmt(l.requested) + (l.mr_numbers ? ' <i class="fas fa-info-circle text-muted"></i>' : '') : '<span class="text-muted">0</span>') + '</td>'
                + '<td class="num"><b>' + fmt(l.remaining) + '</b></td>'
                + '<td class="num' + (l.stock < (def || 0) ? ' text-danger font-weight-bold' : '') + '">' + fmt(l.stock) + '</td>'
                + '<td class="num"><input type="text" inputmode="decimal" class="form-control form-control-sm mr-qty plan-qty" value="' + (def > 0 ? r4(def) : '') + '" placeholder="0"></td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        $('#planBody').html(html);
        updatePlanSel();
    }
    function updatePlanSel() {
        var n = 0;
        $('#planBody tbody tr').each(function () { if ($(this).find('.plan-chk').prop('checked') && num($(this).find('.plan-qty').val()) > 0) n++; });
        $('#planSelCount').text(n); $('#btnPlanAdd').prop('disabled', n === 0);
        var all = $('#planBody .plan-chk'), on = all.filter(':checked');
        $('#planAll').prop({ checked: all.length && on.length === all.length, indeterminate: on.length > 0 && on.length < all.length });
    }
    $('#planBody').on('change', '.plan-chk', updatePlanSel);
    $('#planBody').on('change', '#planAll', function () { $('#planBody .plan-chk').prop('checked', this.checked); updatePlanSel(); });
    $('#planBody').on('input', '.plan-qty', function () {
        var $tr = $(this).closest('tr'); if (num(this.value) > 0) $tr.find('.plan-chk').prop('checked', true); updatePlanSel();
    });
    $('#btnPlanRemaining').on('click', function () {
        var lines = state.plans[state.active] || [];
        $('#planBody tbody tr').each(function () {
            var l = lines[$(this).data('i')]; if (!l) return;
            $(this).find('.plan-qty').val(l.remaining > 0 ? r4(l.remaining) : '');
            $(this).find('.plan-chk').prop('checked', l.remaining > 0);
        });
        updatePlanSel();
    });
    $('#btnPlanAdd').on('click', function () {
        var id = state.active, w = state.wos[id], lines = state.plans[id] || [], added = 0, updated = 0;
        $('#planBody tbody tr').each(function () {
            if (!$(this).find('.plan-chk').prop('checked')) return;
            var l = lines[$(this).data('i')], q = r4(num($(this).find('.plan-qty').val()));
            if (!l || q <= 0) return;
            var ex = findItem(id, l.id, l.type);
            if (ex) { ex.qty = q; updated++; }
            else { state.items.push({ key: key(id, l.id, l.type), wo: id, id: l.id, type: l.type, code: l.code, name: l.name, unit: l.unit, stock: l.stock, qty: q, notes: '' }); added++; }
        });
        if (added) toast('success', MR_T.added.replace('%d', added));
        if (updated) toast('info', MR_T.updated.replace('%d', updated));
        renderItems(); renderPlan(); renderWoList();
    });

    // ---------- tambah item manual ----------
    var manualSel = null;
    if ($.fn.select2) {
        $('#input_component').select2({
            theme: 'bootstrap4', width: '100%', placeholder: MR_T.search_ph, minimumInputLength: 1, allowClear: true,
            ajax: { url: MR_URL.search, dataType: 'json', delay: 250, data: function (p) { return { q: p.term }; },
                processResults: function (d) { return { results: d.results || [] }; } }
        }).on('select2:select', function (e) {
            manualSel = e.params.data;
            $('#input_meta').text('· ' + MR_T.stock + ' ' + fmt(manualSel.stock) + ' ' + (manualSel.unit || ''));
            $('#input_qty').trigger('focus'); updateAddBtn();
        }).on('select2:clear', function () { manualSel = null; $('#input_meta').text(''); updateAddBtn(); });
    }
    function updateAddBtn() { $('#btn_add_item').prop('disabled', !(state.active && manualSel && num($('#input_qty').val()) > 0)); }
    $('#input_qty').on('input', updateAddBtn).on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#btn_add_item').trigger('click'); } });
    $('#input_notes').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#btn_add_item').trigger('click'); } });
    $('#btn_add_item').on('click', function () {
        if (!state.active || !manualSel) return;
        var q = r4(num($('#input_qty').val())); if (q <= 0) return;
        var parts = String(manualSel.id).split('|'), id = parseInt(parts[0], 10), type = parts[1];
        var ex = findItem(state.active, id, type), notes = $.trim($('#input_notes').val());
        if (ex) { ex.qty = r4(ex.qty + q); if (notes) ex.notes = ex.notes ? ex.notes + '; ' + notes : notes; toast('info', MR_T.merged); }
        else { state.items.push({ key: key(state.active, id, type), wo: state.active, id: id, type: type, code: manualSel.code, name: manualSel.name, unit: manualSel.unit, stock: manualSel.stock, qty: q, notes: notes }); toast('success', MR_T.added.replace('%d', 1)); }
        $('#input_component').val(null).trigger('change'); manualSel = null;
        $('#input_qty, #input_notes').val(''); $('#input_meta').text('');
        renderItems(); renderWoList(); if (state.plans[state.active]) renderPlan();
        setTimeout(function () { $('#input_component').select2('open'); }, 50);
    });

    // ---------- tabel item ----------
    function flags(it) {
        var f = [], l = planLine(it.wo, it.id, it.type);
        if (it.qty > it.stock + 0.00001) f.push('stock');
        if (l && it.qty > l.remaining + 0.00001) f.push('plan');
        if (state.plans[it.wo] && !l) f.push('extra');
        return f;
    }
    function renderItems() {
        var $tb = $('#items_tbody').empty();
        $('#item_count').text(state.items.length);
        if (!state.items.length) {
            $tb.html('<tr><td colspan="5" class="pp-empty"><i class="fas fa-inbox"></i>' + esc(MR_T.no_items) + '</td></tr>');
        }
        var cStock = 0, cPlan = 0, woUsed = {};
        state.woOrder.forEach(function (wid) {
            var rows = state.items.filter(function (it) { return it.wo == wid; });
            if (!rows.length) return;
            woUsed[wid] = 1;
            var w = state.wos[wid];
            $tb.append('<tr class="mr-group"><td colspan="5"><i class="fas fa-clipboard-check mr-1"></i>' + esc(w.wo_number) + ' <span class="font-weight-normal" style="color:var(--pp-muted)">· ' + esc(w.fg_name || '') + ' · ' + rows.length + ' item</span></td></tr>');
            rows.forEach(function (it) {
                var f = flags(it);
                if (f.indexOf('stock') !== -1) cStock++;
                if (f.indexOf('plan') !== -1) cPlan++;
                $tb.append('<tr data-key="' + esc(it.key) + '">'
                    + '<td><div class="pp-item-name">' + esc(it.name || '-') + '</div><div class="pp-item-code">' + esc(it.code) + '</div>'
                    + (f.indexOf('stock') !== -1 ? '<span class="mr-flag mr-flag--stock"><i class="fas fa-exclamation-circle"></i>' + esc(MR_T.flag_stock) + '</span>' : '')
                    + (f.indexOf('plan') !== -1 ? '<span class="mr-flag mr-flag--plan"><i class="fas fa-exclamation-triangle"></i>' + esc(MR_T.flag_plan) + '</span>' : '')
                    + (f.indexOf('extra') !== -1 ? '<span class="mr-flag mr-flag--extra"><i class="fas fa-plus-circle"></i>' + esc(MR_T.flag_extra) + '</span>' : '') + '</td>'
                    + '<td class="num' + (f.indexOf('stock') !== -1 ? ' text-danger font-weight-bold' : '') + '">' + fmt(it.stock) + '</td>'
                    + '<td class="num text-nowrap"><input type="text" inputmode="decimal" class="form-control form-control-sm mr-qty item-qty" value="' + r4(it.qty) + '"> <span class="pp-item-code">' + esc(it.unit || '') + '</span></td>'
                    + '<td><input type="text" class="form-control form-control-sm mr-notes item-notes" maxlength="255" value="' + esc(it.notes) + '"></td>'
                    + '<td class="text-right"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 item-del" title="Hapus"><i class="fas fa-trash-alt"></i></button></td>'
                    + '</tr>');
            });
        });
        var nWo = Object.keys(woUsed).length;
        $('#sumItems').text(state.items.length); $('#sumWos').text(nWo); $('#sumStock').text(cStock); $('#sumPlan').text(cPlan);
        $('#barInfo').html(state.items.length ? sprintf(MR_T.bar, state.items.length, nWo) : esc(MR_T.bar_empty));
    }
    function itemByRow(el) { var k = $(el).closest('tr').data('key'); for (var i = 0; i < state.items.length; i++) if (state.items[i].key === k) return state.items[i]; return null; }
    $('#items_tbody').on('change', '.item-qty', function () {
        var it = itemByRow(this); if (!it) return;
        var q = r4(num(this.value));
        if (q <= 0) { $(this).addClass('is-invalid'); return; }
        it.qty = q; renderItems(); if (state.active == it.wo && state.plans[it.wo]) renderPlan();
    });
    $('#items_tbody').on('input', '.item-notes', function () { var it = itemByRow(this); if (it) it.notes = this.value; });
    $('#items_tbody').on('click', '.item-del', function () {
        var it = itemByRow(this); if (!it) return;
        state.items = state.items.filter(function (x) { return x !== it; });
        renderItems(); renderWoList(); if (state.active == it.wo && state.plans[it.wo]) renderPlan();
    });

    // ---------- simpan ----------
    $('#mrAddForm').on('submit', function (e) {
        if (submitting) return;
        e.preventDefault();
        var errs = [];
        if (!$('#mr_request_date').val()) errs.push(MR_T.v_date);
        if (!$('#mr_dept').val()) errs.push(MR_T.v_dept);
        if (!state.items.length) errs.push(MR_T.v_items);
        if (state.items.some(function (it) { return !(it.qty > 0); }) || $('#items_tbody .item-qty.is-invalid').length) errs.push(MR_T.v_qty);
        if (errs.length) { Swal.fire({ icon: 'warning', title: MR_T.err_title, html: errs.map(esc).join('<br>') }); return; }

        var cStock = 0, cPlan = 0;
        state.items.forEach(function (it) { var f = flags(it); if (f.indexOf('stock') !== -1) cStock++; if (f.indexOf('plan') !== -1) cPlan++; });
        var form = this;
        function go() {
            $('#items_payload').val(JSON.stringify(state.items.map(function (it) {
                return { wo_header_id: it.wo, component_id: it.id, component_type: it.type, qty: it.qty, notes: it.notes || '' };
            })));
            submitting = true;
            $('#btnSubmitMr').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>' + esc(MR_T.saving));
            form.submit();
        }
        if (cStock || cPlan) {
            var msg = []; if (cStock) msg.push(MR_T.warn_stock.replace('%d', cStock)); if (cPlan) msg.push(MR_T.warn_plan.replace('%d', cPlan));
            Swal.fire({ icon: 'question', title: MR_T.warn_title, html: msg.map(esc).join('<br>'), showCancelButton: true,
                confirmButtonText: MR_T.warn_go, cancelButtonText: MR_T.back }).then(function (r) { if (r.isConfirmed) go(); });
        } else go();
    });
    // Tandai ada perubahan: setiap aksi di form setelah halaman siap.
    $('#mrAddForm').on('input change', 'input, select, textarea', function () { dirty = true; });
    $(document).on('click', '#btnPlanAdd, #btn_add_item, .item-del, [data-remove], .mr-wo-row', function () { dirty = true; });
    window.addEventListener('beforeunload', function (e) { if (dirty && !submitting) { e.preventDefault(); e.returnValue = MR_T.leave; return MR_T.leave; } });

    // ---------- awal ----------
    (MR_PREFILL.wos || []).forEach(function (w) { state.wos[w.id] = w; state.woOrder.push(w.id); });
    (MR_PREFILL.items || []).forEach(function (it) { it.key = key(it.wo, it.id, it.type); state.items.push(it); });
    renderWoList(); renderItems();
    if (state.woOrder.length) {
        // Rencana tiap WO dimuat supaya tanda "melebihi rencana" di tabel item akurat.
        state.woOrder.slice(1).forEach(function (wid) { loadPlan(wid); });
        setActive(state.woOrder[0]);
    }
    var qWo = parseInt(new URLSearchParams(location.search).get('wo_id') || '0', 10);
    if (qWo) { state.wos[qWo] = { id: qWo, wo_number: '#' + qWo }; state.woOrder.push(qWo); setActive(qWo); }
});
</script>
