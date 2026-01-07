<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary mb-0">
            <i class="fas fa-dolly me-2"></i>Pengeluaran Barang Jadi (FG Delivery)
        </h3>
        <a href="<?= site_url('fg_delivery/create') ?>" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Tambah Delivery
        </a>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Cari nomor DO / customer">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="Draft" <?= ($filters['status'] ?? '')=='Draft'?'selected':'' ?>>Draft</option>
                        <option value="Confirmed" <?= ($filters['status'] ?? '')=='Confirmed'?'selected':'' ?>>Confirmed</option>
                        <option value="Cancelled" <?= ($filters['status'] ?? '')=='Cancelled'?'selected':'' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-primary px-4"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="<?= site_url('fg_delivery') ?>" class="btn btn-light border ms-2"><i class="fas fa-sync"></i></a>
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
                            <th style="width:5%;">#</th>
                            <th>No. DO</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>SC</th>
                            <th>BC</th>
                            <th>Status</th>
                            <th style="width:18%;">Aksi</th>
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
                                <a href="<?= site_url('fg_delivery/show/'.$d->id) ?>" class="btn btn-sm btn-info me-1"><i class="fas fa-eye"></i></a>
                                <?php if ($d->status=='Draft'): ?>
                                    <a href="<?= site_url('fg_delivery/edit/'.$d->id) ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                                    <a href="<?= site_url('fg_delivery/destroy/'.$d->id) ?>" onclick="return confirm('Hapus draft ini?')" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></a>
                                <?php elseif ($d->status=='Confirmed'): ?>
                                    <a href="<?= site_url('fg_delivery/destroy/'.$d->id) ?>" onclick="return confirm('Batalkan DO ini?')" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted"><em>Tidak ada data ditemukan.</em></td>
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
