<?php
// Kết nối cơ sở dữ liệu
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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
        'message' => 'Invalid product ID format.'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra xem sản phẩm có tồn tại và lấy dữ liệu của nó trước khi xóa
$check_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$check_stmt->bind_param("s", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Product not found.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}

// Lưu trữ dữ liệu sản phẩm trước khi xóa
$product_data = $result->fetch_assoc();
$check_stmt->close();

// Kiểm tra xem sản phẩm có các bản ghi liên kết hay không
$review_check_stmt = $conn->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ?");
$review_check_stmt->bind_param("s", $product_id);
$review_check_stmt->execute();
$review_result = $review_check_stmt->get_result()->fetch_assoc();
$review_check_stmt->close();

// Kiểm tra xem sản phẩm có các bản ghi liên kết hay không
$order_check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM order_items WHERE product_id = ?");
$order_check_stmt->bind_param("s", $product_id);
$order_check_stmt->execute();
$order_result = $order_check_stmt->get_result()->fetch_assoc();
$order_check_stmt->close();

// Nếu có các bản ghi liên kết, không xóa mà đánh dấu là không hoạt động
if ($review_result['review_count'] > 0 || $order_result['order_count'] > 0) {
    $update_stmt = $conn->prepare("UPDATE products SET status = 0, updated_at = ? WHERE id = ?");
    $current_time = date('Y-m-d H:i:s');
    $update_stmt->bind_param("ss", $current_time, $product_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Sản phẩm có các bản ghi liên kết và đã được đánh dấu là không hoạt động thay vì bị xóa.',
            'data' => array_merge($product_data, ['status' => 0, 'updated_at' => $current_time])
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không thể cập nhật trạng thái sản phẩm: ' . $update_stmt->error
        ]);
        http_response_code(500);
    }
    $update_stmt->close();
    $conn->close();
    exit;
}

// Nếu không có các bản ghi liên kết, tiến hành xóa
$delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$delete_stmt->bind_param("s", $product_id);

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Sản phẩm đã được xóa thành công.'
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không có sản phẩm nào được xóa.'
        ]);
        http_response_code(400);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không thể xóa sản phẩm: ' . $delete_stmt->error
    ]);
    http_response_code(500);
}

$delete_stmt->close();
$conn->close();
?>