<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Tính tổng doanh thu từ total_price của các đơn hàng đã hoàn thành
    $revenue_sql = "SELECT SUM(total_price) as total_revenue
                FROM orders 
                WHERE status = 'completed'";
    
    $revenue_stmt = $conn->prepare($revenue_sql);
    $revenue_stmt->execute();
    $revenue_result = $revenue_stmt->get_result();
    $revenue_data = $revenue_result->fetch_assoc();

    // Tính tỷ lệ tăng trưởng so với tháng trước
    $growth_sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(total_price) as monthly_revenue
                FROM orders
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 2";

    $growth_stmt = $conn->prepare($growth_sql);
    $growth_stmt->execute();
    $growth_result = $growth_stmt->get_result();
    
    $monthly_revenues = [];
    while($row = $growth_result->fetch_assoc()) {
        $monthly_revenues[] = $row;
    }

    // Tính tỷ lệ tăng trưởng
    $growth_rate = 0;
    if (count($monthly_revenues) == 2) {
        $current_month = $monthly_revenues[0]['monthly_revenue'];
        $previous_month = $monthly_revenues[1]['monthly_revenue'];
        if ($previous_month > 0) {
            $growth_rate = (($current_month - $previous_month) / $previous_month) * 100;
        }
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê doanh thu thành công',
        'data' => [
            'total_revenue' => (float)$revenue_data['total_revenue'] ?? 0,
            'growth_rate' => round($growth_rate, 1),
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
