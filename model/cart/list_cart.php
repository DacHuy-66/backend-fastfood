<?php

include_once __DIR__ . '/../../config/db.php';
try {
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Sorting and search parameters
    $user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
    $sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

    // Prepare SQL query
    $sql = "SELECT c.id, c.user_id, c.product_id, c.quantity, c.checker,
                   p.name as product_name, p.price, p.image_url 
            FROM cart c
            LEFT JOIN products p ON c.product_id = p.id
            WHERE (? = '' OR c.user_id = ?)
            ORDER BY $sort_by $sort_order 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $user_id, $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get cart items
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = (int)$row['quantity'];
        $row['price'] = (float)$row['price'];
        $row['checker'] = (bool)$row['checker'];
        $cart_items[] = $row;
    }

    // Get total count for pagination
    $count_sql = "SELECT COUNT(id) AS total 
                  FROM cart 
                  WHERE (? = '' OR user_id = ?)";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $user_id, $user_id);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total_items = $total_result['total'];

    // Close queries
    $stmt->close();
    $count_stmt->close();
    $conn->close();

    // Prepare response
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Cart items retrieved successfully',
        'code' => 200,
        'data' => [
            'cart_items' => $cart_items,
            'pagination' => [
                'total' => (int)$total_items,
                'count' => count($cart_items),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_items / $limit)
            ],
            'filters' => [
                'user_id' => $user_id,
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
