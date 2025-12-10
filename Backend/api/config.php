<?php
// Database configuration (example)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sams_db');

// API configuration
define('API_VERSION', 'v1');
define('API_BASE_URL', 'http://localhost/sams/Backend/api');

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
?>

