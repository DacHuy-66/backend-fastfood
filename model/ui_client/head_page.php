<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Get website info
function getWebsiteInfo($conn) {
    $result = $conn->query("SELECT * FROM website_info LIMIT 1");
    return $result->fetch_assoc();
}

// Get main menu
// function getMainMenu($conn) {
//     $result = $conn->query("SELECT * FROM main_menu WHERE status = 1 ORDER BY order_number ASC");
//     $menu = [];
//     while($row = $result->fetch_assoc()) {
//         $menu[] = $row;
//     }
//     return $menu;
// }

// Get action buttons

// Combine all header data
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
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}