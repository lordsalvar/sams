<?php
/**
 * User Management Service
 * Handles user CRUD operations
 */

class UserService {
    
    public function getAllUsers($query, $body) {
        $conn = db();
        $sql = "SELECT id, name, email, role, created_at FROM users ORDER BY id DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $conn->close();
        return ['success' => true, 'data' => $rows];
    }
    
    public function createUser($body) {
        if (!isset($body['name'], $body['email'], $body['password'], $body['role'])) {
            return ['success' => false, 'message' => 'name, email, password, and role are required'];
        }
        
        $conn = db();
        $name = trim($body['name']);
        $email = trim($body['email']);
        $password = trim($body['password']);
        $role = strtolower(trim($body['role']));
        
        // Validate role
        if (!in_array($role, ['admin', 'instructor', 'student'])) {
            $conn->close();
            return ['success' => false, 'message' => 'Invalid role. Must be admin, instructor, or student'];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Email already exists'];
        }
        $stmt->close();
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Failed to create user'];
        }
        $id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        
        return ['success' => true, 'data' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role]];
    }
    
    public function updateUser($body) {
        if (!isset($body['id'], $body['name'], $body['email'], $body['role'])) {
            return ['success' => false, 'message' => 'id, name, email, and role are required'];
        }
        
        $conn = db();
        $id = (int)$body['id'];
        $name = trim($body['name']);
        $email = trim($body['email']);
        $role = strtolower(trim($body['role']));
        
        // Validate role
        if (!in_array($role, ['admin', 'instructor', 'student'])) {
            $conn->close();
            return ['success' => false, 'message' => 'Invalid role. Must be admin, instructor, or student'];
        }
        
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Email already exists'];
        }
        $stmt->close();
        
        // Update user (with or without password)
        if (isset($body['password']) && !empty($body['password'])) {
            $password = trim($body['password']);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $role, $id);
        }
        
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            // Check if user exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows === 0) {
                $stmt->close();
                $conn->close();
                return ['success' => false, 'message' => 'User not found'];
            }
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'User updated'];
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'User updated'];
    }
    
    public function deleteUser($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'id is required'];
        }
        
        $conn = db();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'User not deleted or not found'];
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'User deleted'];
    }
    
    public function getInstructors() {
        $conn = db();
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'data' => $rows];
    }
    
    public function getStudents() {
        $conn = db();
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'data' => $rows];
    }
}
?>

