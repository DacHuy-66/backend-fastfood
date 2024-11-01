<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Note: $user_id is now passed from the route handler

// Validate required fields
$required_fields = ['address', 'phone'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
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

// Sanitize input data
$address = trim($data['address']);
$phone = trim($data['phone']);
$tengoinho = isset($data['tengoinho']) ? trim($data['tengoinho']) : ''; // Optional field

// Validate phone number format (basic validation)
if (!preg_match('/^[0-9+\-\s()]*$/', $phone)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid phone number format.'
    ]);
    http_response_code(400);
    exit;
}


// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'User not found.'
    ]);
    http_response_code(404);
    $stmt->close();
    exit;
}
$stmt->close();

// Check for duplicate tengoinho for the same user
if (!empty($tengoinho)) {
    $check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE user_id = ? AND tengoinho = ?");
    $check_stmt->bind_param("ss", $user_id, $tengoinho);
    $check_stmt->execute();
    $duplicate_result = $check_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'The specified tengoinho already exists for this user. Please choose a different one.'
        ]);
        http_response_code(400);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
}

// Insert new address
$stmt = $conn->prepare("INSERT INTO detail_address (user_id, address, phone, tengoinho) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $address, $phone, $tengoinho);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Address created successfully.'
    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to create address: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>
