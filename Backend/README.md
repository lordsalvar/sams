# PHP REST API Backend

This is a native PHP REST API backend for the SAMS project.

## Structure

- `index.php` - Main router that handles all API requests
- `api/` - Directory containing all API endpoints
- `.htaccess` - Apache rewrite rules for clean URLs

## Setup

1. Make sure Apache mod_rewrite is enabled
2. Place this folder in your XAMPP htdocs directory
3. Access API endpoints via: `http://localhost/sams/Backend/api/{endpoint}`

## Example Endpoints

- `GET /api/test.php` - Test endpoint to verify API is working

## Adding New Endpoints

1. Create a new PHP file in the `api/` directory
2. Add the route to `index.php` in the `$routes` array
3. Handle CORS headers in your endpoint file

## CORS

CORS is configured to allow requests from any origin. For production, restrict this to your React app's domain.

