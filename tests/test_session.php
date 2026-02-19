<?php
// Test session functionality for OAuth debugging
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_start();

// Store a test value in session
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

// Store OAuth test state
if (!isset($_SESSION['oauth_test_state'])) {
    $_SESSION['oauth_test_state'] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
    </style>
</head>
<body>
    <h1>Session Test for OAuth Debugging</h1>
    
    <div class="info">
        <strong>Session ID:</strong> <?= session_id() ?><br>
        <strong>Test Counter:</strong> <?= $_SESSION['test_counter'] ?><br>
        <strong>OAuth Test State:</strong> <?= $_SESSION['oauth_test_state'] ?><br>
        <strong>OAuth Provider (if set):</strong> <?= $_SESSION['oauth_provider'] ?? 'Not set' ?><br>
        <strong>OAuth State (if set):</strong> <?= $_SESSION['oauth_state'] ?? 'Not set' ?>
    </div>
    
    <?php if ($_SESSION['test_counter'] > 1): ?>
    <div class="success">
        ✓ Session is working! Counter increased from previous page load.
    </div>
    <?php else: ?>
    <div class="info">
        Refresh this page to test if session persists.
    </div>
    <?php endif; ?>
    
    <a href="test_session.php" class="button">Refresh Page</a>
    <a href="../pages/auth/signin.php" class="button">Go to Sign In</a>
    <a href="<?= $_SERVER['PHP_SELF'] ?>?reset=1" class="button" onclick="return confirm('Reset session?')">Reset Session</a>
    
    <?php
    if (isset($_GET['reset'])) {
        session_destroy();
        header('Location: test_session.php');
        exit();
    }
    ?>
    
    <h2>PHP Session Configuration</h2>
    <div class="info">
        <strong>Session Save Path:</strong> <?= session_save_path() ?><br>
        <strong>Session Cookie Parameters:</strong><br>
        <?php
        $params = session_get_cookie_params();
        foreach ($params as $key => $value) {
            echo "  - $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "<br>";
        }
        ?>
    </div>
</body>
</html>
