<?php
// Comprehensive mapping of all product image folders to database products

require_once '/var/www/html/poshy_store/includes/db_connect.php';

// Get all products
$result = $conn->query("SELECT id, name_en FROM products ORDER BY id");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Scan images directory for all folders with 1.png
$images_dir = '/var/www/html/poshy_store/images';
$all_folders = scandir($images_dir);

echo "Mapping all product image folders to database...\n";
echo "====================================\n";

$mapped = 0;
$failed = 0;

foreach ($all_folders as $folder) {
    if ($folder === '.' || $folder === '..' || $folder === 'products') {
        continue;
    }
    
    $folder_path = "$images_dir/$folder";
    
    if (!is_dir($folder_path)) {
        continue;
    }
    
    // Check if folder has 1.png
    if (!file_exists("$folder_path/1.png")) {
        continue;
    }
    
    // Try to find matching product
    $product_id = null;
    $folder_lower = strtolower(trim($folder));
    $best_match = null;
    $best_score = 0;
    
    foreach ($products as $product) {
        $prod_name_lower = strtolower(trim($product['name_en']));
        
        // Calculate similarity score
        similar_text($folder_lower, $prod_name_lower, $percent);
        
        if ($percent > $best_score) {
            $best_score = $percent;
            $best_match = $product;
        }
        
        // Direct substring match (high priority)
        if (strpos($prod_name_lower, $folder_lower) !== false && strlen($prod_name_lower) < 100) {
            if (strlen($prod_name_lower) > (isset($product_id) ? strlen($best_match['name_en']) : 0)) {
                $best_match = $product;
                $best_score = 95;
            }
        }
    }
    
    if ($best_match && $best_score > 50) {
        $product_id = $best_match['id'];
        
        // Update database
        $image_path = "images/$folder/1.png";
        $stmt = $conn->prepare("UPDATE products SET image_link = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $product_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "✓ Product {$product_id}: '$folder' ({$best_score}% match)\n";
                $mapped++;
            } else {
                echo "~ Product {$product_id}: Already has image_link\n";
            }
        } else {
            echo "✗ ERROR updating product {$product_id}\n";
            $failed++;
        }
        $stmt->close();
    } else if ($best_match) {
        echo "? '$folder' - Low match ({$best_score}%) to product {$best_match['id']}\n";
    } else {
        echo "✗ NO MATCH: '$folder'\n";
        $failed++;
    }
}

echo "====================================\n";
echo "✅ Mapping complete!\n";
echo "Successfully mapped: $mapped\n";
echo "Failed matches: $failed\n";
?>
