<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE'); 
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    // Lấy ID từ URL
    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
    $review_id = end($url_parts);  // Lấy phần tử cuối cùng của URL
    
    // Kiểm tra ID có hợp lệ không
    if (!filter_var($review_id, FILTER_VALIDATE_INT)) {
        throw new Exception('Invalid review ID format', 400);
    }

    // Kiểm tra review có tồn tại không
    $select_query = "SELECT * FROM reviews WHERE id = ?";
    $select_stmt = $conn->prepare($select_query);
    $select_stmt->bind_param("i", $review_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Review not found', 404);
    }
    
    $review = $result->fetch_assoc();

    // Thực hiện xóa review
    $delete_query = "DELETE FROM reviews WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $review_id);
    
    if ($delete_stmt->execute()) {
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Review deleted successfully',
            'code' => 200,
        ];
        http_response_code(200);
    } else {
        throw new Exception('Failed to delete review', 500);
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