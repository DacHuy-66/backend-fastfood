<?php
// File: delete_discount_user.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Get discount_id from URL
$request_uri = $_SERVER['REQUEST_URI'];


$discount_id = $matches[1];

try {
    // Set ID property
    $discount->id = $discount_id;

    // Delete the discount
    if ($discount->delete()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount deleted successfully',
            'code' => 200,
            'data' => [
                'id' => $discount_id
            ]
        ];
        http_response_code(200);
    } else {
        throw new Exception("Failed to delete discount", 500);
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
