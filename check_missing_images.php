<?php
/**
 * Check which products in the database are missing images
 * Access via: /check_missing_images.php?token=poshy_img_check_2026
 */
header('Content-Type: text/plain; charset=utf-8');

// Simple auth token
if (($_GET['token'] ?? '') !== 'poshy_img_check_2026') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/product_image_helper.php';

$images_dir = __DIR__ . '/images';

// Get all products
$result = $conn->query("SELECT id, name_en, image_link FROM products ORDER BY id");

$missing = [];
$has_image = [];

while ($row = $result->fetch_assoc()) {
    $name = trim($row['name_en']);
    $image_link = $row['image_link'] ?? '';
    
    // Check using the gallery helper
    $gallery = get_product_gallery_images($name, $image_link, $images_dir, '/');
    
    // Also check if image folder exists
    $folder_path = $images_dir . '/' . $name;
    $folder_exists = is_dir($folder_path);
    
    if (empty($gallery)) {
        $missing[] = [
            'id' => $row['id'],
            'name' => $name,
            'image_link_db' => $image_link ?: '(empty)',
            'folder_exists' => $folder_exists ? 'YES' : 'NO'
        ];
    } else {
        $has_image[] = [
            'id' => $row['id'],
            'name' => $name,
            'image_count' => count($gallery)
        ];
    }
}

echo "=== PRODUCTS MISSING IMAGES ===\n";
echo "Total missing: " . count($missing) . "\n\n";
foreach ($missing as $m) {
    echo "ID: {$m['id']} | Name: {$m['name']} | DB image_link: {$m['image_link_db']} | Folder exists: {$m['folder_exists']}\n";
}

echo "\n=== PRODUCTS WITH IMAGES ===\n";
echo "Total with images: " . count($has_image) . "\n\n";
foreach ($has_image as $h) {
    echo "ID: {$h['id']} | Name: {$h['name']} | Image count: {$h['image_count']}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total products: " . (count($missing) + count($has_image)) . "\n";
echo "With images: " . count($has_image) . "\n";
echo "Missing images: " . count($missing) . "\n";
