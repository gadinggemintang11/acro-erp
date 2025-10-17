<div class="container-fluid">
  <h3 class="mb-4 text-gray-800">
    <i class="fas fa-tachometer-alt text-primary mr-2"></i><?= $title; ?>
  </h3>

  <div class="row">

    <!-- Welcome Card -->
    <div class="col-xl-4 col-md-6 mb-4">
      <div class="card border-left-primary shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Selamat Datang</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $username; ?></div>
              <div class="text-xs text-muted"><?= $role_name; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-user-circle fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Card Menu Count -->
    <div class="col-xl-4 col-md-6 mb-4">
      <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Modul</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $menu_count; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-layer-group fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Card User Count -->
    <div class="col-xl-4 col-md-6 mb-4">
      <div class="card border-left-info shadow h-100 py-2">
        <div class="card-body">
          <div class="row no-gutters align-items-center">
            <div class="col mr-2">
              <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total User</div>
              <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $user_count; ?></div>
            </div>
            <div class="col-auto">
              <i class="fas fa-users fa-2x text-gray-300"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="card shadow-sm border-0 mt-3">
    <div class="card-body text-center">
      <h5 class="text-primary font-weight-bold mb-2">NEOKABER SYSTEM</h5>
      <p class="text-muted mb-1">"Satu Data, Satu Sistem, Satu Kendali"</p>
      <small class="text-secondary">© <?= date('Y'); ?> Handal Optima Integration</small>
    </div>
  </div>
</div>
