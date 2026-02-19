<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imported Products Review</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            direction: ltr;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 0.9em;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .description {
            max-width: 400px;
            font-size: 0.85em;
            line-height: 1.4;
            color: #666;
        }
        
        .arabic {
            direction: rtl;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        }
        
        .price {
            font-weight: bold;
            color: #4caf50;
        }
        
        .stock {
            color: #ff9800;
            font-weight: 600;
        }
        
        .image-status {
            font-size: 0.85em;
        }
        
        .missing {
            color: #f44336;
        }
        
        .available {
            color: #4caf50;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Imported Products Review</h1>
        <p style="color: #666; margin-bottom: 20px;">All products successfully imported with bilingual descriptions</p>
        
        <?php
        require_once __DIR__ . '/includes/db_connect.php';
        
        // Get statistics
        $total_query = $conn->query("SELECT COUNT(*) as total FROM products");
        $total = $total_query->fetch_assoc()['total'];
        
        $with_images = $conn->query("SELECT COUNT(*) as count FROM products WHERE image_link IS NOT NULL AND image_link != ''")->fetch_assoc()['count'];
        
        $total_value = $conn->query("SELECT SUM(price_jod * stock_quantity) as value FROM products")->fetch_assoc()['value'];
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $with_images; ?></div>
                <div class="stat-label">With Image Links</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_value, 2); ?> JOD</div>
                <div class="stat-label">Total Inventory Value</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="download_images_guide.php" class="btn btn-primary">🖼️ Download Product Images</a>
            <a href="pages/admin/" class="btn btn-secondary">⚙️ Admin Panel</a>
            <a href="pages/shop/" class="btn btn-secondary">🛍️ View Shop</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Description (EN / AR)</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "
                    SELECT 
                        p.id,
                        p.name_en,
                        p.description,
                        p.price_jod,
                        p.stock_quantity,
                        p.image_link
                    FROM products p
                    ORDER BY p.id ASC
                ";
                
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()) {
                    // Extract English and Arabic descriptions
                    preg_match('/\*\*English:\*\* (.+?)\n\n\*\*العربية:\*\* (.+)/', $row['description'], $matches);
                    $desc_en = $matches[1] ?? 'N/A';
                    $desc_ar = $matches[2] ?? 'N/A';
                    
                    // Check if image file exists
                    $image_path = __DIR__ . '/' . $row['image_link'];
                    $image_exists = file_exists($image_path);
                    
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td><strong>{$row['name_en']}</strong></td>";

                    echo "<td class='description'>";
                    echo "<div><strong>EN:</strong> {$desc_en}</div>";
                    echo "<div class='arabic'><strong>AR:</strong> {$desc_ar}</div>";
                    echo "</td>";
                    echo "<td class='price'>" . number_format($row['price_jod'], 2) . " JOD</td>";
                    echo "<td class='stock'>{$row['stock_quantity']} units</td>";
                    echo "<td class='image-status'>";
                    if ($image_exists) {
                        echo "<span class='available'>✓ Available</span>";
                    } else {
                        echo "<span class='missing'>✗ Missing</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        
        <div class="action-buttons" style="margin-top: 40px;">
            <a href="download_images_guide.php" class="btn btn-primary">🖼️ Complete Image Download</a>
        </div>
    </div>
</body>
</html>
