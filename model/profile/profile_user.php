<?php
// Include database connection configuration
include_once __DIR__ . '/../../config/db.php';

// Get the API key from the headers
$headers = getallheaders();
$api_key = $headers['X-Api-Key'] ?? '';

// Check if API key is provided
if (empty($api_key)) {
    echo json_encode([
        'success' => false,
        'message' => 'API key is missing.'
    ]);
    exit;
}

// Check if the API key exists in the database
$sql = "SELECT * FROM users WHERE api_key = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // API key is valid, fetch user details
    $user = $result->fetch_assoc();

    // Return user information (excluding sensitive data)
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
            // You can add more fields if necessary
        ]
    ]);
} else {
    // Invalid API key  
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API key.'
    ],
);
http_response_code(response_code: 404);
}

// Close the connection
$conn->close();
?>
