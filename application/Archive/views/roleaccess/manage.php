<div class="container-fluid">
  <h3 class="mb-4 text-gray-800 d-flex align-items-center">
    <i class="fas fa-lock mr-2 text-primary"></i>
    Kelola Hak Akses:
    <span class="text-dark font-weight-bold ml-2"><?= isset($role->role_name) ? $role->role_name : ''; ?></span>
  </h3>

  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="fas fa-user-shield mr-2"></i>Role Permission Matrix</span>
      <a href="<?= base_url('roleaccess'); ?>" class="btn btn-light btn-sm btn-load">
        <i class="fas fa-arrow-left mr-1"></i> Kembali
      </a>
    </div>

    <form method="post" action="<?= base_url('roleaccess/save_permission/'.$role->id); ?>">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="thead-light text-center">
              <tr>
                <th style="width:5%">#</th>
                <th>Nama Modul</th>
                <th style="width:10%">View</th>
                <th style="width:10%">Add</th>
                <th style="width:10%">Edit</th>
                <th style="width:10%">Delete</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($permissions)): ?>
                <?php $no = 1; foreach($permissions as $perm): ?>
                <tr>
                  <td class="text-center"><?= $no++; ?></td>
                  <td><i class="fas fa-folder-open text-secondary mr-2"></i><?= $perm->menu_name; ?></td>
                  
                  <td class="text-center">
                    <label class="switch">
                      <input type="checkbox" name="perm[<?= $perm->menu_id; ?>][view]" <?= $perm->can_view ? 'checked' : ''; ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <label class="switch">
                      <input type="checkbox" name="perm[<?= $perm->menu_id; ?>][add]" <?= $perm->can_add ? 'checked' : ''; ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <label class="switch">
                      <input type="checkbox" name="perm[<?= $perm->menu_id; ?>][edit]" <?= $perm->can_edit ? 'checked' : ''; ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <label class="switch">
                      <input type="checkbox" name="perm[<?= $perm->menu_id; ?>][delete]" <?= $perm->can_delete ? 'checked' : ''; ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">Tidak ada data modul ditemukan.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-right">
        <button type="submit" class="btn btn-success btn-load">
          <i class="fas fa-save mr-1"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Styling -->
<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%) !important;
  }
  .switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
  }
  .switch input { opacity: 0; width: 0; height: 0; }
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 24px;
  }
  .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
  }
  input:checked + .slider { background-color: #457b9d; }
  input:checked + .slider:before { transform: translateX(22px); }
  table th, table td { vertical-align: middle !important; }
</style>

<!-- SweetAlert success feedback -->
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
