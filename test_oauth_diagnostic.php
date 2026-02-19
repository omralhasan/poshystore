<?php
/**
 * OAuth Diagnostic Tool
 * Tests Google and Facebook OAuth configuration
 */
session_start();
require_once __DIR__ . '/includes/oauth_functions.php';
require_once __DIR__ . '/includes/oauth_config.php';

echo "<!DOCTYPE html><html><head><title>OAuth Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo "pre{background:#252526;padding:15px;border-left:3px solid #4ec9b0;margin:10px 0;}";
echo ".success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#ce9178;}";
echo "h1{color:#4ec9b0;} h3{color:#569cd6;} a{color:#569cd6;text-decoration:none;}";
echo "a:hover{text-decoration:underline;} .btn{display:inline-block;padding:10px 20px;background:#007acc;color:white;border-radius:4px;margin:5px;}";
echo ".btn:hover{background:#005a9e;}</style></head><body>";

echo "<h1>🔍 OAuth Configuration Diagnostic</h1>";

// Load config
$config = include __DIR__ . '/includes/oauth_config.php';

// Test Google Configuration
echo "<h3>1. Google OAuth Configuration:</h3>";
echo "<pre>";
echo "<span class='warning'>Client ID:</span> " . substr($config['google']['client_id'], 0, 20) . "..." . substr($config['google']['client_id'], -10) . "\n";
echo "<span class='warning'>Client Secret:</span> " . (strlen($config['google']['client_secret']) > 0 ? "Set (length: " . strlen($config['google']['client_secret']) . ")" : "<span class='error'>NOT SET</span>") . "\n";
echo "<span class='warning'>Redirect URI:</span> " . $config['google']['redirect_uri'] . "\n";
echo "<span class='warning'>Auth URL:</span> " . $config['google']['auth_url'] . "\n";
echo "</pre>";

// Test Facebook Configuration
echo "<h3>2. Facebook OAuth Configuration:</h3>";
echo "<pre>";
echo "<span class='warning'>App ID:</span> " . $config['facebook']['app_id'] . "\n";
echo "<span class='warning'>App Secret:</span> " . (strlen($config['facebook']['app_secret']) > 0 ? "Set (length: " . strlen($config['facebook']['app_secret']) . ")" : "<span class='error'>NOT SET</span>") . "\n";
echo "<span class='warning'>Redirect URI:</span> " . $config['facebook']['redirect_uri'] . "\n";
echo "<span class='warning'>Auth URL:</span> " . $config['facebook']['auth_url'] . "\n";
echo "</pre>";

// Test OAuth URL Generation
echo "<h3>3. Test OAuth URL Generation:</h3>";
echo "<pre>";
$google_url = getOAuthURL('google');
$facebook_url = getOAuthURL('facebook');

if ($google_url) {
    echo "<span class='success'>✅ Google URL generated successfully</span>\n";
    echo "URL: " . htmlspecialchars(substr($google_url, 0, 100)) . "...\n";
} else {
    echo "<span class='error'>❌ Failed to generate Google URL</span>\n";
}

echo "\n";

if ($facebook_url) {
    echo "<span class='success'>✅ Facebook URL generated successfully</span>\n";
    echo "URL: " . htmlspecialchars(substr($facebook_url, 0, 100)) . "...\n";
} else {
    echo "<span class='error'>❌ Failed to generate Facebook URL</span>\n";
}
echo "</pre>";

// Check redirect URI accessibility
echo "<h3>4. Redirect URI Check:</h3>";
echo "<pre>";
$callback_url = "http://localhost/poshy_store/pages/auth/oauth_callback.php";
echo "Callback URL: $callback_url\n";

// Test if callback file exists
if (file_exists(__DIR__ . '/pages/auth/oauth_callback.php')) {
    echo "<span class='success'>✅ Callback file exists</span>\n";
} else {
    echo "<span class='error'>❌ Callback file NOT FOUND</span>\n";
}
echo "</pre>";

// Common Issues and Solutions
echo "<h3>5. Common Issues:</h3>";
echo "<pre>";
echo "<span class='warning'>Why OAuth buttons might not work:</span>\n\n";
echo "1. <span class='error'>OAuth Provider Not Configured:</span>\n";
echo "   - Google/Facebook apps must be created in their developer consoles\n";
echo "   - Redirect URIs must be added to allowed redirects in console\n";
echo "   - For localhost testing, use: http://localhost/poshy_store/pages/auth/oauth_callback.php\n\n";

echo "2. <span class='error'>Domain Restrictions:</span>\n";
echo "   - OAuth providers require valid domains or localhost\n";
echo "   - Ensure redirect URI in code matches console configuration exactly\n\n";

echo "3. <span class='error'>Client Credentials Invalid:</span>\n";
echo "   - Client ID/Secret may be incorrect or expired\n";
echo "   - App may be in development mode restricting access\n\n";

echo "4. <span class='error'>Session/Cookie Issues:</span>\n";
echo "   - State token verification may fail if cookies disabled\n";
echo "   - SameSite cookie settings may block OAuth flow\n";
echo "</pre>";

// Session Info
echo "<h3>6. Current Session State:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "OAuth States: " . (isset($_SESSION['oauth_states']) ? count($_SESSION['oauth_states']) . " stored" : "None") . "\n";
echo "</pre>";

// Test Buttons
echo "<h3>7. Test OAuth Flow:</h3>";
echo "<div style='padding:20px;background:#252526;border-radius:8px;'>";
echo "<p style='color:#ce9178;'>Click these buttons to test the OAuth flow:</p>";

if ($google_url) {
    echo "<a href='" . htmlspecialchars($google_url) . "' class='btn' style='background:#4285F4;'>🔵 Sign in with Google</a>";
}

if ($facebook_url) {
    echo "<a href='" . htmlspecialchars($facebook_url) . "' class='btn' style='background:#1877F2;'>📘 Sign in with Facebook</a>";
}

echo "<br><br><p style='color:#999;font-size:12px;'>Note: For testing, you may need to configure OAuth apps in Google Console and Facebook Developer Console</p>";
echo "</div>";

// Setup Instructions
echo "<h3>8. Setup Instructions:</h3>";
echo "<pre>";
echo "<span class='success'>Google OAuth Setup:</span>\n";
echo "1. Go to: https://console.cloud.google.com/\n";
echo "2. Create a project or select existing one\n";
echo "3. Enable Google+ API\n";
echo "4. Create OAuth 2.0 credentials (Web application)\n";
echo "5. Add redirect URI: http://localhost/poshy_store/pages/auth/oauth_callback.php\n";
echo "6. Update client_id and client_secret in includes/oauth_config.php\n\n";

echo "<span class='success'>Facebook OAuth Setup:</span>\n";
echo "1. Go to: https://developers.facebook.com/\n";
echo "2. Create an app or select existing one\n";
echo "3. Add Facebook Login product\n";
echo "4. In Facebook Login settings, add redirect URI:\n";
echo "   http://localhost/poshy_store/pages/auth/oauth_callback.php\n";
echo "5. Update app_id and app_secret in includes/oauth_config.php\n";
echo "</pre>";

echo "<hr><p><a href='pages/auth/signin.php'>← Back to Sign In</a> | ";
echo "<a href='docs/OAUTH_SETUP.md'>📖 Full OAuth Documentation</a></p>";

echo "</body></html>";
?>
