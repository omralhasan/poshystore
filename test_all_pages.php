<?php
/**
 * Quick Test Script - Run from command line
 * php test_all_pages.php
 */

echo "=== Testing Poshy Store Pages ===\n\n";

// Test 1: Index Page
echo "1. Testing index.php...\n";
ob_start();
try {
    include '/var/www/html/poshy_store/index.php';
    $output = ob_get_clean();
    if (strlen($output) > 1000 && strpos($output, '<!DOCTYPE html>') !== false) {
        echo "   ✅ index.php loads successfully\n";
    } else {
        echo "   ⚠️  index.php loads but may have issues\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "   ❌ index.php error: " . $e->getMessage() . "\n";
}

// Test 2: Points Handler
echo "\n2. Testing points_wallet_handler.php...\n";
try {
    require_once '/var/www/html/poshy_store/includes/points_wallet_handler.php';
    if (function_exists('getUserPointsAndWallet')) {
        echo "   ✅ getUserPointsAndWallet() function exists\n";
    }
    if (function_exists('convertPointsToWallet')) {
        echo "   ✅ convertPointsToWallet() function exists\n";
    }
    if (function_exists('awardPurchasePoints')) {
        echo "   ✅ awardPurchasePoints() function exists\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Database
echo "\n3. Testing database connection...\n";
try {
    require_once '/var/www/html/poshy_store/includes/db_connect.php';
    if ($conn && !$conn->connect_error) {
        echo "   ✅ Database connected\n";
        
        // Check tables
        $tables = ['points_transactions', 'wallet_transactions', 'points_settings'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "   ✅ Table '$table' exists\n";
            } else {
                echo "   ❌ Table '$table' missing\n";
            }
        }
        
        // Check users table columns
        $result = $conn->query("DESCRIBE users");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        if (in_array('points', $columns)) {
            echo "   ✅ 'points' column exists in users table\n";
        } else {
            echo "   ❌ 'points' column missing from users table\n";
        }
        if (in_array('wallet_balance', $columns)) {
            echo "   ✅ 'wallet_balance' column exists in users table\n";
        } else {
            echo "   ❌ 'wallet_balance' column missing from users table\n";
        }
    } else {
        echo "   ❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check files exist
echo "\n4. Checking files...\n";
$files = [
    '/var/www/html/poshy_store/index.php' => 'Home page',
    '/var/www/html/poshy_store/pages/shop/points_wallet.php' => 'Rewards page',
    '/var/www/html/poshy_store/pages/shop/checkout_page.php' => 'Checkout page',
    '/var/www/html/poshy_store/api/convert_points.php' => 'Convert API',
    '/var/www/html/poshy_store/includes/points_wallet_handler.php' => 'Points handler'
];

foreach ($files as $file => $name) {
    if (file_exists($file)) {
        echo "   ✅ $name exists\n";
    } else {
        echo "   ❌ $name missing: $file\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nIf you see ✅ for all tests, everything is working!\n";
echo "If you see ❌, those items need to be fixed.\n";
