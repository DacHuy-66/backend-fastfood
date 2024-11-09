<?php
include_once __DIR__ . '/../../config/db.php';

// Lấy order_id từ URL
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', $request_uri);
$order_id = end($path_parts); // Lấy phần tử cuối cùng của URL

// Lấy status từ request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['status'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu status!'
    ]);
    http_response_code(400);
    exit;
}

$status = $data['status'];

// Kiểm tra đơn hàng tồn tại
$check_sql = "SELECT * FROM orders WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $order_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng!'
    ]);
    http_response_code(404);
    exit;
}

// Cập nhật trạng thái đơn hàng
$update_sql = "UPDATE orders SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ss", $status, $order_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Cập nhật trạng thái đơn hàng thành công!',
        'data' => [
            'order_id' => $order_id,
            'status' => $status
        ]
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi khi cập nhật trạng thái đơn hàng!'
    ]);
    http_response_code(500);
}

$conn->close(); 