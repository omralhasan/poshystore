<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Test - Poshy Store</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .icon {
            font-size: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px 10px 10px 0;
        }
        .button:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .check { color: #28a745; }
        .cross { color: #dc3545; }
        ul {
            line-height: 1.8;
        }
        .highlight {
            background: yellow;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔐 OAuth Implementation Test</h1>
        <p style="text-align: center; color: #666;">Poshy Lifestyle Social Login</p>
    </div>

    <div class="card">
        <h2>✅ Implementation Status</h2>
        
        <?php
        // Check if files exist
        $files_to_check = [
            __DIR__ . '/../../includes/oauth_config.php' => 'OAuth Configuration',
            __DIR__ . '/../../includes/oauth_functions.php' => 'OAuth Functions',
            __DIR__ . '/oauth_callback.php' => 'OAuth Callback Handler',
            __DIR__ . '/../../sql/add_oauth_support.sql' => 'Database Migration SQL'
        ];
        
        echo '<table>';
        echo '<tr><th>File</th><th>Status</th></tr>';
        foreach ($files_to_check as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $icon = $exists ? '✅' : '❌';
            $status = $exists ? 'Found' : 'Missing';
            echo "<tr><td>$description</td><td>$icon $status</td></tr>";
        }
        echo '</table>';
        ?>
    </div>

    <div class="card">
        <h2>🗄️ Database Status</h2>
        
        <?php
        require_once __DIR__ . '/../../includes/db_connect.php';
        
        // Check OAuth columns
        $result = $conn->query("DESCRIBE users");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $required_columns = ['oauth_provider', 'oauth_id', 'profile_picture'];
        $db_ready = true;
        
        echo '<table>';
        echo '<tr><th>Column</th><th>Status</th></tr>';
        foreach ($required_columns as $col) {
            $exists = in_array($col, $columns);
            $icon = $exists ? '✅' : '❌';
            $status = $exists ? 'Present' : 'Missing';
            if (!$exists) $db_ready = false;
            echo "<tr><td>$col</td><td>$icon $status</td></tr>";
        }
        echo '</table>';
        
        if ($db_ready) {
            echo '<div class="status success"><span class="icon">✅</span> Database is ready for OAuth!</div>';
        } else {
            echo '<div class="status warning"><span class="icon">⚠️</span> Run the migration: <code>mysql -u poshy_user -p\'Poshy2026\' poshy_lifestyle < add_oauth_support.sql</code></div>';
        }
        ?>
    </div>

    <div class="card">
        <h2>🔧 OAuth Configuration</h2>
        
        <?php
        $config = require __DIR__ . '/../../includes/oauth_config.php';
        
        $providers = ['google', 'facebook', 'apple'];
        $configured = [];
        
        echo '<table>';
        echo '<tr><th>Provider</th><th>Status</th><th>Note</th></tr>';
        
        foreach ($providers as $provider) {
            $is_configured = false;
            $note = 'Not configured';
            
            if (isset($config[$provider])) {
                if ($provider === 'google') {
                    $is_configured = !str_contains($config['google']['client_id'], 'YOUR_');
                    $note = $is_configured ? 'Configured' : 'Needs client_id';
                } elseif ($provider === 'facebook') {
                    $is_configured = !str_contains($config['facebook']['app_id'], 'YOUR_');
                    $note = $is_configured ? 'Configured' : 'Needs app_id';
                } elseif ($provider === 'apple') {
                    $is_configured = !str_contains($config['apple']['client_id'], 'YOUR_');
                    $note = $is_configured ? 'Configured' : 'Needs credentials';
                }
            }
            
            $icon = $is_configured ? '✅' : '⚠️';
            $configured[] = $is_configured;
            
            echo "<tr><td>" . ucfirst($provider) . "</td><td>$icon</td><td>$note</td></tr>";
        }
        echo '</table>';
        
        if (in_array(true, $configured)) {
            echo '<div class="status success"><span class="icon">✅</span> Some providers are configured!</div>';
        } else {
            echo '<div class="status warning"><span class="icon">⚠️</span> Configure OAuth credentials in oauth_config.php</div>';
        }
        ?>
    </div>

    <div class="card">
        <h2>🎨 UI Test</h2>
        <p>Test the new social login interface:</p>
        <a href="signin.php" class="button">📱 View Sign In Page</a>
        <a href="signup.php" class="button">📝 View Sign Up Page</a>
    </div>

    <div class="card">
        <h2>📋 Next Steps</h2>
        <ul>
            <li><strong>Option 1:</strong> Configure OAuth credentials (see <a href="#" style="color: #667eea;">OAUTH_SETUP.md</a>)</li>
            <li><strong>Option 2:</strong> Test the UI without OAuth (buttons visible but need credentials)</li>
            <li><strong>Option 3:</strong> Continue using email/password login (works perfectly)</li>
        </ul>
        
        <h3 style="margin-top: 25px;">Quick Setup (Google Only)</h3>
        <ol>
            <li>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color: #667eea;">Google Cloud Console</a></li>
            <li>Create a project and get OAuth credentials</li>
            <li>Update <code>oauth_config.php</code> with your credentials</li>
            <li>Add redirect URI: <span class="highlight">http://localhost/poshy_store/oauth_callback.php?provider=google</span></li>
            <li>Test by clicking "Continue with Google" on signin page</li>
        </ol>
    </div>

    <div class="card">
        <h2>📚 Documentation</h2>
        <p>Complete guides available:</p>
        <ul>
            <li><strong>OAUTH_SETUP.md</strong> - Detailed OAuth setup for all providers</li>
            <li><strong>SOCIAL_LOGIN_README.md</strong> - Implementation overview</li>
            <li><strong>VISUAL_GUIDE.md</strong> - UI design reference</li>
        </ul>
        
        <a href="../../index.php" class="button">🏠 Back to Home</a>
    </div>
</body>
</html>
