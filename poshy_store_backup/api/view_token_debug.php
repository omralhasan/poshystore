<?php
/**
 * View Token Exchange Debug Information
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Token Exchange Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        pre { background: #252526; padding: 15px; border-left: 3px solid #007acc; overflow-x: auto; white-space: pre-wrap; }
        .button { display: inline-block; padding: 10px 20px; background: #007acc; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        .error { border-left-color: #f48771; }
        .success { border-left-color: #4ec9b0; }
        .warning { background: #5a5a00; padding: 10px; border-radius: 4px; margin: 10px 0; color: #ffff00; }
    </style>
</head>
<body>
    <h1>🐛 Token Exchange Debug Information</h1>
    
    <a href="view_token_debug.php" class="button">🔄 Refresh</a>
    <a href="../pages/auth/signin.php" class="button">← Sign In</a>
    <a href="../pages/auth/oauth_diagnostic.php" class="button">📊 Diagnostic</a>
    
    <?php
    $debug_file = __DIR__ . '/oauth_token_debug.txt';
    
    if (file_exists($debug_file)) {
        $content = file_get_contents($debug_file);
        
        if (!empty($content)) {
            echo "<h2>Latest Token Exchange Attempts:</h2>";
            echo "<pre>$content</pre>";
            
            // Show only last entry as well
            $entries = explode(str_repeat("-", 80), $content);
            $last_entry = array_filter($entries);
            $last_entry = array_pop($last_entry);
            
            if ($last_entry) {
                echo "<h2>🔎 Most Recent Attempt:</h2>";
                echo "<pre class='error'>$last_entry</pre>";
            }
        } else {
            echo "<div class='warning'>⚠️ Debug file is empty. Try signing in with Google to generate debug info.</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Debug file doesn't exist yet. Try signing in with Google first.</div>";
    }
    ?>
    
    <h2>📝 Instructions:</h2>
    <pre>
1. Click "Sign In" button above
2. Click "Sign in with Google"
3. Complete the Google authorization
4. Come back here and click "Refresh"
5. You'll see the exact error from Google
    </pre>
</body>
</html>
