<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';
include_once __DIR__ . '/dashboard_stats.php';

Headers();

try {
    // Tính tổng số đơn hàng thành công
    $orders_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE status = 'completed'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders_data = $orders_result->fetch_assoc();

    // Tính tổng doanh thu từ total_price của các đơn hàng đã hoàn thành
    $revenue_sql = "SELECT SUM(total_price) as total_revenue FROM orders WHERE status = 'completed'";
    $revenue_stmt = $conn->prepare($revenue_sql);
    $revenue_stmt->execute();
    $revenue_result = $revenue_stmt->get_result();
    $revenue_data = $revenue_result->fetch_assoc();

    // Khởi tạo DashboardStats
    $dashboardStats = new DashboardStats($conn);
    $newUsers = $dashboardStats->getNewUsers();
    $growth = $dashboardStats->getNewUsersGrowth();
    $formattedGrowth = number_format($growth, 1);

    // Tính tỷ lệ tăng trưởng đơn hàng
    $growth_sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as monthly_orders FROM orders WHERE status = 'completed' AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 2";
    $growth_stmt = $conn->prepare($growth_sql);
    $growth_stmt->execute();
    $growth_result = $growth_stmt->get_result();
    
    $monthly_orders = [];
    while($row = $growth_result->fetch_assoc()) {
        $monthly_orders[] = $row;
    }

    // Tính tỷ lệ tăng trưởng đơn hàng
    $order_growth_rate = 0;
    if (count($monthly_orders) == 2) {
        $current_month = $monthly_orders[0]['monthly_orders'];
        $previous_month = $monthly_orders[1]['monthly_orders'];
        if ($previous_month > 0) {
            $order_growth_rate = (($current_month - $previous_month) / $previous_month) * 100;
        }
    }

    // Tính tỷ lệ tăng trưởng doanh thu
    $revenue_growth_sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as monthly_revenue FROM orders WHERE status = 'completed' AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 2";
    $revenue_growth_stmt = $conn->prepare($revenue_growth_sql);
    $revenue_growth_stmt->execute();
    $revenue_growth_result = $revenue_growth_stmt->get_result();
    
    $monthly_revenues = [];
    while($row = $revenue_growth_result->fetch_assoc()) {
        $monthly_revenues[] = $row;
    }

    // Tính tỷ lệ tăng trưởng doanh thu
    $revenue_growth_rate = 0;
    if (count($monthly_revenues) == 2) {
        $current_month = $monthly_revenues[0]['monthly_revenue'];
        $previous_month = $monthly_revenues[1]['monthly_revenue'];
        if ($previous_month > 0) {
            $revenue_growth_rate = (($current_month - $previous_month) / $previous_month) * 100;
        }
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê tổng hợp thành công',
        'data' => [
            'total_orders' => (int)$orders_data['total_orders'],
            'total_revenue' => (float)$revenue_data['total_revenue'] ?? 0,
            'new_users' => $newUsers,
            'growth_rate_users' => $formattedGrowth,
            'growth_rate_orders' => round($order_growth_rate, 1),
            'growth_rate_revenue' => round($revenue_growth_rate, 1),
            'currency' => 'VNĐ'
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