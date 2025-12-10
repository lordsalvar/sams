<?php
// Users management API

$configPath = dirname(__DIR__) . '/api/config.php';
require_once $configPath;

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// DB connection
function db()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }
    return $conn;
}

// Basic role guard (expects role from client for now; supports body or query param)
function requireRole(array $allowed, $body)
{
    $role = '';
    if (is_array($body) && isset($body['requested_by_role'])) {
        $role = $body['requested_by_role'];
    } elseif (isset($_GET['requested_by_role'])) {
        $role = $_GET['requested_by_role'];
    }
    $role = strtolower(trim((string)$role));
    if (!in_array($role, array_map('strtolower', $allowed), true)) {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$body = getRequestBody();
if (!is_array($body)) {
    $body = [];
}
$conn = db();

switch ($method) {
    case 'GET':
        // Only admins can view users list
        requireRole(['admin'], $body);
        
        $sql = "SELECT id, name, email, role, created_at FROM users ORDER BY id DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        sendResponse(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        // Only admins can create users
        requireRole(['admin'], $body);
        
        if (!isset($body['name'], $body['email'], $body['password'], $body['role'])) {
            sendResponse(['success' => false, 'message' => 'name, email, password, and role are required'], 400);
        }
        
        $name = trim($body['name']);
        $email = trim($body['email']);
        $password = trim($body['password']);
        $role = strtolower(trim($body['role']));
        
        // Validate role
        if (!in_array($role, ['admin', 'instructor', 'student'])) {
            sendResponse(['success' => false, 'message' => 'Invalid role. Must be admin, instructor, or student'], 400);
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
        }
        $stmt->close();
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
        $ok = $stmt->execute();
        if (!$ok) {
            sendResponse(['success' => false, 'message' => 'Failed to create user'], 500);
        }
        $id = $stmt->insert_id;
        $stmt->close();
        
        sendResponse(['success' => true, 'data' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role]], 201);
        break;

    case 'PUT':
        // Only admins can update users
        requireRole(['admin'], $body);
        
        if (!isset($body['id'], $body['name'], $body['email'], $body['role'])) {
            sendResponse(['success' => false, 'message' => 'id, name, email, and role are required'], 400);
        }
        
        $id = (int)$body['id'];
        $name = trim($body['name']);
        $email = trim($body['email']);
        $role = strtolower(trim($body['role']));
        
        // Validate role
        if (!in_array($role, ['admin', 'instructor', 'student'])) {
            sendResponse(['success' => false, 'message' => 'Invalid role. Must be admin, instructor, or student'], 400);
        }
        
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
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
                sendResponse(['success' => false, 'message' => 'User not found'], 404);
            }
            // User exists but no changes were made
            $stmt->close();
            sendResponse(['success' => true, 'message' => 'User updated']);
        }
        $stmt->close();
        sendResponse(['success' => true, 'message' => 'User updated']);
        break;

    case 'DELETE':
        // Only admins can delete users
        requireRole(['admin'], $body);
        
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
        $id = isset($query['id']) ? (int)$query['id'] : null;
        if (!$id) {
            sendResponse(['success' => false, 'message' => 'id is required'], 400);
        }
        
        // Prevent deleting yourself (optional safety check)
        // You could add this check if you have user session management
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            sendResponse(['success' => false, 'message' => 'User not deleted or not found'], 404);
        }
        $stmt->close();
        sendResponse(['success' => true, 'message' => 'User deleted']);
        break;

    default:
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

