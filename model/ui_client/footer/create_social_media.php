<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $conn->begin_transaction();
    
    try {
        // Kiểm tra dữ liệu đầu vào
        if (!isset($data['platform']) || !isset($data['icon']) || !isset($data['url'])) {
            throw new Exception('Thiếu thông tin bắt buộc');
        }

        $platform = trim($data['platform']);
        $icon = trim($data['icon']);
        $url = trim($data['url']);

        $sql = "INSERT INTO social_media (platform, icon, url) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $platform, $icon, $url);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Thêm social media thành công',
            'data' => [
                'id' => $new_id,
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