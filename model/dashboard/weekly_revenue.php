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
    
    // Initialize an array for days of the week
    $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    // Initialize revenue data for each day of the week
    foreach ($days_of_week as $day) {
        $data[] = [
            'name' => $day,
            'revenue' => 0 // Default revenue to 0
        ];
    }

    while($row = $daily_result->fetch_assoc()) {
        $day_index = $row['day_of_week'] - 1; // Adjust for 0-based index
        $data[$day_index]['revenue'] = (float)$row['revenue']; // Update revenue for the corresponding day
    }

    // // Tính tỷ lệ tăng trưởng so với ngày trước
    // $growth_rate = 0;
    // if (count($data) >= 2) {
    //     $current_day = end($data)['revenue'];
    //     $previous = prev($data);
    //     $previous_day = $previous['revenue'];
    //     if ($previous_day > 0) {
    //         $growth_rate = (($current_day - $previous_day) / $previous_day) * 100;
    //     }
    // }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê doanh thu theo thứ trong tuần thành công',
        'data' => $data,
        // 'growth_rate' => round($growth_rate, 1),
        // 'period' => '7 ngày qua',
        // 'comparison' => 'so với ngày trước',
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