<?php
/**
 * Attendance Service
 * Handles attendance sessions, logs, scanning, and analytics
 */

class AttendanceService {
    
    public function getSessions($query) {
        $conn = db();
        $courseId = isset($query['course_id']) ? (int)$query['course_id'] : 0;
        
        if ($courseId) {
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
        } else {
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
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        $conn->close();
        
        return ['success' => true, 'data' => $rows];
    }
    
    public function getLatestSession($courseId) {
        $conn = db();
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
        $conn->close();
        
        return ['success' => true, 'data' => $session ?: null];
    }
    
    public function createSession($body) {
        $courseId = isset($body['course_id']) ? (int)$body['course_id'] : 0;
        if (!$courseId) {
            return ['success' => false, 'message' => 'course_id is required'];
        }
        
        $conn = db();
        
        // Validate course exists
        $stmt = $conn->prepare("SELECT id, name FROM courses WHERE id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $courseResult = $stmt->get_result();
        if ($courseResult->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Course not found'];
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
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Failed to create attendance session'];
        }
        $stmt->close();
        $conn->close();

        return ['success' => true, 'data' => [
            'course_id' => $courseId,
            'course_name' => $courseRow['name'],
            'token' => $token,
            'expires_at' => $expiresAt,
        ]];
    }
    
    public function scanAttendance($body) {
        $token = isset($body['token']) ? trim((string)$body['token']) : '';
        $studentEmail = isset($body['student_email']) ? trim((string)$body['student_email']) : '';
        if ($token === '' || $studentEmail === '') {
            return ['success' => false, 'message' => 'token and student_email are required'];
        }
        
        $conn = db();
        
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
            $conn->close();
            return ['success' => false, 'message' => 'Invalid or unknown attendance token'];
        }
        if (strtotime($session['expires_at']) < time()) {
            $conn->close();
            return ['success' => false, 'message' => 'Attendance session has expired'];
        }

        // Get student id
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
        $stmt->bind_param("s", $studentEmail);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        $student = $studentResult->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $conn->close();
            return ['success' => false, 'message' => 'Student not found'];
        }

        // Ensure enrollment
        $stmt = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
        $stmt->bind_param("ii", $session['course_id'], $student['id']);
        $stmt->execute();
        $enrolled = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$enrolled) {
            $conn->close();
            return ['success' => false, 'message' => 'You are not enrolled in this course'];
        }

        // Record attendance
        $stmt = $conn->prepare("INSERT IGNORE INTO attendance_logs (session_id, student_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $session['id'], $student['id']);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Attendance recorded', 'data' => [
            'course_id' => $session['course_id'],
            'course_name' => $session['course_name'] ?? '',
        ]];
    }
    
    public function getLogs($query) {
        $conn = db();
        $token = isset($query['token']) ? trim((string)$query['token']) : '';
        $sessionId = isset($query['session_id']) ? (int)$query['session_id'] : 0;

        if ($token === '' && !$sessionId) {
            return ['success' => false, 'message' => 'token or session_id is required'];
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
            $conn->close();
            return ['success' => false, 'message' => 'Attendance session not found'];
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
        
        $conn->close();

        return ['success' => true, 'data' => [
            'session' => $session,
            'logs' => $logs,
            'roster' => $includeStudents ? $roster : null,
        ]];
    }
    
    public function getAnalytics($courseId) {
        if (!$courseId) {
            return ['success' => false, 'message' => 'course_id is required'];
        }
        
        $conn = db();
        
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
        $conn->close();

        return ['success' => true, 'data' => [
            'summary' => $summary,
            'sessions' => $sessions,
            'students' => $students,
        ]];
    }
}
?>

