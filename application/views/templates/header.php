<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="IT Inventory Kawasan Berikat">
  <meta name="author" content="<?= htmlspecialchars(company_info('app_name')) ?>">
  
  <!-- Favicon - Multiple sizes for better display -->
  <?php /*
      KELIMA baris ikon di bawah WAJIB menunjuk berkas yang sama. Dua baris
      rel="icon" sizes="..." ini yang paling dipercaya Chrome/Firefox --
      kalau cuma shortcut/apple-touch yang diganti, favicon di tab TIDAK
      ikut berubah dan slot Developer Mode terlihat tidak berfungsi.
      Nilai bawaannya HARUS sama dgn 'favicon' di Developer_mode::ASSET_SLOTS,
      kalau tidak pratinjau di halaman pengaturan menampilkan gambar lain
      daripada yang benar-benar dipakai. (fav.png, logo_mini.png &
      logo_full_biru.png kebetulan berkas identik -- md5 sama.)
  */ ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url(site_asset('favicon', 'assets/img/logo_mini.png')); ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url(site_asset('favicon', 'assets/img/logo_mini.png')); ?>">
  <link rel="shortcut icon" href="<?= base_url(site_asset('favicon', 'assets/img/logo_mini.png')); ?>">
  <!-- Apple Touch Icon (untuk iOS, tampil bulat sempurna) -->
  <link rel="apple-touch-icon" href="<?= base_url(site_asset('favicon', 'assets/img/logo_mini.png')); ?>">
  <!-- Mask Icon untuk Safari (bisa set warna) -->
  <link rel="mask-icon" href="<?= base_url(site_asset('favicon', 'assets/img/logo_mini.png')); ?>" color="#334155">
  
  <?php $app_name = company_info('app_name'); ?>
  <title><?= htmlspecialchars(isset($title) ? $title . ' | ' . $app_name : $app_name); ?></title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet" type="text/css">

  <!-- SB Admin 2 -->
  <link href="<?= base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">

  <!-- DataTables -->
  <link href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap4.min.css'); ?>" rel="stylesheet">

  <!-- Select2 -->
  <link href="<?= base_url('assets/vendor/select2/css/select2.min.css'); ?>" rel="stylesheet" />

  <!-- Custom Styles -->
  <link href="<?= base_url('assets/css/style.css'); ?>" rel="stylesheet">

  <!-- Dark Mode Styles (Global) -->
  <link href="<?= base_url('assets/css/dark-mode.css'); ?>" rel="stylesheet">

  <style>
    /* ============================================================
       WARNA TEMA -- SATU-SATUNYA tempat warna brand didefinisikan.
       Rebrand Acropoly 2026-11-05: dari merah NSI (var(--brand-1)) ke abu-abu
       charcoal. Kontras teks putih di --brand-1 = 10.4:1 (WCAG AAA),
       lebih jelas dari merah lama (5.6:1).
       Mau ganti tema (mis. hijau tua)? Cukup ubah nilai di blok ini, plus
       blok :root yang sama di views/auth/login.php (halaman login tidak
       memuat header.php ini). Contoh hijau tua:
         #1b5e20 / #2e7d32 / #0d3d12 / #e8f5e9 / 27, 94, 32
       Merah tetap dipakai utk arti BAHAYA (btn-danger, alert, tolak).
       ============================================================ */
    :root {
      --brand-1: #334155;        /* warna utama: topbar, tombol, header kartu */
      --brand-2: #475569;        /* ujung gradien / versi lebih terang */
      --brand-dark: #1e293b;     /* hover / versi lebih gelap */
      --brand-soft: #e2e8f0;     /* latar lembut (hover/menu aktif, badge) */
      --brand-rgb: 51, 65, 85;   /* = --brand-1, utk rgba(var(--brand-rgb), .x) */
      --brand-dm-1: #111827;     /* dark mode: topbar */
      --brand-dm-2: #1f2937;
      --brand-dm-accent: #94a3b8;/* dark mode: garis aksen */
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f7f8fa;
      color: #333;
      overflow-x: hidden;
    }

    /* Sidebar gradient (overridden by sidebar.php light theme — kept as fallback) */
    .navbar-nav.bg-gradient-primary {
      background: linear-gradient(180deg, var(--brand-1) 0%, var(--brand-dark) 100%);
    }

    /* Button styling */
    .btn-primary {
      background-color: var(--brand-1);
      border: none;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background-color: var(--brand-dark);
    }
    .btn-danger {
      background-color: #e63946;
      border: none;
    }
    .btn-danger:hover {
      background-color: #b71c1c;
    }

    /* Card and layout tweaks */
    .card {
      border: none;
      border-radius: 0.75rem;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    .table th, .table td {
      vertical-align: middle !important;
    }
    .modal-content {
      border-radius: 1rem;
      overflow: hidden;
    }

    /* Loader animation base */
    #loader-wrapper {
      display: none;
      position: fixed;
      z-index: 9999;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(255,255,255,0.95);
      /* backdrop-filter dihapus (user 2026-09-21: "buat loading nya metode
         nya yang ringan") — blur seluruh layar dihitung ulang tiap frame
         animasi spinner, dan latarnya sudah nyaris pekat jadi efeknya
         hampir tidak terlihat. */
      text-align: center;
    }
    .loader {
      position: absolute;
      top: 50%; left: 50%;
      width: 70px; height: 70px;
      transform: translate(-50%, -50%);
      border: 6px solid #e0e0e0;
      border-top: 6px solid var(--brand-1);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    .loader::before {
      content: "";
      position: absolute;
      top: 8px; left: 8px; right: 8px; bottom: 8px;
      border-radius: 50%;
      border: 5px solid transparent;
      border-top: 5px solid var(--brand-dark);
      animation: spin 1.5s linear infinite reverse;
    }
    @keyframes spin { 
      0% { transform: rotate(0deg); } 
      100% { transform: rotate(360deg); } 
    }

    #loader-text {
      position: absolute;
      top: calc(50% + 80px);
      left: 50%;
      transform: translateX(-50%);
      font-weight: 500;
      color: var(--brand-1);
      font-size: 14px;
      letter-spacing: 0.3px;
    }
  </style>

  <!-- jQuery -->
  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body id="page-top">
<div id="wrapper">

<!-- ==================== Global Loader ==================== -->
<div id="loader-wrapper">
  <div class="loader"></div>
  <div id="loader-text">Memuat halaman, harap tunggu...</div>
</div>

<script>
$(document).ready(function() {
  const showLoader = () => $("#loader-wrapper").fadeIn(200);
  const hideLoader = () => $("#loader-wrapper").fadeOut(300);

  // Loader saat pertama kali halaman dimuat
  $(window).on('load', function() {
    hideLoader();
  });

  // Loader saat klik link navigasi (BUKAN dropdown toggle)
  $(document).on('click', 'a.btn-load, a.page-link, a.collapse-item', function(e){
    const href = $(this).attr('href');
    // Hanya tampilkan loader jika benar-benar navigasi ke halaman lain
    if (href && href !== '#' && !href.startsWith('javascript:') && !$(this).attr('target') && !$(this).attr('data-toggle')) {
      showLoader();
    }
  });

  // JANGAN trigger loader untuk menu dropdown sidebar
  // Menu dengan data-toggle="collapse" adalah toggle dropdown, bukan navigasi

  // Jika ada form submit manual, tampilkan loader juga (kecuali form.no-loader).
  // Submit yang DICEGAH (dialog konfirmasi dulu, atau dikirim lewat AJAX)
  // tidak menampilkan loader — dulu overlay menutupi dialog konfirmasinya
  // sampai batas waktu (user 2026-09-21: "sangat lama loading nya").
  $(document).on('submit', 'form:not(.no-loader)', function(e) {
    if (e.isDefaultPrevented()) return;
    showLoader();
  });

  // Sembunyikan loader jika AJAX selesai (opsional, untuk AJAX requests)
  $(document).ajaxStop(function() {
    hideLoader();
  });

  // ===== DARK MODE GLOBAL =====
  (function() {
    // Check localStorage for dark mode preference
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (isDarkMode || (!localStorage.getItem('darkMode') && prefersDark)) {
      document.body.classList.add('dark-mode');
    }

    // Function to update dark mode UI
    function updateDarkModeUI() {
      const isDark = document.body.classList.contains('dark-mode');
      const toggleBtn = document.getElementById('dark-mode-toggle-topbar');
      const label = document.getElementById('dark-mode-label');
      
      if (toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        if (icon) {
          icon.className = isDark ? 'fas fa-sun fa-fw text-warning' : 'fas fa-moon fa-fw text-info';
        }
      }
      
      if (label) {
        label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
      }
    }

    // Initial UI update
    updateDarkModeUI();

    // Get topbar toggle button and add event listener
    const topbarToggle = document.getElementById('dark-mode-toggle-topbar');
    if (topbarToggle) {
      topbarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        // Update UI
        updateDarkModeUI();
      });
    }

    // Listen to system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem('darkMode')) {
        if (e.matches) {
          document.body.classList.add('dark-mode');
        } else {
          document.body.classList.remove('dark-mode');
        }
        updateDarkModeUI();
      }
    });
  })();
});
</script>
