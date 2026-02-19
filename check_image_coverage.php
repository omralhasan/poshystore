<?php
/**
 * Check image coverage for all products
 * Identifies which products have 1.png files
 */

require_once __DIR__ . '/includes/db_connect.php';

$conn = new mysqli(
    'localhost',
    'poshy_user',
    'Poshy2026',
    'poshy_lifestyle'
);

$query = "SELECT id, name_en FROM products ORDER BY id";
$result = $conn->query($query);

echo "=== PRODUCT IMAGE COVERAGE CHECK ===\n\n";

$with_images = [];
$missing = [];

while ($product = $result->fetch_assoc()) {
    $id = $product['id'];
    $name = htmlspecialchars($product['name_en']);
    
    // Check for 1.png in image folders
    $found = false;
    
    // Check images directory
    $images_dir = __DIR__ . '/images';
    if (is_dir($images_dir)) {
        $folders = scandir($images_dir);
        
        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..') continue;
            $folder_path = $images_dir . '/' . $folder;
            if (!is_dir($folder_path)) continue;
            
            // Check if 1.png exists
            if (file_exists($folder_path . '/1.png')) {
                // Check if folder name matches product name
                if (stripos($folder, trim($name)) !== false || stripos(trim($name), $folder) !== false) {
                    $with_images[] = [
                        'id' => $id,
                        'name' => $name,
                        'folder' => $folder
                    ];
                    $found = true;
                    break;
                }
            }
        }
    }
    
    if (!$found) {
        $missing[] = [
            'id' => $id,
            'name' => $name
        ];
    }
}

echo "Products WITH images: " . count($with_images) . " / " . ($with_images + $missing) . "\n";
echo "Products WITHOUT images: " . count($missing) . " / " . (count($with_images) + count($missing)) . "\n\n";

if (!empty($missing)) {
    echo "=== MISSING PRODUCTS ===\n";
    foreach ($missing as $product) {
        echo "[{$product['id']}] {$product['name']}\n";
    }
}

echo "\n";
?>
