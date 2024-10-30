<?php
// File: model/Discount.php

class Discount {
    // Database connection and table names
    private $conn;
    private $table = 'discounts';
    private $user_discount_table = 'discount_user';

    // Discount properties
    public $id;
    public $code;
    public $description;
    public $discount_percent;
    public $valid_from;
    public $valid_to;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create Discount
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                SET code = ?,
                    description = ?,
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));

        // Bind data
        $stmt->bind_param("ssiss", 
            $this->code,
            $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read Discounts with pagination
    public function read($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $query = "SELECT 
                    id,
                    code,
                    description,
                    discount_percent,
                    valid_from,
                    valid_to
                FROM " . $this->table . "
                ORDER BY valid_from DESC 
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    // Get single discount
    public function show($id) {
        $query = "SELECT 
                    d.*,
                    COUNT(du.user_id) as usage_count
                FROM " . $this->table . " d
                LEFT JOIN " . $this->user_discount_table . " du ON d.id = du.discount_id
                WHERE d.id = ?
                GROUP BY d.id
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    // Update discount
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET code = ?,
                    description = ?,
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("ssissi",
            $this->code,
            $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to,
            $this->id
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete discount
    public function delete() {
        // First check if discount is used in any orders
        $check_query = "SELECT COUNT(*) as count FROM orders WHERE discount_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("i", $this->id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            return false; // Cannot delete if discount is used in orders
        }

        // Begin transaction
        $this->conn->begin_transaction();

        try {
            // Delete from discount_user table first
            $delete_user_discounts = "DELETE FROM " . $this->user_discount_table . " WHERE discount_id = ?";
            $stmt1 = $this->conn->prepare($delete_user_discounts);
            $stmt1->bind_param("i", $this->id);
            $stmt1->execute();

            // Then delete the discount
            $delete_discount = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt2 = $this->conn->prepare($delete_discount);
            $stmt2->bind_param("i", $this->id);
            $stmt2->execute();

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            printf("Error: %s.\n", $e->getMessage());
            return false;
        }
    }

    // Get total count of discounts
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Assign discount to user
    public function assignToUser($user_id) {
        // Check if discount is valid
        if (!$this->isValid()) {
            return false;
        }

        // Check if user already has this discount
        if ($this->isAssignedToUser($user_id)) {
            return false;
        }

        $query = "INSERT INTO " . $this->user_discount_table . "
                (user_id, discount_id, code, description, discount_percent, valid_from, valid_to)
                SELECT ?, id, code, description, discount_percent, valid_from, valid_to
                FROM " . $this->table . "
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $this->id);

        return $stmt->execute();
    }

    // Check if discount is valid
    public function isValid() {
        $query = "SELECT * FROM " . $this->table . "
                WHERE id = ? 
                AND valid_from <= CURRENT_DATE 
                AND valid_to >= CURRENT_DATE";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }

    // Check if discount is assigned to user
    public function isAssignedToUser($user_id) {
        $query = "SELECT * FROM " . $this->user_discount_table . "
                WHERE discount_id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->id, $user_id);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }

    // Get user's discounts
    public function getUserDiscounts($user_id, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $query = "SELECT 
                    du.*,
                    CASE 
                        WHEN CURRENT_DATE < du.valid_from THEN 'pending'
                        WHEN CURRENT_DATE > du.valid_to THEN 'expired'
                        ELSE 'active'
                    END as status
                FROM " . $this->user_discount_table . " du
                WHERE du.user_id = ?
                ORDER BY 
                    CASE 
                        WHEN CURRENT_DATE BETWEEN du.valid_from AND du.valid_to THEN 0
                        WHEN CURRENT_DATE < du.valid_from THEN 1
                        ELSE 2
                    END,
                    du.valid_to DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    // Get user's discount total count
    public function getUserDiscountsTotalCount($user_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->user_discount_table . " 
                WHERE user_id = ?";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Validate discount code for user
    public function validateDiscountCode($code, $user_id) {
        $query = "SELECT du.* 
                FROM " . $this->user_discount_table . " du
                WHERE du.code = ? 
                AND du.user_id = ? 
                AND CURRENT_DATE BETWEEN du.valid_from AND du.valid_to
                AND du.status = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $code, $user_id);
        $stmt->execute();
        
        return $stmt->get_result();
    }
}
?>