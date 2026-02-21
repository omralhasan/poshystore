<?php
/**
 * Get Cart Item Details and Recommended Products
 * Returns details of just-added item and recommended products
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/product_manager.php';
require_once __DIR__ . '/../includes/cart_handler.php';
require_once __DIR__ . '/../includes/product_image_helper.php';

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

$product_id = (int)$_GET['product_id'];
$user_id = $_SESSION['user_id'] ?? null;

// Get the added product details
$product_result = getProductById($product_id);
if (!$product_result['success']) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$added_product = $product_result['product'];

// Get cart item quantity for this product
$cart_quantity = 1;
if ($user_id) {
    $cart_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cart_quantity = $row['quantity'];
    }
    $stmt->close();
}

// Get cart count
$cart_count = 0;
if ($user_id) {
    $cart_info = getCartCount($user_id);
    $cart_count = $cart_info['count'] ?? 0;
}

// Get recommended products (random products, exclude current product, limit 4)
$recommended_products = [];

// Get random products (excluding the current one)
$rec_sql = "SELECT id, slug, name_en, name_ar, price_jod as price, 
            stock_quantity, image_link as image_url,
            original_price, discount_percentage, has_discount
            FROM products 
            WHERE id != ? AND stock_quantity > 0
            ORDER BY RAND()
            LIMIT 4";
$stmt = $conn->prepare($rec_sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['in_stock'] = $row['stock_quantity'] > 0;
    // Calculate final price
    if ($row['has_discount'] && $row['original_price'] > 0) {
        $discounted_price = $row['original_price'] * (1 - $row['discount_percentage'] / 100);
        $row['discounted_price'] = $discounted_price;
        $row['discounted_price_formatted'] = formatJOD($discounted_price);
        $row['final_price'] = $discounted_price;
        $row['original_price_formatted'] = formatJOD($row['original_price']);
    } else {
        $row['final_price'] = $row['price'];
    }
    $row['final_price_formatted'] = formatJOD($row['final_price']);
    $row['price_formatted'] = formatJOD($row['price']);
    $recommended_products[] = $row;
}
$stmt->close();

// If not enough products, get more random ones
if (count($recommended_products) < 4) {
    $needed = 4 - count($recommended_products);
    $exclude_ids = array_merge([$product_id], array_column($recommended_products, 'id'));
    $exclude_str = implode(',', $exclude_ids);
    
    $rec_sql = "SELECT id, slug, name_en, name_ar, price_jod as price, 
                stock_quantity, image_link as image_url,
                original_price, discount_percentage, has_discount
                FROM products 
                WHERE id NOT IN ($exclude_str) AND stock_quantity > 0
                ORDER BY RAND()
                LIMIT $needed";
    $result = $conn->query($rec_sql);
    
    while ($row = $result->fetch_assoc()) {
        $row['in_stock'] = $row['stock_quantity'] > 0;
        // Calculate final price
        if ($row['has_discount'] && $row['original_price'] > 0) {
            $discounted_price = $row['original_price'] * (1 - $row['discount_percentage'] / 100);
            $row['discounted_price'] = $discounted_price;
            $row['discounted_price_formatted'] = formatJOD($discounted_price);
            $row['final_price'] = $discounted_price;
            $row['original_price_formatted'] = formatJOD($row['original_price']);
        } else {
            $row['final_price'] = $row['price'];
        }
        $row['final_price_formatted'] = formatJOD($row['final_price']);
        $row['price_formatted'] = formatJOD($row['price']);
        $recommended_products[] = $row;
    }
}

// Calculate final price for added product
$final_price_formatted = $added_product['price_formatted'];
if ($added_product['has_discount'] && $added_product['original_price'] > 0) {
    $discounted_price = $added_product['original_price'] * (1 - $added_product['discount_percentage'] / 100);
    $final_price_formatted = formatJOD($discounted_price);
}

// Generate proper image paths using image helper
$site_root = realpath(__DIR__ . '/..');
$images_dir = $site_root . '/images';

$added_image = get_product_thumbnail(
    trim($added_product['name_en']),
    $added_product['image_link'] ?? '',
    $site_root
);

// Add image paths to recommended products
foreach ($recommended_products as &$rec) {
    $rec['image_path'] = get_product_thumbnail(
        trim($rec['name_en'] ?? ''),
        $rec['image_url'] ?? '',
        $site_root
    );
}
unset($rec);

echo json_encode([
    'success' => true,
    'added_product' => [
        'id' => $added_product['id'],
        'name_en' => $added_product['name_en'],
        'name_ar' => $added_product['name_ar'],
        'image_url' => $added_product['image_link'],
        'image_path' => '/' . $added_image,
        'price' => $final_price_formatted,
        'quantity' => $cart_quantity
    ],
    'cart_count' => $cart_count,
    'recommended_products' => $recommended_products
]);
