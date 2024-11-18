<?php

include_once __DIR__ . '/../../config/db.php';

// Lấy API key từ header
$headers = getallheaders();
$api_key = $headers['X-Api-Key'] ?? '';

// Kiểm tra xem API key có được cung cấp không
if (empty($api_key)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key bị thiếu.'
    ]);
    exit;
}

// Kiểm tra xem API key có tồn tại trong cơ sở dữ liệu không
$sql = "SELECT * FROM users WHERE api_key = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // API key hợp lệ, lấy thông tin user
    $user = $result->fetch_assoc();

    // Cập nhật vai trò của user thành '1'
    $update_sql = "UPDATE users SET role = '0' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $user['id']);
    if ($update_stmt->execute()) {
        // Chuẩn bị dữ liệu phản hồi
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Vai trò user được cập nhật thành công.',
        ]);
    } else {
        // Lỗi cập nhật vai trò user
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi cập nhật vai trò user.'
        ]);
        http_response_code(500);
    }
} else {
    // API key không hợp lệ
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key không hợp lệ.'
    ]);
    http_response_code(404);
}

// Đóng kết nối
$conn->close();
