<?php
/**
 * Struktur sidebar (sumber kebenaran urutan & grouping di halaman ini).
 * Mirror persis dari application/views/templates/sidebar.php.
 *
 * Bentuk:
 *   section_key  => kunci lang_line untuk heading section
 *   group_key    => kunci lang_line untuk label menu top-level di sidebar
 *   group_id     => identifier internal untuk data-group attribute (tidak diubah saat translasi)
 *   icon         => fontawesome class
 *   subs         => list of [sub_header_key|null, [menu_key, ...]]
 */
$sidebar_structure = [
    // ============ CORE OPERATIONS ============
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_ipo', 'group_id' => 'Sales Order Number', 'icon' => 'fas fa-file-signature', 'subs' => [
        [null, ['sales_contract', 'sales_contract_approval', 'sales_contract_list']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_irf', 'group_id' => 'Permintaan Barang', 'icon' => 'fas fa-clipboard-list', 'subs' => [
        // material_request (Create New PMR) ada di Production group sesuai sidebar.
        // Permintaan Barang hanya menampung approval & status-nya.
        ['ra_sub_prod_material', ['material_request_approval', 'material_outstanding']],
        ['ra_sub_gen_material',  ['consumable_request', 'consumable_request_approval', 'irf_status_general', 'irf_status_produksi', 'irf_status', 'consumable_outstanding_general', 'consumable_outstanding_produksi', 'consumable_outstanding']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_production', 'group_id' => 'Production', 'icon' => 'fas fa-industry', 'subs' => [
        // Urutan persis sidebar: work_order → material_request → production_process → production_qc → bom → production_receive
        [null,           ['work_order', 'material_request', 'production_process', 'production_qc', 'bom', 'production_receive']],
        ['ra_sub_report', ['qc_production']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_warehouse', 'group_id' => 'Warehouse', 'icon' => 'fas fa-warehouse', 'subs' => [
        // Mirror sidebar persis: PRO-GUDANG, lalu sub-LAPORAN, GUDANG GENERAL, sub-LAPORAN, LAINNYA.
        ['ra_sub_pro_warehouse',        ['warehouse_in', 'warehouse_out', 'material_issuance', 'prodreturn', 'purchase_request', 'purchase_request_approval']],
        ['ra_sub_pro_warehouse_report', ['stock_material']],
        ['ra_sub_gen_warehouse',        ['supplier_general_item_receipt_general', 'supplier_general_item_receipt_produksi', 'supplier_general_item_receipt', 'consumable_out_general', 'consumable_out_produksi', 'consumable_out', 'stock_transfer', 'purchase_request_general', 'purchase_request_approval_general', 'purchase_request_jasa', 'purchase_request_approval_jasa']],
        ['ra_sub_gen_warehouse_report', ['stock_inventory', 'stock_machine']],
        ['ra_sub_other',                ['initial_stock', 'stock_ledger', 'material_traceability', 'inventory_traceability', 'stock_alert']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_purchasing', 'group_id' => 'Purchasing', 'icon' => 'fas fa-shopping-cart', 'subs' => [
        ['ra_sub_prod_material', ['purchase_order', 'purchase_order_approval', 'pmpo_status']],
        ['ra_sub_gen_material',  ['purchase_order_general', 'purchase_order_approval_general', 'gmpo_status']],
        [null,                   ['purchase_order_jasa', 'purchase_order_approval_jasa']],
        ['ra_sub_report',        ['pmpo_list', 'gmpo_list', 'po_report']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_qc', 'group_id' => 'Quality Control & Assurances', 'icon' => 'fas fa-check-circle', 'subs' => [
        [null,            ['quality_assurance', 'qa_sampling']],
        ['ra_sub_report', ['qa_report', 'qa_sampling_report']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_fg', 'group_id' => 'Finished Goods', 'icon' => 'fas fa-box-open', 'subs' => [
        [null, ['fg_receipt', 'fg_receipt_approval', 'fg_stock']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_shipping', 'group_id' => 'Shipping & Transportation', 'icon' => 'fas fa-truck-loading', 'subs' => [
        // 'mir' (dulu di Purchasing > IMPORT) & 'fg_delivery' (dulu di Finished
        // Goods) dipindah ke sini 2026-09-06 mengikuti sidebar.
        [null, ['exim_verification', 'mir', 'fg_delivery']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_scrap', 'group_id' => 'Scrap', 'icon' => 'fas fa-recycle', 'subs' => [
        [null,            ['scrap_in', 'scrap_out']],
        ['ra_sub_report', ['scrap_stock']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_subkon', 'group_id' => 'Subkon', 'icon' => 'fas fa-handshake', 'subs' => [
        [null, ['subkon_order', 'subkon_out', 'subkon_in']],
    ]],
    ['section_key' => 'ra_section_core', 'group_key' => 'ra_group_it_inventory', 'group_id' => 'IT Inventory [BC]', 'icon' => 'fas fa-desktop', 'subs' => [
        [null,                     ['lap_pemasukan_bb', 'lap_pengeluaran_barang']],
        ['ra_sub_mutation_report', ['lap_mutasi_scrap', 'lapmutasimaterial', 'lapmutasifinished', 'lapmutasiwip', 'lapmutasimesin']],
    ]],

    // ============ ACCOUNTING & FINANCE ============
    ['section_key' => 'ra_section_accounting', 'group_key' => 'ra_group_finance', 'group_id' => 'Finance & Accounting', 'icon' => 'fas fa-calculator', 'subs' => [
        ['ra_sub_finance',           ['prf', 'prf_approval', 'prf_payment', 'erf', 'erf_approval', 'erf_payment']],
        ['ra_sub_finance_report',    ['prf_list', 'erf_list']],
        ['ra_sub_accounting',        ['jurnalumum', 'summary_ledger', 'voucher_disbursement_list', 'apinvoice', 'appayment', 'hutang', 'arinvoice', 'arreceipt', 'piutang', 'cashbank']],
        ['ra_sub_stock_opname',      ['stockopname', 'adjustment']],
        ['ra_sub_accounting_report', ['laba_rugi', 'neraca', 'trial_balance', 'cash_flow']],
        ['ra_sub_setting',           ['coa', 'settingakun', 'kurs_pajak']],
    ]],

    // ============ CONFIGURATION ============
    ['section_key' => 'ra_section_config', 'group_key' => 'ra_group_master', 'group_id' => 'Master Database', 'icon' => 'fas fa-database', 'subs' => [
        [null, ['master_supplier', 'master_customer', 'master_material', 'master_semifinished', 'master_fg', 'master_scrap', 'master_inventory', 'master_machine', 'master_karyawan', 'master_jasa']],
    ]],
    ['section_key' => 'ra_section_config', 'group_key' => 'ra_group_system', 'group_id' => 'System Setup', 'icon' => 'fas fa-cogs', 'subs' => [
        [null, ['commoncode', 'roleaccess', 'user', 'log_activity']],
    ]],
];

// Build lookup map: menu_key (lowercase) => permission object
$by_key = [];
$used   = [];
if (!empty($permissions)) {
    foreach ($permissions as $p) {
        $by_key[strtolower($p->menu_key)] = $p;
    }
}

/**
 * Mapping menu_key → sidebar lang_line key supaya menu_name di halaman ini
 * ikut bahasa yang dipilih (Indonesian / English / Chinese). Fallback ke
 * `menu_name` dari DB kalau key tidak dimap.
 *
 * Sumber: application/views/templates/sidebar.php — pakai lang_line yang
 * sama supaya konsisten dengan label sidebar.
 */
$menu_name_lang = [
    // CORE — IPO Number
    'sales_contract'                    => 'sidebar_ipo_create',
    'sales_contract_approval'           => 'sidebar_ipo_approval',
    'sales_contract_list'               => 'sidebar_ipo_list',
    // Permintaan Barang
    'material_request_approval'         => 'sidebar_pmr_approval',
    'material_outstanding'              => 'sidebar_pmr_status',
    'consumable_request'                => 'sidebar_irf_create',
    'consumable_request_approval'       => 'sidebar_irf_approval',
    'irf_status_general'                => 'sidebar_irf_status_general',
    'irf_status_produksi'               => 'sidebar_irf_status_produksi',
    'irf_status'                        => 'sidebar_irf_status',
    'consumable_outstanding_general'    => 'sidebar_irf_outstanding_general',
    'consumable_outstanding_produksi'   => 'sidebar_irf_outstanding_produksi',
    'consumable_outstanding'            => 'sidebar_irf_outstanding',
    // Production
    'work_order'                        => 'sidebar_work_order_create',
    'material_request'                  => 'sidebar_pmr_create',
    'production_process'                => 'sidebar_production_in_progress',
    'production_qc'                     => 'sidebar_production_qc',
    'bom'                               => 'sidebar_bom',
    'production_receive'                => 'sidebar_production_receipt',
    'qc_production'                     => 'sidebar_qc_production_report',
    // Warehouse — PRO
    'warehouse_in'                      => 'sidebar_supplier_material_receipt',
    'warehouse_out'                     => 'sidebar_supplier_material_return',
    'material_issuance'                 => 'sidebar_production_material_release',
    'prodreturn'                        => 'sidebar_prodreturn',
    'purchase_request'                  => 'sidebar_material_purchase_request',
    'purchase_request_approval'         => 'sidebar_material_pr_approval',
    'stock_material'                    => 'sidebar_stock_material_semifinished',
    // Warehouse — GEN
    'supplier_general_item_receipt_general'  => 'sidebar_sgr_general',
    'supplier_general_item_receipt_produksi' => 'sidebar_sgr_produksi',
    'supplier_general_item_receipt'     => 'sidebar_supplier_general_item_receipt',
    'consumable_out_general'            => 'sidebar_stock_item_release_general',
    'consumable_out_produksi'           => 'sidebar_stock_item_release_produksi',
    'consumable_out'                    => 'sidebar_stock_item_release',
    'stock_transfer'                    => 'sidebar_stock_transfer',
    'purchase_request_general'          => 'sidebar_general_item_purchase_request',
    'purchase_request_approval_general' => 'sidebar_general_pr_approval',
    'stock_inventory'                   => 'sidebar_stock_item_report',
    'stock_machine'                     => 'sidebar_equipment_tools_report',
    // Warehouse — OTHER
    'initial_stock'                     => 'sidebar_initial_stock',
    'stock_ledger'                      => 'sidebar_stock_ledger',
    'material_traceability'             => 'sidebar_material_traceability',
    'inventory_traceability'            => 'sidebar_inventory_traceability',
    'stock_alert'                       => 'sidebar_stock_alert',
    // Purchasing
    'purchase_order'                    => 'sidebar_pmpo_create',
    'purchase_order_approval'           => 'sidebar_pmpo_approval',
    'pmpo_status'                       => 'sidebar_pmpo_status',
    'purchase_order_general'            => 'sidebar_gmpo_create',
    'purchase_order_approval_general'   => 'sidebar_gmpo_approval',
    'gmpo_status'                       => 'sidebar_gmpo_status',
    'mir'                               => 'sidebar_import_request',
    'pmpo_list'                         => 'sidebar_pmpo_list',
    'gmpo_list'                         => 'sidebar_gmpo_list',
    'po_report'                         => 'sidebar_po_report',
    // QA
    'quality_assurance'                 => 'sidebar_quality_assurance',
    'qa_sampling'                       => 'sidebar_qa_sampling',
    'qa_report'                         => 'sidebar_qa_report',
    'qa_sampling_report'                => 'sidebar_qa_sampling_report',
    // FG
    'fg_receipt'                        => 'sidebar_fg_receipt_create',
    'fg_receipt_approval'               => 'sidebar_fg_receipt_list',
    'fg_stock'                          => 'sidebar_fg_stock_card',
    'fg_delivery'                       => 'sidebar_fg_release_permit',
    // Shipping
    'exim_verification'                 => 'sidebar_exim_verifications',
    // Scrap
    'scrap_in'                          => 'sidebar_scrap_receipt',
    'scrap_out'                         => 'sidebar_scrap_release',
    'scrap_stock'                       => 'sidebar_scrap_stock_report',
    // Subkon
    'subkon_order'                      => 'sidebar_subcon_order',
    'subkon_out'                        => 'sidebar_subcon_out',
    'subkon_in'                         => 'sidebar_subcon_in',
    // IT Inventory
    'lap_pemasukan_bb'                  => 'sidebar_goods_incoming_report',
    'lap_pengeluaran_barang'            => 'sidebar_goods_outgoing_report',
    'lap_mutasi_scrap'                  => 'sidebar_scrap_mutation_report',
    'lapmutasimaterial'                 => 'sidebar_material_mutation_report',
    'lapmutasifinished'                 => 'sidebar_fg_mutation_report',
    'lapmutasiwip'                      => 'sidebar_wip_mutation_report',
    'lapmutasimesin'                    => 'sidebar_machine_mutation_report',
    // ACCT — Finance
    'prf'                               => 'sidebar_prf_create',
    'prf_approval'                      => 'sidebar_prf_approval',
    'prf_payment'                       => 'sidebar_prf_payment',
    'erf'                               => 'sidebar_erf_create',
    'erf_approval'                      => 'sidebar_erf_approval',
    'erf_payment'                       => 'sidebar_erf_payment',
    'prf_list'                          => 'sidebar_prf_list',
    'erf_list'                          => 'sidebar_erf_list',
    // ACCT — Accounting
    'jurnalumum'                        => 'sidebar_general_journal',
    'summary_ledger'                    => 'sidebar_summary_ledger',
    'voucher_disbursement_list'         => 'sidebar_voucher_disbursement_list',
    'apinvoice'                         => 'sidebar_account_payable',
    'appayment'                         => 'sidebar_payable_payment',
    'hutang'                            => 'sidebar_hutang_list',
    'arinvoice'                         => 'sidebar_receivable_invoice',
    'arreceipt'                         => 'sidebar_receivable_receipt',
    'piutang'                           => 'sidebar_piutang_list',
    'cashbank'                          => 'sidebar_cash_bank',
    // ACCT — Stock Opname
    'stockopname'                       => 'sidebar_stock_opname',
    'adjustment'                        => 'sidebar_stock_adjustment',
    // ACCT — Report
    'laba_rugi'                         => 'sidebar_profit_loss',
    'neraca'                            => 'sidebar_balance_sheet',
    'trial_balance'                     => 'sidebar_trial_balance',
    'cash_flow'                         => 'sidebar_cash_flow',
    // ACCT — Setting
    'coa'                               => 'sidebar_coa',
    'settingakun'                       => 'sidebar_account_settings',
    'kurs_pajak'                        => 'sidebar_kurs_pajak',
    // Master
    'master_supplier'                   => 'sidebar_master_supplier',
    'master_customer'                   => 'sidebar_master_customer',
    'master_material'                   => 'sidebar_master_material',
    'master_semifinished'               => 'sidebar_master_semifinished',
    'master_fg'                         => 'sidebar_master_fg',
    'master_scrap'                      => 'sidebar_master_scrap',
    'master_inventory'                  => 'sidebar_master_inventory',
    'master_machine'                    => 'sidebar_master_machine',
    'master_karyawan'                   => 'sidebar_master_karyawan',
    // System
    'commoncode'                        => 'sidebar_common_code',
    'roleaccess'                        => 'sidebar_role_access',
    'user'                              => 'sidebar_user_management',
    'log_activity'                      => 'sidebar_log_activity',
];
?>

<div class="container-fluid">
  <h3 class="mb-4 text-gray-800 d-flex align-items-center">
    <i class="fas fa-lock mr-2 text-primary"></i>
    <?= lang_line('ra_manage_title', 'Kelola Hak Akses:'); ?>
    <span class="text-dark font-weight-bold ml-2"><?= isset($role->role_name) ? $role->role_name : ''; ?></span>
  </h3>

  <div class="card shadow border-0">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="fas fa-user-shield mr-2"></i><?= lang_line('ra_matrix_title', 'Role Permission Matrix'); ?></span>
      <div class="btn-group" role="group">
        <a href="<?= base_url('roleaccess/export_permission/'.$role->id); ?>" class="btn btn-light btn-sm font-weight-bold">
          <i class="fas fa-download mr-1"></i> <?= lang_line('ra_btn_excel', 'Export Excel'); ?>
        </a>
        <a href="<?= base_url('roleaccess'); ?>" class="btn btn-light btn-sm btn-load">
          <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('ra_btn_back', 'Kembali'); ?>
        </a>
      </div>
    </div>

    <form method="post" action="<?= base_url('roleaccess/save_permission/'.$role->id); ?>">
      <div class="card-body">
        <div class="table-responsive sticky-table-wrapper">
          <table class="table table-bordered table-hover align-middle sticky-header-table">
            <thead class="thead-light text-center">
              <tr>
                <th style="width:4%"><?= lang_line('ra_th_no', 'No'); ?></th>
                <th><?= lang_line('ra_th_module_name', 'Nama Modul'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_view', 'View'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_add', 'Add'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_edit', 'Edit'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_confirm', 'Confirm'); ?></th>
                <th style="width:8%"><?= lang_line('ra_th_head_dept', 'Head of Dept'); ?></th>
                <th style="width:8%"><?= lang_line('ra_th_endorsement', 'Endorsement'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_approval', 'Approval'); ?></th>
                <th style="width:7%"><?= lang_line('ra_th_delete', 'Delete'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($permissions)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4"><?= lang_line('ra_no_modules', 'Tidak ada data modul ditemukan.'); ?></td></tr>
              <?php else: ?>

                <?php
                $prev_section = null;
                foreach ($sidebar_structure as $group_def):
                    // Hitung dulu menu mana saja yang benar-benar ada di DB untuk group ini
                    $group_has_rows = false;
                    foreach ($group_def['subs'] as $sub) {
                        foreach ($sub[1] as $mk) {
                            if (isset($by_key[strtolower($mk)])) { $group_has_rows = true; break 2; }
                        }
                    }
                    if (!$group_has_rows) continue;

                    // Section heading (Core Operations / Accounting & Finance / Configuration)
                    if ($group_def['section_key'] !== $prev_section):
                        $prev_section = $group_def['section_key'];
                ?>
                  <tr class="section-header">
                    <td colspan="10"><i class="fas fa-bars mr-2"></i><?= htmlspecialchars(lang_line($group_def['section_key'], $group_def['section_key'])); ?></td>
                  </tr>
                <?php endif; ?>

                  <!-- Group header row (with level buttons) -->
                  <tr class="group-header bg-light">
                    <td colspan="10">
                      <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <strong>
                          <i class="<?= htmlspecialchars($group_def['icon']); ?> mr-2 text-primary"></i>
                          <?= htmlspecialchars(lang_line($group_def['group_key'], $group_def['group_id'])); ?>
                        </strong>
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" class="btn btn-warning text-dark" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'Direktur')" title="<?= lang_line('ra_level_tooltip_full', 'Full akses'); ?>">
                            <i class="fas fa-gem mr-1"></i><?= lang_line('ra_level_direktur', 'Direktur'); ?>
                          </button>
                          <button type="button" class="btn btn-dark" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'Manager')" title="<?= lang_line('ra_level_tooltip_full', 'Full akses'); ?>">
                            <i class="fas fa-crown mr-1"></i><?= lang_line('ra_level_manager', 'Manager'); ?>
                          </button>
                          <button type="button" class="btn btn-primary" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'SPV')" title="<?= lang_line('ra_level_tooltip_spv', 'View, Add, Edit, Confirm, Approval'); ?>">
                            <i class="fas fa-user-tie mr-1"></i><?= lang_line('ra_level_spv', 'SPV'); ?>
                          </button>
                          <button type="button" class="btn btn-info" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'Leader')" title="<?= lang_line('ra_level_tooltip_basic', 'View, Add'); ?>">
                            <i class="fas fa-user-check mr-1"></i><?= lang_line('ra_level_leader', 'Leader'); ?>
                          </button>
                          <button type="button" class="btn btn-secondary" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'Staff')" title="<?= lang_line('ra_level_tooltip_basic', 'View, Add'); ?>">
                            <i class="fas fa-user mr-1"></i><?= lang_line('ra_level_staff', 'Staff'); ?>
                          </button>
                          <button type="button" class="btn btn-outline-danger" onclick="applyLevel('<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>', 'None')" title="<?= lang_line('ra_level_tooltip_clear', 'Clear all'); ?>">
                            <i class="fas fa-times mr-1"></i><?= lang_line('ra_level_clear', 'Clear'); ?>
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>

                  <?php $no = 1; foreach ($group_def['subs'] as $sub): ?>
                    <?php list($sub_header_key, $menu_keys) = $sub; ?>
                    <?php
                      // Filter hanya menu_key yang ada di DB
                      $rows = [];
                      foreach ($menu_keys as $mk) {
                          $lk = strtolower($mk);
                          if (isset($by_key[$lk])) {
                              $rows[] = $by_key[$lk];
                              $used[$lk] = true;
                          }
                      }
                      if (empty($rows)) continue;
                    ?>

                    <?php if ($sub_header_key): ?>
                      <tr class="sub-header">
                        <td colspan="10"><i class="fas fa-angle-right mr-2"></i><?= htmlspecialchars(lang_line($sub_header_key, $sub_header_key)); ?></td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $perm): ?>
                      <tr data-group="<?= htmlspecialchars($group_def['group_id'], ENT_QUOTES); ?>">
                        <td class="text-center"><?= $no++; ?></td>
                        <?php
                            $mkl = strtolower($perm->menu_key);
                            $menu_label = isset($menu_name_lang[$mkl])
                                ? lang_line($menu_name_lang[$mkl], $perm->menu_name)
                                : $perm->menu_name;
                        ?>
                        <td><i class="fas fa-folder-open text-secondary mr-2"></i><?= htmlspecialchars($menu_label); ?></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="view"        name="perm[<?= $perm->menu_id; ?>][view]"        <?= $perm->can_view        ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="add"         name="perm[<?= $perm->menu_id; ?>][add]"         <?= $perm->can_add         ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="edit"        name="perm[<?= $perm->menu_id; ?>][edit]"        <?= $perm->can_edit        ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="confirm"     name="perm[<?= $perm->menu_id; ?>][confirm]"     <?= !empty($perm->can_confirm)     ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="head_dept"   name="perm[<?= $perm->menu_id; ?>][head_dept]"   <?= !empty($perm->can_head_dept)   ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="endorsement" name="perm[<?= $perm->menu_id; ?>][endorsement]" <?= !empty($perm->can_endorsement) ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="approval"    name="perm[<?= $perm->menu_id; ?>][approval]"    <?= !empty($perm->can_approval)    ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                        <td class="text-center"><label class="switch"><input type="checkbox" data-type="delete"      name="perm[<?= $perm->menu_id; ?>][delete]"      <?= $perm->can_delete      ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>

                <?php endforeach; ?>

                <?php
                // Render any DB menus NOT mapped above, grouped under "OTHER"
                $leftover = [];
                foreach ($by_key as $lk => $perm) {
                    if (!isset($used[$lk])) $leftover[] = $perm;
                }
                if (!empty($leftover)):
                ?>
                  <tr class="section-header">
                    <td colspan="10"><i class="fas fa-bars mr-2"></i><?= lang_line('ra_section_other', 'Other'); ?></td>
                  </tr>
                  <tr class="group-header bg-light">
                    <td colspan="10">
                      <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <strong><i class="fas fa-ellipsis-h mr-2 text-primary"></i><?= lang_line('ra_group_uncategorized', 'Uncategorized'); ?></strong>
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" class="btn btn-warning text-dark" onclick="applyLevel('__other__', 'Direktur')"><i class="fas fa-gem mr-1"></i><?= lang_line('ra_level_direktur', 'Direktur'); ?></button>
                          <button type="button" class="btn btn-dark"               onclick="applyLevel('__other__', 'Manager')"><i class="fas fa-crown mr-1"></i><?= lang_line('ra_level_manager', 'Manager'); ?></button>
                          <button type="button" class="btn btn-primary"            onclick="applyLevel('__other__', 'SPV')"><i class="fas fa-user-tie mr-1"></i><?= lang_line('ra_level_spv', 'SPV'); ?></button>
                          <button type="button" class="btn btn-info"               onclick="applyLevel('__other__', 'Leader')"><i class="fas fa-user-check mr-1"></i><?= lang_line('ra_level_leader', 'Leader'); ?></button>
                          <button type="button" class="btn btn-secondary"          onclick="applyLevel('__other__', 'Staff')"><i class="fas fa-user mr-1"></i><?= lang_line('ra_level_staff', 'Staff'); ?></button>
                          <button type="button" class="btn btn-outline-danger"     onclick="applyLevel('__other__', 'None')"><i class="fas fa-times mr-1"></i><?= lang_line('ra_level_clear', 'Clear'); ?></button>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <?php $no = 1; foreach ($leftover as $perm): ?>
                    <?php
                        $mkl = strtolower($perm->menu_key);
                        $menu_label = isset($menu_name_lang[$mkl])
                            ? lang_line($menu_name_lang[$mkl], $perm->menu_name)
                            : $perm->menu_name;
                    ?>
                    <tr data-group="__other__">
                      <td class="text-center"><?= $no++; ?></td>
                      <td><i class="fas fa-folder-open text-secondary mr-2"></i><?= htmlspecialchars($menu_label); ?> <small class="text-muted">(<?= htmlspecialchars($perm->menu_key); ?>)</small></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="view"        name="perm[<?= $perm->menu_id; ?>][view]"        <?= $perm->can_view        ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="add"         name="perm[<?= $perm->menu_id; ?>][add]"         <?= $perm->can_add         ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="edit"        name="perm[<?= $perm->menu_id; ?>][edit]"        <?= $perm->can_edit        ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="confirm"     name="perm[<?= $perm->menu_id; ?>][confirm]"     <?= !empty($perm->can_confirm)     ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="head_dept"   name="perm[<?= $perm->menu_id; ?>][head_dept]"   <?= !empty($perm->can_head_dept)   ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="endorsement" name="perm[<?= $perm->menu_id; ?>][endorsement]" <?= !empty($perm->can_endorsement) ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="approval"    name="perm[<?= $perm->menu_id; ?>][approval]"    <?= !empty($perm->can_approval)    ? 'checked' : ''; ?>><span class="slider slider-orange"></span></label></td>
                      <td class="text-center"><label class="switch"><input type="checkbox" data-type="delete"      name="perm[<?= $perm->menu_id; ?>][delete]"      <?= $perm->can_delete      ? 'checked' : ''; ?>><span class="slider"></span></label></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>

              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-right">
        <button type="submit" class="btn btn-success btn-load">
          <i class="fas fa-save mr-1"></i> <?= lang_line('ra_btn_save_changes', 'Simpan Perubahan'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Styling -->
<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #1d3557 0%, #457b9d 100%) !important;
  }

  /* Section heading (Core Operations / Accounting & Finance / Configuration) */
  .section-header td {
    background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%) !important;
    color: #fff !important;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.6rem 1rem !important;
    border-bottom: 2px solid var(--brand-dark) !important;
  }

  /* Group header (top-level sidebar menu) */
  .group-header { font-weight: 600; }
  .group-header td {
    padding: 0.9rem 1rem !important;
    border-bottom: 2px solid #dee2e6 !important;
    background-color: #f5f6f8 !important;
  }
  .group-header strong { font-size: 0.95rem; }

  /* Sub-header (PRO-WAREHOUSE, FINANCE, REPORT, etc.) */
  .sub-header td {
    background-color: #ebedf0 !important;
    color: #555 !important;
    font-weight: 600;
    font-size: 0.78rem !important;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    padding: 0.45rem 1rem 0.45rem 2rem !important;
    border-bottom: 1px solid #d0d3d8 !important;
    box-shadow: inset 3px 0 0 var(--brand-1);
  }

  /* Sticky table header: stays visible on page scroll */
  .sticky-table-wrapper { overflow: visible !important; }
  .sticky-header-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background-color: #e9ecef;
    box-shadow: inset 0 -1px 0 #dee2e6, 0 2px 4px rgba(0,0,0,0.05);
  }
  body.dark-mode .sticky-header-table thead th {
    background-color: #2c3e50;
    color: #f1f1f1;
  }
  .switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
  }
  .switch input { opacity: 0; width: 0; height: 0; }
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 24px;
  }
  .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
  }
  input:checked + .slider { background-color: #457b9d; }
  input:checked + .slider.slider-orange { background-color: #e67e22; }
  input:checked + .slider:before { transform: translateX(22px); }
  table th, table td { vertical-align: middle !important; }
  .btn-group-sm .btn { padding: 0.35rem 0.65rem; font-size: 0.875rem; }
</style>

<!-- JavaScript: Apply permission level (group-scoped) -->
<script>
const LEVELS = {
  'Direktur':{ view: true,  add: true,  edit: true,  confirm: true,  head_dept: true,  endorsement: true,  approval: true,  del: true  },
  'Manager': { view: true,  add: true,  edit: true,  confirm: true,  head_dept: true,  endorsement: true,  approval: true,  del: true  },
  'SPV':     { view: true,  add: true,  edit: true,  confirm: true,  head_dept: false, endorsement: false, approval: true,  del: false },
  'Leader':  { view: true,  add: true,  edit: false, confirm: false, head_dept: false, endorsement: false, approval: false, del: false },
  'Staff':   { view: true,  add: true,  edit: false, confirm: false, head_dept: false, endorsement: false, approval: false, del: false },
  'None':    { view: false, add: false, edit: false, confirm: false, head_dept: false, endorsement: false, approval: false, del: false }
};

function applyLevel(group, level) {
  const perm = LEVELS[level];
  const rows = document.querySelectorAll(`tr[data-group="${CSS.escape(group)}"]`);

  rows.forEach(row => {
    const fields = ['view','add','edit','confirm','head_dept','endorsement','approval','delete'];
    fields.forEach(f => {
      const el = row.querySelector(`input[data-type="${f}"]`);
      if (!el) return;
      el.checked = (f === 'delete') ? perm.del : perm[f];
    });
  });
}
</script>

<!-- SweetAlert success feedback -->
<?php if($this->session->flashdata('success')): ?>
<script>
Swal.fire({
  icon: 'success',
  title: <?= json_encode(lang_line('ra_swal_success_title', 'Berhasil!')); ?>,
  text: '<?= $this->session->flashdata('success'); ?>',
  timer: 2000,
  showConfirmButton: false
});
</script>
<?php endif; ?>
