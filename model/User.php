<?php

class User
{
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $phone;
    public $address;
    public $created_at;
    public $updated_at;
    public $api_key;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create a new user
    public function create()
    {
        $sql = "INSERT INTO $this->table (username, email, password, phone, address, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $this->created_at = $this->updated_at = date('Y-m-d H:i:s');
        $stmt->bind_param("sssssss", $this->username, $this->email, $this->password, $this->phone, $this->address, $this->created_at, $this->updated_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            return true;
        } else {
            return false;
        }
    }

    // Read a single user
    public function read()
    {
        $sql = "SELECT * FROM $this->table WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->api_key = $row['api_key'];
            return true;
        } else {
            return false;
        }
    }

    // Update a user
    public function update()
    {
        $sql = "UPDATE $this->table 
                SET username = ?, email = ?, password = ?, phone = ?, address = ?, updated_at = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $this->updated_at = date('Y-m-d H:i:s');
        $stmt->bind_param("ssssssi", $this->username, $this->email, $this->password, $this->phone, $this->address, $this->updated_at, $this->id);

        return $stmt->execute();
    }

    // Delete a user
    public function delete()
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->id);

        return $stmt->execute();
    }

    // Get user by email and password
    public static function getByEmailAndPassword($conn, $email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return $user;
        } else {
            return null;
        }
    }

    // Update user API key
    public function updateApiKey($apiKey)
    {
        $sql = "UPDATE $this->table SET api_key = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $apiKey, $this->id);

        return $stmt->execute();
    }
}