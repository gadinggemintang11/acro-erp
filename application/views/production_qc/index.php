<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1">
                <i class="fas fa-check-circle text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <p class="text-muted small mb-0"><?= lang_line('pqc_subtitle', 'Output produksi yang menunggu approval QC. Klik <em>Detail</em> untuk review & approve.'); ?></p>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Stats per stage -->
    <div class="row mb-4">
        <?php
            $stage_meta = [
                'grinding'    => ['label' => lang_line('pqc_stage_grinding', 'Grinding'),     'icon' => 'fa-cut',     'color' => 'warning'],
                'assembling'  => ['label' => lang_line('pqc_stage_assembling', 'Assembling'), 'icon' => 'fa-tools',   'color' => 'info'],
                'finishing'   => ['label' => lang_line('pqc_stage_finishing', 'Finishing'),   'icon' => 'fa-check',   'color' => 'success'],
            ];
            $total_pending = array_sum($stage_counts);
        ?>
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang_line('pqc_total_pending', 'Total Pending QC'); ?></div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?= number_format($total_pending); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach ($stages as $stage): $meta = $stage_meta[$stage]; ?>
        <div class="col-md-3">
            <div class="card border-left-<?= $meta['color']; ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= $meta['color']; ?> text-uppercase mb-1"><?= $meta['label']; ?></div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?= number_format($stage_counts[$stage]); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas <?= $meta['icon']; ?> fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter card + List -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-1"></i> <?= lang_line('pqc_list_title', 'Pending QC Outputs'); ?>
            </h6>
            <form method="get" action="<?= base_url('production_qc'); ?>" class="form-inline flex-wrap mt-2 mt-md-0">
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= lang_line('pqc_search_placeholder', 'Cari produksi/WO/item...'); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" style="min-width:200px;">
                </div>
                <div class="form-group mr-2">
                    <select name="stage" class="form-control form-control-sm">
                        <option value=""><?= lang_line('pqc_filter_all_stage', '-- Semua Stage --'); ?></option>
                        <?php foreach ($stages as $st): ?>
                            <option value="<?= $st; ?>" <?= ($filters['stage'] ?? '') == $st ? 'selected' : ''; ?>><?= lang_line('pqc_stage_'.$st, ucfirst($st)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" title="<?= lang_line('pqc_filter_date_from', 'Dari tanggal'); ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" title="<?= lang_line('pqc_filter_date_to', 'Sampai tanggal'); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search fa-sm"></i></button>
                    <a href="<?= base_url('production_qc'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo fa-sm"></i></a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('pqc_th_production_no', 'Production No.'); ?></th>
                            <th><?= lang_line('pqc_th_wo_number', 'WO Number'); ?></th>
                            <th><?= lang_line('pqc_th_stage', 'Stage'); ?></th>
                            <th><?= lang_line('pqc_th_item', 'Item'); ?></th>
                            <th class="text-right"><?= lang_line('pqc_th_qty_produced', 'Qty Produced'); ?></th>
                            <th><?= lang_line('pqc_th_received_by', 'Received By'); ?></th>
                            <th><?= lang_line('pqc_th_received_at', 'Received At'); ?></th>
                            <th width="120"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending)): foreach ($pending as $p): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($p->production_number ?: '-'); ?></td>
                            <td><?= htmlspecialchars($p->wo_number ?: '-'); ?></td>
                            <td>
                                <?php
                                    $cls = 'secondary';
                                    if ($p->stage_name == 'grinding') $cls = 'warning';
                                    elseif ($p->stage_name == 'assembling') $cls = 'info';
                                    elseif ($p->stage_name == 'finishing')  $cls = 'success';
                                ?>
                                <span class="badge badge-<?= $cls; ?>"><?= lang_line('pqc_stage_'.$p->stage_name, ucfirst(htmlspecialchars($p->stage_name))); ?></span>
                            </td>
                            <td class="text-left">
                                <strong><?= htmlspecialchars($p->item_code ?: '-'); ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($p->item_name ?: '-'); ?></small>
                                <?php if ($p->item_type): ?>
                                    <br><span class="badge badge-secondary"><?= ucfirst(htmlspecialchars($p->item_type)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <strong><?= number_format((float)$p->qty_qc, 2); ?></strong>
                                <?php if (!empty($p->satuan)): ?> <small class="text-muted"><?= htmlspecialchars($p->satuan); ?></small><?php endif; ?>
                                <?php if ((float)$p->qty_scrap > 0): ?>
                                    <br><small class="text-danger">+<?= number_format((float)$p->qty_scrap, 2); ?> <?= lang_line('pqc_scrap_label', 'scrap'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p->received_by_name ?: '-'); ?></td>
                            <td class="small"><?= $p->received_at ? date('d M Y H:i', strtotime($p->received_at)) : '-'; ?></td>
                            <td>
                                <a href="<?= base_url('production_qc/detail/'.$p->id); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> <?= lang_line('pqc_btn_detail', 'Detail'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                                <?= lang_line('pqc_no_pending', 'Tidak ada output yang menunggu QC. Semua sudah di-approve.'); ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
