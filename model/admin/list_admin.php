<?php
// File: ./model/admin/list_admin.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    // Initialize pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Initialize sorting and search parameters
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
    $sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

    // Prepare the SQL query
    $sql = "SELECT id, username, email, role_1, role_2, role_3, role_4, note, time FROM admin WHERE username LIKE ? ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    
    $search_param = "%" . $search . "%";
    $stmt->bind_param("sii", $search_param, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch admins data
    $admins_arr = [];
    while ($row = $result->fetch_assoc()) {
        // Format data appropriately
        $row['role_1'] = (bool)$row['role_1'];
        $row['role_2'] = (bool)$row['role_2'];
        $row['role_3'] = (bool)$row['role_3'];
        $row['role_4'] = (bool)$row['role_4'];
        $admins_arr[] = $row;
    }

    // Get total number of admins for pagination
    $count_sql = "SELECT COUNT(id) AS total FROM admin WHERE username LIKE ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $search_param);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total_admins = $total_result['total'];
    
    // Close statements
    $stmt->close();
    $count_stmt->close();
    $conn->close();

    // Prepare response
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Admins retrieved successfully',
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
