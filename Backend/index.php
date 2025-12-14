<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse query string and ensure $_GET is populated
// This is important because when requests go through proxy/router, $_GET might not be auto-populated
$queryString = parse_url($requestUri, PHP_URL_QUERY);
if ($queryString) {
    // Merge with existing $_GET to avoid overwriting
    parse_str($queryString, $parsedQuery);
    $_GET = array_merge($_GET, $parsedQuery);
}

// Remove query string from URI for routing
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if needed
$basePath = '/sams/Backend';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Remove /api prefix if present
if (strpos($uri, '/api') === 0) {
    $uri = substr($uri, 4);
}

// Route to appropriate endpoint
$routes = [
    'GET' => [
        '/test' => 'api/test.php',
        '/test.php' => 'api/test.php',
        '/users' => 'api/users.php',
        '/users.php' => 'api/users.php',
        '/courses' => 'api/courses.php',
        '/courses.php' => 'api/courses.php',
        '/courses/instructors' => 'api/courses.php',
        '/courses/instructors.php' => 'api/courses.php',
        '/courses/students' => 'api/courses.php',
        '/courses/students.php' => 'api/courses.php',
        '/courses/attendance-sessions' => 'api/attendance/sessions.php',
        '/courses/attendance-session' => 'api/attendance/sessions.php',
        '/courses/attendance-logs' => 'api/attendance/logs.php',
        '/courses/attendance-analytics' => 'api/attendance/analytics.php',
    ],
    'POST' => [
        '/auth/login' => 'api/auth/login.php',
        '/auth/login.php' => 'api/auth/login.php',
        '/users' => 'api/users.php',
        '/users.php' => 'api/users.php',
        '/courses' => 'api/courses.php',
        '/courses.php' => 'api/courses.php',
        '/courses/enroll' => 'api/enrollments.php',
        '/courses/enroll.php' => 'api/enrollments.php',
        '/courses/attendance-session' => 'api/attendance/sessions.php',
        '/courses/attendance-scan' => 'api/attendance/scan.php',
    ],
    'PUT' => [
        '/users' => 'api/users.php',
        '/users.php' => 'api/users.php',
        '/courses' => 'api/courses.php',
        '/courses.php' => 'api/courses.php',
    ],
    'DELETE' => [
        '/users' => 'api/users.php',
        '/users.php' => 'api/users.php',
        '/courses' => 'api/courses.php',
        '/courses.php' => 'api/courses.php',
        '/courses/unenroll' => 'api/enrollments.php',
        '/courses/unenroll.php' => 'api/enrollments.php',
    ],
];

// Check if route exists
if (isset($routes[$requestMethod][$uri])) {
    $file = $routes[$requestMethod][$uri];
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
} else {
    // Try direct file access
    $directPath = 'api' . $uri;
    if (file_exists($directPath)) {
        require_once $directPath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found', 'uri' => $uri]);
    }
}
?>

