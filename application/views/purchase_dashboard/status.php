<?php
    // Hitung breakdown per status dari $pos
    $status_counts = ['Draft'=>0,'Submitted'=>0,'Confirmed'=>0,'Closed'=>0,'Cancelled'=>0];
    $receipt_counts = ['Not Received'=>0,'Partial'=>0,'Complete'=>0];
    foreach ($pos as $p) {
        if (isset($status_counts[$p->status])) $status_counts[$p->status]++;
        if (isset($receipt_counts[$p->receipt_status])) $receipt_counts[$p->receipt_status]++;
    }
    // Rute dikirim eksplisit oleh controller (Pmpo_status/Gmpo_status) sejak
    // label tampilannya jadi "PO Material"/"PO General" (rebrand Acropoly).
    $base_url = $status_route ?? (strtolower($po_type_label) . '_status');

    $status_meta = [
        'Draft'     => ['color' => 'secondary', 'icon' => 'fa-pencil-alt'],
        'Submitted' => ['color' => 'info',      'icon' => 'fa-paper-plane'],
        'Confirmed' => ['color' => 'success',   'icon' => 'fa-check-circle'],
        'Closed'    => ['color' => 'dark',      'icon' => 'fa-lock'],
        'Cancelled' => ['color' => 'danger',    'icon' => 'fa-ban'],
    ];
    $status_label_map = [
        'Draft'     => lang_line('pdb_status_draft'),
        'Submitted' => lang_line('pdb_status_submitted'),
        'Confirmed' => lang_line('pdb_status_confirmed'),
        'Closed'    => lang_line('pdb_status_closed'),
        'Cancelled' => lang_line('pdb_status_cancelled'),
    ];
    $receipt_label_map = [
        'Not Received' => lang_line('pdb_receipt_not_received'),
        'Partial'      => lang_line('pdb_receipt_partial'),
        'Complete'     => lang_line('pdb_receipt_complete'),
    ];
    $po_label_translated = $po_type === 'produksi' ? lang_line('pdb_label_production_mat') : lang_line('pdb_label_general_mat');
?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1">
                <i class="fas fa-tachometer-alt text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <p class="text-muted small mb-0"><?= sprintf(lang_line('pdb_subtitle_status'), htmlspecialchars($po_type_label)); ?>
                (<?= $po_label_translated; ?>).
            </p>
        </div>
    </div>

    <!-- Status breakdown cards -->
    <div class="row mb-4">
        <?php foreach ($status_meta as $st => $meta): ?>
        <div class="col-md">
            <div class="card border-left-<?= $meta['color']; ?> shadow-sm h-100 py-2">
                <div class="card-body p-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= $meta['color']; ?> text-uppercase mb-1"><?= $status_label_map[$st] ?? $st; ?></div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800"><?= number_format($status_counts[$st]); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas <?= $meta['icon']; ?> fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Receipt status breakdown -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body p-3">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?= lang_line('pdb_receipt_not_received'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($receipt_counts['Not Received']); ?> PO</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body p-3">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?= lang_line('pdb_receipt_partial_received'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($receipt_counts['Partial']); ?> PO</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body p-3">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= lang_line('pdb_receipt_complete'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($receipt_counts['Complete']); ?> PO</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter + table -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-1"></i> <?= sprintf(lang_line('pdb_list_label'), htmlspecialchars($po_type_label)); ?>
            </h6>
            <form method="get" class="form-inline flex-wrap mt-2 mt-md-0">
                <input type="text" name="search" class="form-control form-control-sm mr-2"
                       placeholder="<?= htmlspecialchars(lang_line('pdb_search_placeholder')); ?>"
                       value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" style="min-width:180px;">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_status'); ?></option>
                    <?php foreach (array_keys($status_meta) as $st): ?>
                        <option value="<?= $st; ?>" <?= ($filters['status'] ?? '') == $st ? 'selected' : ''; ?>><?= $status_label_map[$st] ?? $st; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="receipt_status" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_receipt'); ?></option>
                    <option value="Not Received" <?= ($filters['receipt_status'] ?? '') == 'Not Received' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_not_received'); ?></option>
                    <option value="Partial"      <?= ($filters['receipt_status'] ?? '') == 'Partial' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_partial'); ?></option>
                    <option value="Complete"     <?= ($filters['receipt_status'] ?? '') == 'Complete' ? 'selected' : ''; ?>><?= lang_line('pdb_receipt_complete'); ?></option>
                </select>
                <select name="supplier_id" class="form-control form-control-sm mr-2">
                    <option value=""><?= lang_line('pdb_opt_all_supplier'); ?></option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s->id; ?>" <?= ($filters['supplier_id'] ?? '') == $s->id ? 'selected' : ''; ?>><?= htmlspecialchars($s->nama_supplier); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search fa-sm"></i></button>
                <a href="<?= base_url($base_url); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo fa-sm"></i></a>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <?php
                                // Kolom "Tipe" muncul kalau daftar ini memayungi lebih
                                // dari satu tipe PO — GMPO sejak 2026-09-11 berisi
                                // General + Jasa + Asset sekaligus.
                                $show_type = !empty($mixed_types);
                                $type_labels_st = ['produksi'=>'Produksi','non_produksi'=>'General','jasa'=>'Jasa','asset'=>'Asset'];
                            ?>
                            <th><?= lang_line('pdb_th_po_number'); ?></th>
                            <?php if ($show_type): ?><th><?= lang_line('pdb_th_type', 'Tipe'); ?></th><?php endif; ?>
                            <th><?= lang_line('pdb_th_pr_number'); ?></th>
                            <th><?= lang_line('pdb_th_po_date'); ?></th>
                            <th><?= lang_line('pdb_th_supplier'); ?></th>
                            <th><?= lang_line('pdb_th_requester'); ?></th>
                            <th><?= lang_line('pdb_th_status'); ?></th>
                            <th><?= lang_line('pdb_th_receipt'); ?></th>
                            <th class="text-right"><?= lang_line('pdb_th_qty_po'); ?></th>
                            <th class="text-right"><?= lang_line('pdb_th_qty_received'); ?></th>
                            <th width="110"><?= lang_line('pdb_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pos)): foreach ($pos as $p):
                            $status_cls  = $status_meta[$p->status]['color'] ?? 'secondary';
                            $receipt_cls = ['Not Received'=>'warning','Partial'=>'info','Complete'=>'success'][$p->receipt_status] ?? 'secondary';
                        ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($p->po_number); ?></td>
                            <?php if ($show_type): ?>
                                <td><span class="badge badge-light border"><?= htmlspecialchars($type_labels_st[$p->type] ?? $p->type); ?></span></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($p->pr_number ?: '-'); ?></td>
                            <td><?= $p->po_date ? date('d M Y', strtotime($p->po_date)) : '-'; ?></td>
                            <td class="text-left"><?= htmlspecialchars($p->nama_supplier ?: '-'); ?></td>
                            <td><?= htmlspecialchars($p->requester_name ?: '-'); ?></td>
                            <td><span class="badge badge-<?= $status_cls; ?>"><?= htmlspecialchars($status_label_map[$p->status] ?? $p->status); ?></span></td>
                            <td><span class="badge badge-<?= $receipt_cls; ?>"><?= htmlspecialchars($receipt_label_map[$p->receipt_status] ?? $p->receipt_status); ?></span></td>
                            <td class="text-right"><?= number_format((float)$p->total_po_qty, 2); ?></td>
                            <td class="text-right"><?= number_format((float)$p->total_qty_received, 2); ?></td>
                            <td>
                                <a href="<?= base_url($detail_route . '/' . $p->id); ?>" class="btn btn-sm btn-primary" title="<?= lang_line('pdb_btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (!empty($can_delete) && $p->status === 'Draft' && !empty($delete_route)): ?>
                                    <a href="<?= base_url($delete_route . '/' . $p->id); ?>"
                                       class="btn btn-sm btn-danger"
                                       title="<?= lang_line('pdb_btn_delete_draft'); ?>"
                                       onclick="return confirm('<?= htmlspecialchars(addslashes(sprintf(lang_line('pdb_confirm_delete'), $p->po_number))); ?>');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="<?= $show_type ? 11 : 10 ?>" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                <?= htmlspecialchars(sprintf(lang_line('pdb_no_data'), $po_type_label)); ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
