-- ============================================
-- SAMS Data Warehouse Setup Script
-- Dimension and Fact Tables for Analytics & Data Mining
-- ============================================
-- Run this script after the main database_setup.sql
-- This creates the star schema structure for data analytics

USE sams_db;

-- ============================================
-- DIMENSION TABLES
-- ============================================

-- Dim_student: Student dimension table
-- Stores student profile information for analytics
CREATE TABLE IF NOT EXISTS Dim_student (
    student_key INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE COMMENT 'Reference to users.id',
    student_name VARCHAR(100) NOT NULL,
    student_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_student_email (student_email),
    CONSTRAINT fk_dim_student_user FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dim_Instructor: Instructor dimension table
-- Contains instructor details for analytics
CREATE TABLE IF NOT EXISTS Dim_Instructor (
    instructor_key INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL UNIQUE COMMENT 'Reference to users.id',
    instructor_name VARCHAR(100) NOT NULL,
    instructor_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_instructor_id (instructor_id),
    INDEX idx_instructor_email (instructor_email),
    CONSTRAINT fk_dim_instructor_user FOREIGN KEY (instructor_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dim_Course: Course dimension table
-- Describes each course for analytics
CREATE TABLE IF NOT EXISTS Dim_Course (
    course_key INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL UNIQUE COMMENT 'Reference to courses.id',
    course_name VARCHAR(150) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    instructor_email VARCHAR(100) NOT NULL,
    instructor_key INT COMMENT 'Reference to Dim_Instructor.instructor_key',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_course_id (course_id),
    INDEX idx_course_code (course_code),
    INDEX idx_instructor_key (instructor_key),
    CONSTRAINT fk_dim_course_course FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_dim_course_instructor FOREIGN KEY (instructor_key)
        REFERENCES Dim_Instructor(instructor_key)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dim_Session: Attendance session dimension table
-- Describes attendance sessions for analytics
CREATE TABLE IF NOT EXISTS Dim_Session (
    session_key INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL UNIQUE COMMENT 'Reference to attendance_sessions.id',
    token CHAR(36) NOT NULL,
    course_id INT NOT NULL COMMENT 'Reference to courses.id',
    course_key INT COMMENT 'Reference to Dim_Course.course_key',
    expires_at DATETIME NOT NULL,
    created_by_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_token (token),
    INDEX idx_course_key (course_key),
    INDEX idx_expires_at (expires_at),
    CONSTRAINT fk_dim_session_session FOREIGN KEY (session_id)
        REFERENCES attendance_sessions(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_dim_session_course FOREIGN KEY (course_key)
        REFERENCES Dim_Course(course_key)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dim_date: Calendar dimension table for time-based reporting
-- Supports reporting by day/week/month/semester/year
CREATE TABLE IF NOT EXISTS Dim_date (
    date_key INT PRIMARY KEY COMMENT 'YYYYMMDD format (e.g., 20240115)',
    date_value DATE NOT NULL UNIQUE,
    day_of_week INT NOT NULL COMMENT '1=Monday, 7=Sunday',
    day_name VARCHAR(10) NOT NULL COMMENT 'Monday, Tuesday, etc.',
    day_of_month INT NOT NULL,
    day_of_year INT NOT NULL,
    week_of_year INT NOT NULL,
    month_number INT NOT NULL COMMENT '1-12',
    month_name VARCHAR(10) NOT NULL COMMENT 'January, February, etc.',
    quarter INT NOT NULL COMMENT '1-4',
    semester INT COMMENT '1 or 2 (can be NULL if not applicable)',
    year INT NOT NULL,
    is_weekend BOOLEAN NOT NULL DEFAULT FALSE,
    is_holiday BOOLEAN NOT NULL DEFAULT FALSE,
    holiday_name VARCHAR(100) COMMENT 'Name of holiday if applicable',
    INDEX idx_date_value (date_value),
    INDEX idx_year_month (year, month_number),
    INDEX idx_year_quarter (year, quarter),
    INDEX idx_year_semester (year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FACT TABLES
-- ============================================

-- fact_enrollment: Enrollment fact table
-- Records which student is enrolled in which course
CREATE TABLE IF NOT EXISTS fact_enrollment (
    enrollment_key INT AUTO_INCREMENT PRIMARY KEY,
    student_key INT NOT NULL COMMENT 'Reference to Dim_student.student_key',
    course_key INT NOT NULL COMMENT 'Reference to Dim_Course.course_key',
    enrollment_date_key INT COMMENT 'Reference to Dim_date.date_key (date of enrollment)',
    enrollment_timestamp TIMESTAMP NOT NULL,
    INDEX idx_student_key (student_key),
    INDEX idx_course_key (course_key),
    INDEX idx_enrollment_date (enrollment_date_key),
    INDEX idx_student_course (student_key, course_key),
    CONSTRAINT fk_fact_enroll_student FOREIGN KEY (student_key)
        REFERENCES Dim_student(student_key)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fact_enroll_course FOREIGN KEY (course_key)
        REFERENCES Dim_Course(course_key)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fact_enroll_date FOREIGN KEY (enrollment_date_key)
        REFERENCES Dim_date(date_key)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    UNIQUE KEY unique_enrollment (student_key, course_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fact_attendance: Attendance fact table
-- Stores each scan/log of attendance per session per student
-- Includes attendance status for data mining (Present/Absent/Late)
CREATE TABLE IF NOT EXISTS fact_attendance (
    attendance_key INT AUTO_INCREMENT PRIMARY KEY,
    session_key INT NOT NULL COMMENT 'Reference to Dim_Session.session_key',
    student_key INT NOT NULL COMMENT 'Reference to Dim_student.student_key',
    course_key INT NOT NULL COMMENT 'Reference to Dim_Course.course_key',
    date_key INT NOT NULL COMMENT 'Reference to Dim_date.date_key (date of attendance)',
    scanned_at TIMESTAMP NOT NULL COMMENT 'When the student scanned the QR code',
    attendance_status ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
    minutes_late INT DEFAULT 0 COMMENT 'Minutes late if status is Late',
    day_of_week INT COMMENT 'Derived from date_key for data mining',
    INDEX idx_session_key (session_key),
    INDEX idx_student_key (student_key),
    INDEX idx_course_key (course_key),
    INDEX idx_date_key (date_key),
    INDEX idx_attendance_status (attendance_status),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_student_session (student_key, session_key),
    INDEX idx_student_course_date (student_key, course_key, date_key),
    CONSTRAINT fk_fact_attendance_session FOREIGN KEY (session_key)
        REFERENCES Dim_Session(session_key)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fact_attendance_student FOREIGN KEY (student_key)
        REFERENCES Dim_student(student_key)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fact_attendance_course FOREIGN KEY (course_key)
        REFERENCES Dim_Course(course_key)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_fact_attendance_date FOREIGN KEY (date_key)
        REFERENCES Dim_date(date_key)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    UNIQUE KEY unique_attendance (session_key, student_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STORED PROCEDURES FOR DATA POPULATION
-- ============================================

-- Procedure to populate Dim_date with calendar data
-- Populates dates from 2020 to 2030 (adjust range as needed)
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateDimDate()
BEGIN
    DECLARE start_date DATE DEFAULT '2020-01-01';
    DECLARE end_date DATE DEFAULT '2030-12-31';
    DECLARE current_date DATE;
    DECLARE date_key_val INT;
    DECLARE day_of_week_val INT;
    DECLARE day_name_val VARCHAR(10);
    DECLARE month_name_val VARCHAR(10);
    DECLARE quarter_val INT;
    DECLARE semester_val INT;
    DECLARE is_weekend_val BOOLEAN;
    
    SET current_date = start_date;
    
    WHILE current_date <= end_date DO
        SET date_key_val = YEAR(current_date) * 10000 + MONTH(current_date) * 100 + DAY(current_date);
        SET day_of_week_val = DAYOFWEEK(current_date);
        -- Convert MySQL DAYOFWEEK (1=Sunday) to our format (1=Monday)
        SET day_of_week_val = CASE 
            WHEN day_of_week_val = 1 THEN 7
            ELSE day_of_week_val - 1
        END;
        SET day_name_val = DAYNAME(current_date);
        SET month_name_val = MONTHNAME(current_date);
        SET quarter_val = QUARTER(current_date);
        SET semester_val = CASE 
            WHEN MONTH(current_date) BETWEEN 1 AND 6 THEN 1
            WHEN MONTH(current_date) BETWEEN 7 AND 12 THEN 2
        END;
        SET is_weekend_val = (day_of_week_val IN (6, 7));
        
        INSERT INTO Dim_date (
            date_key, date_value, day_of_week, day_name, day_of_month, day_of_year,
            week_of_year, month_number, month_name, quarter, semester, year,
            is_weekend, is_holiday, holiday_name
        ) VALUES (
            date_key_val,
            current_date,
            day_of_week_val,
            day_name_val,
            DAY(current_date),
            DAYOFYEAR(current_date),
            WEEK(current_date, 3), -- ISO week
            MONTH(current_date),
            month_name_val,
            quarter_val,
            semester_val,
            YEAR(current_date),
            is_weekend_val,
            FALSE,
            NULL
        )
        ON DUPLICATE KEY UPDATE date_value = date_value;
        
        SET current_date = DATE_ADD(current_date, INTERVAL 1 DAY);
    END WHILE;
END //

DELIMITER ;

-- Procedure to populate Dim_student from users table
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateDimStudent()
BEGIN
    INSERT INTO Dim_student (student_id, student_name, student_email)
    SELECT id, name, email
    FROM users
    WHERE role = 'student'
    ON DUPLICATE KEY UPDATE
        student_name = VALUES(student_name),
        student_email = VALUES(student_email),
        updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- Procedure to populate Dim_Instructor from users table
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateDimInstructor()
BEGIN
    INSERT INTO Dim_Instructor (instructor_id, instructor_name, instructor_email)
    SELECT id, name, email
    FROM users
    WHERE role = 'instructor'
    ON DUPLICATE KEY UPDATE
        instructor_name = VALUES(instructor_name),
        instructor_email = VALUES(instructor_email),
        updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- Procedure to populate Dim_Course from courses table
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateDimCourse()
BEGIN
    INSERT INTO Dim_Course (course_id, course_name, course_code, instructor_email, instructor_key)
    SELECT 
        c.id,
        c.name,
        c.code,
        c.instructor_email,
        di.instructor_key
    FROM courses c
    LEFT JOIN Dim_Instructor di ON c.instructor_email = di.instructor_email
    ON DUPLICATE KEY UPDATE
        course_name = VALUES(course_name),
        course_code = VALUES(course_code),
        instructor_email = VALUES(instructor_email),
        instructor_key = VALUES(instructor_key),
        updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- Procedure to populate Dim_Session from attendance_sessions table
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateDimSession()
BEGIN
    INSERT INTO Dim_Session (session_id, token, course_id, course_key, expires_at, created_by_email, created_at)
    SELECT 
        as_table.id,
        as_table.token,
        as_table.course_id,
        dc.course_key,
        as_table.expires_at,
        as_table.created_by_email,
        as_table.created_at
    FROM attendance_sessions as_table
    LEFT JOIN Dim_Course dc ON as_table.course_id = dc.course_id
    ON DUPLICATE KEY UPDATE
        token = VALUES(token),
        course_id = VALUES(course_id),
        course_key = VALUES(course_key),
        expires_at = VALUES(expires_at),
        created_by_email = VALUES(created_by_email),
        created_at = VALUES(created_at);
END //

DELIMITER ;

-- Procedure to populate fact_enrollment from enrollments table
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateFactEnrollment()
BEGIN
    INSERT INTO fact_enrollment (student_key, course_key, enrollment_date_key, enrollment_timestamp)
    SELECT 
        ds.student_key,
        dc.course_key,
        DATE_FORMAT(e.created_at, '%Y%m%d'),
        e.created_at
    FROM enrollments e
    INNER JOIN Dim_student ds ON e.student_id = ds.student_id
    INNER JOIN Dim_Course dc ON e.course_id = dc.course_id
    LEFT JOIN Dim_date dd ON DATE_FORMAT(e.created_at, '%Y%m%d') = dd.date_key
    WHERE dd.date_key IS NOT NULL
    ON DUPLICATE KEY UPDATE
        enrollment_timestamp = VALUES(enrollment_timestamp);
END //

DELIMITER ;

-- Procedure to populate fact_attendance from attendance_logs table
-- Determines attendance status (Present/Late) based on session creation time
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateFactAttendance()
BEGIN
    INSERT INTO fact_attendance (
        session_key, student_key, course_key, date_key, 
        scanned_at, attendance_status, minutes_late, day_of_week
    )
    SELECT 
        ds.session_key,
        dst.student_key,
        dc.course_key,
        DATE_FORMAT(al.scanned_at, '%Y%m%d') as date_key_val,
        al.scanned_at,
        CASE 
            WHEN TIMESTAMPDIFF(MINUTE, ass.created_at, al.scanned_at) > 15 THEN 'Late'
            ELSE 'Present'
        END as status,
        GREATEST(0, TIMESTAMPDIFF(MINUTE, ass.created_at, al.scanned_at)) as late_minutes,
        dd.day_of_week
    FROM attendance_logs al
    INNER JOIN Dim_Session ds ON al.session_id = ds.session_id
    INNER JOIN Dim_student dst ON al.student_id = dst.student_id
    INNER JOIN Dim_Course dc ON ds.course_key = dc.course_key
    INNER JOIN attendance_sessions ass ON al.session_id = ass.id
    INNER JOIN Dim_date dd ON DATE_FORMAT(al.scanned_at, '%Y%m%d') = dd.date_key
    ON DUPLICATE KEY UPDATE
        scanned_at = VALUES(scanned_at),
        attendance_status = VALUES(attendance_status),
        minutes_late = VALUES(minutes_late),
        day_of_week = VALUES(day_of_week);
END //

DELIMITER ;

-- Procedure to insert absent records for students enrolled but not scanned
-- This creates "Absent" records for data mining purposes
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS PopulateAbsentRecords()
BEGIN
    -- Insert absent records for enrolled students who didn't scan
    INSERT INTO fact_attendance (
        session_key, student_key, course_key, date_key,
        scanned_at, attendance_status, minutes_late, day_of_week
    )
    SELECT DISTINCT
        ds.session_key,
        fe.student_key,
        ds.course_key,
        DATE_FORMAT(ds.created_at, '%Y%m%d') as date_key_val,
        ds.created_at as scanned_at,
        'Absent' as status,
        0 as late_minutes,
        dd.day_of_week
    FROM Dim_Session ds
    INNER JOIN fact_enrollment fe ON ds.course_key = fe.course_key
    INNER JOIN Dim_date dd ON DATE_FORMAT(ds.created_at, '%Y%m%d') = dd.date_key
    WHERE NOT EXISTS (
        SELECT 1 
        FROM fact_attendance fa 
        WHERE fa.session_key = ds.session_key 
        AND fa.student_key = fe.student_key
    )
    AND ds.expires_at >= NOW()
    ON DUPLICATE KEY UPDATE attendance_status = 'Absent';
END //

DELIMITER ;

-- Master procedure to refresh all dimension and fact tables
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS RefreshDataWarehouse()
BEGIN
    -- Populate dimensions first
    CALL PopulateDimDate();
    CALL PopulateDimStudent();
    CALL PopulateDimInstructor();
    CALL PopulateDimCourse();
    CALL PopulateDimSession();
    
    -- Then populate facts
    CALL PopulateFactEnrollment();
    CALL PopulateFactAttendance();
    CALL PopulateAbsentRecords();
    
    SELECT 'Data warehouse refreshed successfully' as message;
END //

DELIMITER ;

-- ============================================
-- VIEWS FOR DATA MINING FEATURES
-- ============================================

-- View: Student attendance features for data mining
-- Provides: total_attendance_count, number_of_absences, class/subject, day_of_week
CREATE OR REPLACE VIEW vw_student_attendance_features AS
SELECT 
    dst.student_key,
    dst.student_id,
    dst.student_name,
    dst.student_email,
    dc.course_key,
    dc.course_code,
    dc.course_name,
    dd.day_of_week,
    dd.day_name,
    dd.month_name,
    dd.year,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as total_attendance_count,
    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as number_of_absences,
    COUNT(CASE WHEN fa.attendance_status = 'Late' THEN 1 END) as number_of_late,
    COUNT(*) as total_sessions,
    ROUND(COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) * 100.0 / COUNT(*), 2) as attendance_rate
FROM Dim_student dst
INNER JOIN fact_enrollment fe ON dst.student_key = fe.student_key
INNER JOIN Dim_Course dc ON fe.course_key = dc.course_key
LEFT JOIN fact_attendance fa ON dst.student_key = fa.student_key AND dc.course_key = fa.course_key
LEFT JOIN Dim_date dd ON fa.date_key = dd.date_key
GROUP BY 
    dst.student_key, dst.student_id, dst.student_name, dst.student_email,
    dc.course_key, dc.course_code, dc.course_name,
    dd.day_of_week, dd.day_name, dd.month_name, dd.year;

-- View: Aggregated attendance by day of week for data mining
CREATE OR REPLACE VIEW vw_attendance_by_day AS
SELECT 
    dd.day_of_week,
    dd.day_name,
    dc.course_key,
    dc.course_code,
    dc.course_name,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count,
    COUNT(CASE WHEN fa.attendance_status = 'Absent' THEN 1 END) as absent_count,
    COUNT(CASE WHEN fa.attendance_status = 'Late' THEN 1 END) as late_count,
    COUNT(*) as total_count,
    ROUND(COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) * 100.0 / COUNT(*), 2) as attendance_rate
FROM Dim_date dd
INNER JOIN fact_attendance fa ON dd.date_key = fa.date_key
INNER JOIN Dim_Course dc ON fa.course_key = dc.course_key
GROUP BY dd.day_of_week, dd.day_name, dc.course_key, dc.course_code, dc.course_name;

-- ============================================
-- INITIAL DATA POPULATION
-- ============================================

-- Run initial population
CALL RefreshDataWarehouse();

-- ============================================
-- NOTES
-- ============================================
-- 1. Run RefreshDataWarehouse() periodically to sync with source tables
-- 2. The attendance_status is determined as:
--    - 'Present': Scanned within 15 minutes of session creation
--    - 'Late': Scanned after 15 minutes
--    - 'Absent': Enrolled but did not scan (populated by PopulateAbsentRecords)
-- 3. For data mining, use vw_student_attendance_features view
-- 4. Target variable for data mining: attendance_status (Present/Absent/Late)
-- 5. Key features available:
--    - total_attendance_count
--    - number_of_absences
--    - number_of_late
--    - class/subject (course_code, course_name)
--    - day_of_week
--    - attendance_rate

