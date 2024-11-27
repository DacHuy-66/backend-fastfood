<?php
include_once __DIR__ . '/../../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Đọc dữ liệu JSON từ input stream
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $id = 5; // ID mặc định

    // Trước tiên, lấy dữ liệu hiện tại từ database
    $sql_select = "SELECT title, image FROM nav_menu WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $current_data = $result->fetch_assoc();
    $stmt_select->close();

    // Cập nhật chỉ những trường được gửi đến
    $title = isset($data['title']) ? trim($data['title']) : $current_data['title'];
    $image = isset($data['image']) ? trim($data['image']) : $current_data['image'];

    $sql_update = "UPDATE nav_menu SET 
            title = ?, 
            image = ?
            WHERE id = ?";
            
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssi", $title, $image, $id);
    
    if ($stmt_update->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => [
                'id' => $id,
                'title' => $title,
                'image' => $image
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
