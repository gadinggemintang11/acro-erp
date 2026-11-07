<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-clipboard-list text-warning mr-2"></i>
                <?= lang_line('ost_title_mat'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('ost_subtitle_mat'); ?></small>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $this->session->flashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $this->session->flashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= htmlspecialchars(lang_line('ost_search_placeholder')); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="department" class="form-control form-control-sm">
                        <option value=""><?= lang_line('ost_all_departments'); ?></option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d->department) ?>"
                                <?= ($filters['department'] === $d->department) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d->department) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                           title="<?= lang_line('ost_date_from_title'); ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                           title="<?= lang_line('ost_date_to_title'); ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1">
                    <i class="fas fa-search"></i> <?= lang_line('ost_btn_filter'); ?>
                </button>
                <a href="<?= base_url('material_outstanding') ?>" class="btn btn-sm btn-secondary mb-1"><?= lang_line('ost_btn_reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('ost_th_code'); ?></th>
                            <th><?= lang_line('ost_th_item_name'); ?></th>
                            <th class="text-center"><?= lang_line('ost_th_type'); ?></th>
                            <th class="text-center"><?= lang_line('ost_th_unit'); ?></th>
                            <th class="text-right"><?= lang_line('ost_th_total_requested'); ?></th>
                            <th class="text-right"><?= lang_line('ost_th_total_issued'); ?></th>
                            <th class="text-right"><?= lang_line('ost_th_outstanding'); ?></th>
                            <th width="100" class="text-center"><?= lang_line('ost_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle text-success mr-1"></i>
                                    <?= lang_line('ost_no_data'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($r->item_code) ?></strong></td>
                                <td><?= htmlspecialchars($r->item_name) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-<?= $r->item_type === 'material' ? 'info' : 'secondary' ?>">
                                        <?= htmlspecialchars($r->item_type) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($r->item_unit ?? '-') ?></td>
                                <td class="text-right"><?= number_format((float)$r->total_requested, 2) ?></td>
                                <td class="text-right"><?= number_format((float)$r->total_issued, 2) ?></td>
                                <td class="text-right">
                                    <span class="badge badge-warning" style="font-size: 0.9rem;">
                                        <?= number_format((float)$r->total_outstanding, 2) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('material_outstanding/detail/' . $r->item_id . '/' . $r->item_type) ?>"
                                       class="btn btn-sm btn-outline-primary" title="<?= lang_line('ost_view_breakdown_title'); ?>">
                                        <i class="fas fa-eye"></i> <?= lang_line('ost_btn_view'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
