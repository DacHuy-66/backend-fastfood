<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// lấy api key từ header
$headers = apache_request_headers();
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// giả sử $id_user được nhận từ client (ví dụ: yêu cầu POST hoặc GET)
$id_user = isset($id_user) ? $id_user : null;

// kiểm tra xem cả api key và id user được cung cấp không
if (!$api_key || !$id_user) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key hoặc ID user không được cung cấp.'
    ]);
    http_response_code(400);
    exit;  
}

// kiểm tra api key và id user
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND api_key = ?");
$stmt->bind_param("is", $id_user, $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // đọc dữ liệu yêu cầu PUT (giả sử đó là JSON)
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);  

    // lấy dữ liệu mới từ dữ liệu đã phân tích
    $new_username = isset($data['username']) ? $data['username'] : null;
    // $new_phone = isset($data['phone']) ? $data['phone'] : null;
    // $new_address = isset($data['address']) ? $data['address'] : null;
    $new_avata = isset($data['avata']) ? $data['avata'] : null;


    // kiểm tra xem tất cả các trường bắt buộc có tồn tại không
    if ($new_username  && $new_avata) {
        // chuẩn bị câu truy vấn cập nhật
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, avata=? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_username, $new_avata, $id_user);

        if ($update_stmt->execute()) {
        echo json_encode([
        'ok' => True,
        'success' => True,
        'message' => 'Dữ liệu người dùng đã cập nhật thành công.'
        ]);
    http_response_code(response_code: 200);

        } else {
            echo "Failed to update user data: " . $update_stmt->error;
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key không hợp lệ.'
    ]);
    http_response_code(response_code: 404);

        }
        $update_stmt->close();
    } else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Các trường bắt buộc bị thiếu.'
    ]);
        http_response_code(response_code: 500);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key hoặc ID user không hợp lệ.'
    ]);
    http_response_code(response_code: 404);
}

$stmt->close();
$conn->close();
?>
