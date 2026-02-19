<?php
/**
 * API Endpoint: Add to Cart
 * Accepts POST requests with product_id and quantity
 */

header('Content-Type: application/json');

// Load required files (auth_functions.php starts the session)
require_once __DIR__ . '/../includes/cart_handler.php';

// Validate input
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
    exit;
}

$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

if ($product_id === false || $quantity === false || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid parameters'
    ]);
    exit;
}

// Add to cart
$result = addToCart($product_id, $quantity);
echo json_encode($result);
?>
