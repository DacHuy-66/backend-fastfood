<?php
// config/db.php
$servername = "localhost";
$port = "3308";
$username = "root";
$password = "";
$dbname = "fastfoot";

try {
    // Kiểm tra kết nối MySQL
    $socket = @fsockopen($servername, $port, $errno, $errstr, 5);
    if (!$socket) {
        throw new Exception("MySQL server không hoạt động hoặc port $port bị chặn. Vui lòng kiểm tra XAMPP.");
    }
    @fclose($socket);

    // Thử kết nối database
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    if ($conn->connect_error) {
        $error_message = "";
        
        // Xác định loại lỗi cụ thể
        if (strpos($conn->connect_error, "Unknown database") !== false) {
            $error_message = "Database '$dbname' không tồn tại. Vui lòng kiểm tra lại tên database.";
        } 
        else if (strpos($conn->connect_error, "Access denied") !== false) {
            $error_message = "Sai tài khoản hoặc mật khẩu MySQL.";
        }
        else if (strpos($conn->connect_error, "Connection refused") !== false) {
            $error_message = "Không thể kết nối đến MySQL trên port $port. Vui lòng kiểm tra:
                1. MySQL đang chạy trong XAMPP
                2. Port $port đúng và đang được sử dụng
                3. Không có ứng dụng nào đang chặn port này";
        }
        else {
            $error_message = "Lỗi kết nối database: " . $conn->connect_error;
        }

        header('Content-Type: application/json');
        $response = [
            'code' => 500,
            'status_code' => 'FAILED',
            'message' => $error_message,
            'details' => [
                'server' => $servername,
                'port' => $port,
                'database' => $dbname,
                'time' => date('Y-m-d H:i:s')
            ]
        ];
        die(json_encode($response));
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    header('Content-Type: application/json');
    $response = [
        'code' => 500,
        'status_code' => 'FAILED', 
        'message' => $e->getMessage(),
        'details' => [
            'server' => $servername,
            'port' => $port,
            'database' => $dbname,
            'time' => date('Y-m-d H:i:s')
        ]
    ];
    die(json_encode($response));
}
?>