<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ambil base URL dan segment URL saat ini
$base = base_url();
$current = strtolower($this->uri->segment(1)); 

// 1. Core Operations
$order_items = ['sales_contract'];
$purchase_items = ['purchase_order', 'mir', 'po_report'];
$inventory_items = ['warehouse_in','subkon_order','subkon_out', 'subkon_in','scrap_in', 'scrap_out', 'scrap_stock',  'stock_ledger', 'adjustment', 'stockopname','material_issuance', 'prodreturn']; 
$production_items = ['work_order', 'material_request', 'production_receive', 'production_process'];
$fg_items = ['fg_receipt', 'fg_stock', 'fg_delivery'];
$exim_items = ['exim_verification'];
$it_inventory_items = ['lappemasukanbb', 'lappengeluaranbarang', 'lapmutasiscrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin']; 

// 3. Accounting
$accounting_items = ['coa', 'jurnalumum', 'apinvoice', 'settingakun', 'appayment', 'arinvoice','arreceipt', 'neraca', 'cashbank','laba_rugi','balance_sheet', 'cash_flow', 'trial_balance', 'reports'];

// 2. Configuration
$master_items = ['master_supplier', 'master_customer', 'master_material', 'master_semifinished', 'master_scrap', 'master_fg', 'master_machine', 'master_inventory'];
$system_items = ['commoncode', 'roleaccess', 'user','log_activity'];

// CEK STATUS 'ACTIVE'
$isOrderActive = in_array($current, $order_items);
$isPurchaseActive = in_array($current, $purchase_items);
$isInventoryActive = in_array($current, $inventory_items); 
$isEximActive = in_array($current, $exim_items);
$isItInventoryActive = in_array($current, $it_inventory_items);
$isProductionActive = in_array($current, $production_items);
$isFgActive = in_array($current, $fg_items);
$isAccountingActive = in_array($current, $accounting_items);
$isMasterActive = in_array($current, $master_items);
$isSystemActive = in_array($current, $system_items);

// CEK IZIN AKSES
$canViewDashboard = check_access('dashboard', 'view');
$canViewOrder = check_access('sales_contract', 'view');
$canViewPurchase = check_access('purchase_order', 'view') || check_access('mir', 'view');
$canViewInventory = check_access('warehouse_in', 'view') || check_access('stock_ledger', 'view') || check_access('material_issuance', 'view') || check_access('adjustment', 'view') || check_access('stockopname', 'view') || check_access('prodreturn', 'view') || check_access('subkon_order', 'view') || check_access('subkon_out', 'view') || check_access('subkon_in', 'view') || check_access('scrap_in', 'view') || check_access('scrap_out', 'view') || check_access('scrap_stock', 'view');
$canViewExim = check_access('exim_verification', 'view');
$canViewItInventory = check_access('Lap_Pemasukan_BB', 'view') || check_access('lap_pengeluaran_barang', 'view') || check_access('lap_mutasi_scrap', 'view') || check_access('lapmutasimaterial', 'view') || check_access('lapmutasimesin', 'view') || check_access('lapmutasiwip', 'view') || check_access('lapmutasifinished', 'view'); 
$canViewProduction = check_access('work_order', 'view') || check_access('material_request', 'view') || check_access('production_receive', 'view') || check_access('production_process', 'view');
$canViewFg = check_access('fg_receipt', 'view') || check_access('fg_stock', 'view') || check_access('fg_delivery', 'view');
$canViewBom = check_access('bom', 'view');
$canViewAccounting = check_access('jurnalumum', 'view') || check_access('settingakun', 'view') || check_access('apinvoice', 'view') || check_access('appayment', 'view') || check_access('arinvoice', 'view')|| check_access('arreceipt', 'view') || check_access('coa', 'view') || check_access('laba_rugi', 'view');
$canViewMaster = check_access('master_supplier', 'view') || check_access('master_customer', 'view') || check_access('master_material', 'view') || check_access('master_semifinished', 'view') || check_access('master_scrap', 'view') || check_access('master_fg', 'view') || check_access('master_machine', 'view') || check_access('master_inventory', 'view');
$canViewSystem = check_access('commoncode', 'view') || check_access('roleaccess', 'view') || check_access('user', 'view') || check_access('log_activity', 'view');

?>

<style>
/* Modern Sidebar Styling */
:root {
    --sidebar-bg: #2b46e2ff;
    --sidebar-hover: rgba(79, 70, 229, 0.3);
    --sidebar-active: #4F46E5;
    --sidebar-text: #a8b3cf;
    --sidebar-text-active: #ffffff;
    --sidebar-border: rgba(255,255,255,0.05);
}

#accordionSidebar {
    background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0430f3ff 100%) !important;
    width: 260px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
}

/* Brand Logo */
.sidebar-brand {
    height: 70px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid var(--sidebar-border);
    -webkit-transition: all 0.3s ease;
    transition: all 0.3s ease;
}

.sidebar-brand:hover {
    background: rgba(255,255,255,0.05) !important;
}

.sidebar-brand-full {
    max-height: 60px;
    ;
}

.sidebar-brand-mini {
    max-height: 45px;
    
}

/* Divider */
.sidebar-divider {
    border-top: 1px solid var(--sidebar-border);
    margin: 1rem 0;
}

/* Section Heading */
.sidebar-heading {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 1px;
    color: var(--sidebar-text);
    text-transform: uppercase;
    padding: 0.5rem 1rem;
    margin-top: 0.5rem;
}

/* Nav Item */
.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.85rem 1.25rem;
    color: var(--sidebar-text);
    -webkit-transition: all 0.2s ease;
    transition: all 0.2s ease;
    border-radius: 0;
    border-left: 3px solid transparent;
    font-size: 0.875rem;
    font-weight: 500;
}

.nav-link i {
    width: 24px;
    margin-right: 0.75rem;
    font-size: 0.95rem;
    -webkit-transition: all 0.2s ease;
    transition: all 0.2s ease;
}

.nav-link span {
    -webkit-transition: all 0.2s ease;
    transition: all 0.2s ease;
}

/* Hover State */
.nav-link:hover {
    color: var(--sidebar-text-active) !important;
    background: var(--sidebar-hover) !important;
    border-left-color: var(--sidebar-active) !important;
    padding-left: 1.5rem !important;
}

.nav-link:hover i {
    -webkit-transform: translateX(3px);
    transform: translateX(3px);
    color: var(--sidebar-active) !important;
}

/* Active State */
.nav-item.active > .nav-link,
.nav-link.active {
    color: var(--sidebar-text-active);
    background: linear-gradient(90deg, rgba(79, 70, 229, 0.2) 0%, transparent 100%);
    border-left-color: var(--sidebar-active);
    font-weight: 600;
}

.nav-item.active > .nav-link i {
    color: var(--sidebar-active);
}

/* Collapse Menu */
.collapse-inner {
    background: rgba(0,0,0,0.2);
    border-radius: 8px;
    margin: 0.25rem 0.75rem 0.5rem;
    padding: 0.5rem 0;
}

.collapse-header {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--sidebar-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.75rem 1rem 0.5rem;
    margin: 0;
}

.collapse-item {
    display: block;
    padding: 0.65rem 1.25rem;
    color: var(--sidebar-text);
    font-size: 0.8125rem;
    -webkit-transition: all 0.2s ease;
    transition: all 0.2s ease;
    border-radius: 6px;
    margin: 0.125rem 0.5rem;
    position: relative;
}

.collapse-item:hover {
    color: var(--sidebar-text-active) !important;
    background: rgba(79, 70, 229, 0.15) !important;
    padding-left: 1.5rem !important;
    text-decoration: none !important;
}

.collapse-item.active {
    color: var(--sidebar-text-active);
    background: rgba(79, 70, 229, 0.25);
    font-weight: 600;
}

.collapse-item.active::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 50%;
    -webkit-transform: translateY(-50%);
    transform: translateY(-50%);
    width: 4px;
    height: 4px;
    background: var(--sidebar-active);
    border-radius: 50%;
}

/* Sidebar Toggle Button */
#sidebarToggle {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border: 1px solid var(--sidebar-border);
    transition: all 0.2s ease;
}

#sidebarToggle:hover {
    background: var(--sidebar-active);
    border-color: var(--sidebar-active);
    transform: scale(1.05);
}

/* Toggled State */
.sidebar.toggled {
    width: 6.5rem;
}

/* Content wrapper adjustment for fixed sidebar */
#content-wrapper {
    margin-left: 260px;
    transition: margin-left 0.3s ease;
}

.sidebar.toggled ~ #content-wrapper {
    margin-left: 6.5rem;
}

.sidebar.toggled .sidebar-brand-full {
    display: none;
}

.sidebar.toggled .sidebar-brand-mini {
    display: block;
}

.sidebar.toggled .nav-link span,
.sidebar.toggled .sidebar-heading,
.sidebar.toggled .collapse-header {
    display: none;
}

.sidebar.toggled .nav-link {
    justify-content: center;
    padding: 0.85rem 0;
}

.sidebar.toggled .nav-link i {
    margin-right: 0;
    font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 768px) {
    #accordionSidebar {
        width: 6.5rem;
    }
    
    #content-wrapper {
        margin-left: 6.5rem;
    }
    
    .sidebar-brand-full {
        display: none;
    }
    
    .sidebar-brand-mini {
        display: block;
    }
    
    .nav-link span,
    .sidebar-heading,
    .collapse-header {
        display: none;
    }
    
    .nav-link {
        justify-content: center;
        padding: 0.85rem 0;
    }
    
    .nav-link i {
        margin-right: 0;
        font-size: 1.2rem;
    }
}

/* Scrollbar Styling */
#accordionSidebar::-webkit-scrollbar {
    width: 6px;
}

#accordionSidebar::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.1);
}

#accordionSidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
}

#accordionSidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
</style>

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="<?= $base; ?>dashboard">
        <img src="<?= $base; ?>assets/img/logo_full.png" class="sidebar-brand-full" alt="Neokaber Logo">
        
        <img src="<?= $base; ?>assets/img/logo_mini.png" class="sidebar-brand-mini" alt="N">
    </a>

    <hr class="sidebar-divider my-0">

    <?php if ($canViewDashboard): ?>
    <li class="nav-item <?= ($current == 'dashboard' || $current == '') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?= $base; ?>dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span><?= lang_line('sidebar_dashboard', 'Dashboard'); ?></span>
        </a>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_core_operations', 'Core Operations'); ?>
    </div>

    <?php if ($canViewOrder): ?>
    <li class="nav-item <?= $isOrderActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isOrderActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseOrder"
            aria-expanded="<?= $isOrderActive ? 'true' : 'false'; ?>" aria-controls="collapseOrder">
            <i class="fas fa-file-signature"></i>
            <span><?= lang_line('sidebar_order_management', 'Order Management'); ?></span>
        </a>
        <div id="collapseOrder" class="collapse <?= $isOrderActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('sales_contract', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'sales_contract') ? 'active' : ''; ?>" href="<?= $base; ?>sales_contract"><?= lang_line('sidebar_sales_contract', 'Sales Contract'); ?></a>
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
            <span><?= lang_line('sidebar_purchase', 'Purchase'); ?></span>
        </a>
        <div id="collapsePurchase" class="collapse <?= $isPurchaseActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('purchase_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_order') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order"><?= lang_line('sidebar_purchase_order', 'Purchase Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('mir', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'mir') ? 'active' : ''; ?>" href="<?= $base; ?>mir"><?= lang_line('sidebar_import_request', 'Import Request'); ?></a>
                <?php endif; ?>
                <?php if (check_access('po_report', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'po_report') ? 'active' : ''; ?>" href="<?= $base; ?>po_report"><?= lang_line('sidebar_po_report', 'PO Report'); ?></a>
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
            <span><?= lang_line('sidebar_warehouse', 'Warehouse'); ?></span>
        </a>
        <div id="collapseInventory" class="collapse <?= $isInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                
                <h6 class="collapse-header"><?= lang_line('sidebar_warehouse_transactions', 'Transaksi Gudang:'); ?></h6>
                <?php if (check_access('warehouse_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'warehouse_in') ? 'active' : ''; ?>" href="<?= $base; ?>warehouse_in"><?= lang_line('sidebar_goods_receipt', 'Penerimaan Barang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_issuance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_issuance') ? 'active' : ''; ?>" href="<?= $base; ?>material_issuance"><?= lang_line('sidebar_material_issuance', 'Material Issuance'); ?></a>
                <?php endif; ?>
                 <?php if (check_access('prodreturn', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'prodreturn') ? 'active' : ''; ?>" href="<?= $base; ?>ProdReturn"><?= lang_line('sidebar_material_return', 'Retur Material (WIP)'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_subcon', 'Subkon'); ?></h6>
                <?php if (check_access('subkon_order', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_order') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_order"><?= lang_line('sidebar_subcon_order', 'Subkon Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_out', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_out') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_out"><?= lang_line('sidebar_subcon_out', 'Subkon Out'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_in', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'subkon_in') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_in"><?= lang_line('sidebar_subcon_in', 'Subkon In'); ?></a>
                <?php endif; ?>

                <h6 class="collapse-header"><?= lang_line('sidebar_warehouse_reports', 'Laporan Gudang:'); ?></h6>
                <?php if (check_access('stock_ledger', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'stock_ledger') ? 'active' : ''; ?>" href="<?= $base; ?>stock_ledger"><?= lang_line('sidebar_stock_report', 'Laporan Stok'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_scrap_transactions', 'Transaksi Scrap:'); ?></h6>
                <?php if (check_access('scrap_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_in') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_In"><?= lang_line('sidebar_scrap_receipt', 'Penerimaan Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_out') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Out"><?= lang_line('sidebar_scrap_out', 'Pengeluaran Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_stock', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'scrap_stock') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Stock"><?= lang_line('sidebar_scrap_stock_report', 'Laporan Stok Scrap'); ?></a>
                <?php endif; ?>
                 <h6 class="collapse-header"><?= lang_line('sidebar_adjustment_opname', 'Adjustment & Opname:'); ?></h6>
                <?php if (check_access('adjustment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'adjustment') ? 'active' : ''; ?>" href="<?= $base; ?>Adjustment"><?= lang_line('sidebar_stock_adjustment', 'Penyesuaian Stok'); ?></a>
                <?php endif; ?>
                <?php if (check_access('stockopname', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stockopname') ? 'active' : ''; ?>" href="<?= $base; ?>stockopname"><?= lang_line('sidebar_stock_opname', 'Stock Opname'); ?></a>
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
            <span><?= lang_line('sidebar_production', 'Production'); ?></span>
        </a>
        <div id="collapseProduction" class="collapse <?= $isProductionActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('work_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'work_order') ? 'active' : ''; ?>" href="<?= $base; ?>work_order"><?= lang_line('sidebar_work_order', 'Work Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_request', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_request') ? 'active' : ''; ?>" href="<?= $base; ?>material_request"><?= lang_line('sidebar_material_request', 'Material Request (MR)'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_receive', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'production_receive') ? 'active' : ''; ?>" href="<?= $base; ?>production_receive"><?= lang_line('sidebar_production_receipt', 'Penerimaan Produksi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_process', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'production_process') ? 'active' : ''; ?>" href="<?= $base; ?>production_process"><?= lang_line('sidebar_production_process', 'Proses Produksi'); ?></a>
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
            <span><?= lang_line('sidebar_finished_goods', 'Finished Goods'); ?></span>
        </a>
        <div id="collapseFg" class="collapse <?= $isFgActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('fg_receipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_receipt') ? 'active' : ''; ?>" href="<?= $base; ?>fg_receipt"><?= lang_line('sidebar_fg_receipt', 'Penerimaan FG'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_stock', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_stock') ? 'active' : ''; ?>" href="<?= $base; ?>fg_stock"><?= lang_line('sidebar_fg_stock', 'Kartu Stok FG'); ?></a>
                <?php endif; ?>
                 <?php if (check_access('fg_delivery', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_delivery') ? 'active' : ''; ?>" href="<?= $base; ?>fg_delivery"><?= lang_line('sidebar_fg_delivery', 'Pengeluaran FG'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewBom): ?>
        <li class="nav-item <?= ($current == 'bom') ? 'active' : ''; ?>">
            <a class="nav-link" href="<?= $base; ?>bom">
                <i class="fas fa-sitemap"></i>
                <span><?= lang_line('sidebar_bom', 'Bill of Materials'); ?></span>
            </a>
        </li>
    <?php endif; ?>
    
    <?php if ($canViewExim): ?>
    <li class="nav-item <?= $isEximActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isEximActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseExim"
            aria-expanded="<?= $isEximActive ? 'true' : 'false'; ?>" aria-controls="collapseExim">
            <i class="fas fa-check-circle"></i>
            <span><?= lang_line('sidebar_exim_verification', 'EXIM Verification'); ?></span>
        </a>
        <div id="collapseExim" class="collapse <?= $isEximActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_customs_verification', 'Verifikasi Bea Cukai:'); ?></h6>
                <a class="collapse-item" href="<?= $base; ?>exim_verification"><?= lang_line('sidebar_dashboard', 'Dashboard'); ?></a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/incoming"><?= lang_line('sidebar_goods_incoming', 'Pemasukan Barang'); ?></a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/outgoing"><?= lang_line('sidebar_goods_outgoing', 'Pengeluaran Barang'); ?></a>
            </div>
        </div>
    </li>
    <?php endif; ?>
    
    <?php if ($canViewItInventory): ?>
    <li class="nav-item <?= $isItInventoryActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isItInventoryActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseItInventory"
            aria-expanded="<?= $isItInventoryActive ? 'true' : 'false'; ?>" aria-controls="collapseItInventory">
            <i class="fas fa-desktop"></i>
            <span><?= lang_line('sidebar_it_inventory', 'IT Inventory (BC)'); ?></span>
        </a>
        <div id="collapseItInventory" class="collapse <?= $isItInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_customs_reports', 'Laporan Bea Cukai:'); ?></h6>
                <?php if (check_access('Lap_Pemasukan_BB', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappemasukanbb') ? 'active' : ''; ?>" href="<?= $base; ?>LapPemasukanBB"><?= lang_line('sidebar_goods_incoming_report', 'Lap Pemasukan Barang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lap_pengeluaran_barang', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappengeluaranbarang') ? 'active' : ''; ?>" href="<?= $base; ?>LapPengeluaranBarang"><?= lang_line('sidebar_goods_outgoing_report', 'Lap Pengeluaran Barang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lap_mutasi_scrap', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiscrap') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiScrap"><?= lang_line('sidebar_scrap_mutation_report', 'Lap Mutasi Scrap'); ?></a>
                <?php endif; ?>
                
                <h6 class="collapse-header"><?= lang_line('sidebar_mutation_reports', 'Laporan Mutasi:'); ?></h6>
                <?php if (check_access('lapmutasimaterial', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimaterial') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMaterial"><?= lang_line('sidebar_material_mutation_report', 'Lap Mutasi Bahan Baku'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasifinished', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasifinished') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiFinished"><?= lang_line('sidebar_fg_mutation_report', 'Lap Mutasi Barang Jadi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasiwip', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiwip') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiWIP"><?= lang_line('sidebar_wip_mutation_report', 'Lap Mutasi WIP'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasimesin', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimesin') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMesin"><?= lang_line('sidebar_machine_mutation_report', 'Lap Mutasi Mesin'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_accounting', 'Accounting'); ?>
    </div>

    <?php if ($canViewAccounting): ?>
    <li class="nav-item <?= $isAccountingActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isAccountingActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseAccounting"
            aria-expanded="<?= $isAccountingActive ? 'true' : 'false'; ?>" aria-controls="collapseAccounting">
            <i class="fas fa-fw fa-calculator"></i>
            <span><?= lang_line('sidebar_finance_accounting', 'Finance & Accounting'); ?></span>
        </a>
        <div id="collapseAccounting" class="collapse <?= $isAccountingActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_general_ledger', 'General Ledger:'); ?></h6>
                <?php if (check_access('coa', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'coa') ? 'active' : ''; ?>" href="<?= $base; ?>coa"><?= lang_line('sidebar_coa', 'Chart of Accounts (COA)'); ?></a>
                <?php endif; ?>
                <?php if (check_access('jurnalumum', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'jurnalumum') ? 'active' : ''; ?>" href="<?= $base; ?>jurnalumum"><?= lang_line('sidebar_general_journal', 'Jurnal Umum'); ?></a>
                <?php endif; ?>
                <?php if (check_access('apinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'apinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>apinvoice"><?= lang_line('sidebar_account_payable', 'Account Payable'); ?></a>
                <?php endif; ?>
                <?php if (check_access('settingakun', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'settingakun') ? 'active' : ''; ?>" href="<?= $base; ?>settingakun"><?= lang_line('sidebar_account_settings', 'Setting Akun'); ?></a>
                <?php endif; ?>
                <?php if (check_access('appayment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'appayment') ? 'active' : ''; ?>" href="<?= $base; ?>appayment"><?= lang_line('sidebar_payable_payment', 'Pembayaran Hutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('arinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>arinvoice"><?= lang_line('sidebar_receivable_invoice', 'Invoice Piutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('arreceipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arreceipt') ? 'active' : ''; ?>" href="<?= $base; ?>arreceipt"><?= lang_line('sidebar_receivable_receipt', 'Pembayaran Piutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('cashbank', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'cashbank') ? 'active' : ''; ?>" href="<?= $base; ?>cashbank"><?= lang_line('sidebar_cash_bank', 'KAS & Bank'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_reports', 'Laporan'); ?></h6>
                <?php if (check_access('laba_rugi', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'profit_loss') ? 'active' : ''; ?>" href="<?= $base; ?>reports/profit_loss"><?= lang_line('sidebar_profit_loss', 'Laba Rugi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('neraca', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'balance_sheet') ? 'active' : ''; ?>" href="<?= $base; ?>reports/balance_sheet"><?= lang_line('sidebar_balance_sheet', 'Neraca'); ?></a>
                <?php endif; ?>
                <?php if (check_access('trial_balance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'trial_balance') ? 'active' : ''; ?>" href="<?= $base; ?>reports/trial_balance"><?= lang_line('sidebar_trial_balance', 'Neraca Saldo'); ?></a>
                <?php endif; ?>
                <?php if (check_access('cash_flow', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'cash_flow') ? 'active' : ''; ?>" href="<?= $base; ?>reports/cash_flow"><?= lang_line('sidebar_cash_flow', 'Arus Kas'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>


    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_configuration', 'Configuration'); ?>
    </div>

    <?php if ($canViewMaster): ?>
    <li class="nav-item <?= $isMasterActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isMasterActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseMaster"
            aria-expanded="<?= $isMasterActive ? 'true' : 'false'; ?>" aria-controls="collapseMaster">
            <i class="fas fa-fw fa-database"></i>
            <span><?= lang_line('sidebar_master_data', 'Master Data'); ?></span>
        </a>
        <div id="collapseMaster" class="collapse <?= $isMasterActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                 <?php if (check_access('master_supplier', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_supplier') ? 'active' : ''; ?>" href="<?= $base; ?>master_supplier"><?= lang_line('sidebar_master_supplier', 'Master Supplier'); ?></a>
                 <?php endif; ?>
                 <?php if (check_access('master_customer', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_customer') ? 'active' : ''; ?>" href="<?= $base; ?>master_customer"><?= lang_line('sidebar_master_customer', 'Master Pelanggan'); ?></a>
                 <?php endif; ?>
                <?php if (check_access('master_material', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_material') ? 'active' : ''; ?>" href="<?= $base; ?>master_material"><?= lang_line('sidebar_master_material', 'Master Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_semifinished', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_semifinished') ? 'active' : ''; ?>" href="<?= $base; ?>master_semifinished"><?= lang_line('sidebar_master_semifinished', 'Master Semifinished'); ?></a>
                <?php endif; ?>
                 <?php if (check_access('master_scrap', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_scrap') ? 'active' : ''; ?>" href="<?= $base; ?>master_scrap"><?= lang_line('sidebar_master_scrap', 'Master Scrap'); ?></a>
                 <?php endif; ?>
                 <?php if (check_access('master_fg', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_fg') ? 'active' : ''; ?>" href="<?= $base; ?>master_fg"><?= lang_line('sidebar_master_fg', 'Master Finished Goods'); ?></a>
                 <?php endif; ?>
                 <?php if (check_access('master_machine', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_machine') ? 'active' : ''; ?>" href="<?= $base; ?>master_machine"><?= lang_line('sidebar_master_machine', 'Master Mesin'); ?></a>
                 <?php endif; ?>
                <?php if (check_access('master_inventory', 'view')): ?>
                     <a class="collapse-item <?= ($current == 'master_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>master_inventory"><?= lang_line('sidebar_master_inventory', 'Master Inventaris'); ?></a>
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
            <span><?= lang_line('sidebar_system_setup', 'System Setup'); ?></span>
        </a>
        <div id="collapseSystem" class="collapse <?= $isSystemActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if (check_access('commoncode', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'commoncode') ? 'active' : ''; ?>" href="<?= $base; ?>commoncode"><?= lang_line('sidebar_common_code', 'Common Code'); ?></a>
                <?php endif; ?>
                 <?php if (check_access('roleaccess', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'roleaccess') ? 'active' : ''; ?>" href="<?= $base; ?>roleaccess"><?= lang_line('sidebar_role_access', 'Role Access'); ?></a>
                 <?php endif; ?>
                 <?php if (check_access('user', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'user') ? 'active' : ''; ?>" href="<?= $base; ?>user"><?= lang_line('sidebar_user_management', 'User Management'); ?></a>
                 <?php endif; ?>
                 <?php if (check_access('log_activity', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'log_activity') ? 'active' : ''; ?>" 
                    href="<?= $base; ?>log_activity"><?= lang_line('sidebar_log_activity', 'Log Activity'); ?></a>
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