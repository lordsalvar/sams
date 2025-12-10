<?php
// Diagnostic file to check server configuration
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Backend Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Backend Diagnostic Information</h1>
    
    <h2>File System</h2>
    <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
    <p><strong>Script Path:</strong> <?php echo __FILE__; ?></p>
    <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
    <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
    <p><strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
    
    <h2>File Existence Check</h2>
    <?php
    $files = [
        'api/test.php' => __DIR__ . '/api/test.php',
        'api/config.php' => __DIR__ . '/api/config.php',
        'index.php' => __DIR__ . '/index.php',
    ];
    
    foreach ($files as $name => $path) {
        $exists = file_exists($path);
        $color = $exists ? 'success' : 'error';
        echo "<p class='$color'><strong>$name:</strong> " . ($exists ? 'EXISTS ✓' : 'NOT FOUND ✗') . "</p>";
        if ($exists) {
            echo "<p class='info'>Path: $path</p>";
        }
    }
    ?>
    
    <h2>PHP Configuration</h2>
    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
    
    <h2>Test API Endpoint</h2>
    <p>Try accessing: <a href="api/test.php">api/test.php</a></p>
    
    <h2>Directory Listing</h2>
    <pre><?php
    function listDir($dir, $prefix = '') {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $path = $dir . '/' . $item;
                $type = is_dir($path) ? '[DIR]' : '[FILE]';
                echo $prefix . $type . ' ' . $item . "\n";
                if (is_dir($path) && $item != 'node_modules') {
                    listDir($path, $prefix . '  ');
                }
            }
        }
    }
    listDir(__DIR__);
    ?></pre>
</body>
</html>

