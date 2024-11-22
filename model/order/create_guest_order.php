<?php
include_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['name']) || !isset($data['phone']) || !isset($data['address']) || 
    !isset($data['products']) || empty($data['products'])) {
    echo json_encode([
        'ok' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin!'
    ]);
    http_response_code(400);
    exit;
}

try {
    $conn->begin_transaction();

    // Validate products and get details
    $product_details = [];
    foreach ($data['products'] as $product) {
        if (!isset($product['product_id']) || !isset($product['quantity']) || $product['quantity'] <= 0) {
            throw new Exception('Thông tin sản phẩm không hợp lệ!');
        }

        // Check product availability
        $product_sql = "SELECT id, name, price, quantity as stock FROM products WHERE id = ?";
        $product_stmt = $conn->prepare($product_sql);
        $product_stmt->bind_param("s", $product['product_id']);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();

        if ($product_result->num_rows === 0) {
            throw new Exception('Sản phẩm không tồn tại!');
        }

        $product_info = $product_result->fetch_assoc();
        if ($product_info['stock'] < $product['quantity']) {
            throw new Exception("Sản phẩm {$product_info['name']} không đủ số lượng trong kho!");
        }

        $product_details[] = [
            'product_id' => $product_info['id'],
            'name' => $product_info['name'],
            'price' => $product_info['price'],
            'quantity' => $product['quantity']
        ];
    }

    // Create order ID
    $order_id = substr(uniqid(), 0, 8);

    // Calculate total quantity
    $total_quantity = array_sum(array_column($product_details, 'quantity'));

    // Create guest order
    $order_sql = "INSERT INTO guest_orders (id, name, phone, email, address, quantity, 
                  status, note, discount_code, total_price, subtotal) 
                  VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)";
    $order_stmt = $conn->prepare($order_sql);
    $note = $data['note'] ?? null;
    $email = $data['email'] ?? null;
    $discount_code = $data['discount_code'] ?? null;
    $total_price = $data['total_price'];
    $subtotal = $data['subtotal'];

    $order_stmt->bind_param(
        "sssssissdd",
        $order_id,
        $data['name'],
        $data['phone'],
        $email,
        $data['address'],
        $total_quantity,
        $note,
        $discount_code,
        $total_price,
        $subtotal
    );
    $order_stmt->execute();

    // Add order details and update inventory
    foreach ($product_details as $item) {
        $detail_sql = "INSERT INTO guest_product_order (order_id, product_id, quantity, price) 
                      VALUES (?, ?, ?, ?)";
        $detail_stmt = $conn->prepare($detail_sql);
        $detail_stmt->bind_param("ssid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $detail_stmt->execute();

        // Update inventory
        $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $item['quantity'], $item['product_id']);
        $update_stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Đặt hàng thành công!',
        'data' => [
            'order_id' => $order_id,
            'status' => 'Pending'
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Guest order error: " . $e->getMessage());
    
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
} finally {
    $conn->close();
} 