<?php $this->load->helper('language_helper'); $current_lang = get_current_language(); $available_langs = get_available_languages(); ?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'chinese' ? 'zh' : ($current_lang === 'english' ? 'en' : 'id') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= lang_line('login_title', 'Login'); ?> - <?= htmlspecialchars(company_info('app_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
<link href="<?= base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* Warna tema -- harus SAMA dengan blok :root di views/templates/header.php
   (halaman login tidak memuat header.php). Rebrand Acropoly 2026-11-05:
   merah NSI -> abu-abu charcoal. */
:root {
  --brand-1: #334155;
  --brand-2: #475569;
  --brand-dark: #1e293b;
  --brand-soft: #e2e8f0;
  --brand-accent: #64748b;
  --brand-rgb: 51, 65, 85;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-1) 35%, var(--brand-2) 100%);
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

/* Decorative pattern overlay */
body::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 20% 30%, rgba(255,255,255,0.08) 0%, transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(255,255,255,0.08) 0%, transparent 40%),
    radial-gradient(circle at 50% 50%, rgba(255,255,255,0.03) 0%, transparent 60%);
  pointer-events: none;
}

/* Floating shapes */
.shape {
  position: absolute;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04));
  backdrop-filter: blur(20px);
  animation: float 8s ease-in-out infinite;
  z-index: 1;
}
.shape-1 { width: 380px; height: 380px; top: -120px; right: -120px; animation-delay: 0s; }
.shape-2 { width: 280px; height: 280px; bottom: -100px; left: -80px; animation-delay: 2s; }
.shape-3 { width: 160px; height: 160px; top: 60%; right: 8%; animation-delay: 4s; }
.shape-4 { width: 120px; height: 120px; top: 12%; left: 8%; animation-delay: 1s; }

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-25px) rotate(8deg); }
}

.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 980px;
  padding: 20px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  background: rgba(255, 255, 255, 0.98);
  border-radius: 28px;
  overflow: hidden;
  box-shadow: 0 30px 80px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.2);
  animation: slideUp 0.7s cubic-bezier(.22,.61,.36,1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

/* LEFT side: brand panel (minimal) */
.brand-side {
  position: relative;
  background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
  color: #fff;
  padding: 60px 45px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  overflow: hidden;
}
.brand-side::before {
  content: '';
  position: absolute;
  width: 320px; height: 320px;
  border-radius: 50%;
  background: rgba(255,255,255,0.08);
  top: -120px; right: -120px;
}
.brand-side::after {
  content: '';
  position: absolute;
  width: 260px; height: 260px;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
  bottom: -100px; left: -100px;
}

.brand-logo {
  width: 110px;
  height: 110px;
  background: #fff;
  border-radius: 24px;
  padding: 16px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 28px;
}
.brand-logo img { width: 100%; height: 100%; object-fit: contain; }
.brand-logo .logo-fallback {
  font-size: 36px;
  font-weight: 800;
  background: linear-gradient(135deg, var(--brand-1), var(--brand-2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-title {
  position: relative;
  z-index: 2;
}
.brand-title h2 {
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 8px;
  letter-spacing: -0.3px;
}
.brand-title p {
  font-size: 13px;
  font-weight: 400;
  opacity: 0.9;
  letter-spacing: 0.5px;
}

/* RIGHT side: form */
.form-side {
  padding: 55px 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #fff;
}

.login-header {
  margin-bottom: 32px;
}
.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 6px;
  letter-spacing: -0.5px;
}
.login-header p {
  color: #718096;
  font-size: 14px;
  font-weight: 400;
}
.login-header h1 .accent {
  background: linear-gradient(135deg, var(--brand-1), var(--brand-2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.form-group {
  margin-bottom: 20px;
  position: relative;
}

.form-label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 8px;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

.input-group-custom {
  position: relative;
}

.input-group-custom > i.input-icon {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 15px;
  z-index: 1;
  transition: color 0.3s ease;
  pointer-events: none;
}

.form-control-custom {
  width: 100%;
  padding: 14px 20px 14px 48px;
  border: 2px solid #edf2f7;
  border-radius: 12px;
  font-size: 14.5px;
  font-weight: 400;
  color: #2d3748;
  background: #f8fafc;
  transition: all 0.25s ease;
  outline: none;
  font-family: 'Poppins', sans-serif;
}

.form-control-custom:focus {
  border-color: var(--brand-2);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(var(--brand-rgb), 0.12);
}

.form-control-custom:focus ~ i.input-icon {
  color: var(--brand-2);
}

.form-control-custom::placeholder {
  color: #cbd5e0;
}

.toggle-password {
  position: absolute;
  right: 18px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #a0aec0;
  z-index: 2;
  transition: color 0.2s ease;
  user-select: none;
}
.toggle-password:hover { color: var(--brand-2); }

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
  border: none;
  border-radius: 12px;
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.3px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 25px rgba(var(--brand-rgb), 0.32);
  position: relative;
  overflow: hidden;
  margin-top: 8px;
  font-family: 'Poppins', sans-serif;
}

.btn-login::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
  transition: left 0.6s ease;
}
.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 35px rgba(var(--brand-rgb), 0.45);
}
.btn-login:hover::before { left: 100%; }
.btn-login:active { transform: translateY(0); }
.btn-login i { margin-right: 8px; }

.btn-login.loading { pointer-events: none; opacity: 0.7; }
.btn-login.loading::after {
  content: '';
  position: absolute;
  width: 20px; height: 20px;
  top: 50%; left: 50%;
  margin-left: -10px; margin-top: -10px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.form-footer {
  text-align: center;
  margin-top: 26px;
  padding-top: 22px;
  border-top: 1px solid #edf2f7;
}
.form-footer small {
  color: #a0aec0;
  font-size: 12.5px;
}
.form-footer small i { color: var(--brand-2); }

.copyright {
  position: absolute;
  bottom: 20px;
  left: 0; right: 0;
  text-align: center;
  color: rgba(255,255,255,0.85);
  font-size: 12px;
  font-weight: 300;
  z-index: 5;
}
.copyright i { margin-right: 4px; }

/* Language Selector (top-right) */
.lang-selector {
  position: absolute;
  top: 20px;
  right: 22px;
  z-index: 20;
}
.lang-toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  background: rgba(255,255,255,0.18);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255,255,255,0.28);
  border-radius: 999px;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
  font-family: 'Poppins', sans-serif;
}
.lang-toggle:hover {
  background: rgba(255,255,255,0.28);
  transform: translateY(-1px);
}
.lang-toggle .flag { font-size: 16px; line-height: 1; }
.lang-toggle .chev { font-size: 10px; opacity: 0.85; margin-left: 2px; transition: transform 0.2s ease; }
.lang-selector.open .lang-toggle .chev { transform: rotate(180deg); }

.lang-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 18px 50px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.04);
  overflow: hidden;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-6px);
  transition: all 0.22s ease;
}
.lang-selector.open .lang-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.lang-menu-header {
  padding: 10px 16px;
  background: linear-gradient(135deg, var(--brand-1), var(--brand-2));
  color: #fff;
  font-size: 11.5px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.lang-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
  color: #2d3748;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s ease;
  border-bottom: 1px solid #f1f5f9;
}
.lang-menu a:last-child { border-bottom: 0; }
.lang-menu a:hover { background: var(--brand-soft); color: var(--brand-1); }
.lang-menu a.active { background: var(--brand-soft); color: var(--brand-1); font-weight: 600; }
.lang-menu a .flag { font-size: 18px; line-height: 1; }
.lang-menu a .check { margin-left: auto; color: var(--brand-1); font-size: 13px; }

/* Responsive */
@media (max-width: 860px) {
  .login-container {
    grid-template-columns: 1fr;
    max-width: 460px;
  }
  .brand-side {
    padding: 36px 28px;
  }
  .brand-logo { width: 84px; height: 84px; margin-bottom: 18px; }
  .brand-title h2 { font-size: 22px; }
  .form-side { padding: 40px 35px; }
}

@media (max-width: 480px) {
  .login-container { border-radius: 20px; }
  .brand-side { padding: 32px 26px; }
  .form-side { padding: 32px 26px; }
  .login-header h1 { font-size: 24px; }
  .copyright { font-size: 11px; padding: 0 14px; }
}
</style>
</head>
<body>

  <div class="shape shape-1"></div>
  <div class="shape shape-2"></div>
  <div class="shape shape-3"></div>
  <div class="shape shape-4"></div>

  <!-- Language Selector -->
  <div class="lang-selector" id="langSelector">
    <button type="button" class="lang-toggle" onclick="toggleLangMenu(event)">
      <span class="flag"><?= $available_langs[$current_lang]['flag'] ?? '🌐' ?></span>
      <span class="d-none d-sm-inline"><?= $available_langs[$current_lang]['name'] ?? 'Language' ?></span>
      <i class="fas fa-chevron-down chev"></i>
    </button>
    <div class="lang-menu">
      <div class="lang-menu-header">
        <i class="fas fa-language mr-1"></i> <?= lang_line('login_language_label', 'Bahasa') ?>
      </div>
      <?php foreach ($available_langs as $lang): ?>
        <a href="<?= current_url() . '?lang=' . $lang['code'] ?>" class="<?= $current_lang === $lang['code'] ? 'active' : '' ?>">
          <span class="flag"><?= $lang['flag'] ?></span>
          <span><?= $lang['name'] ?></span>
          <?php if ($current_lang === $lang['code']): ?>
            <i class="fas fa-check-circle check"></i>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="login-container">

    <!-- LEFT: BRAND PANEL (minimal) -->
    <div class="brand-side">
      <div class="brand-logo" id="logoContainer">
        <!-- Logo dimuat via JS -->
      </div>
      <div class="brand-title">
        <h2><?= htmlspecialchars(company_info('app_name')) ?></h2>
        <p><?= htmlspecialchars(company_info('company_name')) ?></p>
      </div>
    </div>

    <!-- RIGHT: FORM -->
    <div class="form-side">
      <div class="login-header">
        <h1><?= lang_line('login_heading_1', 'Masuk ke') ?> <span class="accent"><?= lang_line('login_heading_2', 'Akun Anda') ?></span></h1>
        <p><?= lang_line('login_subtitle', 'Gunakan kredensial yang telah diberikan untuk melanjutkan.') ?></p>
      </div>

      <form method="post" action="<?= base_url('auth/login_action'); ?>" id="loginForm">
        <div class="form-group">
          <label class="form-label" for="usernameInput"><?= lang_line('login_label_username', 'Username') ?></label>
          <div class="input-group-custom">
            <input type="text" id="usernameInput" name="username" class="form-control-custom" placeholder="<?= lang_line('login_placeholder_username', 'Masukkan username') ?>" required autocomplete="username">
            <i class="fas fa-user input-icon"></i>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="passwordInput"><?= lang_line('login_label_password', 'Password') ?></label>
          <div class="input-group-custom">
            <input type="password" id="passwordInput" name="password" class="form-control-custom" placeholder="<?= lang_line('login_placeholder_password', 'Masukkan password') ?>" required autocomplete="current-password">
            <i class="fas fa-lock input-icon"></i>
            <span class="toggle-password" onclick="togglePassword()">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
          </div>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <i class="fas fa-sign-in-alt"></i> <span class="btn-text"><?= lang_line('login_button', 'Masuk ke Sistem') ?></span>
        </button>
      </form>

      <div class="form-footer">
        <small>
          <i class="fas fa-shield-alt"></i> <?= lang_line('login_footer_secure', 'Sistem dilindungi & terenkripsi') ?>
        </small>
      </div>
    </div>

  </div>

  <div class="copyright">
    <?php /*
        Nama perusahaan diambil dari Developer Mode, bukan ditulis di dalam
        kalimat hak cipta -- kalau ditulis di situ, ganti nama perusahaan
        tidak akan mengubah baris ini. Tahunnya ikut tahun berjalan.
    */ ?>
    <i class="fas fa-copyright"></i> <?= date('Y'); ?> <?= htmlspecialchars(company_info('company_name')) ?>. <?= lang_line('login_rights_reserved', 'All rights reserved.') ?>
  </div>

<script>
// Load logo dengan error handling
document.addEventListener('DOMContentLoaded', function() {
  const logoContainer = document.getElementById('logoContainer');
  const baseUrl = '<?= base_url(); ?>';

  // Logo pertama = slot "Logo Halaman Login" di Developer Mode. Sisanya tetap
  // jadi cadangan berantai seperti dulu, jadi kalau berkas unggahan hilang
  // logonya tidak langsung jatuh ke tulisan inisial "AP".
  const logoUrls = [
    baseUrl + <?= json_encode(site_asset('logo_login', 'assets/img/logo_mini.png')); ?>,
    baseUrl + 'assets/img/logo_mini.png',
    baseUrl + 'assets/img/logo_full.png',
    baseUrl + 'assets/img/logo_full_biru.png'
  ];

  let currentIndex = 0;

  function tryLoadLogo() {
    if (currentIndex >= logoUrls.length) {
      logoContainer.innerHTML = '<div class="logo-fallback">AP</div>';
      return;
    }
    const img = new Image();
    img.onload = function() {
      logoContainer.innerHTML = '';
      logoContainer.appendChild(img);
    };
    img.onerror = function() {
      currentIndex++;
      tryLoadLogo();
    };
    img.src = logoUrls[currentIndex];
    img.alt = <?= json_encode(company_info('company_name')); ?>;
  }
  tryLoadLogo();
});

// Toggle password
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

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<span style="opacity: 0;"><?= lang_line('login_button_loading', 'Memproses...') ?></span>';
});

// Language selector toggle
function toggleLangMenu(e) {
  e.stopPropagation();
  document.getElementById('langSelector').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const sel = document.getElementById('langSelector');
  if (sel && !sel.contains(e.target)) sel.classList.remove('open');
});
</script>

<?php if($this->session->flashdata('error')): ?>
<script>
Swal.fire({
  icon: 'error',
  title: '<?= lang_line('login_failed_title', 'Login Gagal!') ?>',
  text: '<?= $this->session->flashdata('error'); ?>',
  confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155',
  background: '#fff',
  backdrop: 'rgba(' + (getComputedStyle(document.documentElement).getPropertyValue('--brand-rgb').trim() || '51, 65, 85') + ', 0.25)'
});
</script>
<?php endif; ?>

<?php if($this->session->flashdata('success')): ?>
<script>
Swal.fire({
  icon: 'success',
  title: '<?= lang_line('login_success_title', 'Berhasil!') ?>',
  text: '<?= $this->session->flashdata('success'); ?>',
  confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155',
  timer: 2000,
  timerProgressBar: true
});
</script>
<?php endif; ?>

<?php if($this->session->flashdata('force_logout')): ?>
<script>
Swal.fire({
  icon: 'warning',
  title: '<?= lang_line('login_session_ended_title', 'Sesi Berakhir') ?>',
  text: '<?= lang_line('login_session_ended_text', 'Akun Anda telah login dari perangkat lain. Anda telah dikeluarkan secara otomatis.') ?>',
  confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-1').trim() || '#334155',
  background: '#fff',
  backdrop: 'rgba(' + (getComputedStyle(document.documentElement).getPropertyValue('--brand-rgb').trim() || '51, 65, 85') + ', 0.25)'
});
</script>
<?php endif; ?>

</body>
</html>
