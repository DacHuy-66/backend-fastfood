<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
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

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $conn->begin_transaction();
    
    try {
        // Lấy dữ liệu hiện tại
        $sql_select = "SELECT title, icon, content, type FROM contact_info WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $current_data = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        if (!$current_data) {
            throw new Exception('Không tìm thấy contact info với ID: ' . $id);
        }

        // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
        $title = isset($data['title']) ? trim($data['title']) : $current_data['title'];
        $icon = isset($data['icon']) ? trim($data['icon']) : $current_data['icon'];
        $content = isset($data['content']) ? trim($data['content']) : $current_data['content'];
        $type = isset($data['type']) ? trim($data['type']) : $current_data['type'];

        $sql_update = "UPDATE contact_info SET title = ?, icon = ?, content = ?, type = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("ssssi", $title, $icon, $content, $type, $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật contact info thành công',
            'data' => [
                'id' => $id,
                'title' => $title,
                'icon' => $icon,
                'content' => $content,
                'type' => $type
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi cập nhật: ' . $e->getMessage()
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