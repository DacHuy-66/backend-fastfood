<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php-errors.log');

include_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}


// Lấy api key từ header
$id_user = isset($id_user) ? $id_user : null;

// Kiểm tra api key và id user
if (!$id_user) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID user không được cung cấp.'
    ]);
    http_response_code(400);
    exit;  
}

try {
    // Thay đổi câu query để kiểm tra id dưới dạng string
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("s", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Đọc và kiểm tra input JSON
        $input = file_get_contents("php://input");
        if (!$input) {
            throw new Exception('Không nhận được dữ liệu input');
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Dữ liệu JSON không hợp lệ: ' . json_last_error_msg());
        }

        // Lấy dữ liệu mới
        $new_username = isset($data['username']) ? trim($data['username']) : null;
        $new_avata = isset($data['avata']) ? trim($data['avata']) : null;

        // Kiểm tra dữ liệu
        if (!$new_username || !$new_avata) {
            throw new Exception('Các trường username và avata là bắt buộc');
        }

        // Thay đổi câu UPDATE để xử lý id dưới dạng string
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, avata = ? WHERE id = ?");
        $update_stmt->bind_param("sss", $new_username, $new_avata, $id_user);

        if (!$update_stmt->execute()) {
            throw new Exception('Lỗi khi cập nhật dữ liệu: ' . $update_stmt->error);
        }

        if ($update_stmt->affected_rows === 0) {
            throw new Exception('Không có dữ liệu nào được cập nhật');
        }

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Dữ liệu người dùng đã cập nhật thành công.'
        ]);
        http_response_code(200);
        
        $update_stmt->close();
    } else {
        throw new Exception('ID user không hợp lệ');
    }

} catch (Exception $e) {
    error_log('Profile Update Error: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(400);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
    if (isset($conn)) $conn->close();
}
?>
