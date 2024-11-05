<?php
// Include database connection
include_once __DIR__ . '/../../config/db.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Parse ID from the URL path
$request_uri = $_SERVER['REQUEST_URI'];
$segments = explode('/', trim($request_uri, '/'));
$id_admin = end($segments);

// Check if admin ID is provided
if (empty($id_admin)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID admin không được cung cấp!'
    ]);
    http_response_code(400);
    exit;
}

// Validate admin ID
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->bind_param("s", $id_admin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Admin exists, proceed with deleting the admin
    $delete_stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $delete_stmt->bind_param("s", $id_admin);

    if ($delete_stmt->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Xóa thành công!'
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Xóa không thành công: ' . $delete_stmt->error
        ]);
        http_response_code(500);
    }
    $delete_stmt->close();
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không tìm thấy Admin ID!'
    ]);
    http_response_code(404);
}

$stmt->close();
$conn->close();
