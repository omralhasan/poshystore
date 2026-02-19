<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Status - Poshy Store</title>
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
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2em;
        }
        
        .status-box {
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .status-good {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        
        .status-info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        
        .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .explanation {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        
        .explanation h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .explanation ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 10px 10px 0;
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
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .preview-item p {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Product Images Status</h1>
        
        <?php
        require_once __DIR__ . '/includes/db_connect.php';
        
        // Count products and images
        $total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
        
        $images_dir = __DIR__ . '/images/products/';
        $files = glob($images_dir . '*');
        $total_files = count($files);
        
        $placeholder_count = 0;
        $real_images = 0;
        
        // Check how many are placeholders (SVG files)
        foreach ($files as $file) {
            if (strpos(file_get_contents($file), '<svg') !== false) {
                $placeholder_count++;
            } else {
                $real_images++;
            }
        }
        
        $percentage = $total_products > 0 ? round(($total_files / $total_products) * 100) : 0;
        ?>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?= $total_products ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $total_files ?></div>
                <div class="stat-label">Image Files</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $placeholder_count ?></div>
                <div class="stat-label">Placeholders</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $real_images ?></div>
                <div class="stat-label">Real Images</div>
            </div>
        </div>
        
        <?php if ($total_files >= $total_products): ?>
            <div class="status-box status-good">
                <div class="icon">✅</div>
                <h2>All Product Images Available!</h2>
                <p><strong><?= $total_files ?></strong> out of <strong><?= $total_products ?></strong> products have images.</p>
                <?php if ($placeholder_count > 0): ?>
                    <p style="margin-top: 10px;"><em>Note: <?= $placeholder_count ?> are using placeholder images. Download real product images for better display.</em></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="status-box status-warning">
                <div class="icon">⚠️</div>
                <h2>Some Images Missing</h2>
                <p><strong><?= $total_files ?></strong> out of <strong><?= $total_products ?></strong> products have images.</p>
                <p>Missing: <strong><?= $total_products - $total_files ?></strong> images</p>
            </div>
        <?php endif; ?>
        
        <div class="explanation">
            <h3>📋 What Happened?</h3>
            <ol>
                <li><strong>Products Imported:</strong> <?= $total_products ?> products were imported from your Excel sheet</li>
                <li><strong>Placeholder Images:</strong> <?= $placeholder_count ?> placeholder images were created automatically</li>
                <li><strong>Your Website Works:</strong> All products now display with images (placeholders for now)</li>
                <li><strong>Next Step:</strong> Download real product images to replace placeholders</li>
            </ol>
        </div>
        
        <div class="explanation">
            <h3>🎯 Why Use Placeholders?</h3>
            <p style="line-height: 1.8;">The Excel sheet contained <strong>Google Image Search links</strong> instead of direct image URLs. These links require manual download because:</p>
            <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
                <li>Google Image Search shows search results, not direct images</li>
                <li>Automated download requires selecting the right product image</li>
                <li>Manual download ensures you get the exact product picture</li>
            </ul>
        </div>
        
        <?php if ($placeholder_count > 0): ?>
        <div class="status-box status-info">
            <div class="icon">📥</div>
            <h3>Download Real Product Images</h3>
            <p>Replace placeholders with real product photos for better presentation:</p>
            <div style="margin-top: 15px;">
                <a href="download_images_guide.php" class="btn btn-primary">📸 Open Download Guide</a>
                <a href="index.php" class="btn btn-secondary">🏪 View Store</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="explanation">
            <h3>🚀 Current Status</h3>
            <p><strong>Your website is working!</strong> Visitors can:</p>
            <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
                <li>✅ Browse all <?= $total_products ?> products</li>
                <li>✅ See product names in English & Arabic</li>
                <li>✅ Read bilingual descriptions</li>
                <li>✅ Add products to cart</li>
                <li>✅ Complete purchases</li>
                <li>⚠️ Images show placeholders (replace with real photos)</li>
            </ul>
        </div>
        
        <h3 style="margin-top: 30px;">Sample Products with Current Images:</h3>
        <div class="preview">
            <?php
            $products = $conn->query("SELECT id, name_en, image_link FROM products LIMIT 8");
            while ($product = $products->fetch_assoc()):
                $img_path = $product['image_link'];
            ?>
            <div class="preview-item">
                <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($product['name_en']) ?>" onerror="this.src='images/placeholder-cosmetics.svg'">
                <p><?= htmlspecialchars(substr($product['name_en'], 0, 30)) ?>...</p>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="download_images_guide.php" class="btn btn-primary btn-success">📥 Download Real Images Now</a>
            <a href="index.php" class="btn btn-secondary">View Live Website</a>
        </div>
    </div>
</body>
</html>
