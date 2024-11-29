<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Lấy ID từ URL mới
    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
    $id = end($url_parts); // Lấy phần tử cuối cùng

    // Validate ID
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
        // Lấy dữ liệu hiện tại từ database
        $sql_select = "SELECT * FROM head_review WHERE id = ?";
        $stmt = $conn->prepare($sql_select);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!$current_data) {
            throw new Exception('Không tìm thấy head_review với ID: ' . $id);
        }
        
        // Sử dụng dữ liệu hiện tại nếu không có dữ liệu mới
        $name = isset($data['name']) ? trim($data['name']) : $current_data['name'];
        $description = isset($data['description']) ? trim($data['description']) : $current_data['description'];
        
        $sql_update_about = "UPDATE head_review SET 
            name = ?,
            description = ?
            WHERE id = ?";
        
        $stmt = $conn->prepare($sql_update_about);
        $stmt->bind_param("ssi", $name, $description, $id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thông tin head review thành công',
            'data' => [
                'id' => $id,
                'name' => $name,
                'description' => $description
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
