# 1. System Requirements

## A. Technology Stack

### Backend
- **REST API** built with PHP
- Use mysqli or PDO to connect to the database
- Use prepared statements for all database queries
- Process API requests and return JSON responses
- Implement proper HTTP status codes and error handling

### Frontend
- **React** application with TypeScript
- Modern UI components (using shadcn/ui)
- Responsive design for mobile and desktop
- API integration using fetch or axios

## B. Dashboard Layout

Your dashboard must include:

- A clear navigation menu
- A welcome message (e.g., "Welcome, [Username]")
- Dynamic sections based on user role (Admin/Instructor/Student)
- Role-specific dashboards with appropriate features

## C. Student Attendance Monitoring System

### System Overview
A QR code-based attendance system where:
- Instructors belong to courses and manage attendance
- Instructors can enroll students to their courses
- Instructors generate QR codes for course attendance sessions
- Students scan QR codes to mark their attendance

### Core Features

#### For Instructors:
- **Course Management:**
  - View assigned courses
  - Enroll students to courses
  - Manage course enrollment (add/remove students)
  
- **Attendance Management:**
  - Generate QR codes for attendance sessions
  - View attendance records for their courses
  - Track student attendance history
  - Set attendance session time windows

#### For Students:
- **Attendance:**
  - Scan QR codes to mark attendance
  - View personal attendance history
  - View attendance status for enrolled courses

#### For Admins:
- **System Management:**
  - Full access to all courses and attendance records
  - Manage users (instructors and students)
  - Manage courses and enrollments
  - View system-wide attendance reports

## D. CRUD Functionality

Your project must allow:

- **Create:**
  - New courses
  - Student enrollments
  - Attendance sessions (QR code generation)
  - Attendance records (via QR scan)
  - User accounts

- **Read:**
  - Display courses, students, instructors
  - View attendance records
  - View enrollment lists
  - View user profiles

- **Update:**
  - Course information
  - User profiles
  - Attendance records (corrections)
  - Enrollment status

- **Delete:**
  - Courses (with proper validation)
  - Student enrollments
  - Attendance records (with admin approval)
  - User accounts

All CRUD operations must connect to your database through REST API endpoints.

## E. REST API Endpoints

Your backend must provide RESTful endpoints for:

- **Authentication:**
  - `POST /api/auth/login` - User login
  - `POST /api/auth/logout` - User logout
  - `GET /api/auth/me` - Get current user

- **Courses:**
  - `GET /api/courses` - List courses
  - `POST /api/courses` - Create course
  - `GET /api/courses/:id` - Get course details
  - `PUT /api/courses/:id` - Update course
  - `DELETE /api/courses/:id` - Delete course

- **Enrollments:**
  - `GET /api/courses/:id/students` - Get enrolled students
  - `POST /api/courses/:id/enroll` - Enroll student
  - `DELETE /api/courses/:id/enroll/:studentId` - Remove enrollment

- **Attendance:**
  - `POST /api/attendance/sessions` - Create attendance session (generate QR)
  - `GET /api/attendance/sessions/:id/qr` - Get QR code for session
  - `POST /api/attendance/scan` - Scan QR code (mark attendance)
  - `GET /api/attendance/courses/:id` - Get attendance records for course
  - `GET /api/attendance/students/:id` - Get student attendance history

- **Users:**
  - `GET /api/users` - List users (admin only)
  - `POST /api/users` - Create user
  - `GET /api/users/:id` - Get user details
  - `PUT /api/users/:id` - Update user
  - `DELETE /api/users/:id` - Delete user

## F. Database Integration

Your system must:

- Use mysqli or PDO to connect to the database
- Use prepared statements for all queries
- Implement proper database schema for:
  - Users (id, username, email, password, role, created_at)
  - Courses (id, name, code, instructor_id, created_at)
  - Enrollments (id, course_id, student_id, enrolled_at)
  - Attendance Sessions (id, course_id, instructor_id, qr_code, expires_at, created_at)
  - Attendance Records (id, session_id, student_id, scanned_at, status)

## G. Security Requirements

Your project must include:

- **Password Security:**
  - `password_hash()` for storing passwords
  - `password_verify()` for authentication

- **Input Validation:**
  - Validate all API inputs
  - Use `htmlspecialchars()` to prevent XSS
  - Sanitize user inputs before database operations

- **Authentication & Authorization:**
  - JWT tokens or secure session handling for API authentication
  - Role-Based Access Control (RBAC):
    - **Admin** → full system access
    - **Instructor** → manage assigned courses, enrollments, and attendance
    - **Student** → view own attendance, scan QR codes

- **API Security:**
  - CORS configuration
  - Rate limiting for API endpoints
  - Secure QR code generation with expiration
  - Validate QR code authenticity and expiration

- **QR Code Security:**
  - Generate unique, time-limited QR codes
  - Validate QR code signatures/tokens
  - Prevent duplicate attendance scans
  - Set expiration times for attendance sessions

