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

    // Tính tổng số người dùng mới trong khoảng thời gian hiện tại
    $users_sql = "SELECT COUNT(*) as total_users 
                  FROM users 
                  WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $users_stmt = $conn->prepare($users_sql);
    $users_stmt->bind_param("ss", $start_date, $end_date);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    $users_data = $users_result->fetch_assoc();
    $current_users = (int)$users_data['total_users'];

    // Tính tổng số người dùng mới trong khoảng thời gian trước đó
    $previous_stmt = $conn->prepare($users_sql);
    $previous_stmt->bind_param("ss", $previous_start, $previous_end);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result();
    $previous_data = $previous_result->fetch_assoc();
    $previous_users = (int)$previous_data['total_users'];

    // Tính phần trăm tăng/giảm
    $growth_rate = 0;
    if ($previous_users > 0) {
        $growth_rate = (($current_users - $previous_users) / $previous_users) * 100;
    }

    // Lấy số người dùng mới theo từng ngày
    $daily_users_sql = "SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as daily_users
                        FROM users
                        WHERE DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";

    $daily_stmt = $conn->prepare($daily_users_sql);
    $daily_stmt->bind_param("ss", $start_date, $end_date);
    $daily_stmt->execute();
    $daily_result = $daily_stmt->get_result();
    
    $daily_users = [];
    while($row = $daily_result->fetch_assoc()) {
        $daily_users[] = [
            'date' => $row['date'],
            'users' => (int)$row['daily_users']
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy thống kê người dùng mới thành công',
        'data' => [
            'total_new_users' => $current_users,
            'growth_rate' => round($growth_rate, 1),
            // 'daily_users' => $daily_users
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
