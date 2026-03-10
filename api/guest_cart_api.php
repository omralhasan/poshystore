<?php
/**
 * Guest Cart API
 * Endpoints for adding/updating/removing items from guest cart
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/guest_cart_handler.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_SERVER['REQUEST_METHOD'];
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

// If user is logged in, redirect to regular cart API
if (isset($_SESSION['user_id'])) {
    // Use regular cart functions
    require_once __DIR__ . '/../includes/cart_handler.php';
    
    if ($action === 'POST' || $action === 'add') {
        $quantity = intval($_POST['quantity'] ?? 1);
        $result = addToCart($product_id, $quantity);
        echo json_encode($result);
        exit;
    }
}

switch ($action) {
    case 'add':
    case 'POST':
        $quantity = intval($_POST['quantity'] ?? 1);
        $result = guestAddToCart($product_id, $quantity);
        if ($result['success']) {
            $count_info = guestGetCartCount();
            $result['cart_count'] = $count_info['count'] ?? 0;
        }
        echo json_encode($result);
        break;
        
    case 'update':
        $act = $_POST['cart_action'] ?? 'increase';
        $result = guestUpdateCartQuantity($product_id, $act);
        echo json_encode($result);
        break;
        
    case 'remove':
        $result = guestRemoveFromCart($product_id);
        echo json_encode($result);
        break;
        
    case 'view':
        $result = guestViewCart();
        echo json_encode($result);
        break;
        
    case 'count':
        $result = guestGetCartCount();
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
