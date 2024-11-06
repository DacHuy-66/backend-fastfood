<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID đánh giá là bắt buộc', 400);
    }

    $review_id = $_GET['id'];
    if (!filter_var($review_id, FILTER_VALIDATE_INT)) {
        throw new Exception('Định dạng ID đánh giá không hợp lệ', 400);
    }
    
    $query = "SELECT r.*, u.username 
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?";
            
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Lấy đánh giá thành công',
            'code' => 200,
            'data' => $result->fetch_assoc()
        ];
        http_response_code(200);
    } else {
        throw new Exception('Không tìm thấy đánh giá', 404);
    }

} catch (Exception $e) {
    $response = [
        'ok' => true,
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
?>