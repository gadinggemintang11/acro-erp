<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-gray-800 mb-0">
      <i class="fas fa-users-cog text-primary mr-2"></i><?= $title; ?>
    </h3>

    <?php if (check_access('user', 'add')): ?>
    <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
      <i class="fas fa-plus-circle mr-1"></i> Tambah User
    </button>
    <?php endif; ?>
  </div>

  <!-- Flash message -->
  <?php if($this->session->flashdata('success')): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= $this->session->flashdata('success'); ?>',
        timer: 2000,
        showConfirmButton: false
      });
    </script>
  <?php endif; ?>

  <!-- Card -->
  <div class="card border-0 shadow-sm rounded">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
          <thead class="thead-light text-center">
            <tr>
              <th>#</th>
              <th>Nama Lengkap</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th width="180">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($users as $u): ?>
            <tr class="text-center">
              <td><?= $no++; ?></td>
              <td class="text-left"><?= htmlspecialchars($u->fullname); ?></td>
              <td><?= htmlspecialchars($u->username); ?></td>
              <td><?= htmlspecialchars($u->email); ?></td>
              <td><span class="badge badge-info"><?= $u->role_name; ?></span></td>
              <td><span class="badge badge-<?= $u->status=='active'?'success':'secondary'; ?>"><?= ucfirst($u->status); ?></span></td>
              <td><?= $u->last_login ? date('d M Y H:i', strtotime($u->last_login)) : '-'; ?></td>
              <td>
                <?php if (check_access('user', 'edit')): ?>
                  <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $u->id; ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <a href="<?= base_url('user/reset_password/'.$u->id); ?>" class="btn btn-sm btn-info btn-load">
                    <i class="fas fa-key"></i>
                  </a>
                <?php endif; ?>

                <?php if (check_access('user', 'delete')): ?>
                  <a href="<?= base_url('user/delete/'.$u->id); ?>" class="btn btn-sm btn-danger btn-delete">
                    <i class="fas fa-trash"></i>
                  </a>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit User -->
            <div class="modal fade" id="editModal<?= $u->id; ?>" tabindex="-1" role="dialog">
              <div class="modal-dialog" role="document">
                <form method="post" action="<?= base_url('user/update/'.$u->id); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit User</h5>
                      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="fullname" class="form-control" value="<?= $u->fullname; ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?= $u->username; ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $u->email; ?>">
                      </div>
                      <div class="form-group">
                        <label>Role</label>
                        <select name="role_id" class="form-control">
                          <?php foreach($roles as $r): ?>
                            <option value="<?= $r->id; ?>" <?= $u->role_id==$r->id?'selected':''; ?>><?= $r->role_name; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                          <option value="active" <?= $u->status=='active'?'selected':''; ?>>Active</option>
                          <option value="inactive" <?= $u->status=='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-success btn-load">
                        <i class="fas fa-save mr-1"></i> Update
                      </button>
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add User -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('user/add'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Tambah User Baru</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="fullname" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role_id" class="form-control">
              <?php foreach($roles as $r): ?>
                <option value="<?= $r->id; ?>"><?= $r->role_name; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-load">
            <i class="fas fa-save mr-1"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%) !important;
  }
  .modal-content { border-radius: 0.75rem; }
  .table th, .table td { vertical-align: middle !important; }
  .btn-warning { background-color: #f4a261; border: none; }
  .btn-warning:hover { background-color: #e76f51; }
  .badge-info { background-color: #457b9d; }
</style>
