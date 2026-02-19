<?php
/**
 * Test Admin Access - Debug why admin can't login
 */
session_start();

echo "<!DOCTYPE html><html><head><title>Admin Access Test</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-left:3px solid #4ec9b0;margin:10px 0;}";
echo ".success{color:#4ec9b0;}.error{color:#f48771;}</style></head><body>";

echo "<h1>🔍 Admin Access Debug</h1>";

// Check if already logged in
echo "<h3>Step 1: Check Current Session</h3>";
echo "<pre>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Session exists\n";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
    echo "Email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
} else {
    echo "❌ No active session\n";
}
echo "</pre>";

// Test login
if (isset($_POST['do_login'])) {
    echo "<h3>Step 2: Attempting Login</h3>";
    require_once __DIR__ . '/includes/db_connect.php';
    
    $email = 'admin@poshylifestyle.com';
    $password = 'admin123';
    
    $stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<pre>";
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo "✅ User found: " . $user['email'] . "\n";
        echo "Role from DB: " . $user['role'] . "\n";
        
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verified\n\n";
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            echo "✅ Session variables set:\n";
            echo "  user_id: " . $_SESSION['user_id'] . "\n";
            echo "  role: " . $_SESSION['role'] . "\n";
            echo "  logged_in: " . $_SESSION['logged_in'] . "\n";
        } else {
            echo "❌ Password verification failed\n";
        }
    } else {
        echo "❌ User not found\n";
    }
    $stmt->close();
    echo "</pre>";
}

// Test isAdmin() function
echo "<h3>Step 3: Test isAdmin() Function</h3>";
require_once __DIR__ . '/includes/auth_functions.php';
echo "<pre>";
$isAdminResult = isAdmin();
echo "isAdmin() returns: " . ($isAdminResult ? '<span class="success">TRUE ✅</span>' : '<span class="error">FALSE ❌</span>') . "\n";
echo "\nExpected: TRUE if role='admin'\n";
echo "Actual role in session: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Test admin panel access
echo "<h3>Step 4: Test Admin Panel Redirect</h3>";
echo "<pre>";
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "✅ Should redirect to: ../admin/admin_panel.php\n";
    echo "From signin.php location: pages/auth/signin.php\n";
    echo "To admin panel: pages/admin/admin_panel.php\n";
    echo "Relative path: ../admin/admin_panel.php ✅\n";
} else {
    echo "❌ Not logged in as admin\n";
    echo "Current role: " . ($_SESSION['role'] ?? 'NONE') . "\n";
}
echo "</pre>";

// Display full session
echo "<h3>Full Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test form
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<hr><form method='POST'>";
    echo "<button type='submit' name='do_login' style='padding:10px 20px;background:#007acc;color:white;border:none;cursor:pointer;'>"; 
    echo "🔐 Test Admin Login</button>";
    echo "</form>";
}

// Navigation
echo "<hr><p>";
echo "<a href='pages/auth/signin.php' style='color:#569cd6;'>Go to Signin Page</a> | ";
echo "<a href='pages/admin/admin_panel.php' style='color:#569cd6;'>Try Admin Panel</a> | ";
echo "<a href='pages/auth/logout.php' style='color:#f48771;'>Logout</a>";
echo "</p>";

echo "</body></html>";
?>
