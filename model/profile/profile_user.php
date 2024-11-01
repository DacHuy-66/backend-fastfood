<?php
// Include database connection configuration
header("Access-Control-Allow-Origin: *");  // Or specify your frontend domain
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Api-Key");
header('Content-Type: application/json'); // Set content type to JSON

include_once __DIR__ . '/../../config/db.php';

// Define the default avatar URL
$default_avatar = 'https://thumbs.dreamstime.com/b/default-avatar-profile-image-vector-social-media-user-icon-potrait-182347582.jpg';

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

    // Assign default avatar if avata field is empty
    $avatar = $user['avata'] ?: $default_avatar;

    // Fetch the user's address details
    $address_sql = "SELECT * FROM detail_address WHERE user_id = ?";
    $address_stmt = $conn->prepare($address_sql);
    $address_stmt->bind_param("s", $user['id']);
    $address_stmt->execute();
    $address_result = $address_stmt->get_result();

    $addresses = [];
    while ($address = $address_result->fetch_assoc()) {
        $addresses[] = $address;
    }

    // Prepare the response data structure
    $response = [
        'ok' => true,
        'success' => true,
        'data' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'created_at' => $user['created_at'],
            'avata' => $avatar,
            'addresses' => $addresses 
        ]
    ];

    // Return the response as JSON
    echo json_encode($response);
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
