<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../model/Product.php';
include_once __DIR__ . '/../../utils/helpers.php';  

$product = new Product($conn);

try {
    // Lấy các tham số phân trang từ chuỗi truy vấn
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3; 
    $offset = ($page - 1) * $limit;

    $product_id = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
    $result = $product->show($product_id);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $row['status'] = $row['quantity'] > 0;
        $row['status'] = (bool)$row['status'];
        $row['lock'] = (bool)$row['lock'];

        
        // tính average_rating
        $avg_rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?";
        $avg_rating_stmt = $conn->prepare($avg_rating_query);
        $avg_rating_stmt->bind_param("s", $product_id);
        $avg_rating_stmt->execute();
        $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
        $average_rating = $avg_rating_result['average_rating'] !== null ? (float)$avg_rating_result['average_rating'] : 0;
        
        // Lấy tổng số lượng bản ghi
        $total_query = "SELECT COUNT(*) as total FROM reviews WHERE product_id = ?";
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->bind_param("s", $product_id);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result()->fetch_assoc();
        $total_reviews = (int)$total_result['total'];
        
        // Tính tổng số trang
        $total_pages = ceil($total_reviews / $limit);
        
        // Lấy bản ghi phân trang với thông tin người dùng
        $reviews_query = "
            SELECT r.*, u.username, u.avata
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $reviews_stmt = $conn->prepare($reviews_query);
        $reviews_stmt->bind_param("sii", $product_id, $limit, $offset);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();
        
        $reviews = [];
        while ($review = $reviews_result->fetch_assoc()) {
            $reviews[] = [
                'id' => $review['id'],
                'username' => $review['username'],
                'avata' => $review['avata'],
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'image_1' => $review['image_1'] ?? null,
                'image_2' => $review['image_2'] ?? null,
                'image_3' => $review['image_3'] ?? null,
                'created_at' => $review['created_at']
            ];
        }
        
        $response = [
            'ok' => true,
            'status' => 'success',
            'message' => 'Lấy sản phẩm thành công',
            'code' => 200,
            'data' => array_merge($row, [
                'average_rating' => $average_rating,
                'reviews' => [
                    'items' => $reviews,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $total_pages,
                        'total_items' => $total_reviews,
                        'limit' => $limit
                    ]
                ]
            ])
        ];
        http_response_code(200);
    } else {
        throw new Exception('Không tìm thấy sản phẩm', 404);
    }
} catch (Exception $e) {
    $response = [
        'ok' => false,
        'code' => $e->getCode() ? $e->getCode() : 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ? $e->getCode() : 400);
}

echo json_encode($response);