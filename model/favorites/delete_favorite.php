<?php
include_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Lấy favorite_id từ URL
    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
    $favorite_id = end($url_parts);

    if (!is_numeric($favorite_id)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        exit;
    }

    try {
        // Kiểm tra xem favorite có tồn tại không
        $sql_check = "SELECT id FROM favorites WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $favorite_id);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows === 0) {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Không tìm thấy mục yêu thích với ID: ' . $favorite_id
            ]);
            exit;
        }
        $stmt_check->close();

        // Xóa khỏi favorites
        $sql_delete = "DELETE FROM favorites WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $favorite_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Xóa khỏi yêu thích thành công'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi xóa khỏi yêu thích: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}

$conn->close();
?> 