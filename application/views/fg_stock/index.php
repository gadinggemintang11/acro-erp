<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
    .page-header {
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(var(--brand-rgb), 0.3);
    }
    .page-header h3 {
        margin: 0;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: 0.5px;
    }
    .page-header .btn {
        background: white;
        color: var(--brand-1);
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .page-header .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .filter-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        border: none;
    }
    .filter-card .card-body {
        padding: 25px;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 15px;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    .btn-search {
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 10px 25px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.4);
        color: white;
    }
    .btn-reset {
        background: white;
        border: 2px solid #e2e8f0;
        color: #495057;
        font-weight: 600;
        padding: 10px 25px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    .btn-reset:hover {
        border-color: #cbd5e0;
        background: #f8f9fa;
    }
    .data-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border: none;
        overflow: hidden;
    }
    .table-header {
        background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
        color: white;
    }
    .table-header th {
        border: none;
        padding: 15px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .data-table tbody tr {
        transition: all 0.3s ease;
    }
    .data-table tbody tr:hover {
        background-color: #f8f9ff !important;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(var(--brand-rgb), 0.1);
    }
    .data-table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }
    .fg-code {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #667eea;
        font-size: 0.95rem;
    }
    .stock-value {
        font-weight: 700;
        color: #2d3748;
        font-variant-numeric: tabular-nums;
    }
    .badge-uom {
        background: #e0e7ff;
        color: #667eea;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .btn-action {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        margin: 2px;
    }
    .btn-detail {
        background: linear-gradient(135deg, #4299e1 0%, #667eea 100%);
        border: none;
        color: white;
    }
    .btn-detail:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(66, 153, 225, 0.4);
        color: white;
    }
    .btn-pdf {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        border: none;
        color: white;
    }
    .btn-pdf:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 137, 54, 0.4);
        color: white;
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #a0aec0;
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
</style>

<div class="container-fluid">
    <div class="page-header d-flex align-items-center justify-content-between">
        <div>
            <h3><i class="fas fa-boxes mr-2"></i><?= lang_line('fg_stock_title', $title); ?></h3>
            <small style="opacity: 0.9;"><?= lang_line('fg_stock_subtitle'); ?></small>
        </div>
        <a href="<?= base_url('fg_stock') ?>" class="btn btn-sm">
            <i class="fas fa-sync-alt mr-1"></i><?= lang_line('btn_refresh'); ?>
        </a>
    </div>

    <div class="card filter-card">
        <div class="card-body">
            <form method="get" action="<?= base_url('fg_stock/index'); ?>" class="row align-items-end">
                <div class="col-md-5">
                    <label class="form-label">
                        <i class="fas fa-search mr-1"></i><?= lang_line('fg_stock_search'); ?>
                    </label>
                    <input type="text" name="search_summary" id="search_summary" class="form-control" placeholder="<?= lang_line('fg_stock_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search_summary'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt mr-1"></i><?= lang_line('fg_stock_from_date'); ?>
                    </label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-calendar-check mr-1"></i><?= lang_line('fg_stock_to_date'); ?>
                    </label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search mr-1"></i><?= lang_line('common_search'); ?>
                    </button>
                    <a href="<?= base_url('fg_stock') ?>" class="btn btn-reset">
                        <i class="fas fa-redo mr-1"></i><?= lang_line('btn_reset'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card data-card">
        <div class="table-responsive">
            <table class="table data-table mb-0">
                <thead class="table-header">
                    <tr>
                        <th width="60" class="text-center"><?= lang_line('label_no'); ?></th>
                        <th width="150"><?= lang_line('fg_stock_code'); ?></th>
                        <th><?= lang_line('fg_stock_name'); ?></th>
                        <th width="150" class="text-end"><?= lang_line('fg_stock_current'); ?></th>
                        <th width="100" class="text-center"><?= lang_line('fg_stock_unit'); ?></th>
                        <th width="200" class="text-center"><?= lang_line('label_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($stock_summary)): $no = 1; ?>
                        <?php foreach($stock_summary as $item): ?>
                            <tr>
                                <td class="text-center">
                                    <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; margin: 0 auto;">
                                        <?= $no++; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fg-code"><?= htmlspecialchars($item->fg_code); ?></span>
                                </td>
                                <td>
                                    <strong style="color: #2d3748;"><?= htmlspecialchars($item->fg_name); ?></strong>
                                </td>
                                <td class="text-end">
                                    <span class="stock-value"><?= number_format((float)$item->current_stock, 2); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-uom"><?= htmlspecialchars($item->uom); ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('fg_stock/detail/' . $item->fg_id); ?>" class="btn btn-sm btn-action btn-detail" title="<?= lang_line('btn_detail'); ?>">
                                        <i class="fas fa-list-alt mr-1"></i><?= lang_line('btn_detail'); ?>
                                    </a>
                                    <a href="<?= base_url('fg_stock/export_pdf') . '?fg_id=' . $item->fg_id ?>" class="btn btn-sm btn-action btn-pdf" target="_blank" title="<?= lang_line('btn_export'); ?> PDF">
                                        <i class="fas fa-file-pdf mr-1"></i>PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h5><?= lang_line('fg_stock_no_data'); ?></h5>
                                <p><?= lang_line('fg_stock_no_data_desc'); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
