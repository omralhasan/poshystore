<?php
/**
 * Cart Handler for Poshy Lifestyle E-Commerce
 * 
 * Manages shopping cart operations using database storage
 * Connects to: cart table (id, user_id, product_id, quantity)
 *             products table (for product details and stock validation)
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/product_manager.php';

/**
 * Add product to cart
 * 
 * @param int $product_id Product ID to add
 * @param int $quantity Quantity to add
 * @param int|null $user_id User ID (uses current session if null)
 * @return array Response with success status
 */
function addToCart($product_id, $quantity = 1, $user_id = null) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in to add to cart'
            ];
        }
    }
    
    // Validate inputs
    if (!is_numeric($product_id) || $product_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    if (!is_numeric($quantity) || $quantity <= 0) {
        return [
            'success' => false,
            'error' => 'Quantity must be greater than 0'
        ];
    }
    
    // Check if product exists and has sufficient stock
    $product = getProductById($product_id);
    if (!$product['success']) {
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
    
    if ($product['product']['stock_quantity'] < $quantity) {
        return [
            'success' => false,
            'error' => 'Insufficient stock. Available: ' . $product['product']['stock_quantity']
        ];
    }
    
    // Check if product already in cart - connects to cart table
    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $user_id, $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Product already in cart - update quantity
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        // Check stock for new quantity
        if ($product['product']['stock_quantity'] < $new_quantity) {
            $check_stmt->close();
            return [
                'success' => false,
                'error' => 'Cannot add more. Maximum available: ' . $product['product']['stock_quantity']
            ];
        }
        
        $update_sql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $new_quantity, $cart_item['id']);
        $update_stmt->execute();
        $update_stmt->close();
        $check_stmt->close();
        
        return [
            'success' => true,
            'message' => 'Cart updated successfully',
            'action' => 'updated',
            'new_quantity' => $new_quantity
        ];
    } else {
        // Add new item to cart
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('iii', $user_id, $product_id, $quantity);
        
        if ($insert_stmt->execute()) {
            $cart_id = $insert_stmt->insert_id;
            $insert_stmt->close();
            $check_stmt->close();
            
            return [
                'success' => true,
                'message' => 'Product added to cart',
                'action' => 'added',
                'cart_id' => $cart_id
            ];
        } else {
            $insert_stmt->close();
            $check_stmt->close();
            return [
                'success' => false,
                'error' => 'Failed to add product to cart'
            ];
        }
    }
}

/**
 * View cart contents with product details
 * 
 * @param int|null $user_id User ID (uses current session if null)
 * @return array Response with cart items and totals
 */
function viewCart($user_id = null) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in to view cart'
            ];
        }
    }
    
    // Get cart items with product details - joins cart and products tables
    $sql = "SELECT 
                c.id as cart_id,
                c.product_id,
                c.quantity,
                p.name_en,
                p.name_ar,
                p.price_jod as price,
                p.stock_quantity as stock,
                p.image_link as image_url,
                p.category_id,
                (c.quantity * p.price_jod) as subtotal
            FROM cart c
            INNER JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.id DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("View cart prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Failed to fetch cart'
        ];
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total_amount = 0;
    $total_items = 0;
    
    while ($row = $result->fetch_assoc()) {
        $row['price_formatted'] = formatJOD($row['price']);
        $row['subtotal_formatted'] = formatJOD($row['subtotal']);
        $row['in_stock'] = $row['stock'] >= $row['quantity'];
        
        $cart_items[] = $row;
        $total_amount += $row['subtotal'];
        $total_items += $row['quantity'];
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'cart_items' => $cart_items,
        'total_items' => $total_items,
        'total_amount' => $total_amount,
        'total_amount_formatted' => formatJOD($total_amount),
        'currency' => 'JOD'
    ];
}

/**
 * Remove item from cart
 * 
 * @param int $cart_id Cart item ID to remove
 * @param int|null $user_id User ID (uses current session if null)
 * @return array Response with success status
 */
function removeFromCart($cart_id, $user_id = null) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in'
            ];
        }
    }
    
    if (!is_numeric($cart_id) || $cart_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid cart item ID'
        ];
    }
    
    // Delete from cart table (with user_id check for security)
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Remove from cart prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Failed to remove item'
        ];
    }
    
    $stmt->bind_param('ii', $cart_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        return [
            'success' => true,
            'message' => 'Item removed from cart'
        ];
    } else {
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Item not found in cart'
        ];
    }
}

/**
 * Update cart item quantity
 * 
 * @param int $cart_id Cart item ID
 * @param int $new_quantity New quantity
 * @param int|null $user_id User ID (uses current session if null)
 * @return array Response with success status
 */
function updateCartQuantity($cart_id, $new_quantity, $user_id = null) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in'
            ];
        }
    }
    
    if (!is_numeric($new_quantity) || $new_quantity < 0) {
        return [
            'success' => false,
            'error' => 'Invalid quantity'
        ];
    }
    
    // If quantity is 0, remove item
    if ($new_quantity == 0) {
        return removeFromCart($cart_id, $user_id);
    }
    
    // Get cart item and check stock
    $check_sql = "SELECT c.product_id, p.stock_quantity as stock 
                  FROM cart c 
                  INNER JOIN products p ON c.product_id = p.id 
                  WHERE c.id = ? AND c.user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ii', $cart_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Cart item not found'
        ];
    }
    
    $item = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($item['stock'] < $new_quantity) {
        return [
            'success' => false,
            'error' => 'Insufficient stock. Available: ' . $item['stock']
        ];
    }
    
    // Update quantity in cart table
    $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('iii', $new_quantity, $cart_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    return [
        'success' => true,
        'message' => 'Quantity updated',
        'new_quantity' => $new_quantity
    ];
}

/**
 * Clear entire cart for user
 * 
 * @param int|null $user_id User ID (uses current session if null)
 * @return array Response with success status
 */
function clearCart($user_id = null) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in'
            ];
        }
    }
    
    // Delete all cart items for user
    $sql = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return [
        'success' => true,
        'message' => 'Cart cleared',
        'items_removed' => $affected
    ];
}

/**
 * Get cart item count for user
 * 
 * @param int|null $user_id User ID (uses current session if null)
 * @return int Number of items in cart
 */
function getCartCount($user_id = null) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return 0;
        }
    }
    
    $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] ?? 0;
}
?>
