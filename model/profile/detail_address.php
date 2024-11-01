<?php
// detail_address.php

// Include database connection configuration
header("Access-Control-Allow-Origin: *");  // Or specify your frontend domain
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include_once __DIR__ . '/../../config/db.php';

// Check if 'id' parameter is provided in the GET request
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

// If no 'id' is provided, return an error response
if (!$user_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'User ID is required.'
    ]);
    http_response_code(400);
    exit;
}

// Query to retrieve data for the specified user_id
$sql = "SELECT * FROM detail_address WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();

// Create an array to store the retrieved address data
$addresses = array();

// Loop through the results and add each row to the addresses array
while ($row = $address_result->fetch_assoc()) {
    $addresses[] = $row;
}

// Return the address data as a JSON response
echo json_encode([
    'ok' => true,
    'success' => true,
    'addresses' => $addresses
]);

// Close the connection
$conn->close();
?>
