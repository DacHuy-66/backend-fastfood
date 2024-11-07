<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Lấy ID từ URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($url_path, '/'));
    $discount_id = end($path_parts);

    if (empty($discount_id)) {
        throw new Exception("ID discount không được cung cấp!", 400);
    }

    // Lấy dữ liệu được gửi đi
    $data = json_decode(file_get_contents("php://input"));

    // if (
    //     !$data || !isset($data->code) ||
    //     !isset($data->discount_percent) || !isset($data->valid_from) ||
    //     !isset($data->valid_to)
    // ) {
    //     throw new Exception("Thiếu các trường bắt buộc", 400);
    // }

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
    $discount->id = $discount_id;
    $discount->code = $data->code;
    $discount->name = $data->name ?? '';
    $discount->description = $data->description ?? '';
    $discount->discount_percent = $data->discount_percent;
    $discount->valid_from = $data->valid_from;
    $discount->valid_to = $data->valid_to;
    $discount->quantity = $data->quantity ?? 0;
    $discount->minimum_price = $data->minimum_price ?? 0;
    $discount->type = $data->type ?? 'percent';
    $discount->status = $data->status ?? 1;

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
