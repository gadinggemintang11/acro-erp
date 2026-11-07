<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-gray-800 mb-0">
      <i class="fas fa-id-card-alt text-primary mr-2"></i><?= htmlspecialchars($title); ?>
    </h3>
    <div>
      <?php if (check_access('master_karyawan', 'add')): ?>
        <button class="btn btn-success shadow-sm" data-toggle="modal" data-target="#importModal">
          <i class="fas fa-file-excel mr-1"></i> Import Excel
        </button>
      <?php endif; ?>
      <a href="<?= base_url('masterkaryawan/export_excel?search=' . urlencode($search ?? '') . '&status=' . urlencode($status ?? '')); ?>" class="btn btn-outline-success shadow-sm">
        <i class="fas fa-download mr-1"></i> Export
      </a>
      <?php if (check_access('master_karyawan', 'add')): ?>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
          <i class="fas fa-plus-circle mr-1"></i> Tambah Karyawan
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash message -->
  <?php if($this->session->flashdata('success')): ?>
    <script>
      Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= addslashes($this->session->flashdata('success')); ?>', timer: 2500, showConfirmButton: false });
    </script>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <script>
      Swal.fire({ icon: 'error', title: 'Gagal!', text: '<?= addslashes($this->session->flashdata('error')); ?>', showConfirmButton: true });
    </script>
  <?php endif; ?>

  <!-- Filter -->
  <div class="card border-0 shadow-sm rounded mb-3">
    <div class="card-body py-3">
      <form method="get" action="<?= base_url('masterkaryawan'); ?>" class="form-row align-items-end">
        <div class="col-md-5 mb-2">
          <label class="small font-weight-bold mb-1">Cari</label>
          <input type="text" name="search" class="form-control" placeholder="Nama / NIP / Bank / No. Rekening" value="<?= htmlspecialchars($search ?? ''); ?>">
        </div>
        <div class="col-md-3 mb-2">
          <label class="small font-weight-bold mb-1">Status</label>
          <select name="status" class="form-control">
            <option value="">-- Semua --</option>
            <option value="active"   <?= ($status ?? '')=='active'?'selected':''; ?>>Active</option>
            <option value="inactive" <?= ($status ?? '')=='inactive'?'selected':''; ?>>Inactive</option>
          </select>
        </div>
        <div class="col-md-4 mb-2">
          <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
          <a href="<?= base_url('masterkaryawan'); ?>" class="btn btn-secondary"><i class="fas fa-redo mr-1"></i> Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card border-0 shadow-sm rounded">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
          <thead class="thead-light text-center">
            <tr>
              <th width="5%">No</th>
              <th>Nama Karyawan</th>
              <th>NIP</th>
              <th>Nama Bank</th>
              <th>No. Rekening</th>
              <th width="10%">Status</th>
              <th width="120">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($karyawan)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data karyawan.</td></tr>
            <?php else: $no = ($offset ?? 0) + 1; foreach($karyawan as $k): ?>
            <tr class="text-center">
              <td><?= $no++; ?></td>
              <td class="text-left"><?= htmlspecialchars($k->nama_karyawan); ?></td>
              <td><?= htmlspecialchars($k->nip ?? '-'); ?></td>
              <td><?= htmlspecialchars($k->bank_name ?? '-'); ?></td>
              <td><?= htmlspecialchars($k->bank_account ?? '-'); ?></td>
              <td><span class="badge badge-<?= $k->status=='active'?'success':'secondary'; ?>"><?= ucfirst($k->status); ?></span></td>
              <td>
                <?php if (check_access('master_karyawan', 'edit')): ?>
                  <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $k->id; ?>"><i class="fas fa-edit"></i></button>
                <?php endif; ?>
                <?php if (check_access('master_karyawan', 'delete') && $k->status == 'active'): ?>
                  <a href="<?= base_url('masterkaryawan/delete/'.$k->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Nonaktifkan karyawan ini?');"><i class="fas fa-ban"></i></a>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="editModal<?= $k->id; ?>" tabindex="-1" role="dialog">
              <div class="modal-dialog" role="document">
                <form method="post" action="<?= base_url('masterkaryawan/update/'.$k->id); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Karyawan</h5>
                      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label>Nama Karyawan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_karyawan" class="form-control" value="<?= htmlspecialchars($k->nama_karyawan); ?>" required>
                      </div>
                      <div class="form-group">
                        <label>NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($k->nip ?? ''); ?>">
                      </div>
                      <div class="form-group">
                        <label>Nama Bank</label>
                        <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($k->bank_name ?? ''); ?>">
                      </div>
                      <div class="form-group">
                        <label>No. Rekening</label>
                        <input type="text" name="bank_account" class="form-control" value="<?= htmlspecialchars($k->bank_account ?? ''); ?>">
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                          <option value="active"   <?= $k->status=='active'?'selected':''; ?>>Active</option>
                          <option value="inactive" <?= $k->status=='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Update</button>
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted">Total: <?= (int)($total_rows ?? 0); ?> karyawan</small>
        <?= $pagination ?? ''; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('masterkaryawan/add'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Tambah Karyawan</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Karyawan <span class="text-danger">*</span></label>
            <input type="text" name="nama_karyawan" class="form-control" required>
          </div>
          <div class="form-group">
            <label>NIP (Nomor Induk Pegawai)</label>
            <input type="text" name="nip" class="form-control">
          </div>
          <div class="form-group">
            <label>Nama Bank</label>
            <input type="text" name="bank_name" class="form-control" placeholder="cth: BCA / Mandiri">
          </div>
          <div class="form-group">
            <label>No. Rekening</label>
            <input type="text" name="bank_account" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('masterkaryawan/import_excel'); ?>" enctype="multipart/form-data">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-file-excel mr-2"></i>Import Karyawan dari Excel</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Format kolom: <strong>Nama Karyawan</strong> | <strong>NIP</strong> | <strong>Nama Bank</strong> | <strong>No. Rekening</strong>.
            Baris pertama dianggap header. NIP duplikat akan dilewati.
          </div>
          <a href="<?= base_url('masterkaryawan/download_template'); ?>" class="btn btn-sm btn-outline-primary mb-3">
            <i class="fas fa-download mr-1"></i> Download Template
          </a>
          <div class="form-group">
            <label>File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
            <input type="file" name="import_file" class="form-control-file" accept=".xlsx,.xls" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i> Import</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
  .bg-gradient-primary { background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%) !important; }
  .modal-content { border-radius: 0.75rem; }
  .table th, .table td { vertical-align: middle !important; }
  .btn-warning { background-color: #f4a261; border: none; }
  .btn-warning:hover { background-color: #e76f51; }
</style>
