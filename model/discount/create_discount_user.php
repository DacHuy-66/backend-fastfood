<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Lấy dữ liệu được gửi đi
$data = json_decode(file_get_contents("php://input"));

try {
    // Kiểm tra các trường bắt buộc
    if (empty($data->email) || empty($data->valid_from) || empty($data->valid_to)) {
        throw new Exception("Thiếu các trường bắt buộc", 400);
    }

    // Lấy user_id từ email
    $userId = $discount->getUserIdByEmail($data->email);
    if (!$userId) {
        throw new Exception("Không tìm thấy user với email này", 404);
    }

    // Kiểm tra ngày hợp lệ
    $valid_from = new DateTime($data->valid_from);
    $valid_to = new DateTime($data->valid_to);
    $today = new DateTime();

    if ($valid_to < $valid_from) {
        throw new Exception("Ngày hết hạn phải sau ngày bắt đầu", 400);
    }

    // Đặt các thuộc tính của discount
    $discount->name = $data->name ?? '';
    $discount->user_id = $userId;
    $discount->email = $data->email;
    $discount->description = $data->description ?? '';
    $discount->minimum_price = $data->minimum_price ?? 0;
    $discount->type = $data->type ?? 'percent';
    $discount->discount_percent = $data->discount_percent ?? 0;
    $discount->valid_from = $data->valid_from;
    $discount->valid_to = $data->valid_to;
    $discount->status = $data->status ?? 1;

    // Tạo mã duy nhất nếu không được cung cấp hoặc rỗng
    if (empty($data->code)) {
        $discount->code = $discount->generateUniqueCode();
    } else {
        $discount->code = $data->code;
    }

    // Kiểm tra xem mã đã tồn tại hay chưa
    if ($discount->isCodeExists($discount->code)) {
        throw new Exception("Mã giảm giá đã tồn tại", 400);
    }

    // Tạo discount
    if ($discount->create()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được tạo thành công',
            'code' => 201,
            'data' => [
                'code' => $discount->code,
                'user_id' => $discount->user_id
            ]
        ];
        http_response_code(201);
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

echo json_encode($response);
?>