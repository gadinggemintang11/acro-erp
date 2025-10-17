<div class="container-fluid">
  <h3 class="mb-4 text-gray-800">
    <i class="fas fa-user-shield mr-2 text-primary"></i><?= $title; ?>
  </h3>

  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="fas fa-list mr-2"></i>Daftar Role Pengguna</span>
      <button class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus mr-1"></i> Tambah Role
      </button>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th>#</th>
              <th>Role Name</th>
              <th>Description</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($roles as $row): ?>
            <tr class="text-center">
              <td><?= $no++; ?></td>
              <td class="text-left font-weight-bold"><?= $row->role_name; ?></td>
              <td class="text-left"><?= $row->description ?: '-'; ?></td>
              <td>
                <span class="badge badge-<?= $row->status=='active'?'success':'secondary'; ?>">
                  <?= ucfirst($row->status); ?>
                </span>
              </td>
              <td>
                <a href="<?= base_url('roleaccess/manage_permission/'.$row->id); ?>" class="btn btn-sm btn-info btn-load">
                  <i class="fas fa-lock"></i> Kelola
                </a>
                <a href="<?= base_url('roleaccess/delete/'.$row->id); ?>" class="btn btn-sm btn-danger btn-delete">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add Role -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('roleaccess/add'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Tambah Role Baru</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Role Name</label>
            <input type="text" name="role_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>
