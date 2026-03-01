<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><?= $title ?></h1>
            <p class="text-muted small">Daftar transaksi Return Supplier (RTN). Gunakan tombol aksi di kanan untuk melihat atau menindaklanjuti.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= base_url('Warehouse_out/create') ?>" class="btn btn-gradient btn-primary">
                <i class="fas fa-plus-circle"></i> Buat Retur Baru
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center mb-3">
                <div class="col-md-4 mb-2 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input id="tableSearch" type="search" class="form-control form-control-sm" placeholder="Cari Nomor, Supplier, BC...">
                        <button id="clearSearch" class="btn btn-sm btn-outline-secondary" type="button">Clear</button>
                    </div>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        <a href="<?= base_url('Warehouse_out/export_excel_all') ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel"></i> Export Excel</a>
                        <a href="<?= base_url('Warehouse_out/export_pdf_all') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf"></i> Export PDF</a>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" id="warehouseOutTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nomor</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>BC</th>
                            <th>Qty Items</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach (($report_data ?? []) as $r): ?>
                        <tr>
                            <td class="text-muted"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($r->out_number ?: '-') ?></strong></td>
                            <td class="small text-muted"><?= $r->created_at ? date('d M Y', strtotime($r->created_at)) : '-' ?></td>
                            <td class="small"><?= htmlspecialchars($r->nama_supplier ?? ($r->supplier_name ?? '-')) ?></td>
                            <td class="small"><?= trim(htmlspecialchars(($r->bc_type_code ?? '') . ' ' . ($r->bc_number ?? ''))) ?: '-' ?></td>
                            <td class="text-center small"><?= (int)($r->item_count ?? 0) ?></td>
                            <td>
                                <?php
                                    $status = $r->status ?? '-';
                                    $badge = 'bg-secondary text-white';
                                    if ($status == 'Draft') $badge = 'bg-warning text-dark';
                                    if ($status == 'Pending') $badge = 'bg-info text-white';
                                    if ($status == 'Confirmed') $badge = 'bg-success text-white';
                                ?>
                                <span class="badge <?= $badge ?> small"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= base_url('Warehouse_out/export_excel/'.$r->id) ?>" class="btn btn-outline-success" title="Excel"><i class="fas fa-file-excel"></i></a>
                                    <a href="<?= base_url('Warehouse_out/export_pdf/'.$r->id) ?>" class="btn btn-outline-secondary" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                    <?php if (check_access('warehouse_out', 'edit')): ?>
                                        <a href="<?= base_url('Warehouse_out/detail/'.$r->id) ?>" class="btn btn-outline-primary" title="Detail"><i class="fas fa-eye"></i></a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if (($r->status ?? '') === 'Draft' && check_access('warehouse_out', 'delete')): ?>
                                            <li>
                                                <form method="post" action="<?= base_url('Warehouse_out/delete_header/'.$r->id) ?>" onsubmit="return confirm('Hapus header retur ini? Pastikan tidak ada detail di dalamnya.');">
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (($r->status ?? '') !== 'Confirmed' && check_access('warehouse_out', 'confirm')): ?>
                                            <li><a class="dropdown-item" href="<?= base_url('Warehouse_out/confirm/'.$r->id) ?>">Confirm</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && jQuery.fn.DataTable) {
        const table = jQuery('#warehouseOutTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            lengthChange: false,
            responsive: true,
            columnDefs: [
                { orderable: false, targets: -1 },
                { orderable: false, targets: 5 } // Qty items not necessary to order
            ],
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                paginate: {
                    previous: "<",
                    next: ">"
                }
            }
        });

        // Wire custom search box to DataTables
        const $search = jQuery('#tableSearch');
        $search.on('input', function(){ table.search(this.value).draw(); });
        jQuery('#clearSearch').on('click', function(){ $search.val(''); table.search('').draw(); });

        // Filter by status
        jQuery('#filterStatus').on('change', function(){
            const val = this.value;
            if (!val) table.column(6).search('').draw();
            else table.column(6).search('^' + val + '$', true, false).draw();
        });
    }
});
</script>
