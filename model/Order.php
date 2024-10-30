<?php
// File: model/Order.php

class Order {
    // Database connection and table names
    private $conn;
    private $table = 'orders';
    private $items_table = 'order_items';

    // Order properties
    public $id;
    public $user_id;
    public $status;
    public $total_price;
    public $created_at;
    public $updated_at;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create Order
    public function create($items) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create order
            $query = "INSERT INTO " . $this->table . " 
                    SET user_id = ?,
                        status = ?,
                        total_price = ?,
                        created_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($query);

            // Clean data
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->total_price = htmlspecialchars(strip_tags($this->total_price));

            // Bind data
            $stmt->bind_param("isd", 
                $this->user_id,
                $this->status,
                $this->total_price
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create order");
            }

            $order_id = $this->conn->insert_id;

            // Insert order items
            $items_query = "INSERT INTO " . $this->items_table . "
                        (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)";
            
            $items_stmt = $this->conn->prepare($items_query);

            // Update product quantities and insert order items
            foreach ($items as $item) {
                // Check product quantity
                $check_query = "SELECT quantity FROM products WHERE id = ? FOR UPDATE";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->bind_param("i", $item['product_id']);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $product = $result->fetch_assoc();

                if (!$product || $product['quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product ID: " . $item['product_id']);
                }

                // Update product quantity
                $update_query = "UPDATE products 
                               SET quantity = quantity - ?,
                                   sold = sold + ?
                               WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bind_param("iii", 
                    $item['quantity'],
                    $item['quantity'],
                    $item['product_id']
                );
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update product quantity");
                }

                // Insert order item
                $items_stmt->bind_param("iiid",
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                );
                
                if (!$items_stmt->execute()) {
                    throw new Exception("Failed to create order item");
                }
            }

            // Commit transaction
            $this->conn->commit();
            return $order_id;

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            throw $e;
        }
    }

    // Read Orders with pagination
    public function read($page = 1, $limit = 10, $user_id = null) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT 
                    o.*,
                    u.username,
                    COUNT(oi.id) as item_count
                FROM " . $this->table . " o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN " . $this->items_table . " oi ON o.id = oi.order_id ";
        
        if ($user_id) {
            $query .= "WHERE o.user_id = ? ";
        }
        
        $query .= "GROUP BY o.id 
                  ORDER BY o.created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if ($user_id) {
            $stmt->bind_param("iii", $user_id, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Get total count of orders
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

    // Get single order with items
    public function show($id) {
        // Get order details
        $query = "SELECT 
                    o.*,
                    u.username
                FROM " . $this->table . " o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $order = $stmt->get_result();

        if ($order->num_rows === 0) {
            return false;
        }

        // Get order items
        $items_query = "SELECT 
                        oi.*,
                        p.name as product_name,
                        p.image_url
                    FROM " . $this->items_table . " oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";

        $items_stmt = $this->conn->prepare($items_query);
        $items_stmt->bind_param("i", $id);
        $items_stmt->execute();
        $items = $items_stmt->get_result();

        $order_data = $order->fetch_assoc();
        $order_items = [];

        while ($item = $items->fetch_assoc()) {
            $order_items[] = $item;
        }

        return [
            'order' => $order_data,
            'items' => $order_items
        ];
    }

    // Update order status
    public function updateStatus() {
        $query = "UPDATE " . $this->table . "
                SET status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bind_param("ii", 
            $this->status,
            $this->id
        );

        return $stmt->execute();
    }

    // Cancel order
    public function cancel() {
        try {
            $this->conn->begin_transaction();

            // Get order items
            $items_query = "SELECT product_id, quantity 
                          FROM " . $this->items_table . "
                          WHERE order_id = ?";
            
            $items_stmt = $this->conn->prepare($items_query);
            $items_stmt->bind_param("i", $this->id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            // Return products to inventory
            while ($item = $items_result->fetch_assoc()) {
                $update_query = "UPDATE products 
                               SET quantity = quantity + ?,
                                   sold = sold - ?
                               WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bind_param("iii",
                    $item['quantity'],
                    $item['quantity'],
                    $item['product_id']
                );
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update product quantity");
                }
            }

            // Update order status to cancelled
            $this->status = 4; // Assuming 4 is cancelled status
            if (!$this->updateStatus()) {
                throw new Exception("Failed to update order status");
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    // Get order statistics
    public function getStatistics($user_id = null) {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_price) as total_revenue,
                    COUNT(CASE WHEN status = 1 THEN 1 END) as pending_orders,
                    COUNT(CASE WHEN status = 2 THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN status = 3 THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN status = 4 THEN 1 END) as cancelled_orders
                FROM " . $this->table;

        if ($user_id) {
            $query .= " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $user_id);
        } else {
            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get most ordered products
    public function getMostOrderedProducts($limit = 5) {
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.image_url,
                    SUM(oi.quantity) as total_quantity,
                    COUNT(DISTINCT o.id) as order_count
                FROM " . $this->items_table . " oi
                JOIN products p ON oi.product_id = p.id
                JOIN " . $this->table . " o ON oi.order_id = o.id
                GROUP BY p.id
                ORDER BY total_quantity DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>