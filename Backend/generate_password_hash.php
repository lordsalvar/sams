<?php
// Simple script to generate password hash
// Usage: php generate_password_hash.php

$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: {$password}\n";
echo "Hash: {$hash}\n";
echo "\nSQL INSERT statement:\n";
echo "-- Password hash for 'password': {$hash}\n";
?>

