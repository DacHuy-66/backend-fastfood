<?php
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Lấy dữ liệu JSON từ input
$data = json_decode(file_get_contents("php://input"), true);

// Kiểm tra các trường bắt buộc dựa trên schema của bảng products
$required_fields = ['name', 'description', 'type', 'price', 'quantity'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu các trường bắt buộc: ' . implode(', ', $missing_fields)
    ]);
    http_response_code(400);
    exit;
}

// Làm sạch và kiểm tra dữ liệu đầu vào
$name = trim($data['name']);
$description = trim($data['description']);
$type = trim($data['type']);
$price = floatval($data['price']);
$quantity = intval($data['quantity']);
$status = isset($data['status']) ? $data['status'] : true;
$lock = isset($data['lock']) ? $data['lock'] : false;
$discount = isset($data['discount']) ? trim($data['discount']) : null;
$image_url = isset($data['image_url']) ? trim($data['image_url']) : null;
$created_at = date('Y-m-d H:i:s');

// Kiểm tra độ dài tên sản phẩm
if (strlen($name) < 2 || strlen($name) > 100) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Tên sản phẩm phải có độ dài từ 2 đến 100 ký tự.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra loại sản phẩm
if (empty($type)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Loại sản phẩm không thể trống.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra giá
if ($price <= 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Giá phải lớn hơn 0.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra số lượng
if ($quantity < 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Số lượng không thể là số âm.'
    ]);
    http_response_code(400);
    exit;
}

// Tạo ID sản phẩm duy nhất
$product_id = uniqid();

// Chèn sản phẩm mới
$stmt = $conn->prepare("INSERT INTO products (id, name, description, type, price, quantity, status, `lock`, discount, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$status_int = $status ? 1 : 0;
$lock_int = $lock ? 1 : 0;
$discount_val = $discount !== null ? $discount : 0;

$stmt->bind_param("sssssdiisss", 
    $product_id, 
    $name, 
    $description, 
    $type,
    $price, 
    $quantity, 
    $status_int,
    $lock_int,
    $discount_val,
    $image_url, 
    $created_at
);

if ($stmt->execute()) {
    // Lấy sản phẩm mới được tạo
    $select_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $select_stmt->bind_param("s", $product_id);
    $select_stmt->execute();
    $new_product = $select_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Sản phẩm được tạo thành công.',
        'data' => $new_product
    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không thể tạo sản phẩm: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>