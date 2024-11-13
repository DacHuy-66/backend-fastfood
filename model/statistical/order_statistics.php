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

    // Lấy thống kê tổng quan với thêm đơn chờ và đơn hủy
    $overview_sql = "SELECT 
                        COUNT(*) as total_orders,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                        SUM(total_price) as total_revenue
                    FROM orders 
                    WHERE 1=1";
    
    // Lấy chi tiết đơn hàng
    $orders_sql = "SELECT 
                    o.id as order_id,
                    u.username as customer_name,
                    o.created_at as order_date,
                    SUM(po.quantity) as total_items,
                    o.total_price,
                    o.status
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN product_order po ON o.id = po.order_id
                WHERE 1=1";

    // Thêm điều kiện tìm kiếm nếu có
    if ($search) {
        $orders_sql .= " AND (o.id LIKE CONCAT('%', ?, '%') OR u.username LIKE CONCAT('%', ?, '%'))";
    }

    // Thêm điều kiện thời gian nếu có
    if ($start_date) {
        $overview_sql .= " AND DATE(orders.created_at) >= ?";
        $orders_sql .= " AND DATE(o.created_at) >= ?";
    }
    if ($end_date) {
        $overview_sql .= " AND DATE(orders.created_at) <= ?";
        $orders_sql .= " AND DATE(o.created_at) <= ?";
    }

    $orders_sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

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

    // Thực thi truy vấn chi tiết đơn hàng
    $orders_stmt = $conn->prepare($orders_sql);
    if ($start_date && $end_date) {
        if ($search) {
            $orders_stmt->bind_param("ssssii", $search, $search, $start_date, $end_date, $limit, $offset);
        } else {
            $orders_stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);
        }
    } else if ($start_date) {
        if ($search) {
            $orders_stmt->bind_param("sssii", $search, $search, $start_date, $limit, $offset);
        } else {
            $orders_stmt->bind_param("sii", $start_date, $limit, $offset);
        }
    } else if ($end_date) {
        if ($search) {
            $orders_stmt->bind_param("sssii", $search, $search, $end_date, $limit, $offset);
        } else {
            $orders_stmt->bind_param("sii", $end_date, $limit, $offset);
        }
    } else {
        if ($search) {
            $orders_stmt->bind_param("ssii", $search, $search, $limit, $offset);
        } else {
            $orders_stmt->bind_param("ii", $limit, $offset);
        }
    }
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();

    $orders = [];
    while($row = $orders_result->fetch_assoc()) {
        $status_text = '';
        switch($row['status']) {
            case 'completed':
                $status_text = 'Đã thanh toán';
                break;
            case 'cancelled':
                $status_text = 'Đã hủy';
                break;
            default:
                $status_text = $row['status'];
        }

        $orders[] = [
            'order_id' =>  str_pad($row['order_id'], 3, '0', STR_PAD_LEFT),
            'customer_name' => $row['customer_name'],
            'order_date' => date('Y-m-d', strtotime($row['order_date'])),
            'total_items' => (int)$row['total_items'],
            'total_price' => (float)$row['total_price'],
            'status' => $status_text
        ];
    }

    // Lấy tổng số đơn hàng để tính toán phân trang
    $total_sql = "SELECT COUNT(DISTINCT o.id) as total 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id 
                  WHERE 1=1";
    
    if ($search) {
        $total_sql .= " AND (o.id LIKE CONCAT('%', ?, '%') OR u.username LIKE CONCAT('%', ?, '%'))";
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
            $total_stmt->bind_param("ssss", $search, $search, $start_date, $end_date);
        } else {
            $total_stmt->bind_param("ss", $start_date, $end_date);
        }
    } else if ($start_date) {
        if ($search) {
            $total_stmt->bind_param("sss", $search, $search, $start_date);
        } else {
            $total_stmt->bind_param("s", $start_date);
        }
    } else if ($end_date) {
        if ($search) {
            $total_stmt->bind_param("sss", $search, $search, $end_date);
        } else {
            $total_stmt->bind_param("s", $end_date);
        }
    } else if ($search) {
        $total_stmt->bind_param("ss", $search, $search);
    }

    $total_stmt->execute();
    $total_result = $total_stmt->get_result()->fetch_assoc();
    $total_count = (int)$total_result['total'];

    // Kết hợp dữ liệu và thông tin phân trang
    $response = [
        'overview' => [
            'total_orders' => (int)$overview_result['total_orders'],
            'completed_orders' => (int)$overview_result['completed_orders'],
            'cancelled_orders' => (int)$overview_result['cancelled_orders'],
            'total_revenue' => (float)$overview_result['total_revenue']
        ],
        'orders' => $orders,
        'pagination' => [
            'total' => $total_count,
            'count' => count($orders),
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