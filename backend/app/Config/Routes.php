<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */
$routes->group('api', ['namespace' => 'App\Controllers\API'], function ($routes) {
    
    // Authentication routes (no middleware required)
    $routes->group('auth', function ($routes) {
        $routes->post('register', 'AuthController::register');
        $routes->post('login', 'AuthController::login');
        $routes->post('refresh', 'AuthController::refresh');
        $routes->post('logout', 'AuthController::logout', ['filter' => 'jwtauth']);
        $routes->get('profile', 'AuthController::profile', ['filter' => 'jwtauth']);
        $routes->put('profile', 'AuthController::updateProfile', ['filter' => 'jwtauth']);
        $routes->post('change-password', 'AuthController::changePassword', ['filter' => 'jwtauth']);
    });

    // Protected API routes (require JWT authentication)
    $routes->group('', ['filter' => 'jwtauth'], function ($routes) {
        
        // User management routes (admin/super admin only)
        $routes->group('users', ['filter' => 'rbac:admin'], function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('(:num)', 'UserController::show/$1');
            $routes->post('/', 'UserController::create');
            $routes->put('(:num)', 'UserController::update/$1');
            $routes->delete('(:num)', 'UserController::delete/$1');
            $routes->post('(:num)/roles', 'UserController::assignRole/$1');
            $routes->delete('(:num)/roles/(:num)', 'UserController::removeRole/$1/$2');
        });

        // Role and permission management (super admin only)
        $routes->group('roles', ['filter' => 'rbac:super_admin'], function ($routes) {
            $routes->get('/', 'RoleController::index');
            $routes->get('(:num)', 'RoleController::show/$1');
            $routes->post('/', 'RoleController::create');
            $routes->put('(:num)', 'RoleController::update/$1');
            $routes->delete('(:num)', 'RoleController::delete/$1');
            $routes->post('(:num)/permissions', 'RoleController::assignPermission/$1');
            $routes->delete('(:num)/permissions/(:num)', 'RoleController::removePermission/$1/$2');
        });

        $routes->group('permissions', ['filter' => 'rbac:super_admin'], function ($routes) {
            $routes->get('/', 'PermissionController::index');
            $routes->post('/', 'PermissionController::create');
            $routes->put('(:num)', 'PermissionController::update/$1');
            $routes->delete('(:num)', 'PermissionController::delete/$1');
        });

        // Server management routes
        $routes->group('servers', function ($routes) {
            $routes->get('/', 'ServerController::index', ['filter' => 'rbac:user']);
            $routes->get('(:num)', 'ServerController::show/$1', ['filter' => 'rbac:user']);
            $routes->post('/', 'ServerController::create', ['filter' => 'rbac:server_owner']);
            $routes->put('(:num)', 'ServerController::update/$1', ['filter' => 'rbac:server_owner']);
            $routes->delete('(:num)', 'ServerController::delete/$1', ['filter' => 'rbac:admin']);
            
            // Server approval routes (admin/reviewer only)
            $routes->post('(:num)/approve', 'ServerController::approve/$1', ['filter' => 'rbac:reviewer']);
            $routes->post('(:num)/reject', 'ServerController::reject/$1', ['filter' => 'rbac:reviewer']);
            
            // Server settings
            $routes->get('(:num)/settings', 'ServerController::getSettings/$1', ['filter' => 'rbac:server_owner']);
            $routes->put('(:num)/settings', 'ServerController::updateSettings/$1', ['filter' => 'rbac:server_owner']);
            
            // Database connection test
            $routes->post('(:num)/test-connection', 'ServerController::testConnection/$1', ['filter' => 'rbac:server_owner']);
        });

        // System health check (admin only)
        $routes->get('health', 'SystemController::health', ['filter' => 'rbac:admin']);
        $routes->get('stats', 'SystemController::stats', ['filter' => 'rbac:admin']);
    });
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}