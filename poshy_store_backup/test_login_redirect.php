<?php
/**
 * Test Login and Redirect - Debug admin vs customer login
 */
session_start();
require_once __DIR__ . '/includes/db_connect.php';

echo "<!DOCTYPE html><html><head><title>Login Redirect Test</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-left:3px solid #4ec9b0;margin:10px 0;}";
echo ".success{color:#4ec9b0;}.error{color:#f48771;}.info{color:#569cd6;}</style></head><body>";

echo "<h1>🔐 Login & Redirect Test</h1>";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: test_login_redirect.php');
    exit();
}

// Show current session
echo "<h3>Current Session:</h3><pre>";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Email: " . $_SESSION['email'] . "\n";
    echo "Role: " . $_SESSION['role'] . "\n";
    echo "<a href='?logout=1' style='color:#f48771;'>Logout</a>";
} else {
    echo "Not logged in\n";
}
echo "</pre>";

// Handle login
if (isset($_POST['test_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<h3>🔍 Login Attempt:</h3><pre>";
    echo "Email: $email\n";
    
    $stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            echo "<span class='success'>✅ Login successful!</span>\n";
            echo "Role: " . $user['role'] . "\n\n";
            
            // Determine redirect
            if ($user['role'] === 'admin') {
                $redirect = 'pages/admin/admin_panel.php';
                echo "<span class='info'>→ Should redirect to: $redirect</span>\n";
                echo "\n<h3>Test Admin Panel Access:</h3>";
                echo "<a href='$redirect' style='color:#569cd6; padding:10px 20px; background:#007acc; text-decoration:none; display:inline-block; border-radius:4px;'>Go to Admin Panel</a>";
            } else {
                $redirect = 'index.php';
                echo "<span class='info'>→ Should redirect to: $redirect</span>\n";
                echo "\n<h3>Test Homepage Access:</h3>";
                echo "<a href='$redirect' style='color:#569cd6; padding:10px 20px; background:#007acc; text-decoration:none; display:inline-block; border-radius:4px;'>Go to Homepage</a>";
            }
        } else {
            echo "<span class='error'>❌ Incorrect password</span>\n";
        }
    } else {
        echo "<span class='error'>❌ User not found</span>\n";
    }
    $stmt->close();
    echo "</pre>";
}

// Show login forms
if (!isset($_SESSION['user_id'])) {
    echo "<hr><h3>Test Admin Login:</h3>";
    echo "<form method='POST' style='background:#252526;padding:20px;border-radius:8px;margin:10px 0;'>";
    echo "<input type='hidden' name='email' value='admin@poshylifestyle.com'>";
    echo "<input type='hidden' name='password' value='admin123'>";
    echo "<button type='submit' name='test_login' style='padding:10px 20px;background:#007acc;color:white;border:none;cursor:pointer;border-radius:4px;'>";
    echo "🔐 Login as Admin</button>";
    echo "<p style='color:#999;margin-top:10px;'>admin@poshylifestyle.com / admin123</p>";
    echo "</form>";
    
    echo "<h3>Test Customer Login:</h3>";
    echo "<form method='POST' style='background:#252526;padding:20px;border-radius:8px;margin:10px 0;'>";
    echo "<input type='hidden' name='email' value='mate7762s@gmail.com'>";
    echo "<input type='hidden' name='password' value='password123'>";
    echo "<button type='submit' name='test_login' style='padding:10px 20px;background:#28a745;color:white;border:none;cursor:pointer;border-radius:4px;'>";
    echo "🔐 Login as Customer</button>";
    echo "<p style='color:#999;margin-top:10px;'>mate7762s@gmail.com / password123</p>";
    echo "</form>";
}

echo "<hr><p><a href='pages/auth/signin.php' style='color:#569cd6;'>Real Signin Page</a> | ";
echo "<a href='test_admin_access.php' style='color:#569cd6;'>Admin Access Test</a></p>";

echo "</body></html>";
?>
