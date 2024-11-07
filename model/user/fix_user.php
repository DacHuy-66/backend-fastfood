<?php
include_once __DIR__ . '/../../config/db.php';

// Lấy ID user từ URL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$user_id = end($path_parts);

if (empty($user_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID người dùng không được cung cấp!'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra xem user có tồn tại không
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $user_id = $user_data['id'];

    // Xử lý dữ liệu cập nhật
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    $updates = [];
    $types = "";
    $values = [];

    // Chuẩn bị cập nhật trường động
    if (isset($data['username'])) {
        $updates[] = "username = ?";
        $types .= "s";
        $values[] = $data['username'];
    }

    if (isset($data['email'])) {
        $updates[] = "email = ?";
        $types .= "s";
        $values[] = $data['email'];
    }

    if (isset($data['password'])) {
        $updates[] = "password = ?";
        $types .= "s";
        $values[] = $data['password'];
    }

    if (isset($data['role'])) {
        $updates[] = "role = ?";
        $types .= "i";
        $values[] = (int)$data['role'];
    }

    if (isset($data['avata'])) {
        $updates[] = "avata = ?";
        $types .= "s";
        $values[] = $data['avata'];
    }

    if (empty($updates)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không có trường nào để cập nhật được cung cấp!'
        ]);
        http_response_code(400);
        exit;
    }

    // Tạo câu truy vấn SQL
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";

    // Thêm ID user vào values và types
    $values[] = $user_id;
    $types .= "s";

    // Chuẩn bị và thực thi cập nhật
    $update_stmt = $conn->prepare($sql);
    $update_stmt->bind_param($types, ...$values);

    if ($update_stmt->execute()) {
        // Lấy dữ liệu user đã cập nhật
        $select_stmt = $conn->prepare("SELECT id, username, email, role, avata, created_at FROM users WHERE id = ?");
        $select_stmt->bind_param("s", $user_id);
        $select_stmt->execute();
        $updated_user = $select_stmt->get_result()->fetch_assoc();

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật dữ liệu thành công!',
            'data' => [
                'id' => $updated_user['id'],
                'username' => $updated_user['username'],
                'email' => $updated_user['email'],
                'role' => (int)$updated_user['role'],
                'avata' => $updated_user['avata'],
                'created_at' => $updated_user['created_at']
            ]
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Cập nhật không thành công: ' . $update_stmt->error
        ]);
        http_response_code(500);
    }
    $update_stmt->close();
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID không hợp lệ!'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
