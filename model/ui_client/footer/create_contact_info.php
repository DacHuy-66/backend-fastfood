<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $conn->begin_transaction();
    
    try {
        // Kiểm tra dữ liệu đầu vào
        if (!isset($data['title']) || !isset($data['icon']) || !isset($data['content']) || !isset($data['type'])) {
            throw new Exception('Thiếu thông tin bắt buộc');
        }

        $title = trim($data['title']);
        $icon = trim($data['icon']);
        $content = trim($data['content']);
        $type = trim($data['type']);

        $sql = "INSERT INTO contact_info (title, icon, content, type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $title, $icon, $content, $type);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Thêm contact info thành công',
            'data' => [
                'id' => $new_id,
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
            'message' => 'Lỗi khi thêm mới: ' . $e->getMessage()
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