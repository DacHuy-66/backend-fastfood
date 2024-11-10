<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Api-Key");
header('Content-Type: application/json');

include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy API key từ header
    $headers = getallheaders();
    $api_key = $headers['X-Api-Key'] ?? '';

    if (empty($api_key)) {
        throw new Exception('API key bị thiếu.', 401);
    }

    // Lấy user_id từ API key
    $user_sql = "SELECT id FROM users WHERE api_key = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $api_key);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        throw new Exception('API key không hợp lệ.', 401);
    }

    $user_id = $user_result->fetch_assoc()['id'];

    // Lấy danh sách đơn hàng
    $orders_sql = "SELECT o.*, 
                         p.payment_method, 
                         p.payment_status,
                         da.address,
                         da.phone
                  FROM orders o
                  LEFT JOIN payments p ON o.id = p.order_id
                  LEFT JOIN detail_address da ON o.address_id = da.id
                  WHERE o.user_id = ?
                  ORDER BY o.created_at DESC";

    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("s", $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();

    $orders = [];
    while ($order = $orders_result->fetch_assoc()) {
        // Lấy chi tiết sản phẩm trong đơn hàng
        $products_sql = "SELECT po.*, p.name as product_name, p.image_url
                        FROM product_order po
                        LEFT JOIN products p ON po.product_id = p.id
                        WHERE po.order_id = ?";
        
        $products_stmt = $conn->prepare($products_sql);
        $products_stmt->bind_param("s", $order['id']);
        $products_stmt->execute();
        $products_result = $products_stmt->get_result();

        $products = [];
        while ($product = $products_result->fetch_assoc()) {
            $products[] = [
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'image_url' => $product['image_url']
            ];
        }

        $orders[] = [
            'order_id' => $order['id'],
            'created_at' => $order['created_at'],
            'status' => $order['status'],
            'total_price' => $order['total_price'],
            // 'address' => $order['address'],
            'phone' => $order['phone'],
            'payment_method' => $order['payment_method'],
            'payment_status' => $order['payment_status'],
            'products' => $products
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy lịch sử đơn hàng thành công',
        'data' => $orders
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode());
    echo json_encode($response);
}

$conn->close();
?>
