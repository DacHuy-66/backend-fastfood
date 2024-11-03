<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Get website info
function getHeaderReview($conn) {
    $result = $conn->query("SELECT * FROM head_review   ");
    $socialMedia = [];
    while($row = $result->fetch_assoc()) {
        $socialMedia[] = $row;
    }
    return $socialMedia;
}

// Combine all header data
try {
    $response = [
        'websiteInfo' => getHeaderReview($conn),
        'ok' => true,

    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}