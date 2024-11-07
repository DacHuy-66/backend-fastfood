<?php
include_once __DIR__ . '/../../config/db.php';

try {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';

    if ($page <= 0 || $limit <= 0) {
        throw new Exception('Trang và số lượng phải là số nguyên dương', 400);
    }

    $offset = ($page - 1) * $limit;

    $query = "SELECT id, username, email, password, role, avata, created_at 
              FROM users 
              WHERE 1=1 ";

    if ($search) {
        $query .= "AND (username LIKE ? OR email LIKE ?) ";
    }

    if ($role !== '') {
        $query .= "AND role = ? ";
        $role = intval($role);
    }

    $query .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);

    if ($search && $role !== '') {
        $search_param = "%$search%";
        $stmt->bind_param("ssiii", $search_param, $search_param, $role, $limit, $offset);
    } elseif ($search) {
        $search_param = "%$search%";
        $stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
    } elseif ($role !== '') {
        $stmt->bind_param("iii", $role, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users_arr = [];

    while ($row = $result->fetch_assoc()) {
        $row['role'] = $row['role'] == 1;
        $users_arr[] = $row;
    }

    $count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    if ($search) {
        $count_query .= " AND (username LIKE ? OR email LIKE ?)";
    }
    if ($role !== '') {
        $count_query .= " AND role = ?";
    }

    $count_stmt = $conn->prepare($count_query);
    
    if ($search && $role !== '') {
        $search_param = "%$search%";
        $count_stmt->bind_param("sss", $search_param, $search_param, $role);
    } elseif ($search) {
        $search_param = "%$search%";
        $count_stmt->bind_param("ss", $search_param, $search_param);
    } elseif ($role !== '') {
        $count_stmt->bind_param("s", $role);
    }

    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['total'];

    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Lấy danh sách người dùng thành công',
        'code' => 200,
        'data' => [
            'users' => $users_arr,
            'pagination' => [
                'total' => $total_users,
                'count' => count($users_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_users / $limit)
            ]
        ]
    ];
    http_response_code(200);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'status' => 'failed',
        'code' => $e->getCode() ?: 400,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
