<?php
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
$order = isset($data['order']) ? (int)$data['order'] : 0;
$mess = isset($data['mess']) ? (int)$data['mess'] : 0;
$statistics = isset($data['statistics']) ? (int)$data['statistics'] : 0;
$user = isset($data['user']) ? (int)$data['user'] : 0;
$product = isset($data['product']) ? (int)$data['product'] : 0;
$discount = isset($data['discount']) ? (int)$data['discount'] : 0;
$review = isset($data['review']) ? (int)$data['review'] : 0;
$layout = isset($data['layout']) ? (int)$data['layout'] : 0;
$decentralization = isset($data['decentralization']) ? (int)$data['decentralization'] : 0;
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
        'message' => 'Email đã tồn tại!'
    ]);
    exit;
}

// Tạo API key
$api_key = bin2hex(random_bytes(32));

// Tạo ID ngẫu nhiên
$id = bin2hex(random_bytes(6)); 

// Thêm admin mới
$sql = "INSERT INTO admin (id, username, email, password, `order`, mess, `statistics`, `user`, product, discount, review, layout, decentralization, note, api_key, time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssiiiiiiiiiss", 
    $id,     
    $username, 
    $email, 
    $password, 
    $order, 
    $mess, 
    $statistics,
    $user, 
    $product, 
    $discount, 
    $review, 
    $layout, 
    $decentralization, 
    $note, 
    $api_key
);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Tạo tài khoản thành công!',
        'data' => [
            'id' => $id,    
            'username' => $username,
            'email' => $email,
            'roles' => [
                'order' => (bool)$order,
                'mess' => (bool)$mess,
                'statistics' => (bool)$statistics,
                'user' => (bool)$user,
                'product' => (bool)$product,
                'discount' => (bool)$discount,
                'review' => (bool)$review,
                'layout' => (bool)$layout,
                'decentralization' => (bool)$decentralization
            ],
            'note' => $note,
            'api_key' => $api_key
        ]
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Tạo tài khoản thất bại: ' . $conn->error
    ]);
}

// Đóng kết nối
$conn->close();
?>