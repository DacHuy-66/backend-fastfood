<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Lấy discount_id từ URL
$discount_id = $matches[1];

// Lấy dữ liệu được gửi đi
$data = json_decode(file_get_contents("php://input"));

try {
    // Kiểm tra ngày hợp lệ nếu được cung cấp
    if (!empty($data->valid_from) && !empty($data->valid_to)) {
        $valid_from = new DateTime($data->valid_from);
        $valid_to = new DateTime($data->valid_to);
        
        if ($valid_to < $valid_from) {
            throw new Exception("Ngày hết hạn phải sau ngày bắt đầu", 400);
        }
    }

    // Đặt các thuộc tính của discount
    $discount->id = $discount_id;
    $discount->name = $data->name ?? '';
    $discount->description = $data->description ?? '';
    $discount->minimum_price = $data->minimum_price ?? 0;
    $discount->type = $data->type ?? 'percent';
    $discount->discount_percent = $data->discount_percent ?? 0;
    $discount->valid_from = $data->valid_from ?? null;
    $discount->valid_to = $data->valid_to ?? null;

    // Kiểm tra xem mã có tồn tại hay không (nếu mã đang được cập nhật)
    if (!empty($data->code)) {
        $discount->code = $data->code;
        if ($discount->isCodeExists($discount->code, $discount_id)) {
            throw new Exception("Mã giảm giá đã tồn tại", 400);
        }
    }

    // Cập nhật discount
    if ($discount->update()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được cập nhật thành công',
            'code' => 200,
            'data' => [
                'id' => $discount_id
            ]
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

echo json_encode($response);
?>
