<?php
include_once __DIR__ . '/../../config/db.php';
// lấy thông tin website
function getWebsiteInfo($conn) {
    $result = $conn->query("SELECT * FROM website_info LIMIT 1");
    return $result->fetch_assoc();
}

// kết hợp tất cả dữ liệu header
try {
    $response = [
        'websiteInfo' => getWebsiteInfo($conn),
        'ok' => true
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}