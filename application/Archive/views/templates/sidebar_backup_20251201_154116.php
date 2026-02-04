<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ambil base URL dan segment URL saat ini
$base = base_url();
$current = strtolower($this->uri->segment(1)); 

// 1. Core Operations
$order_items = ['sales_contract'];
$purchase_items = ['purchase_order', 'mir', 'po_report'];
// -- Warehouse (Tanpa IT Inv) --
$inventory_items = ['warehouse_in','subkon_order','subkon_out', 'subkon_in','scrap_in', 'scrap_out', 'scrap_stock',  'stock_ledger', 'adjustment', 'stockopname','material_issuance', 'prodreturn']; 
$production_items = ['work_order', 'material_request', 'production_receive', 'production_process'];
$fg_items = ['fg_receipt', 'fg_stock', 'fg_delivery'];
// -- EXIM Verification (Baru) --
$exim_items = ['exim_verification'];
// -- IT Inventory (Terpisah) --
$it_inventory_items = ['lappemasukanbb', 'lappengeluaranbarang', 'lapmutasiscrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin']; 
// (BOM tetap terpisah)

// 3. Accounting
$accounting_items = ['coa', 'jurnalumum', 'apinvoice', 'settingakun', 'appayment', 'arinvoice','arreceipt', 'neraca', 'cashbank','laba_rugi','balance_sheet', 'cash_flow', 'trial_balance','reports/cash_flow'];

// 2. Configuration
$master_items = ['master_supplier', 'master_customer', 'master_material', 'master_semifinished', 'master_scrap', 'master_fg', 'master_machine', 'master_inventory'];
$system_items = ['commoncode', 'roleaccess', 'user','log_activity'];


// =========================================================================
// CEK STATUS 'ACTIVE' (Parent dropdown terbuka)
// =========================================================================
$isOrderActive = in_array($current, $order_items);
$isPurchaseActive = in_array($current, $purchase_items);
$isInventoryActive = in_array($current, $inventory_items); 
$isEximActive = in_array($current, $exim_items); // <-- Variabel baru
$isItInventoryActive = in_array($current, $it_inventory_items); // <-- Variabel baru
$isProductionActive = in_array($current, $production_items);
$isFgActive = in_array($current, $fg_items);
$isAccountingActive = in_array($current, $accounting_items);
$isMasterActive = in_array($current, $master_items);
$isSystemActive = in_array($current, $system_items);


// =========================================================================
// CEK IZIN AKSES (Apakah dropdown grup TAMPIL atau TIDAK)
// =========================================================================
$canViewDashboard = check_access('dashboard', 'view');
$canViewOrder = check_access('sales_contract', 'view');
$canViewPurchase = check_access('purchase_order', 'view') || check_access('mir', 'view');
// -- Izin Warehouse (Terpisah) --
$canViewInventory = check_access('warehouse_in', 'view') || 
                    check_access('stock_ledger', 'view') || 
                    check_access('material_issuance', 'view') || 
                    check_access('adjustment', 'view') ||
                    check_access('stockopname', 'view') ||
                    check_access('prodreturn', 'view') ||
                    check_access('subkon_order', 'view') ||
                    check_access('subkon_out', 'view') ||
                    check_access('subkon_in', 'view') ||
                    check_access('scrap_in', 'view') ||
                    check_access('scrap_out', 'view') ||
                    check_access('scrap_stock', 'view');
// -- Izin EXIM Verification (Baru) --
$canViewExim = check_access('exim_verification', 'view');
// -- Izin IT Inventory (Terpisah) --
$canViewItInventory = check_access('Lap_Pemasukan_BB', 'view') || 
                      check_access('lap_pengeluaran_barang', 'view') ||
                      check_access('lap_mutasi_scrap', 'view') ||
                      check_access('lapmutasimaterial', 'view') ||
                      check_access('lapmutasimesin', 'view') ||
                      check_access('lapmutasiwip', 'view') ||
                      check_access('lapmutasifinished', 'view'); 
$canViewProduction = check_access('work_order', 'view') || check_access('material_request', 'view') || check_access('production_receive', 'view') || check_access('production_process', 'view');
$canViewFg = check_access('fg_receipt', 'view') || check_access('fg_stock', 'view') || check_access('fg_delivery', 'view');
$canViewBom = check_access('bom', 'view');
$canViewAccounting = check_access('jurnalumum', 'view') || check_access('settingakun', 'view') || check_access('apinvoice', 'view') || check_access('appayment', 'view') || check_access('arinvoice', 'view')|| check_access('arreceipt', 'view') || check_access('coa', 'view') || check_access('laba_rugi', 'view');
$canViewMaster = check_access('master_supplier', 'view') || check_access('master_customer', 'view') || check_access('master_material', 'view') || check_access('master_semifinished', 'view') || check_access('master_scrap', 'view') || check_access('master_fg', 'view') || check_access('master_machine', 'view') || check_access('master_inventory', 'view');
$canViewSystem = check_access('commoncode', 'view') || check_access('roleaccess', 'view') || check_access('user', 'view') || check_access('log_activity', 'view');

?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion shadow-sm" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="<?= $base; ?>dashboard">
        <img src="<?= $base; ?>assets/img/logo_full.png" class="sidebar-brand-full" alt="Neokaber Logo">
        
        <img src="<?= $base; ?>assets/img/logo_mini.png" class="sidebar-brand-mini" alt="N">
    </a>

    <hr class="sidebar-divider my-0">

    <?php if ($canViewDashboard): ?>
    <li class="nav-item <?= ($current == 'dashboard' || $current == '') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?= $base; ?>dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Core Operations
    </div>

    <?php if ($canViewOrder): ?>
    <li class="nav-item <?= $isOrderActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isOrderActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseOrder"
            aria-expanded="<?= $isOrderActive ? 'true' : 'false'; ?>" aria-controls="collapseOrder">
            <i class="fas fa-file-signature"></i>
            <span>Order Management</span>
        </a>
        <div id="collapseOrder" class="collapse <?= $isOrderActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('sales_contract', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'sales_contract') ? 'active' : ''; ?>" href="<?= $base; ?>sales_contract">Sales Contract</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewPurchase): ?>
    <li class="nav-item <?= $isPurchaseActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isPurchaseActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapsePurchase"
            aria-expanded="<?= $isPurchaseActive ? 'true' : 'false'; ?>" aria-controls="collapsePurchase">
            <i class="fas fa-shopping-cart"></i>
            <span>Purchase</span>
        </a>
        <div id="collapsePurchase" class="collapse <?= $isPurchaseActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('purchase_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_order') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order">Purchase Order</a>
                <?php endif; ?>
                <?php if (check_access('mir', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'mir') ? 'active' : ''; ?>" href="<?= $base; ?>mir">Import Request</a>
                <?php endif; ?>
                <?php if (check_access('po_report', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'po_report') ? 'active' : ''; ?>" href="<?= $base; ?>po_report">PO Report</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewInventory): ?>
    <li class="nav-item <?= $isInventoryActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isInventoryActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseInventory"
            aria-expanded="<?= $isInventoryActive ? 'true' : 'false'; ?>" aria-controls="collapseInventory">
            <i class="fas fa-warehouse"></i>
            <span>Warehouse</span>
        </a>
        <div id="collapseInventory" class="collapse <?= $isInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                
                <h6 class="collapse-header">Transaksi Gudang:</h6>
                <?php if (check_access('warehouse_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'warehouse_in') ? 'active' : ''; ?>" href="<?= $base; ?>warehouse_in">Penerimaan Barang</a>
                <?php endif; ?>
                <?php if (check_access('material_issuance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_issuance') ? 'active' : ''; ?>" href="<?= $base; ?>material_issuance">Material Issuance</a>
                <?php endif; ?>
                 <?php if (check_access('prodreturn', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'prodreturn') ? 'active' : ''; ?>" href="<?= $base; ?>ProdReturn">Retur Material (WIP)</a>
                <?php endif; ?>
                <h6 class="collapse-header">Subkon</h6>
                <?php if (check_access('subkon_order', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_order') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_order">Subkon Order</a>
                <?php endif; ?>
                <?php if (check_access('subkon_out', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_out') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_out">Subkon Out</a>
                <?php endif; ?>
                <?php if (check_access('subkon_in', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_in') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_in">Subkon In</a>
                <?php endif; ?>

                <h6 class="collapse-header">Laporan Gudang:</h6>
                <?php if (check_access('stock_ledger', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'stock_ledger') ? 'active' : ''; ?>" href="<?= $base; ?>stock_ledger">Laporan Stok</a>
                <?php endif; ?>
                <h6 class="collapse-header">Transaksi Scrap:</h6>
                <?php if (check_access('scrap_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_in') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_In">Penerimaan Scrap</a>
                <?php endif; ?>
                <?php if (check_access('scrap_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_out') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Out">Pengeluaran Scrap</a>
                <?php endif; ?>
                <?php if (check_access('scrap_stock', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'scrap_stock') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Stock">Laporan Stok Scrap</a>
                <?php endif; ?>
                 <h6 class="collapse-header">Adjustment & Opname:</h6>
                <?php if (check_access('adjustment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'adjustment') ? 'active' : ''; ?>" href="<?= $base; ?>Adjustment">Penyesuaian Stok</a>
                <?php endif; ?>
                <?php if (check_access('stockopname', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stockopname') ? 'active' : ''; ?>" href="<?= $base; ?>stockopname">Stock Opname</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>
    
    <?php if ($canViewProduction): ?>
    <li class="nav-item <?= $isProductionActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isProductionActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseProduction"
            aria-expanded="<?= $isProductionActive ? 'true' : 'false'; ?>" aria-controls="collapseProduction">
            <i class="fas fa-industry"></i>
            <span>Production</span>
        </a>
        <div id="collapseProduction" class="collapse <?= $isProductionActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('work_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'work_order') ? 'active' : ''; ?>" href="<?= $base; ?>work_order">Work Order</a>
                <?php endif; ?>
                <?php if (check_access('material_request', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_request') ? 'active' : ''; ?>" href="<?= $base; ?>material_request">Material Request (MR)</a>
                <?php endif; ?>
                <?php if (check_access('production_receive', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'production_receive') ? 'active' : ''; ?>" href="<?= $base; ?>production_receive">Penerimaan Produksi</a>
                <?php endif; ?>
                <?php if (check_access('production_process', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'production_process') ? 'active' : ''; ?>" href="<?= $base; ?>production_process">Proses Produksi</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>
    
    <?php if ($canViewFg): ?>
    <li class="nav-item <?= $isFgActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isFgActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseFg"
            aria-expanded="<?= $isFgActive ? 'true' : 'false'; ?>" aria-controls="collapseFg">
            <i class="fas fa-box-open"></i>
            <span>Finished Goods</span>
        </a>
        <div id="collapseFg" class="collapse <?= $isFgActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('fg_receipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_receipt') ? 'active' : ''; ?>" href="<?= $base; ?>fg_receipt">Penerimaan FG</a>
                <?php endif; ?>
                <?php if (check_access('fg_stock', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_stock') ? 'active' : ''; ?>" href="<?= $base; ?>fg_stock">Kartu Stok FG</a>
                <?php endif; ?>
                 <?php if (check_access('fg_delivery', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_delivery') ? 'active' : ''; ?>" href="<?= $base; ?>fg_delivery">Pengeluaran FG</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewBom): ?>
        <li class="nav-item <?= ($current == 'bom') ? 'active' : ''; ?>">
            <a class="nav-link" href="<?= $base; ?>bom">
                <i class="fas fa-sitemap"></i>
                <span>Bill of Materials</span>
            </a>
        </li>
    <?php endif; ?>
    
    <?php if ($canViewExim): ?>
    <li class="nav-item <?= $isEximActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isEximActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseExim"
            aria-expanded="<?= $isEximActive ? 'true' : 'false'; ?>" aria-controls="collapseExim">
            <i class="fas fa-check-circle"></i>
            <span>EXIM Verification</span>
        </a>
        <div id="collapseExim" class="collapse <?= $isEximActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Verifikasi Bea Cukai:</h6>
                <a class="collapse-item" href="<?= $base; ?>exim_verification">Dashboard</a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/incoming">Pemasukan Barang</a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/outgoing">Pengeluaran Barang</a>
            </div>
        </div>
    </li>
    <?php endif; ?>
    
    <?php if ($canViewItInventory): ?>
    <li class="nav-item <?= $isItInventoryActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isItInventoryActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseItInventory"
            aria-expanded="<?= $isItInventoryActive ? 'true' : 'false'; ?>" aria-controls="collapseItInventory">
            <i class="fas fa-desktop"></i>
            <span>IT Inventory (BC)</span>
        </a>
        <div id="collapseItInventory" class="collapse <?= $isItInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Laporan Bea Cukai:</h6>
                <?php if (check_access('Lap_Pemasukan_BB', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappemasukanbb') ? 'active' : ''; ?>" href="<?= $base; ?>LapPemasukanBB">Lap Pemasukan Barang</a>
                <?php endif; ?>
                <?php if (check_access('lap_pengeluaran_barang', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappengeluaranbarang') ? 'active' : ''; ?>" href="<?= $base; ?>LapPengeluaranBarang">Lap Pengeluaran Barang</a>
                <?php endif; ?>
                <?php if (check_access('lap_mutasi_scrap', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiscrap') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiScrap">Lap Mutasi Scrap</a>
                <?php endif; ?>
                
                <h6 class="collapse-header">Laporan Mutasi:</h6>
                <?php if (check_access('lapmutasimaterial', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimaterial') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMaterial">Lap Mutasi Bahan Baku</a>
                <?php endif; ?>
                <?php if (check_access('lapmutasifinished', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasifinished') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiFinished">Lap Mutasi Barang Jadi</a>
                <?php endif; ?>
                <?php if (check_access('lapmutasiwip', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiwip') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiWIP">Lap Mutasi WIP</a>
                <?php endif; ?>
                <?php if (check_access('lapmutasimesin', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimesin') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMesin">Lap Mutasi Mesin</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Accounting
    </div>

    <?php if ($canViewAccounting): ?>
    <li class="nav-item <?= $isAccountingActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isAccountingActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseAccounting"
            aria-expanded="<?= $isAccountingActive ? 'true' : 'false'; ?>" aria-controls="collapseAccounting">
            <i class="fas fa-fw fa-calculator"></i>
            <span>Finance & Accounting</span>
        </a>
        <div id="collapseAccounting" class="collapse <?= $isAccountingActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">General Ledger:</h6>
                <?php if (check_access('coa', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'coa') ? 'active' : ''; ?>" href="<?= $base; ?>coa">Chart of Accounts (COA)</a>
                <?php endif; ?>
                <?php if (check_access('jurnalumum', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'jurnalumum') ? 'active' : ''; ?>" href="<?= $base; ?>jurnalumum">Jurnal Umum</a>
                <?php endif; ?>
                <?php if (check_access('apinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'apinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>apinvoice">Account Payable</a>
                <?php endif; ?>
                <?php if (check_access('settingakun', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'settingakun') ? 'active' : ''; ?>" href="<?= $base; ?>settingakun">Setting Akun</a>
                <?php endif; ?>
                <?php if (check_access('appayment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'appayment') ? 'active' : ''; ?>" href="<?= $base; ?>appayment">Pembayaran Hutang</a>
                <?php endif; ?>
                <?php if (check_access('arinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>arinvoice">Invoice Piutang</a>
                <?php endif; ?>
                <?php if (check_access('arreceipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arreceipt') ? 'active' : ''; ?>" href="<?= $base; ?>arreceipt">Pembayaran Piutang</a>
                <?php endif; ?>
                <?php if (check_access('cashbank', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'cashbank') ? 'active' : ''; ?>" href="<?= $base; ?>cashbank">KAS & Bank</a>
                <?php endif; ?>
                <h6 class="collapse-header">Laporan</h6>
                <?php if (check_access('laba_rugi', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports/profit_loss') ? 'active' : ''; ?>" href="<?= $base; ?>reports/profit_loss">Laba Rugi</a>
                <?php endif; ?>
                <?php if (check_access('neraca', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports/balance_sheet') ? 'active' : ''; ?>" href="<?= $base; ?>reports/balance_sheet">Neraca</a>
                <?php endif; ?>
                <?php if (check_access('cash_flow', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports/cash_flow') ? 'active' : ''; ?>" href="<?= $base; ?>reports/trial_balance">Neraca Saldo</a>
                <?php endif; ?>
                <?php if (check_access('trial_balance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports/cash_flow') ? 'active' : ''; ?>" href="<?= $base; ?>reports/cash_flow">Arus Kas</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>


    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Configuration
    </div>

    <?php if ($canViewMaster): ?>
    <li class="nav-item <?= $isMasterActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isMasterActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseMaster"
            aria-expanded="<?= $isMasterActive ? 'true' : 'false'; ?>" aria-controls="collapseMaster">
            <i class="fas fa-fw fa-database"></i>
            <span>Master Data</span>
        </a>
        <div id="collapseMaster" class="collapse <?= $isMasterActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                 <?php if (check_access('master_supplier', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_supplier') ? 'active' : ''; ?>" href="<?= $base; ?>master_supplier">Master Supplier</a>
                 <?php endif; ?>
                 <?php if (check_access('master_customer', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_customer') ? 'active' : ''; ?>" href="<?= $base; ?>master_customer">Master Pelanggan</a>
                 <?php endif; ?>
                <?php if (check_access('master_material', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_material') ? 'active' : ''; ?>" href="<?= $base; ?>master_material">Master Material</a>
                <?php endif; ?>
                <?php if (check_access('master_semifinished', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_semifinished') ? 'active' : ''; ?>" href="<?= $base; ?>master_semifinished">Master Semifinished</a>
                <?php endif; ?>
                 <?php if (check_access('master_scrap', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_scrap') ? 'active' : ''; ?>" href="<?= $base; ?>master_scrap">Master Scrap</a>
                 <?php endif; ?>
                 <?php if (check_access('master_fg', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_fg') ? 'active' : ''; ?>" href="<?= $base; ?>master_fg">Master Finished Goods</a>
                 <?php endif; ?>
                 <?php if (check_access('master_machine', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_machine') ? 'active' : ''; ?>" href="<?= $base; ?>master_machine">Master Mesin</a>
                 <?php endif; ?>
                <?php if (check_access('master_inventory', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>master_inventory">Master Inventaris</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewSystem): ?>
    <li class="nav-item <?= $isSystemActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isSystemActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseSystem"
            aria-expanded="<?= $isSystemActive ? 'true' : 'false'; ?>" aria-controls="collapseSystem">
            <i class="fas fa-fw fa-cogs"></i>
            <span>System Setup</span>
        </a>
        <div id="collapseSystem" class="collapse <?= $isSystemActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('commoncode', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'commoncode') ? 'active' : ''; ?>" href="<?= $base; ?>commoncode">Common Code</a>
                <?php endif; ?>
                 <?php if (check_access('roleaccess', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'roleaccess') ? 'active' : ''; ?>" href="<?= $base; ?>roleaccess">Role Access</a>
                 <?php endif; ?>
                 <?php if (check_access('user', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'user') ? 'active' : ''; ?>" href="<?= $base; ?>user">User Management</a>
                 <?php endif; ?>
                 <?php if (check_access('log_activity', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'log_activity') ? 'active' : ''; ?>" 
                    href="<?= $base; ?>log_activity">Log Activity</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>



    <div class="text-center d-none d-md-inline my-3">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">