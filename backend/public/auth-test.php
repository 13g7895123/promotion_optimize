<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:9117');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simulate the auth/login response
$response = [
    'status' => 'success',
    'message' => 'Mock login test - CORS working for authentication',
    'method' => $_SERVER['REQUEST_METHOD'],
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
    'data' => [
        'token' => 'mock_jwt_token_here',
        'user' => [
            'id' => 1,
            'username' => 'test_user'
        ]
    ]
];

echo json_encode($response);
?>