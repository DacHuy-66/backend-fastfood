<?php
include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy user_id từ URL
    $url_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $user_id = end($url_parts);  

    // Kiểm tra xem ID user có được cung cấp hay không
    if (empty($user_id)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID user không được cung cấp!'
        ]);
        http_response_code(400);
        exit;
    }

    // Kiểm tra xem user có tồn tại không
    $check_query = "SELECT * FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy user', 404);
    }

    // Xóa các dữ liệu liên quan trước khi xóa user
    $delete_reviews_query = "DELETE FROM reviews WHERE user_id = ?";
    $delete_reviews_stmt = $conn->prepare($delete_reviews_query);
    $delete_reviews_stmt->bind_param("s", $user_id);
    $delete_reviews_stmt->execute();

    $delete_orders_query = "DELETE FROM orders WHERE user_id = ?";
    $delete_orders_stmt = $conn->prepare($delete_orders_query);
    $delete_orders_stmt->bind_param("s", $user_id);
    $delete_orders_stmt->execute();

    $delete_cart_query = "DELETE FROM cart WHERE user_id = ?";
    $delete_cart_stmt = $conn->prepare($delete_cart_query);
    $delete_cart_stmt->bind_param("s", $user_id);
    $delete_cart_stmt->execute();

    $delete_discount_user_query = "DELETE FROM discount_user WHERE user_id = ?";
    $delete_discount_user_stmt = $conn->prepare($delete_discount_user_query);
    $delete_discount_user_stmt->bind_param("s", $user_id);
    $delete_discount_user_stmt->execute();

    $delete_discount_history_query = "DELETE FROM discount_history WHERE user_id = ?";
    $delete_discount_history_stmt = $conn->prepare($delete_discount_history_query);
    $delete_discount_history_stmt->bind_param("s", $user_id);
    $delete_discount_history_stmt->execute();

    $delete_detail_address_query = "DELETE FROM detail_address WHERE user_id = ?";
    $delete_detail_address_stmt = $conn->prepare($delete_detail_address_query);
    $delete_detail_address_stmt->bind_param("s", $user_id);
    $delete_detail_address_stmt->execute();

    // Chuẩn bị câu truy vấn xóa user
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = [
                'ok' => true,
                'status' => 'success',
                'message' => 'Xóa user và dữ liệu liên quan thành công',
                'code' => 200
            ];
            http_response_code(200);
        } else {
            throw new Exception('Không tìm thấy user', 404);
        }
    } else {
        // Kiểm tra mã lỗi để xác định xem có phải do ràng buộc khóa ngoại không
        if ($stmt->errno === 1451) {
            throw new Exception('Không thể xóa hoặc cập nhật hàng cha: ràng buộc khóa ngoại không thỏa mãn', 1451);
        } else {
            throw new Exception('Không thể xóa user', 500);
        }
    }
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
