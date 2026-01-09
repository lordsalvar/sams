<?php
/**
 * Course Management Service
 * Handles course CRUD operations
 */

class CourseService {
    
    public function getCourses($query, $body) {
        $conn = db();
        $requestedByRole = isset($query['requested_by_role']) ? strtolower(trim((string)$query['requested_by_role'])) : '';
        $instructorEmail = isset($query['instructor_email']) ? trim((string)$query['instructor_email']) : '';
        $studentEmail = isset($query['student_email']) ? trim((string)$query['student_email']) : '';
        
        // If student, only show courses they are enrolled in
        if ($requestedByRole === 'student' && !empty($studentEmail)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
            $stmt->bind_param("s", $studentEmail);
            $stmt->execute();
            $studentResult = $stmt->get_result();
            if ($studentResult->num_rows === 0) {
                $stmt->close();
                $conn->close();
                return ['success' => false, 'message' => 'Student not found'];
            }
            $studentRow = $studentResult->fetch_assoc();
            $studentId = (int)$studentRow['id'];
            $stmt->close();
            
            $sql = "SELECT c.id, c.name, c.code, c.instructor_email, c.created_at, c.updated_at,
                           COUNT(e2.id) as enrollment_count
                    FROM enrollments e
                    JOIN courses c ON c.id = e.course_id
                    LEFT JOIN enrollments e2 ON e2.course_id = c.id
                    WHERE e.student_id = ?
                    GROUP BY c.id
                    ORDER BY c.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $studentId);
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
            $conn->close();
            return ['success' => true, 'data' => $rows];
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
        $conn->close();
        return ['success' => true, 'data' => $rows];
    }
    
    public function getCourseById($courseId, $requestedByRole, $studentEmail) {
        $conn = db();
        
        // Get course details
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
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Course not found'];
        }
        
        $course = $courseResult->fetch_assoc();
        $stmt->close();
        
        // If student, verify enrollment
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
                $conn->close();
                return ['success' => false, 'message' => 'You are not enrolled in this course'];
            }
            $stmt->close();
        }
        
        // Get enrolled students
        $students = [];
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
        
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
        $conn->close();
        
        return ['success' => true, 'data' => ['course' => $course, 'students' => $students]];
    }
    
    public function createCourse($body) {
        if (!isset($body['name'], $body['code'], $body['instructor_email'])) {
            return ['success' => false, 'message' => 'name, code, instructor_email are required'];
        }
        
        $conn = db();
        $name = trim($body['name']);
        $code = trim($body['code']);
        $instructorEmail = trim($body['instructor_email']);

        $stmt = $conn->prepare("INSERT INTO courses (name, code, instructor_email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $code, $instructorEmail);
        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Failed to create course'];
        }
        $id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        
        return ['success' => true, 'data' => ['id' => $id, 'name' => $name, 'code' => $code, 'instructor_email' => $instructorEmail]];
    }
    
    public function updateCourse($body) {
        if (!isset($body['id'], $body['name'], $body['code'], $body['instructor_email'])) {
            return ['success' => false, 'message' => 'id, name, code, instructor_email are required'];
        }
        
        $conn = db();
        $id = (int)$body['id'];
        $name = trim($body['name']);
        $code = trim($body['code']);
        $instructorEmail = trim($body['instructor_email']);

        $stmt = $conn->prepare("UPDATE courses SET name = ?, code = ?, instructor_email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $code, $instructorEmail, $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Course not updated or not found'];
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Course updated'];
    }
    
    public function deleteCourse($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'id is required'];
        }
        
        $conn = db();
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Course not deleted or not found'];
        }
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Course deleted'];
    }
}
?>

