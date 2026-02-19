<?php
/**
 * Database Migration: Add short description columns to products table
 * This allows storing short descriptions in both Arabic and English
 */

require_once '../includes/config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Short Description Columns Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 20px 0;
        }
        .error {
            color: #dc3545;
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            color: #004085;
            padding: 15px;
            background: #cce5ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            margin: 20px 0;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #d4af37;
            padding-bottom: 10px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📝 Short Description Columns Migration</h1>
        
        <?php
        try {
            // Check if columns already exist
            $check_ar = $conn->query("SHOW COLUMNS FROM products LIKE 'short_description_ar'");
            $check_en = $conn->query("SHOW COLUMNS FROM products LIKE 'short_description_en'");
            
            if ($check_ar->num_rows > 0 && $check_en->num_rows > 0) {
                echo '<div class="info">';
                echo '<strong>ℹ️ Info:</strong> The short description columns already exist in the products table.';
                echo '</div>';
            } else {
                // Add the columns
                $sql = "ALTER TABLE products 
                        ADD COLUMN short_description_ar VARCHAR(255) DEFAULT NULL AFTER name_ar,
                        ADD COLUMN short_description_en VARCHAR(255) DEFAULT NULL AFTER name_en";
                
                if ($conn->query($sql)) {
                    echo '<div class="success">';
                    echo '<strong>✅ Success!</strong> The short description columns have been added to the products table.';
                    echo '</div>';
                } else {
                    throw new Exception($conn->error);
                }
            }
            
            // Show current table structure
            echo '<h2>Current Products Table Structure:</h2>';
            echo '<div class="info">';
            $columns = $conn->query("SHOW COLUMNS FROM products");
            echo '<ul>';
            while ($col = $columns->fetch_assoc()) {
                echo '<li><code>' . htmlspecialchars($col['Field']) . '</code> - ' . 
                     htmlspecialchars($col['Type']) . 
                     ($col['Null'] === 'YES' ? ' (nullable)' : ' (required)') . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            
            echo '<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">';
            echo '<h3>📋 Next Steps:</h3>';
            echo '<ol>';
            echo '<li>Import short descriptions from CSV file</li>';
            echo '<li>Short descriptions will appear below product names on product pages</li>';
            echo '<li>Descriptions are bilingual (Arabic and English)</li>';
            echo '</ol>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="import_short_descriptions.php" style="text-decoration: none; color: white; background: #d4af37; padding: 10px 20px; border-radius: 5px; display: inline-block;">
                Import Short Descriptions
            </a>
            <a href="../index.php" style="text-decoration: none; color: #333; background: #e9ecef; padding: 10px 20px; border-radius: 5px; display: inline-block; margin-left: 10px;">
                Go to Home
            </a>
        </div>
    </div>
</body>
</html>
