<?php
function render_coa_tree($accounts, $level = 0) {
    $indent = $level * 30;

    foreach ($accounts as $acc) {
        $is_header = $acc['is_header'] == 1;
        $font_weight = $is_header ? 'font-weight-bold' : '';
        $color = $is_header ? 'text-primary' : 'text-dark';

        // Map account_type DB value → lang key
        $type_map = [
            'Aset' => 'coa_type_aset',
            'Liabilitas' => 'coa_type_liabilitas',
            'Ekuitas' => 'coa_type_ekuitas',
            'Pendapatan' => 'coa_type_pendapatan',
            'HPP' => 'coa_type_hpp',
            'Biaya' => 'coa_type_biaya',
        ];
        $type_lbl = isset($type_map[$acc['account_type']]) ? lang_line($type_map[$acc['account_type']]) : $acc['account_type'];

        echo '<tr>';
        echo '<td style="padding-left: ' . (10 + $indent) . 'px;" class="' . $font_weight . ' ' . $color . '">';
        echo $acc['account_code'];
        echo '</td>';
    echo '<td class="' . $font_weight . ' ' . $color . '">' . $acc['account_name'] . '</td>';
    echo '<td>' . htmlspecialchars($type_lbl) . '</td>';
    echo '<td>' . $acc['currency'] . '</td>';
    $normal = isset($acc['normal_balance']) && $acc['normal_balance'] ? $acc['normal_balance'] : ((in_array($acc['account_type'], ['Aset','Biaya','HPP'])) ? 'Debit' : 'Kredit');
    $normal_lbl = ($normal === 'Debit') ? lang_line('acct_th_debit') : lang_line('acct_th_credit');
    echo '<td>' . htmlspecialchars($normal_lbl) . '</td>';
    echo '<td>' . ($is_header ? '<span class="badge badge-info">' . lang_line('coa_badge_header') . '</span>' : '<span class="badge badge-secondary">' . lang_line('coa_badge_detail') . '</span>') . '</td>';
    echo '<td>' . ($acc['status'] == 'active' ? '<span class="badge badge-success">' . lang_line('coa_badge_active') . '</span>' : '<span class="badge badge-danger">' . lang_line('coa_badge_inactive') . '</span>') . '</td>';
        echo '<td class="text-center">';
        echo '<a href="' . base_url('coa/edit/' . $acc['id']) . '" class="btn btn-warning btn-circle btn-sm" title="' . lang_line('btn_edit') . '"><i class="fas fa-pen"></i></a> ';
        echo '<a href="' . base_url('coa/delete/' . $acc['id']) . '" class="btn btn-danger btn-circle btn-sm" title="' . lang_line('acct_delete_btn') . '" onclick="return confirm(\'' . addslashes(lang_line('coa_confirm_delete')) . '\');"><i class="fas fa-trash"></i></a>';
        echo '</td>';
        echo '</tr>';

        if (isset($acc['children'])) {
            render_coa_tree($acc['children'], $level + 1);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('coa/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('coa_create_btn'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('coa_structure_title'); ?></h6>
            <form method="get" action="" class="form-inline mt-2">
                <input type="text" name="search" class="form-control mr-2" placeholder="<?= htmlspecialchars(lang_line('coa_search_placeholder')); ?>" value="<?= isset($search) ? htmlspecialchars($search) : '' ?>">
                <button type="submit" class="btn btn-primary"><?= lang_line('coa_search_btn'); ?></button>
                <?php if (!empty($search)): ?>
                    <a href="<?= base_url('coa') ?>" class="btn btn-secondary ml-2"><?= lang_line('coa_reset_btn'); ?></a>
                <?php endif; ?>
            </form>
            <?php if (!empty($search)): ?>
                <div class="mt-2">
                    <small><?= lang_line('coa_search_result_prefix'); ?> "<strong><?= htmlspecialchars($search) ?></strong>"</small>
                    <?php if (isset($search_error)): ?>
                        <div class="text-danger small"><?= lang_line('coa_search_error_prefix'); ?> <?= htmlspecialchars($search_error) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="coaTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('coa_th_code'); ?></th>
                            <th><?= lang_line('coa_th_name'); ?></th>
                            <th><?= lang_line('coa_th_type'); ?></th>
                            <th><?= lang_line('coa_th_currency'); ?></th>
                            <th><?= lang_line('coa_th_normal_balance'); ?></th>
                            <th><?= lang_line('coa_th_postable'); ?></th>
                            <th><?= lang_line('coa_th_status'); ?></th>
                            <th><?= lang_line('acct_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php render_coa_tree($accounts_tree); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
