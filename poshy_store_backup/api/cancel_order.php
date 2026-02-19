<?php
/**
 * Cancel Order API
 * Allows users to cancel their pending orders
 */

require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../pages/shop/checkout.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to cancel an order'
    ]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid order ID'
    ]);
    exit;
}

// Verify order belongs to user and is pending
global $conn;
$check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    echo json_encode([
        'success' => false,
        'error' => 'Order not found or access denied'
    ]);
    exit;
}

$order = $result->fetch_assoc();
$check_stmt->close();

// Check if order can be cancelled (only pending orders)
if ($order['status'] !== 'pending') {
    echo json_encode([
        'success' => false,
        'error' => 'Only pending orders can be cancelled'
    ]);
    exit;
}

// Update order status to cancelled
$cancel_result = updateOrderStatus($order_id, 'cancelled');

echo json_encode($cancel_result);
