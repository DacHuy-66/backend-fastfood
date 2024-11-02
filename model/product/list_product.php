<?php
// File: ./model/product/list_product.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Product.php';
include_once __DIR__ . '/../../utils/helpers.php';

$product = new Product($conn);

try {
    // Lấy các tham số phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 40;
    
    // Lấy các tham số tìm kiếm và lọc
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    $filters = [
        'type' => isset($_GET['type']) ? trim($_GET['type']) : '',
        'min_price' => isset($_GET['min_price']) ? floatval($_GET['min_price']) : null,
        'max_price' => isset($_GET['max_price']) ? floatval($_GET['max_price']) : null,
        'status' => isset($_GET['status']) ? intval($_GET['status']) : null,
        'sort_by' => isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'created_at',
        'sort_order' => isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC'
    ];
    
    // Lấy danh sách sản phẩm với các điều kiện
    $result = $product->read($page, $limit, $search, $filters);
    $total_products = $product->getTotalCount($search, $filters);
    $products_arr = [];
    
    // Prepare the average rating statement once
    $avg_rating_stmt = $conn->prepare("SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?");
    
    while ($row = $result->fetch_assoc()) {
        // Get average rating
        $avg_rating_stmt->bind_param("s", $row['id']);
        $avg_rating_stmt->execute();
        $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
        $average_rating = $avg_rating_result['average_rating'] !== null ? round((float)$avg_rating_result['average_rating'], 1) : 0;
        
        // Add additional fields
        $row['average_rating'] = $average_rating;
        
        // Convert numeric fields to appropriate types
        $row['price'] = (float)$row['price'];
        $row['sold'] = (int)$row['sold'];
        $row['quantity'] = (int)$row['quantity'];
        $row['status'] = (int)$row['status'];
        $row['discount'] = $row['discount'] !== null ? (float)$row['discount'] : null;
        
        $products_arr[] = $row;
    }
    
    $avg_rating_stmt->close();
    
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Products retrieved successfully',
        'code' => 200,
        'data' => [
            'products' => $products_arr,
            'pagination' => [
                'total' => (int)$total_products,
                'count' => count($products_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_products / $limit)
            ],
            'filters' => [
                'search' => $search,
                'type' => $filters['type'],
                'min_price' => $filters['min_price'],
                'max_price' => $filters['max_price'],
                'status' => $filters['status'],
                'sort_by' => $filters['sort_by'],
                'sort_order' => $filters['sort_order']
            ]
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
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>