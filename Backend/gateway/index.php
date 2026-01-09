<?php
/**
 * API Gateway - Single Entry Point
 * Routes all requests to appropriate microservice classes
 * Flow: UI → API Gateway → Microservices → Database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php_errors.log');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Load configuration and service classes
require_once dirname(__DIR__) . '/microservices/config.php';
require_once dirname(__DIR__) . '/microservices/AuthService.php';
require_once dirname(__DIR__) . '/microservices/UserService.php';
require_once dirname(__DIR__) . '/microservices/CourseService.php';
require_once dirname(__DIR__) . '/microservices/AttendanceService.php';
require_once dirname(__DIR__) . '/microservices/EnrollmentService.php';
require_once dirname(__DIR__) . '/microservices/TestService.php';

// Enable CORS
setCorsHeaders();
handleOptionsRequest();

// Get request details
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse query string
$queryString = parse_url($requestUri, PHP_URL_QUERY);
if ($queryString) {
    parse_str($queryString, $parsedQuery);
    $_GET = array_merge($_GET, $parsedQuery);
}

// Remove query string from URI for routing
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if needed
$basePath = '/sams/Backend/gateway';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Remove /api prefix if present
if (strpos($uri, '/api') === 0) {
    $uri = substr($uri, 4);
}

// Ensure URI starts with /
if (empty($uri) || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

// Get request body
$body = getRequestBody();
if (!is_array($body)) {
    $body = [];
}

// Parse query params
$query = [];
if (!empty($_GET)) {
    $query = $_GET;
} else {
    $queryString = parse_url($requestUri, PHP_URL_QUERY);
    if ($queryString) {
        parse_str($queryString, $query);
    }
}

// Route to service and method
$response = null;
$statusCode = 200;

try {
    // Test endpoint
    if ($uri === '/test' && $requestMethod === 'GET') {
        $service = new TestService();
        $response = $service->test();
    }
    // Authentication
    elseif ($uri === '/auth/login' && $requestMethod === 'POST') {
        $service = new AuthService();
        $response = $service->login($body);
        $statusCode = isset($response['success']) && $response['success'] ? 200 : 401;
    }
    // Users
    elseif ($uri === '/users' && $requestMethod === 'GET') {
        requireRole(['admin'], $body);
        $service = new UserService();
        $response = $service->getAllUsers($query, $body);
    }
    elseif ($uri === '/users' && $requestMethod === 'POST') {
        requireRole(['admin'], $body);
        $service = new UserService();
        $response = $service->createUser($body);
        $statusCode = 201;
    }
    elseif ($uri === '/users' && $requestMethod === 'PUT') {
        requireRole(['admin'], $body);
        $service = new UserService();
        $response = $service->updateUser($body);
    }
    elseif ($uri === '/users' && $requestMethod === 'DELETE') {
        requireRole(['admin'], $body);
        $service = new UserService();
        $id = isset($query['id']) ? (int)$query['id'] : null;
        $response = $service->deleteUser($id);
    }
    // Courses - Instructors/Students lists
    elseif (strpos($uri, '/courses/instructors') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor'], $body);
        $service = new UserService();
        $response = $service->getInstructors();
    }
    elseif (strpos($uri, '/courses/students') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor'], $body);
        $service = new UserService();
        $response = $service->getStudents();
    }
    // Courses CRUD
    elseif ($uri === '/courses' && $requestMethod === 'GET') {
        $service = new CourseService();
        if (isset($query['id'])) {
            $courseId = (int)$query['id'];
            $requestedByRole = isset($query['requested_by_role']) ? strtolower(trim((string)$query['requested_by_role'])) : '';
            $studentEmail = isset($query['student_email']) ? trim((string)$query['student_email']) : '';
            $response = $service->getCourseById($courseId, $requestedByRole, $studentEmail);
        } else {
            $response = $service->getCourses($query, $body);
        }
    }
    elseif ($uri === '/courses' && $requestMethod === 'POST') {
        requireRole(['admin', 'instructor'], $body);
        $service = new CourseService();
        $response = $service->createCourse($body);
        $statusCode = 201;
    }
    elseif ($uri === '/courses' && $requestMethod === 'PUT') {
        requireRole(['admin', 'instructor'], $body);
        $service = new CourseService();
        $response = $service->updateCourse($body);
    }
    elseif ($uri === '/courses' && $requestMethod === 'DELETE') {
        requireRole(['admin', 'instructor'], $body);
        $service = new CourseService();
        $id = isset($query['id']) ? (int)$query['id'] : null;
        $response = $service->deleteCourse($id);
    }
    // Attendance
    elseif (strpos($uri, '/courses/attendance-sessions') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor', 'student'], $body);
        $service = new AttendanceService();
        $response = $service->getSessions($query);
    }
    elseif (strpos($uri, '/courses/attendance-session') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor', 'student'], $body);
        $service = new AttendanceService();
        $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
        $response = $service->getLatestSession($courseId);
    }
    elseif (strpos($uri, '/courses/attendance-session') !== false && $requestMethod === 'POST') {
        requireRole(['admin', 'instructor'], $body);
        $service = new AttendanceService();
        $response = $service->createSession($body);
    }
    elseif (strpos($uri, '/courses/attendance-scan') !== false && $requestMethod === 'POST') {
        requireRole(['student'], $body);
        $service = new AttendanceService();
        $response = $service->scanAttendance($body);
    }
    elseif (strpos($uri, '/courses/attendance-logs') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor'], $body);
        $service = new AttendanceService();
        $response = $service->getLogs($query);
    }
    elseif (strpos($uri, '/courses/attendance-analytics') !== false && $requestMethod === 'GET') {
        requireRole(['admin', 'instructor'], $body);
        $service = new AttendanceService();
        $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
        $response = $service->getAnalytics($courseId);
    }
    // Enrollments
    elseif (strpos($uri, '/courses/enroll') !== false && $requestMethod === 'POST') {
        requireRole(['admin', 'instructor'], $body);
        $service = new EnrollmentService();
        $response = $service->enrollStudent($body);
    }
    elseif (strpos($uri, '/courses/unenroll') !== false && $requestMethod === 'DELETE') {
        requireRole(['admin', 'instructor'], $body);
        $service = new EnrollmentService();
        $enrollmentId = isset($query['enrollment_id']) ? (int)$query['enrollment_id'] : null;
        $response = $service->unenrollStudent($enrollmentId);
    }
    // Route not found
    else {
        $response = [
            'success' => false,
            'message' => 'Route not found',
            'uri' => $uri,
            'method' => $requestMethod
        ];
        $statusCode = 404;
    }
    
    // Determine status code from response if not set
    if ($statusCode === 200 && isset($response['success']) && !$response['success']) {
        if (isset($response['message'])) {
            if (strpos($response['message'], 'not found') !== false) {
                $statusCode = 404;
            } elseif (strpos($response['message'], 'Unauthorized') !== false || strpos($response['message'], 'Role is required') !== false) {
                $statusCode = 403;
            } elseif (strpos($response['message'], 'required') !== false) {
                $statusCode = 400;
            } elseif (strpos($response['message'], 'already exists') !== false || strpos($response['message'], 'already enrolled') !== false) {
                $statusCode = 409;
            }
        }
    }
    
    sendResponse($response, $statusCode);
    
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ], 500);
}
?>
