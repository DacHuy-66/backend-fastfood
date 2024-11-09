<?php

include_once __DIR__ . '/../../utils/helpers.php';
include_once __DIR__ . '/../../config/db.php';
Headers();

// Lấy API key từ headers
$headers = apache_request_headers();
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Kiểm tra xem API key có được cung cấp không
if (!$api_key) {
    echo json_encode([
        'success' => false,
        'message' => 'API key không được cung cấp.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra API key và lấy thông tin user
$stmt = $conn->prepare("SELECT id, password FROM users WHERE api_key = ?");
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Đọc dữ liệu PUT request
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $current_password = isset($data['current_password']) ? $data['current_password'] : null;
    $new_password = isset($data['new_password']) ? $data['new_password'] : null;

    // Kiểm tra xem cả hai mật khẩu đã được cung cấp
    if (!$current_password || !$new_password) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Mật khẩu hiện tại và mật khẩu mới là bắt buộc.'
        ]);
        http_response_code(400);
        exit;
    }

    // So sánh trực tiếp với mật khẩu đã lưu
    if ($current_password == $user['password']) {
        // Cập nhật với mật khẩu mới
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE api_key = ?");
        $update_stmt->bind_param("ss", $new_password, $api_key);

        if ($update_stmt->execute()) {
            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Mật khẩu được cập nhật thành công.'
            ]);
            http_response_code(200);
        } else {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Lỗi cập nhật mật khẩu: ' . $update_stmt->error
            ]);
            http_response_code(500);
        }
        $update_stmt->close();
    } else {
        echo json_encode([
            'ok' => true,
            'success' => false,
            'message' => 'Mật khẩu hiện tại sai.'
        ]);
        http_response_code(401);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key không hợp lệ.'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
?>
