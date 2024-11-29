<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $url_path);
    $id = end($path_parts);

    // Chỉ cho phép sửa ID từ 1-4
    if (!is_numeric($id) || $id < 1 || $id > 4) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ. Chỉ được phép sửa ID từ 1-4'
        ]);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $conn->begin_transaction();
    
    try {
        // Lấy dữ liệu hiện tại
        $sql_select = "SELECT name, description, icon FROM body_review WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $current_data = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        if (!$current_data) {
            throw new Exception('Không tìm thấy body review với ID: ' . $id);
        }

        // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
        $name = isset($data['name']) ? trim($data['name']) : $current_data['name'];
        $description = isset($data['description']) ? trim($data['description']) : $current_data['description'];
        $icon = isset($data['icon']) ? trim($data['icon']) : $current_data['icon'];

        $sql_update = "UPDATE body_review SET name = ?, description = ?, icon = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("sssi", $name, $description, $icon, $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật body review thành công',
            'data' => [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'icon' => $icon
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