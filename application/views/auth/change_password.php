<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= lang_line('auth_cp_page_label', 'Ubah Password'); ?> - <?= htmlspecialchars(company_info('app_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
<link href="<?= base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

body::before {
  content: '';
  position: absolute;
  width: 500px;
  height: 500px;
  background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
  border-radius: 50%;
  top: -250px;
  right: -250px;
  animation: float 6s ease-in-out infinite;
}

body::after {
  content: '';
  position: absolute;
  width: 400px;
  height: 400px;
  background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
  border-radius: 50%;
  bottom: -200px;
  left: -200px;
  animation: float 8s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
}

.change-password-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 500px;
  padding: 20px;
}

.change-password-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  padding: 50px 40px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.6s ease-out;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.change-password-header {
  text-align: center;
  margin-bottom: 30px;
}

.change-password-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 15px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.change-password-header p {
  color: #718096;
  font-size: 15px;
  line-height: 1.6;
}

.alert-info {
  background-color: #e0f7ff;
  border: 1px solid #80deea;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 25px;
  color: #01579b;
  font-size: 14px;
}

.alert-info i {
  margin-right: 8px;
}

.form-group {
  margin-bottom: 25px;
  position: relative;
}

.input-group-custom {
  position: relative;
}

.input-group-custom i {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 16px;
  z-index: 1;
  transition: color 0.3s ease;
}

.form-control-custom {
  width: 100%;
  padding: 16px 20px 16px 50px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 400;
  color: #2d3748;
  background: #f7fafc;
  transition: all 0.3s ease;
  outline: none;
}

.form-control-custom:focus {
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-control-custom:focus ~ i {
  color: #667eea;
}

.form-control-custom::placeholder {
  color: #a0aec0;
}

.password-requirements {
  font-size: 13px;
  color: #718096;
  margin-top: 8px;
  padding: 10px;
  background: #f7fafc;
  border-radius: 8px;
  border-left: 3px solid #667eea;
}

.btn-change-password {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
  position: relative;
  overflow: hidden;
  margin-top: 10px;
}

.btn-change-password::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.btn-change-password:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
}

.btn-change-password:hover::before {
  left: 100%;
}

.btn-change-password:active {
  transform: translateY(0);
}

.btn-change-password i {
  margin-right: 8px;
}

.form-footer {
  text-align: center;
  margin-top: 25px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 13px;
  font-weight: 300;
}

.system-info {
  text-align: center;
  margin-top: 30px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 13px;
  font-weight: 300;
}

.btn-change-password.loading {
  pointer-events: none;
  opacity: 0.7;
}

.btn-change-password.loading::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  top: 50%;
  left: 50%;
  margin-left: -10px;
  margin-top: -10px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .change-password-card {
    padding: 40px 25px;
  }

  .change-password-header h1 {
    font-size: 24px;
  }
}
</style>
</head>
<body>

  <div class="change-password-container">
    <div class="change-password-card">
      <div class="change-password-header">
        <h1><i class="fas fa-lock mr-2"></i><?= lang_line('auth_cp_heading'); ?></h1>
        <p><?= lang_line('auth_cp_subtitle'); ?></p>
      </div>

      <div class="alert-info">
        <i class="fas fa-shield-alt"></i>
        <?= lang_line('auth_cp_default_info_pre'); ?> <strong>ACRO123</strong> <?= lang_line('auth_cp_default_info_suf'); ?>
      </div>

      <form method="post" action="<?= base_url('auth/save_password'); ?>" id="changePasswordForm">
        <div class="form-group">
          <div class="input-group-custom">
            <input type="password" name="new_password" class="form-control-custom" placeholder="<?= lang_line('auth_cp_ph_new'); ?>" required id="newPassword">
            <i class="fas fa-lock"></i>
            <span style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; z-index: 1;" onclick="togglePassword('newPassword')">
              <i class="fas fa-eye" id="toggleIcon1"></i>
            </span>
          </div>
          <div class="password-requirements">
            <i class="fas fa-check-circle mr-1" id="check1" style="color: #ccc;"></i> <?= lang_line('auth_cp_min_chars'); ?>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group-custom">
            <input type="password" name="confirm_password" class="form-control-custom" placeholder="<?= lang_line('auth_cp_ph_confirm'); ?>" required id="confirmPassword">
            <i class="fas fa-lock"></i>
            <span style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; z-index: 1;" onclick="togglePassword('confirmPassword')">
              <i class="fas fa-eye" id="toggleIcon2"></i>
            </span>
          </div>
          <div class="password-requirements">
            <i class="fas fa-check-circle mr-1" id="check2" style="color: #ccc;"></i> <?= lang_line('auth_cp_must_match'); ?>
          </div>
        </div>

        <button type="submit" class="btn-change-password" id="submitBtn">
          <i class="fas fa-save"></i> <?= lang_line('auth_cp_btn_save'); ?>
        </button>
      </form>

      <div class="form-footer">
        <small><i class="fas fa-shield-alt"></i> <?= lang_line('auth_cp_footer_safe'); ?></small>
      </div>
    </div>

    <div class="system-info">
      <i class="fas fa-copyright"></i> <?= date('Y'); ?> <?= htmlspecialchars(company_info('company_name')) ?>. <?= lang_line('login_rights_reserved', 'All rights reserved.') ?>
    </div>
  </div>

<script>
const I18N_AUTH_CP = {
  short_title: <?= json_encode(lang_line('auth_cp_swal_short_title'), JSON_UNESCAPED_UNICODE); ?>,
  short_text:  <?= json_encode(lang_line('auth_cp_swal_short_text'), JSON_UNESCAPED_UNICODE); ?>,
  mis_title:   <?= json_encode(lang_line('auth_cp_swal_mis_title'), JSON_UNESCAPED_UNICODE); ?>,
  mis_text:    <?= json_encode(lang_line('auth_cp_swal_mis_text'), JSON_UNESCAPED_UNICODE); ?>,
  saving:      <?= json_encode(lang_line('auth_cp_btn_saving'), JSON_UNESCAPED_UNICODE); ?>,
  err_title:   <?= json_encode(lang_line('auth_cp_swal_err_title'), JSON_UNESCAPED_UNICODE); ?>,
  ok_title:    <?= json_encode(lang_line('auth_cp_swal_ok_title'), JSON_UNESCAPED_UNICODE); ?>
};
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const iconId = fieldId === 'newPassword' ? 'toggleIcon1' : 'toggleIcon2';
  const icon = document.getElementById(iconId);

  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

// Validasi real-time
document.getElementById('newPassword').addEventListener('input', function() {
  const check = document.getElementById('check1');
  if (this.value.length >= 6) {
    check.style.color = '#4caf50';
  } else {
    check.style.color = '#ccc';
  }
});

document.getElementById('confirmPassword').addEventListener('input', function() {
  const newPass = document.getElementById('newPassword').value;
  const check = document.getElementById('check2');
  if (this.value === newPass && this.value.length > 0) {
    check.style.color = '#4caf50';
  } else {
    check.style.color = '#ccc';
  }
});

// Loading state on form submit
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
  const newPass = document.getElementById('newPassword').value;
  const confirmPass = document.getElementById('confirmPassword').value;

  if (newPass.length < 6) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_AUTH_CP.short_title,
      text: I18N_AUTH_CP.short_text
    });
    return;
  }

  if (newPass !== confirmPass) {
    e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_AUTH_CP.mis_title,
      text: I18N_AUTH_CP.mis_text
    });
    return;
  }

  const btn = document.getElementById('submitBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<span style="opacity: 0;">' + I18N_AUTH_CP.saving + '</span>';
});
</script>

<?php if($this->session->flashdata('error')): ?>
<script>
Swal.fire({
  icon: 'error',
  title: I18N_AUTH_CP.err_title,
  text: '<?= $this->session->flashdata('error'); ?>',
  confirmButtonColor: '#667eea'
});
</script>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
<script>
Swal.fire({
  icon: 'success',
  title: I18N_AUTH_CP.ok_title,
  text: '<?= $this->session->flashdata('success'); ?>',
  confirmButtonColor: '#667eea',
  timer: 2000,
  timerProgressBar: true
});
</script>
<?php endif; ?>

</body>
</html>
