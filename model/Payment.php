<?php
// File: model/Payment.php

class Payment {
    // Database connection and table name
    private $conn;
    private $table = 'payments';

    // Payment properties
    public $id;
    public $order_id;
    public $payment_method;
    public $payment_status;
    public $payment_date;
    public $amount;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create payment
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET order_id = ?,
                    payment_method = ?,
                    payment_status = ?,
                    payment_date = CURRENT_TIMESTAMP";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->order_id = htmlspecialchars(strip_tags($this->order_id));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));

        // Bind data
        $stmt->bind_param("iss", 
            $this->order_id,
            $this->payment_method,
            $this->payment_status
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read payments with pagination
    public function read($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // Create query
        $query = "SELECT 
                    p.id,
                    p.order_id,
                    p.payment_method,
                    p.payment_status,
                    p.payment_date,
                    o.total_price as amount,
                    o.user_id,
                    u.username
                FROM " . $this->table . " p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY p.payment_date DESC 
                LIMIT ? OFFSET ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind params
        $stmt->bind_param("ii", $limit, $offset);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get total count of payments
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Get single payment
    public function show($id) {
        // Create query
        $query = "SELECT 
                    p.id,
                    p.order_id,
                    p.payment_method,
                    p.payment_status,
                    p.payment_date,
                    o.total_price as amount,
                    o.user_id,
                    u.username,
                    o.status as order_status
                FROM " . $this->table . " p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE p.id = ?
                LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bind_param("i", $id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get payments by order ID
    public function getByOrderId($order_id) {
        // Create query
        $query = "SELECT 
                    p.id,
                    p.order_id,
                    p.payment_method,
                    p.payment_status,
                    p.payment_date,
                    o.total_price as amount,
                    o.user_id,
                    u.username
                FROM " . $this->table . " p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE p.order_id = ?
                ORDER BY p.payment_date DESC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind order ID
        $stmt->bind_param("i", $order_id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get payments by user ID
    public function getByUserId($user_id, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // Create query
        $query = "SELECT 
                    p.id,
                    p.order_id,
                    p.payment_method,
                    p.payment_status,
                    p.payment_date,
                    o.total_price as amount,
                    o.user_id,
                    u.username
                FROM " . $this->table . " p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.user_id = ?
                ORDER BY p.payment_date DESC
                LIMIT ? OFFSET ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bind_param("iii", $user_id, $limit, $offset);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update payment status
    public function updateStatus() {
        // Create query
        $query = "UPDATE " . $this->table . "
                SET payment_status = ?,
                    payment_date = CURRENT_TIMESTAMP
                WHERE id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("si", 
            $this->payment_status,
            $this->id
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Get payment statistics
    public function getStatistics($start_date = null, $end_date = null) {
        // Base query
        $query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(o.total_price) as total_amount,
                    payment_method,
                    payment_status,
                    COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as successful_payments,
                    COUNT(CASE WHEN payment_status = 'failed' THEN 1 END) as failed_payments
                FROM " . $this->table . " p
                LEFT JOIN orders o ON p.order_id = o.id
                WHERE 1=1";

        // Add date filters if provided
        if ($start_date && $end_date) {
            $query .= " AND p.payment_date BETWEEN ? AND ?";
        }

        $query .= " GROUP BY payment_method, payment_status";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind dates if provided
        if ($start_date && $end_date) {
            $stmt->bind_param("ss", $start_date, $end_date);
        }

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Verify payment exists for order
    public function verifyOrderPayment($order_id) {
        $query = "SELECT id, payment_status 
                FROM " . $this->table . " 
                WHERE order_id = ? 
                ORDER BY payment_date DESC 
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    // Delete payment (usually only for failed/cancelled payments)
    public function delete() {
        // First check if payment can be deleted (not completed)
        $query = "SELECT payment_status FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $payment = $result->fetch_assoc();
            if ($payment['payment_status'] === 'completed') {
                return false; // Cannot delete completed payments
            }
        }

        // Create delete query
        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND payment_status != 'completed'";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean id
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind id
        $stmt->bind_param("i", $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }
}
?>