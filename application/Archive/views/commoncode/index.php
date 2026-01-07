<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-gray-800 mb-0">
      <i class="fas fa-cogs text-primary mr-2"></i><?= $title; ?>
    </h3>

    <?php if (check_access('commoncode', 'add')): ?>
      <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addGroupModal">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Group
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
              <th>Group Code</th>
              <th>Description</th>
              <th>Total Codes</th>
              <th width="180">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($groups as $row): ?>
            <tr>
              <td class="text-center"><?= $no++; ?></td>
              <td><strong><?= htmlspecialchars($row->group_code); ?></strong></td>
              <td><?= htmlspecialchars($row->group_description); ?></td>
              <td class="text-center">
                <span class="badge badge-info px-3 py-1">
                  <?= $row->total_codes; ?> code(s)
                </span>
              </td>
              <td class="text-center">
                <a href="<?= base_url('commoncode/detail/'.$row->group_code); ?>" 
                   class="btn btn-sm btn-info text-white" 
                   title="Lihat Detail">
                  <i class="fas fa-folder-open"></i> Detail
                </a>

                <?php if (check_access('commoncode', 'edit')): ?>
                <button class="btn btn-sm btn-warning text-white" 
                        data-toggle="modal" 
                        data-target="#editGroupModal<?= $row->id; ?>"
                        title="Edit Group">
                  <i class="fas fa-edit"></i>
                </button>
                <?php endif; ?>

                <?php if (check_access('commoncode', 'delete')): ?>
                <a href="<?= base_url('commoncode/delete_group/'.$row->group_code); ?>"
                   class="btn btn-sm btn-danger btn-delete"
                   title="Hapus Group">
                   <i class="fas fa-trash-alt"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit Group -->
            <div class="modal fade" id="editGroupModal<?= $row->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="post" action="<?= base_url('commoncode/update_group/'.$row->group_code); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-warning text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Group Code</h5>
                      <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <div class="modal-body">
                      <div class="form-group">
                        <label>Group Code <span class="text-danger">*</span></label>
                        <input type="text" name="group_code" class="form-control" value="<?= htmlspecialchars($row->group_code); ?>" required>
                        <small class="form-text text-muted">Gunakan huruf kapital dan underscore (_)</small>
                      </div>
                      <div class="form-group">
                        <label>Group Description <span class="text-danger">*</span></label>
                        <input type="text" name="group_description" class="form-control" value="<?= htmlspecialchars($row->group_description); ?>" required>
                        <small class="form-text text-muted">Deskripsi untuk group/header</small>
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

<!-- Modal Add Group -->
<div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" action="<?= base_url('commoncode/add_group'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Group Code Baru</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Group Code <span class="text-danger">*</span></label>
            <input type="text" name="group_code" class="form-control" placeholder="Contoh: BC_TYPE" required>
            <small class="form-text text-muted">Gunakan huruf kapital dan underscore (_)</small>
          </div>
          <div class="form-group">
            <label>Group Description <span class="text-danger">*</span></label>
            <input type="text" name="group_description" class="form-control" placeholder="Contoh: Jenis Dokumen BC" required>
            <small class="form-text text-muted">Deskripsi untuk group/header</small>
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
  .btn-info { background-color: #3498db; border: none; }
  .btn-info:hover { background-color: #2980b9; }
  .btn-warning { background-color: #f4a261; border: none; }
  .btn-warning:hover { background-color: #e76f51; }
</style>

<script>
$(document).on('click', '.btn-delete', function(e){
  e.preventDefault();
  const url = $(this).attr('href');
  Swal.fire({
    title: 'Anda yakin?',
    text: "Semua code dalam group ini akan terhapus!",
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
