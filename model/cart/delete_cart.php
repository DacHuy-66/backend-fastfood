<?php

include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy dữ liệu từ request body thay vì URL
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Lấy ID từ URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', rtrim($url_path, '/'));
    $cart_id = intval(end($path_parts));

    if (!$cart_id) {
        throw new Exception('Thiếu hoặc sai định dạng cart_id', 400);
    }

    // Lấy các tham số từ request body
    $delete_type = isset($data['delete_type']) ? trim($data['delete_type']) : 'all';
    $quantity = 1; // Mặc định quantity là 1 khi delete_type là reduce

    // kiểm tra sản phẩm có tồn tại trong giỏ hàng hay không
    $check_sql = "SELECT c.id, c.quantity, c.product_id FROM cart c WHERE c.id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $cart_id);
    $check_stmt->execute();
    $cart_result = $check_stmt->get_result();

    if ($cart_result->num_rows === 0) {
        throw new Exception('Không tìm thấy sản phẩm trong giỏ hàng', 404);
    }

    $cart_item = $cart_result->fetch_assoc();

    if ($delete_type === 'reduce') {
        // giảm số lượng
        $new_quantity = max(0, $cart_item['quantity'] - $quantity);
        
        if ($new_quantity > 0) {
            // cập nhật số lượng giỏ hàng
            $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_quantity, $cart_id);
            $update_stmt->execute();
            
            // tăng lại số lượng trong kho
            $update_product_sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            $update_product_stmt = $conn->prepare($update_product_sql);
            $update_product_stmt->bind_param("is", $quantity, $cart_item['product_id']);
            $update_product_stmt->execute();
            
            $message = 'Giảm số lượng thành công';
        } else {
            // xóa nếu số lượng trở thành 0
            $delete_sql = "DELETE FROM cart WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $cart_id);
            $delete_stmt->execute();
            
            // tăng lại số lượng trong kho
            $update_product_sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            $update_product_stmt = $conn->prepare($update_product_sql);
            $update_product_stmt->bind_param("is", $cart_item['quantity'], $cart_item['product_id']);
            $update_product_stmt->execute();
            
            $message = 'Xóa sản phẩm thành công do số lượng trở thành 0';
        }
    } else {
        // xóa hoàn toàn
        $delete_sql = "DELETE FROM cart WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $cart_id);
        $delete_stmt->execute();
        
        // tăng lại số lượng trong kho
        $update_product_sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
        $update_product_stmt = $conn->prepare($update_product_sql);
        $update_product_stmt->bind_param("is", $cart_item['quantity'], $cart_item['product_id']);
        $update_product_stmt->execute();
        
        $message = 'Xóa sản phẩm thành công';
    }

    // chuẩn bị response thành công
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => $message,
        'code' => 200,
        'data' => [
            'cart_id' => $cart_id,
            'delete_type' => $delete_type,
            'quantity_reduced' => $delete_type === 'reduce' ? $quantity : null
        ]
    ];
    http_response_code(200);

} catch (Exception $e) {
    $response = [
        'ok' => false,
        'status' => 'error',
        'code' => $e->getCode() ?: 400,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
} finally {
    // đóng tất cả các prepared statements
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    if (isset($update_product_stmt)) $update_product_stmt->close();
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
