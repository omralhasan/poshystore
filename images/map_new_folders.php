<?php
// Map new product folders to database products
// These new folders are at /var/www/html/poshy_store/images/{product_name}/
// Each folder should have: 1.png (main image), 2.png, 3.png, etc. (additional images)

require_once '/var/www/html/poshy_store/includes/db_connect.php';

// Get all products
$result = $conn->query("SELECT id, name_en, name_ar FROM products ORDER BY id");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// New product folders at /var/www/html/poshy_store/images/
$images_dir = '/var/www/html/poshy_store/images';
$new_folders = [];

// Scan for new product folders
// These are folders at images/ level that have 1.png
$all_items = scandir($images_dir);
foreach ($all_items as $item) {
    $path = "$images_dir/$item";
    // Check if it's a directory and not a special folder and has 1.png
    if (is_dir($path) && $item !== '.' && $item !== '..' && $item !== 'products' && 
        !str_contains($item, '.') && file_exists("$path/1.png")) {
        $new_folders[$item] = $path;
    }
}

echo "Found " . count($new_folders) . " new product folders\n";
echo "====================================\n";

$matched = 0;
$updated = 0;

// Try to match each folder to a product
foreach ($new_folders as $folder_name => $folder_path) {
    $images = glob("$folder_path/*.png");
    
    if (empty($images) || !file_exists("$folder_path/1.png")) {
        continue;
    }
    
    // Try to find matching product
    $product_id = null;
    $folder_lower = strtolower(trim($folder_name));
    
    // Exact or partial match with product names
    foreach ($products as $product) {
        $prod_name = strtolower($product['name_en']);
        
        // Check if folder name contains or is contained in product name
        if (stripos($folder_lower, $prod_name) !== false || 
            stripos($prod_name, $folder_lower) !== false) {
            $product_id = $product['id'];
            break;
        }
    }
    
    if ($product_id) {
        // Update database to point to new location
        $image_path = "images/" . basename($folder_path) . "/1.png";
        $stmt = $conn->prepare("UPDATE products SET image_link = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $product_id);
        if ($stmt->execute()) {
            echo "✓ Product $product_id: Mapped to '$folder_name'\n";
            $matched++;
            if ($stmt->affected_rows > 0) {
                $updated++;
            }
        }
        $stmt->close();
    } else {
        echo "✗ '$folder_name': No matching product found\n";
    }
}

echo "====================================\n";
echo "✅ Mapping complete!\n";
echo "Matched: {$matched} folders\n";
echo "Updated: {$updated} products in database\n";
?>
