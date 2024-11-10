<?php

include_once __DIR__ . '/../../config/db.php';

try {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($page <= 0 || $limit <= 0) {
        throw new Exception('Trang và số lượng phải là số nguyên dương', 400);
    }

    $offset = ($page - 1) * $limit;

    $query = "SELECT o.id, o.created_at, o.status, u.username, 
              o.quantity, o.total_price
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              WHERE 1=1 
              AND o.status IN ('Cancel', 'Completed') ";

    if ($search) {
        $query .= "AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    }

    if ($user_id) {
        $query .= "AND o.user_id = ? ";
    }

    if ($status) {
        $query .= "AND o.status = ? ";
    }

    $query .= "ORDER BY o.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);

    if ($search) {
        $search_param = "%$search%";
        if ($user_id && $status) {
            $stmt->bind_param("sssssii", $search_param, $search_param, $search_param, $user_id, $status, $limit, $offset);
        } elseif ($user_id) {
            $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $user_id, $limit, $offset);
        } elseif ($status) {
            $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $status, $limit, $offset);
        } else {
            $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
        }
    } else {
        if ($user_id && $status) {
            $stmt->bind_param("ssii", $user_id, $status, $limit, $offset);
        } elseif ($user_id) {
            $stmt->bind_param("sii", $user_id, $limit, $offset);
        } elseif ($status) {
            $stmt->bind_param("sii", $status, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $orders_arr = [];

    while ($row = $result->fetch_assoc()) {
        $orders_arr[] = [
            'id' => $row['id'],
            'username' => $row['username'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'total_price' => $row['total_price']
        ];
    }

    $count_query = "SELECT COUNT(*) as total FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE 1=1
                    AND o.status IN ('Cancel', 'Completed')";
    if ($search) {
        $count_query .= " AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    }
    if ($user_id) {
        $count_query .= " AND o.user_id = ?";
    }
    if ($status) {
        $count_query .= " AND o.status = ?";
    }

    $count_stmt = $conn->prepare($count_query);
    if ($search) {
        $search_param = "%$search%";
        if ($user_id && $status) {
            $count_stmt->bind_param("sssss", $search_param, $search_param, $search_param, $user_id, $status);
        } elseif ($user_id) {
            $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $user_id);
        } elseif ($status) {
            $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $status);
        } else {
            $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
        }
    } else {
        if ($user_id && $status) {
            $count_stmt->bind_param("ss", $user_id, $status);
        } elseif ($user_id) {
            $count_stmt->bind_param("s", $user_id);
        } elseif ($status) {
            $count_stmt->bind_param("s", $status);
        }
    }

    $count_stmt->execute();
    $total_orders = $count_stmt->get_result()->fetch_assoc()['total'];

    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Lấy danh sách đơn hàng thành công',
        'code' => 200,
        'data' => [
            'orders' => $orders_arr,
            'pagination' => [
                'total' => $total_orders,
                'count' => count($orders_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_orders / $limit)
            ]
        ]
    ];
    http_response_code(200);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
