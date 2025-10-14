<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $title; ?> - NeoKABER</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <link href="<?php echo base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/custom.css'); ?>" rel="stylesheet">
</head>

<body id="page-top">
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <div id="wrapper">

        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo base_url(); ?>">
                <div class="sidebar-brand-full">
                    <img src="<?php echo base_url('assets/img/logo_full.png'); ?>" alt="NeoKABER Full Logo" style="height: 40px;">
                </div>
                <div class="sidebar-brand-compact">
                    <img src="<?php echo base_url('assets/img/logo_mini.png'); ?>" alt="NeoKABER Mini Logo" style="height: 40px;">
                </div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="<?php echo base_url('dashboard'); ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>
            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Transaksi
            </div>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('goods_receipt'); ?>">
                    <i class="fas fa-fw fa-arrow-down"></i>
                    <span>Penerimaan Barang</span></a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Master Data
            </div>
            
            <?php if ($this->session->userdata('role_id') == 1 || in_array('material', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('material'); ?>">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Master Material</span></a>
            </li>
            <?php endif; ?>

            <?php if ($this->session->userdata('role_id') == 1 || in_array('asset', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('asset'); ?>">
                    <i class="fas fa-fw fa-cogs"></i>
                    <span>Master Aset</span></a>
            </li>
            <?php endif; ?>

            <?php if ($this->session->userdata('role_id') == 1 || in_array('supplier', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('supplier'); ?>">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Supplier</span></a>
            </li>
            <?php endif; ?>

            <?php if ($this->session->userdata('role_id') == 1 || in_array('customer', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('customer'); ?>">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Pelanggan</span></a>
            </li>
            <?php endif; ?>

            <?php if ($this->session->userdata('role_id') == 1 || in_array('product', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('product'); ?>">
                    <i class="fas fa-fw fa-box-open"></i>
                    <span>Barang Jadi / SFG</span></a>
            </li>
            <?php endif; ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Pengaturan
            </div>
            <?php if ($this->session->userdata('role_id') == 1 || in_array('common_code', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('common_code'); ?>">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Common Codes</span></a>
            </li>
            <?php endif; ?>
            <?php if ($this->session->userdata('role_id') == 1 || in_array('log', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('log'); ?>">
                    <i class="fas fa-fw fa-history"></i>
                    <span>Log Aktivitas</span></a>
            </li>
            <?php endif; ?>
            
            <?php if ($this->session->userdata('role_id') == 1 || in_array('role', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('role'); ?>">
                    <i class="fas fa-fw fa-user-shield"></i>
                    <span>Manajemen Role</span></a>
            </li>
            <?php endif; ?>

            <?php if ($this->session->userdata('role_id') == 1 || in_array('user', $allowed_controllers)) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url('user'); ?>">
                    <i class="fas fa-fw fa-users-cog"></i>
                    <span>Manajemen User</span></a>
            </li>
            <?php endif; ?>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $this->session->userdata('full_name'); ?></span>
                                <img class="img-profile rounded-circle" src="https://startbootstrap.github.io/startbootstrap-sb-admin-2/img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="<?php echo base_url('auth/logout'); ?>" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <div class="container-fluid">
                    <?php $this->load->view($content); ?>
                </div>
                </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; NeoKABER <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            </div>
        </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <script src="<?php echo base_url('assets/js/sb-admin-2.min.js'); ?>"></script>
    
    <script>
        $(window).on('load', function() {
            $('#preloader').fadeOut('slow');
        });
    </script>
</body>
</html>