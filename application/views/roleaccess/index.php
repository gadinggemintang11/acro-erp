<?php
    // Kunci Role Access (user 2026-09-17, diminta lagi 2026-09-29): satu
    // tombol mengunci SEMUA role. Selagi terkunci, tombol Tambah / Kelola Hak
    // Akses / Edit / Hapus disembunyikan. Hanya Admin yang bisa memutar kunci.
    // Gerbang yang sesungguhnya ada di Roleaccess::_deny_if_locked().
    $ra_locked   = !empty($is_locked);
    $ra_can_lock = is_admin() && !empty($lock_info); // lock_info null = migrasi belum jalan
?>
<div class="container-fluid">
  <h3 class="mb-4 text-gray-800">
    <i class="fas fa-user-shield mr-2 text-primary"></i><?= $title; ?>
  </h3>

  <?php if ($ra_locked): ?>
  <div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="fas fa-lock fa-lg mr-3"></i>
    <div>
      <strong><?= lang_line('ra_locked_title', 'Role Access terkunci.'); ?></strong>
      <?= lang_line('ra_locked_desc', 'Tambah, ubah, dan hapus role dimatikan sampai Admin membuka kuncinya.'); ?>
      <?php if (!empty($lock_info->locked_by_name)): ?>
      <div class="small text-muted mt-1">
        <?= lang_line('ra_locked_by', 'Dikunci oleh'); ?>
        <?= htmlspecialchars($lock_info->locked_by_name); ?>
        <?= !empty($lock_info->locked_at) ? ' · ' . date('d M Y H:i', strtotime($lock_info->locked_at)) : ''; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card shadow border-0">
    <div class="card-header" style="background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%); color: white;" class="d-flex justify-content-between align-items-center">
      <span><i class="fas fa-list mr-2"></i><?= lang_line('ra_list_title', 'Daftar Role Pengguna'); ?></span>
      <div class="btn-group" role="group">
        <?php if ($ra_can_lock): ?>
        <a href="<?= base_url('roleaccess/toggle_lock'); ?>"
           class="btn btn-sm font-weight-bold <?= $ra_locked ? 'btn-warning' : 'btn-light'; ?>"
           onclick="return confirm('<?= htmlspecialchars($ra_locked
                ? lang_line('ra_confirm_unlock', 'Buka kunci Role Access? Tombol tambah, ubah, dan hapus role akan muncul lagi.')
                : lang_line('ra_confirm_lock', 'Kunci Role Access? Tombol tambah, ubah, dan hapus role akan disembunyikan untuk semua orang.'), ENT_QUOTES); ?>');">
          <i class="fas <?= $ra_locked ? 'fa-unlock' : 'fa-lock'; ?> mr-1"></i>
          <?= $ra_locked ? lang_line('ra_btn_unlock', 'Buka Kunci') : lang_line('ra_btn_lock', 'Kunci Role'); ?>
        </a>
        <?php endif; ?>
        <?php if (!$ra_locked): ?>
        <button class="btn btn-light btn-sm font-weight-bold" data-toggle="modal" data-target="#addModal">
          <i class="fas fa-plus mr-1"></i> <?= lang_line('ra_btn_add', 'Tambah Role'); ?>
        </button>
        <?php endif; ?>
        <a href="<?= base_url('roleaccess/export_excel'); ?>" class="btn btn-light btn-sm font-weight-bold">
          <i class="fas fa-download mr-1"></i> <?= lang_line('ra_btn_excel', 'Export Excel'); ?>
        </a>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="thead-light text-center">
            <tr>
              <th><?= lang_line('label_no', 'No'); ?></th>
              <th><?= lang_line('ra_th_role_name', 'Role Name'); ?></th>
              <th><?= lang_line('ra_th_description', 'Description'); ?></th>
              <th><?= lang_line('ra_th_department', 'Department'); ?></th>
              <th><?= lang_line('ra_th_status', 'Status'); ?></th>
              <th><?= lang_line('ra_th_action', 'Action'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($roles as $row): ?>
            <tr class="text-center">
              <td><?= $no++; ?></td>
              <td class="text-left font-weight-bold"><?= $row->role_name; ?></td>
              <td class="text-left"><?= $row->description ?: '-'; ?></td>
              <td class="text-left"><?= $row->department ?: '-'; ?></td>
              <td>
                <span class="badge badge-<?= $row->status=='active'?'success':'secondary'; ?>">
                  <?= ucfirst($row->status); ?>
                </span>
              </td>
              <td>
                <?php if ($ra_locked): ?>
                  <span class="text-muted small"><i class="fas fa-lock mr-1"></i><?= lang_line('ra_locked_short', 'Terkunci'); ?></span>
                <?php else: ?>
                <a href="<?= base_url('roleaccess/manage_permission/'.$row->id); ?>" class="btn btn-sm btn-info btn-load" title="<?= lang_line('ra_btn_manage_perm', 'Kelola Hak Akses'); ?>">
                  <i class="fas fa-lock"></i>
                </a>
                <button type="button" class="btn btn-sm btn-warning btn-edit" title="<?= lang_line('ra_btn_edit_role', 'Edit Role'); ?>"
                  data-id="<?= $row->id; ?>"
                  data-name="<?= $row->role_name; ?>"
                  data-desc="<?= $row->description ?: ''; ?>"
                  data-dept="<?= $row->department ?: ''; ?>"
                  data-status="<?= $row->status; ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <a href="<?= base_url('roleaccess/delete/'.$row->id); ?>" class="btn btn-sm btn-danger btn-delete" title="<?= lang_line('ra_btn_delete_role', 'Hapus Role'); ?>">
                  <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
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
        <div class="modal-header" style="background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%); color: white;">
          <h5 class="modal-title"><i class="fas fa-plus mr-2"></i><?= lang_line('ra_add_modal_title', 'Tambah Role Baru'); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><?= lang_line('ra_label_role_name', 'Role Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="role_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= lang_line('ra_label_description', 'Description'); ?></label>
            <textarea name="description" class="form-control" rows="2" placeholder="<?= lang_line('ra_desc_placeholder', 'Deskripsi singkat role...'); ?>"></textarea>
          </div>
          <div class="form-group">
            <label><?= lang_line('ra_label_department', 'Department'); ?></label>
            <select name="department" class="form-control">
              <option value=""><?= lang_line('ra_select_department', '-- Pilih Department --'); ?></option>
              <?php foreach($departments as $dept): ?>
              <option value="<?= $dept->code; ?>"><?= $dept->description; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save', 'Simpan'); ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Role -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" id="editForm" action="">
      <div class="modal-content border-0 shadow">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%); color: white;">
          <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('ra_edit_modal_title', 'Edit Role'); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><?= lang_line('ra_label_role_name', 'Role Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" id="editRoleName" name="role_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= lang_line('ra_label_description', 'Description'); ?></label>
            <textarea id="editDescription" name="description" class="form-control" rows="2" placeholder="<?= lang_line('ra_desc_placeholder', 'Deskripsi singkat role...'); ?>"></textarea>
          </div>
          <div class="form-group">
            <label><?= lang_line('ra_label_department', 'Department'); ?></label>
            <select id="editDepartment" name="department" class="form-control">
              <option value=""><?= lang_line('ra_select_department', '-- Pilih Department --'); ?></option>
              <?php foreach($departments as $dept): ?>
              <option value="<?= $dept->code; ?>"><?= $dept->description; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label><?= lang_line('ra_label_status', 'Status'); ?></label>
            <select name="status" id="editStatus" class="form-control">
              <option value="active"><?= lang_line('ra_status_active', 'Active'); ?></option>
              <option value="inactive"><?= lang_line('ra_status_inactive', 'Inactive'); ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save', 'Simpan'); ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  // Handle edit modal population
  $(document).on('click', '.btn-edit', function(e) {
    e.preventDefault();

    const id = $(this).data('id');
    const name = $(this).data('name');
    const desc = $(this).data('desc');
    const dept = $(this).data('dept');
    const status = $(this).data('status');

    // Set form action
    $('#editForm').attr('action', '<?= base_url('roleaccess/update/'); ?>' + id);

    // Set values
    setTimeout(function() {
      $('#editRoleName').val(name).trigger('change');
      $('#editDescription').val(desc).trigger('change');
      $('#editDepartment').val(dept).trigger('change');
      $('#editStatus').val(status).trigger('change');
    }, 100);

    // Show modal
    $('#editModal').modal('show');
  });
</script>
