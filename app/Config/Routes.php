<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// User// Locate or replace the default '/' route with this:
$routes->get('/', 'UserController::index'); 
$routes->get('users', 'UserController::index');             // Read - Paginated Listing & Search
$routes->get('users/create', 'UserController::create');     // Read - Display Registration Form
$routes->post('users/store', 'UserController::store');       // Write - Process Form Security & File Upload
$routes->get('users/delete/(:num)', 'UserController::delete/$1'); // Write - Remove Record