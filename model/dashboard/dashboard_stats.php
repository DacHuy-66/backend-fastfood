<?php
class DashboardStats {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function getNewUsers() {
        // Đếm số người dùng mới trong tháng hiện tại
        $query = "SELECT COUNT(*) as new_users 
                 FROM users 
                 WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['new_users'] ?? 0;
    }

    public function getNewUsersGrowth() {
        // Tính tỷ lệ tăng trưởng người dùng mới so với tháng trước
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as user_count
                 FROM users 
                 WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 2";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        if (count($results) < 2) return 0;
        
        $current = $results[0]['user_count'];
        $previous = $results[1]['user_count'];
        
        return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
    }

    public function getTotalOrders() {
        // Đếm tổng số đơn hàng đã hoàn thành
        $query = "SELECT COUNT(*) as total_orders 
                 FROM orders 
                 WHERE status = 'completed'";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total_orders'] ?? 0;
    }

    public function getOrdersGrowth() {
        // Tính tỷ lệ tăng trưởng đơn hàng so với tháng trước
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as order_count
                 FROM orders 
                 WHERE status = 'completed'
                 AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 2";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        
        if (count($results) < 2) return 0;
        
        $current = $results[0]['order_count'];
        $previous = $results[1]['order_count'];
        
        return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
    }

    public function getMonthlyStats() {
        // Lấy thống kê theo tháng cho biểu đồ
        $query = "SELECT 
                    DATE_FORMAT(o.created_at, '%m-%Y') as month,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(po.price * po.quantity) as revenue
                 FROM orders o
                 JOIN product_order po ON o.id = po.order_id
                 WHERE o.status = 'completed' 
                 AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 MONTH)
                 GROUP BY DATE_FORMAT(o.created_at, '%m-%Y')
                 ORDER BY o.created_at ASC";
                 
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $results = [];
        while($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        return $results;
    }
} 