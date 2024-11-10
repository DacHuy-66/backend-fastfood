<?php

class DiscountHistory
{
    private $conn;
    private $table = 'discount_history';

    public $id;
    public $user_id;
    public $status;
    public $datetime;
    public $discount_code;
    public function __construct($db)
    {
        $this->conn = $db;
    }


    // Lấy lịch sử chiết khấu với phân trang
    public function read($page = 1, $limit = 10, $user_id = null, $email = null, $order_id = null, $discount_code = null)
    {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];
        $types = "";

        $query = "SELECT DISTINCT
                    dh.discount_code,
                    dh.datetime,
                    u.email,
                    d.discount_percent,
                    (SELECT o.id FROM orders o WHERE o.discount_code = dh.discount_code LIMIT 1) as order_id,
                    (SELECT o.status FROM orders o WHERE o.discount_code = dh.discount_code LIMIT 1) as status
                FROM " . $this->table . " dh
                LEFT JOIN users u ON dh.user_id = u.id
                LEFT JOIN discounts d ON dh.discount_code = d.code
                LEFT JOIN orders o ON o.discount_code = dh.discount_code ";

        // Build conditions array and params
        if ($user_id) {
            $conditions[] = "dh.user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        if ($email) {
            $conditions[] = "u.email LIKE ?";
            $params[] = "%$email%";
            $types .= "s";
        }
        if ($order_id) {
            $conditions[] = "o.id = ?";
            $params[] = $order_id;
            $types .= "i";
        }
        if ($discount_code) {
            $conditions[] = "dh.discount_code LIKE ?";
            $params[] = "%$discount_code%";
            $types .= "s";
        }

        // Add WHERE clause if conditions exist
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Add pagination
        $query .= " ORDER BY dh.datetime DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy tổng số lượng bản ghi lịch sử chiết khấu
    public function getTotalCount($user_id = null, $email = null, $order_id = null, $discount_code = null)
    {
        $conditions = [];
        $params = [];
        $types = "";

        $query = "SELECT COUNT(DISTINCT dh.id) as total 
                 FROM " . $this->table . " dh
                 LEFT JOIN users u ON dh.user_id = u.id
                 LEFT JOIN orders o ON o.discount_code = dh.discount_code ";

        if ($user_id) {
            $conditions[] = "dh.user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        }
        if ($email) {
            $conditions[] = "u.email LIKE ?";
            $params[] = "%$email%";
            $types .= "s";
        }
        if ($order_id) {
            $conditions[] = "o.id = ?";
            $params[] = $order_id;
            $types .= "i";
        }
        if ($discount_code) {
            $conditions[] = "dh.discount_code LIKE ?";
            $params[] = "%$discount_code%";
            $types .= "s";
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Xóa lịch sử chiết khấu
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
