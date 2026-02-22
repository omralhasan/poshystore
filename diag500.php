<?php
// Temporary diagnostic - DELETE AFTER USE
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h2>PHP OK - Version: " . PHP_VERSION . "</h2>\n";

// Step 1: Check config
echo "<p>Loading config...</p>\n";
try {
    require_once __DIR__ . '/config.php';
    echo "<p style='color:green'>✓ config.php loaded</p>\n";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ config.php FAILED: " . $e->getMessage() . "</p>\n";
    die();
}

// Step 2: DB credentials
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: 3306;
$user = getenv('DB_USER') ?: 'poshy_user';
$pass = getenv('DB_PASS') ?: 'Poshy2026secure';
$db   = getenv('DB_NAME') ?: 'poshy_db';

echo "<p>DB Host: <b>$host:$port</b> | User: <b>$user</b> | DB: <b>$db</b></p>\n";

// Step 3: MySQL connection
echo "<p>Testing MySQL connection...</p>\n";
$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) {
    echo "<p style='color:red'>✗ MySQL FAILED: " . $conn->connect_error . "</p>\n";
    
    // Try poshy_lifestyle as alternative
    echo "<p>Trying database 'poshy_lifestyle'...</p>\n";
    $conn2 = new mysqli($host, $user, $pass, 'poshy_lifestyle', (int)$port);
    if ($conn2->connect_error) {
        echo "<p style='color:red'>✗ Also failed: " . $conn2->connect_error . "</p>\n";
    } else {
        echo "<p style='color:orange'>✓ Connected to poshy_lifestyle — .env has wrong DB_NAME!</p>\n";
        $conn2->close();
    }
} else {
    echo "<p style='color:green'>✓ MySQL connected to '$db' successfully</p>\n";
    $conn->close();
}

// Step 4: Check key files exist
echo "<h3>Key Files:</h3>\n";
$files = [
    'includes/auth_functions.php',
    'includes/product_manager.php',
    'includes/cart_handler.php',
    'includes/language.php',
    'includes/ramadan_navbar.php',
    'includes/ramadan_theme_header.php',
];
foreach ($files as $f) {
    $exists = file_exists(__DIR__ . '/' . $f);
    $color = $exists ? 'green' : 'red';
    $icon = $exists ? '✓' : '✗ MISSING';
    echo "<p style='color:$color'>$icon $f</p>\n";
}

echo "<p><b>Done. Delete this file after checking!</b></p>\n";
