<?php
include_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lấy user_id từ URL
    $url_parts = explode('/', $_SERVER['REQUEST_URI']);
    $user_id = end($url_parts);

    if (empty($user_id)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Thiếu user_id'
        ]);
        exit;
    }

    try {
        // Query để lấy danh sách sản phẩm yêu thích của user
        $sql = "SELECT 
                    f.id as favorite_id,
                    f.user_id,
                    f.product_id,
                    f.created_at,
                    p.name as product_name,
                    p.price,
                    p.description,
                    p.image_url,
                    p.type
                FROM favorites f
                JOIN products p ON f.product_id = p.id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            $favorites[] = [
                'favorite_id' => $row['favorite_id'],
                'user_id' => $row['user_id'],
                'product' => [
                    'id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'price' => $row['price'],
                    'description' => $row['description'],
                    'image_url' => $row['image_url'],
                    'type' => $row['type']
                ],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Lấy danh sách yêu thích thành công',
            'data' => [
                'total' => count($favorites),
                'favorites' => $favorites
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi lấy danh sách yêu thích: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}

$conn->close();
?>
