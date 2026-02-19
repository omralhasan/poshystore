<!DOCTYPE html>
<html>
<head>
    <title>Add to Cart Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .log { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; font-family: monospace; max-height: 400px; overflow-y: auto; }
        .log-line { margin: 2px 0; }
        .error { color: #f48771; }
        .success { color: #89d185; }
        .info { color: #6a9fb5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛒 Add to Cart Test</h1>
        
        <div class="test-section">
            <h3>User Status</h3>
            <?php
            require_once 'includes/auth_functions.php';
            $is_logged_in = isset($_SESSION['user_id']);
            
            if ($is_logged_in) {
                echo '<div class="alert alert-success">✓ Logged in as User ID: ' . $_SESSION['user_id'] . '</div>';
            } else {
                echo '<div class="alert alert-warning">⚠ Not logged in</div>';
                echo '<p><a href="quick_login.php" class="btn btn-primary">Quick Login</a></p>';
            }
            ?>
        </div>
        
        <div class="test-section">
            <h3>Button Test</h3>
            <button id="testBtn" class="btn btn-success btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Test Add to Cart
            </button>
            <button id="clearLog" class="btn btn-secondary ms-2">Clear Log</button>
        </div>
        
        <div class="test-section">
            <h3>API Response Log</h3>
            <div class="log" id="logBox">
                <div class="log-line info">Ready for testing...</div>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Browser Console</h3>
            <p>Open DevTools (F12) and select the "Console" tab to see detailed logs</p>
        </div>
    </div>
    
    <script>
        const logBox = document.getElementById('logBox');
        
        function log(message, type = 'info') {
            const line = document.createElement('div');
            line.className = `log-line ${type}`;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logBox.appendChild(line);
            logBox.scrollTop = logBox.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        // Test button
        document.getElementById('testBtn').addEventListener('click', async function() {
            log('━━━ Test Started ━━━', 'info');
            log('Calling: /api/add_to_cart_api.php', 'info');
            log('Data: product_id=85, quantity=1', 'info');
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
            
            try {
                log('Sending fetch request...', 'info');
                const response = await fetch('api/add_to_cart_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=85&quantity=1'
                });
                
                log(`Response status: ${response.status}`, response.ok ? 'success' : 'error');
                log(`Response ok: ${response.ok}`, 'info');
                
                const text = await response.text();
                log(`Raw response: ${text}`, 'info');
                
                const data = JSON.parse(text);
                log(`Parsed JSON: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
                
                if (data.success) {
                    log('✓ SUCCESS! Product added to cart', 'success');
                } else {
                    log(`✗ Error: ${data.error}`, 'error');
                }
                
            } catch (error) {
                log(`✗ Exception: ${error.message}`, 'error');
                log(`Stack: ${error.stack}`, 'error');
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Test Add to Cart';
        });
        
        document.getElementById('clearLog').addEventListener('click', function() {
            logBox.innerHTML = '';
            log('Logs cleared', 'info');
        });
    </script>
</body>
</html>
