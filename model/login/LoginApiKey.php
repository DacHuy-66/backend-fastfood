<?php
include_once __DIR__ . '/../../config/db.php';

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

// Kiểm tra thông tin người dùng
$sql = "SELECT * FROM users WHERE email = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Người dùng hợp lệ
    $user = $result->fetch_assoc();
    
    // Kiểm tra role của user
    if ($user['role'] == '0') {
        // Tạo API key
        $api_key = bin2hex(random_bytes(32));

        // Cập nhật API key vào cơ sở dữ liệu
        $update_sql = "UPDATE users SET api_key = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $api_key, $user['email']);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'ok' => true,
                'success' => true,
                'api_key' => $api_key,
                'message' => 'API key được tạo thành công.'
            ]);
        } else {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Lỗi cập nhật API key trong cơ sở dữ liệu.'
            ]);
        }
    } else {
        // Trả về thông báo khi role không phải 0
        echo json_encode([
            'ok' => false,
            'status' => 'block',
            'success' => false,
            'message' => 'Người dùng không có quyền tạo API key.'
        ]);
    }
} else {
    // Người dùng không hợp lệ
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Tên đăng nhập hoặc mật khẩu không hợp lệ.'
    ]);
}

// Đóng kết nối
$conn->close();
?>