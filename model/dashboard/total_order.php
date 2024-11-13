<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy tham số thời gian từ request
    $today = date('Y-m-d');
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today;

    // Tính khoảng thời gian trước đó có cùng độ dài để so sánh
    $date_diff = strtotime($end_date) - strtotime($start_date);
    $previous_end = date('Y-m-d', strtotime($start_date) - 1);
    $previous_start = date('Y-m-d', strtotime($previous_end) - $date_diff);

    // Tính tổng số đơn hàng trong khoảng thời gian hiện tại
    $orders_sql = "SELECT COUNT(*) as total_orders 
                   FROM orders 
                   WHERE status = 'completed' 
                   AND DATE(created_at) BETWEEN ? AND ?";
    
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("ss", $start_date, $end_date);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    $orders_data = $orders_result->fetch_assoc();
    $current_orders = (int)$orders_data['total_orders'];

    // Tính tổng số đơn hàng trong khoảng thời gian trước đó
    $previous_stmt = $conn->prepare($orders_sql);
    $previous_stmt->bind_param("ss", $previous_start, $previous_end);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result();
    $previous_data = $previous_result->fetch_assoc();
    $previous_orders = (int)$previous_data['total_orders'];

    // Tính phần trăm tăng/giảm
    $growth_rate = 0;
    if ($previous_orders > 0) {
        $growth_rate = (($current_orders - $previous_orders) / $previous_orders) * 100;
    }

    // Lấy số đơn hàng theo từng ngày
    $daily_orders_sql = "SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as daily_orders
                        FROM orders
                        WHERE status = 'completed'
                        AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";

    $daily_stmt = $conn->prepare($daily_orders_sql);
    $daily_stmt->bind_param("ss", $start_date, $end_date);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $daily_orders = [];
    while($row = $daily_result->fetch_assoc()) {
        $daily_orders[] = [
            'date' => $row['date'],
            'orders' => (int)$row['daily_orders']
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê đơn hàng thành công',
        'data' => [
            'total_orders' => $current_orders,
            'growth_rate' => round($growth_rate, 1),
            // 'daily_orders' => $daily_orders
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
