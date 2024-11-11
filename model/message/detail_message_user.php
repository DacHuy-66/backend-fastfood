<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

// Lấy user_id từ URL
$url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($url_path, '/'));
$model_index = array_search('detail_message_user', $path_parts);
$user_id = $path_parts[$model_index + 1] ?? null;

if (!isset($user_id)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'User ID bị thiếu!'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Kiểm tra user tồn tại
    $user_sql = "SELECT id, username, avata FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception('Người dùng không tồn tại!');
    }
    
    $user = $user_result->fetch_assoc();

    // Lấy tin nhắn
    $messages_sql = "SELECT m.*, a.username as admin_name 
                    FROM messages m
                    LEFT JOIN admin a ON m.admin_id = a.id
                    WHERE m.user_id = ?
                    ORDER BY m.created_at ASC";
    
    $messages_stmt = $conn->prepare($messages_sql);
    $messages_stmt->bind_param("s", $user_id);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();

    $messages = [];
    while ($message = $messages_result->fetch_assoc()) {
        $messages[] = [
            'id' => $message['id'],
            'content' => $message['content'],
            'sender_type' => $message['sender_type'],
            'status' => (bool)$message['status'],
            'created_at' => $message['created_at'],
            'admin' => $message['sender_type'] === 'admin' ? [
                'id' => $message['admin_id'],
                'username' => $message['admin_name']
            ] : null
        ];
    }

    // Cập nhật status thành đã đọc cho tin nhắn từ user
    $update_sql = "UPDATE messages 
                   SET status = 0 
                   WHERE user_id = ? 
                   AND sender_type = 'user' 
                   AND status = 1";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $user_id);
    $update_stmt->execute();

    echo json_encode([
        'ok' => true,
        'success' => true,
        'message' => 'Lấy tin nhắn thành công!',
        'data' => [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avata']
            ],
            'messages' => $messages
        ]
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
