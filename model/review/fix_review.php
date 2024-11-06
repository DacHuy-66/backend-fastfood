<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    // lấy ID đánh giá từ URL
    $url_parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $review_id = end($url_parts);

    if (!is_numeric($review_id)) {
        throw new Exception('ID đánh giá không hợp lệ', 400);
    }

    // lấy dữ liệu PUT
    $data = json_decode(file_get_contents("php://input"));

    // kiểm tra đánh giá nếu được cung cấp
    if (isset($data->rating)) {
        if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
            throw new Exception('Đánh giá không hợp lệ. Phải nằm giữa 1 và 5', 400);
        }
    }

    // kiểm tra xem đánh giá có tồn tại không
    $check_query = "SELECT * FROM reviews WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $review_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy đánh giá', 404);
    }

    // xây dựng câu truy vấn cập nhật dựa trên các trường được cung cấp
    $update_fields = array();
    $types = "";
    $values = array();

    if (isset($data->rating)) {
        $update_fields[] = "rating = ?";
        $types .= "i";
        $values[] = $data->rating;
    }

    if (isset($data->comment)) {
        $update_fields[] = "comment = ?";
        $types .= "s";
        $values[] = $data->comment;
    }

    if (isset($data->image_1)) {
        $update_fields[] = "image_1 = ?";
        $types .= "s";
        $values[] = $data->image_1;
    }

    if (isset($data->image_2)) {
        $update_fields[] = "image_2 = ?";
        $types .= "s";
        $values[] = $data->image_2;
    }

    if (isset($data->image_3)) {
        $update_fields[] = "image_3 = ?";
        $types .= "s";
        $values[] = $data->image_3;
    }

    if (empty($update_fields)) {
        throw new Exception('Không có trường nào để cập nhật', 400);
    }

    // thêm ID đánh giá vào mảng giá trị và kiểu
    $values[] = $review_id;
    $types .= "i";

    // chuẩn bị và thực thi câu truy vấn cập nhật
    $query = "UPDATE reviews SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);

    // gán các tham số
    $bind_params = array($types);
    foreach ($values as $key => $value) {
        $bind_params[] = &$values[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute()) {
        // lấy đánh giá đã cập nhật
        $select_query = "SELECT r.*, u.username 
                        FROM reviews r
                        LEFT JOIN users u ON r.user_id = u.id 
                        WHERE r.id = ?";
        $select_stmt = $conn->prepare($select_query);
        $select_stmt->bind_param("i", $review_id);
        $select_stmt->execute();
        $updated = $select_stmt->get_result()->fetch_assoc();

        $response = [
            'status' => 'success',
            'message' => 'Đánh giá đã cập nhật thành công',
            'code' => 200,
            'data' => $updated
        ];
        http_response_code(200);
    } else {
        throw new Exception('Không thể cập nhật đánh giá', 500);
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