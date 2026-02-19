<?php
/**
 * Test OAuth Error Display
 * This will show the actual error from Google
 */
session_start();

// Get recent error from URL if redirected
$error_message = $_GET['error'] ?? 'No error message';

?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth Test Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #d32f2f; }
        .error-box { background: #ffebee; border: 1px solid #ef5350; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .session-info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .button { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>OAuth Error Details</h1>
        
        <div class="error-box">
            <h2>Error Message:</h2>
            <p><?= htmlspecialchars($error_message) ?></p>
        </div>
        
        <div class="session-info">
            <h2>Session Information:</h2>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="session-info">
            <h2>Current Configuration:</h2>
            <?php
            $config = require __DIR__ . '/../includes/oauth_config.php';
            echo "<strong>Google Client ID:</strong> " . $config['google']['client_id'] . "<br>";
            echo "<strong>Google Redirect URI:</strong> " . $config['google']['redirect_uri'] . "<br>";
            echo "<strong>State Verification:</strong> " . ($config['enable_state_verification'] ? 'Enabled' : 'Disabled') . "<br>";
            ?>
        </div>
        
        <div class="session-info">
            <h2>Debug Info:</h2>
            <?php
            // Check if log file exists and is writable
            $log_file = __DIR__ . '/oauth_debug.log';
            if (file_exists($log_file)) {
                echo "<p>✅ Log file exists: $log_file</p>";
                echo "<p>Writable: " . (is_writable($log_file) ? '✅ Yes' : '❌ No') . "</p>";
                echo "<p>Size: " . filesize($log_file) . " bytes</p>";
                
                if (filesize($log_file) > 0) {
                    echo "<h3>Last 20 lines from log:</h3>";
                    echo "<pre>";
                    $lines = file($log_file);
                    echo htmlspecialchars(implode('', array_slice($lines, -20)));
                    echo "</pre>";
                }
            } else {
                echo "<p>❌ Log file doesn't exist yet</p>";
            }
            ?>
        </div>
        
        <a href="../pages/auth/signin.php" class="button">Back to Sign In</a>
        <a href="../api/view_logs.php" class="button">View Full Logs</a>
    </div>
</body>
</html>
