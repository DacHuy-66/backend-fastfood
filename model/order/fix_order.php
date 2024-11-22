<?php
include_once __DIR__ . '/../../config/db.php';


// Lấy order_id từ URL
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', $request_uri);
$order_id = end($path_parts); // Lấy phần tử cuối cùng của URL

// Lấy status và reason từ request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['status'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu status!'
    ]);
    http_response_code(400);
    exit;
}

$status = $data['status'];
$reason = isset($data['reason']) ? $data['reason'] : null;

// Kiểm tra đơn hàng tồn tại và lấy thông tin discount
$check_sql = "SELECT * FROM orders WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $order_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$order = $result->fetch_assoc();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng!'
    ]);
    http_response_code(404);
    exit;
}

try {
    $conn->begin_transaction();

    // Nếu status là Completed - cập nhật số lượng đã bán của sản phẩm và xử lý discount
    if ($status === 'Completed') {
        // Xử lý discount nếu đơn hàng có discount code
        if (!empty($order['discount_code'])) {
            // Kiểm tra xem discount có phải của user cụ thể không
            $check_user_discount_sql = "SELECT * FROM discount_user WHERE code = ? AND user_id = ?";
            $check_user_stmt = $conn->prepare($check_user_discount_sql);
            $check_user_stmt->bind_param("ss", $order['discount_code'], $order['user_id']);
            $check_user_stmt->execute();
            $user_discount_result = $check_user_stmt->get_result();

            if ($user_discount_result->num_rows > 0) {
                // Xóa discount user vì đơn hàng đã hoàn thành
                $delete_sql = "DELETE FROM discount_user WHERE code = ? AND user_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("ss", $order['discount_code'], $order['user_id']);
                $delete_stmt->execute();
            } 
            // else {
            //     // Giảm số lượng discount chung
            //     $update_sql = "UPDATE discounts SET quantity = quantity - 1 WHERE code = ? AND quantity > 0";
            //     $update_stmt = $conn->prepare($update_sql);
            //     $update_stmt->bind_param("s", $order['discount_code']);
            //     $update_stmt->execute();
            // }

            // // Cập nhật trạng thái trong discount_history
            // $update_history_sql = "UPDATE discount_history SET status = 'Completed' 
            //                      WHERE user_id = ? AND discount_code = ? AND status = 'Pending' 
            //                      ORDER BY Datetime DESC LIMIT 1";
            // $update_history_stmt = $conn->prepare($update_history_sql);
            // $update_history_stmt->bind_param("ss", $order['user_id'], $order['discount_code']);
            // $update_history_stmt->execute();
        }

        // Lấy danh sách sản phẩm trong đơn hàng từ bảng product_order
        $get_products_sql = "SELECT product_id, quantity FROM product_order WHERE order_id = ?";
        $get_products_stmt = $conn->prepare($get_products_sql);
        $get_products_stmt->bind_param("s", $order_id);
        $get_products_stmt->execute();
        $products_result = $get_products_stmt->get_result();
        
        // Cập nhật số lượng đã bán cho từng sản phẩm
        while ($product_item = $products_result->fetch_assoc()) {
            $update_product_sql = "UPDATE products SET sold = sold + ? WHERE id = ?";
            $update_product_stmt = $conn->prepare($update_product_sql);
            $update_product_stmt->bind_param("is", $product_item['quantity'], $product_item['product_id']);
            $update_product_stmt->execute();
        }
    }

    // Nếu status là Cancel và đơn hàng có discount code
    if ($status === 'Cancel' && !empty($order['discount_code'])) {
        // Kiểm tra xem discount có phải của user cụ thể không
        $check_user_discount_sql = "SELECT * FROM discount_user WHERE code = ? AND user_id = ?";
        $check_user_stmt = $conn->prepare($check_user_discount_sql);
        $check_user_stmt->bind_param("ss", $order['discount_code'], $order['user_id']);
        $check_user_stmt->execute();
        $user_discount_result = $check_user_stmt->get_result();

        if ($user_discount_result->num_rows === 0) {
            // Nếu là discount chung - tăng số lượng
            $update_sql = "UPDATE discounts SET quantity = quantity + 1 WHERE code = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $order['discount_code']);
            $update_stmt->execute();
        }

        // Cập nhật trạng thái trong discount_history
        $update_history_sql = "UPDATE discount_history SET status = 'Cancel' 
                             WHERE user_id = ? AND discount_code = ? AND status = 'Completed' 
                             ORDER BY Datetime DESC LIMIT 1";
        $update_history_stmt = $conn->prepare($update_history_sql);
        $update_history_stmt->bind_param("ss", $order['user_id'], $order['discount_code']);
        $update_history_stmt->execute();

        // Lấy danh sách sản phẩm trong đơn hàng
        $get_products_sql = "SELECT product_id, quantity FROM product_order WHERE order_id = ?";
        $get_products_stmt = $conn->prepare($get_products_sql);
        $get_products_stmt->bind_param("s", $order_id);
        $get_products_stmt->execute();
        $products_result = $get_products_stmt->get_result();

        // Cập nhật số lượng kho cho từng sản phẩm
        while ($product_item = $products_result->fetch_assoc()) {
            $update_stock_sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
            $update_stock_stmt = $conn->prepare($update_stock_sql);
            $update_stock_stmt->bind_param("is", $product_item['quantity'], $product_item['product_id']);
            $update_stock_stmt->execute();
        }
    }

    // Cập nhật trạng thái đơn hàng và review nếu status là Cancel
    if ($status === 'Cancel') {
        $update_sql = "UPDATE orders SET status = ?, reason = ?, review = 0 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $status, $reason, $order_id);
    } else {
        $update_sql = "UPDATE orders SET status = ?, reason = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $status, $reason, $order_id);
    }
    
    $update_stmt->execute();

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Cập nhật trạng thái đơn hàng thành công!',
        'data' => [
            'order_id' => $order_id,
            'status' => $status,
            'reason' => $reason,
            'review' => ($status === 'Cancel') ? false : null
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Lỗi khi cập nhật trạng thái đơn hàng: ' . $e->getMessage()
    ]);
    http_response_code(500);
}

$conn->close(); 