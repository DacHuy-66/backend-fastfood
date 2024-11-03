<?php
// File: fix_discount_user.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Discount_user.php';

$discount = new DiscountUser($conn);

// Get discount_id from URL
$discount_id = $matches[1];

// Get posted data
$data = json_decode(file_get_contents("php://input"));

try {
    // Validate dates if provided
    if (!empty($data->valid_from) && !empty($data->valid_to)) {
        $valid_from = new DateTime($data->valid_from);
        $valid_to = new DateTime($data->valid_to);
        
        if ($valid_to < $valid_from) {
            throw new Exception("Valid to date must be after valid from date", 400);
        }
    }

    // Set discount properties
    $discount->id = $discount_id;
    $discount->name = $data->name ?? '';
    $discount->description = $data->description ?? '';
    $discount->minimum_price = $data->minimum_price ?? 0;
    $discount->type = $data->type ?? 'percent';
    $discount->discount_percent = $data->discount_percent ?? 0;
    $discount->valid_from = $data->valid_from ?? null;
    $discount->valid_to = $data->valid_to ?? null;

    // Check if code exists (if code is being updated)
    if (!empty($data->code)) {
        $discount->code = $data->code;
        if ($discount->isCodeExists($discount->code, $discount_id)) {
            throw new Exception("Discount code already exists", 400);
        }
    }

    // Update the discount
    if ($discount->update()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Discount updated successfully',
            'code' => 200,
            'data' => [
                'id' => $discount_id
            ]
        ];
        http_response_code(200);
    } else {
        throw new Exception("Failed to update discount", 500);
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
