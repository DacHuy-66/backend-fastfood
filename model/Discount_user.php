<?php
// File: model/DiscountUser.php

class DiscountUser {
    // Database connection and table name
    private $conn;
    private $table = 'discount_user';

    // Object properties matching database columns
    public $id;
    public $user_id;
    public $code;
    public $description;
    public $discount_percent;
    public $valid_from;
    public $valid_to;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create discount user
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET user_id = ?,
                    code = ?,
                    description = ?,
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));

        // Bind data
        $stmt->bind_param("isssss",
            $this->user_id,
            $this->code,
            $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read all discount users with pagination
    public function read($page = 1, $limit = 10, $user_id = null) {
        $offset = ($page - 1) * $limit;

        // Base query
        $query = "SELECT 
                    du.id,
                    du.user_id,
                    du.code,
                    du.description,
                    du.discount_percent,
                    du.valid_from,
                    du.valid_to,
                    u.username,
                    CASE 
                        WHEN CURRENT_DATE() < du.valid_from THEN 'pending'
                        WHEN CURRENT_DATE() > du.valid_to THEN 'expired'
                        ELSE 'active'
                    END as status
                FROM " . $this->table . " du
                LEFT JOIN users u ON du.user_id = u.id ";

        // Add user filter if provided
        if ($user_id) {
            $query .= "WHERE du.user_id = ? ";
        }

        // Add ordering and pagination
        $query .= "ORDER BY 
                    CASE 
                        WHEN CURRENT_DATE() BETWEEN du.valid_from AND du.valid_to THEN 0
                        WHEN CURRENT_DATE() < du.valid_from THEN 1
                        ELSE 2
                    END,
                    du.valid_from DESC 
                    LIMIT ? OFFSET ?";

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

    // Get single discount user
    public function show($id) {
        // Create query
        $query = "SELECT 
                    du.*,
                    u.username,
                    CASE 
                        WHEN CURRENT_DATE() < du.valid_from THEN 'pending'
                        WHEN CURRENT_DATE() > du.valid_to THEN 'expired'
                        ELSE 'active'
                    END as status
                FROM " . $this->table . " du
                LEFT JOIN users u ON du.user_id = u.id
                WHERE du.id = ?
                LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bind_param("i", $id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update discount user
    public function update() {
        // Create query
        $query = "UPDATE " . $this->table . "
                SET code = ?,
                    description = ?,
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?
                WHERE id = ? AND user_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bind_param("sssssii",
            $this->code,
            $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to,
            $this->id,
            $this->user_id
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete discount user
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind data
        $stmt->bind_param("ii", $this->id, $this->user_id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Get total count
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

    // Validate discount code for user
    public function validateDiscountCode($code, $user_id) {
        $query = "SELECT * FROM " . $this->table . "
                WHERE code = ? 
                AND user_id = ?
                AND CURRENT_DATE() BETWEEN valid_from AND valid_to";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $code, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $discount = $result->fetch_assoc();
            return [
                'valid' => true,
                'discount_percent' => $discount['discount_percent'],
                'valid_until' => $discount['valid_to']
            ];
        }

        return ['valid' => false];
    }

    // Check if code exists
    public function isCodeExists($code, $exclude_id = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE code = ?";
        
        if ($exclude_id) {
            $query .= " AND id != ?";
        }

        $stmt = $this->conn->prepare($query);

        if ($exclude_id) {
            $stmt->bind_param("si", $code, $exclude_id);
        } else {
            $stmt->bind_param("s", $code);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    // Get user's active discounts
    public function getUserActiveDiscounts($user_id) {
        $query = "SELECT * FROM " . $this->table . "
                WHERE user_id = ?
                AND CURRENT_DATE() BETWEEN valid_from AND valid_to
                ORDER BY discount_percent DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Generate unique discount code
    public function generateUniqueCode($length = 8) {
        do {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while ($this->isCodeExists($code));

        return $code;
    }
}
?>