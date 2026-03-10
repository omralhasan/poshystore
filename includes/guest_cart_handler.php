<?php
/**
 * Guest Cart Handler
 * Manages shopping cart for non-logged-in users using session IDs
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/product_manager.php';
require_once __DIR__ . '/language.php';

/**
 * Get the guest session ID (create if not exists)
 */
function getGuestSessionId() {
    if (!isset($_SESSION['guest_cart_id'])) {
        $_SESSION['guest_cart_id'] = session_id();
    }
    return $_SESSION['guest_cart_id'];
}

/**
 * Add product to guest cart
 */
function guestAddToCart($product_id, $quantity = 1, $options = null) {
    global $conn;
    $session_id = getGuestSessionId();
    
    if (!is_numeric($product_id) || $product_id <= 0 || !is_numeric($quantity) || $quantity <= 0) {
        return ['success' => false, 'error' => 'Invalid input'];
    }
    
    // Check product exists and has stock
    $product = getProductById($product_id);
    if (!$product['success']) {
        return ['success' => false, 'error' => 'Product not found'];
    }
    
    if ($product['product']['stock_quantity'] < $quantity) {
        return ['success' => false, 'error' => 'Insufficient stock. Available: ' . $product['product']['stock_quantity']];
    }
    
    $options_json = $options ? json_encode($options) : null;
    
    // Check if already in cart
    $check = $conn->prepare("SELECT id, quantity FROM guest_cart WHERE session_id = ? AND product_id = ?");
    $check->bind_param('si', $session_id, $product_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $new_qty = $row['quantity'] + $quantity;
        
        if ($product['product']['stock_quantity'] < $new_qty) {
            $check->close();
            return ['success' => false, 'error' => 'Cannot add more. Maximum available: ' . $product['product']['stock_quantity']];
        }
        
        $update = $conn->prepare("UPDATE guest_cart SET quantity = ?, selected_options = ? WHERE id = ?");
        $update->bind_param('isi', $new_qty, $options_json, $row['id']);
        $update->execute();
        $update->close();
        $check->close();
        
        return ['success' => true, 'action' => 'updated', 'new_quantity' => $new_qty];
    }
    
    $check->close();
    
    $insert = $conn->prepare("INSERT INTO guest_cart (session_id, product_id, quantity, selected_options) VALUES (?, ?, ?, ?)");
    $insert->bind_param('siis', $session_id, $product_id, $quantity, $options_json);
    
    if ($insert->execute()) {
        $insert->close();
        return ['success' => true, 'action' => 'added', 'new_quantity' => $quantity];
    }
    
    $insert->close();
    return ['success' => false, 'error' => 'Failed to add to cart'];
}

/**
 * View guest cart
 */
function guestViewCart() {
    global $conn;
    $session_id = getGuestSessionId();
    
    $sql = "SELECT gc.id as cart_id, gc.product_id, gc.quantity, gc.selected_options,
                   p.name_en, p.name_ar, p.price_jod, p.stock_quantity as stock,
                   p.image_link, p.slug, p.has_discount, p.discount_percentage, p.original_price,
                   p.supplier_cost
            FROM guest_cart gc
            JOIN products p ON gc.product_id = p.id
            WHERE gc.session_id = ?
            ORDER BY gc.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $price = $row['price_jod'];
        if ($row['has_discount'] && $row['discount_percentage'] > 0 && $row['original_price'] > 0) {
            $price = $row['price_jod']; // Already discounted
        }
        
        $subtotal = $price * $row['quantity'];
        $total += $subtotal;
        
        $items[] = [
            'cart_id' => $row['cart_id'],
            'product_id' => $row['product_id'],
            'name_en' => $row['name_en'],
            'name_ar' => $row['name_ar'],
            'price' => $price,
            'price_formatted' => formatJOD($price),
            'quantity' => $row['quantity'],
            'stock' => $row['stock'],
            'subtotal' => $subtotal,
            'subtotal_formatted' => formatJOD($subtotal),
            'image_link' => $row['image_link'],
            'slug' => $row['slug'],
            'selected_options' => $row['selected_options'] ? json_decode($row['selected_options'], true) : null
        ];
    }
    $stmt->close();
    
    return [
        'success' => true,
        'cart_items' => $items,
        'total_amount' => $total,
        'total_formatted' => formatJOD($total),
        'item_count' => count($items)
    ];
}

/**
 * Update guest cart quantity
 */
function guestUpdateCartQuantity($product_id, $action) {
    global $conn;
    $session_id = getGuestSessionId();
    
    $stmt = $conn->prepare("SELECT id, quantity FROM guest_cart WHERE session_id = ? AND product_id = ?");
    $stmt->bind_param('si', $session_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'Item not in cart'];
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($action === 'increase') {
        $product = getProductById($product_id);
        $new_qty = $row['quantity'] + 1;
        if ($product['success'] && $product['product']['stock_quantity'] < $new_qty) {
            return ['success' => false, 'error' => 'Maximum stock reached'];
        }
        $update = $conn->prepare("UPDATE guest_cart SET quantity = ? WHERE id = ?");
        $update->bind_param('ii', $new_qty, $row['id']);
        $update->execute();
        $update->close();
        return ['success' => true, 'action' => 'increased', 'new_quantity' => $new_qty];
    } elseif ($action === 'decrease') {
        if ($row['quantity'] <= 1) {
            $del = $conn->prepare("DELETE FROM guest_cart WHERE id = ?");
            $del->bind_param('i', $row['id']);
            $del->execute();
            $del->close();
            return ['success' => true, 'action' => 'removed'];
        }
        $new_qty = $row['quantity'] - 1;
        $update = $conn->prepare("UPDATE guest_cart SET quantity = ? WHERE id = ?");
        $update->bind_param('ii', $new_qty, $row['id']);
        $update->execute();
        $update->close();
        return ['success' => true, 'action' => 'decreased', 'new_quantity' => $new_qty];
    }
    
    return ['success' => false, 'error' => 'Invalid action'];
}

/**
 * Remove item from guest cart
 */
function guestRemoveFromCart($product_id) {
    global $conn;
    $session_id = getGuestSessionId();
    
    $stmt = $conn->prepare("DELETE FROM guest_cart WHERE session_id = ? AND product_id = ?");
    $stmt->bind_param('si', $session_id, $product_id);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true, 'message' => 'Item removed'];
}

/**
 * Clear entire guest cart
 */
function guestClearCart() {
    global $conn;
    $session_id = getGuestSessionId();
    
    $stmt = $conn->prepare("DELETE FROM guest_cart WHERE session_id = ?");
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $stmt->close();
    
    return ['success' => true];
}

/**
 * Get guest cart count
 */
function guestGetCartCount() {
    global $conn;
    $session_id = getGuestSessionId();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cnt FROM guest_cart WHERE session_id = ?");
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return ['count' => (int)$row['cnt']];
}

/**
 * Merge guest cart into user cart after login
 */
function mergeGuestCartToUser($user_id) {
    global $conn;
    $session_id = getGuestSessionId();
    
    // Get guest cart items
    $stmt = $conn->prepare("SELECT product_id, quantity FROM guest_cart WHERE session_id = ?");
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check if already in user's cart
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check->bind_param('ii', $user_id, $row['product_id']);
        $check->execute();
        $existing = $check->get_result();
        
        if ($existing->num_rows > 0) {
            $ex_row = $existing->fetch_assoc();
            $new_qty = $ex_row['quantity'] + $row['quantity'];
            $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->bind_param('ii', $new_qty, $ex_row['id']);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->bind_param('iii', $user_id, $row['product_id'], $row['quantity']);
            $insert->execute();
            $insert->close();
        }
        $check->close();
    }
    $stmt->close();
    
    // Clear guest cart
    guestClearCart();
    unset($_SESSION['guest_cart_id']);
}
