<?php
/**
 * Get Stock Status API
 * Returns current stock levels and recent order activity
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/product_manager.php';

header('Content-Type: application/json');

try {
    // Get all products with stock information
    $products_sql = "SELECT id, name_en, name_ar, stock_quantity FROM products ORDER BY id LIMIT 20";
    $products_result = $conn->query($products_sql);
    
    $products = [];
    while ($row = $products_result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'name_en' => $row['name_en'],
            'name_ar' => $row['name_ar'],
            'stock_quantity' => (int)$row['stock_quantity']
        ];
    }
    
    // Get recent orders (last 10)
    $orders_sql = "SELECT id, user_id, total_amount, status, order_date 
                   FROM orders 
                   ORDER BY order_date DESC 
                   LIMIT 10";
    $orders_result = $conn->query($orders_sql);
    
    $orders = [];
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = [
            'order_id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'total_amount' => number_format((float)$row['total_amount'], 3),
            'status' => $row['status'],
            'order_date' => $row['order_date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'recent_orders' => $orders,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
