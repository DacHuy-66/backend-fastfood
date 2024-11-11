<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With, X-Api-Key');

include_once __DIR__ . '/../../config/db.php';

try {
    $headers = getallheaders();
    $api_key = $headers['X-Api-Key'] ?? '';

    if (empty($api_key)) {
        throw new Exception('API key bị thiếu.', 401);
    }

    $sql = "SELECT id FROM users WHERE api_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('API key không hợp lệ.', 401);
    }

    $user = $result->fetch_assoc();
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->product_id) || !isset($data->rating)) {
        throw new Exception('Thiếu các trường bắt buộc: product_id và rating là bắt buộc', code: 400);
    }

    if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
        throw new Exception('Đánh giá không hợp lệ. Phải nằm giữa 1 và 5', 400);
    }

    $comment = $data->comment ?? '';
    
    // Xử lý mảng images
    $images = $data->images ?? [];
    $image_1 = isset($images[0]) ? $images[0] : null;
    $image_2 = isset($images[1]) ? $images[1] : null;
    $image_3 = isset($images[2]) ? $images[2] : null;

    $query = "INSERT INTO reviews (user_id, product_id, rating, comment, image_1, image_2, image_3, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssissss", 
        $user['id'],
        $data->product_id,
        $data->rating,
        $comment,
        $image_1,
        $image_2,
        $image_3
    );

    if ($stmt->execute()) {
        $new_review_id = $conn->insert_id;

        // Cập nhật trường review trong bảng orders
        $update_order_sql = "UPDATE orders o 
                            INNER JOIN product_order po ON o.id = po.order_id 
                            SET o.review = 0 
                            WHERE o.user_id = ? AND po.product_id = ?";
        $update_order_stmt = $conn->prepare($update_order_sql);
        $update_order_stmt->bind_param("ss", $user['id'], $data->product_id);
        $update_order_stmt->execute();

        $select_query = "SELECT r.*, u.username 
                         FROM reviews r
                         LEFT JOIN users u ON r.user_id = u.id 
                         WHERE r.id = ?";
        $select_stmt = $conn->prepare($select_query);
        $select_stmt->bind_param("i", $new_review_id);
        $select_stmt->execute();
        $created = $select_stmt->get_result()->fetch_assoc();

        $response = [
            'status' => 'success',
            'message' => 'Review created successfully',
            'code' => 201,
            'data' => $created
        ];
        http_response_code(201);
    } else {
        throw new Exception('Không thể tạo đánh giá', 500);
    }

} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
?>
