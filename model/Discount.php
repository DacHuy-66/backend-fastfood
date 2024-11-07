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
    public $name;
    public $description;
    public $discount_percent;
    public $valid_from;
    public $valid_to;
    public $quantity;
    public $minimum_price;
    public $type;
    public $status;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create Discount
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                SET code = ?,
                    name = ?,
                    description = ?,
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?,
                    quantity = ?,
                    minimum_price = ?,
                    type = ?,
                    status = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->minimum_price = htmlspecialchars(strip_tags($this->minimum_price));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->status = 1;

        // Bind data
        $stmt->bind_param("sssissiisi",
            $this->code,
            $this->name,
            $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to,
            $this->quantity,
            $this->minimum_price,
            $this->type,
            $this->status
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read Discounts with pagination
    public function read($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM discounts WHERE 1=1";
        if ($search) {
            $query .= " AND (code LIKE ? OR name LIKE ? OR description LIKE ?)";
        }
        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $search_param = "%$search%";
            $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update discount
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET code = ?,
                    name = ?,
  
                    discount_percent = ?,
                    valid_from = ?,
                    valid_to = ?,
                    quantity = ?,
                    minimum_price = ?,
                    type = ?,
                    status = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->code = htmlspecialchars(strip_tags($this->code));
        $this->name = htmlspecialchars(strip_tags($this->name));
        // $this->description = htmlspecialchars(strip_tags($this->description));
        $this->discount_percent = htmlspecialchars(strip_tags($this->discount_percent));
        $this->valid_from = htmlspecialchars(strip_tags($this->valid_from));
        $this->valid_to = htmlspecialchars(strip_tags($this->valid_to));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->minimum_price = htmlspecialchars(strip_tags($this->minimum_price));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("ssissiisii",
            $this->code,
            $this->name,
            // $this->description,
            $this->discount_percent,
            $this->valid_from,
            $this->valid_to,
            $this->quantity,
            $this->minimum_price,
            $this->type,
            $this->status,
            $this->id
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Get total count of discounts
    public function getTotalCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM discounts WHERE 1=1";
        if ($search) {
            $query .= " AND (code LIKE ? OR name LIKE ? OR description LIKE ?)";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($search) {
            $search_param = "%$search%";
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
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


    // Thêm phương thức mới vào class Discount
    public function isCodeExists($code) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE code = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

}