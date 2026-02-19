<?php
/**
 * New Products Import Script
 * Imports 42 products from Store_Ready_42_Products_ShortDesc.csv
 * - Removes all existing products
 * - Imports products with bilingual descriptions (Arabic & English)
 * - Downloads and saves product images
 */

require_once __DIR__ . '/includes/db_connect.php';

echo "=== Product Import Started ===\n\n";

// Read the CSV file
$csv_file = __DIR__ . '/Store_Ready_42_Products_ShortDesc.csv';

if (!file_exists($csv_file)) {
    die("Error: CSV file not found at: $csv_file\n");
}

$csv_data = array_map(function($line) {
    return str_getcsv($line);
}, file($csv_file));

// Remove header row
$headers = array_shift($csv_data);
echo "CSV Headers: " . implode(', ', $headers) . "\n\n";

// Step 1: Delete all existing products
echo "Step 1: Removing all existing products...\n";
try {
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Delete cart items first
    $result = $conn->query("DELETE FROM cart");
    echo "✓ Cleared cart items\n";
    
    // Delete order items
    $result = $conn->query("DELETE FROM order_items");
    echo "✓ Cleared order items\n";
    
    // Delete all products
    $result = $conn->query("DELETE FROM products");
    $deleted_count = $conn->affected_rows;
    echo "✓ Deleted $deleted_count existing products\n";
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Foreign key checks restored\n\n";
} catch (Exception $e) {
    echo "Error deleting products: " . $e->getMessage() . "\n";
    $conn->query("SET FOREIGN_KEY_CHECKS = 1"); // Make sure to re-enable
}

// Step 2: Import new products
echo "Step 2: Importing new products...\n";

$imported = 0;
$failed = 0;
$image_dir = __DIR__ . '/images/products/';

// Create products directory if it doesn't exist
if (!is_dir($image_dir)) {
    mkdir($image_dir, 0777, true);
    echo "Created directory: $image_dir\n";
}

foreach ($csv_data as $index => $row) {
    if (count($row) < 5) {
        echo "⚠ Skipping row " . ($index + 2) . " - insufficient data\n";
        continue;
    }
    
    $category = trim($row[0]);
    $product_name = trim($row[1]);
    $description_ar = trim($row[2]);
    $description_en = trim($row[3]);
    $image_search_link = trim($row[4]);
    
    if (empty($product_name)) {
        echo "⚠ Skipping row " . ($index + 2) . " - empty product name\n";
        continue;
    }
    
    // Set default price based on category (can be adjusted later)
    $price_jod = 20.000; // Default price in JOD
    $stock_quantity = 10; // Default stock
    
    // Create a short description combining both languages
    $description = "**English:** $description_en\n\n**العربية:** $description_ar";
    
    // Generate image filename from product name
    $image_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($product_name));
    $image_filename = substr($image_filename, 0, 50) . '.jpg';
    $image_link = 'images/products/' . $image_filename;
    
    echo "\n[" . ($index + 1) . "] Importing: $product_name\n";
    echo "   Description (EN): " . substr($description_en, 0, 50) . "...\n";
    echo "   Description (AR): " . substr($description_ar, 0, 50) . "...\n";
    
    try {
        // Insert product into database
        $stmt = $conn->prepare("
            INSERT INTO products (name_en, name_ar, description, price_jod, stock_quantity, image_link, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "sssdis",
            $product_name,     // name_en
            $product_name,     // name_ar (using English for now)
            $description,      // description
            $price_jod,        // price_jod
            $stock_quantity,   // stock_quantity
            $image_link        // image_link
        );
        
        $stmt->execute();
        
        $imported++;
        echo "   ✓ Imported successfully (ID: " . $conn->insert_id . ")\n";
        
        $stmt->close();
        
        // Download image if possible
        if (!empty($image_search_link)) {
            echo "   Image link: $image_search_link\n";
            echo "   ⚠ Note: Image is a Google search link - manual download recommended\n";
        }
        
    } catch (Exception $e) {
        $failed++;
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Import Summary ===\n";
echo "Total products imported: $imported\n";
echo "Failed imports: $failed\n";
echo "Total in CSV: " . count($csv_data) . "\n";

echo "\n=== IMPORTANT NOTES ===\n";
echo "1. All product prices are set to 20.000 JOD - please update them manually\n";
echo "2. Stock levels are set to 10 - please adjust as needed\n";
echo "3. Images need to be downloaded manually from the Google search links\n";
echo "4. You can update product details via the admin panel\n";

echo "\n=== Next Steps ===\n";
echo "1. Run: php update_product_prices.php (to set correct prices)\n";
echo "2. Download product images from the search links provided\n";
echo "3. Save images to: $image_dir\n";
echo "4. Access admin panel to review and edit products\n";


// Categories have been removed from the system
// This function is no longer used but kept for reference purposes
