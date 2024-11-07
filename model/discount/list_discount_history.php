<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_history.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount_history = new DiscountHistory($conn);

try {
    // Lấy các tham số phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    // Lấy kết quả
    $result = $discount_history->read($page, $limit, $user_id);
    $total_records = $discount_history->getTotalCount($user_id);
    
    if ($result->num_rows > 0) {
        $history_arr = [];
        
        while ($row = $result->fetch_assoc()) {
            $history_item = array(
                'id' => (int)$row['id'],
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'status' => $row['status'],
                'datetime' => $row['datetime'],
                'code' => $row['code'],
            );
            
            $history_arr[] = $history_item;
        }
        
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Lấy lịch sử giảm giá thành công!',
            'code' => 200,
            'data' => [
                'discount_history' => $history_arr,
                'pagination' => [
                    'total' => (int)$total_records,
                    'count' => count($history_arr),
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => ceil($total_records / $limit)
                ]
            ]
        ];
    } else {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Không tìm thấy lịch sử giảm giá!',
            'code' => 200,
            'data' => [
                'discount_history' => [],
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
