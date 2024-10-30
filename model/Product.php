<?php

class Product
{
    private $conn;
    private $table = 'products';

    public $id;
    public $category_id;
    public $category_name;
    public $name;
    public $description;
    public $price;
    public $image_url;
    public $sold;
    public $quantity;
    public $status;
    public $discount;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read($page = 1, $limit = 10)
    {
        $start = ($page - 1) * $limit;

        $query = "SELECT 
                    p.id,
                    p.category_id,
                    c.Name as category_name,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.sold,
                    p.quantity,
                    p.status,
                    p.discount,
                    p.created_at
                FROM
                    {$this->table} p
                LEFT JOIN
                    categories c ON p.category_id = c.id
                ORDER BY
                    p.created_at DESC
                LIMIT ?, ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $start, $limit);
        $stmt->execute();

        return $stmt->get_result();
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
                    p.quantity,
                    p.status,
                    p.discount,
                    p.category_id,
                    c.Name as category_name,
                    p.created_at
                FROM
                    {$this->table} p
                LEFT JOIN
                    categories c ON p.category_id = c.id
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
                      image_url = ?, 
                      sold = ?,
                      p.discount = ?, 
                      quantity = ?, 
                      status = ?, 
                      category_id = ? 
                  WHERE 
                      id = ?";

        $stmt = $this->conn->prepare($query);
    
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->sold = htmlspecialchars(strip_tags($this->sold));
        $this->discount = htmlspecialchars(strip_tags($this->discount));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        $this->status = htmlspecialchars(strip_tags($this->status)); 
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
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
            $this->category_id, 
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
                  (name, description, price, image_url, sold, quantity, status, discount, category_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->sold = htmlspecialchars(strip_tags($this->sold));
        $this->discount = htmlspecialchars(strip_tags($this->discount));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity)); 
        $this->status = htmlspecialchars(strip_tags($this->status)); 
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));

        $stmt->bind_param("ssdssiis", 
            $this->name, 
            $this->description, 
            $this->price, 
            $this->image_url, 
            $this->sold, 
            $this->discount, 
            $this->quantity, 
            $this->status, 
            $this->category_id
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

    // Lấy tổng số sản phẩm
    public function getTotalCount()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
}