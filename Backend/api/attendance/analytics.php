<?php
// Attendance Analytics API
// Handles: GET /courses/attendance-analytics

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

