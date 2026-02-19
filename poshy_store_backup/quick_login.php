<?php
/**
 * Live Login Test - Actually logs you in
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_login'])) {
    require_once __DIR__ . '/includes/db_connect.php';
    
    $email = 'admin@poshylifestyle.com';
    $password = 'admin123';
    
    $stmt = $conn->prepare('SELECT id, firstname, lastname, phonenumber, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phonenumber'] = $user['phonenumber'] ?? '';
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            $stmt->close();
            $conn->close();
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: pages/admin/admin_panel.php');
            } else {
                header('Location: index.php');
            }
            exit();
        }
    }
    $stmt->close();
}

// Check if already logged in
$already_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 500px; margin: 0 auto; background: #252526; padding: 30px; border-radius: 8px; }
        h1 { color: #4ec9b0; }
        button { width: 100%; padding: 15px; background: #007acc; color: white; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; margin: 10px 0; }
        button:hover { background: #005a9e; }
        .success { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #cfe2ff; color: #084298; padding: 15px; border-radius: 4px; margin: 10px 0; }
        a { color: #569cd6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Quick Login</h1>
        
        <?php if ($already_logged_in): ?>
            <div class="success">
                ✅ Already logged in as: <?= htmlspecialchars($_SESSION['firstname']) ?> <?= htmlspecialchars($_SESSION['lastname']) ?><br>
                Role: <?= htmlspecialchars($_SESSION['role']) ?>
            </div>
            
            <p style="margin: 20px 0;">Where would you like to go?</p>
            
            <a href="index.php"><button>🏠 Homepage</button></a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="pages/admin/admin_panel.php"><button>⚙️ Admin Panel</button></a>
            <?php endif; ?>
            <a href="pages/shop/my_orders.php"><button>📦 My Orders</button></a>
            <a href="pages/auth/logout.php"><button style="background: #dc3545;">🚪 Logout</button></a>
            
        <?php else: ?>
            <div class="info">
                Click the button below to instantly login as admin
            </div>
            
            <form method="POST">
                <button type="submit" name="quick_login">🔐 Login as Admin</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #999;">
                Credentials: admin@poshylifestyle.com / admin123
            </p>
        <?php endif; ?>
        
        <hr style="margin: 30px 0; border-color: #444;">
        
        <p style="text-align: center;">
            <a href="pages/auth/signin.php">Regular Sign In Page</a> | 
            <a href="test_admin_login.php">Debug Test</a> |
            <a href="start.php">All Links</a>
        </p>
    </div>
</body>
</html>
