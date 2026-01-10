# Postman Quick Start Guide

## 🚀 Quick Setup (5 minutes)

### Step 1: Import Collection
1. Open Postman
2. Click **Import** button
3. Select `SAMS_API.postman_collection.json` file
4. Collection will be imported with all endpoints

### Step 2: Set Up Environment Variables
1. Click **Environments** in left sidebar
2. Click **+** to create new environment
3. Name it: `SAMS Local`
4. Add these variables:

| Variable | Initial Value |
|----------|---------------|
| `base_url` | `http://localhost/sams/Backend` |
| `admin_email` | `admin@sams.com` |
| `admin_password` | `your_actual_password` |
| `instructor_email` | `instructor1@sams.com` |
| `student_email` | `student1@sams.com` |
| `course_id` | `1` |
| `session_token` | (leave empty) |

5. Click **Save**
6. Select `SAMS Local` from environment dropdown (top right)

### Step 3: Verify Backend is Running
1. Make sure XAMPP Apache and MySQL are running
2. Test connection: Run **Test API Connection** request
3. Should return: `"success": true`

---

## 📋 Testing Flow (Recommended Order)

### 1️⃣ Test Connection
- **Request:** `Test → Test API Connection`
- **Expected:** Success message

### 2️⃣ Login
- **Request:** `Authentication → Login`
- **Update:** Change email/password in body if needed
- **Expected:** Token in response (save it if needed)

### 3️⃣ Create Users
- **Request:** `Users → Create User`
- **Body:** Change role to `instructor` or `student` as needed
- **Repeat:** Create at least 1 instructor and 1 student

### 4️⃣ Create Course
- **Request:** `Courses → Create Course`
- **Update:** Set `instructor_email` to match created instructor
- **Note:** Save the `course_id` from response

### 5️⃣ Enroll Student
- **Request:** `Enrollments → Enroll Student`
- **Update:** Set `course_id` and `student_email` in body
- **Expected:** Success message

### 6️⃣ Create Attendance Session
- **Request:** `Attendance Sessions → Create Attendance Session`
- **Update:** Set `course_id` and `instructor_email` in body
- **Note:** Save the `token` from response (copy to `session_token` variable)

### 7️⃣ Scan QR Code
- **Request:** `Attendance Scanning → Scan QR Code`
- **Update:** Set `token` and `student_email` in body
- **Expected:** Attendance recorded message

### 8️⃣ View Attendance Logs
- **Request:** `Attendance Logs → Get Attendance Logs with Roster`
- **Update:** Set `session_id` in query params
- **Expected:** List of students with present/absent status

### 9️⃣ View Analytics
- **Request:** `Analytics → Get Course Analytics`
- **Update:** Set `course_id` in query params
- **Expected:** Summary stats and student attendance

---

## 🔧 Common Issues

### ❌ 404 Not Found
**Problem:** Endpoint not found  
**Solution:**
- Check base URL: `http://localhost/sams/Backend`
- Verify Apache mod_rewrite is enabled
- Check `.htaccess` file exists

### ❌ 403 Forbidden - Role Required
**Problem:** Missing `requested_by_role` parameter  
**Solution:**
- Add `requested_by_role` to query string or body
- Valid values: `admin`, `instructor`, `student`

### ❌ 500 Internal Server Error
**Problem:** Database or server error  
**Solution:**
- Check MySQL is running
- Verify database `sams_db` exists
- Check `Backend/php_errors.log` for details

### ❌ Database Connection Failed
**Problem:** Can't connect to database  
**Solution:**
- Open `Backend/api/config.php`
- Verify credentials: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=`, `DB_NAME=sams_db`
- Make sure MySQL is running in XAMPP

---

## 💡 Pro Tips

1. **Use Variables:** Update environment variables instead of editing each request
2. **Save Responses:** Use Tests tab to save IDs/tokens automatically
3. **Organize:** Create folders in Postman for different test scenarios
4. **Export:** Export collection after customizing for team sharing

---

## 📚 Full Documentation

For complete API documentation with all endpoints, request/response examples, and detailed explanations, see:
- **`POSTMAN_API_GUIDE.md`** - Complete API reference

---

## 🎯 Quick Test Checklist

- [ ] Backend accessible at `http://localhost/sams/Backend`
- [ ] Test endpoint returns success
- [ ] Can login successfully
- [ ] Can create users (admin, instructor, student)
- [ ] Can create course
- [ ] Can enroll student
- [ ] Can create attendance session
- [ ] Can scan QR code (record attendance)
- [ ] Can view attendance logs
- [ ] Can view analytics

---

**Need Help?** Check `POSTMAN_API_GUIDE.md` for detailed endpoint documentation.

