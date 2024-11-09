<?php

include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy order_id từ URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($url_path, '/'));
    $order_id = end($path_parts);

    if (empty($order_id)) {
        throw new Exception('ID đơn hàng không được cung cấp!', 400);
    }

    // Query lấy thông tin đơn hàng và khách hàng
    $order_sql = "SELECT o.*, u.username, da.phone, da.address
                  FROM orders o
                  LEFT JOIN users u ON o.user_id = u.id
                  LEFT JOIN detail_address da ON o.address_id = da.id
                  WHERE o.id = ?";
    
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("s", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();

    if ($order_result->num_rows === 0) {
        throw new Exception('Không tìm thấy đơn hàng!', 404);
    }

    $order_info = $order_result->fetch_assoc();

    // Query lấy chi tiết sản phẩm trong đơn hàng
    $products_sql = "SELECT po.*, p.name as product_name
                    FROM product_order po
                    LEFT JOIN products p ON po.product_id = p.id
                    WHERE po.order_id = ?";
    
    $products_stmt = $conn->prepare($products_sql);
    $products_stmt->bind_param("s", $order_id);
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

    // Query lấy thông tin thanh toán
    $payment_sql = "SELECT payment_method, payment_status, payment_date 
                   FROM payments 
                   WHERE order_id = ?";
    
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("s", $order_id);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    $payment_info = $payment_result->fetch_assoc();

    // Chuẩn bị response
    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy chi tiết đơn hàng thành công',
        'data' => [
            'order_id' => $order_info['id'],
            'customer' => [
                'name' => $order_info['username'],
                'phone' => $order_info['phone'],
                'address' => $order_info['address'],
                'note' => $order_info['note']
            ],
            'products' => $products,
            'total_price' => $total_price,
            'payment' => $payment_info
        ]
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
