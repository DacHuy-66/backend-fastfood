<?php
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// lấy id từ url
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$address_id = end($path_parts);

if (!is_numeric($address_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID địa chỉ không hợp lệ.'
    ]);
    http_response_code(400);
    exit;
}

// lấy dữ liệu json từ input
$data = json_decode(file_get_contents("php://input"), true);

// kiểm tra xem có ít nhất một trường được cung cấp không
if (!isset($data['address']) && !isset($data['phone']) && !isset($data['note'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Ít nhất một trường (address, phone, hoặc note) phải được cung cấp để cập nhật.'
    ]);
    http_response_code(400);
    exit;
}

// khởi tạo mảng cho cập nhật sql
$updateFields = [];
$paramValues = [];
$paramTypes = '';

// xử lý cập nhật địa chỉ
if (isset($data['address'])) {
    $address = trim($data['address']);
    $updateFields[] = "address = ?";
    $paramValues[] = $address;
    $paramTypes .= "s";
}

// xử lý cập nhật số điện thoại
if (isset($data['phone'])) {
    $phone = trim($data['phone']);
    if (!preg_match('/^[0-9+\-\s()]*$/', $phone)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Số điện thoại không hợp lệ.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "phone = ?";
    $paramValues[] = $phone;
    $paramTypes .= "s";
}

// xử lý cập nhật note với kiểm tra tính duy nhất
if (isset($data['note'])) {
    $note = trim($data['note']);
    if (strlen($note) > 100) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Note không được vượt quá 100 ký tự.'
        ]);
        http_response_code(400);
        exit;
    }

    // kiểm tra xem note có trùng với note của địa chỉ khác không
    $check_note_stmt = $conn->prepare("SELECT id FROM detail_address WHERE note = ? AND id != ?");
    $check_note_stmt->bind_param("si", $note, $address_id);
    $check_note_stmt->execute();
    $duplicate_result = $check_note_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Note đã tồn tại. Vui lòng chọn một note khác.',
            'status' => false
        ]);
        http_response_code(400);
        $check_note_stmt->close();
        exit;
    }
    $check_note_stmt->close();

    $updateFields[] = "note = ?";
    $paramValues[] = $note;
    $paramTypes .= "s";
}

// kiểm tra xem địa chỉ với id đã cho có tồn tại không
$check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE id = ?");
$check_stmt->bind_param("i", $address_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Địa chỉ không tồn tại.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// thêm address_id vào tham số
$paramTypes .= "i";
$paramValues[] = $address_id;

// tạo và thực thi câu truy vấn cập nhật
$sql = "UPDATE detail_address SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

// liên kết tham số động
$bindParams = array_merge([$paramTypes], $paramValues);
$tmp = [];
foreach ($bindParams as $key => $value) {
    $tmp[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $tmp);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Cập nhật địa chỉ thành công.'
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Cập nhật địa chỉ thất bại: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>