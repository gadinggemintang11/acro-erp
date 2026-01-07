<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-signature text-primary mr-2"></i><?= lang_line('subkon_order_title', 'Daftar Kontrak Subkon (Subkon Order)'); ?></h1>
        <?php if (check_access('subkon_order', 'add')): ?>
            <a href="<?= base_url('subkon_order/create'); ?>" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle fa-sm mr-1"></i> <?= lang_line('subkon_order_create_new', 'Buat Kontrak Baru'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> <?= lang_line('subkon_order_filter', 'Filter Kontrak'); ?></h6>
            <div>
                <a href="<?= base_url('subkon_order/export_list_excel?' . http_build_query($filters)); ?>" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-excel mr-1"></i> <?= lang_line('subkon_order_export_excel', 'Ekspor Excel'); ?></a>
            </div>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_order/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date"><?= lang_line('subkon_order_start_date', 'Mulai Tgl'); ?></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date"><?= lang_line('subkon_order_end_date', 'Sampai Tgl'); ?></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status"><?= lang_line('subkon_order_status', 'Status'); ?></label>
                        <select name="status" id="status" class="form-control">
                            <option value=""><?= lang_line('subkon_order_all_status', 'Semua Status'); ?></option>
                            <option value="Draft" <?= $filters['status'] == 'Draft' ? 'selected' : ''; ?>><?= lang_line('subkon_order_status_draft', 'Draft'); ?></option>
                            <option value="Confirmed" <?= $filters['status'] == 'Confirmed' ? 'selected' : ''; ?>><?= lang_line('subkon_order_status_confirmed', 'Confirmed'); ?></option>
                            <option value="Closed" <?= $filters['status'] == 'Closed' ? 'selected' : ''; ?>><?= lang_line('subkon_order_status_closed', 'Closed'); ?></option>
                            <option value="Cancelled" <?= $filters['status'] == 'Cancelled' ? 'selected' : ''; ?>><?= lang_line('subkon_order_status_cancelled', 'Cancelled'); ?></option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search"><?= lang_line('subkon_order_search', 'Cari (No/Supplier)'); ?></label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search']); ?>" placeholder="<?= lang_line('subkon_order_search_placeholder', 'Cari...'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('subkon_order_show', 'Tampilkan'); ?></button>
                <a href="<?= base_url('subkon_order/index'); ?>" class="btn btn-secondary"><?= lang_line('reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="dataTable" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('subkon_order_no', 'No'); ?></th>
                            <th><?= lang_line('subkon_order_contract_no', 'No. Kontrak'); ?></th>
                            <th><?= lang_line('subkon_order_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('subkon_order_supplier', 'Supplier (Vendor)'); ?></th>
                            <th class="text-right"><?= lang_line('subkon_order_total_service', 'Total Jasa (IDR)'); ?></th>
                            <th class="text-center"><?= lang_line('subkon_order_status', 'Status'); ?></th>
                            <th class="text-center"><?= lang_line('subkon_order_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center"><?= lang_line('subkon_order_no_data', 'Tidak ada data kontrak subkon ditemukan'); ?></td></tr>
                        <?php else: $no = $start_no; foreach ($orders as $order): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($order->subkon_order_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($order->order_date)); ?></td>
                                <td><?= htmlspecialchars($order->nama_supplier); ?></td>
                                
                                <td class="text-right"><?= number_format($order->total_jasa, 2); ?></td>
                                <td class="text-center">
                                    <?php
                                        $status_colors = ['Draft' => 'secondary', 'Confirmed' => 'success', 'Closed' => 'primary', 'Cancelled' => 'danger'];
                                        $color = $status_colors[$order->status] ?? 'dark';
                                    ?>
                                    <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($order->status); ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= base_url('subkon_order/detail/' . $order->id); ?>" class="btn btn-info btn-sm" title="<?= lang_line('subkon_order_view_detail', 'Lihat Detail & BOM'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('subkon_order/export_pdf/' . $order->id); ?>" target="_blank" class="btn btn-danger btn-sm" title="<?= lang_line('subkon_order_print_pdf', 'Cetak PDF'); ?>">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <?= $pagination; ?>
            </div>
        </div>
    </div>
</div>