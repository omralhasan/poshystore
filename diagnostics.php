<?php
/**
 * System Diagnostics - Check if everything is working
 */
session_start();
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';

$user_id = getCurrentUserId();
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostics - Poshy Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .test-card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 System Diagnostics</h1>
        
        <div class="test-card">
            <h3>1. User Authentication</h3>
            <?php if ($user_id): ?>
                <p class="status-ok">✅ User is logged in (ID: <?= $user_id ?>)</p>
            <?php else: ?>
                <p class="status-error">❌ No user logged in</p>
                <p><a href="signin.php" class="btn btn-primary">Log In</a></p>
            <?php endif; ?>
        </div>
        
        <div class="test-card">
            <h3>2. Database Connection</h3>
            <?php if ($conn && !$conn->connect_error): ?>
                <p class="status-ok">✅ Database connected</p>
            <?php else: ?>
                <p class="status-error">❌ Database connection failed</p>
            <?php endif; ?>
        </div>
        
        <div class="test-card">
            <h3>3. Points & Wallet System</h3>
            <?php
            // Check if tables exist
            $tables_to_check = ['points_transactions', 'wallet_transactions', 'points_settings'];
            $missing_tables = [];
            
            foreach ($tables_to_check as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows == 0) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)):
            ?>
                <p class="status-ok">✅ All required tables exist</p>
                
                <?php if ($user_id): ?>
                    <?php
                    // Check user columns
                    $user_sql = "SELECT points, wallet_balance FROM users WHERE id = ?";
                    $stmt = $conn->prepare($user_sql);
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    
                    if ($user_row = $user_result->fetch_assoc()):
                    ?>
                        <p class="status-ok">✅ User has points column: <?= number_format($user_row['points']) ?> points</p>
                        <p class="status-ok">✅ User has wallet_balance: <?= number_format($user_row['wallet_balance'], 3) ?> JOD</p>
                    <?php else: ?>
                        <p class="status-error">❌ Could not read user data</p>
                    <?php endif; ?>
                    
                    <?php
                    // Check if points_wallet_handler.php exists
                    if (file_exists(__DIR__ . '/includes/points_wallet_handler.php')):
                    ?>
                        <p class="status-ok">✅ points_wallet_handler.php exists</p>
                    <?php else: ?>
                        <p class="status-error">❌ points_wallet_handler.php not found</p>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <p class="status-error">❌ Missing tables: <?= implode(', ', $missing_tables) ?></p>
                <p>Run: <code>mysql -u poshy_user -p poshy_lifestyle < sql/setup_points_wallet.sql</code></p>
            <?php endif; ?>
        </div>
        
        <div class="test-card">
            <h3>4. File Checks</h3>
            <?php
            $files_to_check = [
                'pages/shop/points_wallet.php' => 'Rewards Dashboard',
                'pages/shop/checkout_page.php' => 'Checkout Page',
                'api/convert_points.php' => 'Convert Points API',
                'includes/points_wallet_handler.php' => 'Points Handler'
            ];
            
            foreach ($files_to_check as $file => $name):
                if (file_exists(__DIR__ . '/' . $file)):
            ?>
                <p class="status-ok">✅ <?= $name ?> exists</p>
            <?php else: ?>
                <p class="status-error">❌ <?= $name ?> not found (<?= $file ?>)</p>
            <?php
                endif;
            endforeach;
            ?>
        </div>
        
        <div class="test-card">
            <h3>5. Quick Links</h3>
            <a href="index.php" class="btn btn-primary me-2">Home Page</a>
            <a href="pages/shop/points_wallet.php" class="btn btn-success me-2">Rewards Dashboard</a>
            <a href="pages/shop/checkout_page.php" class="btn btn-warning me-2">Checkout Page</a>
            <a href="test_points_wallet.php" class="btn btn-info">Test Points System</a>
        </div>
        
        <?php if ($user_id): ?>
        <div class="test-card">
            <h3>6. Your Current Status</h3>
            <?php
            $stmt = $conn->prepare("SELECT email, points, wallet_balance FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            ?>
            <table class="table">
                <tr>
                    <th>Email:</th>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                </tr>
                <tr>
                    <th>Points:</th>
                    <td><strong><?= number_format($user['points']) ?></strong></td>
                </tr>
                <tr>
                    <th>Wallet Balance:</th>
                    <td><strong><?= number_format($user['wallet_balance'], 3) ?> JOD</strong></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="test-card">
            <h3>7. PHP Info</h3>
            <p>PHP Version: <strong><?= phpversion() ?></strong></p>
            <p>Session ID: <strong><?= session_id() ?></strong></p>
        </div>
    </div>
</body>
</html>
