<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';
include_once __DIR__ . '/../../model/Promotion.php';
setDefaultCorsHeaders();

$promotion = new Promotion($conn);

try {
    // Extract the ID from the URL
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match("/\/promotion\/(\d+)$/", $uri, $matches)) {
        $id = intval($matches[1]);
    } else {
        throw new Exception("Promotion ID is required", 400);
    }

    // Get JSON data from request body
    $data = json_decode(file_get_contents("php://input"));

    if (
        !$data || !isset($data->title) || !isset($data->description) ||
        !isset($data->discount_percent) || !isset($data->start_date) ||
        !isset($data->end_date) || !isset($data->min_order_value) ||
        !isset($data->max_discount)
    ) {
        throw new Exception("Missing required fields", 400);
    }

    // Validate discount percentage
    if ($data->discount_percent <= 0 || $data->discount_percent > 100) {
        throw new Exception("Invalid discount percentage", 400);
    }

    // Set promotion properties (excluding id)
    $promotion->id = $id;
    $promotion->title = $data->title;
    $promotion->description = $data->description;
    $promotion->discount_percent = $data->discount_percent;
    $promotion->start_date = $data->start_date;
    $promotion->end_date = $data->end_date;
    $promotion->min_order_value = $data->min_order_value;
    $promotion->max_discount = $data->max_discount;

    // Update promotion
    if ($promotion->update()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Promotion updated successfully',
            'code' => 200
        ];
        http_response_code(200);
    } else {
        throw new Exception("Error updating promotion", 500);
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
