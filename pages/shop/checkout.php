<?php
/**
 * Checkout Process for Poshy Lifestyle E-Commerce
 * 
 * Handles order creation from cart and stock management
 * Connects to: cart table (for cart items)
 *             orders table (id, user_id, total_amount, status, created_at)
 *             products table (for stock updates)
 */

require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/../../includes/product_manager.php';
require_once __DIR__ . '/../../includes/points_wallet_handler.php';

/**
 * Process checkout and create order from cart
 * 
 * @param int|null $user_id User ID (uses current session if null)
 * @param array $additional_data Additional order data (shipping address, etc.)
 * @return array Response with order details
 */
function processCheckout($user_id = null, $additional_data = []) {
    global $conn;
    
    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in to checkout'
            ];
        }
    }
    
    // Get cart contents
    $cart = viewCart($user_id);
    if (!$cart['success']) {
        return $cart; // Return error
    }
    
    if (empty($cart['cart_items'])) {
        return [
            'success' => false,
            'error' => 'Cart is empty'
        ];
    }
    
    // Validate all items have sufficient stock
    $stock_errors = [];
    foreach ($cart['cart_items'] as $item) {
        if ($item['stock'] < $item['quantity']) {
            $stock_errors[] = $item['name_en'] . ' - Available: ' . $item['stock'] . ', Requested: ' . $item['quantity'];
        }
    }
    
    if (!empty($stock_errors)) {
        return [
            'success' => false,
            'error' => 'Insufficient stock for some items',
            'stock_errors' => $stock_errors
        ];
    }
    
    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // Calculate total amount
        $original_total = $cart['total_amount'];
        $total_amount = $original_total;
        $wallet_used = 0;
        $current_wallet = 0;
        $new_wallet_balance = 0;
        $status = 'pending'; // Order status: pending, processing, completed, cancelled
        
        // Apply coupon discount if available
        $coupon_discount = 0;
        $coupon_id = null;
        if (isset($_SESSION['applied_coupon'])) {
            $coupon_discount = $_SESSION['applied_coupon']['discount'] ?? 0;
            $coupon_id = $_SESSION['applied_coupon']['coupon_id'] ?? null;
            $total_amount = max(0, $total_amount - $coupon_discount);
        }
        
        // Apply delivery fee: 2 JOD, free for orders >= 35 JOD (after coupon)
        $delivery_fee = ($total_amount >= 35) ? 0 : 2;
        $total_amount += $delivery_fee;
        
        // Process wallet balance if user wants to use it
        if (!empty($additional_data['use_wallet'])) {
            require_once __DIR__ . '/../../includes/points_wallet_handler.php';
            
            // Get current wallet balance
            $wallet_sql = "SELECT wallet_balance FROM users WHERE id = ?";
            $wallet_stmt = $conn->prepare($wallet_sql);
            $wallet_stmt->bind_param('i', $user_id);
            $wallet_stmt->execute();
            $wallet_result = $wallet_stmt->get_result();
            $wallet_data = $wallet_result->fetch_assoc();
            $current_wallet = (float)($wallet_data['wallet_balance'] ?? 0);
            $wallet_stmt->close();
            
            // Calculate how much wallet to use (minimum of wallet balance and order total)
            if ($current_wallet > 0) {
                $wallet_used = min($current_wallet, $total_amount);
                $total_amount = max(0, $total_amount - $wallet_used);
                
                // Deduct from wallet
                $new_wallet_balance = $current_wallet - $wallet_used;
                $update_wallet_sql = "UPDATE users SET wallet_balance = ? WHERE id = ?";
                $update_wallet_stmt = $conn->prepare($update_wallet_sql);
                $update_wallet_stmt->bind_param('di', $new_wallet_balance, $user_id);
                $update_wallet_stmt->execute();
                $update_wallet_stmt->close();
            }
        }
        
        // Extract shipping details from additional data
        $shipping_address = $additional_data['shipping_address'] ?? '';
        $phone = $additional_data['phone'] ?? '';
        $city = $additional_data['city'] ?? '';
        $notes = $additional_data['notes'] ?? '';
        $is_gift = isset($additional_data['is_gift']) ? (int)$additional_data['is_gift'] : 0;
        $gift_recipient_name = $additional_data['gift_recipient_name'] ?? '';
        $gift_message = $additional_data['gift_message'] ?? '';
        
        // Validate required fields
        if (empty($phone)) {
            throw new Exception("Phone number is required for order processing");
        }
        if (empty($city)) {
            throw new Exception("City is required for order processing");
        }
        if (empty($shipping_address)) {
            throw new Exception("Shipping address is required for order processing");
        }
        
        // Insert order into orders table with shipping details
        // Auto-set order_type based on user role
        $order_type = (isset($_SESSION['role']) && $_SESSION['role'] === 'supplier') ? 'supplier' : 'customer';
        $order_sql = "INSERT INTO orders (user_id, total_amount, status, shipping_address, phone, city, notes, is_gift, gift_recipient_name, gift_message, order_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $order_stmt = $conn->prepare($order_sql);
        
        if (!$order_stmt) {
            throw new Exception("Failed to prepare order statement: " . $conn->error);
        }
        
        $order_stmt->bind_param('idsssssssss', $user_id, $total_amount, $status, $shipping_address, $phone, $city, $notes, $is_gift, $gift_recipient_name, $gift_message, $order_type);
        
        if (!$order_stmt->execute()) {
            throw new Exception("Failed to create order: " . $order_stmt->error);
        }
        
        $order_id = $order_stmt->insert_id;
        $order_stmt->close();
        
        // Insert order items into order_items table
        $item_sql = "INSERT INTO order_items (order_id, product_id, product_name_en, product_name_ar, quantity, price_per_item, subtotal) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        
        // Process each cart item
        $order_items = [];
        foreach ($cart['cart_items'] as $item) {
            // Decrease product stock
            $stock_result = updateStock($item['product_id'], -$item['quantity'], true);
            
            if (!$stock_result['success']) {
                throw new Exception("Failed to update stock for product " . $item['product_id']);
            }
            
            // Save order item
            $item_stmt->bind_param(
                'iissidi',
                $order_id,
                $item['product_id'],
                $item['name_en'],
                $item['name_ar'],
                $item['quantity'],
                $item['price'],
                $item['subtotal']
            );
            
            if (!$item_stmt->execute()) {
                throw new Exception("Failed to save order item: " . $item_stmt->error);
            }
            
            $order_items[] = [
                'product_id' => $item['product_id'],
                'product_name_en' => $item['name_en'],
                'product_name_ar' => $item['name_ar'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal']
            ];
        }
        
        $item_stmt->close();
        
        // Clear cart after successful order
        $clear_sql = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param('i', $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Update coupon usage if coupon was applied
        if ($coupon_id) {
            $update_coupon_sql = "UPDATE coupons SET times_used = times_used + 1 WHERE id = ?";
            $update_coupon_stmt = $conn->prepare($update_coupon_sql);
            $update_coupon_stmt->bind_param('i', $coupon_id);
            $update_coupon_stmt->execute();
            $update_coupon_stmt->close();
            
            // Clear coupon from session
            unset($_SESSION['applied_coupon']);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log wallet transaction if wallet was used
        if ($wallet_used > 0) {
            $wallet_log_sql = "INSERT INTO wallet_transactions 
                              (user_id, amount, transaction_type, reference_id, description, 
                               balance_before, balance_after, created_at) 
                              VALUES (?, ?, 'order_payment', ?, ?, ?, ?, NOW())";
            $wallet_log_stmt = $conn->prepare($wallet_log_sql);
            $negative_amount = -$wallet_used;
            $description = "Used wallet balance for Order #" . $order_id;
            $wallet_log_stmt->bind_param('idisdd', 
                $user_id, 
                $negative_amount, 
                $order_id, 
                $description, 
                $current_wallet, 
                $new_wallet_balance
            );
            $wallet_log_stmt->execute();
            $wallet_log_stmt->close();
        }
        
        // Award points for the purchase (based on amount actually paid, not wallet used)
        $points_result = awardPurchasePoints($user_id, $original_total, $order_id);
        $points_earned = $points_result['success'] ? $points_result['points_earned'] : 0;
        
        // Apply referral code if provided
        $referral_applied = false;
        if (!empty($additional_data['referral_code'])) {
            $referral_code = strtoupper(trim($additional_data['referral_code']));
            require_once __DIR__ . '/../../includes/points_wallet_handler.php';
            $referral_applied = applyReferralCode($referral_code, $user_id, $order_id);
        }
        
        // Send WhatsApp order confirmation (FREE - No API costs!)
        require_once __DIR__ . '/../../includes/whatsapp_functions.php';
        
        // Get customer info for WhatsApp
        $customer_sql = "SELECT firstname, lastname FROM users WHERE id = ?";
        $customer_stmt = $conn->prepare($customer_sql);
        $customer_stmt->bind_param('i', $user_id);
        $customer_stmt->execute();
        $customer_result = $customer_stmt->get_result();
        $customer_data = $customer_result->fetch_assoc();
        $customer_stmt->close();
        
        $customer_name = ($customer_data['firstname'] ?? '') . ' ' . ($customer_data['lastname'] ?? '');
        
        // Prepare WhatsApp order details
        $whatsapp_order = [
            'order_id' => $order_id,
            'customer_name' => trim($customer_name) ?: 'عزيزنا العميل',
            'total_amount' => $total_amount,
            'total_amount_formatted' => formatJOD($total_amount),
            'items' => $order_items,
            'points_earned' => $points_earned,
            'status' => $status
        ];
        
        // Send WhatsApp confirmation if phone number provided
        if (!empty($phone)) {
            sendWhatsAppOrderConfirmation($phone, $whatsapp_order);
        }
        
        return [
            'success' => true,
            'message' => 'Order placed successfully',
            'order' => [
                'order_id' => $order_id,
                'user_id' => $user_id,
                'original_amount' => $original_total,
                'wallet_used' => $wallet_used,
                'total_amount' => $total_amount,
                'total_amount_formatted' => formatJOD($total_amount),
                'wallet_used_formatted' => formatJOD($wallet_used),
                'status' => $status,
                'items' => $order_items,
                'item_count' => count($order_items),
                'currency' => 'JOD',
                'points_earned' => $points_earned,
                'referral_applied' => $referral_applied
            ]
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Checkout failed: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'Checkout failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Get order details by ID
 * 
 * @param int $order_id Order ID
 * @param int|null $user_id User ID (for security check)
 * @return array Response with order details
 */
function getOrderDetails($order_id, $user_id = null) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }
    
    if (!is_numeric($order_id) || $order_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid order ID'
        ];
    }
    
    // Get order from orders table
    $sql = "SELECT id, user_id, total_amount, status, order_date as created_at,
            phone, shipping_address, city, notes,
            is_gift, gift_recipient_name, gift_message
            FROM orders 
            WHERE id = ?";
    
    // Add user check if user_id provided (for security)
    if ($user_id) {
        $sql .= " AND user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($user_id) {
        $stmt->bind_param('ii', $order_id, $user_id);
    } else {
        $stmt->bind_param('i', $order_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
        $order['total_amount_formatted'] = formatJOD($order['total_amount']);
        $order['currency'] = 'JOD';
        
        $stmt->close();
        
        return [
            'success' => true,
            'order' => $order
        ];
    } else {
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Order not found'
        ];
    }
}

/**
 * Get all orders for a user
 * 
 * @param int|null $user_id User ID (uses current session if null)
 * @param int $limit Number of orders to return
 * @param int $offset Offset for pagination
 * @return array Response with orders list
 */
function getUserOrders($user_id = null, $limit = 20, $offset = 0) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = getCurrentUserId();
        if (!$user_id) {
            return [
                'success' => false,
                'error' => 'User must be logged in'
            ];
        }
    }
    
    // Get orders from orders table
    $sql = "SELECT id, user_id, total_amount, status, order_date as created_at, 
            is_gift, gift_recipient_name, gift_message
            FROM orders 
            WHERE user_id = ? 
            ORDER BY order_date DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_amount_formatted'] = formatJOD($row['total_amount']);
        $row['order_id'] = $row['id'];
        
        // Get order items for this order with product details
        $items_sql = "SELECT oi.product_id, oi.product_name_en, oi.product_name_ar, 
                             oi.quantity, oi.price_per_item, oi.subtotal,
                             p.description, p.stock_quantity, p.image_link
                      FROM order_items oi
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param('i', $row['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $order_items = [];
        $total_items_count = 0;
        while ($item = $items_result->fetch_assoc()) {
            $item['price_formatted'] = formatJOD($item['price_per_item']);
            $item['subtotal_formatted'] = formatJOD($item['subtotal']);
            $order_items[] = $item;
            $total_items_count += $item['quantity'];
        }
        $items_stmt->close();
        
        $row['items'] = $order_items;
        $row['items_count'] = $total_items_count;
        $orders[] = $row;
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'orders' => $orders,
        'count' => count($orders),
        'currency' => 'JOD'
    ];
}

/**
 * Update order status
 * 
 * @param int $order_id Order ID
 * @param string $new_status New status (pending, shipped, delivered, cancelled)
 * @return array Response with success status
 */
function updateOrderStatus($order_id, $new_status) {
    global $conn;
    
    $allowed_statuses = ['pending', 'shipped', 'delivered', 'cancelled'];
    
    if (!in_array($new_status, $allowed_statuses)) {
        return [
            'success' => false,
            'error' => 'Invalid status'
        ];
    }
    
    // Get current order status before updating
    $check_sql = "SELECT status FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Order not found'
        ];
    }
    
    $old_status = $result->fetch_assoc()['status'];
    $check_stmt->close();
    
    // If changing to cancelled from a non-cancelled status, restore stock AND reverse points
    if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
        // Get all order items
        $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param('i', $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        // Restore stock for each product
        $restored_items = [];
        while ($item = $items_result->fetch_assoc()) {
            $update_stock_sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $update_stock_stmt = $conn->prepare($update_stock_sql);
            $update_stock_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
            $update_stock_stmt->execute();
            $update_stock_stmt->close();
            
            $restored_items[] = [
                'product_id' => $item['product_id'],
                'quantity_restored' => $item['quantity']
            ];
        }
        $items_stmt->close();
        
        // Reverse points earned from this order
        $points_sql = "SELECT pt.id, pt.user_id, pt.points_change 
                       FROM points_transactions pt 
                       WHERE pt.reference_id = ? AND pt.transaction_type = 'earned_purchase'";
        $points_stmt = $conn->prepare($points_sql);
        if ($points_stmt) {
            $points_stmt->bind_param('i', $order_id);
            $points_stmt->execute();
            $points_result = $points_stmt->get_result();
            
            while ($pt = $points_result->fetch_assoc()) {
                $points_to_deduct = $pt['points_change'];
                $pt_user_id = $pt['user_id'];
                
                if ($points_to_deduct > 0) {
                    // Get current user points
                    $user_pts_sql = "SELECT points FROM users WHERE id = ?";
                    $user_pts_stmt = $conn->prepare($user_pts_sql);
                    $user_pts_stmt->bind_param('i', $pt_user_id);
                    $user_pts_stmt->execute();
                    $user_pts_result = $user_pts_stmt->get_result();
                    $current_points = 0;
                    if ($urow = $user_pts_result->fetch_assoc()) {
                        $current_points = (int)$urow['points'];
                    }
                    $user_pts_stmt->close();
                    
                    // Deduct the points (don't go below 0)
                    $actual_deduct = min($points_to_deduct, $current_points);
                    $points_after = $current_points - $actual_deduct;
                    
                    $deduct_sql = "UPDATE users SET points = ? WHERE id = ?";
                    $deduct_stmt = $conn->prepare($deduct_sql);
                    $deduct_stmt->bind_param('ii', $points_after, $pt_user_id);
                    $deduct_stmt->execute();
                    $deduct_stmt->close();
                    
                    // Record the reversal transaction
                    $neg_points = -$actual_deduct;
                    $reversal_desc = "Points reversed for cancelled order #$order_id";
                    $rev_sql = "INSERT INTO points_transactions 
                                (user_id, points_change, transaction_type, reference_id, description, points_before, points_after) 
                                VALUES (?, ?, 'cancelled_order', ?, ?, ?, ?)";
                    $rev_stmt = $conn->prepare($rev_sql);
                    $rev_stmt->bind_param('iiisii', $pt_user_id, $neg_points, $order_id, $reversal_desc, $current_points, $points_after);
                    $rev_stmt->execute();
                    $rev_stmt->close();
                }
            }
            $points_stmt->close();
        }
    }
    
    // Update orders table
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $new_status, $order_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        
        $response = [
            'success' => true,
            'message' => 'Order status updated',
            'old_status' => $old_status,
            'new_status' => $new_status
        ];
        
        if (isset($restored_items)) {
            $response['stock_restored'] = true;
            $response['restored_items'] = $restored_items;
        }
        
        return $response;
    } else {
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Order not found or status unchanged'
        ];
    }
}

/**
 * Get all orders (admin only)
 * 
 * @param int $limit Number of orders to return
 * @param int $offset Offset for pagination
 * @param string $status_filter Filter by status (optional)
 * @return array Response with all orders list
 */
function getAllOrders($limit = 50, $offset = 0, $status_filter = null) {
    global $conn;
    
    // Build SQL query
    $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, o.order_type, o.order_date as created_at,
                   u.firstname, u.lastname, u.email, u.phonenumber
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id";
    
    if ($status_filter && in_array($status_filter, ['pending', 'shipped', 'delivered', 'cancelled'])) {
        $sql .= " WHERE o.status = ?";
    }
    
    $sql .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($status_filter) {
        $stmt->bind_param('sii', $status_filter, $limit, $offset);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_amount_formatted'] = formatJOD($row['total_amount']);
        $row['order_id'] = $row['id'];
        
        // Get order items for this order with product details
        $items_sql = "SELECT oi.product_id, oi.product_name_en, oi.product_name_ar, 
                             oi.quantity, oi.price_per_item, oi.subtotal,
                             p.description, p.stock_quantity, p.image_link
                      FROM order_items oi
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param('i', $row['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $order_items = [];
        $total_items_count = 0;
        while ($item = $items_result->fetch_assoc()) {
            $item['price_formatted'] = formatJOD($item['price_per_item']);
            $item['subtotal_formatted'] = formatJOD($item['subtotal']);
            $order_items[] = $item;
            $total_items_count += $item['quantity'];
        }
        $items_stmt->close();
        
        $row['items'] = $order_items;
        $row['items_count'] = $total_items_count;
        $row['customer_name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        
        $orders[] = $row;
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'orders' => $orders,
        'count' => count($orders),
        'currency' => 'JOD'
    ];
}

// If this file is called directly via POST (API endpoint)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['PHP_SELF']) === 'checkout.php') {
    define('API_REQUEST', true);
    
    // Ensure user is logged in
    if (!checkSession()) {
        jsonResponse([
            'success' => false,
            'error' => 'Authentication required'
        ], 401);
    }
    
    // Process checkout
    $result = processCheckout();
    jsonResponse($result, $result['success'] ? 200 : 400);
}
?>
