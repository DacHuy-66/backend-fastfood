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
        $sql_select = "SELECT platform, icon, url FROM social_media WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $current_data = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        if (!$current_data) {
            throw new Exception('Không tìm thấy social media với ID: ' . $id);
        }

        // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
        $platform = isset($data['platform']) ? trim($data['platform']) : $current_data['platform'];
        $icon = isset($data['icon']) ? trim($data['icon']) : $current_data['icon'];
        $url = isset($data['url']) ? trim($data['url']) : $current_data['url'];

        $sql_update = "UPDATE social_media SET platform = ?, icon = ?, url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("sssi", $platform, $icon, $url, $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật social media thành công',
            'data' => [
                'id' => $id,
                'platform' => $platform,
                'icon' => $icon,
                'url' => $url
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