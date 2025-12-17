<?php
// Attendance Sessions API
// Handles: GET /courses/attendance-sessions (list all or by course)
//          GET /courses/attendance-session (get latest for course)
//          POST /courses/attendance-session (create new session)

$configPath = dirname(__DIR__, 2) . '/api/config.php';
require_once $configPath;
require_once dirname(__DIR__) . '/helpers/functions.php';

setCorsHeaders();
handleOptionsRequest();

$method = $_SERVER['REQUEST_METHOD'];
$body = getRequestBody();
if (!is_array($body)) {
    $body = [];
}
$conn = db();

// Detect route
$isAttendanceSessionsList = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-sessions') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-sessions') !== false;

$isAttendanceSession = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-session') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-session') !== false;

// Handle attendance-sessions (plural) - list all sessions or by course
if ($isAttendanceSessionsList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Parse query string to get role
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $requestedByRole = isset($query['requested_by_role']) ? strtolower(trim((string)$query['requested_by_role'])) : '';
    $studentEmail = isset($query['student_email']) ? trim((string)$query['student_email']) : '';
    
    // Add role to body for requireRole function
    if ($requestedByRole) {
        $body['requested_by_role'] = $requestedByRole;
    }
    
    requireRole(['admin', 'instructor', 'student'], $body);

    $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
    
    // For students, verify they are enrolled in the course
    if ($requestedByRole === 'student' && $courseId > 0 && !empty($studentEmail)) {
        $conn = db();
        $stmt = $conn->prepare("
            SELECT e.id FROM enrollments e
            JOIN users u ON u.id = e.student_id
            WHERE e.course_id = ? AND u.email = ?
        ");
        $stmt->bind_param("is", $courseId, $studentEmail);
        $stmt->execute();
        $enrollmentResult = $stmt->get_result();
        if ($enrollmentResult->num_rows === 0) {
            $stmt->close();
            sendResponse(['success' => false, 'message' => 'You are not enrolled in this course'], 403);
        }
        $stmt->close();
    }

    if ($courseId) {
        // Get sessions for a specific course
        $stmt = $conn->prepare("
            SELECT s.id, s.course_id, s.token, s.expires_at, s.created_by_email, s.created_at,
                   c.name AS course_name,
                   (s.expires_at <= NOW()) AS is_expired,
                   (SELECT COUNT(*) FROM attendance_logs al WHERE al.session_id = s.id) AS scanned_count,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = s.course_id) AS enrolled_count
            FROM attendance_sessions s
            JOIN courses c ON c.id = s.course_id
            WHERE s.course_id = ?
            ORDER BY s.id DESC
        ");
        if (!$stmt) {
            sendResponse(['success' => false, 'message' => 'Database query preparation failed: ' . $conn->error], 500);
        }
        $stmt->bind_param("i", $courseId);
    } else {
        // Get all sessions across all courses
        $stmt = $conn->prepare("
            SELECT s.id, s.course_id, s.token, s.expires_at, s.created_by_email, s.created_at,
                   c.name AS course_name,
                   (s.expires_at <= NOW()) AS is_expired,
                   (SELECT COUNT(*) FROM attendance_logs al WHERE al.session_id = s.id) AS scanned_count,
                   (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = s.course_id) AS enrolled_count
            FROM attendance_sessions s
            JOIN courses c ON c.id = s.course_id
            ORDER BY s.id DESC
        ");
        if (!$stmt) {
            sendResponse(['success' => false, 'message' => 'Database query preparation failed: ' . $conn->error], 500);
        }
    }
    
    if (!$stmt->execute()) {
        sendResponse(['success' => false, 'message' => 'Database query execution failed: ' . $stmt->error], 500);
    }
    
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    sendResponse(['success' => true, 'data' => $rows]);
}

// Handle attendance-session (singular) - get latest or create new
if ($isAttendanceSession) {
    if (!in_array($method, ['GET', 'POST'], true)) {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Parse query string to get role
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $requestedByRole = isset($query['requested_by_role']) ? strtolower(trim((string)$query['requested_by_role'])) : '';
    $studentEmail = isset($query['student_email']) ? trim((string)$query['student_email']) : '';
    
    // Add role to body for requireRole function
    if ($requestedByRole) {
        $body['requested_by_role'] = $requestedByRole;
    }
    
    // Only allow GET for students, POST requires admin/instructor
    if ($method === 'POST') {
        requireRole(['admin', 'instructor'], $body);
    } else {
        requireRole(['admin', 'instructor', 'student'], $body);
    }

    if ($method === 'GET') {
        $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
        if (!$courseId) {
            sendResponse(['success' => false, 'message' => 'course_id is required'], 400);
        }
        
        // For students, verify they are enrolled in the course
        if ($requestedByRole === 'student' && !empty($studentEmail)) {
            $stmt = $conn->prepare("
                SELECT e.id FROM enrollments e
                JOIN users u ON u.id = e.student_id
                WHERE e.course_id = ? AND u.email = ?
            ");
            $stmt->bind_param("is", $courseId, $studentEmail);
            $stmt->execute();
            $enrollmentResult = $stmt->get_result();
            if ($enrollmentResult->num_rows === 0) {
                $stmt->close();
                sendResponse(['success' => false, 'message' => 'You are not enrolled in this course'], 403);
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("
            SELECT s.id, s.course_id, s.token, s.expires_at, s.created_by_email, c.name AS course_name,
                   (s.expires_at <= NOW()) AS is_expired
            FROM attendance_sessions s
            JOIN courses c ON c.id = s.course_id
            WHERE s.course_id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        sendResponse(['success' => true, 'data' => $session ?: null]);
    }

    // POST create a new session
    $courseId = isset($body['course_id']) ? (int)$body['course_id'] : 0;
    if (!$courseId) {
        sendResponse(['success' => false, 'message' => 'course_id is required'], 400);
    }

    // Validate course exists
    $stmt = $conn->prepare("SELECT id, name FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $courseResult = $stmt->get_result();
    if ($courseResult->num_rows === 0) {
        sendResponse(['success' => false, 'message' => 'Course not found'], 404);
    }
    $courseRow = $courseResult->fetch_assoc();
    $stmt->close();

    $token = uuidv4();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $creatorEmail = isset($body['requested_by_email']) ? trim((string)$body['requested_by_email']) : '';

    $stmt = $conn->prepare("
        INSERT INTO attendance_sessions (course_id, token, expires_at, created_by_email)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $courseId, $token, $expiresAt, $creatorEmail);
    $ok = $stmt->execute();
    if (!$ok) {
        sendResponse(['success' => false, 'message' => 'Failed to create attendance session'], 500);
    }
    $stmt->close();

    sendResponse(['success' => true, 'data' => [
        'course_id' => $courseId,
        'course_name' => $courseRow['name'],
        'token' => $token,
        'expires_at' => $expiresAt,
    ]]);
}

