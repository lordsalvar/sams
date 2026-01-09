# Setup Guide

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- XAMPP/WAMP/LAMP installed

---

## Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**

---

## Step 2: Enable mod_rewrite (Required)

1. Open XAMPP Control Panel
2. Click **Config** next to Apache
3. Select **httpd.conf**
4. Find: `#LoadModule rewrite_module modules/mod_rewrite.so`
5. Remove the `#` to uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
6. Save the file
7. **Restart Apache** in XAMPP Control Panel

---

## Step 3: Database Setup

### Option A: Using phpMyAdmin (Recommended)

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click on **Import** tab
3. Choose file: `Backend/database_setup.sql`
4. Click **Go** to import
5. Verify database `sams_db` is created

### Option B: Using Command Line

```bash
mysql -u root -p < Backend/database_setup.sql
```

---

## Step 4: Configuration

Edit `Backend/microservices/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for default XAMPP
define('DB_NAME', 'sams_db');
```

---

## Step 5: Verify Installation

### Test API Gateway

Open in browser or use Postman:
```
GET http://localhost/sams/Backend/gateway/api/test
```

**Expected Response:**
```json
{
  "success": true,
  "message": "API Gateway and Microservices are working!",
  "timestamp": "2024-01-01 12:00:00",
  "service": "test-microservice",
  "architecture": "microservices"
}
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

## Step 6: Frontend Integration

Update your frontend API base URL:

```javascript
// In your frontend code
const API_BASE_URL = 'http://localhost/sams/Backend/gateway/api';
```

---

## Default Test Users

After importing the database, you can login with:

- **Admin:** `admin@local.dev` / `password`
- **Instructor:** `instructor@local.dev` / `password`
- **Student:** `student@local.dev` / `password`

---

## Architecture Overview

```
Frontend → API Gateway → Microservices → Database
```

**Base URL:** `http://localhost/sams/Backend/gateway/api`

**Important:** All API requests must go through the Gateway. Direct access to microservices is blocked.

---

## Troubleshooting

### Gateway returns 404

- ✅ Check Apache is running
- ✅ Verify mod_rewrite is enabled
- ✅ Check `.htaccess` file exists in `Backend/gateway/`
- ✅ Verify path: `http://localhost/sams/Backend/gateway/api/test`

### Database connection fails

- ✅ Check MySQL is running in XAMPP
- ✅ Verify database `sams_db` exists
- ✅ Check credentials in `Backend/microservices/config.php`
- ✅ Test connection: `mysql -u root -p`

### CORS errors

- ✅ CORS is configured in Gateway
- ✅ Check frontend URL matches allowed origins
- ✅ Verify request headers include `Content-Type: application/json`

### Direct microservice access blocked

- ✅ This is **expected behavior**
- ✅ All requests must go through Gateway
- ✅ Use: `http://localhost/sams/Backend/gateway/api/{endpoint}`

---

## File Structure

```
Backend/
├── gateway/
│   ├── index.php          # API Gateway (single entry point)
│   └── .htaccess          # Routing rules
│
├── microservices/
│   ├── config.php         # Database configuration
│   ├── .htaccess          # Security (blocks direct access)
│   ├── AuthService.php
│   ├── UserService.php
│   ├── CourseService.php
│   ├── AttendanceService.php
│   ├── EnrollmentService.php
│   └── TestService.php
│
└── database_setup.sql     # Database schema
```

---

## Quick Start Checklist

- [ ] XAMPP Apache started
- [ ] XAMPP MySQL started
- [ ] mod_rewrite enabled
- [ ] Database imported (`sams_db`)
- [ ] Configuration updated (`config.php`)
- [ ] Gateway test successful (`/api/test`)
- [ ] Frontend API URL updated

---

## Next Steps

1. Test all API endpoints (see CODEBASE.md)
2. Integrate with frontend
3. Customize configuration as needed

---

**Status:** ✅ Ready for development

