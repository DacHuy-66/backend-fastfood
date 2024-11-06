<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Lấy dữ liệu được gửi đi
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->id) || !isset($data->code) || !isset($data->description) || 
        !isset($data->discount_percent) || !isset($data->valid_from) || 
        !isset($data->valid_to)) {
        throw new Exception("Thiếu các trường bắt buộc", 400);
    }
    
    // Kiểm tra tỷ lệ giảm giá
    if ($data->discount_percent <= 0 || $data->discount_percent > 100) {
        throw new Exception("Tỷ lệ giảm giá không hợp lệ", 400);
    }
    
    // Kiểm tra ngày hợp lệ
    $valid_from = new DateTime($data->valid_from);
    $valid_to = new DateTime($data->valid_to);
    
    if ($valid_from > $valid_to) {
        throw new Exception("Ngày bắt đầu phải trước ngày hết hạn", 400);
    }
    
    // Đặt các thuộc tính của discount
    $discount->id = $data->id;
    $discount->code = $data->code;
    $discount->description = $data->description;
    $discount->discount_percent = $data->discount_percent;
    $discount->valid_from = $data->valid_from;
    $discount->valid_to = $data->valid_to;
    
    if ($discount->update()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được cập nhật thành công',
            'code' => 200
        ];
        http_response_code(200);
    } else {
        throw new Exception("Lỗi cập nhật discount", 500);
    }
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