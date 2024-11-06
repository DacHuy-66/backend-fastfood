<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    // Khởi tạo tham số phân trang
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Khởi tạo tham số sắp xếp và tìm kiếm
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
    $sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

    // Chuẩn bị câu truy vấn SQL
    $sql = "SELECT id, username, email, password, `order`, mess, statistics, user, product, discount, layout, decentralization, note, time 
           FROM admin 
           WHERE (username LIKE ? OR email LIKE ?) 
           AND id != 'highest'
           ORDER BY $sort_by $sort_order 
           LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Lấy dữ liệu admin
    $admins_arr = [];
    while ($row = $result->fetch_assoc()) {
        // Định dạng dữ liệu thích hợp
        $row['password'] = (string)$row['password'];
        $row['order'] = (bool)$row['order'];
        $row['mess'] = (bool)$row['mess'];
        $row['statistics'] = (bool)$row['statistics'];
        $row['user'] = (bool)$row['user'];
        $row['product'] = (bool)$row['product'];
        $row['discount'] = (bool)$row['discount'];
        $row['layout'] = (bool)$row['layout'];
        $row['decentralization'] = (bool)$row['decentralization'];
        $admins_arr[] = $row;
    }

    // Lấy tổng số lượng admin cho phân trang
    $count_sql = "SELECT COUNT(id) AS total 
                  FROM admin 
                  WHERE (username LIKE ? OR email LIKE ?) 
                  AND id != 'highest'";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $search_param, $search_param);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total_admins = $total_result['total'];
    
    // Đóng các câu truy vấn
    $stmt->close();
    $count_stmt->close();
    $conn->close();

    // Chuẩn bị phản hồi
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Admins đã truy xuất thành công',
        'code' => 200,
        'data' => [
            'admins' => $admins_arr,
            'pagination' => [
                'total' => (int)$total_admins,
                'count' => count($admins_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_admins / $limit)
            ],
            'filters' => [
                'search' => $search,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ]
        ]
    ];
    http_response_code(200);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'status' => 'error',
        'code' => $e->getCode() ?: 400,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
