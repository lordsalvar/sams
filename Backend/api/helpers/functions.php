<?php
// Common helper functions used across the API
// Make sure config is loaded first
if (!defined('DB_HOST')) {
    $configPath = dirname(__DIR__) . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

// DB connection
function db()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }
    // Ensure MySQL session uses GMT+8 to align with PHP timezone
    $conn->query("SET time_zone = '+08:00'");
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

// Simple UUIDv4 generator (no external deps)
function uuidv4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Set CORS headers
function setCorsHeaders()
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=UTF-8");
}

// Handle OPTIONS preflight requests
function handleOptionsRequest()
{
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

