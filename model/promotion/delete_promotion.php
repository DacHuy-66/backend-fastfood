<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Promotion.php';
include_once __DIR__ . '/../../utils/helpers.php';

$promotion = new Promotion($conn);

try {
    // Get ID from URL
    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
    $id = end($url_parts);
    
    if (!$id || !is_numeric($id)) {
        throw new Exception("Invalid promotion ID", 400);
    }
    
    $promotion->id = $id;
    
    if ($promotion->delete()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Promotion deleted successfully',
            'code' => 200
        ];
        http_response_code(200);
    } else {
        throw new Exception("Failed to delete promotion", 400);
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
