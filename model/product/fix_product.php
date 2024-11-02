<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate that at least one field is provided
if (empty($data) || !is_array($data)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'At least one field must be provided for update.'
    ]);
    http_response_code(400);
    exit;
}

// Initialize arrays for SQL update
$updateFields = [];
$paramValues = [];
$paramTypes = '';

// Handle name update
if (isset($data['name'])) {
    $name = trim($data['name']);
    if (strlen($name) < 2 || strlen($name) > 100) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Product name must be between 2 and 100 characters.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "name = ?";
    $paramValues[] = $name;
    $paramTypes .= "s";
}

// Handle type update
if (isset($data['type'])) {
    $type = trim($data['type']);
    if (empty($type)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Product type cannot be empty.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "type = ?";
    $paramValues[] = $type;
    $paramTypes .= "s";
}

// Handle description update
if (isset($data['description'])) {
    $description = trim($data['description']);
    $updateFields[] = "description = ?";
    $paramValues[] = $description;
    $paramTypes .= "s";
}

// Handle price update
if (isset($data['price'])) {
    $price = floatval($data['price']);
    if ($price <= 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Price must be greater than 0.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "price = ?";
    $paramValues[] = $price;
    $paramTypes .= "d";
}

// Handle quantity update
if (isset($data['quantity'])) {
    $quantity = intval($data['quantity']);
    if ($quantity < 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Quantity cannot be negative.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "quantity = ?";
    $paramValues[] = $quantity;
    $paramTypes .= "i";
}

// Handle status update
if (isset($data['status'])) {
    $status = $data['status'] ? 1 : 0;
    $updateFields[] = "status = ?";
    $paramValues[] = $status;
    $paramTypes .= "i";
}

// Handle discount update
if (isset($data['discount'])) {
    $discount = trim($data['discount']);
    $updateFields[] = "discount = ?";
    $paramValues[] = $discount;
    $paramTypes .= "s";
}

// Handle image_url update
if (isset($data['image_url'])) {
    $image_url = trim($data['image_url']);
    $updateFields[] = "image_url = ?";
    $paramValues[] = $image_url;
    $paramTypes .= "s";
}

// Check if the product exists
$check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
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
$check_stmt->close();

// Add product_id to parameters
$paramTypes .= "s";
$paramValues[] = $product_id;

// Construct and execute update query
$sql = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically
$bindParams = array_merge([$paramTypes], $paramValues);
$tmp = [];
foreach ($bindParams as $key => $value) {
    $tmp[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $tmp);

if ($stmt->execute()) {
    // Fetch updated product
    $select_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $select_stmt->bind_param("s", $product_id);
    $select_stmt->execute();
    $updated_product = $select_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Product updated successfully.',
        'data' => $updated_product
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to update product: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>