<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

$message_id = $matches[1] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($message_id) || !isset($data['status'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết!'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Kiểm tra tin nhắn tồn tại
    $check_sql = "SELECT id FROM messages WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $message_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        throw new Exception('Tin nhắn không tồn tại!');
    }

    // Cập nhật trạng thái
    $status = $data['status'] ? 1 : 0; // Đảm bảo giá trị là 0 hoặc 1
    $update_sql = "UPDATE messages SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $status, $message_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Không thể cập nhật trạng thái tin nhắn!');
    }

    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Cập nhật trạng thái tin nhắn thành công!',
        'data' => [
            'message_id' => $message_id,
            'status' => (bool)$status
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
}

$conn->close(); 