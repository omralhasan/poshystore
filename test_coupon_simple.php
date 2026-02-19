<?php
/**
 * Simple test script to verify coupon API works
 */

// Simulate a logged-in user session
session_start();
$_SESSION['user_id'] = 1;

// Simulate POST data
$_POST['action'] = 'apply_coupon';
$_POST['code'] = 'POSH';
$_POST['cart_total'] = 100;

// Capture the output
ob_start();
include __DIR__ . '/api/apply_coupon.php';
$response = ob_get_clean();

// Display results
echo "=== Coupon API Test Results ===\n\n";
echo "Request:\n";
echo "  Code: POSH\n";
echo "  Cart Total: 100 JOD\n\n";

echo "Response:\n";
echo $response . "\n\n";

// Try to decode JSON
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON response\n";
    if (isset($data['success'])) {
        if ($data['success']) {
            echo "✓ Coupon applied successfully!\n";
            if (isset($data['discount'])) {
                echo "  Discount: " . $data['discount'] . "\n";
            }
            if (isset($data['new_total'])) {
                echo "  New Total: " . $data['new_total'] . "\n";
            }
        } else {
            echo "✗ Coupon application failed\n";
            echo "  Error: " . ($data['error'] ?? 'Unknown error') . "\n";
        }
    }
} else {
    echo "✗ Invalid JSON response\n";
    echo "  JSON Error: " . json_last_error_msg() . "\n";
}
