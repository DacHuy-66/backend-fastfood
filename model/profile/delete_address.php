<?php
// Database connection
include_once __DIR__ . '/../../config/db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Get address ID from input and validate it
$address_id = $_GET['id'] ?? null;

if (!$address_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Address ID is required.'
    ]);
    http_response_code(400);
    exit;
}

// Check if the address exists
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

// Delete the address
$delete_stmt = $conn->prepare("DELETE FROM detail_address WHERE id = ?");
$delete_stmt->bind_param("i", $address_id);

if ($delete_stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Address deleted successfully.'
    ]);
    http_response_code(200);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Failed to delete address: ' . $delete_stmt->error
    ]);
    http_response_code(500);
}

$delete_stmt->close();
$conn->close();
?>
