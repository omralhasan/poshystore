<?php
// Simple test to check if login functionality works
session_start();

echo "<h2>Login System Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection...</h3>";
require_once __DIR__ . '/includes/db_connect.php';

if ($conn && !$conn->connect_error) {
    echo "✅ Database connection: <strong>SUCCESS</strong><br>";
    echo "Database: poshy_lifestyle<br>";
} else {
    echo "❌ Database connection: <strong>FAILED</strong><br>";
    exit;
}

// Test 2: Check users table
echo "<h3>2. Testing Users Table...</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Users table accessible: <strong>" . $row['count'] . " users found</strong><br>";
} else {
    echo "❌ Cannot access users table<br>";
    exit;
}

// Test 3: Check for test user
echo "<h3>3. Checking for Users...</h3>";
$result = $conn->query("SELECT email, role FROM users LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['email']) . "</td><td>" . htmlspecialchars($row['role']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No users found in database<br>";
}

// Test 4: Session functionality
echo "<h3>4. Testing Session...</h3>";
$_SESSION['test_var'] = 'test_value';
if (isset($_SESSION['test_var'])) {
    echo "✅ Session working: <strong>SUCCESS</strong><br>";
    unset($_SESSION['test_var']);
} else {
    echo "❌ Session not working<br>";
}

echo "<h3>All Basic Tests Complete!</h3>";
echo "<p><a href='pages/auth/signin.php'>Go to Sign In Page</a></p>";
echo "<p><a href='index.php'>Go to Home Page</a></p>";
?>
