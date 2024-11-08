<?php

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
    $sql = "SELECT id, name, user_id, email, code, description, minimum_price, type, 
            discount_percent, valid_from, valid_to, status 
            FROM discount_user 
            WHERE (name LIKE ? OR email LIKE ? OR code LIKE ?) 
            ORDER BY $sort_by $sort_order 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    $search_param = "%" . $search . "%";
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Lấy dữ liệu discount_user
    $discounts_arr = [];
    while ($row = $result->fetch_assoc()) {
        // Định dạng dữ liệu thích hợp
        $row['id'] = (int)$row['id'];
        $row['minimum_price'] = (float)$row['minimum_price'];
        $row['status'] = (bool)$row['status'];

        // Tính trạng thái và message
        $current_date = new DateTime();
        $valid_from = new DateTime($row['valid_from']);
        $valid_to = new DateTime($row['valid_to']);
        
        if ((int)$row['status'] === 0) {
            $message = 'Tạm dừng';
        } else {
            if ($current_date < $valid_from) {
                $message = 'Chờ bắt đầu';
            } elseif ($current_date > $valid_to) {
                $message = 'Hết hạn';
            } else {
                $message = 'Đang hoạt động';
            }
        }

        $row['message'] = $message;
        $row['days_remaining'] = $message === 'Đang hoạt động' ? $current_date->diff($valid_to)->days : 0;
        
        $discounts_arr[] = $row;
    }

    // Lấy tổng số lượng discount cho phân trang
    $count_sql = "SELECT COUNT(id) AS total 
                  FROM discount_user 
                  WHERE (name LIKE ? OR email LIKE ? OR code LIKE ?)";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total_discounts = $total_result['total'];
    
    // Đóng các câu truy vấn
    $stmt->close();
    $count_stmt->close();
    $conn->close();

    // Chuẩn bị phản hồi
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Danh sách mã giảm giá đã truy xuất thành công',
        'code' => 200,
        'data' => [
            'discounts' => $discounts_arr,
            'pagination' => [
                'total' => (int)$total_discounts,
                'count' => count($discounts_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_discounts / $limit)
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
    http_response_code(400);
}

echo json_encode($response);
