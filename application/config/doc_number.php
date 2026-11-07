<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * FORMAT NOMOR DOKUMEN OTOMATIS — SATU-SATUNYA tempat formatnya diatur
 * ------------------------------------------------------------
 * Rebrand Acropoly 2026-11-05 (user: "prefix pake misal PR/ACRO/YYMM(URUT)").
 *
 *   {PREFIX}/{KODE PERUSAHAAN}/{YYMM}{URUT}
 *   contoh: PR/ACRO/26110001  = Purchase Request, Nov 2026, urutan ke-1
 *
 * - Nomor urut RESET tiap bulan (karena YYMM ikut di dalam nomor).
 * - YYMM = bulan saat nomor dibuat.
 * - Ganti prefix / kode perusahaan / jumlah digit CUKUP di file ini;
 *   pembuat nomornya ada di helpers/doc_number_helper.php.
 * - Mengganti prefix TIDAK mengubah nomor dokumen yang sudah tersimpan.
 * ============================================================
 */

$config['doc_number_company_code'] = 'ACRO';
$config['doc_number_digits']       = 4;    // 0001 - 9999 per bulan per jenis dokumen
$config['doc_number_separator']    = '';   // pemisah YYMM & urut ('' = 26110001, '-' = 2611-0001)

$config['doc_number_prefixes'] = [
    // Penjualan & produksi
    'sales_order'        => 'SO',       // Sales Order (eks IPO)
    'work_order'         => 'WO',
    'production'         => 'PROD',
    'production_return'  => 'RFP',      // Retur Material Produksi
    'fg_receipt'         => 'FGR',      // FG Receipt
    'fg_delivery'        => 'DO',       // FG Release Permit / Delivery Order

    // Permintaan & pembelian
    'permintaan_barang'  => 'PB',       // Permintaan Barang (eks IRF)
    'material_request'   => 'MR',       // Material Request (eks PMR/MRF)
    'purchase_request'   => 'PR',       // semua tipe PR (material/general/jasa/asset)
    'po_material'        => 'POM',      // PO Material (eks PMPO)
    'po_general'         => 'POG',      // PO General (eks GMPO)
    'po_jasa'            => 'POJ',
    'po_asset'           => 'POA',
    'import_request'     => 'MIR',

    // Gudang
    'grn'                => 'GRN',      // Penerimaan Material Supplier
    'sgr'                => 'SGR',      // Penerimaan Barang General
    'service_receipt'    => 'JSA',      // Penerimaan Jasa
    'return_supplier'    => 'RTN',      // Retur ke Supplier
    'material_issue'     => 'MI',       // Pengeluaran Material ke Produksi
    'goods_issue'        => 'CO',       // Pengeluaran Barang (Stock Item Release)
    'stock_transfer'     => 'TRF',
    'stock_opname'       => 'OPN',
    'stock_adjustment'   => 'ADJ',

    // Mutu, scrap, subkon
    'qa'                 => 'QA',
    'qa_sampling'        => 'QAS',
    'scrap_in'           => 'SCI',
    'scrap_out'          => 'SCO',
    'subkon_order'       => 'SK',
    'subkon_out'         => 'SKO',
    'subkon_in'          => 'SKI',

    // Keuangan & akuntansi
    'payment_request'    => 'PAY',      // Payment Request (eks PRF)
    'reimburse_request'  => 'RR',       // Reimburse Request (eks ERF)
    'journal'            => 'JU',
    'year_end_closing'   => 'YEC',
    'ap_invoice'         => 'API',      // dulu INV/ -> bentrok dengan AR Invoice
    'ap_payment'         => 'APP',
    'ar_invoice'         => 'INV',
    'ar_receipt'         => 'ARP',
    'cash_bank'          => 'CB',
];
