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
    
    // Khởi tạo mảng các ngày trong tuần theo tiếng Việt
    $days_of_week = [
        'Thứ 2', 
        'Thứ 3', 
        'Thứ 4', 
        'Thứ 5', 
        'Thứ 6', 
        'Thứ 7', 
        'Chủ nhật'
    ];
    
    // Khởi tạo dữ liệu mặc định cho mỗi ngày
    $data = [];
    foreach ($days_of_week as $day) {
        $data[] = [
            'name' => $day,
            'orders' => 0 // Giá trị mặc định là 0
        ];
    }

    // MySQL DAYOFWEEK() trả về: 1 = Chủ nhật, 2 = Thứ 2, ..., 7 = Thứ 7
    while($row = $daily_result->fetch_assoc()) {
        $day_index = $row['day_of_week'] == 1 ? 6 : $row['day_of_week'] - 2; // Chuyển đổi index cho tuần Việt Nam
        if ($day_index >= 0 && $day_index < 7) {
            $data[$day_index]['orders'] = (int)$row['orders'];
        }
    }

    // Tính tổng đơn hàng tuần này
    $current_week_sql = "SELECT COUNT(*) as total
                        FROM orders 
                        WHERE status = 'completed'
                        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";

    // Tính tổng đơn hàng tuần trước
    $previous_week_sql = "SELECT COUNT(*) as total
                         FROM orders 
                         WHERE status = 'completed'
                         AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
                         AND created_at < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";

    $current_stmt = $conn->prepare($current_week_sql);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result()->fetch_assoc();
    $current_orders = (int)$current_result['total'];

    $previous_stmt = $conn->prepare($previous_week_sql);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result()->fetch_assoc();
    $previous_orders = (int)$previous_result['total'];

    // Tính phần trăm tăng/giảm
    $percentage_change = 0;
    if ($previous_orders > 0) {
        $percentage_change = (($current_orders - $previous_orders) / $previous_orders) * 100;
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê đơn hàng theo ngày thành công',
        'data' => $data,
        'percentage_change' => round($percentage_change, 1),
        'total_orders' => $current_orders
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