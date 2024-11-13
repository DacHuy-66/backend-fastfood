<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy tham số thời gian từ request
    $today = date('Y-m-d');
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today;

    // Thêm tham số phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    // Thêm tham số tìm kiếm
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Xây dựng câu query thống kê tổng quan
    $overview_sql = "SELECT 
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(o.total_price) as total_revenue
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'";

    // Xây dựng câu query chi tiết người dùng
    $statistics_sql = "SELECT 
                    u.username,
                    u.email,
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total_price), 0) as total_spent
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
                WHERE 1=1";

    // Thêm điều kiện tìm kiếm nếu có
    if ($search) {
        $statistics_sql .= " AND u.username LIKE CONCAT('%', ?, '%')";
    }

    // Thêm điều kiện thời gian nếu có
    if ($start_date) {
        $overview_sql .= " AND DATE(o.created_at) >= ?";
        $statistics_sql .= " AND DATE(o.created_at) >= ?";
    }
    if ($end_date) {
        $overview_sql .= " AND DATE(o.created_at) <= ?";
        $statistics_sql .= " AND DATE(o.created_at) <= ?";
    }

    $statistics_sql .= " GROUP BY u.id ORDER BY total_spent DESC LIMIT ? OFFSET ?";

    // Thực thi truy vấn tổng quan
    $overview_stmt = $conn->prepare($overview_sql);
    if ($start_date && $end_date) {
        $overview_stmt->bind_param("ss", $start_date, $end_date);
    } else if ($start_date) {
        $overview_stmt->bind_param("s", $start_date);
    } else if ($end_date) {
        $overview_stmt->bind_param("s", $end_date);
    }
    $overview_stmt->execute();
    $overview_result = $overview_stmt->get_result()->fetch_assoc();

    // Thực thi truy vấn chi tiết người dùng
    $statistics_stmt = $conn->prepare($statistics_sql);
    if ($start_date && $end_date) {
        if ($search) {
            $statistics_stmt->bind_param("sssii", $search, $start_date, $end_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);
        }
    } else if ($start_date) {
        if ($search) {
            $statistics_stmt->bind_param("ssii", $search, $start_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("sii", $start_date, $limit, $offset);
        }
    } else if ($end_date) {
        if ($search) {
            $statistics_stmt->bind_param("ssii", $search, $end_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("sii", $end_date, $limit, $offset);
        }
    } else {
        if ($search) {
            $statistics_stmt->bind_param("sii", $search, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("ii", $limit, $offset);
        }
    }
    $statistics_stmt->execute();
    $statistics_result = $statistics_stmt->get_result();

    $users = [];
    while($row = $statistics_result->fetch_assoc()) {
        $users[] = [
            'username' => $row['username'],
            'email' => $row['email'],
            'total_orders' => (int)$row['total_orders'],
            'total_spent' => (float)$row['total_spent']
        ];
    }

    // Thêm query để đếm tổng số user thỏa mãn điều kiện
    $total_sql = "SELECT COUNT(DISTINCT u.id) as total 
                  FROM users u
                  LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
                  WHERE 1=1";

    if ($search) {
        $total_sql .= " AND u.username LIKE CONCAT('%', ?, '%')";
    }

    if ($start_date) {
        $total_sql .= " AND DATE(o.created_at) >= ?";
    }
    if ($end_date) {
        $total_sql .= " AND DATE(o.created_at) <= ?";
    }

    $total_stmt = $conn->prepare($total_sql);

    if ($start_date && $end_date) {
        if ($search) {
            $total_stmt->bind_param("sss", $search, $start_date, $end_date);
        } else {
            $total_stmt->bind_param("ss", $start_date, $end_date);
        }
    } else if ($start_date) {
        if ($search) {
            $total_stmt->bind_param("ss", $search, $start_date);
        } else {
            $total_stmt->bind_param("s", $start_date);
        }
    } else if ($end_date) {
        if ($search) {
            $total_stmt->bind_param("ss", $search, $end_date);
        } else {
            $total_stmt->bind_param("s", $end_date);
        }
    } else if ($search) {
        $total_stmt->bind_param("s", $search);
    }

    $total_stmt->execute();
    $total_result = $total_stmt->get_result()->fetch_assoc();
    $total_count = (int)$total_result['total'];

    // Sửa lại phần response để sử dụng total_count
    $response = [

        'users' => $users,
        'pagination' => [
            'total' => $total_count,
            'count' => count($users),
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => ceil($total_count / $limit)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
