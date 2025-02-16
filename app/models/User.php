<?php
require_once __DIR__ . '/../config/config.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    private $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT id, username, password FROM " . $this->table . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                return true;
            }
        }
        
        return false;
    }

    public function create($username, $password) {
        // Check if username exists
        if($this->usernameExists($username)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (username, password) VALUES (:username, :password)";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Bind values
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password_hash);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    private function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function read($id) {
        $query = "SELECT id, username, created_at FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->username = $row['username'];
            
            return true;
        }
        
        return false;
    }

    public function update($id, $username, $current_password, $new_password = null) {
        // Verify current password first
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!password_verify($current_password, $row['password'])) {
                return false;
            }
        } else {
            return false;
        }

        // Update user information
        if($new_password) {
            $query = "UPDATE " . $this->table . " 
                     SET username = :username, password = :password 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":id", $id);
        } else {
            $query = "UPDATE " . $this->table . " 
                     SET username = :username 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":id", $id);
        }

        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    public function delete($id, $password) {
        // Verify password first
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!password_verify($password, $row['password'])) {
                return false;
            }
        } else {
            return false;
        }

        // Delete user
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
