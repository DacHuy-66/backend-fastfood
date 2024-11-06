<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Lấy discount_id từ URL
$request_uri = $_SERVER['REQUEST_URI'];


$discount_id = $matches[1];

try {
    // Đặt ID thuộc tính
    $discount->id = $discount_id;

    // Xóa discount
    if ($discount->delete()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được xóa thành công',
            'code' => 200,
            'data' => [
                'id' => $discount_id
            ]
        ];
        http_response_code(200);
    } else {
        throw new Exception("Lỗi xóa discount", 500);
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
