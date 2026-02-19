<?php
/**
 * Coupon Validation and Application API
 */

// Start output buffering to prevent any accidental output
ob_start();

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Clear any output that might have been generated
ob_end_clean();

header('Content-Type: application/json');

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login to apply coupon']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'apply_coupon') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $cart_total = floatval($_POST['cart_total'] ?? 0);
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a coupon code']);
        exit();
    }
    
    // Validate coupon
    $sql = "SELECT * FROM coupons WHERE code = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid or inactive coupon code']);
        exit();
    }
    
    $coupon = $result->fetch_assoc();
    $stmt->close();
    
    // Get current database time for accurate timezone comparison
    $current_time_result = $conn->query("SELECT NOW() as current_db_time");
    $current_time_row = $current_time_result->fetch_assoc();
    $current_db_time = strtotime($current_time_row['current_db_time']);
    
    // Check if coupon is expired
    if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < $current_db_time) {
        echo json_encode(['success' => false, 'error' => 'This coupon has expired']);
        exit();
    }
    
    // Check if coupon is not yet valid
    if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > $current_db_time) {
        echo json_encode(['success' => false, 'error' => 'This coupon is not valid yet']);
        exit();
    }
    
    // Check minimum purchase requirement
    if ($cart_total < $coupon['min_purchase']) {
        $min_formatted = formatJOD($coupon['min_purchase']);
        echo json_encode([
            'success' => false, 
            'error' => "Minimum purchase of {$min_formatted} required"
        ]);
        exit();
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] && $coupon['times_used'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'error' => 'Coupon usage limit reached']);
        exit();
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($cart_total * $coupon['discount_value']) / 100;
        
        // Apply max discount if set
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        // Fixed discount
        $discount = $coupon['discount_value'];
    }
    
    // Ensure discount doesn't exceed cart total
    $discount = min($discount, $cart_total);
    
    $new_total = max(0, $cart_total - $discount);
    
    // Store coupon in session
    $_SESSION['applied_coupon'] = [
        'code' => $coupon['code'],
        'discount' => $discount,
        'coupon_id' => $coupon['id']
    ];
    
    echo json_encode([
        'success' => true,
        'discount' => formatJOD($discount),
        'discount_raw' => $discount,
        'new_total' => formatJOD($new_total),
        'new_total_raw' => $new_total,
        'message' => 'Coupon applied successfully!'
    ]);
    exit();
}

if ($action === 'remove_coupon') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Coupon removed']);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
