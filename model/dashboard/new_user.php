<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';
include_once __DIR__ . '/dashboard_stats.php';

Headers();

try {
    // Khởi tạo DashboardStats
    $dashboardStats = new DashboardStats($conn);

    // Lấy thống kê
    $newUsers = $dashboardStats->getNewUsers();
    $growth = $dashboardStats->getNewUsersGrowth();

    // Format số liệu
    $formattedGrowth = number_format($growth, 1);
    
    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê người dùng mới thành công',
        'data' => [
            'new_users' => $newUsers,
            'growth_rate' => $formattedGrowth,
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
    
    http_response_code(500);
    echo json_encode($response);
}
