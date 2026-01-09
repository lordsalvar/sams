<?php
/**
 * Shared Configuration for All Microservices
 */

// Global timezone (GMT+8)
date_default_timezone_set('Asia/Manila');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sams_db');

// API configuration
define('API_VERSION', 'v1');
define('API_BASE_URL', 'http://localhost/sams/Backend/gateway');

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

// Helper function to get request body
function getRequestBody() {
    return json_decode(file_get_contents('php://input'), true);
}

// Database connection function
function db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        sendResponse([
            'success' => false,
            'message' => 'Database connection failed',
            'error' => $conn->connect_error
        ], 500);
    }
    // Ensure MySQL session uses GMT+8 to align with PHP timezone
    $conn->query("SET time_zone = '+08:00'");
    return $conn;
}

// Role-based authorization
function requireRole(array $allowed, $body) {
    $role = '';
    
    // Check request body first (for POST, PUT, etc.)
    if (is_array($body) && isset($body['requested_by_role']) && !empty($body['requested_by_role'])) {
        $role = $body['requested_by_role'];
    } 
    // Check $_GET superglobal
    elseif (isset($_GET['requested_by_role']) && !empty($_GET['requested_by_role'])) {
        $role = $_GET['requested_by_role'];
    }
    // Parse query string from REQUEST_URI as fallback
    else {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = parse_url($requestUri, PHP_URL_QUERY);
        if ($queryString) {
            parse_str($queryString, $query);
            if (isset($query['requested_by_role']) && !empty($query['requested_by_role'])) {
                $role = $query['requested_by_role'];
            }
        }
    }
    
    $role = strtolower(trim((string)$role));
    
    if (empty($role)) {
        sendResponse([
            'success' => false,
            'message' => 'Role is required. Please provide requested_by_role parameter.'
        ], 403);
    }
    
    $allowedLower = array_map('strtolower', $allowed);
    if (!in_array($role, $allowedLower, true)) {
        sendResponse([
            'success' => false,
            'message' => 'Unauthorized. Required role: ' . implode(' or ', $allowed) . '. Provided: ' . $role
        ], 403);
    }
}

// UUIDv4 generator
function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Set CORS headers
function setCorsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=UTF-8");
}

// Handle OPTIONS preflight requests
function handleOptionsRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>

