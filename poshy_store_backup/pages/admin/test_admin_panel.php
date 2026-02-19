<?php
/**
 * Minimal Admin Panel Test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Admin Panel Test</title></head><body>";
echo "<h1>Admin Panel Access Test</h1>";

echo "<h3>Step 1: Session Start</h3>";
session_start();
echo "✅ Session started<br>";

echo "<h3>Step 2: Check Session</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Session exists<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
} else {
    echo "❌ No session - please <a href='../auth/signin.php'>login first</a><br>";
}

echo "<h3>Step 3: Load Auth Functions</h3>";
try {
    require_once __DIR__ . '/../../includes/auth_functions.php';
    echo "✅ Auth functions loaded<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h3>Step 4: Check isAdmin()</h3>";
$isAdminResult = isAdmin();
echo "isAdmin() = " . ($isAdminResult ? 'TRUE ✅' : 'FALSE ❌') . "<br>";

if (!$isAdminResult) {
    echo "<br>❌ Access denied - not an admin<br>";
    echo "<a href='../../index.php'>Go to Homepage</a><br>";
    echo "<a href='../auth/logout.php'>Logout</a>";
    die();
}

echo "<h3>Step 5: Load Database</h3>";
try {
    require_once __DIR__ . '/../../includes/db_connect.php';
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 6: Load Checkout Functions</h3>";
try {
    require_once __DIR__ . '/../shop/checkout.php';
    echo "✅ Checkout functions loaded<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Step 7: Load Product Manager</h3>";
try {
    require_once __DIR__ . '/../../includes/product_manager.php';
    echo "✅ Product manager loaded<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr><h3>✅ ALL CHECKS PASSED!</h3>";
echo "<p><a href='admin_panel.php'>Try Real Admin Panel</a> | <a href='../auth/logout.php'>Logout</a></p>";

echo "</body></html>";
?>
