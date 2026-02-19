<?php
/**
 * Simple log viewer for OAuth debugging
 * Shows the last 50 error_log entries
 */

// Start session BEFORE any output
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth Debug Logs</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        .log-entry { padding: 5px; margin: 2px 0; border-left: 3px solid #007acc; background: #252526; }
        .error { border-left-color: #f48771; }
        .success { border-left-color: #4ec9b0; }
        .button { display: inline-block; padding: 10px 20px; background: #007acc; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        .warning { background: #5a5a00; padding: 10px; border-radius: 4px; margin: 10px 0; color: #ffff00; }
    </style>
</head>
<body>
    <h1>OAuth Debug Logs</h1>
    <a href="view_logs.php" class="button">Refresh</a>
    <a href="../pages/auth/signin.php" class="button">Go to Sign In</a>
    <a href="../tests/test_session.php" class="button">Test Session</a>
    
    <?php
    // Try to read PHP error log from common locations
    $log_files = [
        '/var/log/apache2/error.log',
        '/var/log/php_errors.log',
        '/var/log/httpd/error_log',
        ini_get('error_log'),
        '/tmp/php_errors.log'
    ];
    
    $log_content = '';
    $log_file_used = '';
    
    foreach ($log_files as $log_file) {
        if ($log_file && file_exists($log_file) && is_readable($log_file)) {
            $log_content = shell_exec("tail -100 " . escapeshellarg($log_file) . " 2>&1");
            if ($log_content) {
                $log_file_used = $log_file;
                break;
            }
        }
    }
    
    // If no log file found, try to read from PHP's error_log
    if (empty($log_content)) {
        // Enable error logging to a custom file
        $custom_log = __DIR__ . '/oauth_debug.log';
        echo "<div class='warning'>⚠ System error logs not accessible. Errors will be logged to: <strong>$custom_log</strong></div>";
        echo "<div class='warning'>Please try signing in with Google/Apple again, then refresh this page.</div>";
        
        if (file_exists($custom_log)) {
            $log_content = file_get_contents($custom_log);
            $log_file_used = $custom_log;
        }
    }
    
    if ($log_file_used) {
        echo "<p>Reading from: <strong>$log_file_used</strong></p>";
    }
    
    if ($log_content) {
        $lines = explode("\n", $log_content);
        $lines = array_reverse(array_filter($lines)); // Newest first
        
        echo "<h2>Recent Log Entries (" . count($lines) . " lines)</h2>";
        
        foreach (array_slice($lines, 0, 100) as $line) {
            $class = 'log-entry';
            if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
                $class .= ' error';
            } elseif (stripos($line, 'success') !== false || stripos($line, 'passed') !== false) {
                $class .= ' success';
            }
            
            echo "<div class='$class'>" . htmlspecialchars($line) . "</div>";
        }
    } else {
        echo "<div class='warning'>No logs found. Logs will appear here after you try to sign in.</div>";
        echo "<h2>Alternative: Check browser console</h2>";
        echo "<p>Press F12 in your browser and check the Console and Network tabs for errors.</p>";
    }
    ?>
    
    <h2>Session Information</h2>
    <div class="log-entry">
        <?php
        echo "<strong>Session ID:</strong> " . session_id() . "<br>";
        echo "<strong>OAuth Provider:</strong> " . ($_SESSION['oauth_provider'] ?? 'Not set') . "<br>";
        echo "<strong>OAuth States:</strong><pre>" . print_r($_SESSION['oauth_states'] ?? [], true) . "</pre>";
        echo "<strong>Session Data:</strong><pre>" . print_r($_SESSION, true) . "</pre>";
        ?>
    </div>
</body>
</html>
