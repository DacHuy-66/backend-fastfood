<?php
include_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];

// Kiểm tra email có tồn tại trong hệ thống và lấy username
$sql = "SELECT id, username FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Tạo mã reset code 6 số ngẫu nhiên
    $reset_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Thời gian hết hạn (15 phút từ hiện tại)
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Lưu thông tin vào bảng password_resets
    $insert_sql = "INSERT INTO password_resets (user_id, email, reset_code, expires_at) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ssss", $user['id'], $email, $reset_code, $expires_at);
    
    if ($insert_stmt->execute()) {
        // Cấu hình PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Enable verbose debug output
            $mail->isSMTP();                     // Sử dụng SMTP
            $mail->Host       = 'smtp.gmail.com'; // SMTP server của Gmail
            $mail->SMTPAuth   = true;            // Enable SMTP authentication
            $mail->Username   = 'nguyenhuy30496@gmail.com'; // SMTP username
            $mail->Password   = 'onlladmzmgkgcnmn';    // SMTP password (App Password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS
            $mail->Port       = 465;             // TCP port (465 for SSL)
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom('nguyenhuy30496@gmail.com', 'FastFood');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Mã xác nhận đặt lại mật khẩu';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #333;'>Kính gửi " . $user['username'] . ",</h2>
                    
                    <p>Cảm ơn bạn đã liên hệ với chúng tôi!</p>

                    <p>Chúng tôi đã nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn. Để giúp bạn lấy lại quyền truy cập, vui lòng sử dụng mã code dưới đây:</p>

                    <div style='background: #f5f5f5; padding: 15px; text-align: center; margin: 20px 0;'>
                        <h3 style='color: #e53935; margin: 0;'>Mã Code: <b>$reset_code</b></h3>
                    </div>

                    <p><i>Mã code này có hiệu lực trong vòng 30 phút. Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</i></p>

                    <p>Nếu bạn gặp bất kỳ vấn đề nào trong quá trình khôi phục mật khẩu, đừng ngần ngại liên hệ với chúng tôi.</p>

                    <p>Cảm ơn bạn đã sử dụng dịch vụ của FastFood!</p>

                    <div style='margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;'>
                        <p style='margin: 0;'><b>Trân trọng,</b></p>
                        <p style='margin: 5px 0;'>Đội ngũ hỗ trợ FastFood</p>
                        <p style='margin: 0;'><a href='#' style='color: #1976d2; text-decoration: none;'>[Website FastFood]</a></p>
                        <p style='margin: 5px 0;'>Email: fastfood@gmail.com</p>
                    </div>
                </div>
            ";

            $mail->send();
            
            echo json_encode([
                'ok' => true,
                'success' => true,
                'message' => 'Mã xác nhận đã được gửi đến email của bạn.',
                'reset_code' => $reset_code
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Không thể gửi email: ' . $mail->ErrorInfo
            ]);
        }
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi lưu thông tin đặt lại mật khẩu.'
        ]);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Email không tồn tại trong hệ thống.'
    ]);
}

$conn->close();
?>
