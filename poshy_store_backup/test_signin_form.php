<?php
/**
 * Enhanced Signin Page Debug
 * Shows POST data and what happens during login
 */

// Start session FIRST
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_start();

$debug_info = [];
$debug_info['session_id'] = session_id();
$debug_info['request_method'] = $_SERVER['REQUEST_METHOD'];
$debug_info['has_signin_post'] = isset($_POST['signin']) ? 'YES' : 'NO';
$debug_info['post_data_keys'] = array_keys($_POST);

// Process login if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    require_once __DIR__ . '/includes/db_connect.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug_info['email_submitted'] = $email;
    $debug_info['password_length'] = strlen($password);
    
    if (empty($email) || empty($password)) {
        $debug_info['error'] = 'Email or password empty';
    } else {
        $stmt = $conn->prepare('SELECT id, firstname, lastname, phonenumber, email, password, role, oauth_provider FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $debug_info['users_found'] = $result->num_rows;
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $debug_info['user_role'] = $user['role'];
            $debug_info['has_password'] = !empty($user['password']) ? 'YES' : 'NO';
            
            if ($user['password'] === null || empty($user['password'])) {
                $debug_info['error'] = 'OAuth user - no password';
            } elseif (password_verify($password, $user['password'])) {
                // SUCCESS - Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phonenumber'] = $user['phonenumber'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                $debug_info['login_success'] = 'YES';
                $debug_info['session_set'] = 'YES';
                $debug_info['redirect_to'] = ($user['role'] === 'admin') ? 'admin panel' : 'homepage';
                
                // Don't redirect in debug mode - show success
            } else {
                $debug_info['error'] = 'Password incorrect';
            }
        } else {
            $debug_info['error'] = 'User not found';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signin Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; margin-top: 30px; }
        .form-box { background: #252526; padding: 30px; border-radius: 8px; margin: 20px 0; }
        input { width: 100%; padding: 12px; margin: 10px 0; font-size: 14px; background: #3c3c3c; border: 1px solid #555; color: #d4d4d4; border-radius: 4px; }
        button { width: 100%; padding: 15px; background: #007acc; color: white; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; margin: 10px 0; }
        button:hover { background: #005a9e; }
        pre { background: #252526; padding: 15px; border-left: 3px solid #4ec9b0; overflow-x: auto; }
        .success { border-left-color: #4ec9b0; background: #1e3a1e; }
        .error { border-left-color: #f48771; background: #3a1e1e; }
        .info { border-left-color: #569cd6; }
        a { color: #569cd6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Signin Page Debug</h1>
        
        <?php if (!empty($debug_info)): ?>
        <h2>Debug Information:</h2>
        <pre class="<?= isset($debug_info['login_success']) ? 'success' : (isset($debug_info['error']) ? 'error' : 'info') ?>">
<?php 
foreach ($debug_info as $key => $value) {
    echo str_pad($key . ':', 25) . (is_array($value) ? json_encode($value) : $value) . "\n";
}
?>
        </pre>
        
        <?php if (isset($debug_info['login_success'])): ?>
            <div class="form-box success">
                <h2 style="color: #4ec9b0;">✅ Login Successful!</h2>
                <p>Session variables have been set. You can now access protected pages.</p>
                <p><a href="pages/admin/admin_panel.php" style="color: #4ec9b0; font-size: 18px;">→ Go to Admin Panel</a></p>
                <p><a href="index.php" style="color: #4ec9b0; font-size: 18px;">→ Go to Homepage</a></p>
            </div>
        <?php endif; ?>
        
        <h2>Current Session:</h2>
        <pre class="info"><?php print_r($_SESSION); ?></pre>
        <?php endif; ?>
        
        <h2>Test Login Form:</h2>
        <div class="form-box">
            <form method="POST">
                <label>Email:</label>
                <input type="email" name="email" value="admin@poshylifestyle.com" required>
                
                <label>Password:</label>
                <input type="password" name="password" value="admin123" required>
                
                <button type="submit" name="signin">🔐 Sign In (Debug Mode)</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="pages/auth/signin.php">Use Real Signin Page</a> |
                <a href="quick_login.php">Quick Login</a> |
                <a href="start.php">All Links</a>
            </p>
        </div>
    </div>
</body>
</html>
