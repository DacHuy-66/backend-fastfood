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
    
    foreach ($data['products'] as $product) {
        if (!isset($product['product_id']) || !isset($product['quantity'])) {
            throw new Exception('Thông tin sản phẩm không hợp lệ!');
        }

        // Kiểm tra sản phẩm và lấy giá
        $product_sql = "SELECT id, name, price FROM products WHERE id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("s", $product['product_id']);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        
        if ($product_result->num_rows === 0) {
            throw new Exception('Sản phẩm không tồn tại!');
        }
        
        $product_info = $product_result->fetch_assoc();
        
        $product_details[] = [
            'product_id' => $product_info['id'],
            'name' => $product_info['name'],
            'price' => $product_info['price'],
            'quantity' => $product['quantity']
        ];
    }

    // Thay thế phần tạo UUID bằng ID ngắn hơn
    $order_id = substr(uniqid(), 0, 8);
    
    // Tính tổng số lượng
    $total_quantity = array_sum(array_column($product_details, 'quantity'));
    
    $discount_code = $data['discount_code'] ?? null;
    
    // Tạo một đơn hàng duy nhất
    $order_sql = "INSERT INTO orders (id, user_id, address_id, quantity, status, note, discount_code, total_price, subtotal, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 'Chờ xác nhận', ?, ?, ?, ?, NOW(), NOW())";
    $order_stmt = $conn->prepare($order_sql);
    $note = $data['note'] ?? null;
    $total_price = $data['total_price'];
    $subtotal = $data['subtotal'];
    $order_stmt->bind_param("ssiissdd", 
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

    // Thêm từng sản phẩm vào đơn hàng
    foreach ($product_details as $item) {
        $product_order_sql = "INSERT INTO product_order (order_id, product_id, quantity, price) 
                            VALUES (?, ?, ?, ?)";
        $product_order_stmt = $conn->prepare($product_order_sql);
        $product_order_stmt->bind_param("ssid", 
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        );
        $product_order_stmt->execute();
    }

    // Tạo một payment record duy nhất cho đơn hàng
    $payment_sql = "INSERT INTO payments (order_id, payment_method, payment_status, payment_date) 
                VALUES (?, ?, 'Pending', NULL)";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_method = $data['payment_method'] ?? 'COD';
    $payment_stmt->bind_param("ss", $order_id, $payment_method);
    $payment_stmt->execute();

    // Thêm xóa sản phẩm khỏi giỏ hàng sau khi tạo đơn hàng thành công
    foreach ($data['products'] as $product) {
        $delete_cart_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("ss", $data['user_id'], $product['product_id']);
        $delete_cart_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Lấy thông tin đơn hàng vừa tạo
    $order_detail_sql = "SELECT o.*, u.username, p.name as product_name, po.price,
                        da.phone, da.address
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.id
                        LEFT JOIN product_order po ON o.id = po.order_id
                        LEFT JOIN products p ON po.product_id = p.id
                        LEFT JOIN detail_address da ON o.address_id = da.id
                        WHERE o.user_id = ? 
                        ORDER BY o.created_at DESC 
                        LIMIT ?";
    $order_detail_stmt = $conn->prepare($order_detail_sql);
    $limit = count($product_details);
    $order_detail_stmt->bind_param("si", $data['user_id'], $limit);
    $order_detail_stmt->execute();
    $order_result = $order_detail_stmt->get_result();
    
    $orders = [];
    $customer = null;  // Khởi tạo customer là null

    while ($row = $order_result->fetch_assoc()) {
        // Lưu thông tin customer từ row đầu tiên
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
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'total_price' => $row['price'],
            'subtotal' => $row['subtotal']
        ];
    }

    // Kiểm tra nếu không có dữ liệu
    if ($customer === null) {
        throw new Exception('Không thể lấy thông tin đơn hàng!');
    }

    // Trả về response thành công
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Tạo đơn hàng thành công!',
        'data' => [
            'customer' => $customer,
            'orders' => $orders,
            'status' => 'Chờ xác nhận',
            'payment_method' => $payment_method,
            'discount_code' => $discount_code,
            'note' => $note
        ]
    ]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
}

$conn->close();