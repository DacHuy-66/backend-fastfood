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
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'limit' => true
    ]);
    http_response_code(400);
    exit;
}

// Sanitize input data
$address = trim($data['address']);
$phone = trim($data['phone']);
$note = isset($data['note']) ? trim($data['note']) : ''; // Optional field

// Validate phone number format (basic validation)
if (!preg_match('/^[0-9+\-\s()]*$/', $phone)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid phone number format.',
        'limit' => true
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
        'message' => 'User not found.',
        'limit' => true
    ]);
    http_response_code(404);
    $stmt->close();
    exit;
}
$stmt->close();

// Check if the user has reached the address limit (3)
$count_stmt = $conn->prepare("SELECT COUNT(*) as address_count FROM detail_address WHERE user_id = ?");
$count_stmt->bind_param("s", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_data = $count_result->fetch_assoc();

if ($count_data['address_count'] >= 3) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Address limit reached. You can only add up to 3 addresses.',
        'limit' => false
    ]);
    http_response_code(400);
    $count_stmt->close();
    exit;
}
$count_stmt->close();

// Check for duplicate note for the same user
if (!empty($note)) {
    $check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE user_id = ? AND note = ?");
    $check_stmt->bind_param("ss", $user_id, $note);
    $check_stmt->execute();
    $duplicate_result = $check_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'The specified note already exists for this user. Please choose a different one.',
            'status' => false,
            'limit' => true
        ]);
        http_response_code(400);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
}

// Insert new address
$stmt = $conn->prepare("INSERT INTO detail_address (user_id, address, phone, note) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $address, $phone, $note);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Address created successfully.',
        'limit' => true
    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to create address: ' . $stmt->error,
        'limit' => true
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>
