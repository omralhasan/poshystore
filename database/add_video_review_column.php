<?php
/**
 * Database Migration: Add video_review_url column to products table
 * This allows storing video URLs for "See in Action" product demonstration videos
 */

require_once '../includes/config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add See in Action Video Column Migration</title>
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
        <h1>🎥 See in Action Video Column Migration</h1>
        
        <?php
        try {
            // Check if column already exists
            $check = $conn->query("SHOW COLUMNS FROM products LIKE 'video_review_url'");
            
            if ($check->num_rows > 0) {
                echo '<div class="info">';
                echo '<strong>ℹ️ Info:</strong> The <code>video_review_url</code> column already exists in the products table.';
                echo '</div>';
            } else {
                // Add the column
                $sql = "ALTER TABLE products 
                        ADD COLUMN video_review_url VARCHAR(500) DEFAULT NULL 
                        AFTER how_to_use";
                
                if ($conn->query($sql)) {
                    echo '<div class="success">';
                    echo '<strong>✅ Success!</strong> The <code>video_review_url</code> column has been added to the products table.';
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
            echo '<h3>📝 How to Add See in Action Videos:</h3>';
            echo '<ol>';
            echo '<li>Go to the admin panel and edit a product</li>';
            echo '<li>Enter the video URL in the "See in Action Video URL" field</li>';
            echo '<li>Supported formats: YouTube embed URLs, Vimeo, or direct video URLs</li>';
            echo '<li>Example YouTube embed: <code>https://www.youtube.com/embed/VIDEO_ID</code></li>';
            echo '<li>The video will automatically appear in the first tab "See in Action" on the product page</li>';
            echo '</ol>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../pages/admin/products.php" style="text-decoration: none; color: white; background: #d4af37; padding: 10px 20px; border-radius: 5px; display: inline-block;">
                Go to Admin Products
            </a>
            <a href="../index.php" style="text-decoration: none; color: #333; background: #e9ecef; padding: 10px 20px; border-radius: 5px; display: inline-block; margin-left: 10px;">
                Go to Home
            </a>
        </div>
    </div>
</body>
</html>
