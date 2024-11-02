<?php

class Product
{
    private $conn;
    private $table = 'products';

    public $id;
    public $category_name;
    public $name;
    public $description;
    public $price;
    public $image_url;
    public $type;
    public $sold;
    public $quantity;
    public $status;
    public $discount;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read($page = 1, $limit = 40, $search = '', $filters = [])
    {
        $limit = $limit > 0 ? $limit : 40;  
        $start = ($page - 1) * $limit;
        $start = ($page - 1) * $limit;

        // Xây dựng câu truy vấn cơ bản
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.sold,
                    p.type,
                    p.quantity,
                    p.status,
                    p.discount,
                    p.created_at
                FROM
                    {$this->table} p
                WHERE 1=1";

        $params = [];
        $types = "";

        // Thêm điều kiện tìm kiếm theo tên
        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        // Lọc theo loại sản phẩm
        if (!empty($filters['type'])) {
            $query .= " AND p.type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }

        // Lọc theo khoảng giá
        if (isset($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }
        if (isset($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= "i";
        }

        // Sắp xếp
        $allowedSortFields = ['name', 'price', 'created_at', 'sold'];
        $sortBy = isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortFields) 
                ? $filters['sort_by'] 
                : 'created_at';
        
        $sortOrder = isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC' 
                  ? 'ASC' 
                  : 'DESC';
        
        $query .= " ORDER BY p.{$sortBy} {$sortOrder}";

        // Thêm LIMIT và OFFSET
        $query .= " LIMIT ?, ?";
        $params[] = $start;
        $params[] = $limit;
        $types .= "ii";

        // Chuẩn bị và thực thi câu truy vấn
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getTotalCount($search = '', $filters = [])
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " p WHERE 1=1";
        
        $params = [];
        $types = "";

        // Thêm điều kiện tìm kiếm theo tên
        if (!empty($search)) {
            $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        // Lọc theo loại sản phẩm
        if (!empty($filters['type'])) {
            $query .= " AND p.type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }

        // Lọc theo khoảng giá
        if (isset($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }
        if (isset($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        // Lọc theo trạng thái
        if (isset($filters['status'])) {
            $query .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= "i";
        }

        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }
    public function show($id)
    {
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.sold,
                    p.type,
                    p.quantity,
                    p.status,
                    p.discount,
                    p.created_at
                FROM
                    {$this->table} p
                WHERE
                    p.id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $id);
        $stmt->execute();

        return $stmt->get_result();
    }

    // Cập nhật sản phẩm
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET 
                      name = ?, 
                      description = ?, 
                      price = ?, 
                      type = ?, 
                      image_url = ?, 
                      sold = ?,
                      p.discount = ?, 
                      quantity = ?, 
                      status = ?, 
                  WHERE 
                      id = ?";

        $stmt = $this->conn->prepare($query);
    
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->sold = htmlspecialchars(strip_tags($this->sold));
        $this->discount = htmlspecialchars(strip_tags($this->discount));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->status = htmlspecialchars(strip_tags($this->status)); 
        $this->id = htmlspecialchars(strip_tags($this->id));
    
        $stmt->bind_param("ssdssiisi", 
            $this->name, 
            $this->description, 
            $this->price, 
            $this->image_url, 
            $this->sold, 
            $this->discount, 
            $this->quantity, 
            $this->status, 
            $this->type, 
            $this->id
        );
    
        if ($stmt->execute()) {
            return true;
        }
    
        return false;
    }

    // Tạo một sản phẩm mới
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (name, description, price, image_url, sold, quantity, status, discount,type, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?,?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->sold = htmlspecialchars(strip_tags($this->sold));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->discount = htmlspecialchars(strip_tags($this->discount));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity)); 
        $this->status = htmlspecialchars(strip_tags($this->status)); 

        $stmt->bind_param("ssdsssii", 
            $this->name, 
            $this->description, 
            $this->price, 
            $this->image_url, 
            $this->sold, 
            $this->type, 
            $this->discount, 
            $this->quantity, 
            $this->status
        );

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Xóa sản phẩm theo ID
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bind_param("i", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // // Lấy tổng số sản phẩm
    // public function getTotalCount()
    // {
    //     $query = "SELECT COUNT(*) as total FROM " . $this->table;
    //     $result = $this->conn->query($query);
    //     $row = $result->fetch_assoc();
    //     return $row['total'];
    // }
}