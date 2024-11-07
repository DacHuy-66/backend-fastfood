<?php

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Lấy dữ liệu được gửi đi
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->code) || !isset($data->name) || 
        !isset($data->discount_percent) || !isset($data->valid_from) || !isset($data->valid_to) ||
        !isset($data->quantity) || !isset($data->minimum_price) || !isset($data->type)) {
        throw new Exception("Thiếu các trường bắt buộc", 400);
    }
    
    // Kiểm tra mã code đã tồn tại chưa
    if ($discount->isCodeExists($data->code)) {
        throw new Exception("Mã giảm giá này đã tồn tại", 400);
    }
    
    // Kiểm tra tỷ lệ giảm giá
    if ($data->discount_percent <= 0 || $data->discount_percent > 100) {
        throw new Exception("Tỷ lệ giảm giá không hợp lệ", 400);
    }
    
    // Kiểm tra ngày hợp lệ
    $valid_from = new DateTime($data->valid_from);
    $valid_to = new DateTime($data->valid_to);
    $current_date = new DateTime();
    
    if ($valid_from > $valid_to) {
        throw new Exception("Ngày bắt đầu phải trước ngày hết hạn", 400);
    }
    
    // Đặt các thuộc tính của discount
    $discount->code = $data->code;
    $discount->name = $data->name;
    $discount->description = $data->description ?? '';
    $discount->discount_percent = $data->discount_percent;
    $discount->valid_from = $data->valid_from;
    $discount->valid_to = $data->valid_to;
    $discount->quantity = $data->quantity;
    $discount->minimum_price = $data->minimum_price;
    $discount->type = $data->type;
    $discount->status = 1;
    
    if ($discount->create()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được tạo thành công',
            'code' => 201
        ];
        http_response_code(201);
    } else {
        throw new Exception("Lỗi tạo discount", 500);
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
