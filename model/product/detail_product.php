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
    $product_id = basename($_SERVER['REQUEST_URI']);
    
    $result = $product->show($product_id);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $row['_id'] = generateRandomId(); // Use the helper function
        
        // Calculate average rating
        $avg_rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?";
        $avg_rating_stmt = $conn->prepare($avg_rating_query);
        $avg_rating_stmt->bind_param("s", $product_id);
        $avg_rating_stmt->execute();
        $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
        $average_rating = $avg_rating_result['average_rating'] !== null ? (float)$avg_rating_result['average_rating'] : 0;
        
        // Fetch reviews
        $reviews_query = "SELECT * FROM reviews WHERE product_id = ?";
        $reviews_stmt = $conn->prepare($reviews_query);
        $reviews_stmt->bind_param("s", $product_id);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();
        
        $reviews = [];
        while ($review = $reviews_result->fetch_assoc()) {
            $reviews[] = $review;
        }
        
        $response = [
            'status' => 'success',
            'message' => 'Product retrieved successfully',
            'code' => 200,
            'data' => array_merge($row, [
                'average_rating' => $average_rating,
                'reviews' => $reviews
            ])
        ];
        http_response_code(200);
    } else {
        throw new Exception('Product not found', 404);
    }
} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ? $e->getCode() : 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ? $e->getCode() : 400);
}

echo json_encode($response);