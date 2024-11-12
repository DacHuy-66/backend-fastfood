<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy số đơn hàng theo thứ trong tuần
    $daily_sql = "SELECT 
                    DAYOFWEEK(created_at) as day_of_week,
                    COUNT(*) as orders
                FROM orders 
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DAYOFWEEK(created_at)
                ORDER BY day_of_week ASC";

    $daily_stmt = $conn->prepare($daily_sql);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $data = [];
    
    while($row = $daily_result->fetch_assoc()) {
        $data[] = [
            'name' => date('l', strtotime("Sunday +{$row['day_of_week']} days")), // Convert to day name
            'orders' => (int)$row['orders']
        ];
    }

    // Tính tỷ lệ tăng trưởng so với ngày trước
    // $growth_rate = 0;
    // if (count($data) >= 2) {
    //     $current_day = end($data)['orders'];
    //     $previous = prev($data);
    //     $previous_day = $previous['orders'];
    //     if ($previous_day > 0) {
    //         $growth_rate = (($current_day - $previous_day) / $previous_day) * 100;
    //     }
    // }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê đơn hàng theo ngày thành công',
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