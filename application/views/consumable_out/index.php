<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i>Pengeluaran Barang Habis Pakai
        </h3>
        <?php if (check_access('consumable_out', 'add')): ?>
        <a href="<?= base_url('consumable_out/add'); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus fa-sm mr-1"></i> Buat Pengeluaran Baru
        </a>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('consumable_out'); ?>" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Cari No. Dok / Penerima / Dept..."
                           value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" style="min-width:240px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="Created"   <?= ($filters['status'] ?? '') == 'Created'   ? 'selected' : ''; ?>>Created</option>
                        <option value="Confirmed" <?= ($filters['status'] ?? '') == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Cancelled" <?= ($filters['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>" title="Dari tanggal">
                </div>
                <div class="form-group mr-2 mb-2">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>" title="Sampai tanggal">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search fa-sm mr-1"></i>Cari</button>
                    <a href="<?= base_url('consumable_out'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo fa-sm mr-1"></i>Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pengeluaran Barang Habis Pakai</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. Dokumen</th>
                            <th>Tanggal</th>
                            <th>Departemen</th>
                            <th>Penerima</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): foreach ($records as $r): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($r->doc_number); ?></td>
                            <td><?= date('d M Y', strtotime($r->issuance_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($r->department ?? '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($r->recipient ?? '-'); ?></td>
                            <td class="text-left" style="max-width:200px;">
                                <?= htmlspecialchars(mb_strimwidth($r->purpose ?? '-', 0, 60, '...')); ?>
                            </td>
                            <td>
                                <?php
                                    $badge = 'secondary';
                                    if ($r->status == 'Created')   $badge = 'warning';
                                    if ($r->status == 'Confirmed') $badge = 'success';
                                    if ($r->status == 'Cancelled') $badge = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $r->status; ?></span>
                            </td>
                            <td><?= htmlspecialchars($r->created_by_name ?? '-'); ?></td>
                            <td>
                                <a href="<?= base_url('consumable_out/detail/' . $r->id); ?>"
                                   class="btn btn-sm btn-info btn-load" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= base_url('consumable_out/export_pdf/' . $r->id); ?>"
                                   class="btn btn-sm btn-secondary" target="_blank" title="PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php if ($r->status == 'Created' && check_access('consumable_out', 'delete')): ?>
                                <a href="<?= base_url('consumable_out/delete/' . $r->id); ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Hapus dokumen ini?');" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
