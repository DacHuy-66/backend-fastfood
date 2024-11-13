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
                    COUNT(DISTINCT p.id) as total_products,
                    SUM(po.quantity) as total_items_sold,
                    SUM(po.price * po.quantity) as total_revenue
                FROM products p
                LEFT JOIN product_order po ON p.id = po.product_id
                LEFT JOIN orders o ON po.order_id = o.id
                WHERE o.status = 'completed'";

    // Xây dựng câu query chi tiết sản phẩm
    $statistics_sql = "SELECT 
                    p.id,
                    p.name,
                    p.price as current_price,
                    COALESCE(SUM(po.quantity), 0) as total_sold,
                    COALESCE(SUM(po.price * po.quantity), 0) as total_revenue
                FROM products p
                LEFT JOIN product_order po ON p.id = po.product_id
                LEFT JOIN orders o ON po.order_id = o.id AND o.status = 'completed'
                WHERE 1=1";
    
    // Thêm điều kiện tìm kiếm nếu có
    if ($search) {
        $statistics_sql .= " AND (p.id LIKE CONCAT('%', ?, '%') OR p.name LIKE CONCAT('%', ?, '%'))";
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
    
    $statistics_sql .= " GROUP BY p.id ORDER BY total_revenue DESC LIMIT ? OFFSET ?";

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

    // Thực thi truy vấn chi tiết sản phẩm
    $statistics_stmt = $conn->prepare($statistics_sql);
    if ($start_date && $end_date) {
        if ($search) {
            $statistics_stmt->bind_param("ssssii", $search, $search, $start_date, $end_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);
        }
    } else if ($start_date) {
        if ($search) {
            $statistics_stmt->bind_param("sssii", $search, $search, $start_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("sii", $start_date, $limit, $offset);
        }
    } else if ($end_date) {
        if ($search) {
            $statistics_stmt->bind_param("sssii", $search, $search, $end_date, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("sii", $end_date, $limit, $offset);
        }
    } else {
        if ($search) {
            $statistics_stmt->bind_param("ssii", $search, $search, $limit, $offset);
        } else {
            $statistics_stmt->bind_param("ii", $limit, $offset);
        }
    }
    $statistics_stmt->execute();
    $statistics_result = $statistics_stmt->get_result();
    
    $products = [];
    while($row = $statistics_result->fetch_assoc()) {
        $products[] = [
            // 'product_id' => str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'name' => $row['name'],
            // 'current_price' => (float)$row['current_price'],
            'total_sold' => (int)$row['total_sold'],
            'total_revenue' => (float)$row['total_revenue']
        ];
    }

    // Sử dụng total_products từ overview để tính phân trang
    $response = [
        'overview' => [
            'total_products' => (int)$overview_result['total_products'],
            'total_items_sold' => (int)$overview_result['total_items_sold'],
            'total_revenue' => (float)$overview_result['total_revenue']
        ],
        'products' => $products,
        'pagination' => [
            'total' => (int)$overview_result['total_products'],
            'count' => count($products),
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => ceil((int)$overview_result['total_products'] / $limit)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>