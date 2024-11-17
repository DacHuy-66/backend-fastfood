<?php
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
        'message' => 'ID không hợp lệ.'
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
        'message' => 'Sản phẩm không tồn tại.'
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
$order_check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM product_order WHERE product_id = ?");
$order_check_stmt->bind_param("s", $product_id);
$order_check_stmt->execute();
$order_result = $order_check_stmt->get_result()->fetch_assoc();
$order_check_stmt->close();

// Xóa các dữ liệu liên quan trước khi xóa sản phẩm
$delete_reviews_query = "DELETE FROM reviews WHERE product_id = ?";
$delete_reviews_stmt = $conn->prepare($delete_reviews_query);
$delete_reviews_stmt->bind_param("s", $product_id);
$delete_reviews_stmt->execute();
$delete_reviews_stmt->close();

$delete_product_order_query = "DELETE FROM product_order WHERE product_id = ?";
$delete_product_order_stmt = $conn->prepare($delete_product_order_query);
$delete_product_order_stmt->bind_param("s", $product_id);
$delete_product_order_stmt->execute();
$delete_product_order_stmt->close();

$delete_cart_query = "DELETE FROM cart WHERE product_id = ?";
$delete_cart_stmt = $conn->prepare($delete_cart_query);
$delete_cart_stmt->bind_param("s", $product_id);
$delete_cart_stmt->execute();
$delete_cart_stmt->close();

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