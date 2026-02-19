<?php
/**
 * Test Invoice Access with Admin Session
 */

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

// Check current session
echo "<h2>Session Debug for Invoice Access</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n\n";

echo "Session Data:\n";
print_r($_SESSION);

echo "\nAuthentication Checks:\n";
echo "checkSession(): " . (checkSession() ? 'TRUE' : 'FALSE') . "\n";
echo "isLoggedIn(): " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "\n";
echo "isAdmin(): " . (isAdmin() ? 'TRUE' : 'FALSE') . "\n";

if (isset($_SESSION['user_id'])) {
    echo "\nUser ID: " . $_SESSION['user_id'] . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
}

echo "\n================================\n\n";

// Test invoice access
if (isAdmin()) {
    echo "<p style='color: green;'>✅ You are logged in as ADMIN</p>";
    echo "<p><a href='pages/admin/print_invoice.php?order_id=2' target='_blank'>🖨️ Test Invoice Link (Order #2)</a></p>";
    echo "<p><a href='pages/admin/admin_panel.php'>← Back to Admin Panel</a></p>";
} else {
    echo "<p style='color: red;'>❌ You are NOT logged in as admin</p>";
    echo "<p>Please <a href='pages/auth/signin.php'>login</a> first</p>";
}

echo "</pre>";
?>
