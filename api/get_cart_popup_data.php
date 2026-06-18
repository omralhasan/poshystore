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
require_once __DIR__ . '/../includes/guest_cart_handler.php';
require_once __DIR__ . '/../includes/product_image_helper.php';

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

$product_id = (int)$_GET['product_id'];
$user_id = $_SESSION['user_id'] ?? null;

$product_result = getProductById($product_id);
if (!$product_result['success']) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$added_product = $product_result['product'];

// Get cart item quantity
$cart_quantity = 1;
if ($user_id) {
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $cart_quantity = $row['quantity'];
    $stmt->close();
} else {
    $guest_session_id = getGuestSessionId();
    $stmt = $conn->prepare("SELECT quantity FROM guest_cart WHERE session_id = ? AND product_id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $guest_session_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $cart_quantity = (int)$row['quantity'];
        $stmt->close();
    }
}

// Get cart count
$cart_count = 0;
if ($user_id) {
    $cart_info = getCartCount($user_id);
    $cart_count = $cart_info['count'] ?? 0;
} else {
    $cart_info = guestGetCartCount();
    $cart_count = $cart_info['count'] ?? 0;
}

// Get recommended products using smart logic
$recommended_products = getRecommendedProducts($product_id, $conn, 3);

// Calculate final price for added product
$final_price_formatted = $added_product['price_formatted'];
if ($added_product['has_discount'] && $added_product['original_price'] > 0) {
    $discounted_price = $added_product['original_price'] * (1 - $added_product['discount_percentage'] / 100);
    $final_price_formatted = formatJOD($discounted_price);
}

$site_root = realpath(__DIR__ . '/..');
$added_image = get_product_thumbnail(
    trim($added_product['name_en']),
    $added_product['image_link'] ?? '',
    $site_root
);

// Add image paths to recommended products
foreach ($recommended_products as &$rec) {
    $rec['image_path'] = get_product_thumbnail(
        trim($rec['name_en'] ?? ''),
        $rec['image_link'] ?? '',
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
        'slug' => $added_product['slug'],
        'image_url' => $added_product['image_link'],
        'image_path' => '/' . $added_image,
        'price' => $final_price_formatted,
        'quantity' => $cart_quantity
    ],
    'cart_count' => $cart_count,
    'recommended_products' => $recommended_products
]);
