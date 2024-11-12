<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy top sản phẩm bán chạy
    $top_products_sql = "SELECT 
                            p.name,
                            SUM(po.quantity) as total_sold,
                            SUM(po.price * po.quantity) as total_revenue
                        FROM products p
                        JOIN product_order po ON p.id = po.product_id
                        GROUP BY p.id
                        ORDER BY total_sold DESC
                        LIMIT 5"; 

    $top_products_stmt = $conn->prepare($top_products_sql);
    $top_products_stmt->execute();
    $top_products_result = $top_products_stmt->get_result();
    
    $data = [];
    
    while($row = $top_products_result->fetch_assoc()) {
        $data[] = [
            'name' => $row['name'],
            'sold' => (int)$row['total_sold'],
            'revenue' => number_format((float)$row['total_revenue'], 0, ',', '.') . 'đ' // Format revenue
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy danh sách sản phẩm bán chạy thành công',
        'data' => $data,
    ];

    echo json_encode($response);

} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
    
    http_response_code(500);
    echo json_encode($response);
} 