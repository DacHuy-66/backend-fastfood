<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';

// Retrieve API key from headers
$headers = apache_request_headers();
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Assuming $id_user is received from the client
$id_user = isset($id_user) ? $id_user : null;

// Check if both API key and user ID are provided
if (!$api_key || !$id_user) {
    echo json_encode([
        'success' => false,
        'message' => 'API key or user ID not provided.'
    ]);
    http_response_code(400);
    exit;
}

// Validate API key and user ID and get current password
$stmt = $conn->prepare("SELECT id, password FROM users WHERE id = ? AND api_key = ?");
$stmt->bind_param("ss", $id_user, $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Read PUT request data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $current_password = isset($data['current_password']) ? $data['current_password'] : null;
    $new_password = isset($data['new_password']) ? $data['new_password'] : null;

    // Verify that both passwords are provided
    if (!$current_password || !$new_password) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Current password and new password are required.'
        ]);
        http_response_code(400);
        exit;
    }

    // Direct comparison with stored password
    if ($current_password == $user['password']) {
        // Update with new password
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("ss", $new_password, $id_user);

        if ($update_stmt->execute()) {
            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);
            http_response_code(200);
        } else {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Failed to update password: ' . $update_stmt->error
            ]);
            http_response_code(500);
        }
        $update_stmt->close();
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Current password is incorrect.'
        ]);
        http_response_code(401);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid API key or user ID.'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
?>