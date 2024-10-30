<?php
// Include database connection configuration
include_once __DIR__ . '/../config/db.php';
// Include the User model
include_once __DIR__ . '/../model/User.php';

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

// Get user by email and password
$user = User::getByEmailAndPassword($conn, $email, $password);

if ($user) {
    // User is valid
    $api_key = bin2hex(random_bytes(32)); // Generate a random API key

    // Update user API key
    $userObj = new User($conn);
    $userObj->id = $user['id'];
    if ($userObj->updateApiKey($api_key)) {
        // Return the API key to the client
        echo json_encode([
            'success' => true,
            'api_key' => $api_key,
            'message' => 'API key created successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update API key in the database.'
        ]);
    }
} else {
    // Invalid username or password
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password.'
    ]);
}
