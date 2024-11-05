<?php
// Include database connection configuration 
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';


// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

// Kiểm tra thông tin admin
$sql = "SELECT * FROM admin WHERE email = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Admin hợp lệ
    $admin = $result->fetch_assoc();
    
    // Tạo API key mới
    $api_key = bin2hex(random_bytes(32));
    
    // Cập nhật API key vào cơ sở dữ liệu
    $update_sql = "UPDATE admin SET api_key = ?, time = NOW() WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $api_key, $admin['email']);
    
    if ($update_stmt->execute()) {
        // Chuẩn bị response với thông tin roles
        $response = [
            'ok' => true,
            'success' => true,
            'api_key' => $api_key,
            'message' => 'Đăng Nhập thành công!'
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không cập nhật được API key trong cơ sở dữ liệu'
        ]);
    }
} else {
    // Admin không hợp lệ
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Email hoặc mật khẩu không hợp lệ!'
    ]);
}

// Đóng kết nối
$conn->close();
?>