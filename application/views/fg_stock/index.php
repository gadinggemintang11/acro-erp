<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-boxes text-info mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <a href="<?= base_url('fg_stock') ?>" class="btn btn-sm btn-secondary">Refresh</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get" action="<?= base_url('fg_stock/index'); ?>" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Cari Kode atau Nama Barang Jadi</label>
                    <input type="text" name="search_summary" id="search_summary" class="form-control form-control-sm" placeholder="Cari Kode atau Nama..." value="<?= htmlspecialchars($filters['search_summary'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Filter Tanggal (opsional)</label>
                    <div class="d-flex">
                        <input type="date" name="start_date" class="form-control form-control-sm me-1" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Cari</button>
                    <a href="<?= base_url('fg_stock') ?>" class="btn btn-light btn-sm border ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" style="font-size:0.95rem;">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang (Style)</th>
                            <th class="text-end" width="160">Stok Saat Ini</th>
                            <th width="80" class="text-center">Unit</th>
                            <th width="130" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($stock_summary)): $no = 1; ?>
                            <?php foreach($stock_summary as $item): ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td class="font-weight-bold"><?= htmlspecialchars($item->fg_code); ?></td>
                                    <td><?= htmlspecialchars($item->fg_name); ?></td>
                                    <td class="text-end font-weight-bold"><?= number_format((float)$item->current_stock, 4); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($item->uom); ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('fg_stock/detail/' . $item->fg_id); ?>" class="btn btn-sm btn-info" title="Lihat Kartu Stok">
                                            <i class="fas fa-list-alt"></i> Detail
                                        </a>
                                        <a href="<?= base_url('fg_stock/export_pdf') . '?fg_id=' . $item->fg_id ?>" class="btn btn-sm btn-secondary" target="_blank" title="Export PDF Kartu Stok">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data stok ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
