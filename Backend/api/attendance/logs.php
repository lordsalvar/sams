<?php
// Attendance Logs API
// Handles: GET /courses/attendance-logs (get logs for a session)

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

