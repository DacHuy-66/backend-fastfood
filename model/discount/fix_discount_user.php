<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Lấy discount_id từ URL
if (!isset($matches[1])) {
    echo json_encode([
        'ok' => false,
        'status' => 'error',
        'message' => 'Thiếu discount_id',
        'code' => 400
    ]);
    http_response_code(400);
    exit;
}

$discount_id = $matches[1];

// Lấy dữ liệu được gửi đi
$data = json_decode(file_get_contents("php://input"));

try {
    // Kiểm tra discount_id có tồn tại không
    $existingDiscount = $discount->show($discount_id);
    if ($existingDiscount->num_rows === 0) {
        throw new Exception("Không tìm thấy discount với ID này", 404);
    }

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
    $discount->discount_percent = $data->discount_percent ?? 0;
    $discount->valid_from = $data->valid_from ?? null;
    $discount->valid_to = $data->valid_to ?? null;
    $discount->status = $data->status ?? 1;
    // cập nhật email và user_id nếu email được cung cấp
    if (!empty($data->email)) {
        $discount->email = $data->email;
        $user_id = $discount->getUserIdByEmail($data->email);
        if ($user_id === null) {
            throw new Exception("Không tìm thấy user với email này", 400);
        }
        $discount->user_id = $user_id;
    }

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
                'id' => $discount_id,
                'email' => $discount->email,
                'user_id' => $discount->user_id
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
