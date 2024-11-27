<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Lấy ID từ URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $url_path);
    $id = end($path_parts); // Lấy phần tử cuối cùng của URL

    // Đọc dữ liệu JSON từ input stream
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_numeric($id)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'ID không hợp lệ'
        ]);
        exit;
    }

    // Trước tiên, lấy dữ liệu hiện tại từ database
    $sql_select = "SELECT step_number, title, description, icon, order_number FROM order_process WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $current_data = $result->fetch_assoc();
    $stmt_select->close();

    if (!$current_data) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không tìm thấy bản ghi với ID này'
        ]);
        exit;
    }

    // Cập nhật chỉ những trường được gửi đến
    $step_number = isset($data['step_number']) ? trim($data['step_number']) : $current_data['step_number'];
    $title = isset($data['title']) ? trim($data['title']) : $current_data['title'];
    $description = isset($data['description']) ? trim($data['description']) : $current_data['description'];
    $icon = isset($data['icon']) ? trim($data['icon']) : $current_data['icon'];
    $order_number = isset($data['order_number']) ? trim($data['order_number']) : $current_data['order_number'];

    $sql_update = "UPDATE order_process SET 
            step_number = ?,
            title = ?,
            description = ?,
            icon = ?,
            order_number = ?
            WHERE id = ?";
            
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("isssii", 
        $step_number, 
        $title, 
        $description, 
        $icon,
        $order_number,
        $id
    );
    
    if ($stmt_update->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => [
                'id' => $id,
                'step_number' => $step_number,
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
                'order_number' => $order_number
            ]
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi cập nhật: ' . $stmt_update->error
        ]);
    }
    $stmt_update->close();
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}

$conn->close();
?>
