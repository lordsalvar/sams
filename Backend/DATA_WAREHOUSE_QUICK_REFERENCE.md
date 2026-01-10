# Data Warehouse Quick Reference

## Quick Setup

```sql
-- Run after database_setup.sql
source Backend/data_warehouse_setup.sql;
```

## Refresh Data Warehouse

```sql
CALL RefreshDataWarehouse();
```

## Tables Overview

### Dimensions
- `Dim_student` - Student profiles
- `Dim_Instructor` - Instructor details  
- `Dim_Course` - Course information
- `Dim_Session` - Attendance sessions
- `Dim_date` - Calendar (2020-2030)

### Facts
- `fact_enrollment` - Student-course enrollments
- `fact_attendance` - Attendance records with status

## Data Mining Features

**Target Variable:** `attendance_status` (Present/Absent/Late)

**Features:**
- `total_attendance_count` ✅
- `number_of_absences` ✅
- `class` / `subject` ✅
- `day_of_week` ✅

## Quick Queries

### Get Student Features
```sql
SELECT * FROM vw_student_attendance_features 
WHERE student_id = 1;
```

### Get Data Mining Dataset
```sql
SELECT 
    student_key,
    course_code as class,
    day_of_week,
    total_attendance_count,
    number_of_absences,
    attendance_status as target_variable
FROM vw_student_attendance_features;
```

### Attendance by Day
```sql
SELECT * FROM vw_attendance_by_day;
```

## PHP Usage

```php
// Refresh warehouse
DataWarehouseService::refreshDataWarehouse();

// Get features
DataWarehouseService::getStudentAttendanceFeatures($studentId);

// Get mining dataset
DataWarehouseService::getDataMiningDataset();
```

## Attendance Status Rules

- **Present**: Scanned within 15 min of session start
- **Late**: Scanned after 15 min
- **Absent**: Enrolled but didn't scan

---

See `DATA_WAREHOUSE_README.md` for full documentation.

