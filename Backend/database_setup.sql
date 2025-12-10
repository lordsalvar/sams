-- SAMS (Student Attendance Management System) Database Setup Script
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS sams_db;
USE sams_db;

-- Users table: Stores all system users (admins, instructors, and students)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table: Stores course information with assigned instructor
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    instructor_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_instructor (instructor_email),
    CONSTRAINT fk_course_instructor FOREIGN KEY (instructor_email) 
        REFERENCES users(email) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollments table: Links students to courses (Many-to-Many relationship)
-- This table is the core of the enrollment system:
-- - Each row represents one student enrolled in one course
-- - A student can be enrolled in multiple courses
-- - A course can have multiple students enrolled
-- - Prevents duplicate enrollments with UNIQUE constraint
-- - CASCADE DELETE: Automatically removes enrollments when course or student is deleted
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL COMMENT 'Reference to the course',
    student_id INT NOT NULL COMMENT 'Reference to the student user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the student was enrolled',
    UNIQUE KEY unique_enrollment (course_id, student_id),
    INDEX idx_course (course_id),
    INDEX idx_student (student_id),
    CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) 
        REFERENCES courses(id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Test/Default Users
-- ============================================
-- Default password for all test users: 'password'
-- The hash below is generated using: password_hash('password', PASSWORD_DEFAULT)

-- Admin user (can manage everything: users, courses, enrollments)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'admin')
ON DUPLICATE KEY UPDATE name=name;

-- Instructor user (can manage their courses and view enrolled students)
INSERT INTO users (name, email, password, role) VALUES
('Instructor User', 'instructor@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'instructor')
ON DUPLICATE KEY UPDATE name=name;

-- Student user (can view their enrolled courses)
INSERT INTO users (name, email, password, role) VALUES
('Student User', 'student@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'student')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- Test Login Credentials:
-- ============================================
-- Admin:      admin@local.dev      / password
-- Instructor: instructor@local.dev / password
-- Student:    student@local.dev    / password
--
-- ============================================
-- How Enrollment Works:
-- ============================================
-- 1. Admin/Instructor creates a course and assigns an instructor
-- 2. Admin/Instructor enrolls students into the course by email
-- 3. System creates a record in enrollments table linking student_id to course_id
-- 4. Students can then view their enrolled courses
-- 5. Instructors can view all students enrolled in their courses

