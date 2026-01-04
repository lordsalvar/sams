# SAMS Attendance System - Entity Relationship Diagram

## Mermaid ERD

```mermaid
erDiagram
    users ||--o{ courses : "instructor_email"
    users ||--o{ enrollments : "student_id"
    users ||--o{ attendance_logs : "student_id"
    courses ||--o{ enrollments : "course_id"
    courses ||--o{ attendance_sessions : "course_id"
    attendance_sessions ||--o{ attendance_logs : "session_id"

    users {
        int id PK "AUTO_INCREMENT"
        varchar name "NOT NULL"
        varchar email UK "NOT NULL, UNIQUE"
        varchar password "NOT NULL"
        enum role "admin|instructor|student, DEFAULT 'student'"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
        timestamp updated_at "ON UPDATE CURRENT_TIMESTAMP"
    }

    courses {
        int id PK "AUTO_INCREMENT"
        varchar name "NOT NULL"
        varchar code UK "NOT NULL, UNIQUE"
        varchar instructor_email FK "NOT NULL"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
        timestamp updated_at "ON UPDATE CURRENT_TIMESTAMP"
    }

    enrollments {
        int id PK "AUTO_INCREMENT"
        int course_id FK "NOT NULL"
        int student_id FK "NOT NULL"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
        unique_key unique_enrollment "course_id, student_id"
    }

    attendance_sessions {
        int id PK "AUTO_INCREMENT"
        int course_id FK "NOT NULL"
        char token UK "CHAR(36), NOT NULL, UNIQUE"
        datetime expires_at "NOT NULL"
        varchar created_by_email "NOT NULL"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
    }

    attendance_logs {
        int id PK "AUTO_INCREMENT"
        int session_id FK "NOT NULL"
        int student_id FK "NOT NULL"
        timestamp scanned_at "DEFAULT CURRENT_TIMESTAMP"
        unique_key uniq_session_student "session_id, student_id"
    }
```

## Business Flow Logic

### 1. User Management Flow
```
Admin/Instructor creates Users → users table
- Users can have roles: admin, instructor, or student
- Each user has unique email
```

### 2. Course Creation Flow
```
Admin/Instructor creates Course → courses table
- Course assigned to Instructor via instructor_email (FK to users.email)
- Course has unique code
- ON DELETE RESTRICT: Cannot delete instructor if courses exist
```

### 3. Enrollment Flow
```
Admin/Instructor enrolls Student → enrollments table
- Links student_id (FK to users.id) with course_id (FK to courses.id)
- Many-to-Many: Student can enroll in multiple courses
- Many-to-Many: Course can have multiple students
- UNIQUE constraint prevents duplicate enrollments
- ON DELETE CASCADE: Enrollment removed if course or student deleted
```

### 4. Attendance Session Creation Flow
```
Instructor/Admin creates Attendance Session → attendance_sessions table
- Session belongs to a course (course_id FK to courses.id)
- Generates unique UUID token (CHAR(36))
- Sets expiration time (typically 15 minutes from creation)
- Stores creator email (created_by_email)
- ON DELETE CASCADE: Sessions removed if course deleted
```

### 5. Attendance Scanning Flow
```
Student scans QR Code → attendance_logs table
- Validates token exists in attendance_sessions
- Checks token hasn't expired (expires_at > NOW())
- Verifies student is enrolled in course (via enrollments table)
- Records attendance: session_id + student_id
- UNIQUE constraint prevents duplicate scans per session
- ON DELETE CASCADE: Logs removed if session or student deleted
```

## Key Constraints & Business Rules

### Foreign Key Relationships
- `courses.instructor_email` → `users.email` (ON UPDATE CASCADE, ON DELETE RESTRICT)
- `enrollments.course_id` → `courses.id` (ON DELETE CASCADE)
- `enrollments.student_id` → `users.id` (ON DELETE CASCADE)
- `attendance_sessions.course_id` → `courses.id` (ON DELETE CASCADE)
- `attendance_logs.session_id` → `attendance_sessions.id` (ON DELETE CASCADE)
- `attendance_logs.student_id` → `users.id` (ON DELETE CASCADE)

### Unique Constraints
- `users.email` - Unique email per user
- `courses.code` - Unique course code
- `attendance_sessions.token` - Unique QR token per session
- `enrollments(course_id, student_id)` - One enrollment per student-course pair
- `attendance_logs(session_id, student_id)` - One attendance record per student per session

### Indexes for Performance
- `users`: idx_email, idx_role
- `courses`: idx_code, idx_instructor
- `enrollments`: idx_course, idx_student
- `attendance_sessions`: idx_attendance_course, idx_attendance_expires
- `attendance_logs`: idx_session, idx_student

## Attendance Workflow Sequence

```mermaid
sequenceDiagram
    participant Admin/Instructor
    participant System
    participant Student
    participant Database

    Admin/Instructor->>System: Create Course
    System->>Database: INSERT INTO courses
    Database-->>System: Course created

    Admin/Instructor->>System: Enroll Student
    System->>Database: INSERT INTO enrollments
    Database-->>System: Enrollment created

    Instructor->>System: Create Attendance Session
    System->>Database: INSERT INTO attendance_sessions<br/>(Generate UUID token, Set expires_at)
    Database-->>System: Session created with token
    System-->>Instructor: Return QR Code (token)

    Student->>System: Scan QR Code (token)
    System->>Database: Validate token exists & not expired
    Database-->>System: Session valid
    System->>Database: Verify student enrollment
    Database-->>System: Enrollment confirmed
    System->>Database: INSERT INTO attendance_logs<br/>(session_id, student_id)
    Database-->>System: Attendance recorded
    System-->>Student: Attendance confirmed

    Instructor->>System: View Attendance Logs
    System->>Database: SELECT attendance_logs<br/>JOIN users, sessions
    Database-->>System: Attendance records
    System-->>Instructor: Display attendance list
```

## Data Flow Summary

1. **Setup Phase**: Admin creates users and courses
2. **Enrollment Phase**: Admin/Instructor enrolls students in courses
3. **Session Creation**: Instructor creates attendance session (generates QR token)
4. **Attendance Recording**: Student scans QR code → System validates → Records attendance
5. **Reporting**: Instructor/Admin views attendance logs per session

