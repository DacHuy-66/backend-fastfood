<?php
// File: model/Promotion.php

class Promotion {
    // Database connection and table name
    private $conn;
    private $table = 'promotions';

    // Promotion properties
    public $id;
    public $title;
    public $description;
    public $discount_percent;
    public $start_date;
    public $end_date;
    public $min_order_value;
    public $max_discount;
    public $created_at;
    public $updated_at;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create Promotion
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET title = ?,
                    description = ?,
                    discount_percent = ?,
                    start_date = ?,
                    end_date = ?,
                    min_order_value = ?,
                    max_discount = ?,
                    created_at = CURRENT_TIMESTAMP";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->min_order_value = htmlspecialchars(strip_tags($this->min_order_value));
        $this->max_discount = htmlspecialchars(strip_tags($this->max_discount));

        // Bind data
        $stmt->bind_param("ssdssdd",
            $this->title,
            $this->description,
            $this->discount_percent,
            $this->start_date,
            $this->end_date,
            $this->min_order_value,
            $this->max_discount
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read Promotions with pagination
    public function read($page = 1, $limit = 10, $active_only = false) {
        $offset = ($page - 1) * $limit;
        
        // Base query
        $query = "SELECT * FROM " . $this->table;
        
        // Add active filter if requested
        if ($active_only) {
            $query .= " WHERE start_date <= CURRENT_DATE 
                       AND end_date >= CURRENT_DATE";
        }
        
        // Add ordering and pagination
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bind_param("ii", $limit, $offset);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get total count of promotions
    public function getTotalCount($active_only = false) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if ($active_only) {
            $query .= " WHERE start_date <= CURRENT_DATE 
                       AND end_date >= CURRENT_DATE";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Get single promotion
    public function show($id) {
        // Create query
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bind_param("i", $id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update promotion
    public function update() {
        // Create query
        $query = "UPDATE " . $this->table . "
                SET title = ?,
                    description = ?,
                    discount_percent = ?,
                    start_date = ?,
                    end_date = ?,
                    min_order_value = ?,
                    max_discount = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->start_date = htmlspecialchars(strip_tags($this->start_date));
        $this->end_date = htmlspecialchars(strip_tags($this->end_date));
        $this->min_order_value = htmlspecialchars(strip_tags($this->min_order_value));
        $this->max_discount = htmlspecialchars(strip_tags($this->max_discount));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("ssdssddي",
            $this->title,
            $this->description,
            $this->discount_percent,
            $this->start_date,
            $this->end_date,
            $this->min_order_value,
            $this->max_discount,
            $this->id
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete promotion
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("i", $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Get active promotions
    public function getActivePromotions() {
        // Create query
        $query = "SELECT * FROM " . $this->table . "
                WHERE start_date <= CURRENT_DATE 
                AND end_date >= CURRENT_DATE
                ORDER BY discount_percent DESC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Check if promotion is valid for order value
    public function isValidForOrderValue($order_value) {
        if ($order_value < $this->min_order_value) {
            return false;
        }
        return true;
    }

    // Calculate discount amount
    public function calculateDiscount($order_value) {
        if (!$this->isValidForOrderValue($order_value)) {
            return 0;
        }

        $discount = ($order_value * $this->discount_percent) / 100;
        
        // Apply maximum discount limit if set
        if ($this->max_discount > 0 && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return $discount;
    }

    // Check if promotion is currently active
    public function isActive() {
        $current_date = date('Y-m-d');
        return ($this->start_date <= $current_date && $this->end_date >= $current_date);
    }

    // Get upcoming promotions
    public function getUpcomingPromotions() {
        // Create query
        $query = "SELECT * FROM " . $this->table . "
                WHERE start_date > CURRENT_DATE
                ORDER BY start_date ASC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Search promotions
    public function search($keywords) {
        // Create query
        $query = "SELECT * FROM " . $this->table . "
                WHERE title LIKE ? OR description LIKE ?
                ORDER BY created_at DESC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $search_term = "%{$keywords}%";

        // Bind data
        $stmt->bind_param("ss", $search_term, $search_term);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>