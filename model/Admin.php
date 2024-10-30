<?php
// File: model/Admin.php

class Admin {
    // Database connection and table name
    private $conn;
    private $table = 'Admin';

    // Admin properties
    public $id;
    public $username;
    public $password;
    public $role_1;
    public $role_2;
    public $role_3;
    public $role_4;
    public $note;
    public $time;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new admin
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                SET username = ?,
                    password = ?,
                    role_1 = ?,
                    role_2 = ?,
                    role_3 = ?,
                    role_4 = ?,
                    note = ?,
                    time = CURRENT_TIMESTAMP";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->note = htmlspecialchars(strip_tags($this->note));

        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        // Convert roles to boolean (0 or 1)
        $role1 = $this->role_1 ? 1 : 0;
        $role2 = $this->role_2 ? 1 : 0;
        $role3 = $this->role_3 ? 1 : 0;
        $role4 = $this->role_4 ? 1 : 0;

        // Bind data
        $stmt->bind_param("ssiiiiis",
            $this->username,
            $hashed_password,
            $role1,
            $role2,
            $role3,
            $role4,
            $this->note
        );

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Read admins with pagination
    public function read($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // Create query
        $query = "SELECT
                    id,
                    username,
                    role_1,
                    role_2,
                    role_3,
                    role_4,
                    note,
                    time
                FROM " . $this->table . "
                ORDER BY time DESC
                LIMIT ?, ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind params
        $stmt->bind_param("ii", $offset, $limit);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get total count of admins
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    // Get single admin
    public function show($id) {
        // Create query
        $query = "SELECT
                    id,
                    username,
                    role_1,
                    role_2,
                    role_3,
                    role_4,
                    note,
                    time
                FROM " . $this->table . "
                WHERE id = ?
                LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bind_param("i", $id);

        // Execute query
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update admin
    public function update() {
        // Create base query
        $query = "UPDATE " . $this->table . " SET ";
        $params = [];
        $types = "";
        $values = [];

        // Check which fields are being updated
        if(isset($this->username)) {
            $params[] = "username = ?";
            $types .= "s";
            $values[] = htmlspecialchars(strip_tags($this->username));
        }

        if(isset($this->password)) {
            $params[] = "password = ?";
            $types .= "s";
            $values[] = password_hash(htmlspecialchars(strip_tags($this->password)), PASSWORD_DEFAULT);
        }

        if(isset($this->role_1)) {
            $params[] = "role_1 = ?";
            $types .= "i";
            $values[] = $this->role_1 ? 1 : 0;
        }

        if(isset($this->role_2)) {
            $params[] = "role_2 = ?";
            $types .= "i";
            $values[] = $this->role_2 ? 1 : 0;
        }

        if(isset($this->role_3)) {
            $params[] = "role_3 = ?";
            $types .= "i";
            $values[] = $this->role_3 ? 1 : 0;
        }

        if(isset($this->role_4)) {
            $params[] = "role_4 = ?";
            $types .= "i";
            $values[] = $this->role_4 ? 1 : 0;
        }

        if(isset($this->note)) {
            $params[] = "note = ?";
            $types .= "s";
            $values[] = htmlspecialchars(strip_tags($this->note));
        }

        // If no fields to update
        if(empty($params)) {
            return false;
        }

        $query .= implode(", ", $params);
        $query .= " WHERE id = ?";
        $types .= "i";
        $values[] = $this->id;

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$values);

        // Execute query
        if($stmt->execute()) {
            return true;
        }

        printf("Error: %s.\n", $stmt->error);
        return false;
    }

    // Delete admin
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";

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

    // Login admin
    public function login($username, $password) {
        // Create query
        $query = "SELECT 
                    id,
                    username,
                    password,
                    role_1,
                    role_2,
                    role_3,
                    role_4
                FROM " . $this->table . "
                WHERE username = ?
                LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $username = htmlspecialchars(strip_tags($username));

        // Bind username
        $stmt->bind_param("s", $username);

        // Execute query
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if(password_verify($password, $row['password'])) {
                // Remove password from return data
                unset($row['password']);
                return $row;
            }
        }

        return false;
    }

    // Check if username exists
    public function usernameExists($username) {
        // Create query
        $query = "SELECT id FROM " . $this->table . " WHERE username = ? LIMIT 1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $username = htmlspecialchars(strip_tags($username));

        // Bind username
        $stmt->bind_param("s", $username);

        // Execute query
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    // Check admin permissions
    public function hasPermission($roleNumber) {
        if($roleNumber < 1 || $roleNumber > 4) {
            return false;
        }
        $roleField = "role_" . $roleNumber;
        return $this->$roleField == 1;
    }
}
?>