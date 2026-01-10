# Postman API Testing Guide for SAMS Backend

Complete guide to test all API endpoints using Postman.

## Base URL
```
http://localhost/sams/Backend
```

**Note:** All endpoints go through `index.php` router. Use the routes listed below.

---

## Table of Contents
1. [Authentication](#1-authentication)
2. [Users Management](#2-users-management)
3. [Courses Management](#3-courses-management)
4. [Enrollments](#4-enrollments)
5. [Attendance Sessions](#5-attendance-sessions)
6. [Attendance Scanning](#6-attendance-scanning)
7. [Attendance Logs](#7-attendance-logs)
8. [Attendance Analytics](#8-attendance-analytics)

---

## 1. Authentication

### 1.1 Login
**Endpoint:** `POST /api/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "email": "admin@sams.com",
  "password": "your_password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@sams.com",
    "role": "admin"
  },
  "token": "generated_token_here"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

---

## 2. Users Management

**Note:** All user endpoints require `requested_by_role` parameter. Only `admin` role can access these endpoints.

### 2.1 Get All Users
**Endpoint:** `GET /api/users?requested_by_role=admin`

**Headers:**
```
Content-Type: application/json
```

**Query Parameters:**
- `requested_by_role` (required): `admin`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@sams.com",
      "role": "admin",
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

### 2.2 Create User
**Endpoint:** `POST /api/users`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "admin",
  "name": "Instructor One",
  "email": "instructor1@sams.com",
  "password": "password123",
  "role": "instructor"
}
```

**Valid Roles:** `admin`, `instructor`, `student`

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Instructor One",
    "email": "instructor1@sams.com",
    "role": "instructor"
  }
}
```

**Error Response (409) - Email exists:**
```json
{
  "success": false,
  "message": "Email already exists"
}
```

### 2.3 Update User
**Endpoint:** `PUT /api/users`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "admin",
  "id": 2,
  "name": "Updated Instructor Name",
  "email": "instructor1@sams.com",
  "role": "instructor"
}
```

**Optional:** Include `password` field to update password:
```json
{
  "requested_by_role": "admin",
  "id": 2,
  "name": "Updated Instructor Name",
  "email": "instructor1@sams.com",
  "role": "instructor",
  "password": "newpassword123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "User updated"
}
```

### 2.4 Delete User
**Endpoint:** `DELETE /api/users?id=2&requested_by_role=admin`

**Headers:**
```
Content-Type: application/json
```

**Query Parameters:**
- `id` (required): User ID to delete
- `requested_by_role` (required): `admin`

**Success Response (200):**
```json
{
  "success": true,
  "message": "User deleted"
}
```

---

## 3. Courses Management

### 3.1 Get All Courses
**Endpoint:** `GET /api/courses?requested_by_role=admin`

**For Admin (all courses):**
```
GET /api/courses?requested_by_role=admin
```

**For Instructor (only their courses):**
```
GET /api/courses?requested_by_role=instructor&instructor_email=instructor1@sams.com
```

**For Student (only enrolled courses):**
```
GET /api/courses?requested_by_role=student&student_email=student1@sams.com
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Introduction to Programming",
      "code": "CS101",
      "instructor_email": "instructor1@sams.com",
      "enrollment_count": 2,
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    }
  ]
}
```

### 3.2 Get Course by ID
**Endpoint:** `GET /api/courses?id=1&requested_by_role=admin`

**Query Parameters:**
- `id` (required): Course ID
- `requested_by_role` (required): `admin`, `instructor`, or `student`
- `instructor_email` (optional): Required if `requested_by_role=instructor`
- `student_email` (optional): Required if `requested_by_role=student`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "course": {
      "id": 1,
      "name": "Introduction to Programming",
      "code": "CS101",
      "instructor_email": "instructor1@sams.com",
      "instructor_name": "Instructor One",
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00"
    },
    "students": [
      {
        "id": 1,
        "student_id": 3,
        "student_name": "Student One",
        "student_email": "student1@sams.com",
        "enrolled_at": "2024-01-01 11:00:00"
      }
    ]
  }
}
```

### 3.3 Get Instructors List
**Endpoint:** `GET /api/courses/instructors?requested_by_role=admin`

**Query Parameters:**
- `requested_by_role` (required): `admin` or `instructor`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Instructor One",
      "email": "instructor1@sams.com"
    }
  ]
}
```

### 3.4 Get Students List
**Endpoint:** `GET /api/courses/students?requested_by_role=admin`

**Query Parameters:**
- `requested_by_role` (required): `admin` or `instructor`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "name": "Student One",
      "email": "student1@sams.com"
    }
  ]
}
```

### 3.5 Create Course
**Endpoint:** `POST /api/courses`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "admin",
  "name": "Introduction to Programming",
  "code": "CS101",
  "instructor_email": "instructor1@sams.com"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Introduction to Programming",
    "code": "CS101",
    "instructor_email": "instructor1@sams.com"
  }
}
```

### 3.6 Update Course
**Endpoint:** `PUT /api/courses`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "admin",
  "id": 1,
  "name": "Advanced Programming",
  "code": "CS201",
  "instructor_email": "instructor1@sams.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Course updated"
}
```

### 3.7 Delete Course
**Endpoint:** `DELETE /api/courses?id=1`

**Query Parameters:**
- `id` (required): Course ID

**Success Response (200):**
```json
{
  "success": true,
  "message": "Course deleted"
}
```

---

## 4. Enrollments

### 4.1 Enroll Student in Course
**Endpoint:** `POST /api/courses/enroll`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "admin",
  "course_id": 1,
  "student_email": "student1@sams.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Student enrolled"
}
```

**Error Response (409) - Already enrolled:**
```json
{
  "success": false,
  "message": "Student already enrolled"
}
```

### 4.2 Unenroll Student from Course
**Endpoint:** `DELETE /api/courses/unenroll?enrollment_id=1&requested_by_role=admin`

**Query Parameters:**
- `enrollment_id` (required): Enrollment ID (from course details)
- `requested_by_role` (required): `admin` or `instructor`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Student unenrolled successfully"
}
```

---

## 5. Attendance Sessions

### 5.1 Get All Sessions (or by Course)
**Endpoint:** `GET /api/courses/attendance-sessions`

**Get all sessions:**
```
GET /api/courses/attendance-sessions?requested_by_role=admin
```

**Get sessions for a specific course:**
```
GET /api/courses/attendance-sessions?requested_by_role=admin&course_id=1
```

**For students (must be enrolled):**
```
GET /api/courses/attendance-sessions?requested_by_role=student&student_email=student1@sams.com&course_id=1
```

**Query Parameters:**
- `requested_by_role` (required): `admin`, `instructor`, or `student`
- `course_id` (optional): Filter by course ID
- `student_email` (required if `requested_by_role=student`)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "token": "550e8400-e29b-41d4-a716-446655440000",
      "expires_at": "2024-01-01 10:15:00",
      "created_by_email": "instructor1@sams.com",
      "created_at": "2024-01-01 10:00:00",
      "course_name": "Introduction to Programming",
      "is_expired": 0,
      "scanned_count": 2,
      "enrolled_count": 2
    }
  ]
}
```

### 5.2 Get Latest Session for Course
**Endpoint:** `GET /api/courses/attendance-session`

**Query Parameters:**
- `course_id` (required): Course ID
- `requested_by_role` (required): `admin`, `instructor`, or `student`
- `student_email` (required if `requested_by_role=student`)

**Example:**
```
GET /api/courses/attendance-session?course_id=1&requested_by_role=instructor
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course_id": 1,
    "token": "550e8400-e29b-41d4-a716-446655440000",
    "expires_at": "2024-01-01 10:15:00",
    "created_by_email": "instructor1@sams.com",
    "course_name": "Introduction to Programming",
    "is_expired": 0
  }
}
```

### 5.3 Create Attendance Session
**Endpoint:** `POST /api/courses/attendance-session`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "instructor",
  "requested_by_email": "instructor1@sams.com",
  "course_id": 1
}
```

**Note:** Session expires 15 minutes after creation.

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "course_name": "Introduction to Programming",
    "token": "550e8400-e29b-41d4-a716-446655440000",
    "expires_at": "2024-01-01 10:15:00"
  }
}
```

---

## 6. Attendance Scanning

### 6.1 Scan QR Code (Record Attendance)
**Endpoint:** `POST /api/courses/attendance-scan`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "requested_by_role": "student",
  "token": "550e8400-e29b-41d4-a716-446655440000",
  "student_email": "student1@sams.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Attendance recorded",
  "data": {
    "course_id": 1,
    "course_name": "Introduction to Programming"
  }
}
```

**Error Responses:**

**Invalid token (404):**
```json
{
  "success": false,
  "message": "Invalid or unknown attendance token"
}
```

**Expired session (410):**
```json
{
  "success": false,
  "message": "Attendance session has expired"
}
```

**Not enrolled (403):**
```json
{
  "success": false,
  "message": "You are not enrolled in this course"
}
```

**Note:** Duplicate scans are ignored (idempotent operation).

---

## 7. Attendance Logs

### 7.1 Get Attendance Logs for Session
**Endpoint:** `GET /api/courses/attendance-logs`

**Query Parameters:**
- `token` (optional): Session token
- `session_id` (optional): Session ID
- `include_students` (optional): `1` to include full roster with present/absent status
- `requested_by_role` (required): `admin` or `instructor`

**Examples:**

**By token:**
```
GET /api/courses/attendance-logs?token=550e8400-e29b-41d4-a716-446655440000&requested_by_role=admin
```

**By session ID:**
```
GET /api/courses/attendance-logs?session_id=1&requested_by_role=admin
```

**With roster (include_students=1):**
```
GET /api/courses/attendance-logs?session_id=1&requested_by_role=admin&include_students=1
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "course_id": 1,
      "token": "550e8400-e29b-41d4-a716-446655440000",
      "expires_at": "2024-01-01 10:15:00",
      "course_name": "Introduction to Programming"
    },
    "logs": [
      {
        "id": 1,
        "scanned_at": "2024-01-01 10:05:00",
        "student_id": 3,
        "student_name": "Student One",
        "student_email": "student1@sams.com"
      }
    ],
    "roster": [
      {
        "student_id": 3,
        "student_name": "Student One",
        "student_email": "student1@sams.com",
        "scanned_at": "2024-01-01 10:05:00",
        "present": 1
      },
      {
        "student_id": 4,
        "student_name": "Student Two",
        "student_email": "student2@sams.com",
        "scanned_at": null,
        "present": 0
      }
    ]
  }
}
```

---

## 8. Attendance Analytics

### 8.1 Get Course Attendance Analytics
**Endpoint:** `GET /api/courses/attendance-analytics?course_id=1&requested_by_role=admin`

**Query Parameters:**
- `course_id` (required): Course ID
- `requested_by_role` (required): `admin` or `instructor`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "sessions_count": 5,
      "active_sessions": 1,
      "enrolled_count": 2,
      "last_session_at": "2024-01-01 10:00:00"
    },
    "sessions": [
      {
        "id": 5,
        "token": "550e8400-e29b-41d4-a716-446655440000",
        "created_at": "2024-01-01 10:00:00",
        "expires_at": "2024-01-01 10:15:00",
        "created_by_email": "instructor1@sams.com",
        "is_expired": 0,
        "scanned_count": 2,
        "enrolled_count": 2
      }
    ],
    "students": [
      {
        "student_id": 3,
        "student_name": "Student One",
        "student_email": "student1@sams.com",
        "total_sessions": 5,
        "attended_sessions": 4
      },
      {
        "student_id": 4,
        "student_name": "Student Two",
        "student_email": "student2@sams.com",
        "total_sessions": 5,
        "attended_sessions": 3
      }
    ]
  }
}
```

---

## 9. Test Endpoint

### 9.1 Test API Connection
**Endpoint:** `GET /api/test`

**Success Response (200):**
```json
{
  "success": true,
  "message": "PHP REST API is working! React frontend connected successfully.",
  "timestamp": "2024-01-01 10:00:00",
  "method": "GET"
}
```

---

## Postman Collection Setup

### Step 1: Create Environment Variables

Create a new environment in Postman with these variables:

| Variable | Initial Value | Current Value |
|----------|--------------|---------------|
| `base_url` | `http://localhost/sams/Backend` | `http://localhost/sams/Backend` |
| `admin_email` | `admin@sams.com` | `admin@sams.com` |
| `admin_password` | `your_password` | `your_password` |
| `instructor_email` | `instructor1@sams.com` | `instructor1@sams.com` |
| `student_email` | `student1@sams.com` | `student1@sams.com` |
| `auth_token` | (leave empty) | (will be set after login) |
| `course_id` | (leave empty) | (set after creating course) |
| `session_token` | (leave empty) | (set after creating session) |

### Step 2: Common Headers

Create a collection-level header:
- `Content-Type: application/json`

### Step 3: Pre-request Scripts

For endpoints requiring `requested_by_role`, add this to the Pre-request Script tab:

```javascript
// Set requested_by_role based on endpoint
// You can customize this per request
pm.environment.set("requested_by_role", "admin");
```

### Step 4: Test Scripts

Add this to Tests tab for login endpoint to save token:

```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.token) {
        pm.environment.set("auth_token", jsonData.token);
    }
}
```

---

## Testing Workflow

### Complete Flow Example:

1. **Test API Connection**
   - `GET /api/test`

2. **Login as Admin**
   - `POST /api/auth/login`
   - Save token from response

3. **Create Instructor User**
   - `POST /api/users`
   - Body: `{"requested_by_role": "admin", "name": "Instructor One", "email": "instructor1@sams.com", "password": "password123", "role": "instructor"}`

4. **Create Student User**
   - `POST /api/users`
   - Body: `{"requested_by_role": "admin", "name": "Student One", "email": "student1@sams.com", "password": "password123", "role": "student"}`

5. **Get Instructors List**
   - `GET /api/courses/instructors?requested_by_role=admin`

6. **Create Course**
   - `POST /api/courses`
   - Body: `{"requested_by_role": "admin", "name": "CS101", "code": "CS101", "instructor_email": "instructor1@sams.com"}`
   - Save course_id from response

7. **Enroll Student**
   - `POST /api/courses/enroll`
   - Body: `{"requested_by_role": "admin", "course_id": 1, "student_email": "student1@sams.com"}`

8. **Create Attendance Session**
   - `POST /api/courses/attendance-session`
   - Body: `{"requested_by_role": "instructor", "requested_by_email": "instructor1@sams.com", "course_id": 1}`
   - Save token from response

9. **Scan QR Code (Record Attendance)**
   - `POST /api/courses/attendance-scan`
   - Body: `{"requested_by_role": "student", "token": "saved_token", "student_email": "student1@sams.com"}`

10. **Get Attendance Logs**
    - `GET /api/courses/attendance-logs?session_id=1&requested_by_role=admin&include_students=1`

11. **Get Analytics**
    - `GET /api/courses/attendance-analytics?course_id=1&requested_by_role=admin`

---

## Common Issues & Troubleshooting

### Issue: 404 Not Found
- **Solution:** Make sure Apache mod_rewrite is enabled
- Check `.htaccess` file exists in Backend directory
- Verify base URL is correct: `http://localhost/sams/Backend`

### Issue: 403 Forbidden - Role Required
- **Solution:** Add `requested_by_role` parameter to query string or request body
- Valid roles: `admin`, `instructor`, `student`

### Issue: 500 Internal Server Error
- **Solution:** Check database connection in `api/config.php`
- Verify database `sams_db` exists
- Check PHP error logs: `Backend/php_errors.log`

### Issue: CORS Errors
- **Solution:** CORS headers are already set in the API
- Make sure you're using the correct base URL
- Check browser console for specific CORS errors

### Issue: Database Connection Failed
- **Solution:** 
  - Verify MySQL is running in XAMPP
  - Check database credentials in `api/config.php`
  - Default: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=`, `DB_NAME=sams_db`

---

## Quick Reference: All Endpoints

| Method | Endpoint | Required Role | Description |
|--------|----------|---------------|-------------|
| GET | `/api/test` | None | Test API connection |
| POST | `/api/auth/login` | None | Login user |
| GET | `/api/users` | admin | Get all users |
| POST | `/api/users` | admin | Create user |
| PUT | `/api/users` | admin | Update user |
| DELETE | `/api/users` | admin | Delete user |
| GET | `/api/courses` | admin/instructor/student | Get courses |
| GET | `/api/courses?id=X` | admin/instructor/student | Get course by ID |
| GET | `/api/courses/instructors` | admin/instructor | Get instructors list |
| GET | `/api/courses/students` | admin/instructor | Get students list |
| POST | `/api/courses` | admin/instructor | Create course |
| PUT | `/api/courses` | admin/instructor | Update course |
| DELETE | `/api/courses` | admin/instructor | Delete course |
| POST | `/api/courses/enroll` | admin/instructor | Enroll student |
| DELETE | `/api/courses/unenroll` | admin/instructor | Unenroll student |
| GET | `/api/courses/attendance-sessions` | admin/instructor/student | Get sessions |
| GET | `/api/courses/attendance-session` | admin/instructor/student | Get latest session |
| POST | `/api/courses/attendance-session` | admin/instructor | Create session |
| POST | `/api/courses/attendance-scan` | student | Scan QR code |
| GET | `/api/courses/attendance-logs` | admin/instructor | Get attendance logs |
| GET | `/api/courses/attendance-analytics` | admin/instructor | Get analytics |

---

## Notes

1. **Authentication:** Currently, the API uses role-based access control via `requested_by_role` parameter. In production, implement proper JWT/session-based authentication.

2. **Session Expiration:** Attendance sessions expire 15 minutes after creation.

3. **Idempotent Operations:** Scanning the same QR code multiple times won't create duplicate records.

4. **Time Zone:** All timestamps are in GMT+8 (Asia/Manila).

5. **Database:** Make sure all tables are created using `database_setup.sql` before testing.

---

Happy Testing! 🚀

