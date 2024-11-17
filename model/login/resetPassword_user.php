<?php
include_once __DIR__ . '/../../config/db.php';

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$new_password = trim($data['new_password'] ?? '');
$email = trim(strtolower($data['email'] ?? ''));

// Validate input
if (empty($new_password) || empty($email)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Dữ liệu đầu vào không hợp lệ.'
    ]);
    exit;
}

// Debug input data
error_log("Debug Input - Email: " . $email);
error_log("Debug Input - Password length: " . strlen($new_password));

try {
    // Kiểm tra email trong bảng password_resets
    $check_sql = "SELECT * FROM password_resets WHERE email = ? AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $email);
    if (!$check_stmt->execute()) {
        throw new Exception("Execute failed: " . $check_stmt->error);
    }
    
    $check_result = $check_stmt->get_result();
    error_log("Debug - Total records found: " . $check_result->num_rows);

    if ($check_result->num_rows > 0) {
        $reset_data = $check_result->fetch_assoc();
        error_log("Debug - Reset Data: " . json_encode($reset_data));

        // Kiểm tra thời hạn
        if (strtotime($reset_data['expires_at']) > time()) {
            // Begin transaction
            $conn->begin_transaction();

            try {
                // Cập nhật mật khẩu
                $update_sql = "UPDATE users SET password = ? WHERE id = ? AND email = ?";
                $update_stmt = $conn->prepare($update_sql);
                if (!$update_stmt) {
                    throw new Exception("Prepare update statement failed: " . $conn->error);
                }

                $user_id = $reset_data['user_id'];
                $update_stmt->bind_param("sss", $new_password, $user_id, $email);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Update failed: " . $update_stmt->error);
                }

                if ($update_stmt->affected_rows === 0) {
                    throw new Exception("No rows were updated in users table");
                }

                // Đánh dấu token đã sử dụng
                $mark_used_sql = "UPDATE password_resets SET used = 1 WHERE id = ?";
                $mark_used_stmt = $conn->prepare($mark_used_sql);
                if (!$mark_used_stmt) {
                    throw new Exception("Prepare mark used statement failed: " . $conn->error);
                }

                $mark_used_stmt->bind_param("i", $reset_data['id']);
                if (!$mark_used_stmt->execute()) {
                    throw new Exception("Mark used failed: " . $mark_used_stmt->error);
                }

                // Commit transaction
                $conn->commit();

                echo json_encode([
                    'ok' => true,
                    'success' => true,
                    'message' => 'Mật khẩu đã được cập nhật thành công.'
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                error_log("Transaction failed: " . $e->getMessage());
                echo json_encode([
                    'ok' => false,
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật mật khẩu: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Mã xác nhận đã hết hạn.'
            ]);
        }
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không tìm thấy mã xác nhận hợp lệ cho email này.'
        ]);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}

$conn->close();