<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = new Config\Paths();
define('ROOTPATH', realpath(__DIR__ . '/../') . '/');
define('APPPATH', ROOTPATH . 'app/');
define('SYSTEMPATH', __DIR__ . '/../vendor/codeigniter4/framework/system/');
define('FCPATH', __DIR__ . '/');
define('WRITEPATH', ROOTPATH . 'writable/');

// Load environment
$dotenv = new CodeIgniter\Config\DotEnv(ROOTPATH);
$dotenv->load();

// Set environment
putenv('CI_ENVIRONMENT=' . (getenv('CI_ENVIRONMENT') ?: 'development'));

try {
    $userModel = new \App\Models\UserModel();
    $login = 'admin';
    $password = 'password';

    // Find user
    $user = $userModel->where('username', $login)
                     ->orWhere('email', $login)
                     ->where('deleted_at', null)
                     ->first();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Verify password
    $passwordValid = $userModel->verifyPassword($password, $user['password_hash']);

    if (!$passwordValid) {
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }

    // Get user with roles
    $userWithRoles = $userModel->getUserWithRoles($user['id']);

    echo json_encode([
        'success' => true,
        'user' => $userWithRoles
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice($e->getTrace(), 0, 5)
    ], JSON_PRETTY_PRINT);
}
?>