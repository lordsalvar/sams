# XAMPP Backend Setup Guide

## Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL** (for database)

## Step 2: Enable mod_rewrite (Required for clean URLs)

1. Open XAMPP Control Panel
2. Click **Config** next to Apache
3. Select **httpd.conf**
4. Find the line: `#LoadModule rewrite_module modules/mod_rewrite.so`
5. Remove the `#` to uncomment it: `LoadModule rewrite_module modules/mod_rewrite.so`
6. Save the file
7. Restart Apache in XAMPP Control Panel

## Step 3: Set Up Database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click on **Import** tab
3. Choose file: `Backend/database_setup.sql`
4. Click **Go** to import
5. Verify the database `sams_db` and table `users` are created

## Step 4: Verify Backend is Working

### Test the API endpoint:
Open in browser or use Postman:
- **Test endpoint**: http://localhost/sams/Backend/api/test.php
- **Login endpoint**: http://localhost/sams/Backend/api/auth/login.php (POST request)

### Expected Response from test.php:
```json
{
    "success": true,
    "message": "PHP REST API is working! React frontend connected successfully.",
    "timestamp": "2024-01-01 12:00:00",
    "method": "GET"
}
```

## Step 5: Configure Frontend API URL

The frontend is already configured to use:
- API Base URL: `http://localhost/sams/Backend/api`

This is set in:
- `Frontend/src/pages/Login.tsx` (line 8)
- `Backend/api/config.php` (line 10)

## Troubleshooting

### If you get 404 errors:
- Make sure Apache is running
- Check that mod_rewrite is enabled
- Verify the path: `D:\xampp\htdocs\sams\Backend\`

### If database connection fails:
- Make sure MySQL is running in XAMPP
- Verify database credentials in `Backend/api/config.php`:
  - DB_HOST: localhost
  - DB_USER: root
  - DB_PASS: (empty for default XAMPP)
  - DB_NAME: sams_db

### If CORS errors occur:
- CORS is already configured in `Backend/index.php`
- Make sure you're accessing from the correct frontend URL

## API Endpoints

- **GET** `/api/test.php` - Test endpoint
- **POST** `/api/auth/login.php` - User login
  - Body: `{ "email": "admin@local.dev", "password": "password" }`

## Test Users

After importing the database:
- Admin: `admin@local.dev` / `password`
- Instructor: `instructor@local.dev` / `password`
- Student: `student@local.dev` / `password`

