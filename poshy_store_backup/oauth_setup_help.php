<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Why OAuth Isn't Working - Posh Store</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 4px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #667eea;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .issue-box {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .solution-box {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
        ul {
            line-height: 1.8;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .warning-text {
            color: #856404;
            font-weight: bold;
        }
        .success-text {
            color: #155724;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Why Google & Facebook Sign-In Isn't Working</h1>
        
        <div class="issue-box">
            <h3>⚠️ Current Status</h3>
            <p><strong>The OAuth buttons are visible and the code is configured, BUT:</strong></p>
            <ul>
                <li>The Google and Facebook OAuth apps are <strong>NOT fully set up</strong> in their developer consoles</li>
                <li>The credentials in <code>oauth_config.php</code> may be placeholders or test credentials</li>
                <li>The redirect URIs need to be registered with Google and Facebook</li>
            </ul>
        </div>

        <h2>🔍 Why It's Not Working</h2>
        
        <div class="error-box">
            <h3>Problem #1: OAuth Apps Not Configured</h3>
            <p>When you click "Continue with Google" or "Continue with Facebook", you're redirected to those services, but:</p>
            <ul>
                <li>Google/Facebook don't recognize the credentials</li>
                <li>The redirect URI isn't authorized</li>
                <li>The app might be in development mode (restricted access)</li>
                <li>You might get errors like "redirect_uri_mismatch" or "app not approved"</li>
            </ul>
        </div>

        <div class="error-box">
            <h3>Problem #2: Localhost Limitations</h3>
            <p>OAuth providers have restrictions:</p>
            <ul>
                <li><strong>Google:</strong> Allows localhost for testing, but needs proper setup</li>
                <li><strong>Facebook:</strong> More restrictive with localhost, may require HTTPS</li>
                <li>Some providers block localhost entirely in production mode</li>
            </ul>
        </div>

        <h2>✅ How to Fix It</h2>

        <div class="solution-box">
            <h3>Option 1: Complete OAuth Setup (Recommended for Production)</h3>
            
            <div class="step">
                <span class="step-number">1</span>
                <strong>Google OAuth Setup:</strong>
                <ul>
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select existing one</li>
                    <li>Enable "Google+ API" or "Google Identity Services"</li>
                    <li>Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"</li>
                    <li>Application type: <strong>Web application</strong></li>
                    <li>Add authorized redirect URI: <code>http://localhost/poshy_store/pages/auth/oauth_callback.php</code></li>
                    <li>Copy your <strong>Client ID</strong> and <strong>Client Secret</strong></li>
                    <li>Update them in <code>includes/oauth_config.php</code></li>
                </ul>
            </div>

            <div class="step">
                <span class="step-number">2</span>
                <strong>Facebook OAuth Setup:</strong>
                <ul>
                    <li>Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                    <li>Create a new app (type: Consumer or Business)</li>
                    <li>Add product: <strong>Facebook Login</strong></li>
                    <li>Go to Facebook Login → Settings</li>
                    <li>Add Valid OAuth Redirect URI: <code>http://localhost/poshy_store/pages/auth/oauth_callback.php</code></li>
                    <li>Copy your <strong>App ID</strong> and <strong>App Secret</strong> from Settings → Basic</li>
                    <li>Update them in <code>includes/oauth_config.php</code></li>
                    <li><strong>Important:</strong> Switch app to "Live" mode when ready (Settings → Basic → App Mode)</li>
                </ul>
            </div>
        </div>

        <div class="info-box">
            <h3>Option 2: Disable OAuth Buttons (Quick Solution)</h3>
            <p>If you don't need social login right now, you can hide the buttons:</p>
            <ol>
                <li>Open <code>pages/auth/signin.php</code></li>
                <li>Find the "Social Login Buttons" section (around line 310)</li>
                <li>Comment out or remove the social login section</li>
                <li>Users will only see email/password login</li>
            </ol>
        </div>

        <h2>🧪 Test OAuth Configuration</h2>
        
        <div class="info-box">
            <p>Use the diagnostic tool to check your OAuth setup:</p>
            <div style="text-align: center; margin: 20px 0;">
                <a href="test_oauth_diagnostic.php" class="btn">🔍 Run OAuth Diagnostic</a>
            </div>
            <p><small>This will show you the current configuration status and test if URLs are generated correctly.</small></p>
        </div>

        <h2>📝 Current Configuration</h2>
        
        <div class="step">
            <p><strong>Redirect URI set in code:</strong></p>
            <code>http://localhost/poshy_store/pages/auth/oauth_callback.php</code>
            
            <p style="margin-top: 15px;"><strong>This EXACT URL must be added to:</strong></p>
            <ul>
                <li>Google Console → Credentials → Authorized redirect URIs</li>
                <li>Facebook App → Facebook Login Settings → Valid OAuth Redirect URIs</li>
            </ul>
        </div>

        <h2>⚡ Quick Reference</h2>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Provider</th>
                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Console URL</th>
                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Status</th>
            </tr>
            <tr>
                <td style="padding: 12px; border: 1px solid #dee2e6;">Google</td>
                <td style="padding: 12px; border: 1px solid #dee2e6;"><a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a></td>
                <td style="padding: 12px; border: 1px solid #dee2e6;"><span class="warning-text">⚠️ Needs Setup</span></td>
            </tr>
            <tr>
                <td style="padding: 12px; border: 1px solid #dee2e6;">Facebook</td>
                <td style="padding: 12px; border: 1px solid #dee2e6;"><a href="https://developers.facebook.com/" target="_blank">developers.facebook.com</a></td>
                <td style="padding: 12px; border: 1px solid #dee2e6;"><span class="warning-text">⚠️ Needs Setup</span></td>
            </tr>
        </table>

        <div class="solution-box">
            <h3>✅ After Setup, Test With:</h3>
            <ul>
                <li>Go to <strong>Sign In</strong> page</li>
                <li>Click "Continue with Google" or "Continue with Facebook"</li>
                <li>You should be redirected to the provider's login page</li>
                <li>After login, you'll be redirected back and automatically signed in</li>
                <li>Your account will be created in the database with OAuth provider info</li>
            </ul>
        </div>

        <div class="footer">
            <a href="pages/auth/signin.php" class="btn">🔐 Go to Sign In</a>
            <a href="test_oauth_diagnostic.php" class="btn btn-secondary">🔍 OAuth Diagnostic</a>
            <a href="docs/OAUTH_SETUP.md" class="btn btn-secondary">📖 Full Documentation</a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h4>💡 Pro Tip:</h4>
            <p>For development/testing, you can create test Google/Facebook accounts to try the OAuth flow even before your app is approved or live. Just make sure to add those test accounts in the respective developer consoles.</p>
        </div>
    </div>
</body>
</html>
