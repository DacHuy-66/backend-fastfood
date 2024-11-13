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

    // Tính tổng doanh thu trong khoảng thời gian hiện tại
    $revenue_sql = "SELECT SUM(total_price) as total_revenue
                FROM orders 
                WHERE status = 'completed'
                AND DATE(created_at) BETWEEN ? AND ?";
    
    $revenue_stmt = $conn->prepare($revenue_sql);
    $revenue_stmt->bind_param("ss", $start_date, $end_date);
    $revenue_stmt->execute();
    $revenue_result = $revenue_stmt->get_result();
    $revenue_data = $revenue_result->fetch_assoc();
    $current_revenue = (float)$revenue_data['total_revenue'] ?? 0;

    // Tính tổng doanh thu trong khoảng thời gian trước đó
    $previous_stmt = $conn->prepare($revenue_sql);
    $previous_stmt->bind_param("ss", $previous_start, $previous_end);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result();
    $previous_data = $previous_result->fetch_assoc();
    $previous_revenue = (float)$previous_data['total_revenue'] ?? 0;

    // Tính phần trăm tăng/giảm
    $growth_rate = 0;
    if ($previous_revenue > 0) {
        $growth_rate = (($current_revenue - $previous_revenue) / $previous_revenue) * 100;
    }

    // Lấy doanh thu theo từng ngày trong khoảng thời gian
    $daily_revenue_sql = "SELECT 
                            DATE(created_at) as date,
                            SUM(total_price) as daily_revenue
                        FROM orders
                        WHERE status = 'completed'
                        AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";

    $daily_stmt = $conn->prepare($daily_revenue_sql);
    $daily_stmt->bind_param("ss", $start_date, $end_date);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $daily_revenues = [];
    while($row = $daily_result->fetch_assoc()) {
        $daily_revenues[] = [
            'date' => $row['date'],
            'revenue' => (float)$row['daily_revenue']
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê doanh thu thành công',
        'data' => [
            'total_revenue' => $current_revenue,
            'growth_rate' => round($growth_rate, 1), // Làm tròn đến 1 chữ số thập phân
            // 'daily_revenues' => $daily_revenues,
            // 'currency' => 'VNĐ'
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
