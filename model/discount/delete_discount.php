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
    // Get data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->id)) {
        throw new Exception("Missing discount ID", 400);
    }
    
    $discount->id = $data->id;
    
    if ($discount->delete()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount deleted successfully',
            'code' => 200
        ];
        http_response_code(200);
    } else {
        throw new Exception("Cannot delete discount that is in use", 400);
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