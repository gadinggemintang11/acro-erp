<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-truck me-2"></i>Detail Delivery <?= htmlspecialchars($delivery_header->delivery_number ?? '') ?>
        </h3>
        <div>
            <a href="<?= site_url('fg_delivery') ?>" class="btn btn-light border me-2">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>

            <?php if ($delivery_header->status == 'Draft'): ?>
                <a href="<?= site_url('fg_delivery/edit_items/'.$delivery_header->id) ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                <a href="<?= site_url('fg_delivery/ship/'.$delivery_header->id) ?>" 
                   onclick="return confirm('Konfirmasi pengiriman?')" 
                   class="btn btn-success me-2">
                    <i class="fas fa-check me-1"></i> Confirm
                </a>
            <?php elseif ($delivery_header->status == 'Confirmed'): ?>
                <a href="<?= site_url('fg_delivery/unship/'.$delivery_header->id) ?>" 
                   onclick="return confirm('Batalkan konfirmasi pengiriman?')" 
                   class="btn btn-danger me-2">
                    <i class="fas fa-undo me-1"></i> Unconfirm
                </a>
            <?php endif; ?>

            <a href="<?= site_url('fg_delivery/export_pdf/'.$delivery_header->id) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <!-- Info Header -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <strong>Nomor DO</strong><br>
                    <?= htmlspecialchars($delivery_header->delivery_number ?? '-') ?>
                </div>
                <div class="col-md-3 mb-2">
                    <strong>Customer</strong><br>
                    <?= htmlspecialchars($delivery_header->nama_customer ?? '-') ?>
                </div>
                <div class="col-md-4 mb-2">
                    <strong>Alamat</strong><br>
                    <?= htmlspecialchars($delivery_header->customer_address ?? '-') ?>
                </div>
                <div class="col-md-2 mb-2">
                    <strong>Tanggal</strong><br>
                    <?= htmlspecialchars($delivery_header->delivery_date ?? '-') ?>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <strong>Status</strong><br>
                    <?php
                        $status = $delivery_header->status ?? '-';
                        $badgeClass = [
                            'Draft' => 'secondary',
                            'Confirmed' => 'success',
                            'Cancelled' => 'danger'
                        ][$status] ?? 'light';
                    ?>
                    <span class="badge bg-<?= $badgeClass ?> px-3 py-2"><?= $status ?></span>
                </div>
                <div class="col-md-3">
                    <strong>BC Type</strong><br>
                    <?= htmlspecialchars($delivery_header->bc_type_desc ?? '-') ?>

                </div>
                <div class="col-md-3">
                    <strong>BC Number</strong><br>
                    <?= htmlspecialchars($delivery_header->bc_number ?? '-') ?>
                </div>
                <div class="col-md-3">
                    <strong>BC Date</strong><br>
                    <?= htmlspecialchars($delivery_header->bc_date ?? '-') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Item -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark fw-semibold">
                <i class="fas fa-list me-2 text-primary"></i>Detail Item
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th style="width:5%;">#</th>
                            <th style="width:10%;">SC</th>
                            <th style="width:15%;">Kode</th>
                            <th>Nama</th>
                            <th style="width:10%;">Qty</th>
                            <th style="width:8%;">UOM</th>
                            <th style="width:15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($delivery_details)): $no=1; ?>
                            <?php foreach ($delivery_details as $d): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center"><?= htmlspecialchars($d->sc_number ? $d->sc_number : '-') ?></td>
                                    <td><?= htmlspecialchars($d->kode_barang) ?></td>
                                    <td><?= htmlspecialchars($d->nama_barang) ?></td>
                                    <td class="text-end"><?= number_format($d->qty_shipped, 3) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($d->satuan) ?></td>
                                    <td class="text-center">
                                        <?php if ($delivery_header->status == 'Draft'): ?>
                                            <a href="<?= site_url('fg_delivery/edit_items/'.$delivery_header->id) ?>" class="btn btn-sm btn-warning me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= site_url('fg_delivery/delete_detail/'.$d->id.'/'.$delivery_header->id) ?>" 
                                               onclick="return confirm('Hapus item ini?')" 
                                               class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <em>Tidak ada item pada pengiriman ini.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
    .card {
        border-radius: 12px;
    }
    .table th {
        font-weight: 600;
    }
    .table-hover tbody tr:hover {
        background-color: #f3f6ff !important;
    }
    .btn-light.border:hover {
        background: #f8f9fa;
        border-color: #ced4da;
    }
</style>
