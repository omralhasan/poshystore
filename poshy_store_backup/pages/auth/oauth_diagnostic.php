<?php
/**
 * OAuth Diagnostic Tool
 * Shows exactly what's being sent to Google
 */
session_start();

require_once __DIR__ . '/../../includes/oauth_functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; font-size: 14px; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; margin-top: 30px; }
        .info-box { background: #252526; padding: 15px; border-left: 3px solid #007acc; margin: 10px 0; }
        .success { border-left-color: #4ec9b0; }
        .warning { border-left-color: #ce9178; }
        .error { border-left-color: #f48771; }
        .button { display: inline-block; padding: 10px 20px; background: #007acc; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        pre { margin: 5px 0; }
        code { color: #ce9178; }
    </style>
</head>
<body>
    <h1>🔍 OAuth Configuration Diagnostic</h1>
    
    <?php
    $config = require __DIR__ . '/../../includes/oauth_config.php';
    
    // Generate Google OAuth URL
    $google_url = getOAuthURL('google');
    
    // Parse the URL to show what's being sent
    $parsed = parse_url($google_url);
    parse_str($parsed['query'], $params);
    ?>
    
    <h2>📋 Google OAuth Configuration</h2>
    <div class="info-box success">
        <strong>Client ID:</strong><br>
        <code><?= htmlspecialchars($config['google']['client_id']) ?></code>
    </div>
    
    <div class="info-box success">
        <strong>Redirect URI (in config):</strong><br>
        <code><?= htmlspecialchars($config['google']['redirect_uri']) ?></code>
    </div>
    
    <div class="info-box warning">
        <strong>⚠️ IMPORTANT: This redirect URI must be EXACTLY configured in Google Cloud Console</strong><br>
        Go to: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color: #569cd6;">https://console.cloud.google.com/apis/credentials</a><br>
        Click your OAuth 2.0 Client ID → Authorized redirect URIs → Make sure you have:<br>
        <code><?= htmlspecialchars($config['google']['redirect_uri']) ?></code>
    </div>
    
    <h2>🔗 Authorization Request Parameters</h2>
    <div class="info-box">
        <strong>What's being sent to Google:</strong>
        <pre><?php
        foreach ($params as $key => $value) {
            echo htmlspecialchars("$key = $value\n");
        }
        ?></pre>
    </div>
    
    <div class="info-box">
        <strong>Full Authorization URL:</strong><br>
        <textarea readonly style="width: 100%; height: 100px; background: #1e1e1e; color: #d4d4d4; border: 1px solid #007acc; padding: 10px; font-family: monospace;"><?= htmlspecialchars($google_url) ?></textarea>
    </div>
    
    <h2>🧪 Token Exchange Configuration</h2>
    <div class="info-box">
        <strong>Token URL:</strong> <code><?= htmlspecialchars($config['google']['token_url']) ?></code><br>
        <strong>Grant Type:</strong> <code>authorization_code</code><br>
        <strong>Redirect URI (will be sent):</strong> <code><?= htmlspecialchars($config['google']['redirect_uri']) ?></code>
    </div>
    
    <h2>✅ Checklist</h2>
    <div class="info-box warning">
        <strong>In Google Cloud Console, verify:</strong><br>
        ☐ OAuth 2.0 Client ID is created<br>
        ☐ Authorized redirect URIs includes: <code><?= htmlspecialchars($config['google']['redirect_uri']) ?></code><br>
        ☐ No typos or extra spaces in the redirect URI<br>
        ☐ Client ID and Client Secret match your configuration<br>
        ☐ OAuth consent screen is configured<br>
        ☐ Your email is added as a test user (if app is in testing mode)
    </div>
    
    <h2>🔄 Test Authentication Flow</h2>
    <div class="info-box">
        <p>Click the button below to test the OAuth flow. After clicking:</p>
        <ol>
            <li>You'll be redirected to Google</li>
            <li>Sign in with your Google account</li>
            <li>You'll be redirected back to oauth_callback.php</li>
            <li>Check the logs or error message to see what happened</li>
        </ol>
        <a href="<?= htmlspecialchars($google_url) ?>" class="button">🚀 Test Google Sign-In</a>
        <a href="test_oauth_error.php" class="button">📊 View Last Error</a>
        <a href="view_logs.php" class="button">📜 View Logs</a>
    </div>
    
    <h2>🐛 Common Issues</h2>
    <div class="info-box error">
        <strong>"redirect_uri_mismatch" error:</strong><br>
        → The redirect URI in Google Cloud Console doesn't exactly match <code><?= htmlspecialchars($config['google']['redirect_uri']) ?></code><br><br>
        
        <strong>"invalid_client" error:</strong><br>
        → Wrong Client ID or Client Secret<br>
        → OAuth client was deleted in Google Cloud Console<br><br>
        
        <strong>"access_denied" error:</strong><br>
        → User canceled the authorization<br>
        → User's email is not added as a test user (if app is in testing mode)<br><br>
        
        <strong>"Failed to get access token" error:</strong><br>
        → Check the detailed error in <a href="view_logs.php" style="color: #569cd6;">view_logs.php</a><br>
        → Usually caused by redirect_uri mismatch during token exchange
    </div>
    
    <a href="signin.php" class="button">← Back to Sign In</a>
</body>
</html>
