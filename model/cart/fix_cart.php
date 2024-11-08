<?php

include_once __DIR__ . '/../../config/db.php';

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['cart_id'])) {
        throw new Exception('Missing required field: cart_id', 400);
    }

    $cart_id = intval($data['cart_id']);
    $quantity = isset($data['quantity']) ? max(1, intval($data['quantity'])) : null;
    $checker = isset($data['checker']) ? (bool)$data['checker'] : null;

    // Check if cart item exists
    $check_sql = "SELECT c.id, c.product_id, c.quantity, p.quantity as stock 
                  FROM cart c
                  LEFT JOIN products p ON c.product_id = p.id 
                  WHERE c.id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $cart_id);
    $check_stmt->execute();
    $cart_result = $check_stmt->get_result();

    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart item not found', 404);
    }

    $cart_item = $cart_result->fetch_assoc();

    // Build update SQL based on provided fields
    $updates = [];
    $types = "";
    $params = [];

    if ($quantity !== null) {
        // Check if requested quantity is available in stock
        if ($quantity > $cart_item['stock']) {
            throw new Exception('Requested quantity exceeds available stock', 400);
        }
        $updates[] = "quantity = ?";
        $types .= "i";
        $params[] = $quantity;
    }

    if ($checker !== null) {
        $updates[] = "checker = ?";
        $types .= "i";
        $params[] = $checker ? 1 : 0;
    }

    if (empty($updates)) {
        throw new Exception('No fields to update', 400);
    }

    // Add cart_id to params
    $types .= "i";
    $params[] = $cart_id;

    // Prepare and execute update
    $update_sql = "UPDATE cart SET " . implode(", ", $updates) . " WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);

    // Dynamically bind parameters
    $bind_params = array_merge([$types], $params);
    $tmp = [];
    foreach ($bind_params as $key => $value) {
        $tmp[$key] = &$bind_params[$key];
    }
    call_user_func_array([$update_stmt, 'bind_param'], $tmp);

    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('No changes made to cart item', 400);
    }

    // Get updated cart item
    $get_sql = "SELECT c.*, p.name as product_name, p.price 
                FROM cart c
                LEFT JOIN products p ON c.product_id = p.id 
                WHERE c.id = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("i", $cart_id);
    $get_stmt->execute();
    $updated_item = $get_stmt->get_result()->fetch_assoc();

    // Prepare success response
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Cart item updated successfully',
        'code' => 200,
        'data' => [
            'cart_item' => [
                'id' => (int)$updated_item['id'],
                'product_id' => $updated_item['product_id'],
                'product_name' => $updated_item['product_name'],
                'quantity' => (int)$updated_item['quantity'],
                'price' => (float)$updated_item['price'],
                'checker' => (bool)$updated_item['checker']
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
} finally {
    // Close all prepared statements
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($get_stmt)) $get_stmt->close();
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
