<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">

  <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
    <i class="fa fa-bars"></i>
  </button>

  <a class="btn-load" href="<?= base_url('dashboard'); ?>">
    <img src="<?= base_url('assets/img/logo_full_biru.png'); ?>" alt="Neokaber Logo" style="height: 85px;">
  </a>
  <ul class="navbar-nav ml-auto">

    <li class="nav-item mx-1">
      <a class="nav-link btn-load" href="<?= base_url('dashboard'); ?>" title="Dashboard">
        <i class="fas fa-home fa-lg text-primary"></i>
      </a>
    </li>

    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown">
        <i class="fas fa-bell fa-lg text-warning"></i>
        <span class="badge badge-danger badge-counter">2</span>
      </a>
      <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
        <h6 class="dropdown-header">Notifikasi Sistem</h6>
        <a class="dropdown-item d-flex align-items-center" href="#">
          <div class="font-weight-bold">Backup database berhasil dilakukan</div>
        </a>
        <a class="dropdown-item d-flex align-items-center" href="#">
          <div class="font-weight-bold">User baru telah ditambahkan</div>
        </a>
        <a class="dropdown-item text-center small text-gray-500" href="#">Lihat semua notifikasi</a>
      </div>
    </li>

    <div class="topbar-divider d-none d-sm-block"></div>

    <li class="nav-item dropdown no-arrow">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
        <span class="mr-2 d-none d-lg-inline text-gray-600 small">
          <?= $this->session->userdata('username') ?? 'Administrator'; ?> 
          (<?= $this->session->userdata('role_name') ?? 'Superadmin'; ?>)
        </span>
      </a>
      <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
        <a class="dropdown-item" href="#">
          <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profil
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="<?= base_url('auth/logout'); ?>">
          <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i> Logout
        </a>
      </div>
    </li>

  </ul>
</nav>