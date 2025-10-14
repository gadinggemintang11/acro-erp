<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index');
$routes->get('supplier', 'Supplier::index');
$routes->get('supplier/new', 'Supplier::new');
$routes->post('supplier/create', 'Supplier::create');
$routes->resource('material');