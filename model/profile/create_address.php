<?php
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra các trường bắt buộc
$required_fields = ['address', 'phone'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu các trường bắt buộc: ' . implode(', ', $missing_fields),
        'limit' => true
    ]);
    http_response_code(400);
    exit;
}

// Làm sạch dữ liệu đầu vào
$address = trim($data['address']);
$phone = trim($data['phone']);
$note = isset($data['note']) ? trim($data['note']) : ''; // Optional field

// Kiểm tra định dạng số điện thoại (kiểm tra cơ bản)
if (!preg_match('/^[0-9+\-\s()]*$/', $phone)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Định dạng số điện thoại không hợp lệ.',
        'limit' => true
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra xem user có tồn tại không
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Người dùng không tồn tại.',
        'limit' => true
    ]);
    http_response_code(404);
    $stmt->close();
    exit;
}
$stmt->close();

// Kiểm tra xem user đã đạt đến giới hạn địa chỉ (3)
$count_stmt = $conn->prepare("SELECT COUNT(*) as address_count FROM detail_address WHERE user_id = ?");
$count_stmt->bind_param("s", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

if ($count_data['address_count'] >= 3) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Đã đạt đến giới hạn địa chỉ. Bạn chỉ có thể thêm tối đa 3 địa chỉ.',
        'limit' => false
    ]);
    http_response_code(400);
    $count_stmt->close();
    exit;
}
$count_stmt->close();

// Kiểm tra xem note đã tồn tại cho user đó chưa
if (!empty($note)) {
    $check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE user_id = ? AND note = ?");
    $check_stmt->bind_param("ss", $user_id, $note);
    $check_stmt->execute();
    $duplicate_result = $check_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Note đã tồn tại cho user này. Vui lòng chọn một khác.',
            'status' => false,
            'limit' => true
        ]);
        http_response_code(400);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
}

// Thêm địa chỉ mới
$stmt = $conn->prepare("INSERT INTO detail_address (user_id, address, phone, note) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $address, $phone, $note);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Địa chỉ được tạo thành công.',
        'limit' => true
    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi tạo địa chỉ: ' . $stmt->error,
        'limit' => true
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
