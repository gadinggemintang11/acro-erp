<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div>
            <a href="<?= base_url('stockopname/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> <?= lang_line('so_btn_print_pdf'); ?>
            </a>
            <a href="<?= base_url('stockopname/export_excel/' . $header->id); ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> <?= lang_line('so_btn_export_excel'); ?>
            </a>
            <a href="<?= base_url('stockopname'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('so_btn_back'); ?></a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_doc_no'); ?></strong><br>
                    <?= $header->opname_no; ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_date_short'); ?></strong><br>
                    <?= date('d M Y', strtotime($header->opname_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_status'); ?></strong><br>
                    <span class="badge badge-success" style="font-size: 1rem;"><?= $header->status; ?></span>
                </div>
                <div class="col-md-3">
                    <strong class="text-primary"><?= lang_line('so_label_adjustment_doc'); ?></strong><br>
                    <?php if($header->adjustment_doc): ?>
                        <a href="<?= base_url('adjustment/view/' . $header->adjustment_header_id); ?>" target="_blank" class="btn btn-success btn-sm">
                            <i class="fas fa-file-alt"></i> <?= $header->adjustment_doc; ?>
                        </a>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= lang_line('so_no_variance'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong class="text-primary"><?= lang_line('so_label_notes_short'); ?></strong><br>
                    <?= $header->notes; ?>
                </div>
                <?php if($header->status == 'Completed'): ?>
                <div class="col-md-6">
                    <strong class="text-primary"><?= lang_line('so_label_confirmed_by'); ?></strong><br>
                    <?= $header->confimer_name; ?> <?= lang_line('so_at'); ?> <?= date('d M Y H:i', strtotime($header->confirmed_at)); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('so_card_final_result'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th><?= lang_line('so_th_code'); ?></th>
                            <th><?= lang_line('so_th_item_name'); ?></th>
                            <th><?= lang_line('so_th_unit'); ?></th>
                            <th><?= lang_line('so_th_qty_system'); ?></th>
                            <th><?= lang_line('so_th_qty_physical'); ?></th>
                            <th><?= lang_line('so_th_variance'); ?></th>
                            <th><?= lang_line('so_th_notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $sum_sistem = 0; $sum_fisik = 0; $sum_selisih = 0; $counted = 0; $pending = 0;
                            foreach($details as $d):
                                $color = 'secondary';
                                if ($d->selisih > 0) $color = 'success';
                                if ($d->selisih < 0) $color = 'danger';
                                $sum_sistem += (float)$d->qty_sistem;
                                if ($d->qty_fisik !== NULL) { $sum_fisik += (float)$d->qty_fisik; $counted++; } else { $pending++; }
                                $sum_selisih += (float)$d->selisih;
                        ?>
                        <tr>
                            <td><small><?= htmlspecialchars($d->item_code ?? ''); ?></small></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit); ?></td>
                            <td class="text-right"><?= rtrim(rtrim(number_format((float)$d->qty_sistem, 4, '.', ''), '0'), '.'); ?></td>
                            <td class="text-right">
                                <?= ($d->qty_fisik !== NULL)
                                    ? rtrim(rtrim(number_format((float)$d->qty_fisik, 4, '.', ''), '0'), '.')
                                    : '<i class="text-muted">' . lang_line('so_not_filled') . '</i>'; ?>
                            </td>
                            <td class="text-right font-weight-bold">
                                <span class="text-<?= $color; ?>"><?= rtrim(rtrim(number_format((float)$d->selisih, 4, '.', ''), '0'), '.'); ?></span>
                            </td>
                            <td><?= htmlspecialchars($d->notes ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <th colspan="3" class="text-right"><?= sprintf(lang_line('so_total_label'), $counted, $pending); ?></th>
                            <th class="text-right"><?= rtrim(rtrim(number_format($sum_sistem, 4, '.', ''), '0'), '.'); ?></th>
                            <th class="text-right"><?= rtrim(rtrim(number_format($sum_fisik, 4, '.', ''), '0'), '.'); ?></th>
                            <th class="text-right"><?= rtrim(rtrim(number_format($sum_selisih, 4, '.', ''), '0'), '.'); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
