<?php
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
$data = json_decode(file_get_contents("php://input"), true);

// kiểm tra các trường bắt buộc
$required_fields = ['username', 'email', 'password'];
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
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    http_response_code(400);
    exit;
}

// làm sạch và kiểm tra dữ liệu đầu vào
$username = trim($data['username']);
$email = trim($data['email']);
$password = trim($data['password']);


// kiểm tra định dạng email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Vui lòng điền đúng email!'
    ]);
    http_response_code(400);
    exit;
}

// kiểm tra độ dài tên đăng nhập
if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Tên đăng nhập nên lớn hơn 3 kí tự và nhỏ hơn 50 kí tự!'
    ]);
    http_response_code(400);
    exit;
}

// kiểm tra độ dài mật khẩu
if (strlen($password) < 1) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Vui lòng điền mật khẩu!'
    ]);
    http_response_code(400);
    exit;
}

// kiểm tra xem email đã tồn tại chưa
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Email đã tồn tại!'
    ]);
    http_response_code(409);
    $stmt->close();
    exit;
}
$stmt->close();



// tạo ID và API key duy nhất
$user_id = uniqid();
$api_key = bin2hex(random_bytes(32)); 
$default_avata = 'https://tse4.mm.bing.net/th?id=OIP.Zmki3GIiRk-XKTzRRlxn4QHaER&pid=Api&P=0&h=220'; // đường dẫn mặc định cho avatar
// vai trò mặc định là user (0)
$role = '0';

// chèn người dùng mới
$stmt = $conn->prepare("INSERT INTO users (id, username, email, password, api_key, role, avata) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $user_id, $username, $email, $password, $api_key, $role, $default_avata);

if ($stmt->execute()) {
    // trả về thành công với dữ liệu người dùng (không bao gồm mật khẩu)
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Tài khoản được tạo thành công!',

    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Tạo tài khoản thất bại: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
