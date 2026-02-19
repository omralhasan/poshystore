<?php
/**
 * Final Test Summary - Login Status
 */
session_start();

echo "<!DOCTYPE html><html><head><title>Login Test Summary</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo "h1{color:#333;border-bottom:3px solid #4CAF50;padding-bottom:10px;}"; 
echo ".success{background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{background:#d1ecf1;color:#0c5460;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".test-section{background:#f8f9fa;padding:20px;border-radius:5px;margin:15px 0;}";
echo "button{padding:12px 30px;margin:5px;font-size:16px;border:none;border-radius:5px;cursor:pointer;}";
echo ".admin-btn{background:#007bff;color:white;}.customer-btn{background:#28a745;color:white;}";
echo "a{color:#007bff;text-decoration:none;} a:hover{text-decoration:underline;}</style></head><body>";

echo "<div class='container'>";
echo "<h1>✅ Login System - TEST SUMMARY</h1>";

// Show current session
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>";
    echo "<h3>🔐 Currently Logged In</h3>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($_SESSION['email']) . "</p>";
    echo "<p><strong>Role:</strong> " . htmlspecialchars($_SESSION['role']) . "</p>";
    echo "<p><a href='pages/auth/logout.php'>Logout</a></p>";
    echo "</div>";
} else {
    echo "<div class='info'>";
    echo "<p>Not currently logged in</p>";
    echo "</div>";
}

echo "<div class='test-section'>";
echo "<h3>📋 Available Test Accounts:</h3>";
echo "<table style='width:100%;border-collapse:collapse;'>";
echo "<tr style='background:#007bff;color:white;'><th style='padding:10px;'>Email</th><th>Password</th><th>Role</th></tr>";
echo "<tr style='background:#f8f9fa;'><td style='padding:10px;'>admin@poshylifestyle.com</td><td>admin123</td><td>Admin</td></tr>";
echo"<tr style='background:white;'><td style='padding:10px;'>mate7762s@gmail.com</td><td>(Must be reset)</td><td>Customer</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>🧪 Quick Test Login:</h3>";
echo "<form method='POST' action='pages/auth/signin.php'>";
echo "<input type='hidden' name='signin' value='1'>";
echo "<input type='hidden' name='email' value='admin@poshylifestyle.com'>";
echo "<input type='hidden' name='password' value='admin123'>";
echo "<button type='submit' class='admin-btn'>🔐 Login as Admin</button>";
echo "</form>";
echo "<p style='margin-top:10px;'><small>Should redirect to Admin Panel</small></p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>📊 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ Changed signin redirects from relative paths to absolute paths</li>";
echo "<li>✅ Admin redirect: <code>/poshy_store/pages/admin/admin_panel.php</code></li>";
echo "<li>✅ Customer redirect: <code>/poshy_store/index.php</code></li>";
echo "<li>✅ Fixed Shop navigation links to avoid unnecessary redirects</li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h3>🔗 Navigation:</h3>";
echo "<p><a href='pages/auth/signin.php'>→ Signin Page</a></p>";
echo "<p><a href='pages/admin/admin_panel.php'>→ Admin Panel (requires admin login)</a></p>";
echo "<p><a href='index.php'>→ Homepage</a></p>";
echo "<p><a href='test_login_redirect.php'>→ Login Redirect Test</a></p>";
echo "</div>";

echo "</div></body></html>";
?>
