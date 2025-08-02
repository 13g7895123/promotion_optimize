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
            
            // File upload routes
            $routes->post('(:num)/upload/logo', 'ServerController::uploadLogo/$1', ['filter' => 'rbac:server_owner']);
            $routes->post('(:num)/upload/background', 'ServerController::uploadBackground/$1', ['filter' => 'rbac:server_owner']);
            $routes->post('(:num)/upload/banners', 'ServerController::uploadBanners/$1', ['filter' => 'rbac:server_owner']);
            $routes->delete('(:num)/files', 'ServerController::deleteImage/$1', ['filter' => 'rbac:server_owner']);
            $routes->get('(:num)/files', 'ServerController::getFiles/$1', ['filter' => 'rbac:server_owner']);
            
            // Reward table mapping test
            $routes->post('(:num)/test-reward-mapping', 'ServerController::testRewardMapping/$1', ['filter' => 'rbac:server_owner']);
        });

        // Promotion system routes
        $routes->group('promotions', function ($routes) {
            // Basic promotion management
            $routes->get('/', 'PromotionController::index', ['filter' => 'rbac:user']);
            $routes->get('(:num)', 'PromotionController::show/$1', ['filter' => 'rbac:user']);
            $routes->post('/', 'PromotionController::create', ['filter' => 'rbac:user']);
            $routes->put('(:num)', 'PromotionController::update/$1', ['filter' => 'rbac:user']);
            $routes->delete('(:num)', 'PromotionController::delete/$1', ['filter' => 'rbac:user']);
            
            // Promotion status management
            $routes->post('(:num)/pause', 'PromotionController::pause/$1', ['filter' => 'rbac:user']);
            $routes->post('(:num)/resume', 'PromotionController::resume/$1', ['filter' => 'rbac:user']);
            
            // Analytics and reporting
            $routes->get('(:num)/analytics', 'PromotionController::analytics/$1', ['filter' => 'rbac:user']);
            $routes->get('(:num)/materials', 'PromotionController::materials/$1', ['filter' => 'rbac:user']);
            $routes->get('(:num)/export', 'PromotionController::export/$1', ['filter' => 'rbac:user']);
            
            // Promotion validation
            $routes->get('validate/(:alphanum)', 'PromotionController::validate/$1');
        });

        // Promotion tracking routes (public access for click tracking)
        $routes->group('promotion', function ($routes) {
            $routes->get('track/(:alphanum)', 'PromotionController::track/$1');
            $routes->post('track/conversion', 'PromotionController::trackConversion');
            $routes->get('track/pixel/(:num)', 'PromotionController::trackingPixel/$1');
        });

        // Reward system routes
        $routes->group('rewards', function ($routes) {
            // Basic reward management
            $routes->get('/', 'RewardController::index', ['filter' => 'rbac:user']);
            $routes->get('(:num)', 'RewardController::show/$1', ['filter' => 'rbac:user']);
            $routes->post('/', 'RewardController::create', ['filter' => 'rbac:server_owner']);
            $routes->put('(:num)', 'RewardController::update/$1', ['filter' => 'rbac:user']);
            $routes->delete('(:num)', 'RewardController::delete/$1', ['filter' => 'rbac:user']);
            
            // Reward approval and distribution (admin/reviewer only)
            $routes->post('(:num)/approve', 'RewardController::approve/$1', ['filter' => 'rbac:reviewer']);
            $routes->post('(:num)/distribute', 'RewardController::markDistributed/$1', ['filter' => 'rbac:reviewer']);
            
            // User rewards
            $routes->get('user/(:num)', 'RewardController::userRewards/$1', ['filter' => 'rbac:user']);
            $routes->get('history', 'RewardController::history', ['filter' => 'rbac:user']);
            
            // Reward preview and statistics
            $routes->post('preview', 'RewardController::preview', ['filter' => 'rbac:user']);
            $routes->get('statistics', 'RewardController::statistics', ['filter' => 'rbac:reviewer']);
            $routes->get('leaderboard', 'RewardController::leaderboard', ['filter' => 'rbac:user']);
            
            // Bulk operations (admin only)
            $routes->post('bulk', 'RewardController::bulkProcess', ['filter' => 'rbac:admin']);
        });

        // Statistics and analytics routes
        $routes->group('statistics', function ($routes) {
            $routes->get('dashboard', 'StatisticsController::dashboard', ['filter' => 'rbac:user']);
            $routes->get('promotions', 'StatisticsController::promotions', ['filter' => 'rbac:user']);
            $routes->get('rewards', 'StatisticsController::rewards', ['filter' => 'rbac:user']);
            $routes->get('servers/comparison', 'StatisticsController::serverComparison', ['filter' => 'rbac:admin']);
            $routes->get('export', 'StatisticsController::export', ['filter' => 'rbac:reviewer']);
            $routes->get('realtime', 'StatisticsController::realtime', ['filter' => 'rbac:user']);
        });

        // System health check (admin only)
        $routes->get('health', 'SystemController::health', ['filter' => 'rbac:admin']);
        $routes->get('stats', 'SystemController::stats', ['filter' => 'rbac:admin']);
    });

    // Public short link redirects (no authentication required)
    $routes->get('r/(:alphanum)', 'PromotionController::track/$1');
    
    // Test endpoints (no authentication required)
    $routes->get('test', 'TestController::index');
    $routes->get('health', 'TestController::health');
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