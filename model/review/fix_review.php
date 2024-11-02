<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../Review.php';

try {
    // Parse input data
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required ID parameter
    if (!isset($data->id)) {
        throw new Exception('Review ID is required', 400);
    }
    
    // Initialize review object
    $review = new Review($conn);
    $review->id = $data->id;
    
    // Check if review exists
    $result = $review->show($review->id);
    if ($result->num_rows === 0) {
        throw new Exception('Review not found', 404);
    }
    
    // Get current review data
    $current_review = $result->fetch_assoc();
    
    // Validate and assign rating if provided
    if (isset($data->rating)) {
        if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
            throw new Exception('Invalid rating. Must be between 1 and 5', 400);
        }
        $review->rating = $data->rating;
    } else {
        $review->rating = $current_review['rating']; // Keep existing rating
    }
    
    // Handle comment and image updates
    $review->comment = isset($data->comment) ? $data->comment : $current_review['comment'];
    $review->image_1 = isset($data->image_1) ? $data->image_1 : $current_review['image_1'];
    $review->image_2 = isset($data->image_2) ? $data->image_2 : $current_review['image_2'];
    $review->image_3 = isset($data->image_3) ? $data->image_3 : $current_review['image_3'];
    
    // Update review
    if ($review->update()) {
        // Fetch and return updated review data
        $updated = $review->show($review->id)->fetch_assoc();
        $response = [
            'status' => 'success',
            'message' => 'Review updated successfully',
            'code' => 200,
            'data' => $updated
        ];
        http_response_code(200);
    } else {
        throw new Exception('Failed to update review', 500);
    }
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'code' => $e->getCode() ?: 400,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

echo json_encode($response);
