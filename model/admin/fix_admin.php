<?php
include_once __DIR__ . '/../../config/db.php';


// Lấy API key từ header
$headers = apache_request_headers();

$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_uri, '/'));
$admin_id = end($path_parts);
// Kiểm tra xem ID admin có được cung cấp hay không
if (empty($admin_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID admin không được cung cấp!'
    ]);
    http_response_code(400);
    exit;
}

// Kiểm tra ID admin
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    $admin_id = $admin_data['id'];

    // Admin đã xác thực, tiến hành cập nhật dữ liệu
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Lấy dữ liệu mới từ dữ liệu được phân tích
    $updates = [];
    $types = "";
    $values = [];

    // Chuẩn bị các trường cập nhật động
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

    if (isset($data['order'])) {
        $updates[] = "`order` = ?";
        $types .= "i";
        $values[] = (int)$data['order'];
    }

    if (isset($data['mess'])) {
        $updates[] = "mess = ?";
        $types .= "i";
        $values[] = (int)$data['mess'];
    }

    if (isset($data['statistics'])) {
        $updates[] = "statistics = ?";
        $types .= "i";
        $values[] = (int)$data['statistics'];
    }

    if (isset($data['user'])) {
        $updates[] = "user = ?";
        $types .= "i";
        $values[] = (int)$data['user'];
    }

    if (isset($data['note'])) {
        $updates[] = "note = ?";
        $types .= "s";
        $values[] = $data['note'];
    }
    if (isset($data['product'])) {
        $updates[] = "product = ?";
        $types .= "i";
        $values[] = (int)$data['product'];
    }
    if (isset($data['discount'])) {
        $updates[] = "discount = ?";
        $types .= "i";
        $values[] = (int)$data['discount'];
    }
    if (isset($data['review'])) {
        $updates[] = "review = ?";
        $types .= "i";
        $values[] = (int)$data['review'];
    }
    if (isset($data['layout'])) {
        $updates[] = "layout = ?";
        $types .= "i";
        $values[] = (int)$data['layout'];
    }
    if (isset($data['decentralization'])) {
        $updates[] = "decentralization = ?";
        $types .= "i";
        $values[] = (int)$data['decentralization'];
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

    // Thêm thời gian cập nhật
    $updates[] = "time = NOW()";

    // Tạo câu truy vấn SQL
    $sql = "UPDATE admin SET " . implode(", ", $updates) . " WHERE id = ?";

    // Thêm ID admin vào giá trị và kiểu
    $values[] = $admin_id;
    $types .= "s";

    // Chuẩn bị và thực thi cập nhật
    $update_stmt = $conn->prepare($sql);

    // Chuẩn bị và thực thi cập nhật
    $update_stmt->bind_param($types, ...$values);

    if ($update_stmt->execute()) {
        // Lấy dữ liệu admin đã cập nhật
        $select_stmt = $conn->prepare("SELECT id, username, email, 'order', mess, 'statistics', user, product, discount, review, layout, decentralization, note, time FROM admin WHERE id = ?");
        $select_stmt->bind_param("s", $admin_id);
        $select_stmt->execute();
        $updated_admin = $select_stmt->get_result()->fetch_assoc();

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật dữ liệu thành công!',
            'data' => [
                'id' => $updated_admin['id'],
                'username' => $updated_admin['username'],
                'email' => $updated_admin['email'],
                'roles' => [
                    'order' => (bool)$updated_admin['order'],
                    'mess' => (bool)$updated_admin['mess'],
                    'statistics' => (bool)$updated_admin['statistics'],
                    'user' => (bool)$updated_admin['user'],
                    'product' => (bool)$updated_admin['product'],
                    'discount' => (bool)$updated_admin['discount'],
                    'review' => (bool)$updated_admin['review'],
                    'layout' => (bool)$updated_admin['layout'],
                    'decentralization' => (bool)$updated_admin['decentralization']
                ],
                'note' => $updated_admin['note'],
                'time' => $updated_admin['time']
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
