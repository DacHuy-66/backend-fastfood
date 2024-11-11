<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

// Lấy user_id và admin_id từ URL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$model_index = array_search('message', $path_parts);
$user_id = $path_parts[$model_index + 3] ?? null; // +3 vì có admin/create_message
$admin_id = $path_parts[$model_index + 4] ?? null;

// Lấy dữ liệu từ request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate dữ liệu đầu vào
if (!isset($user_id) || !isset($admin_id) || !isset($data['content'])) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Thiếu thông tin cần thiết!'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Kiểm tra user tồn tại
    $user_sql = "SELECT id FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $user_id);
    $user_stmt->execute();
    if ($user_stmt->get_result()->num_rows === 0) {
        throw new Exception('Người dùng không tồn tại!');
    }

    // Kiểm tra admin tồn tại
    $admin_sql = "SELECT id FROM admin WHERE id = ?";
    $admin_stmt = $conn->prepare($admin_sql);
    $admin_stmt->bind_param("s", $admin_id);
    $admin_stmt->execute();
    if ($admin_stmt->get_result()->num_rows === 0) {
        throw new Exception('Admin không tồn tại!');
    }

    // Tạo tin nhắn mới
    $message_sql = "INSERT INTO messages (user_id, admin_id, content, sender_type, status, created_at) 
                    VALUES (?, ?, ?, 'admin', false, NOW())";
    $message_stmt = $conn->prepare($message_sql);
    
    $message_stmt->bind_param("sss", 
        $user_id,
        $admin_id,
        $data['content']
    );

    if (!$message_stmt->execute()) {
        throw new Exception('Không thể tạo tin nhắn!');
    }

    // Lấy thông tin tin nhắn vừa tạo
    $message_id = $message_stmt->insert_id;
    
    $get_message_sql = "SELECT m.*, u.username, u.avata, a.username as admin_name
                        FROM messages m
                        LEFT JOIN users u ON m.user_id = u.id
                        LEFT JOIN admin a ON m.admin_id = a.id
                        WHERE m.id = ?";
    $get_message_stmt = $conn->prepare($get_message_sql);
    $get_message_stmt->bind_param("i", $message_id);
    $get_message_stmt->execute();
    $message = $get_message_stmt->get_result()->fetch_assoc();

    // Format response
    $response_data = [
        'id' => $message['id'],
        'content' => $message['content'],
        'sender_type' => $message['sender_type'],
        'status' => $message['status'],
        'created_at' => $message['created_at'],
        'user' => [
            'id' => $message['user_id'],
            'username' => $message['username'],
            'avatar' => $message['avata']
        ],
        'admin' => [
            'id' => $message['admin_id'],
            'username' => $message['admin_name']
        ]
    ];

    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Tạo tin nhắn thành công!',
        'data' => $response_data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage()
    ]);
    http_response_code(500);
}

$conn->close(); 