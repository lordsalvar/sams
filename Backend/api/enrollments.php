<?php
// Enrollments API
// Handles: POST /courses/enroll, DELETE /courses/unenroll

$configPath = dirname(__DIR__) . '/api/config.php';
require_once $configPath;
require_once dirname(__DIR__) . '/api/helpers/functions.php';

setCorsHeaders();
handleOptionsRequest();

$method = $_SERVER['REQUEST_METHOD'];
$body = getRequestBody();
if (!is_array($body)) {
    $body = [];
}
$conn = db();

// Detect routes
$isEnroll = strpos($_SERVER['REQUEST_URI'], '/courses/enroll') !== false 
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/enroll') !== false;

$isUnenroll = strpos($_SERVER['REQUEST_URI'], '/courses/unenroll') !== false 
    || strpos($_SERVER['REQUEST_URI'], '/courses.php/unenroll') !== false;

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

