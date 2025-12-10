<?php
// Courses and enrollment API

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

// Detect enroll action (mapped from /courses/enroll route)
$isEnroll = strpos($_SERVER['REQUEST_URI'], '/courses/enroll') !== false 
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/enroll') !== false;

// Detect unenroll action (mapped from /courses/unenroll route)
$isUnenroll = strpos($_SERVER['REQUEST_URI'], '/courses/unenroll') !== false 
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/unenroll') !== false;

// Attendance session creation (QR)
$isAttendanceSession = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-session') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-session') !== false;

// Attendance sessions list per course
$isAttendanceSessionsList = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-sessions') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-sessions') !== false;

// Attendance analytics per course
$isAttendanceAnalytics = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-analytics') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-analytics') !== false;

// Attendance scan (student)
$isAttendanceScan = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-scan') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-scan') !== false;

// Attendance logs for a session
$isAttendanceLogs = strpos($_SERVER['REQUEST_URI'], '/courses/attendance-logs') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/attendance-logs') !== false;

// Detect instructor directory via path or query
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $queryParams);
$isInstructorList = strpos($_SERVER['REQUEST_URI'], '/courses/instructors') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses/instructors.php') !== false
    || isset($queryParams['instructors'])
    || (isset($queryParams['list']) && strtolower((string)$queryParams['list']) === 'instructors');

// Detect students list via path or query
$isStudentsList = strpos($_SERVER['REQUEST_URI'], '/courses/students') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses/students.php') !== false
    || isset($queryParams['students'])
    || (isset($queryParams['list']) && strtolower((string)$queryParams['list']) === 'students');

$method = $_SERVER['REQUEST_METHOD'];
$body = getRequestBody();
if (!is_array($body)) {
    $body = [];
}
$conn = db();

if ($isAttendanceSession) {
    // Support GET (fetch latest active) and POST (create new)
    if (!in_array($method, ['GET', 'POST'], true)) {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin', 'instructor'], $body);

    if ($method === 'GET') {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
        $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
        if (!$courseId) {
            sendResponse(['success' => false, 'message' => 'course_id is required'], 400);
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

if ($isAttendanceSessionsList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin', 'instructor'], $body);

    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
    if (!$courseId) {
        sendResponse(['success' => false, 'message' => 'course_id is required'], 400);
    }

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
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    sendResponse(['success' => true, 'data' => $rows]);
}

if ($isAttendanceAnalytics) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin', 'instructor'], $body);

    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
    if (!$courseId) {
        sendResponse(['success' => false, 'message' => 'course_id is required'], 400);
    }

    // Course summary
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM attendance_sessions s WHERE s.course_id = ?) AS sessions_count,
            (SELECT COUNT(*) FROM attendance_sessions s WHERE s.course_id = ? AND s.expires_at > NOW()) AS active_sessions,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = ?) AS enrolled_count,
            (SELECT MAX(created_at) FROM attendance_sessions s WHERE s.course_id = ?) AS last_session_at
    ");
    $stmt->bind_param("iiii", $courseId, $courseId, $courseId, $courseId);
    $stmt->execute();
    $summaryResult = $stmt->get_result();
    $summary = $summaryResult->fetch_assoc();
    $stmt->close();

    // Sessions breakdown
    $stmt = $conn->prepare("
        SELECT s.id, s.token, s.created_at, s.expires_at, s.created_by_email,
               (s.expires_at <= NOW()) AS is_expired,
               (SELECT COUNT(*) FROM attendance_logs al WHERE al.session_id = s.id) AS scanned_count,
               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = s.course_id) AS enrolled_count
        FROM attendance_sessions s
        WHERE s.course_id = ?
        ORDER BY s.id DESC
    ");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $sessionsResult = $stmt->get_result();
    $sessions = [];
    while ($row = $sessionsResult->fetch_assoc()) {
        $sessions[] = $row;
    }
    $stmt->close();

    // Student attendance aggregates
    $stmt = $conn->prepare("
        SELECT u.id AS student_id, u.name AS student_name, u.email AS student_email,
               COUNT(DISTINCT s.id) AS total_sessions,
               COUNT(DISTINCT al.session_id) AS attended_sessions
        FROM enrollments e
        JOIN users u ON u.id = e.student_id
        LEFT JOIN attendance_sessions s ON s.course_id = e.course_id
        LEFT JOIN attendance_logs al ON al.session_id = s.id AND al.student_id = e.student_id
        WHERE e.course_id = ?
        GROUP BY u.id, u.name, u.email
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $studentsResult = $stmt->get_result();
    $students = [];
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    sendResponse(['success' => true, 'data' => [
        'summary' => $summary,
        'sessions' => $sessions,
        'students' => $students,
    ]]);
}

if ($isAttendanceLogs) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin', 'instructor'], $body);

    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $token = isset($query['token']) ? trim((string)$query['token']) : '';
    $sessionId = isset($query['session_id']) ? (int)$query['session_id'] : 0;

    if ($token === '' && !$sessionId) {
        sendResponse(['success' => false, 'message' => 'token or session_id is required'], 400);
    }

    // Resolve session
    if ($token !== '') {
        $stmt = $conn->prepare("
            SELECT s.id, s.course_id, s.token, s.expires_at, c.name AS course_name
            FROM attendance_sessions s
            JOIN courses c ON c.id = s.course_id
            WHERE s.token = ?
        ");
        $stmt->bind_param("s", $token);
    } else {
        $stmt = $conn->prepare("
            SELECT s.id, s.course_id, s.token, s.expires_at, c.name AS course_name
            FROM attendance_sessions s
            JOIN courses c ON c.id = s.course_id
            WHERE s.id = ?
        ");
        $stmt->bind_param("i", $sessionId);
    }
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    $session = $sessionResult->fetch_assoc();
    $stmt->close();

    if (!$session) {
        sendResponse(['success' => false, 'message' => 'Attendance session not found'], 404);
    }

    // Fetch logs
    $includeStudents = isset($query['include_students']) ? (int)$query['include_students'] : 0;

    $stmt = $conn->prepare("
        SELECT al.id, al.scanned_at, u.id AS student_id, u.name AS student_name, u.email AS student_email
        FROM attendance_logs al
        JOIN users u ON u.id = al.student_id
        WHERE al.session_id = ?
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("i", $session['id']);
    $stmt->execute();
    $logsResult = $stmt->get_result();
    $logs = [];
    while ($row = $logsResult->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();

    $roster = [];
    if ($includeStudents) {
        $stmt = $conn->prepare("
            SELECT u.id AS student_id, u.name AS student_name, u.email AS student_email,
                   al.scanned_at,
                   CASE WHEN al.id IS NULL THEN 0 ELSE 1 END AS present
            FROM enrollments e
            JOIN users u ON u.id = e.student_id
            LEFT JOIN attendance_logs al ON al.session_id = ? AND al.student_id = e.student_id
            WHERE e.course_id = ?
            ORDER BY u.name ASC
        ");
        $stmt->bind_param("ii", $session['id'], $session['course_id']);
        $stmt->execute();
        $rosterResult = $stmt->get_result();
        while ($row = $rosterResult->fetch_assoc()) {
            $roster[] = $row;
        }
        $stmt->close();
    }

    sendResponse(['success' => true, 'data' => [
        'session' => $session,
        'logs' => $logs,
        'roster' => $includeStudents ? $roster : null,
    ]]);
}

if ($isAttendanceScan) {
    if ($method !== 'POST') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['student'], $body);

    $token = isset($body['token']) ? trim((string)$body['token']) : '';
    $studentEmail = isset($body['student_email']) ? trim((string)$body['student_email']) : '';
    if ($token === '' || $studentEmail === '') {
        sendResponse(['success' => false, 'message' => 'token and student_email are required'], 400);
    }

    // Find session
    $stmt = $conn->prepare("
        SELECT s.id, s.course_id, s.expires_at, c.name AS course_name
        FROM attendance_sessions s
        JOIN courses c ON c.id = s.course_id
        WHERE s.token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $sessionResult = $stmt->get_result();
    $session = $sessionResult->fetch_assoc();
    $stmt->close();

    if (!$session) {
        sendResponse(['success' => false, 'message' => 'Invalid or unknown attendance token'], 404);
    }
    if (strtotime($session['expires_at']) < time()) {
        sendResponse(['success' => false, 'message' => 'Attendance session has expired'], 410);
    }

    // Get student id
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
    $stmt->bind_param("s", $studentEmail);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    $student = $studentResult->fetch_assoc();
    $stmt->close();

    if (!$student) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    // Ensure enrollment
    $stmt = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $session['course_id'], $student['id']);
    $stmt->execute();
    $enrolled = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$enrolled) {
        sendResponse(['success' => false, 'message' => 'You are not enrolled in this course'], 403);
    }

    // Record attendance (idempotent per session/student)
    $stmt = $conn->prepare("INSERT IGNORE INTO attendance_logs (session_id, student_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $session['id'], $student['id']);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Attendance recorded', 'data' => [
        'course_id' => $session['course_id'],
        'course_name' => $session['course_name'] ?? '',
    ]]);
}

if ($isEnroll) {
    if ($method !== 'POST') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin', 'instructor'], $body);

    if (!isset($body['course_id'], $body['student_email'])) {
        sendResponse(['success' => false, 'message' => 'course_id and student_email are required'], 400);
    }

    $courseId = (int)$body['course_id'];
    $studentEmail = trim($body['student_email']);

    // Validate course
    $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $courseResult = $stmt->get_result();
    if ($courseResult->num_rows === 0) {
        sendResponse(['success' => false, 'message' => 'Course not found'], 404);
    }
    $stmt->close();

    // Validate student
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
    $stmt->bind_param("s", $studentEmail);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    if ($studentResult->num_rows === 0) {
        sendResponse(['success' => false, 'message' => 'Student not found or not a student'], 404);
    }
    $studentRow = $studentResult->fetch_assoc();
    $studentId = (int)$studentRow['id'];
    $stmt->close();

    // Prevent duplicate enrollment
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $courseId, $studentId);
    $stmt->execute();
    $dupResult = $stmt->get_result();
    if ($dupResult->num_rows > 0) {
        sendResponse(['success' => false, 'message' => 'Student already enrolled'], 409);
    }
    $stmt->close();

    // Enroll
    $stmt = $conn->prepare("INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $courseId, $studentId);
    $ok = $stmt->execute();
    if (!$ok) {
        sendResponse(['success' => false, 'message' => 'Failed to enroll student'], 500);
    }
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Student enrolled']);
}

if ($isUnenroll) {
    if ($method !== 'DELETE') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    requireRole(['admin'], $body);

    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
    $enrollmentId = isset($query['enrollment_id']) ? (int)$query['enrollment_id'] : null;
    
    if (!$enrollmentId) {
        sendResponse(['success' => false, 'message' => 'enrollment_id is required'], 400);
    }

    // Delete the enrollment
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->bind_param("i", $enrollmentId);
    $ok = $stmt->execute();
    if (!$ok || $stmt->affected_rows === 0) {
        sendResponse(['success' => false, 'message' => 'Enrollment not found or already removed'], 404);
    }
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Student unenrolled successfully']);
}

if ($isInstructorList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    // Only admins/instructors can request the instructor directory (admin uses it to assign)
    requireRole(['admin', 'instructor'], $body);

    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'instructor' ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    sendResponse(['success' => true, 'data' => $rows]);
}

if ($isStudentsList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    // Only admins/instructors can request the students list (for enrollment)
    requireRole(['admin', 'instructor'], $body);

    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    sendResponse(['success' => true, 'data' => $rows]);
}

switch ($method) {
    case 'GET':
        // Check if requesting a specific course by ID
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
        if (isset($query['id'])) {
            $courseId = (int)$query['id'];
            
            // Get course details with instructor name
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.code, c.instructor_email, c.created_at, c.updated_at,
                       u.name as instructor_name
                FROM courses c
                LEFT JOIN users u ON u.email = c.instructor_email AND u.role = 'instructor'
                WHERE c.id = ?
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $courseResult = $stmt->get_result();
            
            if ($courseResult->num_rows === 0) {
                sendResponse(['success' => false, 'message' => 'Course not found'], 404);
            }
            
            $course = $courseResult->fetch_assoc();
            $stmt->close();
            
            // Get enrolled students
            $stmt = $conn->prepare("
                SELECT e.id, e.student_id, u.name as student_name, u.email as student_email, e.created_at as enrolled_at
                FROM enrollments e
                JOIN users u ON u.id = e.student_id
                WHERE e.course_id = ?
                ORDER BY u.name ASC
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $studentsResult = $stmt->get_result();
            
            $students = [];
            while ($row = $studentsResult->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
            
            sendResponse(['success' => true, 'data' => ['course' => $course, 'students' => $students]]);
        }
        
        // Otherwise, return courses (filtered by role)
        // Get the requested_by_role from query params
        $requestedByRole = isset($query['requested_by_role']) ? strtolower(trim((string)$query['requested_by_role'])) : '';
        $instructorEmail = isset($query['instructor_email']) ? trim((string)$query['instructor_email']) : '';
        
        // If instructor, only show their courses
        if ($requestedByRole === 'instructor' && !empty($instructorEmail)) {
            $sql = "SELECT c.id, c.name, c.code, c.instructor_email, c.created_at, c.updated_at,
                           COUNT(e.id) as enrollment_count
                    FROM courses c
                    LEFT JOIN enrollments e ON e.course_id = c.id
                    WHERE c.instructor_email = ?
                    GROUP BY c.id
                    ORDER BY c.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $instructorEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();
            sendResponse(['success' => true, 'data' => $rows]);
        }
        
        // Admin or other roles see all courses
        $sql = "SELECT c.id, c.name, c.code, c.instructor_email, c.created_at, c.updated_at,
                       COUNT(e.id) as enrollment_count
                FROM courses c
                LEFT JOIN enrollments e ON e.course_id = c.id
                GROUP BY c.id
                ORDER BY c.id DESC";
        $result = $conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        sendResponse(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        requireRole(['admin', 'instructor'], $body);
        if (!isset($body['name'], $body['code'], $body['instructor_email'])) {
            sendResponse(['success' => false, 'message' => 'name, code, instructor_email are required'], 400);
        }
        $name = trim($body['name']);
        $code = trim($body['code']);
        $instructorEmail = trim($body['instructor_email']);

        $stmt = $conn->prepare("INSERT INTO courses (name, code, instructor_email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $code, $instructorEmail);
        $ok = $stmt->execute();
        if (!$ok) {
            sendResponse(['success' => false, 'message' => 'Failed to create course'], 500);
        }
        $id = $stmt->insert_id;
        $stmt->close();
        sendResponse(['success' => true, 'data' => ['id' => $id, 'name' => $name, 'code' => $code, 'instructor_email' => $instructorEmail]], 201);
        break;

    case 'PUT':
        requireRole(['admin', 'instructor'], $body);
        if (!isset($body['id'], $body['name'], $body['code'], $body['instructor_email'])) {
            sendResponse(['success' => false, 'message' => 'id, name, code, instructor_email are required'], 400);
        }
        $id = (int)$body['id'];
        $name = trim($body['name']);
        $code = trim($body['code']);
        $instructorEmail = trim($body['instructor_email']);

        $stmt = $conn->prepare("UPDATE courses SET name = ?, code = ?, instructor_email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $code, $instructorEmail, $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            sendResponse(['success' => false, 'message' => 'Course not updated or not found'], 404);
        }
        $stmt->close();
        sendResponse(['success' => true, 'message' => 'Course updated']);
        break;

    case 'DELETE':
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
        $id = isset($query['id']) ? (int)$query['id'] : null;
        if (!$id) {
            sendResponse(['success' => false, 'message' => 'id is required'], 400);
        }
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            sendResponse(['success' => false, 'message' => 'Course not deleted or not found'], 404);
        }
        $stmt->close();
        sendResponse(['success' => true, 'message' => 'Course deleted']);
        break;

    default:
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

