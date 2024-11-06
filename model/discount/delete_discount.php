<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount.php';
include_once __DIR__ . '/../../utils/helpers.php';

$discount = new Discount($conn);

try {
    // Lấy dữ liệu
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->id)) {
        throw new Exception("Thiếu ID discount", 400);
    }
    
    $discount->id = $data->id;
    
    if ($discount->delete()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount được xóa thành công',
            'code' => 200
        ];
        http_response_code(200);
    } else {
        throw new Exception("Không thể xóa discount đang được sử dụng", 400);
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