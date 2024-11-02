<?php
// Include database connection configuration
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

include_once __DIR__ . '/../../config/db.php';

// Get the API key from the headers
$headers = getallheaders();
$api_key = $headers['X-Api-Key'] ?? '';

// Check if API key is provided
if (empty($api_key)) {
    echo json_encode([
        'ok' => false,
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

    // Update the user's role to '1'
    $update_sql = "UPDATE users SET role = '1' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $user['id']);
    if ($update_stmt->execute()) {
        // Prepare the response data structure
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'User role updated successfully.',
        ]);
    } else {
        // Error updating user role
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Failed to update user role.'
        ]);
        http_response_code(500);
    }
} else {
    // Invalid API key  
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid API key.'
    ]);
    http_response_code(404);
}

// Close the connection
$conn->close();
?>
