<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('stockopname/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('stockopname_create_new'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Summary Cards untuk Current Month -->
    <div class="row mb-4">
        <?php 
        $current_month = date('Y-m');
        $item_types = ['material', 'semifinished', 'finished_goods', 'scrap', 'inventory'];
        foreach ($item_types as $type):
            $completed_this_month = 0;
            $active_this_month = 0;
            foreach ($sessions as $s) {
                if (date('Y-m', strtotime($s->opname_date)) == $current_month && $s->item_type == $type) {
                    if ($s->status == 'Completed') $completed_this_month++;
                    if (in_array($s->status, ['Draft', 'Counting'])) $active_this_month++;
                }
            }
            
            $card_color = 'secondary';
            if ($completed_this_month > 0 && $active_this_month == 0) $card_color = 'success';
            if ($active_this_month > 0) $card_color = 'warning';
        ?>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-2">
            <div class="card border-left-<?= $card_color; ?> shadow h-100 py-1">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= $card_color; ?> text-uppercase mb-1">
                                <?= ucfirst($type); ?>
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <?php if ($completed_this_month > 0): ?>
                                    ✓ <?= $completed_this_month; ?> <?= lang_line('stockopname_summary_completed'); ?>
                                <?php endif; ?>
                                <?php if ($active_this_month > 0): ?>
                                    <span class="text-warning">⏳ <?= $active_this_month; ?> <?= lang_line('stockopname_summary_active'); ?></span>
                                <?php endif; ?>
                                <?php if ($completed_this_month == 0 && $active_this_month == 0): ?>
                                    <span class="text-muted"><?= lang_line('stockopname_summary_not_started'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('stockopname_list_title'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('stockopname_doc_number'); ?></th>
                            <th><?= lang_line('stockopname_date'); ?></th>
                            <th><?= lang_line('stockopname_period'); ?></th>
                            <th><?= lang_line('stockopname_item_type'); ?></th>
                            <th><?= lang_line('stockopname_notes'); ?></th>
                            <th><?= lang_line('stockopname_status'); ?></th>
                            <th><?= lang_line('stockopname_adjustment_doc'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $opname_by_month = []; // Group untuk monitoring
                        foreach ($sessions as $s): 
                            $month_key = date('Y-m', strtotime($s->opname_date)) . '-' . $s->item_type;
                            if (!isset($opname_by_month[$month_key])) $opname_by_month[$month_key] = [];
                            $opname_by_month[$month_key][] = $s;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('stockopname/view/' . $s->id); ?>">
                                    <strong><?= $s->opname_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($s->opname_date)); ?></td>
                            <td>
                                <span class="badge badge-light"><?= date('M Y', strtotime($s->opname_date)); ?></span>
                                <?php
                                // Hitung jumlah opname untuk item_type dan bulan yang sama
                                $same_month_count = 0;
                                $active_in_month = false;
                                foreach ($sessions as $check) {
                                    if (date('Y-m', strtotime($check->opname_date)) == date('Y-m', strtotime($s->opname_date)) 
                                        && $check->item_type == $s->item_type) {
                                        $same_month_count++;
                                        if (in_array($check->status, ['Draft', 'Counting']) && $check->id != $s->id) {
                                            $active_in_month = true;
                                        }
                                    }
                                }
                                if ($same_month_count > 1): ?>
                                    <small class="text-warning d-block">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?= $same_month_count; ?> <?= lang_line('stockopname_opname_count'); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($active_in_month && !in_array($s->status, ['Draft', 'Counting'])): ?>
                                    <small class="text-danger d-block">
                                        <i class="fas fa-ban"></i> <?= lang_line('stockopname_active_other'); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?= ucfirst($s->item_type); ?></span></td>
                            <td><?= $s->notes; ?></td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($s->status == 'Draft') $badge = 'secondary';
                                    if ($s->status == 'Counting') $badge = 'warning';
                                    if ($s->status == 'Completed') $badge = 'success';
                                    if ($s->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $s->status; ?></span>
                            </td>
                            <td>
                                <?php if($s->adjustment_doc): ?>
                                    <a href="<?= base_url('adjustment/view/' . $s->adjustment_header_id); ?>" target="_blank">
                                        <?= $s->adjustment_doc; ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($s->status == 'Draft' || $s->status == 'Counting'): ?>
                                    <a href="<?= base_url('stockopname/counting/' . $s->id); ?>" class="btn btn-warning btn-circle btn-sm" title="<?= lang_line('stockopname_count_results'); ?>">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('stockopname/view/' . $s->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('stockopname_view_results'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('stockopname/export_pdf/' . $s->id); ?>" target="_blank" class="btn btn-danger btn-circle btn-sm" title="Print PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <a href="<?= base_url('stockopname/export_excel/' . $s->id); ?>" class="btn btn-success btn-circle btn-sm" title="Export Excel">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                                <?php if($s->status == 'Draft'): ?>
                                <a href="<?= base_url('stockopname/delete/' . $s->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('btn_delete'); ?>" onclick="return confirm('<?= lang_line('stockopname_delete_confirm'); ?>');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>