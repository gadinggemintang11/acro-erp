<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Halaman tahap Proses Produksi -- SATU tampilan untuk semua tahap
 * (redesign 2026-11-08; menggantikan cutting_view / stage_view / finishing_view).
 *
 *   Cutting    : bahan = material dari MR/WIP           -> hasil semi-finished / scrap
 *   Sewing     : bahan = hasil Cutting (+ material)     -> hasil semi-finished / scrap
 *   Assembling : bahan = hasil Sewing (+ material)      -> hasil semi-finished / scrap
 *   Finishing  : bahan = hasil Assembling (+ material)  -> Barang Jadi (FG milik WO) / scrap
 *
 * Bahan & hasil hanya bisa dicatat saat status Draft DAN waktu Mulai tahap
 * sudah diisi (lihat _stage_time.php; dijaga juga di controller).
 */
$this->load->view('production_process/_pp_helpers');
$this->load->view('production_process/_pp_style');

$st          = $current_stage;
$prev        = $previous_stage;                       // null untuk cutting
$is_finish   = ($st === 'finishing');
$st_label    = lang_line('pp_stage_' . $st);
$prev_label  = $prev ? lang_line('pp_stage_' . $prev) : '';
$meta        = pp_stage_meta($st);
$is_draft    = ($header->status === 'Draft');
$can_edit    = $is_draft && check_access('production_process', 'edit');
$can_delete  = $is_draft && check_access('production_process', 'delete');
$can_qc      = $header->status !== 'Completed' && (check_access('production_process', 'add') || check_access('production_process', 'qc'));
$time_ok     = !$stage_times_ready || !empty($stage_time->started_at);
$base        = 'production_process/';

// URL aksi per tahap
$url_prev_in  = $prev ? base_url($base . 'save_' . $st . '_' . $prev . '_input/' . $header->id) : '';
$url_mat_in   = base_url($base . ($st === 'cutting' ? 'save_cutting_material_usage/' : 'save_' . $st . '_additional_input/') . $header->id);
$url_output   = base_url($base . ($is_finish ? 'save_finishing_output/' : 'save_' . $st . '_output/') . $header->id);

// Ringkasan angka
$sum_in = 0; foreach ($current_inputs as $r) $sum_in += (float) $r->qty_used;
$sum_good = 0; $sum_scrap = 0; $qc_wait = 0;
foreach ($current_outputs as $r) {
    if ($r->item_type === 'scrap') $sum_scrap += (float) $r->qty_produced; else $sum_good += (float) $r->qty_produced;
    if (empty($r->qc_checked_by)) $qc_wait++;
}
$prev_avail = array_values(array_filter($available_previous_outputs ?? [], function ($o) { return (float) $o->remaining_qty > 0.00001; }));
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">

    <?php $this->load->view('production_process/_pp_hero'); ?>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert">
            <i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>
        </div>
    <?php endif; endforeach; ?>

    <?php $this->load->view('production_process/_stage_tabs'); ?>

    <?php $this->load->view('production_process/_stage_time'); ?>

    <div class="row">
        <!-- ======================= BAHAN MASUK ======================= -->
        <div class="col-xl-6">
            <div class="pp-card <?= $meta['tone']; ?>">
                <div class="pp-card-head">
                    <h2>
                        <span class="pp-ico"><i class="fas fa-sign-in-alt"></i></span>
                        <span><?= lang_line('pp2_inputs_title', 'Bahan Masuk'); ?>
                            <span class="d-block pp-sub"><?= $prev
                                ? sprintf(lang_line('pp2_inputs_sub_prev', 'Hasil %s yang sudah lolos QC, ditambah material bila perlu'), htmlspecialchars($prev_label))
                                : lang_line('pp2_inputs_sub_mat', 'Material dari Material Request untuk Work Order ini'); ?></span>
                        </span>
                    </h2>
                    <?php if ($prev && $can_edit && $time_ok): ?>
                    <ul class="nav pp-seg" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ppInPrev" role="tab"><i class="fas fa-level-down-alt"></i><?= sprintf(lang_line('pp2_tab_from_prev', 'Hasil %s'), htmlspecialchars($prev_label)); ?></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ppInMat" role="tab"><i class="fas fa-boxes"></i><?= lang_line('pp2_tab_material', 'Material'); ?></a></li>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="pp-card-body">
                    <?php if (!$is_draft): ?>
                        <div class="pp-readonly"><i class="fas fa-lock"></i><span><?= lang_line('pp2_readonly_status', 'Dokumen sudah dikonfirmasi — bahan & hasil tidak bisa diubah lagi.'); ?></span></div>
                    <?php elseif (!$can_edit): ?>
                        <div class="pp-readonly"><i class="fas fa-eye"></i><span><?= lang_line('pp2_readonly_perm', 'Anda hanya punya akses lihat.'); ?></span></div>
                    <?php elseif (!$time_ok): ?>
                        <div class="pp-locked"><i class="fas fa-clock"></i>
                            <div><?= sprintf(lang_line('pp_time_required_msg'), htmlspecialchars($st_label)); ?>
                                <a href="#ppStageTime" class="font-weight-bold ml-1" style="color:inherit;text-decoration:underline"><?= lang_line('pp2_go_fill_time', 'Isi waktu proses'); ?></a></div>
                        </div>
                    <?php else: ?>
                    <div class="tab-content">
                        <?php if ($prev): ?>
                        <!-- Hasil tahap sebelumnya -->
                        <div class="tab-pane fade show active" id="ppInPrev" role="tabpanel">
                            <form id="formInputPrev" method="post" action="<?= $url_prev_in; ?>" class="pp-qty-form" data-qty="#prev_qty_used" data-select="#prev_output_selector">
                                <input type="hidden" name="item_id" id="prev_item_id">
                                <input type="hidden" name="item_type" id="prev_item_type">
                                <div class="form-group">
                                    <label class="pp-label" for="prev_output_selector"><?= sprintf(lang_line('pp_label_pick_prev_qc'), htmlspecialchars($prev_label)); ?></label>
                                    <select name="source_output_id" id="prev_output_selector" class="form-control pp-select" required>
                                        <option value=""><?= sprintf(lang_line('pp_opt_pick_prev_result'), htmlspecialchars($prev_label)); ?></option>
                                        <?php foreach ($prev_avail as $o): ?>
                                            <option value="<?= (int) $o->output_id; ?>" data-itemid="<?= (int) $o->item_id; ?>" data-itemtype="<?= htmlspecialchars($o->item_type); ?>"
                                                    data-maxqty="<?= (float) $o->remaining_qty; ?>" data-unit="<?= htmlspecialchars($o->item_unit ?? ''); ?>">
                                                <?= htmlspecialchars(($o->item_name ?? '-') . ($o->item_code ? ' (' . $o->item_code . ')' : '') . ' — ' . lang_line('pp_label_remaining') . ' ' . pp_qty($o->remaining_qty) . ' ' . ($o->item_unit ?? '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($prev_avail)): ?>
                                        <small class="pp-hint text-warning"><i class="fas fa-info-circle mr-1"></i><?= sprintf(lang_line('pp_opt_no_prev_qc_avail'), htmlspecialchars($prev_label)); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-5 form-group">
                                        <label class="pp-label" for="prev_qty_used"><?= lang_line('pp_label_qty_used'); ?></label>
                                        <input type="text" inputmode="decimal" name="qty_used" id="prev_qty_used" class="form-control" required autocomplete="off">
                                        <small class="pp-hint"><?= lang_line('pp_label_max'); ?> <b class="pp-max">0</b> <span class="pp-unit"></span></small>
                                    </div>
                                    <div class="col-md-7 form-group">
                                        <label class="pp-label" for="prev_notes"><?= lang_line('pp_label_notes'); ?></label>
                                        <input type="text" name="notes" id="prev_notes" class="form-control" maxlength="255">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-tone"><i class="fas fa-plus mr-1"></i><?= sprintf(lang_line('pp_btn_add_input_prev_named'), htmlspecialchars($prev_label)); ?></button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <!-- Material dari MR/WIP -->
                        <div class="tab-pane fade<?= $prev ? '' : ' show active'; ?>" id="ppInMat" role="tabpanel">
                            <form id="formInputAdd" method="post" action="<?= $url_mat_in; ?>" class="pp-qty-form" data-qty="#add_qty_used" data-select="#add_batch_selector">
                                <input type="hidden" name="item_id" id="add_item_id">
                                <input type="hidden" name="item_type" id="add_item_type">
                                <div class="form-group">
                                    <label class="pp-label" for="add_batch_selector"><?= lang_line('pp_label_select_material_fifo'); ?></label>
                                    <select name="batch_id" id="add_batch_selector" class="form-control pp-select" required>
                                        <option value=""><?= lang_line('pp_opt_select_batch'); ?></option>
                                        <?php foreach ($available_batches as $b): ?>
                                            <option value="<?= htmlspecialchars($b->batch_id); ?>" data-itemid="<?= (int) $b->item_id; ?>" data-itemtype="<?= htmlspecialchars($b->item_type); ?>"
                                                    data-maxqty="<?= (float) $b->remaining_qty; ?>" data-unit="<?= htmlspecialchars($b->item_unit ?? ''); ?>">
                                                <?= htmlspecialchars($b->item_name . ' (' . $b->item_code . ') · Batch ' . ($b->grn_number ?? 'WIP') . ' — ' . lang_line('pp_label_remaining') . ' ' . pp_qty($b->remaining_qty) . ' ' . ($b->item_unit ?? '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($available_batches)): ?>
                                        <small class="pp-hint text-warning"><i class="fas fa-info-circle mr-1"></i><?= lang_line('pp_opt_no_batch'); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-5 form-group">
                                        <label class="pp-label" for="add_qty_used"><?= lang_line('pp_label_qty_used'); ?></label>
                                        <input type="text" inputmode="decimal" name="qty_used" id="add_qty_used" class="form-control" required autocomplete="off">
                                        <small class="pp-hint"><?= lang_line('pp_label_remaining'); ?> <b class="pp-max">0</b> <span class="pp-unit"></span></small>
                                    </div>
                                    <div class="col-md-7 form-group">
                                        <label class="pp-label" for="add_notes"><?= lang_line('pp_label_notes'); ?></label>
                                        <input type="text" name="notes" id="add_notes" class="form-control" maxlength="255">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-tone"><i class="fas fa-plus mr-1"></i><?= lang_line('pp_btn_add_input_material'); ?></button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="pp-table">
                        <thead><tr>
                            <th><?= lang_line('pp_th_item'); ?></th>
                            <th><?= lang_line('pp_th_source'); ?></th>
                            <th class="num"><?= lang_line('pp_th_qty'); ?></th>
                            <th><?= lang_line('pp_th_notes'); ?></th>
                            <?php if ($can_delete): ?><th class="text-right"><?= lang_line('pp_th_action'); ?></th><?php endif; ?>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($current_inputs)): ?>
                            <tr><td colspan="5" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pp_no_input'); ?></td></tr>
                        <?php else: foreach ($current_inputs as $in):
                            $from_prev = !empty($in->source_output_id); ?>
                            <tr>
                                <td>
                                    <div class="pp-item-name"><?= htmlspecialchars($in->item_name ?? '-'); ?></div>
                                    <?php if (!empty($in->item_code)): ?><div class="pp-item-code"><?= htmlspecialchars($in->item_code); ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($from_prev): ?>
                                        <span class="pp-tag <?= pp_stage_meta($in->source_output_stage ?? $prev)['tone']; ?>"><i class="fas fa-level-down-alt mr-1"></i><?= sprintf(lang_line('pp2_src_prev', 'Hasil %s'), htmlspecialchars(lang_line('pp_stage_' . ($in->source_output_stage ?? $prev), ucfirst((string) ($in->source_output_stage ?? $prev))))); ?></span>
                                    <?php else: ?>
                                        <span class="pp-tag tone-slate"><i class="fas fa-boxes mr-1"></i><?= lang_line('pp2_src_material', 'Material'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><b><?= pp_qty($in->qty_used); ?></b> <span class="pp-item-code"><?= htmlspecialchars($in->item_unit ?? $in->uom_code ?? ''); ?></span></td>
                                <td><span class="pp-item-code" style="font-size:.8rem"><?= htmlspecialchars($in->notes ?: '—'); ?></span></td>
                                <?php if ($can_delete): ?>
                                <td class="text-right">
                                    <a href="<?= base_url($base . 'delete_' . $st . '_input/' . $header->id . '/' . $in->id); ?>" class="btn btn-outline-danger btn-sm btn-delete py-0 px-2"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('pp_delete_input_full'), $in->item_name ?? '')); ?>" title="<?= htmlspecialchars(lang_line('btn_delete', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pp-foot">
                    <span><?= count($current_inputs); ?> <?= lang_line('pp2_rows', 'baris'); ?></span>
                    <span><?= lang_line('pp2_total_qty', 'Total qty'); ?>: <b><?= pp_qty($sum_in); ?></b></span>
                </div>
            </div>
        </div>

        <!-- ======================= HASIL ======================= -->
        <div class="col-xl-6">
            <div class="pp-card <?= $meta['tone']; ?>">
                <div class="pp-card-head">
                    <h2>
                        <span class="pp-ico"><i class="fas fa-sign-out-alt"></i></span>
                        <span><?= lang_line('pp2_outputs_title', 'Hasil Proses'); ?>
                            <span class="d-block pp-sub"><?= $is_finish
                                ? lang_line('pp2_outputs_sub_fg', 'Barang Jadi sesuai Work Order, plus scrap bila ada')
                                : lang_line('pp2_outputs_sub_semi', 'Barang setengah jadi untuk tahap berikutnya, plus scrap bila ada'); ?></span>
                        </span>
                    </h2>
                    <?php if ($can_edit && $time_ok): ?>
                    <ul class="nav pp-seg" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ppOutGood" role="tab"><i class="fas <?= $is_finish ? 'fa-box' : 'fa-cube'; ?>"></i><?= $is_finish ? lang_line('pp2_tab_fg', 'Barang Jadi') : lang_line('pp2_tab_semi', 'Semi-finished'); ?></a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ppOutScrap" role="tab"><i class="fas fa-recycle"></i><?= lang_line('pp2_tab_scrap', 'Scrap'); ?></a></li>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="pp-card-body">
                    <?php if (!$is_draft): ?>
                        <div class="pp-readonly"><i class="fas fa-lock"></i><span><?= lang_line('pp2_readonly_status', 'Dokumen sudah dikonfirmasi — bahan & hasil tidak bisa diubah lagi.'); ?></span></div>
                    <?php elseif (!$can_edit): ?>
                        <div class="pp-readonly"><i class="fas fa-eye"></i><span><?= lang_line('pp2_readonly_perm', 'Anda hanya punya akses lihat.'); ?></span></div>
                    <?php elseif (!$time_ok): ?>
                        <div class="pp-locked"><i class="fas fa-clock"></i>
                            <div><?= sprintf(lang_line('pp_time_required_msg'), htmlspecialchars($st_label)); ?>
                                <a href="#ppStageTime" class="font-weight-bold ml-1" style="color:inherit;text-decoration:underline"><?= lang_line('pp2_go_fill_time', 'Isi waktu proses'); ?></a></div>
                        </div>
                    <?php else: ?>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="ppOutGood" role="tabpanel">
                            <form id="formOutput" method="post" action="<?= $url_output; ?>" class="pp-qty-form" data-qty="#output_qty_good">
                                <?php if ($is_finish): ?>
                                    <?php /* Barang Jadi dikunci ke FG milik Work Order (user 2026-09-30);
                                             dijaga juga di server (check_output_fg). */ ?>
                                    <div class="form-group">
                                        <label class="pp-label"><?= lang_line('pp_label_select_fg'); ?></label>
                                        <?php if (!empty($header->fg_id)): ?>
                                            <input type="hidden" name="item_id" value="<?= (int) $header->fg_id; ?>|finished_goods">
                                            <input type="text" class="form-control" readonly value="<?= htmlspecialchars($header->fg_name . ($header->fg_code ? ' (' . $header->fg_code . ')' : '')); ?>">
                                            <small class="pp-hint"><i class="fas fa-lock mr-1"></i><?= sprintf(lang_line('pp_fg_locked_to_wo', 'Mengikuti Finished Good pada %s'), htmlspecialchars($header->wo_number ?: 'Work Order')); ?></small>
                                        <?php else: ?>
                                            <div class="pp-locked"><i class="fas fa-exclamation-triangle"></i><div><?= lang_line('pp_fg_missing', 'Work Order ini tidak punya Finished Good — output tidak bisa dicatat.'); ?></div></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group">
                                        <label class="pp-label" for="output_item_sf"><?= lang_line('pp_label_select_semi'); ?></label>
                                        <select name="item_id" id="output_item_sf" class="form-control pp-select-semi" required style="width:100%"></select>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group" style="max-width:260px">
                                    <label class="pp-label" for="output_qty_good"><?= $is_finish ? lang_line('pp_label_total_qty_produced') : lang_line('pp_label_qty_produced'); ?></label>
                                    <input type="text" inputmode="decimal" name="qty" id="output_qty_good" class="form-control" required autocomplete="off">
                                </div>
                                <button type="submit" class="btn btn-tone" <?= ($is_finish && empty($header->fg_id)) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus mr-1"></i><?= $is_finish ? lang_line('pp_btn_add_fg') : lang_line('pp_btn_add_result'); ?>
                                </button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="ppOutScrap" role="tabpanel">
                            <form id="formOutputScrap" method="post" action="<?= $url_output; ?>" class="pp-qty-form" data-qty="#output_qty_scrap">
                                <div class="form-group">
                                    <label class="pp-label" for="output_item_scrap"><?= lang_line('pp_label_select_scrap'); ?></label>
                                    <select name="item_id" id="output_item_scrap" class="form-control pp-select-scrap" required style="width:100%"></select>
                                </div>
                                <div class="form-group" style="max-width:260px">
                                    <label class="pp-label" for="output_qty_scrap"><?= lang_line('pp_label_qty_scrap'); ?></label>
                                    <input type="text" inputmode="decimal" name="qty" id="output_qty_scrap" class="form-control" required autocomplete="off">
                                </div>
                                <button type="submit" class="btn btn-warning"><i class="fas fa-plus mr-1"></i><?= lang_line('pp_btn_add_scrap'); ?></button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="pp-table">
                        <thead><tr>
                            <th><?= lang_line('pp_th_item'); ?></th>
                            <th><?= lang_line('pp_th_type'); ?></th>
                            <th class="num"><?= lang_line('pp_th_qty'); ?></th>
                            <th><?= lang_line('pp_th_qc_status'); ?></th>
                            <th class="text-right"><?= lang_line('pp_th_action'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($current_outputs)): ?>
                            <tr><td colspan="5" class="pp-empty"><i class="fas fa-box-open"></i><?= lang_line('pp_no_output'); ?></td></tr>
                        <?php else: foreach ($current_outputs as $out):
                            $is_scrap = ($out->item_type === 'scrap');
                            $type_tone = $is_scrap ? 'tone-amber' : ($out->item_type === 'finished_goods' ? 'tone-green' : 'tone-blue');
                            $type_label = $is_scrap ? 'Scrap' : ($out->item_type === 'finished_goods' ? lang_line('pp2_tab_fg', 'Barang Jadi') : 'Semi-finished');
                            $item_txt = ($out->item_name ?? lang_line('pp_unknown_item')) ;
                        ?>
                            <tr data-output-id="<?= (int) $out->id; ?>"
                                data-qc-description="<?= htmlspecialchars($out->qc_description ?? ''); ?>"
                                data-qc-photo="<?= htmlspecialchars($out->qc_photo_url ?? ''); ?>"
                                data-qc-checked-by="<?= htmlspecialchars($out->qc_checked_by_name ?? ''); ?>"
                                data-qc-checked-at="<?= !empty($out->qc_checked_at) ? date('d M Y H:i', strtotime($out->qc_checked_at)) : ''; ?>">
                                <td>
                                    <?php if ($is_finish && !empty($out->size_breakdown)): ?>
                                        <a href="javascript:void(0)" class="pp-item-name show-size-detail" data-sizes="<?= base64_encode($out->size_breakdown); ?>"
                                           data-item="<?= htmlspecialchars($item_txt . (!empty($out->item_code) ? ' (' . $out->item_code . ')' : '')); ?>"><?= htmlspecialchars($item_txt); ?> <i class="fas fa-ruler-combined ml-1 small"></i></a>
                                    <?php else: ?>
                                        <div class="pp-item-name"><?= htmlspecialchars($item_txt); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($out->item_code)): ?><div class="pp-item-code"><?= htmlspecialchars($out->item_code); ?></div><?php endif; ?>
                                </td>
                                <td><span class="pp-tag <?= $type_tone; ?>"><?= htmlspecialchars($type_label); ?></span></td>
                                <td class="num"><b><?= pp_qty($out->qty_produced); ?></b> <span class="pp-item-code"><?= htmlspecialchars($out->item_unit ?? ''); ?></span></td>
                                <td>
                                    <?php if (!empty($out->qc_checked_by)): ?>
                                        <span class="pp-chip pp-chip--ok"><i class="fas fa-check-circle"></i><?= lang_line('pp_badge_qc_ok'); ?></span>
                                        <?php if (!empty($out->qc_description)): ?>
                                            <div class="pp-item-code mt-1" title="<?= htmlspecialchars($out->qc_description); ?>"><?= htmlspecialchars(mb_strimwidth($out->qc_description, 0, 34, '…')); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="pp-chip pp-chip--warn"><i class="fas fa-hourglass-half"></i><?= lang_line('pp_badge_qc_pending'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div class="pp-actions">
                                        <?php if ($is_scrap && check_access('production_process', 'edit')): ?>
                                            <button type="button" class="btn btn-outline-secondary" onclick="initScrapDetailModal('<?= (int) $header->id; ?>', '<?= (int) $out->id; ?>', '<?= $st; ?>')" title="<?= htmlspecialchars(lang_line('pp_scrap_modal_title')); ?>">
                                                <i class="fas fa-search-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($can_qc): ?>
                                            <?php if (empty($out->qc_checked_by)): ?>
                                                <button type="button" class="btn btn-success" onclick="initQcModal('<?= (int) $header->id; ?>', '<?= (int) $out->id; ?>', '<?= $st; ?>')">
                                                    <i class="fas fa-clipboard-check mr-1"></i><?= lang_line('pp_btn_qc'); ?>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-info" onclick="initQcModal('<?= (int) $header->id; ?>', '<?= (int) $out->id; ?>', '<?= $st; ?>', true)" title="<?= htmlspecialchars(lang_line('pp_btn_view')); ?>"><i class="fas fa-eye"></i></button>
                                                <a href="<?= base_url($base . 'unconfirm_qc_' . $st . '/' . $header->id . '/' . $out->id); ?>" class="btn btn-outline-warning pp-confirm-link"
                                                   data-title="<?= htmlspecialchars(lang_line('pp2_qc_undo_title', 'Batalkan QC?')); ?>" data-text="<?= htmlspecialchars($item_txt); ?>" title="<?= htmlspecialchars(lang_line('pp2_qc_undo_title', 'Batalkan QC?')); ?>"><i class="fas fa-undo"></i></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($can_delete): ?>
                                            <a href="<?= base_url($base . 'delete_' . $st . '_output/' . $header->id . '/' . $out->id); ?>" class="btn btn-outline-danger btn-delete"
                                               data-message="<?= htmlspecialchars(sprintf(lang_line('pp_delete_output_confirm'), $item_txt)); ?>" title="<?= htmlspecialchars(lang_line('btn_delete', 'Hapus')); ?>"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pp-foot">
                    <span><?= $is_finish ? lang_line('pp2_tab_fg', 'Barang Jadi') : 'Semi-finished'; ?>: <b><?= pp_qty($sum_good); ?></b> · Scrap: <b><?= pp_qty($sum_scrap); ?></b></span>
                    <span><?= $qc_wait
                        ? '<i class="fas fa-hourglass-half text-warning mr-1"></i>' . sprintf(lang_line('pp2_qc_waiting', '%d menunggu QC'), $qc_wait)
                        : (count($current_outputs) ? '<i class="fas fa-check-circle text-success mr-1"></i>' . lang_line('pp2_qc_all_ok', 'Semua sudah QC') : ''); ?></span>
                </div>
            </div>

            <?php if ($st !== 'finishing' && $sum_good > 0): ?>
                <?php $next = $pp_stages[$stage_index + 1] ?? null; if ($next): ?>
                <a href="<?= base_url($base . $next . '/' . $header->id); ?>" class="pp-card d-flex align-items-center justify-content-between px-3 py-3 <?= pp_stage_meta($next)['tone']; ?>" style="text-decoration:none;color:inherit">
                    <span><span class="pp-sub d-block"><?= lang_line('pp2_next_stage', 'Tahap berikutnya'); ?></span>
                        <b><i class="fas <?= pp_stage_meta($next)['icon']; ?> mr-1" style="color:var(--t)"></i><?= htmlspecialchars(lang_line('pp_stage_' . $next)); ?></b></span>
                    <i class="fas fa-arrow-right" style="color:var(--t)"></i>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $this->load->view('production_process/_pp_modals'); ?>
<?php $this->load->view('production_process/_pp_script'); ?>
