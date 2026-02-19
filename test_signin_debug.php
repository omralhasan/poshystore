<?php
/**
 * Debug tool to test signin functionality
 */
session_start();

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db_connect.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<h2>🔍 Login Debug Results:</h2>";
    echo "<pre>";
    echo "Email: " . htmlspecialchars($email) . "\n";
    echo "Password length: " . strlen($password) . "\n";
    echo "DB Connection: " . ($conn->connect_error ? "❌ FAILED: " . $conn->connect_error : "✅ OK") . "\n\n";
    
    if (!$conn->connect_error && !empty($email)) {
        // Check if user exists
        $stmt = $conn->prepare('SELECT id, firstname, lastname, email, password, role, oauth_provider FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "User search results: " . $result->num_rows . " user(s) found\n\n";
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            echo "User found:\n";
            echo "  ID: " . $user['id'] . "\n";
            echo "  Name: " . htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']) . "\n";
            echo "  Email: " . htmlspecialchars($user['email']) . "\n";
            echo "  Role: " . htmlspecialchars($user['role']) . "\n";
            echo "  OAuth Provider: " . ($user['oauth_provider'] ?? 'none') . "\n";
            echo "  Has Password: " . (empty($user['password']) ? "❌ NO" : "✅ YES") . "\n\n";
            
            if (!empty($password) && !empty($user['password'])) {
                if (password_verify($password, $user['password'])) {
                    echo "Password verification: ✅ CORRECT\n";
                    echo "\n🎉 LOGIN SHOULD SUCCEED!\n";
                    
                    // Actually log them in
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['firstname'] = $user['firstname'];
                    $_SESSION['lastname'] = $user['lastname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    echo "\nSession created. <a href='index.php'>Go to homepage</a>\n";
                } else {
                    echo "Password verification: ❌ INCORRECT\n";
                    echo "\nThe password you entered doesn't match.\n";
                }
            } elseif (empty($user['password'])) {
                echo "⚠️ This account uses OAuth (" . ($user['oauth_provider'] ?? 'unknown') . "). Cannot login with password.\n";
            }
        } else {
            echo "❌ No user found with email: " . htmlspecialchars($email) . "\n";
        }
        $stmt->close();
    }
    echo "</pre>";
    
    echo "<hr><a href='test_signin_debug.php'>Try again</a> | <a href='pages/auth/signin.php'>Go to real signin</a>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signin Debug Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        form { background: #252526; padding: 20px; border-radius: 8px; max-width: 400px; }
        input { width: 100%; padding: 10px; margin: 10px 0; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #007acc; color: white; border: none; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a9e; }
        pre { background: #252526; padding: 15px; border-left: 3px solid #4ec9b0; overflow-x: auto; }
        a { color: #569cd6; }
    </style>
</head>
<body>
    <h1>🔐 Signin Debug Tool</h1>
    <p>Test your login credentials and see detailed debug information</p>
    
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required placeholder="admin@poshylifestyle.com">
        
        <label>Password:</label>
        <input type="password" name="password" required placeholder="Enter password">
        
        <button type="submit">🧪 Test Login</button>
    </form>
    
    <hr>
    
    <h3>📋 Available Test Accounts:</h3>
    <pre><?php
    require_once __DIR__ . '/includes/db_connect.php';
    $result = $conn->query("SELECT email, role, password IS NOT NULL as has_pass, oauth_provider FROM users LIMIT 5");
    echo "EMAIL                      | ROLE     | PASSWORD | OAUTH\n";
    echo "------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        printf("%-26s | %-8s | %-8s | %s\n", 
            $row['email'], 
            $row['role'],
            $row['has_pass'] ? 'YES' : 'NO',
            $row['oauth_provider'] ?? '-'
        );
    }
    ?></pre>
    
    <p><strong>Note:</strong> Accounts with PASSWORD=NO can only login via OAuth (Google/Facebook)</p>
</body>
</html>
