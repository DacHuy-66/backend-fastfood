<?php
// Include database connection configuration
include_once __DIR__ . '/../../config/db.php';

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra dữ liệu bắt buộc
$required_fields = ['username', 'email', 'password'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Lấy dữ liệu từ request
$username = $data['username'];
$email = $data['email'];
$password = $data['password'];
// Các role mặc định là 0 nếu không được cung cấp
$role_1 = isset($data['role_1']) ? (int)$data['role_1'] : 0;
$role_2 = isset($data['role_2']) ? (int)$data['role_2'] : 0;
$role_3 = isset($data['role_3']) ? (int)$data['role_3'] : 0;
$role_4 = isset($data['role_4']) ? (int)$data['role_4'] : 0;
$note = isset($data['note']) ? $data['note'] : '';

// Kiểm tra email đã tồn tại chưa
$check_sql = "SELECT COUNT(*) as count FROM admin WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Email already exists'
    ]);
    exit;
}

// Tạo API key
$api_key = bin2hex(random_bytes(32));

// Thêm admin mới
$sql = "INSERT INTO admin (username, email, password, role_1, role_2, role_3, role_4, note, api_key, time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssiiiiss", 
    $username, 
    $email, 
    $password, 
    $role_1, 
    $role_2, 
    $role_3, 
    $role_4, 
    $note, 
    $api_key
);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Admin created successfully',
        'data' => [
            'id' => $conn->insert_id,
            'username' => $username,
            'email' => $email,
            'roles' => [
                'role_1' => (bool)$role_1,
                'role_2' => (bool)$role_2,
                'role_3' => (bool)$role_3,
                'role_4' => (bool)$role_4
            ],
            'note' => $note,
            'api_key' => $api_key
        ]
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to create admin: ' . $conn->error
    ]);
}

// Đóng kết nối
$conn->close();
?>