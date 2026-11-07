<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="IT Inventory Kawasan Berikat">
  <meta name="author" content="ERP NSI">
  <title><?= isset($title) ? $title . ' | Neokaber' : 'Neokaber System'; ?></title>

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

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f7f8fa;
      color: #333;
      overflow-x: hidden;
    }

    /* Sidebar gradient */
    .navbar-nav.bg-gradient-primary {
      background: linear-gradient(180deg, #316ec4ff 0%rgba(53, 94, 119, 1)9d 100%);
    }

    /* Button styling */
    .btn-primary {
      background-color: #457b9d;
      border: none;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #1d3557;
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
      backdrop-filter: blur(2px);
      text-align: center;
    }
    .loader {
      position: absolute;
      top: 50%; left: 50%;
      width: 70px; height: 70px;
      transform: translate(-50%, -50%);
      border: 6px solid #e0e0e0;
      border-top: 6px solid #457b9d;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    .loader::before {
      content: "";
      position: absolute;
      top: 8px; left: 8px; right: 8px; bottom: 8px;
      border-radius: 50%;
      border: 5px solid transparent;
      border-top: 5px solid #1d3557;
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
      color: #1d3557;
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

  // Loader saat klik link atau tombol navigasi
  $(document).on('click', 'a.btn-load, a.nav-link, a.page-link', function(e){
    const href = $(this).attr('href');
    if (href && href !== '#' && !href.startsWith('javascript:') && !$(this).attr('target')) {
      showLoader();
    }
  });

  // Jika ada form submit manual, tampilkan loader juga
  $(document).on('submit', 'form', function(e) {
    showLoader();
  });

  // Jika terjadi AJAX request global
  $(document).ajaxStart(function() {
    showLoader();
  }).ajaxStop(function() {
    hideLoader();
  });
});
</script>
