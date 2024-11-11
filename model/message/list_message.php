<?php

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy API key từ header
    $headers = getallheaders();
    $api_key = $headers['X-Api-Key'] ?? '';

    if (empty($api_key)) {
        throw new Exception('API key bị thiếu!', 401);
    }

    // Kiểm tra user tồn tại qua API key
    $user_sql = "SELECT id FROM users WHERE api_key = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $api_key);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        throw new Exception('API key không hợp lệ!', 401);
    }

    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];

    // Query lấy tin nhắn theo user_id
    $messages_sql = "SELECT m.*, u.username, u.avata, a.username as admin_name
                    FROM messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    LEFT JOIN admin a ON m.admin_id = a.id
                    WHERE m.user_id = ?
                    ORDER BY m.created_at DESC";
    
    $messages_stmt = $conn->prepare($messages_sql);
    $messages_stmt->bind_param("s", $user_id);
    $messages_stmt->execute();
    $messages_result = $messages_stmt->get_result();

    // Nếu không có tin nhắn, trả về mảng rỗng nhưng không báo lỗi
    $messages = [];
    while ($message = $messages_result->fetch_assoc()) {
        $messages[] = [
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
            'admin' => $message['admin_id'] ? [
                'id' => $message['admin_id'],
                'username' => $message['admin_name']
            ] : null
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy danh sách tin nhắn thành công',
        'data' => $messages
    ];

    echo json_encode($response);
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ];

    echo json_encode($response);
}
