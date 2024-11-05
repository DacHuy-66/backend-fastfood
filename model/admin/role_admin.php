<?php
// Include database connection
include_once __DIR__ . '/../../config/db.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Retrieve API key from headers
$headers = apache_request_headers();
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Check if API key is provided
if (!$api_key) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key not provided.'
    ]);
    http_response_code(400);
    exit;
}

// Validate API key and get roles
$stmt = $conn->prepare("SELECT username,email, 'order', mess, 'statistics', user,product, discount, layout, decentralization FROM admin WHERE api_key = ?");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    
    // Convert roles to boolean
    $roles = [
        'email' => $admin_data['email'],
        'username' => $admin_data['username'],
        'order' => (bool)$admin_data['order'],
        'mess' => (bool)$admin_data['mess'],
        'statistics' => (bool)$admin_data['statistics'],
        'user' => (bool)$admin_data['user'],
        'product' => (bool)$admin_data['product'],
        'discount' => (bool)$admin_data['discount'],
        'layout' => (bool)$admin_data['layout'],
        'decentralization' => (bool)$admin_data['decentralization']
    ];

    echo json_encode([
        'ok' => true,
        'success' => true,
        'roles' => $roles
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid API key.'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
?>