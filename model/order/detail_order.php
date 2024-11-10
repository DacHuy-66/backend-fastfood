<?php

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy order_id từ URL thay vì user_id
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($url_path, '/'));
    $order_id = end($path_parts);

    if (empty($order_id)) {
        throw new Exception('ID đơn hàng không được cung cấp!', 400);
    }

    // Query lấy thông tin đơn hàng theo order_id
    $orders_sql = "SELECT o.*, da.phone, da.address
                   FROM orders o
                   LEFT JOIN detail_address da ON o.address_id = da.id
                   WHERE o.id = ?
                   ORDER BY o.created_at DESC";
    
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("s", $order_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();

    if ($orders_result->num_rows === 0) {
        throw new Exception('Không tìm thấy đơn hàng!', 404);
    }

    $order = $orders_result->fetch_assoc();
    
    // Query lấy chi tiết sản phẩm của đơn hàng
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

    // Sửa query lấy thông tin discount
    $discount_sql = "SELECT 
                        COALESCE(d.code, du.code) as code,
                        COALESCE(d.discount_percent, du.discount_percent) as discount_percent,
                        COALESCE(d.description, du.description) as description
                    FROM orders o
                    LEFT JOIN discounts d ON o.discount_code = d.code
                    LEFT JOIN discount_user du ON o.discount_code = du.code
                    WHERE o.id = ?";
    
    $discount_stmt = $conn->prepare($discount_sql);
    $discount_stmt->bind_param("s", $order_id);
    $discount_stmt->execute();
    $discount_result = $discount_stmt->get_result();
    $discount_info = $discount_result->fetch_assoc();

    // Chuẩn bị response với một đơn hàng duy nhất
    $order_detail = [
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
        'discount' => $discount_info,
        'payment' => $payment_info
    ];

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy chi tiết đơn hàng thành công',
        'data' => $order_detail
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
