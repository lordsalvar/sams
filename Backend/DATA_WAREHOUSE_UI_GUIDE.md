# Data Warehouse UI Implementation Guide

## Overview

A complete UI has been added for the Data Warehouse analytics and data mining features. The page provides interactive views for exploring attendance data, generating reports, and exporting datasets for machine learning.

## Access

**URL:** `/dashboard/data-warehouse`

**Access Control:** Admin and Instructor roles only

**Navigation:** Available in the sidebar menu (Database icon)

## Features

### 1. Student Attendance Features Tab
- Displays detailed attendance features for data mining
- Shows: total attendance count, number of absences, number of late, class/subject, day of week
- Includes attendance rate with color-coded badges
- Filterable and sortable table view

### 2. Attendance by Day of Week Tab
- Shows attendance statistics grouped by day of week
- Displays present/absent/late counts per day
- Useful for identifying patterns (e.g., Monday attendance vs Friday)

### 3. Summary Tab
- Course-level attendance summaries
- Card-based layout showing:
  - Present count (green)
  - Absent count (red)
  - Late count (yellow)
  - Overall attendance rate
  - Total records and unique students

### 4. Data Mining Tab
- Complete dataset for machine learning
- Shows all features and target variable
- Export to CSV functionality
- Displays first 100 records (full dataset available via CSV export)

## API Endpoints

The following endpoints are available through the gateway:

### Refresh Data Warehouse
```
POST /api/analytics/refresh
Body: { requested_by_role: "admin" | "instructor" }
```

### Get Student Attendance Features
```
GET /api/analytics/features?requested_by_role=admin&student_id=1&course_id=2
```

### Get Attendance by Day
```
GET /api/analytics/by-day?requested_by_role=admin&course_code=CS101
```

### Get Attendance Summary
```
GET /api/analytics/summary?requested_by_role=admin&course_id=1&start_date=2024-01-01&end_date=2024-12-31
```

### Get Data Mining Dataset
```
GET /api/analytics/mining?requested_by_role=admin&student_id=1&course_id=2
```

## Usage

### Initial Setup

1. **Run Database Setup:**
   ```sql
   source Backend/database_setup.sql;
   source Backend/data_warehouse_setup.sql;
   ```

2. **Access the UI:**
   - Login as admin or instructor
   - Navigate to "Data Warehouse" in the sidebar
   - Click "Refresh Data" to populate the warehouse

### Refreshing Data

- Click the "Refresh Data" button to sync the data warehouse with source tables
- This runs the `RefreshDataWarehouse()` stored procedure
- Recommended: Refresh after creating new attendance sessions or enrolling students

### Exporting Data

- Navigate to the "Data Mining" tab
- Click "Export CSV" to download the complete dataset
- The CSV includes all features and the target variable for machine learning

## Data Mining Dataset Structure

The exported CSV includes:

**Target Variable:**
- `target_variable` (attendance_status): Present, Absent, or Late

**Features:**
- `total_attendance_count`: Total number of present records
- `number_of_absences`: Total number of absent records
- `number_of_late`: Total number of late records
- `class`: Course code
- `subject`: Course name
- `day_of_week`: Day of week (1-7)
- `day_name`: Day name (Monday-Sunday)
- `minutes_late`: Minutes late (if applicable)
- `student_id`, `student_name`: Student identifiers

## UI Components

### Cards
- Summary cards with color-coded statistics
- Course-level summaries with visual indicators

### Tables
- Sortable and scrollable data tables
- Color-coded badges for attendance rates
- Responsive design for mobile/desktop

### Tabs
- Four main tabs for different views
- Easy navigation between analytics views

### Actions
- Refresh button to sync data warehouse
- Export CSV for data mining dataset
- Loading states and error handling

## Integration Points

### Frontend
- **Page:** `Frontend/src/pages/DataWarehouse.tsx`
- **Route:** Added to `Frontend/src/App.tsx`
- **Navigation:** Added to `Frontend/src/components/AppSidebar.tsx`

### Backend
- **Service:** `Backend/microservices/analytics/DataWarehouseService.php`
- **Gateway:** Routes added to `Backend/gateway/index.php`
- **Database:** Uses stored procedures from `Backend/data_warehouse_setup.sql`

## Troubleshooting

### No Data Showing
1. Click "Refresh Data" to populate the warehouse
2. Ensure the database setup scripts have been run
3. Check that there are attendance records in the source tables

### Refresh Fails
1. Verify database connection
2. Check that stored procedures exist (`RefreshDataWarehouse()`)
3. Review browser console and network tab for errors

### Export Not Working
1. Ensure there is data in the mining dataset
2. Check browser download permissions
3. Try a different browser if issues persist

## Next Steps

1. **Run Setup:** Execute the database setup scripts
2. **Test Access:** Login and navigate to Data Warehouse
3. **Refresh Data:** Click refresh to populate initial data
4. **Explore:** Use the tabs to view different analytics
5. **Export:** Download CSV for data mining projects

---

**Status:** ✅ Complete and Ready for Use

