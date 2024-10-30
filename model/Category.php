<?php

class Category {
    private $conn;
    private $table = 'categories';

    public $id;
    public $Name;
    public $Description;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                SET Name = ?, 
                    Description = ?";

        $stmt = $this->conn->prepare($query);

        $this->Name = htmlspecialchars(string: strip_tags($this->Name));
        $this->Description = htmlspecialchars(strip_tags($this->Description));

        $stmt->bind_param("ss", 
            $this->Name, 
            $this->Description
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Đọc danh mục với phân trang
    public function read($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // Tạo truy vấn với số lượng sản phẩm
        $query = "SELECT 
                    c.id,
                    c.Name,
                    c.Description,
                    COUNT(p.id) as product_count
                FROM " . $this->table . " c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
                ORDER BY c.Name ASC 
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("ii", $limit, $offset);

        $stmt->execute();
        return $stmt->get_result();
    }

    // Nhận tổng số danh mục
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Nhận danh mục duy nhất với số lượng sản phẩm
    public function show($id) {
        $query = "SELECT 
                    c.id,
                    c.Name,
                    c.Description,
                    COUNT(p.id) as product_count
                FROM " . $this->table . " c
                LEFT JOIN products p ON c.id = p.category_id
                WHERE c.id = ?
                GROUP BY c.id
                LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("i", $id);

        $stmt->execute();
        return $stmt->get_result();
    }

    // Update category
    public function update() {
        // kiểm tra xem danh mục có tồn tại và không có sản phẩm nào không
        $check_query = "SELECT COUNT(p.id) as product_count 
                       FROM products p 
                       WHERE p.category_id = ?";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("i", $this->id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        $query = "UPDATE " . $this->table . "
                SET Name = ?,
                    Description = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        $this->Name = htmlspecialchars(strip_tags($this->Name));
        $this->Description = htmlspecialchars(strip_tags($this->Description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bind_param("ssi",
            $this->Name,
            $this->Description,
            $this->id
        );

        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete category
    public function delete() {
        // kiểm tra xem danh mục có tồn tại và không có sản phẩm nào không
        $check_query = "SELECT COUNT(*) as product_count 
                       FROM products 
                       WHERE category_id = ?";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("i", $this->id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        // Không thể xóa danh mục có sản phẩm
        if($row['product_count'] > 0) {
            return false; 
        }

         $query = "DELETE FROM " . $this->table . " WHERE id = ?";

         $stmt = $this->conn->prepare($query);

         $this->id = htmlspecialchars(strip_tags($this->id));

         $stmt->bind_param("i", $this->id);

         if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Nhận sản phẩm trong danh mục
    public function getCategoryProducts($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // Create query
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.quantity,
                    p.sold,
                    p.status,
                    p.created_at
                FROM products p
                WHERE p.category_id = ?
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";

         $stmt = $this->conn->prepare($query);

         $stmt->bind_param("iii", $this->id, $limit, $offset);

         $stmt->execute();
        return $stmt->get_result();
    }

    // Lấy tổng số sản phẩm trong danh mục
    public function getCategoryProductCount() {
        $query = "SELECT COUNT(*) as total 
                 FROM products 
                 WHERE category_id = ?";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Search categories
    public function search($keyword, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$keyword}%";
        
         $query = "SELECT 
                    c.id,
                    c.Name,
                    c.Description,
                    COUNT(p.id) as product_count
                FROM " . $this->table . " c
                LEFT JOIN products p ON c.id = p.category_id
                WHERE c.Name LIKE ? OR c.Description LIKE ?
                GROUP BY c.id
                ORDER BY c.Name ASC 
                LIMIT ? OFFSET ?";

         $stmt = $this->conn->prepare($query);

         $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);

         $stmt->execute();
        return $stmt->get_result();
    }
    
    // Nhận số lượng kết quả tìm kiếm
    public function getSearchCount($keyword) {
        $searchTerm = "%{$keyword}%";
        
        $query = "SELECT COUNT(DISTINCT c.id) as total 
                 FROM " . $this->table . " c
                 WHERE c.Name LIKE ? OR c.Description LIKE ?";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }
}
?>