<?php
/**
 * Import How to Use Information
 * Imports bilingual how_to_use content from CSV file
 * CSV format: Product Name, How to Use (EN), How to Use (AR)
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "=== Import How to Use Content ===\n\n";

// Check if file was uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    
    if (!file_exists($file)) {
        echo "<div class='alert alert-danger'>File not found!</div>";
        exit;
    }
    
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    
    echo "Processing: $filename\n";
    echo "Columns: " . implode(', ', $header) . "\n\n";
    
    $count = 0;
    $errors = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 3) continue;
        
        $product_name = trim($row[0]);
        $how_to_use_en = trim($row[1]);
        $how_to_use_ar = trim($row[2]);
        
        if (empty($product_name)) continue;
        
        // Find product by name (try both EN and AR)
        $find_sql = "SELECT id FROM products WHERE name_en = ? OR name_ar = ? LIMIT 1";
        $find_stmt = $conn->prepare($find_sql);
        $find_stmt->bind_param('ss', $product_name, $product_name);
        $find_stmt->execute();
        $find_result = $find_stmt->get_result();
        
        if ($find_result->num_rows > 0) {
            $product = $find_result->fetch_assoc();
            $product_id = $product['id'];
            
            // Update how_to_use_en and how_to_use_ar
            $update_sql = "UPDATE products SET how_to_use_en = ?, how_to_use_ar = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('ssi', $how_to_use_en, $how_to_use_ar, $product_id);
            
            if ($update_stmt->execute()) {
                echo "✓ Updated: $product_name (ID: $product_id)\n";
                $count++;
            } else {
                echo "✗ Error updating $product_name: " . $conn->error . "\n";
                $errors++;
            }
            $update_stmt->close();
        } else {
            echo "⚠ Product not found: $product_name\n";
            $errors++;
        }
        $find_stmt->close();
    }
    
    fclose($handle);
    
    echo "\n=== Results ===\n";
    echo "Successfully imported: $count products\n";
    echo "Errors/Skipped: $errors\n";
    exit;
}

// Show HTML form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import How to Use</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; background: #f5f5f5; }
        .container { max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-section { margin: 30px 0; }
        .csv-template { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; margin: 15px 0; }
        .alert { margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Import How to Use Content</h1>
        
        <div class="alert alert-info">
            <strong>Important:</strong> This tool imports bilingual "How to Use" instructions for products.
        </div>
        
        <div class="form-section">
            <h3>CSV Format</h3>
            <p>Your CSV file should have these columns:</p>
            <div class="csv-template">
Product Name, How to Use (EN), How to Use (AR)
EQQUAL BERRY BAKUCHIOL Plumping Serum, Apply 2-3 drops to clean skin., ضع 2-3 قطرات على البشرة النظيفة
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="csv_file" class="form-label">Select CSV File:</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                <small class="form-text text-muted">
                    Make sure your CSV has headers: Product Name, How to Use (EN), How to Use (AR)
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-upload"></i> Import CSV
            </button>
            <a href="javascript:history.back()" class="btn btn-secondary btn-lg">Cancel</a>
        </form>
        
        <div class="alert alert-warning mt-4">
            <strong>Tips:</strong>
            <ul style="margin: 10px 0 0 0;">
                <li>Make sure product names match exactly with database names</li>
                <li>Use product English name (name_en) for matching</li>
                <li>If product is not found, it will be skipped with a warning</li>
                <li>Existing how_to_use content will be overwritten</li>
            </ul>
        </div>
    </div>
</body>
</html>
