<?php
/**
 * Test Admin Login Flow
 * Simulates a complete login to debug issues
 */
session_start();

echo "<h2>🔍 Testing Admin Login Flow</h2>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#252526;padding:15px;border-left:3px solid #4ec9b0;}</style>";

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Test 1: Check if already logged in
echo "<h3>Step 1: Check Current Session</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID set: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
echo "Role set: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
echo "Logged in flag: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'YES' : 'NO') : 'NOT SET') . "\n";
echo "</pre>";

// Test 2: Attempt login
$email = 'admin@poshylifestyle.com';
$password = 'admin123';

echo "<h3>Step 2: Attempt Login</h3>";
echo "<pre>";
echo "Testing with:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Check user exists
$stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "✅ User found:\n";
    echo "  ID: " . $user['id'] . "\n";
    echo "  Name: " . $user['firstname'] . " " . $user['lastname'] . "\n";
    echo "  Role: " . $user['role'] . "\n";
    
    if (password_verify($password, $user['password'])) {
        echo "\n✅ Password correct!\n";
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        echo "\n✅ Session variables set\n";
    } else {
        echo "\n❌ Password incorrect\n";
    }
} else {
    echo "❌ User not found\n";
}
$stmt->close();
echo "</pre>";

// Test 3: Check isAdmin() function
echo "<h3>Step 3: Check isAdmin() Function</h3>";
echo "<pre>";
$is_admin = isAdmin();
echo "isAdmin() returns: " . ($is_admin ? "TRUE ✅" : "FALSE ❌") . "\n";

if (!$is_admin) {
    echo "\n🔍 Debugging why isAdmin() is false:\n";
    echo "  _SESSION['role']: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
    echo "  _SESSION['logged_in']: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'NOT SET') . "\n";
}
echo "</pre>";

// Test 4: Access admin panel
echo "<h3>Step 4: Test Admin Panel Access</h3>";
echo "<pre>";
if ($is_admin) {
    echo "✅ Should be able to access admin panel\n";
    echo "<a href='pages/admin/admin_panel.php' style='color:#569cd6;'>Click here to test admin panel</a>\n";
} else {
    echo "❌ Cannot access admin panel (isAdmin() is false)\n";
    echo "You will be redirected to homepage\n";
}
echo "</pre>";

// Display full session
echo "<h3>Full Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<a href='pages/auth/signin.php' style='color:#569cd6;'>Go to Sign In Page</a> | ";
echo "<a href='test_admin_login.php' style='color:#569cd6;'>Refresh Test</a>";
?>
