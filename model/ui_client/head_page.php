<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// lấy thông tin website
function getWebsiteInfo($conn) {
    $result = $conn->query("SELECT * FROM website_info LIMIT 1");
    return $result->fetch_assoc();
}

// kết hợp tất cả dữ liệu header
try {
    $response = [
        'websiteInfo' => getWebsiteInfo($conn),
        'ok' => true,
        // 'mainMenu' => getMainMenu($conn),
        'delivery' => [
            'title' => 'Đặt Đồ ăn, giao hàng từ chỉ 30 phút',
            'openingHours' => 'Thời gian mở cửa 6:30 A.M - 12:00 P.M'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}