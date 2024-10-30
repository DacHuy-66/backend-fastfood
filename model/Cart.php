<?php

class Cart {
    private $conn;
    private $table = 'cart';

    public $id;
    public $user_id;
    public $product_id;
    public $quantity;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add/Update item to cart
    public function addItem() {
        // Kiểm tra sản phẩm có tồn tại và đủ số lượng hay không
        $check_product = "SELECT quantity, status FROM products WHERE id = ? AND status = 1";
        $stmt = $this->conn->prepare($check_product);
        $stmt->bind_param("i", $this->product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not available");
        }
        
        $product = $result->fetch_assoc();
        if ($product['quantity'] < $this->quantity) {
            throw new Exception("Not enough product in stock. Available: " . $product['quantity']);
        }

        // Kiểm tra xem mặt hàng đã tồn tại trong giỏ hàng chưa
        $check_query = "SELECT id, quantity FROM " . $this->table . " 
                       WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bind_param("ii", $this->user_id, $this->product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Cập nhật mặt hàng giỏ hàng hiện có
            $cart_item = $result->fetch_assoc();
            $new_quantity = $this->quantity;
            
            if ($new_quantity > $product['quantity']) {
                throw new Exception("Not enough product in stock");
            }

            $query = "UPDATE " . $this->table . "
                     SET quantity = ?
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        } else {
            // Thêm mặt hàng giỏ hàng mới
            $query = "INSERT INTO " . $this->table . " 
                     SET user_id = ?, product_id = ?, quantity = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iii", $this->user_id, $this->product_id, $this->quantity);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Nhận các mặt hàng trong giỏ hàng của người dùng
    public function getUserCart($user_id) {
        $query = "SELECT c.id, c.user_id, c.product_id, c.quantity,
                        p.name, p.price, p.image_url, p.quantity as available_quantity,
                        (p.price * c.quantity) as total_price
                 FROM " . $this->table . " c
                 LEFT JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ? AND p.status = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Nhận tổng số giỏ hàng
    public function getCartTotal($user_id) {
        $query = "SELECT SUM(p.price * c.quantity) as total
                 FROM " . $this->table . " c
                 LEFT JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ? AND p.status = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    // Cập nhật số lượng mặt hàng trong giỏ hàng
    public function updateQuantity() {
        // Check product availability and quantity
        $check_product = "SELECT quantity FROM products WHERE id = ? AND status = 1";
        $stmt = $this->conn->prepare($check_product);
        $stmt->bind_param("i", $this->product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not available");
        }
        
        $product = $result->fetch_assoc();
        if ($product['quantity'] < $this->quantity) {
            throw new Exception("Not enough product in stock. Available: " . $product['quantity']);
        }

        // Update quantity
        $query = "UPDATE " . $this->table . "
                 SET quantity = ?
                 WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $this->quantity, $this->id, $this->user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Xóa mặt hàng khỏi giỏ hàng
    public function removeItem() {
        $query = "DELETE FROM " . $this->table . "
                 WHERE id = ? AND user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->id, $this->user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Xóa giỏ hàng của người dùng
    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Kiểm tra xem mặt hàng trong giỏ hàng có tồn tại không
    public function getCartItem($id, $user_id) {
        $query = "SELECT c.*, p.name, p.price, p.quantity as available_quantity
                 FROM " . $this->table . " c
                 LEFT JOIN products p ON c.product_id = p.id
                 WHERE c.id = ? AND c.user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Nhận số lượng giỏ hàng
    public function getCartCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                 WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    //Xác thực các mặt hàng trong giỏ hàng trước khi thanh toán
    public function validateCart($user_id) {
        $query = "SELECT c.product_id, c.quantity, 
                        p.quantity as available_quantity, p.status, p.name
                 FROM " . $this->table . " c
                 LEFT JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $invalid_items = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] != 1) {
                $invalid_items[] = [
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'reason' => 'Product is not available'
                ];
            } elseif ($row['quantity'] > $row['available_quantity']) {
                $invalid_items[] = [
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'requested' => $row['quantity'],
                    'available' => $row['available_quantity'],
                    'reason' => 'Insufficient stock'
                ];
            }
        }
        
        return $invalid_items;
    }
}
?>