<?php
// Include database connection configuration
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

    // Tạo API key
    $api_key = bin2hex(random_bytes(32)); // Tạo một API key ngẫu nhiên

    // Cập nhật API key vào cơ sở dữ liệu
    $update_sql = "UPDATE users SET api_key = ? WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $api_key, $user['email']);
    
    if ($update_stmt->execute()) {
        // Trả về API key cho client
        echo json_encode([
            'ok' => true,
            'success' => true,
            'api_key' => $api_key,
            'message' => 'API key created successfully.'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Failed to update API key in the database.'
        ]);
    }
} else {
    // Người dùng không hợp lệ
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid username or password.'
    ]);
}

// Đóng kết nối
$conn->close();
?>
