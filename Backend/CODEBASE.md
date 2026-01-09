# Codebase Documentation

## Overview

**Student Attendance Monitoring System (SAMS)** - A RESTful API built with PHP using microservices architecture.

**Base URL:** `http://localhost/sams/Backend/gateway/api`

---

## Architecture

### System Flow

```
Frontend → API Gateway → Microservices → Database
```

### Key Principles

1. **Single Entry Point** - All requests go through `gateway/index.php`
2. **Service Classes** - Microservices are PHP classes, not web endpoints
3. **Gateway Routing** - Gateway routes requests to appropriate services
4. **Security** - Direct microservice access is blocked

---

## File Structure

```
Backend/
├── gateway/
│   ├── index.php          # API Gateway (ONLY entry point)
│   └── .htaccess          # Routing rules
│
├── microservices/
│   ├── config.php         # Shared configuration
│   ├── .htaccess          # Security (blocks direct access)
│   │
│   ├── AuthService.php    # Authentication service
│   ├── UserService.php    # User management service
│   ├── CourseService.php  # Course management service
│   ├── AttendanceService.php # Attendance service
│   ├── EnrollmentService.php # Enrollment service
│   └── TestService.php    # Test/health check service
│
└── database_setup.sql     # Database schema
```

---

## How It Works

### Request Flow

1. **Request arrives** → `gateway/index.php`
2. **Gateway parses** → URI, method, query params, body
3. **Gateway routes** → Instantiates appropriate service class
4. **Gateway authorizes** → Checks roles using `requireRole()`
5. **Service executes** → Returns data array
6. **Gateway responds** → Formats and sends JSON response

### Example: Login Request

```php
// Request: POST /api/auth/login
// Body: { "email": "...", "password": "..." }

// Gateway (gateway/index.php)
$service = new AuthService();
$response = $service->login($body);
sendResponse($response, $statusCode);

// Service (microservices/AuthService.php)
public function login($data) {
    // Validate, authenticate, return array
    return ['success' => true, 'user' => ...];
}
```

---

## Microservices

### 1. AuthService

**Purpose:** User authentication

**Methods:**
- `login($data)` - Authenticate user and return token

**What it does:**
- Validates user credentials
- Verifies password against database
- Generates session token
- Returns user information

**Why it's used:**
- Secure user authentication
- Session management
- Role-based access control

---

### 2. UserService

**Purpose:** User management (CRUD operations)

**Methods:**
- `getAllUsers($query, $body)` - List all users
- `createUser($body)` - Create new user
- `updateUser($body)` - Update user
- `deleteUser($id)` - Delete user
- `getInstructors()` - List instructors
- `getStudents()` - List students

**What it does:**
- Manages all user accounts
- Handles user CRUD operations
- Provides user lists by role

**Why it's used:**
- Centralized user management
- Admin functionality
- User administration

---

### 3. CourseService

**Purpose:** Course management

**Methods:**
- `getCourses($query, $body)` - List courses (filtered by role)
- `getCourseById($courseId, $role, $email)` - Get course details
- `createCourse($body)` - Create course
- `updateCourse($body)` - Update course
- `deleteCourse($id)` - Delete course

**What it does:**
- Manages courses
- Handles course CRUD operations
- Filters courses by user role

**Why it's used:**
- Course administration
- Role-based course access
- Course management for instructors

---

### 4. AttendanceService

**Purpose:** Attendance tracking and analytics

**Methods:**
- `getSessions($query)` - List attendance sessions
- `getLatestSession($courseId)` - Get latest session
- `createSession($body)` - Create attendance session (QR code)
- `scanAttendance($body)` - Record attendance via QR scan
- `getLogs($query)` - Get attendance logs
- `getAnalytics($courseId)` - Get attendance analytics

**What it does:**
- Creates QR code sessions
- Records student attendance
- Tracks attendance history
- Generates analytics

**Why it's used:**
- QR code-based attendance
- Automated attendance tracking
- Attendance reporting
- Analytics and insights

---

### 5. EnrollmentService

**Purpose:** Student enrollment management

**Methods:**
- `enrollStudent($body)` - Enroll student in course
- `unenrollStudent($enrollmentId)` - Remove student from course

**What it does:**
- Manages course enrollments
- Links students to courses
- Handles enrollment/unenrollment

**Why it's used:**
- Student registration
- Course roster management
- Enrollment tracking

---

### 6. TestService

**Purpose:** Health check and testing

**Methods:**
- `test()` - Health check endpoint

**What it does:**
- Verifies system is operational
- Returns system status

**Why it's used:**
- System health monitoring
- API testing
- Debugging

---

## API Endpoints

### Authentication

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| POST | `/api/auth/login` | User login | AuthService |

**Request:**
```json
{
  "email": "admin@local.dev",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@local.dev",
    "role": "admin"
  },
  "token": "abc123..."
}
```

---

### User Management (Admin Only)

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| GET | `/api/users` | List all users | UserService |
| POST | `/api/users` | Create user | UserService |
| PUT | `/api/users` | Update user | UserService |
| DELETE | `/api/users?id={id}` | Delete user | UserService |

---

### Course Management

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| GET | `/api/courses` | List courses | CourseService |
| GET | `/api/courses?id={id}` | Get course details | CourseService |
| GET | `/api/courses/instructors` | List instructors | UserService |
| GET | `/api/courses/students` | List students | UserService |
| POST | `/api/courses` | Create course | CourseService |
| PUT | `/api/courses` | Update course | CourseService |
| DELETE | `/api/courses?id={id}` | Delete course | CourseService |

---

### Enrollment

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| POST | `/api/courses/enroll` | Enroll student | EnrollmentService |
| DELETE | `/api/courses/unenroll?enrollment_id={id}` | Unenroll student | EnrollmentService |

---

### Attendance

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| GET | `/api/courses/attendance-sessions` | List sessions | AttendanceService |
| GET | `/api/courses/attendance-session?course_id={id}` | Get latest session | AttendanceService |
| POST | `/api/courses/attendance-session` | Create session | AttendanceService |
| POST | `/api/courses/attendance-scan` | Scan QR code | AttendanceService |
| GET | `/api/courses/attendance-logs` | Get logs | AttendanceService |
| GET | `/api/courses/attendance-analytics` | Get analytics | AttendanceService |

---

### Test

| Method | Endpoint | Purpose | Service |
|--------|----------|---------|---------|
| GET | `/api/test` | Health check | TestService |

---

## Security

### Role-Based Access Control (RBAC)

- **Admin:** Full access to all features
- **Instructor:** Course management + attendance tracking
- **Student:** View courses + scan attendance

### Security Features

1. **Direct Access Blocked**
   - `.htaccess` prevents direct microservice access
   - All requests must go through Gateway

2. **Input Validation**
   - All inputs validated
   - SQL injection prevention (prepared statements)
   - Email format validation

3. **Authentication**
   - Token-based authentication
   - Password hashing
   - Session management

4. **Authorization**
   - Role-based access control
   - Permission checks before service calls

---

## Error Handling

All APIs return consistent error formats:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `500` - Internal Server Error

---

## Database

### Database: `sams_db`

**Tables:**
- `users` - System users (admin, instructor, student)
- `courses` - Course information
- `enrollments` - Student-course relationships
- `attendance_sessions` - QR code sessions
- `attendance_logs` - Attendance records

### Connection

Each microservice creates its own database connection using:
- `config.php` - Database configuration
- `db()` function - Connection helper

---

## API Statistics

- **Total Endpoints:** 20
- **Custom-Built APIs:** 20 (100%)
- **Third-Party APIs:** 0
- **All Pass Through Gateway:** ✅ Yes
- **Microservices:** 6 services
- **Authentication Required:** Most endpoints

---

## Key Features

1. **Microservices Architecture** - 6 independent services
2. **API Gateway** - Single entry point
3. **RESTful APIs** - Standard HTTP methods
4. **Database Integration** - MySQL with prepared statements
5. **Role-Based Access** - Admin, Instructor, Student
6. **QR Code Attendance** - Session-based tracking

---

## Benefits

1. **Single Entry Point** - Centralized routing
2. **Clean Separation** - Services are classes, not endpoints
3. **Better Organization** - Clear method signatures
4. **Easier Testing** - Test service classes directly
5. **Maintainability** - Easy to understand and modify
6. **Scalability** - Services can be scaled independently

---

## Testing

### Test Gateway
```bash
GET http://localhost/sams/Backend/gateway/api/test
```

### Test Login
```bash
POST http://localhost/sams/Backend/gateway/api/auth/login
Content-Type: application/json

{
  "email": "admin@local.dev",
  "password": "password"
}
```

---

## Default Credentials

- **Admin:** `admin@local.dev` / `password`
- **Instructor:** `instructor@local.dev` / `password`
- **Student:** `student@local.dev` / `password`

---

**Status:** ✅ Production Ready

