<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-gray-800 mb-0">
      <i class="fas fa-cogs text-primary mr-2"></i><?= $title; ?>
    </h3>

    <?php if (check_access('commoncode', 'add')): ?>
      <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Data
      </button>
    <?php endif; ?>
  </div>

  <!-- Debug (hapus/comment jika sudah beres) -->
  <?php
  // DEBUG - tampilkan session & hasil check_access ke console (hapus nanti)
  $role_id = $this->session->userdata('role_id');
  $can_delete_debug = function() {
      // Avoid running check_access for each row repeatedly here, we'll call once
      return check_access('commoncode', 'delete');
  };
  $check_delete_result = $can_delete_debug();
  ?>
  <script>
    console.debug('DEBUG session:', <?= json_encode($this->session->userdata()); ?>);
    console.debug('DEBUG check_access(commoncode,delete) =>', <?= json_encode($check_delete_result); ?>);
  </script>

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
        <table class="table table-striped table-hover align-middle">
          <thead class="thead-light">
            <tr class="text-center">
              <th style="width:5%">#</th>
              <th>Group</th>
              <th>Code</th>
              <th>Description</th>
              <th>Status</th>
              <th width="180">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($common as $row): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><?= htmlspecialchars($row->group_code); ?></td>
              <td><?= htmlspecialchars($row->code); ?></td>
              <td><?= htmlspecialchars($row->description); ?></td>
              <td class="text-center">
                <span class="badge badge-<?= $row->status == 'active' ? 'success' : 'secondary'; ?> px-3 py-1">
                  <?= ucfirst($row->status); ?>
                </span>
              </td>
              <td class="text-center">
                <?php if (check_access('commoncode', 'edit')): ?>
                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $row->id; ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <?php endif; ?>

                <?php
                // Fallback admin: jika role_id == 1 tampilkan tombol sementara (untuk debugging)
                $is_admin_fallback = ($role_id == 1);
                $can_delete = check_access('commoncode', 'delete') || $is_admin_fallback;
                ?>

                <?php if ($can_delete): ?>
                <a href="<?= base_url('commoncode/delete/'.$row->id); ?>"
                   class="btn btn-sm btn-danger btn-delete">
                   <i class="fas fa-trash-alt"></i>
                </a>
                <?php else: ?>
                <!-- Jika ingin tampil ikon disable, gunakan ini (optional) -->
                <!-- <button class="btn btn-sm btn-danger" disabled title="No permission"><i class="fas fa-trash-alt"></i></button> -->
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="editModal<?= $row->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="post" action="<?= base_url('commoncode/update/'.$row->id); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Common Code</h5>
                      <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label>Group Code</label>
                        <input type="text" name="group_code" class="form-control" value="<?= htmlspecialchars($row->group_code); ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Code</label>
                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($row->code); ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($row->description); ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                          <option value="active" <?= $row->status=='active'?'selected':''; ?>>Active</option>
                          <option value="inactive" <?= $row->status=='inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Update</button>
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

<!-- Modal Add -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" action="<?= base_url('commoncode/add'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Common Code</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Group Code</label>
            <input type="text" name="group_code" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Code</label>
            <input type="text" name="code" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control" required>
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

<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%) !important;
  }
  .modal-content { border-radius: 0.75rem; }
  .table th, .table td { vertical-align: middle !important; }
  .btn-warning { background-color: #f4a261; border: none; }
  .btn-warning:hover { background-color: #e76f51; }
</style>
