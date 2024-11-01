<?php
// Include database connection configuration
header("Access-Control-Allow-Origin: *");  // Or specify your frontend domain
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include_once __DIR__ . '/../../config/db.php';

// Query to retrieve all data from the detail_address table
$sql = "SELECT * FROM detail_address";
$stmt = $conn->prepare($sql);
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
