<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    $data = json_decode(file_get_contents("php://input"));

    // kiểm tra các trường bắt buộc
    if (!isset($data->user_id) || !isset($data->product_id) || !isset($data->rating)) {
        throw new Exception('Thiếu các trường bắt buộc: user_id, product_id, và rating là bắt buộc', 400);
    }

    if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
        throw new Exception('Đánh giá không hợp lệ. Phải nằm giữa 1 và 5', 400);
    }

    // chuẩn bị câu truy vấn chèn
    $query = "INSERT INTO reviews (user_id, product_id, rating, comment, image_1, image_2, image_3, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

    $stmt = $conn->prepare($query);
    
    // gán các tham số
    $comment = $data->comment ?? '';
    $image_1 = $data->image_1 ?? null; // giả định là các đường dẫn hình ảnh được truyền
    $image_2 = $data->image_2 ?? null;
    $image_3 = $data->image_3 ?? null;

    // gán các tham số
    $stmt->bind_param("ssissss", 
        $data->user_id, 
        $data->product_id, 
        $data->rating, 
        $comment, 
        $image_1, 
        $image_2, 
        $image_3
    );

    if ($stmt->execute()) {
        $new_review_id = $conn->insert_id;

        // lấy đánh giá đã tạo
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
