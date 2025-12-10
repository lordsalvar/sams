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
    return $conn;
}

// Basic role guard (expects role from client for now)
function requireRole(array $allowed, $body)
{
    $role = isset($body['requested_by_role']) ? strtolower(trim($body['requested_by_role'])) : '';
    if (!in_array($role, array_map('strtolower', $allowed), true)) {
        sendResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
}

// Detect enroll action (mapped from /courses/enroll route)
$isEnroll = strpos($_SERVER['REQUEST_URI'], '/courses/enroll') !== false || strpos($_SERVER['REQUEST_URI'], '/courses/enroll.php') !== false;

$method = $_SERVER['REQUEST_METHOD'];
$body = getRequestBody();
$conn = db();

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

switch ($method) {
    case 'GET':
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

