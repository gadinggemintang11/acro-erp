<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * TEMPLATE HAK AKSES — assets/NSI - ERP ROLE LEVEL.pdf
 * ------------------------------------------------------------
 * Role Level tahap 2 (klien 2026-09-15). File ini adalah salinan isi PDF
 * dalam bentuk data, dipakai Role_template untuk:
 *   - membuat role Departemen x Posisi yang belum ada, dan
 *   - mengisi matriks hak akses sebuah role di halaman Kelola Hak Akses.
 *
 * Kalau PDF direvisi, CUKUP ubah file ini.
 *
 * Keputusan yang dikonfirmasi user (2026-09-15):
 *  - Hlm 8 "Production Department" (IRF, PO) = departemen Production juga,
 *    digabung dengan hlm 7.
 *  - General Warehouse (GWH) & Logistic (LOG) = departemen baru.
 *  - Hlm 8 digabung ke PRO (Production); PUR belum dikonfirmasi punya aturan PDF.
 *  - Administration: "Supervisor/Admin/Clerk" dibaca posisi Supervisor.
 *  - Dokumen Admin/Clerk di-SUBMIT Supervisor; kalau departemen tidak punya
 *    Supervisor, Admin/Clerk ikut boleh submit (lihat 'submit_fallback').
 *  - Modul/departemen di luar PDF tetap diatur manual: template TIDAK
 *    menyentuh menu yang tidak dipetakan di 'documents'.
 *
 * Kunci departemen = kode master Department di production (user 2026-09-15):
 * ACC, ADM, GWH, LOG, MGM, PIE, PRO, PUR, PWH, QAD. PUR (Purchasing) tidak
 * ada di PDF -> tidak punya template (diatur manual).
 *
 * OWN DEPT vs ALL DEPT dicatat per aksi -> role_permissions.all_dept_actions.
 * ============================================================
 */

// Aksi di PDF -> kolom izin (role_permissions.can_*) per MENU ditentukan di
// 'documents'. Kunci aksi:
//   MAKER, SUBMIT, EDIT, EDIT_BEFORE_SUBMIT, DELETE, ENDORSE,
//   APPROVE_HOD, APPROVE_DIRECTOR, MONITORING
$config['role_template_actions'] = [
    'MAKER'              => 'Maker',
    'SUBMIT'             => 'Submit',
    'EDIT'               => 'Edit',
    'EDIT_BEFORE_SUBMIT' => 'Edit Before Submit',
    'DELETE'             => 'Delete',
    'ENDORSE'            => 'Endorse',
    'APPROVE_HOD'        => 'Approve (Lvl. Head of Dept)',
    'APPROVE_DIRECTOR'   => 'Approve (Lvl. Director)',
    'MONITORING'         => 'Monitoring',
];

/*
 * Dokumen PDF -> menu. Per menu: aksi => kolom yang diberi.
 * '*' = diberikan kalau posisi punya aksi APA PUN pada dokumen ini
 *       (dipakai untuk halaman status/laporan yang sifatnya baca saja).
 * Kolom: view, add, edit, submit, confirm, head_dept, endorsement, approval, delete
 */
$main   = ['MAKER' => ['view', 'add'], 'SUBMIT' => ['view', 'submit'], 'EDIT' => ['view', 'edit'], 'EDIT_BEFORE_SUBMIT' => ['view', 'edit'], 'DELETE' => ['view', 'delete']];
$stage3 = ['APPROVE_HOD' => ['view', 'head_dept'], 'ENDORSE' => ['view', 'endorsement'], 'APPROVE_DIRECTOR' => ['view', 'approval']];
$single = $main + ['APPROVE_HOD' => ['view', 'confirm']]; // modul approve 1 tahap (izin Confirm)
$read   = ['*' => ['view']];

$config['role_template_documents'] = [
    'IRF' => ['label' => 'Permintaan Barang', 'menus' => [
        'consumable_request'              => $main,
        'consumable_request_approval'     => $stage3,
        'irf_status_general'              => $read,
        'irf_status_produksi'             => $read,
        'consumable_outstanding_general'  => $read,
        'consumable_outstanding_produksi' => $read,
    ]],
    'PRF' => ['label' => 'Payment Request', 'menus' => [
        'prf'          => $main,
        'prf_approval' => $stage3,
    ]],
    'ERF' => ['label' => 'Reimburse Request', 'menus' => [
        'erf'          => $main,
        'erf_approval' => $stage3,
    ]],
    'ACC_REPORT' => ['label' => 'All Acc. Report', 'menus' => [
        'laba_rugi' => $read, 'neraca' => $read, 'trial_balance' => $read, 'cash_flow' => $read,
        'prf_list'  => $read, 'erf_list' => $read,
    ]],
    'MRF' => ['label' => 'Material Request', 'menus' => [
        'material_request'          => $main,
        'material_request_approval' => $stage3,
        'material_outstanding'      => $read,
    ]],
    // IPO tidak punya tahap HOD: alurnya Submit -> Endorse -> Approve. Approve
    // level HOD di PDF dipetakan ke tahap Endorse.
    'IPO' => ['label' => 'Sales Order', 'menus' => [
        'sales_contract'          => $main,
        'sales_contract_approval' => ['APPROVE_HOD' => ['view', 'endorsement'], 'ENDORSE' => ['view', 'endorsement'], 'APPROVE_DIRECTOR' => ['view', 'approval']],
        'sales_contract_list'     => $read,
    ]],
    'PROD_IN_PROGRESS' => ['label' => 'Prod. In Progress', 'menus' => [
        'production_process' => $single,
    ]],
    'RETURN_TO_PWH' => ['label' => 'Return to PWH', 'menus' => [
        'prodreturn' => $single,
    ]],
    'QA_TEST' => ['label' => 'Quality Assurance Test', 'menus' => [
        'quality_assurance'  => $single,
        'qa_sampling'        => $main,
        'qa_report'          => $read,
        'qa_sampling_report' => $read,
    ]],
    'PO' => ['label' => 'PO', 'menus' => [
        'purchase_order'                  => $main,
        'purchase_order_general'          => $main,
        'purchase_order_jasa'             => $main,
        'purchase_order_asset'            => $main,
        'purchase_order_approval'         => $stage3,
        'purchase_order_approval_general' => $stage3,
        'purchase_order_approval_jasa'    => $stage3,
        'purchase_order_approval_asset'   => $stage3,
        'pmpo_status'                     => $read,
        'gmpo_status'                     => $read,
    ]],
    // "RECEIVING" & "RETURN TO SUPPLIER" di PDF muncul di 2 departemen gudang
    // dengan menu berbeda.
    'RECEIVING_GWH' => ['label' => 'Receiving', 'menus' => [
        'supplier_general_item_receipt_general'  => $single,
        'supplier_general_item_receipt_produksi' => $single,
        'supplier_general_item_receipt'          => $single,
    ]],
    'RETURN_SUPPLIER_GWH' => ['label' => 'Return to Supplier', 'menus' => [],
        'note' => 'Belum ada menu retur ke supplier untuk General Warehouse.'],
    'RECEIVING_PWH' => ['label' => 'Receiving', 'menus' => [
        'warehouse_in' => $single,
    ]],
    'RETURN_SUPPLIER_PWH' => ['label' => 'Return to Supplier', 'menus' => [
        'warehouse_out' => $single,
    ]],
    'IMPORT' => ['label' => 'Import', 'menus' => [
        'mir'               => $single,
        'exim_verification' => ['*' => ['view'], 'EDIT' => ['view', 'edit'], 'EDIT_BEFORE_SUBMIT' => ['view', 'edit']],
    ]],
    'EXPORT' => ['label' => 'Export', 'menus' => [
        'fg_delivery' => $main,
    ], 'note' => 'Dipetakan ke FG Release Permit — perlu konfirmasi klien.'],
];
/*
 * Kolom mana yang boleh DICABUT saat template diterapkan.
 *   'pdf_actions' (bawaan) -> hanya kolom yang memang punya aksi di PDF
 *        (Maker/Submit/Edit/Delete/Endorse/Approve). Hak lihat dan kolom di
 *        halaman baca-saja tidak pernah dicabut — user 2026-09-17: "yang sudah
 *        ada sekarang gaperlu di cabut kecuali dimention di pdf".
 *   'all_columns' -> template menulis ulang seluruh kolom menu yang dicakup.
 */
$config['role_template_revoke'] = 'pdf_actions';

/*
 * Hak yang TIDAK dicabut walaupun PDF tidak memberikannya, per
 * 'DEPARTEMEN.POSISI' -> [menu => [kolom]]. Hanya menahan pencabutan: kalau
 * role memang belum punya hak itu, tidak ditambahkan.
 */
$config['role_template_keep'] = [
    // User 2026-09-17: "biarkan submit irf tetap" — Admin/Clerk Production
    // (Dewi) & Production Warehouse (Gilang) tetap submit IRF sendiri.
    'PRO.ADMIN_CLERK' => ['consumable_request' => ['submit']],
    'PWH.ADMIN_CLERK' => ['consumable_request' => ['submit']],
];


/*
 * Seberapa luas template boleh MENULIS ULANG hak akses sebuah role:
 *   'position' (bawaan) = hanya dokumen yang disebut PDF untuk posisi itu.
 *       Dokumen lain dibiarkan apa adanya, walau dokumen itu muncul di PDF
 *       untuk posisi departemen lain. Dipilih user 2026-09-17: "jika
 *       sebelumnya uda diatur di db tapi ga di mention di pdf biarkan saja".
 *   'all' = semua menu dokumen PDF ditulis ulang untuk semua posisi (mis.
 *       role Produksi kehilangan akses PRF, karena PRF di PDF bukan miliknya).
 */
$config['role_template_override_scope'] = 'position';

// Menu yang TIDAK ikut diberi View oleh aksi MONITORING (pengaturan sistem).
$config['role_template_monitoring_exclude'] = ['commoncode', 'roleaccess', 'user', 'user_approval', 'developer_mode'];

/*
 * Departemen & posisi sesuai PDF. Format per posisi:
 *   AKSI => ['own' => [dokumen...], 'all' => [dokumen...]]
 */
$irf_prf_erf = ['IRF', 'PRF', 'ERF'];
$gw_docs     = ['IRF', 'RECEIVING_GWH', 'RETURN_SUPPLIER_GWH'];
$gw_all      = ['RECEIVING_GWH', 'RETURN_SUPPLIER_GWH'];
$log_own     = ['IRF', 'PRF'];
$log_all     = ['IMPORT', 'EXPORT'];
$prod_docs   = ['IRF', 'MRF', 'IPO', 'PROD_IN_PROGRESS', 'RETURN_TO_PWH', 'PO'];
$pwh_docs    = ['IRF', 'MRF', 'RECEIVING_PWH', 'RETURN_SUPPLIER_PWH'];
$pwh_all     = ['RECEIVING_PWH', 'RETURN_SUPPLIER_PWH'];
$qa_docs     = ['IRF', 'QA_TEST'];

$config['role_template_departments'] = [
    'ACC' => ['pdf_name' => 'Accounting Department', 'positions' => [
        'OFFICER' => [
            'MAKER'   => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
            'SUBMIT'  => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
            'EDIT'    => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
            'DELETE'  => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
            'ENDORSE' => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
        ],
        'ADMIN_CLERK' => [
            'MAKER' => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
            'EDIT'  => ['own' => $irf_prf_erf, 'all' => ['IRF', 'PRF', 'ERF', 'ACC_REPORT']],
        ],
    ]],
    'ADM' => ['pdf_name' => 'Administration Department', 'positions' => [
        'HOD' => [
            'MAKER'       => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
            'SUBMIT'      => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
            'EDIT'        => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
            'DELETE'      => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
            'APPROVE_HOD' => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $irf_prf_erf, 'all' => []],
            'SUBMIT' => ['own' => $irf_prf_erf, 'all' => ['PRF', 'ERF']],
            'EDIT'   => ['own' => $irf_prf_erf, 'all' => []],
        ],
        'ADMIN_CLERK' => [
            'MAKER' => ['own' => $irf_prf_erf, 'all' => []],
            'EDIT'  => ['own' => $irf_prf_erf, 'all' => []],
        ],
    ]],
    'GWH' => ['pdf_name' => 'General Warehouse Department', 'positions' => [
        'OFFICER' => [
            'MAKER'       => ['own' => $gw_docs, 'all' => $gw_all],
            'SUBMIT'      => ['own' => $gw_docs, 'all' => $gw_all],
            'EDIT'        => ['own' => $gw_docs, 'all' => $gw_all],
            'DELETE'      => ['own' => $gw_docs, 'all' => $gw_all],
            'APPROVE_HOD' => ['own' => $gw_docs, 'all' => $gw_all],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $gw_docs, 'all' => $gw_all],
            'SUBMIT' => ['own' => $gw_docs, 'all' => $gw_all],
            'EDIT'   => ['own' => $gw_docs, 'all' => $gw_all],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => $gw_docs, 'all' => $gw_all],
            'EDIT_BEFORE_SUBMIT' => ['own' => $gw_docs, 'all' => $gw_all],
        ],
    ]],
    'LOG' => ['pdf_name' => 'Logistic Department', 'positions' => [
        'OFFICER' => [
            'MAKER'       => ['own' => $log_own, 'all' => $log_all],
            'SUBMIT'      => ['own' => $log_own, 'all' => $log_all],
            'EDIT'        => ['own' => $log_own, 'all' => $log_all],
            'DELETE'      => ['own' => $log_own, 'all' => $log_all],
            'APPROVE_HOD' => ['own' => $log_own, 'all' => $log_all],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $log_own, 'all' => $log_all],
            'SUBMIT' => ['own' => $log_own, 'all' => $log_all],
            'EDIT'   => ['own' => $log_own, 'all' => $log_all],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => $log_own, 'all' => $log_all],
            'EDIT_BEFORE_SUBMIT' => ['own' => $log_own, 'all' => $log_all],
        ],
    ]],
    'MGM' => ['pdf_name' => 'Management Department', 'positions' => [
        'DIRECTOR' => [
            'MAKER'            => ['own' => $irf_prf_erf, 'all' => []],
            'SUBMIT'           => ['own' => $irf_prf_erf, 'all' => []],
            'EDIT'             => ['own' => $irf_prf_erf, 'all' => []],
            'DELETE'           => ['own' => $irf_prf_erf, 'all' => []],
            'APPROVE_DIRECTOR' => ['own' => ['IRF', 'PRF', 'ERF', 'PO'], 'all' => ['IRF', 'PRF', 'ERF', 'PO']],
            'MONITORING'       => ['own' => ['*'], 'all' => ['*']],
        ],
        'ASST_DIRECTOR' => [
            'MAKER'      => ['own' => $irf_prf_erf, 'all' => []],
            'SUBMIT'     => ['own' => $irf_prf_erf, 'all' => []],
            'EDIT'       => ['own' => $irf_prf_erf, 'all' => []],
            'DELETE'     => ['own' => $irf_prf_erf, 'all' => []],
            'MONITORING' => ['own' => ['*'], 'all' => ['*']],
        ],
    ]],
    'PIE' => ['pdf_name' => 'Production & Industrial Engineering Department', 'positions' => [
        'HOD' => [
            'MAKER'       => ['own' => ['IRF'], 'all' => []],
            'SUBMIT'      => ['own' => ['IRF'], 'all' => []],
            'EDIT'        => ['own' => ['IRF'], 'all' => []],
            'DELETE'      => ['own' => ['IRF'], 'all' => []],
            'APPROVE_HOD' => ['own' => ['IRF'], 'all' => []],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => ['IRF'], 'all' => []],
            'SUBMIT' => ['own' => $irf_prf_erf, 'all' => []],
            'EDIT'   => ['own' => ['IRF'], 'all' => []],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => ['IRF'], 'all' => []],
            'EDIT_BEFORE_SUBMIT' => ['own' => ['IRF'], 'all' => []],
        ],
    ]],
    // Hlm 7 (IRF, MRF, IPO, Prod. In Progress, Return to PWH) + hlm 8 (IRF, PO).
    'PRO' => ['pdf_name' => 'Production Department', 'positions' => [
        'HOD' => [
            'MAKER'       => ['own' => $prod_docs, 'all' => ['PO']],
            'SUBMIT'      => ['own' => $prod_docs, 'all' => ['PO']],
            'EDIT'        => ['own' => $prod_docs, 'all' => ['PO']],
            'DELETE'      => ['own' => $prod_docs, 'all' => ['PO']],
            'APPROVE_HOD' => ['own' => $prod_docs, 'all' => ['PO']],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $prod_docs, 'all' => ['PO']],
            'SUBMIT' => ['own' => $prod_docs, 'all' => ['PO']],
            'EDIT'   => ['own' => $prod_docs, 'all' => ['PO']],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => $prod_docs, 'all' => []],
            'EDIT_BEFORE_SUBMIT' => ['own' => $prod_docs, 'all' => []],
        ],
    ]],
    'PWH' => ['pdf_name' => 'Production Warehouse Department', 'positions' => [
        'OFFICER' => [
            'MAKER'       => ['own' => $pwh_docs, 'all' => $pwh_all],
            'SUBMIT'      => ['own' => $pwh_docs, 'all' => $pwh_all],
            'EDIT'        => ['own' => $pwh_docs, 'all' => $pwh_all],
            'DELETE'      => ['own' => $pwh_docs, 'all' => $pwh_all],
            'APPROVE_HOD' => ['own' => $pwh_docs, 'all' => $pwh_all],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $pwh_docs, 'all' => $pwh_all],
            'SUBMIT' => ['own' => $pwh_docs, 'all' => $pwh_all],
            'EDIT'   => ['own' => $pwh_docs, 'all' => $pwh_all],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => $pwh_docs, 'all' => $pwh_all],
            'EDIT_BEFORE_SUBMIT' => ['own' => $pwh_docs, 'all' => $pwh_all],
        ],
    ]],
    'QAD' => ['pdf_name' => 'Quality Assurance Department', 'positions' => [
        'HOD' => [
            'MAKER'       => ['own' => $qa_docs, 'all' => []],
            'SUBMIT'      => ['own' => $qa_docs, 'all' => []],
            'EDIT'        => ['own' => $qa_docs, 'all' => []],
            'DELETE'      => ['own' => $qa_docs, 'all' => []],
            'APPROVE_HOD' => ['own' => $qa_docs, 'all' => []],
        ],
        'SUPERVISOR' => [
            'MAKER'  => ['own' => $qa_docs, 'all' => []],
            'SUBMIT' => ['own' => $qa_docs, 'all' => []],
            'EDIT'   => ['own' => $qa_docs, 'all' => []],
        ],
        'ADMIN_CLERK' => [
            'MAKER'              => ['own' => $qa_docs, 'all' => []],
            'EDIT_BEFORE_SUBMIT' => ['own' => $qa_docs, 'all' => []],
        ],
    ]],
];

// Aturan poin 4 (user 2026-09-15): di departemen yang TIDAK punya posisi
// Supervisor, Admin/Clerk mendapat SUBMIT untuk dokumen yang dia buat (MAKER).
$config['role_template_submit_fallback'] = ['position' => 'ADMIN_CLERK', 'if_missing' => 'SUPERVISOR'];

/*
 * ------------------------------------------------------------
 * AKSES TAMBAHAN DI LUAR PDF (user 2026-09-16: "tambahkan akses penting
 * misal master data inventory untuk general warehouse dll, jadi akses tidak
 * hanya fokus pada IRF, PRF, ERF")
 * ------------------------------------------------------------
 * PDF cuma memetakan DOKUMEN (IRF/PRF/ERF/MRF/PO/Receiving/QA/...). Tanpa
 * daftar ini, role hasil template tidak bisa mengerjakan pekerjaan harian
 * departemennya: gudang tidak punya master barang & stock opname, accounting
 * tidak punya jurnal & pembayaran, produksi tidak punya work order, dst.
 *
 * Tingkat akses:
 *   view   = lihat saja
 *   use    = lihat, buat, ubah, submit, confirm (transaksi harian)
 *   manage = use + hapus (master data milik departemen itu sendiri)
 *
 * Aturan pemakaian (lihat Role_template::extras()):
 *   - Hanya untuk posisi kerja: HOD, Officer, Supervisor, Admin/Clerk.
 *     Director & Asst. Director sudah dapat View semua menu lewat MONITORING.
 *   - Admin/Clerk tidak dapat hak HAPUS (manage turun jadi use).
 *   - Menu di sini TIDAK dibatasi departemen (master & stok memang dipakai
 *     lintas departemen), jadi tercatat sebagai aksi ALL DEPT.
 *   - Kalau menu-nya juga dipakai dokumen PDF, hak dari PDF tidak pernah
 *     dikurangi — keduanya digabung.
 */
// DIMATIKAN 2026-09-17. Daftar di bawah dibuat waktu template dipakai untuk
// MEMBUAT role baru yang masih kosong. Sekarang pendekatannya berbeda (user:
// "yang ada di pdf di sesuaikan ... selain yang di mention di pdf tapi sudah
// ada di role acces db, biarkan saja"): role yang dipakai sudah punya menu
// kerjanya sendiri di database, jadi template TIDAK boleh menambah menu di
// luar PDF. Daftarnya sengaja tidak dihapus supaya bisa dipakai lagi kalau
// suatu saat ada role baru yang benar-benar kosong.
$config['role_template_apply_extras'] = FALSE;

$config['role_template_extra_levels'] = [
    'view'   => ['view'],
    'use'    => ['view', 'add', 'edit', 'submit', 'confirm'],
    'manage' => ['view', 'add', 'edit', 'submit', 'confirm', 'delete'],
];
$config['role_template_extra_positions']   = ['HOD', 'OFFICER', 'SUPERVISOR', 'ADMIN_CLERK'];
$config['role_template_extra_no_delete']   = ['ADMIN_CLERK'];

$config['role_template_extras'] = [
    // Berlaku untuk SEMUA departemen.
    '*' => [
        'document_sharing' => 'use',
    ],
    'ACC' => [
        'jurnalumum' => 'manage', 'coa' => 'manage', 'settingakun' => 'use',
        'cashbank' => 'manage', 'kurs_pajak' => 'use',
        'apinvoice' => 'manage', 'appayment' => 'manage',
        'arinvoice' => 'manage', 'arreceipt' => 'manage',
        'prf_payment' => 'use', 'erf_payment' => 'use',
        'hutang' => 'view', 'piutang' => 'view',
        'voucher_disbursement_list' => 'view', 'summary_ledger' => 'use',
        'master_asset' => 'use', 'master_supplier' => 'view', 'master_customer' => 'view',
        'pr_monitoring' => 'view', 'po_monitoring' => 'view', 'po_report' => 'view',
    ],
    // HR, GA & Maintenance.
    'ADM' => [
        'master_karyawan' => 'manage', 'master_machine' => 'manage',
        'stock_machine' => 'view', 'lapmutasimesin' => 'view', 'master_asset' => 'view',
    ],
    'GWH' => [
        'master_inventory' => 'manage', 'master_jasa' => 'view', 'master_supplier' => 'view',
        'stock_inventory' => 'view', 'stock_ledger' => 'view', 'stock_alert' => 'use',
        'stockopname' => 'use', 'stock_transfer' => 'use', 'initial_stock' => 'use', 'adjustment' => 'use',
        'consumable_out' => 'use', 'consumable_out_general' => 'use',
        'consumable_outstanding' => 'use', 'consumable_outstanding_general' => 'use',
        // Sisa IRF yang stoknya kurang dijadikan PR dari gudang.
        'purchase_request' => 'use', 'purchase_request_general' => 'use',
        'purchase_request_jasa' => 'use', 'purchase_request_asset' => 'use',
        'pr_monitoring' => 'view', 'po_monitoring' => 'view', 'lap_pengeluaran_barang' => 'view',
    ],
    'LOG' => [
        'master_customer' => 'view', 'sales_contract_list' => 'view',
        'fg_stock' => 'view', 'lapmutasifinished' => 'view',
        'warehouse_in' => 'view', 'supplier_general_item_receipt' => 'view',
        'po_monitoring' => 'view', 'kurs_pajak' => 'view',
    ],
    'MGM' => [],
    'PIE' => [
        'bom' => 'manage', 'master_semifinished' => 'manage',
        'work_order' => 'use', 'production_process' => 'view',
        'master_fg' => 'view', 'master_material' => 'view', 'stock_material' => 'view',
        'material_traceability' => 'view', 'lapmutasiwip' => 'view', 'lapmutasifinished' => 'view',
    ],
    'PRO' => [
        'work_order' => 'use', 'production_receive' => 'use', 'production_qc' => 'use',
        'subkon_order' => 'use', 'subkon_in' => 'use', 'subkon_out' => 'use',
        'scrap_out' => 'use', 'scrap_stock' => 'view',
        'fg_receipt' => 'use', 'material_issuance' => 'view',
        'bom' => 'view', 'master_fg' => 'view', 'master_material' => 'view', 'stock_material' => 'view',
        'qc_production' => 'view', 'material_traceability' => 'view',
        'lapmutasiwip' => 'view', 'lapmutasifinished' => 'view',
    ],
    'PWH' => [
        'master_material' => 'manage', 'master_fg' => 'view', 'master_semifinished' => 'view',
        'stock_material' => 'view', 'stock_ledger' => 'view',
        'stockopname' => 'use', 'initial_stock' => 'use', 'adjustment' => 'use',
        'material_issuance' => 'use', 'material_outstanding' => 'use', 'purchase_request' => 'use',
        'fg_receipt' => 'use', 'fg_receipt_approval' => 'use', 'fg_stock' => 'view',
        'subkon_in' => 'use', 'subkon_out' => 'use', 'scrap_in' => 'use', 'scrap_stock' => 'view',
        'consumable_out' => 'use', 'consumable_out_produksi' => 'use',
        'consumable_outstanding' => 'use', 'consumable_outstanding_produksi' => 'use',
        'production_receive' => 'view', 'material_traceability' => 'view',
        'lapmutasimaterial' => 'view', 'lap_pemasukan_bb' => 'view',
    ],
    'QAD' => [
        'production_qc' => 'use', 'qc_production' => 'view',
        'warehouse_in' => 'view', 'supplier_general_item_receipt' => 'view',
        'master_material' => 'view', 'master_fg' => 'view', 'stock_material' => 'view',
        'material_traceability' => 'view', 'scrap_in' => 'view', 'lap_mutasi_scrap' => 'view',
    ],
];

unset($main, $stage3, $single, $read, $irf_prf_erf, $gw_docs, $gw_all, $log_own, $log_all, $prod_docs, $pwh_docs, $pwh_all, $qa_docs);
