# SAMS Analytics & Data Mining System - Complete Explanation

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Data Flow](#data-flow)
4. [How It Works](#how-it-works)
5. [Components Breakdown](#components-breakdown)
6. [Analytics Achievement](#analytics-achievement)
7. [Data Mining Features](#data-mining-features)

---

## Overview

The SAMS Analytics system implements a **Star Schema Data Warehouse** design to transform operational attendance data into an analytics-optimized structure. This enables fast reporting, trend analysis, and provides structured data for predictive analytics and data mining.

### Key Objectives

-   **Performance**: Optimize queries for analytics (aggregations, groupings, time-based analysis)
-   **Data Integrity**: Maintain referential integrity while denormalizing for speed
-   **Completeness**: Automatically generate "Absent" records for enrolled students
-   **Flexibility**: Support multiple analytical views (by student, course, day, time period)

---

## System Architecture

### Star Schema Design

The system uses a **Star Schema** pattern, which consists of:

```
                    ┌─────────────────┐
                    │  fact_attendance│  ← Central Fact Table (Measures)
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼────┐         ┌─────▼─────┐        ┌────▼────┐
   │Dim_date │         │Dim_student│        │Dim_Course│
   │(Time)   │         │(Who)     │        │(What)   │
   └─────────┘         └──────────┘        └─────────┘

   ┌─────────┐         ┌──────────┐
   │Dim_Session│        │fact_enrollment│
   │(When/How)│         │(Relationships)│
   └─────────┘         └──────────┘
```

**Dimension Tables** (Descriptive attributes):

-   `Dim_student` - Who (student information)
-   `Dim_Course` - What (course information)
-   `Dim_Session` - When/How (attendance session details)
-   `Dim_date` - When (calendar/time attributes)
-   `Dim_Instructor` - Who (instructor information)

**Fact Tables** (Measurable events):

-   `fact_attendance` - Attendance events (Present/Absent/Late)
-   `fact_enrollment` - Student-course enrollment relationships

### Why Star Schema?

1. **Query Performance**: Pre-joined structure reduces complex joins
2. **Analytics-Friendly**: Dimensions provide natural grouping/filtering
3. **Scalability**: Fact tables can grow large while dimensions stay manageable
4. **Maintainability**: Clear separation of descriptive vs. measurable data

---

## Data Flow

### Complete Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    OPERATIONAL DATABASE                         │
│  (Source Tables - Normalized, Transactional)                   │
├─────────────────────────────────────────────────────────────────┤
│  • users (students, instructors)                                │
│  • courses                                                       │
│  • enrollments                                                   │
│  • attendance_sessions                                          │
│  • attendance_logs (QR scans)                                    │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ ETL Process (Extract, Transform, Load)
                     │ via Stored Procedures
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ANALYTICS DATABASE                            │
│  (Star Schema - Denormalized, Analytics-Optimized)              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  DIMENSIONS (Descriptive)                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ Dim_student  │  │ Dim_Course   │  │ Dim_Session  │          │
│  │ Dim_Instructor│  │ Dim_date     │  │              │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                   │
│  FACTS (Measurable Events)                                        │
│  ┌──────────────────┐  ┌──────────────────┐                     │
│  │ fact_attendance  │  │ fact_enrollment  │                     │
│  └──────────────────┘  └──────────────────┘                     │
│                                                                   │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ Analytics Queries
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ANALYTICS VIEWS                               │
│  (Pre-aggregated, Optimized for Reporting)                      │
├─────────────────────────────────────────────────────────────────┤
│  • vw_student_attendance_features                                │
│  • vw_attendance_by_day                                          │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ API Layer
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND ANALYTICS UI                        │
│  • Student Features Tab                                          │
│  • By Day of Week Tab                                            │
│  • Summary Tab                                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## How It Works

### Step-by-Step Process

#### Phase 1: Initial Setup

1. **Database Schema Creation**

    - Run `data_warehouse_setup.sql`
    - Creates all dimension and fact tables
    - Creates stored procedures for data population
    - Creates views for analytics

2. **Calendar Dimension Population**
    - `PopulateDimDate()` procedure runs
    - Generates dates from 2020-2030
    - Pre-calculates day_of_week, month, quarter, semester, etc.
    - One-time setup, rarely needs updating

#### Phase 2: Data Synchronization (ETL Process)

When `RefreshDataWarehouse()` is called:

**Step 1: Populate Dimensions** (Who, What, When)

```
PopulateDimStudent()
  ↓
  Reads: users WHERE role = 'student'
  Writes: Dim_student (student_key, student_id, student_name, student_email)

PopulateDimInstructor()
  ↓
  Reads: users WHERE role = 'instructor'
  Writes: Dim_Instructor (instructor_key, instructor_id, instructor_name, ...)

PopulateDimCourse()
  ↓
  Reads: courses table
  Joins: Dim_Instructor (to get instructor_key)
  Writes: Dim_Course (course_key, course_id, course_name, course_code, ...)

PopulateDimSession()
  ↓
  Reads: attendance_sessions table
  Joins: Dim_Course (to get course_key)
  Writes: Dim_Session (session_key, session_id, token, course_key, ...)
```

**Step 2: Populate Fact Tables** (What Happened)

```
PopulateFactEnrollment()
  ↓
  Reads: enrollments table
  Joins: Dim_student, Dim_Course, Dim_date
  Writes: fact_enrollment (student_key, course_key, enrollment_date_key, ...)

PopulateFactAttendance()
  ↓
  Reads: attendance_logs (QR scans)
  Joins: Dim_Session, Dim_student, Dim_Course, Dim_date, attendance_sessions
  Calculates: attendance_status based on time difference
    - IF scanned_at - session_created_at <= 15 minutes → 'Present'
    - IF scanned_at - session_created_at > 15 minutes → 'Late'
  Writes: fact_attendance (session_key, student_key, course_key,
                          attendance_status, minutes_late, ...)

PopulateAbsentRecords()
  ↓
  Logic: For each session, find enrolled students who didn't scan
  Reads: Dim_Session, fact_enrollment
  Checks: NOT EXISTS in fact_attendance for that session+student
  Writes: fact_attendance records with status = 'Absent'
```

#### Phase 3: Analytics Queries

**Example: Get Student Attendance Features**

```sql
SELECT
    student_name,
    course_code,
    day_of_week,
    COUNT(CASE WHEN attendance_status = 'Present' THEN 1 END) as total_attendance_count,
    COUNT(CASE WHEN attendance_status = 'Absent' THEN 1 END) as number_of_absences,
    COUNT(CASE WHEN attendance_status = 'Late' THEN 1 END) as number_of_late,
    attendance_rate
FROM vw_student_attendance_features
WHERE student_id = 123
GROUP BY student_name, course_code, day_of_week
```

This query is fast because:

-   Data is pre-joined in the view
-   Indexes on dimension keys
-   Aggregations are optimized

---

## Components Breakdown

### 1. Database Layer

#### Dimension Tables

**Dim_student**

-   Purpose: Student master data
-   Source: `users` table (role = 'student')
-   Key: `student_key` (surrogate key), `student_id` (business key)
-   Updates: When new students register or student info changes

**Dim_Course**

-   Purpose: Course master data
-   Source: `courses` table
-   Key: `course_key` (surrogate), `course_id` (business key)
-   Links: `instructor_key` → Dim_Instructor
-   Updates: When courses are created/modified

**Dim_date**

-   Purpose: Time dimension for all time-based analysis
-   Source: Generated (not from operational data)
-   Key: `date_key` (YYYYMMDD format, e.g., 20240115)
-   Attributes: day_of_week, month, quarter, semester, year, is_weekend
-   Updates: One-time population, rarely updated

**Dim_Session**

-   Purpose: Attendance session details
-   Source: `attendance_sessions` table
-   Key: `session_key` (surrogate), `session_id` (business key)
-   Links: `course_key` → Dim_Course
-   Updates: When new attendance sessions are created

#### Fact Tables

**fact_attendance**

-   Purpose: Store every attendance event (Present/Absent/Late)
-   Source: `attendance_logs` (scans) + generated Absent records
-   Measures: attendance_status, minutes_late, scanned_at
-   Dimensions: session_key, student_key, course_key, date_key
-   Unique Constraint: (session_key, student_key) - one record per student per session
-   Business Logic:
    -   Present: Scanned within 15 minutes of session creation
    -   Late: Scanned after 15 minutes
    -   Absent: Enrolled but didn't scan (auto-generated)

**fact_enrollment**

-   Purpose: Track which students are enrolled in which courses
-   Source: `enrollments` table
-   Measures: enrollment_timestamp
-   Dimensions: student_key, course_key, enrollment_date_key
-   Unique Constraint: (student_key, course_key) - one enrollment per student-course

### 2. Stored Procedures (ETL Logic)

**PopulateDimDate()**

-   Generates calendar dates from 2020-2030
-   Calculates all time attributes (day_of_week, month, quarter, etc.)
-   Uses INSERT ... ON DUPLICATE KEY UPDATE for idempotency

**PopulateDimStudent() / PopulateDimInstructor()**

-   Syncs user data from `users` table
-   Filters by role
-   Updates existing records or inserts new ones

**PopulateDimCourse()**

-   Syncs from `courses` table
-   Joins with Dim_Instructor to get instructor_key
-   Maintains referential integrity

**PopulateFactAttendance()**

-   Core logic for attendance status determination:
    ```sql
    CASE
        WHEN TIMESTAMPDIFF(MINUTE, session_created_at, scanned_at) > 15
        THEN 'Late'
        ELSE 'Present'
    END
    ```
-   Joins multiple tables to get all required keys
-   Calculates minutes_late

**PopulateAbsentRecords()**

-   Finds enrolled students who didn't scan
-   Logic: For each session, check enrolled students, if no attendance record exists, create Absent record
-   Ensures complete data for analytics (no missing records)

**RefreshDataWarehouse()** (Master Procedure)

-   Orchestrates all population procedures
-   Order matters: Dimensions first, then Facts
-   Returns success message

### 3. Views (Pre-aggregated Analytics)

**vw_student_attendance_features**

-   Pre-joins: Dim_student, Dim_Course, Dim_date, fact_attendance
-   Pre-calculates: total_attendance_count, number_of_absences, attendance_rate
-   Groups by: student, course, day_of_week
-   Purpose: Fast access to student-level analytics

**vw_attendance_by_day**

-   Groups attendance by day of week and course
-   Calculates: present_count, absent_count, late_count, attendance_rate
-   Purpose: Identify patterns (e.g., Monday vs Friday attendance)

### 4. Backend Service Layer

**DataWarehouseService.php**

-   PHP service class that wraps database operations
-   Methods:
    -   `refreshDataWarehouse()` - Calls stored procedure
    -   `getStudentAttendanceFeatures()` - Queries view
    -   `getAttendanceByDay()` - Queries view
    -   `getAttendanceSummary()` - Aggregates fact table
    -   `getDataMiningDataset()` - Returns features for ML

### 5. API Gateway

**gateway/index.php**

-   Routes analytics requests:
    -   `POST /api/analytics/refresh` - Refresh data warehouse
    -   `GET /api/analytics/features` - Get student features
    -   `GET /api/analytics/by-day` - Get attendance by day
    -   `GET /api/analytics/summary` - Get summary statistics
-   Role-based access control (admin, instructor only)

### 6. Frontend UI

**Analytics.tsx**

-   Three main tabs:
    1. **Student Features**: Shows detailed attendance features per student/course/day
    2. **By Day of Week**: Shows attendance patterns by day
    3. **Summary**: Course-level aggregated statistics
-   Refresh button to sync data warehouse
-   Real-time data display from API

---

## Analytics Achievement

### How Analytics is Achieved

#### 1. **Data Transformation**

**From Operational to Analytical:**

-   **Operational**: Normalized, transactional, optimized for CRUD operations
-   **Analytical**: Denormalized, aggregated, optimized for read-heavy queries

**Example Transformation:**

```
Operational Query (Slow):
SELECT
    u.name, c.code,
    COUNT(CASE WHEN al.id IS NOT NULL THEN 1 END) as present_count
FROM users u
JOIN enrollments e ON u.id = e.student_id
JOIN courses c ON e.course_id = c.id
LEFT JOIN attendance_sessions ass ON c.id = ass.course_id
LEFT JOIN attendance_logs al ON ass.id = al.session_id AND u.id = al.student_id
WHERE u.role = 'student'
GROUP BY u.id, c.id
-- Multiple joins, complex aggregations, slow performance

Analytical Query (Fast):
SELECT
    ds.student_name, dc.course_code,
    COUNT(CASE WHEN fa.attendance_status = 'Present' THEN 1 END) as present_count
FROM fact_attendance fa
JOIN Dim_student ds ON fa.student_key = ds.student_key
JOIN Dim_Course dc ON fa.course_key = dc.course_key
GROUP BY ds.student_key, dc.course_key
-- Pre-joined, indexed, fast performance
```

#### 2. **Pre-aggregation Strategy**

Views pre-calculate common aggregations:

-   Total attendance count per student/course
-   Number of absences
-   Attendance rates
-   Day-of-week patterns

This means:

-   **No real-time calculation**: Values are pre-computed
-   **Fast queries**: Just SELECT from view, no complex GROUP BY
-   **Consistent results**: Same calculation logic everywhere

#### 3. **Complete Data Coverage**

**Absent Record Generation:**

-   Operational system only records when students **scan** (Present/Late)
-   Missing scans = no records = incomplete analytics
-   Solution: `PopulateAbsentRecords()` generates Absent records for enrolled students who didn't scan
-   Result: Complete dataset with Present, Absent, and Late for every session

**Example:**

```
Session 1 created for Course A
- Student 1 scanned → fact_attendance: Present
- Student 2 scanned (late) → fact_attendance: Late
- Student 3 didn't scan → fact_attendance: Absent (auto-generated)
```

#### 4. **Time-Based Analysis**

**Dim_date Dimension:**

-   Pre-calculated time attributes (day_of_week, month, quarter, semester)
-   Enables fast time-based filtering and grouping
-   No need to calculate DAYOFWEEK(), MONTH(), etc. in every query

**Example Queries Enabled:**

-   "Show attendance by day of week"
-   "Compare Q1 vs Q2 attendance"
-   "Show semester trends"
-   "Filter by specific month"

#### 5. **Performance Optimization**

**Indexes:**

-   All foreign keys indexed
-   Composite indexes on common query patterns
-   Example: `idx_student_course_date` on (student_key, course_key, date_key)

**Denormalization:**

-   Store `day_of_week` in fact_attendance (derived from date_key)
-   Avoids joining Dim_date for simple day-of-week queries
-   Trade-off: Slight storage increase for significant query speed

**Surrogate Keys:**

-   Use integer keys (student_key) instead of business keys (student_id)
-   Smaller indexes, faster joins
-   Business keys still available for reference

---

## Data Mining Features

### Target Variable

**attendance_status**

-   Values: `Present`, `Absent`, `Late`
-   This is what we want to predict
-   Stored in `fact_attendance.attendance_status`

### Feature Set

The system provides these features for machine learning:

1. **total_attendance_count**

    - Number of times student was Present
    - Calculated: COUNT WHERE attendance_status = 'Present'
    - Purpose: Historical attendance pattern

2. **number_of_absences**

    - Number of times student was Absent
    - Calculated: COUNT WHERE attendance_status = 'Absent'
    - Purpose: Absenteeism pattern

3. **number_of_late**

    - Number of times student was Late
    - Calculated: COUNT WHERE attendance_status = 'Late'
    - Purpose: Punctuality pattern

4. **class / subject**

    - Course code and course name
    - Source: Dim_Course
    - Purpose: Course-specific patterns

5. **day_of_week**

    - Day of week (1=Monday, 7=Sunday)
    - Source: Dim_date or fact_attendance
    - Purpose: Day-specific patterns (e.g., Monday absences)

6. **attendance_rate**

    - Percentage: (Present count / Total sessions) \* 100
    - Calculated in views
    - Purpose: Overall attendance performance

7. **minutes_late**
    - How many minutes late (if Late)
    - Source: fact_attendance
    - Purpose: Severity of lateness

### Data Mining Dataset Structure

```sql
SELECT
    student_id,
    student_name,
    class,                    -- course_code
    subject,                   -- course_name
    day_of_week,
    day_name,
    total_attendance_count,   -- Feature
    number_of_absences,       -- Feature
    number_of_late,           -- Feature
    minutes_late,             -- Feature
    attendance_status         -- TARGET VARIABLE
FROM fact_attendance fa
JOIN Dim_student ds ON fa.student_key = ds.student_key
JOIN Dim_Course dc ON fa.course_key = dc.course_key
JOIN Dim_date dd ON fa.date_key = dd.date_key
```

This structure is ready for:

-   **Classification Models**: Predict Present/Absent/Late
-   **Regression Models**: Predict attendance_rate
-   **Pattern Recognition**: Identify at-risk students
-   **Clustering**: Group students by attendance patterns

---

## Summary

### Key Achievements

1. **Fast Analytics**: Star schema enables sub-second query performance
2. **Complete Data**: Auto-generated Absent records ensure no missing data
3. **Flexible Reporting**: Multiple views support various analytical needs
4. **Data Mining Ready**: Structured features and target variable for ML
5. **Maintainable**: Clear separation of concerns (dimensions vs. facts)
6. **Scalable**: Can handle large volumes of attendance data

### Workflow Summary

```
1. Operational System Records Events
   ↓
2. User Clicks "Refresh Data" in Analytics UI
   ↓
3. API Calls DataWarehouseService::refreshDataWarehouse()
   ↓
4. Stored Procedure RefreshDataWarehouse() Executes
   ↓
5. ETL Process:
   - Populate Dimensions (Who, What, When)
   - Populate Facts (What Happened)
   - Generate Absent Records
   ↓
6. Views Pre-aggregate Data
   ↓
7. Analytics Queries Use Views/Facts
   ↓
8. Results Displayed in Frontend UI
```

### Best Practices

-   **Refresh Frequency**: After each attendance session or daily batch
-   **Data Integrity**: Foreign key constraints ensure referential integrity
-   **Performance**: Indexes on all join keys and common filter columns
-   **Completeness**: Absent record generation ensures complete analytics
-   **Consistency**: Single source of truth for calculations (stored procedures)

---

## Technical Stack

-   **Database**: MySQL/MariaDB
-   **Backend**: PHP (DataWarehouseService)
-   **Frontend**: React/TypeScript (Analytics.tsx)
-   **API**: RESTful API via Gateway
-   **Architecture**: Star Schema Data Warehouse
-   **ETL**: Stored Procedures
-   **Analytics**: SQL Views + Aggregations

---

_This document explains the complete analytics and data mining system implementation in SAMS. For specific implementation details, refer to the SQL scripts and service classes._
