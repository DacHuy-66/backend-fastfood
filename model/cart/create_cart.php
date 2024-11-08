<?php

include_once __DIR__ . '/../../config/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // kiểm tra các trường bắt buộc
    if (!isset($data['product_id']) || !isset($data['quantity']) || !isset($data['api_key'])) {
        throw new Exception('Missing required fields: product_id, quantity, api_key', 400);
    }

    // validate và làm sạch các trường đầu vào
    $product_id = trim($data['product_id']);
    $quantity = max(1, intval($data['quantity']));
    $api_key = trim($data['api_key']);

    // Get user_id from api_key
    $user_sql = "SELECT id FROM users WHERE api_key = ? LIMIT 1";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $api_key);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception('Invalid API key', 401);
    }
    
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];

    // Check if product exists and is available
    $product_sql = "SELECT id, quantity FROM products WHERE id = ? AND status = 1 LIMIT 1";
    $product_stmt = $conn->prepare($product_sql);
    $product_stmt->bind_param("s", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows === 0) {
        throw new Exception('Product not found or unavailable', 404);
    }

    // Check if item already exists in cart
    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $user_id, $product_id);
    $check_stmt->execute();
    $cart_result = $check_stmt->get_result();

    if ($cart_result->num_rows > 0) {
        // cập nhật số lượng sản phẩm trong giỏ hàng
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_stmt->execute();
    } else {
        // thêm sản phẩm vào giỏ hàng
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, checker) VALUES (?, ?, ?, 0)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssi", $user_id, $product_id, $quantity);
        $insert_stmt->execute();
    }

    // chuẩn bị response thành công
    $response = [
        'ok' => true,
        'status' => 'success',
        'message' => 'Item added to cart successfully',
        'code' => 201,
        'data' => [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'quantity' => $quantity
        ]
    ];
    http_response_code(201);

} catch (Exception $e) {
    $response = [
        'ok' => false,
        'status' => 'error',
        'code' => $e->getCode() ?: 400,
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
} finally {
    // đóng tất cả các prepared statements
    if (isset($user_stmt)) $user_stmt->close();
    if (isset($product_stmt)) $product_stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($insert_stmt)) $insert_stmt->close();
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
