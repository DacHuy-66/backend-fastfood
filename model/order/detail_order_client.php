<?php

include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy user_id từ URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($url_path, '/'));
    $user_id = end($path_parts);

    if (empty($user_id)) {
        throw new Exception('ID người dùng không được cung cấp!', 400);
    }

    // Query lấy tất cả đơn hàng của user
    $orders_sql = "SELECT o.*, da.phone, da.address
                   FROM orders o
                   LEFT JOIN detail_address da ON o.address_id = da.id
                   WHERE o.user_id = ?
                   ORDER BY o.created_at DESC";
    
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("s", $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();

    if ($orders_result->num_rows === 0) {
        throw new Exception('Không tìm thấy đơn hàng nào!', 404);
    }

    $all_orders = [];
    
    while ($order = $orders_result->fetch_assoc()) {
        // Query lấy chi tiết sản phẩm trong từng đơn hàng
        $products_sql = "SELECT po.*, p.name as product_name
                        FROM product_order po
                        LEFT JOIN products p ON po.product_id = p.id
                        WHERE po.order_id = ?";
        
        $products_stmt = $conn->prepare($products_sql);
        $products_stmt->bind_param("s", $order['id']);
        $products_stmt->execute();
        $products_result = $products_stmt->get_result();

        $products = [];
        $total_price = 0;
        
        while ($product = $products_result->fetch_assoc()) {
            $subtotal = $product['quantity'] * $product['price'];
            $total_price += $subtotal;
            
            $products[] = [
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'subtotal' => $subtotal
            ];
        }

        // Query lấy thông tin thanh toán cho từng đơn hàng
        $payment_sql = "SELECT payment_method, payment_status, payment_date 
                       FROM payments 
                       WHERE order_id = ?";
        
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("s", $order['id']);
        $payment_stmt->execute();
        $payment_result = $payment_stmt->get_result();
        $payment_info = $payment_result->fetch_assoc();

        $all_orders[] = [
            'order_id' => $order['id'],
            'order_status' => $order['status'],
            'created_at' => $order['created_at'],
            'shipping_info' => [
                'phone' => $order['phone'],
                'address' => $order['address'],
                'note' => $order['note']
            ],
            'products' => $products,
            'total_price' => $total_price,
            'payment' => $payment_info
        ];
    }

    // Chuẩn bị response
    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy danh sách đơn hàng thành công',
        'data' => $all_orders
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ];

    echo json_encode($response);
}
