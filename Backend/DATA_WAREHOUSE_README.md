# Data Warehouse Documentation

## Overview

This data warehouse implements a star schema design for analytics and data mining on the Student Attendance Management System (SAMS). It provides dimension and fact tables optimized for reporting and predictive analytics.

## Table Structure

### Dimension Tables

#### 1. Dim_student
- **Type:** Dimension
- **Purpose:** Stores student profile information
- **Source:** `users` table where `role = 'student'`
- **Key Fields:**
  - `student_key` (Primary Key)
  - `student_id` (Foreign Key to `users.id`)
  - `student_name`, `student_email`

#### 2. Dim_Instructor
- **Type:** Dimension
- **Purpose:** Contains instructor details
- **Source:** `users` table where `role = 'instructor'`
- **Key Fields:**
  - `instructor_key` (Primary Key)
  - `instructor_id` (Foreign Key to `users.id`)
  - `instructor_name`, `instructor_email`

#### 3. Dim_Course
- **Type:** Dimension
- **Purpose:** Describes each course
- **Source:** `courses` table
- **Key Fields:**
  - `course_key` (Primary Key)
  - `course_id` (Foreign Key to `courses.id`)
  - `course_name`, `course_code`, `instructor_email`

#### 4. Dim_Session
- **Type:** Dimension
- **Purpose:** Describes attendance sessions
- **Source:** `attendance_sessions` table
- **Key Fields:**
  - `session_key` (Primary Key)
  - `session_id` (Foreign Key to `attendance_sessions.id`)
  - `token`, `expires_at`, `created_at`

#### 5. Dim_date
- **Type:** Dimension
- **Purpose:** Calendar table for time-based reporting
- **Source:** Generated (2020-2030)
- **Key Fields:**
  - `date_key` (Primary Key, format: YYYYMMDD)
  - `date_value`, `day_of_week`, `day_name`
  - `month_number`, `month_name`, `quarter`, `semester`
  - `year`, `is_weekend`, `is_holiday`

### Fact Tables

#### 1. fact_enrollment
- **Type:** Fact
- **Purpose:** Records student-course enrollment relationships
- **Source:** `enrollments` table
- **Key Fields:**
  - `enrollment_key` (Primary Key)
  - `student_key`, `course_key`, `enrollment_date_key`
  - `enrollment_timestamp`

#### 2. fact_attendance
- **Type:** Fact
- **Purpose:** Stores attendance records per session per student
- **Source:** `attendance_logs` table + absent records
- **Key Fields:**
  - `attendance_key` (Primary Key)
  - `session_key`, `student_key`, `course_key`, `date_key`
  - `scanned_at`, `attendance_status` (Present/Absent/Late)
  - `minutes_late`, `day_of_week`

## Data Mining Features

### Target Variable
- **attendance_status**: `Present`, `Absent`, or `Late`

### Key Features Available
1. **total_attendance_count**: Number of times student was present
2. **number_of_absences**: Number of times student was absent
3. **number_of_late**: Number of times student was late
4. **class/subject**: Course code and course name
5. **day_of_week**: Day of week (1=Monday, 7=Sunday)
6. **attendance_rate**: Percentage attendance rate

## Usage

### Initial Setup

1. Run the main database setup first:
   ```sql
   source Backend/database_setup.sql;
   ```

2. Run the data warehouse setup:
   ```sql
   source Backend/data_warehouse_setup.sql;
   ```

### Refreshing Data

To sync the data warehouse with source tables, run:

```sql
CALL RefreshDataWarehouse();
```

This procedure:
- Populates all dimension tables
- Populates all fact tables
- Creates absent records for enrolled students who didn't scan

### Recommended Refresh Schedule

- **Real-time:** Call `RefreshDataWarehouse()` after each attendance session
- **Batch:** Run daily via cron job or scheduled task
- **On-demand:** Run before generating analytics reports

## Stored Procedures

### Dimension Population
- `PopulateDimDate()` - Populates calendar dimension (2020-2030)
- `PopulateDimStudent()` - Syncs student dimension from users table
- `PopulateDimInstructor()` - Syncs instructor dimension from users table
- `PopulateDimCourse()` - Syncs course dimension from courses table
- `PopulateDimSession()` - Syncs session dimension from attendance_sessions table

### Fact Population
- `PopulateFactEnrollment()` - Syncs enrollment facts
- `PopulateFactAttendance()` - Syncs attendance facts (Present/Late)
- `PopulateAbsentRecords()` - Creates Absent records for non-scanned students

### Master Procedure
- `RefreshDataWarehouse()` - Runs all population procedures in correct order

## Views for Analytics

### vw_student_attendance_features
Provides aggregated student attendance features for data mining:
```sql
SELECT * FROM vw_student_attendance_features
WHERE student_key = 1;
```

**Columns:**
- Student information (key, id, name, email)
- Course information (key, code, name)
- Day of week, month, year
- Total attendance count
- Number of absences
- Number of late
- Total sessions
- Attendance rate

### vw_attendance_by_day
Shows attendance statistics grouped by day of week:
```sql
SELECT * FROM vw_attendance_by_day
WHERE course_code = 'CS101';
```

## Example Queries

### 1. Student Attendance Rate by Course
```sql
SELECT 
    ds.student_name,
    dc.course_code,
    dc.course_name,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count,
    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as absent_count,
    COUNT(*) as total_sessions,
    ROUND(COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) * 100.0 / COUNT(*), 2) as attendance_rate
FROM Dim_student ds
INNER JOIN fact_attendance fa ON ds.student_key = fa.student_key
INNER JOIN Dim_Course dc ON fa.course_key = dc.course_key
GROUP BY ds.student_key, ds.student_name, dc.course_key, dc.course_code, dc.course_name;
```

### 2. Attendance by Day of Week
```sql
SELECT 
    dd.day_name,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count,
    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as absent_count,
    COUNT(CASE WHEN fa.attendance_status = 'Late' THEN 1 END) as late_count
FROM Dim_date dd
INNER JOIN fact_attendance fa ON dd.date_key = fa.date_key
GROUP BY dd.day_of_week, dd.day_name
ORDER BY dd.day_of_week;
```

### 3. Data Mining Dataset
```sql
SELECT 
    student_key,
    course_code as class,
    day_of_week,
    total_attendance_count,
    number_of_absences,
    number_of_late,
    attendance_rate,
    attendance_status as target_variable
FROM vw_student_attendance_features
ORDER BY student_key, course_key;
```

### 4. Monthly Attendance Trends
```sql
SELECT 
    dd.year,
    dd.month_name,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count,
    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as absent_count,
    COUNT(*) as total_records
FROM Dim_date dd
INNER JOIN fact_attendance fa ON dd.date_key = fa.date_key
GROUP BY dd.year, dd.month_number, dd.month_name
ORDER BY dd.year, dd.month_number;
```

## Attendance Status Logic

The `attendance_status` field is determined as follows:

1. **Present**: Student scanned QR code within 15 minutes of session creation
2. **Late**: Student scanned QR code after 15 minutes of session creation
3. **Absent**: Student is enrolled in the course but did not scan the QR code for that session

The 15-minute threshold can be adjusted in the `PopulateFactAttendance()` procedure.

## Integration with Application

### PHP Integration Example

```php
<?php
require_once 'microservices/config.php';

function refreshDataWarehouse() {
    $conn = db();
    $result = $conn->query("CALL RefreshDataWarehouse()");
    $conn->close();
    return $result;
}

// Call after creating attendance session
function syncAfterSessionCreation($sessionId) {
    refreshDataWarehouse();
}

// Get student attendance features for data mining
function getStudentAttendanceFeatures($studentId) {
    $conn = db();
    $stmt = $conn->prepare("
        SELECT * FROM vw_student_attendance_features 
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $data;
}
?>
```

## Performance Considerations

1. **Indexes:** All foreign keys and commonly queried fields are indexed
2. **Refresh Frequency:** Balance between data freshness and performance
3. **Partitioning:** Consider partitioning `fact_attendance` by date for large datasets
4. **Materialized Views:** The views can be converted to materialized tables for better performance

## Maintenance

### Regular Tasks
1. **Daily:** Run `RefreshDataWarehouse()` to sync data
2. **Weekly:** Check for orphaned records
3. **Monthly:** Review and optimize indexes
4. **Quarterly:** Extend `Dim_date` if needed beyond 2030

### Extending Date Dimension
To extend beyond 2030, modify the date range in `PopulateDimDate()` procedure.

## Notes

- The data warehouse uses a star schema design optimized for analytics
- All dimension tables have surrogate keys (`*_key`) for better performance
- Fact tables reference dimension tables via foreign keys
- The `Dim_date` table supports flexible time-based reporting
- Absent records are automatically generated for enrolled students who didn't scan

