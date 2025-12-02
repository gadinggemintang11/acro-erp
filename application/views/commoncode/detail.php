<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="<?= base_url('commoncode'); ?>" class="btn btn-outline-secondary btn-sm mb-2">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
      <h3 class="text-gray-800 mb-0">
        <i class="fas fa-list-alt text-primary mr-2"></i><?= $title; ?>
      </h3>
      <small class="text-muted">Kelola code untuk group: <strong><?= htmlspecialchars($group_code); ?></strong></small>
    </div>

    <?php if (check_access('commoncode', 'add')): ?>
      <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addCodeModal">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Code
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
        <table class="table table-striped table-hover align-middle">
          <thead class="thead-light">
            <tr class="text-center">
              <th style="width:5%">#</th>
              <th>Code</th>
              <th>Description</th>
              <th>Status</th>
              <th width="150">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($codes as $row): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><code><?= htmlspecialchars($row->code); ?></code></td>
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

                <?php if (check_access('commoncode', 'delete')): ?>
                <a href="<?= base_url('commoncode/delete/'.$row->id.'/'.$group_code); ?>"
                   class="btn btn-sm btn-danger btn-delete">
                   <i class="fas fa-trash-alt"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="editModal<?= $row->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="post" action="<?= base_url('commoncode/update/'.$row->id.'/'.$group_code); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-warning text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Code</h5>
                      <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label>Group Code</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($row->group_code); ?>" disabled>
                      </div>
                      <div class="form-group">
                        <label>Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($row->code); ?>" required>
                      </div>
                      <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
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

<!-- Modal Add Code -->
<div class="modal fade" id="addCodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" action="<?= base_url('commoncode/add_code/'.$group_code); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Code Baru</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Group Code</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($group_code); ?>" disabled>
          </div>
          <div class="form-group">
            <label>Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" placeholder="Contoh: BC23" required>
          </div>
          <div class="form-group">
            <label>Description <span class="text-danger">*</span></label>
            <input type="text" name="description" class="form-control" placeholder="Contoh: BC 2.3" required>
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
  .bg-gradient-warning {
    background: linear-gradient(135deg, #f4a261 0%, #e76f51 100%) !important;
  }
  .modal-content { border-radius: 0.75rem; }
  .table th, .table td { vertical-align: middle !important; }
  .btn-warning { background-color: #f4a261; border: none; }
  .btn-warning:hover { background-color: #e76f51; }
  code {
    background-color: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    color: #2c3e50;
    font-weight: 600;
  }
</style>

<script>
$(document).on('click', '.btn-delete', function(e){
  e.preventDefault();
  const url = $(this).attr('href');
  Swal.fire({
    title: 'Anda yakin?',
    text: "Data ini akan dihapus permanen!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });
});
</script>
