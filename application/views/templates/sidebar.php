<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ambil base URL dan segment URL saat ini
$base = base_url();
$current = strtolower($this->uri->segment(1));
$current_action = strtolower($this->uri->segment(2));

// 1. Core Operations
$order_items = ['sales_contract', 'sales_contract_approval', 'sales_contract_list']; // IPO Number
$purchase_items = ['purchase_order', 'purchase_order_approval', 'pmpo_status', 'purchase_order_general', 'purchase_order_approval_general', 'gmpo_status', 'purchase_order_jasa', 'purchase_order_approval_jasa', 'purchase_order_asset', 'purchase_order_approval_asset', 'po_report', 'pmpo_list', 'gmpo_list', 'po_monitoring', 'pr_monitoring'];
$inventory_items = ['goods_receiving','goods_issuance','warehouse_in','warehouse_out','supplier_general_item_receipt','material_issuance','consumable_out','stock_transfer','purchase_request','purchase_request_approval','purchase_request_general','purchase_request_approval_general','purchase_request_jasa','purchase_request_approval_jasa','purchase_request_asset','purchase_request_approval_asset','stock_material','stock_inventory','stock_machine','stock_ledger','initial_stock','adjustment','stockopname','prodreturn','stock_alert','material_traceability','inventory_traceability'];
$production_items = ['work_order','material_request', 'production_receive', 'production_process', 'production_qc', 'qc_production', 'bom'];
$permintaan_items = ['material_request_approval', 'material_outstanding', 'consumable_request', 'consumable_request_approval', 'consumable_outstanding', 'irf_status'];
$qa_items = ['quality_assurance', 'qa_report', 'qa_sampling', 'qa_sampling_report', 'material_ng_report'];
$scrap_items = ['scrap_in', 'scrap_stock', 'scrap_out'];
$subkon_items = ['subkon_order', 'subkon_out', 'subkon_in'];
$fg_items = ['fg_receipt', 'fg_receipt_approval', 'fg_stock'];
// 'mir' & 'fg_delivery' dipindah ke sini dari Purchasing/Finished Goods
// (user 2026-09-06) — keduanya memang urusan pengiriman & impor-ekspor.
$exim_items = ['exim_verification', 'mir', 'fg_delivery'];
$it_inventory_items = ['lappemasukanbb', 'lappengeluaranbarang', 'lapmutasiscrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin'];

// 3. Accounting
$accounting_items = ['coa', 'jurnalumum', 'summary_ledger', 'voucher_disbursement_list', 'apinvoice', 'hutang', 'piutang', 'prf', 'prf_approval', 'prf_payment', 'prf_list', 'erf', 'erf_approval', 'erf_payment', 'erf_list', 'settingakun', 'appayment', 'arinvoice','arreceipt', 'neraca', 'cashbank','laba_rugi','balance_sheet', 'cash_flow', 'trial_balance', 'reports', 'stockopname', 'adjustment', 'kurs_pajak'];

// 2. Configuration
$master_items = ['master_supplier', 'master_customer', 'master_material', 'master_semifinished', 'master_scrap', 'master_fg', 'master_machine', 'master_inventory', 'master_karyawan', 'master_jasa', 'master_asset'];
$system_items = ['commoncode', 'roleaccess', 'user','log_activity','developer_mode'];

// CEK STATUS 'ACTIVE'
$isOrderActive = in_array($current, $order_items);
$isPurchaseActive = in_array($current, $purchase_items);
$isInventoryActive = in_array($current, $inventory_items); 
$isEximActive = in_array($current, $exim_items);
$isItInventoryActive = in_array($current, $it_inventory_items);
$isMrApprovalRoute = ($current == 'material_request' && $current_action == 'approval');
$isProductionActive = in_array($current, $production_items) && !$isMrApprovalRoute;
$isPermintaanActive = in_array($current, $permintaan_items) || $isMrApprovalRoute || $current == 'material_outstanding';
$isQaActive = in_array($current, $qa_items);
$isScrapActive = in_array($current, $scrap_items);
$isSubkonActive = in_array($current, $subkon_items);
$isFgActive = in_array($current, $fg_items);
$isAccountingActive = in_array($current, $accounting_items);
$isMasterActive = in_array($current, $master_items);
$isSystemActive = in_array($current, $system_items);

// CEK IZIN AKSES
// Dashboard TIDAK ditampilkan sbg menu sidebar (user 2026-11-09: "cukup klik logo
// saja masuk ke dashboard") -- logo di atas sidebar menautkan ke /dashboard.
$canViewOrder = check_access('sales_contract', 'view') || check_access('sales_contract_approval', 'view') || check_access('sales_contract_list', 'view');
$canViewPurchase = check_access('purchase_order', 'view') || check_access('purchase_order_approval', 'view') || check_access('pmpo_status', 'view') || check_access('purchase_order_general', 'view') || check_access('purchase_order_approval_general', 'view') || check_access('gmpo_status', 'view') || check_access('purchase_order_jasa', 'view') || check_access('purchase_order_approval_jasa', 'view') || check_access('purchase_order_asset', 'view') || check_access('purchase_order_approval_asset', 'view') || check_access('po_report', 'view') || check_access('pmpo_list', 'view') || check_access('gmpo_list', 'view') || check_access('po_monitoring', 'view') || check_access('pr_monitoring', 'view');
$canViewInventory = check_access('warehouse_in', 'view') || check_access('warehouse_out', 'view') || check_access('supplier_general_item_receipt', 'view') || check_access('supplier_general_item_receipt_general', 'view') || check_access('supplier_general_item_receipt_produksi', 'view') || check_access('material_issuance', 'view') || check_access('consumable_out', 'view') || check_access('consumable_out_general', 'view') || check_access('consumable_out_produksi', 'view') || check_access('stock_transfer', 'view') || check_access('purchase_request', 'view') || check_access('purchase_request_approval', 'view') || check_access('purchase_request_general', 'view') || check_access('purchase_request_approval_general', 'view') || check_access('purchase_request_jasa', 'view') || check_access('purchase_request_approval_jasa', 'view') || check_access('purchase_request_asset', 'view') || check_access('purchase_request_approval_asset', 'view') || check_access('stock_material', 'view') || check_access('stock_inventory', 'view') || check_access('stock_machine', 'view') || check_access('stock_ledger', 'view') || check_access('initial_stock', 'view') || check_access('adjustment', 'view') || check_access('stockopname', 'view') || check_access('prodreturn', 'view') || check_access('stock_alert', 'view') || check_access('material_traceability', 'view') || check_access('inventory_traceability', 'view');
$canViewExim = check_access('exim_verification', 'view') || check_access('mir', 'view') || check_access('fg_delivery', 'view');
$canViewItInventory = check_access('Lap_Pemasukan_BB', 'view') || check_access('lap_pengeluaran_barang', 'view') || check_access('lap_mutasi_scrap', 'view') || check_access('lapmutasimaterial', 'view') || check_access('lapmutasimesin', 'view') || check_access('lapmutasiwip', 'view') || check_access('lapmutasifinished', 'view'); 
$canViewProduction = check_access('work_order', 'view') || check_access('material_request', 'view') || check_access('material_request_approval', 'view') || check_access('material_outstanding', 'view') || check_access('production_receive', 'view') || check_access('production_process', 'view') || check_access('production_qc', 'view') || check_access('qc_production', 'view') || check_access('bom', 'view');
$canViewPermintaan = check_access('consumable_request', 'view') || check_access('consumable_request_approval', 'view')
    || check_access('consumable_outstanding', 'view') || check_access('consumable_outstanding_general', 'view') || check_access('consumable_outstanding_produksi', 'view');
$canViewQa = check_access('quality_assurance', 'view') || check_access('qa_report', 'view') || check_access('qa_sampling', 'view') || check_access('qa_sampling_report', 'view') || check_access('material_ng_report', 'view');
$canViewScrap = check_access('scrap_in', 'view') || check_access('scrap_stock', 'view') || check_access('scrap_out', 'view');
$canViewSubkon = check_access('subkon_order', 'view') || check_access('subkon_out', 'view') || check_access('subkon_in', 'view');
$canViewFg = check_access('fg_receipt', 'view') || check_access('fg_receipt_approval', 'view') || check_access('fg_stock', 'view');
$canViewBom = check_access('bom', 'view');
$canViewAccounting = check_access('jurnalumum', 'view') || check_access('summary_ledger', 'view') || check_access('voucher_disbursement_list', 'view') || check_access('settingakun', 'view') || check_access('apinvoice', 'view') || check_access('hutang', 'view') || check_access('piutang', 'view') || check_access('prf', 'view') || check_access('prf_approval', 'view') || check_access('prf_payment', 'view') || check_access('prf_list', 'view') || check_access('erf', 'view') || check_access('erf_approval', 'view') || check_access('erf_payment', 'view') || check_access('erf_list', 'view') || check_access('appayment', 'view') || check_access('arinvoice', 'view')|| check_access('arreceipt', 'view') || check_access('cashbank', 'view') || check_access('coa', 'view') || check_access('laba_rugi', 'view') || check_access('neraca', 'view') || check_access('trial_balance', 'view') || check_access('cash_flow', 'view') || check_access('stockopname', 'view') || check_access('adjustment', 'view') || check_access('kurs_pajak', 'view');
$canViewMaster = check_access('master_supplier', 'view') || check_access('master_customer', 'view') || check_access('master_material', 'view') || check_access('master_semifinished', 'view') || check_access('master_scrap', 'view') || check_access('master_fg', 'view') || check_access('master_machine', 'view') || check_access('master_inventory', 'view') || check_access('master_karyawan', 'view') || check_access('master_jasa', 'view') || check_access('master_asset', 'view');
$canViewSystem = check_access('commoncode', 'view') || check_access('roleaccess', 'view') || check_access('user', 'view') || check_access('log_activity', 'view') || check_access('developer_mode', 'view');
$canViewDocSharing = check_access('document_sharing', 'view');

// Conditional Section Headings
$canViewCoreOperations = $canViewOrder || $canViewPurchase || $canViewInventory || $canViewProduction || $canViewPermintaan || $canViewFg || $canViewQa || $canViewScrap || $canViewSubkon || $canViewExim || $canViewItInventory;
$canViewConfiguration = $canViewMaster || $canViewSystem;
?>

<style>
/* =====================================================================
   SIDEBAR ACROPOLY -- redesign 2026-11-09.
   Riwayat masukan user: "rombak total agar modern dan profesional" ->
   "ga suka warna gelap, kurang suka point biru, suka agak sederhana" ->
   "kurang modern". Hasil: tema TERANG & sederhana gaya dashboard modern:
   logo + nama aplikasi, kotak "Cari menu", ikon di dalam kotak bulat,
   menu aktif = kartu putih + ikon charcoal (--brand-1), TANPA titik/garis
   aksen biru. Menu dikelompokkan heading (sidebar_section_groups()).
   Struktur SB Admin 2 tetap: .nav-item/.nav-link, .collapse-inner/
   .collapse-item, mode ciut (.toggled) & slide di mobile. Warna gelap hanya
   bila user memilih "Dark Mode" di topbar.
   ===================================================================== */

/* ---------- Scroll terpisah: sidebar & konten ---------- */
html, body { height: 100% !important; overflow: hidden !important; margin: 0 !important; padding: 0 !important; }
#wrapper { display: flex !important; height: 100vh !important; overflow: hidden !important; }
#content-wrapper { flex: 1 !important; overflow-y: auto !important; overflow-x: hidden !important; height: 100vh !important; }

#accordionSidebar {
    --sb-bg: #f7f8fa; --sb-card: #ffffff; --sb-line: #eaecf0; --sb-chip-line: #e6e9ee;
    --sb-text: #344054; --sb-strong: #101828; --sb-icon: #667085; --sb-head: #98a2b3; --sb-sub: #667085;
    --sb-hover: #eef0f3; --sb-sub-active: #eaedf1; --sb-accent: var(--brand-1, #334155);
    height: 100vh !important; overflow-y: auto !important; overflow-x: hidden !important;
    flex-shrink: 0 !important; position: sticky !important; top: 0 !important;
    background: var(--sb-bg) !important; background-image: none !important;
    border-right: 1px solid var(--sb-line) !important; box-shadow: none !important;
    padding-bottom: 1.5rem;
}
body.dark-mode #accordionSidebar {
    --sb-bg: #0f141c; --sb-card: #1a212c; --sb-line: rgba(255,255,255,.07); --sb-chip-line: rgba(255,255,255,.08);
    --sb-text: #cfd6e0; --sb-strong: #f8fafc; --sb-icon: #8b95a5; --sb-head: #6b7584; --sb-sub: #98a2b3;
    --sb-hover: rgba(255,255,255,.05); --sb-sub-active: rgba(255,255,255,.08); --sb-accent: #e2e8f0;
}
@media (min-width: 768px) { #accordionSidebar:not(.toggled) { width: 16rem !important; } }
/* sidebar = flex-column dgn tinggi 100vh: anak langsung jangan ikut menyusut (logo sempat terjepit jadi 33px) */
#accordionSidebar > * { flex-shrink: 0 !important; }
#accordionSidebar::-webkit-scrollbar { width: 6px; }
#accordionSidebar::-webkit-scrollbar-track { background: transparent; }
#accordionSidebar::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
#accordionSidebar:hover::-webkit-scrollbar-thumb { background: #d0d5dd; }
#accordionSidebar .sidebar-divider { display: none !important; }

/* ---------- Logo + nama aplikasi ---------- */
#accordionSidebar .sidebar-brand {
    display: flex !important; align-items: center; justify-content: flex-start; gap: .7rem;
    height: auto !important; min-height: 0 !important; padding: 1.1rem 1.1rem .8rem !important;
    background: var(--sb-bg) !important; text-align: left !important; text-transform: none !important;
    letter-spacing: 0 !important; line-height: 1.2 !important; text-decoration: none !important;
    position: sticky; top: 0; z-index: 3;
}
#accordionSidebar .sb-logo {
    width: 40px; height: 40px; min-width: 40px; border-radius: 50%; overflow: hidden; background: #fff;
    box-shadow: 0 0 0 1px var(--sb-chip-line), 0 2px 6px rgba(16,24,40,.08);
    display: flex; align-items: center; justify-content: center;
}
#accordionSidebar .sb-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
#accordionSidebar .sb-brand-text { display: flex; flex-direction: column; min-width: 0; }
#accordionSidebar .sb-brand-name { color: var(--sb-strong); font-size: .92rem; font-weight: 700; letter-spacing: -.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#accordionSidebar .sb-brand-sub { color: var(--sb-head); font-size: .66rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
#accordionSidebar.toggled .sidebar-brand { justify-content: center; padding: 1rem .5rem .8rem !important; }
#accordionSidebar.toggled .sb-brand-text { display: none; }

/* ---------- Cari menu ---------- */
#accordionSidebar .sb-search { position: relative; margin: .1rem .9rem .35rem; }
#accordionSidebar .sb-search > i { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); font-size: .75rem; color: var(--sb-head); pointer-events: none; }
#accordionSidebar .sb-search input {
    width: 100%; height: 36px; padding: 0 2rem 0 2.05rem; font-size: .8rem; color: var(--sb-strong);
    background: var(--sb-card); border: 1px solid var(--sb-chip-line); border-radius: 10px; outline: none;
    box-shadow: 0 1px 2px rgba(16,24,40,.04); transition: border-color .15s ease, box-shadow .15s ease;
}
#accordionSidebar .sb-search input::placeholder { color: var(--sb-head); }
#accordionSidebar .sb-search input:focus { border-color: #c4cad4; box-shadow: 0 0 0 3px rgba(var(--brand-rgb, 51,65,85), .08); }
#accordionSidebar .sb-search-clear {
    position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); display: none;
    width: 24px; height: 24px; border: 0; border-radius: 6px; background: transparent; color: var(--sb-head); font-size: 1rem; line-height: 1;
}
#accordionSidebar .sb-search-clear:hover { background: var(--sb-hover); color: var(--sb-strong); }
#accordionSidebar .sb-search-empty { display: none; padding: 1.25rem 1.2rem; font-size: .78rem; color: var(--sb-head); text-align: center; }
#accordionSidebar.toggled .sb-search, #accordionSidebar.toggled .sb-search-empty { display: none !important; }

/* ---------- Heading kelompok ---------- */
#accordionSidebar .sidebar-heading {
    color: var(--sb-head) !important; opacity: 1 !important;
    font-size: calc(.64rem * var(--sb-font-scale, 1)) !important; font-weight: 600 !important;
    letter-spacing: .08em !important; text-transform: uppercase !important;
    padding: 1.15rem 1.35rem .4rem !important; margin: 0 !important; border: 0 !important; white-space: nowrap;
}
#accordionSidebar.toggled .sidebar-heading { text-align: center; font-size: .52rem !important; letter-spacing: .03em !important; padding: .9rem .2rem .3rem !important; white-space: normal; }

/* ---------- Menu utama ---------- */
#accordionSidebar .nav-item { padding: 0 .7rem; margin: 2px 0; }
#accordionSidebar .nav-item .nav-link {
    color: var(--sb-text) !important; font-weight: 500 !important; border-radius: 10px;
    border: 0 !important; box-shadow: none !important; background: transparent;
    transition: background .15s ease, color .15s ease, box-shadow .15s ease;
}
#accordionSidebar:not(.toggled) .nav-item .nav-link {
    display: flex !important; align-items: center; gap: .65rem; width: 100% !important;
    padding: .34rem .5rem !important; font-size: calc(.845rem * var(--sb-font-scale, 1)) !important; line-height: 1.3;
}
/* ikon di dalam kotak bulat */
#accordionSidebar .nav-item .nav-link i {
    display: inline-flex !important; align-items: center; justify-content: center;
    width: 30px !important; height: 30px; min-width: 30px; margin: 0 !important; border-radius: 8px;
    background: var(--sb-card); box-shadow: inset 0 0 0 1px var(--sb-chip-line);
    color: var(--sb-icon) !important; font-size: .8rem !important; transition: all .15s ease;
}
#accordionSidebar:not(.toggled) .nav-item .nav-link span { flex: 1; min-width: 0; font-size: inherit !important; }
#accordionSidebar .nav-item .nav-link:hover,
#accordionSidebar .nav-item .nav-link:focus { background: var(--sb-hover) !important; color: var(--sb-strong) !important; }
#accordionSidebar .nav-item .nav-link:hover i { color: var(--sb-strong) !important; }
#accordionSidebar .nav-item .nav-link[data-toggle="collapse"]:not(.collapsed) { color: var(--sb-strong) !important; }
/* menu aktif: kartu putih + ikon charcoal */
#accordionSidebar .nav-item.active > .nav-link {
    background: var(--sb-card) !important; color: var(--sb-strong) !important; font-weight: 600 !important;
    box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 0 0 1px var(--sb-chip-line) !important;
}
#accordionSidebar .nav-item.active > .nav-link i {
    background: var(--sb-accent); box-shadow: none; color: #fff !important;
}
body.dark-mode #accordionSidebar .nav-item.active > .nav-link i { color: #0f141c !important; }
/* panah buka/tutup */
#accordionSidebar .nav-item .nav-link[data-toggle="collapse"]::after {
    float: none !important; margin-left: auto; width: auto !important; font-size: .62rem; color: var(--sb-head) !important;
    content: '\f107'; font-family: 'Font Awesome 5 Free'; font-weight: 900; transition: transform .2s ease;
}
#accordionSidebar .nav-item .nav-link[data-toggle="collapse"].collapsed::after { content: '\f107'; transform: rotate(-90deg); }

/* ---------- Submenu: panah › + ikon tiap item (user 2026-11-09: "jangan polos, harusnya ada icon panah") ---------- */
#accordionSidebar .nav-item .collapse,
#accordionSidebar .nav-item .collapsing { margin: 0 !important; }
#accordionSidebar:not(.toggled) .nav-item .collapse .collapse-inner,
#accordionSidebar:not(.toggled) .nav-item .collapsing .collapse-inner {
    background: transparent !important; box-shadow: none !important; border-radius: 0 !important; border: 0 !important;
    border-left: 1px solid var(--sb-line) !important;
    margin: .2rem 0 .45rem 1.45rem !important; padding: 0 0 0 .5rem !important; min-width: 0 !important;
}
#accordionSidebar .collapse-inner .collapse-header {
    color: var(--sb-head) !important; font-size: calc(.6rem * var(--sb-font-scale, 1)) !important; font-weight: 600;
    letter-spacing: .07em; text-transform: uppercase; padding: .55rem .55rem .2rem !important; margin: 0 !important;
}
#accordionSidebar .collapse-inner .collapse-item {
    display: flex !important; align-items: baseline; gap: .45rem; position: relative; color: var(--sb-sub) !important; background: transparent !important;
    font-size: calc(.8rem * var(--sb-font-scale, 1)) !important; font-weight: 500; line-height: 1.35 !important;
    padding: .36rem .55rem !important; margin: 1px 0 !important; border-radius: 7px; white-space: normal !important;
    word-break: break-word; box-shadow: none !important; transition: background .15s ease, color .15s ease;
}
#accordionSidebar .collapse-inner .collapse-item::after { content: none !important; }
#accordionSidebar .collapse-inner .collapse-item::before {
    content: '\f105' !important; font-family: 'Font Awesome 5 Free'; font-weight: 900; font-size: .62rem;
    position: static !important; color: var(--sb-head); width: .45rem; min-width: .45rem; flex-shrink: 0;
    transition: transform .15s ease, color .15s ease;
}
#accordionSidebar .collapse-inner .collapse-item i {
    display: inline-block !important; width: .95rem; min-width: .95rem; flex-shrink: 0; text-align: center;
    margin: 0 !important; font-size: .72rem !important; color: var(--sb-icon); opacity: .85;
}
#accordionSidebar .collapse-inner .collapse-item:hover { background: var(--sb-hover) !important; color: var(--sb-strong) !important; }
#accordionSidebar .collapse-inner .collapse-item:hover::before { color: var(--sb-strong); transform: translateX(2px); }
#accordionSidebar .collapse-inner .collapse-item:hover i { color: var(--sb-strong); opacity: 1; }
#accordionSidebar .collapse-inner .collapse-item.active {
    background: var(--sb-sub-active) !important; color: var(--sb-strong) !important; font-weight: 600 !important;
}
#accordionSidebar .collapse-inner .collapse-item.active::before,
#accordionSidebar .collapse-inner .collapse-item.active i { color: var(--sb-accent); opacity: 1; }

/* ---------- Mode ciut (desktop): kotak ikon + label kecil, submenu melayang ---------- */
#accordionSidebar.toggled .nav-item { padding: 0 .45rem; }
#accordionSidebar.toggled .nav-item .nav-link {
    display: flex !important; flex-direction: column; align-items: center; gap: .3rem;
    padding: .5rem .2rem !important; width: 100% !important; text-align: center !important;
}
#accordionSidebar.toggled .nav-item .nav-link span { font-size: .6rem !important; line-height: 1.2; color: inherit; }
#accordionSidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after { display: none !important; }
#accordionSidebar.toggled .nav-item .collapse .collapse-inner {
    background: var(--sb-card) !important; border: 1px solid var(--sb-line) !important; border-radius: 12px !important;
    box-shadow: 0 12px 32px rgba(16,24,40,.12) !important; padding: .4rem !important; margin: 0 !important; min-width: 13rem;
}

/* ---------- Mobile: slide dari kiri ---------- */
@media (max-width: 768px) {
    #accordionSidebar {
        position: fixed !important; left: -17rem !important; top: 0 !important; width: 17rem !important;
        height: 100vh !important; z-index: 9999 !important; transition: left .3s ease !important;
    }
    #accordionSidebar.show-mobile { left: 0 !important; box-shadow: 0 0 40px rgba(16,24,40,.18) !important; }
    #content-wrapper { margin-left: 0 !important; width: 100% !important; }
    .sidebar-backdrop { position: fixed; inset: 0; background: rgba(16,24,40,.45); z-index: 9998; display: none; }
    .sidebar-backdrop.show { display: block; }
    #accordionSidebar .sidebar-brand { justify-content: flex-start !important; }
    #accordionSidebar .sb-brand-text { display: flex !important; }
    #accordionSidebar .sb-search { display: block !important; }
    #accordionSidebar .nav-item .nav-link {
        display: flex !important; flex-direction: row !important; align-items: center; gap: .65rem;
        padding: .34rem .5rem !important; text-align: left !important; width: 100% !important; font-size: .845rem !important;
    }
    #accordionSidebar .nav-item .nav-link span { flex: 1; min-width: 0; display: inline !important; text-align: left !important; font-size: .845rem !important; }
    #accordionSidebar .nav-item .nav-link[data-toggle="collapse"]::after { display: inline-block !important; }
    #accordionSidebar .sidebar-heading { text-align: left !important; padding: 1.15rem 1.35rem .4rem !important; font-size: .64rem !important; }
    #accordionSidebar .nav-item .collapse { position: relative !important; left: 0 !important; top: 0 !important; }
    #accordionSidebar .nav-item .collapse .collapse-inner,
    #accordionSidebar .nav-item .collapsing .collapse-inner {
        background: transparent !important; box-shadow: none !important; border-radius: 0 !important; border: 0 !important;
        border-left: 1px solid var(--sb-line) !important; margin: .2rem 0 .45rem 1.45rem !important; padding: 0 0 0 .75rem !important; width: auto !important;
    }
}
@media (min-width: 769px) and (max-width: 992px) { #accordionSidebar:not(.toggled) { width: 15rem !important; } }
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

    <a class="sidebar-brand" href="<?= $base; ?>dashboard" title="Dashboard">
        <?php // Logo bisa diganti via Developer Mode (site_asset() slot logo_sidebar_mini);
              // nama dari company_info() (app_name & company_name). Klik = Dashboard
              // (menu Dashboard sengaja tidak ada di sidebar, user 2026-11-09). ?>
        <span class="sb-logo"><img src="<?= $base . site_asset('logo_sidebar_mini', 'assets/img/logo_mini.png'); ?>" alt="<?= htmlspecialchars(company_info('company_name')); ?>"></span>
        <span class="sb-brand-text">
            <span class="sb-brand-name"><?= htmlspecialchars(company_info('app_name')); ?></span>
            <span class="sb-brand-sub"><?= htmlspecialchars(company_info('company_name')); ?></span>
        </span>
    </a>

    <div class="sb-search" role="search">
        <i class="fas fa-search"></i>
        <input type="text" id="sbMenuSearch" autocomplete="off" spellcheck="false"
               placeholder="<?= htmlspecialchars(lang_line('sidebar_search_ph', 'Cari menu...')); ?>"
               aria-label="<?= htmlspecialchars(lang_line('sidebar_search_ph', 'Cari menu...')); ?>">
        <button type="button" class="sb-search-clear" id="sbMenuSearchClear" aria-label="Clear">&times;</button>
    </div>
    <div class="sb-search-empty" id="sbMenuSearchEmpty"><?= lang_line('sidebar_search_empty', 'Menu tidak ditemukan'); ?></div>

    <hr class="sidebar-divider my-0">


    <?php // Heading tunggal "Core Operations / OPERASI INTI" diganti heading per
          // kelompok (Penjualan & Produksi, Pengadaan & Gudang, Kepabeanan,
          // Keuangan, Data & Sistem) -- lihat sidebar_render_grouped_sections(). ?>

    <?php
        // Ditampung dulu ke buffer per-section (bukan langsung di-echo)
        // supaya urutan tampilnya bisa diatur lewat Developer Mode -- lihat
        // penjelasan lengkap di titik echo-nya (dekat "Accounting" di bawah).
        $sections = [];
    ?>

    <!-- 1. Sales Order Number -->
    <?php ob_start(); if ($canViewOrder): ?>
    <li class="nav-item <?= $isOrderActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isOrderActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseOrder"
            aria-expanded="<?= $isOrderActive ? 'true' : 'false'; ?>" aria-controls="collapseOrder">
            <i class="fas fa-file-signature"></i>
            <span><?= lang_line('sidebar_ipo_number', 'Sales Order'); ?></span>
        </a>
        <div id="collapseOrder" class="collapse <?= $isOrderActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    $isCreateIpo   = ($current == 'sales_contract' && ($current_action == '' || $current_action == 'index' || $current_action == 'add' || $current_action == 'edit' || $current_action == 'update' || $current_action == 'delete' || $current_action == 'submit_for_approval' || $current_action == 'upload_excel' || $current_action == 'download_template'));
                    $isIpoApproval = ($current == 'sales_contract' && in_array($current_action, ['approval', 'endorse', 'approve', 'reject']));
                    $isIpoList     = ($current == 'sales_contract' && $current_action == 'list_all');
                ?>
                <?php if (check_access('sales_contract', 'view')): ?>
                    <a data-lk="sidebar_ipo_create" class="collapse-item <?= $isCreateIpo ? 'active' : ''; ?>" href="<?= $base; ?>sales_contract"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_ipo_create', 'Create New Sales Order'); ?></a>
                <?php endif; ?>
                <?php if (check_access('sales_contract_approval', 'view')): ?>
                    <a data-lk="sidebar_ipo_approval" class="collapse-item <?= $isIpoApproval ? 'active' : ''; ?>" href="<?= $base; ?>sales_contract/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_ipo_approval', 'Sales Order Approval'); ?></a>
                <?php endif; ?>
                <?php if (check_access('sales_contract_list', 'view')): ?>
                    <a data-lk="sidebar_ipo_list" class="collapse-item <?= $isIpoList ? 'active' : ''; ?>" href="<?= $base; ?>sales_contract/list_all"><i class="fas fa-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_ipo_list', 'Sales Order List'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('order', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['order'] = ob_get_clean(); ?>

    <!-- 2. Permintaan Barang -->
    <?php ob_start(); if ($canViewPermintaan): ?>
    <li class="nav-item <?= $isPermintaanActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isPermintaanActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapsePermintaan"
            aria-expanded="<?= $isPermintaanActive ? 'true' : 'false'; ?>" aria-controls="collapsePermintaan">
            <i class="fas fa-clipboard-list"></i>
            <span><?= lang_line('sidebar_permintaan_barang', 'Permintaan Barang'); ?></span>
        </a>
        <div id="collapsePermintaan" class="collapse <?= $isPermintaanActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    $isMrApproval = ($current == 'material_request' && $current_action == 'approval');
                    $isMrStatus   = ($current == 'material_outstanding');
                    // 'Create New IRF' sekarang langsung ke form (consumable_request/add),
                    // bukan ke list lagi (ERP Revision Notes 2026-09-02 — user tidak suka
                    // klik sidebar malah ketemu list). List-nya sendiri dapat menu baru
                    // "IRF List" (consumable_request/index, tanpa segment action).
                    $isCrCreate   = ($current == 'consumable_request' && $current_action == 'add');
                    $isCrList     = ($current == 'consumable_request' && empty($current_action));
                    $isCrApproval = ($current == 'consumable_request' && $current_action == 'approval');

                    $hasProdAny = check_access('material_request_approval', 'view') || check_access('material_outstanding', 'view');
                    $hasGenAny  = check_access('consumable_request', 'view') || check_access('consumable_request_approval', 'view');
                ?>

                <?php if ($hasProdAny): ?>
                    <h6 class="collapse-header" data-gk="sidebar_irf_production_material"><?= lang_line('sidebar_irf_production_material', 'PRODUCTION MATERIAL'); ?></h6>
                    <?php if (check_access('material_request_approval', 'view')): ?>
                        <a data-lk="sidebar_pmr_approval" class="collapse-item <?= $isMrApproval ? 'active' : ''; ?>" href="<?= $base; ?>material_request/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmr_approval', 'Material Request Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('material_outstanding', 'view')): ?>
                        <a data-lk="sidebar_pmr_status" class="collapse-item <?= $isMrStatus ? 'active' : ''; ?>" href="<?= $base; ?>material_outstanding"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmr_status', 'Material Request Status'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($hasGenAny): ?>
                    <h6 class="collapse-header" data-gk="sidebar_irf_general_material"><?= lang_line('sidebar_irf_general_material', 'GENERAL MATERIAL'); ?></h6>
                    <?php if (check_access('consumable_request', 'add')): ?>
                        <a data-lk="sidebar_irf_create" class="collapse-item <?= $isCrCreate ? 'active' : ''; ?>" href="<?= $base; ?>consumable_request/add"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_create', 'Buat Permintaan Barang'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('consumable_request', 'view')): ?>
                        <a data-lk="sidebar_irf_list" class="collapse-item <?= $isCrList ? 'active' : ''; ?>" href="<?= $base; ?>consumable_request"><i class="fas fa-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_list', 'Daftar Permintaan Barang'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('consumable_request_approval', 'view')): ?>
                        <a data-lk="sidebar_irf_approval" class="collapse-item <?= $isCrApproval ? 'active' : ''; ?>" href="<?= $base; ?>consumable_request/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_approval', 'Approval Permintaan Barang'); ?></a>
                    <?php endif; ?>
                    <?php
                        // IRF Outstanding — menu BARU 2026-09-09. Dulu halaman ini
                        // cuma bisa dicapai lewat tombol "Lihat Outstanding" di
                        // halaman IRF List; tombol itu dihilangkan atas permintaan
                        // user, jadi pintunya dipindah ke sidebar supaya halaman
                        // "Buat PR" tidak jadi yatim. Gate = union 3 menu_key,
                        // sama persis dengan gate tombol lamanya.
                        $canIrfOutstanding = check_access('consumable_outstanding', 'view')
                            || check_access('consumable_outstanding_general', 'view')
                            || check_access('consumable_outstanding_produksi', 'view');
                    ?>
                    <?php if ($canIrfOutstanding): ?>
                        <a data-lk="sidebar_irf_outstanding" class="collapse-item <?= ($current == 'consumable_outstanding') ? 'active' : ''; ?>" href="<?= $base; ?>consumable_outstanding"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_irf_outstanding', 'Outstanding Permintaan Barang'); ?></a>
                    <?php endif; ?>
                    <?php // Catatan sejarah: IRF Outstanding sempat digabung ke "IRF List"
                          // (cuma lewat tombol "Lihat Outstanding") sesuai ERP Revision Notes
                          // 2026-09-02; per 2026-09-09 tombol itu dihilangkan atas permintaan
                          // user dan menunya dikembalikan ke sidebar (blok tepat di atas).
                          // "IRF Status - General/Produksi" DIHAPUS dari sidebar (user
                          // 2026-09-06: "hilangi saja") -- permission menu_key
                          // irf_status_general/irf_status_produksi TIDAK dihapus dari DB, cuma
                          // link-nya yg dicabut dari sini. ?>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('permintaan', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['permintaan'] = ob_get_clean(); ?>

    <!-- 3. Produksi -->
    <?php ob_start(); if ($canViewProduction): ?>
    <li class="nav-item <?= $isProductionActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isProductionActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseProduction"
            aria-expanded="<?= $isProductionActive ? 'true' : 'false'; ?>" aria-controls="collapseProduction">
            <i class="fas fa-industry"></i>
            <span><?= lang_line('sidebar_production', 'Production'); ?></span>
        </a>
        <div id="collapseProduction" class="collapse <?= $isProductionActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('work_order', 'view')): ?>
                    <a data-lk="sidebar_work_order_create" class="collapse-item <?= ($current == 'work_order') ? 'active' : ''; ?>" href="<?= $base; ?>work_order"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_work_order_create', 'Create New Work Order'); ?></a>
                <?php endif; ?>

                <?php
                    // 'Create New MRF' langsung ke form (material_request/add), bukan ke
                    // list lagi (user 2026-09-04, MRF disamakan pola dgn IRF/PRF/ERF —
                    // lihat [[sidebar_form_naming_alignment]]). List-nya dapat menu baru
                    // "MRF List" (material_request, tanpa segment action).
                    $isMrCreate = ($current == 'material_request' && $current_action == 'add');
                    $isMrList   = ($current == 'material_request' && empty($current_action));
                ?>
                <?php if (check_access('material_request', 'add')): ?>
                    <a data-lk="sidebar_pmr_create" class="collapse-item <?= $isMrCreate ? 'active' : ''; ?>" href="<?= $base; ?>material_request/add"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmr_create', 'Create New Material Request'); ?></a>
                <?php endif; ?>
                <?php if (check_access('material_request', 'view')): ?>
                    <a data-lk="sidebar_mr_list" class="collapse-item <?= $isMrList ? 'active' : ''; ?>" href="<?= $base; ?>material_request"><i class="fas fa-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_mr_list', 'Material Request List'); ?></a>
                <?php endif; ?>

                <?php if (check_access('production_process', 'view')): ?>
                    <a data-lk="sidebar_production_in_progress" class="collapse-item <?= ($current == 'production_process') ? 'active' : ''; ?>" href="<?= $base; ?>production_process"><i class="fas fa-cog" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_production_in_progress', 'Production In Progress'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_qc', 'view')): ?>
                    <a data-lk="sidebar_production_qc" class="collapse-item <?= ($current == 'production_qc') ? 'active' : ''; ?>" href="<?= $base; ?>production_qc"><i class="fas fa-clipboard-check" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_production_qc', 'Production QC'); ?></a>
                <?php endif; ?>
                <?php if (check_access('bom', 'view')): ?>
                    <a data-lk="sidebar_bom" class="collapse-item <?= ($current == 'bom') ? 'active' : ''; ?>" href="<?= $base; ?>bom"><i class="fas fa-sitemap" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_bom', 'Bill of Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('production_receive', 'view')): ?>
                    <a data-lk="sidebar_production_receipt" class="collapse-item <?= ($current == 'production_receive') ? 'active' : ''; ?>" href="<?= $base; ?>production_receive"><i class="fas fa-inbox" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_production_receipt', 'Production Receipt'); ?></a>
                <?php endif; ?>
                <?php if (check_access('qc_production', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_production_reports"><?= lang_line('sidebar_production_reports', 'REPORT:'); ?></h6>
                    <a data-lk="sidebar_qc_production_report" class="collapse-item <?= ($current == 'qc_production') ? 'active' : ''; ?>" href="<?= $base; ?>qc_production"><i class="fas fa-check-square" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qc_production_report', 'QC Production Report'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('produksi', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['produksi'] = ob_get_clean(); ?>

    <!-- 4. Warehouse -->
    <?php ob_start(); if ($canViewInventory): ?>
    <li class="nav-item <?= $isInventoryActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isInventoryActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseInventory"
            aria-expanded="<?= $isInventoryActive ? 'true' : 'false'; ?>" aria-controls="collapseInventory">
            <i class="fas fa-warehouse"></i>
            <span><?= lang_line('sidebar_warehouse', 'Warehouse'); ?></span>
        </a>
        <div id="collapseInventory" class="collapse <?= $isInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>

                <?php
                    $hasProWarehouse = check_access('warehouse_out', 'view') || check_access('purchase_request', 'view') || check_access('purchase_request_approval', 'view') || check_access('stock_material', 'view');
                    $hasGenWarehouse = check_access('stock_transfer', 'view') || check_access('purchase_request_general', 'view') || check_access('purchase_request_approval_general', 'view') || check_access('purchase_request_jasa', 'view') || check_access('purchase_request_approval_jasa', 'view') || check_access('purchase_request_asset', 'view') || check_access('purchase_request_approval_asset', 'view') || check_access('stock_inventory', 'view') || check_access('stock_machine', 'view');
                    $hasOtherWarehouse = check_access('initial_stock', 'view') || check_access('prodreturn', 'view') || check_access('stock_alert', 'view') || check_access('material_traceability', 'view') || check_access('inventory_traceability', 'view') || check_access('stock_ledger', 'view');

                    // Chooser "Pemasukan"/"Pengeluaran" (ERP Revision Notes 2026-09-02,
                    // Fase 2 poin 10) — 1 link masing2, gantikan 6 link individual
                    // (SGR General/Produksi/Lainnya, Warehouse In, Consumable Out
                    // General/Produksi, Material Issuance) yang sebelumnya tersebar
                    // di PRO-WAREHOUSE & GENERAL WAREHOUSE. Modul aslinya TIDAK
                    // berubah — cuma titik masuknya dikonsolidasi lewat
                    // Goods_receiving/Goods_issuance (halaman tile-chooser).
                    // Visibilitas = union permission semua tujuan di baliknya.
                    $canGoodsReceiving = check_access('supplier_general_item_receipt_general', 'view')
                        || check_access('supplier_general_item_receipt_produksi', 'view')
                        || check_access('supplier_general_item_receipt', 'view')
                        || check_access('warehouse_in', 'view');
                    $canGoodsIssuance = check_access('consumable_out_general', 'view')
                        || check_access('consumable_out_produksi', 'view')
                        || check_access('consumable_out', 'view')
                        || check_access('material_issuance', 'view');
                ?>

                <!-- ==================== PEMASUKAN / PENGELUARAN (chooser) ==================== -->
                <?php if ($canGoodsReceiving): ?>
                    <a data-lk="sidebar_goods_receiving" class="collapse-item <?= ($current == 'goods_receiving') ? 'active' : ''; ?>" href="<?= $base; ?>goods_receiving"><i class="fas fa-truck-loading" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_receiving', 'Pemasukan Barang'); ?></a>
                <?php endif; ?>
                <?php if ($canGoodsIssuance): ?>
                    <a data-lk="sidebar_goods_issuance" class="collapse-item <?= ($current == 'goods_issuance') ? 'active' : ''; ?>" href="<?= $base; ?>goods_issuance"><i class="fas fa-dolly-flatbed" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_issuance', 'Pengeluaran Barang'); ?></a>
                <?php endif; ?>

                <!-- ==================== PRO-WAREHOUSE ==================== -->
                <?php if ($hasProWarehouse): ?>
                    <h6 class="collapse-header" data-gk="sidebar_pro_warehouse"><?= lang_line('sidebar_pro_warehouse', 'PRO-WAREHOUSE'); ?></h6>
                    <?php if (check_access('warehouse_out', 'view')): ?>
                        <a data-lk="sidebar_supplier_material_return" class="collapse-item <?= ($current == 'warehouse_out') ? 'active' : ''; ?>" href="<?= $base; ?>warehouse_out"><i class="fas fa-undo" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_supplier_material_return', 'Supplier Material Return Receipt'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('prodreturn', 'view')): ?>
                        <a data-lk="sidebar_prodreturn" class="collapse-item <?= ($current == 'prodreturn') ? 'active' : ''; ?>" href="<?= $base; ?>ProdReturn"><i class="fas fa-undo" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_prodreturn', 'Retur Material Produksi (WIP)'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('purchase_request', 'view')): ?>
                        <a data-lk="sidebar_material_purchase_request" class="collapse-item <?= ($current == 'purchase_request') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request"><i class="fas fa-file-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_purchase_request', 'Material Item Purchase Request'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('purchase_request_approval', 'view')): ?>
                        <a data-lk="sidebar_material_pr_approval" class="collapse-item <?= ($current == 'purchase_request_approval') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request_approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_pr_approval', 'Material Item PR Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('stock_material', 'view')): ?>
                        <h6 class="collapse-header" data-gk="sidebar_pro_warehouse_reports"><?= lang_line('sidebar_pro_warehouse_reports', 'REPORTS:'); ?></h6>
                        <a data-lk="sidebar_stock_material_semifinished" class="collapse-item <?= ($current == 'stock_material') ? 'active' : ''; ?>" href="<?= $base; ?>stock_material"><i class="fas fa-cubes" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_material_semifinished', 'Material & Semi Finished Stock Report'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ==================== GENERAL WAREHOUSE ==================== -->
                <?php if ($hasGenWarehouse): ?>
                    <h6 class="collapse-header" data-gk="sidebar_general_warehouse"><?= lang_line('sidebar_general_warehouse', 'GENERAL WAREHOUSE'); ?></h6>
                    <?php // SGR (Penerimaan) & Consumable Out (Pengeluaran) sekarang diakses
                          // lewat chooser "Pemasukan Barang"/"Pengeluaran Barang" di atas. ?>
                    <?php if (check_access('stock_transfer', 'view')): ?>
                        <a data-lk="sidebar_stock_transfer" class="collapse-item <?= ($current == 'stock_transfer') ? 'active' : ''; ?>" href="<?= $base; ?>stock_transfer"><i class="fas fa-exchange-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_transfer', 'Stock Transfer'); ?></a>
                    <?php endif; ?>
                    <?php
                        // IPR (General/Jasa/Asset) digabung jadi 1 link masing2 (list &
                        // approval) — user 2026-09-06: "IRF-nya General/Jasa/Asset/
                        // Produksi sudah jadi 1, harusnya PR-nya juga jadi 1" (lihat
                        // memory pr_controllers_merge.md). $canViewIpr/$canApproveIpr
                        // union ketiga menu_key, pola sama spt GMPO di section
                        // Purchasing. Baris List/Approval tetap "milik" controller
                        // aslinya per row (`$pr->type`) — lihat
                        // views/purchase_request_general/index.php &
                        // purchase_request_approval_general/approval.php.
                        $canViewIpr = check_access('purchase_request_general', 'view')
                            || check_access('purchase_request_jasa', 'view')
                            || check_access('purchase_request_asset', 'view');
                        $canApproveIpr = check_access('purchase_request_approval_general', 'view')
                            || check_access('purchase_request_approval_jasa', 'view')
                            || check_access('purchase_request_approval_asset', 'view');
                    ?>
                    <?php if ($canViewIpr): ?>
                        <a data-lk="sidebar_general_item_purchase_request" class="collapse-item <?= (in_array($current, ['purchase_request_general','purchase_request_jasa','purchase_request_asset'])) ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request_general"><i class="fas fa-file-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_general_item_purchase_request', 'General Item Purchase Request'); ?></a>
                    <?php endif; ?>
                    <?php if ($canApproveIpr): ?>
                        <a data-lk="sidebar_general_pr_approval" class="collapse-item <?= (in_array($current, ['purchase_request_approval_general','purchase_request_approval_jasa','purchase_request_approval_asset'])) ? 'active' : ''; ?>" href="<?= $base; ?>purchase_request_approval_general/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_general_pr_approval', 'General Item PR Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('stock_inventory', 'view') || check_access('stock_machine', 'view')): ?>
                        <h6 class="collapse-header" data-gk="sidebar_general_warehouse_reports"><?= lang_line('sidebar_general_warehouse_reports', 'REPORT:'); ?></h6>
                        <?php if (check_access('stock_inventory', 'view')): ?>
                            <a data-lk="sidebar_stock_item_report" class="collapse-item <?= ($current == 'stock_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>stock_inventory"><i class="fas fa-boxes" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_item_report', 'Stock Item Report'); ?></a>
                        <?php endif; ?>
                        <?php if (check_access('stock_machine', 'view')): ?>
                            <a data-lk="sidebar_equipment_tools_report" class="collapse-item <?= ($current == 'stock_machine') ? 'active' : ''; ?>" href="<?= $base; ?>stock_machine"><i class="fas fa-cog" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_equipment_tools_report', 'Equipment & Tools Item Report'); ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ==================== OTHER ==================== -->
                <?php if ($hasOtherWarehouse): ?>
                    <h6 class="collapse-header" data-gk="sidebar_warehouse_other"><?= lang_line('sidebar_warehouse_other', 'OTHER'); ?></h6>
                    <?php if (check_access('initial_stock', 'view')): ?>
                        <a data-lk="sidebar_initial_stock" class="collapse-item <?= ($current == 'initial_stock') ? 'active' : ''; ?>" href="<?= $base; ?>initial_stock"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_initial_stock', 'Input Stock Awal'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('stock_ledger', 'view')): ?>
                        <a data-lk="sidebar_stock_ledger" class="collapse-item <?= ($current == 'stock_ledger') ? 'active' : ''; ?>" href="<?= $base; ?>stock_ledger"><i class="fas fa-book" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_ledger', 'Stock Ledger'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('material_traceability', 'view')): ?>
                        <a data-lk="sidebar_material_traceability" class="collapse-item <?= ($current == 'material_traceability') ? 'active' : ''; ?>" href="<?= $base; ?>material_traceability"><i class="fas fa-route" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_traceability', 'Material Traceability'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('inventory_traceability', 'view')): ?>
                        <a data-lk="sidebar_inventory_traceability" class="collapse-item <?= ($current == 'inventory_traceability') ? 'active' : ''; ?>" href="<?= $base; ?>inventory_traceability"><i class="fas fa-route" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_inventory_traceability', 'General Item Traceability'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('stock_alert', 'view')): ?>
                        <a data-lk="sidebar_stock_alert" class="collapse-item <?= ($current == 'stock_alert') ? 'active' : ''; ?>" href="<?= $base; ?>stock_alert"><i class="fas fa-bell" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_alert', 'Stock Alert'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('warehouse', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['warehouse'] = ob_get_clean(); ?>

    <!-- 5. Pembelian -->
    <?php ob_start(); if ($canViewPurchase): ?>
    <li class="nav-item <?= $isPurchaseActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isPurchaseActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapsePurchase"
            aria-expanded="<?= $isPurchaseActive ? 'true' : 'false'; ?>" aria-controls="collapsePurchase">
            <i class="fas fa-shopping-cart"></i>
            <span><?= lang_line('sidebar_purchase', 'Purchasing'); ?></span>
        </a>
        <div id="collapsePurchase" class="collapse <?= $isPurchaseActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    $hasPmpo   = check_access('purchase_order', 'view') || check_access('purchase_order_approval', 'view') || check_access('pmpo_status', 'view');
                    // GMPO (GENERAL MATERIAL) — user 2026-09-05 minta section
                    // sidebar Jasa & Asset yg TADINYA terpisah digabung ke sini
                    // (lihat memory po_controllers_merge.md bagian "Konsolidasi
                    // sidebar"). Jadi $hasGmpo sekarang union akses ke SEMUA 3
                    // po_type, bukan cuma non_produksi lagi.
                    $hasGmpo   = check_access('purchase_order_general', 'view') || check_access('purchase_order_approval_general', 'view') || check_access('gmpo_status', 'view')
                              || check_access('purchase_order_jasa', 'view') || check_access('purchase_order_approval_jasa', 'view')
                              || check_access('purchase_order_asset', 'view') || check_access('purchase_order_approval_asset', 'view');
                    $hasPurchaseReport = check_access('pmpo_list', 'view') || check_access('gmpo_list', 'view') || check_access('po_report', 'view') || check_access('po_monitoring', 'view') || check_access('pr_monitoring', 'view');
                ?>

                <?php if ($hasPmpo): ?>
                    <h6 class="collapse-header" data-gk="sidebar_pmpo_group"><?= lang_line('sidebar_pmpo_group', 'PRODUCTION MATERIAL'); ?></h6>
                    <?php if (check_access('purchase_order', 'view')): ?>
                        <a data-lk="sidebar_pmpo_create" class="collapse-item <?= ($current == 'purchase_order') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order/add"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmpo_create', 'Create New PO Material'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('purchase_order_approval', 'view')): ?>
                        <a data-lk="sidebar_pmpo_approval" class="collapse-item <?= ($current == 'purchase_order_approval') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order_approval"><i class="fas fa-thumbs-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmpo_approval', 'PO Material Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('pmpo_status', 'view')): ?>
                        <a data-lk="sidebar_pmpo_status" class="collapse-item <?= ($current == 'pmpo_status') ? 'active' : ''; ?>" href="<?= $base; ?>pmpo_status"><i class="fas fa-tachometer-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmpo_status', 'PO Material Status'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($hasGmpo): ?>
                    <h6 class="collapse-header" data-gk="sidebar_gmpo_group"><?= lang_line('sidebar_gmpo_group', 'GENERAL MATERIAL'); ?></h6>
                    <?php
                        // "Create New GMPO" langsung ke FORM (purchase_order_general/add,
                        // py dropdown Kategori General/Jasa/Asset) -- BUKAN ke list lagi
                        // (user 2026-09-05: "HARUSNYA SAAT KLIK BUAT GMPO LANGSUNG
                        // MUNCUL FORM", pola sama spt IRF/PRF/ERF/MRF). Gate akses pakai
                        // union ketiga menu_key krn form itu bisa bikin PO tipe mana pun.
                        $canCreateGmpo = check_access('purchase_order_general', 'add')
                            || check_access('purchase_order_jasa', 'add')
                            || check_access('purchase_order_asset', 'add');
                    ?>
                    <?php if ($canCreateGmpo): ?>
                        <a data-lk="sidebar_gmpo_create" class="collapse-item <?= ($current == 'purchase_order_general' && $current_action == 'add') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order_general/add"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_gmpo_create', 'Create New PO General'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('purchase_order_general', 'view')): ?>
                        <a data-lk="sidebar_gmpo_manage_list" class="collapse-item <?= ($current == 'purchase_order_general' && empty($current_action)) ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order_general"><i class="fas fa-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_gmpo_manage_list', 'Kelola PO General'); ?></a>
                    <?php endif; ?>
                    <?php
                        // Approval GMPO juga digabung -- lihat
                        // Purchase_order_approval_general::_included_types().
                        $canApproveGmpo = check_access('purchase_order_approval_general', 'view')
                            || check_access('purchase_order_approval_jasa', 'view')
                            || check_access('purchase_order_approval_asset', 'view');
                    ?>
                    <?php if ($canApproveGmpo): ?>
                        <a data-lk="sidebar_gmpo_approval" class="collapse-item <?= ($current == 'purchase_order_approval_general') ? 'active' : ''; ?>" href="<?= $base; ?>purchase_order_approval_general"><i class="fas fa-thumbs-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_gmpo_approval', 'PO General Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('gmpo_status', 'view')): ?>
                        <a data-lk="sidebar_gmpo_status" class="collapse-item <?= ($current == 'gmpo_status') ? 'active' : ''; ?>" href="<?= $base; ?>gmpo_status"><i class="fas fa-tachometer-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_gmpo_status', 'PO General Status'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($hasPurchaseReport): ?>
                    <h6 class="collapse-header" data-gk="sidebar_purchase_reports"><?= lang_line('sidebar_purchase_reports', 'REPORT:'); ?></h6>
                    <?php if (check_access('pmpo_list', 'view')): ?>
                        <a data-lk="sidebar_pmpo_list" class="collapse-item <?= ($current == 'pmpo_list') ? 'active' : ''; ?>" href="<?= $base; ?>pmpo_list"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pmpo_list', 'PO Material List'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('gmpo_list', 'view')): ?>
                        <a data-lk="sidebar_gmpo_list" class="collapse-item <?= ($current == 'gmpo_list') ? 'active' : ''; ?>" href="<?= $base; ?>gmpo_list"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_gmpo_list', 'PO General List'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('po_report', 'view')): ?>
                        <a data-lk="sidebar_po_report" class="collapse-item <?= ($current == 'po_report') ? 'active' : ''; ?>" href="<?= $base; ?>po_report"><i class="fas fa-file-invoice" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_po_report', 'PO Report'); ?></a>
                    <?php endif; ?>
                    <?php
                        // PO List (monitoring) — ERP Revision Notes, [[erp_revision_monitoring_lists]].
                        // Gabungan lintas-tipe (produksi/general/jasa/asset) dari
                        // Pmpo_list/Gmpo_list di atas (yang masing2 cuma 1 tipe).
                    ?>
                    <?php if (check_access('po_monitoring', 'view')): ?>
                        <a data-lk="sidebar_po_monitoring" class="collapse-item <?= ($current == 'po_monitoring') ? 'active' : ''; ?>" href="<?= $base; ?>po_monitoring"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_po_monitoring', 'PO List (Semua Tipe)'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('pr_monitoring', 'view')): ?>
                        <a data-lk="sidebar_pr_monitoring" class="collapse-item <?= ($current == 'pr_monitoring') ? 'active' : ''; ?>" href="<?= $base; ?>pr_monitoring"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_pr_monitoring', 'Daftar PR (Semua Tipe)'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('purchase', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['purchase'] = ob_get_clean(); ?>

    <!-- 6. Quality Control & Assurances -->
    <?php ob_start(); if ($canViewQa): ?>
    <li class="nav-item <?= $isQaActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isQaActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseQa"
            aria-expanded="<?= $isQaActive ? 'true' : 'false'; ?>" aria-controls="collapseQa">
            <i class="fas fa-check-circle"></i>
            <span><?= lang_line('sidebar_quality_assurance_group', 'Quality Control & Assurances'); ?></span>
        </a>
        <div id="collapseQa" class="collapse <?= $isQaActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    // Active state per sub-item
                    $isQaIndex     = ($current == 'quality_assurance' && $current_action != 'qa_report');
                    $isQaReport    = ($current == 'quality_assurance' && $current_action == 'qa_report');
                    $isQaSampling  = ($current == 'qa_sampling'       && $current_action != 'report');
                    $isQaSampReport = ($current == 'qa_sampling'      && $current_action == 'report');
                ?>
                <?php if (check_access('quality_assurance', 'view')): ?>
                    <a data-lk="sidebar_quality_assurance" class="collapse-item <?= $isQaIndex ? 'active' : ''; ?>" href="<?= $base; ?>quality_assurance"><i class="fas fa-check-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_quality_assurance', 'Quality Assurance'); ?></a>
                <?php endif; ?>
                <?php if (check_access('qa_sampling', 'view')): ?>
                    <a data-lk="sidebar_qa_sampling" class="collapse-item <?= $isQaSampling ? 'active' : ''; ?>" href="<?= $base; ?>qa_sampling"><i class="fas fa-vial" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qa_sampling', 'QA Sampling'); ?></a>
                <?php endif; ?>

                <?php if (check_access('qa_report', 'view') || check_access('qa_sampling_report', 'view') || check_access('material_ng_report', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_qa_reports"><?= lang_line('sidebar_qa_reports', 'REPORT:'); ?></h6>
                    <?php if (check_access('qa_report', 'view')): ?>
                        <a data-lk="sidebar_qa_report" class="collapse-item <?= $isQaReport ? 'active' : ''; ?>" href="<?= $base; ?>quality_assurance/qa_report"><i class="fas fa-chart-bar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qa_report', 'QA Report'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('qa_sampling_report', 'view')): ?>
                        <a data-lk="sidebar_qa_sampling_report" class="collapse-item <?= $isQaSampReport ? 'active' : ''; ?>" href="<?= $base; ?>qa_sampling/report"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_qa_sampling_report', 'QA Sampling Report'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('material_ng_report', 'view')): ?>
                        <a data-lk="sidebar_material_ng_report" class="collapse-item <?= $current == 'material_ng_report' ? 'active' : ''; ?>" href="<?= $base; ?>material_ng_report"><i class="fas fa-exclamation-triangle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_ng_report', 'Daily Material NG Report'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('qa', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['qa'] = ob_get_clean(); ?>

    <!-- 7. Finished Goods -->
    <?php ob_start(); if ($canViewFg): ?>
    <li class="nav-item <?= $isFgActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isFgActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseFg"
            aria-expanded="<?= $isFgActive ? 'true' : 'false'; ?>" aria-controls="collapseFg">
            <i class="fas fa-box-open"></i>
            <span><?= lang_line('sidebar_finished_goods', 'Finished Goods'); ?></span>
        </a>
        <div id="collapseFg" class="collapse <?= $isFgActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    $isFgCreate    = ($current == 'fg_receipt' && $current_action != 'approval');
                    $isFgApproval  = ($current == 'fg_receipt' && $current_action == 'approval');
                ?>
                <?php if (check_access('fg_receipt', 'view')): ?>
                    <a data-lk="sidebar_fg_receipt_create" class="collapse-item <?= $isFgCreate ? 'active' : ''; ?>" href="<?= $base; ?>fg_receipt"><i class="fas fa-plus-circle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_receipt_create', 'Create New FG Receipt'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_receipt_approval', 'view')): ?>
                    <a data-lk="sidebar_fg_receipt_list" class="collapse-item <?= $isFgApproval ? 'active' : ''; ?>" href="<?= $base; ?>fg_receipt/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_receipt_list', 'FG Receipt List'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_stock', 'view')): ?>
                    <a data-lk="sidebar_fg_stock_card" class="collapse-item <?= ($current == 'fg_stock') ? 'active' : ''; ?>" href="<?= $base; ?>fg_stock"><i class="fas fa-book" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_stock_card', 'FG Stock Card'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('fg', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['fg'] = ob_get_clean(); ?>

    <!-- 8. Shipping & Transportation (EXIM) -->
    <?php ob_start(); if ($canViewExim): ?>
    <li class="nav-item <?= $isEximActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isEximActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseExim"
            aria-expanded="<?= $isEximActive ? 'true' : 'false'; ?>" aria-controls="collapseExim">
            <i class="fas fa-truck-loading"></i>
            <span><?= lang_line('sidebar_shipping_transport', 'Shipping & Transportation'); ?></span>
        </a>
        <div id="collapseExim" class="collapse <?= $isEximActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php
                    $isEximIndex    = ($current == 'exim_verification' && ($current_action == '' || $current_action == 'index'));
                    $isEximIncoming = ($current == 'exim_verification' && $current_action == 'incoming');
                    $isEximOutgoing = ($current == 'exim_verification' && $current_action == 'outgoing');
                ?>
                <a data-lk="sidebar_exim_verifications" class="collapse-item <?= $isEximIndex ? 'active' : ''; ?>" href="<?= $base; ?>exim_verification"><i class="fas fa-shield-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_exim_verifications', 'EXIM Verifications'); ?></a>
                <a data-lk="sidebar_import_receive" class="collapse-item <?= $isEximIncoming ? 'active' : ''; ?>" href="<?= $base; ?>exim_verification/incoming"><i class="fas fa-arrow-down" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_import_receive', 'Import Receive'); ?></a>
                <a data-lk="sidebar_export_release" class="collapse-item <?= $isEximOutgoing ? 'active' : ''; ?>" href="<?= $base; ?>exim_verification/outgoing"><i class="fas fa-arrow-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_export_release', 'Export Release'); ?></a>
                <?php
                    // Dipindah ke sini 2026-09-06 atas permintaan user:
                    //  - Import Request (`mir`)  dulu di Purchasing > grup "IMPORT"
                    //  - FG Release Permit (`fg_delivery`) dulu di Finished Goods
                    // URL & menu_key TIDAK berubah, cuma letak menunya. Gate-nya
                    // tetap permission masing-masing (bukan permission EXIM).
                ?>
                <?php if (check_access('mir', 'view')): ?>
                    <a data-lk="sidebar_import_request" class="collapse-item <?= ($current == 'mir') ? 'active' : ''; ?>" href="<?= $base; ?>mir"><i class="fas fa-globe" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_import_request', 'Import Request'); ?></a>
                <?php endif; ?>
                <?php if (check_access('fg_delivery', 'view')): ?>
                    <a data-lk="sidebar_fg_release_permit" class="collapse-item <?= ($current == 'fg_delivery') ? 'active' : ''; ?>" href="<?= $base; ?>fg_delivery"><i class="fas fa-shipping-fast" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_release_permit', 'FG Release Permit'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('exim', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['exim'] = ob_get_clean(); ?>

    <!-- 9. Scrap -->
    <?php ob_start(); if ($canViewScrap): ?>
    <li class="nav-item <?= $isScrapActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isScrapActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseScrap"
            aria-expanded="<?= $isScrapActive ? 'true' : 'false'; ?>" aria-controls="collapseScrap">
            <i class="fas fa-recycle"></i>
            <span><?= lang_line('sidebar_scrap_group', 'Scrap'); ?></span>
        </a>
        <div id="collapseScrap" class="collapse <?= $isScrapActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('scrap_in', 'view')): ?>
                    <a data-lk="sidebar_scrap_receipt" class="collapse-item <?= ($current == 'scrap_in') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_In"><i class="fas fa-arrow-down" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_receipt', 'Scrap Receipt'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_out', 'view')): ?>
                    <a data-lk="sidebar_scrap_release" class="collapse-item <?= ($current == 'scrap_out') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Out"><i class="fas fa-arrow-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_release', 'Scrap Release'); ?></a>
                <?php endif; ?>
                <?php if (check_access('scrap_stock', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_scrap_reports"><?= lang_line('sidebar_scrap_reports', 'REPORT:'); ?></h6>
                    <a data-lk="sidebar_scrap_stock_report" class="collapse-item <?= ($current == 'scrap_stock') ? 'active' : ''; ?>" href="<?= $base; ?>Scrap_Stock"><i class="fas fa-chart-line" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_stock_report', 'Scrap Stock Report'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('scrap', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['scrap'] = ob_get_clean(); ?>

    <!-- 10. Subkon -->
    <?php ob_start(); if ($canViewSubkon): ?>
    <li class="nav-item <?= $isSubkonActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isSubkonActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseSubkon"
            aria-expanded="<?= $isSubkonActive ? 'true' : 'false'; ?>" aria-controls="collapseSubkon">
            <i class="fas fa-handshake"></i>
            <span><?= lang_line('sidebar_subcon', 'Subkon'); ?></span>
        </a>
        <div id="collapseSubkon" class="collapse <?= $isSubkonActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('subkon_order', 'view')): ?>
                    <a data-lk="sidebar_subcon_order" class="collapse-item <?= ($current == 'subkon_order') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_order"><i class="fas fa-ticket-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_order', 'Kontrak Kerja Subkon'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_out', 'view')): ?>
                    <a data-lk="sidebar_subcon_out" class="collapse-item <?= ($current == 'subkon_out') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_out"><i class="fas fa-arrow-right" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_out', 'Pengeluaran Barang Subkon'); ?></a>
                <?php endif; ?>
                <?php if (check_access('subkon_in', 'view')): ?>
                    <a data-lk="sidebar_subcon_in" class="collapse-item <?= ($current == 'subkon_in') ? 'active' : ''; ?>" href="<?= $base; ?>subkon_in"><i class="fas fa-arrow-left" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_subcon_in', 'Penerimaan Barang Hasil Subkon'); ?></a>
                <?php endif; ?>
            <?php echo sidebar_render_ordered_items('subkon', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php endif; $sections['subkon'] = ob_get_clean(); ?>

    <?php ob_start(); if ($canViewItInventory): ?>
    <li class="nav-item <?= $isItInventoryActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isItInventoryActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseItInventory"
            aria-expanded="<?= $isItInventoryActive ? 'true' : 'false'; ?>" aria-controls="collapseItInventory">
            <i class="fas fa-desktop"></i>
            <span><?= lang_line('sidebar_it_inventory', 'IT Inventory [BC]'); ?></span>
        </a>
        <div id="collapseItInventory" class="collapse <?= $isItInventoryActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('Lap_Pemasukan_BB', 'view')): ?>
                    <a data-lk="sidebar_goods_incoming_report" class="collapse-item <?= ($current == 'lappemasukanbb') ? 'active' : ''; ?>" href="<?= $base; ?>LapPemasukanBB"><i class="fas fa-arrow-down" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_incoming_report', 'Good Incoming Report'); ?></a>
                <?php endif; ?>
                <?php if (check_access('lap_pengeluaran_barang', 'view')): ?>
                    <a data-lk="sidebar_goods_outgoing_report" class="collapse-item <?= ($current == 'lappengeluaranbarang') ? 'active' : ''; ?>" href="<?= $base; ?>LapPengeluaranBarang"><i class="fas fa-arrow-up" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_goods_outgoing_report', 'Good Outgoing Report'); ?></a>
                <?php endif; ?>

                <?php
                    $hasMutationReport = check_access('lap_mutasi_scrap', 'view') || check_access('lapmutasimaterial', 'view') || check_access('lapmutasifinished', 'view') || check_access('lapmutasiwip', 'view') || check_access('lapmutasimesin', 'view');
                ?>
                <?php if ($hasMutationReport): ?>
                    <h6 class="collapse-header" data-gk="sidebar_mutation_reports"><?= lang_line('sidebar_mutation_reports', 'MUTATION REPORT:'); ?></h6>
                    <?php if (check_access('lap_mutasi_scrap', 'view')): ?>
                        <a data-lk="sidebar_scrap_mutation_report" class="collapse-item <?= ($current == 'lapmutasiscrap') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiScrap"><i class="fas fa-recycle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_scrap_mutation_report', 'Scrap'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('lapmutasimaterial', 'view')): ?>
                        <a data-lk="sidebar_material_mutation_report" class="collapse-item <?= ($current == 'lapmutasimaterial') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMaterial"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_material_mutation_report', 'Material'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('lapmutasifinished', 'view')): ?>
                        <a data-lk="sidebar_fg_mutation_report" class="collapse-item <?= ($current == 'lapmutasifinished') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiFinished"><i class="fas fa-box" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_fg_mutation_report', 'Finish Good'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('lapmutasiwip', 'view')): ?>
                        <a data-lk="sidebar_wip_mutation_report" class="collapse-item <?= ($current == 'lapmutasiwip') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiWIP"><i class="fas fa-hourglass" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_wip_mutation_report', 'Work In Progress'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('lapmutasimesin', 'view')): ?>
                        <a data-lk="sidebar_machine_mutation_report" class="collapse-item <?= ($current == 'lapmutasimesin') ? 'active' : ''; ?>" href="<?= $base; ?>LapMutasiMesin"><i class="fas fa-tools" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_machine_mutation_report', 'Equipment & Tools'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php echo sidebar_render_ordered_items('it_inventory', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php
        endif;
        // Section grup "Core Operations" (11 di atas) SELESAI ditampung.
        // Lanjut tampung Accounting/Master/Document Sharing/System JUGA ke
        // pool $sections yg SAMA (user 2026-09-06: "jangan cuma core
        // operation, tapi semuanya") -- echo final-nya BARU terjadi setelah
        // System (paling bawah, sebelum tombol collapse sidebar). Heading
        // "Accounting & Finance"/"Configuration" yg dulu misahin grup ini
        // SENGAJA DIHAPUS (bukan cuma disembunyikan) krn begitu semua section
        // bisa saling tukar posisi bebas, heading tetap di posisi lama jadi
        // tidak akurat lagi -- 1 heading "Core Operations" di atas dianggap
        // cukup mewakili seluruh pool ini.
        $sections['it_inventory'] = ob_get_clean();
    ?>

    <?php
    /* =====================================================================
       FINANCE & ACCOUNTING -- DIPECAH jadi 6 menu (user 2026-11-09:
       "accounting juga harusnya di pisah"). Dulu 1 dropdown berisi ±25 item.
       - Hak akses tiap item TIDAK berubah (check_access() sama persis), dan
         baris <a> (href/data-lk) sama persis -> item_key "Atur Urutan
         Sidebar" tetap sama (lihat db/migration_sidebar_accounting_split.sql).
       - Key section 'accounting' tetap dipakai utk "Kas, Bank & Jurnal" supaya
         baris sidebar_order lama tidak jadi yatim.
       - Menu yang isinya kosong utk user ybs (tidak punya akses) tidak tampil.
       ===================================================================== */
    $sb_dropdown = function ($dd_key, $icon, $label, $is_active, $inner) use ($base) {
        $inner = sidebar_render_ordered_items($dd_key, $inner, $base);
        if (trim(strip_tags($inner, '<a>')) === '') return '';
        $id = 'collapse_' . $dd_key;
        return '<li class="nav-item' . ($is_active ? ' active' : '') . '">'
             . '<a class="nav-link' . ($is_active ? '' : ' collapsed') . '" href="#" data-toggle="collapse" data-target="#' . $id . '"'
             . ' aria-expanded="' . ($is_active ? 'true' : 'false') . '" aria-controls="' . $id . '">'
             . '<i class="fas fa-fw ' . $icon . '"></i><span>' . htmlspecialchars($label) . '</span></a>'
             . '<div id="' . $id . '" class="collapse' . ($is_active ? ' show' : '') . '" data-parent="#accordionSidebar">'
             . '<div class="py-2 collapse-inner rounded">' . $inner . '</div></div></li>';
    };
    $isAccFundActive    = in_array($current, ['prf', 'prf_approval', 'prf_payment', 'prf_list', 'erf', 'erf_approval', 'erf_payment', 'erf_list'], true);
    $isAccLedgerActive  = in_array($current, ['jurnalumum', 'summary_ledger', 'voucher_disbursement_list', 'cashbank'], true);
    $isAccAparActive    = in_array($current, ['apinvoice', 'appayment', 'hutang', 'arinvoice', 'arreceipt', 'piutang'], true);
    $isAccReportActive  = in_array($current, ['reports', 'laba_rugi', 'neraca', 'balance_sheet', 'trial_balance', 'cash_flow'], true);
    $isAccStockActive   = in_array($current, ['stockopname', 'adjustment'], true);
    $isAccSettingActive = in_array($current, ['coa', 'settingakun', 'kurs_pajak'], true);
    ?>

    <?php /* ---- 1. Pengajuan Dana (PRF & ERF) ---- */ ob_start(); ?>
                <?php if (check_access('prf', 'add') || check_access('prf_approval', 'view') || check_access('prf_payment', 'view') || check_access('prf_list', 'view') || check_access('prf', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_fund_prf_group"><?= lang_line('sidebar_fund_prf_group', 'PAYMENT REQUEST (PRF)'); ?></h6>
                    <?php if (check_access('prf', 'add')): ?>
                        <a data-lk="sidebar_prf_create" class="collapse-item <?= ($current == 'prf' && $current_action == 'create') ? 'active' : ''; ?>" href="<?= $base; ?>prf/create"><i class="fas fa-file-invoice" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_prf_create', 'Create Payment Request'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('prf_approval', 'view')): ?>
                        <a data-lk="sidebar_prf_approval" class="collapse-item <?= ($current == 'prf' && $current_action == 'approval_queue') ? 'active' : ''; ?>" href="<?= $base; ?>prf/approval_queue"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_prf_approval', 'Payment Request Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('prf_payment', 'view')): ?>
                        <a data-lk="sidebar_prf_payment" class="collapse-item <?= ($current == 'prf' && $current_action == 'payment') ? 'active' : ''; ?>" href="<?= $base; ?>prf/payment"><i class="fas fa-wallet" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_prf_payment', 'Payment Request Payment'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('prf_list', 'view') || check_access('prf', 'view')): ?>
                        <a data-lk="sidebar_prf_list" class="collapse-item <?= ($current == 'prf' && $current_action == 'list_all') ? 'active' : ''; ?>" href="<?= $base; ?>prf/list_all"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_prf_list', 'Payment Request List'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (check_access('erf', 'add') || check_access('erf_approval', 'view') || check_access('erf_payment', 'view') || check_access('erf_list', 'view') || check_access('erf', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_fund_erf_group"><?= lang_line('sidebar_fund_erf_group', 'REIMBURSE (ERF)'); ?></h6>
                    <?php if (check_access('erf', 'add')): ?>
                        <a data-lk="sidebar_erf_create" class="collapse-item <?= ($current == 'erf' && $current_action == 'create') ? 'active' : ''; ?>" href="<?= $base; ?>erf/create"><i class="fas fa-hand-holding-usd" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_erf_create', 'Create Reimburse Request'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('erf_approval', 'view')): ?>
                        <a data-lk="sidebar_erf_approval" class="collapse-item <?= ($current == 'erf' && $current_action == 'approval') ? 'active' : ''; ?>" href="<?= $base; ?>erf/approval"><i class="fas fa-check-double" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_erf_approval', 'Reimburse Request Approval'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('erf_payment', 'view')): ?>
                        <a data-lk="sidebar_erf_payment" class="collapse-item <?= ($current == 'erf' && $current_action == 'payment') ? 'active' : ''; ?>" href="<?= $base; ?>erf/payment"><i class="fas fa-wallet" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_erf_payment', 'Reimburse Request Payment'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('erf_list', 'view') || check_access('erf', 'view')): ?>
                        <a data-lk="sidebar_erf_list" class="collapse-item <?= ($current == 'erf' && $current_action == 'list_all') ? 'active' : ''; ?>" href="<?= $base; ?>erf/list_all"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_erf_list', 'Reimburse Request List'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
    <?php $sections['acc_fund'] = $sb_dropdown('acc_fund', 'fa-hand-holding-usd', lang_line('sidebar_acc_fund', 'Pengajuan Dana'), $isAccFundActive, ob_get_clean()); ?>

    <?php /* ---- 2. Kas, Bank & Jurnal (key lama 'accounting') ---- */ ob_start(); ?>
                <?php if (check_access('cashbank', 'view')): ?>
                    <a data-lk="sidebar_cash_bank" class="collapse-item <?= ($current == 'cashbank') ? 'active' : ''; ?>" href="<?= $base; ?>cashbank"><i class="fas fa-university" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_cash_bank', 'Cash & Bank'); ?></a>
                <?php endif; ?>
                <?php if (check_access('jurnalumum', 'view')): ?>
                    <a data-lk="sidebar_general_journal" class="collapse-item <?= ($current == 'jurnalumum') ? 'active' : ''; ?>" href="<?= $base; ?>jurnalumum"><i class="fas fa-journal-whills" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_general_journal', 'General Journal'); ?></a>
                <?php endif; ?>
                <?php if (check_access('summary_ledger', 'view')): ?>
                    <a data-lk="sidebar_summary_ledger" class="collapse-item <?= ($current == 'summary_ledger') ? 'active' : ''; ?>" href="<?= $base; ?>summary_ledger"><i class="fas fa-file-import" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_summary_ledger', 'Voucher Disbursement Upload'); ?></a>
                <?php endif; ?>
                <?php if (check_access('voucher_disbursement_list', 'view')): ?>
                    <a data-lk="sidebar_voucher_disbursement_list" class="collapse-item <?= ($current == 'summary_ledger' && $current_action == 'voucher_list') ? 'active' : ''; ?>" href="<?= $base; ?>summary_ledger/voucher_list"><i class="fas fa-file-invoice-dollar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_voucher_disbursement_list', 'Voucher Disbursement List'); ?></a>
                <?php endif; ?>
    <?php $sections['accounting'] = $sb_dropdown('accounting', 'fa-book', lang_line('sidebar_acc_ledger', 'Kas, Bank & Jurnal'), $isAccLedgerActive, ob_get_clean()); ?>

    <?php /* ---- 3. Hutang & Piutang ---- */ ob_start(); ?>
                <?php if (check_access('apinvoice', 'view') || check_access('appayment', 'view') || check_access('hutang', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_ap_group"><?= lang_line('sidebar_ap_group', 'HUTANG USAHA (AP)'); ?></h6>
                    <?php if (check_access('apinvoice', 'view')): ?>
                        <a data-lk="sidebar_account_payable" class="collapse-item <?= ($current == 'apinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>apinvoice"><i class="fas fa-file-invoice-dollar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_account_payable', 'Account Payable'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('appayment', 'view')): ?>
                        <a data-lk="sidebar_payable_payment" class="collapse-item <?= ($current == 'appayment') ? 'active' : ''; ?>" href="<?= $base; ?>appayment"><i class="fas fa-money-bill-wave" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_payable_payment', 'Payable Payment'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('hutang', 'view')): ?>
                        <a data-lk="sidebar_hutang_list" class="collapse-item <?= ($current == 'hutang') ? 'active' : ''; ?>" href="<?= $base; ?>hutang"><i class="fas fa-list-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_hutang_list', 'Daftar Hutang'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (check_access('arinvoice', 'view') || check_access('arreceipt', 'view') || check_access('piutang', 'view')): ?>
                    <h6 class="collapse-header" data-gk="sidebar_ar_group"><?= lang_line('sidebar_ar_group', 'PIUTANG USAHA (AR)'); ?></h6>
                    <?php if (check_access('arinvoice', 'view')): ?>
                        <a data-lk="sidebar_receivable_invoice" class="collapse-item <?= ($current == 'arinvoice') ? 'active' : ''; ?>" href="<?= $base; ?>arinvoice"><i class="fas fa-file-invoice-dollar" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_receivable_invoice', 'Receivable Invoice'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('arreceipt', 'view')): ?>
                        <a data-lk="sidebar_receivable_receipt" class="collapse-item <?= ($current == 'arreceipt') ? 'active' : ''; ?>" href="<?= $base; ?>arreceipt"><i class="fas fa-hand-holding-usd" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_receivable_receipt', 'Receivable Receipt'); ?></a>
                    <?php endif; ?>
                    <?php if (check_access('piutang', 'view')): ?>
                        <a data-lk="sidebar_piutang_list" class="collapse-item <?= ($current == 'piutang') ? 'active' : ''; ?>" href="<?= $base; ?>piutang"><i class="fas fa-list-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_piutang_list', 'Daftar Piutang'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
    <?php $sections['acc_apar'] = $sb_dropdown('acc_apar', 'fa-file-invoice-dollar', lang_line('sidebar_acc_apar', 'Hutang & Piutang'), $isAccAparActive, ob_get_clean()); ?>

    <?php /* ---- 4. Laporan Keuangan ---- */ ob_start(); ?>
                <?php if (check_access('laba_rugi', 'view')): ?>
                    <a data-lk="sidebar_profit_loss" class="collapse-item <?= ($current == 'reports' && $current_action == 'profit_loss') ? 'active' : ''; ?>" href="<?= $base; ?>reports/profit_loss"><i class="fas fa-chart-pie" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_profit_loss', 'Profit & Loss'); ?></a>
                <?php endif; ?>
                <?php if (check_access('neraca', 'view')): ?>
                    <a data-lk="sidebar_balance_sheet" class="collapse-item <?= ($current == 'reports' && $current_action == 'balance_sheet') ? 'active' : ''; ?>" href="<?= $base; ?>reports/balance_sheet"><i class="fas fa-balance-scale" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_balance_sheet', 'Balance Sheet'); ?></a>
                <?php endif; ?>
                <?php if (check_access('trial_balance', 'view')): ?>
                    <a data-lk="sidebar_trial_balance" class="collapse-item <?= ($current == 'reports' && $current_action == 'trial_balance') ? 'active' : ''; ?>" href="<?= $base; ?>reports/trial_balance"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_trial_balance', 'Trial Balance'); ?></a>
                <?php endif; ?>
                <?php if (check_access('cash_flow', 'view')): ?>
                    <a data-lk="sidebar_cash_flow" class="collapse-item <?= ($current == 'reports' && $current_action == 'cash_flow') ? 'active' : ''; ?>" href="<?= $base; ?>reports/cash_flow"><i class="fas fa-water" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_cash_flow', 'Cash Flow'); ?></a>
                <?php endif; ?>
    <?php $sections['acc_report'] = $sb_dropdown('acc_report', 'fa-chart-pie', lang_line('sidebar_acc_report', 'Laporan Keuangan'), $isAccReportActive, ob_get_clean()); ?>

    <?php /* ---- 5. Stock Opname ---- */ ob_start(); ?>
                <?php if (check_access('stockopname', 'view')): ?>
                    <a data-lk="sidebar_stock_opname" class="collapse-item <?= ($current == 'stockopname') ? 'active' : ''; ?>" href="<?= $base; ?>stockopname"><i class="fas fa-clipboard-check" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_opname', 'Stock Opname'); ?></a>
                <?php endif; ?>
                <?php if (check_access('adjustment', 'view')): ?>
                    <a data-lk="sidebar_stock_adjustment" class="collapse-item <?= ($current == 'adjustment') ? 'active' : ''; ?>" href="<?= $base; ?>Adjustment"><i class="fas fa-sliders-h" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_stock_adjustment', 'Stock Adjustment'); ?></a>
                <?php endif; ?>
    <?php $sections['acc_stock'] = $sb_dropdown('acc_stock', 'fa-clipboard-check', lang_line('sidebar_acc_stock', 'Stock Opname'), $isAccStockActive, ob_get_clean()); ?>

    <?php /* ---- 6. Pengaturan Akuntansi ---- */ ob_start(); ?>
                <?php if (check_access('coa', 'view')): ?>
                    <a data-lk="sidebar_coa" class="collapse-item <?= ($current == 'coa') ? 'active' : ''; ?>" href="<?= $base; ?>coa"><i class="fas fa-sitemap" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_coa', 'Chart of Account (COA)'); ?></a>
                <?php endif; ?>
                <?php if (check_access('settingakun', 'view')): ?>
                    <a data-lk="sidebar_account_settings" class="collapse-item <?= ($current == 'settingakun') ? 'active' : ''; ?>" href="<?= $base; ?>settingakun"><i class="fas fa-sliders-h" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_account_settings', 'Account Settings'); ?></a>
                <?php endif; ?>
                <?php if (check_access('kurs_pajak', 'view')): ?>
                    <a data-lk="sidebar_kurs_pajak" class="collapse-item <?= ($current == 'kurs_pajak') ? 'active' : ''; ?>" href="<?= $base; ?>kurs_pajak"><i class="fas fa-coins" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_kurs_pajak', 'Kurs Pajak'); ?></a>
                <?php endif; ?>
    <?php $sections['acc_setting'] = $sb_dropdown('acc_setting', 'fa-sliders-h', lang_line('sidebar_acc_setting', 'Pengaturan Akuntansi'), $isAccSettingActive, ob_get_clean()); ?>

    <?php ob_start(); if ($canViewMaster): ?>
    <li class="nav-item <?= $isMasterActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isMasterActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseMaster"
            aria-expanded="<?= $isMasterActive ? 'true' : 'false'; ?>" aria-controls="collapseMaster">
            <i class="fas fa-fw fa-database"></i>
            <span><?= lang_line('sidebar_master_database', 'Master Database'); ?></span>
        </a>
        <div id="collapseMaster" class="collapse <?= $isMasterActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('master_supplier', 'view')): ?>
                    <a data-lk="sidebar_master_supplier" class="collapse-item <?= ($current == 'master_supplier') ? 'active' : ''; ?>" href="<?= $base; ?>master_supplier"><i class="fas fa-truck" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_supplier', 'Supplier'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_customer', 'view')): ?>
                    <a data-lk="sidebar_master_customer" class="collapse-item <?= ($current == 'master_customer') ? 'active' : ''; ?>" href="<?= $base; ?>master_customer"><i class="fas fa-users" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_customer', 'Customer'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_material', 'view')): ?>
                    <a data-lk="sidebar_master_material" class="collapse-item <?= ($current == 'master_material') ? 'active' : ''; ?>" href="<?= $base; ?>master_material"><i class="fas fa-layer-group" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_material', 'Production Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_semifinished', 'view')): ?>
                    <a data-lk="sidebar_master_semifinished" class="collapse-item <?= ($current == 'master_semifinished') ? 'active' : ''; ?>" href="<?= $base; ?>master_semifinished"><i class="fas fa-industry" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_semifinished', 'Semi-Finished Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_fg', 'view')): ?>
                    <a data-lk="sidebar_master_fg" class="collapse-item <?= ($current == 'master_fg') ? 'active' : ''; ?>" href="<?= $base; ?>master_fg"><i class="fas fa-cube" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_fg', 'Finished Goods'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_scrap', 'view')): ?>
                    <a data-lk="sidebar_master_scrap" class="collapse-item <?= ($current == 'master_scrap') ? 'active' : ''; ?>" href="<?= $base; ?>master_scrap"><i class="fas fa-recycle" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_scrap', 'Scrap'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_inventory', 'view')): ?>
                    <a data-lk="sidebar_master_inventory" class="collapse-item <?= ($current == 'master_inventory') ? 'active' : ''; ?>" href="<?= $base; ?>master_inventory"><i class="fas fa-clipboard-list" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_inventory', 'General Stock Material'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_machine', 'view')): ?>
                    <a data-lk="sidebar_master_machine" class="collapse-item <?= ($current == 'master_machine') ? 'active' : ''; ?>" href="<?= $base; ?>master_machine"><i class="fas fa-tools" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_machine', 'Equipment & Tools'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_karyawan', 'view')): ?>
                    <a data-lk="sidebar_master_karyawan" class="collapse-item <?= ($current == 'masterkaryawan') ? 'active' : ''; ?>" href="<?= $base; ?>masterkaryawan"><i class="fas fa-id-card-alt" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_karyawan', 'Master Karyawan'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_jasa', 'view')): ?>
                    <a data-lk="sidebar_master_jasa" class="collapse-item <?= ($current == 'master_jasa') ? 'active' : ''; ?>" href="<?= $base; ?>master_jasa"><i class="fas fa-concierge-bell" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_jasa', 'Master Jasa'); ?></a>
                <?php endif; ?>
                <?php if (check_access('master_asset', 'view')): ?>
                    <a data-lk="sidebar_master_asset" class="collapse-item <?= ($current == 'master_asset') ? 'active' : ''; ?>" href="<?= $base; ?>master_asset"><i class="fas fa-building" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_master_asset', 'Master Asset'); ?></a>
                <?php endif; ?>
            </div>
            <?php echo sidebar_render_ordered_items('master', ob_get_clean(), $base); ?>
        </div>
    </li>
    <?php endif; $sections['master'] = ob_get_clean(); ?>

    <?php
        // Document Sharing — menu baru (permintaan user 2026-09-04): upload
        // dokumen dgn folder per departemen + akses per-file (private/roles).
        // Ditaruh sbg link top-level tersendiri (BUKAN di dalam collapse
        // "System Setup" di bawah) krn ini utility utk semua karyawan yang
        // punya akses, bukan tool admin-only spt menu2 System Setup lainnya.
    ?>
    <?php ob_start(); if ($canViewDocSharing): ?>
    <li class="nav-item <?= ($current == 'document_sharing') ? 'active' : ''; ?>">
        <a class="nav-link" href="<?= $base; ?>document_sharing">
            <i class="fas fa-folder-open"></i>
            <span><?= lang_line('sidebar_document_sharing', 'Document Sharing'); ?></span>
        </a>
    </li>
    <?php endif; $sections['document_sharing'] = ob_get_clean(); ?>

    <?php ob_start(); if ($canViewSystem): ?>
    <li class="nav-item <?= $isSystemActive ? 'active' : ''; ?>">
        <a class="nav-link <?= $isSystemActive ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseSystem"
            aria-expanded="<?= $isSystemActive ? 'true' : 'false'; ?>" aria-controls="collapseSystem">
            <i class="fas fa-fw fa-cogs"></i>
            <span><?= lang_line('sidebar_system_setup', 'System Setup'); ?></span>
        </a>
        <div id="collapseSystem" class="collapse <?= $isSystemActive ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded"><?php ob_start(); ?>
                <?php if (check_access('commoncode', 'view')): ?>
                    <a data-lk="sidebar_common_code" class="collapse-item <?= ($current == 'commoncode') ? 'active' : ''; ?>" href="<?= $base; ?>commoncode"><i class="fas fa-code" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_common_code', 'Common Code'); ?></a>
                <?php endif; ?>
                <?php if (check_access('roleaccess', 'view')): ?>
                    <a data-lk="sidebar_role_access" class="collapse-item <?= ($current == 'roleaccess') ? 'active' : ''; ?>" href="<?= $base; ?>roleaccess"><i class="fas fa-lock" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_role_access', 'Role Access'); ?></a>
                <?php endif; ?>
                <?php if (check_access('user', 'view')): ?>
                    <a data-lk="sidebar_user_management" class="collapse-item <?= ($current == 'user') ? 'active' : ''; ?>" href="<?= $base; ?>user"><i class="fas fa-user-tie" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_user_management', 'User Management'); ?></a>
                <?php endif; ?>
                <?php if (check_access('log_activity', 'view')): ?>
                    <a data-lk="sidebar_log_activity" class="collapse-item <?= ($current == 'log_activity') ? 'active' : ''; ?>" href="<?= $base; ?>log_activity"><i class="fas fa-history" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_log_activity', 'Log Activity'); ?></a>
                <?php endif; ?>
                <?php
                    // "Developer Mode" (editor teks/terjemahan lewat UI) - ERP
                    // Revision Notes, user 2026-09-03. Klik menu ini LANGSUNG
                    // nyalain mode (toggle on) & bawa balik ke tampilan ERP asli
                    // (dashboard) - BUKAN ke halaman tabel dulu (itu tetap ada,
                    // dibuka lewat link "Kelola semua teks" di banner Developer
                    // Mode, lihat templates/topbar.php). Kalau devmode SUDAH
                    // aktif, klik lagi menu ini = matiin (toggle biasa).
                ?>
                <?php if (check_access('developer_mode', 'view')): ?>
                    <a data-lk="sidebar_developer_mode" class="collapse-item" href="<?= $base; ?>developer_mode/toggle"><i class="fas fa-language" style="font-size: 0.7rem; margin-right: 0.3rem;"></i> <?= lang_line('sidebar_developer_mode', 'Developer Mode'); ?></a>
                <?php endif; ?>
                <?php echo sidebar_render_ordered_items('system', ob_get_clean(), $base); ?>
            </div>
        </div>
    </li>
    <?php
        endif;
        $sections['system'] = ob_get_clean();

        // SEMUA 15 section menu utama sudah ditampung -- render SESUAI
        // URUTAN CUSTOM dari Developer Mode ("Atur Urutan Sidebar", user
        // 2026-09-06). Isi & visibility tiap section TIDAK berubah sama
        // sekali, cuma URUTAN tampilnya yg bisa diatur. Lihat
        // application/helpers/sidebar_order_helper.php.
        // Label diambil dari sidebar_section_lang_keys() (sidebar_order_helper)
        // supaya halaman "Atur Urutan Sidebar" memakai kunci teks yang sama.
        $sidebar_section_labels = [];
        foreach (sidebar_section_lang_keys() as $__sk => $__def) {
            $sidebar_section_labels[$__sk] = lang_line($__def[0], $__def[1]);
        }
        // 2026-11-09: dirender per KELOMPOK dengan heading (sidebar_section_groups()).
        echo sidebar_render_grouped_sections($sections, $sidebar_section_labels);
    ?>

    <?php // Tombol bulat "ciutkan sidebar" (#sidebarToggle) DIHAPUS 2026-09-09
          // atas permintaan user. Fungsi ciutkan/lebarkan sidebar TETAP ADA
          // lewat tombol hamburger di topbar (#sidebarToggleTop) — handler
          // JS-nya di footer.php & sb-admin-2.js menargetkan KEDUA id, jadi
          // aman walau salah satunya tidak dirender. ?>

</ul>
<script>
/* Cari menu sidebar (2026-11-09): saring menu & submenu sesuai teks, dropdown yang
   cocok otomatis terbuka; Enter = buka hasil pertama, Esc = kosongkan. Vanilla JS
   (jQuery dimuat ulang di footer). Kosongkan kotak = kembali ke keadaan semula. */
(function () {
    var sb = document.getElementById('accordionSidebar'), inp = document.getElementById('sbMenuSearch');
    if (!sb || !inp) return;
    var clr = document.getElementById('sbMenuSearchClear'), empty = document.getElementById('sbMenuSearchEmpty');
    var kids = Array.prototype.slice.call(sb.children);
    var lis = kids.filter(function (el) { return el.matches('li.nav-item'); });
    var norm = function (t) { return (t || '').toLowerCase().replace(/\s+/g, ' ').trim(); };
    var saved = null;

    function run() {
        var q = norm(inp.value);
        clr.style.display = q ? 'block' : 'none';
        if (q && !saved) saved = lis.map(function (li) { var c = li.querySelector('.collapse'); return c ? c.classList.contains('show') : null; });
        var anyVisible = false;
        lis.forEach(function (li, i) {
            var link = li.querySelector('.nav-link'), col = li.querySelector('.collapse');
            var subs = col ? Array.prototype.slice.call(col.querySelectorAll('.collapse-inner > *')) : [];
            if (!q) {
                li.style.display = '';
                subs.forEach(function (e) { e.style.display = ''; });
                if (col && saved) { col.classList.toggle('show', !!saved[i]); link.classList.toggle('collapsed', !saved[i]); }
                return;
            }
            var self = norm(link ? link.textContent : '').indexOf(q) > -1, hit = self;
            subs.forEach(function (e) {
                if (e.matches('.collapse-item')) {
                    var ok = self || norm(e.textContent).indexOf(q) > -1;
                    e.style.display = ok ? '' : 'none';
                    if (ok) hit = true;
                }
            });
            // judul grup submenu (mis. "LAPORAN:") tampil hanya bila ada item di bawahnya
            subs.forEach(function (e, k) {
                if (!e.matches('.collapse-header')) return;
                var show = false;
                for (var j = k + 1; j < subs.length && !subs[j].matches('.collapse-header'); j++) {
                    if (subs[j].matches('.collapse-item') && subs[j].style.display !== 'none') { show = true; break; }
                }
                e.style.display = show ? '' : 'none';
            });
            li.style.display = hit ? '' : 'none';
            if (hit) anyVisible = true;
            if (hit && col) { col.classList.add('show'); link.classList.remove('collapsed'); }
        });
        // heading kelompok tampil hanya bila ada menu terlihat di bawahnya
        kids.forEach(function (el, k) {
            if (!el.matches('.sidebar-heading')) return;
            var show = !q;
            for (var j = k + 1; q && j < kids.length && !kids[j].matches('.sidebar-heading'); j++) {
                if (kids[j].matches('li.nav-item') && kids[j].style.display !== 'none') { show = true; break; }
            }
            el.style.display = show ? '' : 'none';
        });
        empty.style.display = (q && !anyVisible) ? 'block' : 'none';
        if (!q) saved = null;
    }
    inp.addEventListener('input', run);
    inp.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { inp.value = ''; run(); inp.blur(); }
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = Array.prototype.slice.call(sb.querySelectorAll('li.nav-item')).filter(function (li) { return li.style.display !== 'none'; })
                .map(function (li) {
                    var items = Array.prototype.slice.call(li.querySelectorAll('.collapse-item')).filter(function (a) { return a.style.display !== 'none'; });
                    if (items.length) return items[0];
                    var l = li.querySelector('.nav-link'); return (l && l.getAttribute('href') !== '#') ? l : null;
                }).filter(Boolean)[0];
            if (first) window.location.href = first.href;
        }
    });
    clr.addEventListener('click', function () { inp.value = ''; run(); inp.focus(); });
})();
</script>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
