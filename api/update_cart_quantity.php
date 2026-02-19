<?php
/**
 * Update Cart Item Quantity
 * Increases or decreases quantity of a product in cart
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
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

if (!isset($_POST['action']) || !in_array($_POST['action'], ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$action = $_POST['action'];

// Get current quantity in cart
$cart_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Product not in cart']);
    exit;
}

$row = $result->fetch_assoc();
$current_quantity = (int)$row['quantity'];
$stmt->close();

// Calculate new quantity
if ($action === 'increase') {
    $new_quantity = $current_quantity + 1;
    
    // Check stock availability
    $stock_sql = "SELECT stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($stock_sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stock_result = $stmt->get_result();
    $stock_row = $stock_result->fetch_assoc();
    $stmt->close();
    
    if ($new_quantity > $stock_row['stock_quantity']) {
        echo json_encode([
            'success' => false, 
            'error' => 'Not enough stock available',
            'max_stock' => $stock_row['stock_quantity']
        ]);
        exit;
    }
    
    // Update quantity
    $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('iii', $new_quantity, $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'action' => 'increased',
        'new_quantity' => $new_quantity
    ]);
    
} else if ($action === 'decrease') {
    $new_quantity = $current_quantity - 1;
    
    if ($new_quantity <= 0) {
        // Remove from cart
        $delete_sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'new_quantity' => 0
        ]);
    } else {
        // Update quantity
        $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('iii', $new_quantity, $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'action' => 'decreased',
            'new_quantity' => $new_quantity
        ]);
    }
}
