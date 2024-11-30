<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $url_path);
    $id = end($path_parts);

    if (!is_numeric($id)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        exit;
    }

    $conn->begin_transaction();
    
    try {
        $sql = "DELETE FROM social_media WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Không tìm thấy social media với ID: ' . $id);
        }
        
        $stmt->close();
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Xóa social media thành công'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi xóa: ' . $e->getMessage()
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