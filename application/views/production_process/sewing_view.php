<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<style>
    .select2-container { width: 100% !important; z-index: 1050; }
    .select2-dropdown { z-index: 1051 !important; }
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.5em + .75rem + 2px) !important;
        padding: .375rem .75rem !important;
        line-height: 1.5; border-radius: .25rem;
    }
    .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .2rem; }
    .is-invalid + .select2-container--bootstrap4 .select2-selection--single { border-color: #e74a3b; }
    .table th, .table td { vertical-align: middle; padding: 0.5rem; }
</style>

<div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h4 mb-0 text-gray-800">
                        <i class="fas fa-cut text-primary mr-2"></i><?= htmlspecialchars($title); ?>
                        <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->production_number); ?></span>
                    </h3>
                    <div>
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                            <a href="<?= base_url('production_process/confirm/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1"
                               onclick="return confirm('<?= addslashes(lang_line('pp_confirm_process_msg_short')); ?>');">
                                <i class="fas fa-check-circle fa-sm"></i> <?= lang_line('pp_btn_confirm_process'); ?>
                            </a>
                        <?php elseif ($header->status == 'In Progress' && check_access('production_process', 'edit')): ?>
                            <a href="<?= base_url('production_process/complete_production/'.$header->id); ?>" class="btn btn-success btn-sm mr-1"
                               onclick="return confirm('<?= addslashes(lang_line('pp_finish_production_msg')); ?>');">
                                <i class="fas fa-flag-checkered fa-sm"></i> <?= lang_line('pp_btn_finish_production'); ?>
                            </a>
                            <a href="<?= base_url('production_process/unconfirm/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1"
                               onclick="return confirm('<?= addslashes(lang_line('pp_cancel_confirm_msg')); ?>');">
                                <i class="fas fa-undo fa-sm"></i> <?= lang_line('pp_btn_cancel_confirm'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('production_process/export_sewing_pdf/'.$header->id); ?>" target="_blank" class="btn btn-danger btn-sm mr-1">
                            <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('pp_btn_pdf'); ?> (<?= lang_line('pp_stage_sewing'); ?>)
                        </a>
                        <a href="<?= base_url('production_process'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('pp_btn_back'); ?></a>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-fill mb-4">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/cutting/'.$header->id); ?>"><?= lang_line('pp_tab_1_cutting'); ?></a></li>
                    <li class="nav-item"><a class="nav-link active font-weight-bold" href="#"><?= lang_line('pp_tab_2_sewing'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/assembling/'.$header->id); ?>"><?= lang_line('pp_tab_3_assembling'); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('production_process/finishing/'.$header->id); ?>"><?= lang_line('pp_tab_4_finishing'); ?></a></li>
                </ul>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4"><strong><?= lang_line('pp_info_prod_no'); ?></strong> <?= htmlspecialchars($header->production_number); ?></div>
                            <div class="col-md-4"><strong><?= lang_line('pp_info_wo_no'); ?></strong> <?= htmlspecialchars($header->wo_number); ?></div>
                            <div class="col-md-4"><strong><?= lang_line('pp_info_status'); ?></strong>
                                <?php
                                    $badge = 'secondary';
                                    if ($header->status == 'Draft') $badge = 'warning';
                                    if ($header->status == 'In Progress') $badge = 'primary';
                                    if ($header->status == 'Completed') $badge = 'success';
                                    $status_key_map = ['Draft' => 'pp_status_draft', 'In Progress' => 'pp_status_in_progress', 'Completed' => 'pp_status_completed'];
                                    $status_label = isset($status_key_map[$header->status]) ? lang_line($status_key_map[$header->status]) : $header->status;
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $status_label; ?></span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong><?= lang_line('pp_info_fg'); ?></strong> <?= htmlspecialchars($header->fg_name); ?></div>
                            <div class="col-md-4"><strong><?= lang_line('pp_info_prod_date_short'); ?></strong> <?= date('d M Y', strtotime($header->production_date)); ?></div>
                            <div class="col-md-4"><strong><?= lang_line('pp_info_planned_qty'); ?></strong> <?= number_format($header->planned_qty, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- ===================== LEFT COLUMN ===================== -->
                    <div class="col-lg-6">

                        <!-- Input dari Cutting -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-info text-white py-3"><h6 class="m-0 font-weight-bold"><?= sprintf(lang_line('pp_card_input_prev_stage'), lang_line('pp_stage_cutting')); ?></h6></div>
                            <div class="card-body">
                                <form id="formInputPrev" method="post" action="<?= base_url('production_process/save_sewing_cutting_input/'.$header->id); ?>">
                                    <input type="hidden" name="item_id" id="prev_item_id">
                                    <input type="hidden" name="item_type" id="prev_item_type">

                                    <div class="form-group">
                                        <label for="prev_output_selector"><?= sprintf(lang_line('pp_label_select_prev_output'), lang_line('pp_stage_cutting')); ?></label>
                                        <select name="source_output_id" id="prev_output_selector" class="form-control select2-wip" required>
                                            <option value=""><?= sprintf(lang_line('pp_opt_select_prev'), lang_line('pp_stage_cutting')); ?></option>
                                            <?php if(!empty($available_previous_outputs)): ?>
                                                <?php foreach($available_previous_outputs as $out): ?>
                                                    <?php if($out->remaining_qty > 0): ?>
                                                    <option value="<?= $out->output_id; ?>"
                                                            data-itemid="<?= $out->item_id; ?>"
                                                            data-itemtype="<?= $out->item_type; ?>"
                                                            data-maxqty="<?= $out->remaining_qty; ?>">
                                                        <?= htmlspecialchars($out->item_name.' ('.$out->item_code.') - '.lang_line('pp_label_remaining').' '.number_format($out->remaining_qty,4).' '.$out->item_unit); ?>
                                                    </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled><?= sprintf(lang_line('pp_opt_no_prev_output'), lang_line('pp_stage_cutting')); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label><?= lang_line('pp_label_qty_used'); ?></label>
                                            <input type="number" step="0.0001" name="qty_used" id="prev_qty_used" class="form-control" required min="0.0001" max="0">
                                            <small class="form-text text-muted"><?= lang_line('pp_label_max'); ?> <span id="prev_max_qty">0.0000</span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label><?= lang_line('pp_label_notes'); ?></label>
                                            <input type="text" name="notes" id="prev_notes" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm mt-2"><i class="fas fa-plus mr-1"></i> <?= sprintf(lang_line('pp_btn_add_input_prev'), lang_line('pp_stage_cutting')); ?></button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Material Tambahan -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-primary text-white py-3"><h6 class="m-0 font-weight-bold"><?= lang_line('pp_card_input_warehouse_add'); ?></h6></div>
                            <div class="card-body">
                                <form id="formInputAdd" method="post" action="<?= base_url('production_process/save_sewing_additional_input/'.$header->id); ?>">
                                    <input type="hidden" name="item_id" id="add_item_id">
                                    <input type="hidden" name="item_type" id="add_item_type">
                                    <div class="form-group">
                                        <label><?= lang_line('pp_label_select_material'); ?></label>
                                        <select name="stock_on_hand_id" id="add_batch_selector" class="form-control select2-wip" required>
                                            <option value=""><?= lang_line('pp_opt_select_batch'); ?></option>
                                            <?php if(!empty($available_batches)): ?>
                                                <?php foreach($available_batches as $batch): ?>
                                                    <option value="<?= $batch->stock_on_hand_id; ?>"
                                                            data-itemid="<?= $batch->item_id; ?>"
                                                            data-itemtype="<?= $batch->item_type; ?>"
                                                            data-maxqty="<?= $batch->remaining_qty; ?>">
                                                        <?= htmlspecialchars($batch->item_name.' ('.$batch->item_code.') - Batch: '.($batch->grn_number ?? 'N/A').' - '.lang_line('pp_label_remaining').' '.number_format($batch->remaining_qty,4).' '.$batch->item_unit); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option disabled><?= lang_line('pp_opt_no_batch'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label><?= lang_line('pp_label_qty_used'); ?></label>
                                            <input type="number" step="0.0001" name="qty_used" id="add_qty_used" class="form-control" required min="0.0001" max="0">
                                            <small class="form-text text-muted"><?= lang_line('pp_label_remaining_stock'); ?> <span id="add_max_qty">0.0000</span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label><?= lang_line('pp_label_notes'); ?></label>
                                            <input type="text" name="notes" id="add_notes" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-plus mr-1"></i> <?= lang_line('pp_btn_add_material'); ?></button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Daftar Input -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold"><?= sprintf(lang_line('pp_card_input_list'), lang_line('pp_stage_sewing')); ?></h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light text-center">
                                            <tr><th><?= lang_line('pp_th_item'); ?></th><th><?= lang_line('pp_th_type'); ?></th><th><?= lang_line('pp_th_qty'); ?></th><th><?= lang_line('pp_th_source'); ?></th><th><?= lang_line('pp_th_notes'); ?></th><th><?= lang_line('pp_th_action'); ?></th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($current_inputs)): ?>
                                                <tr><td colspan="6" class="text-center text-muted py-2"><?= lang_line('pp_no_input'); ?></td></tr>
                                            <?php else: foreach($current_inputs as $in): ?>
                                                <tr class="text-center">
                                                    <td class="text-left"><?= htmlspecialchars($in->item_name); ?></td>
                                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($in->item_type); ?></span></td>
                                                    <td class="text-right"><?= number_format($in->qty_used, 4); ?></td>
                                                    <td><?= $in->source_output_id ? lang_line('pp_stage_cutting') : lang_line('pp_src_warehouse'); ?></td>
                                                    <td class="text-left"><?= htmlspecialchars($in->notes); ?></td>
                                                    <td>
                                                        <?php if ($header->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                                            <a href="<?= base_url('production_process/delete_sewing_input/'.$header->id.'/'.$in->id); ?>"
                                                               class="btn btn-danger btn-xs btn-delete"
                                                               data-message="<?= htmlspecialchars(addslashes(sprintf(lang_line('pp_delete_input_confirm'), $in->item_name))); ?>">
                                                               <i class="fas fa-trash"></i></a>
                                                        <?php else: echo '-'; endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================== RIGHT COLUMN ===================== -->
                    <div class="col-lg-6">

                        <!-- Output Semi-Finished -->
                        <?php if ($header->status == 'Draft' && check_access('production_process', 'edit')): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-success text-white py-3"><h6 class="m-0 font-weight-bold"><?= lang_line('pp_card_output_semi_only'); ?></h6></div>
                            <div class="card-body">
                                <form id="formOutput" method="post" action="<?= base_url('production_process/save_sewing_output/'.$header->id); ?>">
                                    <div class="form-group">
                                        <label><?= lang_line('pp_label_select_semi'); ?></label>
                                        <select name="item_id" id="output_item_sf" class="form-control select2-ajax-semifinished" required style="width:100%;"></select>
                                    </div>
                                    <div class="form-group">
                                        <label><?= lang_line('pp_label_qty_produced'); ?></label>
                                        <input type="number" step="0.0001" name="qty" id="output_qty_sf" class="form-control" required min="0.0001">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> <?= lang_line('pp_btn_add_output'); ?></button>
                                </form>
                            </div>
                        </div>

                        <!-- Output Scrap -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-warning text-dark py-3"><h6 class="m-0 font-weight-bold"><?= lang_line('pp_card_output_scrap'); ?></h6></div>
                            <div class="card-body">
                                <form id="formOutputScrap" method="post" action="<?= base_url('production_process/save_sewing_output/'.$header->id); ?>">
                                    <div class="form-group">
                                        <label><?= lang_line('pp_label_select_scrap'); ?></label>
                                        <select name="item_id" id="output_item_scrap" class="form-control select2-ajax-scrap" required style="width:100%;"></select>
                                    </div>
                                    <div class="form-group">
                                        <label><?= lang_line('pp_label_qty_scrap'); ?></label>
                                        <input type="number" step="0.0001" name="qty" id="output_qty_scrap" class="form-control" required min="0.0001">
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i> <?= lang_line('pp_btn_add_scrap'); ?></button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Daftar Output -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold"><?= sprintf(lang_line('pp_card_output_list'), lang_line('pp_stage_sewing')); ?></h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light text-center">
                                            <tr><th><?= lang_line('pp_th_item'); ?></th><th><?= lang_line('pp_th_type'); ?></th><th><?= lang_line('pp_th_qty'); ?></th><th><?= lang_line('pp_th_qc_status'); ?></th><th><?= lang_line('pp_th_action'); ?></th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($current_outputs)): ?>
                                                <tr><td colspan="5" class="text-center text-muted py-2"><?= lang_line('pp_no_output'); ?></td></tr>
                                            <?php else: foreach($current_outputs as $out): ?>
                                                <tr class="text-center" data-output-id="<?= $out->id; ?>" data-qc-description="<?= htmlspecialchars($out->qc_description ?? ''); ?>" data-qc-photo="<?= htmlspecialchars($out->qc_photo_url ?? ''); ?>" data-qc-checked-by="<?= htmlspecialchars($out->qc_checked_by_name ?? ''); ?>" data-qc-checked-at="<?= ($out->qc_checked_at ? date('d M Y H:i', strtotime($out->qc_checked_at)) : ''); ?>">
                                                    <td class="text-left"><?= htmlspecialchars($out->item_name); ?></td>
                                                    <td><span class="badge badge-<?= $out->item_type == 'scrap' ? 'warning text-dark' : 'success'; ?>"><?= ucfirst($out->item_type); ?></span></td>
                                                    <td class="text-right"><?= number_format($out->qty_produced,4); ?></td>
                                                    <td>
                                                        <?php if($out->qc_checked_by): ?>
                                                            <span class="badge badge-success"><?= lang_line('pp_badge_qc_ok'); ?></span><br>
                                                            <?php if($out->qc_description): ?>
                                                                <small class="text-muted"><?= htmlspecialchars(substr($out->qc_description, 0, 30) . (strlen($out->qc_description) > 30 ? '...' : '')); ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary"><?= lang_line('pp_badge_qc_pending'); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($out->item_type == 'scrap' && check_access('production_process', 'edit')): ?>
                                                            <button type="button" class="btn btn-secondary btn-xs mr-1" data-toggle="modal" data-target="#scrapDetailModal"
                                                                onclick="initScrapDetailModal('<?= $header->id; ?>', '<?= $out->id; ?>', 'sewing')">
                                                                <i class="fas fa-info-circle"></i> <?= lang_line('pp_btn_detail'); ?>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($header->status != 'Completed' && check_access('production_process', 'add')): ?>
                                                            <?php if(!$out->qc_checked_by): ?>
                                                                <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#qcModal"
                                                                    onclick="initQcModal('<?= $header->id; ?>', '<?= $out->id; ?>', 'sewing')">
                                                                    <i class="fas fa-check"></i> <?= lang_line('pp_btn_qc'); ?>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-info btn-xs" data-toggle="modal" data-target="#qcModal"
                                                                    onclick="initQcModal('<?= $header->id; ?>', '<?= $out->id; ?>', 'sewing', true)">
                                                                    <i class="fas fa-eye"></i> <?= lang_line('pp_btn_view'); ?>
                                                                </button>
                                                                <a href="<?= base_url('production_process/unconfirm_qc_sewing/'.$header->id.'/'.$out->id); ?>" class="btn btn-warning btn-xs"><i class="fas fa-undo"></i></a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>

                                                        <?php if ($header->status == 'Draft' && check_access('production_process', 'delete')): ?>
                                                            <a href="<?= base_url('production_process/delete_sewing_output/'.$header->id.'/'.$out->id); ?>" class="btn btn-danger btn-xs btn-delete ml-1" data-message="<?= htmlspecialchars(addslashes(sprintf(lang_line('pp_delete_output_confirm'), $out->item_name))); ?>"><i class="fas fa-trash"></i></a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- END RIGHT -->
                </div><!-- END ROW -->
            </div><!-- END CONTAINER -->

<!-- Scrap Detail Modal -->
<div class="modal fade" id="scrapDetailModal" tabindex="-1" role="dialog" aria-labelledby="scrapDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scrapDetailModalLabel"><?= lang_line('pp_scrap_modal_title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label><strong><?= lang_line('pp_scrap_item_label'); ?></strong></label>
                        <p id="scrapItemName"></p>
                    </div>
                    <div class="col-md-6">
                        <label><strong><?= lang_line('pp_scrap_qty_label'); ?></strong></label>
                        <p class="mb-1" id="scrapQtyTotal"></p>
                        <small class="text-muted">Total qty material asal scrap: <span id="scrapSourceTotal">0.0000</span></small>
                    </div>
                </div>

                <h6 class="mb-3"><?= lang_line('pp_scrap_sources_title'); ?></h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered" id="scrapSourcesTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:34px;" class="text-center">
                                    <input type="checkbox" id="scrapSourcesCheckAll" title="<?= lang_line('pp_scrap_check_all'); ?>">
                                </th>
                                <th><?= lang_line('pp_scrap_th_material_input'); ?></th>
                                <th><?= lang_line('pp_th_type'); ?></th>
                                <th><?= lang_line('pp_scrap_th_qty_input'); ?></th>
                                <th style="width: 18%;"><?= lang_line('pp_scrap_th_qty_scrap'); ?></th>
                                <th><?= lang_line('pp_scrap_th_ng', 'Rincian NG'); ?></th>
                                <th style="width: 10%;"><?= lang_line('pp_th_action'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="scrapSourcesBody">
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-2 pb-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        <?= lang_line('pp_scrap_create_mr_info'); ?>
                    </small>
                    <button type="button" id="btnCreateMrFromScrap" class="btn btn-warning btn-sm" disabled>
                        <i class="fas fa-paper-plane mr-1"></i> <?= lang_line('pp_scrap_create_mr_btn'); ?>
                    </button>
                </div>

                <form id="addScrapSourceForm" method="post" class="border-top pt-3 no-loader">
                    <h6><?= lang_line('pp_scrap_add_title'); ?></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('pp_scrap_select_input'); ?></label>
                                <select id="sourceInputId" name="source_input_id" class="form-control" required>
                                    <option value=""><?= lang_line('pp_scrap_opt_select'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('pp_scrap_qty_from_input'); ?></label>
                                <input type="number" id="sourceQty" name="source_qty" step="0.0001" min="0" class="form-control" placeholder="0.0000" required>
                                <small class="form-text text-muted"><?= lang_line('pp_scrap_max_available'); ?> <span id="maxSourceQty">0</span></small>
                            </div>
                        </div>
                    </div>
                    <?php $ng_codes = function_exists('ng_code_options') ? ng_code_options() : []; ?>
                    <div class="border rounded p-2 mb-3 bg-light">
                        <label class="mb-1 font-weight-bold">
                            <?= lang_line('pp_scrap_ng_title', 'Rincian NG'); ?>
                            <small class="text-muted font-weight-normal">
                                <?= lang_line('pp_scrap_ng_hint', '— catatan penyebab, tidak mempengaruhi stok'); ?>
                            </small>
                        </label>
                        <div class="form-row">
                            <div class="col-6 col-md-3 form-group mb-1">
                                <label class="small mb-0"><?= lang_line('pp_ng_human', 'Human Error'); ?></label>
                                <input type="number" name="ng_human" class="form-control form-control-sm ng-cat" step="0.0001" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-3 form-group mb-1">
                                <label class="small mb-0"><?= lang_line('pp_ng_material', 'Material Issue'); ?></label>
                                <input type="number" name="ng_material" class="form-control form-control-sm ng-cat" step="0.0001" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-3 form-group mb-1">
                                <label class="small mb-0"><?= lang_line('pp_ng_mechanic', 'Mechanic Issue'); ?></label>
                                <input type="number" name="ng_mechanic" class="form-control form-control-sm ng-cat" step="0.0001" min="0" value="0">
                            </div>
                            <div class="col-6 col-md-3 form-group mb-1">
                                <label class="small mb-0"><?= lang_line('pp_ng_others', 'Others Issue'); ?></label>
                                <input type="number" name="ng_others" class="form-control form-control-sm ng-cat" step="0.0001" min="0" value="0">
                            </div>
                        </div>
                        <small class="form-text mb-2" id="ngBalanceNote"></small>
                        <div class="form-row">
                            <div class="col-md-5 form-group mb-0">
                                <label class="small mb-0"><?= lang_line('pp_ng_code', 'Kode NG'); ?></label>
                                <select name="ng_code" class="form-control form-control-sm ng-code">
                                    <option value="">-</option>
                                    <?php foreach ($ng_codes as $ngc): ?>
                                        <option value="<?= htmlspecialchars($ngc->code); ?>"><?= htmlspecialchars($ngc->code . ' — ' . $ngc->description); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7 form-group mb-0">
                                <label class="small mb-0"><?= lang_line('pp_ng_remark', 'Remark'); ?></label>
                                <input type="text" name="ng_remark" class="form-control form-control-sm ng-remark" maxlength="255">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> <?= lang_line('pp_btn_add_scrap_source'); ?>
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('pp_btn_close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- QC Modal -->
<div class="modal fade" id="qcModal" tabindex="-1" role="dialog" aria-labelledby="qcModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qcModalLabel"><?= lang_line('pp_qc_modal_title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="qcForm" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('pp_qc_desc_label'); ?></label>
                        <textarea id="qcDescription" name="qc_description" class="form-control" rows="4" placeholder="<?= htmlspecialchars(lang_line('pp_qc_desc_placeholder')); ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('pp_qc_photo_label'); ?></label>
                        <input type="url" id="qcPhotoUrl" name="qc_photo_url" class="form-control" placeholder="<?= lang_line('pp_qc_photo_placeholder'); ?>">
                        <small class="form-text text-muted"><?= lang_line('pp_qc_photo_help'); ?></small>
                    </div>
                    <div id="viewOnlyDiv" style="display:none;">
                        <div class="form-group">
                            <label><strong><?= lang_line('pp_qc_checked_by_label'); ?></strong></label>
                            <p id="qcCheckedByName"></p>
                        </div>
                        <div class="form-group">
                            <label><strong><?= lang_line('pp_qc_checked_at_label'); ?></strong></label>
                            <p id="qcCheckedAt"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('pp_btn_close'); ?></button>
                    <button type="submit" id="qcSubmitBtn" class="btn btn-primary"><?= lang_line('pp_qc_save_btn'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const I18N_PP = {
    confirm_title:     <?= json_encode(lang_line('pp_sw_confirm_title')); ?>,
    delete_title:      <?= json_encode(lang_line('pp_sw_delete_title')); ?>,
    yes:               <?= json_encode(lang_line('pp_sw_yes')); ?>,
    cancel:            <?= json_encode(lang_line('pp_sw_cancel')); ?>,
    delete_default:    <?= json_encode(lang_line('pp_sw_delete_default_text')); ?>,
    delete_scrap:      <?= json_encode(lang_line('pp_sw_delete_scrap_text')); ?>,
    add_scrap:         <?= json_encode(lang_line('pp_sw_add_scrap_text')); ?>,
    save_qc:           <?= json_encode(lang_line('pp_sw_save_qc_text')); ?>,
    mr_title:          <?= json_encode(lang_line('pp_mr_create_title')); ?>,
    mr_yes:            <?= json_encode(lang_line('pp_mr_create_yes')); ?>,
    mr_warn_semi:      <?= json_encode(lang_line('pp_mr_create_warn_semi')); ?>,
    mr_warn_default:   <?= json_encode(lang_line('pp_mr_create_warn_default')); ?>,
    mr_success:        <?= json_encode(lang_line('pp_mr_success_title')); ?>,
    mr_success_link:   <?= json_encode(lang_line('pp_mr_success_link')); ?>,
    mr_failed:         <?= json_encode(lang_line('pp_mr_failed')); ?>,
    mr_processing:     <?= json_encode(lang_line('pp_mr_processing')); ?>,
    err_title:         <?= json_encode(lang_line('pp_err_title')); ?>,
    err_network:       <?= json_encode(lang_line('pp_err_network')); ?>,
    err_network_mr:    <?= json_encode(lang_line('pp_err_network_mr')); ?>,
    err_delete:        <?= json_encode(lang_line('pp_err_delete')); ?>,
    err_save:          <?= json_encode(lang_line('pp_err_save')); ?>,
    err_load_scrap:    <?= json_encode(lang_line('pp_err_load_scrap')); ?>,
    err_load_prefix:   <?= json_encode(lang_line('pp_err_load_scrap_prefix')); ?>,
    no_scrap_source:   <?= json_encode(lang_line('pp_scrap_no_source')); ?>,
    locked_tooltip:    <?= json_encode(lang_line('pp_scrap_locked_tooltip')); ?>,
    cannot_delete_mr:  <?= json_encode(lang_line('pp_scrap_cannot_delete')); ?>,
    no_input_avail:    <?= json_encode(lang_line('pp_scrap_no_input_available')); ?>,
    scrap_opt_select:  <?= json_encode(lang_line('pp_scrap_opt_select')); ?>,
    create_mr_btn:     <?= json_encode(lang_line('pp_scrap_create_mr_btn')); ?>,
    search_semi:       <?= json_encode(lang_line('pp_opt_search_semi')); ?>,
    search_scrap:      <?= json_encode(lang_line('pp_opt_search_scrap')); ?>
};
$(document).ready(function() {
    $('.select2-wip').select2({ theme: 'bootstrap4', width: '100%' });

    // Semi-finished AJAX
    $('.select2-ajax-semifinished').select2({
        theme: 'bootstrap4', width: '100%', placeholder: I18N_PP.search_semi, minimumInputLength: 1,
        ajax: {
            url: '<?= base_url("production_process/search_semifinished"); ?>',
            dataType: 'json', delay: 250, data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        }
    });

    // Scrap AJAX
    $('.select2-ajax-scrap').select2({
        theme: 'bootstrap4', width: '100%', placeholder: I18N_PP.search_scrap, minimumInputLength: 1,
        ajax: {
            url: '<?= base_url("production_process/search_scrap"); ?>',
            dataType: 'json', delay: 250, data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results }),
        }
    });

    // Cutting input
    $('#prev_output_selector').on('change', function() {
        const sel = $(this).find('option:selected');
        const max = parseFloat(sel.data('maxqty') || 0);
        $('#prev_item_id').val(sel.data('itemid') || '');
        $('#prev_item_type').val(sel.data('itemtype') || '');
        $('#prev_qty_used').attr('max', max).val('');
        $('#prev_max_qty').text(max.toFixed(4));
    });

    // Additional material
    $('#add_batch_selector').on('change', function() {
        const sel = $(this).find('option:selected');
        const max = parseFloat(sel.data('maxqty') || 0);
        $('#add_item_id').val(sel.data('itemid') || '');
        $('#add_item_type').val(sel.data('itemtype') || '');
        $('#add_qty_used').attr('max', max).val('');
        $('#add_max_qty').text(max.toFixed(4));
    });

    // SweetAlert delete
    $('body').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const msg = $(this).data('message') || I18N_PP.delete_default;
        Swal.fire({
            title: I18N_PP.confirm_title, text: msg, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel
        }).then(res => { if(res.isConfirmed) window.location.href = url; });
    });

    // QC Modal handling
    window.initQcModal = function(headerId, outputId, stage, viewOnly = false) {
        const form = $('#qcForm');
        form.attr('action', '<?= base_url('production_process/save_qc_'); ?>' + stage + '/' + headerId + '/' + outputId);

        // Get data from the row
        const row = $('tr[data-output-id="' + outputId + '"]');
        const desc = row.data('qc-description') || '';
        const photo = row.data('qc-photo') || '';
        const checkedBy = row.data('qc-checked-by') || '';
        const checkedAt = row.data('qc-checked-at') || '';

        if (viewOnly) {
            $('#qcSubmitBtn').hide();
            $('#qcDescription').prop('disabled', true).val(desc);
            $('#qcPhotoUrl').prop('disabled', true).val(photo);
            $('#qcCheckedByName').text(checkedBy);
            $('#qcCheckedAt').text(checkedAt);
            $('#viewOnlyDiv').show();
        } else {
            $('#qcSubmitBtn').show();
            $('#qcDescription').prop('disabled', false).val('');
            $('#qcPhotoUrl').prop('disabled', false).val('');
            $('#viewOnlyDiv').hide();
        }
        $('#qcModal').modal('show');
    };

    // QC Form submit
    $('#qcForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: I18N_PP.confirm_title, text: I18N_PP.save_qc, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
            confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel
        }).then(res => {
            if (res.isConfirmed) {
                if (window.showLoader) window.showLoader(); // loader muncul saat navigasi, bukan saat dialog konfirmasi
                form.submit();
            }
        });
    });

    // Scrap Detail Modal handling
    window.initScrapDetailModal = function(headerId, outputId, stage) {
        // Fetch production inputs untuk populate dropdown
        fetch('<?= base_url('production_process/ajax_get_scrap_sources'); ?>?output_id=' + outputId + '&stage=' + stage)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.error) {
                    Swal.fire(I18N_PP.err_title, data.error || I18N_PP.err_load_scrap, 'error');
                    return;
                }

                // Update modal dengan data scrap
                $('#scrapItemName').text(data.item_name || '-');
                $('#scrapQtyTotal').text(parseFloat(data.qty_scrap || 0).toFixed(4));
                // Total qty material yang ditunjuk sebagai asal scrap — beda hal
                // dengan qty barang scrap di atas, jadi ditulis terpisah.
                $('#scrapSourceTotal').text(parseFloat(data.total_source_qty || 0).toFixed(4));

                // Populate existing sources
                const tbody = $('#scrapSourcesBody');
                tbody.empty();
                if (data.sources && data.sources.length > 0) {
                    data.sources.forEach(src => {
                        const itemName = src.source_item_name || 'Unknown';
                        const itemType = src.item_type || '-';
                        const qtyInput = parseFloat(src.source_qty_input || 0).toFixed(4);
                        const qtyScrap = parseFloat(src.source_qty || 0).toFixed(4);
                        const typeBadge = itemType === 'material' ? 'badge-info' : (itemType === 'semifinished' ? 'badge-warning text-dark' : 'badge-secondary');

                        const mrActive = src.mr_status && src.mr_status !== 'Draft' && src.mr_status !== 'Rejected';
                        const checkCell = mrActive
                            ? `<span title="${I18N_PP.locked_tooltip}" class="text-muted"><i class="fas fa-lock"></i></span>`
                            : `<input type="checkbox" class="scrap-src-check" value="${src.id}" data-item-type="${itemType}">`;
                        const mrBadge = mrActive
                            ? `<br><a href="<?= base_url('material_request/detail/'); ?>${src.mr_header_id}" target="_blank" class="badge badge-success" style="font-weight:normal;"><i class="fas fa-paper-plane"></i> ${src.mr_number} (${src.mr_status})</a>`
                            : '';

                        tbody.append(`
                            <tr>
                                <td class="text-center">${checkCell}</td>
                                <td>${itemName}${mrBadge}</td>
                                <td><span class="badge ${typeBadge}">${itemType}</span></td>
                                <td class="text-right">${qtyInput}</td>
                                <td class="text-right">${qtyScrap}</td>
                                <td><small class="text-muted">${ngSummary(src)}</small></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteScrapSource(${src.id}, ${headerId}, '${stage}')" ${mrActive ? 'disabled title="' + I18N_PP.cannot_delete_mr + '"' : ''}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append('<tr><td colspan="7" class="text-center text-muted">' + I18N_PP.no_scrap_source + '</td></tr>');
                }

                $('#btnCreateMrFromScrap').data('prod-id', headerId).prop('disabled', true);
                $('#scrapSourcesCheckAll').prop('checked', false);

                // Populate input materials dropdown
                const select = $('#sourceInputId');
                select.empty().append('<option value="">' + I18N_PP.scrap_opt_select + '</option>');
                if (data.available_inputs && data.available_inputs.length > 0) {
                    data.available_inputs.forEach(inp => {
                        const itemName = inp.item_name || 'Unknown';
                        const itemCode = inp.item_code || '';
                        const remaining = parseFloat(inp.remaining_qty || 0).toFixed(4);
                        select.append(`
                            <option value="${inp.input_id}" data-max-qty="${remaining}">
                                ${itemName} (${itemCode}) - Qty: ${remaining}
                            </option>
                        `);
                    });
                } else {
                    select.append('<option value="">' + I18N_PP.no_input_avail + '</option>');
                }

                // Setup form with stage in URL + simpan context di modal
                $('#addScrapSourceForm').attr('action', '<?= base_url('production_process/save_scrap_source'); ?>/' + headerId + '/' + outputId + '/' + stage);
                $('#scrapDetailModal').data({ headerId: headerId, outputId: outputId, stage: stage });

                $('#scrapDetailModal').modal('show');
            })
            .catch(err => {
                console.error('Scrap sources fetch error:', err);
                Swal.fire(I18N_PP.err_title, I18N_PP.err_load_prefix + err.message, 'error');
            });

        $('#sourceInputId').off('change').on('change', function() {
            const max = $(this).find('option:selected').data('max-qty') || 0;
            $('#sourceQty').attr('max', max).val('');
            $('#maxSourceQty').text(parseFloat(max).toFixed(4));
        });
    };

    // ===== Buat Permintaan Barang dari asal scrap =====
    $(document).on('change', '.scrap-src-check', function() {
        const checked = $('.scrap-src-check:checked').length;
        $('#btnCreateMrFromScrap').prop('disabled', checked === 0);
        const total = $('.scrap-src-check').length;
        $('#scrapSourcesCheckAll').prop('checked', checked > 0 && checked === total);
        $('#scrapSourcesCheckAll').prop('indeterminate', checked > 0 && checked < total);
    });

    $(document).on('change', '#scrapSourcesCheckAll', function() {
        const master = $(this).prop('checked');
        $('.scrap-src-check').prop('checked', master);
        $('.scrap-src-check').first().trigger('change');
    });

    $(document).on('click', '#btnCreateMrFromScrap', function() {
        const prodId = $(this).data('prod-id');
        const ids = $('.scrap-src-check:checked').map(function(){ return $(this).val(); }).get();
        if (ids.length === 0) return;

        const hasSemi = $('.scrap-src-check:checked').filter(function(){
            return $(this).data('item-type') === 'semifinished';
        }).length > 0;

        const warn = hasSemi
            ? I18N_PP.mr_warn_semi
            : I18N_PP.mr_warn_default.replace('%d', ids.length);

        Swal.fire({
            title: I18N_PP.mr_title, html: warn,
            icon: hasSemi ? 'warning' : 'question',
            showCancelButton: true, confirmButtonText: I18N_PP.mr_yes, cancelButtonText: I18N_PP.cancel
        }).then(function(res) {
            if (!res.isConfirmed) return;
            $('#btnCreateMrFromScrap').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + I18N_PP.mr_processing);

            $.ajax({
                url: '<?= base_url("production_process/create_mr_from_scrap/"); ?>' + prodId,
                type: 'POST',
                data: { 'scrap_source_ids[]': ids },
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        Swal.fire({
                            title: I18N_PP.mr_success,
                            html: resp.message + '<br><br><a href="<?= base_url("material_request/detail/"); ?>' + resp.mr_id + '" target="_blank">' + I18N_PP.mr_success_link + ' ' + resp.mr_number + ' <i class="fas fa-external-link-alt"></i></a>',
                            icon: 'success'
                        }).then(function(){ $('#scrapDetailModal').modal('hide'); });
                    } else {
                        Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.mr_failed, 'error');
                    }
                },
                error: function() { Swal.fire(I18N_PP.err_title, I18N_PP.err_network_mr, 'error'); },
                complete: function() {
                    $('#btnCreateMrFromScrap').html('<i class="fas fa-paper-plane mr-1"></i> ' + I18N_PP.create_mr_btn);
                }
            });
        });
    });

    // Delete scrap source (AJAX, refresh modal tanpa reload page)
    window.deleteScrapSource = function(sourceId, headerId, stage) {
        Swal.fire({
            title: I18N_PP.delete_title, text: I18N_PP.delete_scrap, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel
        }).then(res => {
            if (!res.isConfirmed) return;
            const ctx = $('#scrapDetailModal').data();
            $.ajax({
                url: '<?= base_url('production_process/delete_scrap_source'); ?>/' + sourceId + '/' + headerId,
                type: 'POST',
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        toastr.success(resp.message);
                        initScrapDetailModal(ctx.headerId, ctx.outputId, ctx.stage);
                    } else {
                        Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.err_delete, 'error');
                    }
                },
                error: function() { Swal.fire(I18N_PP.err_title, I18N_PP.err_network, 'error'); }
            });
        });
    };

    // Add scrap source form submit (AJAX, refresh modal tanpa reload page)
    $('#addScrapSourceForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $form = $(this);
        Swal.fire({
            title: I18N_PP.confirm_title, text: I18N_PP.add_scrap, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
            confirmButtonText: I18N_PP.yes, cancelButtonText: I18N_PP.cancel
        }).then(res => {
            if (!res.isConfirmed) return;
            const ctx = $('#scrapDetailModal').data();
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        toastr.success(resp.message);
                        $form[0].reset();
                        $('#maxSourceQty').text('0');
                        initScrapDetailModal(ctx.headerId, ctx.outputId, ctx.stage);
                    } else {
                        Swal.fire(I18N_PP.err_title, resp.message || I18N_PP.err_save, 'error');
                    }
                },
                error: function() { Swal.fire(I18N_PP.err_title, I18N_PP.err_network, 'error'); }
            });
        });
    });
});
</script>

<script>
/* ------------------------------------------------------------------
 * Rincian NG pada form "Tambah Asal Scrap".
 *
 * Angka di sini murni catatan (lihat application/helpers/ng_helper.php):
 * tidak menyentuh stok, ledger, maupun jurnal. Tujuannya mengisi kolom
 * HUMAN ERROR / MATERIAL ISSUE / MECHANIC ISSUE / OTHERS ISSUE di
 * Daily Material NG Report.
 *
 * Qty yang diketik langsung dituangkan ke satu kategori default, jadi total
 * rincian selalu sama dengan qty tanpa user mengetik dua kali. Begitu user
 * menyentuh salah satu kategori, pengisian otomatis berhenti dan angkanya
 * dibiarkan apa adanya -- sengaja TIDAK memblokir simpan kalau tidak pas,
 * karena sifatnya catatan.
 * ------------------------------------------------------------------ */
function ngSummary(src) {
    if (!src) return '';
    var map = { ng_human: 'HUM', ng_material: 'MAT', ng_mechanic: 'MEC', ng_others: 'OTH' };
    var parts = [];
    for (var k in map) {
        var v = parseFloat(src[k] || 0);
        if (v > 0) parts.push(map[k] + ' ' + (Math.round(v * 10000) / 10000));
    }
    var out = parts.join(' \u00b7 ');
    if (src.ng_code) out = out ? out + ' (' + src.ng_code + ')' : '(' + src.ng_code + ')';
    return out || '<span class="text-muted">-</span>';
}

$(function () {
    var $form    = $('#addScrapSourceForm');
    if (!$form.length) return;
    var $qty     = $('#sourceQty');
    var $cats    = $form.find('.ng-cat');
    var autoFill = true;   // false begitu user mengetik sendiri di salah satu kategori

    function catTotal() {
        var t = 0;
        $cats.each(function () { t += parseFloat($(this).val() || 0) || 0; });
        return Math.round(t * 10000) / 10000;
    }

    function refreshNote() {
        var qty   = Math.round((parseFloat($qty.val() || 0) || 0) * 10000) / 10000;
        var total = catTotal();
        var $note = $('#ngBalanceNote');
        if (qty <= 0 && total <= 0) { $note.text('').removeClass('text-danger text-success'); return; }
        var label = 'Total rincian ' + total + ' dari qty ' + qty;
        if (Math.abs(total - qty) < 0.0001) {
            $note.text(label).removeClass('text-danger').addClass('text-success');
        } else {
            $note.text(label + ' — belum pas').removeClass('text-success').addClass('text-danger');
        }
    }

    $qty.on('input change', function () {
        if (autoFill) {
            $cats.val(0);
            $form.find('[name="ng_material"]').val($(this).val() || 0);
        }
        refreshNote();
    });

    $cats.on('input', function () { autoFill = false; refreshNote(); });

    // Modal dipakai ulang untuk output scrap yang berbeda-beda -> bersihkan
    // isian NG tiap kali dibuka, jangan sampai terbawa ke scrap berikutnya.
    $('#scrapDetailModal').on('show.bs.modal', function () {
        autoFill = true;
        $cats.val(0);
        $form.find('.ng-code').val('');
        $form.find('.ng-remark').val('');
        $('#ngBalanceNote').text('').removeClass('text-danger text-success');
    });
});
</script>
