<?php
/**
 * Test Login Check - Shows what happens when trying to login with each user
 */
require_once 'db_connect.php';

$test_email = isset($_GET['email']) ? $_GET['email'] : 'zcka062@gmail.com';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Check Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .warning-box { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .error-box { background: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .success-box { background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test-form { background: #f9f9f9; padding: 20px; border-radius: 4px; margin: 20px 0; }
        .test-form input { padding: 8px; margin-right: 10px; width: 300px; }
        .test-form button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .code { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Check Test for: <?php echo htmlspecialchars($test_email); ?></h1>
        
        <div class="test-form">
            <form method="GET">
                <label><strong>Test with different email:</strong></label><br><br>
                <input type="email" name="email" value="<?php echo htmlspecialchars($test_email); ?>" placeholder="Enter email to test">
                <button type="submit">Check This Email</button>
            </form>
        </div>
        
        <?php
        // Get user info
        $stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, oauth_provider, oauth_id FROM users WHERE email = ?');
        $stmt->bind_param('s', $test_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo '<div class="error-box"><strong>❌ User not found!</strong><br>Email "' . htmlspecialchars($test_email) . '" does not exist in the database.</div>';
        } else {
            $user = $result->fetch_assoc();
            
            echo '<h2>📊 User Information</h2>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td><strong>ID</strong></td><td>' . $user['id'] . '</td></tr>';
            echo '<tr><td><strong>Name</strong></td><td>' . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . '</td></tr>';
            echo '<tr><td><strong>Email</strong></td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
            echo '<tr><td><strong>Has Password?</strong></td><td>' . ($user['password'] ? '✅ Yes (can login with email/password)' : '❌ No (password is NULL)') . '</td></tr>';
            echo '<tr><td><strong>OAuth Provider</strong></td><td>' . ($user['oauth_provider'] ? '🔑 ' . ucfirst($user['oauth_provider']) : 'None (regular user)') . '</td></tr>';
            echo '<tr><td><strong>OAuth ID</strong></td><td>' . ($user['oauth_id'] ? $user['oauth_id'] : 'None') . '</td></tr>';
            echo '</table>';
            
            // Simulate login check logic
            echo '<h2>🧪 Login Behavior Simulation</h2>';
            
            if ($user['password'] === null || empty($user['password'])) {
                $provider = ucfirst($user['oauth_provider'] ?? 'social media');
                echo '<div class="error-box">';
                echo '<strong>❌ Email/Password Login: BLOCKED</strong><br>';
                echo '<strong>Reason:</strong> This account has no password set.<br>';
                echo '<strong>Error Message:</strong> "This account was created using ' . $provider . '. Please sign in with ' . $provider . '."<br><br>';
                echo '<strong>This is what signin.php will display when attempting email/password login.</strong>';
                echo '</div>';
                
                echo '<div class="success-box">';
                echo '<strong>✅ OAuth Login: ALLOWED</strong><br>';
                echo '<strong>Method:</strong> Click the "' . $provider . '" button on the signin page<br>';
                echo '<strong>Will work:</strong> Yes, because account was created via ' . $provider;
                echo '</div>';
            } else {
                echo '<div class="success-box">';
                echo '<strong>✅ Email/Password Login: ALLOWED</strong><br>';
                echo'<strong>Method:</strong> Use email and password in the signin form<br>';
                echo '<strong>Will work:</strong> Yes, because account has a password set';
                echo '</div>';
                
                if ($user['oauth_provider']) {
                    echo '<div class="success-box">';
                    echo '<strong>✅ OAuth Login: ALSO ALLOWED</strong><br>';
                    echo '<strong>Method:</strong> Click the "' . ucfirst($user['oauth_provider']) . '" button<br>';
                    echo '<strong>Will work:</strong> Yes, because account is linked to ' . ucfirst($user['oauth_provider']);
                    echo '</div>';
                } else {
                    echo '<div class="info-box">';
                    echo '<strong>ℹ️ OAuth Login: NOT LINKED</strong><br>';
                    echo '<strong>Note:</strong> This account is not linked to Google or Facebook';
                    echo '</div>';
                }
            }
            
            // Show the actual code path
            echo '<h2>📝 Code Path in signin.php</h2>';
            echo '<div class="code">';
            echo '<pre style="color: #abb2bf; margin: 0;">';
            echo "// Line 309: SELECT query fetches oauth_provider\n";
            echo "\$stmt = \$conn->prepare('SELECT ... oauth_provider FROM users WHERE email = ?');\n\n";
            echo "// Lines 321-327: Check if user has OAuth-only account\n";
            
            if ($user['password'] === null || empty($user['password'])) {
                echo '<span style="color: #98c379;">// ✓ THIS PATH WILL EXECUTE for ' . htmlspecialchars($test_email) . '</span>' . "\n";
                echo "if (\$user['password'] === null || empty(\$user['password'])) {\n";
                echo "    \$oauth_provider = ucfirst(\$user['oauth_provider'] ?? 'social media');\n";
                echo "    <span style=\"color: #e06c75;\">header('Location: signin.php?error=' . urlencode(\"This account was created using {\$oauth_provider}. Please sign in with {\$oauth_provider}.\"));</span>\n";
                echo "    exit();\n";
                echo "}\n";
            } else {
                echo '<span style="color: #56b6c2;">// ✗ This path will NOT execute (has password)</span>' . "\n";
                echo '<span style="color: #5c6370;">if ($user[\'password\'] === null || empty($user[\'password\'])) { ... }</span>' . "\n\n";
                echo '<span style="color: #98c379;">// ✓ THIS PATH WILL EXECUTE instead</span>' . "\n";
                echo "if (password_verify(\$password, \$user['password'])) {\n";
                echo "    // Login successful\n";
                echo "}\n";
            }
            echo '</pre>';
            echo '</div>';
        }
        
        $stmt->close();
        ?>
        
        <div class="warning-box">
            <h3>⚠️ Testing Tips:</h3>
            <ul>
                <li><strong>Clear browser cache:</strong> Hold Ctrl+Shift+R (or Cmd+Shift+R on Mac) to hard refresh the signin page</li>
                <li><strong>Try different emails:</strong> Test with both OAuth users and regular users</li>
                <li><strong>Check the URL:</strong> After failed login, look for <code>?error=</code> in the URL bar</li>
                <li><strong>View all users:</strong> <a href="test_login_types.php">Click here to see all login types</a></li>
            </ul>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="../pages/auth/signin.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">← Test Actual Sign In Page</a>
            <a href="test_login_types.php" style="display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">View All Users</a>
        </div>
    </div>
</body>
</html>
