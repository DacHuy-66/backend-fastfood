<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../model/Product.php';

$product = new Product($conn);

// Helper Functions
function generateRandomId($length = 24) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function convertToWebUrl($path) {
    if (strpos($path, 'file:///') === 0) {
        $relativePath = str_replace('file:///D:/xampp/htdocs/', '', $path);
        return 'http://localhost/' . $relativePath;
    }
    return $path;
}

function validateNumeric($value, $fieldName) {
    if (isset($value) && !is_numeric($value)) {
        throw new Exception("Invalid {$fieldName}. Must be a numeric value.");
    }
}

function validateStatus($status) {
    if (isset($status) && !in_array((int)$status, [0, 1])) {
        throw new Exception('Invalid status. Must be 0 (inactive) or 1 (active).');
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path_parts = explode('/', $path);
            $product_id = end($path_parts);

            if ($product_id && $product_id !== 'Product_API.php') {
                $result = $product->show($product_id);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $row['_id'] = generateRandomId(); // Add _id field
                    
                    // Calculate average rating
                    $avg_rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?";
                    $avg_rating_stmt = $conn->prepare($avg_rating_query);
                    $avg_rating_stmt->bind_param("s", $product_id);
                    $avg_rating_stmt->execute();
                    $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
                    $average_rating = $avg_rating_result['average_rating'] !== null ? (float)$avg_rating_result['average_rating'] : 0;
                    
                    // Fetch reviews
                    $reviews_query = "SELECT * FROM reviews WHERE product_id = ?";
                    $reviews_stmt = $conn->prepare($reviews_query);
                    $reviews_stmt->bind_param("s", $product_id);
                    $reviews_stmt->execute();
                    $reviews_result = $reviews_stmt->get_result();
                    
                    $reviews = [];
                    while ($review = $reviews_result->fetch_assoc()) {
                        $reviews[] = $review;
                    }
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Product retrieved successfully',
                        'code' => 200,
                        'data' => array_merge($row, [
                            'average_rating' => $average_rating,
                            'reviews' => $reviews
                        ])
                    ];
                    http_response_code(200);
                } else {
                    throw new Exception('Product not found', 404);
                }
            } else {
                // Xử lý danh sách sản phẩm
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                if ($page <= 0 || $limit <= 0) {
                    throw new Exception('Page and limit must be positive integers', 400);
                }
                
                $result = $product->read($page, $limit);
                $total_products = $product->getTotalCount();
                $products_arr = [];
                
                while ($row = $result->fetch_assoc()) {
                    $avg_rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE product_id = ?";
                    $avg_rating_stmt = $conn->prepare($avg_rating_query);
                    $avg_rating_stmt->bind_param("s", $row['id']); // Đổi kiểu tham số từ i sang s
                    $avg_rating_stmt->execute();
                    $avg_rating_result = $avg_rating_stmt->get_result()->fetch_assoc();
                    $average_rating = $avg_rating_result['average_rating'] !== null ? (float)$avg_rating_result['average_rating'] : 0;
                    
                    $row['image_url'] = convertToWebUrl($row['image_url']);
                    $row['_id'] = generateRandomId();
                    $row['average_rating'] = $average_rating;
                    $products_arr[] = $row;
                }
                
                $response = [
                    'status' => 'success',
                    'message' => 'Products retrieved successfully',
                    'code' => 200,
                    'data' => [
                        'products' => $products_arr,
                        'pagination' => [
                            'total' => $total_products,
                            'count' => count($products_arr),
                            'per_page' => $limit,
                            'current_page' => $page,
                            'total_pages' => ceil($total_products / $limit)
                        ]
                    ]
                ];
                http_response_code(200);
            }
            break;

            
        default:
            throw new Exception('Method Not Allowed', 405);
    }
} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ? $e->getCode() : 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ? $e->getCode() : 400);
}

echo json_encode($response);
?>