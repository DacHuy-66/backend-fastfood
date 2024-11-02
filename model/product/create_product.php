<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields based on products table schema
$required_fields = ['name', 'description', 'type', 'price', 'quantity'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
    ]);
    http_response_code(400);
    exit;
}

// Sanitize and validate input data
$name = trim($data['name']);
$description = trim($data['description']);
$type = trim($data['type']);
$price = floatval($data['price']);
$quantity = intval($data['quantity']);
$status = isset($data['status']) ? $data['status'] : true;
$discount = isset($data['discount']) ? trim($data['discount']) : null;
$image_url = isset($data['image_url']) ? trim($data['image_url']) : null;
$created_at = date('Y-m-d H:i:s');

// Validate product name length
if (strlen($name) < 2 || strlen($name) > 100) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Product name must be between 2 and 100 characters.'
    ]);
    http_response_code(400);
    exit;
}

// Validate type
if (empty($type)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Product type cannot be empty.'
    ]);
    http_response_code(400);
    exit;
}

// Validate price
if ($price <= 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Price must be greater than 0.'
    ]);
    http_response_code(400);
    exit;
}

// Validate quantity
if ($quantity < 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Quantity cannot be negative.'
    ]);
    http_response_code(400);
    exit;
}

// Generate unique product ID
$product_id = uniqid();

// Insert new product
$stmt = $conn->prepare("INSERT INTO products (id, name, description, type, price, quantity, status, discount, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssdiisss", 
    $product_id, 
    $name, 
    $description, 
    $type,
    $price, 
    $quantity, 
    $status, 
    $discount, 
    $image_url, 
    $created_at
);

if ($stmt->execute()) {
    // Fetch the newly created product
    $select_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $select_stmt->bind_param("s", $product_id);
    $select_stmt->execute();
    $new_product = $select_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Product created successfully.',
        'data' => $new_product
    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to create product: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>