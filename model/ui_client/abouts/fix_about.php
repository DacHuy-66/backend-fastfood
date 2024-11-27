<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate input data
    if (!$data || !is_array($data)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Dữ liệu đầu vào không hợp lệ'
        ]);
        exit;
    }

    $conn->begin_transaction();
    
    try {
        // Cập nhật bảng body_review (bảng 1)
        if (isset($data['body_review'])) {
            if (!is_array($data['body_review'])) {
                throw new Exception('Dữ liệu body_review không hợp lệ');
            }
            
            // Nếu body_review là object đơn lẻ, chuyển thành mảng
            if (isset($data['body_review']['id'])) {
                $data['body_review'] = [$data['body_review']];
            }
            
            foreach ($data['body_review'] as $review) {
                if (!isset($review['id'])) {
                    throw new Exception('Thiếu ID trong body_review');
                }
                
                $id = (int)$review['id'];
                
                // Lấy dữ liệu hiện tại từ database
                $sql_select = "SELECT * FROM body_review WHERE id = ?";
                $stmt = $conn->prepare($sql_select);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $current_data = $result->fetch_assoc();
                $stmt->close();
                
                if (!$current_data) {
                    throw new Exception('Không tìm thấy body_review với ID: ' . $id);
                }
                
                // Sử dụng dữ liệu hiện tại nếu không có dữ liệu mới
                $name = isset($review['name']) ? trim((string)$review['name']) : $current_data['name'];
                $description = isset($review['description']) ? trim((string)$review['description']) : $current_data['description'];
                $icon = isset($review['icon']) ? trim((string)$review['icon']) : $current_data['icon'];
                
                $sql_update_features = "UPDATE body_review SET 
                    name = ?,
                    description = ?,
                    icon = ?
                    WHERE id = ?";
                
                $stmt = $conn->prepare($sql_update_features);
                $stmt->bind_param("sssi", $name, $description, $icon, $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Cập nhật bảng head_review (bảng 2)
        if (isset($data['head_review'])) {
            if (!is_array($data['head_review'])) {
                throw new Exception('Dữ liệu head_review không hợp lệ');
            }
            
            // Nếu head_review là object đơn lẻ, chuyển thành mảng
            if (isset($data['head_review']['id'])) {
                $data['head_review'] = [$data['head_review']];
            }
            
            foreach ($data['head_review'] as $section) {
                if (!isset($section['id'])) {
                    throw new Exception('Thiếu ID trong head_review');
                }
                
                $id = (int)$section['id'];
                
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
                $name = isset($section['name']) ? trim((string)$section['name']) : $current_data['name'];
                $description = isset($section['description']) ? trim((string)$section['description']) : $current_data['description'];
                
                $sql_update_about = "UPDATE head_review SET 
                    name = ?,
                    description = ?
                    WHERE id = ?";
                
                $stmt = $conn->prepare($sql_update_about);
                $stmt->bind_param("ssi", $name, $description, $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Commit transaction nếu mọi thứ OK
        $conn->commit();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thông tin about thành công',
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
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
