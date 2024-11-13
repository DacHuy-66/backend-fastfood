<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy doanh thu theo thứ trong tuần
    $daily_sql = "SELECT 
                    DAYOFWEEK(o.created_at) as day_of_week,
                    SUM(po.price * po.quantity) as revenue
                FROM orders o
                JOIN product_order po ON o.id = po.order_id
                WHERE o.status = 'completed'
                AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DAYOFWEEK(o.created_at)
                ORDER BY day_of_week ASC";

    $daily_stmt = $conn->prepare($daily_sql);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $data = [];
    
    // Initialize an array for days of the week (Vietnamese format)
    $days_of_week = [
        'Thứ 2', 
        'Thứ 3', 
        'Thứ 4', 
        'Thứ 5', 
        'Thứ 6', 
        'Thứ 7', 
        'Chủ nhật'
    ];
    
    // Initialize revenue data for each day of the week
    foreach ($days_of_week as $day) {
        $data[] = [
            'name' => $day,
            'revenue' => 0 // Default revenue to 0
        ];
    }

    // MySQL DAYOFWEEK() returns 1 for Sunday, 2 for Monday, etc.
    // We need to adjust the index to match Vietnamese format
    while($row = $daily_result->fetch_assoc()) {
        $day_index = $row['day_of_week'] == 1 ? 6 : $row['day_of_week'] - 2; // Convert to 0-based index for Vietnamese week
        if ($day_index >= 0 && $day_index < 7) {
            $data[$day_index]['revenue'] = (float)$row['revenue'];
        }
    }

    // Tính tỷ lệ tăng trưởng so với tuần trước
    $current_week_sql = "SELECT SUM(po.price * po.quantity) as revenue
                        FROM orders o
                        JOIN product_order po ON o.id = po.order_id
                        WHERE o.status = 'completed'
                        AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";

    $previous_week_sql = "SELECT SUM(po.price * po.quantity) as revenue
                         FROM orders o
                         JOIN product_order po ON o.id = po.order_id
                         WHERE o.status = 'completed'
                         AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
                         AND o.created_at < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";

    $current_stmt = $conn->prepare($current_week_sql);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result()->fetch_assoc();
    $current_revenue = (float)$current_result['revenue'];

    $previous_stmt = $conn->prepare($previous_week_sql);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result()->fetch_assoc();
    $previous_revenue = (float)$previous_result['revenue'];

    $percentage_change = 0;
    if ($previous_revenue > 0) {
        $percentage_change = (($current_revenue - $previous_revenue) / $previous_revenue) * 100;
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê doanh thu theo thứ trong tuần thành công',
        'data' => $data,
        'percentage_change' => round($percentage_change, 1),
        'currency' => 'VNĐ'
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