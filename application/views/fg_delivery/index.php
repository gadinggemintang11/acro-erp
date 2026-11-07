<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-weight-bold text-primary mb-0">
            <i class="fas fa-dolly mr-2"></i><?= lang_line('fg_delivery_title'); ?>
        </h3>
        <a href="<?= site_url('fg_delivery/create') ?>" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus mr-1"></i> <?= lang_line('fg_delivery_create'); ?>
        </a>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?= lang_line('fg_delivery_search'); ?></label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= lang_line('fg_delivery_search_placeholder'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang_line('fg_delivery_from_date'); ?></label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang_line('fg_delivery_to_date'); ?></label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang_line('label_status'); ?></label>
                    <select name="status" class="form-control">
                        <option value=""><?= lang_line('common_all'); ?></option>
                        <option value="Draft" <?= ($filters['status'] ?? '')=='Draft'?'selected':'' ?>><?= lang_line('fg_delivery_status_draft'); ?></option>
                        <option value="Confirmed" <?= ($filters['status'] ?? '')=='Confirmed'?'selected':'' ?>><?= lang_line('fg_delivery_status_confirmed'); ?></option>
                        <option value="Cancelled" <?= ($filters['status'] ?? '')=='Cancelled'?'selected':'' ?>><?= lang_line('fg_delivery_status_cancelled'); ?></option>
                    </select>
                </div>
                <div class="col-md-3 text-right">
                    <button class="btn btn-primary px-4"><i class="fas fa-search mr-1"></i><?= lang_line('common_filter'); ?></button>
                    <a href="<?= site_url('fg_delivery') ?>" class="btn btn-light border ml-2"><i class="fas fa-sync"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width:5%;"><?= lang_line('label_no'); ?></th>
                            <th><?= lang_line('fg_delivery_number'); ?></th>
                            <th><?= lang_line('fg_delivery_date'); ?></th>
                            <th><?= lang_line('fg_delivery_customer'); ?></th>
                            <th><?= lang_line('fg_delivery_sc_number'); ?></th>
                            <th><?= lang_line('fg_delivery_bc_number'); ?></th>
                            <th><?= lang_line('label_status'); ?></th>
                            <th style="width:18%;"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($deliveries)): $no=1; foreach($deliveries as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($d->delivery_number) ?></strong></td>
                            <td class="text-center"><?= htmlspecialchars($d->delivery_date) ?></td>
                            <td><?= htmlspecialchars($d->nama_customer) ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->sc_number ?? '-') ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->bc_number ?? '-') ?></td>
                            <td class="text-center">
                                <?php
                                    $badge = ['Draft'=>'secondary','Confirmed'=>'success','Cancelled'=>'danger'][$d->status] ?? 'light';
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= $d->status ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= site_url('fg_delivery/show/'.$d->id) ?>" class="btn btn-sm btn-info mr-1" title="<?= lang_line('btn_detail'); ?>"><i class="fas fa-eye"></i></a>
                                <?php if ($d->status=='Draft'): ?>
                                    <a href="<?= site_url('fg_delivery/edit/'.$d->id) ?>" class="btn btn-sm btn-warning mr-1" title="<?= lang_line('btn_edit'); ?>"><i class="fas fa-edit"></i></a>
                                    <a href="<?= site_url('fg_delivery/destroy/'.$d->id) ?>" onclick="return confirm('<?= lang_line('fg_delivery_delete_draft_confirm'); ?>')" class="btn btn-sm btn-danger" title="<?= lang_line('btn_delete'); ?>"><i class="fas fa-trash-alt"></i></a>
                                <?php elseif ($d->status=='Confirmed'): ?>
                                    <a href="<?= site_url('fg_delivery/destroy/'.$d->id) ?>" onclick="return confirm('<?= lang_line('fg_delivery_cancel_confirm'); ?>')" class="btn btn-sm btn-outline-danger" title="<?= lang_line('btn_cancel'); ?>"><i class="fas fa-ban"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted"><em><?= lang_line('fg_delivery_no_data'); ?></em></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
.table-hover tbody tr:hover { background-color:#f6f9ff!important; }
.card { border-radius:12px; }
</style>
