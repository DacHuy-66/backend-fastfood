<?php

class DiscountHistory
{
    private $conn;
    private $table = 'discount_history';

    public $id;
    public $user_id;
    public $status;
    public $datetime;
    public $code;
    public function __construct($db)
    {
        $this->conn = $db;
    }


    // Lấy lịch sử chiết khấu với phân trang
    public function read($page = 1, $limit = 10, $user_id = null)
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT 
                    dh.id,
                    dh.user_id,
                    dh.status,
                    dh.datetime,
                    dh.code,
                    u.username,
                    u.email,
                    d.description as discount_description,
                    d.discount_percent
                FROM " . $this->table . " dh
                LEFT JOIN users u ON dh.user_id = u.id
                LEFT JOIN discounts d ON dh.code = d.code ";

        // thêm điều kiện lọc theo user_id nếu có   
        if ($user_id) {
            $query .= "WHERE dh.user_id = ? ";
        }

        // thêm điều kiện sắp xếp và phân trang
        $query .= "ORDER BY dh.datetime DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if ($user_id) {
            $stmt->bind_param("iii", $user_id, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy tổng số lượng bản ghi lịch sử chiết khấu
    public function getTotalCount($user_id = null)
    {
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
