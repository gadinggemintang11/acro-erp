<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= lang_line('adjustment_title', $title); ?></h1>
        <a href="<?= base_url('adjustment/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('adjustment_create'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('adjustment_list_title'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('adjustment_doc_no'); ?></th>
                            <th><?= lang_line('adjustment_date'); ?></th>
                            <th><?= lang_line('adjustment_notes'); ?></th>
                            <th><?= lang_line('adjustment_item_type'); ?></th>
                            <th><?= lang_line('adjustment_created_by'); ?></th>
                            <th><?= lang_line('label_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjustments as $adj): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('adjustment/view/' . $adj->id); ?>">
                                    <strong><?= $adj->doc_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($adj->doc_date)); ?></td>
                            <td><?= $adj->notes; ?></td>
                            <td>
                                <?php if (!empty($adj->item_types)): ?>
                                    <?php $types = explode(', ', $adj->item_types); ?>
                                    <?php foreach ($types as $type): ?>
                                        <?php
                                            $badge_class = 'secondary';
                                            $display_text = strtoupper($type);
                                            switch($type) {
                                                case 'material': $badge_class = 'primary'; $display_text = 'MAT'; break;
                                                case 'semifinished': $badge_class = 'info'; $display_text = 'SF'; break;
                                                case 'finished_goods': $badge_class = 'success'; $display_text = 'FG'; break;
                                                case 'scrap': $badge_class = 'warning'; $display_text = 'SCRAP'; break;
                                                case 'inventory': $badge_class = 'dark'; $display_text = 'INV'; break;
                                                case 'machine': $badge_class = 'danger'; $display_text = 'MCH'; break;
                                            }
                                        ?>
                                        <span class="badge badge-<?= $badge_class; ?> mr-1"><?= $display_text; ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $adj->creator_name; ?></td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($adj->status == 'Draft') $badge = 'warning';
                                    if ($adj->status == 'Confirmed') $badge = 'success';
                                    if ($adj->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $adj->status; ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('adjustment/view/' . $adj->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($adj->status == 'Draft'): ?>
                                <a href="<?= base_url('adjustment/edit/' . $adj->id); ?>" class="btn btn-warning btn-circle btn-sm" title="<?= lang_line('btn_edit'); ?>">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="<?= base_url('adjustment/delete/' . $adj->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('btn_delete'); ?>" onclick="return confirm('<?= lang_line('adjustment_delete_confirm'); ?>');">
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