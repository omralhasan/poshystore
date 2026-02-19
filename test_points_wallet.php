<?php
/**
 * Points & Wallet System Test Page
 * For testing and verifying the points/wallet functionality
 */

session_start();
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/points_wallet_handler.php';

// Check if user is logged in
$user_id = getCurrentUserId();
if (!$user_id) {
    die("Please <a href='signin.php'>log in</a> to test the points system.");
}

// Get settings
$settings = getPointsSettings();
$user_info = getUserPointsAndWallet($user_id);

// Handle test actions
$test_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'award_test_points':
                $test_amount = 100; // Test purchase amount
                $test_order_id = 99999; // Fake order ID for testing
                $result = awardPurchasePoints($user_id, $test_amount, $test_order_id);
                $test_result = json_encode($result, JSON_PRETTY_PRINT);
                break;
                
            case 'convert_test_points':
                $points = intval($_POST['points'] ?? 100);
                $result = convertPointsToWallet($user_id, $points);
                $test_result = json_encode($result, JSON_PRETTY_PRINT);
                break;
        }
        
        // Refresh user info after action
        $user_info = getUserPointsAndWallet($user_id);
    }
}

$points_history = getPointsHistory($user_id, 10);
$wallet_history = getWalletHistory($user_id, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points & Wallet System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .test-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 15px 0;
        }
        .result-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre;
            overflow-x: auto;
            max-height: 300px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🧪 Points & Wallet System Test Page</h1>
        
        <!-- System Status -->
        <div class="test-card">
            <h3>System Status</h3>
            <div class="status-box">
                <strong>✅ System is <?= $settings['enable_points_system'] == '1' ? 'ENABLED' : 'DISABLED' ?></strong>
            </div>
            
            <table class="table table-sm">
                <tr>
                    <th>Points per JOD:</th>
                    <td><?= $settings['points_per_jod'] ?></td>
                </tr>
                <tr>
                    <th>Points to JOD rate:</th>
                    <td><?= $settings['points_to_jod_rate'] ?> points = 1 JOD</td>
                </tr>
                <tr>
                    <th>Minimum conversion:</th>
                    <td><?= $settings['minimum_conversion_points'] ?> points</td>
                </tr>
            </table>
        </div>
        
        <!-- Current User Balance -->
        <div class="test-card">
            <h3>Your Current Balance</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5>Points</h5>
                            <h2><?= number_format($user_info['points']) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>Wallet Balance</h5>
                            <h2><?= $user_info['wallet_balance_formatted'] ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Actions -->
        <div class="test-card">
            <h3>Test Actions</h3>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Test Point Earning</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="award_test_points">
                        <button type="submit" class="btn btn-primary">
                            Award Test Points (100 JOD purchase = <?= 100 * $settings['points_per_jod'] ?> points)
                        </button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <h5>Test Point Conversion</h5>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="convert_test_points">
                        <input type="number" 
                               name="points" 
                               class="form-control" 
                               value="<?= $settings['minimum_conversion_points'] ?>"
                               min="<?= $settings['minimum_conversion_points'] ?>"
                               max="<?= $user_info['points'] ?>"
                               required>
                        <button type="submit" class="btn btn-success">Convert Points</button>
                    </form>
                </div>
            </div>
            
            <?php if ($test_result): ?>
                <div class="alert alert-info">
                    <strong>Last Test Result:</strong>
                    <div class="result-box"><?= htmlspecialchars($test_result) ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Transactions -->
        <div class="test-card">
            <h3>Recent Points Transactions</h3>
            <?php if (empty($points_history)): ?>
                <p class="text-muted">No transactions yet</p>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Change</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($points_history as $trans): ?>
                            <tr>
                                <td><?= $trans['transaction_type'] ?></td>
                                <td class="<?= $trans['points_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $trans['points_change'] > 0 ? '+' : '' ?><?= number_format($trans['points_change']) ?>
                                </td>
                                <td><?= number_format($trans['points_before']) ?></td>
                                <td><?= number_format($trans['points_after']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($trans['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="test-card">
            <h3>Recent Wallet Transactions</h3>
            <?php if (empty($wallet_history)): ?>
                <p class="text-muted">No transactions yet</p>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wallet_history as $trans): ?>
                            <tr>
                                <td><?= $trans['transaction_type'] ?></td>
                                <td class="<?= $trans['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $trans['amount_formatted'] ?>
                                </td>
                                <td><?= $trans['balance_before_formatted'] ?></td>
                                <td><?= $trans['balance_after_formatted'] ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($trans['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links -->
        <div class="test-card">
            <h3>Quick Links</h3>
            <div class="d-flex gap-2">
                <a href="pages/shop/points_wallet.php" class="btn btn-primary">View Rewards Dashboard</a>
                <a href="pages/shop/shop.php" class="btn btn-success">Go Shopping</a>
                <a href="index.php" class="btn btn-secondary">Home</a>
            </div>
        </div>
        
        <!-- Database Queries -->
        <div class="test-card">
            <h3>📊 Useful Database Queries</h3>
            <div class="mb-3">
                <h6>Check all users' balances:</h6>
                <code>SELECT id, email, points, wallet_balance FROM users;</code>
            </div>
            <div class="mb-3">
                <h6>View system settings:</h6>
                <code>SELECT * FROM points_settings;</code>
            </div>
            <div class="mb-3">
                <h6>Recent points transactions:</h6>
                <code>SELECT * FROM points_transactions ORDER BY created_at DESC LIMIT 10;</code>
            </div>
            <div class="mb-3">
                <h6>Recent wallet transactions:</h6>
                <code>SELECT * FROM wallet_transactions ORDER BY created_at DESC LIMIT 10;</code>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
