<?php
include_once __DIR__ . '/../../config/db.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Get ID from URL
$request_uri = $_SERVER['REQUEST_URI'];
$segments = explode('/', trim($request_uri, '/'));
$id_discount = end($segments);

// Check if discount ID is provided
if (empty($id_discount)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID discount không được cung cấp!'
    ]);
    http_response_code(400);
    exit;
}

// Check discount ID
$stmt = $conn->prepare("SELECT id FROM discounts WHERE id = ?");
$stmt->bind_param("i", $id_discount);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Discount exists, proceed to delete
    $delete_stmt = $conn->prepare("DELETE FROM discounts WHERE id = ?");
    $delete_stmt->bind_param("i", $id_discount);

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
        'message' => 'Không tìm thấy Discount ID!'
    ]);
    http_response_code(404);
}

$stmt->close();
$conn->close();
