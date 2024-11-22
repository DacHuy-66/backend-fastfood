<?php
include_once __DIR__ . '/../../config/db.php';

// Lấy dữ liệu từ request 
$data = json_decode(file_get_contents('php://input'), true);

// Validate dữ liệu đầu vào
if (!isset($data['user_id']) || !isset($data['address_id']) || !isset($data['products']) || empty($data['products'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết!'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Validate và lấy thông tin sản phẩm
    $product_details = [];
    // $subtotal = 0;
    // $total_quantity = 0;

    // Validate và tính toán thông tin sản phẩm
    foreach ($data['products'] as $product) {
        if (!isset($product['product_id']) || !isset($product['quantity']) || $product['quantity'] <= 0) {
            throw new Exception('Thông tin sản phẩm không hợp lệ!');
        }

        // Kiểm tra sản phẩm và lấy giá và số lượng tồn
        $product_sql = "SELECT id, name, price, quantity as stock FROM products WHERE id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("s", $product['product_id']);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();

        if ($product_result->num_rows === 0) {
            throw new Exception('Sản phẩm không tồn tại!');
        }

        $product_info = $product_result->fetch_assoc();

        // Kiểm tra số lượng tồn kho
        if ($product_info['stock'] < $product['quantity']) {
            throw new Exception("Sản phẩm {$product_info['name']} không đủ số lượng trong kho!");
        }

        // $item_subtotal = $product_info['price'] * $product['quantity'];
        // $subtotal += $item_subtotal;
        // $total_quantity += $product['quantity'];

        $product_details[] = [
            'product_id' => $product_info['id'],
            'name' => $product_info['name'],
            'price' => $product_info['price'],
            'quantity' => $product['quantity']
            // 'subtotal' => $item_subtotal
        ];
    }

    // Tạo order ID
    $order_id = substr(uniqid(), 0, 8);

    // Tính tổng số lượng
    $total_quantity = array_sum(array_column($product_details, 'quantity'));

    $discount_code = $data['discount_code'] ?? null;
    // $discount_amount = 0;

    // Xử lý discount nếu có
    if ($discount_code) {
        // Kiểm tra discount cho user cụ thể - chỉ kiểm tra trong bảng discount_user
        $check_user_discount_sql = "SELECT * FROM discount_user 
                                  WHERE code = ? AND user_id = ?";
        $check_user_stmt = $conn->prepare($check_user_discount_sql);
        $check_user_stmt->bind_param("ss", $discount_code, $data['user_id']);
        $check_user_stmt->execute();
        $user_discount_result = $check_user_stmt->get_result();

        if ($user_discount_result->num_rows === 0) {
            // Kiểm tra và cập nhật discount chung
            $check_general_discount_sql = "SELECT * FROM discounts WHERE code = ? 
                                         AND valid_from <= NOW() AND valid_to >= NOW() 
                                         AND quantity > 0";
            $check_general_stmt = $conn->prepare($check_general_discount_sql);
            $check_general_stmt->bind_param("s", $discount_code);
            $check_general_stmt->execute();
            $general_discount_result = $check_general_stmt->get_result();

            if ($general_discount_result->num_rows > 0) {
                // Giảm số lượng discount chung
                $update_sql = "UPDATE discounts SET quantity = quantity - 1 WHERE code = ? AND quantity > 0";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("s", $discount_code);
                $update_stmt->execute();
            } else {
                throw new Exception('Mã giảm giá không hợp lệ hoặc đã hết hạn!');
            }
        }

        // Thêm vào discount_history
        $insert_history_sql = "INSERT INTO discount_history (user_id, status, Datetime, discount_code) 
                             VALUES (?, 'Completed', NOW(), ?)";
        $history_stmt = $conn->prepare($insert_history_sql);
        $history_stmt->bind_param("ss", $data['user_id'], $discount_code);
        $history_stmt->execute();
    }

    // // Tính total_price sau khi áp dụng giảm giá
    // $total_price = $subtotal - $discount_amount;

    // Tạo đơn hàng - cập nhật theo cấu trúc bảng mới
    $order_sql = "INSERT INTO orders (id, user_id, address_id, quantity, status, reason, note, 
   discount_code, total_price, subtotal, review, created_at, updated_at) 
VALUES (?, ?, ?, ?, 'Pending', NULL, ?, ?, ?, ?, 1, NOW(), NOW())";
    $order_stmt = $conn->prepare($order_sql);
    $note = $data['note'] ?? null;
    $total_price = $data['total_price'];
    $subtotal = $data['subtotal'];
    $order_stmt->bind_param(
        "ssiissdd",
        $order_id,
        $data['user_id'],
        $data['address_id'],
        $total_quantity,
        $note,
        $discount_code,
        $total_price,
        $subtotal
    );
    $order_stmt->execute();

    // Thêm chi tiết đơn hàng
    foreach ($product_details as $item) {
        // Thêm vào product_order
        $product_order_sql = "INSERT INTO product_order (order_id, product_id, quantity, price) 
                            VALUES (?, ?, ?, ?)";
        $product_order_stmt = $conn->prepare($product_order_sql);
        $product_order_stmt->bind_param(
            "ssid",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        );
        $product_order_stmt->execute();

        // Cập nhật số lượng tồn kho
        $update_product_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
        $update_product_stmt = $conn->prepare($update_product_sql);
        $update_product_stmt->bind_param(
            "isi",
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        );
        $update_product_stmt->execute();

        // Xóa sản phẩm khỏi giỏ hàng
        $delete_cart_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("ss", $data['user_id'], $item['product_id']);
        $delete_cart_stmt->execute();
    }

    // Tạo payment record
    $payment_sql = "INSERT INTO payments (order_id, payment_method, payment_status, payment_date) 
                   VALUES (?, ?, 'Pending', NULL)";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_method = $data['payment_method'] ?? 'COD';
    $payment_stmt->bind_param("ss", $order_id, $payment_method);
    $payment_stmt->execute();

    // Lấy thông tin chi tiết đơn hàng để trả về
    $order_detail_sql = "SELECT o.*, u.username, p.name as product_name, po.price, po.quantity as item_quantity,
                        da.phone, da.address
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN product_order po ON o.id = po.order_id
                        LEFT JOIN products p ON po.product_id = p.id
                        LEFT JOIN detail_address da ON o.address_id = da.id
                        WHERE o.id = ?";
    $order_detail_stmt = $conn->prepare($order_detail_sql);
    $order_detail_stmt->bind_param("s", $order_id);
    $order_detail_stmt->execute();
    $order_result = $order_detail_stmt->get_result();

    $orders = [];
    $customer = null;

    while ($row = $order_result->fetch_assoc()) {
        if ($customer === null) {
            $customer = [
                'username' => $row['username'],
                'user_id' => $data['user_id'],
                'phone' => $row['phone'],
                'address' => $row['address'],
                'note' => $row['note']
            ];
        }

        $orders[] = [
            'order_id' => $row['id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['item_quantity'],
            'price' => $row['price'],
            'total_price' => $row['total_price'],
            'subtotal' => $row['subtotal']
        ];
    }

    if ($customer === null) {
        throw new Exception('Không thể lấy thông tin đơn hàng!');
    }

    // Commit transaction
    $conn->commit();

    // Trả về kết quả thành công
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Tạo đơn hàng thành công!',
        'data' => [
            'customer' => $customer,
            'orders' => $orders,
            'status' => 'Pending',
            'payment_method' => $payment_method,
            'discount_code' => $discount_code,
            'note' => $note
        ]
    ]);
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();

    error_log("Order creation error: " . $e->getMessage());

    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
} finally {
    $conn->close();
}
