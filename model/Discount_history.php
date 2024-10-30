<?php
// File: model/DiscountHistory.php

class DiscountHistory {
    // Database connection and table name
    private $conn;
    private $table = 'discount_history';

    // Object properties
    public $id;
    public $user_id;
    public $status;
    public $datetime;
    public $code;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create discount history record
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                SET user_id = ?,
                    status = ?,
                    datetime = CURRENT_TIMESTAMP,
                    code = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->code = htmlspecialchars(strip_tags($this->code));

        // Bind data
        $stmt->bind_param("iss", 
            $this->user_id,
            $this->status,
            $this->code
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read discount history with pagination
    public function read($page = 1, $limit = 10, $user_id = null) {
        $offset = ($page - 1) * $limit;

        // Base query with JOINs
        $query = "SELECT 
                    dh.id,
                    dh.user_id,
                    dh.status,
                    dh.datetime,
                    dh.code,
                    u.username,
                    d.description as discount_description,
                    d.discount_percent
                FROM " . $this->table . " dh
                LEFT JOIN users u ON dh.user_id = u.id
                LEFT JOIN discounts d ON dh.code = d.code ";

        // Add user filter if provided
        if ($user_id) {
            $query .= "WHERE dh.user_id = ? ";
        }

        // Add ordering and pagination
        $query .= "ORDER BY dh.datetime DESC LIMIT ? OFFSET ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        if ($user_id) {
            $stmt->bind_param("iii", $user_id, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get total count of discount history records
    public function getTotalCount($user_id = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if ($user_id) {
            $query .= " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Get single discount history record
    public function show($id) {
        $query = "SELECT 
                    dh.id,
                    dh.user_id,
                    dh.status,
                    dh.datetime,
                    dh.code,
                    u.username,
                    d.description as discount_description,
                    d.discount_percent
                FROM " . $this->table . " dh
                LEFT JOIN users u ON dh.user_id = u.id
                LEFT JOIN discounts d ON dh.code = d.code
                WHERE dh.id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update discount history status
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET status = ?
                WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bind_param("sii",
            $this->status,
            $this->id,
            $this->user_id
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete discount history record
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bind_param("ii", $this->id, $this->user_id);

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Get user's discount usage for a specific code
    public function getUserDiscountUsage($user_id, $code) {
        $query = "SELECT COUNT(*) as usage_count 
                FROM " . $this->table . " 
                WHERE user_id = ? AND code = ? AND status = 'used'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['usage_count'];
    }

    // Get recent discount usage history for a user
    public function getUserRecentDiscounts($user_id, $limit = 5) {
        $query = "SELECT 
                    dh.*,
                    d.description as discount_description,
                    d.discount_percent
                FROM " . $this->table . " dh
                LEFT JOIN discounts d ON dh.code = d.code
                WHERE dh.user_id = ?
                ORDER BY dh.datetime DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Check if discount code is valid and not expired
    public function validateDiscountCode($code) {
        $query = "SELECT * FROM discounts 
                WHERE code = ? 
                AND valid_from <= CURRENT_TIMESTAMP 
                AND valid_to >= CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : false;
    }

    // Get popular discount codes
    public function getPopularDiscounts($limit = 5) {
        $query = "SELECT 
                    dh.code,
                    d.description,
                    d.discount_percent,
                    COUNT(*) as usage_count
                FROM " . $this->table . " dh
                LEFT JOIN discounts d ON dh.code = d.code
                WHERE dh.status = 'used'
                GROUP BY dh.code
                ORDER BY usage_count DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>