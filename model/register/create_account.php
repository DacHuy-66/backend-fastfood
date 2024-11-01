<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$required_fields = ['username', 'email', 'password'];
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

// Sanitize and validate input data
$username = trim($data['username']);
$email = trim($data['email']);
$password = trim($data['password']); 


// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    http_response_code(400);
    exit;
}

// Validate username length
if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Username must be between 3 and 50 characters.'
    ]);
    http_response_code(400);
    exit;
}

// Validate password length
if (strlen($password) < 1) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Password cannot be empty.'
    ]);
    http_response_code(400);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Email already exists.'
    ]);
    http_response_code(409);
    $stmt->close();
    exit;
}
$stmt->close();



// Generate unique ID and API key
$user_id = uniqid();
$api_key = bin2hex(random_bytes(32)); // 64 characters long
$default_avata = 'https://tse4.mm.bing.net/th?id=OIP.Zmki3GIiRk-XKTzRRlxn4QHaER&pid=Api&P=0&h=220'; // Set default avatar path
// Default role is user (0)
$role = '0';

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (id, username, email, password, api_key, role, avata) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $user_id, $username, $email, $password, $api_key, $role, $default_avata);

if ($stmt->execute()) {
    // Return success with user data (excluding password)
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Account created successfully.',

    ]);
    http_response_code(201);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to create account: ' . $stmt->error
    ]);
    http_response_code(500);
}

$stmt->close();
$conn->close();
?>