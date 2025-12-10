<?php
// Test endpoint to verify API is working
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple GET endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = [
        'success' => true,
        'message' => 'PHP REST API is working! React frontend connected successfully.',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => 'GET'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

