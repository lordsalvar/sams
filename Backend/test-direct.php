<?php
// Direct test file to verify PHP is working
echo "PHP is working!<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
phpinfo();
?>

