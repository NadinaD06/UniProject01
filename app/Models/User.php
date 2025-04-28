<?php
namespace App\Models;

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password_hash, age, bio, interests)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['age'] ?? null,
            $data['bio'] ?? null,
            $data['interests'] ?? null
        ]);
    }
    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'password_hash') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (isset($data['password_hash'])) {
            $fields[] = "password_hash = ?";
            $values[] = $data['password_hash'];
        }
        
        $values[] = $id;
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($values);
    }
    
    public function createPasswordResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET reset_token = ?, reset_token_expires = ? 
            WHERE email = ?
        ");
        
        return $stmt->execute([$token, $expires, $email]) ? $token : false;
    }
    
    public function validatePasswordResetToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE reset_token = ? 
            AND reset_token_expires > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    public function resetPassword($token, $newPassword) {
        $user = $this->validatePasswordResetToken($token);
        if (!$user) {
            return false;
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = ?
        ");
        
        return $stmt->execute([$passwordHash, $user['id']]);
    }
} 