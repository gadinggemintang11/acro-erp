<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['auth/login'] = 'Auth/login';
$route['commoncode'] = 'Commoncode/index';
$route['commoncode/delete/(:num)'] = 'Commoncode/delete/$1';
$route['master_semifinished'] = 'Master_semifinished/index';
$route['master_semifinished/add'] = 'Master_semifinished/add';
$route['master_semifinished/update/(:num)'] = 'Master_semifinished/update/$1';
$route['master_semifinished/delete/(:num)'] = 'Master_semifinished/delete/$1';
$route['purchase_order/search_items'] = 'Purchase_order/search_items';
$route['LapMutasiFinished/detail/(:num)'] = 'LapMutasiFinished/detail/$1';
$route['LapMutasiFinished/trace_recursive/(:num)'] = 'LapMutasiFinished/trace_recursive/$1';
$route['fg_stock/ajax_get_traceability_info/(:any)/(:any)/(:any)'] = 'Fg_stock/ajax_get_traceability_info/$1/$2/$3';
$route['adjustment'] = 'Adjustment/index';
$route['adjustment/create'] = 'Adjustment/create';
$route['adjustment/store'] = 'Adjustment/store';
$route['adjustment/view/(:num)'] = 'Adjustment/view/$1';
$route['adjustment/edit/(:num)'] = 'Adjustment/edit/$1';       // <-- BARU
$route['adjustment/update/(:num)'] = 'Adjustment/update/$1';   // <-- BARU
$route['adjustment/delete/(:num)'] = 'Adjustment/delete/$1';   // <-- BARU
$route['adjustment/confirm/(:num)'] = 'Adjustment/confirm/$1';
$route['adjustment/cancel/(:num)'] = 'Adjustment/cancel/$1';
$route['stockopname'] = 'StockOpname/index';
$route['stockopname/create'] = 'StockOpname/create';
$route['stockopname/counting/(:num)'] = 'StockOpname/counting/$1';
$route['stockopname/save_counts/(:num)'] = 'StockOpname/save_counts/$1';
$route['stockopname/view/(:num)'] = 'StockOpname/view/$1';
$route['stockopname/confirm/(:num)'] = 'StockOpname/confirm/$1';
$route['stockopname/delete/(:num)'] = 'StockOpname/delete/$1';
$route['coa'] = 'Coa/index';
$route['coa/create'] = 'Coa/create';
$route['coa/store'] = 'Coa/store';
$route['coa/edit/(:num)'] = 'Coa/edit/$1';
$route['coa/update/(:num)'] = 'Coa/update/$1';
$route['coa/delete/(:num)'] = 'Coa/delete/$1';
$route['jurnalumum'] = 'JurnalUmum/index';
$route['jurnalumum/create'] = 'JurnalUmum/create';
$route['jurnalumum/store'] = 'JurnalUmum/store';
$route['jurnalumum/view/(:num)'] = 'JurnalUmum/view/$1';
$route['jurnalumum/post/(:num)'] = 'JurnalUmum/post/$1';
$route['jurnalumum/delete/(:num)'] = 'JurnalUmum/delete/$1';
$route['jurnalumum/import'] = 'JurnalUmum/import';
$route['jurnalumum/process_import'] = 'JurnalUmum/process_import';
$route['jurnalumum/export_excel/(:num)'] = 'JurnalUmum/export_excel/$1';
$route['jurnalumum/export_pdf/(:num)'] = 'JurnalUmum/export_pdf/$1';
$route['yearendclosing'] = 'YearEndClosing/index';
$route['yearendclosing/create'] = 'YearEndClosing/create';
$route['yearendclosing/preview'] = 'YearEndClosing/preview';
$route['yearendclosing/process'] = 'YearEndClosing/process';
$route['yearendclosing/view/(:num)'] = 'YearEndClosing/view/$1';
$route['apinvoice'] = 'ApInvoice/index';
$route['apinvoice/create'] = 'ApInvoice/create';
$route['apinvoice/store'] = 'ApInvoice/store';
$route['apinvoice/view/(:num)'] = 'ApInvoice/view/$1';
$route['apinvoice/post/(:num)'] = 'ApInvoice/post/$1';
$route['apinvoice/delete/(:num)'] = 'ApInvoice/delete/$1';
// Rute AJAX untuk AP Invoice
$route['apinvoice/ajax_get_pending_grn'] = 'ApInvoice/ajax_get_pending_grn';
$route['apinvoice/ajax_get_grn_details'] = 'ApInvoice/ajax_get_grn_details';
$route['settingakun'] = 'SettingAkun/index';
$route['settingakun/save'] = 'SettingAkun/save';
$route['appayment'] = 'ApPayment/index';
$route['appayment/create'] = 'ApPayment/create';
$route['appayment/store'] = 'ApPayment/store';
$route['appayment/view/(:num)'] = 'ApPayment/view/$1';
$route['appayment/post/(:num)'] = 'ApPayment/post/$1';
$route['appayment/delete/(:num)'] = 'ApPayment/delete/$1';
// Rute AJAX untuk AP Payment
$route['appayment/ajax_get_unpaid_invoices'] = 'ApPayment/ajax_get_unpaid_invoices';
$route['arinvoice'] = 'ArInvoice/index';
$route['arinvoice/create'] = 'ArInvoice/create';
$route['arinvoice/store'] = 'ArInvoice/store';
$route['arinvoice/view/(:num)'] = 'ArInvoice/view/$1';
$route['arinvoice/post/(:num)'] = 'ArInvoice/post/$1';
$route['arinvoice/delete/(:num)'] = 'ArInvoice/delete/$1';
// Rute Ekspor
$route['arinvoice/export_excel'] = 'ArInvoice/export_excel';
$route['arinvoice/export_pdf'] = 'ArInvoice/export_pdf';
$route['arinvoice/export_pdf_detail/(:num)'] = 'ArInvoice/export_pdf_detail/$1';
// Rute AJAX
$route['arinvoice/ajax_get_pending_docs'] = 'ArInvoice/ajax_get_pending_docs';
$route['arinvoice/ajax_get_doc_details'] = 'ArInvoice/ajax_get_doc_details';
// Rute AR Receipt
$route['arreceipt'] = 'ArReceipt/index';
$route['arreceipt/create'] = 'ArReceipt/create';
$route['arreceipt/store'] = 'ArReceipt/store';
$route['arreceipt/view/(:num)'] = 'ArReceipt/view/$1';
$route['arreceipt/post/(:num)'] = 'ArReceipt/post/$1';
$route['arreceipt/delete/(:num)'] = 'ArReceipt/delete/$1';
// Rute AJAX untuk AR Receipt
$route['arreceipt/ajax_get_payment_accounts'] = 'ArReceipt/ajax_get_payment_accounts';
$route['arreceipt/ajax_get_unpaid_invoices'] = 'ArReceipt/ajax_get_unpaid_invoices';

// Scrap Stock routes
$route['scrap_stock'] = 'Scrap_Stock/index';
$route['scrap_stock/summary'] = 'Scrap_Stock/summary';
$route['scrap_stock/detail/(:num)'] = 'Scrap_Stock/detail/$1';
$route['scrap_stock/export_excel'] = 'Scrap_Stock/export_excel';
$route['scrap_stock/export_pdf'] = 'Scrap_Stock/export_pdf';