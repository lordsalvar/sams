<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 2) . '/php_errors.log');

// Log script start
error_log("Login.php accessed at " . date('Y-m-d H:i:s'));

// Get the directory of this file
$configPath = dirname(__DIR__) . '/config.php';
error_log("Attempting to load config from: " . $configPath);

if (!file_exists($configPath)) {
    error_log("ERROR: Config file not found at: " . $configPath);
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error', 'details' => 'Config file not found']);
    exit();
}

require_once $configPath;
error_log("Config loaded successfully");

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get request body
$data = getRequestBody();

// Validate input
if (!isset($data['email']) || !isset($data['password'])) {
    sendResponse([
        'success' => false,
        'message' => 'Email and password are required'
    ], 400);
}

$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse([
        'success' => false,
        'message' => 'Invalid email format'
    ], 400);
}

// Connect to database
try {
    error_log("Attempting database connection...");
    error_log("DB_HOST: " . DB_HOST . ", DB_USER: " . DB_USER . ", DB_NAME: " . DB_NAME);
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        sendResponse([
            'success' => false,
            'message' => 'Database connection failed'
        ], 500);
    }
    
    error_log("Database connected successfully");

    // Prepare statement to get user
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    
    if (!$stmt) {
        sendResponse([
            'success' => false,
            'message' => 'Database query preparation failed'
        ], 500);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        sendResponse([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $stmt->close();
        $conn->close();
        sendResponse([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }

    // Generate a simple token (in production, use JWT)
    $token = bin2hex(random_bytes(32));

    // Update last login (optional - you may want to add a last_login column)
    // $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    // $updateStmt->bind_param("i", $user['id']);
    // $updateStmt->execute();
    // $updateStmt->close();

    $stmt->close();
    $conn->close();

    // Return success response
    sendResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'token' => $token
    ], 200);

} catch (Exception $e) {
    error_log("Exception caught in login.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred during login',
        'error' => $e->getMessage() // Include error in development
    ], 500);
}
?>

