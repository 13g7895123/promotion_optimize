<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $mysqli = new mysqli('mysql', 'promotion_user', 'promotion_password', 'promotion_platform');

    if ($mysqli->connect_error) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }

    $login = 'admin';
    $password = 'password';

    // Query user
    $stmt = $mysqli->prepare("SELECT id, username, email, password_hash, status FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Verify password
    $passwordValid = password_verify($password, $user['password_hash']);

    // Get roles
    $roleStmt = $mysqli->prepare("
        SELECT r.name, r.display_name
        FROM roles r
        JOIN user_roles ur ON r.id = ur.role_id
        WHERE ur.user_id = ?
    ");
    $roleStmt->bind_param('i', $user['id']);
    $roleStmt->execute();
    $rolesResult = $roleStmt->get_result();
    $roles = [];
    while ($role = $rolesResult->fetch_assoc()) {
        $roles[] = $role;
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $user['status']
        ],
        'password_valid' => $passwordValid,
        'roles' => $roles
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>