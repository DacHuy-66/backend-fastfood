<?php
include_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Kiểm tra dữ liệu đầu vào
    if (!isset($data['user_id']) || !isset($data['product_id'])) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Thiếu thông tin user_id hoặc product_id'
        ]);
        exit;
    }

    $user_id = trim($data['user_id']);
    $product_id = trim($data['product_id']);

    try {
        // Kiểm tra xem đã tồn tại trong favorites chưa
        $sql_check = "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $user_id, $product_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode([
                'ok' => false,
                'success' => false,
                'message' => 'Sản phẩm đã có trong danh sách yêu thích'
            ]);
            exit;
        }
        $stmt_check->close();

        // Thêm vào favorites
        $sql_insert = "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ss", $user_id, $product_id);
        $stmt_insert->execute();
        $favorite_id = $conn->insert_id;
        $stmt_insert->close();

        // Lấy thông tin sản phẩm vừa thêm vào favorites
        $sql_product = "SELECT 
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
                        WHERE f.id = ?";
        
        $stmt_product = $conn->prepare($sql_product);
        $stmt_product->bind_param("i", $favorite_id);
        $stmt_product->execute();
        $product_result = $stmt_product->get_result()->fetch_assoc();
        $stmt_product->close();

        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Thêm vào yêu thích thành công',
            'data' => [
                'favorite_id' => $product_result['favorite_id'],
                'user_id' => $product_result['user_id'],
                'product' => [
                    'id' => $product_result['product_id'],
                    'name' => $product_result['product_name'],
                    'price' => $product_result['price'],
                    'description' => $product_result['description'],
                    'image_url' => $product_result['image_url'],
                    'type' => $product_result['type']
                ],
                'created_at' => $product_result['created_at']
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Lỗi khi thêm vào yêu thích: ' . $e->getMessage()
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