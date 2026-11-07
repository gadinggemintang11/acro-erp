<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-gray-800 mb-0">
      <i class="fas fa-users-cog text-primary mr-2"></i><?= $title; ?>
    </h3>

    <div>
      <?php // Tombol "Persetujuan User" dihapus 2026-11-05: user baru langsung aktif tanpa approval. ?>
      <?php if (check_access('user', 'add')): ?>
      <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
        <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('usr_btn_add', 'Tambah User'); ?>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php
      // ERP Revision Notes "TYPE OF USER" (dikerjakan 2026-09-04) — daftar
      // label & warna badge dipakai berulang di list+modal, disatukan di sini.
      $USER_TYPE_LABELS = [
          'super_admin' => 'Super Admin',
          'accounting'  => 'Accounting',
          'user'        => 'User',
      ];
      $USER_TYPE_BADGE = [
          'super_admin' => 'dark',
          'accounting'  => 'info',
          'user'        => 'secondary',
      ];
      // Tipe user eksekutif dihapus 2026-11-05 -- semua tipe di atas boleh dipilih.
      $USER_TYPE_SELECTABLE = $USER_TYPE_LABELS;

      $ROLE_BY_ID = [];
      foreach ($roles as $r) { $ROLE_BY_ID[(int) $r->id] = $r; }

  ?>

  <!-- Flash message -->
  <?php if($this->session->flashdata('success')): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: <?= json_encode(lang_line('usr_flash_success_title', 'Berhasil!')); ?>,
        text: '<?= $this->session->flashdata('success'); ?>',
        timer: 2000,
        showConfirmButton: false
      });
    </script>
  <?php endif; ?>

  <?php if($this->session->flashdata('error')): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: <?= json_encode(lang_line('usr_flash_error_title', 'Gagal!')); ?>,
        text: '<?= $this->session->flashdata('error'); ?>',
        showConfirmButton: true
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
              <th><?= lang_line('label_no', 'No'); ?></th>
              <th><?= lang_line('usr_th_fullname', 'Nama Lengkap'); ?></th>
              <th><?= lang_line('usr_th_username', 'Username'); ?></th>
              <th><?= lang_line('usr_th_email', 'Email'); ?></th>
              <th><?= lang_line('usr_th_department', 'Departemen'); ?></th>
              <th><?= lang_line('usr_th_join_date', 'Tanggal Masuk'); ?></th>
              <th><?= lang_line('usr_th_role', 'Departemen (Role)'); ?></th>
              <th><?= lang_line('usr_th_type', 'Tipe'); ?></th>
              <th><?= lang_line('usr_th_status', 'Status'); ?></th>
              <th><?= lang_line('usr_th_last_login', 'Last Login'); ?></th>
              <th width="180"><?= lang_line('label_action', 'Aksi'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php $no=1; foreach($users as $u): ?>
            <tr class="text-center">
              <td><?= $no++; ?></td>
              <td class="text-left"><?= htmlspecialchars($u->fullname); ?></td>
              <td><?= htmlspecialchars($u->username); ?></td>
              <td><?= htmlspecialchars($u->email); ?></td>
              <td class="text-left"><?= htmlspecialchars($u->departemen ?? '-'); ?></td>
              <td><?= $u->tanggal_masuk ? date('d M Y', strtotime($u->tanggal_masuk)) : '-'; ?></td>
              <td class="text-left">
                <?php
                  // Semua role user (Role Level 2026-09-15); role nonaktif tetap tampil dari nama role utama.
                  $uroles = $user_roles_map[(int) $u->id] ?? [];
                  if (empty($uroles) && !empty($u->role_name)): ?>
                    <span class="badge badge-info"><?= htmlspecialchars($u->role_name); ?></span>
                <?php endif;
                  foreach ($uroles as $rid):
                    $ro = $ROLE_BY_ID[$rid] ?? null;
                    $label = $ro ? $ro->role_name : ('Role #' . $rid . ($rid === (int) $u->role_id && $u->role_name ? ' ' . $u->role_name : ''));
                ?>
                    <span class="badge badge-info mb-1" title="<?= htmlspecialchars($ro && !empty($ro->position_name) ? ($ro->department_name . ' · ' . $ro->position_name) : ''); ?>"><?= htmlspecialchars($label); ?></span>
                <?php endforeach; ?>
              </td>
              <td><span class="badge badge-<?= $USER_TYPE_BADGE[$u->user_type] ?? 'secondary'; ?>"><?= $USER_TYPE_LABELS[$u->user_type] ?? $u->user_type; ?></span></td>
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
                  <?php if ($u->status == 'active'): ?>
                    <a href="<?= base_url('user/delete/'.$u->id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang_line('usr_confirm_deactivate', 'Yakin nonaktifkan user ini? Data tidak akan dihapus dan masih bisa diaktifkan kembali.'); ?>');">
                      <i class="fas fa-ban"></i>
                    </a>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?= lang_line('usr_badge_inactive', 'Inactive'); ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Edit User -->
            <div class="modal fade" id="editModal<?= $u->id; ?>" tabindex="-1" role="dialog">
              <div class="modal-dialog modal-lg" role="document">
                <form method="post" action="<?= base_url('user/update/'.$u->id); ?>">
                  <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-gradient-primary text-white">
                      <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('usr_edit_title', 'Edit User'); ?></h5>
                      <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                      <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle mr-1"></i>
                        Perubahan langsung berlaku.
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_fullname', 'Nama Lengkap'); ?></label>
                        <input type="text" name="fullname" class="form-control" value="<?= $u->fullname; ?>" required>
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_username', 'Username'); ?></label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u->username); ?>" required>
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_email', 'Email'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?= $u->email; ?>">
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_department', 'Departemen'); ?></label>
                        <select name="departemen" class="form-control">
                          <option value=""><?= lang_line('usr_select_department', '-- Pilih Departemen --'); ?></option>
                          <?php foreach($departments as $dept): ?>
                          <option value="<?= $dept->code; ?>" <?= ($u->departemen == $dept->code) ? 'selected' : ''; ?>>
                            <?= $dept->description; ?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_join_date', 'Tanggal Masuk'); ?></label>
                        <input type="date" name="tanggal_masuk" class="form-control" value="<?= $u->tanggal_masuk ?? ''; ?>">
                      </div>
                      <div class="form-group">
                        <label>Role / Posisi <span class="text-danger">*</span></label>
                        <?php $this->load->view('user/_role_picker', ['roles' => $roles, 'selected' => $user_roles_map[(int) $u->id] ?? [], 'picker_id' => 'rpEdit' . $u->id]); ?>
                        <small class="form-text text-muted">Boleh lebih dari satu. Hak akses user = gabungan semua role yang dipilih.</small>
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_type', 'Tipe User'); ?></label>
                        <?php $type_options = $USER_TYPE_SELECTABLE; ?>
                        <select name="user_type" class="form-control">
                          <?php foreach ($type_options as $tval => $tlabel): ?>
                            <option value="<?= $tval; ?>" <?= $u->user_type == $tval ? 'selected' : ''; ?>><?= $tlabel; ?></option>
                          <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted"><?= lang_line('usr_type_hint_edit', 'Perubahan data langsung berlaku.'); ?></small>
                      </div>
                      <div class="form-group">
                        <label><?= lang_line('usr_label_status', 'Status'); ?></label>
                        <select name="status" class="form-control">
                          <option value="active" <?= $u->status=='active'?'selected':''; ?>><?= lang_line('usr_status_active', 'Active'); ?></option>
                          <option value="inactive" <?= $u->status=='inactive'?'selected':''; ?>><?= lang_line('usr_status_inactive', 'Inactive'); ?></option>
                        </select>
                      </div>

                      <hr>
                      <h6 class="mb-3"><i class="fas fa-signature mr-2"></i><?= lang_line('usr_section_signature', 'Tanda Tangan Digital'); ?></h6>

                      <?php if($u->signature_path && file_exists(FCPATH . $u->signature_path)): ?>
                      <div class="form-group">
                        <label><?= lang_line('usr_current_signature', 'Tanda Tangan Saat Ini:'); ?></label><br>
                        <img src="<?= base_url($u->signature_path); ?>" alt="Signature" style="max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                        <div class="mt-2">
                          <button type="button" class="btn btn-sm btn-danger" onclick="deleteSignature(<?= $u->id; ?>)">
                            <i class="fas fa-trash mr-1"></i><?= lang_line('usr_btn_delete_signature', 'Hapus Tanda Tangan'); ?>
                          </button>
                        </div>
                      </div>
                      <?php else: ?>
                      <div class="alert alert-info"><?= lang_line('usr_no_signature', 'Belum ada tanda tangan digital'); ?></div>
                      <?php endif; ?>

                      <div class="form-group">
                        <label><?= lang_line('usr_label_upload_sig', 'Upload Tanda Tangan (Gambar)'); ?></label>
                        <input type="file" id="signatureInput<?= $u->id; ?>" accept="image/*" class="form-control-file" onchange="uploadSignature(<?= $u->id; ?>)">
                        <small class="form-text text-muted"><?= lang_line('usr_upload_hint', 'Format: JPG, PNG, GIF, WebP | Ukuran max: 2MB. <strong>Akan otomatis terupload saat file dipilih.</strong>'); ?></small>
                        <div id="signatureStatus<?= $u->id; ?>" class="mt-2"></div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-success btn-load">
                        <i class="fas fa-save mr-1"></i> <?= lang_line('usr_btn_update', 'Update'); ?>
                      </button>
                      <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
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
  <div class="modal-dialog modal-lg">
    <form method="post" action="<?= base_url('user/add'); ?>">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i><?= lang_line('usr_add_title', 'Tambah User Baru'); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <?= lang_line('usr_default_password', '<strong>Password Default:</strong> ACRO123<br><small>User wajib mengubah password saat login pertama kali.</small>'); ?>
          </div>

          <div class="form-group">
            <label><?= lang_line('usr_label_fullname', 'Nama Lengkap'); ?></label>
            <input type="text" name="fullname" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= lang_line('usr_label_username', 'Username'); ?></label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= lang_line('usr_label_email', 'Email'); ?></label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="form-group">
            <label><?= lang_line('usr_label_department', 'Departemen'); ?></label>
            <select name="departemen" class="form-control">
              <option value=""><?= lang_line('usr_select_department', '-- Pilih Departemen --'); ?></option>
              <?php foreach($departments as $dept): ?>
              <option value="<?= $dept->code; ?>"><?= $dept->description; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label><?= lang_line('usr_label_join_date', 'Tanggal Masuk'); ?></label>
            <input type="date" name="tanggal_masuk" class="form-control">
          </div>
          <div class="form-group">
            <label>Role / Posisi <span class="text-danger">*</span></label>
            <?php $this->load->view('user/_role_picker', ['roles' => $roles, 'selected' => [], 'picker_id' => 'rpAdd']); ?>
            <small class="form-text text-muted">Boleh lebih dari satu. Hak akses user = gabungan semua role yang dipilih.</small>
          </div>
          <div class="form-group">
            <label><?= lang_line('usr_label_type', 'Tipe User'); ?></label>
            <select name="user_type" class="form-control">
              <?php foreach ($USER_TYPE_SELECTABLE as $tval => $tlabel): ?>
                <option value="<?= $tval; ?>" <?= $tval === 'user' ? 'selected' : ''; ?>><?= $tlabel; ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted"><?= lang_line('usr_type_hint_add', 'User baru langsung aktif dan bisa login.'); ?></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-load">
            <i class="fas fa-save mr-1"></i> <?= lang_line('btn_save', 'Simpan'); ?>
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel', 'Batal'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Role picker (Role Level 2026-09-15)
  $(document).on('input', '.role-picker-search', function () {
    const q = this.value.toLowerCase().trim();
    $(this).closest('.role-picker').find('.role-picker-group').each(function () {
      const group = $(this).children('div').first().text().toLowerCase();
      let shown = 0;
      $(this).find('.role-picker-item').each(function () {
        const hit = !q || group.includes(q) || $(this).text().toLowerCase().includes(q);
        $(this).toggle(hit);
        if (hit) shown++;
      });
      $(this).toggle(shown > 0);
    });
  });
  $(document).on('change', '.role-picker input[type=checkbox]', function () {
    const $p = $(this).closest('.role-picker');
    $p.find('.role-picker-count').text($p.find('input[type=checkbox]:checked').length);
    $p.removeClass('border-danger');
  });
  $(document).on('submit', 'form', function (e) {
    const $p = $(this).find('.role-picker');
    if ($p.length && !$p.find('input[type=checkbox]:checked').length) {
      e.preventDefault();
      e.stopImmediatePropagation();
      $p.addClass('border-danger');
      Swal.fire({ icon: 'warning', title: 'Role belum dipilih', text: 'Pilih minimal satu role untuk user ini.' });
      $(this).data('submitted', false).find('button[type="submit"]').prop('disabled', false).removeClass('disabled');
      if (typeof hideLoader === 'function') hideLoader();
    }
  });
});

const I18N_USR = {
  uploading:       <?= json_encode(lang_line('usr_js_uploading', 'Mengupload tanda tangan...')); ?>,
  server_error:    <?= json_encode(lang_line('usr_js_server_error', 'Server error.')); ?>,
  response_label:  <?= json_encode(lang_line('usr_js_response_label', 'Response:')); ?>,
  fail_label:      <?= json_encode(lang_line('usr_js_fail_label', 'Gagal:')); ?>,
  unknown_error:   <?= json_encode(lang_line('usr_js_unknown_error', 'Unknown error')); ?>,
  network_error:   <?= json_encode(lang_line('usr_js_network_error', 'Network error.')); ?>,
  network_hint:    <?= json_encode(lang_line('usr_js_network_hint', 'Periksa koneksi.')); ?>,
  swal_del_title:  <?= json_encode(lang_line('usr_js_swal_del_sig_title', 'Hapus Tanda Tangan?')); ?>,
  swal_del_text:   <?= json_encode(lang_line('usr_js_swal_del_sig_text', 'Apakah Anda yakin ingin menghapus tanda tangan digital ini?')); ?>,
  swal_btn_delete: <?= json_encode(lang_line('usr_js_swal_btn_delete', 'Hapus')); ?>,
  swal_btn_cancel: <?= json_encode(lang_line('btn_cancel', 'Batal')); ?>,
  success_title:   <?= json_encode(lang_line('usr_flash_success_title', 'Berhasil!')); ?>,
  fail_title:      <?= json_encode(lang_line('usr_flash_error_title', 'Gagal!')); ?>,
  error_resp:      <?= json_encode(lang_line('usr_js_swal_error_resp', 'Response tidak valid:')); ?>,
  error_label:     <?= json_encode(lang_line('usr_js_swal_error_label', 'Error')); ?>,
  del_failed:      <?= json_encode(lang_line('usr_js_swal_del_failed', 'Terjadi kesalahan saat menghapus')); ?>,
  net_err_title:   <?= json_encode(lang_line('usr_js_swal_net_err_title', 'Network Error')); ?>,
  net_err_text:    <?= json_encode(lang_line('usr_js_swal_net_err_text', 'Terjadi kesalahan koneksi')); ?>
};

function uploadSignature(userId) {
  var fileInput = document.getElementById('signatureInput' + userId);
  var statusEl = document.getElementById('signatureStatus' + userId);
  var file = fileInput.files[0];
  if (!file) return;

  // Show inline progress
  statusEl.innerHTML = '<div class="alert alert-info mb-0 py-2"><i class="fas fa-spinner fa-spin mr-1"></i> ' + I18N_USR.uploading + '</div>';

  var formData = new FormData();
  formData.append('signature', file);

  var xhr = new XMLHttpRequest();
  xhr.open('POST', '<?= base_url('user/upload_signature/'); ?>' + userId, true);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

  xhr.onload = function() {
    console.log('[uploadSignature] status=' + xhr.status + ' response=' + xhr.responseText.substring(0, 500));
    var result;
    try {
      result = JSON.parse(xhr.responseText);
    } catch (e) {
      statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><strong>' + I18N_USR.server_error + '</strong><br><small>' + I18N_USR.response_label + ' ' + xhr.responseText.substring(0, 200) + '</small></div>';
      return;
    }
    if (xhr.status === 200 && result.success) {
      statusEl.innerHTML = '<div class="alert alert-success mb-0 py-2"><i class="fas fa-check-circle mr-1"></i> ' + result.message + '<br><img src="<?= base_url(); ?>' + result.path + '?t=' + Date.now() + '" style="max-height: 60px; margin-top: 5px; border: 1px solid #ddd; padding: 4px;"></div>';
      // Reload after 1.5s so the modal shows the saved signature in main area too
      setTimeout(function() { location.reload(); }, 1500);
    } else {
      statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><strong>' + I18N_USR.fail_label + '</strong> ' + (result.message || I18N_USR.unknown_error) + '</div>';
    }
  };

  xhr.onerror = function() {
    statusEl.innerHTML = '<div class="alert alert-danger mb-0 py-2"><strong>' + I18N_USR.network_error + '</strong> ' + I18N_USR.network_hint + '</div>';
  };

  xhr.send(formData);
}

function deleteSignature(userId) {
  Swal.fire({
    title: I18N_USR.swal_del_title,
    text: I18N_USR.swal_del_text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: I18N_USR.swal_btn_delete,
    cancelButtonText: I18N_USR.swal_btn_cancel
  }).then((result) => {
    if (result.isConfirmed) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '<?= base_url('user/delete_signature/'); ?>' + userId, true);

      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var result = JSON.parse(xhr.responseText);
            if (result.success) {
              Swal.fire({
                icon: 'success',
                title: I18N_USR.success_title,
                text: result.message,
                timer: 2000,
                showConfirmButton: false
              });
              setTimeout(function() {
                location.reload();
              }, 2000);
            } else {
              Swal.fire({
                icon: 'error',
                title: I18N_USR.fail_title,
                text: result.message
              });
            }
          } catch (e) {
            Swal.fire({
              icon: 'error',
              title: I18N_USR.error_label + '!',
              text: I18N_USR.error_resp + ' ' + xhr.responseText
            });
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: I18N_USR.error_label + ' ' + xhr.status,
            text: I18N_USR.del_failed
          });
        }
      };

      xhr.onerror = function() {
        Swal.fire({
          icon: 'error',
          title: I18N_USR.net_err_title,
          text: I18N_USR.net_err_text
        });
      };

      xhr.send();
    }
  });
}
</script>

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
