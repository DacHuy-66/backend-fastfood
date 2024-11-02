<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get product ID from URL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$product_id = end($path_parts);

if (empty($product_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid product ID format.'
    ]);
    http_response_code(400);
    exit;
}

// Check if the product exists and get its data before deletion
$check_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$check_stmt->bind_param("s", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Product not found.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}

// Store product data before deletion
$product_data = $result->fetch_assoc();
$check_stmt->close();

// Check if product has associated reviews
$review_check_stmt = $conn->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE product_id = ?");
$review_check_stmt->bind_param("s", $product_id);
$review_check_stmt->execute();
$review_result = $review_check_stmt->get_result()->fetch_assoc();
$review_check_stmt->close();

// Check if product has associated order items
$order_check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM order_items WHERE product_id = ?");
$order_check_stmt->bind_param("s", $product_id);
$order_check_stmt->execute();
$order_result = $order_check_stmt->get_result()->fetch_assoc();
$order_check_stmt->close();

// If there are associated records, don't delete but mark as inactive
if ($review_result['review_count'] > 0 || $order_result['order_count'] > 0) {
    $update_stmt = $conn->prepare("UPDATE products SET status = 0, updated_at = ? WHERE id = ?");
    $current_time = date('Y-m-d H:i:s');
    $update_stmt->bind_param("ss", $current_time, $product_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Product has associated records and has been marked as inactive instead of being deleted.',
            'data' => array_merge($product_data, ['status' => 0, 'updated_at' => $current_time])
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Failed to update product status: ' . $update_stmt->error
        ]);
        http_response_code(500);
    }
    $update_stmt->close();
    $conn->close();
    exit;
}

// If no associated records exist, proceed with deletion
$delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$delete_stmt->bind_param("s", $product_id);

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Product deleted successfully.'
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'No product was deleted.'
        ]);
        http_response_code(400);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to delete product: ' . $delete_stmt->error
    ]);
    http_response_code(500);
}

$delete_stmt->close();
$conn->close();
?>