<?php
/**
 * Update Cart Item Quantity by Cart ID
 * Used for cart page quantity adjustments
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/cart_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate input
if (!isset($_POST['cart_id']) || !is_numeric($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid cart ID']);
    exit;
}

if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid quantity']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$new_quantity = (int)$_POST['quantity'];

// Update quantity
$result = updateCartQuantity($cart_id, $new_quantity, $user_id);

if ($result['success']) {
    // Get updated cart totals
    $cart = viewCart($user_id);
    
    // Get updated item details
    $updated_item = null;
    foreach ($cart['cart_items'] as $item) {
        if ($item['cart_id'] == $cart_id) {
            $updated_item = $item;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'new_quantity' => $new_quantity,
        'item_subtotal' => $updated_item['subtotal_formatted'] ?? '0.000 JOD',
        'cart_total' => $cart['total_amount_formatted'] ?? '0.000 JOD',
        'total_items' => $cart['total_items'] ?? 0,
        'at_max_stock' => isset($updated_item) && $new_quantity >= $updated_item['stock']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? 'Failed to update quantity'
    ]);
}
