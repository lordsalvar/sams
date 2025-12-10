<?php
// Simple info page to test if Apache is working
echo "<h1>SAMS Backend - Info Page</h1>";
echo "<p>If you see this, Apache is serving PHP files correctly.</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Filename:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// Check if files exist
echo "<h2>File Check:</h2>";
$files = [
    'verify-database.php',
    'test-login-diagnostic.php',
    'api/auth/login.php',
    'api/config.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? 'EXISTS' : 'NOT FOUND';
    echo "<p style='color: $color'>$file: $status</p>";
}

echo "<h2>Links:</h2>";
echo "<ul>";
echo "<li><a href='verify-database.php'>Verify Database</a></li>";
echo "<li><a href='test-login-diagnostic.php'>Login Diagnostic</a></li>";
echo "<li><a href='diagnostic.php'>General Diagnostic</a></li>";
echo "<li><a href='api/test.php'>API Test</a></li>";
echo "</ul>";
?>

