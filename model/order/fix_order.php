<?php
include_once __DIR__ . '/../../config/db.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');



// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu id đơn hàng hoặc status!'
    ]);
    http_response_code(400);
    exit;
}

$order_id = $data['order_id'];
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