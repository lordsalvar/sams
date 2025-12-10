# API Testing Guide

## Test Direct File Access

Try accessing these URLs in your browser:

1. **Test PHP is working:**
   - http://localhost/sams/Backend/test-direct.php
   - Should show PHP info

2. **Test API file directly:**
   - http://localhost/sams/Backend/api/test.php
   - Should return JSON response

3. **If still getting 404, check:**

### Option A: Check Apache AllowOverride

1. Open XAMPP Control Panel
2. Click **Config** next to Apache
3. Select **httpd.conf**
4. Find the section for your htdocs directory (around line 250-280)
5. Look for: `<Directory "D:/xampp/htdocs">`
6. Make sure it has: `AllowOverride All`
7. If it says `AllowOverride None`, change it to `AllowOverride All`
8. Save and restart Apache

### Option B: Test without .htaccess

Temporarily rename `.htaccess` to `.htaccess.bak` and try accessing:
- http://localhost/sams/Backend/api/test.php

If this works, the issue is with .htaccess configuration.

### Option C: Check File Permissions

Make sure the files are readable by Apache.

### Debugging Steps

1. Check Apache error logs:
   - XAMPP Control Panel → Apache → Logs → Error log
   - Look for any errors related to your request

2. Verify the file exists:
   - Check: `D:\xampp\htdocs\sams\Backend\api\test.php`
   - Make sure the file is actually there

3. Test with a simple PHP file:
   - Create `Backend/simple-test.php` with: `<?php echo "Hello"; ?>`
   - Access: http://localhost/sams/Backend/simple-test.php
   - If this works, PHP is fine, issue is with routing

