<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
    
    if ($page <= 0 || $limit <= 0) {
        throw new Exception('Page and limit must be positive integers', 400);
    }
    
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT r.*, u.username, u.avata
              FROM reviews r
              LEFT JOIN users u ON r.user_id = u.id ";
    
    if ($product_id) {
        $query .= "WHERE r.product_id = ? ";
    }
    
    $query .= "ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    
    if ($product_id) {
        $stmt->bind_param("iii", $product_id, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews_arr = [];
    
    while ($row = $result->fetch_assoc()) {
        $reviews_arr[] = $row;
    }
    
    $count_query = "SELECT COUNT(*) as total FROM reviews";
    if ($product_id) {
        $count_query .= " WHERE product_id = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $product_id);
    } else {
        $count_stmt = $conn->prepare($count_query);
    }
    
    $count_stmt->execute();
    $total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
    
    $response = [
        'status' => 'success',
        'message' => 'Reviews retrieved successfully',
        'code' => 200,
        'data' => [
            'reviews' => $reviews_arr,
            'pagination' => [
                'total' => $total_reviews,
                'count' => count($reviews_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_reviews / $limit)
            ]
        ]
    ];
    http_response_code(200);

} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
?>
