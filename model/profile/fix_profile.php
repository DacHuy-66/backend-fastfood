<?php
// Database connection (update the credentials as needed)
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Retrieve API key from headers
$headers = apache_request_headers();
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Assuming $id_user is received from the client (e.g., POST or GET request)
$id_user = isset($id_user) ? $id_user : null;

// Check if both API key and user ID are provided
if (!$api_key || !$id_user) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key or user ID not provided.'
    ]);
    http_response_code(400);
    exit;  
}

// Validate API key and user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND api_key = ?");
$stmt->bind_param("is", $id_user, $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User is authenticated, proceed with updating data

    // Read PUT request data (assuming it's JSON)
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);  

    // Get the new data from the parsed input
    $new_username = isset($data['username']) ? $data['username'] : null;
    // $new_phone = isset($data['phone']) ? $data['phone'] : null;
    // $new_address = isset($data['address']) ? $data['address'] : null;
    $new_avata = isset($data['avata']) ? $data['avata'] : null;


    // Check if all required fields are present
    if ($new_username  && $new_avata) {
        // Prepare the update query
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, avata=? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_username, $new_avata, $id_user);

        if ($update_stmt->execute()) {
        echo json_encode([
        'ok' => True,
        'success' => True,
        'message' => 'User data updated successfully.'
        ]);
    http_response_code(response_code: 200);

        } else {
            echo "Failed to update user data: " . $update_stmt->error;
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid API key.'
    ]);
    http_response_code(response_code: 404);

        }
        $update_stmt->close();
    } else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Required fields are missing.'
    ]);
        http_response_code(response_code: 500);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Invalid API key or user ID.'
    ]);
    http_response_code(response_code: 404);
}

$stmt->close();
$conn->close();
?>
