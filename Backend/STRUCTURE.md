# Backend API Structure

## Overview
The backend has been refactored from a monolithic `courses.php` file (727 lines) into a well-organized, modular structure following separation of concerns.

## Directory Structure

```
Backend/
├── api/
│   ├── auth/
│   │   └── login.php          # Authentication endpoints
│   ├── attendance/             # Attendance-related endpoints
│   │   ├── sessions.php        # Session management (list, create, get)
│   │   ├── logs.php           # Attendance logs retrieval
│   │   ├── scan.php           # Student QR code scanning
│   │   └── analytics.php      # Attendance analytics and reports
│   ├── helpers/
│   │   └── functions.php      # Common helper functions
│   ├── config.php             # Configuration and global functions
│   ├── courses.php            # Courses CRUD operations only
│   ├── enrollments.php        # Enrollment/unenrollment operations
│   ├── users.php              # User management
│   └── test.php               # Testing endpoints
├── index.php                  # Main router
└── ...
```

## File Responsibilities

### `api/helpers/functions.php`
Common utility functions used across all endpoints:
- `db()` - Database connection
- `requireRole()` - Role-based authorization
- `uuidv4()` - UUID generation
- `setCorsHeaders()` - CORS header setup
- `handleOptionsRequest()` - OPTIONS request handling

### `api/courses.php`
**Only handles course CRUD operations:**
- GET `/courses` - List all courses (filtered by role)
- GET `/courses?id=X` - Get specific course with enrolled students
- POST `/courses` - Create new course
- PUT `/courses` - Update course
- DELETE `/courses?id=X` - Delete course
- GET `/courses/instructors` - List all instructors
- GET `/courses/students` - List all students

### `api/attendance/sessions.php`
**Attendance session management:**
- GET `/courses/attendance-sessions` - List all sessions (or by course_id)
- GET `/courses/attendance-session?course_id=X` - Get latest session for course
- POST `/courses/attendance-session` - Create new attendance session (QR code)

### `api/attendance/logs.php`
**Attendance logs:**
- GET `/courses/attendance-logs?session_id=X` - Get attendance logs for a session
- GET `/courses/attendance-logs?token=X` - Get logs by session token
- Supports `include_students` parameter for full roster

### `api/attendance/scan.php`
**Student QR code scanning:**
- POST `/courses/attendance-scan` - Record attendance via QR scan

### `api/attendance/analytics.php`
**Attendance analytics:**
- GET `/courses/attendance-analytics?course_id=X` - Get analytics for a course
- Returns summary, sessions breakdown, and student attendance stats

### `api/enrollments.php`
**Student enrollment management:**
- POST `/courses/enroll` - Enroll a student in a course
- DELETE `/courses/unenroll?enrollment_id=X` - Unenroll a student

## Benefits of This Structure

1. **Separation of Concerns**: Each file has a single, clear responsibility
2. **Maintainability**: Easier to find and modify specific functionality
3. **Scalability**: Easy to add new endpoints without bloating existing files
4. **Reusability**: Common functions are centralized in helpers
5. **Readability**: Smaller, focused files are easier to understand
6. **Testing**: Easier to test individual components

## Routing

The `index.php` router handles all incoming requests and routes them to the appropriate endpoint file. It uses both explicit route mapping and fallback to direct file access for flexibility.

## Migration Notes

All existing API endpoints continue to work exactly as before. The refactoring is internal only - no frontend changes are required.

