<?php
/**
 * Test Email Login - Debug what happens during regular email/password login
 */
session_start();
require_once 'db_config.php';

// Simulating a login attempt
$test_email = 'admin@poshylifestyle.com';
$test_password = 'admin123'; // You'll need to enter the actual password

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .test-form { background: #f9f9f9; padding: 20px; border-radius: 4px; margin: 20px 0; }
        .test-form input { padding: 10px; margin: 5px 0; width: 100%; }
        .test-form button { padding: 12px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; color: #721c24; }
        .success { background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Email/Password Login Test</h1>
        
        <div class="info">
            <strong>This tool will test the email/password login process step-by-step</strong>
        </div>
        
        <div class="test-form">
            <form method="POST">
                <label><strong>Email:</strong></label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'admin@poshylifestyle.com'; ?>" required>
                
                <label><strong>Password:</strong></label>
                <input type="password" name="password" placeholder="Enter password" required>
                
                <button type="submit" name="test_login">Test Login</button>
            </form>
        </div>
        
        <?php
        if (isset($_POST['test_login'])) {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            echo '<h2>📊 Login Test Results</h2>';
            
            // Connect to database
            $conn = new mysqli(
                $db_config['host'],
                $db_config['user'],
                $db_config['password'],
                $db_config['database']
            );
            
            if ($conn->connect_error) {
                echo '<div class="error">❌ Database connection failed: ' . $conn->connect_error . '</div>';
                exit;
            }
            
            echo '<div class="success">✅ Database connected successfully</div>';
            
            // Set charset
            $conn->set_charset($db_config['charset']);
            
            // Query user
            $stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, role, oauth_provider FROM users WHERE email = ?');
            
            if (!$stmt) {
                echo '<div class="error">❌ Failed to prepare statement: ' . $conn->error . '</div>';
                exit;
            }
            
            echo '<div class="success">✅ Statement prepared successfully</div>';
            
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo '<div class="info">📧 Looking for user with email: ' . htmlspecialchars($email) . '</div>';
            
            if ($result->num_rows === 0) {
                echo '<div class="error">❌ User not found in database</div>';
            } else {
                echo '<div class="success">✅ User found in database</div>';
                
                $user = $result->fetch_assoc();
                
                echo '<h3>User Information:</h3>';
                echo '<pre>';
                echo 'ID: ' . $user['id'] . "\n";
                echo 'Name: ' . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . "\n";
                echo 'Email: ' . htmlspecialchars($user['email']) . "\n";
                echo 'Role: ' . htmlspecialchars($user['role']) . "\n";
                echo 'OAuth Provider: ' . ($user['oauth_provider'] ?? 'none') . "\n";
                echo 'Has Password: ' . ($user['password'] ? 'YES' : 'NO (NULL)') . "\n";
                if ($user['password']) {
                    echo 'Password Hash: ' . substr($user['password'], 0, 50) . '...' . "\n";
                }
                echo '</pre>';
                
                // Check if OAuth-only account
                if ($user['password'] === null || empty($user['password'])) {
                    $provider = ucfirst($user['oauth_provider'] ?? 'social media');
                    echo '<div class="error">';
                    echo '❌ <strong>OAuth-Only Account Detected</strong><br>';
                    echo 'This account was created using ' . $provider . '.<br>';
                    echo 'Error message that will be shown: "This account was created using ' . $provider . '. Please sign in with ' . $provider . '."';
                    echo '</div>';
                } else {
                    // Test password verification
                    echo '<h3>Password Verification:</h3>';
                    echo '<div class="info">Testing password entered: ' . str_repeat('*', strlen($password)) . '</div>';
                    
                    if (password_verify($password, $user['password'])) {
                        echo '<div class="success">';
                        echo '✅ <strong>Password Verified Successfully!</strong><br>';
                        echo 'Login would succeed and redirect to: ' . ($user['role'] === 'admin' ? 'admin_panel.php' : 'index.php');
                        echo '</div>';
                        
                        echo '<h3>Session Data That Would Be Set:</h3>';
                        echo '<pre>';
                        echo '$_SESSION[\'user_id\'] = ' . $user['id'] . "\n";
                        echo '$_SESSION[\'firstname\'] = \'' . htmlspecialchars($user['firstname']) . '\'' . "\n";
                        echo '$_SESSION[\'lastname\'] = \'' . htmlspecialchars($user['lastname']) . '\'' . "\n";
                        echo '$_SESSION[\'email\'] = \'' . htmlspecialchars($user['email']) . '\'' . "\n";
                        echo '$_SESSION[\'role\'] = \'' . htmlspecialchars($user['role']) . '\'' . "\n";
                        echo '$_SESSION[\'logged_in\'] = true' . "\n";
                        echo '</pre>';
                    } else {
                        echo '<div class="error">';
                        echo '❌ <strong>Password Verification Failed</strong><br>';
                        echo 'The password you entered does not match the stored hash.<br>';
                        echo 'Error shown to user: "Invalid email or password"';
                        echo '</div>';
                    }
                }
            }
            
            $stmt->close();
            $conn->close();
        }
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 4px;">
            <h3>💡 Common Issues:</h3>
            <ul>
                <li><strong>Wrong password:</strong> Make sure you're using the correct password</li>
                <li><strong>OAuth account:</strong> If account was created via Google/Facebook, email login won't work</li>
                <li><strong>Browser cache:</strong> Try clearing browser cache or use incognito mode</li>
                <li><strong>Session issues:</strong> Check if sessions are working properly</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="../pages/auth/signin.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">← Back to Sign In</a>
        </div>
    </div>
</body>
</html>
