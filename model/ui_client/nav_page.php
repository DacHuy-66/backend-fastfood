<?php
include_once __DIR__ . '/../../config/db.php';

// lấy menu điều hướng
function getNavMenu($conn) {
    $result = $conn->query("SELECT * FROM nav_menu WHERE is_active = 1 ORDER BY order_number ASC");
    $menu = [];
    while($row = $result->fetch_assoc()) {
        $menu[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'url' => $row['url'],
            'image' => $row['image'],
            'isHighlight' => (bool)$row['highlight'],
            'className' => $row['class_name']
        ];
    }
    return $menu;
}

// kết hợp tất cả dữ liệu
try {
    $response = [
        'menu' => getNavMenu($conn),
        'isFixed' => true, 
        'className' => 'main-navbar', 
        'ok' => true
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}