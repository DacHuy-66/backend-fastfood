<?php
// File: model/Order_Item.php

class Order_Item {
    // Database connection and table name
    private $conn;
    private $table = 'order_items';

    // Object properties
    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $price;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create order item
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET order_id = ?, 
                    product_id = ?, 
                    quantity = ?, 
                    price = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->order_id = htmlspecialchars(strip_tags($this->order_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->price = htmlspecialchars(strip_tags($this->price));

        // Bind data
        $stmt->bind_param("iiid", 
            $this->order_id,
            $this->product_id,
            $this->quantity,
            $this->price
        );

        // Update product quantity and sold count
        if ($stmt->execute()) {
            return $this->updateProductInventory();
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Update product inventory after order
    private function updateProductInventory() {
        $query = "UPDATE products 
                SET quantity = quantity - ?,
                    sold = sold + ?
                WHERE id = ? AND quantity >= ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiii",
            $this->quantity,
            $this->quantity,
            $this->product_id,
            $this->quantity
        );

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return true;
        }
        
        // If update fails, rollback order item creation
        $this->delete();
        return false;
    }

    // Read order items
    public function read($order_id) {
        // Create query
        $query = "SELECT 
                    oi.id,
                    oi.order_id,
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    p.name as product_name,
                    p.image_url as product_image
                FROM " . $this->table . " oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind order_id
        $stmt->bind_param("i", $order_id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get single order item
    public function show($id) {
        // Create query
        $query = "SELECT 
                    oi.id,
                    oi.order_id,
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    p.name as product_name,
                    p.image_url as product_image,
                    o.user_id,
                    o.status as order_status
                FROM " . $this->table . " oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE oi.id = ?
                LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bind_param("i", $id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update order item
    public function update() {
        // Get current quantity
        $current_query = "SELECT quantity FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($current_query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_item = $result->fetch_assoc();
        $quantity_difference = $this->quantity - $current_item['quantity'];

        // Create update query
        $query = "UPDATE " . $this->table . "
                SET quantity = ?,
                    price = ?
                WHERE id = ? AND 
                    EXISTS (
                        SELECT 1 FROM orders o 
                        WHERE o.id = order_id 
                        AND o.status = 'pending'
                    )";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("idi",
            $this->quantity,
            $this->price,
            $this->id
        );

        // Update product quantity if order item update is successful
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return $this->updateProductQuantity($quantity_difference);
        }

        return false;
    }

    // Update product quantity after order item update
    private function updateProductQuantity($quantity_difference) {
        $query = "UPDATE products 
                SET quantity = quantity - ?,
                    sold = sold + ?
                WHERE id = ? AND quantity >= ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiii",
            $quantity_difference,
            $quantity_difference,
            $this->product_id,
            $quantity_difference
        );

        return $stmt->execute();
    }

    // Delete order item
    public function delete() {
        // Only allow deletion if order is still pending
        $query = "DELETE FROM " . $this->table . " 
                WHERE id = ? AND 
                    EXISTS (
                        SELECT 1 FROM orders o 
                        WHERE o.id = order_id 
                        AND o.status = 'pending'
                    )";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("i", $this->id);

        // Execute query
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Restore product quantity
            return $this->restoreProductQuantity();
        }

        return false;
    }

    // Restore product quantity after order item deletion
    private function restoreProductQuantity() {
        $query = "UPDATE products 
                SET quantity = quantity + ?,
                    sold = sold - ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii",
            $this->quantity,
            $this->quantity,
            $this->product_id
        );

        return $stmt->execute();
    }

    // Calculate total amount for an order
    public function calculateOrderTotal($order_id) {
        $query = "SELECT SUM(quantity * price) as total 
                FROM " . $this->table . " 
                WHERE order_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] ?? 0;
    }

    // Get order items count
    public function getItemsCount($order_id) {
        $query = "SELECT COUNT(*) as count 
                FROM " . $this->table . " 
                WHERE order_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }

    // Validate product availability
    public function validateProductAvailability() {
        $query = "SELECT quantity, status 
                FROM products 
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            return ['valid' => false, 'message' => 'Product not found'];
        }

        if ($product['status'] != 1) {
            return ['valid' => false, 'message' => 'Product is not available'];
        }

        if ($product['quantity'] < $this->quantity) {
            return ['valid' => false, 'message' => 'Insufficient product quantity'];
        }

        return ['valid' => true];
    }
}
?>