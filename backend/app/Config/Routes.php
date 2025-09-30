<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\API'], function($routes) {
    // Auth Routes
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->post('auth/refresh', 'AuthController::refresh');

    // Test Routes
    $routes->get('test', 'TestController::index');
    $routes->get('cors-test', 'CorsTestController::test');

    // Protected Routes (require authentication)
    $routes->group('', ['filter' => 'auth'], function($routes) {
        // User Routes
        $routes->get('users/me', 'UserController::getCurrentUser');
        $routes->get('users/me/permissions', 'UserController::getUserPermissions');
        $routes->get('users', 'UserController::index');
        $routes->get('users/(:num)', 'UserController::show/$1');
        $routes->post('users', 'UserController::create');
        $routes->put('users/(:num)', 'UserController::update/$1');
        $routes->delete('users/(:num)', 'UserController::delete/$1');

        // Promotion Routes
        $routes->get('promotions', 'PromotionController::index');
        $routes->get('promotions/(:num)', 'PromotionController::show/$1');
        $routes->post('promotions', 'PromotionController::create');
        $routes->put('promotions/(:num)', 'PromotionController::update/$1');
        $routes->delete('promotions/(:num)', 'PromotionController::delete/$1');

        // Server Routes
        $routes->get('servers', 'ServerController::index');
        $routes->get('servers/(:num)', 'ServerController::show/$1');
        $routes->post('servers', 'ServerController::create');
        $routes->put('servers/(:num)', 'ServerController::update/$1');
        $routes->delete('servers/(:num)', 'ServerController::delete/$1');

        // Statistics Routes
        $routes->get('statistics/dashboard', 'StatisticsController::getDashboard');
        $routes->get('statistics/promotions', 'StatisticsController::getPromotionStats');
        $routes->get('statistics/rewards', 'StatisticsController::getRewardStats');

        // Role & Permission Routes
        $routes->get('roles', 'RoleController::index');
        $routes->get('roles/(:num)', 'RoleController::show/$1');
        $routes->post('roles', 'RoleController::create');
        $routes->put('roles/(:num)', 'RoleController::update/$1');
        $routes->delete('roles/(:num)', 'RoleController::delete/$1');

        $routes->get('permissions', 'PermissionController::index');

        // System Routes
        $routes->get('system/health', 'SystemController::health');
        $routes->get('system/info', 'SystemController::info');
    });
});
