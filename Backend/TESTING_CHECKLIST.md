# SAMS Testing Checklist

Complete end-to-end testing guide from user creation to attendance recording.

## Prerequisites
- [ ] XAMPP/Apache is running
- [ ] MySQL is running
- [ ] Database `sams_db` is created and tables are set up
- [ ] Frontend dev server is running (`npm run dev`)
- [ ] Backend is accessible at `http://localhost/sams/Backend`

---

## Phase 1: User Management

### 1.1 Admin User Creation (Initial Setup)
- [ ] Navigate to `http://localhost:3000/login`
- [ ] If no admin exists, create one directly in database:
  ```sql
  INSERT INTO users (name, email, password, role) 
  VALUES ('Admin User', 'admin@sams.com', '$2y$10$...', 'admin');
  ```
- [ ] Or use the Users page (if accessible) to create admin

### 1.2 Login as Admin
- [ ] Go to login page
- [ ] Enter admin credentials
- [ ] Click "Login"
- [ ] Verify redirect to `/dashboard/admin`
- [ ] Verify admin name and role displayed in sidebar
- [ ] Verify sidebar shows: Dashboard, Users, Courses, Sessions

### 1.3 Create Instructor User
- [ ] Navigate to `/dashboard/users`
- [ ] Click "Add User" button
- [ ] Fill in form:
  - Name: `Instructor One`
  - Email: `instructor1@sams.com`asd
  - Password: `password123`
  - Role: `Instructor`
- [ ] Click "Save"
- [ ] Verify success message
- [ ] Verify instructor appears in users list
- [ ] Verify instructor role is displayed correctly

### 1.4 Create Student Users
- [ ] Click "Add User" again
- [ ] Create Student 1:
  - Name: `Student One`
  - Email: `student1@sams.com`
  - Password: `password123`
  - Role: `Student`
- [ ] Click "Save"
- [ ] Repeat for Student 2:
  - Name: `Student Two`
  - Email: `student2@sams.com`
  - Password: `password123`
  - Role: `Student`
- [ ] Verify both students appear in users list

### 1.5 Edit User
- [ ] Click edit icon on any user
- [ ] Modify name or email
- [ ] Click "Save"
- [ ] Verify changes are reflected in the list

### 1.6 Delete User (Optional)
- [ ] Click delete icon on a test user
- [ ] Confirm deletion
- [ ] Verify user is removed from list

---

## Phase 2: Course Management

### 2.1 Login as Instructor
- [ ] Logout from admin account
- [ ] Login as `instructor1@sams.com` / `password123`
- [ ] Verify redirect to `/dashboard/instructor`
- [ ] Verify sidebar shows: Dashboard, Courses, Sessions

### 2.2 Create Course (as Instructor)
- [ ] Navigate to `/dashboard/courses`
- [ ] Click "Add Course" button
- [ ] Fill in form:
  - Name: `Introduction to Programming`
  - Code: `CS101`
  - Instructor: `instructor1@sams.com` (should be pre-selected)
- [ ] Click "Save"
- [ ] Verify success message
- [ ] Verify course appears in courses list
- [ ] Verify course shows enrollment count (0)

### 2.3 Create Another Course
- [ ] Create second course:
  - Name: `Database Systems`
  - Code: `CS201`
  - Instructor: `instructor1@sams.com`
- [ ] Verify both courses are listed

### 2.4 View Course Details
- [ ] Click on a course card or "View Details"
- [ ] Verify course information is displayed
- [ ] Verify enrolled students section (should be empty initially)
- [ ] Verify attendance sessions section

### 2.5 Edit Course
- [ ] From course details page, click "Edit"
- [ ] Modify course name or code
- [ ] Click "Save"
- [ ] Verify changes are reflected

### 2.6 Login as Admin - Create Course
- [ ] Logout and login as admin
- [ ] Navigate to `/dashboard/courses`
- [ ] Click "Add Course"
- [ ] Verify instructor dropdown shows all instructors
- [ ] Create course assigned to `instructor1@sams.com`
- [ ] Verify course appears in list

---

## Phase 3: Student Enrollment

### 3.1 Enroll Students (as Admin)
- [ ] Navigate to `/dashboard/courses`
- [ ] Click on a course
- [ ] Click "Enroll Student" button
- [ ] Verify student dropdown/combobox appears
- [ ] Select `Student One` from dropdown
- [ ] Click "Enroll"
- [ ] Verify success message
- [ ] Verify student appears in enrolled students list
- [ ] Verify enrollment count increases

### 3.2 Enroll Multiple Students
- [ ] Enroll `Student Two` in the same course
- [ ] Verify both students appear in enrolled list
- [ ] Verify enrollment count shows 2

### 3.3 Enroll in Different Course
- [ ] Navigate to another course
- [ ] Enroll `Student One` in this course
- [ ] Verify student is enrolled in multiple courses

### 3.4 Unenroll Student
- [ ] From course details, find enrolled student
- [ ] Click "Unenroll" or delete icon
- [ ] Confirm unenrollment
- [ ] Verify student is removed from enrolled list
- [ ] Verify enrollment count decreases

---

## Phase 4: Attendance Session Creation

### 4.1 Create Attendance Session (as Instructor)
- [ ] Login as instructor
- [ ] Navigate to `/dashboard/courses`
- [ ] Click on a course with enrolled students
- [ ] Navigate to "Attendance Display" or "Generate QR"
- [ ] Click "Create Session" or "Generate QR Code"
- [ ] Verify session is created
- [ ] Verify QR code is displayed
- [ ] Verify session token is generated
- [ ] Verify expiration time (should be 15 minutes from now)

### 4.2 View Active Session
- [ ] Verify session status shows "Active"
- [ ] Verify session shows:
  - Course name
  - Created time
  - Expiration time
  - Created by email
- [ ] Verify attendance link is displayed

### 4.3 View All Sessions
- [ ] Navigate to `/dashboard/sessions` (from sidebar)
- [ ] Verify all sessions from all courses are listed
- [ ] Verify each session shows:
  - Course name
  - Created date
  - Expiration date
  - Status (Active/Expired)
  - Scanned count
- [ ] Click expand icon on a session
- [ ] Verify attendance records are loaded (may be empty initially)

---

## Phase 5: QR Code Scanning (Student Attendance)

### 5.1 Access QR Scan Page
- [ ] Copy the attendance link from the session
- [ ] Open in new browser/incognito window (to simulate student device)
- [ ] Or navigate to `/attendance-scan?token=TOKEN_HERE`
- [ ] Verify scan page loads

### 5.2 Student Login for Scanning
- [ ] Enter student email: `student1@sams.com`
- [ ] Enter password: `password123`
- [ ] Click "Login" or "Scan QR"
- [ ] Verify student is authenticated

### 5.3 Scan QR Code
- [ ] If QR code is displayed, scan it with camera
- [ ] Or manually enter token if needed
- [ ] Click "Record Attendance" or similar button
- [ ] Verify success message: "Attendance recorded"
- [ ] Verify course name is displayed

### 5.4 Scan with Second Student
- [ ] Logout or use different browser
- [ ] Login as `student2@sams.com`
- [ ] Access the same attendance link
- [ ] Scan QR code
- [ ] Verify attendance is recorded

### 5.5 Test Expired Session
- [ ] Wait for session to expire (or manually expire in database)
- [ ] Try to scan with a student
- [ ] Verify error message: "Attendance session has expired"
- [ ] Verify attendance is NOT recorded

### 5.6 Test Unenrolled Student
- [ ] Create a new student (not enrolled in course)
- [ ] Try to scan QR code for that course
- [ ] Verify error: "You are not enrolled in this course"

---

## Phase 6: View Attendance Records

### 6.1 View Session Roster (as Instructor)
- [ ] Login as instructor
- [ ] Navigate to course details
- [ ] Go to "Attendance Sessions" tab
- [ ] Click on a session
- [ ] Verify roster is displayed showing:
  - All enrolled students
  - Present/Absent status
  - Scanned timestamp for present students
- [ ] Verify count: "X/Y present" (where X = scanned, Y = enrolled)

### 6.2 View All Sessions Page
- [ ] Navigate to `/dashboard/sessions`
- [ ] Click expand icon on a session
- [ ] Verify attendance records are displayed:
  - Student name
  - Student email
  - Scanned at timestamp
  - Status (Present)
- [ ] Verify only students who scanned are shown in records

### 6.3 View Attendance Logs
- [ ] From session details, verify attendance logs section
- [ ] Verify logs show:
  - Student name
  - Student email
  - Scanned timestamp
- [ ] Verify logs are sorted by student name

---

## Phase 7: Attendance Analytics

### 7.1 View Course Analytics
- [ ] Navigate to course details
- [ ] Go to "Analytics" or "Attendance Analytics" tab
- [ ] Verify analytics show:
  - Total sessions count
  - Active sessions count
  - Enrolled students count
  - Last session date

### 7.2 View Student Attendance Stats
- [ ] Scroll to student attendance section
- [ ] Verify each enrolled student shows:
  - Total sessions
  - Attended sessions
  - Attendance percentage (if calculated)
- [ ] Verify students are sorted by name

### 7.3 View Session Breakdown
- [ ] Verify sessions breakdown shows:
  - Session ID
  - Created date
  - Expiration date
  - Status (Active/Expired)
  - Scanned count
  - Enrolled count

---

## Phase 8: Cross-Role Testing

### 8.1 Admin Access
- [ ] Login as admin
- [ ] Verify can:
  - [ ] View all courses (from all instructors)
  - [ ] Create courses
  - [ ] Assign courses to any instructor
  - [ ] View all sessions (from all courses)
  - [ ] Enroll/unenroll students
  - [ ] Manage users

### 8.2 Instructor Access
- [ ] Login as instructor
- [ ] Verify can:
  - [ ] View only their own courses
  - [ ] Create courses (assigned to themselves)
  - [ ] View sessions for their courses
  - [ ] Create attendance sessions
  - [ ] View attendance records
  - [ ] Enroll students in their courses
- [ ] Verify CANNOT:
  - [ ] View other instructors' courses
  - [ ] Manage users
  - [ ] Assign courses to other instructors

### 8.3 Student Access
- [ ] Login as student
- [ ] Verify can:
  - [ ] Access attendance scan page
  - [ ] Scan QR codes for enrolled courses
- [ ] Verify CANNOT:
  - [ ] Access dashboard (should redirect)
  - [ ] View courses list
  - [ ] View attendance records
  - [ ] Create sessions

---

## Phase 9: Edge Cases & Error Handling

### 9.1 Invalid Token
- [ ] Try to scan with invalid/random token
- [ ] Verify error: "Invalid or unknown attendance token"

### 9.2 Duplicate Scanning
- [ ] Scan QR code twice with same student
- [ ] Verify second scan doesn't create duplicate record
- [ ] Verify only one attendance log exists

### 9.3 Multiple Sessions
- [ ] Create multiple sessions for same course
- [ ] Verify all sessions are listed
- [ ] Verify each session has independent attendance records

### 9.4 Empty Course
- [ ] Create course with no enrolled students
- [ ] Create attendance session
- [ ] Verify session shows "0/0 scanned"
- [ ] Verify roster is empty

### 9.5 Network Errors
- [ ] Test with network disconnected
- [ ] Verify appropriate error messages
- [ ] Verify UI handles errors gracefully

---

## Phase 10: UI/UX Testing

### 10.1 Navigation
- [ ] Verify all sidebar links work
- [ ] Verify active page is highlighted
- [ ] Verify breadcrumbs (if present)
- [ ] Verify back buttons work

### 10.2 Responsive Design
- [ ] Test on mobile viewport
- [ ] Test on tablet viewport
- [ ] Verify tables are scrollable
- [ ] Verify buttons are accessible

### 10.3 Loading States
- [ ] Verify loading spinners appear during API calls
- [ ] Verify buttons are disabled during operations
- [ ] Verify no duplicate requests are sent

### 10.4 Form Validation
- [ ] Try submitting empty forms
- [ ] Try invalid email formats
- [ ] Try invalid data types
- [ ] Verify validation error messages

---

## Phase 11: Data Integrity

### 11.1 Database Verification
- [ ] Check `users` table - verify all created users exist
- [ ] Check `courses` table - verify all courses exist
- [ ] Check `enrollments` table - verify enrollments are correct
- [ ] Check `attendance_sessions` table - verify sessions are created
- [ ] Check `attendance_logs` table - verify scans are recorded

### 11.2 Data Consistency
- [ ] Verify enrollment counts match actual enrollments
- [ ] Verify scanned counts match actual logs
- [ ] Verify session expiration times are correct
- [ ] Verify timestamps are in correct timezone (GMT+8)

---

## Phase 12: Performance Testing

### 12.1 Large Data Sets
- [ ] Create 10+ courses
- [ ] Enroll 20+ students
- [ ] Create 10+ sessions
- [ ] Verify pages load reasonably fast
- [ ] Verify tables paginate or scroll smoothly

### 12.2 Concurrent Operations
- [ ] Have multiple students scan simultaneously
- [ ] Verify all scans are recorded
- [ ] Verify no data corruption

---

## Test Results Summary

### Test Date: _______________
### Tester: _______________

### Summary:
- Total Tests: ___
- Passed: ___
- Failed: ___
- Skipped: ___

### Critical Issues Found:
1. ________________________________
2. ________________________________
3. ________________________________

### Notes:
________________________________
________________________________
________________________________

---

## Quick Test Script (Minimal Flow)

For quick verification, test this minimal flow:

1. [ ] Login as admin
2. [ ] Create instructor user
3. [ ] Create student user
4. [ ] Create course
5. [ ] Enroll student in course
6. [ ] Login as instructor
7. [ ] Create attendance session
8. [ ] Login as student
9. [ ] Scan QR code
10. [ ] Login as instructor
11. [ ] Verify attendance is recorded

If all these pass, core functionality is working! ✅

