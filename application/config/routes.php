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
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['auth/login'] = 'auth/login';
$route['commoncode'] = 'Commoncode/index';
$route['commoncode/delete/(:num)'] = 'commoncode/delete/$1';
$route['master_semifinished'] = 'Master_semifinished/index';
$route['master_semifinished/add'] = 'Master_semifinished/add';
$route['master_semifinished/update/(:num)'] = 'Master_semifinished/update/$1';
$route['master_semifinished/delete/(:num)'] = 'Master_semifinished/delete/$1';
$route['purchase_order/search_items'] = 'purchase_order/search_items';
$route['LapMutasiFinished/detail/(:num)'] = 'LapMutasiFinished/detail/$1';
$route['LapMutasiFinished/trace_recursive/(:num)'] = 'LapMutasiFinished/trace_recursive/$1';
$route['fg_stock/ajax_get_traceability_info/(:any)/(:any)/(:any)'] = 'fg_stock/ajax_get_traceability_info/$1/$2/$3';
$route['adjustment'] = 'adjustment/index';
$route['adjustment/create'] = 'adjustment/create';
$route['adjustment/store'] = 'adjustment/store';
$route['adjustment/view/(:num)'] = 'adjustment/view/$1';
$route['adjustment/edit/(:num)'] = 'adjustment/edit/$1';       // <-- BARU
$route['adjustment/update/(:num)'] = 'adjustment/update/$1';   // <-- BARU
$route['adjustment/delete/(:num)'] = 'adjustment/delete/$1';   // <-- BARU
$route['adjustment/confirm/(:num)'] = 'adjustment/confirm/$1';
$route['adjustment/cancel/(:num)'] = 'adjustment/cancel/$1';
$route['stockopname'] = 'stockopname/index';
$route['stockopname/create'] = 'stockopname/create';
$route['stockopname/counting/(:num)'] = 'stockopname/counting/$1';
$route['stockopname/save_counts/(:num)'] = 'stockopname/save_counts/$1';
$route['stockopname/view/(:num)'] = 'stockopname/view/$1';
$route['stockopname/confirm/(:num)'] = 'stockopname/confirm/$1';
$route['stockopname/delete/(:num)'] = 'stockopname/delete/$1';
$route['coa'] = 'coa/index';
$route['coa/create'] = 'coa/create';
$route['coa/store'] = 'coa/store';
$route['coa/edit/(:num)'] = 'coa/edit/$1';
$route['coa/update/(:num)'] = 'coa/update/$1';
$route['coa/delete/(:num)'] = 'coa/delete/$1';
$route['jurnalumum'] = 'jurnalumum/index';
$route['jurnalumum/create'] = 'jurnalumum/create';
$route['jurnalumum/store'] = 'jurnalumum/store';
$route['jurnalumum/view/(:num)'] = 'jurnalumum/view/$1';
$route['jurnalumum/post/(:num)'] = 'jurnalumum/post/$1';
$route['jurnalumum/delete/(:num)'] = 'jurnalumum/delete/$1';
$route['jurnalumum/import'] = 'jurnalumum/import';
$route['jurnalumum/process_import'] = 'jurnalumum/process_import';
$route['apinvoice'] = 'apinvoice/index';
$route['apinvoice/create'] = 'apinvoice/create';
$route['apinvoice/store'] = 'apinvoice/store';
$route['apinvoice/view/(:num)'] = 'apinvoice/view/$1';
$route['apinvoice/post/(:num)'] = 'apinvoice/post/$1';
$route['apinvoice/delete/(:num)'] = 'apinvoice/delete/$1';
$route['settingakun'] = 'settingakun/index';
$route['settingakun/save'] = 'settingakun/save';
$route['appayment'] = 'appayment/index';
$route['appayment/create'] = 'appayment/create';
$route['appayment/store'] = 'appayment/store';
$route['appayment/view/(:num)'] = 'appayment/view/$1';
$route['appayment/post/(:num)'] = 'appayment/post/$1';
$route['appayment/delete/(:num)'] = 'appayment/delete/$1';
$route['arinvoice'] = 'arinvoice/index';
$route['arinvoice/create'] = 'arinvoice/create';
$route['arinvoice/store'] = 'arinvoice/store';
$route['arinvoice/view/(:num)'] = 'arinvoice/view/$1';
$route['arinvoice/post/(:num)'] = 'arinvoice/post/$1';
$route['arinvoice/delete/(:num)'] = 'arinvoice/delete/$1';
// Rute Ekspor
$route['arinvoice/export_excel'] = 'arinvoice/export_excel';
$route['arinvoice/export_pdf'] = 'arinvoice/export_pdf';
$route['arinvoice/export_pdf_detail/(:num)'] = 'arinvoice/export_pdf_detail/$1';
// Rute AJAX
$route['arinvoice/ajax_get_pending_docs'] = 'arinvoice/ajax_get_pending_docs';
$route['arinvoice/ajax_get_doc_details'] = 'arinvoice/ajax_get_doc_details';