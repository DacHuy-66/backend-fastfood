<?php
include_once __DIR__ . '/../../config/db.php';

// Lấy dữ liệu từ request 
$data = json_decode(file_get_contents('php://input'), true);

// Validate dữ liệu đầu vào
if (!isset($data['user_id']) || !isset($data['address_id']) || !isset($data['products']) || empty($data['products'])) {
   echo json_encode([
       'ok' => false,
       'success' => false,
       'message' => 'Thiếu thông tin cần thiết!'
   ]);
   http_response_code(400);
   exit;
}

try {
   // Bắt đầu transaction
   $conn->begin_transaction();

   // Validate và lấy thông tin sản phẩm
   $product_details = [];
   
   foreach ($data['products'] as $product) {
       if (!isset($product['product_id']) || !isset($product['quantity'])) {
           throw new Exception('Thông tin sản phẩm không hợp lệ!');
       }

       // Kiểm tra sản phẩm và lấy giá và số lượng tồn
       $product_sql = "SELECT id, name, price, quantity as stock FROM products WHERE id = ?";
       $product_stmt = $conn->prepare($product_sql);
       $product_stmt->bind_param("s", $product['product_id']);
       $product_stmt->execute();
       $product_result = $product_stmt->get_result();
       
       if ($product_result->num_rows === 0) {
           throw new Exception('Sản phẩm không tồn tại!');
       }
       
       $product_info = $product_result->fetch_assoc();
       
       // Kiểm tra số lượng tồn kho
       if($product_info['stock'] < $product['quantity']) {
           throw new Exception("Sản phẩm {$product_info['name']} không đủ số lượng trong kho!");
       }

       $product_details[] = [
           'product_id' => $product_info['id'],
           'name' => $product_info['name'], 
           'price' => $product_info['price'],
           'quantity' => $product['quantity']
       ];
   }

   // Tạo order ID
   $order_id = substr(uniqid(), 0, 8);
   
   // Tính tổng số lượng
   $total_quantity = array_sum(array_column($product_details, 'quantity'));
   
   $discount_code = $data['discount_code'] ?? null;
   
   // Xử lý discount history và cập nhật/xóa discount
   if ($discount_code) {
       // Kiểm tra discount trước
    //    $check_discount_sql = "SELECT quantity FROM discounts WHERE code = ?";
    //    $check_stmt = $conn->prepare($check_discount_sql);
    //    $check_stmt->bind_param("s", $discount_code);
    //    $check_stmt->execute();
    //    $discount_result = $check_stmt->get_result()->fetch_assoc();

    //    if (!$discount_result) {
    //        throw new Exception('Mã giảm giá không tồn tại hoặc đã hết!');
    //    }

       // Thêm vào discount_history với cấu trúc mới
       $insert_history_sql = "INSERT INTO discount_history (user_id, status, Datetime, discount_code) 
                            VALUES (?, 'used', NOW(), ?)";
       $history_stmt = $conn->prepare($insert_history_sql);
       $history_stmt->bind_param("ss", 
           $data['user_id'],
           $discount_code
       );
       
       if (!$history_stmt->execute()) {
           throw new Exception('Không thể lưu lịch sử sử dụng mã giảm giá!');
       }

       // Xử lý discount dựa vào loại (user specific hoặc general)
       // Kiểm tra xem discount có phải của user cụ thể không
       $check_user_discount_sql = "SELECT * FROM discount_user WHERE code = ? AND user_id = ?";
       $check_user_stmt = $conn->prepare($check_user_discount_sql);
       $check_user_stmt->bind_param("ss", $discount_code, $data['user_id']);
       $check_user_stmt->execute();
       $user_discount_result = $check_user_stmt->get_result();

       if ($user_discount_result->num_rows > 0) {
           // Nếu là discount của user cụ thể - xóa khỏi discount_user
           $delete_sql = "DELETE FROM discount_user WHERE code = ? AND user_id = ?";
           $delete_stmt = $conn->prepare($delete_sql);
           $delete_stmt->bind_param("ss", $discount_code, $data['user_id']);
           $delete_stmt->execute();
       } else {
           // Nếu là discount chung - giảm số lượng
           $update_sql = "UPDATE discounts SET quantity = quantity - 1 WHERE code = ? AND quantity > 0";
           $update_stmt = $conn->prepare($update_sql);
           $update_stmt->bind_param("s", $discount_code);
           $update_stmt->execute();
       }
   }

   // Tạo đơn hàng
   $order_sql = "INSERT INTO orders (id, user_id, address_id, quantity, status, note, discount_code, total_price, subtotal, created_at, updated_at) 
               VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, NOW(), NOW())";
   $order_stmt = $conn->prepare($order_sql);
   $note = $data['note'] ?? null;
   $total_price = $data['total_price'];
   $subtotal = $data['subtotal'];
   $order_stmt->bind_param("ssiissdd", 
       $order_id,
       $data['user_id'],
       $data['address_id'],
       $total_quantity,
       $note,
       $discount_code,
       $total_price,
       $subtotal
   );
   $order_stmt->execute();

   // Thêm chi tiết đơn hàng
   foreach ($product_details as $item) {
       $product_order_sql = "INSERT INTO product_order (order_id, product_id, quantity, price) 
                           VALUES (?, ?, ?, ?)";
       $product_order_stmt = $conn->prepare($product_order_sql);
       $product_order_stmt->bind_param("ssid", 
           $order_id,
           $item['product_id'],
           $item['quantity'],
           $item['price']
       );
       $product_order_stmt->execute();
   }

   // Tạo payment record
   $payment_sql = "INSERT INTO payments (order_id, payment_method, payment_status, payment_date) 
               VALUES (?, ?, 'Pending', NULL)";
   $payment_stmt = $conn->prepare($payment_sql);
   $payment_method = $data['payment_method'] ?? 'COD';
   $payment_stmt->bind_param("ss", $order_id, $payment_method);
   $payment_stmt->execute();

   // Xử lý giỏ hàng và cập nhật tồn kho
   foreach ($data['products'] as $product) {
       // Xóa sản phẩm khỏi giỏ hàng
       $delete_cart_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
       $delete_cart_stmt = $conn->prepare($delete_cart_sql);
       $delete_cart_stmt->bind_param("ss", $data['user_id'], $product['product_id']);
       $delete_cart_stmt->execute();

       // Cập nhật số lượng tồn kho
       $update_product_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
       $update_product_stmt = $conn->prepare($update_product_sql);
       $update_product_stmt->bind_param("isi", 
           $product['quantity'],
           $product['product_id'],
           $product['quantity']
       );
       $update_product_stmt->execute();
   }

   // Commit transaction
   $conn->commit();

   // Lấy thông tin đơn hàng
   $order_detail_sql = "SELECT o.*, u.username, p.name as product_name, po.price, po.quantity as item_quantity,
                       da.phone, da.address
                       FROM orders o
                       LEFT JOIN users u ON o.user_id = u.id
                       LEFT JOIN product_order po ON o.id = po.order_id
                       LEFT JOIN products p ON po.product_id = p.id
                       LEFT JOIN detail_address da ON o.address_id = da.id
                       WHERE o.id = ?";
   $order_detail_stmt = $conn->prepare($order_detail_sql);
   $order_detail_stmt->bind_param("s", $order_id);
   $order_detail_stmt->execute();
   $order_result = $order_detail_stmt->get_result();
   
   $orders = [];
   $customer = null;

   while ($row = $order_result->fetch_assoc()) {
       // Lưu thông tin customer từ row đầu tiên
       if ($customer === null) {
           $customer = [
               'username' => $row['username'],
               'user_id' => $data['user_id'],
               'phone' => $row['phone'],
               'address' => $row['address'],
               'note' => $row['note']
           ];
       }

       $orders[] = [
           'order_id' => $row['id'],
           'product_name' => $row['product_name'],
           'quantity' => $row['item_quantity'],
           'price' => $row['price'],
           'total_price' => $row['total_price'],
           'subtotal' => $row['subtotal']
       ];
   }

   if ($customer === null) {
       throw new Exception('Không thể lấy thông tin đơn hàng!');
   }

   // Trả về kết quả
   echo json_encode([
       'ok' => true,
       'success' => true,
       'message' => 'Tạo đơn hàng thành công!',
       'data' => [
           'customer' => $customer,
           'orders' => $orders,
           'status' => 'Pending',
           'payment_method' => $payment_method,
           'discount_code' => $discount_code,
           'note' => $note
       ]
   ]);

} catch (Exception $e) {
   // Rollback nếu có lỗi
   $conn->rollback();
   
   echo json_encode([
       'ok' => false,
       'success' => false,
       'message' => $e->getMessage()
   ]);
   http_response_code(500);
}

$conn->close();