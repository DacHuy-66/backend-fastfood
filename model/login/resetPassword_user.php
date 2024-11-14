<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$new_password = $data['new_password'];
$email = trim(strtolower($data['email']));

// Debug input data
error_log("Debug Input - Email: " . $email);

// Kiểm tra email trong bảng password_resets
$check_sql = "SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

error_log("Debug - Total records found: " . $check_result->num_rows);

if ($check_result->num_rows > 0) {
    $reset_data = $check_result->fetch_assoc();
    error_log("Debug - Reset Data: " . json_encode($reset_data));
    
    // Kiểm tra từng điều kiện riêng biệt
    $is_used = $reset_data['used'] == 0;
    $is_not_expired = strtotime($reset_data['expires_at']) > time();
    
    error_log("Debug - Is Used: " . ($is_used ? 'No' : 'Yes'));
    error_log("Debug - Expiry Time: " . $reset_data['expires_at']);
    error_log("Debug - Current Time: " . date('Y-m-d H:i:s'));
    error_log("Debug - Is Not Expired: " . ($is_not_expired ? 'True' : 'False'));

    if ($is_used && $is_not_expired) {
        $user_id = $reset_data['user_id'];
        
        $update_sql = "UPDATE users SET password = ? WHERE id = ? AND email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sis", $new_password, $user_id, $email);
        
        if ($update_stmt->execute()) {
            // Đánh dấu đã sử dụng
            $mark_used_sql = "UPDATE password_resets SET used = 1 WHERE email = ? AND id = ?";
            $mark_used_stmt = $conn->prepare($mark_used_sql);
            $mark_used_stmt->bind_param("si", $email, $reset_data['id']);
            $mark_used_stmt->execute();
            
            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Mật khẩu đã được cập nhật thành công.'
            ]);
        } else {
            error_log("Debug - Update Error: " . $update_stmt->error);
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Lỗi khi cập nhật mật khẩu.'
            ]);
        }
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => $is_used ? 'Mã xác nhận đã được sử dụng.' : 'Mã xác nhận đã hết hạn.'
        ]);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Không tìm thấy mã xác nhận cho email này.'
    ]);
}

$conn->close();
?> 