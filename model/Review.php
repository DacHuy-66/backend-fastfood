<?php

class Review {
    private $conn;
    private $table = 'reviews';

    public $id;
    public $user_id;
    public $product_id;
    public $rating;
    public $comment;
    public $created_at;


    function generateRandomId($length = 24) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET user_id = ?, 
                    product_id = ?, 
                    rating = ?, 
                    comment = ?, 
                    created_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        $stmt->bind_param("iiis", 
            $this->user_id, 
            $this->product_id, 
            $this->rating, 
            $this->comment
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Đọc đánh giá với phân trang
    public function read($page = 1, $limit = 10, $product_id = null) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT 
                    r.id,
                    r.user_id,
                    r.product_id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    u.username,
                    p.name as product_name
                FROM " . $this->table . " r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id ";
        
        // Thêm bộ lọc sản phẩm nếu được cung cấp
        if ($product_id) {
            $query .= "WHERE r.product_id = ? ";
        }
        
        // Thêm thứ tự và phân trang
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

    // Nhận tổng số lượt đánh giá
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

    // Nhận đánh giá duy nhất
    public function show($id) {
        $query = "SELECT 
                    r.id,
                    r.user_id,
                    r.product_id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    u.username,
                    p.name as product_name
                FROM " . $this->table . " r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("i", $id);

        $stmt->execute();
        return $stmt->get_result();
    }

    // cập nhật review
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET rating = ?,
                    comment = ?
                WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);

        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        $stmt->bind_param("isii",
            $this->rating,
            $this->comment,
            $this->id,
            $this->user_id
        );


        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // xóa review
    public function delete() {

        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        $stmt->bind_param("ii", $this->id, $this->user_id);

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Lấy rating trung bình cho một sản phẩm
    public function getProductAverageRating($product_id) {
        $query = "SELECT AVG(rating) as average_rating 
                FROM " . $this->table . " 
                WHERE product_id = ?";

        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("i", $product_id);

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['average_rating'] ? round($row['average_rating'], 1) : 0;
    }

    // Nhận phân phối xếp hạng cho một sản phẩm
    public function getProductRatingDistribution($product_id) {
        $query = "SELECT rating, COUNT(*) as count 
                FROM " . $this->table . " 
                WHERE product_id = ? 
                GROUP BY rating 
                ORDER BY rating DESC";

        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("i", $product_id);

        $stmt->execute();
        return $stmt->get_result();
    }
}
?>