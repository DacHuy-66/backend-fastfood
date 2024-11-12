<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Tính tổng số đơn hàng thành công
    $orders_sql = "SELECT COUNT(*) as total_orders FROM orders WHERE status = 'completed'";
    
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders_data = $orders_result->fetch_assoc();

    // Tính tỷ lệ tăng trưởng so với tháng trước
    $growth_sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as monthly_orders
                FROM orders
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 2";

    $growth_stmt = $conn->prepare($growth_sql);
    $growth_stmt->execute();
    $growth_result = $growth_stmt->get_result();
    
    $monthly_orders = [];
    while($row = $growth_result->fetch_assoc()) {
        $monthly_orders[] = $row;
    }

    // Tính tỷ lệ tăng trưởng
    $growth_rate = 0;
    if (count($monthly_orders) == 2) {
        $current_month = $monthly_orders[0]['monthly_orders'];
        $previous_month = $monthly_orders[1]['monthly_orders'];
        if ($previous_month > 0) {
            $growth_rate = (($current_month - $previous_month) / $previous_month) * 100;
        }
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê đơn hàng thành công',
        'data' => [
            'total_orders' => (int)$orders_data['total_orders'],
            'growth_rate' => round($growth_rate, 1)
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
