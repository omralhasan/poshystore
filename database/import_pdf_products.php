<?php
// Import how-to-use data from CSV for all products

require_once '../includes/db_connect.php';

$csv_file = 'import_pdf_how_to_use.csv';

if (!file_exists($csv_file)) {
    die("CSV file not found: $csv_file");
}

$imported = 0;
$skipped = 0;
$not_found = array();

if (($handle = fopen($csv_file, 'r')) !== false) {
    // Skip header row
    fgetcsv($handle);
    
    while (($line = fgetcsv($handle)) !== false) {
        if (count($line) < 3) continue;
        
        $product_name = trim($line[0]);
        $how_to_use_en = trim($line[1]);
        $how_to_use_ar = trim($line[2]);
        
        if (empty($product_name)) continue;
        
        // Find product by name
        $query = "SELECT id FROM products WHERE name_en = ? OR name_ar = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $product_name, $product_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $product_id = $product['id'];
            
            // Update the product with bilingual how_to_use
            $update_query = "UPDATE products SET how_to_use_en = ?, how_to_use_ar = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ssi', $how_to_use_en, $how_to_use_ar, $product_id);
            
            if ($update_stmt->execute()) {
                $imported++;
                echo "✓ Imported: $product_name\n";
            }
            $update_stmt->close();
        } else {
            $skipped++;
            $not_found[] = $product_name;
        }
        $stmt->close();
    }
    
    fclose($handle);
}

echo "\n=== Import Summary ===\n";
echo "Successfully imported: $imported products\n";
echo "Not found in database: $skipped products\n";

if (!empty($not_found)) {
    echo "\nProducts not found:\n";
    foreach ($not_found as $name) {
        echo "  - $name\n";
    }
}

$conn->close();
?>
