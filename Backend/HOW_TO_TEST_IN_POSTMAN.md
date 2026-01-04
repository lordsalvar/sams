# How to Test SAMS API in Postman - Step by Step

## Prerequisites Checklist
- [ ] XAMPP is installed and running
- [ ] Apache is running (green in XAMPP Control Panel)
- [ ] MySQL is running (green in XAMPP Control Panel)
- [ ] Database `sams_db` is created
- [ ] Postman is installed

---

## Method 1: Using the Collection (Easiest)

### Step 1: Import the Collection
1. Open Postman
2. Click **Import** button (top left)
3. Click **Upload Files**
4. Navigate to: `C:\xampp\htdocs\sams\Backend\SAMS_API.postman_collection.json`
5. Click **Import**
6. You should see "SAMS API Collection" in your collections

### Step 2: Create Environment (Optional but Recommended)
1. Click **Environments** in left sidebar (or press `Ctrl+E`)
2. Click **+** button to create new environment
3. Name it: `SAMS Local`
4. Add these variables one by one:

   | Variable Name | Initial Value | Current Value |
   |--------------|---------------|---------------|
   | `base_url` | `http://localhost/sams/Backend` | `http://localhost/sams/Backend` |
   | `admin_email` | `admin@sams.com` | `admin@sams.com` |
   | `admin_password` | `password123` | `password123` |
   | `instructor_email` | `instructor1@sams.com` | `instructor1@sams.com` |
   | `student_email` | `student1@sams.com` | `student1@sams.com` |

5. Click **Save**
6. Select `SAMS Local` from the environment dropdown (top right corner)

### Step 3: Test Your First Request
1. Expand **SAMS API Collection** → **Test**
2. Click on **Test API Connection**
3. Click **Send** button
4. You should see response like:
   ```json
   {
     "success": true,
     "message": "PHP REST API is working!",
     "timestamp": "2024-01-01 10:00:00"
   }
   ```

✅ **If you see this, your API is working!**

---

## Method 2: Manual Testing (Without Collection)

### Test 1: Check API is Working

1. **Create New Request:**
   - Click **New** → **HTTP Request**
   - Name it: `Test API`

2. **Set Method and URL:**
   - Method: **GET**
   - URL: `http://localhost/sams/Backend/api/test`

3. **Send Request:**
   - Click **Send**
   - Should return: `{"success": true, ...}`

---

### Test 2: Login

1. **Create New Request:**
   - Click **New** → **HTTP Request**
   - Name it: `Login`

2. **Set Method and URL:**
   - Method: **POST**
   - URL: `http://localhost/sams/Backend/api/auth/login`

3. **Set Headers:**
   - Click **Headers** tab
   - Add: `Content-Type` = `application/json`

4. **Set Body:**
   - Click **Body** tab
   - Select **raw**
   - Select **JSON** from dropdown
   - Paste this:
   ```json
   {
     "email": "admin@sams.com",
     "password": "your_password_here"
   }
   ```
   - Replace `your_password_here` with actual password

5. **Send Request:**
   - Click **Send**
   - Should return user data and token

---

### Test 3: Get All Users (as Admin)

1. **Create New Request:**
   - Name it: `Get All Users`

2. **Set Method and URL:**
   - Method: **GET**
   - URL: `http://localhost/sams/Backend/api/users?requested_by_role=admin`

3. **Send Request:**
   - Click **Send**
   - Should return list of users

---

### Test 4: Create User

1. **Create New Request:**
   - Name it: `Create User`

2. **Set Method and URL:**
   - Method: **POST**
   - URL: `http://localhost/sams/Backend/api/users`

3. **Set Headers:**
   - `Content-Type` = `application/json`

4. **Set Body (raw JSON):**
   ```json
   {
     "requested_by_role": "admin",
     "name": "Test Instructor",
     "email": "testinstructor@sams.com",
     "password": "password123",
     "role": "instructor"
   }
   ```

5. **Send Request:**
   - Click **Send**
   - Should return created user data

---

### Test 5: Create Course

1. **Create New Request:**
   - Name it: `Create Course`

2. **Set Method and URL:**
   - Method: **POST**
   - URL: `http://localhost/sams/Backend/api/courses`

3. **Set Headers:**
   - `Content-Type` = `application/json`

4. **Set Body (raw JSON):**
   ```json
   {
     "requested_by_role": "admin",
     "name": "Introduction to Programming",
     "code": "CS101",
     "instructor_email": "testinstructor@sams.com"
   }
   ```

5. **Send Request:**
   - Click **Send**
   - Save the `id` from response (you'll need it for other requests)

---

## Complete Testing Flow

Follow these requests in order:

### 1. Test Connection ✅
```
GET http://localhost/sams/Backend/api/test
```

### 2. Login ✅
```
POST http://localhost/sams/Backend/api/auth/login
Body: {"email": "admin@sams.com", "password": "your_password"}
```

### 3. Create Instructor User ✅
```
POST http://localhost/sams/Backend/api/users
Body: {
  "requested_by_role": "admin",
  "name": "Instructor One",
  "email": "instructor1@sams.com",
  "password": "password123",
  "role": "instructor"
}
```

### 4. Create Student User ✅
```
POST http://localhost/sams/Backend/api/users
Body: {
  "requested_by_role": "admin",
  "name": "Student One",
  "email": "student1@sams.com",
  "password": "password123",
  "role": "student"
}
```

### 5. Get Instructors List ✅
```
GET http://localhost/sams/Backend/api/courses/instructors?requested_by_role=admin
```

### 6. Create Course ✅
```
POST http://localhost/sams/Backend/api/courses
Body: {
  "requested_by_role": "admin",
  "name": "CS101",
  "code": "CS101",
  "instructor_email": "instructor1@sams.com"
}
```
**Save the `id` from response!**

### 7. Enroll Student ✅
```
POST http://localhost/sams/Backend/api/courses/enroll
Body: {
  "requested_by_role": "admin",
  "course_id": 1,
  "student_email": "student1@sams.com"
}
```
(Replace `course_id` with actual ID from step 6)

### 8. Create Attendance Session ✅
```
POST http://localhost/sams/Backend/api/courses/attendance-session
Body: {
  "requested_by_role": "instructor",
  "requested_by_email": "instructor1@sams.com",
  "course_id": 1
}
```
**Save the `token` from response!**

### 9. Scan QR Code (Record Attendance) ✅
```
POST http://localhost/sams/Backend/api/courses/attendance-scan
Body: {
  "requested_by_role": "student",
  "token": "paste_token_from_step_8",
  "student_email": "student1@sams.com"
}
```

### 10. Get Attendance Logs ✅
```
GET http://localhost/sams/Backend/api/courses/attendance-logs?session_id=1&requested_by_role=admin&include_students=1
```

### 11. Get Analytics ✅
```
GET http://localhost/sams/Backend/api/courses/attendance-analytics?course_id=1&requested_by_role=admin
```

---

## Visual Guide: Postman Interface

```
┌─────────────────────────────────────────────────┐
│  [Collections ▼]  [Environments ▼]  [History]    │
├─────────────────────────────────────────────────┤
│                                                  │
│  SAMS API Collection                            │
│    ├─ Authentication                            │
│    │   └─ Login                                │
│    ├─ Users                                     │
│    │   ├─ Get All Users                        │
│    │   ├─ Create User                          │
│    │   └─ ...                                  │
│    └─ Courses                                   │
│        └─ ...                                   │
│                                                  │
├─────────────────────────────────────────────────┤
│  GET  [http://localhost/sams/Backend/api/test]  │
│       [Params] [Authorization] [Headers] [Body] │
│                                                  │
│  [Send]                                         │
│                                                  │
│  Response:                                      │
│  {                                              │
│    "success": true,                             │
│    "message": "PHP REST API is working!"        │
│  }                                              │
└─────────────────────────────────────────────────┘
```

---

## Common Mistakes to Avoid

### ❌ Wrong URL Format
- ❌ `http://localhost/sams/Backend/api/users.php` (don't use .php)
- ✅ `http://localhost/sams/Backend/api/users`

### ❌ Missing Content-Type Header
- Always set `Content-Type: application/json` for POST/PUT requests

### ❌ Missing requested_by_role Parameter
- Most endpoints need `requested_by_role` in query string or body
- Valid values: `admin`, `instructor`, `student`

### ❌ Wrong Request Method
- GET for retrieving data
- POST for creating data
- PUT for updating data
- DELETE for deleting data

---

## Troubleshooting

### Problem: 404 Not Found
**Check:**
1. Is Apache running in XAMPP?
2. Is the URL correct? Should be `http://localhost/sams/Backend/api/...`
3. Try accessing `http://localhost/sams/Backend/api/test` in browser first

### Problem: 403 Forbidden
**Check:**
1. Did you add `requested_by_role` parameter?
2. Is the role correct? (`admin`, `instructor`, or `student`)

### Problem: 500 Internal Server Error
**Check:**
1. Is MySQL running in XAMPP?
2. Does database `sams_db` exist?
3. Check `Backend/php_errors.log` file for details

### Problem: Empty Response or Connection Error
**Check:**
1. Is XAMPP running?
2. Can you access `http://localhost` in browser?
3. Check firewall settings

---

## Quick Test Script

Copy and paste this in Postman's **Tests** tab to auto-save course_id:

```javascript
// For Create Course request
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.id) {
        pm.environment.set("course_id", jsonData.data.id);
        console.log("Course ID saved: " + jsonData.data.id);
    }
}
```

---

## Next Steps

1. ✅ Test all endpoints using the collection
2. ✅ Try manual requests to understand the API
3. ✅ Read `POSTMAN_API_GUIDE.md` for detailed documentation
4. ✅ Create your own test scenarios

---

**Need Help?** 
- Check `POSTMAN_API_GUIDE.md` for complete API reference
- Check `POSTMAN_QUICK_START.md` for quick setup guide
- Check `Backend/php_errors.log` for server errors

