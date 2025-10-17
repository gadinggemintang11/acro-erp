<?php
$base = base_url();
$current = $this->uri->segment(1); // ambil segment pertama dari URL
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion shadow-sm" id="accordionSidebar">

  <!-- Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="<?= $base; ?>dashboard">
    <div class="sidebar-brand-icon rotate-n-15">
      <i class="fas fa-cubes"></i>
    </div>
    <div class="sidebar-brand-text mx-2 font-weight-bold text-uppercase">NEOKABER</div>
  </a>

  <!-- Divider -->
  <hr class="sidebar-divider my-0">

  <!-- Dashboard -->
  <li class="nav-item <?= ($current == 'dashboard') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>dashboard">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">Order</div>

  <li class="nav-item <?= ($current == 'sales_contract') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('sales_contract'); ?>">
        <i class="fas fa-file-signature"></i>
        <span>Sales Contract</span>
    </a>
  </li>

    <!-- Divider -->
  <hr class="sidebar-divider">
  <!-- INVENTORY -->
   <div class="sidebar-heading">PRODUCTION</div>
  <li class="nav-item <?= ($current == 'work_order') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('work_order'); ?>">
        <i class="fas fa-industry"></i>
        <span>Work Order</span>
    </a>
  </li>
  <li class="nav-item <?= ($current == 'material_request') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('material_request'); ?>">
        <i class="fas fa-clipboard-list"></i> <span>Material Request (MR)</span>
    </a>
  </li>
  <li class="nav-item <?= ($current == 'production_receive') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('production_receive'); ?>">
        <i class="fas fa-check-square"></i> <span>Penerimaan Produksi</span>
    </a>
  </li>
  <li class="nav-item <?= ($current == 'production_process') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('production_process'); ?>">
        <i class="fas fa-tasks"></i> <span>Proses Produksi</span>
    </a>
  </li>

  <hr class="sidebar-divider">
  <!-- INVENTORY -->
   <div class="sidebar-heading">BOM</div>


  <li class="nav-item <?= ($current == 'bom') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('bom'); ?>">
        <i class="fas fa-cogs"></i> <span>Bill of Materials</span>
    </a>
</li>
<hr class="sidebar-divider">
<div class="sidebar-heading">PURCHASE</div>
<li class="nav-item <?= ($current == 'purchase_order') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>purchase_order">
      <i class="fas fa-shopping-cart"></i>
      <span>Purchase Order</span>
    </a>
  </li>
  <li class="nav-item <?= ($current == 'mir') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('mir'); ?>">
        <i class="fas fa-plane-departure"></i>
        <span>Import Request</span>
    </a>
  </li>

  <!-- Divider -->
  <hr class="sidebar-divider">
  <!-- INVENTORY -->
<div class="sidebar-heading">Inventory</div>

<li class="nav-item <?= ($current == 'warehouse_in') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>warehouse_in">
    <i class="fas fa-truck-loading"></i>
    <span>Penerimaan Barang</span>
  </a>
</li>
<li class="nav-item <?= ($current == 'stock_ledger') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('stock_ledger'); ?>">
        <i class="fas fa-book"></i>
        <span>Stock</span>
    </a>
</li>
<li class="nav-item <?= ($current == 'material_issuance') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= base_url('material_issuance'); ?>">
        <i class="fas fa-dolly"></i> <span>Material Issuance (WH)</span>
    </a>
</li>

<hr class="sidebar-divider">


  <!-- SYSTEM SETUP -->
  <div class="sidebar-heading text-white-50">System Setup</div>

  <li class="nav-item <?= ($current == 'commoncode') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>commoncode">
      <i class="fas fa-cog"></i>
      <span>Common Code</span>
    </a>
  </li>

  <li class="nav-item <?= ($current == 'roleaccess') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>roleaccess">
      <i class="fas fa-user-shield"></i>
      <span>Role Access</span>
    </a>
  </li>

  <li class="nav-item <?= ($current == 'user') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>user">
      <i class="fas fa-users-cog"></i>
      <span>User Management</span>
    </a>
  </li>

  <!-- Divider -->
  <hr class="sidebar-divider">

  <!-- MASTER DATA -->
  <div class="sidebar-heading text-white-50">Master Data</div>

  <li class="nav-item <?= ($current == 'master_supplier') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>master_supplier">
    <i class="fas fa-truck"></i> <span>Master Supplier</span>
  </a>
  </li>
  <li class="nav-item <?= ($current == 'master_customer') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>master_customer">
    <i class="fas fa-user-tie"></i> <span>Master Pelanggan</span>
  </a>
  </li>



  <li class="nav-item <?= ($current == 'master_material') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>master_material">
      <i class="fas fa-cube"></i>
      <span>Master Material</span>
    </a>
  </li>

  <li class="nav-item <?= ($current == 'master_semifinished') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>master_semifinished">
      <i class="fas fa-puzzle-piece"></i>
      <span>Master Semifinished</span>
    </a>
  </li>
  <li class="nav-item <?= ($current == 'master_scrap') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= base_url('master_scrap'); ?>">
    <i class="fas fa-recycle"></i> <span>Master Scrap</span>
  </a>
  </li>

  <li class="nav-item <?= ($current == 'master_fg') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>master_fg">
    <i class="fas fa-box"></i> <span>Master Finished Goods</span>
  </a>
  </li>
  <li class="nav-item <?= ($current == 'master_machine') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>master_machine">
    <i class="fas fa-cogs"></i> <span>Master Mesin & Peralatan</span>
  </a>
  </li>
  <li class="nav-item <?= ($current == 'master_inventory') ? 'active' : ''; ?>">
  <a class="nav-link btn-load" href="<?= $base; ?>master_inventory">
    <i class="fas fa-clipboard-list"></i> <span>Master Inventaris</span>
  </a>
  </li>



  <!-- (nanti aktifkan ini setelah modul FG selesai dibuat)
  <li class="nav-item <?= ($current == 'master_finished') ? 'active' : ''; ?>">
    <a class="nav-link btn-load" href="<?= $base; ?>master_finished">
      <i class="fas fa-box"></i>
      <span>Master Finished Goods</span>
    </a>
  </li>
  -->

  <!-- Divider -->
  <hr class="sidebar-divider d-none d-md-block">

  <!-- LOGOUT -->
  <li class="nav-item mt-3">
    <a class="nav-link text-warning" href="<?= $base; ?>auth/logout" onclick="return confirm('Yakin ingin logout?');">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <!-- Sidebar Toggler -->
  <div class="text-center d-none d-md-inline mb-3">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

</ul>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
  <div id="content" class="p-4">
