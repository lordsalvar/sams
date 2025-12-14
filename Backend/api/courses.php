<?php
// Courses API - CRUD operations only
// Handles: GET /courses (list all or by ID)
//          POST /courses (create)
//          PUT /courses (update)
//          DELETE /courses (delete)
//          GET /courses/instructors (list instructors)
//          GET /courses/students (list students)

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

// Detect instructor/student list requests
parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $queryParams);
$isInstructorList = strpos($_SERVER['REQUEST_URI'], '/courses/instructors') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses/instructors.php') !== false
    || isset($queryParams['instructors'])
    || (isset($queryParams['list']) && strtolower((string)$queryParams['list']) === 'instructors');

$isStudentsList = strpos($_SERVER['REQUEST_URI'], '/courses/students') !== false
    || strpos($_SERVER['REQUEST_URI'], '/courses/students.php') !== false
    || isset($queryParams['students'])
    || (isset($queryParams['list']) && strtolower((string)$queryParams['list']) === 'students');

// Handle instructor list
if ($isInstructorList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
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

// Handle students list
if ($isStudentsList) {
    if ($method !== 'GET') {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
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

// Handle courses CRUD operations
switch ($method) {
    case 'GET':
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
        
        // Check if requesting a specific course by ID
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
