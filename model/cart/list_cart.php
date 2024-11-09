<?php

include_once __DIR__ . '/../../config/db.php';
try {
    // Get API key from header
    $headers = getallheaders();
    $api_key = $headers['X-Api-Key'] ?? '';

    if (empty($api_key)) {
        throw new Exception('Thiếu API key trong header', 401);
    }

    // Get user_id from api_key
    $user_sql = "SELECT id FROM users WHERE api_key = ? LIMIT 1";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $api_key);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception('API key không hợp lệ', 401);
    }
    
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
    $sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT c.id, c.user_id, c.product_id, c.quantity, c.checker,
                   p.name as product_name, p.price, p.discount, p.image_url, p.status, p.quantity as product_quantity
            FROM cart c
            LEFT JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY $sort_by $sort_order 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $warnings = [];
    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = (int)$row['quantity'];
        $row['price'] = (float)$row['price'];
        $row['checker'] = (bool)$row['checker'];
        $row['discount'] = (float)$row['discount'];
        $row['status'] = (bool)$row['status'];
        $row['product_quantity'] = (int)$row['product_quantity'];
        $row['warning'] = true;

        // Kiểm tra số lượng sản phẩm
        if ($row['product_quantity'] === 0) {
            $warnings[] = "Sản phẩm '{$row['product_name']}' đã hết hàng";
            $row['warning'] = false;
        } elseif ($row['product_quantity'] < $row['quantity']) {
            $warnings[] = "Số lượng sản phẩm '{$row['product_name']}' trong kho không đủ (Còn {$row['product_quantity']} sản phẩm)";
            $row['warning'] = false;
        }

        $cart_items[] = $row;
    }

    // Lấy tổng số lượng để phân trang
    $count_sql = "SELECT COUNT(id) AS total 
                  FROM cart 
                  WHERE user_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $user_id);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result()->fetch_assoc();
    $total_items = $total_result['total'];

    $stmt->close();
    $count_stmt->close();
    $conn->close();

    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Lấy giỏ hàng thành công',
        'code' => 200,
        'data' => [
            'cart_items' => $cart_items,
            'warnings' => $warnings,
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
