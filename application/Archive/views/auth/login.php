<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - ERP NSI</title>
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

/* Animated Background */
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

.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 450px;
  padding: 20px;
}

.login-card {
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

.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo-container {
  width: 120px;
  height: 120px;
  background: white;
  border-radius: 20px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
  animation: pulse 2s ease-in-out infinite;
  padding: 12px;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.logo-container img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.login-header p {
  color: #718096;
  font-size: 14px;
  font-weight: 400;
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

.form-control-custom:focus + i {
  color: #667eea;
}

.form-control-custom::placeholder {
  color: #a0aec0;
}

.btn-login {
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
}

.btn-login::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
}

.btn-login:hover::before {
  left: 100%;
}

.btn-login:active {
  transform: translateY(0);
}

.btn-login i {
  margin-right: 8px;
}

.form-footer {
  text-align: center;
  margin-top: 25px;
  padding-top: 25px;
  border-top: 1px solid #e2e8f0;
}

.form-footer a {
  color: #667eea;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.3s ease;
}

.form-footer a:hover {
  color: #764ba2;
  text-decoration: underline;
}

.system-info {
  text-align: center;
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 13px;
  font-weight: 300;
}

/* Loading State */
.btn-login.loading {
  pointer-events: none;
  opacity: 0.7;
}

.btn-login.loading::after {
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

/* Responsive */
@media (max-width: 480px) {
  .login-card {
    padding: 40px 25px;
  }
  
  .login-header h1 {
    font-size: 24px;
  }
}

/* Dark mode toggle hint */
.theme-hint {
  position: absolute;
  top: 20px;
  right: 20px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 24px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.theme-hint:hover {
  transform: rotate(180deg);
}
</style>
</head>
<body>

  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-container">
          <img src="<?= base_url('assets/img/logo_mini.png'); ?>" alt="Logo">
        </div>
        <h1>ERP</h1>
        <p>PT NEW SPECIALIFE INDUSTRIES</p>
      </div>

      <form method="post" action="<?= base_url('auth/login_action'); ?>" id="loginForm">
        <div class="form-group">
          <div class="input-group-custom">
            <input type="text" name="username" class="form-control-custom" placeholder="Username" required autocomplete="username">
            <i class="fas fa-user"></i>
          </div>
        </div>

        <div class="form-group">
          <div class="input-group-custom">
            <input type="password" name="password" class="form-control-custom" placeholder="Password" required autocomplete="current-password" id="passwordInput">
            <i class="fas fa-lock"></i>
            <span style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; z-index: 1;" onclick="togglePassword()">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
          </div>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <i class="fas fa-sign-in-alt"></i> Masuk ke Sistem
        </button>
      </form>

      <div class="form-footer">
        <small style="color: #718096;">
          <i class="fas fa-shield-alt"></i> Sistem dilindungi & terenkripsi
        </small>
      </div>
    </div>

    <div class="system-info">
      <i class="fas fa-copyright"></i> 2025 PT NEW SPECIALIFE INDUSTRIES. All rights reserved.
    </div>
  </div>

<script>
// Toggle password visibility
function togglePassword() {
  const passwordInput = document.getElementById('passwordInput');
  const toggleIcon = document.getElementById('toggleIcon');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    toggleIcon.classList.remove('fa-eye');
    toggleIcon.classList.add('fa-eye-slash');
  } else {
    passwordInput.type = 'password';
    toggleIcon.classList.remove('fa-eye-slash');
    toggleIcon.classList.add('fa-eye');
  }
}

// Loading state on form submit
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<span style="opacity: 0;">Loading...</span>';
});

// Focus animation
document.querySelectorAll('.form-control-custom').forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.style.transform = 'scale(1.02)';
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.style.transform = 'scale(1)';
  });
});
</script>

<?php if($this->session->flashdata('error')): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Login Gagal!',
  text: '<?= $this->session->flashdata('error'); ?>',
  confirmButtonColor: '#667eea',
  background: '#fff',
  backdrop: 'rgba(102, 126, 234, 0.2)'
});
</script>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Berhasil!',
  text: '<?= $this->session->flashdata('success'); ?>',
  confirmButtonColor: '#667eea',
  timer: 2000,
  timerProgressBar: true
});
</script>
<?php endif; ?>

</body>
</html>
