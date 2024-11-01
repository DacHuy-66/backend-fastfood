<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Product.php';
include_once __DIR__ . '/../../utils/helpers.php';

$product = new Product($conn);

try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    
    $result = $product->read($page, $limit);
    $total_products = $product->getTotalCount();
    $products_arr = [];
    
    // Prepare the average rating statement once
    $avg_rating_stmt = $conn->prepare("SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?");
    
    while ($row = $result->fetch_assoc()) {
        // Get average rating
        $avg_rating_stmt->bind_param("s", $row['id']);
        $avg_rating_stmt->execute();
        $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
        $average_rating = $avg_rating_result['average_rating'] !== null ? round((float)$avg_rating_result['average_rating'], 1) : 0;
        
        // Convert image URL if exists
        $row['image_url'] = convertToWebUrl($row['image_url']);
        
        // Add additional fields
        $row['_id'] = generateRandomId();
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