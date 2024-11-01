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

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate that at least one field is provided
if (!isset($data['address']) && !isset($data['phone']) && !isset($data['tengoinho'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'At least one field (address, phone, or tengoinho) must be provided for update.'
    ]);
    http_response_code(400);
    exit;
}

// Get id from input and validate it
$address_id = $data['id'] ?? null;

if (!$address_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Address ID is required.'
    ]);
    http_response_code(400);
    exit;
}

// Initialize arrays for SQL update
$updateFields = [];
$paramValues = [];
$paramTypes = '';

// Handle phone update
if (isset($data['phone'])) {
    $phone = trim($data['phone']);
    if (!preg_match('/^[0-9+\-\s()]*$/', $phone)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Invalid phone number format.'
        ]);
        http_response_code(400);
        exit;
    }
    $updateFields[] = "phone = ?";
    $paramValues[] = $phone;
    $paramTypes .= "s";
}

// Handle tengoinho update with uniqueness check
if (isset($data['tengoinho'])) {
    $tengoinho = trim($data['tengoinho']);
    if (strlen($tengoinho) > 100) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Tengoinho must not exceed 100 characters.'
        ]);
        http_response_code(400);
        exit;
    }

    // Check for duplicate tengoinho
    $check_tengoinho_stmt = $conn->prepare("SELECT id FROM detail_address WHERE tengoinho = ? AND id != ?");
    $check_tengoinho_stmt->bind_param("si", $tengoinho, $address_id);
    $check_tengoinho_stmt->execute();
    $duplicate_result = $check_tengoinho_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'The specified tengoinho already exists. Please choose a different one.'
        ]);
        http_response_code(400);
        $check_tengoinho_stmt->close();
        exit;
    }
    $check_tengoinho_stmt->close();

    $updateFields[] = "tengoinho = ?";
    $paramValues[] = $tengoinho;
    $paramTypes .= "s";
}

// Check if the address with the given id exists
$check_stmt = $conn->prepare("SELECT id FROM detail_address WHERE id = ?");
$check_stmt->bind_param("i", $address_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Address not found.'
    ]);
    http_response_code(404);
    $check_stmt->close();
    exit;
}
$check_stmt->close();

// Add address_id to parameters
$paramTypes .= "i";
$paramValues[] = $address_id;

// Construct and execute update query
$sql = "UPDATE detail_address SET " . implode(", ", $updateFields) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically
$bindParams = array_merge([$paramTypes], $paramValues);
$tmp = [];
foreach ($bindParams as $key => $value) {
    $tmp[$key] = &$bindParams[$key];
}
call_user_func_array([$stmt, 'bind_param'], $tmp);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Address updated successfully.'
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to update address: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>
