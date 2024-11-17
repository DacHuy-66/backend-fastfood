<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php-errors.log');

include_once __DIR__ . '/../../config/db.php';

// Xử lý yêu cầu preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Lấy ID sản phẩm từ URL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$product_id = end($path_parts);

if (empty($product_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Định dạng ID sản phẩm không hợp lệ.'
    ]);
    http_response_code(400);
    exit;
}

// Lấy dữ liệu JSON từ input
$input = file_get_contents("php://input");
if (!$input) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không nhận được dữ liệu input'
    ]);
    http_response_code(400);
    exit;
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg()
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra xem có ít nhất 1 trường được cung cấp không
if (empty($data) || !is_array($data)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Cần có ít nhất 1 trường để cập nhật.'
    ]);
    http_response_code(400);
    exit;
}

// Khởi tạo mảng cho cập nhật SQL
$updateFields = [];
$paramValues = [];
$paramTypes = '';

// Xử lý cập nhật tên
if (isset($data['name'])) {
    $name = trim($data['name']);
    if (strlen($name) < 2 || strlen($name) > 100) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Tên sản phẩm phải có độ dài từ 2 đến 100 ký tự.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "name = ?";
    $paramValues[] = $name;
    $paramTypes .= "s";
}

// Xử lý cập nhật loại sản phẩm
if (isset($data['type'])) {
    $type = trim($data['type']);
    if (empty($type)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Loại sản phẩm không thể trống.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "type = ?";
    $paramValues[] = $type;
    $paramTypes .= "s";
}

// Xử lý cập nhật mô tả
if (isset($data['description'])) {
    $description = trim($data['description']);
    $updateFields[] = "description = ?";
    $paramValues[] = $description;
    $paramTypes .= "s";
}

// Xử lý cập nhật giá
if (isset($data['price'])) {
    $price = floatval($data['price']);
    if ($price <= 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Giá phải lớn hơn 0.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "price = ?";
    $paramValues[] = $price;
    $paramTypes .= "d";
}

// Xử lý cập nhật số lượng
if (isset($data['quantity'])) {
    $quantity = intval($data['quantity']);
    if ($quantity < 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Số lượng không thể là số âm.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "quantity = ?";
    $paramValues[] = $quantity;
    $paramTypes .= "i";
}

// Xử lý cập nhật trạng thái
if (isset($data['status'])) {
    $status = $data['status'] ? 1 : 0;
    $updateFields[] = "status = ?";
    $paramValues[] = $status;
    $paramTypes .= "i";
}

// Xử lý cập nhật lock
if (isset($data['lock'])) {
    $lock = $data['lock'] ? 1 : 0;
    $updateFields[] = "`lock` = ?";
    $paramValues[] = $lock;
    $paramTypes .= "i";
}

// Xử lý cập nhật giảm giá
if (isset($data['discount'])) {
    $discount = trim($data['discount']);
    $updateFields[] = "discount = ?";
    $paramValues[] = $discount;
    $paramTypes .= "s";
}

// Xử lý cập nhật hình ảnh
if (isset($data['image_url'])) {
    $image_url = trim($data['image_url']);
    $updateFields[] = "image_url = ?";
    $paramValues[] = $image_url;
    $paramTypes .= "s";
}

// Kiểm tra xem sản phẩm có tồn tại không
$check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$check_stmt->bind_param("s", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Sản phẩm không tồn tại.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Thêm product_id vào các tham số
$paramTypes .= "s";
$paramValues[] = $product_id;

// Xây dựng và thực thi câu truy vấn cập nhật
$sql = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

// Liên kết các tham số động
$bindParams = array_merge([$paramTypes], $paramValues);
$tmp = [];
foreach ($bindParams as $key => $value) {
    $tmp[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $tmp);

if ($stmt->execute()) {
    // Lấy sản phẩm đã cập nhật
    $select_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $select_stmt->bind_param("s", $product_id);
    $select_stmt->execute();
    $updated_product = $select_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Cập nhật sản phẩm thành công.',
        'data' => $updated_product
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Cập nhật sản phẩm thất bại: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>