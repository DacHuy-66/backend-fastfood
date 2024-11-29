<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $conn->begin_transaction();
    
    try {
        // Lấy dữ liệu hiện tại
        $sql_select = "SELECT name, description, copyright_text FROM company_info WHERE id = 1";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->execute();
        $current_data = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        if (!$current_data) {
            throw new Exception('Không tìm thấy thông tin công ty');
        }

        // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
        $name = isset($data['name']) ? trim($data['name']) : $current_data['name'];
        $description = isset($data['description']) ? trim($data['description']) : $current_data['description'];
        $copyright_text = isset($data['copyright_text']) ? trim($data['copyright_text']) : $current_data['copyright_text'];

        $sql_update = "UPDATE company_info SET name = ?, description = ?, copyright_text = ? WHERE id = 1";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("sss", $name, $description, $copyright_text);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thông tin công ty thành công',
            'data' => [
                'name' => $name,
                'description' => $description,
                'copyright_text' => $copyright_text
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