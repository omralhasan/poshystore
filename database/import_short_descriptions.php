<?php
/**
 * Import Short Descriptions from CSV
 * Reads Store_Ready_42_Products_ShortDesc.csv and updates products table
 */

require_once '../includes/config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Short Descriptions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .warning {
            color: #856404;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📥 Import Short Descriptions from CSV</h1>
        
        <?php
        $csv_file = '../Store_Ready_42_Products_ShortDesc.csv';
        
        try {
            // Check if CSV file exists
            if (!file_exists($csv_file)) {
                throw new Exception("CSV file not found: $csv_file");
            }
            
            // Check if short description columns exist
            $check = $conn->query("SHOW COLUMNS FROM products LIKE 'short_description_%'");
            if ($check->num_rows < 2) {
                echo '<div class="warning">';
                echo '<strong>⚠️ Warning:</strong> Short description columns not found. Please run the migration first.';
                echo '<br><a href="add_short_descriptions.php">Run Migration Now</a>';
                echo '</div>';
                exit;
            }
            
            // Open and read CSV file
            $file = fopen($csv_file, 'r');
            if (!$file) {
                throw new Exception("Failed to open CSV file");
            }
            
            // Skip header row
            $header = fgetcsv($file);
            
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            echo '<div class="info">';
            echo '<strong>📊 Processing CSV file...</strong>';
            echo '</div>';
            
            echo '<table>';
            echo '<thead><tr><th>Product Name</th><th>Short Desc (AR)</th><th>Short Desc (EN)</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            
            // Read each row
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 4) continue;
                
                $product_name = trim($row[1]);
                $short_desc_ar = trim($row[2]);
                $short_desc_en = trim($row[3]);
                
                if (empty($product_name)) continue;
                
                // Find product by English name
                $stmt = $conn->prepare("SELECT id, name_en FROM products WHERE name_en LIKE ? LIMIT 1");
                $search_name = '%' . $product_name . '%';
                $stmt->bind_param('s', $search_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    // Update short descriptions
                    $update_stmt = $conn->prepare("UPDATE products SET short_description_ar = ?, short_description_en = ? WHERE id = ?");
                    $update_stmt->bind_param('ssi', $short_desc_ar, $short_desc_en, $product['id']);
                    
                    if ($update_stmt->execute()) {
                        echo '<tr style="background: #d4edda;">';
                        echo '<td>' . htmlspecialchars($product['name_en']) . '</td>';
                        echo '<td>' . htmlspecialchars($short_desc_ar) . '</td>';
                        echo '<td>' . htmlspecialchars($short_desc_en) . '</td>';
                        echo '<td>✅ Updated</td>';
                        echo '</tr>';
                        $updated++;
                    } else {
                        echo '<tr style="background: #f8d7da;">';
                        echo '<td>' . htmlspecialchars($product['name_en']) . '</td>';
                        echo '<td colspan="3">❌ Update failed</td>';
                        echo '</tr>';
                        $errors[] = $product['name_en'];
                    }
                    
                    $update_stmt->close();
                } else {
                    echo '<tr style="background: #fff3cd;">';
                    echo '<td>' . htmlspecialchars($product_name) . '</td>';
                    echo '<td colspan="3">⚠️ Product not found</td>';
                    echo '</tr>';
                    $skipped++;
                }
                
                $stmt->close();
            }
            
            echo '</tbody></table>';
            
            fclose($file);
            
            // Summary
            echo '<div class="success">';
            echo '<h3>📈 Import Summary:</h3>';
            echo '<ul>';
            echo '<li><strong>Updated:</strong> ' . $updated . ' products</li>';
            echo '<li><strong>Skipped:</strong> ' . $skipped . ' products (not found)</li>';
            echo '<li><strong>Errors:</strong> ' . count($errors) . ' products</li>';
            echo '</ul>';
            echo '</div>';
            
            if (count($errors) > 0) {
                echo '<div class="error">';
                echo '<strong>Failed products:</strong> ' . implode(', ', $errors);
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../pages/admin/products.php" style="text-decoration: none; color: white; background: #d4af37; padding: 10px 20px; border-radius: 5px; display: inline-block;">
                View Products
            </a>
            <a href="../index.php" style="text-decoration: none; color: #333; background: #e9ecef; padding: 10px 20px; border-radius: 5px; display: inline-block; margin-left: 10px;">
                Go to Home
            </a>
        </div>
    </div>
</body>
</html>
