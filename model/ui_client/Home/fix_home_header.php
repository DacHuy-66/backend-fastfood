<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Đọc dữ liệu JSON từ input stream
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $id = 1; // ID mặc định

    // Trước tiên, lấy dữ liệu hiện tại từ database
    $sql_select = "SELECT site_name, logo_url, site_slogan, opening_hours, search_placeholder FROM website_info WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $current_data = $result->fetch_assoc();
    $stmt_select->close();

    // Cập nhật chỉ những trường được gửi đến
    $site_name = isset($data['site_name']) ? trim($data['site_name']) : $current_data['site_name'];
    $logo_url = isset($data['logo_url']) ? trim($data['logo_url']) : $current_data['logo_url'];
    $site_slogan = isset($data['site_slogan']) ? trim($data['site_slogan']) : $current_data['site_slogan'];
    $opening_hours = isset($data['opening_hours']) ? trim($data['opening_hours']) : $current_data['opening_hours'];
    $search_placeholder = isset($data['search_placeholder']) ? trim($data['search_placeholder']) : $current_data['search_placeholder'];

    $sql_update = "UPDATE website_info SET 
            site_name = ?,
            logo_url = ?,
            site_slogan = ?,
            opening_hours = ?,
            search_placeholder = ?
            WHERE id = ?";
            
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssi", 
        $site_name, 
        $logo_url, 
        $site_slogan, 
        $opening_hours, 
        $search_placeholder, 
        $id
    );
    
    if ($stmt_update->execute()) {
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => [
                'id' => $id,
                'site_name' => $site_name,
                'logo_url' => $logo_url,
                'site_slogan' => $site_slogan,
                'opening_hours' => $opening_hours,
                'search_placeholder' => $search_placeholder
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
