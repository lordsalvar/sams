<?php
/**
 * Data Warehouse Service
 * Handles data warehouse operations for analytics and data mining
 */

require_once __DIR__ . '/../config.php';

class DataWarehouseService {
    
    /**
     * Refresh the entire data warehouse
     * Syncs all dimension and fact tables with source data
     */
    public static function refreshDataWarehouse() {
        $conn = db();
        
        try {
            // Call the master refresh procedure
            $result = $conn->query("CALL RefreshDataWarehouse()");
            
            // Consume all result sets (stored procedures may return multiple)
            while ($conn->more_results()) {
                $conn->next_result();
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            }
            
            $conn->close();
            
            return [
                'success' => true,
                'message' => 'Data warehouse refreshed successfully'
            ];
        } catch (Exception $e) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Failed to refresh data warehouse: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get student attendance features for data mining
     * Returns features: total_attendance_count, number_of_absences, class, day_of_week
     */
    public static function getStudentAttendanceFeatures($studentId = null, $courseId = null) {
        $conn = db();
        $data = [];
        
        try {
            $query = "
                SELECT 
                    student_key,
                    student_id,
                    student_name,
                    student_email,
                    course_key,
                    course_code,
                    course_name,
                    day_of_week,
                    day_name,
                    month_name,
                    year,
                    total_attendance_count,
                    number_of_absences,
                    number_of_late,
                    total_sessions,
                    attendance_rate
                FROM vw_student_attendance_features
                WHERE 1=1
            ";
            
            $params = [];
            $types = '';
            
            if ($studentId !== null) {
                $query .= " AND student_id = ?";
                $params[] = $studentId;
                $types .= 'i';
            }
            
            if ($courseId !== null) {
                $query .= " AND course_key = ?";
                $params[] = $courseId;
                $types .= 'i';
            }
            
            $query .= " ORDER BY student_key, course_key, year, month_name, day_of_week";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Failed to get attendance features: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get attendance statistics by day of week
     */
    public static function getAttendanceByDay($courseCode = null) {
        $conn = db();
        $data = [];
        
        try {
            $query = "
                SELECT 
                    day_of_week,
                    day_name,
                    course_key,
                    course_code,
                    course_name,
                    present_count,
                    absent_count,
                    late_count,
                    total_count,
                    attendance_rate
                FROM vw_attendance_by_day
                WHERE 1=1
            ";
            
            $params = [];
            $types = '';
            
            if ($courseCode !== null) {
                $query .= " AND course_code = ?";
                $params[] = $courseCode;
                $types .= 's';
            }
            
            $query .= " ORDER BY day_of_week, course_code";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Failed to get attendance by day: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get data mining dataset
     * Returns all features needed for predictive analytics
     */
    public static function getDataMiningDataset($studentId = null, $courseId = null) {
        $conn = db();
        $data = [];
        
        try {
            $query = "
                SELECT 
                    fa.student_key,
                    ds.student_id,
                    ds.student_name,
                    dc.course_code as class,
                    dc.course_name as subject,
                    dd.day_of_week,
                    dd.day_name,
                    fa.attendance_status as target_variable,
                    fa.minutes_late,
                    -- Aggregated features
                    (SELECT COUNT(*) 
                     FROM fact_attendance fa2 
                     WHERE fa2.student_key = fa.student_key 
                     AND fa2.course_key = fa.course_key 
                     AND fa2.attendance_status = 'Present') as total_attendance_count,
                    (SELECT COUNT(*) 
                     FROM fact_attendance fa2 
                     WHERE fa2.student_key = fa.student_key 
                     AND fa2.course_key = fa.course_key 
                     AND fa2.attendance_status = 'Absent') as number_of_absences,
                    (SELECT COUNT(*) 
                     FROM fact_attendance fa2 
                     WHERE fa2.student_key = fa.student_key 
                     AND fa2.course_key = fa.course_key 
                     AND fa2.attendance_status = 'Late') as number_of_late
                FROM fact_attendance fa
                INNER JOIN Dim_student ds ON fa.student_key = ds.student_key
                INNER JOIN Dim_Course dc ON fa.course_key = dc.course_key
                INNER JOIN Dim_date dd ON fa.date_key = dd.date_key
                WHERE 1=1
            ";
            
            $params = [];
            $types = '';
            
            if ($studentId !== null) {
                $query .= " AND ds.student_id = ?";
                $params[] = $studentId;
                $types .= 'i';
            }
            
            if ($courseId !== null) {
                $query .= " AND dc.course_key = ?";
                $params[] = $courseId;
                $types .= 'i';
            }
            
            $query .= " ORDER BY fa.student_key, fa.course_key, fa.date_key";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            return [
                'success' => true,
                'data' => $data,
                'features' => [
                    'target_variable' => 'attendance_status',
                    'feature_list' => [
                        'total_attendance_count',
                        'number_of_absences',
                        'number_of_late',
                        'class',
                        'subject',
                        'day_of_week',
                        'day_name',
                        'minutes_late'
                    ]
                ]
            ];
        } catch (Exception $e) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Failed to get data mining dataset: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get attendance statistics summary
     */
    public static function getAttendanceSummary($courseId = null, $startDate = null, $endDate = null) {
        $conn = db();
        $data = [];
        
        try {
            $query = "
                SELECT 
                    dc.course_code,
                    dc.course_name,
                    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as absent_count,
                    COUNT(CASE WHEN fa.attendance_status = 'Late' THEN 1 END) as late_count,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT fa.student_key) as unique_students,
                    ROUND(COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) * 100.0 / COUNT(*), 2) as attendance_rate
                FROM fact_attendance fa
                INNER JOIN Dim_Course dc ON fa.course_key = dc.course_key
                INNER JOIN Dim_date dd ON fa.date_key = dd.date_key
                WHERE 1=1
            ";
            
            $params = [];
            $types = '';
            
            if ($courseId !== null) {
                $query .= " AND dc.course_key = ?";
                $params[] = $courseId;
                $types .= 'i';
            }
            
            if ($startDate !== null) {
                $query .= " AND dd.date_value >= ?";
                $params[] = $startDate;
                $types .= 's';
            }
            
            if ($endDate !== null) {
                $query .= " AND dd.date_value <= ?";
                $params[] = $endDate;
                $types .= 's';
            }
            
            $query .= " GROUP BY dc.course_key, dc.course_code, dc.course_name";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            $conn->close();
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (Exception $e) {
            $conn->close();
            return [
                'success' => false,
                'message' => 'Failed to get attendance summary: ' . $e->getMessage()
            ];
        }
    }
}

?>

