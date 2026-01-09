<?php
/**
 * Enrollment Service
 * Handles student enrollment and unenrollment operations
 */

class EnrollmentService {
    
    public function enrollStudent($body) {
        if (!isset($body['course_id'], $body['student_email'])) {
            return ['success' => false, 'message' => 'course_id and student_email are required'];
        }

        $conn = db();
        $courseId = (int)$body['course_id'];
        $studentEmail = trim($body['student_email']);

        // Validate course
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $courseResult = $stmt->get_result();
        if ($courseResult->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Course not found'];
        }
        $stmt->close();

        // Validate student
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
        $stmt->bind_param("s", $studentEmail);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        if ($studentResult->num_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Student not found or not a student'];
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
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Student already enrolled'];
        }
        $stmt->close();

        // Enroll
        $stmt = $conn->prepare("INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $courseId, $studentId);
        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Failed to enroll student'];
        }
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Student enrolled'];
    }
    
    public function unenrollStudent($enrollmentId) {
        if (!$enrollmentId) {
            return ['success' => false, 'message' => 'enrollment_id is required'];
        }

        $conn = db();
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->bind_param("i", $enrollmentId);
        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Enrollment not found or already removed'];
        }
        $stmt->close();
        $conn->close();

        return ['success' => true, 'message' => 'Student unenrolled successfully'];
    }
}
?>

