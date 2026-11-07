<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ambil base URL dan segment URL saat ini
$base = base_url();
$current = strtolower($this->uri->segment(1)); 

// 1. Core Operations
$order_items = ['sales_contract'];
$purchase_items = ['purchase_request', 'purchase_request_approval', 'purchase_order', 'purchase_order_approval', 'mir', 'po_report'];
$inventory_items = ['warehouse_in','quality_assurance','warehouse_out','subkon_order','subkon_out', 'subkon_in','scrap_in', 'scrap_out', 'scrap_stock',  'stock_ledger', 'stock_material', 'stock_machine', 'stock_inventory', 'initial_stock', 'adjustment', 'stockopname','material_issuance', 'prodreturn', 'stock_alert', 'consumable_out', 'consumable_outstanding', 'material_outstanding'];
$production_items = ['work_order', 'production_receive', 'production_process', 'qc_production'];
$permintaan_items = ['material_request', 'consumable_request'];
$fg_items = ['fg_receipt', 'fg_stock', 'fg_delivery'];
$exim_items = ['exim_verification'];
$it_inventory_items = ['lappemasukanbb', 'lappengeluaranbarang', 'lapmutasiscrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin']; 

// 3. Accounting
$accounting_items = ['coa', 'jurnalumum', 'apinvoice', 'prf', 'settingakun', 'appayment', 'arinvoice','arreceipt', 'neraca', 'cashbank','laba_rugi','balance_sheet', 'cash_flow', 'trial_balance', 'reports'];

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
$isPermintaanActive = in_array($current, $permintaan_items);
$isFgActive = in_array($current, $fg_items);
$isAccountingActive = in_array($current, $accounting_items);
$isMasterActive = in_array($current, $master_items);
$isSystemActive = in_array($current, $system_items);

// CEK IZIN AKSES
$canViewDashboard = check_access('dashboard', 'view');
$canViewOrder = check_access('sales_contract', 'view');
$canViewPurchase = check_access('purchase_request', 'view') || check_access('purchase_request_approval', 'view') || check_access('purchase_order', 'view') || check_access('purchase_order_approval', 'view') || check_access('mir', 'view');
$canViewInventory = check_access('warehouse_in', 'view') || check_access('quality_assurance', 'view') || check_access('stock_ledger', 'view') || check_access('stock_material', 'view') || check_access('stock_machine', 'view') || check_access('stock_inventory', 'view') || check_access('initial_stock', 'view') || check_access('material_issuance', 'view') || check_access('adjustment', 'view') || check_access('stockopname', 'view') || check_access('prodreturn', 'view') || check_access('subkon_order', 'view') || check_access('subkon_out', 'view') || check_access('subkon_in', 'view') || check_access('scrap_in', 'view') || check_access('scrap_out', 'view') || check_access('scrap_stock', 'view') || check_access('consumable_out', 'view') || check_access('consumable_outstanding', 'view') || check_access('material_outstanding', 'view') || check_access('stock_alert', 'view');
$canViewExim = check_access('exim_verification', 'view');
$canViewItInventory = check_access('Lap_Pemasukan_BB', 'view') || check_access('lap_pengeluaran_barang', 'view') || check_access('lap_mutasi_scrap', 'view') || check_access('lapmutasimaterial', 'view') || check_access('lapmutasimesin', 'view') || check_access('lapmutasiwip', 'view') || check_access('lapmutasifinished', 'view'); 
$canViewProduction = check_access('work_order', 'view') || check_access('production_receive', 'view') || check_access('production_process', 'view') || check_access('qc_production', 'view');
$canViewPermintaan = check_access('material_request', 'view') || check_access('consumable_request', 'view');
$canViewFg = check_access('fg_receipt', 'view') || check_access('fg_stock', 'view') || check_access('fg_delivery', 'view');
$canViewBom = check_access('bom', 'view');
$canViewAccounting = check_access('jurnalumum', 'view') || check_access('settingakun', 'view') || check_access('apinvoice', 'view') || check_access('prf', 'view') || check_access('appayment', 'view') || check_access('arinvoice', 'view')|| check_access('arreceipt', 'view') || check_access('coa', 'view') || check_access('laba_rugi', 'view');
$canViewMaster = check_access('master_supplier', 'view') || check_access('master_customer', 'view') || check_access('master_material', 'view') || check_access('master_semifinished', 'view') || check_access('master_scrap', 'view') || check_access('master_fg', 'view') || check_access('master_machine', 'view') || check_access('master_inventory', 'view');
$canViewSystem = check_access('commoncode', 'view') || check_access('roleaccess', 'view') || check_access('user', 'view') || check_access('log_activity', 'view');

// Conditional Section Headings
$canViewCoreOperations = $canViewOrder || $canViewPurchase || $canViewInventory || $canViewProduction || $canViewPermintaan || $canViewFg || $canViewBom || $canViewExim || $canViewItInventory;
$canViewConfiguration = $canViewMaster || $canViewSystem;
?>

<style>
/* ============================================
   SIDEBAR & CONTENT - INDEPENDENT SCROLL
   Sidebar tetap fix, content scroll sendiri
   ============================================ */

/* Hilangkan scroll di body, semua scroll internal */
html, body {
    height: 100% !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* KUNCI: Wrapper jadi flex container dengan height 100vh */
#wrapper {
    display: flex !important;
    height: 100vh !important;
    overflow: hidden !important;
}

/* SIDEBAR: Fixed height, scroll internal */
#accordionSidebar {
    background: linear-gradient(180deg, #2b46e2 0%, #0430f3 100%) !important;
    height: 100vh !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    flex-shrink: 0 !important;
    position: sticky !important;
    top: 0 !important;
}

/* CONTENT WRAPPER: Flex grow, scroll internal */
#content-wrapper {
    flex: 1 !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    height: 100vh !important;
}

/* Brand Logo */
.sidebar-brand-full {
    max-height: 60px;
    display: block;
}
.sidebar-brand-mini {
    max-height: 45px;
    display: none;
}
.sidebar.toggled .sidebar-brand-full {
    display: none;
}
.sidebar.toggled .sidebar-brand-mini {
    display: block;
}

/* Dropdown Menu - HANYA WARNA, tidak ubah layout */
.collapse-inner {
    background-color: #3949ab !important;
}
.collapse-inner .collapse-header {
    color: rgba(255, 255, 255, 0.6) !important;
}
.collapse-inner .collapse-item {
    color: rgba(255, 255, 255, 0.9) !important;
    display: block !important;
    padding: 0.5rem 1rem !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
    min-height: auto !important;
    margin: 0 !important;
}
.collapse-inner .collapse-item:hover {
    background-color: rgba(255, 255, 255, 0.15) !important;
    color: #fff !important;
}
.collapse-inner .collapse-item.active {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #fff !important;
}

/* FIX: Menu utama hover - JANGAN PUTIH */
#accordionSidebar .nav-item .nav-link:hover,
#accordionSidebar .nav-item .nav-link:focus,
#accordionSidebar .nav-item .nav-link:active {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff !important;
}

/* Desktop: Ensure collapse-inner allows text wrapping */
#accordionSidebar .collapse .collapse-inner {
    padding: 0.5rem 0 !important;
    overflow: visible !important;
    word-break: break-word !important;
}

/* Scrollbar Styling untuk Sidebar */
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

/* ============================================
   MOBILE RESPONSIVE - SIMPLE & CLEAN
   ============================================ */
@media (max-width: 768px) {
    /* Sidebar: Hidden by default, slide from left */
    #accordionSidebar {
        position: fixed !important;
        left: -16rem !important;
        top: 0 !important;
        width: 16rem !important;
        height: 100vh !important;
        z-index: 9999 !important;
        transition: left 0.3s ease !important;
        padding-top: 0.5rem !important;
    }
    
    /* Sidebar: Visible when toggled */
    #accordionSidebar.show-mobile {
        left: 0 !important;
    }
    
    /* Content: Full width */
    #content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    /* Overlay backdrop */
    .sidebar-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        display: none;
    }
    .sidebar-backdrop.show {
        display: block;
    }
    
    /* ===== MENU STYLING MOBILE ===== */
    
    /* Logo lebih kecil */
    #accordionSidebar .sidebar-brand {
        padding: 0.75rem !important;
    }
    #accordionSidebar .sidebar-brand img {
        max-height: 50px !important;
    }
    
    /* Section heading */
    #accordionSidebar .sidebar-heading {
        font-size: 0.65rem !important;
        padding: 0.5rem 1rem !important;
        margin-top: 0.25rem !important;
    }
    
    /* Menu items - horizontal layout */
    #accordionSidebar .nav-item .nav-link {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        padding: 0.6rem 1rem !important;
        font-size: 0.85rem !important;
    }
    
    #accordionSidebar .nav-item .nav-link i {
        width: 1.5rem !important;
        font-size: 0.9rem !important;
        margin-right: 0.5rem !important;
        text-align: center !important;
    }
    
    #accordionSidebar .nav-item .nav-link span {
        font-size: 0.85rem !important;
    }
    
    /* Dropdown arrow */
    #accordionSidebar .nav-link[data-toggle="collapse"]::after {
        margin-left: auto !important;
    }
    
    /* ===== DROPDOWN SUBMENU STYLING ===== */
    /* Reset SEMUA styling bawaan sb-admin-2 */
    #accordionSidebar .nav-item .collapse {
        position: relative !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 1 !important;
        margin: 0 !important;
    }

    #accordionSidebar .nav-item .collapse .collapse-inner,
    #accordionSidebar .nav-item .collapsing .collapse-inner {
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        background: rgba(0, 0, 0, 0.15) !important;
        box-shadow: none !important;
        min-width: 0 !important;
        overflow: visible !important;
        width: 100% !important;
    }
    
    /* Sembunyikan header di mobile */
    #accordionSidebar .collapse .collapse-header {
        display: none !important;
    }
    
    /* Submenu items - SEJAJAR LURUS dengan TEXT menu utama */
    #accordionSidebar .nav-item .collapse .collapse-inner .collapse-item,
    #accordionSidebar .nav-item .collapsing .collapse-inner .collapse-item {
        padding: 0.5rem 1rem !important;
        margin: 0 !important;
        font-size: 0.85rem !important;
        color: rgba(255,255,255,0.85) !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.5 !important;
        display: flex !important;
        align-items: center !important;
        min-height: auto !important;
        overflow: visible !important;
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
    }
    
    #accordionSidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
        background: rgba(255,255,255,0.1) !important;
        color: #fff !important;
    }
    
    #accordionSidebar .nav-item .collapse .collapse-inner .collapse-item.active {
        background: rgba(255,255,255,0.2) !important;
        color: #fff !important;
        font-weight: 600 !important;
    }
    
    /* Divider */
    #accordionSidebar .sidebar-divider {
        margin: 0.5rem 1rem !important;
    }
}

/* Tablet (768px - 992px) */
@media (min-width: 769px) and (max-width: 992px) {
    #accordionSidebar {
        width: 14rem !important;
    }
}
</style>

<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Mobile Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Only on mobile
    if (window.innerWidth <= 768) {
        var sidebar = document.getElementById('accordionSidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var toggleBtn = document.getElementById('sidebarToggleTop');
        
        // Toggle sidebar
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show-mobile');
                backdrop.classList.toggle('show');
            });
        }
        
        // Close on backdrop click
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('show-mobile');
                backdrop.classList.remove('show');
            });
        }
        
        // Close on menu item click (for non-dropdown items)
        var menuLinks = sidebar.querySelectorAll('.nav-link:not([data-toggle="collapse"])');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                sidebar.classList.remove('show-mobile');
                backdrop.classList.remove('show');
            });
        });
        
        // Close on submenu item click
        var subMenuLinks = sidebar.querySelectorAll('.collapse-item');
        subMenuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                sidebar.classList.remove('show-mobile');
                backdrop.classList.remove('show');
            });
        });
    }
});
</script>

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
            <span><?= lang_line('sidebar_dashboard', 'Dashboard'); ?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($canViewCoreOperations): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_core_operations', 'Core Operations'); ?>
    </div>
    <?php endif; ?>

    <?php if ($canViewOrder): ?>
    <li class="nav-item <?= $isOrderActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isOrderActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseOrder"
            aria-expanded="<?= $isOrderActive ? 'true' : 'false'; ?>" aria-controls="collapseOrder">
            <i class="fas fa-file-signature"></i>
            <span><?= lang_line('sidebar_order_management', 'Order Management'); ?></span>
        </a>
        <div id="collapseOrder" class="collapse <?= $isOrderActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
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
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('purchase_request', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_request') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request"><i class="fas fa-file-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_purchase_request', 'Purchase Request'); ?></a>
                <?php endif; ?>
                <?php if (check_access('purchase_request_approval', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_request_approval') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request_approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pr_approval', 'PR Approval'); ?></a>
                <?php endif; ?>
                <?php if (check_access('purchase_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_order') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_purchase_order', 'Purchase Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('purchase_order_approval', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'purchase_order_approval') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order_approval"><i class="fas fa-thumbs-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_po_approval', 'PO Approval'); ?></a>
                <?php endif; ?>
                <?php if (check_access('mir', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'mir') ? 'active' : ''; ?>" href="<?= $base; ?>mir"><i class="fas fa-globe" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_import_request', 'Import Request'); ?></a>
                <?php endif; ?>
                <?php if (check_access('po_report', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'po_report') ? 'active' : ''; ?>" href="<?= $base; ?>po_report"><i class="fas fa-chart-bar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_po_report', 'PO Report'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_warehouse_transactions', 'Transaksi Gudang:'); ?></h6>
                <?php if (check_access('warehouse_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'warehouse_in') ? 'active' : ''; ?>" href="<?= $base; ?>warehouse_in"><i class="fas fa-arrow-down" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_receipt', 'Penerimaan Barang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('quality_assurance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'quality_assurance' && empty($this->uri->segment(2))) ? 'active' : ''; ?>" href="<?= $base; ?>quality_assurance"><i class="fas fa-check-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_quality_assurance', 'Quality Assurance'); ?></a>
                    <a class="collapse-item <?= ($current == 'quality_assurance' && $this->uri->segment(2) == 'qa_report') ? 'active' : ''; ?>" href="<?= $base; ?>quality_assurance/qa_report"><i class="fas fa-chart-bar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qa_report', 'QA Report'); ?></a>
                <?php endif; ?>
                <?php if (check_access('warehouse_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'warehouse_out') ? 'active' : ''; ?>" href="<?= $base; ?>warehouse_out"><i class="fas fa-arrow-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_return', 'Goods Return'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_issuance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_issuance') ? 'active' : ''; ?>" href="<?= $base; ?>material_issuance"><i class="fas fa-dolly" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_issuance', 'Material Issuance'); ?></a>
                <?php endif; ?>
                <?php if (check_access('prodreturn', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'prodreturn') ? 'active' : ''; ?>" href="<?= $base; ?>ProdReturn"><i class="fas fa-undo" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_return', 'Retur Material (WIP)'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_consumable', 'Consumable'); ?></h6>
                <?php if (check_access('consumable_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'consumable_out') ? 'active' : ''; ?>" href="<?= $base; ?>consumable_out"><i class="fas fa-box-open" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_consumable_out', 'Consumable Out'); ?></a>
                <?php endif; ?>
                <?php if (check_access('consumable_outstanding', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'consumable_outstanding') ? 'active' : ''; ?>" href="<?= $base; ?>consumable_outstanding"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_consumable_outstanding', 'Outstanding Request General'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_outstanding', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_outstanding') ? 'active' : ''; ?>" href="<?= $base; ?>material_outstanding"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_outstanding', 'Outstanding Request Material'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_subcon', 'Subkon'); ?></h6>
                <?php if (check_access('subkon_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'subkon_order') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_order"><i class="fas fa-ticket-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_order', 'Subkon Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'subkon_out') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_out"><i class="fas fa-arrow-right" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_out', 'Subkon Out'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'subkon_in') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_in"><i class="fas fa-arrow-left" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_in', 'Subkon In'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_warehouse_reports', 'Laporan Gudang:'); ?></h6>
                <?php if (check_access('stock_ledger', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stock_ledger') ? 'active' : ''; ?>" href="<?= $base; ?>stock_ledger"><?= lang_line('sidebar_stock_report', 'Laporan Stok (All)'); ?></a>
                <?php endif; ?>
                <?php if (check_access('stock_material', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stock_material') ? 'active' : ''; ?>" href="<?= $base; ?>stock_material"><i class="fas fa-cubes" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_material_semifinished', 'Stock Material & Semifinished'); ?></a>
                <?php endif; ?>
                <?php if (check_access('stock_machine', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stock_machine') ? 'active' : ''; ?>" href="<?= $base; ?>stock_machine"><i class="fas fa-cog" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_machine', 'Stock Machine'); ?></a>
                <?php endif; ?>
                <?php if (check_access('stock_inventory', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stock_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>stock_inventory"><i class="fas fa-boxes" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_inventory', 'Stock Inventory'); ?></a>
                <?php endif; ?>
                <?php if (check_access('initial_stock', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'initial_stock') ? 'active' : ''; ?>" href="<?= $base; ?>initial_stock"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('initial_stock_title', 'Input Stock Awal'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_scrap_transactions', 'Transaksi Scrap:'); ?></h6>
                <?php if (check_access('scrap_in', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_in') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_In"><i class="fas fa-trash-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_receipt', 'Penerimaan Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_out', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_out') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Out"><i class="fas fa-trash" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_out', 'Pengeluaran Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_stock', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'scrap_stock') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Stock"><i class="fas fa-chart-line" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_stock_report', 'Laporan Stok Scrap'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_alerts_monitoring', 'Monitoring & Alerts:'); ?></h6>
                <?php if (check_access('stock_alert', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stock_alert') ? 'active' : ''; ?>" href="<?= $base; ?>stock_alert"><i class="fas fa-bell" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_alert', 'Stock Alert'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_adjustment_opname', 'Adjustment & Opname:'); ?></h6>
                <?php if (check_access('adjustment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'adjustment') ? 'active' : ''; ?>" href="<?= $base; ?>Adjustment"><i class="fas fa-sliders-h" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_adjustment', 'Penyesuaian Stok'); ?></a>
                <?php endif; ?>
                <?php if (check_access('stockopname', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'stockopname') ? 'active' : ''; ?>" href="<?= $base; ?>stockopname"><i class="fas fa-clipboard-check" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_opname', 'Stock Opname'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>
    
    <?php if ($canViewPermintaan): ?>
    <li class="nav-item <?= $isPermintaanActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isPermintaanActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapsePermintaan"
            aria-expanded="<?= $isPermintaanActive ? 'true' : 'false'; ?>" aria-controls="collapsePermintaan">
            <i class="fas fa-clipboard-list"></i>
            <span><?= lang_line('sidebar_permintaan_barang', 'Permintaan Barang'); ?></span>
        </a>
        <div id="collapsePermintaan" class="collapse <?= $isPermintaanActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('material_request', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'material_request') ? 'active' : ''; ?>" href="<?= $base; ?>material_request"><i class="fas fa-industry" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_produksi', 'A. Produksi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('consumable_request', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'consumable_request') ? 'active' : ''; ?>" href="<?= $base; ?>consumable_request"><i class="fas fa-pencil-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_non_produksi', 'B. Non - Produksi'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('work_order', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'work_order') ? 'active' : ''; ?>" href="<?= $base; ?>work_order"><i class="fas fa-tasks" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_work_order', 'Work Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_receive', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'production_receive') ? 'active' : ''; ?>" href="<?= $base; ?>production_receive"><i class="fas fa-inbox" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_production_receipt', 'Penerimaan Produksi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_process', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'production_process') ? 'active' : ''; ?>" href="<?= $base; ?>production_process"><i class="fas fa-cog" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_production_process', 'Proses Produksi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('qc_production', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'qc_production') ? 'active' : ''; ?>" href="<?= $base; ?>qc_production"><i class="fas fa-check-square" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qc_production', 'QC Production'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('fg_receipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_receipt') ? 'active' : ''; ?>" href="<?= $base; ?>fg_receipt"><i class="fas fa-carton" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_receipt', 'Penerimaan FG'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_stock', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_stock') ? 'active' : ''; ?>" href="<?= $base; ?>fg_stock"><i class="fas fa-book" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_stock', 'Kartu Stok FG'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_delivery', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'fg_delivery') ? 'active' : ''; ?>" href="<?= $base; ?>fg_delivery"><i class="fas fa-shipping-fast" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_delivery', 'Pengeluaran FG'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_customs_verification', 'Verifikasi Bea Cukai:'); ?></h6>
                <a class="collapse-item" href="<?= $base; ?>exim_verification"><i class="fas fa-tachometer-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_dashboard', 'Dashboard'); ?></a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/incoming"><i class="fas fa-arrow-down" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_incoming', 'Pemasukan Barang'); ?></a>
                <a class="collapse-item" href="<?= $base; ?>exim_verification/outgoing"><i class="fas fa-arrow-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_outgoing', 'Pengeluaran Barang'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_customs_reports', 'Laporan Bea Cukai:'); ?></h6>
                <?php if (check_access('Lap_Pemasukan_BB', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappemasukanbb') ? 'active' : ''; ?>" href="<?= $base; ?>LapPemasukanBB"><i class="fas fa-file-invoice" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_incoming_report', 'Lap Pemasukan Barang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lap_pengeluaran_barang', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lappengeluaranbarang') ? 'active' : ''; ?>" href="<?= $base; ?>LapPengeluaranBarang"><i class="fas fa-file-csv" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_outgoing_report', 'Lap Pengeluaran Barang'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_mutation_reports', 'Laporan Mutasi:'); ?></h6>
                <?php if (check_access('lap_mutasi_scrap', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiscrap') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiScrap"><i class="fas fa-recycle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_mutation_report', 'Lap Mutasi Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasimaterial', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimaterial') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMaterial"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_mutation_report', 'Lap Mutasi Bahan Baku'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasifinished', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasifinished') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiFinished"><i class="fas fa-box" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_mutation_report', 'Lap Mutasi Barang Jadi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasiwip', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasiwip') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiWIP"><i class="fas fa-hourglass" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_wip_mutation_report', 'Lap Mutasi WIP'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lapmutasimesin', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'lapmutasimesin') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMesin"><i class="fas fa-wrench" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_machine_mutation_report', 'Lap Mutasi Mesin'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewAccounting): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_accounting', 'Accounting'); ?>
    </div>

    <li class="nav-item <?= $isAccountingActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isAccountingActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseAccounting"
            aria-expanded="<?= $isAccountingActive ? 'true' : 'false'; ?>" aria-controls="collapseAccounting">
            <i class="fas fa-fw fa-calculator"></i>
            <span><?= lang_line('sidebar_finance_accounting', 'Finance & Accounting'); ?></span>
        </a>
        <div id="collapseAccounting" class="collapse <?= $isAccountingActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <h6 class="collapse-header"><?= lang_line('sidebar_general_ledger', ' Accounting Entry '); ?></h6>
                <?php if (check_access('jurnalumum', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'jurnalumum') ? 'active' : ''; ?>" href="<?= $base; ?>jurnalumum"><i class="fas fa-journal-whills" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_general_journal', 'Jurnal Umum'); ?></a>
                <?php endif; ?>
                <?php if (check_access('apinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'apinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>apinvoice"><i class="fas fa-file-invoice-dollar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_account_payable', 'Account Payable'); ?></a>
                <?php endif; ?>
                <?php if (check_access('prf', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'prf') ? 'active' : ''; ?>" href="<?= $base; ?>prf"><i class="fas fa-file-invoice" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_payment_requisition', 'Payment Requisition Form'); ?></a>
                <?php endif; ?>
                <?php if (check_access('appayment', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'appayment') ? 'active' : ''; ?>" href="<?= $base; ?>appayment"><i class="fas fa-money-bill-wave" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_payable_payment', 'Pembayaran Hutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('arinvoice', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>arinvoice"><i class="fas fa-receipt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_receivable_invoice', 'Invoice Piutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('arreceipt', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'arreceipt') ? 'active' : ''; ?>" href="<?= $base; ?>arreceipt"><i class="fas fa-hand-holding-usd" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_receivable_receipt', 'Pembayaran Piutang'); ?></a>
                <?php endif; ?>
                <?php if (check_access('cashbank', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'cashbank') ? 'active' : ''; ?>" href="<?= $base; ?>cashbank"><i class="fas fa-university" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_cash_bank', 'KAS & Bank'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_reports', 'Laporan'); ?></h6>
                <?php if (check_access('laba_rugi', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'profit_loss') ? 'active' : ''; ?>" href="<?= $base; ?>reports/profit_loss"><i class="fas fa-chart-pie" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_profit_loss', 'Laba Rugi'); ?></a>
                <?php endif; ?>
                <?php if (check_access('neraca', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'balance_sheet') ? 'active' : ''; ?>" href="<?= $base; ?>reports/balance_sheet"><i class="fas fa-balance-scale" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_balance_sheet', 'Neraca'); ?></a>
                <?php endif; ?>
                <?php if (check_access('trial_balance', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'trial_balance') ? 'active' : ''; ?>" href="<?= $base; ?>reports/trial_balance"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_trial_balance', 'Neraca Saldo'); ?></a>
                <?php endif; ?>
                <?php if (check_access('cash_flow', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'reports' && $this->uri->segment(2) == 'cash_flow') ? 'active' : ''; ?>" href="<?= $base; ?>reports/cash_flow"><i class="fas fa-water" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_cash_flow', 'Arus Kas'); ?></a>
                <?php endif; ?>
                <h6 class="collapse-header"><?= lang_line('sidebar_setting', 'Setting'); ?></h6>
                <?php if (check_access('coa', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'coa') ? 'active' : ''; ?>" href="<?= $base; ?>coa"><i class="fas fa-sitemap" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_coa', 'Chart of Accounts (COA)'); ?></a>
                <?php endif; ?>
                <?php if (check_access('settingakun', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'settingakun') ? 'active' : ''; ?>" href="<?= $base; ?>settingakun"><i class="fas fa-sliders-h" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_account_settings', 'Setting Akun'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <?php if ($canViewConfiguration): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        <?= lang_line('sidebar_configuration', 'Configuration'); ?>
    </div>
    <?php endif; ?>

    <?php if ($canViewMaster): ?>
    <li class="nav-item <?= $isMasterActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isMasterActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseMaster"
            aria-expanded="<?= $isMasterActive ? 'true' : 'false'; ?>" aria-controls="collapseMaster">
            <i class="fas fa-fw fa-database"></i>
            <span><?= lang_line('sidebar_master_data', 'Master Data'); ?></span>
        </a>
        <div id="collapseMaster" class="collapse <?= $isMasterActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('master_supplier', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_supplier') ? 'active' : ''; ?>" href="<?= $base; ?>master_supplier"><i class="fas fa-truck" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_supplier', 'Master Supplier'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_customer', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_customer') ? 'active' : ''; ?>" href="<?= $base; ?>master_customer"><i class="fas fa-users" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_customer', 'Master Pelanggan'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_material', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_material') ? 'active' : ''; ?>" href="<?= $base; ?>master_material"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_material', 'Master Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_semifinished', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_semifinished') ? 'active' : ''; ?>" href="<?= $base; ?>master_semifinished"><i class="fas fa-industry" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_semifinished', 'Master Semifinished'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_scrap', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_scrap') ? 'active' : ''; ?>" href="<?= $base; ?>master_scrap"><i class="fas fa-recycle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_scrap', 'Master Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_fg', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_fg') ? 'active' : ''; ?>" href="<?= $base; ?>master_fg"><i class="fas fa-cube" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_fg', 'Master Finished Goods'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_machine', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_machine') ? 'active' : ''; ?>" href="<?= $base; ?>master_machine"><i class="fas fa-tools" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_machine', 'Master Mesin'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_inventory', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'master_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>master_inventory"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_inventory', 'Master HRGA'); ?></a>
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
            <div class="py-2 collapse-inner rounded">
                <?php if (check_access('commoncode', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'commoncode') ? 'active' : ''; ?>" href="<?= $base; ?>commoncode"><i class="fas fa-code" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_common_code', 'Common Code'); ?></a>
                <?php endif; ?>
                <?php if (check_access('roleaccess', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'roleaccess') ? 'active' : ''; ?>" href="<?= $base; ?>roleaccess"><i class="fas fa-lock" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_role_access', 'Role Access'); ?></a>
                <?php endif; ?>
                <?php if (check_access('user', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'user') ? 'active' : ''; ?>" href="<?= $base; ?>user"><i class="fas fa-user-tie" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_user_management', 'User Management'); ?></a>
                <?php endif; ?>
                <?php if (check_access('log_activity', 'view')): ?>
                    <a class="collapse-item <?= ($current == 'log_activity') ? 'active' : ''; ?>" href="<?= $base; ?>log_activity"><i class="fas fa-history" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_log_activity', 'Log Activity'); ?></a>
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
