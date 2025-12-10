# Troubleshooting 404 Errors

## Step 1: Test Basic PHP Access

Try these URLs in order:

1. **Test PHP is working:**
   - http://localhost/sams/Backend/simple-test.php
   - Should show "Simple test works!" and PHP info

2. **Test direct file in Backend:**
   - http://localhost/sams/Backend/test-direct.php
   - Should show PHP info

3. **Test API file directly:**
   - http://localhost/sams/Backend/api/test.php
   - Should return JSON

## Step 2: If simple-test.php works but api/test.php doesn't

The issue is likely with the path or .htaccess. Try:

1. **Check file permissions** - Make sure files are readable
2. **Check case sensitivity** - Windows is case-insensitive but Apache might not be
3. **Check the exact path** - Verify: `D:\xampp\htdocs\sams\Backend\api\test.php`

## Step 3: Verify Apache DocumentRoot

Your httpd.conf shows:
```
DocumentRoot "C:/xampp/htdocs"
```

But your files are at:
```
D:\xampp\htdocs\sams\Backend\
```

**This is the problem!** Your files are on D: drive but Apache is looking in C: drive.

### Solution Options:

**Option A: Move files to C: drive**
- Move `sams` folder to: `C:\xampp\htdocs\sams\`
- Then access: http://localhost/sams/Backend/api/test.php

**Option B: Change DocumentRoot in httpd.conf**
- Change: `DocumentRoot "C:/xampp/htdocs"` 
- To: `DocumentRoot "D:/xampp/htdocs"`
- Also update: `<Directory "C:/xampp/htdocs">` to `<Directory "D:/xampp/htdocs">`
- Restart Apache

**Option C: Create Virtual Host (Recommended)**
- Add to httpd-vhosts.conf or httpd.conf:
```apache
<VirtualHost *:80>
    DocumentRoot "D:/xampp/htdocs/sams"
    ServerName sams.local
    <Directory "D:/xampp/htdocs/sams">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
- Add to hosts file: `127.0.0.1 sams.local`
- Access: http://sams.local/Backend/api/test.php

## Step 4: Re-enable .htaccess

Once direct file access works, uncomment the RewriteEngine line in `.htaccess`.

