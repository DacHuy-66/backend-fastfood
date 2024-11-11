<?php
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../utils/helpers.php';

Headers();

try {
    // Lấy danh sách users có tin nhắn
    $users_sql = "SELECT DISTINCT 
                    u.id, 
                    u.username, 
                    u.avata,
                    (SELECT COUNT(*) 
                     FROM messages m2 
                     WHERE m2.user_id = u.id 
                       AND m2.status = 1
                       AND m2.sender_type = 'user') as unread_count
                FROM users u
                INNER JOIN messages m ON u.id = m.user_id
                ORDER BY u.username ASC";
    
    $users_result = $conn->query($users_sql);
    
    $users = [];
    while ($user = $users_result->fetch_assoc()) {
        $users[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'avatar' => $user['avata'],
            'unread_count' => (int)$user['unread_count']
        ];
    }

    $response = [
        'ok' => true,
        'success' => true,
        'message' => 'Lấy danh sách người dùng thành công',
        'data' => [
            'users' => $users
        ]
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
