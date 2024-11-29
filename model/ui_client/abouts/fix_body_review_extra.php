<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $url_path);
    $id = end($path_parts);

    // Chỉ cho phép sửa ID từ 5 trở đi
    if (!is_numeric($id) || $id < 5) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ. Chỉ được phép sửa ID từ 5 trở đi'
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
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['name']) || !isset($data['description']) || !isset($data['icon'])) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Thiếu thông tin cần thiết'
        ]);
        exit;
    }

    $conn->begin_transaction();
    
    try {
        $name = trim($data['name']);
        $description = trim($data['description']);
        $icon = trim($data['icon']);

        $sql_insert = "INSERT INTO body_review (name, description, icon) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("sss", $name, $description, $icon);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Thêm body review thành công',
            'data' => [
                'id' => $new_id,
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
            'message' => 'Lỗi khi thêm mới: ' . $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $url_path);
    $id = end($path_parts);

    if (!is_numeric($id) || $id < 5) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ. Chỉ được phép xóa ID từ 5 trở đi'
        ]);
        exit;
    }

    $conn->begin_transaction();
    
    try {
        $sql_delete = "DELETE FROM body_review WHERE id = ? AND id >= 5";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Không tìm thấy body review để xóa hoặc không được phép xóa ID này');
        }
        
        $stmt->close();
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Xóa body review thành công'
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