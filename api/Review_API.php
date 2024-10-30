<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Kiểm tra xem URL có phân đoạn bổ sung không hợp lệ hay không
$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/WebDoAn/api/Review_API.php';

if ($current_url !== $base_path) {
    $response = [
        'code' => 400,
        'status_code' => 'FAILED',
        'message' => 'Có lỗi. Vui lòng thử lại.'
    ];
    http_response_code(400);
    echo json_encode($response);
    exit;
}

 include_once __DIR__ . '/../config/db.php';

class Review {
    private $conn;
    private $table = 'reviews';

    public $id;
    public $user_id;
    public $product_id;
    public $rating;
    public $comment;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                (user_id, product_id, rating, comment, created_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $this->user_id,
            $this->product_id,
            $this->rating,
            $this->comment
        ]);
    }

    // Read Reviews (with pagination)
    public function read($page = 1, $limit = 10, $product_id = null) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT r.*, u.username 
                FROM " . $this->table . " r
                LEFT JOIN users u ON r.user_id = u.id ";
                
        if ($product_id) {
            $query .= "WHERE r.product_id = ? ";
        }
        
        $query .= "ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        
        if ($product_id) {
            $stmt->bind_param("iii", $product_id, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get Total Count
    public function getTotalCount($product_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if ($product_id) {
            $query .= " WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $product_id);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Show Single Review
    public function show($id) {
        $query = "SELECT r.*, u.username 
                FROM " . $this->table . " r
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.id = ?";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update Review
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET rating = ?,
                    comment = ?
                WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $this->rating,
            $this->comment,
            $this->id,
            $this->user_id
        ]);
    }

    // Delete Review
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->id, $this->user_id]);
    }
}

// Create review instance
$review = new Review($conn);

try {
    // Get HTTP method
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $review_id = $_GET['id'];
                if (!filter_var($review_id, FILTER_VALIDATE_INT)) {
                    throw new Exception('Invalid review ID format', 400);
                }
                
                $result = $review->show($review_id);
                if ($result->num_rows > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Review retrieved successfully',
                        'code' => 200,
                        'data' => $result->fetch_assoc()
                    ];
                    http_response_code(200);
                } else {
                    throw new Exception('Review not found', 404);
                }
            } else {
                $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
                
                if ($page <= 0 || $limit <= 0) {
                    throw new Exception('Page and limit must be positive integers', 400);
                }
                
                $result = $review->read($page, $limit, $product_id);
                $total_reviews = $review->getTotalCount($product_id);
                $reviews_arr = [];
                
                while ($row = $result->fetch_assoc()) {
                    $reviews_arr[] = $row;
                }
                
                $response = [
                    'status' => 'success',
                    'message' => 'Reviews retrieved successfully',
                    'code' => 200,
                    'data' => [
                        'reviews' => $reviews_arr,
                        'pagination' => [
                            'total' => $total_reviews,
                            'count' => count($reviews_arr),
                            'per_page' => $limit,
                            'current_page' => $page,
                            'total_pages' => ceil($total_reviews / $limit)
                        ]
                    ]
                ];
                http_response_code(200);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            
            if (isset($data->id)) {
                // Update existing review
                if (!isset($data->user_id)) {
                    throw new Exception('User ID is required for updating review', 400);
                }
                
                $review->id = $data->id;
                $review->user_id = $data->user_id;
                $result = $review->show($review->id);
                
                if ($result->num_rows === 0) {
                    throw new Exception('Review not found', 404);
                }
                
                $current_review = $result->fetch_assoc();
                if ($current_review['user_id'] != $data->user_id) {
                    throw new Exception('Unauthorized to update this review', 403);
                }
                
                if (!isset($data->rating) || !is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
                    throw new Exception('Invalid rating. Must be between 1 and 5', 400);
                }
                
                $review->rating = $data->rating;
                $review->comment = $data->comment ?? '';
                
                if ($review->update()) {
                    $updated = $review->show($review->id)->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'Review updated successfully',
                        'code' => 200,
                        'data' => $updated
                    ];
                    http_response_code(200);
                } else {
                    throw new Exception('Failed to update review', 500);
                }
            } else {
                // Create new review
                if (!isset($data->user_id) || !isset($data->product_id) || !isset($data->rating)) {
                    throw new Exception('Missing required fields: user_id, product_id, and rating are required', 400);
                }
                
                if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
                    throw new Exception('Invalid rating. Must be between 1 and 5', 400);
                }
                
                $review->user_id = $data->user_id;
                $review->product_id = $data->product_id;
                $review->rating = $data->rating;
                $review->comment = $data->comment ?? '';
                
                if ($review->create()) {
                    $new_review_id = $conn->insert_id;
                    $created = $review->show($new_review_id)->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'Review created successfully',
                        'code' => 201,
                        'data' => $created
                    ];
                    http_response_code(201);
                } else {
                    throw new Exception('Failed to create review', 500);
                }
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"));
            if (!isset($data->id) || !isset($data->user_id)) {
                throw new Exception('Review ID and User ID are required', 400);
            }
            
            if (!filter_var($data->id, FILTER_VALIDATE_INT)) {
                throw new Exception('Invalid review ID format', 400);
            }
            
            $review->id = $data->id;
            $review->user_id = $data->user_id;
            
            $result = $review->show($data->id);
            if ($result->num_rows === 0) {
                throw new Exception('Review not found', 404);
            }
            
            $review_details = $result->fetch_assoc();
            if ($review_details['user_id'] != $data->user_id) {
                throw new Exception('Unauthorized to delete this review', 403);
            }
            
            if ($review->delete()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Review deleted successfully',
                    'code' => 200,
                    'data' => [
                        'id' => $review_details['id'],
                        'product_id' => $review_details['product_id'],
                        'user_id' => $review_details['user_id']
                    ]
                ];
                http_response_code(200);
            } else {
                throw new Exception('Failed to delete review', 500);
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