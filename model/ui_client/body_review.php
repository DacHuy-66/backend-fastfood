<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

    // lấy thông tin website
function getBodyReview($conn) {
    $result = $conn->query("SELECT * FROM body_review");
    $socialMedia = [];
    while($row = $result->fetch_assoc()) {
        $socialMedia[] = $row;
    }
    return $socialMedia;
}

try {
    $response = [
        'websiteInfo' => getBodyReview($conn),
        'ok' => true,

    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}