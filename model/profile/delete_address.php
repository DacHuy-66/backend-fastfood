<?php
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Xử lý yêu cầu preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Lấy ID địa chỉ từ input và kiểm tra nó
$address_id = $_GET['id'] ?? null;

if (!$address_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID địa chỉ là bắt buộc.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra xem địa chỉ có tồn tại không
$check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE id = ?");
$check_stmt->bind_param("i", $address_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Địa chỉ không tồn tại.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Xóa địa chỉ
$delete_stmt = $conn->prepare("DELETE FROM detail_address WHERE id = ?");
$delete_stmt->bind_param("i", $address_id);

if ($delete_stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Địa chỉ được xóa thành công.'
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi xóa địa chỉ: ' . $delete_stmt->error
    ]);
    http_response_code(500);
}

$delete_stmt->close();
$conn->close();
?>
