<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';
include_once __DIR__ . '/../../model/Promotion.php';

setDefaultCorsHeaders();
// Initialize the Promotion class
$promotion = new Promotion($conn);

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;

// Get results
$result = $promotion->read($page, $limit);
$total_promotions = $promotion->getTotalCount();

if ($result->num_rows > 0) {
    $promotions_arr = [];

    while ($row = $result->fetch_assoc()) {
        $promotion_item = array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'discount_percent' => (int)$row['discount_percent'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'min_order_value' => $row['min_order_value'],
            'max_discount' => $row['max_discount'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        );

        $promotions_arr[] = $promotion_item;
    }

    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Promotions retrieved successfully',
        'code' => 200,
        'data' => [
            'promotions' => $promotions_arr,
            'pagination' => [
                'total' => (int)$total_promotions,
                'count' => count($promotions_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_promotions / $limit)
            ]
        ]
    ];
} else {
    $response = [
        'ok' => false,
        'status' => 'error',
        'message' => 'No promotions found',
        'code' => 404,
        'data' => []
    ];
}

echo json_encode($response);
