-- SAMS Database Setup Script
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS sams_db;
USE sams_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    instructor_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (course_id, student_id),
    CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test users
-- Default password for all test users: 'password'
-- The hash below is generated using: password_hash('password', PASSWORD_DEFAULT)
-- To generate a new hash, run: php generate_password_hash.php

-- Admin user
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'admin')
ON DUPLICATE KEY UPDATE username=username;

-- Instructor user
INSERT INTO users (username, email, password, role) VALUES
('instructor', 'instructor@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'instructor')
ON DUPLICATE KEY UPDATE username=username;

-- Student user
INSERT INTO users (username, email, password, role) VALUES
('student', 'student@local.dev', '$2y$12$2gAFjze.5zbVDvH01.KSo..x4vnEtWsSEobnQBPFja/VBJWhtIa0y', 'student')
ON DUPLICATE KEY UPDATE username=username;

-- Test Credentials:
-- Admin: admin@local.dev / password
-- Instructor: instructor@local.dev / password
-- Student: student@local.dev / password

