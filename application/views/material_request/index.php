<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('material_request', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addMrModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat MR Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('material_request/index'); ?>" class="form-inline">
                 <div class="form-group mr-2 mb-2">
                    <label for="start_date" class="mr-2">Tgl Mulai:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start_date'] ?? ''); ?>">
                </div>
                 <div class="form-group mr-3 mb-2">
                    <label for="end_date" class="mr-2">Tgl Akhir:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end_date'] ?? ''); ?>">
                </div>
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari No MR..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter fa-sm mr-1"></i> Filter</button>
                    <a href="<?= base_url('material_request'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No. MR</th>
                            <th>Tgl Permintaan</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($mrs)): foreach($mrs as $mr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mr->mr_number); ?></td>
                            <td><?= date('d M Y', strtotime($mr->request_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($mr->requester_name ?? '-'); ?></td>
                            <td><span class="badge badge-<?= $mr->status == 'Submitted' ? 'warning' : 'info'; ?>"><?= $mr->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('material_request/detail/'.$mr->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <?php if ($mr->status == 'Draft' && check_access('material_request', 'delete')): ?>
                                <a href="<?= base_url('material_request/delete_mr/'.$mr->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menghapus MR ini?">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data Material Request</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('material_request/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Material Request Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal Permintaan</label>
                        <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load">
                        <i class="fas fa-arrow-right mr-1"></i> Simpan & Lanjut ke Item
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>