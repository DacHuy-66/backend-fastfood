<?php

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Product.php';
include_once __DIR__ . '/../../utils/helpers.php';

$product = new Product($conn);

try {
    // Lấy limit từ query parameter hoặc sử dụng giá trị mặc định
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

    // Xây dựng bộ lọc từ query parameters
    $filters = [
        'type' => isset($_GET['type']) ? trim($_GET['type']) : '',
        'min_price' => isset($_GET['min_price']) ? floatval($_GET['min_price']) : null,
        'max_price' => isset($_GET['max_price']) ? floatval($_GET['max_price']) : null,
        'status' => isset($_GET['status']) ? intval($_GET['status']) : null
    ];

    // Lấy danh sách sản phẩm bán chạy
    $result = $product->getTopSellingProducts($limit, $filters);
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
        'message' => 'Top selling products retrieved successfully',
        'code' => 200,
        'data' => [
            'products' => $products_arr,
            'filters' => [
                'limit' => $limit,
                'type' => $filters['type'],
                'min_price' => $filters['min_price'],
                'max_price' => $filters['max_price'],
                'status' => $filters['status']
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
