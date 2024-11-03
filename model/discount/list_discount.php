<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    
    // Get results
    $result = $discount->read($page, $limit);
    $total_discounts = $discount->getTotalCount();
    
    if ($result->num_rows > 0) {
        $discounts_arr = [];
        
        while ($row = $result->fetch_assoc()) {
            // Calculate status
            $current_date = new DateTime();
            $valid_from = new DateTime($row['valid_from']);
            $valid_to = new DateTime($row['valid_to']);
            
            if ($current_date < $valid_from) {
                $status = 'pending';
            } elseif ($current_date > $valid_to) {
                $status = 'expired';
            } else {
                $status = 'active';
            }
            
            $discount_item = array(
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'minimum_price' => $row['minimum_price'],
                'type' => $row['type'],
                'discount_percent' => (int)$row['discount_percent'],
                'valid_from' => $row['valid_from'],
                'valid_to' => $row['valid_to'],
                'status' => $status,
                'days_remaining' => $status === 'active' ? $current_date->diff($valid_to)->days : 0
            );
            
            $discounts_arr[] = $discount_item;
        }
        
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discounts retrieved successfully',
            'code' => 200,
            'data' => [
                'discounts' => $discounts_arr,
                'pagination' => [
                    'total' => (int)$total_discounts,
                    'count' => count($discounts_arr),
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => ceil($total_discounts / $limit)
                ]
            ]
        ];
    } else {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'No discounts found',
            'code' => 200,
            'data' => [
                'discounts' => [],
                'pagination' => [
                    'total' => 0,
                    'count' => 0,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => 0
                ]
            ]
        ];
    }
    
    http_response_code(200);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 400
    ];
    http_response_code($e->getCode() ?: 400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);