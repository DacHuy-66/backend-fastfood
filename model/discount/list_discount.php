<?php

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Thêm tham số tìm kiếm
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Lấy các tham số phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    
    // Lấy kết quả với tham số tìm kiếm
    $result = $discount->read($page, $limit, $search);
    $total_discounts = $discount->getTotalCount($search);
    
    if ($result->num_rows > 0) {
        $discounts_arr = [];
        
        while ($row = $result->fetch_assoc()) {
            // Tính trạng thái
            $current_date = new DateTime();
            $valid_from = new DateTime($row['valid_from']);
            $valid_to = new DateTime($row['valid_to']);
            
            if ((int)$row['status'] === 0) {
                $message = 'Tạm dừng';
            } 
            else {
                if ($current_date < $valid_from) {
                    $message = 'Chờ bắt đầu';
                } elseif ($current_date > $valid_to) {
                    $message = 'Hết hạn';
                } else {
                    $message = 'Đang hoạt động';
                }
                if ((int)$row['quantity'] === 0) {
                    $message = 'Hết số lượng';
                }
            }
            
            $discount_item = array(
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'quantity' => (int)$row['quantity'],
                'minimum_price' => (float)$row['minimum_price'],
                'type' => $row['type'],
                'discount_percent' => (int)$row['discount_percent'],
                'valid_from' => $row['valid_from'],
                'valid_to' => $row['valid_to'],
                'status' => (bool)$row['status'],
                'message' => $message,
                'days_remaining' => $message === 'Đang hoạt động' ? $current_date->diff($valid_to)->days : 0
            );
            
            $discounts_arr[] = $discount_item;
        }
        
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Lấy danh sách discount thành công',
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
            'message' => 'Không tìm thấy discount',
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