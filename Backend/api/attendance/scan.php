<?php
// Attendance Scan API (for students)
// Handles: POST /courses/attendance-scan

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

