<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-check-square text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('production_receive/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No MR / No Issuance..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mr-3 mb-2">
                    <label for="status" class="mr-2">Status:</label>
                    <select name="status" id="status" class="form-control form-control-sm" style="width: 200px;">
                        <option value="">-- Semua Status --</option>
                        <option value="Issued" <?= ($filters['status'] ?? '') == 'Issued' ? 'selected' : ''; ?>>Menunggu Diterima</option>
                        <option value="Received" <?= ($filters['status'] ?? '') == 'Received' ? 'selected' : ''; ?>>Sudah Diterima</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> Cari</button>
                    <a href="<?= base_url('production_receive'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3">
             <h6 class="m-0 font-weight-bold text-primary">Daftar Penerimaan Material (Produksi)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. MR</th>
                            <th>No. Pengeluaran</th>
                            <th>Tgl Minta</th>
                            <th>Disiapkan (Gudang)</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($mrs)): foreach($mrs as $mr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mr->mr_number); ?></td>
                            <td><?= htmlspecialchars($mr->issuance_number ?? '-'); ?></td>
                            <td><?= date('d M Y', strtotime($mr->request_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($mr->issuer_name ?? '-'); ?></td>
                            <td><span class="badge badge-<?= $mr->status == 'Received' ? 'success' : 'warning'; ?>"><?= $mr->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('production_receive/detail/'.$mr->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <?php if($mr->status == 'Issued'): ?>
                                        <i class="fas fa-check-square"></i> Proses Terima
                                    <?php else: ?>
                                        <i class="fas fa-eye"></i> Lihat Detail
                                    <?php endif; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>