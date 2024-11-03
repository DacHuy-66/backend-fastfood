<?php
include_once __DIR__ . '/../../config/db.php';

// Get navigation menu items
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

// Get complete navbar data
try {
    $response = [
        'menu' => getNavMenu($conn),
        'isFixed' => true, // Có thể thêm vào settings nếu cần
        'className' => 'main-navbar', // Có thể thêm vào settings nếu cần,
        'ok' => true
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}